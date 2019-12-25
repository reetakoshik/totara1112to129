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
 * @author Valerii Kuznetsov <valerii.kuznetsov@totaralms.com>
 * @package mod_facetoface
 */
require_once(__DIR__ . '/../../../../config.php');
require_once($CFG->dirroot . '/mod/facetoface/lib.php');

use mod_facetoface\bulk_list;
use mod_facetoface\seminar_event;
use mod_facetoface\attendees_list_helper;
use mod_facetoface\form\attendees_add_list;

// Face-to-face session ID
$s = required_param('s', PARAM_INT);
$listid = optional_param('listid', uniqid('f2f'), PARAM_ALPHANUM);

$seminarevent = new seminar_event($s);
$seminar = $seminarevent->get_seminar();
$course = $DB->get_record('course', ['id' => $seminar->get_course()]);
$cm = $seminar->get_coursemodule();
$context =  context_module::instance($cm->id);

$returnurl  = new moodle_url('/mod/facetoface/attendees/view.php', array('s' => $s, 'backtoallsessions' => 1));
$currenturl = new moodle_url('/mod/facetoface/attendees/list/addlist.php', array('s' => $s, 'listid' => $listid));
// Check capability
require_login($course, false, $cm);
require_capability('mod/facetoface:addattendees', $context);

$PAGE->set_context($context);
$PAGE->set_url($currenturl);
$PAGE->set_cm($cm);
$PAGE->set_pagelayout('standard');

$list  = new bulk_list($listid, $currenturl, 'addlist');
$mform = new attendees_add_list(null, array('s' => $s, 'listid' => $listid));
$mform->set_data($list->get_form_data());
if ($mform->is_cancelled()) {
    $list->clean();
    redirect($returnurl);
}

// Check if data submitted
if ($data = $mform->get_data()) {
    attendees_list_helper::add_list($data);
}

local_js(array(TOTARA_JS_DIALOG));
$PAGE->requires->js_call_amd('mod_facetoface/attendees_addremove', 'init', array(array('s' => $s, 'listid' => $listid)));

$PAGE->set_title(format_string($seminar->get_name()));
$PAGE->set_heading($course->fullname);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('addattendeestep1', 'facetoface'));

/**
 * @var mod_facetoface_renderer $seminarrenderer
 */
$seminarrenderer = $PAGE->get_renderer('mod_facetoface');
echo $seminarrenderer->render_seminar_event($seminarevent, false, false, true);

$mform->display();

echo $OUTPUT->footer();