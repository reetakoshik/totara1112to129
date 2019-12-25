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
 * @package totara_cohort
 */


namespace totara_cohort\event;
defined('MOODLE_INTERNAL') || die();

/**
 * Event triggered when a ruleset is created.
 *
 * @property-read array $other {
 * Extra information about the event.
 *
 * - cohortid The Cohort ID where the ruleset is going to be created.
 * }
 *
 * @author Maria Torres <maria.torres@totaralms.com>
 * @package totara_cohort
 */
class ruleset_created extends \core\event\base {

    /** @var bool Flag for prevention of direct create() call */
    protected static $preventcreatecall = true;

    /** @var \stdClass The database record used to create the event */
    protected $ruleset = null;

    /** @var array Legacy log data */
    protected $legacylogdata = null;

    /**
     * Create event from instance.
     *
     * @param   stdClass $instance cohort_rulesets instance.
     * @param   stdClass $cohort instance.
     * @return  new event
     */
    public static function create_from_instance($instance, $cohort) {
        $data = array(
            'objectid' => $instance->id,
            'context' => \context::instance_by_id($cohort->contextid),
            'other' => array('cohortid' => $cohort->id,)
        );

        self::$preventcreatecall = false;
        $event = self::create($data);
        $event->ruleset = $instance;
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
        return $this->ruleset;
    }

    /**
     * Initialise the event data.
     */
    protected function init() {
        $this->data['objecttable'] = 'cohort_rulesets';
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_OTHER;
    }

    /**
     * Returns localised general event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventrulesetcreated', 'totara_cohort');
    }

    /**
     * Returns non-localised description of what happened.
     *
     * @return string
     */
    public function get_description() {
        return "The ruleset {$this->objectid} was created in the cohort {$this->other['cohortid']}";
    }

    /**
     * Returns relevant URL.
     *
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url('/totara/cohort/rules.php', array('id' => $this->other['cohortid']));
    }

    /**
     * Sets legacy log data.
     *
     * @param array $legacylogdata
     * @return void
     */
    public function set_legacy_logdata($legacylogdata) {
        $this->legacylogdata = $legacylogdata;
    }

    /**
     * Returns array of parameters to be passed to legacy add_to_log() function.
     *
     * @return null|array
     */
    protected function get_legacy_logdata() {
        return $this->legacylogdata;
    }

    /**
     * Validate data
     */
    protected function validate_data() {
        if (self::$preventcreatecall) {
            throw new \coding_exception('cannot call create() directly, use create_from_instance() instead.');
        }

        parent::validate_data();
        if (!isset($this->other['cohortid'])) {
            throw new \coding_exception('cohortid must be set in $other.');
        }
    }
}
