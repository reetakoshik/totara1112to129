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
require_once($CFG->dirroot . '/totara/reportbuilder/lib.php');

// Report params.
$sid = optional_param('sid', '0', PARAM_INT);
$format = optional_param('format', '', PARAM_TEXT);
$debug = optional_param('debug', 0, PARAM_INT);
// Page params.
$courseid = optional_param('courseid', 0, PARAM_INT);

$url = new moodle_url('/totara/completioneditor/course_completion.php', array('courseid' => $courseid));
if (!$courseid) {
    $context = context_system::instance();
    $PAGE->set_context($context);
    $PAGE->set_url($url);

    echo $OUTPUT->header();
    $courseurl = new moodle_url('/course/index.php');
    echo $OUTPUT->container(get_string('coursemembershipselect', 'rb_source_course_membership', $courseurl->out()));
    echo $OUTPUT->footer();
    exit;
}

$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
require_login($course);

// Capability check.
$coursecontext = context_course::instance($courseid);
require_capability('totara/completioneditor:editcoursecompletion', $coursecontext);

// Set up page.
$PAGE->set_url($url);
$PAGE->set_title($course->fullname);
$PAGE->set_heading($course->fullname);

/* @var totara_reportbuilder_renderer $output */
$output = $PAGE->get_renderer('totara_reportbuilder');

// Verify global restrictions.
$reportrecord = $DB->get_record('report_builder', array('shortname' => 'course_membership'));
$globalrestrictionset = rb_global_restriction_set::create_from_page_parameters($reportrecord);

// Load report.
$config = (new rb_config())
    ->set_sid($sid)
    ->set_embeddata(['courseid' => $courseid])
    ->set_global_restriction_set($globalrestrictionset);
if (!$report = reportbuilder::create_embedded('course_membership', $config)) {
    print_error('error:couldnotgenerateembeddedreport', 'totara_reportbuilder');
}

if ($format != '') {
    $report->export_data($format);
    die;
}

echo $output->header();

list($reporthtml, $debughtml) = $output->report_html($report, $debug);
echo $debughtml;

echo $output->heading($PAGE->heading);

/* @var \totara_completioneditor\output\course_renderer $checkeroutput */
$checkeroutput = $PAGE->get_renderer('totara_completioneditor', 'course');
echo $checkeroutput->checker_link($courseid);

$report->display_restrictions();

echo $output->print_description($report->description, $report->_id);

$report->include_js();

$report->display_search();
$report->display_sidebar_search();

// Print saved search buttons if appropriate.
echo $report->display_saved_search_options();

echo $output->result_count_info($report);

echo $reporthtml;

// Export button.
$output->export_select($report, $sid);

echo $output->footer();
