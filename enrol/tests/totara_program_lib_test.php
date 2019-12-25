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
 * @author Nathan Lewis <nathan.lewis@totaralms.com>
 * @package enrol
 * @subpackage totara_program
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Certification module PHPUnit archive test class.
 *
 * To test, run this from the command line from the $CFG->dirroot.
 * vendor/bin/phpunit --verbose enrol_totara_program_lib_testcase enrol/tests/totara_program_lib_test.php
 */
class enrol_totara_program_lib_testcase extends advanced_testcase {

    /**
     * Test that process_program_reassignments is working correctly.
     *
     * Given the specified instance and users, and the enrolment object itself, make sure that
     * only those records are unsuspended, and that user_enrolment_updated event is triggered.
     */
    public function test_process_program_reassignments() {
        global $DB;

        $this->resetAfterTest(true);

        // Enable the program enrolment plugin.
        $enabled = enrol_get_plugins(true);
        $enabled['totara_program'] = true;
        $enabled = array_keys($enabled);
        set_config('enrol_plugins_enabled', implode(',', $enabled));

        // Create courses.
        $course1 = $this->getDataGenerator()->create_course(); // Course we will test with.
        $course2 = $this->getDataGenerator()->create_course(); // Control course.

        // Create users.
        $user1 = $this->getDataGenerator()->create_user(); // Main test user 1, suspended in main test course but not control course.
        $user2 = $this->getDataGenerator()->create_user(); // Main test user 2, suspended in both courses.
        $user3 = $this->getDataGenerator()->create_user(); // Suspended, but not included in reassign call.
        $user4 = $this->getDataGenerator()->create_user(); // Suspended in different course.
        $user5 = $this->getDataGenerator()->create_user(); // Same course as main users, but don't suspend.

        // Enrol the users in the course using the program enrolment plugin.
        /* @var enrol_totara_program_plugin $programenrolmentplugin */
        $programenrolmentplugin = enrol_get_plugin('totara_program');
        $course1instance = $programenrolmentplugin->get_instance_for_course($course1->id);
        $course1enrolid = $course1instance->id;
        $course2instance = $programenrolmentplugin->get_instance_for_course($course2->id);
        $course2enrolid = $course2instance->id;
        $programenrolmentplugin->enrol_user($course1instance, $user1->id);
        $programenrolmentplugin->enrol_user($course2instance, $user1->id);
        $programenrolmentplugin->enrol_user($course1instance, $user2->id);
        $programenrolmentplugin->enrol_user($course2instance, $user2->id);
        $programenrolmentplugin->enrol_user($course1instance, $user3->id);
        $programenrolmentplugin->enrol_user($course2instance, $user4->id);
        $programenrolmentplugin->enrol_user($course1instance, $user5->id);

        // Check that the enrolment records look ok.
        $actualuserenrolments = $DB->get_records('user_enrolments', array(), 'userid, enrolid', 'id, userid, enrolid, status');
        $expecteduserenrolments = array(
            array(
                'userid' => $user1->id,
                'enrolid' => $course1enrolid,
                'status' => ENROL_USER_ACTIVE,
            ),
            array(
                'userid' => $user1->id,
                'enrolid' => $course2enrolid,
                'status' => ENROL_USER_ACTIVE,
            ),
            array(
                'userid' => $user2->id,
                'enrolid' => $course1enrolid,
                'status' => ENROL_USER_ACTIVE,
            ),
            array(
                'userid' => $user2->id,
                'enrolid' => $course2enrolid,
                'status' => ENROL_USER_ACTIVE,
            ),
            array(
                'userid' => $user3->id,
                'enrolid' => $course1enrolid,
                'status' => ENROL_USER_ACTIVE,
            ),
            array(
                'userid' => $user4->id,
                'enrolid' => $course2enrolid,
                'status' => ENROL_USER_ACTIVE,
            ),
            array(
                'userid' => $user5->id,
                'enrolid' => $course1enrolid,
                'status' => ENROL_USER_ACTIVE,
            ),
        );
        $this->assertEquals(count($expecteduserenrolments), count($actualuserenrolments));
        $processedactualuserenrolments = array();
        foreach ($actualuserenrolments as $userenrolment) {
            $processedactualuserenrolments[] = array(
                'userid' => $userenrolment->userid,
                'enrolid' => $userenrolment->enrolid,
                'status' => $userenrolment->status,
            );
        }
        $this->assertEquals($expecteduserenrolments, $processedactualuserenrolments);

        // Suspend the users.
        $programenrolmentplugin->process_program_unassignments($course1instance, array($user1->id, $user2->id, $user3->id));
        $programenrolmentplugin->process_program_unassignments($course2instance, array($user2->id, $user4->id));

        // Check that the users have been suspended.
        $actualuserenrolments = $DB->get_records('user_enrolments', array(), 'userid, enrolid', 'id, userid, enrolid, status');
        $expecteduserenrolments = array(
            array(
                'userid' => $user1->id,
                'enrolid' => $course1enrolid,
                'status' => ENROL_USER_SUSPENDED,
            ),
            array(
                'userid' => $user1->id,
                'enrolid' => $course2enrolid,
                'status' => ENROL_USER_ACTIVE,
            ),
            array(
                'userid' => $user2->id,
                'enrolid' => $course1enrolid,
                'status' => ENROL_USER_SUSPENDED,
            ),
            array(
                'userid' => $user2->id,
                'enrolid' => $course2enrolid,
                'status' => ENROL_USER_SUSPENDED,
            ),
            array(
                'userid' => $user3->id,
                'enrolid' => $course1enrolid,
                'status' => ENROL_USER_SUSPENDED,
            ),
            array(
                'userid' => $user4->id,
                'enrolid' => $course2enrolid,
                'status' => ENROL_USER_SUSPENDED,
            ),
            array(
                'userid' => $user5->id,
                'enrolid' => $course1enrolid,
                'status' => ENROL_USER_ACTIVE,
            ),
        );
        $this->assertEquals(count($expecteduserenrolments), count($actualuserenrolments));
        $processedactualuserenrolments = array();
        foreach ($actualuserenrolments as $userenrolment) {
            $processedactualuserenrolments[] = array(
                'userid' => $userenrolment->userid,
                'enrolid' => $userenrolment->enrolid,
                'status' => $userenrolment->status,
            );
        }
        $this->assertEquals($expecteduserenrolments, $processedactualuserenrolments);

        // Call the function being tested.
        $eventsink = $this->redirectEvents();
        $programenrolmentplugin->process_program_reassignments($course1instance, array($user1->id, $user2->id));
        $events = $eventsink->get_events();

        // Check that the enrolment records have been un-suspended.
        $actualuserenrolments = $DB->get_records('user_enrolments', array(), 'userid, enrolid', 'id, userid, enrolid, status');
        $expecteduserenrolments = array(
            array(
                'userid' => $user1->id,
                'enrolid' => $course1enrolid,
                'status' => ENROL_USER_ACTIVE,
            ),
            array(
                'userid' => $user1->id,
                'enrolid' => $course2enrolid,
                'status' => ENROL_USER_ACTIVE,
            ),
            array(
                'userid' => $user2->id,
                'enrolid' => $course1enrolid,
                'status' => ENROL_USER_ACTIVE,
            ),
            array(
                'userid' => $user2->id,
                'enrolid' => $course2enrolid,
                'status' => ENROL_USER_SUSPENDED,
            ),
            array(
                'userid' => $user3->id,
                'enrolid' => $course1enrolid,
                'status' => ENROL_USER_SUSPENDED,
            ),
            array(
                'userid' => $user4->id,
                'enrolid' => $course2enrolid,
                'status' => ENROL_USER_SUSPENDED,
            ),
            array(
                'userid' => $user5->id,
                'enrolid' => $course1enrolid,
                'status' => ENROL_USER_ACTIVE,
            ),
        );
        $this->assertEquals(count($expecteduserenrolments), count($actualuserenrolments));
        $processedactualuserenrolments = array();
        foreach ($actualuserenrolments as $userenrolment) {
            $processedactualuserenrolments[] = array(
                'userid' => $userenrolment->userid,
                'enrolid' => $userenrolment->enrolid,
                'status' => $userenrolment->status,
            );
        }
        $this->assertEquals($expecteduserenrolments, $processedactualuserenrolments);

        // Check that the correct events were triggered.
        $this->assertEquals(2, count($events));
        if ($events[0]->get_data()['relateduserid'] == $user1->id) {
            $event0data = $events[0]->get_data();
            $event1data = $events[1]->get_data();
        } else {
            $event0data = $events[1]->get_data();
            $event1data = $events[0]->get_data();
        }

        $this->assertEquals('\core\event\user_enrolment_updated', $event0data['eventname']);
        $this->assertEquals('updated', $event0data['action']);
        $this->assertEquals($course1->id, $event0data['courseid']);
        $this->assertEquals($user1->id, $event0data['relateduserid']);
        $this->assertEquals('totara_program', $event0data['other']['enrol']);

        $this->assertEquals($event1data['eventname'], '\core\event\user_enrolment_updated');
        $this->assertEquals($event1data['action'], 'updated');
        $this->assertEquals($event1data['courseid'], $course1->id);
        $this->assertEquals($event1data['relateduserid'], $user2->id);
        $this->assertEquals($event1data['other']['enrol'], 'totara_program');
    }
}