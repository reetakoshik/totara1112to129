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
 * @author Nathan Lewis <nathan.lewis@totaralearning.com>
 * @package totara_reportbuilder
 */

class rb_course_membership_embedded extends rb_base_embedded {

    public function __construct($data) {
        $this->url = '/totara/completioneditor/course_completion.php';
        $this->source = 'course_membership';
        $this->shortname = 'course_membership';
        $this->fullname = get_string('coursemembership', 'totara_completioneditor');
        $this->columns = array(
            array(
                'type' => 'user',
                'value' => 'namelink',
                'heading' => get_string('name', 'rb_source_user'),
            ),
            array(
                'type' => 'coursemembership',
                'value' => 'editcoursecompletion',
                'heading' => get_string('coursecompletionedit', 'totara_completioneditor'),
            ),
        );

        $this->filters = array(
            array(
                'type' => 'user',
                'value' => 'fullname',
                'advanced' => 0,
            ),
        );

        $this->contentmode = REPORT_BUILDER_CONTENT_MODE_NONE;

        $this->embeddedparams = array();
        if (isset($data['courseid'])) {
            $this->embeddedparams['courseid'] = $data['courseid'];
        }

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
        $context = context_course::instance($report->get_param_value('courseid'));
        return has_capability('totara/completioneditor:editcoursecompletion', $context, $reportfor);
    }
}
