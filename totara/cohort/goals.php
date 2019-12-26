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
 * @author Alastair Munro <alastair.munro@totaralms.com>
 * @author David Curry <david.curry@totaralms.com>
 * @package totara
 * @subpackage cohort
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/totara/cohort/cohort_forms.php');
require_once($CFG->dirroot . '/cohort/lib.php');
require_once($CFG->dirroot . '/totara/core/js/lib/setup.php');
require_once($CFG->dirroot . '/totara/hierarchy/prefix/goal/lib.php');

require_login();

// Check if Goals are enabled.
goal::check_feature_enabled();

$id = required_param('id', PARAM_INT);
$cohort = $DB->get_record('cohort', array('id' => $id), '*', MUST_EXIST);
$context = context::instance_by_id($cohort->contextid, MUST_EXIST);
$PAGE->set_context($context);

require_capability('moodle/cohort:view', $context);
$can_edit = has_capability('totara/hierarchy:managegoalassignments', $context)
    && has_capability('moodle/cohort:manage', $context);

// Raise timelimit as this could take a while for big cohorts.
core_php_time_limit::raise(0);
raise_memory_limit(MEMORY_HUGE);

define('COHORT_HISTORY_PER_PAGE', 50);

if ($context->contextlevel == CONTEXT_COURSECAT) {
    $category = $DB->get_record('course_categories', array('id' => $context->instanceid), '*', MUST_EXIST);
    navigation_node::override_active_url(new moodle_url('/cohort/index.php', array('contextid' => $cohort->contextid)));
} else {
    navigation_node::override_active_url(new moodle_url('/cohort/index.php', array()));
}

if ($context->contextlevel == CONTEXT_SYSTEM) {
    admin_externalpage_setup('cohorts');
} else {
    $PAGE->set_url('/totara/cohort/goals.php', array('id' => $id));
    $PAGE->set_heading($COURSE->fullname);
    $PAGE->set_title($cohort->name . ' : ' . get_string('goals', 'totara_cohort'));
    $PAGE->set_pagelayout('report');
}

// Javascript include.
local_js(
    array(
        TOTARA_JS_DIALOG,
        TOTARA_JS_TREEVIEW,
    )
);

$PAGE->requires->strings_for_js(array('addgoal', 'assigngoals'), 'totara_hierarchy');
$PAGE->requires->strings_for_js(array('continue', 'cancel'), 'moodle');
$args = array('args' => '{"id":"' . $cohort->id . '",'
                       . '"sesskey":"' . sesskey() . '"}');
$jsmodule = array(
        'name' => 'totara_cohort',
        'fullpath' => '/totara/core/js/cohort.js',
        'requires' => array('json', 'totara_core'));
$PAGE->requires->js_init_call('M.totara_cohort.init',
    $args, false, $jsmodule);

$strheading = get_string('goals', 'totara_cohort');
totara_cohort_navlinks($cohort->id, format_string($cohort->name), $strheading);

echo $OUTPUT->header();

echo $OUTPUT->heading(format_string($cohort->name));
echo cohort_print_tabs('goals', $cohort->id, $cohort->cohorttype, $cohort);

// Goals header.
echo $OUTPUT->heading(get_string('goalsassigned', 'totara_hierarchy'), 3);

// Add goal(s) button.
$button_form = '';
if ($can_edit) {
    // Needs to be done manually (not with single_button) to get correct ID on input button element.
    $add_button_text = get_string('addgoal', 'totara_hierarchy');
    $add_goal_url = new moodle_url('/totara/hierarchy/prefix/goal/assign/find.php', array('assignto' => $cohort->id));
    $button_form .= html_writer::start_tag('div', array('class' => 'buttons'));
    $button_form .= html_writer::start_tag('div', array('class' => 'singlebutton'));
    $button_form .= html_writer::start_tag('form', array('action' => $add_goal_url, 'method' => 'get'));
    $button_form .= html_writer::start_tag('div');
    $button_form .= html_writer::empty_tag('input', array('type' => 'submit',
        'id' => "show-assignedgoals-dialog", 'value' => $add_button_text));
    $button_form .= html_writer::empty_tag('input', array('type' => 'hidden',
        'name' => "assignto", 'value' => $cohort->id));
    $button_form .= html_writer::empty_tag('input', array('type' => 'hidden',
        'name' => "assigntype", 'value' => GOAL_ASSIGNMENT_AUDIENCE));
    $button_form .= html_writer::empty_tag('input', array('type' => 'hidden',
        'name' => "nojs", 'value' => '1'));
    $button_form .= html_writer::empty_tag('input', array('type' => 'hidden',
        'name' => "returnurl", 'value' => qualified_me()));
    $button_form .= html_writer::empty_tag('input', array('type' => 'hidden',
        'name' => "s", 'value' => sesskey()));
    $button_form .= html_writer::end_tag('div');
    $button_form .= html_writer::end_tag('form');
    $button_form .= html_writer::end_tag('div');
    $button_form .= html_writer::end_tag('div');
}
echo $button_form;

// Goals Table.
$goal_table = cohort::display_goal_table($cohort, $can_edit);
echo $goal_table;

echo $OUTPUT->footer();

function display_yes_no($value) {
    return (isset($value) && $value == 1) ? get_string('yes') : get_string('no');
}
