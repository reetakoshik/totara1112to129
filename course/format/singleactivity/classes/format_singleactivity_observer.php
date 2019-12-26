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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package format_singleactivity
 */

namespace format_singleactivity;
defined('MOODLE_INTERNAL') || die();


/**
 * Class format_singleactivity
 */
class format_singleactivity_observer {
    /**
     * Observer that will be triggered after restoring a course, as this will help to update the
     * course format if the course format is being set to `singleactivity`, whereas when restoring
     * it does not care about the course format, it just restore what needs to be restored.
     *
     * @param \core\event\course_restored $event
     * @return void
     */
    public static function update_course_format(\core\event\course_restored $event) {
        global $DB;

        $course = $DB->get_record('course', array('id' => $event->objectid), '*');
        if (!$course || $course->format != 'singleactivity') {
            return;
        }

        /** @var format_singleactivity $courseformat */
        $courseformat = course_get_format(
            (object) array(
                'id' => $course->id,
                'format' => $course->format
            )
        );

        $courseformat->reorder_activities();
    }
}