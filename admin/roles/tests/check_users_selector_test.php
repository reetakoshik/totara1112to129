<?php
/*
 * This file is part of Totara LMS
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
 * @author Brendan Cox <brendan.cox@totaralearning.com>
 * @package core_role
 */

class core_role_check_users_selector_testcase extends advanced_testcase {

    private $user1, $user2, $user3;
    private $course1, $course2;

    protected function tearDown() {
        $this->user1 = $this->user2 = $this->user3 = null;
        $this->course1 = $this->course2 = null;
        
        parent::tearDown();
    }

    public function setUp() {
        parent::setUp();
        $this->resetAfterTest();

        $this->course1 = $this->getDataGenerator()->create_course();
        $this->course2 = $this->getDataGenerator()->create_course();
        $this->user1 = $this->getDataGenerator()->create_user(array('firstname' => 'ABC1'));
        $this->user2 = $this->getDataGenerator()->create_user(array('firstname' => 'ABC2'));
        $this->user3 = $this->getDataGenerator()->create_user(array('firstname' => 'XYZ3'));

        // Capability checks are done throughout these tests. Run as admin unless specified otherwise.
        $this->setAdminUser();
    }

    /**
     * Tests the core_role_check_users_selector::find_users method.
     * - Uses site context.
     * - No search value.
     */
    public function test_find_users_site_no_search() {
        $context = context_system::instance();
        $options = array(
            'accesscontext' => $context
        );
        $user_selector = new core_role_check_users_selector('htmlname', $options);
        $return = $user_selector->find_users('');

        $this->assertCount(1, $return);
        $this->assertArrayHasKey('Potential users', $return);
        // User count is admin + 3 generated for this test.
        $this->assertCount(4, $return['Potential users']);
    }

    /**
     * Tests the core_role_check_users_selector::find_users method.
     * - Uses site context.
     * - Includes search value.
     */
    public function test_find_users_site_with_search() {
        $context = context_system::instance();
        $options = array(
            'accesscontext' => $context
        );
        $user_selector = new core_role_check_users_selector('htmlname', $options);
        $return = $user_selector->find_users('ABC');

        $this->assertCount(1, $return);
        $this->assertArrayHasKey("Potential users matching 'ABC'", $return);
        $this->assertCount(2, $return["Potential users matching 'ABC'"]);

        $this->assertSame($this->user1->id, array_shift($return["Potential users matching 'ABC'"])->id);
        $this->assertSame($this->user2->id, array_shift($return["Potential users matching 'ABC'"])->id);
    }

    /**
     * Tests the core_role_check_users_selector::find_users method.
     * - Uses course context.
     * - No users are enrolled in the course.
     * - No search value.
     */
    public function test_find_users_course_none_enrolled_no_search() {
        $context = context_course::instance($this->course1->id);
        $options = array(
            'accesscontext' => $context
        );
        $user_selector = new core_role_check_users_selector('htmlname', $options);
        $return = $user_selector->find_users('');

        $this->assertCount(2, $return);
        $this->assertArrayHasKey('', $return);
        $this->assertArrayHasKey('Potential users', $return);
        $this->assertCount(4, $return['Potential users']);
        $this->assertCount(0, $return['']);
    }

    /**
     * Tests the core_role_check_users_selector::find_users method.
     * - Uses course context.
     * - No users are enrolled in the course.
     * - Includes search value.
     */
    public function test_find_users_course_none_enrolled_with_search() {
        $context = context_course::instance($this->course1->id);
        $options = array(
            'accesscontext' => $context
        );
        $user_selector = new core_role_check_users_selector('htmlname', $options);
        $return = $user_selector->find_users('ABC');

        $this->assertCount(2, $return);
        $this->assertArrayHasKey('', $return);
        $this->assertArrayHasKey("Potential users matching 'ABC'", $return);
        $this->assertCount(2, $return["Potential users matching 'ABC'"]);
        $this->assertCount(0, $return['']);

        $this->assertSame($this->user1->id, array_shift($return["Potential users matching 'ABC'"])->id);
        $this->assertSame($this->user2->id, array_shift($return["Potential users matching 'ABC'"])->id);
    }

    /**
     * Tests the core_role_check_users_selector::find_users method.
     * - Uses course context.
     * - One user is enrolled in the course.
     * - No search value.
     */
    public function test_find_users_course_enrolled_no_search() {
        $context = context_course::instance($this->course1->id);
        $options = array(
            'accesscontext' => $context
        );

        // We're going to test course1, but we're enrolling a user in course2 as well to try and throw it off.
        $this->getDataGenerator()->enrol_user($this->user1->id, $this->course1->id);
        $this->getDataGenerator()->enrol_user($this->user2->id, $this->course2->id);

        $user_selector = new core_role_check_users_selector('htmlname', $options);
        $return = $user_selector->find_users('');

        $this->assertCount(3, $return);
        $this->assertArrayHasKey('', $return); // Not sure that this is intended, but is current behaviour.
        $this->assertArrayHasKey('Enrolled users', $return);
        $this->assertArrayHasKey('Potential users', $return);
        $this->assertCount(3, $return['Potential users']);
        $this->assertCount(1, $return['Enrolled users']);
        $this->assertCount(0, $return['']);

        // Let's may sure the enrolled user was correct.
        $this->assertSame($this->user1->id, array_shift($return['Enrolled users'])->id);
    }

    /**
     * Tests the core_role_check_users_selector::find_users method.
     * - Uses course context.
     * - One user is enrolled in the course.
     * - Includes search value.
     */
    public function test_find_users_course_enrolled_with_search() {
        $context = context_course::instance($this->course1->id);
        $options = array(
            'accesscontext' => $context
        );

        // We're going to test course1, but we're enrolling a user in course2 as well to try and throw it off.
        $this->getDataGenerator()->enrol_user($this->user1->id, $this->course1->id);
        $this->getDataGenerator()->enrol_user($this->user2->id, $this->course2->id);

        $user_selector = new core_role_check_users_selector('htmlname', $options);
        $return = $user_selector->find_users('ABC');

        $this->assertCount(3, $return);
        $this->assertArrayHasKey('', $return); // Not sure that this is intended, but is current behaviour.
        $this->assertArrayHasKey("Matching enrolled users", $return);
        $this->assertArrayHasKey("Potential users matching 'ABC'", $return);
        $this->assertCount(1, $return["Matching enrolled users"]);
        $this->assertCount(1, $return["Potential users matching 'ABC'"]);
        $this->assertCount(0, $return['']);

        $this->assertSame($this->user1->id, array_shift($return["Matching enrolled users"])->id);
        $this->assertSame($this->user2->id, array_shift($return["Potential users matching 'ABC'"])->id);
    }

    /**
     * Tests the core_role_check_users_selector::find_users method.
     *
     * Checks for a bug in which the sql to find users including the same user multiple times.
     * - No search value.
     */
    public function test_find_users_with_dual_enrolment_no_search() {
        global $DB;

        $context = context_course::instance($this->course1->id);
        $options = array(
            'accesscontext' => $context
        );

        // Enrol a user to the same course via two different methods.
        $this->getDataGenerator()->enrol_user($this->user1->id, $this->course1->id, null, 'manual');
        $this->getDataGenerator()->enrol_user($this->user1->id, $this->course1->id, null, 'self');

        // Make sure we did end up with 2 enrolments for the one user or we're not testing the issue.
        $enrolments = $DB->get_records('user_enrolments', array('userid' => $this->user1->id));
        $this->assertCount(2, $enrolments);

        $user_selector = new core_role_check_users_selector('htmlname', $options);
        $return = $user_selector->find_users('');
        $this->assertDebuggingNotCalled();

        $this->assertCount(3, $return);
        $this->assertArrayHasKey('', $return); // Not sure that this is intended, but is current behaviour.
        $this->assertArrayHasKey('Enrolled users', $return);
        $this->assertArrayHasKey('Potential users', $return);
        $this->assertCount(3, $return['Potential users']);
        $this->assertCount(1, $return['Enrolled users']);
        $this->assertCount(0, $return['']);

        $this->assertSame($this->user1->id, array_shift($return['Enrolled users'])->id);
    }

    /**
     * Tests the core_role_check_users_selector::find_users method.
     *
     * Checks for a bug in which the sql to find users including the same user multiple times.
     * - Includes search value.
     */
    public function test_find_users_with_dual_enrolment_with_search() {
        global $DB;

        $context = context_course::instance($this->course1->id);
        $options = array(
            'accesscontext' => $context
        );

        // Enrol a user to the same course via two different methods.
        $this->getDataGenerator()->enrol_user($this->user1->id, $this->course1->id, null, 'manual');
        $this->getDataGenerator()->enrol_user($this->user1->id, $this->course1->id, null, 'self');

        // Make sure we did end up with 2 enrolments for the one user or we're not testing the issue.
        $enrolments = $DB->get_records('user_enrolments', array('userid' => $this->user1->id));
        $this->assertCount(2, $enrolments);

        $user_selector = new core_role_check_users_selector('htmlname', $options);
        $return = $user_selector->find_users('ABC');
        $this->assertDebuggingNotCalled();

        $this->assertCount(3, $return);
        $this->assertArrayHasKey('', $return); // Not sure that this is intended, but is current behaviour.
        $this->assertArrayHasKey("Matching enrolled users", $return);
        $this->assertArrayHasKey("Potential users matching 'ABC'", $return);
        $this->assertCount(1, $return["Matching enrolled users"]);
        $this->assertCount(1, $return["Potential users matching 'ABC'"]);
        $this->assertCount(0, $return['']);

        $this->assertSame($this->user1->id, array_shift($return["Matching enrolled users"])->id);
        $this->assertSame($this->user2->id, array_shift($return["Potential users matching 'ABC'"])->id);
    }
}