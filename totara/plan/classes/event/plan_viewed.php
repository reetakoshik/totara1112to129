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
 * Event triggered when a plan is viewed
 *
 * @property-read array $other {
 * Extra information about the event.
 *
 * - name The freetext fullname of the plan
 * }
 *
 */
class plan_viewed extends \core\event\base {
    /**
     * Flag for prevention of direct create() call.
     * @var bool
     */
    protected static $preventcreatecall = true;
    /** @var \development_plan */
    protected $plan;

    /**
     * Create event for plan.
     * @param \development_plan $plan
     * @return plan_viewed
     */
    public static function create_from_plan(\development_plan $plan) {
        $data = array(
            'objectid' => $plan->id,
            'context' => \context_system::instance(),
            'relateduserid' => $plan->userid,
            'other' => array('name' => $plan->name),
        );

        self::$preventcreatecall = false;
        /** @var plan_viewed $event */
        $event = self::create($data);
        self::$preventcreatecall = true;

        return $event;
    }

    /**
     * Get plan instance.
     *
     * NOTE: to be used from observers only.
     *
     * @return \development_plan
     */
    public function get_plan() {
        if ($this->is_restored()) {
            throw new \coding_exception('get_plan() is intended for event observers only');
        }
        return $this->plan;
    }

    /**
     * Initialise the event data.
     */
    protected function init() {
        $this->data['crud'] = 'r';
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->data['objecttable'] = 'dp_plan';
    }

    /**
     * Return localised event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventplanviewed', 'totara_plan');
    }

    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description() {
        return "The user with id '{$this->userid}' viewed the plan {$this->objectid}:{$this->other['name']}";
    }

    /**
     * Return the legacy event log data.
     *
     * @return array
     */
    protected function get_legacy_logdata() {
        $logurl = $this->get_url()->out_as_local_url(false);
        return array(SITEID, 'plan', 'plan viewed', $logurl, "{$this->objectid}:{$this->other['name']}");
    }

    /**
     * Returns relevant URL.
     *
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url('/totara/plan/view.php', array('id' => $this->objectid));
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

        if (!isset($this->other['name'])) {
            throw new \coding_exception('name must be set in $other');
        }
    }
}
