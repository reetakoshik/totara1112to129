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
 * @author Brendan Cox <brendan.cox@totaralms.com>
 * @package totara_program
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/totara/reportbuilder/tests/reportcache_advanced_testcase.php');

/**
 * Tests relating to the recurring courses feature within programs.
 */
class totara_program_recurring_courses_testcase extends reportcache_advanced_testcase {

    private $users;

    protected function tearDown() {
        $this->users = null;
        parent::tearDown();
    }

    /**
     * Adds a recurring course to a program.
     *
     * @param stdClass|program $program
     * @param stdClass $course
     */
    public function add_recurring_courseset($program, $course) {
        $recurringcourseset = new recurring_course_set($program->id);
        $recurringcourseset->course = $course;
        $recurringcourseset->save_set();
    }

    public function test_copy_recurring_courses_task() {
        $this->resetAfterTest(true);
        global $DB;

        $generator = $this->getDataGenerator();
        /** @var totara_program_generator $programgenerator */
        $programgenerator = $generator->get_plugin_generator('totara_program');

        $course = $generator->create_course(array('enablecompletion' => 1));

        $program = $programgenerator->create_program();
        $this->add_recurring_courseset($program, $course);

        // Create users and assign users to the programs as individuals..
        for ($i = 1; $i <= 5; $i++) {
            $this->users[$i] = $this->getDataGenerator()->create_user();
            $this->getDataGenerator()->assign_to_program($program->id, ASSIGNTYPE_INDIVIDUAL, $this->users[$i]->id);
            $this->getDataGenerator()->enrol_user($this->users[$i]->id, $course->id, 'student');
        }

        // The 2 courses at this stage will be the course created in this test and the 'site' course.
        $this->assertEquals(2, $DB->count_records('course'));
        $this->assertEquals(5, $DB->count_records('user_enrolments'));
        $this->assertEquals(5, $DB->count_records('course_completions'));

        $this->setAdminUser();

        ob_start();
        $task = new totara_program\task\copy_recurring_courses_task();
        $task->execute();
        ob_end_clean();

        // The courses table should now include a record for the newly restored course as well as the previous courses.
        $this->assertEquals(3, $DB->count_records('course'));
        $this->assertEquals(10, $DB->count_records('user_enrolments'));
        $this->assertEquals(10, $DB->count_records('course_completions'));

        $newcourseid = $DB->get_field('prog_recurrence', 'nextcourseid', array('programid' => $program->id));
        // TODO odd behaviour: enrolment method should be 'totara_program',
        // but if it is set to 'totara_program' on L71, then enrolments are not copied over into recurring course.
        $newenrolid = $DB->get_field('enrol', 'id', array('enrol' => 'manual', 'courseid' => $newcourseid));

        foreach ($this->users as $user) {
            $this->assertEquals(
                $DB->get_field('user_enrolments', 'timestart', array('userid' => $user->id, 'enrolid' => $newenrolid)),
                $DB->get_field('course_completions', 'timeenrolled', array('userid' => $user->id, 'course' => $newcourseid))
            );
        }
    }
}
