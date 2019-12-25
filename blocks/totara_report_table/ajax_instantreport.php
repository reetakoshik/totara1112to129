<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2015 onwards Totara Learning Solutions LTD
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
 * @author Petr Skoda <petr.skoda@totaralms.com>
 * @package block_totara_report_table
 */

/**
 * Page for returning report table for AJAX call.
 *
 * NOTE: this is a clone of /totara/reportbuilder/ajax/instantreport.php
 */

define('AJAX_SCRIPT', true);

require(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/totara/reportbuilder/lib.php');

$blockid = required_param('blockid', PARAM_INT);

$blockcontext = context_block::instance($blockid, MUST_EXIST);
list($context, $course, $cm) = get_context_info_array($blockcontext->id);

if (empty($course)) {
    $PAGE->set_context(context_system::instance());
}
if ($CFG->forcelogin) {
    require_login($course, false, $cm, false, true);
} else {
    require_course_login($course, false, $cm, false, true);
}

require_capability('moodle/block:view', $blockcontext);

// Send the correct headers.
send_headers('text/html; charset=utf-8', false);

$block = $DB->get_record('block_instances', array('id' => $blockid, 'blockname' => 'totara_report_table'), '*', MUST_EXIST);

if (empty($block->configdata)) {
    die;
}

$config = unserialize(base64_decode($block->configdata));
if (empty($config->reportid)) {
    die;
}
$id = $config->reportid;

// Verify global restrictions.
$reportrecord = $DB->get_record('report_builder', array('id' => $id), '*', MUST_EXIST);
$globalrestrictionset = rb_global_restriction_set::create_from_page_parameters($reportrecord);

// Create the report object. Includes embedded report capability checks.
$uniqueid = 'block_totara_report_table_' . $blockid;
reportbuilder::overrideuniqueid($uniqueid);
$config = (new rb_config())->set_global_restriction_set($globalrestrictionset);
$report = reportbuilder::create($id, $config, true);

$PAGE->set_context($blockcontext);
$PAGE->set_pagelayout('noblocks');

\totara_reportbuilder\event\report_viewed::create_from_report($report)->trigger();

/** @var totara_reportbuilder_renderer $output */
$output = $PAGE->get_renderer('totara_reportbuilder');

// Construct the output which consists of a report, header and (eventually) sidebar filter counts.
// We put the data in a container so that jquery can search inside it.
echo html_writer::start_div('instantreportcontainer');

// Show report results.
$report->display_table();
$report->display_sidebar_search();

// Display heading including filtering stats.
echo $output->result_count_info($report);

// Close the container.
echo html_writer::end_div();
