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
 * @author Nathan Lewis <nathan.lewis@totaralms.com>
 * @package totara_certification
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot . '/totara/program/lib.php');
require_once($CFG->dirroot . '/totara/certification/lib.php');
require_once($CFG->dirroot . '/totara/core/js/lib/setup.php');

require_login();

if (empty($CFG->enableprogramcompletioneditor)) {
    print_error('error:completioneditornotenabled', 'totara_program');
}

$userid = optional_param('userid', 0, PARAM_INT);
$progid = optional_param('progid', 0, PARAM_INT);
$progorcert = optional_param('progorcert', 'program', PARAM_ALPHA);
$fixkey = optional_param('fixkey', false, PARAM_ALPHANUMEXT);
$returntoeditor = optional_param('returntoeditor', false, PARAM_BOOL);

core_php_time_limit::raise(0);

// Process the param (also cleans it so that only 'program' and 'certification' are possible).
if ($progorcert == 'program') {
    check_program_enabled();
} else {
    $progorcert = 'certification';
    check_certification_enabled();
}

if ($progid) {
    $program = new program($progid);
    $programcontext = $program->get_context();
    if (!has_capability('totara/program:editcompletion', $programcontext)) {
        print_error('error:impossibledatasubmitted', 'totara_program');
    }
    if ($progorcert == 'certification' && empty($program->certifid) || $progorcert == 'program' && !empty($program->certifid)) {
        print_error('error:impossibledatasubmitted', 'totara_program');
    }
} else if (!has_capability('totara/program:editcompletion', context_system::instance())) {
    print_error('error:nopermissions', 'totara_program');
}

$url = new moodle_url('/totara/program/check_completion.php', array('progorcert' => $progorcert));
if ($progid) {
    $url->param('progid', $progid);
}
if ($userid) {
    $url->param('userid', $userid);
}

// If a fix key has been provided, fix the corresponding records.
if ($fixkey) {
    require_sesskey();
    if ($progorcert == 'program') {
        prog_fix_completions($fixkey, $progid, $userid);
        if ($returntoeditor) {
            $url = new moodle_url('/totara/program/edit_completion.php', array('id' => $progid, 'userid' => $userid));
        }
    } else {
        certif_fix_completions($fixkey, $progid, $userid);
        if ($returntoeditor) {
            $url = new moodle_url('/totara/certification/edit_completion.php', array('id' => $progid, 'userid' => $userid));
        }
    }
    totara_set_notification(get_string('completionchangessaved', 'totara_program'),
        $url,
        array('class' => 'notifysuccess'));
}

// Set up the page.
$heading = get_string('completionswithproblems', 'totara_' . $progorcert);
$PAGE->set_context(context_system::instance());
$PAGE->set_url($url);
$PAGE->set_title($heading);
$PAGE->set_heading($heading);

echo $OUTPUT->header();
echo $OUTPUT->heading($heading);

// Load js.
$PAGE->requires->strings_for_js(array('fixconfirmsome', 'fixconfirmtitle'), 'totara_program');
$PAGE->requires->js_call_amd('totara_program/check_completion', 'init');

$renderer = $PAGE->get_renderer('totara_' . $progorcert);

$data = new stdClass();
$data->url = $url;
$data->progorcert = $progorcert;
$data->programid = $progid;
$data->userid = $userid;
if ($progorcert == 'certification') {
    list($data->fulllist, $data->aggregatelist, $data->totalcount) = certif_get_all_completions_with_errors($progid, $userid);
} else {
    list($data->fulllist, $data->aggregatelist, $data->totalcount) = prog_get_all_completions_with_errors($progid, $userid);
}
if ($progid) {
    $progurl = new moodle_url('/totara/program/completion.php', array('id' => $progid));
    $data->progname = html_writer::link($progurl, $program->fullname);
}
if ($userid) {
    $user = $DB->get_record('user', array('id' => $userid));
    $data->username = fullname($user);
}

echo $renderer->get_completion_checker_results($data);

echo $OUTPUT->footer();
