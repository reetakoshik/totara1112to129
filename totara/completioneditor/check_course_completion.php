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
 * @author Nathan Lewis <nathan.lewis@totaralearning.com>
 * @package totara_completioneditor
 */

global $CFG, $DB, $PAGE;

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot . '/totara/core/js/lib/setup.php');

require_login();

$userid = optional_param('userid', 0, PARAM_INT);
$courseid = optional_param('courseid', 0, PARAM_INT);
$fixkey = optional_param('fixkey', false, PARAM_ALPHANUMEXT);
$returntoeditor = optional_param('returntoeditor', false, PARAM_BOOL);

core_php_time_limit::raise(0);

$context = context_system::instance();
$coursefullname = "";
if ($courseid) {
    $context = context_course::instance($courseid);
    require_capability('totara/completioneditor:editcoursecompletion', $context);
    $course = $DB->get_record('course', array('id' => $courseid));

    $coursefullname = $course->fullname;
    $PAGE->set_context($context);
    $PAGE->set_course($course);

} else {
    require_capability('totara/completioneditor:editcoursecompletion', $context);
}

// Guest user can't be edited.
if ($userid == guest_user()->id) {
    throw new exception("Guest user completion data cannot be edited");
}

$url = new moodle_url('/totara/completioneditor/check_course_completion.php');
if ($courseid) {
    $url->param('courseid', $courseid);
}
if ($userid) {
    $url->param('userid', $userid);
}

// If a fix key has been provided, fix the corresponding records.
if ($fixkey) {
    require_sesskey();
    \totara_completioneditor\course_editor::apply_fix($fixkey, $courseid, $userid);
    if ($returntoeditor) {
        $url = new moodle_url('/totara/completioneditor/course_completion_overview.php', array('id' => $courseid, 'userid' => $userid));
    }
    redirect($url, get_string('completionchangessaved', 'totara_completioneditor'),
        null, \core\output\notification::NOTIFY_SUCCESS);
}

// Set up the page.
$heading = get_string('completionswithproblems', 'totara_completioneditor');
$PAGE->set_context($context);
$PAGE->set_url($url);
$PAGE->set_title($heading);
$PAGE->set_heading($heading);

if (!empty($courseid)) {
    navigation_node::override_active_url(new moodle_url('/totara/completioneditor/course_completion.php',
        array('courseid' => $courseid)));
} else {
    navigation_node::require_admin_tree();
    navigation_node::override_active_url(new moodle_url('/course/management.php')); // Doesn't seem to be working :|
}

/* @var \totara_completioneditor\output\course_renderer $output */
$output = $PAGE->get_renderer('totara_completioneditor', 'course');

echo $output->header();
echo $output->heading($heading);

// Load js.
$PAGE->requires->strings_for_js(array('fixconfirmsome', 'fixconfirmtitle'), 'totara_completioneditor');
$PAGE->requires->js_call_amd('totara_completioneditor/check_completion', 'init');

// Check all the records and output any problems.
$usernamefields = totara_get_all_user_name_fields(true, 'u');
$sql = "SELECT cc.*, course.fullname, {$usernamefields}
          FROM {course_completions} cc
          JOIN {course} course ON cc.course = course.id
          JOIN {user} u ON cc.userid = u.id
         WHERE 1=1";
$params = array();
if ($userid) {
    $sql .= " AND cc.userid = :userid";
    $params['userid'] = $userid;
}
if ($courseid) {
    $sql .= " AND cc.course = :courseid";
    $params['courseid'] = $courseid;
}
$rs = $DB->get_recordset_sql($sql, $params);

$data = new stdClass();
$data->rs = $rs;
$data->url = $url;
$data->courseid = $courseid;
$data->userid = $userid;
if ($courseid) {
    $courseurl = new moodle_url('/totara/completioneditor/course_completion.php', array('courseid' => $courseid));
    $data->coursename = html_writer::link($courseurl, format_string($coursefullname));
}
if ($userid) {
    $user = $DB->get_record('user', array('id' => $userid));
    $data->username = fullname($user);
}

echo $output->checker_results($data);

$rs->close();

echo $output->footer();
