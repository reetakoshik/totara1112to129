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

class rb_facetoface_events_embedded extends rb_base_embedded {

    public $url, $source, $fullname, $filters, $columns;
    public $contentmode, $contentsettings, $embeddedparams;
    public $hidden, $accessmode, $accesssettings, $shortname;

    public function __construct($data) {
        $this->url = '/mod/facetoface/reports/events.php';
        $this->source = 'facetoface_events';
        $this->shortname = 'facetoface_events';
        $this->fullname = get_string('embedded:seminarevents', 'mod_facetoface');
        $this->columns = array(
            array(
                'type' => 'facetoface',
                'value' => 'namelink',
                'heading' => get_string('ftfname', 'rb_source_facetoface_sessions'),
            ),
            array(
                'type' => 'course',
                'value' => 'courselink',
                'heading' => get_string('coursename', 'totara_reportbuilder'),
            ),
            array(
                'type' => 'session',
                'value' => 'capacity',
                'heading' => get_string('sesscapacity', 'rb_source_facetoface_sessions'),
            ),
            array(
                'type' => 'session',
                'value' => 'overbookingallowed',
                'heading' => get_string('overbookingallowed', 'rb_source_facetoface_summary'),
            ),
            array(
                'type' => 'session',
                'value' => 'numattendeeslink',
                'heading' => get_string('numattendeeslink', 'rb_source_facetoface_summary'),
            ),
            array(
                'type' => 'session',
                'value' => 'bookingstatus',
                'heading' => get_string('bookingstatus', 'rb_source_facetoface_summary'),
            ),
        );

        $this->filters = array(
            array(
                'type' => 'facetoface',
                'value' => 'name',
                'advanced' => 0,
            ),
            array(
                'type' => 'course',
                'value' => 'id',
                'advanced' => 0,
            ),
            array(
                'type' => 'session',
                'value' => 'bookingstatus',
                'advanced' => 0,
            ),
        );

        // No restrictions.
        $this->contentmode = REPORT_BUILDER_CONTENT_MODE_NONE;

        parent::__construct();
    }

    /**
     * Clarify if current embedded report support global report restrictions.
     * Override to true for reports that support GRR
     * @return boolean
     */
    public function embedded_global_restrictions_supported() {
        return true;
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
        return has_capability('mod/facetoface:viewallsessions', context_system::instance(), $reportfor);
    }
}
