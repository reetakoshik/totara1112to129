<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2016 onwards Totara Learning Solutions LTD
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
 * Block for displaying the last course accessed by the user.
 *
 * @package block_last_course_accessed
 * @author Rob Tyler <rob.tyler@totaralearning.com>
 */

namespace block_last_course_accessed;

defined('MOODLE_INTERNAL') || die();

/**
 * Helper.
 *
 * @deprecated Since Totara 10.0.
 * @deprecated As the class currently only contains a single method, if no
 * @deprecated further methods are added to the class it will be deprecated
 * @deprecated and removed in a later version, otherwise only the deprecated
 * @deprecated method will be removed.
 */
class helper {

    /**
     * Using a timestamp return a natural language string describing the
     * timestamp relative to the current time provided by the web server.
     *
     * @deprecated since Totara 10.0.
     *
     * @param integer $timestamp Describes the last access time in a timestamp.
     * @param integer $compare_to Describes what time the comparison should be made against.
     * @return string Natural language string describing the time difference.
     */
    public static function get_last_access_text($timestamp, $compare_to = null) {

        debugging('This function has been deprecated and replaced by totara_core_get_relative_time_text().', DEBUG_DEVELOPER);

        return totara_core_get_relative_time_text($timestamp, $compare_to, true);
    }

}