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
 * @package report_completion
 */

namespace report_completion\event;

defined('MOODLE_INTERNAL') || die();

/**
 * The rpl created event class.
 *
 * @property-read array $other {
 *      Extra information about the event.
 *
 *      - string type: 'course' or completion criteria number if numeric
 * }
 *
 * NOTE: this event is triggered right after the completion of course/criteria.
 *
 * @author Petr Skoda <petr.skoda@totaralms.com>
 * @package report_completion
 */
class rpl_created extends \core\event\base {
    /**
     * Flag for prevention of direct create() call.
     * @var bool
     */
    protected static $preventcreatecall = true;

    /**
     * Create instance of event.
     *
     * @param int $userid
     * @param int $courseid
     * @param int $cmid
     * @param mixed $type
     * @return rpl_created
     */
    public static function create_from_rpl($userid, $courseid, $cmid, $type) {
        $data = array(
            'context' => \context_course::instance($courseid),
            'relateduserid' => $userid,
            'other' => array(
                'cmid' => $cmid,
                'type' => $type,
            ),
        );
        self::$preventcreatecall = false;
        /** @var rpl_created $event */
        $event = self::create($data);
        self::$preventcreatecall = true;
        return $event;
    }

    /**
     * Init method.
     *
     * @return void
     */
    protected function init() {
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_OTHER;
    }

    /**
     * Return localised event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventrplcreated', 'report_completion');
    }

    /**
     * Returns relevant URL.
     *
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url('/report/completion/index.php', array('course' => $this->courseid));
    }

    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description() {
        $type = (($this->other['type'] === 'course') ? 'course' : 'activity');
        return "The user with id '$this->userid' created $type RPL for user '$this->relateduserid' in course '$this->courseid'.";
    }

    /**
     * Custom validation.
     *
     * @return void
     */
    protected function validate_data() {
        if (self::$preventcreatecall) {
            throw new \coding_exception('cannot call rpl_created::create() directly, use rpl_created::create_from_rpl() instead.');
        }

        parent::validate_data();
    }
}
