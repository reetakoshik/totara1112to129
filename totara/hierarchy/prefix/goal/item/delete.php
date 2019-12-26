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
 * @author David Curry <david.curry@totaralms.com>
 * @package totara
 * @subpackage totara_hierarchy
 */

require_once(__DIR__ . '/../../../../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot . '/totara/hierarchy/prefix/goal/lib.php');

// Check if Goals are enabled.
goal::check_feature_enabled();

$goalpersonalid = required_param('goalpersonalid', PARAM_INT);
$delete = optional_param('del', '', PARAM_ALPHANUM);

// Check permissions before we do anything.
$goal = new goal();
$goalpersonal = goal::get_goal_item(array('id' => $goalpersonalid), goal::SCOPE_PERSONAL);
if (empty($goalpersonal)) {
    print_error('error:goalnotfound', 'totara_hierarchy');
}

if (!$permissions = $goal->get_permissions(null, $goalpersonal->userid)) {
    // Error setting up page permissions.
    print_error('error:viewusergoals', 'totara_hierarchy');
}

extract($permissions);
if (!$can_edit[$goalpersonal->assigntype]) {
    print_error('error:deleteusergoals', 'totara_hierarchy');
}

$strdelgoals = get_string('removegoal', 'totara_hierarchy');
$ret_url = new moodle_url("/totara/hierarchy/prefix/goal/mygoals.php", array('userid' => $goalpersonal->userid));

// Set up the page.
$context = context_user::instance($goalpersonal->userid);
$urlparams = array('goalpersonalid' => $goalpersonalid);
$PAGE->set_url(new moodle_url('/totara/hierarchy/prefix/goal/item/delete.php'), $urlparams);
$PAGE->set_context($context);
$PAGE->set_pagelayout('admin');
$PAGE->set_totara_menu_selected('\totara_hierarchy\totara\menu\mygoals');
$PAGE->set_title($strdelgoals);
$PAGE->set_heading($strdelgoals);

if ($delete) {
    // Delete.

    if ($delete != md5($goalpersonal->timemodified)) {
        print_error('error:deletetypecheckvariable', 'totara_hierarchy');
    }

    if (!confirm_sesskey()) {
        print_error('confirmsesskeybad', 'error');
    }

    // Do the deletion.
    if (goal::delete_goal_item(array('id' => $goalpersonalid), goal::SCOPE_PERSONAL)) {
        $success = get_string('deletedpersonalgoal', 'totara_hierarchy', format_string($goalpersonal->name));

        \hierarchy_goal\event\personal_deleted::create_from_instance($goalpersonal)->trigger();

        totara_set_notification($success, $ret_url, array('class' => 'notifysuccess'));
    } else {
        // Failure.
        $error = get_string('error:deletepersonalgoal', 'totara_hierarchy', format_string($goalpersonal->name));
        totara_set_notification($error, $ret_url);
    }
}

// Display confirmation.
$PAGE->navbar->add(get_string('goals', 'totara_hierarchy'),
    new moodle_url('/totara/hierarchy/item/prefix/goal/mygoals.php', array('userid' => $goalpersonal->userid)));
$PAGE->navbar->add(format_string($goalpersonal->name),
    new moodle_url('/totara/hierarchy/prefix/goal/item/view.php', array('goalpersonalid' => $goalpersonalid)));
$PAGE->navbar->add(get_string('deletegoal', 'totara_hierarchy'));

echo $OUTPUT->header();

$strvars = new stdClass();
$strvars->goalname = $goalpersonal->name;
$strvars->username = fullname($DB->get_record('user', array('id' => $goalpersonal->userid)));

$strdelete = get_string('confirmpersonaldelete', 'totara_hierarchy', $strvars);

$del_params = array('goalpersonalid' => $goalpersonalid, 'del' => md5($goalpersonal->timemodified),
        'sesskey' => $USER->sesskey);
$del_url = new moodle_url("/totara/hierarchy/prefix/goal/item/delete.php", $del_params);

echo $OUTPUT->confirm($strdelete, $del_url, $ret_url);

echo $OUTPUT->footer();
