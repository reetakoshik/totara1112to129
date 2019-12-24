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
 * @package mod_facetoface
 * @author Alastair Munro <alastair.munro@totaralms.com>
 */

namespace mod_facetoface\event;
defined('MOODLE_INTERNAL') || die();

/**
 * Event triggered when a user is signup.
 *
 * @property-read array $other {
 * Extra information about the event.
 *
 * - userid User ID who was signed-up
 * - sessionid Session ID
 *
 * }
 *
 * @author Alastair Munro <alastair.munro@totaralms.com>
 * @package mod_facetoface
 */
class session_signup extends \core\event\base {

    /** @var bool Flag for prevention of direct create() call. */
    protected static $preventcreatecall = true;

    /** @var \stdClass */
    protected $usersignup;

    /**
     * Create instance of event.
     *
     * @param \stdClass $usersignup
     * @param \context_module $context
     * @param int $userid
     * @return session_signup
     */
    public static function create_from_instance(\stdClass $usersignup, \context_module $context) {
        $data = array(
            'context' => $context,
            'objectid' => $usersignup->id,
            'other' => array(
                'userid' => $usersignup->userid,
                'sessionid' => $usersignup->sessionid
            )
        );

        self::$preventcreatecall = false;
        $event = self::create($data);
        self::$preventcreatecall = true;
        $event->usersignup = $usersignup;
        return $event;
    }

    /**
     * Get instance.
     *
     * NOTE: to be used from observers only.
     *
     * @return \stdClass session
     */
    public function get_usersignup() {
        if ($this->is_restored()) {
            throw new \coding_exception('get_usersignup is intended for event observers only');
        }

        return $this->usersignup;
    }

    /**
     * Init method
     *
     * @return void
     */
    protected function init() {
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'facetoface_signups';
    }

    /**
     * Return localised event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventsessionsignup', 'mod_facetoface');
    }

    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description() {
        return "The user with id {$this->other['userid']} has been signed up to session id {$this->other['sessionid']}.";
    }

    /**
     * Returns relevant URL.
     *
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url('/mod/facetoface/attendees.php', array('s' => $this->other['sessionid']));
    }

    /**
     * Return the legacy event log data.
     *
     * @return array
     */
    public function get_legacy_logdata() {
        return array($this->courseid, 'facetoface', 'signup', "signup.php?s={$this->other['sessionid']}",
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

        if (!isset($this->other['userid'])) {
            throw new \coding_exception('userid must be set in $other.');
        }

        if (!isset($this->other['sessionid'])) {
            throw new \coding_exception('sessionid must be set in $other.');
        }

        parent::validate_data();
    }
}
