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

require_once(__DIR__ . '/../../../../config.php');
require_once($CFG->dirroot . '/mod/facetoface/lib.php');

$id = required_param('id', PARAM_INT);   // Room id.
$facetofaceid = required_param('f', PARAM_INT);   // Face-to-face id.
$sessionid = optional_param('s', 0, PARAM_INT);

$facetoface = $DB->get_record('facetoface', array('id' => $facetofaceid), '*', MUST_EXIST);
$course = $DB->get_record('course', array('id' => $facetoface->course), '*', MUST_EXIST);
$cm = get_coursemodule_from_instance('facetoface', $facetoface->id, $facetoface->course, false, MUST_EXIST);
if ($sessionid) {
    $session = $DB->get_record('facetoface_sessions', array('id' => $sessionid, 'facetoface' => $facetoface->id), '*', MUST_EXIST);
} else {
    $session = false;
}

$context = context_module::instance($cm->id);
require_login($course, false, $cm, false, true);
require_capability('mod/facetoface:editevents', $context);

require_sesskey();

if ($id) {
    // Only custom rooms can be changed here!
    $room = $DB->get_record('facetoface_room', array('id' => $id, 'custom' => 1), '*', MUST_EXIST);
    if (!facetoface_is_room_available(0, 0, $room, $sessionid, $facetoface->id)) {
        // They should never get here, any error will do.
        print_error('error');
    }
} else {
    $room = false;
}

// Legacy Totara HTML ajax, this should be converted to json + AJAX_SCRIPT.
send_headers('text/html; charset=utf-8', false);

$PAGE->set_context($context);
$PAGE->set_url('/mod/facetoface/room/ajax/room_edit.php');

$form = facetoface_process_room_form($room, $facetoface, $session,
    function($room) {
        echo json_encode(array('id' => $room->id, 'name' => $room->name, 'capacity' => $room->capacity, 'custom' => $room->custom));
        exit();
    },
    null
);

// Include the same strings as mod/facetoface/sessions.php, because we override the lang string cache with this ugly hack.
$PAGE->requires->strings_for_js(array('save', 'delete'), 'totara_core');
$PAGE->requires->strings_for_js(array('cancel', 'ok', 'edit', 'loadinghelp'), 'moodle');
$PAGE->requires->strings_for_js(array('chooseassets', 'chooseroom', 'dateselect', 'useroomcapacity', 'nodatesyet',
    'createnewasset', 'editasset', 'createnewroom', 'editroom'), 'facetoface');

// This is required because custom fields may use AMD module for JS and we can't re-initialise AMD
// which will happen if we call get_end_code() without setting the first arg to false.
// It must be called before form->display and importantly before get_end_code.
$amdsnippets = $PAGE->requires->get_raw_amd_js_code();

$form->display();
echo $PAGE->requires->get_end_code(false);
// Finally add our AMD code into the page.
echo html_writer::script(implode(";\n", $amdsnippets));
