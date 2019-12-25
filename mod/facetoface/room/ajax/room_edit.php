<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2016 onwards Totara Learning Solutions LTD
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
 * @author Valerii Kuznetsov <valerii.kuznetsov@totaralms.com>
 * @package mod_facetoface
 */

define('AJAX_SCRIPT', true);

use \mod_facetoface\room;

require_once(__DIR__ . '/../../../../config.php');
require_once($CFG->dirroot . '/mod/facetoface/lib.php');
require_once($CFG->dirroot . '/totara/customfield/fieldlib.php');

$id = required_param('id', PARAM_INT);   // Room id.
$facetofaceid = required_param('f', PARAM_INT);   // Face-to-face id.
$sessionid = optional_param('s', 0, PARAM_INT);

$seminar = new \mod_facetoface\seminar($facetofaceid);
$course = $DB->get_record('course', array('id' => $seminar->get_course()), '*', MUST_EXIST);
$cm = $seminar->get_coursemodule();

$event = new \mod_facetoface\seminar_event($sessionid);
if (!$event->exists()) {
    $event->set_facetoface($seminar->get_id());
}

$context = context_module::instance($cm->id);
ajax_require_login($course, false, $cm, false, true);
require_capability('mod/facetoface:editevents', $context);
require_sesskey();

if ($id) {
    $room = new room($id);
    if ($room->exists()) {
        // Only custom rooms can be changed here!
        if (!$room->is_available(0, 0, $event)) {
            // They should never get here, any error will do.
            print_error('Error: Room is unavailable in this seminar event');
        }
    }
} else {
    $room = room::create_custom_room();
}

// Legacy Totara HTML ajax, this should be converted to json + AJAX_SCRIPT.
send_headers('text/html; charset=utf-8', false);

$PAGE->set_context($context);
$PAGE->set_url('/mod/facetoface/room/ajax/room_edit.php');

$customdata = ['room' => $room, 'seminar' => $seminar, 'event' => $event, 'editoroptions' => $TEXTAREA_OPTIONS];
$form = new \mod_facetoface\form\editroom(null, $customdata, 'post', '', array('class' => 'dialog-nobind'), true, null, 'mform_modal');

if ($data = $form->get_data()) {
    $data->custom = empty($data->notcustom);
    $room = \mod_facetoface\room_helper::save($data);
    echo json_encode(array('id' => $room->get_id(), 'name' => $room->get_name(), 'custom' => $room->get_custom()));
} else {
    // This is required because custom fields may use AMD module for JS and we can't re-initialise AMD
    // which will happen if we call get_end_code() without setting the first arg to false.
    // It must be called before form->display and importantly before get_end_code.
    $amdsnippets = $PAGE->requires->get_raw_amd_js_code();

    $form->display();
    echo $PAGE->requires->get_end_code(false);
    // Finally add our AMD code into the page.
    echo html_writer::script(implode(";\n", $amdsnippets));
}
