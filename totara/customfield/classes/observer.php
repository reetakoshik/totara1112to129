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
 * @author Valerii Kuznetsov <valerii.kuznetsov@totaralms.com>
 * @package totara_customfield
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Totara customfield event handler.
 */
class totara_customfield_observer {
    /**
     * Triggered via course_deleted event.
     * - Removes course customfield data
     *
     * @param \core\event\course_deleted $event
     * @return bool true on success
     */
    public static function course_deleted(\core\event\course_deleted $event) {
        global $DB;
        $DB->get_records('course_info_data', array('courseid' => $event->objectid));

        $fields = $DB->get_fieldset_select(
            'course_info_data',
            'id',
            "courseid = :courseid",
            array('courseid' => $event->objectid)
        );

        if (!empty($fields)) {
            list($sqlin, $paramsin) = $DB->get_in_or_equal($fields);
            $DB->delete_records_select('course_info_data_param', "dataid {$sqlin}", $paramsin);
            $DB->delete_records_select('course_info_data', "id {$sqlin}", $paramsin);
        }

        return true;
    }

    /**
     * Triggered via program_deleted event.
     * - Removes program customfield data
     *
     * @param \totara_program\event\program_deleted $event
     * @return bool true on success
     */
    public static function program_deleted(\totara_program\event\program_deleted $event) {
        global $DB;

        $fields = $DB->get_fieldset_select(
            'prog_info_data',
            'id',
            "programid = :programid",
            array('programid' => $event->objectid)
        );

        if (!empty($fields)) {
            list($sqlin, $paramsin) = $DB->get_in_or_equal($fields);
            $DB->delete_records_select('prog_info_data_param', "dataid {$sqlin}", $paramsin);
            $DB->delete_records_select('prog_info_data', "id {$sqlin}", $paramsin);
        }

        return true;
    }
}
