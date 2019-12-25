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
 * @author Simon Player <simon.player@totaralearning.com>
 * @package availability_language
 */

namespace availability_language;

defined('MOODLE_INTERNAL') || die();

/**
 * Front-end class.
 */
class frontend extends \core_availability\frontend {

    /**
     * Get the strings required in the javascript
     *
     * @return array
     */
    protected function get_javascript_strings() {
        return array('conditiontitle');
    }

    /**
     * Gets initial params used in the javascript
     *
     * @param \stdClass $course
     * @param \cm_info|null $cm The course module
     * @param \section_info|null $section
     * @return array
     */
    protected function get_javascript_init_params($course, \cm_info $cm = null,
            \section_info $section = null) {

        $langs = get_string_manager()->get_list_of_translations();

        // Make arrays into JavaScript format (non-associative, ordered) and return.
        return array(self::convert_associative_array_for_js($langs, 'field', 'display'));
    }
}
