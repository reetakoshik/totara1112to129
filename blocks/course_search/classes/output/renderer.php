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
 * Course search block renderer
 */

namespace block_course_search\output;

defined('MOODLE_INTERNAL') || die();

/**
 * Course search block renderer
 *
 * @package block_course_search
 */
class renderer extends \plugin_renderer_base {

    /**
     * Renders html to display a course search form
     *
     * @param string $value default value to populate the search field
     * @return string
     */
    public function search_form($value = '') {
        static $count = 0;
        // Ensure we have a unique count. This gets used on ids in the form.
        $count++;

        $data = new \stdClass;
        $data->count = $count;
        $data->searchurl = new \moodle_url('/course/search.php');
        $data->value = $value;
        return $this->render_from_template('block_course_search/search_form', $data);
    }

}
