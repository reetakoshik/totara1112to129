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
 * @author Maria Torres <maria.torres@totaralms.com>
 * @package totara_program
 */


namespace totara_program\event;
defined('MOODLE_INTERNAL') || die();

/**
 * Event triggered when a program extension is granted.
 *
 * @property-read array $other {
 * Extra information about the event.
 *
 * - programid The program ID where the extension was denied.
 * - userid The user ID whose extension was denied.
 *
 * }
 *
 * @author Maria Torres <maria.torres@totaralms.com>
 * @package totara_program
 */
class extension_granted extends \core\event\base {

    /** @var bool Flag for prevention of direct create() call. */
    protected static $preventcreatecall = true;

    /** @var \stdClass The database record used to create the event */
    protected $progextension= null;

    /**
     * Create event from instance.
     *
     * @param   \stdClass $instance prog_extension instance.
     * @return  \totara_program\event\extension_granted $event
     */
    public static function create_from_instance(\stdClass $instance) {
        $data = array(
            'objectid' => $instance->id,
            'context' => \context_system::instance(),
            'other' => array(
                'programid' => $instance->programid,
                'userid' => $instance->userid
            )
        );

        self::$preventcreatecall = false;
        $event = self::create($data);
        $event->progextension = $instance;
        self::$preventcreatecall = true;

        return $event;
    }

    /**
     * Get instance.
     *
     * NOTE: to be used from observers only.
     *
     * @throws \coding_exception
     * @return \stdClass
     */
    public function get_instance() {
        if ($this->is_restored()) {
            throw new \coding_exception('get_instance() is intended for event observers only');
        }
        return $this->progextension;
    }

    /**
     * Initialise the event data.
     */
    protected function init() {
        $this->data['objecttable'] = 'prog_extension';
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_OTHER;
    }

    /**
     * Returns localised general event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventextensiongranted', 'totara_program');
    }

    /**
     * Returns non-localised description of what happened.
     *
     * @return string
     */
    public function get_description() {
        return "A program extension was granted for user {$this->other['userid']} in program {$this->other['programid']}";
    }

    /**
     * Returns relevant URL.
     *
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url('/totara/program/view.php', array('id' => $this->other['programid']));
    }


    /**
     * Return legacy data for add_to_log().
     *
     * @return array
     */
    protected function get_legacy_logdata() {
        $programid = $this->other['programid'];
        return array(SITEID, 'program', 'grant extension', "view.php?id={$programid}", $programid);
    }

    /**
     * Validate data passed to this event.
     *
     */
    protected function validate_data() {
        if (self::$preventcreatecall) {
            throw new \coding_exception('cannot call create() directly, use create_from_instance() instead.');
        }

        parent::validate_data();

        // Check programid and userid are in $other.
        if (!isset($this->other['programid'])) {
            throw new \coding_exception('programid must be set in $other.');
        }
        if (!isset($this->other['userid'])) {
            throw new \coding_exception('userid must be set in $other.');
        }
    }
}
