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
 * @package availability
 * @subpackage audience
 */

namespace availability_audience;

defined('MOODLE_INTERNAL') || die();

/**
 * Condition on user being member of a cohort.
 */
class condition extends \core_availability\condition {
    /** @var int cohort id */
    protected $cohortid;

    /**
     * Constructor.
     *
     * @param \stdClass $structure Data structure from JSON decode
     * @throws \coding_exception If invalid data structure.
     */
    public function __construct(\stdClass $structure) {
        // Get cohort id.
        if (isset($structure->cohort) && is_numeric($structure->cohort)) {
            $this->cohortid = $structure->cohort;
        } else {
            throw new \coding_exception('Missing or invalid ->cohort for audience condition');
        }
    }

    /**
     * Constructs the data object to save the condition
     *
     * @return stdClass An object containing the type of condition and the audience selected.
     */
    public function save() {
        $result = new \stdClass();
        $result->type = 'audience';
        $result->cohort = $this->cohortid;

        return $result;
    }

    /**
     * Returns a JSON object which corresponds to a condition of this type.
     *
     * Intended for unit testing, as normally the JSON values are constructed
     * by JavaScript code.
     *
     * @param int $audienceid Cohort id
     * @return stdClass Object representing condition
     */
    public static function get_json($cohortid) {
        $result = new \stdClass();
        $result->type = 'audience';
        $result->cohort = (int)$cohortid;

        return $result;
    }

    /**
     * Determines if available based on the condition settings
     *
     * @param bool $not
     * @param \core_availability\info $info
     * @param bool $grabthelot Performance, not used as there is only
     *          a single DB call to get cohorts.
     * @param int $userid
     *
     * @return bool True if available
     */
    public function is_available($not, \core_availability\info $info, $grabthelot, $userid) {
        global $CFG;

        require_once($CFG->dirroot . '/totara/cohort/lib.php');
        // Return true if user is member of audience.
        $allow = false;

        $cohorts = \totara_cohort_get_user_cohorts($userid);
        if (in_array($this->cohortid, $cohorts)) {
            $allow = true;
        }

        if ($not) {
            return !$allow;
        } else {
            return $allow;
        }
    }

    /*
     * Gets the description of the condition
     *
     * @param bool $full
     * @param bool $not Inverts the condition
     * @param core_availability\info $info
     *
     * @return String Description of the condition
     */
    public function get_description($full, $not, \core_availability\info $info) {
        global $DB;

        $cohortname = $DB->get_field('cohort', 'name', array('id' => $this->cohortid));
        $cohortname = format_string($cohortname);

        if ($not) {
            return get_string('notmemberofaudiencex', 'availability_audience', $cohortname);
        } else {
            return get_string('memberofaudiencex', 'availability_audience', $cohortname);
        }
    }

    /**
     * Returns a string for debugging
     *
     * @return String
     */
    protected function get_debug_string() {
        $out = '#' . $this->cohortid;

        return $out;
    }

    /**
     * If the Audience doesn't exist (has been deleted since backup) then
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

        $audienceexists = $DB->record_exists('cohort', array('id' => $this->cohortid));

        return $audienceexists;
    }
}
