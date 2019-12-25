<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2015 onwards Totara Learning Solutions LTD
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
 * @author Petr Skoda <petr.skoda@totaralms.com>
 * @package totara_core
 */

namespace totara_core\event;

defined('MOODLE_INTERNAL') || die();

/**
 * Event triggered when course is archived.
 *
 * @since   Totara 2.7
 * @author  Petr Skoda <petr.skoda@totaralms.com>
 * @package totara_core
 */
class course_completion_archived extends \core\event\base {
    /**
     * Create instance of event.
     *
     * @param \stdClass $course
     * @return course_completion_archived
     */
    public static function create_from_course(\stdClass $course) {
        $data = array(
            'objectid' => $course->id,
            'context' => \context_course::instance($course->id),
        );
        $event = self::create($data);
        $event->add_record_snapshot('course', $course);
        return $event;
    }

    /**
     * Init method.
     *
     * @return void
     */
    protected function init() {
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->data['objecttable'] = 'course';
    }

    /**
     * Return localised event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventcoursearchived', 'totara_core');
    }

    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description() {
        return "Course with id '$this->objectid' was archived";
    }

    /**
     * Returns relevant URL.
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url('/course/archivecompletions.php', array('id' => $this->contextinstanceid));
    }

    protected function get_legacy_logdata() {
        $course = $this->get_record_snapshot('course', $this->data['objectid']);
        return array(SITEID, "course", "archive", "archivecompletions.php?id=$course->id", "$course->fullname (ID $course->id)");
    }
}
