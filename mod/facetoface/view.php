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
 * @package modules
 * @subpackage facetoface
 */

require_once '../../config.php';
require_once $CFG->dirroot . '/mod/facetoface/lib.php';
require_once $CFG->dirroot . '/mod/facetoface/renderer.php';
require_once($CFG->dirroot . '/totara/customfield/field/location/field.class.php'); // TODO: TL-9425 this hack is unacceptable.

$id = optional_param('id', 0, PARAM_INT); // Course Module ID
$f = optional_param('f', 0, PARAM_INT); // facetoface ID
$roomid = optional_param('roomid', 0, PARAM_INT);
$download = optional_param('download', '', PARAM_ALPHA); // download attendance

if ($id) {
    if (!$cm = get_coursemodule_from_id('facetoface', $id)) {
        print_error('error:incorrectcoursemoduleid', 'facetoface');
    }
    if (!$course = $DB->get_record('course', array('id' => $cm->course))) {
        print_error('error:coursemisconfigured', 'facetoface');
    }
    if (!$facetoface = $DB->get_record('facetoface', array('id' => $cm->instance))) {
        print_error('error:incorrectcoursemodule', 'facetoface');
    }
} else if ($f) {
    if (!$facetoface = $DB->get_record('facetoface', array('id' => $f))) {
        print_error('error:incorrectfacetofaceid', 'facetoface');
    }
    if (!$course = $DB->get_record('course', array('id' => $facetoface->course))) {
        print_error('error:coursemisconfigured', 'facetoface');
    }
    if (!$cm = get_coursemodule_from_instance('facetoface', $facetoface->id, $course->id)) {
        print_error('error:incorrectcoursemoduleid', 'facetoface');
    }
} else {
    print_error('error:mustspecifycoursemodulefacetoface', 'facetoface');
}

$context = context_module::instance($cm->id);
$PAGE->set_url('/mod/facetoface/view.php', array('id' => $cm->id));
$PAGE->set_context($context);
$PAGE->set_cm($cm);
$PAGE->set_pagelayout('standard');

// Check for auto nofication duplicates.
if (has_capability('moodle/course:manageactivities', $context)) {
    require_once($CFG->dirroot.'/mod/facetoface/notification/lib.php');
    if (facetoface_notification::has_auto_duplicates($facetoface->id)) {
        $url = new moodle_url('/mod/facetoface/notification/index.php', array('update' => $cm->id));
        totara_set_notification(get_string('notificationduplicatesfound', 'facetoface', $url->out()));
    }
}

if (!empty($download)) {
    require_capability('mod/facetoface:viewattendees', $context);
    facetoface_download_attendance($facetoface->name, $facetoface->id, '', $download);
    exit();
}

require_login($course, true, $cm);
require_capability('mod/facetoface:view', $context);

$event = \mod_facetoface\event\course_module_viewed::create(array(
    'objectid' => $PAGE->cm->instance,
    'context' => $PAGE->context,
));
$event->add_record_snapshot('course', $course);
$event->add_record_snapshot($cm->modname, $facetoface);
$event->trigger();

$title = $course->shortname . ': ' . format_string($facetoface->name);

$PAGE->set_title($title);
$PAGE->set_heading($course->fullname);

$pagetitle = format_string($facetoface->name);

$f2f_renderer = $PAGE->get_renderer('mod_facetoface');
$f2f_renderer->setcontext($context);

$completion=new completion_info($course);
$completion->set_module_viewed($cm);

echo $OUTPUT->header();

if (empty($cm->visible) and !has_capability('mod/facetoface:viewemptyactivities', $context)) {
    notice(get_string('activityiscurrentlyhidden'));
}
echo $OUTPUT->box_start();
echo $OUTPUT->heading(get_string('allsessionsin', 'facetoface', $facetoface->name), 2);

echo self_completion_form($cm, $course);

if (!empty($facetoface->intro)) {
    echo $OUTPUT->box(format_module_intro('facetoface', $facetoface, $cm->id), 'generalbox', 'intro');
}

// Display a warning about previously mismatched self approval sessions.
$approvalcountsql = "SELECT selfapproval, count(selfapproval)
                    FROM {facetoface_sessions}
                    WHERE facetoface = :fid
                    GROUP BY selfapproval";
$approvalcount = $DB->get_records_sql($approvalcountsql, array('fid' => $facetoface->id));
if (count($approvalcount) > 1) {
    $message = get_string('warning:mixedapprovaltypes', 'mod_facetoface') . $f2f_renderer->dismiss_selfapproval_notice($facetoface->id);
    echo $OUTPUT->notification($message, 'notifynotice');
}

$rooms = facetoface_get_used_rooms($facetoface->id);
if (count($rooms) > 1) {
    $roomselect = array(0 => get_string('allrooms', 'facetoface'));
    // Here used to be some fancy code that deal with missing room names,
    // that magic cannot be done easily any more, allow selection of named rooms only here.
    foreach ($rooms as $rid => $room) {
        $roomname = format_string($room->name);
        if ($roomname === '') {
            continue;
        }
        $roomselect[$rid] = $roomname;
    }

    if (!isset($roomselect[$roomid])) {
        $roomid = 0;
    }

    if (count($roomselect) > 2) {
        echo $OUTPUT->single_select($PAGE->url, 'roomid', $roomselect, $roomid, null, null, array('label' => get_string('filterbyroom', 'facetoface')));
    }
} else {
    $roomid = 0;
}

$sessions = facetoface_get_sessions($facetoface->id, '', $roomid);
echo facetoface_print_session_list($course->id, $facetoface, $sessions);

if (has_capability('mod/facetoface:viewattendees', $context)) {
    echo html_writer::start_tag('form', array('action' => 'view.php', 'method' => 'get'));
    echo html_writer::start_tag('div') . html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'f', 'value' => $facetoface->id));
    echo get_string('exportattendance', 'facetoface') . '&nbsp;';
    $formats = array(0 => get_string('format', 'mod_facetoface'),
                    'excel' => get_string('excelformat', 'facetoface'),
                    'ods' => get_string('odsformat', 'facetoface'));
    echo html_writer::select($formats, 'download', 0, '');
    echo html_writer::empty_tag('input', array('type' => 'submit', 'value' => get_string('exporttofile', 'facetoface')));
    echo html_writer::end_tag('div'). html_writer::end_tag('form');
}

echo $OUTPUT->box_end();

$alreadydeclaredinterest = facetoface_user_declared_interest($facetoface);
if ($alreadydeclaredinterest || facetoface_activity_can_declare_interest($facetoface)) {
    if ($alreadydeclaredinterest) {
        $strbutton = get_string('declareinterestwithdraw', 'mod_facetoface');
    } else {
        $strbutton = get_string('declareinterest', 'mod_facetoface');
    }
    $url = new moodle_url('/mod/facetoface/interest.php', array('f' => $facetoface->id));
    echo $OUTPUT->single_button($url, $strbutton, 'get');
}

echo $OUTPUT->footer($course);

