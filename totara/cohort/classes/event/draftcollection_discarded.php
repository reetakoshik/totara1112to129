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
 * Event triggered when the draft rules in a cohort has been discarded.
 *
 * @property-read array $other {
 * Extra information about the event.
 *
 * }
 *
 * @author Maria Torres <maria.torres@totaralms.com>
 * @package totara_cohort
 */
class draftcollection_discarded extends \core\event\base {

    /** @var bool Flag for prevention of direct create() call */
    protected static $preventcreatecall = true;

    /** @var \stdClass The database record used to create the event */
    protected $cohort = null;

    /**
     * Create instance of event.
     *
     * @param   stdClass $instance A cohort object.
     * @return  new event
     */
    public static function create_from_instance($instance) {
        $data = array(
            'objectid' => $instance->id,
            'context' => \context::instance_by_id($instance->contextid),
        );

        self::$preventcreatecall = false;
        $event = self::create($data);
        $event->cohort = $instance;
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
        return $this->cohort;
    }

    /**
     * Initialise the event data.
     */
    protected function init() {
        $this->data['objecttable'] = 'cohort';
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_OTHER;
    }

    /**
     * Returns localised general event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventdraftdiscarded', 'totara_cohort');
    }

    /**
     * Returns non-localised description of what happened.
     *
     * @return string
     */
    public function get_description() {
        return "The draft rules for the cohort {$this->objectid} were discarded by user {$this->userid}";
    }

    /**
     * Returns relevant URL.
     *
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url('/totara/cohort/rules.php', array('id' => $this->objectid));
    }

    /**
     * Validate data
     */
    protected function validate_data() {
        if (self::$preventcreatecall) {
            throw new \coding_exception('cannot call create() directly, use create_from_instance() instead.');
        }
        parent::validate_data();
    }
}
