<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2016 onwards Totara Learning Solutions LTD
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
 * @package totara_customfield
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');

class totara_customfield_backup_restore_testcase extends advanced_testcase {

    protected $course1, $course2;

    protected function tearDown() {
        $this->course1 = $this->course2 = null;
        parent::tearDown();
    }

    public function setUp() {
        parent::setUp();

        // Create course customfields.
        /** @var totara_customfield_generator $cfgenerator */
        $cfgenerator = $this->getDataGenerator()->get_plugin_generator('totara_customfield');
        $textids = $cfgenerator->create_text('course', array('text1'));
        $multids = $cfgenerator->create_multiselect('course', array('multi1'=>array('opt1', 'opt2', 'opt3')));

        // Create course 1.
        $this->course1 = $this->getDataGenerator()->create_course(array('fullname'=> 'Course 1'));
        // Add customfields data to course 1.
        $cfgenerator->set_text($this->course1, $textids['text1'], 'value1', 'course', 'course');
        $cfgenerator->set_multiselect($this->course1, $multids['multi1'], array('opt1', 'opt2'), 'course', 'course');

        // Create course 2.
        $this->course2 = $this->getDataGenerator()->create_course(array('fullname'=> 'Course 2'));
        // Add customfields data to course 2.
        $cfgenerator->set_text($this->course2, $textids['text1'], 'value1', 'course', 'course');
        $cfgenerator->set_multiselect($this->course2, $multids['multi1'], array('opt2', 'opt3'), 'course', 'course');
    }

    /**
     * Backs a course up and restores it.
     *
     * @param stdClass $course Course object to backup
     * @param stdClass $user User who is running the backup and restore
     * @return stdClass ID of newly restored course
     */
    protected function backup_and_restore($course, $user) {
        global $DB, $CFG;

        // Turn off file logging, otherwise it can't delete the file (Windows).
        $CFG->backup_file_logger_level = backup::LOG_NONE;

        // Do backup with default settings. MODE_IMPORT means it will just
        // create the directory and not zip it.
        $bc = new backup_controller(backup::TYPE_1COURSE, $course->id,
            backup::FORMAT_MOODLE, backup::INTERACTIVE_NO, backup::MODE_IMPORT,
            $user->id);
        $backupid = $bc->get_backupid();
        $bc->execute_plan();
        $bc->destroy();

        // Do restore to new course with default settings.
        $newcourseid = restore_dbops::create_new_course(
            $course->fullname, $course->shortname . '_2', $course->category);
        $rc = new restore_controller($backupid, $newcourseid,
            backup::INTERACTIVE_NO, backup::MODE_GENERAL, $user->id,
            backup::TARGET_NEW_COURSE);
        $this->assertTrue($rc->execute_precheck());
        $rc->execute_plan();
        $rc->destroy();

        $newcourse = $DB->get_record('course', array('id' => $newcourseid));

        return $newcourse;
    }

    /**
     * Tests that course custom fields are successfully backed up and restored
     * during backup/restore of course.
     */
    public function test_backup_restore_course_customfields() {
        $this->resetAfterTest(true);
        global $DB;

        // Overall there should be 4 records in course_info_data.
        $courseinfodata = $DB->get_records('course_info_data');
        $this->assertCount(4, $courseinfodata);

        // Check the values were correctly assigned to course1.
        $course1cfdata = customfield_get_data($this->course1, 'course', 'course');
        $this->assertEquals('value1', $course1cfdata['text1']);
        $this->assertEquals('opt1, opt2', $course1cfdata['multi1']);

        $admin = get_admin();
        $newcourse = $this->backup_and_restore($this->course1, $admin);

        // Check that data of customfields for the new course exist.
        $newcoursedata = $DB->get_records('course_info_data', array('courseid' => $newcourse->id));
        $this->assertCount(2, $newcoursedata);
        // Overall there should be 6 records in course_info_data.
        $courseinfodata = $DB->get_records('course_info_data');
        $this->assertCount(6, $courseinfodata);

        // Check that the values in the new course match that of course1.
        $newcoursecfdata = customfield_get_data($newcourse, 'course', 'course');
        $this->assertEquals('value1', $newcoursecfdata['text1']);
        $this->assertEquals('opt1, opt2', $newcoursecfdata['multi1']);
    }
}