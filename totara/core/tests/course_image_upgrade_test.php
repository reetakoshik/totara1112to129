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

class totara_core_course_image_upgrade_testcase extends advanced_testcase {
    /**
     * Test suite: We are going to create a bunch of courses, and add the course image with illegal itemid
     * and try to run the upgrade code, and we make sure that those illegal itemid is no longer existing in the system.
     *
     * @return void
     */
    public function test_upgrade_course_images(): void {
        global $USER, $CFG;
        require_once($CFG->dirroot . "/totara/core/db/upgradelib.php");

        $this->resetAfterTest();
        $this->setAdminUser();

        $gen = $this->getDataGenerator();
        $fs = get_file_storage();

        $files = [];
        $courses = [];

        for ($i = 0; $i < 5; $i++) {
            $course = $gen->create_course();
            $courses[] = $course;

            $ctx = context_course::instance($course->id);
            // Start preparing the file record for it, no point to prepare draft file, because
            // in the end, draft file will be removed from system (this means for production codes)
            $rc = new stdClass();
            $rc->contextid = $ctx->id;
            $rc->component = 'course';
            $rc->filearea = 'images';
            $rc->itemid = $course->id;
            $rc->filepath = '/';
            $rc->filename = uniqid('file_');
            $rc->userid = $USER->id;
            $rc->author = 'Bolobala';
            $rc->license = 'public';

            $file = $fs->create_file_from_string($rc, 'This is a test file');
            $files[] = $file;
        }

        totara_core_upgrade_course_images();

        // Start checking for those illegal files are actually gone gone.
        // Make sure that this created file does not exist anymore after the upgrade, because of its invalid itemid
        foreach ($files as $file) {
            $result = $fs->file_exists(
                $file->get_contextid(),
                $file->get_component(),
                $file->get_filearea(),
                $file->get_itemid(),
                $file->get_filepath(),
                $file->get_filename()
            );

            $this->assertFalse($result, "The file '{$file->get_filename()} is still existing after upgrade");
        }

        // Start checking the course is still able to find its own image.
        foreach ($courses as $course) {
            $url = course_get_image($course);
            $this->assertContains(
                $course->cacherev,
                $url->out(),
                "The course's image for course id '{$course->id}' is invalid after upgrade, " .
                "expected to be found with cacherev of the course: '{$course->cacherev}'"
            );
        }

        $this->assertTrue(true);
    }
}