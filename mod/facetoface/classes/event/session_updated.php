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
 * Event triggered when a session is updated.
 *
 * @property-read array $other {
 * Extra information about the event.
 *
 *
 * }
 *
 * @author Alastair Munro <alastair.munro@totaralms.com>
 * @package mod_facetoface
 */
class session_updated extends \core\event\base {

    /** @var bool Flag for prevention of direct create() call. */
    protected static $preventcreatecall = true;

    /** @var \stdClass */
    protected $session;

    /**
     * Create instance of event.
     *
     * @param \stdClass $session
     * @param \context_module $context
     * @return session_updated
     */
    public static function create_from_session(\stdClass $session, \context_module $context) {
        $data = array(
            'context' => $context,
            'objectid' => $session->id,
        );

        self::$preventcreatecall = false;
        $event = self::create($data);
        self::$preventcreatecall = true;
        $event->session = $session;
        return $event;
    }

    /**
     * Get session instance.
     *
     * NOTE: to be used from observers only.
     *
     * @return \stdClass session
     */
    public function get_session() {
        if ($this->is_restored()) {
            throw new \coding_exception('get_session is intended for event observers only');
        }

        return $this->session;
    }

    /**
     * Init method
     *
     * @return void
     */
    protected function init() {
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_TEACHING;
        $this->data['objecttable'] = 'facetoface_sessions';
    }

    /**
     * Return localised event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventsessionupdated', 'mod_facetoface');
    }

    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description() {
        return "Session with the id {$this->objectid} has been updated.";
    }

    /**
     * Returns relevant URL.
     *
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url('/mod/facetoface/view.php', array('id' => $this->contextinstanceid));
    }

    /**
     * Return the legacy event log data.
     *
     * @return array
     */
    public function get_legacy_logdata() {
        return array($this->courseid, 'facetoface', 'updated session',
            "events/edit.php?s=$this->objectid",
            $this->objectid, $this->contextinstanceid);
    }

    /**
     * Custom validation.
     *
     * @return void
     */
    protected function validate_data() {
        if (self::$preventcreatecall) {
            throw new \coding_exception('cannot call create() directly, use create_from_session() instead.');
        }

        parent::validate_data();
    }
}
