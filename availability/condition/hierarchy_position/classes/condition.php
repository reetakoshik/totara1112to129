<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2017 onwards Totara Learning Solutions LTD
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
 * @author Alastair Munro <alastair.munro@totaralearning.com>
 * @author Simon Player <simon.player@totaralearning.com>
 * @package availablity_hierarchy_position
 */

namespace availability_hierarchy_position;

defined('MOODLE_INTERNAL') || die();

/**
 * Condition on user being assigned to a position.
 */
class condition extends \core_availability\condition {
    /** @var int position id */
    protected $positionid;

    /**
     * Constructor.
     *
     * @param \stdClass $structure Data structure from JSON decode
     * @throws \coding_exception If invalid data structure.
     */
    public function __construct(\stdClass $structure) {
        // Get cohort id.
        if (isset($structure->position) && is_numeric($structure->position)) {
            $this->positionid = $structure->position;
        } else {
            throw new \coding_exception('Missing or invalid ->pos for position condition');
        }
    }

    /**
     * Save the restriction
     *
     * @return Object Details of the restriction
     */
    public function save() {
        $result = new \stdClass();
        $result->type = 'hierarchy_position';
        $result->position = $this->positionid;

        return $result;
    }

    /**
     * Returns a JSON object which corresponds to a condition of this type.
     *
     * Intended for unit testing, as normally the JSON values are constructed
     * by JavaScript code.
     *
     * @param int $positionid Cohort id
     * @return stdClass Object representing condition
     */
    public static function get_json($positionid) {
        $result = new \stdClass();
        $result->type = 'hierarchy_position';
        $result->position = $positionid;

        return $result;
    }

    /**
     *  Determines if this condition allow the activity to be available
     *
     *  @param bool $not
     *  @param core_availability\info $info
     *  @param bool $grabthelot Performance, not here used as there is
     *                          only a single API call.
     *  @param int $userid
     *
     *  @return bool True if available
     */
    public function is_available($not, \core_availability\info $info, $grabthelot, $userid) {
        $assigned = $this->is_assigned_to_position($this->positionid, $userid);

        if ($not) {
            $allow = !$assigned;
        } else {
            $allow = $assigned;
        }

        return $allow;
    }

    /**
     * Get condition description
     *
     * @param bool $full Display full description or shortened version, not used
     * @param bool $not Should the condition be inverted
     * @param core_availability/info $info
     *
     * @return string Text describing the conditions of restriction
     */
    public function get_description($full, $not, \core_availability\info $info) {
        global $DB;

        $positionname = $DB->get_field('pos', 'fullname', array('id' => $this->positionid));
        $positionname = format_string($positionname);

        if ($not) {
            return get_string('notassignedtoposx', 'availability_hierarchy_position', $positionname);
        } else {
            return get_string('assignedtoposx', 'availability_hierarchy_position', $positionname);
        }
    }

    /**
     * Return debugging string
     *
     * @return string Debug text
     */
    protected function get_debug_string() {
        $out = '#' . $this->positionid;

        return $out;
    }

    /**
     * Check if a user is assigned to an position
     *
     * @param $positionid
     * @param $userid
     *
     * @return bool True if the user is assigned to the position
     */
    private function is_assigned_to_position($positionid, $userid) {
        global $DB;

        $assigned = false;

        $conditions = array(
            'positionid' => $positionid,
            'userid' => $userid
        );

        $jobs = $DB->get_records('job_assignment', $conditions);

        if (!empty($jobs)) {
            $assigned = true;
        }

        return $assigned;
    }

    /**
     * If the position doesn't exist (has been deleted since backup) then
     * don't restore this restriction.
     *
     * @param string $restoreid Restore ID
     * @param int $courseid ID of target course
     * @param \base_logger $logger Logger for any warnings
     * @param string $name Name of this item (for use in warning messages)
     * @param \base_task $task Current restore task
     * @return bool True if there was any change
     */
    public function include_after_restore($restoreid, $courseid, \base_logger $logger, $name,
        \base_task $task) {
        global $DB;

        $positionexists = $DB->record_exists('pos', array('id' => $this->positionid));

        return $positionexists;
    }
}
