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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Samantha Jayasinghe <samantha.jayasinghe@totaralearning.com>
 * @package core_course
 * @category totara_catalog
 */

global $CFG;

// Get the necessary files to perform backup and restore.
require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');

defined('MOODLE_INTERNAL') || die();

/**
 * @group totara_catalog
 */
class core_course_totara_catalog_course_restored_observer_testcase extends \advanced_testcase {

    public function setUp() {
        $this->resetAfterTest();
        $this->setAdminUser();
    }

    public function test_course_restored_observer() {
        global $DB, $CFG;

        // Create a course.
        $course = $this->getDataGenerator()->create_course();

        // delete catalog data and check the result
        $DB->delete_records('catalog');
        $this->assertSame(0, $DB->count_records('catalog', ['objecttype' => 'course']));

        // Create backup file and save it to the backup location
        $bc = new backup_controller(
            backup::TYPE_1COURSE, $course->id, backup::FORMAT_MOODLE,
            backup::INTERACTIVE_NO, backup::MODE_GENERAL, 2
        );
        $bc->execute_plan();
        $results = $bc->get_results();
        $file = $results['backup_destination'];
        $fp = get_file_packer('application/vnd.moodle.backup');
        $filepath = $CFG->dataroot . '/temp/backup/test-restore-course-event';
        $file->extract_to_pathname($fp, $filepath);
        $bc->destroy();

        // Now restore the course to trigger the event
        $rc = new restore_controller(
            'test-restore-course-event',
            $course->id,
            backup::INTERACTIVE_NO,
            backup::MODE_GENERAL, 2, backup::TARGET_NEW_COURSE
        );
        $rc->execute_precheck();
        $rc->execute_plan();

        // check the result after event triggered
        $this->assertSame(1, $DB->count_records('catalog', ['objecttype' => 'course']));
    }
}
