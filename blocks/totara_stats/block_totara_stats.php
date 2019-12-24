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
 * @author Dan Marsden <dan@catalyst.net.nz>
 * @package totara
 * @subpackage blocks_totara_stats
 */

require_once($CFG->dirroot.'/blocks/totara_stats/locallib.php');

class block_totara_stats extends block_base {

    function init() {
        $this->title = get_string('pluginname', 'block_totara_stats');
    }

    function applicable_formats() {
        return array('all' => true);
    }

    function instance_allow_config() {
        return true;
    }

    function specialization() {

    }

    function has_config() {
        return true;
    }

    function get_content() {
        global $CFG, $USER;

        // Check if content is cached
        if($this->content !== NULL) {
            return $this->content;
        }

        $this->content = new stdClass;
        $this->content->text   = '';
        $this->content->footer = '';

        // Hide block if user has no staff
        if (\totara_job\job_assignment::has_staff($USER->id)) {
            // now get sql required to return stats
            $stats = totara_stats_manager_stats($USER, $this->config);
            if (!empty($stats)) {
                $renderer = $this->page->get_renderer('block_totara_stats');
                $this->content->text .= $renderer->display_stats_list(totara_stats_sql_helper($stats));
            }
        }

        //TODO: get stuff from reminders/notifications.

        return $this->content;
    }

    function instance_allow_multiple() {
        return true;
    }

}
