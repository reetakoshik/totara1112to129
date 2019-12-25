<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
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
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 * @package block_course_search
 */

/**
 * Main block file
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Main block class
 *
 * @property \stdClass $content
 */
class block_course_search extends block_base {

    /**
     * Initialises this block instance.
     */
    public function init() {
        $this->title = get_string('pluginname', 'block_course_search');
    }

    /**
     * Where this block can be added.
     *
     * @return array
     */
    public function applicable_formats() {
        return array('all' => true);
    }

    /**
     * Whether multiple instance of this block can be added to a page.
     *
     * @return bool
     */
    public function instance_allow_multiple() {
        return true; // It doesn't make sense, but it doesn't hurt to.
    }

    /**
     * Returns the content for this block.
     *
     * @return stdClass
     */
    public function get_content() {
        if ($this->content !== null) {
            return $this->content;
        }

        /** @var \block_course_search\output\renderer $output */
        $output = $this->page->get_renderer('block_course_search');

        $this->content = new stdClass();
        $this->content->text = $output->search_form();
        $this->content->footer = '';
        return $this->content;
    }

}
