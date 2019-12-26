<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Courteney Brownie <courteney.brownie@totaralearning.com>
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package tool_sitepolicy
 *
 * @global moodle_database $DB
 * @global moodle_page $PAGE
 */

require(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot . '/totara/reportbuilder/lib.php');

$sid = optional_param('sid', 0, PARAM_INT); // Report: saved search.
$format = optional_param('format', false, PARAM_TEXT); // Report: export.
$debug = optional_param('debug', 0, PARAM_INT); // Report: debug turned on/off.

if (!empty($CFG->enablesitepolicies)) {
    admin_externalpage_setup('tool_sitepolicy-userconsentreport');
} else {
    // If not enabled, the left menu is not initialised.
    // Set context and url manually
    $PAGE->set_context(null); // set context to system context
    require_login();
    $url = new moodle_url('/admin/toot/sitepolicy/sitepolicyreport.php');
    $PAGE->set_url($url);
}

// Verify global restrictions.
$reportrecord = $DB->get_record('report_builder', array('shortname' => 'tool_sitepolicy'));
$globalrestrictionset = rb_global_restriction_set::create_from_page_parameters($reportrecord);
// Get the embedded report.
$config = (new rb_config())
    ->set_global_restriction_set($globalrestrictionset)
    ->set_embeddata(['userid' => $USER->id]) // Default to current user.
    ->set_sid($sid);
$report = reportbuilder::create_embedded('tool_sitepolicy', $config);
if (!$report) {
    print_error('error:couldnotgenerateembeddedreport', 'totara_reportbuilder');
}
// Export the data, if required.
if ($format) {
    $report->export_data($format);
    die;
}

\totara_reportbuilder\event\report_viewed::create_from_report($report)->trigger();

/** @var \tool_sitepolicy\output\page_renderer $renderer */
$renderer = $PAGE->get_renderer('tool_sitepolicy', 'page');
$PAGE->set_button($report->edit_button());
echo $renderer->consent_report($report, $debug, $sid);
