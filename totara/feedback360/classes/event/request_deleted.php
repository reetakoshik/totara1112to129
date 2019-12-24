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
 * @author David Curry <david.curry@totaralms.com>
 * @package totara_feedback360
 */

namespace totara_feedback360\event;

defined('MOODLE_INTERNAL') || die();

/**
 * Event triggered when a feedback360 request is cancelled
 *
 * @property-read array $other {
 *      Extra information about the event.
 *
 *      - 'assignmentid'    The id of the associated user_assignment,
 *      - 'userid'          The userid of the associated user_assignment,
 *      - 'email'           The email for external requests,
 * }
 *
 * @author David Curry <david.curry@totaralms.com>
 * @package totara_feedback360
 */
class request_deleted extends \core\event\base {

    /**
     * Flag for prevention of direct create() call.
     * @var bool
     */
    protected static $preventcreatecall = true;

    /**
     * The instance used to create the event.
     * @var \stdClass
     */
    protected $resp_assignment;

    /**
     * Create instance of event.
     *
     * @param   \stdClass $instance   A resp_assignment record.
     * @param   int       $userassign The userid from the linked user_assignment record.
     * @param   string    $email      The email from the linked email__assignment record.
     * @return  request_deleted
     */
    public static function create_from_instance(\stdClass $instance, $userassign, $email = '') {
        $data = array(
            'objectid' => $instance->id,
            'context' => \context_system::instance(),
            'relateduserid' => $userassign,
            'other' => array(
                'assignmentid' => $instance->feedback360userassignmentid,
                'userid' => $instance->userid,
                'email' => $email,
            )
        );

        self::$preventcreatecall = false;
        $event = self::create($data);
        $event->resp_assignment = $instance;
        $event->add_record_snapshot('feedback360_resp_assignment', $instance);
        self::$preventcreatecall = true;

        return $event;
    }

    /**
     * Get feedback360 instance.
     *
     * NOTE: to be used from observers only.
     *
     * @return \feedback360
     */
    public function get_resp_assignment() {
        if ($this->is_restored()) {
            throw new \coding_exception('get_resp_assignment() is intended for event observers only');
        }
        return $this->resp_assignment;
    }

    /**
     * Init method.
     *
     * @return void
     */
    protected function init() {
        $this->data['objecttable'] = 'feedback360_resp_assignment';
        $this->data['crud'] = 'd';
        $this->data['edulevel'] = self::LEVEL_OTHER;
    }

    /**
     * Return localised event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventdeletedrequest', 'totara_feedback360');
    }

    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description() {
        return "The feedback360 request {$this->objectid} was deleted";
    }

    /**
     * Returns relevant url.
     *
     * @return \moodle_url
     */
    public function get_url() {
        $urlparams = array('userid' => $this->relateduserid);
        return new \moodle_url('/totara/feedback360/index.php', $urlparams);
    }

    public function get_legacy_logdata() {
        $urlparams = array('action' => 'users', 'userid' => $this->relateduserid, 'formid' => $this->data['other']['assignmentid']);

        $logdata = array();
        $logdata[] = SITEID;
        $logdata[] = 'feedback360';
        $logdata[] = 'delete feedback request';
        $logdata[] = new \moodle_url('/totara/feedback360/request.php', $urlparams);

        return $logdata;
    }

    /**
     * Custom validation.
     *
     * @throws \coding_exception
     * @return void
     */
    protected function validate_data() {
        if (self::$preventcreatecall) {
            throw new \coding_exception('cannot call create() directly, use create_from_instance() instead.');
        }

        parent::validate_data();

        if (!isset($this->other['assignmentid'])) {
            throw new \coding_exception('assignmentid must be set in $other.');
        }
        if (!isset($this->other['userid'])) {
            throw new \coding_exception('userid must be set in $other.');
        }
        if (!isset($this->other['email'])) {
            // Note:: this can be empty but it should be set.
            throw new \coding_exception('email must be set in $other.');
        }

    }
}
