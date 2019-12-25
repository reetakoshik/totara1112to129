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
 * @author Valerii Kuznetsov <valerii.kuznetsov@totaralms.com>
 * @package totara
 * @subpackage feedback360
 */
global $CFG;
require_once($CFG->dirroot.'/totara/feedback360/tests/feedback360_testcase.php');

/**
 * Class feedback360_responder_test
 *
 * Tests methods from the feedback360_repsonder class.
 */
class feedback360_responder_test extends feedback360_testcase {

    /**
     * @var testing_data_generator
     */
    private $data_generator;

    protected function tearDown() {
        $this->data_generator = null;
        parent::tearDown();
    }

    public function setUp() {
        parent::setUp();
        $this->resetAfterTest(true);

        $this->data_generator = $this->getDataGenerator();
    }

    public function test_edit() {
        global $DB;
        $this->resetAfterTest();
        list($fdbck, $users) = $this->prepare_feedback_with_users();
        $fdbck->activate();
        $user = current($users);
        $time = time();
        $userassignment = $DB->get_record('feedback360_user_assignment', array('feedback360id' => $fdbck->id,
            'userid' => $user->id));
        $respuser = $this->getDataGenerator()->create_user();
        // Get current time to test timedue against.
        $this->setCurrentTimeStart();
        $response = $this->assign_resp($fdbck, $user->id, $respuser->id);
        $response->viewed = true;
        $response->timeassigned = $time;
        $response->timecompleted = $time + 1;
        $response->save();
        $respid = $response->id;
        unset($response);

        $resptest = new feedback360_responder($respid);
        $this->assertEquals(true, $resptest->viewed);
        $this->assertEquals($time, $resptest->timeassigned);
        $this->assertEquals($time + 1, $resptest->timecompleted);
        $this->assertEquals($fdbck->id, $resptest->feedback360id);
        $this->assertEquals($userassignment->id, $resptest->feedback360userassignmentid);
        $this->assertEquals($respuser->id, $resptest->userid);
        $this->assertEquals(feedback360_responder::TYPE_USER, $resptest->type);
        $this->assertEquals($user->id, $resptest->subjectid);
        $this->assertTimeCurrent($resptest->timedue);
    }

    public function test_by_preview() {
        $this->resetAfterTest();
        list($fdbck) = $this->prepare_feedback_with_users();
        $preview = feedback360_responder::by_preview($fdbck->id);
        $this->assertEquals($fdbck->id, $preview->feedback360id);
        $this->assertTrue($preview->is_fake());
        $this->assertFalse($preview->is_email());
        // Preview simulates user response.
        $this->assertTrue($preview->is_user());
    }

    public function test_by_user() {
        $this->resetAfterTest();
        list($fdbck, $users) = $this->prepare_feedback_with_users();
        $user = current($users);
        $respuser = $this->getDataGenerator()->create_user();
        $response = $this->assign_resp($fdbck, $user->id, $respuser->id);
        $respid = $response->id;
        unset($response);

        $byuser = feedback360_responder::by_user($respuser->id, $fdbck->id, $user->id);
        $this->assertEquals($fdbck->id, $byuser->feedback360id);
        $this->assertFalse($byuser->is_fake());
        $this->assertFalse($byuser->is_email());
        $this->assertTrue($byuser->is_user());
        $this->assertEquals($respid, $byuser->id);
        $this->assertEquals($respuser->id, $byuser->userid);
        $this->assertEquals(feedback360_responder::TYPE_USER, $byuser->type);
        $this->assertEquals($user->id, $byuser->subjectid);
    }

    public function test_by_email() {
        global $CFG, $DB;
        $this->preventResetByRollback();
        $this->resetAfterTest();

        $oldlog = ini_get('error_log');
        ini_set('error_log', "$CFG->dataroot/testlog.log"); // Prevent standard logging.
        unset_config('noemailever');

        list($fdbck, $users) = $this->prepare_feedback_with_users();
        $fdbck->activate();
        $user = current($users);
        $time = time();
        $email = 'somebody@example.com';
        $userassignment = $DB->get_record('feedback360_user_assignment', array('feedback360id' => $fdbck->id,
            'userid' => $user->id));

        // Make sure we are redirecting emails.
        $sink = $this->redirectEmails();
        $this->assertTrue(phpunit_util::is_redirecting_phpmailer());

        feedback360_responder::update_external_assignments(array($email), array(), $userassignment->id, $time);

        // Get the email that we just sent.
        $emails = $sink->get_messages();
        $this->assertCount(1, $sink->get_messages());
        $sink->close();

        $emailassignmentrecord = $DB->get_record('feedback360_email_assignment', array('email' => $email), '*', MUST_EXIST);
        $byemail = feedback360_responder::by_email($email, $emailassignmentrecord->token);
        $this->assertEquals($fdbck->id, $byemail->feedback360id);
        $this->assertFalse($byemail->is_fake());
        $this->assertTrue($byemail->is_email());
        $this->assertFalse($byemail->is_user());
        $this->assertEmpty($byemail->userid);
        $this->assertEquals(feedback360_responder::TYPE_EMAIL, $byemail->type);
        $this->assertEquals($user->id, $byemail->subjectid);
        $this->assertEquals($email, $byemail->email);

        ini_set('error_log', $oldlog);
    }

    public function test_complete() {
        $this->resetAfterTest();
        list($fdbck, $users) = $this->prepare_feedback_with_users();
        $user = current($users);
        $response = $this->assign_resp($fdbck, $user->id);
        $time = time();

        $this->assertFalse($response->is_completed());
        $response->complete($time);
        $this->assertTrue($response->is_completed());
        $this->assertEquals($time, $response->timecompleted);

        $respid = $response->id;
        unset($response);
        $respload = new feedback360_responder($respid);
        $this->assertTrue($respload->is_completed());
        $this->assertEquals($time, $respload->timecompleted);
    }

    public function test_update_timedue() {
        global $DB;

        $this->resetAfterTest();
        list($fdbck, $users) = $this->prepare_feedback_with_users();
        $user = current($users);
        $this->setCurrentTimeStart();
        $response = $this->assign_resp($fdbck, $user->id);
        $this->assertTimeCurrent($response->timedue);

        $respid = $response->id;
        unset($response);
        $userassignment = $DB->get_record('feedback360_user_assignment', array('feedback360id' => $fdbck->id,
            'userid' => $user->id));
        $newtimedue = time() + 86400;
        feedback360_responder::update_timedue($newtimedue, $userassignment->id);
        $resptest = new feedback360_responder($respid);
        $this->assertEquals($newtimedue, $resptest->timedue);
    }

    /**
     * Allows execution of a private or protected static method.
     *
     * @param string $classname
     * @param string $methodname
     * @param array $arguments
     * @return mixed the return value of the static method.
     */
    private function execute_restricted_static_method($classname, $methodname, $arguments = array()) {
        $reflection = new \ReflectionClass($classname);
        $method = $reflection->getMethod($methodname);
        $method->setAccessible(true);

        return $method->invokeArgs(null, $arguments);
    }

    /**
     * Allows ability to set a private or protected property of an object.
     *
     * @param mixed $object
     * @param string $propertyname
     * @param mixed $value
     * @return void
     */
    private function set_restricted_property($object, $propertyname, $value) {
        $reflection = new \ReflectionClass(get_class($object));
        $property = $reflection->getProperty($propertyname);
        $property->setAccessible(true);

        $property->setValue($object, $value);
    }

    /**
     * Allows execution of a private or protected method within an object.
     * @param mixed $object
     * @param string $methodname
     * @param array $arguments - in order of the methods argument signature
     * @return mixed the return value of the method after execution
     */
    private function execute_restricted_method($object, $methodname, $arguments = array()) {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodname);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $arguments);
    }

    /**
     * Tests the create_requestertoken() static method.
     */
    public function test_create_requestertoken() {
        $tokens = array();

        // We'll create a number of tokens and make sure they're each unique.
        for($i = 0; $i < 100; $i++) {
            $token = $this->execute_restricted_static_method('feedback360_responder', 'create_requestertoken');
            // The returned string should be an sha1 hash, which will have a length of 40 characters.
            $this->assertEquals(40, strlen($token));
            $tokens[] = $token;
        }

        // All values should be different, so reducing the array of tokens to unique values should not change the array.
        $uniquetokens = array_unique($tokens);
        $this->assertEquals(count($tokens), count($uniquetokens));
    }

    /**
     * Tests the get_by_requestertoken() method.
     */
    public function test_get_by_requester_token() {
        global $DB;

        // Create feedback360 and assign a user for requesting feedback and users for responding.
        list($feedback360, $requesters, $questions) = $this->prepare_feedback_with_users();
        $requester = reset($requesters);

        // Creating 2 system user assignments.
        $user1 = $this->data_generator->create_user();
        $user2 = $this->data_generator->create_user();
        $systemresponder1 = $this->assign_resp($feedback360, $requester->id, $user1->id);
        $systemresponder2 = $this->assign_resp($feedback360, $requester->id, $user2->id);

        $userassignment = $DB->get_record('feedback360_user_assignment', array('feedback360id' => $feedback360->id,
            'userid' => $requester->id));

        // Creating 2 email user assignments.
        feedback360_responder::update_external_assignments(
            array('email1@example.com', 'email2@example.com'),
            array(),
            $userassignment->id,
            0
        );
        $emailassignments = $DB->get_records('feedback360_email_assignment');
        $emailassignment1 = array_pop($emailassignments);
        $emailresponder1 = feedback360_responder::by_email($emailassignment1->email, $emailassignment1->token);
        $emailassignment2 = array_pop($emailassignments);
        $emailresponder2 = feedback360_responder::by_email($emailassignment2->email, $emailassignment2->token);

        // We'll create a custom requestertoken. Making it 40 characters long to match a returned sha1.
        $mock_requestertoken_value = '0123456789012345678901234567890123456789';

        // First test, we expect false to be returned if the this value can't be found anywhere.
        $result = feedback360_responder::get_by_requester_token($mock_requestertoken_value);
        $this->assertFalse($result);

        // Let's set one of the email responders email token (not 'requestertoken', rather the one used for their email
        // assignment). We need to make sure these aren't getting mixed up.
        $this->set_restricted_property($emailresponder1, 'token', $mock_requestertoken_value);
        $emailresponder1->save();
        $result = feedback360_responder::get_by_requester_token($mock_requestertoken_value);
        $this->assertFalse($result);

        // We will be wanting to get systemresponder1 via the requester token.
        $this->set_restricted_property($systemresponder1, 'requestertoken', $mock_requestertoken_value);
        $systemresponder1->save();

        $result = feedback360_responder::get_by_requester_token($mock_requestertoken_value);
        $this->assertEquals($systemresponder1->id, $result->id);
        $this->assertEquals($systemresponder1->userid, $result->userid);
        $this->assertNotEquals($systemresponder2->id, $result->id);
        $this->assertNotEquals($systemresponder2->userid, $result->userid);
        $this->assertNotEquals($emailresponder1->id, $result->id);
        $this->assertNotEquals($emailresponder1->userid, $result->userid);
        $this->assertNotEquals($emailresponder1->get_email(), $result->get_email());
        $this->assertNotEquals($emailresponder2->id, $result->id);
        $this->assertNotEquals($emailresponder2->userid, $result->userid);
        $this->assertNotEquals($emailresponder2->get_email(), $result->get_email());
    }

    /**
     * Tests the load() method with system users (users selected based on their Totara user records,
     * rather than by email).
     */
    public function test_load_systemusers() {
        global $DB;

        // Create feedback360 and assign a user for requesting feedback and users for responding.
        list($feedback360, $requesters, $questions) = $this->prepare_feedback_with_users();
        $requester = reset($requesters);

        $this->setCurrentTimeStart();

        // Creating 2 system user assignments.
        $user1 = $this->data_generator->create_user();
        $user2 = $this->data_generator->create_user();
        $systemresponder1 = $this->assign_resp($feedback360, $requester->id, $user1->id);
        $systemresponder2 = $this->assign_resp($feedback360, $requester->id, $user2->id);

        $userassignment = $DB->get_record('feedback360_user_assignment', array('feedback360id' => $feedback360->id,
            'userid' => $requester->id));

        // Creating 2 email user assignments.
        feedback360_responder::update_external_assignments(
            array('email1@example.com', 'email2@example.com'),
            array(),
            $userassignment->id,
            0
        );

        // Grab db records for system users assigned to be responders to feedback.
        $dbrecords = $DB->get_records_select('feedback360_resp_assignment', 'feedback360emailassignmentid IS NULL');
        // We'll just use the first for our comparisons.
        $dbrecord = array_pop($dbrecords);

        $result = new feedback360_responder();
        $result->load($dbrecord->id);

        // Confirm data loaded is correct.
        $this->assertEquals($dbrecord->id, $result->id);
        $this->assertEquals($feedback360->id, $result->feedback360id);
        $this->assertEquals($userassignment->id, $result->feedback360userassignmentid);
        $this->assertEquals($requester->id, $result->subjectid);
        $this->assertEquals(0, $result->viewed);
        $this->assertTimeCurrent($result->timeassigned);
        $this->assertEquals(0, $result->timecompleted);
        $this->assertTimeCurrent($result->timedue);
        $this->assertEquals(40, strlen($result->requestertoken));
        $this->assertNull($result->feedback360emailassignmentid);
        $this->assertEquals('', $result->get_email());
        $this->assertEquals('', $result->token);
        $this->assertEquals(feedback360_responder::TYPE_USER, $result->type);
        $this->assertEquals($dbrecord->userid, $result->userid);
    }

    /**
     * Tests the load() method with email-based responders. No user in Totara is specified with these
     * users, instead requests for feedback are based on email addresses only.
     */
    public function test_load_emailresponders() {
        global $DB;

        // Create feedback360 and assign a user for requesting feedback and users for responding.
        list($feedback360, $requesters, $questions) = $this->prepare_feedback_with_users();
        $requester = reset($requesters);

        $this->setCurrentTimeStart();

        // Creating 2 system user assignments.
        $user1 = $this->data_generator->create_user();
        $user2 = $this->data_generator->create_user();
        $systemresponder1 = $this->assign_resp($feedback360, $requester->id, $user1->id);
        $systemresponder2 = $this->assign_resp($feedback360, $requester->id, $user2->id);

        $userassignment = $DB->get_record('feedback360_user_assignment', array('feedback360id' => $feedback360->id,
            'userid' => $requester->id));

        // Creating 2 email user assignments.
        feedback360_responder::update_external_assignments(
            array('email1@example.com', 'email2@example.com'),
            array(),
            $userassignment->id,
            0
        );

        // Grab db records for an email user assigned to be responder to feedback.
        $email1assignmentrecord =  $DB->get_record('feedback360_email_assignment', array('email' => 'email1@example.com'));
        $dbrecord = $DB->get_record('feedback360_resp_assignment', array('feedback360emailassignmentid' => $email1assignmentrecord->id));

        $result = new feedback360_responder();
        $result->load($dbrecord->id);

        // Confirm data loaded is correct.
        $this->assertEquals($dbrecord->id, $result->id);
        $this->assertEquals($feedback360->id, $result->feedback360id);
        $this->assertEquals($userassignment->id, $result->feedback360userassignmentid);
        $this->assertEquals($requester->id, $result->subjectid);
        $this->assertEquals(0, $result->viewed);
        $this->assertTimeCurrent($result->timeassigned);
        $this->assertEquals(0, $result->timecompleted);
        $this->assertTimeCurrent($result->timedue);
        $this->assertEquals(40, strlen($result->requestertoken));
        $this->assertNull($result->feedback360emailassignmentid);
        $this->assertEquals('email1@example.com', $result->get_email());
        $this->assertEquals($email1assignmentrecord->token, $result->token);
        $this->assertEquals(feedback360_responder::TYPE_EMAIL, $result->type);
        $this->assertEquals(0, $result->userid);
    }

    /**
     * Tests the get_requestertoken() method.
     */
    public function test_get_requestertoken() {
        $responder1 =  new feedback360_responder();
        $result1 = $this->execute_restricted_method($responder1, 'get_requestertoken');
        $this->assertEquals(40, strlen($result1));
        $result2 = $this->execute_restricted_method($responder1, 'get_requestertoken');
        $this->assertEquals($result1, $result2);

        $responder2 =  new feedback360_responder();
        // We'll create a custom requestertoken. Making it 40 characters long to match a returned sha1.
        $mock_requestertoken_value = '0123456789012345678901234567890123456789';
        $this->set_restricted_property($responder2, 'requestertoken', $mock_requestertoken_value);
        $result3 = $this->execute_restricted_method($responder2, 'get_requestertoken');
        $this->assertEquals($mock_requestertoken_value, $result3);
    }

    /**
     * Tests feedback360_responder::sort_system_userids().
     *
     * In this case, we are adding an empty array of user ids. The assignee to the feedback360
     * has no existing requests.
     */
    public function test_sort_system_userids_empty_no_existing_users() {
        $this->resetAfterTest(true);
        global $DB;

        $user1 = $this->data_generator->create_user();

        /** @var feedback360 $feedback1*/
        list($feedback1) = $this->prepare_feedback_with_users(array($user1));

        $user1feedback1 = $DB->get_field('feedback360_user_assignment', 'id',
            array('feedback360id' => $feedback1->id, 'userid' => $user1->id));

        list($new, $keep, $cancel) = feedback360_responder::sort_system_userids(array(), $user1feedback1);

        $this->assertEquals(array(), $new);
        $this->assertEquals(array(), $keep);
        $this->assertEquals(array(), $cancel);
    }

    /**
     * Tests feedback360_responder::sort_system_userids().
     *
     * In this case, the array of users we are adding are all invalid and the assignee
     * has made no previous requests.
     */
    public function test_sort_system_userids_invalid_ids_only() {
        $this->resetAfterTest(true);
        global $DB;

        $user1 = $this->data_generator->create_user();

        /** @var feedback360 $feedback1*/
        list($feedback1) = $this->prepare_feedback_with_users(array($user1), 1, false, feedback360::SELF_EVALUATION_DISABLED);
        $feedback1->activate();

        $user1feedback1 = $DB->get_field('feedback360_user_assignment', 'id',
            array('feedback360id' => $feedback1->id, 'userid' => $user1->id));

        // Self evaluation is disabled, assignees can not request feedback for themselves.
        $invalidids = array($user1->id);

        $deleteduser = $this->data_generator->create_user();
        $DB->set_field('user', 'deleted', 1, array('id' => $deleteduser->id));
        $invalidids[] = $deleteduser->id;

        $suspendeduser = $this->data_generator->create_user();
        $DB->set_field('user', 'suspended', 1, array('id' => $suspendeduser->id));
        $invalidids[] = $suspendeduser->id;

        $guestuser = guest_user();
        $invalidids[] = $guestuser->id;

        list($new, $keep, $cancel) = feedback360_responder::sort_system_userids($invalidids, $user1feedback1);

        $this->assertEquals(array(), $new);
        $this->assertEquals(array(), $keep);
        $this->assertEquals(array(), $cancel);
    }

    /**
     * Tests feedback360_responder::sort_system_userids().
     *
     * In this case, the array of users we are adding are all invalid and the assignee
     * has made no previous requests.
     */
    public function test_sort_system_userids_self_evaluation() {
        $this->resetAfterTest(true);
        global $DB;

        $user1 = $this->data_generator->create_user();

        /** @var feedback360 $feedback1_optional*/
        list($feedback1_optional) = $this->prepare_feedback_with_users(array($user1), 1, false, feedback360::SELF_EVALUATION_OPTIONAL);
        $feedback1_optional->activate();

        /** @var feedback360 $feedback1_required*/
        list($feedback1_required) = $this->prepare_feedback_with_users(array($user1), 1, false, feedback360::SELF_EVALUATION_REQUIRED);
        $feedback1_required->activate();

        $user1feedback1_optional = $DB->get_field('feedback360_user_assignment', 'id',
            array('feedback360id' => $feedback1_optional->id, 'userid' => $user1->id));

        $user1feedback2_required = $DB->get_field('feedback360_user_assignment', 'id',
            array('feedback360id' => $feedback1_required->id, 'userid' => $user1->id));


        $newuser1 = $this->data_generator->create_user();
        $newuser2 = $this->data_generator->create_user();

        $invalidids = array();

        $deleteduser = $this->data_generator->create_user();
        $DB->set_field('user', 'deleted', 1, array('id' => $deleteduser->id));
        $invalidids[] = $deleteduser->id;

        $suspendeduser = $this->data_generator->create_user();
        $DB->set_field('user', 'suspended', 1, array('id' => $suspendeduser->id));
        $invalidids[] = $suspendeduser->id;

        $guestuser = guest_user();
        $invalidids[] = $guestuser->id;

        // Create the array of users that we want assigned as responders (which includes user1 for self evaluation).
        $userids_to_sort = $invalidids;

        $userids_to_sort[] = $user1->id;
        $userids_to_sort[] = $newuser1->id;
        $userids_to_sort[] = $newuser2->id;

        list($new, $keep, $cancel) = feedback360_responder::sort_system_userids($userids_to_sort, $user1feedback1_optional);

        $this->assertNotContains($deleteduser->id, $new);
        $this->assertNotContains($suspendeduser->id, $new);
        $this->assertNotContains($guestuser->id, $new);

        $this->assertContains($user1->id, $new);
        $this->assertContains($newuser1->id, $new);
        $this->assertContains($newuser2->id, $new);
        $this->assertEquals(3, count($new));

        $this->assertEquals(array(), $keep);
        $this->assertEquals(array(), $cancel);

        list($new, $keep, $cancel) = feedback360_responder::sort_system_userids($userids_to_sort, $user1feedback2_required);

        $this->assertNotContains($deleteduser->id, $new);
        $this->assertNotContains($suspendeduser->id, $new);
        $this->assertNotContains($guestuser->id, $new);

        $this->assertContains($user1->id, $new);
        $this->assertContains($newuser1->id, $new);
        $this->assertContains($newuser2->id, $new);
        $this->assertEquals(3, count($new));

        $this->assertEquals(array(), $keep);
        $this->assertEquals(array(), $cancel);
    }

    /**
     * Tests feedback360_responder::sort_system_userids().
     *
     * In this case, the assignee has several existing response requests already.
     *
     * An array of user ids is supplied and should see:
     * - new users added to $new
     * - existing users that were in the array added to $keep
     * - existing users missing from the array added to $cancel
     * - invalid users not added to new, the one which was already is kept.
     */
    public function test_sort_system_userids_full_sort() {
        $this->resetAfterTest(true);
        global $DB;

        $assignee = $this->data_generator->create_user();

        /** @var feedback360 $feedback1*/
        list($feedback1) = $this->prepare_feedback_with_users(array($assignee), 1, false, feedback360::SELF_EVALUATION_DISABLED);
        $feedback1->activate();

        /** @var feedback360 $feedback2*/
        list($feedback2) = $this->prepare_feedback_with_users(array($assignee), 1, false, feedback360::SELF_EVALUATION_DISABLED);
        $feedback2->activate();

        // Below we get the user assignment id for feedback1.
        // We're not testing what's returned by feedback2, just making sure that doesn't
        // affect feedback1.
        $feedback1userassignmentid = $DB->get_field('feedback360_user_assignment', 'id',
            array('feedback360id' => $feedback1->id, 'userid' => $assignee->id));

        $existing1 = $this->data_generator->create_user();
        $existing2 = $this->data_generator->create_user();
        $existing3 = $this->data_generator->create_user();

        // This user will only be pre-existing in feedback2, not feedback1.
        $existing_in_feedback2 = $this->data_generator->create_user();

        // 'Request feedback' from the above users.
        $this->assign_resp($feedback1, $assignee->id, $existing1->id);
        $this->assign_resp($feedback1, $assignee->id, $existing2->id);
        $this->assign_resp($feedback1, $assignee->id, $existing3->id);
        $this->assign_resp($feedback2, $assignee->id, $existing_in_feedback2->id);

        // Also add an email assignment. We need to make sure this doesn't cause any errors.
        feedback360_responder::update_external_assignments(
            array('email1@example.com'),
            array(),
            $feedback1userassignmentid,
            0
        );
        // Make sure that email assignment was added.
        $email1assignmentrecord =  $DB->get_record('feedback360_email_assignment', array('email' => 'email1@example.com'));
        $this->assertEquals(true,
            $DB->record_exists('feedback360_resp_assignment', array('feedback360emailassignmentid' => $email1assignmentrecord->id)));

        // Now add new users to add plus the invalid users.

        $newuser1 = $this->data_generator->create_user();
        $newuser2 = $this->data_generator->create_user();
        $newuser3 = $this->data_generator->create_user();

        // Self evaluation is disabled, assignees can not request feedback for themselves.
        $invalidids = array($assignee->id);

        $deleteduser = $this->data_generator->create_user();
        $DB->set_field('user', 'deleted', 1, array('id' => $deleteduser->id));
        $invalidids[] = $deleteduser->id;

        $suspendeduser = $this->data_generator->create_user();
        $DB->set_field('user', 'suspended', 1, array('id' => $suspendeduser->id));
        $invalidids[] = $suspendeduser->id;

        // Also make the suspended user pre-assigned.
        $this->assign_resp($feedback1, $assignee->id, $suspendeduser->id);

        $guestuser = guest_user();
        $invalidids[] = $guestuser->id;

        // Create the array of users that we want assigned as responders (which includes new
        // and existing).
        $userids_to_sort = $invalidids;

        $userids_to_sort[] = $newuser1->id;
        $userids_to_sort[] = $newuser2->id;
        $userids_to_sort[] = $existing1->id;

        // See that we've left out $existing2 and $existing3. So these should be added to cancel.
        // $newuser3 was also never included anywhere.

        list($new, $keep, $cancel) = feedback360_responder::sort_system_userids($userids_to_sort, $feedback1userassignmentid);

        $this->assertContains($newuser1->id, $new);
        $this->assertContains($newuser2->id, $new);
        // As such, we can just check the size of the array is 2 and we'll know it's working,
        // but a few extra assertions below may help if debugging is necessary.
        $this->assertNotContains($existing1->id, $new);
        $this->assertNotContains($assignee->id, $new);
        $this->assertNotContains($suspendeduser->id, $new);
        $this->assertNotContains($newuser3, $new);
        $this->assertEquals(2, count($new));

        $this->assertContains($existing1->id, $keep);
        $this->assertContains($suspendeduser->id, $keep);
        $this->assertNotContains($newuser1->id, $keep);
        $this->assertNotContains($deleteduser->id, $keep);
        $this->assertNotContains($existing2->id, $keep);
        $this->assertNotContains($existing_in_feedback2->id, $keep);
        $this->assertEquals(2, count($keep));

        $this->assertContains($existing2->id, $cancel);
        $this->assertContains($existing3->id, $cancel);
        $this->assertNotContains($suspendeduser->id, $cancel);
        $this->assertNotContains($existing1->id, $cancel);
        $this->assertNotContains($existing_in_feedback2, $cancel);
        $this->assertNotContains($assignee->id, $cancel);
        $this->assertEquals(2, count($cancel));
    }

    /**
     * Tests feedback360_responder::get_system_users_by_assignment().
     */
    public function test_get_system_users_by_assignment() {
        $this->resetAfterTest(true);
        global $DB;

        $assignee = $this->data_generator->create_user();

        /** @var feedback360 $feedback1*/
        list($feedback1) = $this->prepare_feedback_with_users(array($assignee));
        $feedback1->activate();

        /** @var feedback360 $feedback2*/
        list($feedback2) = $this->prepare_feedback_with_users(array($assignee));
        $feedback2->activate();

        // Below we get the user assignment id for feedback1.
        // We're not testing what's returned by feedback2, just making sure that doesn't
        // affect feedback1.
        $feedback1userassignmentid = $DB->get_field('feedback360_user_assignment', 'id',
            array('feedback360id' => $feedback1->id, 'userid' => $assignee->id));

        $existing1 = $this->data_generator->create_user();
        $existing2 = $this->data_generator->create_user();

        // We will delete this user after assigning as a responder.
        $deleteduser  = $this->data_generator->create_user();

        // This user will only be pre-existing in feedback2, not feedback1.
        $existing_in_feedback2 = $this->data_generator->create_user();

        // 'Request feedback' from the above users.
        $this->assign_resp($feedback1, $assignee->id, $existing1->id);
        $this->assign_resp($feedback1, $assignee->id, $existing2->id);
        $this->assign_resp($feedback1, $assignee->id, $deleteduser->id);
        $this->assign_resp($feedback2, $assignee->id, $existing_in_feedback2->id);

        $newuser1 = $this->data_generator->create_user();
        $newuser2 = $this->data_generator->create_user();

        $DB->set_field('user', 'deleted', 1, array('id' => $deleteduser->id));

        $returned = feedback360_responder::get_system_users_by_assignment($feedback1userassignmentid);

        $this->assertContains($existing1->id, array_keys($returned));
        $this->assertContains($existing2->id, array_keys($returned));
        $this->assertContains($deleteduser->id, array_keys($returned));
        $this->assertNotContains($existing_in_feedback2->id, array_keys($returned));
        $this->assertNotContains($newuser1->id, array_keys($returned));
        $this->assertNotContains($newuser2->id, array_keys($returned));
        $this->assertNotContains($assignee->id, array_keys($returned));
        $this->assertEquals(3, count($returned));

        // We'll also make sure that the array keys do indeed match their user id.
        foreach($returned as $id => $user) {
            $this->assertEquals($id, $user->id);
        }
    }

    /**
     * Tests feedback360_responder::sort_responder_emails().
     *
     * In this case, we are adding an empty array of emails. The assignee to the feedback360
     * has no existing requests.
     */
    public function test_sort_responder_emails_empty_no_existing_users() {
        $this->resetAfterTest(true);
        global $DB;

        $user1 = $this->data_generator->create_user();

        /** @var feedback360 $feedback1*/
        list($feedback1) = $this->prepare_feedback_with_users(array($user1));

        $user1feedback1 = $DB->get_field('feedback360_user_assignment', 'id',
            array('feedback360id' => $feedback1->id, 'userid' => $user1->id));

        list($new, $keep, $cancel) = feedback360_responder::sort_responder_emails(array(), $user1feedback1);

        $this->assertEquals(array(), $new);
        $this->assertEquals(array(), $keep);
        $this->assertEquals(array(), $cancel);
    }

    /**
     * Tests feedback360_responder::sort_responder_emails().
     *
     * In this case, the assignee has several existing response requests already.
     *
     * An array of emails is supplied and should see:
     * - new emails added to $new
     * - existing emails that were in the array added to $keep
     * - existing emails missing from the array added to $cancel
     */
    public function test_sort_responder_emails_full_sort() {
        $this->resetAfterTest(true);
        global $DB;

        $assignee = $this->data_generator->create_user();

        /** @var feedback360 $feedback1*/
        list($feedback1) = $this->prepare_feedback_with_users(array($assignee));
        $feedback1->activate();

        /** @var feedback360 $feedback2*/
        list($feedback2) = $this->prepare_feedback_with_users(array($assignee));
        $feedback2->activate();

        // Below we get the user assignment id for feedback1.
        // We're not testing what's returned by feedback2, just making sure that doesn't
        // affect feedback1.
        $feedback1userassignmentid = $DB->get_field('feedback360_user_assignment', 'id',
            array('feedback360id' => $feedback1->id, 'userid' => $assignee->id));
        $feedback2userassignmentid = $DB->get_field('feedback360_user_assignment', 'id',
            array('feedback360id' => $feedback2->id, 'userid' => $assignee->id));

        // We're creating system users as well, just to ensure that doesn't cause an exception.
        $existing1 = $this->data_generator->create_user();
        $existing_in_feedback2 = $this->data_generator->create_user();
        $this->assign_resp($feedback1, $assignee->id, $existing1->id);
        $this->assign_resp($feedback2, $assignee->id, $existing_in_feedback2->id);


        feedback360_responder::update_external_assignments(
            array('existing1@example.com', 'existing2@example.com', 'existing3@example.com'),
            array(),
            $feedback1userassignmentid,
            0
        );

        // Add an email assignment to feedback2 also.
        feedback360_responder::update_external_assignments(
            array('existing_in_feedback2@example.com'),
            array(),
            $feedback2userassignmentid,
            0
        );

        $emails_to_sort  = array('new1@example.com', 'new2@exameple.com', 'existing1@example.com');

        list($new, $keep, $cancel) = feedback360_responder::sort_responder_emails($emails_to_sort, $feedback1userassignmentid);

        $this->assertContains('new1@example.com', $new);
        $this->assertContains('new2@exameple.com', $new);
        // As such, we can just check the size of the array is 2 and we'll know it's working,
        // but a few extra assertions below may help if debugging is necessary.
        $this->assertNotContains('existing1@example.com', $new);
        $this->assertNotContains('existing2@example.com', $new);
        $this->assertNotContains('existing3@example.com', $new);
        $this->assertNotContains('existing_in_feedback2@example.com', $new);
        $this->assertEquals(2, count($new));

        $this->assertContains('existing1@example.com', $keep);
        $this->assertNotContains('new1@example.com', $keep);
        $this->assertNotContains('new2@exameple.com', $keep);
        $this->assertNotContains('existing2@example.com', $keep);
        $this->assertNotContains('existing3@example.com', $keep);
        $this->assertNotContains('existing_in_feedback2@example.com', $keep);
        $this->assertEquals(1, count($keep));

        $this->assertContains('existing2@example.com', $cancel);
        $this->assertContains('existing3@example.com', $cancel);
        $this->assertNotContains('existing1@example.com', $cancel);
        $this->assertNotContains('new1@example.com', $cancel);
        $this->assertNotContains('new2@exameple.com', $cancel);
        $this->assertNotContains('existing_in_feedback2@example.com', $cancel);
        $this->assertEquals(2, count($cancel));
    }

    /**
     * Tests feedback360_responder::get_emails_by_assignment().
     */
    public function test_get_emails_by_assignment() {
        $this->resetAfterTest(true);
        global $DB;

        $assignee = $this->data_generator->create_user();

        /** @var feedback360 $feedback1*/
        list($feedback1) = $this->prepare_feedback_with_users(array($assignee));
        $feedback1->activate();

        /** @var feedback360 $feedback2*/
        list($feedback2) = $this->prepare_feedback_with_users(array($assignee));
        $feedback2->activate();

        // Below we get the user assignment id for feedback1.
        // We're not testing what's returned by feedback2, just making sure that doesn't
        // affect feedback1.
        $feedback1userassignmentid = $DB->get_field('feedback360_user_assignment', 'id',
            array('feedback360id' => $feedback1->id, 'userid' => $assignee->id));
        $feedback2userassignmentid = $DB->get_field('feedback360_user_assignment', 'id',
            array('feedback360id' => $feedback2->id, 'userid' => $assignee->id));

        // We're creating system users as well, just to ensure that doesn't cause an exception.
        $existing1 = $this->data_generator->create_user();
        $existing_in_feedback2 = $this->data_generator->create_user();
        $this->assign_resp($feedback1, $assignee->id, $existing1->id);
        $this->assign_resp($feedback2, $assignee->id, $existing_in_feedback2->id);


        feedback360_responder::update_external_assignments(
            array('existing1@example.com', 'existing2@example.com'),
            array(),
            $feedback1userassignmentid,
            0
        );

        // Add an email assignment to feedback2 also.
        feedback360_responder::update_external_assignments(
            array('existing_in_feedback2@example.com'),
            array(),
            $feedback2userassignmentid,
            0
        );

        $returned = feedback360_responder::get_emails_by_assignment($feedback1userassignmentid);

        $this->assertContains('existing1@example.com', $returned);
        $this->assertContains('existing2@example.com', $returned);
        $this->assertNotContains('existing_in_feedback2@example.com', $returned);
        $this->assertEquals(2, count($returned));

        // Make sure the keys for the array are ids from the feedback360_resp_assignment.
        foreach($returned as $id => $email) {
            $resp_record = $DB->get_record('feedback360_resp_assignment', array('id' => $id));
            $email_record = $DB->get_record('feedback360_email_assignment',
                array('id' => $resp_record->feedback360emailassignmentid));
            $this->assertEquals($email, $email_record->email);
        }
    }

    /**
     * Tests feedback360_responder::validate_new_timedue_timestamp().
     */
    public function test_validate_new_timedue_timestamp() {
        $this->resetAfterTest(true);
        global $DB;

        $user1 = $this->data_generator->create_user();

        /** @var feedback360 $feedback1*/
        list($feedback1) = $this->prepare_feedback_with_users(array($user1));
        $feedback1->activate();

        $feedback1userassignment = $DB->get_record('feedback360_user_assignment',
            array('feedback360id' => $feedback1->id, 'userid' => $user1->id));

        // To begin with the timedue should be 0, which means 'not set'.
        $this->assertEquals(0, $feedback1userassignment->timedue);

        // The validation function should only prevent non-zero values that are less than the time
        // now from being added.
        $errors = feedback360_responder::validate_new_timedue_timestamp(0, $feedback1userassignment->id);
        $this->assertEmpty($errors);
        $errors = feedback360_responder::validate_new_timedue_timestamp((time() - 1), $feedback1userassignment->id);
        $this->assertEquals('Due date is in the past, please select a value in the future.', $errors['duedate']);
        // The method should generally take a fraction of a second, but just going overkill with avoiding
        // random failures on slow tests as the concept of adding a few seconds or 1 hour is the same really.
        $errors = feedback360_responder::validate_new_timedue_timestamp(time() + HOURSECS, $feedback1userassignment->id);
        $this->assertEmpty($errors);

        // Update the timedue and make sure that worked.
        $newtime = time() + 2 * HOURSECS;
        feedback360_responder::update_timedue($newtime, $feedback1userassignment->id);

        $feedback1userassignment = $DB->get_record('feedback360_user_assignment',
            array('feedback360id' => $feedback1->id, 'userid' => $user1->id));
        $this->assertEquals($newtime, $feedback1userassignment->timedue);

        // The validation function should still allow the timedue to be unset (via 0) or otherwise
        // times that are later than the current time due.
        $errors = feedback360_responder::validate_new_timedue_timestamp(0, $feedback1userassignment->id);
        $this->assertEmpty($errors);
        $errors = feedback360_responder::validate_new_timedue_timestamp((time() - 1), $feedback1userassignment->id);
        $this->assertEquals('The due date can not be set to an earlier date, please set it to a date equal to or after the existing due date.', $errors['duedate']);
        $errors = feedback360_responder::validate_new_timedue_timestamp((time() + HOURSECS), $feedback1userassignment->id);
        $this->assertEquals('The due date can not be set to an earlier date, please set it to a date equal to or after the existing due date.', $errors['duedate']);
        $errors = feedback360_responder::validate_new_timedue_timestamp(time() + 3 * HOURSECS, $feedback1userassignment->id);
        $this->assertEmpty($errors);
    }
}