<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2014 onwards Totara Learning Solutions LTD
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
 * @package block_totara_report_graph
 */

// Disable debug messages and any errors in output,
// comment out when debugging or better look into error log!
define('NO_DEBUG_DISPLAY', true);

define('REPORT_BUILDER_IGNORE_PAGE_PARAMETERS', true); // No source params here.

require(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot . '/totara/reportbuilder/lib.php');

$id = required_param('id', PARAM_INT);
$sid = optional_param('sid', '0', PARAM_INT);

require_login();
$context = context_system::instance();
$PAGE->set_context($context);

$reportrecord = $DB->get_record('report_builder', array('id' => $id));

// Verify global restrictions.
$globalrestrictionset = rb_global_restriction_set::create_from_page_parameters($reportrecord);

$config = new rb_config();
$config->set_sid($sid)->set_global_restriction_set($globalrestrictionset);
$report = reportbuilder::create($id, $config);

if (!reportbuilder::is_capable($id)) {
    print_error('nopermission', 'totara_reportbuilder');
}

// Release session lock - most of the access control is over
// and we want to mess with session data and improve perf.
\core\session\manager::write_close();

$graph = new \totara_reportbuilder\local\graph($report);
if (!$graph->is_valid()) {
    // This should not happen.
    die;
}

list($sql, $params, $cache) = $report->build_query(false, true, true);

$reportdb = $report->get_report_db();
$records = $reportdb->get_recordset_sql($sql, $params, 0, $graph->get_max_records());
foreach ($records as $record) {
    $graph->add_record($record);
}

$svgdata = $graph->fetch_svg();

require_once $CFG->libdir . '/pdflib.php';
$pdf = new \PDF('L', 'mm', 'A3', true, 'UTF-8');
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->AddPage();
$pdf->ImageSVG('@'.$svgdata, 30, 10, 210, 100);
$pdfdata = $pdf->Output('graph.pdf', 'S');

send_headers('application/pdf', false);
echo $pdfdata;
