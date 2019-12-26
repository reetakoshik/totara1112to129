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
 * @author Russell England <russell.england@catalyst-eu.net>
 * @package totara
 * @subpackage block_activity_status
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Certifications block
 *
 * Displays upcoming certifications
 */
class block_activity_status extends block_base {

    public function init() {
        $this->title   = get_string('pluginname', 'block_activity_status');
    }

    function get_content() {
        global $CFG, $USER, $DB;

        if ($this->content !== NULL) {
            return $this->content;
        }

        $this->content = new stdClass();

        // get all the mentees, i.e. users you have a direct assignment to
         $this->content->text ='';
        if(is_siteadmin()) {
            $this->content->text .= '<a href="'.$CFG->wwwroot.'/blocks/activity_status/index.php">  Activity Enable/Disable </a><br>';
        }
        $this->content->footer = '';

        return $this->content;
    }
}
