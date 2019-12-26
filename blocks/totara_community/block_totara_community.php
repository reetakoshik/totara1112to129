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
 * Block for displaying user-defined links
 *
 * @package   block_totara_community
 * @author    Carl Anderson <carl.anderson@totaralearning.com>
 */

defined('MOODLE_INTERNAL') || die();

class block_totara_community extends block_base {

    public function init() {
        $this->title   = get_string('totara_community:blocktitle', 'block_totara_community');
    }

    public function display_with_header() : bool {
        return false;
    }

    protected function display_with_border_by_default() {
        return false;
    }

    public function get_content() {
        global $OUTPUT;

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass;
        $this->content->items = array();
        $this->content->icons = array();
        $this->content->footer = '';

        $this->content->text = $OUTPUT->render_from_template('block_totara_community/content', array());
        return $this->content;
    }

    /**
     * Override parent function to always hide if user is not a site admin
     * @param block_contents $bc
     */
    public function is_content_hidden(\block_contents $bc) {
        if (has_capability('block/totara_community:view', context_system::instance())) {
            parent::is_content_hidden($bc);
        } else {
            $bc->collapsible = block_contents::HIDDEN;
        }
    }

    public function applicable_formats() {
        return ['site-index' => true];
    }
}