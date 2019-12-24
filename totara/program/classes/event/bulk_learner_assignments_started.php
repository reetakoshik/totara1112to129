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
 * Event triggered when bulk learner assignments is made for an assignment in a program.
 *
 * @property-read array $other {
 * Extra information about the event.
 *
 * - programid The program ID where the bulk is happening.
 * - assignmentid The assignment ID where the bulk will occur
 * }
 *
 * @author Maria Torres <maria.torres@totaralms.com>
 * @package totara_program
 */
class bulk_learner_assignments_started extends \core\event\base {

    /** @var bool Flag for prevention of direct create() call */
    protected static $preventcreatecall = true;

    /**
     * Create from data
     *
     * @param array $dataevent Array with the data needed for the event.
     * @return \totara_program\event\bulk_learner_assignments_started $event
     */
    public static function create_from_data(array $dataevent) {
        $data = array(
            'context' => \context_system::instance(),
            'other' => $dataevent['other']
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
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_OTHER;
    }

    /**
     * Returns localised general event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventbulklearnersassignedstarted', 'totara_program');
    }

    /**
     * Returns non-localised description of what happened.
     *
     * @return string
     */
    public function get_description() {
        $description = 'Bulk user assignments has started';
        $description .= isset($this->other['programid']) ? " in program: {$this->other['programid']}" : '';
        $description .= isset($this->other['assignmentid']) ? " for assignment: {$this->other['assignmentid']}" : '';
        return $description;
    }

    /**
     * Validate data
     */
    protected function validate_data() {
        if (self::$preventcreatecall) {
            throw new \coding_exception('cannot call create() directly, use create_from_data() instead.');
        }
        parent::validate_data();
    }
}
