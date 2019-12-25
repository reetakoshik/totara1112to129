<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2014 onwards Totara Learning Solutions LTD
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
 * @author Alastair Munro <alastair.munro@totaralms.com>
 * @package mod_facetoface
 */

namespace mod_facetoface\event;
defined('MOODLE_INTERNAL') || die();

/**
 * Event triggered when an attendee's note is updated.
 *
 * @property-read array $other {
 * Extra information about the event.
 *
 * - attendeeid Attendee's ID of the note to update.
 * - sessionid Session's ID.
 *
 * }
 *
 * @author Alastair Munro <alastair.munro@totaralms.com>
 * @package mod_facetoface
 */
class attendee_note_updated extends \core\event\base {

    /** @var bool Flag for prevention of direct create() call. */
    protected static $preventcreatecall = true;

    /** @var \stdClass */
    protected $instance;

    /**
     * Create instance of event.
     *
     * @param \stdClass $attendee
     * @param \context_module $context
     * @return attendee_note_update
     */
    public static function create_from_instance(\stdClass $attendee, \context_module $context) {
        $data = array(
            'context' => $context,
            'objectid' => $attendee->id,
            'other' => array(
                'attendeeid' => $attendee->userid,
                'sessionid'  => $attendee->sessionid
            )
        );

        self::$preventcreatecall = false;
        $event = self::create($data);
        self::$preventcreatecall = true;
        $event->instance = $attendee;

        return $event;
    }


    /**
     * Get instance.
     *
     * NOTE: to be used from observers only.
     *
     * @return \stdClass instance
     */
    public function get_instance() {
        if ($this->is_restored()) {
            throw new \coding_exception('get_instance is intended for event observers only');
        }

        return $this->instance;
    }
    /**
     * Init method
     *
     * @return void
     */
    protected function init() {
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_TEACHING;
        $this->data['objecttable'] = 'facetoface_signups_status';
    }

    /**
     * Return localised event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventattendeenoteupdated', 'mod_facetoface');
    }

    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description() {
        $description  = "The attendee note for User with id {$this->other['attendeeid']} ";
        $description .= "for Session with id {$this->other['sessionid']} has been updated by User with id {$this->userid}.";
        return $description;
    }

    /**
     * Returns relevant URL.
     *
     * @return \moodle_url
     */
    public function get_url() {
        $params = array('id' => $this->other['attendeeid'], 's' => $this->other['sessionid']);
        return new \moodle_url('/mod/facetoface/attendees.php', $params);
    }

    /**
     * Return the legacy event log data.
     *
     * @return array
     */
    public function get_legacy_logdata() {
        return array($this->courseid, 'facetoface', 'update attendee note',
            "attendee_note.php?id={$this->other['attendeeid']}&s={$this->other['sessionid']}",
            $this->other['sessionid'], $this->contextinstanceid);
    }

    /**
     * Custom validation.
     *
     * @return void
     */
    protected function validate_data() {
        if (self::$preventcreatecall) {
           throw new \coding_exception('cannot call create() directly, use create_from_instance() instead.');
        }

        if (!isset($this->other['sessionid'])) {
            throw new \coding_exception('sessionid must be set in $other.');
        }

        if (!isset($this->other['attendeeid'])) {
            throw new \coding_exception('attendeeid must be set in $other.');
        }

        parent::validate_data();
    }
}
