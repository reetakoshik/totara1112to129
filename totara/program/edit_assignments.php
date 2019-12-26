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
 * @author Jake Salmon <jake.salmon@kineo.com>
 * @package totara
 * @subpackage program
 */

/**
 * Program view page
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot . '/totara/program/lib.php');
require_once($CFG->dirroot.'/totara/certification/lib.php');
require_once($CFG->dirroot . '/totara/core/js/lib/setup.php');

require_login();

$id = required_param('id', PARAM_INT);

$program = new program($id);
$iscertif = $program->is_certif();
$programcontext = $program->get_context();

require_capability('totara/program:configureassignments', $programcontext);
$program->check_enabled();

$PAGE->set_url(new moodle_url('/totara/program/edit_assignments.php', array('id' => $id)));
$PAGE->set_program($program);
$PAGE->set_title(format_string($program->fullname));
$PAGE->set_heading(format_string($program->fullname));

// Javascript include.
local_js(array(
TOTARA_JS_DIALOG,
TOTARA_JS_TREEVIEW,
TOTARA_JS_DATEPICKER
));

// Get item pickers
$PAGE->requires->strings_for_js(array('setcompletion', 'removecompletiondate', 'youhaveunsavedchanges',
                'cancel','ok','completioncriteria','pleaseentervaliddate',
                'pleaseentervalidunit','pleasepickaninstance','editassignments',
                'saveallchanges','confirmassignmentchanges','chooseitem'), 'totara_program');
$PAGE->requires->string_for_js('loading', 'admin');
$PAGE->requires->string_for_js('none', 'moodle');
$display_selected = json_encode(dialog_display_currently_selected(get_string('selected', 'totara_hierarchy'), 'completion-event-dialog'));

if (!empty($CFG->enablelegacyprogramassignments)) {
    $args = array('args' => '{"id":"'.$program->id.'",'.
        '"confirmation_template":'.prog_assignments::get_confirmation_template().','.
        '"COMPLETION_EVENT_NONE":"'.COMPLETION_EVENT_NONE.'",'.
        '"COMPLETION_TIME_NOT_SET":"'.COMPLETION_TIME_NOT_SET.'",'.
        '"COMPLETION_EVENT_FIRST_LOGIN":"'.COMPLETION_EVENT_FIRST_LOGIN.'",'.
        '"COMPLETION_EVENT_ENROLLMENT_DATE":"'.COMPLETION_EVENT_ENROLLMENT_DATE.'",'.
        '"display_selected_completion_event":'.$display_selected.'}'
    );

    $jsmodule = array(
        'name' => 'totara_programassignment',
        'fullpath' => '/totara/program/assignment/program_assignment.js',
        'requires' => array('json', 'totara_core')
    );

    $PAGE->requires->js_init_call('M.totara_programassignment.init',$args, false, $jsmodule);
} else {
    // new UI needs M.totara_core.build_datepicker
    $jsmodule = array(
        'name' => 'totara_core',
        'fullpath' => '/totara/core/module.js',
        'requires' => array('json')
    );
    $PAGE->requires->js_init_call('M.totara_core.init', ['args' => ''], false, $jsmodule);
}

// Define the categorys to appear on the page
$categories = prog_assignment_category::get_categories();

if ($data = data_submitted()) {

    // Check the session key
    confirm_sesskey();

    // Update each category
    foreach ($categories as $category) {
        $category->update_assignments($data);
    }

    // reset the assignments property to ensure it only contains the current assignments.
    $program->reset_assignments();

    // Update the user assignments
    $program->update_learner_assignments();

    $prog_update = new stdClass();
    $prog_update->id = $id;
    $prog_update->timemodified = time();
    $prog_update->usermodified = $USER->id;
    $DB->update_record('prog', $prog_update);

    $eventdata = array();
    foreach ($program->get_assignments()->get_assignments() as $assignment) {
        // Event expects an array.
        $eventdata[] = json_decode(json_encode($assignment), true);
    }

    $event = \totara_program\event\program_assignmentsupdated::create(
        array(
            'objectid' => $id,
            'context' => context_program::instance($id),
            'userid' => $USER->id,
            'other' => array(
                'assignments' => $eventdata,
            ),
        )
    );
    $event->trigger();

    if (isset($data->savechanges)) {
        totara_set_notification(get_string('programassignmentssaved', 'totara_program'), 'edit_assignments.php?id='.$id,
                                                                                        array('class' => 'notifysuccess'));
    }

}

$currenturl = qualified_me();
$currenturl_noquerystring = strip_querystring($currenturl);
$viewurl = $currenturl_noquerystring."?id={$id}&action=view";

// Trigger event.
$dataevent = array('id' => $program->id, 'other' => array('section' => 'assignments'));
$event = \totara_program\event\program_viewed::create_from_data($dataevent)->trigger();

// Display.
$heading = format_string($program->fullname);

if ($iscertif) {
    $heading .= ' ('.get_string('certification', 'totara_certification').')';
}

echo $OUTPUT->header();

echo $OUTPUT->container_start('program assignments', 'program-assignments');

echo $OUTPUT->heading($heading);

/** @var totara_program_renderer $renderer */
$renderer = $PAGE->get_renderer('totara_program');
echo html_writer::div($program->display_current_status(), '', ['data-totara_program--notification' => '']);
// Display the current status
$exceptions = $program->get_exception_count();
$currenttab = 'assignments';
require('tabs.php');


// If enabled use the new program assignment interface
if (!empty($CFG->enablelegacyprogramassignments)) {
    echo $renderer->display_edit_assignment_form($program, $categories, CERTIFPATH_STD); // Can use STD or CERT, they are the same.
    if (!$program->has_expired()) {
        echo $renderer->get_cancel_button(array('id' => $program->id));
    }
} else {
    $results = \totara_program\assignment\helper::get_assignments($program->id);

    $program_assignments = \totara_program\output\assignments::create_from_assignments($results['assignments'], $program->id, $results['toomany']);
    $program_assignments->set_categories($categories);
    $assign_data = $program_assignments->get_template_data();

    echo $OUTPUT->render_from_template('totara_program/assignments', $assign_data);
}

echo $OUTPUT->container_end();

echo $OUTPUT->footer();
