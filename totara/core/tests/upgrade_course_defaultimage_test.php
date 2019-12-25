<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package totara_core
 */

defined('MOODLE_INTERNAL') || die();

class totara_core_upgrade_course_defaultimage_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_upgrade(): void {
        global $CFG;
        require_once("{$CFG->dirroot}/totara/core/db/upgradelib.php");

        $this->resetAfterTest();
        $this->setAdminUser();

        $context = context_system::instance();

        // We want the item id to not be a zero, so that this test is able to assure that the file at this itemid
        // is no longer exist after upgrade.
        $fileinfo = [
            'contextid' => $context->id,
            'component' => 'course',
            'filearea' => 'defaultimage',
            'filepath' => '/',
            'filename' => 'hello_world.png',
            'itemid' => 999,
            'license' => 'public'
        ];

        $fs = get_file_storage();
        $fs->create_file_from_string($fileinfo, 'Hello world !!!');

        $files = $fs->get_area_files($context->id, 'course', 'defaultimage', false, 'itemid, filepath, filename', false);
        $file = reset($files);

        $url = "{$CFG->wwwroot}/pluginfile.php/{$context->id}/course/defaultimage/{$file->get_filename()}";
        set_config('defaultimage', $url, 'course');
        totara_core_upgrade_course_defaultimage_config();

        $a = get_config('course', 'defaultimage');
        $this->assertNotEmpty($a);
        $this->assertEquals($file->get_filepath() . $file->get_filename(), $a);

        // Check whether there is file presenting in mdl_file storage.
        $fs = get_file_storage();
        $context = context_system::instance();

        $files = $fs->get_area_files(
            $context->id,
            'course',
            'defaultimage',
            false,
            'itemid, filepath, filename',
            false
        );

        $this->assertCount(1, $files);

        $file = reset($files);
        $this->assertEquals(0, $file->get_itemid());

        // Start creating a default course, trying to get a default image and expect it to be equal with the
        // the one just upgraded.
        $gen = $this->getDataGenerator();
        $course = $gen->create_course();

        $themerev = theme_get_revision();
        $expected = "{$CFG->wwwroot}/pluginfile.php/{$context->id}/course/defaultimage/{$themerev}/{$file->get_filename()}";

        $imageurl = course_get_image($course);
        $this->assertEquals($expected, $imageurl->out());
    }

    /**
     * Test suite: If the configstoredfile had been already used, and the file for course_defaultimage has been inserted
     * into the system. Then the itemid of that file will be a zero, and the upgrade code should be ignoring it.
     * Therefore, by the end of the upgrade process, the file should not be changed at all.
     *
     * @return void
     */
    public function test_upgrade_default_course_image_with_configstoredfile(): void {
        global $CFG, $USER;
        require_once("{$CFG->dirroot}/totara/core/db/upgradelib.php");

        $this->resetAfterTest();
        $context = context_system::instance();

        $record = new stdClass();
        $record->contextid = $context->id;
        $record->component = 'course';
        $record->filearea = 'defaultimage';
        $record->itemid = 0;
        $record->filepath = '/';
        $record->filename = 'hello_world.png';
        $record->userid = $USER->id;
        $record->author = 'Bolobala';
        $record->license = 'public';

        $fs = get_file_storage();
        $storedfile = $fs->create_file_from_string($record, 'Hello World !!!');

        totara_core_upgrade_course_defaultimage_config();

        $files = $fs->get_area_files(
            $context->id,
            'course',
            'defaultimage',
            0,
            'itemid, filepath, filename',
            false
        );

        // There must be only one file for course_defaultimage.
        $this->assertCount(1, $files);

        $file = reset($files);
        $this->assertEquals($file, $storedfile);
    }

    /**
     * Test suite: As a system with more than one course default image that had been added, then it is to ensure that
     * upgrade code is upgrading the right record for the config value, then check whether those un-used records would
     * be deleted or not.
     *
     * @return void
     */
    public function test_upgrade_default_course_image(): void {
        global $CFG, $USER;
        require_once("{$CFG->dirroot}/totara/core/db/upgradelib.php");

        $this->resetAfterTest();
        $this->setAdminUser();

        $context = context_system::instance();
        $fs = get_file_storage();

        $storedfile = null;
        $time = time();

        // Start creating more than one file for course_defaultimage. As the upgrade code will try to delete them
        // after all. The last inserted file will be kept and modified, because it would be last update from user.
        $itemid = rand(0, 999);
        for ($i = 0; $i < 2; $i++) {
            $record = new stdClass();
            $record->contextid = $context->id;
            $record->userid = $USER->id;
            $record->component = 'course';
            $record->filearea = 'defaultimage';
            $record->itemid = $itemid;
            $record->filepath = '/';
            $record->filename = uniqid('file_') . 'png';
            $record->author = 'Bolobala';
            $record->license = 'public';
            $record->timemodified = $time;

            $time += 3600;
            $storedfile = $fs->create_file_from_string($record, 'Hello World !!!');

            // Modify the itemid here, so that it would not add duplicated itemid.
            $itemid += 1;
        }

        totara_core_upgrade_course_defaultimage_config();
        $files = $fs->get_area_files(
            $context->id,
            'course',
            'defaultimage',
            false,
            'itemid, filepath, filename',
            false
        );

        // After upgrade, there must be only one file left.
        $this->assertCount(1, $files);
        $file = reset($files);

        $this->assertEquals(0, $file->get_itemid());
        $this->assertEquals($file->get_filename(), $storedfile->get_filename());
    }

    /**
     * Test suite: A system with more than one course default images, but the latest updated record is using the
     * configstoredfile to write the image. Then the latest file should be a valid one. And the test is to ensure that
     * the upgrade path should not do anything to the valid default image but to remove the unused the course default
     * image.
     *
     * @return void
     */
    public function test_upgrade_default_image_with_unused_record(): void {
        global $CFG, $USER;
        require_once("{$CFG->dirroot}/totara/core/db/upgradelib.php");

        $this->resetAfterTest();
        $this->setAdminUser();

        $context = context_system::instance();
        $fs = get_file_storage();

        $record = new stdClass();
        $record->contextid = $context->id;
        $record->userid = $USER->id;
        $record->component = 'course';
        $record->filearea = 'defaultimage';
        $record->itemid = 1920;
        $record->filepath = '/';
        $record->filename = 'hello_world1.png';
        $record->license = 'public';
        $record->timemodified = time();

        $fs->create_file_from_string($record, 'Hello world !!!');
        unset($record);

        $record2 = new stdClass();
        $record2->contextid = $context->id;
        $record2->userid = $USER->id;
        $record2->component = 'course';
        $record2->filearea = 'defaultimage';
        $record2->itemid = 0;
        $record2->filepath = '/';
        $record2->filename = 'hello_world2.png';
        $record2->license = 'public';
        $record2->timemodified = time() + 3600;

        $storedfile = $fs->create_file_from_string($record2, 'Hello World 1 2 3 !!!');
        unset($record2);

        totara_core_upgrade_course_defaultimage_config();
        $files = $fs->get_area_files(
            $context->id,
            'course',
            'defaultimage',
            false,
            'itemid, filepath, filename',
            false
        );

        $this->assertCount(1, $files);

        $file = reset($files);
        $this->assertEquals($storedfile, $file);
    }
}