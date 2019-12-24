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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package core_user
 */

use core_user\userdata\otherfields;
use totara_userdata\userdata\export;
use totara_userdata\userdata\item;
use totara_userdata\userdata\target_user;

defined('MOODLE_INTERNAL') || die();

/**
 * Test purging, exporting and counting of user other fields
 * Includes: ICQ, Skype, Yahoo, AIM, MSN, phone, mobile phone, institution, department, address, city, country, url, description
 *
 * @group totara_userdata
 */
class core_user_userdata_otherfields_testcase extends advanced_testcase {

    /**
     * test compatible context levels
     */
    public function test_compatible_context_levels() {
        $expectedcontextlevels = [CONTEXT_SYSTEM];
        $this->assertEquals($expectedcontextlevels, otherfields::get_compatible_context_levels());
    }

    /**
     * test if data is correctly purged
     */
    public function test_purge() {
        global $DB;

        $this->resetAfterTest(true);

        /******************************
         * PREPARE USERS
         *****************************/

        // Control user.
        $controluser = $this->getDataGenerator()->create_user();
        // Active user with all names.
        $activeuser = $this->getDataGenerator()->create_user();
        // Deleted user with all names.
        $deleteduser = $this->getDataGenerator()->create_user(['deleted' => 1]);
        // Suspended user with all names.
        $suspendeduser = $this->getDataGenerator()->create_user(['suspended' => 1]);

        $controluser = new target_user($this->setup_otherfields($controluser));
        $activeuser = new target_user($this->setup_otherfields($activeuser));
        $deleteduser = new target_user($this->setup_otherfields($deleteduser));
        $suspendeduser = new target_user($this->setup_otherfields($suspendeduser));

        $fs = get_file_storage();
        $fs->create_file_from_string(
            [
                'contextid' => $controluser->contextid,
                'component' => 'user',
                'filearea' => 'profile',
                'itemid' => 0,
                'filepath' => '/',
                'filename' => 'controlfile.txt'
            ],
            'control file content'
        );

        $fs = get_file_storage();
        $fs->create_file_from_string(
            [
                'contextid' => $activeuser->contextid,
                'component' => 'user',
                'filearea' => 'profile',
                'itemid' => 0,
                'filepath' => '/',
                'filename' => 'testfile.txt'
            ],
            'test file content'
        );
        $fs->create_file_from_string(
            [
                'contextid' => $activeuser->contextid,
                'component' => 'user',
                'filearea' => 'profile',
                'itemid' => 0,
                'filepath' => '/',
                'filename' => 'testfile2.txt'
            ],
            'test file 2 content'
        );

        // Make sure we have some files.
        $this->assertNotEmpty($fs->get_area_files($activeuser->contextid, 'user', 'profile'));
        $this->assertNotEmpty($fs->get_area_files($controluser->contextid, 'user', 'profile'));

        // To test if timemodified changed we need to pause for a second.
        sleep(1);

        // We want to catch events.
        $sink = $this->redirectEvents();

        $this->assertTrue(otherfields::is_purgeable($activeuser->status));
        $this->assertTrue(otherfields::is_purgeable($deleteduser->status));
        $this->assertTrue(otherfields::is_purgeable($suspendeduser->status));

        /******************************
         * PURGE activeuser
         *****************************/

        $result = otherfields::execute_purge($activeuser, context_system::instance());
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $result);
        $events = $sink->get_events();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(\core\event\user_updated::class, reset($events));
        $sink->clear();

        $activeuserreloaded = $DB->get_record('user', ['id' => $activeuser->id]);

        $fields = otherfields::get_other_fields();
        foreach ($fields as $field) {
            $this->assertEquals('', $activeuserreloaded->$field);
        }
        $this->assertGreaterThan($activeuser->timemodified, $activeuserreloaded->timemodified);

        // Files are purged as well.
        $this->assertEmpty($fs->get_area_files($activeuser->contextid, 'user', 'profile'));

        /******************************
         * PURGE suspendeduser
         *****************************/

        $result = otherfields::execute_purge($suspendeduser, context_system::instance());
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $result);
        $events = $sink->get_events();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(\core\event\user_updated::class, reset($events));
        $sink->clear();

        $suspendeduserreloaded = $DB->get_record('user', ['id' => $suspendeduser->id]);

        $fields = otherfields::get_other_fields();
        foreach ($fields as $field) {
            $this->assertEquals('', $suspendeduserreloaded->$field);
        }
        $this->assertGreaterThan($activeuser->timemodified, $suspendeduserreloaded->timemodified);

        /******************************
         * PURGE deleteduser
         *****************************/

        $result = otherfields::execute_purge($deleteduser, context_system::instance());
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $result);
        $events = $sink->get_events();
        // No event for deleted user fired.
        $this->assertCount(0, $events);

        $deleteduserreloaded = $DB->get_record('user', ['id' => $deleteduser->id]);

        $fields = otherfields::get_other_fields();
        foreach ($fields as $field) {
            $this->assertEquals('', $deleteduserreloaded->$field);
        }
        // For deleted users timemodified shouldn't have changed.
        $this->assertEquals($deleteduser->timemodified, $deleteduserreloaded->timemodified);

        /******************************
         * CHECK controluser
         *****************************/

        $controluserreloaded = $DB->get_record('user', ['id' => $controluser->id]);

        $fields = otherfields::get_other_fields();
        foreach ($fields as $field) {
            $this->assertEquals($controluser->$field, $controluserreloaded->$field);
        }

        // Files are purged as well.
        $this->assertNotEmpty($fs->get_area_files($controluser->contextid, 'user', 'profile'));
    }

    /**
     * test if data is correctly counted
     */
    public function test_count() {
        $this->resetAfterTest(true);

        // Set up users.
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user(['deleted' => 1]);
        $user3 = $this->getDataGenerator()->create_user();
        $user4 = $this->getDataGenerator()->create_user();

        $user1 = $this->setup_otherfields($user1, 4);
        $user2 = $this->setup_otherfields($user2, 7);
        $user3 = $this->setup_otherfields($user3);

        // Do the count.
        $result = otherfields::execute_count(new target_user($user1), context_system::instance());
        $this->assertEquals(4, $result);
        $result = otherfields::execute_count(new target_user($user2), context_system::instance());
        $this->assertEquals(7, $result);
        $result = otherfields::execute_count(new target_user($user3), context_system::instance());
        $this->assertEquals(count(otherfields::get_other_fields()), $result);

        // No custom fields filled in.
        $result = otherfields::execute_count(new target_user($user4), context_system::instance());
        $this->assertEquals(0, $result);
    }


    /**
     * test if data is correctly counted
     */
    public function test_export() {
        $this->resetAfterTest(true);

        // Set up users.
        $activeuser = $this->setup_otherfields($this->getDataGenerator()->create_user(), 4);

        $fields = otherfields::get_other_fields();

        $activeuser = new target_user($activeuser);

        // Create two test files.
        $fs = get_file_storage();
        $file1 = $fs->create_file_from_string(
            [
                'contextid' => $activeuser->contextid,
                'component' => 'user',
                'filearea' => 'profile',
                'itemid' => 0,
                'filepath' => '/',
                'filename' => 'testfile.txt'
            ],
            'test file content'
        );
        $file2 = $fs->create_file_from_string(
            [
                'contextid' => $activeuser->contextid,
                'component' => 'user',
                'filearea' => 'profile',
                'itemid' => 0,
                'filepath' => '/',
                'filename' => 'testfile2.txt'
            ],
            'test file 2 content'
        );

        // Export data.
        $result = otherfields::execute_export($activeuser, context_system::instance());
        $this->assertInstanceOf(export::class, $result);
        $this->assertArrayHasKey('otherfields', $result->data);
        $this->assertArrayHasKey('files', $result->data);
        $this->assertCount(count($fields), $result->data['otherfields']);
        foreach ($fields as $field) {
            $this->assertArrayHasKey($field, $result->data['otherfields']);
            $this->assertEquals($activeuser->$field, $result->data['otherfields'][$field]);
        }
        // The two files created previously should be in the export as well.
        $this->assertCount(2, $result->data['files']);
        $this->assertContains(
            [
                'fileid' => $file1->get_id(),
                'filename' => $file1->get_filename(),
                'contenthash' => $file1->get_contenthash()
            ],
            $result->data['files']
        );
        $this->assertContains(
            [
                'fileid' => $file2->get_id(),
                'filename' => $file2->get_filename(),
                'contenthash' => $file2->get_contenthash()
            ],
            $result->data['files']
        );
    }

    /**
     * Sets other fields with random values, if you pass a limit then it chooses the fields randomly
     *
     * @param stdClass $user
     * @param int $limit how many fields you want to fill
     * @return stdClass
     */
    private function setup_otherfields(stdClass $user, int $limit = null): stdClass {
        global $DB;

        $fields = otherfields::get_other_fields();
        shuffle($fields);
        $fields = array_slice($fields, 0, $limit);
        foreach ($fields as $field) {
            // The common max length of all fields is 2, limited by countrycode.
            $user->$field = random_string(2);
        }
        $DB->update_record('user', $user);
        return $user;
    }

}