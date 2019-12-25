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
 * @author Ciaran Irvine <ciaran.irvine@totaralms.com>
 * @package totara_plan
 */

namespace totara_plan\event;
defined('MOODLE_INTERNAL') || die();

/**
 * Event triggered when a user views a list of plans for another user
 */
class plan_list_viewed extends \core\event\base {
    /**
     * Flag for prevention of direct create() call.
     * @var bool
     */
    protected static $preventcreatecall = true;

    /**
     * Create event for plans.
     * @param int $userid
     * @return plan_list_viewed
     */
    public static function create_from_userid($userid) {
        $data = array(
            'context' => \context_system::instance(),
            'relateduserid' => $userid,
        );

        self::$preventcreatecall = false;
        /** @var plan_list_viewed $event */
        $event = self::create($data);
        self::$preventcreatecall = true;

        return $event;
    }

    /**
     * Initialise the event data.
     */
    protected function init() {
        $this->data['crud'] = 'r';
        $this->data['edulevel'] = self::LEVEL_OTHER;
    }

    /**
     * Return localised event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventplanlistviewed', 'totara_plan');
    }

    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description() {
        return "The user with id '{$this->userid}' viewed a list of plans for user {$this->relateduserid}.";
    }

    /**
     * Return the legacy event log data.
     *
     * @return array
     */
    protected function get_legacy_logdata() {
        $logurl = $this->get_url()->out_as_local_url(false);
        return array(SITEID, 'plan', 'view all', $logurl, $this->relateduserid);
    }

    /**
     * Returns relevant URL.
     *
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url('/totara/plan/index.php', array('userid' => $this->relateduserid));
    }

    /**
     * Custom validation
     *
     * @throws \coding_exception
     * @return void
     */
    public function validate_data() {
        if (self::$preventcreatecall) {
            throw new \coding_exception('cannot call create() directly');
        }
        parent::validate_data();

        if (!isset($this->relateduserid)) {
            throw new \coding_exception('relateduserid must be set');
        }
    }
}
