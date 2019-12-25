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
 * @package totara_reportbuilder
 */

class rb_course_progress_embedded extends rb_base_embedded {

    public $url, $source, $fullname, $filters, $columns;
    public $contentmode, $contentsettings, $embeddedparams;
    public $hidden, $accessmode, $accesssettings, $shortname;
    public $defaultsortcolumn, $defaultsortorder;

    public function __construct($data) {
        global $USER;

        $this->url = '/';
        $this->source = 'dp_course';
        $this->shortname = 'course_progress';
        $this->defaultsortcolumn = 'course_courselink';
        $this->fullname = get_string('mycurrentprogress', 'totara_core');
        $this->columns = array(
            array(
                'type' => 'course',
                'value' => 'coursetypeicon',
                'heading' => get_string('coursetypeicon', 'totara_reportbuilder'),
            ),
            array(
                'type' => 'course',
                'value' => 'courselink',
                'heading' => get_string('coursetitle', 'rb_source_dp_course'),
            ),
            array(
                'type' => 'plan',
                'value' => 'statusandapproval',
                'heading' => get_string('progress', 'rb_source_dp_course'),
            ),
            array(
                'type' => 'course_completion',
                'value' => 'enroldate',
                'heading' => get_string('courseenroldate', 'rb_source_dp_course'),
            ),
            array(
                'type' => 'course',
                'value' => 'startdate',
                'heading' => get_string('coursestartdate', 'totara_reportbuilder'),
            ),
        );

        // No restrictions.
        $this->contentmode = REPORT_BUILDER_CONTENT_MODE_NONE;

        $this->embeddedparams = array();
        $this->embeddedparams['userid'] = $USER->id;
        $this->embeddedparams['rolstatus'] = 'active';
        $this->embeddedparams['enrolled'] = 1;

        parent::__construct();
    }

    /**
     * Check if the user is capable of accessing this report.
     * We use $reportfor instead of $USER->id and $report->get_param_value() instead of getting report params
     * some other way so that the embedded report will be compatible with the scheduler (in the future).
     *
     * @param int $reportfor userid of the user that this report is being generated for
     * @param reportbuilder $report the report object - can use get_param_value to get params
     * @return boolean true if the user can access this report
     */
    public function is_capable($reportfor, $report) {
        // Capability checks are not required as users can only view their own active courses.
        return true;
    }

    /**
     * Check if the report is disabled and should be ignored.
     *
     * @return boolean If the report should be ignored of not.
     */
    public static function is_report_ignored() {
        return !totara_feature_visible('recordoflearning');
    }
}
