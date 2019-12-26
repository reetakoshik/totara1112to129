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
 * @author Aaron Barnes <aaron.barnes@totaralms.com>
 * @author Francois Marier <francois@catalyst.net.nz>
 * @author Valerii Kuznetsov <valerii.kuznetsov@totaralms.com>
 * @package mod_facetoface
 */

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot . '/mod/facetoface/lib.php');
require_once($CFG->dirroot . '/totara/core/js/lib/setup.php');

$f  = required_param('f', PARAM_INT);  // facetoface Module ID
$id = optional_param('id', 0, PARAM_INT); // Course Module ID
$cntdates = optional_param('cntdates', 0, PARAM_INT); // Number of events to set.
$backtoallsessions = optional_param('backtoallsessions', 1, PARAM_BOOL);
$savewithconflicts = optional_param('savewithconflicts', 0, PARAM_BOOL); // Save with conflicts.

$session = null;
$s = 0;
$c = 0;

if (!$facetoface = $DB->get_record('facetoface', array('id' => $f))) {
    print_error('error:incorrectfacetofaceid', 'facetoface');
}
if (!$course = $DB->get_record('course', array('id' => $facetoface->course))) {
    print_error('error:coursemisconfigured', 'facetoface');
}
if (!$cm = get_coursemodule_from_instance('facetoface', $facetoface->id, $course->id)) {
    print_error('error:incorrectcoursemoduleid', 'facetoface');
}

$context = context_module::instance($cm->id);
$f  = $facetoface->id;
$id = $cm->id;

require_login($course, false, $cm);
require_capability('mod/facetoface:editevents', $context);

local_js(array(
    TOTARA_JS_DIALOG,
    TOTARA_JS_TREEVIEW
));
$PAGE->set_url('/mod/facetoface/events/add.php', array('f' => $f, 'backtoallsessions' => $backtoallsessions));
$PAGE->requires->strings_for_js(array('save', 'delete'), 'totara_core');
$PAGE->requires->strings_for_js(array('cancel', 'ok', 'edit', 'loadinghelp'), 'moodle');
$PAGE->requires->strings_for_js(array('chooseassets', 'chooseroom', 'dateselect', 'useroomcapacity', 'nodatesyet',
    'createnewasset', 'editasset', 'createnewroom', 'editroom'), 'facetoface');
$PAGE->set_title($facetoface->name);
$PAGE->set_heading($course->fullname);

$jsconfig = array('sessionid' => $s, 'can_edit' => 'true', 'facetofaceid' => $facetoface->id);

// Offer one date for new sessions by default.
if ($cntdates < 1) {
    $cntdates = 1;
}
for ($offset = 0; $offset < $cntdates; $offset++) {
    $display_selected = dialog_display_currently_selected(get_string('selected', 'facetoface'), "selectroom{$offset}-dialog");
    $jsconfig['display_selected_item' . $offset] = $display_selected;
}

$jsmodule = array(
    'name' => 'totara_f2f_room',
    'fullpath' => '/mod/facetoface/js/event.js',
    'requires' => array('json', 'totara_core'));
$PAGE->requires->js_init_call('M.totara_f2f_room.init', array($jsconfig), false, $jsmodule);

if ($backtoallsessions) {
    $returnurl = new moodle_url('/mod/facetoface/view.php', array('f' => $facetoface->id));
} else {
    $returnurl = new moodle_url('/course/view.php', array('id' => $course->id));
}

list($sessiondata, $editoroptions, $defaulttimezone, $nbdays) = \mod_facetoface\form\event::prepare_data($session, $facetoface, $course, $context, $cntdates);

$mform = new \mod_facetoface\form\event(null, compact('id', 'f', 's', 'c', 'session', 'nbdays', 'course', 'editoroptions', 'defaulttimezone', 'facetoface', 'cm', 'sessiondata', 'backtoallsessions', 'savewithconflicts'), 'post', '', array('id' => 'mform_seminar_event'));

if ($mform->is_cancelled()) {
    redirect($returnurl);
}
if ($todb = $mform->process_data()) { // Form submitted
    // Lets see if user conflicts are present.
    $users_in_conflict = $mform->get_users_in_conflict();
    if (empty($users_in_conflict)) {
        $mform->save($todb);
        redirect($returnurl);
    } else {
        $text = facetoface_build_user_roles_in_conflict_message($users_in_conflict);
        $PAGE->requires->js_call_amd('mod_facetoface/user_conflicts_confirm', 'init', array('note' => $text));
    }
}

echo $OUTPUT->header();
echo $OUTPUT->box_start();
echo $OUTPUT->heading(get_string('addingsession', 'facetoface', format_string($facetoface->name)));

$mform->display();

echo $OUTPUT->box_end();
echo $OUTPUT->footer($course);
