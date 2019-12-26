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
 * @author Valerii Kuznetsov <valerii.kuznetsov@totaralms.com>
 * @package totara_reportbuilder
 */

class rb_facetoface_sessions_embedded extends rb_base_embedded {

    public $url, $source, $fullname, $filters, $columns;
    public $contentmode, $contentsettings, $embeddedparams;
    public $hidden, $accessmode, $accesssettings, $shortname;

    /** @var string $returnpage  */
    public $returnpage = 'view';

    public function __construct($data) {
        if (!empty($data['sessionid'])) {
            $this->embeddedparams['sessionid'] = $data['sessionid'];
        }
        if (!empty($data['status'])) {
            $this->embeddedparams['status'] = $data['status'];
        }
        $this->url = '/mod/facetoface/attendees/view.php';
        $this->source = 'facetoface_sessions';
        $this->shortname = 'facetoface_sessions';
        $this->fullname = get_string('embedded:seminareventattendance', 'mod_facetoface');
        $this->columns = array(
            array(
                'type' => 'user',
                'value' => 'namelink',
                'heading' => get_string('name', 'rb_source_user'),
            ),
            array(
                'type' => 'status',
                'value' => 'timecreated',
                'heading' => get_string('timeofsignup', 'rb_source_facetoface_sessions'),
            ),
            array(
                'type' => 'status',
                'value' => 'statuscode',
                'heading' => get_string('status', 'rb_source_facetoface_sessions'),
            ),
            array(
                'type' => 'session',
                'value' => 'jobassignmentnameedit',
                'heading' => get_string('selectedjobassignment', 'mod_facetoface'),
            ),
            array(
                'type' => 'facetoface_signup',
                'value' => 'allsignupcustomfields',
                'heading' => get_string('allsignupcustomfields', 'rb_source_facetoface_sessions')
            )
        );

        $this->filters = array();

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
        $sessionid = $report->get_param_value('sessionid');
        $session = facetoface_get_session($sessionid);

        if ($session) {
            $cm = get_coursemodule_from_instance('facetoface', $session->facetoface);

            // Users can only view this report if they have the viewinterestreport capability for this context.
            return (has_capability('mod/facetoface:viewattendees', context_module::instance($cm->id), $reportfor));
        } else {
            return true;
        }
    }
}
