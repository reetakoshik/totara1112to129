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

use core_user\userdata\privatefiles;
use totara_userdata\userdata\export;
use totara_userdata\userdata\item;
use totara_userdata\userdata\target_user;

defined('MOODLE_INTERNAL') || die();

/**
 * Test purging, exporting and counting of users private files
 *
 * @group totara_userdata
 */
class core_user_userdata_privatefiles_testcase extends advanced_testcase {

    /**
     * test compatible context levels
     */
    public function test_compatible_context_levels() {
        $expectedcontextlevels = [CONTEXT_SYSTEM];
        $this->assertEquals($expectedcontextlevels, privatefiles::get_compatible_context_levels());
    }

    /**
     * test if data is correctly purged
     */
    public function test_purge() {
        $this->resetAfterTest(true);

        // Create the users.
        $activeuser = new target_user($this->getDataGenerator()->create_user());
        $suspendeduser = new target_user($this->getDataGenerator()->create_user(['suspended' => 1]));
        $deleteduser = new target_user($this->getDataGenerator()->create_user(['deleted' => 1]));
        $controluser = new target_user($this->getDataGenerator()->create_user());

        // Add a file to each user except deleted as deleted user does not have a context anymore.
        $this->add_file($activeuser->contextid);
        $this->add_file($suspendeduser->contextid);
        $this->add_file($suspendeduser->contextid);
        $this->add_file($controluser->contextid);
        $this->add_file($controluser->contextid);
        $this->add_file($controluser->contextid);

        // Check if users are purgeable.
        $this->assertTrue(privatefiles::is_purgeable($activeuser->status));
        $this->assertTrue(privatefiles::is_purgeable($suspendeduser->status));
        $this->assertTrue(privatefiles::is_purgeable($deleteduser->status));

        $fs = get_file_storage();

        /****************************
         * PURGE activeuser
         ***************************/
        $result = privatefiles::execute_purge($activeuser, context_system::instance());
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $result);
        $this->assertCount(0, $fs->get_area_files($activeuser->contextid, 'user', 'private'));

        /****************************
         * PURGE suspendeduser
         ***************************/
        $result = privatefiles::execute_purge($suspendeduser, context_system::instance());
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $result);
        $this->assertCount(0, $fs->get_area_files($suspendeduser->contextid, 'user', 'private'));

        /****************************
         * PURGE deleteduser
         ***************************/
        $result = privatefiles::execute_purge($deleteduser, context_system::instance());
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $result);

        // Deleted users don't have a context so we can't check the files.
        // Purge should just run through without any error.

        /****************************
         * CHECK controluser
         ***************************/

        // Control users files must be untouched.
        $controluserfiles = $fs->get_area_files($controluser->contextid, 'user', 'private', 0, 'filename ASC', false);
        $this->assertCount(3, $controluserfiles);
    }

    /**
     * test if data is correctly counted
     */
    public function test_count() {
        $this->resetAfterTest(true);

        // Check if item is exportable.
        $this->assertTrue(privatefiles::is_exportable());

        // Create the users.
        $activeuser = new target_user($this->getDataGenerator()->create_user());
        $suspendeduser = new target_user($this->getDataGenerator()->create_user(['suspended' => 1]));
        $deleteduser = new target_user($this->getDataGenerator()->create_user(['deleted' => 1]));
        $controluser = new target_user($this->getDataGenerator()->create_user());

        // Add a file to each user except deleted as deleted user does not have a context anymore.
        $this->add_file($activeuser->contextid);
        $this->add_file($suspendeduser->contextid);
        $this->add_file($suspendeduser->contextid);
        $this->add_file($controluser->contextid);
        $this->add_file($controluser->contextid);
        $this->add_file($controluser->contextid);

        // Do the count.
        $result = privatefiles::execute_count(new target_user($activeuser), context_system::instance());
        $this->assertEquals(1, $result);

        $result = privatefiles::execute_count(new target_user($suspendeduser), context_system::instance());
        $this->assertEquals(2, $result);

        $result = privatefiles::execute_count(new target_user($deleteduser), context_system::instance());
        $this->assertEquals(0, $result);

        $result = privatefiles::execute_count(new target_user($controluser), context_system::instance());
        $this->assertEquals(3, $result);

        // Purge data.
        privatefiles::execute_purge(new target_user($activeuser), context_system::instance());

        // Count again, it should not find anything.
        $result = privatefiles::execute_count(new target_user($activeuser), context_system::instance());
        $this->assertEquals(0, $result);
    }


    /**
     * test if data is correctly counted
     */
    public function test_export() {
        $this->resetAfterTest(true);

        // Check if item is exportable.
        $this->assertTrue(privatefiles::is_exportable());

        // Create the users.
        $activeuser = new target_user($this->getDataGenerator()->create_user());
        $suspendeduser = new target_user($this->getDataGenerator()->create_user(['suspended' => 1]));
        $deleteduser = new target_user($this->getDataGenerator()->create_user(['deleted' => 1]));

        // Add a file to each user except deleted as deleted user does not have a context anymore.
        $file1 = $this->add_file($activeuser->contextid);
        $file2 = $this->add_file($activeuser->contextid);
        $file3 = $this->add_file($suspendeduser->contextid);
        $file4 = $this->add_file($suspendeduser->contextid);
        $file5 = $this->add_file($suspendeduser->contextid);

        /****************************
         * EXPORT activeuser
         ***************************/

        $result = privatefiles::execute_export($activeuser, context_system::instance());
        $this->assertInstanceOf(export::class, $result);
        $this->assertCount(2, $result->data);
        $this->assertCount(2, $result->files);

        // Check if files array contains the right files.
        $this->assert_contains_file($file1, $result);
        $this->assert_contains_file($file2, $result);

        /****************************
         * EXPORT suspendeduser
         ***************************/

        $result = privatefiles::execute_export($suspendeduser, context_system::instance());
        $this->assertInstanceOf(export::class, $result);
        $this->assertCount(3, $result->data);
        $this->assertCount(3, $result->files);

        // Check if files array contains the right files.
        $this->assert_contains_file($file3, $result);
        $this->assert_contains_file($file4, $result);
        $this->assert_contains_file($file5, $result);

        /****************************
         * EXPORT deleteduser
         ***************************/

        $result = privatefiles::execute_export($deleteduser, context_system::instance());
        $this->assertInstanceOf(export::class, $result);
        // Nothing should be exported for a deleted user.
        $this->assertEmpty($result->data);
        $this->assertEmpty($result->files);
    }

    /**
     * Creates a test file in temp and returns the updated user
     *
     * @return stored_file|null
     */
    private function add_file(int $contextid = null) {
        if ($contextid) {
            $fs = get_file_storage();
            $filerecord = [
                'contextid' => $contextid,
                'component' => 'user',
                'filearea'  => 'private',
                'itemid'    => 0,
                'filepath'  => '/'.random_string(15).'/',
                'filename'  => random_string(15).'.txt'
            ];
            return $fs->create_file_from_string($filerecord, random_string(30));
        }

        return null;
    }

    /**
     * @param stored_file $file
     * @param export $export
     */
    private function assert_contains_file(stored_file $file, export $export) {
        $this->assertArrayHasKey($file->get_filepath(), $export->data);
        $this->assertContains(
            [
                'fileid' => $file->get_id(),
                'filename' => $file->get_filename(),
                'contenthash' => $file->get_contenthash()
            ],
            $export->data[$file->get_filepath()]
        );

        // Check that the correct file is in the files.
        $exportedfileids = [];
        foreach ($export->files as $exportedfile) {
            $this->assertInstanceOf(stored_file::class, $exportedfile);
            $exportedfileids[] = $exportedfile->get_id();
        }
        $this->assertContains($file->get_id(), $exportedfileids);
    }

}