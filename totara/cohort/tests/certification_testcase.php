<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
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
 * @author Simon Player <simon.player@totaralearning.com>
 * @package totara_cohort
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/totara/reportbuilder/tests/reportcache_advanced_testcase.php');
require_once($CFG->dirroot . '/totara/cohort/lib.php');

/**
 * Certification testcase
 *
 */
abstract class totara_cohort_certification_testcase extends reportcache_advanced_testcase {

    const TEST_GROUP_USER_COUNT         = 2;
    const TEST_PROGRAMS_COUNT           = 2;
    const TEST_CERTIFICATIONS_COUNT     = 5;

    protected function tearDown() {
        parent::tearDown();
    }

    public function setUp() {
        parent::setup();
    }

    /**
     * Create users and add to the specified group
     *
     * @param $group
     */
    public function add_users_to_group($group) {
        for ($i = 1; $i <= self::TEST_GROUP_USER_COUNT; $i++) {
            $user = $this->getDataGenerator()->create_user();
            $this->user_groups[$group][$user->id] = $user;
        }
        $this->assertEquals(self::TEST_GROUP_USER_COUNT, count($this->user_groups[$group]));
    }

    /**
     * Assign users to all certifications
     *
     * @param array $users
     * @param array $certifications
     */
    public function assign_users($users, $certifications = []) {
        foreach ($users as $user) {
            foreach ($certifications as $certification) {
                $this->getDataGenerator()->assign_to_program($certification->id, ASSIGNTYPE_INDIVIDUAL, $user->id);
            }
        }
    }

    /**
     * Unassign users from all certifications they are assigned to
     *
     * @param $users
     * @param array $certifications
     */
    public function unassign_users($users, $certifications = []) {
        global $DB;

        foreach ($users as $user) {
            foreach ($certifications as $certification) {
                $DB->delete_records('prog_user_assignment', ['userid' => $user->id, 'programid' => $certification->id]);
                $DB->delete_records('prog_assignment', ['assignmenttypeid' => $user->id, 'programid' => $certification->id]);

                // Need this to move records to history table when required.
                certif_conditionally_delete_completion($certification->id, $user->id);
            }
        }
    }

    /**
     * @param array $users
     * @param iny $courseid
     */
    public function certify_users($users, $courseid, $timecomplete = null) {
        foreach ($users as $user) {
            $completion = new completion_completion(['userid' => $user->id, 'course' => $courseid]);
            $completion->mark_inprogress();
            $completion->mark_complete($timecomplete);
        }
    }

    /**
     * Expire user certifications
     *
     * Move everything back in time for the specified users
     *
     * @param $users
     */
    function expire_user_certitifications($users) {
        global $DB;

        list($sqlin, $params) = $DB->get_in_or_equal(array_keys($users));

        $records = $DB->get_records_select('certif_completion', 'userid ' . $sqlin, $params);
        foreach ($records as $record) {
            if ($record->timewindowopens > 0) {
                $record->timewindowopens = strtotime("-999 day", $record->timewindowopens);
            }
            if ($record->timeexpires > 0) {
                $record->timeexpires = strtotime("-999 day", $record->timeexpires);
            }
            if ($record->timecompleted > 0) {
                $record->timecompleted = strtotime("-999 day", $record->timecompleted);
            }
            if ($record->timemodified > 0) {
                $record->timemodified = strtotime("-999 day", $record->timemodified);
            }
            $DB->update_record('certif_completion', $record);
        }

        $sql = "SELECT *
                  FROM {prog_assignment} pa
                  JOIN {prog_user_assignment} pua ON pua.assignmentid = pa.id
                 WHERE pua.id $sqlin";
        $records = $DB->get_records_sql($sql, $params);
        foreach ($records as $record) {
            if ($record->completiontime > 0) {
                $record->completiontime = strtotime("-999 day", $record->completiontime);
            }
            $DB->update_record('prog_assignment', $record);
        }

        $records = $DB->get_records_select('prog_completion', 'userid ' . $sqlin, $params);
        foreach ($records as $record) {
            if ($record->timecreated > 0) {
                $record->timecreated = strtotime("-999 day", $record->timecreated);
            }
            if ($record->timestarted > 0) {
                $record->timestarted = strtotime("-999 day", $record->timestarted);
            }
            if ($record->timedue > 0) {
                $record->timedue = strtotime("-999 day", $record->timedue);
            }
            if ($record->timecompleted > 0) {
                $record->timecompleted = strtotime("-999 day", $record->timecompleted);
            }
            $DB->update_record('prog_completion', $record);
        }

        $records = $DB->get_records_select('prog_user_assignment', 'userid ' . $sqlin, $params);
        foreach ($records as $record) {
            if ($record->timeassigned > 0) {
                $record->timeassigned = strtotime("-999 day", $record->timeassigned);
            }
            $DB->update_record('prog_user_assignment', $record);
        }

        // Run cron.
        ob_start();
        $certcron = new \totara_certification\task\update_certification_task();
        $certcron->execute();
        $assignmentscron = new \totara_program\task\user_assignments_task();
        $assignmentscron->execute();
        ob_end_clean();
    }
}
