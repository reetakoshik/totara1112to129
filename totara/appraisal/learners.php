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
 * @author Ciaran Irvine <ciaran.irvine@totaralms.com>
 * @package totara
 * @subpackage appraisal
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot . '/totara/appraisal/lib.php');
require_once($CFG->dirroot.'/totara/appraisal/lib/assign/lib.php');
require_once($CFG->dirroot . '/totara/core/js/lib/setup.php');

// Check if Appraisals are enabled.
appraisal::check_feature_enabled();

// Get the appraisal id.
$appraisalid = required_param('appraisalid', PARAM_INT);
$update = optional_param('update', false, PARAM_BOOL);
$module = 'appraisal';
$appraisal = new appraisal($appraisalid);
$assign = new totara_assign_appraisal($module, $appraisal);

// Capability checks.
$systemcontext = context_system::instance();
$canassign = has_capability('totara/appraisal:assignappraisaltogroup', $systemcontext);
$canviewusers = has_capability('totara/appraisal:viewassignedusers', $systemcontext);

admin_externalpage_setup('manageappraisals');
$title = $PAGE->title . ': ' . $appraisal->name;
$PAGE->set_title($title);
$PAGE->set_heading($appraisal->name);
$PAGE->navbar->add($appraisal->name);
/** @var totara_appraisal_renderer $output */
$output = $PAGE->get_renderer('totara_appraisal');

$grouptypes = $assign->get_assignable_grouptype_names();
$returnparams = array('appraisalid' => $appraisalid);
$returnurl = new moodle_url('/totara/appraisal/learners.php', $returnparams);

$deleteid = optional_param('deleteid', null, PARAM_ALPHANUMEXT);
$confirm = optional_param('confirm', false, PARAM_BOOL);
if ($deleteid && $canassign && (!appraisal::is_closed($appraisalid))) {
    list($grp, $aid) = explode("_", $deleteid);

    // Do not allow changes to active appraisals unless dynamic appraisals is enabled.
    if (empty($CFG->dynamicappraisals) && appraisal::is_active($appraisalid)) {
        print_error('error:attemptupdatestatic', 'totara_appraisal');
    }

    if (appraisal::is_active($appraisalid) && !$confirm) {

        $deleteparams = array('appraisalid' => $appraisalid, 'deleteid' => $deleteid, 'confirm' => true, 'sesskey' => sesskey());
        $deleteurl = new moodle_url('/totara/appraisal/learners.php', $deleteparams);

        $confirmparams = new stdClass();
        $confirmparams->grouptype = $grouptypes[$grp];
        $confirmparams->groupname = $assign->get_group_instance_name($grp, $aid);
        $confirmparams->appraisalname = $appraisal->name;
        $confirmstr = get_string('confirmdeletegroup', 'totara_appraisal', $confirmparams);

        echo $output->header();
        echo $output->confirm($confirmstr, $deleteurl, $returnurl);
        echo $output->footer();

        exit();
    } else {
        if (!confirm_sesskey()) {
            print_error('confirmsesskeybad', 'error');
        }
        $assign->delete_assigned_group($grp, $aid);
        redirect($returnurl);
    }
}

if ($update && $canassign) {
    if (!confirm_sesskey()) {
        print_error('confirmsesskeybad', 'error');
    }

    // Do not allow changes to active appraisals unless dynamic appraisals is enabled.
    if (empty($CFG->dynamicappraisals) && appraisal::is_active($appraisalid)) {
        print_error('error:attemptupdatestatic', 'totara_appraisal');
    }

    $appraisal->check_assignment_changes();
    redirect($returnurl);
}

$notlivenotice = $output->display_notlive_notice($appraisalid, $canassign);

// Setup the JS.
totara_setup_assigndialogs($module, $appraisalid, $canviewusers, $notlivenotice);
echo $output->header();
if ($appraisal->id) {
    echo $output->heading(format_string($appraisal->name));
    echo $output->appraisal_additional_actions($appraisal->status, $appraisal->id);
}

if (!empty($CFG->dynamicappraisals) && $appraisal->status == appraisal::STATUS_ACTIVE) {
    $warnings = $appraisal->validate_roles();
    echo $output->display_learner_warnings($appraisal->id, $warnings, $canviewusers);
}

echo $output->appraisal_management_tabs($appraisal->id, 'learners');
if (!appraisal::is_closed($appraisalid)) {
    echo $output->heading(get_string('assigncurrentgroups', 'totara_appraisal'), 3);
}

if ($canassign) {
    if ($appraisal->status == appraisal::STATUS_CLOSED) {
        echo get_string('appraisalclosednochangesallowed', 'totara_appraisal');
    } else if (empty($CFG->dynamicappraisals) && $appraisal->status == appraisal::STATUS_ACTIVE) {
        echo get_string('appraisalactivenochangesallowed', 'totara_appraisal');
    } else {
        // If the hierarchy positions feature is disabled then remove it from the Assign User Group menu.
        if (totara_feature_disabled('positions') && array_key_exists('pos', $grouptypes)) {
            unset($grouptypes['pos']);
        }

        $options = array_merge(array("" => get_string('assigngroup', 'totara_core')),
                $grouptypes);
        echo html_writer::select($options, 'groupselector', null, null,
                array('class' => 'group_selector', 'itemid' => $appraisalid));
    }
}

if (!appraisal::is_closed($appraisalid)) {
    $currentassignments = $assign->get_current_assigned_groups();
    echo $output->display_assigned_groups($currentassignments, $appraisalid);

    echo $output->heading(get_string('assigncurrentusers', 'totara_appraisal'), 3);

    // If the appraisal is active notify the user that changes are not live.
    if ($appraisal->status == appraisal::STATUS_ACTIVE) {
        $userassignments = $assign->get_current_users();
        $groupassignments = $assign->get_current_users(null, null, null, true);
        $differences = $appraisal->compare_assignments($userassignments, $groupassignments);
        echo html_writer::start_tag('div', array('id' => 'notlivenotice'));
        if (!empty($CFG->dynamicappraisals) && $differences) {
            echo $notlivenotice;
        }
        echo html_writer::end_tag('div');
    }
} else {
    echo $output->heading(get_string('assigncompletedusers', 'totara_appraisal'), 3);
}

if ($canviewusers) {
    $canunlockstages = has_capability('totara/appraisal:unlockstages', $systemcontext);
    echo $output->display_user_datatable(
        !appraisal::is_closed($appraisalid),
        $canunlockstages && $appraisal->get()->status == $appraisal::STATUS_ACTIVE
    );
}


echo $output->footer();
