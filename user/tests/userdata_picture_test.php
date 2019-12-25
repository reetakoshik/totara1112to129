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

use core_user\userdata\picture;
use totara_userdata\userdata\export;
use totara_userdata\userdata\item;
use totara_userdata\userdata\target_user;

defined('MOODLE_INTERNAL') || die();

/**
 * Test purging, exporting and counting of users picture
 *
 * @group totara_userdata
 */
class core_user_userdata_picture_testcase extends advanced_testcase {

    /**
     * test compatible context levels
     */
    public function test_compatible_context_levels() {
        $expectedcontextlevels = [CONTEXT_SYSTEM];
        $this->assertEquals($expectedcontextlevels, picture::get_compatible_context_levels());
    }

    /**
     * test if data is correctly purged
     */
    public function test_purge() {
        global $DB;

        $this->resetAfterTest(true);

        // Create the users.
        $activeuser = $this->getDataGenerator()->create_user();
        $suspendeduser = $this->getDataGenerator()->create_user(['suspended' => 1]);
        $deleteduser = $this->getDataGenerator()->create_user(['deleted' => 1]);
        $controluser = $this->getDataGenerator()->create_user();

        // Get the context ids for the users.
        $activeusercontextid = context_user::instance($activeuser->id)->id;
        $suspendedusercontextid = context_user::instance($suspendeduser->id)->id;
        $controlusercontextid = context_user::instance($controluser->id)->id;

        // Add a picture to each user.
        $activeuser = $this->add_picture($activeuser, $activeusercontextid);
        $suspendeduser = $this->add_picture($suspendeduser, $suspendedusercontextid);
        $deleteduser = $this->add_picture($deleteduser);
        $controluser = $this->add_picture($controluser, $controlusercontextid);

        $activeuser = new target_user($activeuser);
        $suspendeduser = new target_user($suspendeduser);
        $deleteduser = new target_user($deleteduser);

        // Check if users are purgeable.
        $this->assertTrue(picture::is_purgeable($activeuser->status));
        $this->assertTrue(picture::is_purgeable($suspendeduser->status));
        $this->assertTrue(picture::is_purgeable($deleteduser->status));

        $fs = get_file_storage();

        /****************************
         * PURGE activeuser
         ***************************/
        $result = picture::execute_purge($activeuser, context_system::instance());
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $result);
        $this->assertCount(0, $fs->get_area_files($activeusercontextid, 'user', 'icon'));

        $activeuserreloaded = $DB->get_record('user', ['id' => $activeuser->id]);
        $this->assertEquals(0, $activeuserreloaded->picture);
        $this->assertEquals('', $activeuserreloaded->imagealt);

        /****************************
         * PURGE suspendeduser
         ***************************/
        $result = picture::execute_purge($suspendeduser, context_system::instance());
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $result);
        $this->assertCount(0, $fs->get_area_files($suspendedusercontextid, 'user', 'icon'));

        $suspendeduserreloaded = $DB->get_record('user', ['id' => $suspendeduser->id]);
        $this->assertEquals(0, $suspendeduserreloaded->picture);
        $this->assertEquals('', $suspendeduserreloaded->imagealt);

        /****************************
         * PURGE deleteduser
         ***************************/
        $result = picture::execute_purge($deleteduser, context_system::instance());
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $result);

        // Deleted users don't have a context so we can't check the files.

        $deleteduserreloaded = $DB->get_record('user', ['id' => $deleteduser->id]);
        $this->assertEquals(0, $deleteduserreloaded->picture);
        $this->assertEquals('', $deleteduserreloaded->imagealt);

        /****************************
         * CHECK controluser
         ***************************/

        // Control users files and data must be untouched.
        $this->assertCount(3, $fs->get_area_files($controlusercontextid, 'user', 'icon', 0, 'filename ASC', false));

        $controluserreloaded = $DB->get_record('user', ['id' => $controluser->id]);

        $this->assertGreaterThan(0, $controluserreloaded->picture);
        $this->assertEquals($controluser->imagealt, $controluserreloaded->imagealt);
    }

    /**
     * test if data is correctly counted
     */
    public function test_count() {
        global $DB;

        $this->resetAfterTest(true);

        // Create the users.
        $activeuser = $this->getDataGenerator()->create_user();
        $suspendeduser = $this->getDataGenerator()->create_user(['suspended' => 1]);
        $deleteduser = $this->getDataGenerator()->create_user(['deleted' => 1]);
        $controluser = $this->getDataGenerator()->create_user();

        // Get the context ids for the users.
        $activeusercontextid = context_user::instance($activeuser->id)->id;
        $suspendedusercontextid = context_user::instance($suspendeduser->id)->id;

        // Add a picture to each user.
        $activeuser = $this->add_picture($activeuser, $activeusercontextid);
        $suspendeduser = $this->add_picture($suspendeduser, $suspendedusercontextid);
        $deleteduser = $this->add_picture($deleteduser);

        // Do the count.
        $result = picture::execute_count(new target_user($activeuser), context_system::instance());
        $this->assertEquals(1, $result);

        $result = picture::execute_count(new target_user($suspendeduser), context_system::instance());
        $this->assertEquals(1, $result);

        $result = picture::execute_count(new target_user($deleteduser), context_system::instance());
        $this->assertEquals(1, $result);

        $result = picture::execute_count(new target_user($controluser), context_system::instance());
        $this->assertEquals(0, $result);

        // Purge data.
        picture::execute_purge(new target_user($activeuser), context_system::instance());

        // Reload user.
        $activeuserreloaded = $DB->get_record('user', ['id' => $activeuser->id]);

        $result = picture::execute_count(new target_user($activeuserreloaded), context_system::instance());
        $this->assertEquals(0, $result);
    }


    /**
     * test if data is correctly counted
     */
    public function test_export() {
        global $DB;

        $this->resetAfterTest(true);

        // Create the users.
        $activeuser = $this->getDataGenerator()->create_user();
        $suspendeduser = $this->getDataGenerator()->create_user(['suspended' => 1]);

        // Get the context ids for the users.
        $activeusercontextid = context_user::instance($activeuser->id)->id;

        // Add a picture to each user.
        $activeuser = $this->add_picture($activeuser, $activeusercontextid);

        /****************************
         * EXPORT activeuser
         ***************************/

        $result = picture::execute_export(new target_user($activeuser), context_system::instance());
        $this->assertInstanceOf(export::class, $result);
        $this->assertArrayHasKey('files', $result->data);
        $this->assertCount(3, $result->data['files']);
        $this->assertCount(3, $result->files);
        $this->assertArrayHasKey('imagealt', $result->data);
        $this->assertEquals($activeuser->imagealt, $result->data['imagealt']);

        // Check if files array contains the right files.
        $fs = get_file_storage();
        $files = $fs->get_area_files($activeusercontextid, 'user', 'icon', 0, 'filename ASC', false);
        foreach ($files as $file) {
            $this->assertContains(
                [
                    'fileid' => $file->get_id(),
                    'filename' => $file->get_filename(),
                    'contenthash' => $file->get_contenthash()
                ],
                $result->data['files']
            );
        }

        /****************************
         * EXPORT suspendeduser
         ***************************/

        $result = picture::execute_export(new target_user($suspendeduser), context_system::instance());
        $this->assertInstanceOf(export::class, $result);
        $this->assertArrayHasKey('files', $result->data);
        $this->assertCount(0, $result->data['files']);
        $this->assertCount(0, $result->files);
        $this->assertArrayHasKey('imagealt', $result->data);
        $this->assertEquals($suspendeduser->imagealt, $result->data['imagealt']);
    }

    /**
     * Creates a test file in temp and returns the updated user
     *
     * @return stdClass
     */
    private function add_picture(stdClass $user, int $contextid = null) {
        global $DB, $CFG;

        // We need some default value in case user is deleted.
        $pictureid = 123;
        if ($contextid) {
            require_once($CFG->libdir . '/gdlib.php');

            // Create a 100*30 image.
            $im = imagecreate(100, 30);
            // Write the string at the top left with blue background.
            imagestring($im, 5, 0, 0, 'Picture!', imagecolorallocate($im, 0, 0, 255));

            $file = tempnam(make_temp_directory('userdata_picture'), 'test');

            imagepng($im, $file);
            imagedestroy($im);

            $activeusercontext = context_user::instance($user->id);
            $pictureid = process_new_icon($activeusercontext, 'user', 'icon', 0, $file);
        }

        $user->picture = $pictureid;
        $user->imagealt = random_string(15);
        $DB->update_record('user', $user);

        return $user;
    }

}