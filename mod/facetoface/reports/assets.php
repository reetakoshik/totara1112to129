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

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot . '/mod/facetoface/lib.php');
require_once($CFG->dirroot . '/mod/facetoface/rb_sources/rb_facetoface_summary_asset_embedded.php');

use \mod_facetoface\asset;

$assetid = optional_param('assetid', 0, PARAM_INT);
$debug = optional_param('debug', 0, PARAM_INT);

require_login(0, false);

$systemcontext = context_system::instance();
$PAGE->set_context($systemcontext);

$baseurl = new moodle_url('/mod/facetoface/reports/assets.php', array('debug' => $debug));
$PAGE->set_url($baseurl);

if (!$assetid) {
    echo $OUTPUT->header();
    $manageassetsurl = new moodle_url('/mod/facetoface/asset/manage.php');
    echo $OUTPUT->container(get_string('selectanasset', 'rb_source_facetoface_asset_assignments', $manageassetsurl->out()));
    echo $OUTPUT->footer();
    exit();
}

$asset = new asset($assetid);

$report = null;
if (rb_facetoface_summary_asset_embedded::is_capable_static($USER->id)) {
    // Verify global restrictions.
    $shortname = 'facetoface_summary_asset';
    $reportrecord = $DB->get_record('report_builder', array('shortname' => $shortname));
    $globalrestrictionset = rb_global_restriction_set::create_from_page_parameters($reportrecord);
    $config = (new rb_config())->set_global_restriction_set($globalrestrictionset);
    $report = reportbuilder::create_embedded($shortname, $config);
    if (!$report) {
        print_error('error:couldnotgenerateembeddedreport', 'totara_reportbuilder');
    }

    $PAGE->set_button($report->edit_button());
}

$title = get_string('viewasset', 'facetoface');
$PAGE->set_title($title);

$PAGE->set_heading($title);

echo $OUTPUT->header();
/** @var mod_facetoface_renderer $renderer */
$renderer = $PAGE->get_renderer('mod_facetoface');
$renderer->setcontext($systemcontext);

echo $renderer->heading($PAGE->title);

echo $renderer->render_asset_details($asset);

if ($report) {

    /** @var totara_reportbuilder_renderer $reportrenderer */
    $reportrenderer = $PAGE->get_renderer('totara_reportbuilder');
    // This must be done after the header and before any other use of the report.
    list($reporthtml, $debughtml) = $reportrenderer->report_html($report, $debug);
    echo $debughtml;

    $report->display_restrictions();

    echo $renderer->heading(get_string('upcomingsessionsinasset', 'facetoface'));
    echo $reportrenderer->print_description($report->description, $report->_id);

    $report->display_search();
    $report->display_sidebar_search();
    echo $report->display_saved_search_options();
    echo $reporthtml;

    if (!empty($backurl)) {
        echo $renderer->single_button($backurl, get_string('goback', 'facetoface'), 'get');
    }

    if (has_capability('mod/facetoface:addinstance', $systemcontext)) {
        echo $renderer->single_button(new moodle_url('/mod/facetoface/asset/manage.php'), get_string('backtoassets', 'facetoface'), 'get');
    }

    $report->include_js();
}

echo $renderer->footer();
