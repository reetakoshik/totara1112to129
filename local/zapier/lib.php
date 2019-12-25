<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

defined('MOODLE_INTERNAL') || die();

class zapier_event_handler {
    
    /**
     * Triggered when 'course_completed' event is triggered.
     *
     * @param \core\event\course_completed $event
     */
    public static function course_completed(\core\event\course_completed $event) {
        global $DB;

        $data = new stdClass();
        $data->eventtype = 'course_completion';
        $data->core_event_id = $event->objectid;

        try {
            $DB->insert_records('local_zapier_events_observer', array($data));
        } catch (dml_exception $e) {
            throw $e->getMessage();
        }
    }

}
