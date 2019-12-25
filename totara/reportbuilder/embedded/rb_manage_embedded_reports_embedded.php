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

class rb_manage_embedded_reports_embedded extends rb_base_embedded {

    public $url, $source, $fullname, $filters, $columns;
    public $contentmode, $contentsettings, $embeddedparams;
    public $hidden, $accessmode, $accesssettings, $shortname;
    public $defaultsortcolumn, $defaultsortorder;

    public function __construct($data) {
        $this->url = '/totara/reportbuilder/manageembeddedreports.php';
        $this->source = 'reports';
        $this->defaultsortcolumn = 'report_namelinkeditview';
        $this->shortname = 'manage_embedded_reports';
        $this->fullname = get_string('manageembeddedreports', 'totara_reportbuilder');
        $this->columns = [
            [
                'type' => 'report',
                'value' => 'namelinkeditview',
                'heading' => get_string('reportname', 'totara_reportbuilder'),
            ],
            [
                'type' => 'report',
                'value' => 'source',
                'heading' => get_string('reportsource', 'totara_reportbuilder'),
            ],
            [
                'type' => 'report',
                'value' => 'actions',
                'heading' => get_string('reportactions', 'totara_reportbuilder'),
            ],
        ];

        $this->filters = [
            [
                'type' => 'report',
                'value' => 'name',
            ],
        ];

        // only show embedded reports
        $this->embeddedparams = [
            'embedded' => '1',
        ];

        parent::__construct();
    }

    /**
     * Clarify if current embedded report support global report restrictions.
     * Override to true for reports that support GRR
     * @return boolean
     */
    public function embedded_global_restrictions_supported() {
        return false;
    }

    /**
     * Hide this source if feature disabled or hidden.
     * @return bool
     */
    public static function is_report_ignored() {
        return false;
    }

    /**
     * Check if the user is capable of accessing this report.
     * We use $reportfor instead of $USER->id and $report->get_param_value() instead of getting params
     * some other way so that the embedded report will be compatible with the scheduler (in the future).
     *
     * @param int $reportfor userid of the user that this report is being generated for
     * @param reportbuilder $report the report object - can use get_param_value to get params
     * @return boolean true if the user can access this report
     */
    public function is_capable($reportfor, $report) {
        $syscontext = context_system::instance();
        return has_capability('totara/reportbuilder:manageembeddedreports', $syscontext, $reportfor);
    }
}
