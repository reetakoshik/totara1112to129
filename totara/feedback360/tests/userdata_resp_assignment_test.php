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
 * @author David Curry <david.curry@totaralearning.com>
 * @package totara_feedback360
 */

defined('MOODLE_INTERNAL') || die();

use totara_userdata\userdata\target_user;
use totara_feedback360\userdata\resp_assignments;

global $CFG;

require_once($CFG->dirroot . '/totara/feedback360/lib.php');
require_once($CFG->dirroot . '/totara/feedback360/tests/feedback360_testcase.php');

/**
 * @group totara_userdata
 * @group totara_feedback360
 * Class userdata_resp_assignment_test
*/
class userdata_resp_assignment_test extends feedback360_testcase {

    /**
     * Create some resp_assignment data for testing.
     *
     * @return stdClass
     */
    private function create_resp_assignments() {
        global $DB;

        $retdata = new stdClass();
        $retdata->now = time();

        // Set up some test users.
        $user1 = $this->getDataGenerator()->create_user(); // Test user_assignment.
        $retdata->user1 = $user1;

        $user2 = $this->getDataGenerator()->create_user(); // Control user.
        $retdata->user2 = $user2;

        $user3 = $this->getDataGenerator()->create_user(); // Blank user.
        $retdata->user3 = $user3;

        // Create a feedback with some user assignments.
        list($fb1, $u1, $q1) = $this->prepare_feedback_with_users([$user1, $user2], 3, true, feedback360::SELF_EVALUATION_OPTIONAL);
        $retdata->fb1 = $fb1;

        // Now set up some resp assignments.
        $users = [];
        $respassignments = [];
        for ($i=0 ; $i < 10; $i++) {
            // Split the resp_assignments between the assigned users.
            if ($i % 2 === 0) {
                $assigned = $user1;
            } else {
                $assigned = $user2;
            }

            $user = $this->getDataGenerator()->create_user();
            $respassignments[$user->id] = $this->assign_resp($fb1, $assigned->id, $user->id);
            $users[$user->id] = $user;
        }

        // Add the users to their own requests for self evaluation.
        $respassignments[$user1->id] = $this->assign_resp($fb1, $user1->id, $user1->id);
        $respassignments[$user2->id] = $this->assign_resp($fb1, $user2->id, $user2->id);

        // Now fill in some answers.
        foreach ($respassignments as $respassignment) {
            $uid = $respassignment->userid;
            $anstable = "feedback360_quest_data_{$fb1->id}";

            // The answer data doesn't exist at this point so insert rather than update.
            $ansdata = new \stdClass();
            $ansdata->timemodified = $retdata->now;
            $ansdata->timecompleted = $retdata->now;
            $ansdata->feedback360respassignmentid = $respassignment->id;

            foreach ($q1 as $question) {
                $column = "data_{$question->id}";
                $ansdata->$column = "Answer from {$uid}";
            }

            $DB->insert_record($anstable, $ansdata);
            $respassignment->complete($retdata->now);
        }

        // Create a feedback with some user assignments.
        $split = ceil(count($users)/2);
        $uagroup2 = array_slice($users, 0, $split);
        list($fb2, $u2, $q2) = $this->prepare_feedback_with_users($uagroup2, 3, false, feedback360::SELF_EVALUATION_OPTIONAL);
        $retdata->fb2 = $fb2;

        // group 2 - always ask user1 for feedback, every other time ask user2 as well.
        $assign2 = false;
        foreach ($uagroup2 as $user) {
            if ($assign2) {
                // Ask user 2 as well, but leave their answer data empty.
                $this->assign_resp($fb2, $user->id, $user2->id);
            }

            $anstable = "feedback360_quest_data_{$fb2->id}";
            $respassignment = $this->assign_resp($fb2, $user->id, $user1->id);

            $ansdata = new \stdClass();
            $ansdata->timemodified = $retdata->now;
            $ansdata->timecompleted = $retdata->now;
            $ansdata->feedback360respassignmentid = $respassignment->id;

            foreach ($q2 as $question) {
                $column = "data_{$question->id}";
                $ansdata->$column = "Answer from {$user1->id}";
            }

            $DB->insert_record($anstable, $ansdata);
            $respassignment->complete($retdata->now);

            $assign2 = !$assign2;
        }

        $uagroup3 = array_slice($users, $split);
        list($fb3, $u3, $q3) = $this->prepare_feedback_with_users($uagroup3, 3, false, feedback360::SELF_EVALUATION_OPTIONAL);
        $retdata->fb3 = $fb3;

        // Group 3 - always ask user2 for feedback, every other time ask user1 as well.
        $assign1 = false;
        foreach ($uagroup3 as $user) {
            if ($assign1) {
                // Ask user 1 as well, but leave their answer data empty.
                $this->assign_resp($fb3, $user->id, $user1->id);
            }

            $anstable = "feedback360_quest_data_{$fb3->id}";
            $respassignment = $this->assign_resp($fb3, $user->id, $user2->id);

            $ansdata = new \stdClass();
            $ansdata->timemodified = $retdata->now;
            $ansdata->timecompleted = $retdata->now;
            $ansdata->feedback360respassignmentid = $respassignment->id;

            foreach ($q3 as $question) {
                $column = "data_{$question->id}";
                $ansdata->$column = "Answer from {$user2->id}";
            }

            $DB->insert_record($anstable, $ansdata);
            $respassignment->complete($retdata->now);

            $assign1 = !$assign1;
        }

        return $retdata;
    }

    /**
     * Test the count function for the feedback360 user assignment userdata items.
     */
    public function test_resp_assignment_count() {
        $this->resetAfterTest(true);
        $this->setAdminUser();

        $data = $this->create_resp_assignments();

        $syscontext = \context_system::instance();
        $userdata = new resp_assignments();

        $this->assertTrue($userdata->is_countable());

        $targetuser1 = new target_user($data->user1);
        $this->assertEquals(8, $userdata->execute_count($targetuser1, $syscontext));

        $targetuser2 = new target_user($data->user2);
        $this->assertEquals(8, $userdata->execute_count($targetuser2, $syscontext));

        $targetuser3 = new target_user($data->user3);
        $this->assertEquals(0, $userdata->execute_count($targetuser3, $syscontext));
    }

    /**
     * Test the purge function for the feedback360 user assignment userdata items.
     */
    public function test_resp_assignment_export() {
        $this->resetAfterTest(true);
        $this->setAdminUser();

        $data = $this->create_resp_assignments();
        $userdata = new resp_assignments();
        $syscontext = \context_system::instance();
        $pending = get_string('pending', 'totara_feedback360');

        $this->assertTrue($userdata->is_exportable());

        $user1 = $data->user1;
        $targetuser1 = new target_user($user1);
        $export1 = $userdata->execute_export($targetuser1, $syscontext);
        $this->assertEquals(8, count($export1->data));
        foreach ($export1->data as $resp) {
            $this->assertEquals($resp->userid, $user1->id);
            if ($resp->timecompleted == $data->now) {
                foreach ($resp->content as $respdata) {
                    $this->assertNotEmpty($respdata->question);
                    $this->assertEquals("Answer from {$user1->id}", $respdata->answer);
                }
            } else {
                $this->assertEquals($pending, $resp->content);
            }
        }

        $user2 = $data->user2;
        $targetuser2 = new target_user($user2);
        $export2 = $userdata->execute_export($targetuser2, $syscontext);
        $this->assertEquals(8, count($export2->data));
        foreach ($export2->data as $resp) {
            $this->assertEquals($resp->userid, $user2->id);
            if ($resp->timecompleted == $data->now) {
                foreach ($resp->content as $respdata) {
                    $this->assertNotEmpty($respdata->question);
                    $this->assertEquals("Answer from {$user2->id}", $respdata->answer);
                }
            } else {
                $this->assertEquals($pending, $resp->content);
            }
        }

        $targetuser3 = new target_user($data->user3);
        $export3 = $userdata->execute_export($targetuser3, $syscontext);
        $this->assertEmpty($export3->data);
        $this->assertEmpty($export3->files);
    }

    /**
     * Test the export function for the feedback360 user assignment userdata items.
     */
    public function test_resp_assignment_purge() {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $data = $this->create_resp_assignments();
        $userdata = new resp_assignments();
        $syscontext = \context_system::instance();

        $user1 = $data->user1;
        $user2 = $data->user2;
        $user3 = $data->user3;

        $this->assertTrue($userdata->is_purgeable(target_user::STATUS_ACTIVE));
        $this->assertTrue($userdata->is_purgeable(target_user::STATUS_SUSPENDED));
        $this->assertTrue($userdata->is_purgeable(target_user::STATUS_DELETED));

        $this->assertEquals(8, $DB->count_records('feedback360_resp_assignment', ['userid' => $user1->id]));
        $this->assertEquals(8, $DB->count_records('feedback360_resp_assignment', ['userid' => $user2->id]));
        $this->assertEquals(0, $DB->count_records('feedback360_resp_assignment', ['userid' => $user3->id]));

        $answertables = [];
        $answertables[1] = "feedback360_quest_data_{$data->fb1->id}";
        $answertables[2] = "feedback360_quest_data_{$data->fb2->id}";
        $answertables[3] = "feedback360_quest_data_{$data->fb3->id}";

        $anscountsql = [];
        foreach ($answertables as $key => $anstable) {
            $anscountsql[$key] = "SELECT COUNT(*)
                                    FROM {{$anstable}}
                                   WHERE feedback360respassignmentid
                                      IN (SELECT id
                                            FROM {feedback360_resp_assignment}
                                           WHERE userid = :uid
                                         )";
        }

        $this->assertEquals(1, $DB->count_records_sql($anscountsql[1], ['uid' => $user1->id]));
        $this->assertEquals(5, $DB->count_records_sql($anscountsql[2], ['uid' => $user1->id]));
        $this->assertEquals(0, $DB->count_records_sql($anscountsql[3], ['uid' => $user1->id]));
        $this->assertEquals(1, $DB->count_records_sql($anscountsql[1], ['uid' => $user2->id]));
        $this->assertEquals(0, $DB->count_records_sql($anscountsql[2], ['uid' => $user2->id]));
        $this->assertEquals(5, $DB->count_records_sql($anscountsql[3], ['uid' => $user2->id]));

        $targetuser1 = new target_user($user1);
        $userdata::execute_purge($targetuser1, $syscontext);

        $this->assertEquals(0, $DB->count_records('feedback360_resp_assignment', ['userid' => $user1->id]));
        $this->assertEquals(8, $DB->count_records('feedback360_resp_assignment', ['userid' => $user2->id]));
        $this->assertEquals(0, $DB->count_records('feedback360_resp_assignment', ['userid' => $user3->id]));

        $this->assertEquals(0, $DB->count_records_sql($anscountsql[1], ['uid' => $user1->id]));
        $this->assertEquals(0, $DB->count_records_sql($anscountsql[2], ['uid' => $user1->id]));
        $this->assertEquals(0, $DB->count_records_sql($anscountsql[3], ['uid' => $user1->id]));
        $this->assertEquals(1, $DB->count_records_sql($anscountsql[1], ['uid' => $user2->id]));
        $this->assertEquals(0, $DB->count_records_sql($anscountsql[2], ['uid' => $user2->id]));
        $this->assertEquals(5, $DB->count_records_sql($anscountsql[3], ['uid' => $user2->id]));

        // Purge user 3 to test empty purges
        $targetuser3 = new target_user($user3);
        $retdata = $userdata->execute_purge($targetuser3, $syscontext);
        $this->assertEquals(resp_assignments::RESULT_STATUS_SUCCESS, $retdata);
    }
}
