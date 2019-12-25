<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2015 onwards Totara Learning Solutions LTD
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
 */

defined('MOODLE_INTERNAL') || die();

class rb_facetoface_summary_asset_embedded extends rb_base_embedded {

    public function __construct($data) {
        $this->url = '/mod/facetoface/reports/assets.php';
        $this->source = 'facetoface_asset_assignments';
        $this->shortname = 'facetoface_summary_asset';
        $this->fullname = get_string('embedded:seminarassetsupcoming', 'mod_facetoface');
        $this->columns = array(
            array('type' => 'facetoface', 'value' => 'name', 'heading' => null),
            array('type' => 'session', 'value' => 'numattendeeslink', 'heading' => get_string('numberofattendees', 'facetoface')),
            array('type' => 'session', 'value' => 'capacity', 'heading' => null),
            array('type' => 'date', 'value' => 'sessionstartdate', 'heading' => null),
            array('type' => 'session', 'value' => 'bookingstatus', 'heading' => null),
            array('type' => 'session', 'value' => 'overallstatus', 'heading' => null),
        );

        $this->filters = array();

        $this->contentmode = REPORT_BUILDER_CONTENT_MODE_ALL;

        $this->contentsettings = array(
            'date' => array(
                'enable' => 1,
                'when' => 'future'
            )
        );

        parent::__construct();
    }

    public function is_capable($reportfor, $report) {
        return self::is_capable_static($reportfor);
    }

    /**
     * Allow to check capability without instance creation
     * @param int $reportfor user id
     * @return bool
     */
    public static function is_capable_static($reportfor) {
        $systemcontext = context_system::instance();
        return has_capability('mod/facetoface:addinstance', $systemcontext, $reportfor);
    }
}
