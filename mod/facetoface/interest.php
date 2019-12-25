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
 * @author Andrew Hancox <andrewdchancox@googlemail.com>
 * @author Oleg Demeshev <oleg.demeshev@totaralearning.com>
 * @package mod_facetoface
 */

require_once('../../config.php');
require_once($CFG->dirroot . '/mod/facetoface/lib.php');

$f = required_param('f', PARAM_INT); // Facetoface ID.

$seminar = new \mod_facetoface\seminar($f);
$course = $DB->get_record('course', array('id' => $seminar->get_course()), '*', MUST_EXIST);
$cm = $seminar->get_coursemodule();
$interest = \mod_facetoface\interest::from_seminar($seminar);

$redirecturl = new moodle_url('/course/view.php', array('id' => $course->id));

// Are we declaring or withdrawing interest?
$declare = !$interest->is_user_declared();
if ($declare && !$interest->can_user_declare()) {
    print_error('error:cannotdeclareinterest', 'facetoface', $redirecturl);
}

$context = context_module::instance($cm->id);

$PAGE->set_url('/mod/facetoface/interest.php', array('id' => $cm->id));
$PAGE->set_context($context);
$PAGE->set_pagelayout('standard');
$PAGE->set_cm($cm);

require_login($course, true, $cm);
require_capability('mod/facetoface:view', $context);

$title = $course->shortname . ': ' . format_string($seminar->get_name());

$PAGE->set_title($title);
$PAGE->set_heading($course->fullname);

$mform = new \mod_facetoface\form\interest(null, array('f' => $seminar->get_id(), 'declare' => $declare));

if ($mform->is_cancelled()) {
    redirect($redirecturl);
} else if ($data = $mform->get_data()) {
    if ($declare) {
        $reason = isset($data->reason) ? $data->reason : '';
        $interest->set_reason($reason)->declare();
        \mod_facetoface\event\interest_declared::create_from_instance($interest, $context)->trigger();
    } else {
        $oldinterest = \mod_facetoface\interest::from_seminar($seminar);
        $interest->withdraw();
        \mod_facetoface\event\interest_withdrawn::create_from_instance($oldinterest, $context)->trigger();
    }
    redirect($redirecturl);
}

$pagetitle = format_string($seminar->get_name());

echo $OUTPUT->header();

if (empty($cm->visible) and !has_capability('mod/facetoface:viewemptyactivities', $context)) {
    notice(get_string('activityiscurrentlyhidden'));
}
echo $OUTPUT->box_start();
if ($declare) {
    $title = get_string('declareinterestin', 'mod_facetoface', $seminar->get_name());
    $question = get_string('declareinterestinconfirm', 'mod_facetoface', $seminar->get_name());
} else {
    $title = get_string('declareinterestwithdrawfrom', 'mod_facetoface', $seminar->get_name());
    $question = get_string('declareinterestwithdrawfromconfirm', 'mod_facetoface', $seminar->get_name());
}
echo $OUTPUT->heading($title, 2);

if ($seminar->get_intro()) {
    echo $OUTPUT->box_start('generalbox', 'description');
    $intro = file_rewrite_pluginfile_urls($seminar->get_intro(), 'pluginfile.php', $context->id, 'mod_facetoface', 'intro', null);
    $seminar->set_intro($intro);
    echo format_text($seminar->get_intro(), $seminar->get_introformat());
    echo $OUTPUT->box_end();
}

echo $OUTPUT->heading($question, 4);
$mform->display();

echo $OUTPUT->box_end();
echo $OUTPUT->footer($course);
