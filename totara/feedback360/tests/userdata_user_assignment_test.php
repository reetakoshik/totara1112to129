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
use totara_feedback360\userdata\user_assignments;

global $CFG;

require_once($CFG->dirroot . '/totara/feedback360/lib.php');
require_once($CFG->dirroot . '/totara/feedback360/tests/feedback360_testcase.php');

/**
 * @group totara_userdata
 * @group totara_feedback360
 * Class userdata_user_assignment_test
 */
class userdata_user_assignment_test extends feedback360_testcase {

    /**
     * Create some user_assignment data for testing.
     *
     * @return stdClass
     */
    private function create_user_assignments() {
        $retdata = new stdClass();

        // Set up some test users.
        $user1 = $this->getDataGenerator()->create_user(); // Test user_assignment.
        $retdata->user1 = $user1;

        $user2 = $this->getDataGenerator()->create_user(); // Control user.
        $retdata->user2 = $user2;

        $user3 = $this->getDataGenerator()->create_user(); // Blank user.
        $retdata->user3 = $user3;

        $retdata->fb3601 = $this->setup_test_feedback360([$user1, $user2], true);
        $retdata->fb3602 = $this->setup_test_feedback360([$user1, $user2], false);
        $retdata->fb3603 = $this->setup_test_feedback360([$user1], false);
        $retdata->fb3604 = $this->setup_test_feedback360([$user2], false);

        return $retdata;
    }

    /**
     * @param array $assign - An array of users to assign to the feedback360
     * @param boolean $anonymous - A flag to determine if the feedback360 is anonymous
     *
     * @return array - An array containing any information necessary for $retdata
     */
    private function setup_test_feedback360($assign, $anonymous = false) {
        global $DB;

        $now = time();

        // Create a feedback with some user assignments.
        list($fb360, $users, $quests) = $this->prepare_feedback_with_users($assign, 3, $anonymous, feedback360::SELF_EVALUATION_OPTIONAL);

        // Now set up some resp assignments.
        $respusers = [];
        $respassignments = [];
        for ($i=0 ; $i < 5; $i++) {
            $user = $this->getDataGenerator()->create_user();

            foreach ($assign as $assignee) {
                $respassignments[] = $this->assign_resp($fb360, $assignee->id, $user->id);
                $respusers[$user->id] = $user;
            }
        }

        // Now fill in some answers.
        foreach ($respassignments as $respassignment) {
            $rauid = $respassignment->userid;
            $uauid = $DB->get_field('feedback360_user_assignment', 'userid', ['id' => $respassignment->feedback360userassignmentid]);
            $anstable = "feedback360_quest_data_{$fb360->id}";

            // The answer data doesn't exist at this point so insert rather than update.
            $ansdata = new \stdClass();
            $ansdata->timemodified = $now;
            $ansdata->timecompleted = $now;
            $ansdata->feedback360respassignmentid = $respassignment->id;

            foreach ($quests as $question) {
                $column = "data_{$question->id}";
                $ansdata->$column = $anonymous ? "Answer from user(Anonymous user) for user({$uauid})" : "Answer from user({$rauid}) for user({$uauid})";
            }

            $DB->insert_record($anstable, $ansdata);
        }

        return ['feedback' => $fb360, 'respassign' => $respassignments, 'respuser' => $respusers];
    }

    /**
     * Test the count function for the feedback360 user assignment userdata items.
     */
    public function test_user_assignment_count() {
        $this->resetAfterTest(true);
        $this->setAdminUser();

        $data = $this->create_user_assignments();

        $syscontext = \context_system::instance();
        $userdata = new user_assignments();

        $this->assertTrue($userdata->is_countable());

        $targetuser1 = new target_user($data->user1);
        $this->assertEquals(3, $userdata->execute_count($targetuser1, $syscontext));

        $targetuser2 = new target_user($data->user2);
        $this->assertEquals(3, $userdata->execute_count($targetuser2, $syscontext));

        $targetuser3 = new target_user($data->user3);
        $this->assertEquals(0, $userdata->execute_count($targetuser3, $syscontext));
    }

    /**
     * Test the purge function for the feedback360 user assignment userdata items.
     */
    public function test_user_assignment_export() {
        $this->resetAfterTest(true);
        $this->setAdminUser();

        $data = $this->create_user_assignments();

        $syscontext = \context_system::instance();
        $userdata = new user_assignments();

        $user1 = $data->user1;
        $targetuser1 = new target_user($user1);
        $expected1 = $userdata->execute_count($targetuser1, $syscontext);
        $export1 = $userdata->execute_export($targetuser1, $syscontext);

        $this->assertEquals($expected1, count($export1->data));
        foreach ($export1->data as $uadata) {
            // Check the user assignment exported is for the expected user.
            $this->assertEquals($user1->id, $uadata->userid);

            // Check the feedback360 information was included in the user assignment export.
            $this->assertNotEmpty($uadata->feedback360id);
            $this->assertNotEmpty($uadata->feedback360name);
            $this->assertTrue(isset($uadata->feedback360status));
            $this->assertTrue(isset($uadata->feedback360anonymous));

            $this->assertNotEmpty($uadata->timedue);
            $this->assertEquals(5, count($uadata->responses));

            foreach ($uadata->responses as $response) {
                $this->assertEquals($uadata->id, $response->feedback360userassignmentid);
                $this->assertEquals(3, count($response->content));

                foreach ($response->content as $answer) {
                    $this->assertNotEmpty($answer->question);
                    $this->assertEquals("Answer from user({$response->userid}) for user({$user1->id})", $answer->answer);
                }
            }
        }

        $user2 = $data->user2;
        $targetuser2 = new target_user($user2);
        $expected2 = $userdata->execute_count($targetuser2, $syscontext);
        $export2 = $userdata->execute_export($targetuser2, $syscontext);

        $this->assertEquals($expected2, count($export2->data));
        foreach ($export2->data as $uadata) {
            // Check the user assignment exported is for the expected user.
            $this->assertEquals($user2->id, $uadata->userid);

            // Check the feedback360 information was included in the user assignment export.
            $this->assertNotEmpty($uadata->feedback360id);
            $this->assertNotEmpty($uadata->feedback360name);
            $this->assertTrue(isset($uadata->feedback360status));
            $this->assertTrue(isset($uadata->feedback360anonymous));

            $this->assertNotEmpty($uadata->timedue);
            $this->assertEquals(5, count($uadata->responses));

            foreach ($uadata->responses as $response) {
                $this->assertEquals($uadata->id, $response->feedback360userassignmentid);
                $this->assertEquals(3, count($response->content));

                foreach ($response->content as $answer) {
                    $this->assertNotEmpty($answer->question);
                    $this->assertEquals("Answer from user({$response->userid}) for user({$user2->id})", $answer->answer);
                }
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
    public function test_user_assignment_purge() {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $syscontext = \context_system::instance();
        $userdata = new user_assignments();
        $data = $this->create_user_assignments();
        $user1 = $data->user1;
        $user2 = $data->user2;
        $user3 = $data->user3;

        $anstables = [];
        $anstables[1] = "feedback360_quest_data_{$data->fb3601['feedback']->id}";
        $anstables[2] = "feedback360_quest_data_{$data->fb3602['feedback']->id}";
        $anstables[3] = "feedback360_quest_data_{$data->fb3603['feedback']->id}";
        $anstables[4] = "feedback360_quest_data_{$data->fb3604['feedback']->id}";

        $this->assertTrue($userdata->is_purgeable(target_user::STATUS_ACTIVE));
        $this->assertTrue($userdata->is_purgeable(target_user::STATUS_SUSPENDED));
        $this->assertTrue($userdata->is_purgeable(target_user::STATUS_DELETED));

        // Check user assignment counts match expectations.
        $this->assertEquals(3, $DB->count_records('feedback360_user_assignment', ['userid' => $user1->id]));
        $this->assertEquals(3, $DB->count_records('feedback360_user_assignment', ['userid' => $user2->id]));
        $this->assertEquals(0, $DB->count_records('feedback360_user_assignment', ['userid' => $user3->id]));

        // Check total resp assignment counts match expectations.
        $this->assertEquals(30, $DB->count_records('feedback360_resp_assignment'));

        // Check answer counts match expectations.
        $this->assertEquals(10, $DB->count_records($anstables[1]));
        $this->assertEquals(10, $DB->count_records($anstables[2]));
        $this->assertEquals(5, $DB->count_records($anstables[3]));
        $this->assertEquals(5, $DB->count_records($anstables[4]));

        $targetuser1 = new target_user($user1);
        $userdata->execute_purge($targetuser1, $syscontext);

        // Check user assignment counts match the new expectations.
        $this->assertEquals(0, $DB->count_records('feedback360_user_assignment', ['userid' => $user1->id]));
        $this->assertEquals(3, $DB->count_records('feedback360_user_assignment', ['userid' => $user2->id]));
        $this->assertEquals(0, $DB->count_records('feedback360_user_assignment', ['userid' => $user3->id]));

        // Check total resp assignment counts match the new expectations.
        $this->assertEquals(15, $DB->count_records('feedback360_resp_assignment'));

        // Check answer counts match the new expectations.
        $this->assertEquals(5, $DB->count_records($anstables[1]));
        $this->assertEquals(5, $DB->count_records($anstables[2]));
        $this->assertEquals(0, $DB->count_records($anstables[3]));
        $this->assertEquals(5, $DB->count_records($anstables[4]));

        // Check that user 2s data is still all there untouched.
        $targetuser2 = new target_user($user2);
        $expected2 = $userdata->execute_count($targetuser2, $syscontext);
        $export2 = $userdata->execute_export($targetuser2, $syscontext);

        $userassignments = $DB->get_records('feedback360_user_assignment', ['userid' => $user2->id]);
        $this->assertEquals(3, count($userassignments));
        foreach ($userassignments as $uadata) {
            $feedback360 = $DB->get_record('feedback360', ['id' => $uadata->feedback360id]);
            $questions = $DB->get_records('feedback360_quest_field', ['feedback360id' => $feedback360->id]);
            $responses = $DB->get_records('feedback360_resp_assignment', ['feedback360userassignmentid' => $uadata->id]);
            $this->assertEquals(5, count($responses));

            foreach ($responses as $response) {
                $answers = $DB->get_record("feedback360_quest_data_{$feedback360->id}", ['feedback360respassignmentid' => $response->id]);

                foreach ($questions as $question) {
                    $field = "data_{$question->id}";
                    $answer = $answers->$field;

                    if ($feedback360->anonymous) {
                        $expected = "Answer from user(Anonymous user) for user({$user2->id})";
                    } else {
                        $expected = "Answer from user({$response->userid}) for user({$user2->id})";
                    }

                    $this->assertEquals($expected, $answer);
                }
            }
        }

        // Purge user 3 to test empty purges
        $targetuser3 = new target_user($user3);
        $retdata = $userdata->execute_purge($targetuser3, $syscontext);
        $this->assertEquals(user_assignments::RESULT_STATUS_SUCCESS, $retdata);
    }
}
