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
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package totara_program
*/

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/totara/reportbuilder/tests/reportcache_advanced_testcase.php');

/**
 * Tests the clean enrolments plugin task.
 */
class totara_clean_enrolments_plugins_task_testcase extends reportcache_advanced_testcase {

    /**
     * @var totara_program_generator
     */
    protected $generator_program;

    protected $programs, $courses, $users;

    protected function tearDown() {
        $this->generator_program = null;
        $this->programs = null;
        $this->courses = null;
        $this->users = null;
        parent::tearDown();
    }

    /**
     * Prepares the environment prior to each test case.
     */
    public function setUp() {
        // Make each generator more easily accessible.
        $this->generator_program = $this->getDataGenerator()->get_plugin_generator('totara_program');

        $programplugin = enrol_get_plugin('totara_program');
        $programplugin->set_config('unenrolaction', ENROL_EXT_REMOVED_SUSPEND);

        // We have the following initial state.
        //  - 2x programs
        //  - First program with courseset content
        //    - 1x courseset in the program
        //    - 1x course in the courseset
        // - Second program based on competency containing 1 course
        // - 2x users enrolled in the program
        // - 2x users in "in progress"
        $this->programs = array(
            $this->generator_program->create_program(),
            $this->generator_program->create_program());
        $this->courses = array (
            $this->getDataGenerator()->create_course(),
            $this->getDataGenerator()->create_course());

        // Setup program1 with set of courses
        $coursesetdata = array(
            array(
                'type' => CONTENTTYPE_MULTICOURSE,
                'nextsetoperator' => NEXTSETOPERATOR_THEN,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_STD,
                'courses' => array($this->courses[0])
            )
        );
        $this->getDataGenerator()->create_coursesets_in_program($this->programs[0], $coursesetdata);

        // Setup program2 based on competency
        /** @var totara_hierarchy_generator $hierarchygenerator */
        $hierarchygenerator = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');
        $competencyframework = $hierarchygenerator->create_comp_frame(array());
        $competencydata = array('frameworkid' => $competencyframework->id, 'fullname' => 'Test Competency');
        $competency = $hierarchygenerator->create_comp($competencydata);

        // Assigned Completions for course to this competency.
        $evidenceid = $hierarchygenerator->assign_linked_course_to_competency($competency, $this->courses[1]);

        $coursesetdata = array(
            array(
                'type' => CONTENTTYPE_COMPETENCY,
                'nextsetoperator' => NEXTSETOPERATOR_THEN,
                'completiontype' => COMPLETIONTYPE_ALL,
                'certifpath' => CERTIFPATH_STD,
                'competency' => $competency
            )
        );
        $this->getDataGenerator()->create_coursesets_in_program($this->programs[1], $coursesetdata);

        $this->users = array();

        for ($i = 0; $i < 2; $i++) {
            $this->users[$i] = $this->getDataGenerator()->create_user();
            $userids[] = $this->users[$i]->id;
        }

        // Assign users to the programs
        foreach ($this->programs as $program) {
            $this->generator_program->assign_program($program->id, $userids);
        }

        // Enrol the users in the courses via the programs and verify that it was done
        foreach ($this->users as $user) {
            foreach ($this->courses as $course) {
                $this->getDataGenerator()->enrol_user($user->id, $course->id, null, 'totara_program');
            }
        }
    }

    /**
     * Test active user still enrolled in the programs
     * No changes expected on user_enrolments
     */
    public function test_active_user_in_program() {
        global $CFG;

        $this->resetAfterTest();

        $CFG->debug = 0;

        foreach ($this->users as $user) {
            foreach ($this->programs as $program) {
                $this->verify_prog_assignment($program->id, $user->id);
            }

            foreach ($this->courses as $course) {
                $this->verify_user_enrolment_status($course->id, $user->id, true, ENROL_USER_ACTIVE);
            }
        }

        // Leave both users in the program, run clean_enrolment_plugins_task and ensure nothing changes
        $task = new \totara_program\task\clean_enrolment_plugins_task();
        $task->execute();

        foreach ($this->users as $user) {
            foreach ($this->programs as $program) {
                $this->verify_prog_assignment($program->id, $user->id, true);
            }

            foreach ($this->courses as $course) {
                $this->verify_user_enrolment_status($course->id, $user->id, true, ENROL_USER_ACTIVE);
            }
        }
    }

    /**
     * Test suspended user enrolled in the program
     */
    public function test_suspended_user_in_program() {
        global $CFG;

        $this->resetAfterTest();

        $CFG->debug = 0;

        // Change first user's user_enrolment status to suspended in both courses
        foreach ($this->courses as $course) {
            $this->update_user_enrolment_status($course->id, $this->users[0]->id, ENROL_USER_SUSPENDED);
        }

        // user1 is assigned to both programs, suspened in both courses
        // user2 is assigned to both programs, active in both courses
        foreach ($this->programs as $program) {
            $this->verify_prog_assignment($program->id, $this->users[0]->id, true);
            $this->verify_prog_assignment($program->id, $this->users[1]->id, true);
        }
        foreach ($this->courses as $course) {
            $this->verify_user_enrolment_status($course->id, $this->users[0]->id, true, ENROL_USER_SUSPENDED);
            $this->verify_user_enrolment_status($course->id, $this->users[1]->id, true, ENROL_USER_ACTIVE);
        }

        $task = new \totara_program\task\clean_enrolment_plugins_task();
        $task->execute();

        // Suspended user enrolment should be reverted to active in cron task
        foreach ($this->programs as $program) {
            $this->verify_prog_assignment($program->id, $this->users[0]->id, true);
            $this->verify_prog_assignment($program->id, $this->users[1]->id, true);
        }
        foreach ($this->courses as $course) {
            $this->verify_user_enrolment_status($course->id, $this->users[0]->id, true, ENROL_USER_ACTIVE);
            $this->verify_user_enrolment_status($course->id, $this->users[1]->id, true, ENROL_USER_ACTIVE);
        }
    }


    /**
     * Active user not in program anymore
     */
    public function test_active_user_not_in_program() {
        global $CFG;

        $this->resetAfterTest();

        $CFG->debug = 0;

        // Remove first user from both program assignments
        foreach ($this->programs as $program) {
            $this->remove_prog_assignment($program->id, $this->users[0]->id);
        }

        foreach ($this->programs as $program) {
            $this->verify_prog_assignment($program->id, $this->users[0]->id, false);
            $this->verify_prog_assignment($program->id, $this->users[1]->id, true);
        }
        foreach ($this->courses as $course) {
            $this->verify_user_enrolment_status($course->id, $this->users[0]->id, true, ENROL_USER_ACTIVE);
            $this->verify_user_enrolment_status($course->id, $this->users[1]->id, true, ENROL_USER_ACTIVE);
        }

        $task = new \totara_program\task\clean_enrolment_plugins_task();
        $task->execute();

        // First user should be suspended from course
        foreach ($this->programs as $program) {
            $this->verify_prog_assignment($program->id, $this->users[0]->id, false);
            $this->verify_prog_assignment($program->id, $this->users[1]->id, true);
        }
        foreach ($this->courses as $course) {
            $this->verify_user_enrolment_status($course->id, $this->users[0]->id, true, ENROL_USER_SUSPENDED);
            $this->verify_user_enrolment_status($course->id, $this->users[1]->id, true, ENROL_USER_ACTIVE);
        }
    }

    /**
     * Suspended user not in program anymore
     */
    public function test_suspended_user_not_in_program() {
        global $CFG;

        $this->resetAfterTest();

        $CFG->debug = 0;

        // Change first user's user_enrolment status to suspended in both courses
        foreach ($this->courses as $course) {
            $this->update_user_enrolment_status($course->id, $this->users[0]->id, ENROL_USER_SUSPENDED);
        }

        // Remove first user from program assignment
        foreach ($this->programs as $program) {
            $this->remove_prog_assignment($program->id, $this->users[0]->id);
        }

        foreach ($this->programs as $program) {
            $this->verify_prog_assignment($program->id, $this->users[0]->id, false);
            $this->verify_prog_assignment($program->id, $this->users[1]->id, true);
        }
        foreach ($this->courses as $course) {
            $this->verify_user_enrolment_status($course->id, $this->users[0]->id, true, ENROL_USER_SUSPENDED);
            $this->verify_user_enrolment_status($course->id, $this->users[1]->id, true, ENROL_USER_ACTIVE);
        }

        $task = new \totara_program\task\clean_enrolment_plugins_task();
        $task->execute();

        // First user should be suspended from courses
        foreach ($this->programs as $program) {
            $this->verify_prog_assignment($program->id, $this->users[0]->id, false);
            $this->verify_prog_assignment($program->id, $this->users[1]->id, true);
        }
        foreach ($this->courses as $course) {
            $this->verify_user_enrolment_status($course->id, $this->users[0]->id, true, ENROL_USER_SUSPENDED);
            $this->verify_user_enrolment_status($course->id, $this->users[1]->id, true, ENROL_USER_ACTIVE);
        }
    }

    /**
     * Remove course from program
     */
    public function test_course_removed_from_program() {
        global $CFG;

        $this->resetAfterTest();

        $CFG->debug = 0;

        foreach ($this->courses as $course) {
            $this->verify_totara_program_enrol($course->id, true);
        }

        // Remove the coursesets and courses from the program
        foreach ($this->programs as $program) {
            $this->remove_prog_courseset($program->id);
        }

        // Cron not yet run
        foreach ($this->programs as $program) {
            $this->verify_prog_assignment($program->id, $this->users[0]->id, true);
            $this->verify_prog_assignment($program->id, $this->users[1]->id, true);
        }
        foreach ($this->courses as $course) {
            $this->verify_totara_program_enrol($course->id, true);
            $this->verify_user_enrolment_status($course->id, $this->users[0]->id, true, ENROL_USER_ACTIVE);
            $this->verify_user_enrolment_status($course->id, $this->users[1]->id, true, ENROL_USER_ACTIVE);
        }

        // Run cron
        $task = new \totara_program\task\clean_enrolment_plugins_task();
        $task->execute();

        // Users still in the program, but not in the course
        foreach ($this->programs as $program) {
            $this->verify_prog_assignment($program->id, $this->users[0]->id, true);
            $this->verify_prog_assignment($program->id, $this->users[1]->id, true);
        }
        foreach ($this->courses as $course) {
            $this->verify_totara_program_enrol($course->id, false);
            $this->verify_user_enrolment_status($course->id, $this->users[0]->id, false);
            $this->verify_user_enrolment_status($course->id, $this->users[1]->id, false);
        }
    }

    /**
     * Verify the existence (or not) of the user assignment to the program
     */
    private function verify_prog_assignment($programid, $userid, $expected=true) {
        global $DB;

        $recordexists = $DB->record_exists('prog_user_assignment',
            array('programid' => $programid, 'userid' => $userid));
        $this->assertEquals($expected, $recordexists);
    }

    /**
     * Remove program assignment
     */
    private function remove_prog_assignment($programid, $userid) {
        global $DB;

        $sql = "DELETE FROM {prog_user_assignment}
                 WHERE programid = :programid
                   AND userid = :userid";
        $params = array ('programid' => $programid, 'userid' => $userid);

        $this->assertTrue($DB->execute($sql, $params));
    }

    /**
     * Remove courseset from the program
     */
    private function remove_prog_courseset($programid) {
        global $DB;

        $sql = "DELETE FROM {prog_courseset_course}
                WHERE coursesetid IN (
                    SELECT id
                      FROM {prog_courseset} pc
                     WHERE pc.programid = :programid)";
        $params = array ('programid' => $programid);

        $this->assertTrue($DB->execute($sql, $params));

        $sql = "DELETE FROM {prog_courseset}
                 WHERE programid = :programid";
        $params = array ('programid' => $programid);

        $this->assertTrue($DB->execute($sql, $params));
    }

    /**
     * Verify the existence (or not) of the user's enrolment in the course and also
     * verify the current enrolment status
     */
    private function verify_user_enrolment_status($courseid, $userid, $expected=true, $status=ENROL_USER_ACTIVE) {
        global $DB;

        $from = "FROM {user_enrolments} ue
                  JOIN {enrol} e on ue.enrolid = e.id
                 WHERE ue.userid = :userid
                   AND e.enrol = 'totara_program'
                   AND e.courseid = :courseid";
        $params = array ('userid' => $userid, 'courseid' => $courseid);

        if (!$expected) {
            $sql = "SELECT count(*) " . $from;
            $this->assertEquals(0, $DB->count_records_sql($sql, $params));
        } else {
            $sql = "SELECT ue.* " . $from;
            $row = $DB->get_record_sql($sql, $params, IGNORE_MISSING);

            $this->assertEquals($status, $row->status);
        }
    }

    /**
     * Verify the existence (or not) of totara_program enrol entries
     */
    private function verify_totara_program_enrol($courseid, $expected=true, $status=ENROL_INSTANCE_ENABLED) {
        global $DB;

        $params = array ('courseid' => $courseid, 'enrol' => 'totara_program', 'status' => $status);

        $this->assertEquals(($expected ? 1 : 0), $DB->count_records("enrol", $params));
    }

    /**
     * Update user enrolment status
     */
    private function update_user_enrolment_status($courseid, $userid, $status) {
        global $DB;

        $sql = "UPDATE {user_enrolments}
                   SET status = :status
                 WHERE userid = :userid
                   AND enrolid IN (
                       SELECT e.id
                         FROM {enrol} e
                        WHERE e.enrol = 'totara_program'
                          AND e.courseid = :courseid)";
        $params = array ('status' => $status, 'userid' => $userid, 'courseid' => $courseid);

        $this->assertTrue($DB->execute($sql, $params));
    }
}
