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
 * @author Lee Campbell <lee@learningpool.com>
 * @package mod_facetoface
 */

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot . '/totara/reportbuilder/lib.php');
require_once($CFG->dirroot . '/totara/program/lib.php');
require_once($CFG->dirroot . '/mod/facetoface/lib.php');

$format = optional_param('format', '', PARAM_PLUGIN); // Export format.
$debug = optional_param('debug', 0, PARAM_INT);
$sessiondateid = optional_param('sessiondateid', 0, PARAM_INT);

$PAGE->set_url('/mod/facetoface/reports/signinsheet.php', array('sessiondateid' => $sessiondateid));

$sessiondate = $DB->get_record('facetoface_sessions_dates', array('id' => $sessiondateid));
if (!$sessiondate) {
    require_login();
    $PAGE->set_context(context_system::instance());
    $PAGE->set_heading($SITE->fullname);
    $PAGE->set_title(get_string('signinsheetreport', 'mod_facetoface'));
    echo $OUTPUT->header();
    echo $OUTPUT->container(get_string('gettosigninreport', 'mod_facetoface'));
    echo $OUTPUT->footer();
    exit;
}

$session = $DB->get_record('facetoface_sessions', array('id' => $sessiondate->sessionid), '*', MUST_EXIST);
$facetoface = $DB->get_record('facetoface', array('id' => $session->facetoface), '*', MUST_EXIST);
$course = $DB->get_record('course', array('id' => $facetoface->course), '*', MUST_EXIST);
$cm = get_coursemodule_from_instance('facetoface', $facetoface->id, $course->id, false, MUST_EXIST);
$context = context_module::instance($cm->id);

require_login($course, false, $cm);
require_capability('mod/facetoface:exportsessionsigninsheet', $context);

// Verify global restrictions.
$shortname = 'facetoface_signin';
$reportrecord = $DB->get_record('report_builder', array('shortname' => $shortname));
$globalrestrictionset = rb_global_restriction_set::create_from_page_parameters($reportrecord);

$reportparams = array(
    'facetofaceid' => $facetoface->id,
    'sessionid' => $session->id,
    'sessiondateid' => $sessiondate->id,
);
$config = (new rb_config())->set_global_restriction_set($globalrestrictionset)->set_embeddata($reportparams);
if (!$report = reportbuilder::create_embedded($shortname, $config)) {
    print_error('error:couldnotgenerateembeddedreport', 'totara_reportbuilder');
}

if ($format != '') {
    \mod_facetoface\event\session_signin_sheet_exported::create_from_facetoface_session($session, $context)->trigger();

    $report->export_data($format);
    die;
}

/** @var totara_reportbuilder_renderer|core_renderer $renderer */
$renderer = $PAGE->get_renderer('totara_reportbuilder');

$strheading = get_string('signinsheetreport', 'mod_facetoface') . ' - ' . $facetoface->name;

$PAGE->set_title($strheading);
$PAGE->set_button($report->edit_button());
$PAGE->set_heading($strheading);

echo $renderer->header();

$report->display_restrictions();

echo $OUTPUT->heading($strheading);

if ($debug) {
    $report->debug($debug);
}

echo $renderer->print_description($report->description, $report->_id);

$renderer->export_select($report->_id, 0);

echo $renderer->footer();
