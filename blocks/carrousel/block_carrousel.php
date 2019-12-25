<?php
/*
 * This file is part of Totara LMS
*
* Copyright (C) 2010-2013 Totara Learning Solutions LTD
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
* @author Rafi Eliasaf <rafi.eliasaf@kineo.co.il>
* @package totara
* @subpackage course
*/

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/blocks/carrousel/locallib.php');

class block_carrousel extends block_base {

    function init() {
        $this->title = get_string('pluginname', 'block_carrousel');
    }

    function has_config() {
        return false;
    }

    /**
     * Return block title only if it's available
     * @return bool
     */
    public function hide_header() {
        return (bool) !$this->title;
    }

    function applicable_formats() {
        return array('all' => true);
    }

    function specialization() {
        $this->title = get_string('pluginname', 'block_carrousel');
    }

    /**
     * Instances per page
     * @return bool
     */
    function instance_allow_multiple() {
        return false;
    }

    function get_content() {
        global $CFG, $COURSE, $PAGE;

        if ($this->content !== NULL) {
            return $this->content;
        }

        $this->content = new stdClass;
        $this->content->footer = '';
        $this->content->text = block_carrousel_render($this->instance->id);
        $this->title = !$this->content->text ? get_string('title_when_empty', 'block_carrousel') : '';

        $url = new moodle_url(
            '/blocks/carrousel/index.php',
            array(
                'blockid'  => $this->instance->id, 
                'courseid' => $COURSE->id
            )
        );

        if ($PAGE->user_is_editing()) {
            $this->content->footer = html_writer::link($url, get_string('editslides', 'block_carrousel'));
        }
        
        return $this->content;
    }

}

