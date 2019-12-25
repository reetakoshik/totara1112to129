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
 * @author Oleg Demeshev <oleg.demeshev@totaralearning.com>
 * @package mod_facetoface
 */

require_once(__DIR__ . '/../../../config.php');
require_once(__DIR__ . '/../lib.php');

$s = required_param('s', PARAM_INT); // facetoface session ID
$backtoallsessions = optional_param('backtoallsessions', 1, PARAM_BOOL);

$seminarevent = new \mod_facetoface\seminar_event($s);
$seminar = $seminarevent->get_seminar();
$course = $DB->get_record('course', ['id' => $seminar->get_course()]);
$cm = $seminar->get_coursemodule();
$context =  context_module::instance($cm->id);

$context = context_module::instance($cm->id);

require_login($course, false, $cm);
require_capability('mod/facetoface:editevents', $context);

$PAGE->set_url('/mod/facetoface/events/delete.php', array('s' => $s, 'backtoallsessions' => $backtoallsessions));
$PAGE->set_title($seminar->get_name());
$PAGE->set_heading($course->fullname);

if ($backtoallsessions) {
    $returnurl = new moodle_url('/mod/facetoface/view.php', array('f' => $seminar->get_id()));
} else {
    $returnurl = new moodle_url('/course/view.php', array('id' => $course->id));
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('deletingsession', 'facetoface', format_string($seminar->get_name())));

$viewattendees = has_capability('mod/facetoface:viewattendees', $context);

/**
 * @var mod_facetoface_renderer $seminarrenderer
 */
$seminarrenderer = $PAGE->get_renderer('mod_facetoface');
echo $seminarrenderer->render_seminar_event($seminarevent, $viewattendees);

$optionsyes = array('sesskey' => sesskey(), 's' => $seminarevent->get_id(), 'backtoallsessions' => $backtoallsessions);
echo $OUTPUT->confirm(get_string('deletesessionconfirm', 'facetoface', format_string($seminar->get_name())),
    new moodle_url('confirm.php', $optionsyes),
    new moodle_url($returnurl));
echo $OUTPUT->footer();
