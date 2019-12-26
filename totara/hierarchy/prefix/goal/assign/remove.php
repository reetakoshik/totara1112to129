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
 * @author Simon Coggins <simon.coggins@totaralms.com>
 * @author David Curry <david.curry@totaralms.com>
 * @package totara
 * @subpackage totara_hierarchy
 */

require_once(__DIR__ . '/../../../../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/totara/hierarchy/prefix/goal/lib.php');

// Setup / loading data.
$goalid         = required_param('goalid', PARAM_INT);
$assigntype     = required_param('assigntype', PARAM_INT);
$modid          = required_param('modid', PARAM_INT);

require_login();

// Check if Goals are enabled.
goal::check_feature_enabled();

// Delete confirmation hash.
$delete = optional_param('delete', '', PARAM_ALPHANUM);

// Return to Goal or Mod_view.
$view_type = optional_param('view', false, PARAM_BOOL);

$strdelgoals = get_string('removegoal', 'totara_hierarchy');
$sitecontext = context_system::instance();

// Set up the page.
// String of params needed in non-js url strings.
$urlparams = array('goalid' => $goalid,
                   'assigntype' => $assigntype,
                   'modid' => $modid
                  );

// Set up the page.
$PAGE->set_url(new moodle_url('/totara/hierarchy/prefix/goal/assign/remove.php'), $urlparams);
$PAGE->set_context($sitecontext);
$PAGE->set_pagelayout('admin');
$PAGE->set_totara_menu_selected('\totara_hierarchy\totara\menu\mygoals');
$PAGE->set_title($strdelgoals);
$PAGE->set_heading($strdelgoals);


// Set up some variables.
$type = goal::goal_assignment_type_info($assigntype, $goalid, $modid);
$strassig = format_string($type->goalname) . ' - ' . format_string($type->modname);

// You must have some form of managegoals permission to see this page.
$admin = has_capability('totara/hierarchy:managegoalassignments', $sitecontext);
$manager = false;
$self = false;

$confirm_url_attrs = [];

if ($assigntype == GOAL_ASSIGNMENT_INDIVIDUAL) {
    $user_context = context_user::instance($modid);
    $manager = \totara_job\job_assignment::is_managing($USER->id, $modid) && has_capability('totara/hierarchy:managestaffcompanygoal', $user_context);
    $self = ($USER->id == $modid) && has_capability('totara/hierarchy:manageowncompanygoal', $user_context);

    // We require to pass a user assignment id attribute for individual assignments now to uniquely identify which
    // assignment is being deleted.

    $user_assignment_id = required_param('assignment_id', PARAM_INT);
    $confirm_url_attrs['assignment_id'] = $user_assignment_id;
}

if (!($admin || $manager || $self)) {
    print_error('error:deletegoalassignment', 'totara_hierarchy');
}

if ($view_type) {
    // If the flag is set, return to the goal item page.
    $returnurl = new moodle_url('/totara/hierarchy/item/view.php', array('prefix' => 'goal', 'id' => $goalid));
} else if ($assigntype == GOAL_ASSIGNMENT_POSITION || $assigntype == GOAL_ASSIGNMENT_ORGANISATION) {
    // Return to viewing the hierarchy item.
    $returnurl = new moodle_url('/totara/hierarchy/item/view.php', array('prefix' => $type->fullname, 'id' => $modid));
} else if ($assigntype == GOAL_ASSIGNMENT_AUDIENCE) {
    // Return to the audiences goal tab.
    $returnurl = new moodle_url('/totara/cohort/goals.php', array('id' => $modid));
} else {
    // Return to the users my goals page.
    $returnurl = new moodle_url('/totara/hierarchy/prefix/goal/mygoals.php', array('userid' => $modid));
}

$confirm_url_attrs = array_merge($confirm_url_attrs, [
    'goalid' => $goalid,
    'assigntype' => $assigntype,
    'modid' => $modid,
    'view' => $view_type,
    'delete' => md5($type->timecreated),
    'sesskey' => $USER->sesskey
]);

$deleteurl = new moodle_url('/totara/hierarchy/prefix/goal/assign/remove.php', $confirm_url_attrs);

if ($delete) {
    // Delete.
    if ($delete != md5($type->timecreated)) {
        print_error('error:checkvariable', 'totara_hierarchy');
    }

    require_sesskey();

    $delete_params = array($type->field => $modid);

    if ($type->companygoal) {
        $delete_params['goalid'] = $goalid;
    } else {
        $delete_params['id'] = $goalid;
    }

    if ($assigntype == GOAL_ASSIGNMENT_INDIVIDUAL) {
        $delete_params['assigntype'] = GOAL_ASSIGNMENT_INDIVIDUAL;
        $delete_params['assignmentid'] = 0;
        $delete_params['id'] = $user_assignment_id;
        $snapshot = $DB->get_record($type->table, $delete_params);
        goal::delete_user_assignments($delete_params);
        $eventclass = "\\hierarchy_goal\\event\\assignment_user_deleted";
    } else {
        // If it's not an individual assignment delete/transfer user assignments.
        $assignmentid = $DB->get_field($type->table, 'id', $delete_params);
        $snapshot = $DB->get_record($type->table, $delete_params);
        goal::delete_group_assignment($assigntype, $assignmentid, $type, $delete_params);
        $eventclass = "\\hierarchy_goal\\event\\assignment_{$type->fullname}_deleted";
    }

    $eventclass::create_from_instance($snapshot)->trigger();

    totara_set_notification(get_string('goaldeletedassignment', 'totara_hierarchy'), $returnurl,
            array('class' => 'notifysuccess'));
} else {
    // Display page.
    echo $OUTPUT->header();
    $strdelete = get_string('goalassigndeletecheck', 'totara_hierarchy');

    echo $OUTPUT->confirm($strdelete . html_writer::empty_tag('br') . html_writer::empty_tag('br') . $strassig,
            $deleteurl, $returnurl);

    echo $OUTPUT->footer();
}
