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
 * @author Simon Coggins <simon.coggins@totaralearning.com>
 * @package totara_reportbuilder
 */

/**
 * Restrict content by report access
 *
 * Note that this content restriction restricts to reports you are
 * allowed to see, which is a logical OR of:
 * 1) Reports which you have been given access to (via Access tab)
 * 2) All user reports, if you are allowed to manage user reports
 * 2) All embedded reports, if you are allowed to manage embedded reports
 *
 * It does NOT take into account results from the is_capable() method on
 * embedded reports because of the per-report cost of doing so.
 */
class rb_report_access_content extends rb_base_content {

    /**
     * Generate the SQL to apply this content restriction
     *
     * @param string $field SQL field to apply the restriction against
     * @param integer $reportid ID of the report
     *
     * @return array containing SQL snippet to be used in a WHERE clause, as well as array of SQL params
     */
    public function sql_restriction($field, $reportid) {
        global $DB;

        $params = [];
        $norestriction = [" 1=1 ", $params]; // No restrictions.
        $restriction   = [" 1=0 ", $params]; // Restrictions.

        $type = substr(get_class($this), 3);
        $enable = reportbuilder::get_setting($reportid, $type, 'enable');
        if (!$enable) {
            return $norestriction;
        }

        $syscontext = context_system::instance();
        $manageuserreports = has_capability('totara/reportbuilder:managereports', $syscontext);
        $manageembeddedreports = has_capability('totara/reportbuilder:manageembeddedreports', $syscontext);
        // If you can manage both user and embedded reports we know you can see all reports.
        // No need to check them individually.
        if ($manageuserreports && $manageembeddedreports) {
            return $norestriction;
        }

        $sqls = [];

        // Check direct access to reports.

        // Using IN() here could perform badly with enough records, but there is no other way to do it in pure SQL for report builder.
        // It's probably safe because:
        // 1. A user who can view all reports won't use this code (they will have the capabilities above so they see everything.
        // 2. We are assuming that a site doesn't have tens or hundreds of thousands of reports,
        // all of which are individually shared with this user.
        $allowedreports = reportbuilder::get_permitted_reports($this->reportfor);
        $allowedreportids = array_map(function($obj) { return $obj->id; }, $allowedreports);
        list($sqlin, $params) = $DB->get_in_or_equal($allowedreportids, SQL_PARAMS_NAMED);
        $sqls[] = "{$field} {$sqlin}";

        // If you have managereports you can see all user reports.
        if ($manageuserreports) {
            $sqls[] = "{$field} IN (SELECT id FROM {report_builder} WHERE embedded = 0)";
        }

        // If you have manageembeddedreports you can see all embedded reports.
        if ($manageembeddedreports) {
            $sqls[] = "{$field} IN (SELECT id FROM {report_builder} WHERE embedded = 1)";
        }

        $sql = "(\n" . implode(" OR \n", $sqls) . ")\n";
        $restriction = [$sql, $params];
        return $restriction;
    }

    /**
     * Generate a human-readable text string describing the restriction
     *
     * @param string $title Name of the field being restricted
     * @param integer $reportid ID of the report
     *
     * @return string Human readable description of the restriction
     */
    public function text_restriction($title, $reportid) {
        return get_string('accessiblereportsonly', 'totara_reportbuilder');
    }

    /**
     * Adds form elements required for this content restriction's settings page
     *
     * @param object &$mform Moodle form object to modify (passed by reference)
     * @param integer $reportid ID of the report being adjusted
     * @param string $title Name of the field the restriction is acting on
     */
    public function form_template(&$mform, $reportid, $title) {


        $type = substr(get_class($this), 3);
        $enable = reportbuilder::get_setting($reportid, $type, 'enable');

        $mform->addElement('header', 'report_access', get_string('showbyx', 'totara_reportbuilder', get_string('reportaccess', 'totara_reportbuilder')));
        $mform->setExpanded('report_access');
        $mform->addElement('checkbox', 'report_access_enable', '',
            get_string('showbasedonx', 'totara_reportbuilder', get_string('reportaccess', 'totara_reportbuilder')));
        $mform->setDefault('report_access_enable', $enable);
        $mform->disabledIf('report_access_enable', 'contentenabled', 'eq', 0);

    }

    /**
     * Processes the form elements created by {@link form_template()}
     *
     * @param integer $reportid ID of the report to process
     * @param object $fromform Moodle form data received via form submission
     *
     * @return boolean True if form was successfully processed
     */
    public function form_process($reportid, $fromform) {

        $status = true;
        $type = substr(get_class($this), 3);
        $enable = (isset($fromform->report_access_enable) && $fromform->report_access_enable) ? 1 : 0;
        $status = $status && reportbuilder::update_setting($reportid, $type, 'enable', $enable);

        return $status;
    }
}
