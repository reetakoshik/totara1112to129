<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2010 onwards Totara Learning Solutions LTD
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Aaron Wells <aaronw@catalyst.net.nz>
 * @author Russell England <russell.england@totaralms.com>
 * @author Simon Player <simon.player@totaralms.com>
 * @package totara
 * @subpackage plan
 */

/**
 * Edit evidence
 */

require_once(__DIR__ . '/../../../../config.php');
require_once($CFG->dirroot . '/totara/plan/lib.php');
require_once($CFG->dirroot . '/totara/core/js/lib/setup.php');
require_once('edit_form.php');
require_once('lib.php');

require_login();

if (totara_feature_disabled('recordoflearning')) {
    print_error('error:recordoflearningdisabled', 'totara_plan');
}

$userid = optional_param('userid', $USER->id, PARAM_INT);
$evidenceid = optional_param('id', 0, PARAM_INT);
$deleteflag = optional_param('d', false, PARAM_BOOL);
$deleteconfirmed = optional_param('delete', false, PARAM_BOOL);

$systemcontext = context_system::instance();
$PAGE->set_context($systemcontext);
$PAGE->set_pagelayout('report');
$PAGE->set_url('/totara/plan/record/evidence/edit.php',
        array('id' => $evidenceid, 'userid' => $userid));

if (!$user = $DB->get_record('user', array('id' => $userid))) {
    print_error('error:usernotfound', 'totara_plan');
}

if (!empty($evidenceid)) {
    // Editing or deleting, check record exists
    if (!$item = $DB->get_record('dp_plan_evidence', array('id' => $evidenceid))) {
        print_error('error:evidenceidincorrect', 'totara_plan');
    } else {
        // Check if its readonly
        if ($item->readonly && !can_create_or_edit_evidence($userid, true, true)) {
            print_error('evidence_readonly', 'totara_plan');
        }
        // Check that the user owns this evidence
        $userid = $item->userid;
    }
}

if (!can_create_or_edit_evidence($userid, !empty($evidenceid))) {
    print_error('error:cannotviewpage', 'totara_plan');
}

if ($USER->id == $userid) {
    // Own evidence
    $strheading = get_string('recordoflearning', 'totara_core');
    $usertype = 'learner';
} else {
    // Admin / manager
    $strheading = get_string('recordoflearningforname', 'totara_core', fullname($user, true));
    $usertype = 'manager';
}

$indexurl = new moodle_url('/totara/plan/record/evidence/index.php', array('userid' => $userid));

if (!empty($evidenceid) || $deleteflag) {
    if ($deleteflag) {
        $action = 'delete';
    } else {
        $action = 'edit';
    }
    $itemurl = new moodle_url('/totara/plan/record/evidence/view.php', array('id' => $evidenceid));

    // load custom fields data - customfield values need to be available in $item before the call to set_data
    customfield_load_data($item, 'evidence', 'dp_plan_evidence');
} else {
    // New evidence, initialise values
    $item = new stdClass();
    $item->id = 0;
    $item->name = '';
    $item->description = '';
    $item->evidencetypeid = null;
    $action = 'add';
    $itemurl = $indexurl;
}

if ($deleteflag && $deleteconfirmed) {
    // Deletion confirmed.
    require_sesskey();

    // TODO: trigger evidence unlinked events, see T-14190.
    /*
    $sql = "SELECT p.id, p.name
              FROM {dp_plan} p
              JOIN {dp_plan_evidence_relation} er ON er.planid = p.id
             WHERE er.evidenceid = :evidenceid";
    $plans = $DB->get_records_sql($sql, array('evidenceid' => $item->id));
    */

    evidence_delete($item->id);

    totara_set_notification(get_string('evidencedeleted', 'totara_plan'),
        $indexurl, array('class' => 'notifysuccess'));
}

$mform = new plan_evidence_edit_form(
    null,
    array(
        'id' => $item->id,
        'userid' => $userid,
        'item' => $item
    )
);
$mform->set_data($item);

if ($data = $mform->get_data()) {
    $data->timemodified = time();
    $data->userid = $userid;

    if (empty($data->id)) {
        // Create a new record.
        $data->timecreated = $data->timemodified;
        $data->usermodified = $USER->id;
        $data->planid = 0;
        $data->id = $DB->insert_record('dp_plan_evidence', $data);

        // Add the items custom fields.
        customfield_save_data($data, 'evidence', 'dp_plan_evidence');

        $item = $DB->get_record('dp_plan_evidence', array('id' => $data->id), '*', MUST_EXIST);
        \totara_plan\event\evidence_created::create_from_instance($item)->trigger();

        totara_set_notification(get_string('evidenceadded', 'totara_plan'), $itemurl, array('class' => 'notifysuccess'));

    } else {
        // Update a record.
        $DB->update_record('dp_plan_evidence', $data);

        // Update the items custom fields.
        customfield_save_data($data, 'evidence', 'dp_plan_evidence');

        $item = $DB->get_record('dp_plan_evidence', array('id' => $data->id), '*', MUST_EXIST);
        \totara_plan\event\evidence_updated::create_from_instance($item)->trigger();

        totara_set_notification(get_string('evidenceupdated', 'totara_plan'), $itemurl, array('class' => 'notifysuccess'));
    }

} else if ($mform->is_cancelled()) {
    if ($action == 'add') {
        redirect($indexurl);
    } else {
        redirect($itemurl);
    }
}
if ($usertype == 'manager') {
    if (totara_feature_visible('myteam')) {
        $menuitem = 'myteam';
        $url = new moodle_url('/my/teammembers.php');
        $PAGE->navbar->add(get_string('team', 'totara_core'), $url);
    } else {
        $menuitem = null;
        $url = null;
    }
} else {
    $menuitem = null;
    $url = null;
}
$PAGE->navbar->add($strheading, new moodle_url('/totara/plan/record/index.php', array('userid' => $userid)));
$PAGE->navbar->add(get_string('allevidence', 'totara_plan'), new moodle_url('/totara/plan/record/evidence/index.php', array('userid' => $userid)));
$PAGE->navbar->add(get_string($action . 'evidence', 'totara_plan'));
$PAGE->set_title($strheading);
$PAGE->set_heading(format_string($SITE->fullname));
dp_display_plans_menu($userid, 0, $usertype, 'evidence/index', 'none', false);

echo $OUTPUT->header();


echo $OUTPUT->container_start('', 'dp-plan-content');

echo $OUTPUT->heading($strheading);

dp_print_rol_tabs(null, 'evidence', $userid);

switch($action){
    case 'add':
    case 'edit':
        echo $OUTPUT->heading(get_string($action . 'evidence', 'totara_plan'));
        $mform->display();
        break;

    case 'delete':
        echo $OUTPUT->heading(get_string($action . 'evidence', 'totara_plan'));
        echo display_evidence_detail($item->id, true);
        $params = array('id' => $item->id, 'userid'=>$userid, 'd' => '1', 'delete' => '1', 'sesskey' => sesskey());
        $deleteurl = new moodle_url('/totara/plan/record/evidence/edit.php', $params);
        echo list_evidence_in_use($item->id);
        echo $OUTPUT->confirm(get_string('deleteevidenceareyousure', 'totara_plan'), $deleteurl, $indexurl);
        break;
}

echo $OUTPUT->container_end();
echo $OUTPUT->footer();
