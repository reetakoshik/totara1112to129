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
 * @author David Curry <david.curry@totaralearning.com>
 * @package totara_program
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/totara/reportbuilder/tests/reportcache_advanced_testcase.php');
require_once($CFG->dirroot . '/totara/program/lib.php');
require_once($CFG->dirroot . '/totara/program/db/upgradelib.php');

/**
 * Program module PHPUnit test class.
 *
 * To test, run this from the command line from the $CFG->dirroot.
 * vendor/bin/phpunit --verbose totara_program_upgradelib_testcase totara/program/tests/upgradelib_test.php
 */
class totara_program_upgradelib_testcase extends reportcache_advanced_testcase {
    /** @var totara_reportbuilder_cache_generator */
    private $data_generator;

    /** @var stdClass */
    private $user1, $user2, $user3, $user4, $user5, $user6, $user7;

    /** @var stdClass */
    private $course1, $course2, $course3, $course4, $course5, $course6;

    /** @var program */
    private $program1, $program2;

    private $past, $now, $future;

    protected function tearDown() {
        $this->data_generator = null;
        $this->user1 = null;
        $this->course1 = null;
        $this->program1 = null;
        $this->past = null;
        parent::tearDown();
    }

    public function setUp() {
        parent::setUp();
        global $DB;

        $this->now = time();
        $this->past = $this->now - 50000;
        $this->future = $this->now + 50000;

        $this->resetAfterTest(true);
        set_config('enablecompletion', 1);

        $this->data_generator = $this->getDataGenerator();

        // Create some users.
        $this->user1 = $this->data_generator->create_user(); // P1 - Not started.
        $this->user2 = $this->data_generator->create_user(); // P1 - Started CS1 Past
        $this->user3 = $this->data_generator->create_user(); // P1 - Started CS1 Future
        $this->user4 = $this->data_generator->create_user(); // P1 - Started CS2.
        $this->user5 = $this->data_generator->create_user(); // P1 - Completed everythin, P2 - Started.
        $this->user6 = $this->data_generator->create_user(); // P2 - Started.
        $this->user7 = $this->data_generator->create_user(); // Not assigned.

        // Create some courses.
        $this->course1 = $this->data_generator->create_course(array('enablecompletion' => 1));
        $this->course2 = $this->data_generator->create_course(array('enablecompletion' => 1));
        $this->course3 = $this->data_generator->create_course(array('enablecompletion' => 1));
        $this->course4 = $this->data_generator->create_course(array('enablecompletion' => 1));
        $this->course5 = $this->data_generator->create_course(array('enablecompletion' => 1));
        $this->course6 = $this->data_generator->create_course(array('enablecompletion' => 1));

        // Create some programs.
        $this->program1 = $this->data_generator->create_program();
        $this->program2 = $this->data_generator->create_program();

        // Reload courses. Otherwise when we compare the courses with the returned courses,
        // we get subtle differences in some values such as cacherev and sortorder.
        // Todo: Investigate whether we can improve the generator to fix this.
        $this->course1 = $DB->get_record('course', array('id' => $this->course1->id));
        $this->course2 = $DB->get_record('course', array('id' => $this->course2->id));
        $this->course3 = $DB->get_record('course', array('id' => $this->course3->id));
        $this->course4 = $DB->get_record('course', array('id' => $this->course4->id));
        $this->course5 = $DB->get_record('course', array('id' => $this->course5->id));
        $this->course6 = $DB->get_record('course', array('id' => $this->course6->id));

        // Create some program data.
        $this->data_generator->add_courseset_program(
            $this->program1->id,
            array($this->course1->id, $this->course2->id)
        );
        $this->data_generator->add_courseset_program(
            $this->program1->id,
            array($this->course3->id, $this->course4->id)
        );

        $this->data_generator->add_courseset_program(
            $this->program2->id,
            array($this->course5->id, $this->course6->id)
        );

        // Assign some users.
        $this->data_generator->assign_to_program($this->program1->id, ASSIGNTYPE_INDIVIDUAL, $this->user1->id);
        $this->data_generator->assign_to_program($this->program1->id, ASSIGNTYPE_INDIVIDUAL, $this->user2->id);
        $this->data_generator->assign_to_program($this->program1->id, ASSIGNTYPE_INDIVIDUAL, $this->user3->id);
        $this->data_generator->assign_to_program($this->program1->id, ASSIGNTYPE_INDIVIDUAL, $this->user4->id);
        $this->data_generator->assign_to_program($this->program1->id, ASSIGNTYPE_INDIVIDUAL, $this->user5->id);
        $this->data_generator->assign_to_program($this->program2->id, ASSIGNTYPE_INDIVIDUAL, $this->user5->id);
        $this->data_generator->assign_to_program($this->program2->id, ASSIGNTYPE_INDIVIDUAL, $this->user6->id);

        // User 2 - Started course 1 pre-assignment.
        $this->data_generator->enrol_user($this->user2->id, $this->course1->id);
        $completion = new completion_completion(array('userid' => $this->user2->id, 'course' => $this->course1->id));
        $completion->mark_inprogress($this->past);

        // User 3 - Started course 2 post-assignment.
        $this->data_generator->enrol_user($this->user3->id, $this->course2->id);
        $completion = new completion_completion(array('userid' => $this->user3->id, 'course' => $this->course2->id));
        $completion->mark_inprogress($this->future);

        // User 4 - Completed courseset 1 and started course 3.
        $this->data_generator->enrol_user($this->user4->id, $this->course1->id);
        $completion = new completion_completion(array('userid' => $this->user4->id, 'course' => $this->course1->id));
        $completion->mark_inprogress($this->future - 10000);
        $completion->mark_complete($this->future - 10000);
        $this->data_generator->enrol_user($this->user4->id, $this->course2->id);
        $completion = new completion_completion(array('userid' => $this->user4->id, 'course' => $this->course2->id));
        $completion->mark_inprogress($this->future - 20000);
        $completion->mark_complete($this->future - 20000);
        $this->data_generator->enrol_user($this->user4->id, $this->course3->id);
        $completion = new completion_completion(array('userid' => $this->user4->id, 'course' => $this->course3->id));
        $completion->mark_inprogress($this->future);

        // User 5 - Completed program 1 and started course 5 from program 2.
        $this->data_generator->enrol_user($this->user5->id, $this->course5->id);
        $completion = new completion_completion(array('userid' => $this->user5->id, 'course' => $this->course5->id));
        $completion->mark_inprogress($this->future);

        $this->data_generator->enrol_user($this->user5->id, $this->course1->id);
        $completion = new completion_completion(array('userid' => $this->user5->id, 'course' => $this->course1->id));
        $completion->mark_inprogress($this->future - 20000);
        $completion->mark_complete($this->future - 20000);
        $this->data_generator->enrol_user($this->user5->id, $this->course2->id);
        $completion = new completion_completion(array('userid' => $this->user5->id, 'course' => $this->course2->id));
        $completion->mark_inprogress($this->future - 30000);
        $completion->mark_complete($this->future - 30000);
        $this->data_generator->enrol_user($this->user5->id, $this->course3->id);
        $completion = new completion_completion(array('userid' => $this->user5->id, 'course' => $this->course3->id));
        $completion->mark_inprogress($this->future - 10000);
        $completion->mark_complete($this->future - 10000);
        $this->data_generator->enrol_user($this->user5->id, $this->course4->id);
        $completion = new completion_completion(array('userid' => $this->user5->id, 'course' => $this->course4->id));
        $completion->mark_inprogress($this->future);
        $completion->mark_complete($this->future);

        // User 6 - Started course 5 post-assignment.
        $this->data_generator->enrol_user($this->user6->id, $this->course5->id);
        $completion = new completion_completion(array('userid' => $this->user6->id, 'course' => $this->course5->id));
        $completion->mark_inprogress($this->future);
    }

    public function reload_course($course) {
        global $DB;
        return $DB->get_record('course', array('id' => $course->id));
    }


    public function test_totara_program_fix_timestarted() {
        global $DB;

        // Check that the instant completion calculated times match expectations.
        $progcomp1 = $DB->get_record('prog_completion', array('userid' => $this->user1->id, 'coursesetid' => 0));
        $this->assertEquals(0, $progcomp1->timestarted);

        $progcomp2 = $DB->get_record('prog_completion', array('userid' => $this->user2->id, 'coursesetid' => 0));
        $this->assertGreaterThanOrEqual($this->now, $progcomp2->timestarted);

        $progcomp3 = $DB->get_record('prog_completion', array('userid' => $this->user3->id, 'coursesetid' => 0));
        $this->assertGreaterThanOrEqual($this->now, $progcomp3->timestarted);

        $progcomp4 = $DB->get_record('prog_completion', array('userid' => $this->user4->id, 'coursesetid' => 0));
        $this->assertGreaterThanOrEqual($this->now, $progcomp4->timestarted);

        $this->assertEquals($DB->count_records('prog_completion', array('userid' => $this->user5->id, 'coursesetid' => 0)), 2);
        $progcomp51 = $DB->get_record('prog_completion', array('userid' => $this->user5->id, 'coursesetid' => 0, 'programid' => $this->program1->id));
        $this->assertGreaterThanOrEqual($this->now, $progcomp51->timestarted);
        $progcomp52 = $DB->get_record('prog_completion', array('userid' => $this->user5->id, 'coursesetid' => 0, 'programid' => $this->program2->id));
        $this->assertGreaterThanOrEqual($this->now, $progcomp52->timestarted);

        $progcomp6 = $DB->get_record('prog_completion', array('userid' => $this->user6->id, 'coursesetid' => 0));
        $this->assertGreaterThanOrEqual($this->now, $progcomp6->timestarted);

        $this->assertEquals(0, $DB->count_records('prog_completion', array('userid' => $this->user7->id, 'coursesetid' => 0)));

        // Reset and attempt to recalculate the timestarted.
        $DB->execute('UPDATE {prog_completion} set timestarted = 0');
        totara_program_fix_timestarted();

        // Check that the calculated times match expectations.
        $progcomp1 = $DB->get_record('prog_completion', array('userid' => $this->user1->id, 'coursesetid' => 0));
        $this->assertEquals(0, $progcomp1->timestarted);

        $sql = 'SELECT MIN(timestarted) FROM {prog_completion} WHERE userid = :uid AND coursesetid > 0';
        $progcomp2 = $DB->get_record('prog_completion', array('userid' => $this->user2->id, 'coursesetid' => 0));
        $this->assertNotEquals($progcomp2->timestarted, $this->past); // Even though we set the course to earlier you cant start before assignment.
        $this->assertEquals($progcomp2->timestarted, $DB->get_field_sql($sql, array('uid' => $this->user2->id)));
        $this->assertGreaterThanOrEqual($progcomp2->timecreated, $progcomp2->timestarted);

        $progcomp3 = $DB->get_record('prog_completion', array('userid' => $this->user3->id, 'coursesetid' => 0));
        $this->assertGreaterThanOrEqual($this->future, $progcomp3->timestarted);

        $progcomp4 = $DB->get_record('prog_completion', array('userid' => $this->user4->id, 'coursesetid' => 0));
        $this->assertGreaterThanOrEqual($this->future - 20000, $progcomp4->timestarted);

        $this->assertEquals($DB->count_records('prog_completion', array('userid' => $this->user5->id, 'coursesetid' => 0)), 2);
        $progcomp51 = $DB->get_record('prog_completion', array('userid' => $this->user5->id, 'coursesetid' => 0, 'programid' => $this->program1->id));
        $this->assertGreaterThanOrEqual($this->future - 30000, $progcomp51->timestarted);
        $progcomp52 = $DB->get_record('prog_completion', array('userid' => $this->user5->id, 'coursesetid' => 0, 'programid' => $this->program2->id));
        $this->assertGreaterThanOrEqual($this->future, $progcomp52->timestarted);

        $progcomp6 = $DB->get_record('prog_completion', array('userid' => $this->user6->id, 'coursesetid' => 0));
        $this->assertGreaterThanOrEqual($this->future, $progcomp6->timestarted);

        $this->assertEquals(0, $DB->count_records('prog_completion', array('userid' => $this->user7->id, 'coursesetid' => 0)));
    }
}
