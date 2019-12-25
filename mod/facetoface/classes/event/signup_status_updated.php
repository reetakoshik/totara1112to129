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
 * @author Andrew Hancox <andrewdchancox@googlemail.com> on behalf of Synergy Learning
 * @package mod_facetoface
 */

namespace mod_facetoface\event;
defined('MOODLE_INTERNAL') || die();

/**
 * Event triggered when the signup status for a user has been updated.
 *
 * @property-read array $other {
 * Extra information about the event.
 *
 * - userid User ID which status has been updated
 * - sessionid Session ID where the action occurs
 *
 * }
 *
 * @author Alastair Munro <alastair.munro@totaralms.com>
 * @package mod_facetoface
 */
class signup_status_updated extends \core\event\base {

    /** @var bool Flag for prevention of direct create() call. */
    protected static $preventcreatecall = true;

    /** @var \stdClass */
    protected $signupstatus;

    /**
     * Create instance of event.
     *
     * @param \stdClass $signupstatus
     * @param \context_module $context
     * @param \stdClass $signup
     * @return signup_status_updated
     */
    public static function create_from_signup(\stdClass $signupstatus, \context_module $context, \stdClass $signup) {
        $data = array(
            'context' => $context,
            'objectid' => $signupstatus->id,
            'other' => array(
                'userid' => (int) $signup->userid,
                'sessionid' => (int) $signup->sessionid,
                'statuscode' => (int) $signupstatus->statuscode
            )
        );

        self::$preventcreatecall = false;
        $event = self::create($data);
        self::$preventcreatecall = true;
        $event->signupstatus = $signupstatus;

        return $event;
    }

    /**
     * Get session instance.
     *
     * NOTE: to be used from observers only.
     *
     * @return \stdClass session
     */
    public function get_signupstatus() {
        if ($this->is_restored()) {
            throw new \coding_exception('get_signupstatus is intended for event observers only');
        }

        return $this->signupstatus;
    }

    /**
     * Initialise the event data.
     */
    protected function init() {
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_TEACHING;
        $this->data['objecttable'] = 'facetoface_signups_status';
    }

    public static function get_name() {
        return get_string('eventsignupstatusupdated', 'facetoface');
    }

    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description() {
        global $MDL_F2F_STATUS;
        $description  = "A {$MDL_F2F_STATUS[$this->other['statuscode']]} status was set for ";
        $description .= "User with id {$this->other['userid']} in Session id {$this->other['sessionid']}.";
        return $description;
    }

    /**
     * Return the legacy event name.
     *
     * @return string
     */
    public static function get_legacy_eventname() {
        return 'mod_facetoface_statusupdated';
    }

    /**
     * Legacy event data if get_legacy_eventname() is not empty.
     *
     * Note: do not use directly!
     *
     * @return mixed
     */
    protected function get_legacy_eventdata() {
        $data = $this->get_data();
        $snapshot = $this->get_record_snapshot('facetoface_signups_status', $data['objectid']);
        return $snapshot;
    }

    /**
     * Custom validation.
     *
     * @return void
     */
    protected function validate_data() {
        global $MDL_F2F_STATUS;
        if (self::$preventcreatecall) {
           throw new \coding_exception('cannot call create() directly, use create_from_signup() instead.');
        }

        if (!isset($this->other['userid'])) {
            throw new \coding_exception('userid must be set in $other.');
        }

        if (!isset($this->other['sessionid'])) {
            throw new \coding_exception('sessionid must be set in $other.');
        }

        if (!isset($this->other['statuscode']) || !array_key_exists($this->other['statuscode'], $MDL_F2F_STATUS)) {
            throw new \coding_exception('statuscode must be set in $other and must be a valid status.');
        }

        parent::validate_data();
    }
}


