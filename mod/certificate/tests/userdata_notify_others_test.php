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
 * @author Rob Tyler <rob.tyler@totaralearning.com>
 * @package mod_certificate
 */

use mod_certificate\userdata\notify_others;
use totara_userdata\userdata\target_user;

/**
 * @group totara_userdata
 * @group mod_certificate
 */
class mod_certificate_userdata_notify_others_testcase extends advanced_testcase {

    /**
     * Set up and return the data we need to run the test cases.
     *
     * @return object
     */
    private function setup_data() {
        global $DB;

        $data = new class () {
            /*
             * @var coursecat $category1 course category data.
             */
            public $category1;

            /*
             * @var coursecat $category2 course category data.
             */
            public $category2;

            /*
             * @var stdClass $course1 course data.
             */
            public $course1;

            /*
             * @var stdClass $course2 course data.
             */
            public $course2;

            /*
             * @var $certificate1 \stdClass Certificate data.
             */
            public $certificate1;

            /*
             * @var $certificate2 \stdClass Certificate data.
             */
            public $certificate2;

            /*
             * @var $certificate3 \stdClass Certificate data.
             */
            public $certificate3;

            /*
             * @var $certificate1 \stdClass Certificate data.
             */
            public $certificate_issue1;

            /*
             * @var $certificate2 \stdClass Certificate issue data.
             */
            public $certificate_issue2;

            /*
             * @var $certificate3 \stdClass Certificate issue data.
             */
            public $certificate_issue3;

            /*
             * @var target_ser1 $target_user User data.
             */
            public $target_user1;

            /*
             * @var target_ser2 $target_user User data.
             */
            public $target_user2;

            /*
             * @var target_ser3 $target_user User data.
             */
            public $target_user3;
        };

        $this->resetAfterTest(true);

        $generator = $this->getDataGenerator();

        $user1 = $generator->create_user(['email' => 'dave@example.com']);
        $user2 = $generator->create_user(['email' => 'bob@example.com']);
        $user3 = $generator->create_user(['email' => '']);

        // Create a target user object that uses the email address we want to test with.
        $data->target_user1 = new target_user($user1);
        $data->target_user2 = new target_user($user2);
        $data->target_user3 = new target_user($user3);

        $data->category1 = $generator->create_category();
        $data->category2 = $generator->create_category();

        // Create a course to hang any certificates on.
        $data->course1 = $generator->create_course(['category' => $data->category1->id]);
        course_create_sections_if_missing($data->course1, array(0, 1));

        $data->course2 = $generator->create_course(['category' => $data->category2->id]);
        course_create_sections_if_missing($data->course2, array(0, 1));

        // Define some certificate data with different notify other email data.
        $params1 = [];
        $params1['course'] = $data->course1;
        $params1['emailothers'] = 'bob@example.com, trev@example.com';

        $params2 = [];
        $params2['course'] = $data->course2;
        $params2['emailothers'] = 'dave@example.com, bob@example.com, fred@example.com';

        $params3 = [];
        $params3['course'] = $data->course2;
        $params3['emailothers'] = 'dave@example.com';

        // Create three certificates from the given data.
        $data->certificate1 = $generator->create_module('certificate', $params1);
        $data->certificate2 = $generator->create_module('certificate', $params2);
        $data->certificate3 = $generator->create_module('certificate', $params3);
        $this->assertEquals(3, $DB->count_records('certificate'));

        return $data;
    }

    /**
     * Check the email addresses stored agains all of the certificates.
     *
     * @param array $certificates List of certifiacte objects.
     * @param array $emailaddresses List of eachmail addresses to match agains the notify others field.
     */
    private function assert_notify_others($data, array $emailaddresses) {
        global $DB;

        list($where, $params) = $DB->get_in_or_equal([$data->certificate1->id, $data->certificate2->id, $data->certificate3->id]);

        $sql = "SELECT id, emailothers
                FROM {certificate}
                WHERE id {$where}";
        $result = $DB->get_records_sql($sql, $params);

        $this->assertEquals($emailaddresses[0], $result[$data->certificate1->id]->emailothers);
        $this->assertEquals($emailaddresses[1], $result[$data->certificate2->id]->emailothers);
        $this->assertEquals($emailaddresses[2], $result[$data->certificate3->id]->emailothers);
    }

    /**
     * Test function is_compatible_context_level with all possible contexts.
     */
    public function test_get_compatible_context_levels() {
        $this->assertEquals([CONTEXT_SYSTEM, CONTEXT_COURSECAT, CONTEXT_COURSE, CONTEXT_MODULE], \mod_certificate\userdata\notify_others::get_compatible_context_levels());
    }

    /**
     * Test function is_purgeable.
     */
    public function test_is_purgeable() {
        $this->assertTrue(\mod_certificate\userdata\notify_others::is_purgeable(target_user::STATUS_ACTIVE));
        $this->assertTrue(\mod_certificate\userdata\notify_others::is_purgeable(target_user::STATUS_SUSPENDED));
        $this->assertTrue(\mod_certificate\userdata\notify_others::is_purgeable(target_user::STATUS_DELETED));
    }

    /**
     * Test function is_exportable.
     */
    public function test_is_exportable() {
        $this->assertFalse(\mod_certificate\userdata\notify_others::is_exportable());
    }

    /**
     * Test function is_countable.
     */
    public function test_is_countable() {
        $this->assertTrue(\mod_certificate\userdata\notify_others::is_countable());
    }

    /**
     * Test function execute_count in_system context ensuring correct number of certificates per user are returned.
     */
    public function test_count_in_system_context() {
        $data = $this->setup_data();

        // Get a count of the number of certificates that have the target_user's email address.
        $count = notify_others::execute_count($data->target_user1, context_system::instance());
        $this->assertEquals(2, $count);

        $count = notify_others::execute_count($data->target_user2, context_system::instance());
        $this->assertEquals(2, $count);

        $count = notify_others::execute_count($data->target_user3, context_system::instance());
        $this->assertEquals(0, $count);
    }

    /**
     * Test function execute_count in_system context ensuring correct number of certificates per user are returned.
     */
    public function test_count_in_category_context() {
        $data = $this->setup_data();

        // Check the number of course categories that match user1s email.
        $count = notify_others::execute_count($data->target_user1, context_coursecat::instance($data->category1->id));
        $this->assertEquals(0, $count);
        $count = notify_others::execute_count($data->target_user1, context_coursecat::instance($data->category2->id));
        $this->assertEquals(2, $count);

        // Check the number of course categories that match user2s email.
        $count = notify_others::execute_count($data->target_user2, context_coursecat::instance($data->category1->id));
        $this->assertEquals(1, $count);
        $count = notify_others::execute_count($data->target_user2, context_coursecat::instance($data->category2->id));
        $this->assertEquals(1, $count);

        // Check the number of course categories that match user3s email.
        $count = notify_others::execute_count($data->target_user3, context_coursecat::instance($data->category1->id));
        $this->assertEquals(0, $count);
        $count = notify_others::execute_count($data->target_user3, context_coursecat::instance($data->category2->id));
        $this->assertEquals(0, $count);
    }

    /**
     * Test function execute_count in course context ensuring correct number of certificates per user are returned.
     */
    public function test_count_in_course_context() {
        $data = $this->setup_data();

        // Check the number of course categories that match user1s email.
        $count = notify_others::execute_count($data->target_user1, context_course::instance($data->course1->id));
        $this->assertEquals(0, $count);
        $count = notify_others::execute_count($data->target_user1, context_course::instance($data->course2->id));
        $this->assertEquals(2, $count);

        // Check the number of course categories that match user2s email.
        $count = notify_others::execute_count($data->target_user2, context_course::instance($data->course1->id));
        $this->assertEquals(1, $count);
        $count = notify_others::execute_count($data->target_user2, context_course::instance($data->course2->id));
        $this->assertEquals(1, $count);

        // Check the number of course categories that match user3s email.
        $count = notify_others::execute_count($data->target_user3, context_course::instance($data->course1->id));
        $this->assertEquals(0, $count);
        $count = notify_others::execute_count($data->target_user3, context_course::instance($data->course2->id));
        $this->assertEquals(0, $count);
    }

    /**
     * Test function execute_count in module context ensuring correct number of certificates per user are returned.
     */
    public function test_count_in_module_context() {
        $data = $this->setup_data();

        // Check the number of course categories that match user1s email.
        $count = notify_others::execute_count($data->target_user1, context_module::instance($data->certificate1->cmid));
        $this->assertEquals(0, $count);
        $count = notify_others::execute_count($data->target_user1, context_module::instance($data->certificate2->cmid));
        $this->assertEquals(1, $count);
        $count = notify_others::execute_count($data->target_user1, context_module::instance($data->certificate3->cmid));
        $this->assertEquals(1, $count);

        // Check the number of course categories that match user2s email.
        $count = notify_others::execute_count($data->target_user2, context_module::instance($data->certificate1->cmid));
        $this->assertEquals(1, $count);
        $count = notify_others::execute_count($data->target_user2, context_module::instance($data->certificate2->cmid));
        $this->assertEquals(1, $count);
        $count = notify_others::execute_count($data->target_user2, context_module::instance($data->certificate3->cmid));
        $this->assertEquals(0, $count);

        // Check the number of course categories that match user3s email.
        $count = notify_others::execute_count($data->target_user3, context_module::instance($data->certificate1->cmid));
        $this->assertEquals(0, $count);
        $count = notify_others::execute_count($data->target_user3, context_module::instance($data->certificate2->cmid));
        $this->assertEquals(0, $count);
        $count = notify_others::execute_count($data->target_user3, context_module::instance($data->certificate3->cmid));
        $this->assertEquals(0, $count);
    }

    /**
     * Test function execute_purge in system context ensuring correct number of certificates are updated per user.
     */
    public function test_purge_in_system_context() {
        $data = $this->setup_data();

        // Check that a user without en email address does not break things.
        $count = notify_others::execute_purge($data->target_user3, context_system::instance());
        $this->assertEquals(mod_certificate\userdata\certificate_issues_history::RESULT_STATUS_SUCCESS, $count);

        // Check that the certificates using the first two users are unaffected.
        $count = notify_others::execute_count($data->target_user1, context_system::instance());
        $this->assertEquals(2, $count);
        $count = notify_others::execute_count($data->target_user2, context_system::instance());
        $this->assertEquals(2, $count);

        // Check the email addresses remain unchanged.
        $this->assert_notify_others($data, [
            'bob@example.com, trev@example.com',
            'dave@example.com, bob@example.com, fred@example.com',
            'dave@example.com'
        ]);

        // Purge the certificate records of the first users email address.
        $result = notify_others::execute_purge($data->target_user1, context_system::instance());
        $this->assertEquals(mod_certificate\userdata\certificate_issues_history::RESULT_STATUS_SUCCESS, $result);

        // Use the count method to check no instances of the first users email exists.
        $count = notify_others::execute_count($data->target_user1, context_system::instance());
        $this->assertEquals(0, $count);

        // Check the certificate using our second users email isn't affected.
        $count = notify_others::execute_count($data->target_user2, context_system::instance());
        $this->assertEquals(2, $count);

        // Check the email addresses has been correctly removed.
        $this->assert_notify_others($data, [
            'bob@example.com, trev@example.com',
            'bob@example.com, fred@example.com',
            ''
        ]);
    }

    /**
     * Test function execute_purge in category context ensuring correct number of certificates are updated per user.
     */
    public function test_purge_in_category_context() {
        $data = $this->setup_data();

        // Check that a user without en email address does not break things.
        $result = notify_others::execute_purge($data->target_user3, context_coursecat::instance($data->category1->id));
        $this->assertEquals(mod_certificate\userdata\certificate_issues_history::RESULT_STATUS_SUCCESS, $result);
        $result = notify_others::execute_purge($data->target_user3, context_coursecat::instance($data->category2->id));
        $this->assertEquals(mod_certificate\userdata\certificate_issues_history::RESULT_STATUS_SUCCESS, $result);

        // Verify the number of categories that match user1s email remain unchanged.
        $count = notify_others::execute_count($data->target_user1, context_coursecat::instance($data->category1->id));
        $this->assertEquals(0, $count);
        $count = notify_others::execute_count($data->target_user1, context_coursecat::instance($data->category2->id));
        $this->assertEquals(2, $count);
        $count = notify_others::execute_count($data->target_user2, context_coursecat::instance($data->category1->id));
        $this->assertEquals(1, $count);
        $count = notify_others::execute_count($data->target_user2, context_coursecat::instance($data->category2->id));
        $this->assertEquals(1, $count);

        // Check the email addresses remain unchanged.
        $this->assert_notify_others($data, [
            'bob@example.com, trev@example.com',
            'dave@example.com, bob@example.com, fred@example.com',
            'dave@example.com'
        ]);

        // Verify the number of course categories that match user2s email remain unchanged.
        $count = notify_others::execute_count($data->target_user2, context_coursecat::instance($data->category1->id));
        $this->assertEquals(1, $count);
        $count = notify_others::execute_count($data->target_user2, context_coursecat::instance($data->category2->id));
        $this->assertEquals(1, $count);

        // Purge the certificate records of the first users email address.
        $result = notify_others::execute_purge($data->target_user1, context_coursecat::instance($data->category2->id));
        $this->assertEquals(mod_certificate\userdata\certificate_issues_history::RESULT_STATUS_SUCCESS, $result);

        // Use the count method to check no instances of user1s email.
        $count = notify_others::execute_count($data->target_user1, context_coursecat::instance($data->category1->id));
        $this->assertEquals(0, $count);
        $count = notify_others::execute_count($data->target_user1, context_coursecat::instance($data->category2->id));
        $this->assertEquals(0, $count);

        // Use the count method to check no instances of user2s email.
        $count = notify_others::execute_count($data->target_user2, context_coursecat::instance($data->category1->id));
        $this->assertEquals(1, $count);
        $count = notify_others::execute_count($data->target_user2, context_coursecat::instance($data->category2->id));
        $this->assertEquals(1, $count);

        // Check the email addresses has been correctly removed.
        $this->assert_notify_others($data, [
            'bob@example.com, trev@example.com',
            'bob@example.com, fred@example.com',
            ''
        ]);
    }

    /**
     * Test function execute_purge in course context ensuring correct number of certificates are updated per user.
     */
    public function test_purge_in_course_context() {
        $data = $this->setup_data();

        // Check that a user without en email address does not break things.
        $result = notify_others::execute_purge($data->target_user3, context_course::instance($data->course1->id));
        $this->assertEquals(mod_certificate\userdata\certificate_issues_history::RESULT_STATUS_SUCCESS, $result);
        $result = notify_others::execute_purge($data->target_user3, context_course::instance($data->course2->id));
        $this->assertEquals(mod_certificate\userdata\certificate_issues_history::RESULT_STATUS_SUCCESS, $result);

        // Verify the number of courses that match user1s email remain unchanged.
        $count = notify_others::execute_count($data->target_user1, context_course::instance($data->course1->id));
        $this->assertEquals(0, $count);
        $count = notify_others::execute_count($data->target_user1, context_course::instance($data->course2->id));
        $this->assertEquals(2, $count);

        // Check the email addresses remain unchanged.
        $this->assert_notify_others($data, [
            'bob@example.com, trev@example.com',
            'dave@example.com, bob@example.com, fred@example.com',
            'dave@example.com'
        ]);

        // Verify the number of courses that match user2s email remain unchanged.
        $count = notify_others::execute_count($data->target_user2, context_course::instance($data->course1->id));
        $this->assertEquals(1, $count);
        $count = notify_others::execute_count($data->target_user2, context_course::instance($data->course2->id));
        $this->assertEquals(1, $count);

        // Purge the certificate records of the first users email address.
        $result = notify_others::execute_purge($data->target_user1, context_course::instance($data->course2->id));
        $this->assertEquals(mod_certificate\userdata\certificate_issues_history::RESULT_STATUS_SUCCESS, $result);

        // Use the count method to check no instances of user1s email.
        $count = notify_others::execute_count($data->target_user1, context_course::instance($data->course1->id));
        $this->assertEquals(0, $count);
        $count = notify_others::execute_count($data->target_user1, context_course::instance($data->course2->id));
        $this->assertEquals(0, $count);

        // Use the count method to check no instances of user2s email.
        $count = notify_others::execute_count($data->target_user2, context_course::instance($data->course1->id));
        $this->assertEquals(1, $count);
        $count = notify_others::execute_count($data->target_user2, context_course::instance($data->course2->id));
        $this->assertEquals(1, $count);

        // Check the email addresses has been correctly removed.
        $this->assert_notify_others($data, [
            'bob@example.com, trev@example.com',
            'bob@example.com, fred@example.com',
            ''
        ]);
    }

    /**
     * Test function execute_purge in module context ensuring correct number of certificates are updated per user.
     */
    public function test_purge_in_module_context() {
        $data = $this->setup_data();

        // Check that a user without en email address does not break things.
        $result = notify_others::execute_purge($data->target_user3, context_module::instance($data->certificate1->cmid));
        $this->assertEquals(mod_certificate\userdata\certificate_issues_history::RESULT_STATUS_SUCCESS, $result);
        $result = notify_others::execute_purge($data->target_user3, context_module::instance($data->certificate2->cmid));
        $this->assertEquals(mod_certificate\userdata\certificate_issues_history::RESULT_STATUS_SUCCESS, $result);
        $result = notify_others::execute_purge($data->target_user3, context_module::instance($data->certificate3->cmid));
        $this->assertEquals(mod_certificate\userdata\certificate_issues_history::RESULT_STATUS_SUCCESS, $result);

        // Verify the number of certificates that match user1s email remain unchanged.
        $count = notify_others::execute_count($data->target_user1, context_module::instance($data->certificate1->cmid));
        $this->assertEquals(0, $count);
        $count = notify_others::execute_count($data->target_user1, context_module::instance($data->certificate2->cmid));
        $this->assertEquals(1, $count);
        $count = notify_others::execute_count($data->target_user1, context_module::instance($data->certificate3->cmid));
        $this->assertEquals(1, $count);

        // Verify the number of certificates that match user2s email remain unchanged.
        $count = notify_others::execute_count($data->target_user2, context_module::instance($data->certificate1->cmid));
        $this->assertEquals(1, $count);
        $count = notify_others::execute_count($data->target_user2, context_module::instance($data->certificate2->cmid));
        $this->assertEquals(1, $count);
        $count = notify_others::execute_count($data->target_user2, context_module::instance($data->certificate3->cmid));
        $this->assertEquals(0, $count);

        // Check the email addresses remain unchanged.
        $this->assert_notify_others($data, [
            'bob@example.com, trev@example.com',
            'dave@example.com, bob@example.com, fred@example.com',
            'dave@example.com'
        ]);

        // Purge the certificate records of the first users email address.
        $result = notify_others::execute_purge($data->target_user1, context_module::instance($data->certificate2->cmid));
        $this->assertEquals(mod_certificate\userdata\certificate_issues_history::RESULT_STATUS_SUCCESS, $result);

        // Use the count method to check no instances of user1s email.
        $count = notify_others::execute_count($data->target_user1, context_module::instance($data->certificate1->cmid));
        $this->assertEquals(0, $count);
        $count = notify_others::execute_count($data->target_user1, context_module::instance($data->certificate2->cmid));
        $this->assertEquals(0, $count);
        $count = notify_others::execute_count($data->target_user1, context_module::instance($data->certificate3->cmid));
        $this->assertEquals(1, $count);

        // Use the count method to check no instances of user2s email.
        $count = notify_others::execute_count($data->target_user2, context_module::instance($data->certificate1->cmid));
        $this->assertEquals(1, $count);
        $count = notify_others::execute_count($data->target_user2, context_module::instance($data->certificate2->cmid));
        $this->assertEquals(1, $count);
        $count = notify_others::execute_count($data->target_user2, context_module::instance($data->certificate3->cmid));
        $this->assertEquals(0, $count);

        // Check the email addresses has been correctly removed.
        $this->assert_notify_others($data, [
            'bob@example.com, trev@example.com',
            'bob@example.com, fred@example.com',
            'dave@example.com'
        ]);
    }

}
