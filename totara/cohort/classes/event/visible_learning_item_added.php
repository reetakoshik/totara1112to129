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
 * This program is distributed in the hope that it willv be useful,
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
 * Event triggered when a learning item is added as a visible learning.
 *
 * @property-read array $other {
 * Extra information about the event.
 *
 * - cohortid The Cohort ID where the learning item was added
 * - instanceid The learning item ID
 * - type The type of content to be added (course, program or certification)
 * - value Value of the association.
 * }
 *
 * @author Maria Torres <maria.torres@totaralms.com>
 * @package totara_cohort
 */
class visible_learning_item_added extends \core\event\base {

    /** @var bool Flag for prevention of direct create() call */
    protected static $preventcreatecall = true;

    /** @var array Legacy log data */
    protected $legacylogdata = null;

    /**
     * Create event from data.
     *
     * @param   int $instanceid cohort_visible instance ID.
     * @param   \stdClass $cohort instance.
     * @return  visible_learning_item_added
     */
    public static function create_from_data($instanceid, $cohort) {
        $data = array(
            'objectid' => $instanceid,
            'context' => \context::instance_by_id($cohort->contextid),
            'other' => array('cohortid' => $cohort->id)
        );

        self::$preventcreatecall = false;
        $event = self::create($data);
        self::$preventcreatecall = true;

        return $event;
    }

    /**
     * Initialise the event data.
     */
    protected function init() {
        $this->data['objecttable'] = 'cohort_visibility';
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_OTHER;
    }

    /**
     * Returns localised general event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventlearningitemadded', 'totara_cohort');
    }

    /**
     * Returns non-localised description of what happened.
     *
     * @return string
     */
    public function get_description() {
        return "The learning item {$this->objectid} has been added to the cohort {$this->other['cohortid']}";
    }

    /**
     * Returns relevant URL.
     *
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url('/totara/cohort/visiblelearning.php', array('id' => $this->other['cohortid']));
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
            throw new \coding_exception('cannot call create() directly, use create_from_data() instead.');
        }

        parent::validate_data();
        if (!isset($this->other['cohortid'])) {
            throw new \coding_exception('cohortid must be set in $other.');
        }
    }
}
