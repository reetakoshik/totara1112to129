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
 * @package availablity_hierarchy_organisation
 */

namespace availability_hierarchy_organisation;

defined('MOODLE_INTERNAL') || die();

/**
 * Condition on user being assigned to an organisation.
 */
class condition extends \core_availability\condition {
    /** @var int organisation id */
    protected $organisationid;

    /**
     * Constructor.
     *
     * @param \stdClass $structure Data structure from JSON decode
     * @throws \coding_exception If invalid data structure.
     */
    public function __construct(\stdClass $structure) {
        // Get cohort id.
        if (isset($structure->organisation) && is_numeric($structure->organisation)) {
            $this->organisationid = $structure->organisation;
        } else {
            throw new \coding_exception('Missing or invalid ->org for organisation condition');
        }
    }

    /**
     * Save the restriction
     *
     * @return Object Details of the restriction
     */
    public function save() {
        $result = new \stdClass();
        $result->type = 'hierarchy_organisation';
        $result->organisation = $this->organisationid;

        return $result;
    }

    /**
     * Returns a JSON object which corresponds to a condition of this type.
     *
     * Intended for unit testing, as normally the JSON values are constructed
     * by JavaScript code.
     *
     * @param int $organisationid Cohort id
     * @return stdClass Object representing condition
     */
    public static function get_json($organisationid) {
        $result = new \stdClass();
        $result->type = 'hierarchy_organisation';
        $result->organisation = $organisationid;

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
        $assigned = $this->is_assigned_to_organisation($this->organisationid, $userid);

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

        $organisationname = $DB->get_field('org', 'fullname', array('id' => $this->organisationid));
        $organisationname = format_string($organisationname);

        if ($not) {
            return get_string('notassignedtoorgx', 'availability_hierarchy_organisation', $organisationname);
        } else {
            return get_string('assignedtoorgx', 'availability_hierarchy_organisation', $organisationname);
        }
    }

    /**
     * Return debugging string
     *
     * @return string Debug text
     */
    protected function get_debug_string() {
        $out = '#' . $this->organisationid;

        return $out;
    }

    /**
     * Check if a user is assigned to an organisation
     *
     * @param $organisationid
     * @param $userid
     *
     * @return bool True if the user is assigned to the organisation
     */
    private function is_assigned_to_organisation($organisationid, $userid) {
        global $DB;

        $assigned = false;

        $conditions = array(
            'organisationid' => $organisationid,
            'userid' => $userid
        );

        $jobs = $DB->get_records('job_assignment', $conditions);

        if (!empty($jobs)) {
            $assigned = true;
        }

        return $assigned;
    }

    /**
     * If the Organisation doesn't exist (has been deleted since backup) then
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

        $organisationexists = $DB->record_exists('org', array('id' => $this->organisationid));

        return $organisationexists;
    }
}
