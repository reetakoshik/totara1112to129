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
 * @package totara
 * @subpackage reportbuilder
 */

define('REPORTBUIDLER_MANAGE_REPORTS_PAGE', true);
define('REPORT_BUILDER_IGNORE_PAGE_PARAMETERS', true); // We are setting up report here, do not accept source params.

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot.'/totara/reportbuilder/lib.php');

// Check access.
require_sesskey();
require_login();

$PAGE->set_context(context_user::instance($USER->id));

// Get params.
$action = required_param('action', PARAM_ALPHA);
$reportid = required_param('id', PARAM_INT);

// Make sure the report actually exists.
$rawreport = $DB->get_record('report_builder', array('id' => $reportid), '*', MUST_EXIST);

$capability = $rawreport->embedded ? 'totara/reportbuilder:manageembeddedreports' : 'totara/reportbuilder:managereports';
require_capability($capability, context_system::instance());

switch ($action) {
    case 'add':
        $searchcolumn = required_param('searchcolumn', PARAM_ALPHANUMEXT);

        $searchcolumn = explode('-', $searchcolumn);
        $searchcolumntype = $searchcolumn[0];
        $searchcolumnvalue = $searchcolumn[1];

        // Prevent duplicates.
        $params = array('reportid' => $reportid, 'type' => $searchcolumntype, 'value' => $searchcolumnvalue);
        if ($DB->record_exists('report_builder_search_cols', $params)) {
            echo false;
            exit;
        }

        // Save filter.
        $todb = new stdClass();
        $todb->reportid = $reportid;
        $todb->type = $searchcolumntype;
        $todb->value = $searchcolumnvalue;
        $id = $DB->insert_record('report_builder_search_cols', $todb);
        reportbuilder_set_status($reportid);

        echo $id;
        break;
    case 'delete':
        $searchcolumnid = required_param('searchcolumnid', PARAM_INT);

        if ($searchcolumn = $DB->get_record('report_builder_search_cols', array('id' => $searchcolumnid))) {
            $DB->delete_records('report_builder_search_cols', array('id' => $searchcolumnid));
            reportbuilder_set_status($reportid);

            // To be able to sort the filter back into the correct group of the select box
            // we need the translated label
            $config = (new rb_config())->set_nocache(true);
            $reportbuilder = reportbuilder::create($reportid, $config, false); // No access control for managing of reports here.
            $searchcolumn->typelabel = $reportbuilder->get_type_heading($searchcolumn->type);

            echo json_encode((array)$searchcolumn);
        } else {
            echo false;
        }
        break;
    default:
        echo '';
        break;
}
