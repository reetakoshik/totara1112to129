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
 * @author James Robinson <jamesr@learningpool.com>
 * @author Ryan Lafferty <ryanl@learningpool.com>
 * @package totara
 * @subpackage totara_hierarchy
 */

require_once(__DIR__ . '/../../../../../config.php');
require_once($CFG->dirroot . '/totara/customfield/fieldlib.php');
require_once($CFG->dirroot . '/totara/hierarchy/prefix/goal/item/edit_form.php');
require_once($CFG->dirroot . '/totara/hierarchy/prefix/goal/lib.php');

// Check if Goals are enabled.
goal::check_feature_enabled();

$id = optional_param('id', 0, PARAM_INT);

require_login();

if (!empty($id)) {
    $goalpersonal = goal::get_goal_item(array('id' => $id), goal::SCOPE_PERSONAL);
    $userid = $goalpersonal->userid;
} else {
    $goalpersonal = new stdClass();
    $userid = optional_param('userid', $USER->id, PARAM_INT);
}

$goal = new goal();
if (!$permissions = $goal->get_permissions(null, $userid)) {
    // Error setting up page permissions.
    print_error('error:viewusergoals', 'totara_hierarchy');
}

extract($permissions);

$strmygoals = get_string('goals', 'totara_hierarchy');
$mygoalsurl = new moodle_url('/totara/hierarchy/prefix/goal/mygoals.php', array('userid' => $userid));
$pageurl = new moodle_url('/totara/hierarchy/prefix/goal/item/edit_personal.php', array('userid' => $userid));

$context = context_user::instance($userid);
$PAGE->set_context($context);

if (!empty($id)) {
    $goalname = format_string($goalpersonal->name);

    // Check the specific permissions for this goal.
    if (!$can_edit[$goalpersonal->assigntype]) {
        print_error('error:editgoals', 'totara_hierarchy');
    }
} else {
    $goalpersonal->userid = $userid;
    $goalname = get_string('addgoalpersonal', 'totara_hierarchy');

    // Check they have generic permissions to create a personal goal for this user.
    if (!$can_edit[GOAL_ASSIGNMENT_SELF] && !$can_edit[GOAL_ASSIGNMENT_MANAGER] && !$can_edit[GOAL_ASSIGNMENT_ADMIN]) {
        print_error('error:createpersonalgoal', 'totara_hierarchy');
    }
}

// Set up the page.
$PAGE->navbar->add($strmygoals, $mygoalsurl);
$PAGE->navbar->add($goalname);
$PAGE->set_url($pageurl);
$PAGE->set_pagelayout('admin');
$PAGE->set_totara_menu_selected('\totara_hierarchy\totara\menu\mygoals');
$PAGE->set_title($strmygoals);
$PAGE->set_heading($strmygoals);

$prefix = 'goal_user';

if ($id === 0) {
    $item = new stdClass();
    $item->id = 0;
    $item->description = $DB->get_field('goal_personal', 'description', array('id' => $id));
    $item->visible = 1;
    $item->typeid = $DB->get_field('goal_personal', 'typeid', array('id' => $id));

} else {
    $item = $DB->get_record('goal_personal', array('id' => $id), '*', MUST_EXIST);

    // Load custom fields data - customfield values need to be available in $item before the call to set_data.
    if ($id !== 0) {
        customfield_load_data($item, $prefix, 'goal_user');
    }
}

// Display page.
// Create form.
$item->descriptionformat = FORMAT_HTML;
$options = array(
    'subdirs' => 0,
    'maxfiles' => EDITOR_UNLIMITED_FILES,
    'maxbytes' => get_max_upload_file_size(),
    'trusttext' => false,
    'context' => $context,
    'collapsed' => true
);

$item = file_prepare_standard_editor($item, 'description', $options, $options['context'], 'totara_hierarchy','goal', $item->id);

$datatosend = array('item' => $item, 'id' => $id, 'userid' => $userid);
$mform = new goal_edit_personal_form(null, $datatosend);
$mform->set_data($item);

// Handle the form.
if ($mform->is_cancelled()) {
    // Cancelled.
    redirect("{$CFG->wwwroot}/totara/hierarchy/prefix/goal/mygoals.php?userid={$userid}");
} else if ($fromform = $mform->get_data()) {
    // Update data.
    $todb = new stdClass();
    $todb->userid = $fromform->userid;
    $todb->scaleid = $fromform->scaleid;
    $todb->typeid = $fromform->typeid;
    $todb->name = $fromform->name;
    $todb->usermodified = $USER->id;
    $todb->timemodified = time();
    if (isset($fromform->targetdate)) {
        if (empty($fromform->targetdate)) {
            $todb->targetdate = 0;
        } else {
            $todb->targetdate = $fromform->targetdate;
        }
    }

    $existingrecord = null;
    if ($fromform->id !== 0) {
        $existingrecord = goal::get_goal_item(array('id' => $fromform->id), goal::SCOPE_PERSONAL);
    }

    if (isset($existingrecord)) {
        // Handle updates.

        // Set the existing goal id.
        $todb->id = $fromform->id;

        // If the scale changes then set the current scale value to default.
        if ($todb->scaleid != $existingrecord->scaleid) {
            $todb->scalevalueid = $DB->get_field('goal_scale', 'defaultid', array('id' => $todb->scaleid));
        }

        $fromform = file_postupdate_standard_editor($fromform, 'description', $TEXTAREA_OPTIONS, $context,
            'totara_hierarchy', 'goal', $fromform->id);
        $todb->description = $fromform->description;

        customfield_save_data($fromform, $prefix, 'goal_user');

        // Update the record.
        goal::update_goal_item($todb, goal::SCOPE_PERSONAL);

        $instance = $DB->get_record('goal_personal', array('id' => $todb->id));
        \hierarchy_goal\event\personal_updated::create_from_instance($instance)->trigger();
    } else {
        // Handle creating a new goal.

        // Set the assignment type self/manager/admin.
        if ($USER->id == $todb->userid && $can_edit[GOAL_ASSIGNMENT_SELF]) {
            // They are assigning it to themselves.
            $todb->assigntype = GOAL_ASSIGNMENT_SELF;
        } else if (\totara_job\job_assignment::is_managing($USER->id, $todb->userid) && $can_edit[GOAL_ASSIGNMENT_MANAGER]) {
            // They are assigning it to their team.
            $todb->assigntype = GOAL_ASSIGNMENT_MANAGER;
        } else if ($can_edit[GOAL_ASSIGNMENT_ADMIN]) {
            // Last option, they are an admin assigning it to someone.
            $todb->assigntype = GOAL_ASSIGNMENT_ADMIN;
        } else {
            print_error('error:createpersonalgoal', 'totara_hierarchy');
        }

        // Set the user/time created.
        $todb->usercreated = $USER->id;
        $todb->timecreated = time();

        // Set the current scale value to default.
        $todb->scalevalueid = $DB->get_field('goal_scale', 'defaultid', array('id' => $todb->scaleid));

        // Set the goal type id.
        $todb->typeid = $fromform->typeid;

        // Insert the record.
        $todb->id = goal::insert_goal_item($todb, goal::SCOPE_PERSONAL);

        // We need to know the new id before we can process the editor and save the description.
        $fromform = file_postupdate_standard_editor($fromform, 'description', $TEXTAREA_OPTIONS, $context,
            'totara_hierarchy', 'goal', $todb->id);
        $DB->set_field('goal_personal', 'description', $fromform->description, array('id' => $todb->id));

        $instance = $DB->get_record('goal_personal', array('id' => $todb->id));
        \hierarchy_goal\event\personal_created::create_from_instance($instance)->trigger();
    }

    redirect("{$CFG->wwwroot}/totara/hierarchy/prefix/goal/mygoals.php?userid={$todb->userid}");
}

// Display the page and form.
echo $OUTPUT->header();

if ($id) {
    echo $OUTPUT->heading(get_string("editpersonalgoal", 'totara_hierarchy') . $item->name);
} else {
    echo $OUTPUT->heading(get_string("newpersonalgoal", 'totara_hierarchy'));
}

$mform->display();

echo $OUTPUT->footer();
