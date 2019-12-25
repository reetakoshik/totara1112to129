<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2017 onwards Totara Learning Solutions LTD
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
 * @author Murali Nair <murali.nair@totaralearning.com>
 * @package totara_reportbuilder
 */

final class rb_manage_scheduled_reports_embedded extends rb_base_embedded {

    public $url, $source, $fullname, $filters, $columns;
    public $contentmode, $contentsettings, $embeddedparams;
    public $hidden, $accessmode, $accesssettings, $shortname;
    public $defaultsortcolumn, $defaultsortorder;

    public function __construct($data) {
        $this->url = '/totara/reportbuilder/managescheduledreports.php';
        $this->source = 'scheduled_reports';
        $this->shortname = 'manage_scheduled_reports';
        $this->fullname = get_string('managescheduledreports', 'totara_reportbuilder');

        $this->columns = $this->define_columns();
        $this->filters = $this->define_filters();

        parent::__construct();
    }

    /**
     * Indicares if the report supports global report restrictions (GRR).
     *
     * @return boolean true if the report supports GRR.
     */
    public function embedded_global_restrictions_supported() {
        return false;
    }

    private function define_columns() {
        return [
            [
                'type' => 'report',
                'value' => 'namelinkview',
                'heading' => get_string('reportname', 'totara_reportbuilder'),
            ],
            [
                'type' => 'user',
                'value' => 'fullname',
                'heading' => get_string('userfullname', 'totara_reportbuilder'),
            ],
            [
                'type' => 'schedule',
                'value' => 'format',
                'heading' => get_string('format', 'rb_source_scheduled_reports')
            ],
            [
                'type' => 'schedule',
                'value' => 'schedule',
                'heading' => get_string('schedule', 'rb_source_scheduled_reports')
            ],
            [
                'type' => 'schedule',
                'value' => 'next',
                'heading' => get_string('nextschedule', 'rb_source_scheduled_reports')
            ],
            [
                'type' => 'schedule',
                'value' => 'user_modified',
                'heading' => get_string('user_modified', 'rb_source_scheduled_reports')
            ],
            [
                'type' => 'schedule',
                'value' => 'last_modified',
                'heading' => get_string('last_modified', 'rb_source_scheduled_reports')
            ],
            [
                'type' => 'schedule',
                'value' => 'actions',
                'heading' => get_string('actions', 'rb_source_scheduled_reports')
            ]
        ];
    }

    private function define_filters() {
        return [
            [
                'type' => 'report',
                'value' => 'name',
                'advanced' => 0
            ],
            [
                'type' => 'user',
                'value' => 'fullname',
                'advanced' => 0
            ],
            [
                'type' => 'schedule',
                'value' => 'format',
                'advanced' => 0
            ],
        ];
    }

    /**
     * Check if the user is capable of accessing this report.
     *
     * @param int $reportfor userid for which this report is being generated.
     * @param reportbuilder $report the parent report.
     *
     * @return boolean true if the user can access this report
     */
    public function is_capable($reportfor, $report) {
        return has_capability(
            'totara/reportbuilder:managescheduledreports',
            context_system::instance(),
            $reportfor
        );
    }
}
