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
 * Reminder event
 *
 * @property-read array $other {
 *      Extra information about the event.
 *
 *      - string type: type of reminder.
 *      - string title: title of reminder.
 * }
 *
 * @since   Totara 2.7
 * @author  Petr Skoda <petr.skoda@totaralms.com>
 * @package totara_core
 */
class reminder_deleted extends \core\event\base {
    /** @var \reminder */
    protected $reminder;
    /**
     * Create instance of event.
     *
     * @param \reminder $reminder
     * @return reminder_deleted
     */
    public static function create_from_reminder(\reminder $reminder) {
        $data = array(
            'objectid' => $reminder->id,
            'context' => \context_course::instance($reminder->courseid),
            'other' => array(
                'type' => $reminder->type,
                'title' => $reminder->title,
            ),
        );
        /** @var reminder_deleted $event */
        $event = self::create($data);
        $event->reminder = $reminder;
        return $event;
    }

    /**
     * Return relevant reminder.
     * @return \reminder
     */
    public function get_reminder() {
        if ($this->is_restored()) {
            throw new \coding_exception('get_reminder() is intended for event observers only');
        }
        return $this->reminder;
    }

    /**
     * Init method.
     *
     * @return void
     */
    protected function init() {
        $this->data['crud'] = 'd';
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->data['objecttable'] = 'reminder';
    }

    /**
     * Return localised event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventreminderdeleted', 'totara_core');
    }

    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description() {
        return "Reminder with title '". $this->other['title'] . "' was deleted";
    }

    /**
     * Returns relevant URL.
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url('/course/reminders.php', array('courseid' => $this->contextinstanceid));
    }

    protected function get_legacy_logdata() {
        return array($this->reminder->courseid, 'course', 'reminder deleted',
            'reminders.php?courseid='.$this->reminder->courseid, $this->reminder->title);
    }
}
