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
 * @author Nathan Lewis <nathan.lewis@totaralms.com>
 * @package totara_program
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/totara/program/lib.php');
require_once($CFG->dirroot . '/totara/program/program.class.php');
require_once($CFG->dirroot . '/totara/reportbuilder/tests/reportcache_advanced_testcase.php');

/**
 * Test update_learner_assignments function.
 *
 * To test, run this from the command line from the $CFG->dirroot
 * vendor/bin/phpunit totara_program_update_learner_assignments_testcase totara/program/tests/update_learner_assignments_test.php
 *
 */
class totara_program_update_learner_assignments_testcase extends reportcache_advanced_testcase {

    /** @var totara_program_generator */
    private $programgenerator = null;
    private $users = array();
    private $audiences = array();
    private $audienceusers = array();

    /** @var program */
    private $program = null;

    private $controlprogram = null;
    private $controlprogassignments = null;
    private $controlproguserassignments = null;
    private $controlprogcompletions = null;

    private $updateassignmentsdateformat;

    /**
     * Setup.
     *
     * Set up the program generator.
     * Set up the program to be used.
     * Set up another program that will be check after each test to ensure nothing leaked.
     */
    public function setUp() {
        global $DB;
        parent::setup();

        $this->updateassignmentsdateformat = get_string('datepickerlongyearparseformat', 'totara_core');

        $this->resetAfterTest(true);

        $this->programgenerator = $this->getDataGenerator()->get_plugin_generator('totara_program');
        $this->program = $this->programgenerator->create_program(array('fullname' => 'program1'));
        $this->controlprogram = $this->programgenerator->create_program(array('fullname' => 'controlprogram'));

        $this->audiences[0] = $this->getDataGenerator()->create_cohort(); // DO NOT MODIFY DURING TESTING, USED IN CONTROL!
        $this->audiences[1] = $this->getDataGenerator()->create_cohort();

        // Create test users.
        $this->audienceusers[0] = array();
        $this->audienceusers[1] = array();
        for ($i = 0; $i < 20; $i++) {
            $this->users[$i] = $this->getDataGenerator()->create_user(array('firstname' => 'user' . $i));
            // Assign half of them (even $i) to audience[0], half of them (odd $i) to audience[1].
            cohort_add_member($this->audiences[$i % 2]->id, $this->users[$i]->id);
            $this->audienceusers[$i % 2][$i] = $this->users[$i];
        }

        // Add ten user assignments, specifically users 0 to 9, because they will be used in tests.
        $controlusers = array();
        for ($i = 0; $i < 10; $i++) {
            $controlusers[] = $this->users[$i];
        }
        $this->set_individual_assignments($this->controlprogram, $controlusers);

        // Add audience 0 with odd numbered users.
        $this->set_audience_assignment($this->controlprogram, $this->audiences[0]);

        // Initialise everything.
        $this->controlprogram->update_learner_assignments(true);

        // Check that the correct number of records is correct.
        $this->assertEquals(11, $DB->count_records('prog_assignment', array('programid' => $this->controlprogram->id)));
        $this->assertEquals(20, $DB->count_records('prog_user_assignment', array('programid' => $this->controlprogram->id)));
        $this->assertEquals(15, $DB->count_records('prog_completion', array('programid' => $this->controlprogram->id)));

        // Store whole records.
        $this->controlprogassignments = $DB->get_records('prog_assignment',
            array('programid' => $this->controlprogram->id));
        $this->controlproguserassignments = $DB->get_records('prog_user_assignment',
            array('programid' => $this->controlprogram->id));
        $this->controlprogcompletions = $DB->get_records('prog_completion',
            array('programid' => $this->controlprogram->id));
    }

    protected function tearDown() {
        // No asserts here!
        $this->programgenerator = null;
        $this->users = null;
        $this->audiences = null;
        $this->audienceusers = null;
        $this->program = null;
        $this->controlprogram = null;
        $this->controlprogassignments = null;
        $this->controlproguserassignments = null;
        $this->controlprogcompletions = null;
        $this->updateassignmentsdateformat = null;
        parent::tearDown();
    }

    /**
     * Checks that the control program has not been changed in any way.
     */
    private function check_control_program() {
        global $DB;

        // Compare whole records at once.
        $currentcontrolprogassignments = $DB->get_records('prog_assignment',
            array('programid' => $this->controlprogram->id));
        $this->assertEquals($currentcontrolprogassignments, $this->controlprogassignments);
        $currentcontrolproguserassignments = $DB->get_records('prog_user_assignment',
            array('programid' => $this->controlprogram->id));
        $this->assertEquals($currentcontrolproguserassignments, $this->controlproguserassignments);
        $currentcontrolprogcompletions = $DB->get_records('prog_completion',
            array('programid' => $this->controlprogram->id));
        $this->assertEquals($currentcontrolprogcompletions, $this->controlprogcompletions);
    }

    /**
     * Set individual assignments in a program.
     */
    private function set_individual_assignments(program $program, $users, $completiontime = -1, $completionevent = 0, $completioninstance = 0) {
        $data = new stdClass();
        $data->id = $program->id;
        $data->item = array(ASSIGNTYPE_INDIVIDUAL => array());
        $data->completiontime = array(ASSIGNTYPE_INDIVIDUAL => array());
        $data->completionevent = array(ASSIGNTYPE_INDIVIDUAL => array());
        $data->completioninstance = array(ASSIGNTYPE_INDIVIDUAL => array());

        foreach ($users as $user) {
            $data->item[ASSIGNTYPE_INDIVIDUAL][$user->id] = 1;
            $data->completiontime[ASSIGNTYPE_INDIVIDUAL][$user->id] = $completiontime;
            $data->completionevent[ASSIGNTYPE_INDIVIDUAL][$user->id] = $completionevent;
            $data->completioninstance[ASSIGNTYPE_INDIVIDUAL][$user->id] = $completioninstance;
        }

        $category = new individuals_category();
        $category->update_assignments($data);

        // Reset the assignments. We use get_assignments()->reset() because we don't want to load the assignments yet.
        $program->get_assignments()->reset();
    }

    /**
     * Set audience assignment to a program.
     */
    private function set_audience_assignment(program $program, $audience, $completiontime = -1, $completionevent = 0) {
        $data = new stdClass();
        $data->id = $program->id;
        $data->item = array(ASSIGNTYPE_COHORT => array());
        $data->completiontime = array(ASSIGNTYPE_COHORT => array());
        $data->completionevent = array(ASSIGNTYPE_COHORT => array());
        $data->completioninstance = array(ASSIGNTYPE_COHORT => array());
        // The lines below could be moved into the above arrays.
        $data->item[ASSIGNTYPE_COHORT][$audience->id] = 1;
        $data->completiontime[ASSIGNTYPE_COHORT][$audience->id] = $completiontime;
        $data->completionevent[ASSIGNTYPE_COHORT][$audience->id] = $completionevent;
        $data->completioninstance[ASSIGNTYPE_COHORT][$audience->id] = 0;

        $category = new cohorts_category();
        $category->update_assignments($data);

        // Reset the assignments. We use get_assignments()->reset() because we don't want to load the assignments yet.
        $program->get_assignments()->reset();
    }

    /**
     * Assign an individual to a program.
     *
     * Check that the program user assignment and program completion records are created correctly.
     */
    public function test_assigning_individuals() {
        global $DB;

        $timebefore = time();

        // Create users and courses.
        $user0 = $this->users[0];
        $user1 = $this->users[1];
        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();

        // Assign courses to program. Reload program after changing it.
        $this->getDataGenerator()->add_courseset_program($this->program->id, array($course1->id, $course2->id));
        $this->program = new program($this->program->id);

        // Add individual assignments.
        $this->set_individual_assignments($this->program, array($user0, $user1));

        // Check that records exist.
        $this->assertEquals(2, $DB->count_records('prog_assignment',
            array('programid' => $this->program->id)));
        $this->assertEquals(0, $DB->count_records('prog_user_assignment',
            array('programid' => $this->program->id)));
        $this->assertEquals(0, $DB->count_records('prog_completion',
            array('programid' => $this->program->id)));

        // Apply assignment changes.
        $this->program->update_learner_assignments(true);

        $timeafter = time();

        // Check that records exist.
        $this->assertEquals(2, $DB->count_records('prog_assignment',
            array('programid' => $this->program->id)));
        $this->assertEquals(2, $DB->count_records('prog_user_assignment',
            array('programid' => $this->program->id)));
        $this->assertEquals(2, $DB->count_records('prog_completion', // Program completion records.
            array('programid' => $this->program->id, 'coursesetid' => 0)));
        $this->assertEquals(4, $DB->count_records('prog_completion', // Course set completion records were also created.
            array('programid' => $this->program->id)));

        // Check prog_assignment records.
        $progassignment0 = $DB->get_record('prog_assignment',
            array('programid' => $this->program->id, 'assignmenttype' => 5, 'assignmenttypeid' => $user0->id));
        $this->assertNotEmpty($progassignment0);

        $progassignment1 = $DB->get_record('prog_assignment',
            array('programid' => $this->program->id, 'assignmenttype' => 5, 'assignmenttypeid' => $user1->id));
        $this->assertNotEmpty($progassignment1);

        $this->assertEquals(0, $progassignment0->includechildren);
        $this->assertEquals(0, $progassignment1->includechildren);
        $this->assertEquals(-1, $progassignment0->completiontime);
        $this->assertEquals(-1, $progassignment1->completiontime);
        $this->assertEquals(0, $progassignment0->completionevent);
        $this->assertEquals(0, $progassignment1->completionevent);
        $this->assertEquals(0, $progassignment0->completioninstance);
        $this->assertEquals(0, $progassignment1->completioninstance);

        // Check prog_user_assignment records.
        $proguserassignment0 = $DB->get_record('prog_user_assignment',
            array('programid' => $this->program->id, 'userid' => $user0->id));
        $this->assertNotEmpty($proguserassignment0);

        $proguserassignment1 = $DB->get_record('prog_user_assignment',
            array('programid' => $this->program->id, 'userid' => $user1->id));
        $this->assertNotEmpty($proguserassignment1);

        $this->assertEquals($progassignment0->id, $proguserassignment0->assignmentid);
        $this->assertEquals($progassignment1->id, $proguserassignment1->assignmentid);
        $this->assertGreaterThanOrEqual($timebefore, $proguserassignment0->timeassigned);
        $this->assertGreaterThanOrEqual($timebefore, $proguserassignment1->timeassigned);
        $this->assertLessThanOrEqual($timeafter, $proguserassignment0->timeassigned);
        $this->assertLessThanOrEqual($timeafter, $proguserassignment1->timeassigned);
        $this->assertEquals(0, $proguserassignment0->exceptionstatus);
        $this->assertEquals(0, $proguserassignment1->exceptionstatus);

        // Check prog_completion records.
        $progcompletion0 = $DB->get_record('prog_completion',
            array('programid' => $this->program->id, 'userid' => $user0->id, 'coursesetid' => 0));
        $this->assertNotEmpty($progcompletion0);

        $progcompletion1 = $DB->get_record('prog_completion',
            array('programid' => $this->program->id, 'userid' => $user1->id, 'coursesetid' => 0));
        $this->assertNotEmpty($progcompletion1);

        $this->assertEquals(0, $progcompletion0->coursesetid);
        $this->assertEquals(0, $progcompletion1->coursesetid);
        $this->assertEquals(STATUS_PROGRAM_INCOMPLETE, $progcompletion0->status);
        $this->assertEquals(STATUS_PROGRAM_INCOMPLETE, $progcompletion1->status);
        $this->assertGreaterThanOrEqual($timebefore, $progcompletion0->timecreated);
        $this->assertGreaterThanOrEqual($timebefore, $progcompletion1->timecreated);
        $this->assertLessThanOrEqual($timeafter, $progcompletion0->timecreated);
        $this->assertLessThanOrEqual($timeafter, $progcompletion1->timecreated);
        $this->assertEquals(0, $progcompletion0->timestarted);
        $this->assertEquals(0, $progcompletion1->timestarted);
        $this->assertEquals(-1, $progcompletion0->timedue);
        $this->assertEquals(-1, $progcompletion1->timedue);
        $this->assertEquals(0, $progcompletion0->timecompleted);
        $this->assertEquals(0, $progcompletion1->timecompleted);

        // Check prog_completion course set non-0 records.
        $sql = "SELECT pc.*
                  FROM {prog_completion} pc
                 WHERE pc.programid = :programid
                   AND pc.userid = :userid
                   AND coursesetid != 0";
        $progcoursesetcompletion0 = $DB->get_record_sql($sql, array('programid' => $this->program->id, 'userid' => $user0->id));
        $this->assertNotEmpty($progcoursesetcompletion0);
        $progcoursesetcompletion1 = $DB->get_record_sql($sql, array('programid' => $this->program->id, 'userid' => $user1->id));
        $this->assertNotEmpty($progcoursesetcompletion1);

        $this->assertNotEquals(0, $progcoursesetcompletion0->coursesetid);
        $this->assertNotEquals(0, $progcoursesetcompletion1->coursesetid);
        $this->assertEquals(STATUS_COURSESET_INCOMPLETE, $progcoursesetcompletion0->status);
        $this->assertEquals(STATUS_COURSESET_INCOMPLETE, $progcoursesetcompletion1->status);
        $this->assertEquals(0, $progcoursesetcompletion0->timecompleted);
        $this->assertEquals(0, $progcoursesetcompletion1->timecompleted);

        $this->check_control_program();
    }

    /**
     * Assign an individual to a program and then later remove them.
     *
     * Check that their records are tidied up correctly.
     */
    public function test_unassigning_individuals() {
        global $DB;

        $timebefore = time();

        // Add individual assignments.
        $user0 = $this->users[0];
        $user1 = $this->users[1];
        $user2 = $this->users[2];
        $user3 = $this->users[3];
        $this->set_individual_assignments($this->program, array($user0, $user1, $user2, $user3));

        // Apply assignment changes.
        $this->program->update_learner_assignments(true);

        $timeafter = time();

        // Check that records exist.
        $this->assertEquals(4, $DB->count_records('prog_assignment',
            array('programid' => $this->program->id)));
        $this->assertEquals(4, $DB->count_records('prog_user_assignment',
            array('programid' => $this->program->id)));
        $this->assertEquals(4, $DB->count_records('prog_completion',
            array('programid' => $this->program->id)));

        // Remove individual assignments (by replacing existing assignments with just the ones we want to not delete).
        $this->set_individual_assignments($this->program, array($user0, $user1));

        // Apply assignment changes.
        $this->program->update_learner_assignments(true);

        // Check that records exist.
        $this->assertEquals(2, $DB->count_records('prog_assignment',
            array('programid' => $this->program->id)));
        $this->assertEquals(2, $DB->count_records('prog_user_assignment',
            array('programid' => $this->program->id)));
        $this->assertEquals(2, $DB->count_records('prog_completion',
            array('programid' => $this->program->id)));

        // Check prog_assignment records.
        $progassignment0 = $DB->get_record('prog_assignment',
            array('programid' => $this->program->id, 'assignmenttype' => ASSIGNTYPE_INDIVIDUAL, 'assignmenttypeid' => $user0->id));
        $this->assertNotEmpty($progassignment0);

        $progassignment1 = $DB->get_record('prog_assignment',
            array('programid' => $this->program->id, 'assignmenttype' => ASSIGNTYPE_INDIVIDUAL, 'assignmenttypeid' => $user1->id));
        $this->assertNotEmpty($progassignment1);

        $this->assertEquals(0, $progassignment0->includechildren);
        $this->assertEquals(0, $progassignment1->includechildren);
        $this->assertEquals(-1, $progassignment0->completiontime);
        $this->assertEquals(-1, $progassignment1->completiontime);
        $this->assertEquals(0, $progassignment0->completionevent);
        $this->assertEquals(0, $progassignment1->completionevent);
        $this->assertEquals(0, $progassignment0->completioninstance);
        $this->assertEquals(0, $progassignment1->completioninstance);

        // Check prog_user_assignment records.
        $proguserassignment0 = $DB->get_record('prog_user_assignment',
            array('programid' => $this->program->id, 'userid' => $user0->id));
        $this->assertNotEmpty($proguserassignment0);

        $proguserassignment1 = $DB->get_record('prog_user_assignment',
            array('programid' => $this->program->id, 'userid' => $user1->id));
        $this->assertNotEmpty($proguserassignment1);

        $this->assertEquals($progassignment0->id, $proguserassignment0->assignmentid);
        $this->assertEquals($progassignment1->id, $proguserassignment1->assignmentid);
        $this->assertGreaterThanOrEqual($timebefore, $proguserassignment0->timeassigned);
        $this->assertGreaterThanOrEqual($timebefore, $proguserassignment1->timeassigned);
        $this->assertLessThanOrEqual($timeafter, $proguserassignment0->timeassigned);
        $this->assertLessThanOrEqual($timeafter, $proguserassignment1->timeassigned);
        $this->assertEquals(0, $proguserassignment0->exceptionstatus);
        $this->assertEquals(0, $proguserassignment1->exceptionstatus);

        // Check prog_completion records.
        $progcompletion0 = $DB->get_record('prog_completion',
            array('programid' => $this->program->id, 'userid' => $user0->id));
        $this->assertNotEmpty($progcompletion0);

        $progcompletion1 = $DB->get_record('prog_completion',
            array('programid' => $this->program->id, 'userid' => $user1->id));
        $this->assertNotEmpty($progcompletion1);

        $this->assertEquals(0, $progcompletion0->coursesetid);
        $this->assertEquals(0, $progcompletion1->coursesetid);
        $this->assertEquals(0, $progcompletion0->status);
        $this->assertEquals(0, $progcompletion1->status);
        $this->assertGreaterThanOrEqual($timebefore, $progcompletion0->timecreated);
        $this->assertGreaterThanOrEqual($timebefore, $progcompletion1->timecreated);
        $this->assertLessThanOrEqual($timeafter, $progcompletion0->timecreated);
        $this->assertLessThanOrEqual($timeafter, $progcompletion1->timecreated);
        $this->assertEquals(0, $progcompletion0->timestarted);
        $this->assertEquals(0, $progcompletion1->timestarted);
        $this->assertEquals(-1, $progcompletion0->timedue);
        $this->assertEquals(-1, $progcompletion1->timedue);
        $this->assertEquals(0, $progcompletion0->timecompleted);
        $this->assertEquals(0, $progcompletion1->timecompleted);

        $this->check_control_program();
    }

    /**
     * Assign an audience to a program.
     *
     * Check that audience assignment works.
     */
    public function test_assigning_audience() {
        global $DB;

        $timebefore = time();

        // Add audience assignment.
        $audience = $this->audiences[0];
        $audienceusers = $this->audienceusers[0];
        $this->set_audience_assignment($this->program, $audience);

        // Check that records exist.
        $this->assertEquals(1, $DB->count_records('prog_assignment',
            array('programid' => $this->program->id)));
        $this->assertEquals(0, $DB->count_records('prog_user_assignment',
            array('programid' => $this->program->id)));
        $this->assertEquals(0, $DB->count_records('prog_completion',
            array('programid' => $this->program->id)));

        // Apply assignment changes.
        $this->program->update_learner_assignments(true);

        $timeafter = time();

        // Check that records exist.
        $this->assertEquals(1, $DB->count_records('prog_assignment',
            array('programid' => $this->program->id)));
        $this->assertEquals(10, $DB->count_records('prog_user_assignment',
            array('programid' => $this->program->id)));
        $this->assertEquals(10, $DB->count_records('prog_completion',
            array('programid' => $this->program->id)));

        // Check prog_assignment record.
        $progassignment = $DB->get_record('prog_assignment',
            array('programid' => $this->program->id, 'assignmenttype' => ASSIGNTYPE_COHORT, 'assignmenttypeid' => $audience->id));
        $this->assertNotEmpty($progassignment);

        $this->assertEquals(0, $progassignment->includechildren);
        $this->assertEquals(-1, $progassignment->completiontime);
        $this->assertEquals(0, $progassignment->completionevent);
        $this->assertEquals(0, $progassignment->completioninstance);

        // All audience members should be assigned.
        foreach ($audienceusers as $user) {
            // Check prog_user_assignment records.
            $proguserassignment = $DB->get_record('prog_user_assignment',
                array('programid' => $this->program->id, 'userid' => $user->id));
            $this->assertNotEmpty($proguserassignment);

            $this->assertEquals($progassignment->id, $proguserassignment->assignmentid);
            $this->assertGreaterThanOrEqual($timebefore, $proguserassignment->timeassigned);
            $this->assertLessThanOrEqual($timeafter, $proguserassignment->timeassigned);
            $this->assertEquals(0, $proguserassignment->exceptionstatus);

            // Check prog_completion records.
            $progcompletion = $DB->get_record('prog_completion',
                array('programid' => $this->program->id, 'userid' => $user->id));
            $this->assertNotEmpty($progcompletion);

            $this->assertEquals(0, $progcompletion->coursesetid);
            $this->assertEquals(0, $progcompletion->status);
            $this->assertGreaterThanOrEqual($timebefore, $progcompletion->timecreated);
            $this->assertLessThanOrEqual($timeafter, $progcompletion->timecreated);
            $this->assertEquals(0, $progcompletion->timestarted);
            $this->assertEquals(-1, $progcompletion->timedue);
            $this->assertEquals(0, $progcompletion->timecompleted);
        }

        $this->check_control_program();
    }

    /**
     * Assign an audience to a program and later change the membership (in dynamic audiences by changing the criteria or changing
     * the properties of the user, or static audiences by just adding and removing, they should all be equivalent).
     *
     * Check that any new audience member is added to the program
     * Check that any removed audience member is removed from the program.
     */
    public function test_changing_audience_membership() {
        global $DB;

        $timebefore = time();

        // Add audience assignment.
        $audience = $this->audiences[1];
        $audienceusers = $this->audienceusers[1];
        $this->set_audience_assignment($this->program, $audience);

        // Apply assignment changes.
        $this->program->update_learner_assignments(true);

        // Check that records exist.
        $this->assertEquals(1, $DB->count_records('prog_assignment',
            array('programid' => $this->program->id)));
        $this->assertEquals(10, $DB->count_records('prog_user_assignment',
            array('programid' => $this->program->id)));
        $this->assertEquals(10, $DB->count_records('prog_completion',
            array('programid' => $this->program->id)));

        // Add three users and remove one.
        cohort_add_member($audience->id, $this->users[0]->id);
        $audienceusers[0] = $this->users[0];
        cohort_add_member($audience->id, $this->users[2]->id);
        $audienceusers[2] = $this->users[2];
        cohort_add_member($audience->id, $this->users[4]->id);
        $audienceusers[4] = $this->users[4];
        cohort_remove_member($audience->id, $this->users[1]->id);
        unset($audienceusers[1]);

        // Apply assignment changes.
        $this->program->update_learner_assignments(true);

        $timeafter = time();

        // Check that records exist.
        $this->assertEquals(1, $DB->count_records('prog_assignment',
            array('programid' => $this->program->id)));
        $this->assertEquals(12, $DB->count_records('prog_user_assignment',
            array('programid' => $this->program->id)));
        $this->assertEquals(12, $DB->count_records('prog_completion',
            array('programid' => $this->program->id)));

        // Check prog_assignment record.
        $progassignment = $DB->get_record('prog_assignment',
            array('programid' => $this->program->id, 'assignmenttype' => ASSIGNTYPE_COHORT, 'assignmenttypeid' => $audience->id));
        $this->assertNotEmpty($progassignment);

        $this->assertEquals(0, $progassignment->includechildren);
        $this->assertEquals(-1, $progassignment->completiontime);
        $this->assertEquals(0, $progassignment->completionevent);
        $this->assertEquals(0, $progassignment->completioninstance);

        // All audience members should be assigned.
        foreach ($audienceusers as $user) {
            // Check prog_user_assignment records.
            $proguserassignment = $DB->get_record('prog_user_assignment',
                array('programid' => $this->program->id, 'userid' => $user->id));
            $this->assertNotEmpty($proguserassignment);

            $this->assertEquals($progassignment->id, $proguserassignment->assignmentid);
            $this->assertGreaterThanOrEqual($timebefore, $proguserassignment->timeassigned);
            $this->assertLessThanOrEqual($timeafter, $proguserassignment->timeassigned);
            $this->assertEquals(0, $proguserassignment->exceptionstatus);

            // Check prog_completion records.
            $progcompletion = $DB->get_record('prog_completion',
                array('programid' => $this->program->id, 'userid' => $user->id));
            $this->assertNotEmpty($progcompletion);

            $this->assertEquals(0, $progcompletion->coursesetid);
            $this->assertEquals(0, $progcompletion->status);
            $this->assertGreaterThanOrEqual($timebefore, $progcompletion->timecreated);
            $this->assertLessThanOrEqual($timeafter, $progcompletion->timecreated);
            $this->assertEquals(0, $progcompletion->timestarted);
            $this->assertEquals(-1, $progcompletion->timedue);
            $this->assertEquals(0, $progcompletion->timecompleted);
        }

        $this->check_control_program();
    }

    /**
     * Assign a large number of users.
     *
     * Check that the deferred assignment functionality is working correctly.
     */
    public function test_deferred_assignments() {
        global $DB;

        $timebefore = time();

        $usercount = PROG_UPDATE_ASSIGNMENTS_DEFER_COUNT + 1;

        // Add audience assignment.
        $audience = $this->getDataGenerator()->create_cohort();
        $audienceusers = array();
        for ($i = 0; $i < $usercount; $i++) {
            $audienceusers[$i] = $this->getDataGenerator()->create_user(array('fullname' => 'audthree' . $i));
            cohort_add_member($audience->id, $audienceusers[$i]->id);
        }
        $this->set_audience_assignment($this->program, $audience);

        // Apply assignment changes, which will be deferred (pass nothing, defaults to false).
        $result = $this->program->update_learner_assignments();

        // Check that the returned value was correct and that the record's assignmentsdeferred flag is correct.
        $this->assertEquals(PROG_UPDATE_ASSIGNMENTS_DEFERRED, $result);
        $this->assertEquals(1, $DB->get_field('prog', 'assignmentsdeferred', array('id' => $this->program->id)));

        // Check that records exist.
        $this->assertEquals(1, $DB->count_records('prog_assignment',
            array('programid' => $this->program->id)));
        $this->assertEquals(0, $DB->count_records('prog_user_assignment',
            array('programid' => $this->program->id)));
        $this->assertEquals(0, $DB->count_records('prog_completion',
            array('programid' => $this->program->id)));

        // Apply assignment changes after deferring (pass true).
        $result = $this->program->update_learner_assignments(true);

        // Check that the returned value was correct and that the record's assignmentsdeferred flag is correct.
        $this->assertEquals(PROG_UPDATE_ASSIGNMENTS_COMPLETE, $result);
        $this->assertEquals(0, $DB->get_field('prog', 'assignmentsdeferred', array('id' => $this->program->id)));

        $timeafter = time();

        // Check that records exist.
        $this->assertEquals(1, $DB->count_records('prog_assignment',
            array('programid' => $this->program->id)));
        $this->assertEquals($usercount, $DB->count_records('prog_user_assignment',
            array('programid' => $this->program->id)));
        $this->assertEquals($usercount, $DB->count_records('prog_completion',
            array('programid' => $this->program->id)));

        // Check prog_assignment record.
        $progassignment = $DB->get_record('prog_assignment',
            array('programid' => $this->program->id, 'assignmenttype' => ASSIGNTYPE_COHORT, 'assignmenttypeid' => $audience->id));
        $this->assertNotEmpty($progassignment);

        $this->assertEquals(0, $progassignment->includechildren);
        $this->assertEquals(-1, $progassignment->completiontime);
        $this->assertEquals(0, $progassignment->completionevent);
        $this->assertEquals(0, $progassignment->completioninstance);

        // All audience members should be assigned.
        foreach ($audienceusers as $user) {
            // Check prog_user_assignment records.
            $proguserassignment = $DB->get_record('prog_user_assignment',
                array('programid' => $this->program->id, 'userid' => $user->id));
            $this->assertNotEmpty($proguserassignment);

            $this->assertEquals($progassignment->id, $proguserassignment->assignmentid);
            $this->assertGreaterThanOrEqual($timebefore, $proguserassignment->timeassigned);
            $this->assertLessThanOrEqual($timeafter, $proguserassignment->timeassigned);
            $this->assertEquals(0, $proguserassignment->exceptionstatus);

            // Check prog_completion records.
            $progcompletion = $DB->get_record('prog_completion',
                array('programid' => $this->program->id, 'userid' => $user->id));
            $this->assertNotEmpty($progcompletion);

            $this->assertEquals(0, $progcompletion->coursesetid);
            $this->assertEquals(0, $progcompletion->status);
            $this->assertGreaterThanOrEqual($timebefore, $progcompletion->timecreated);
            $this->assertLessThanOrEqual($timeafter, $progcompletion->timecreated);
            $this->assertEquals(0, $progcompletion->timestarted);
            $this->assertEquals(-1, $progcompletion->timedue);
            $this->assertEquals(0, $progcompletion->timecompleted);
        }

        $this->check_control_program();
    }

    /**
     * Assign a user via individual and audience.
     *
     * Check that two user assignment records are created.
     * Check that only one program completion record is created.
     */
    public function test_multiple_assignments() {
        global $DB;

        $timebefore = time();

        // Add audience assignment.
        $audience = $this->audiences[0];
        $audienceusers = $this->audienceusers[0];
        $this->set_audience_assignment($this->program, $audience);

        // Add individual assignments.
        $user0 = $this->users[0]; // Is also in audience 0, so adds assignment but not completion.
        $user1 = $this->users[1]; // Is not in audience 0, so adds assignment and not completion.
        $this->set_individual_assignments($this->program, array($user0, $user1));

        // Check that records exist.
        $this->assertEquals(3, $DB->count_records('prog_assignment',
            array('programid' => $this->program->id)));
        $this->assertEquals(0, $DB->count_records('prog_user_assignment',
            array('programid' => $this->program->id)));
        $this->assertEquals(0, $DB->count_records('prog_completion',
            array('programid' => $this->program->id)));

        // Apply assignment changes.
        $this->program->update_learner_assignments(true);

        $timeafter = time();

        // Check that records exist.
        $this->assertEquals(3, $DB->count_records('prog_assignment',
            array('programid' => $this->program->id))); // One audience, two individuals.
        $this->assertEquals(12, $DB->count_records('prog_user_assignment',
            array('programid' => $this->program->id))); // Ten from audience, two from individuals.
        $this->assertEquals(11, $DB->count_records('prog_completion',
            array('programid' => $this->program->id))); // Eleven unique individuals.

        // Check the COHORT prog_assignment record only, too much work to check the individuals.
        $progassignment = $DB->get_record('prog_assignment',
            array('programid' => $this->program->id, 'assignmenttype' => ASSIGNTYPE_COHORT, 'assignmenttypeid' => $audience->id));
        $this->assertNotEmpty($progassignment);

        $this->assertEquals(0, $progassignment->includechildren);
        $this->assertEquals(-1, $progassignment->completiontime);
        $this->assertEquals(0, $progassignment->completionevent);
        $this->assertEquals(0, $progassignment->completioninstance);

        $assignedusers = array_merge($audienceusers, array($user0, $user1));
        foreach ($assignedusers as $key => $user) {
            // Check prog_user_assignment records.
            $proguserassignments = $DB->get_records('prog_user_assignment',
                array('programid' => $this->program->id, 'userid' => $user->id));
            // Skip checking the records, just make sure we've got the right count.
            if ($user == $this->users[0]) {
                $this->assertEquals(2, count($proguserassignments));
            } else {
                $this->assertEquals(1, count($proguserassignments));
            }

            // Check prog_completion records.
            $progcompletion = $DB->get_record('prog_completion',
                array('programid' => $this->program->id, 'userid' => $user->id));
            $this->assertNotEmpty($progcompletion);

            $this->assertEquals(0, $progcompletion->coursesetid);
            $this->assertEquals(0, $progcompletion->status);
            $this->assertGreaterThanOrEqual($timebefore, $progcompletion->timecreated);
            $this->assertLessThanOrEqual($timeafter, $progcompletion->timecreated);
            $this->assertEquals(0, $progcompletion->timestarted);
            $this->assertEquals(-1, $progcompletion->timedue);
            $this->assertEquals(0, $progcompletion->timecompleted);
        }

        $this->check_control_program();
    }

    /**
     * Assign a user via individual and audience, then later remove the individual assignment.
     *
     * Check that one program user assignment record was removed but the other was not.
     * Check that their program completion record was not removed.
     */
    public function test_multiple_assignments_removing_one() {
        global $DB;

        $timebefore = time();

        // Add audience assignment.
        $audience = $this->audiences[0];
        $audienceusers = $this->audienceusers[0];
        $this->set_audience_assignment($this->program, $audience);

        // Add individual assignments.
        $user0 = $this->users[0]; // Is also in audience 0, so adds assignment but not completion.
        $user1 = $this->users[1]; // Is not in audience 0, so adds assignment and not completion.
        $this->set_individual_assignments($this->program, array($user0, $user1));

        // Check that records exist.
        $this->assertEquals(3, $DB->count_records('prog_assignment',
            array('programid' => $this->program->id)));
        $this->assertEquals(0, $DB->count_records('prog_user_assignment',
            array('programid' => $this->program->id)));
        $this->assertEquals(0, $DB->count_records('prog_completion',
            array('programid' => $this->program->id)));

        // Apply assignment changes.
        $this->program->update_learner_assignments(true);

        $timeafter = time();

        // Check that records exist.
        $this->assertEquals(3, $DB->count_records('prog_assignment',
            array('programid' => $this->program->id))); // One audience, two individuals.
        $this->assertEquals(12, $DB->count_records('prog_user_assignment',
            array('programid' => $this->program->id))); // Ten from audience, two from individuals.
        $this->assertEquals(11, $DB->count_records('prog_completion',
            array('programid' => $this->program->id))); // Eleven unique individuals.

        // Remove individual assignments.
        $this->set_individual_assignments($this->program, array());

        // Apply assignment changes.
        $this->program->update_learner_assignments(true);

        // Check that records exist.
        $this->assertEquals(1, $DB->count_records('prog_assignment',
            array('programid' => $this->program->id)));
        $this->assertEquals(10, $DB->count_records('prog_user_assignment',
            array('programid' => $this->program->id)));
        $this->assertEquals(10, $DB->count_records('prog_completion',
            array('programid' => $this->program->id)));

        // Check prog_assignment record.
        $progassignment = $DB->get_record('prog_assignment',
            array('programid' => $this->program->id, 'assignmenttype' => ASSIGNTYPE_COHORT, 'assignmenttypeid' => $audience->id));
        $this->assertNotEmpty($progassignment);

        $this->assertEquals(0, $progassignment->includechildren);
        $this->assertEquals(-1, $progassignment->completiontime);
        $this->assertEquals(0, $progassignment->completionevent);
        $this->assertEquals(0, $progassignment->completioninstance);

        // All audience members should be assigned.
        foreach ($audienceusers as $user) {
            // Check prog_user_assignment records.
            $proguserassignment = $DB->get_record('prog_user_assignment',
                array('programid' => $this->program->id, 'userid' => $user->id));
            $this->assertNotEmpty($proguserassignment);

            $this->assertEquals($progassignment->id, $proguserassignment->assignmentid);
            $this->assertGreaterThanOrEqual($timebefore, $proguserassignment->timeassigned);
            $this->assertLessThanOrEqual($timeafter, $proguserassignment->timeassigned);
            $this->assertEquals(0, $proguserassignment->exceptionstatus);

            // Check prog_completion records.
            $progcompletion = $DB->get_record('prog_completion',
                array('programid' => $this->program->id, 'userid' => $user->id));
            $this->assertNotEmpty($progcompletion);

            $this->assertEquals(0, $progcompletion->coursesetid);
            $this->assertEquals(0, $progcompletion->status);
            $this->assertGreaterThanOrEqual($timebefore, $progcompletion->timecreated);
            $this->assertLessThanOrEqual($timeafter, $progcompletion->timecreated);
            $this->assertEquals(0, $progcompletion->timestarted);
            $this->assertEquals(-1, $progcompletion->timedue);
            $this->assertEquals(0, $progcompletion->timecompleted);
        }

        $this->check_control_program();
    }

    /**
     * Assign an audience with an assignment due date.
     *
     * Check that assignment due dates are applied to audience members.
     */
    public function test_audience_assignment_due_date() {
        global $DB;

        $timebefore = time();
        $completiontime = date($this->updateassignmentsdateformat, $timebefore + DAYSECS * 20);
        $duedate = totara_date_parse_from_format($this->updateassignmentsdateformat, $completiontime);

        // Add audience assignment.
        $audience = $this->audiences[0];
        $audienceusers = $this->audienceusers[0];
        $this->set_audience_assignment($this->program, $audience, $completiontime);

        // Check that records exist.
        $this->assertEquals(1, $DB->count_records('prog_assignment',
            array('programid' => $this->program->id)));
        $this->assertEquals(0, $DB->count_records('prog_user_assignment',
            array('programid' => $this->program->id)));
        $this->assertEquals(0, $DB->count_records('prog_completion',
            array('programid' => $this->program->id)));

        // Apply assignment changes.
        $this->program->update_learner_assignments(true);

        $timeafter = time();

        // Check that records exist.
        $this->assertEquals(1, $DB->count_records('prog_assignment',
            array('programid' => $this->program->id)));
        $this->assertEquals(10, $DB->count_records('prog_user_assignment',
            array('programid' => $this->program->id)));
        $this->assertEquals(10, $DB->count_records('prog_completion',
            array('programid' => $this->program->id)));

        // Check prog_assignment record.
        $progassignment = $DB->get_record('prog_assignment',
            array('programid' => $this->program->id, 'assignmenttype' => ASSIGNTYPE_COHORT, 'assignmenttypeid' => $audience->id));
        $this->assertNotEmpty($progassignment);

        $this->assertEquals(0, $progassignment->includechildren);
        $this->assertEquals($duedate, $progassignment->completiontime); // Due date!
        $this->assertEquals(0, $progassignment->completionevent);
        $this->assertEquals(0, $progassignment->completioninstance);

        // All audience members should be assigned.
        foreach ($audienceusers as $user) {
            // Check prog_user_assignment records.
            $proguserassignment = $DB->get_record('prog_user_assignment',
                array('programid' => $this->program->id, 'userid' => $user->id));
            $this->assertNotEmpty($proguserassignment);

            $this->assertEquals($progassignment->id, $proguserassignment->assignmentid);
            $this->assertGreaterThanOrEqual($timebefore, $proguserassignment->timeassigned);
            $this->assertLessThanOrEqual($timeafter, $proguserassignment->timeassigned);
            $this->assertEquals(0, $proguserassignment->exceptionstatus);

            // Check prog_completion records.
            $progcompletion = $DB->get_record('prog_completion',
                array('programid' => $this->program->id, 'userid' => $user->id));
            $this->assertNotEmpty($progcompletion);

            $this->assertEquals(0, $progcompletion->coursesetid);
            $this->assertEquals(0, $progcompletion->status);
            $this->assertGreaterThanOrEqual($timebefore, $progcompletion->timecreated);
            $this->assertLessThanOrEqual($timeafter, $progcompletion->timecreated);
            $this->assertEquals(0, $progcompletion->timestarted);
            $this->assertEquals($duedate, $progcompletion->timedue); // Due date!
            $this->assertEquals(0, $progcompletion->timecompleted);
        }

        $this->check_control_program();
    }

    /**
     * Assign an audience with an assignment due date and then increase it. The new date should be applied.
     *
     * Check that assignment due dates are updated for all audience members.
     */
    public function test_increase_due_date() {
        global $DB;

        $timebefore = time();
        $originalcompletiontime = date($this->updateassignmentsdateformat, $timebefore + DAYSECS * 20);
        $originalduedate = totara_date_parse_from_format($this->updateassignmentsdateformat, $originalcompletiontime);
        $newcompletiontime = date($this->updateassignmentsdateformat, $timebefore + DAYSECS * 30); // Increased.
        $newduedate = totara_date_parse_from_format($this->updateassignmentsdateformat, $newcompletiontime);

        // Add audience assignment.
        $audience = $this->audiences[0];
        $audienceusers = $this->audienceusers[0];
        $this->set_audience_assignment($this->program, $audience, $originalcompletiontime);

        // Check that records exist.
        $this->assertEquals(1, $DB->count_records('prog_assignment',
            array('programid' => $this->program->id)));
        $this->assertEquals(0, $DB->count_records('prog_user_assignment',
            array('programid' => $this->program->id)));
        $this->assertEquals(0, $DB->count_records('prog_completion',
            array('programid' => $this->program->id)));

        // Apply assignment changes.
        $this->program->update_learner_assignments(true);

        $timeafter = time();

        // Check that records exist.
        $this->assertEquals(1, $DB->count_records('prog_assignment',
            array('programid' => $this->program->id)));
        $this->assertEquals(10, $DB->count_records('prog_user_assignment',
            array('programid' => $this->program->id)));
        $this->assertEquals(10, $DB->count_records('prog_completion',
            array('programid' => $this->program->id)));

        // Check prog_assignment record.
        $progassignment = $DB->get_record('prog_assignment',
            array('programid' => $this->program->id, 'assignmenttype' => ASSIGNTYPE_COHORT, 'assignmenttypeid' => $audience->id));
        $this->assertNotEmpty($progassignment);

        $this->assertEquals(0, $progassignment->includechildren);
        $this->assertEquals($originalduedate, $progassignment->completiontime); // Original.
        $this->assertEquals(0, $progassignment->completionevent);
        $this->assertEquals(0, $progassignment->completioninstance);

        // All audience members should be assigned.
        foreach ($audienceusers as $user) {
            // Check prog_user_assignment records.
            $proguserassignment = $DB->get_record('prog_user_assignment',
                array('programid' => $this->program->id, 'userid' => $user->id));
            $this->assertNotEmpty($proguserassignment);

            $this->assertEquals($progassignment->id, $proguserassignment->assignmentid);
            $this->assertGreaterThanOrEqual($timebefore, $proguserassignment->timeassigned);
            $this->assertLessThanOrEqual($timeafter, $proguserassignment->timeassigned);
            $this->assertEquals(0, $proguserassignment->exceptionstatus);

            // Check prog_completion records.
            $progcompletion = $DB->get_record('prog_completion',
                array('programid' => $this->program->id, 'userid' => $user->id));
            $this->assertNotEmpty($progcompletion);

            $this->assertEquals(0, $progcompletion->coursesetid);
            $this->assertEquals(0, $progcompletion->status);
            $this->assertGreaterThanOrEqual($timebefore, $progcompletion->timecreated);
            $this->assertLessThanOrEqual($timeafter, $progcompletion->timecreated);
            $this->assertEquals(0, $progcompletion->timestarted);
            $this->assertEquals($originalduedate, $progcompletion->timedue); // Original.
            $this->assertEquals(0, $progcompletion->timecompleted);
        }

        // Change audience assignment's completion time.
        $this->set_audience_assignment($this->program, $audience, $newcompletiontime);

        // Apply assignment changes.
        $this->program->update_learner_assignments(true);

        // Check that records exist.
        $this->assertEquals(1, $DB->count_records('prog_assignment',
            array('programid' => $this->program->id)));
        $this->assertEquals(10, $DB->count_records('prog_user_assignment',
            array('programid' => $this->program->id)));
        $this->assertEquals(10, $DB->count_records('prog_completion',
            array('programid' => $this->program->id)));

        // Check prog_assignment record.
        $progassignment = $DB->get_record('prog_assignment',
            array('programid' => $this->program->id, 'assignmenttype' => ASSIGNTYPE_COHORT, 'assignmenttypeid' => $audience->id));
        $this->assertNotEmpty($progassignment);

        $this->assertEquals(0, $progassignment->includechildren);
        $this->assertEquals($newduedate, $progassignment->completiontime); // New!
        $this->assertEquals(0, $progassignment->completionevent);
        $this->assertEquals(0, $progassignment->completioninstance);

        // All audience members should be assigned.
        foreach ($audienceusers as $user) {
            // Check prog_user_assignment records.
            $proguserassignment = $DB->get_record('prog_user_assignment',
                array('programid' => $this->program->id, 'userid' => $user->id));
            $this->assertNotEmpty($proguserassignment);

            $this->assertEquals($progassignment->id, $proguserassignment->assignmentid);
            $this->assertGreaterThanOrEqual($timebefore, $proguserassignment->timeassigned);
            $this->assertLessThanOrEqual($timeafter, $proguserassignment->timeassigned);
            $this->assertEquals(0, $proguserassignment->exceptionstatus);

            // Check prog_completion records.
            $progcompletion = $DB->get_record('prog_completion',
                array('programid' => $this->program->id, 'userid' => $user->id));
            $this->assertNotEmpty($progcompletion);

            $this->assertEquals(0, $progcompletion->coursesetid);
            $this->assertEquals(0, $progcompletion->status);
            $this->assertGreaterThanOrEqual($timebefore, $progcompletion->timecreated);
            $this->assertLessThanOrEqual($timeafter, $progcompletion->timecreated);
            $this->assertEquals(0, $progcompletion->timestarted);
            $this->assertEquals($newduedate, $progcompletion->timedue); // New!
            $this->assertEquals(0, $progcompletion->timecompleted);
        }

        $this->check_control_program();
    }

    /**
     * Assign an audience with a set assignment due date and then decrease it. The new date should NOT be applied.
     *
     * Check that assignment due dates are not updated for all audience members.
     */
    public function test_decrease_due_date_set_dates() {
        global $DB;

        $timebefore = time();
        $originalcompletiontime = date($this->updateassignmentsdateformat, $timebefore + DAYSECS * 20);
        $originalduedate = totara_date_parse_from_format($this->updateassignmentsdateformat, $originalcompletiontime);
        $newcompletiontime = date($this->updateassignmentsdateformat, $timebefore + DAYSECS * 10); // Decreased.
        $newduedate = totara_date_parse_from_format($this->updateassignmentsdateformat, $newcompletiontime);

        // Add audience assignment.
        $audience = $this->audiences[0];
        $audienceusers = $this->audienceusers[0];
        $this->set_audience_assignment($this->program, $audience, $originalcompletiontime);

        // Check that records exist.
        $this->assertEquals(1, $DB->count_records('prog_assignment',
            array('programid' => $this->program->id)));
        $this->assertEquals(0, $DB->count_records('prog_user_assignment',
            array('programid' => $this->program->id)));
        $this->assertEquals(0, $DB->count_records('prog_completion',
            array('programid' => $this->program->id)));

        // Apply assignment changes.
        $this->program->update_learner_assignments(true);

        $timeafter = time();

        // Check that records exist.
        $this->assertEquals(1, $DB->count_records('prog_assignment',
            array('programid' => $this->program->id)));
        $this->assertEquals(10, $DB->count_records('prog_user_assignment',
            array('programid' => $this->program->id)));
        $this->assertEquals(10, $DB->count_records('prog_completion',
            array('programid' => $this->program->id)));

        // Check prog_assignment record.
        $progassignment = $DB->get_record('prog_assignment',
            array('programid' => $this->program->id, 'assignmenttype' => ASSIGNTYPE_COHORT, 'assignmenttypeid' => $audience->id));
        $this->assertNotEmpty($progassignment);

        $this->assertEquals(0, $progassignment->includechildren);
        $this->assertEquals($originalduedate, $progassignment->completiontime); // Original.
        $this->assertEquals(0, $progassignment->completionevent);
        $this->assertEquals(0, $progassignment->completioninstance);

        // All audience members should be assigned.
        foreach ($audienceusers as $user) {
            // Check prog_user_assignment records.
            $proguserassignment = $DB->get_record('prog_user_assignment',
                array('programid' => $this->program->id, 'userid' => $user->id));
            $this->assertNotEmpty($proguserassignment);

            $this->assertEquals($progassignment->id, $proguserassignment->assignmentid);
            $this->assertGreaterThanOrEqual($timebefore, $proguserassignment->timeassigned);
            $this->assertLessThanOrEqual($timeafter, $proguserassignment->timeassigned);
            $this->assertEquals(0, $proguserassignment->exceptionstatus);

            // Check prog_completion records.
            $progcompletion = $DB->get_record('prog_completion',
                array('programid' => $this->program->id, 'userid' => $user->id));
            $this->assertNotEmpty($progcompletion);

            $this->assertEquals(0, $progcompletion->coursesetid);
            $this->assertEquals(0, $progcompletion->status);
            $this->assertGreaterThanOrEqual($timebefore, $progcompletion->timecreated);
            $this->assertLessThanOrEqual($timeafter, $progcompletion->timecreated);
            $this->assertEquals(0, $progcompletion->timestarted);
            $this->assertEquals($originalduedate, $progcompletion->timedue); // Original.
            $this->assertEquals(0, $progcompletion->timecompleted);
        }

        // Change audience assignment's completion time.
        $this->set_audience_assignment($this->program, $audience, $newcompletiontime);

        // Apply assignment changes.
        $this->program->update_learner_assignments(true);

        // Check that records exist.
        $this->assertEquals(1, $DB->count_records('prog_assignment',
            array('programid' => $this->program->id)));
        $this->assertEquals(10, $DB->count_records('prog_user_assignment',
            array('programid' => $this->program->id)));
        $this->assertEquals(10, $DB->count_records('prog_completion',
            array('programid' => $this->program->id)));

        // Check prog_assignment record.
        $progassignment = $DB->get_record('prog_assignment',
            array('programid' => $this->program->id, 'assignmenttype' => ASSIGNTYPE_COHORT, 'assignmenttypeid' => $audience->id));
        $this->assertNotEmpty($progassignment);

        $this->assertEquals(0, $progassignment->includechildren);
        $this->assertEquals($newduedate, $progassignment->completiontime); // New!
        $this->assertEquals(0, $progassignment->completionevent);
        $this->assertEquals(0, $progassignment->completioninstance);

        // All audience members should be assigned.
        foreach ($audienceusers as $user) {
            // Check prog_user_assignment records.
            $proguserassignment = $DB->get_record('prog_user_assignment',
                array('programid' => $this->program->id, 'userid' => $user->id));
            $this->assertNotEmpty($proguserassignment);

            $this->assertEquals($progassignment->id, $proguserassignment->assignmentid);
            $this->assertGreaterThanOrEqual($timebefore, $proguserassignment->timeassigned);
            $this->assertLessThanOrEqual($timeafter, $proguserassignment->timeassigned);
            $this->assertEquals(0, $proguserassignment->exceptionstatus);

            // Check prog_completion records.
            $progcompletion = $DB->get_record('prog_completion',
                array('programid' => $this->program->id, 'userid' => $user->id));
            $this->assertNotEmpty($progcompletion);

            $this->assertEquals(0, $progcompletion->coursesetid);
            $this->assertEquals(0, $progcompletion->status);
            $this->assertGreaterThanOrEqual($timebefore, $progcompletion->timecreated);
            $this->assertLessThanOrEqual($timeafter, $progcompletion->timecreated);
            $this->assertEquals(0, $progcompletion->timestarted);
            $this->assertEquals($originalduedate, $progcompletion->timedue); // Still original!!!
            $this->assertEquals(0, $progcompletion->timecompleted);
        }

        $this->check_control_program();
    }

    /**
     * Assign an audience with a relative assignment due date and then decrease it. The new date should NOT be applied.
     *
     * Check that assignment due dates are not updated for all audience members.
     */
    public function test_decrease_due_date_relative_dates() {
        global $DB;

        $timebefore = time();
        $originaldurationstring = "20 " . TIME_SELECTOR_DAYS;
        $originalduration = DAYSECS * 20;
        $newdurationstring = "10 " . TIME_SELECTOR_DAYS;
        $newduration = DAYSECS * 10;

        // Add audience assignment.
        $audience = $this->audiences[0];
        $audienceusers = $this->audienceusers[0];
        $this->set_audience_assignment($this->program, $audience, $originaldurationstring, COMPLETION_EVENT_ENROLLMENT_DATE);

        // Check that records exist.
        $this->assertEquals(1, $DB->count_records('prog_assignment',
            array('programid' => $this->program->id)));
        $this->assertEquals(0, $DB->count_records('prog_user_assignment',
            array('programid' => $this->program->id)));
        $this->assertEquals(0, $DB->count_records('prog_completion',
            array('programid' => $this->program->id)));

        // Apply assignment changes.
        $this->program->update_learner_assignments(true);

        $timeafter = time();

        // Check that records exist.
        $this->assertEquals(1, $DB->count_records('prog_assignment',
            array('programid' => $this->program->id)));
        $this->assertEquals(10, $DB->count_records('prog_user_assignment',
            array('programid' => $this->program->id)));
        $this->assertEquals(10, $DB->count_records('prog_completion',
            array('programid' => $this->program->id)));

        // Check prog_assignment record.
        $progassignment = $DB->get_record('prog_assignment',
            array('programid' => $this->program->id, 'assignmenttype' => ASSIGNTYPE_COHORT, 'assignmenttypeid' => $audience->id));
        $this->assertNotEmpty($progassignment);

        $this->assertEquals(0, $progassignment->includechildren);
        $this->assertEquals($originalduration, $progassignment->completiontime); // Original.
        $this->assertEquals(COMPLETION_EVENT_ENROLLMENT_DATE, $progassignment->completionevent);
        $this->assertEquals(0, $progassignment->completioninstance);

        // All audience members should be assigned.
        foreach ($audienceusers as $user) {
            // Check prog_user_assignment records.
            $proguserassignment = $DB->get_record('prog_user_assignment',
                array('programid' => $this->program->id, 'userid' => $user->id));
            $this->assertNotEmpty($proguserassignment);

            $this->assertEquals($progassignment->id, $proguserassignment->assignmentid);
            $this->assertGreaterThanOrEqual($timebefore, $proguserassignment->timeassigned);
            $this->assertLessThanOrEqual($timeafter, $proguserassignment->timeassigned);
            $this->assertEquals(0, $proguserassignment->exceptionstatus);

            // Check prog_completion records.
            $progcompletion = $DB->get_record('prog_completion',
                array('programid' => $this->program->id, 'userid' => $user->id));
            $this->assertNotEmpty($progcompletion);

            $this->assertEquals(0, $progcompletion->coursesetid);
            $this->assertEquals(0, $progcompletion->status);
            $this->assertGreaterThanOrEqual($timebefore, $progcompletion->timecreated);
            $this->assertLessThanOrEqual($timeafter, $progcompletion->timecreated);
            $this->assertEquals(0, $progcompletion->timestarted);
            // When timedue for event "Enrollment date" is calculated, the prog_user_assignment record doesn't yet exist,
            // so it uses time() instead of timecreated. Some short time later, prog_user_assignment is created, and
            // timecreated may have a slightly different value. We know the calculated time must be between when the test
            // code was started and finished, and should have added the duration.
            $this->assertGreaterThanOrEqual($timebefore + $originalduration, $progcompletion->timedue); // Original.
            $this->assertLessThanOrEqual($timeafter + $originalduration, $progcompletion->timedue); // Original.
            $this->assertEquals(0, $progcompletion->timecompleted);
        }

        // Change audience assignment's completion time.
        $this->set_audience_assignment($this->program, $audience, $newdurationstring, COMPLETION_EVENT_ENROLLMENT_DATE);

        // Apply assignment changes.
        $this->program->update_learner_assignments(true);

        // Check that records exist.
        $this->assertEquals(1, $DB->count_records('prog_assignment',
            array('programid' => $this->program->id)));
        $this->assertEquals(10, $DB->count_records('prog_user_assignment',
            array('programid' => $this->program->id)));
        $this->assertEquals(10, $DB->count_records('prog_completion',
            array('programid' => $this->program->id)));

        // Check prog_assignment record.
        $progassignment = $DB->get_record('prog_assignment',
            array('programid' => $this->program->id, 'assignmenttype' => ASSIGNTYPE_COHORT, 'assignmenttypeid' => $audience->id));
        $this->assertNotEmpty($progassignment);

        $this->assertEquals(0, $progassignment->includechildren);
        $this->assertEquals($newduration, $progassignment->completiontime); // New!
        $this->assertEquals(COMPLETION_EVENT_ENROLLMENT_DATE, $progassignment->completionevent);
        $this->assertEquals(0, $progassignment->completioninstance);

        // All audience members should be assigned.
        foreach ($audienceusers as $user) {
            // Check prog_user_assignment records.
            $proguserassignment = $DB->get_record('prog_user_assignment',
                array('programid' => $this->program->id, 'userid' => $user->id));
            $this->assertNotEmpty($proguserassignment);

            $this->assertEquals($progassignment->id, $proguserassignment->assignmentid);
            $this->assertGreaterThanOrEqual($timebefore, $proguserassignment->timeassigned);
            $this->assertLessThanOrEqual($timeafter, $proguserassignment->timeassigned);
            $this->assertEquals(0, $proguserassignment->exceptionstatus);

            // Check prog_completion records.
            $progcompletion = $DB->get_record('prog_completion',
                array('programid' => $this->program->id, 'userid' => $user->id));
            $this->assertNotEmpty($progcompletion);

            $this->assertEquals(0, $progcompletion->coursesetid);
            $this->assertEquals(0, $progcompletion->status);
            $this->assertGreaterThanOrEqual($timebefore, $progcompletion->timecreated);
            $this->assertLessThanOrEqual($timeafter, $progcompletion->timecreated);
            $this->assertEquals(0, $progcompletion->timestarted);
            // The timedue shouldn't have been changed, so it is still not based on the actual timecreated.
            $this->assertGreaterThanOrEqual($timebefore + $originalduration, $progcompletion->timedue); // Still original!!!
            $this->assertLessThanOrEqual($timeafter + $originalduration, $progcompletion->timedue); // Still original!!!
            $this->assertEquals(0, $progcompletion->timecompleted);
        }

        $this->check_control_program();
    }

    /**
     * Assign a due date, complete the program and then increase the due date. The new date should NOT be applied.
     *
     * Check that assignment due dates are not updated for completed users.
     */
    public function test_increase_due_date_when_complete() {
        global $DB;

        $timebefore = time();
        $originalcompletiontime = date($this->updateassignmentsdateformat, $timebefore + DAYSECS * 10);
        $originalduedate = totara_date_parse_from_format($this->updateassignmentsdateformat, $originalcompletiontime);
        $newcompletiontime = date($this->updateassignmentsdateformat, $timebefore + DAYSECS * 20); // Increased - normally allowed but not when complete.
        $newduedate = totara_date_parse_from_format($this->updateassignmentsdateformat, $newcompletiontime);

        // Add audience assignment.
        $audience = $this->audiences[0];
        $audienceusers = $this->audienceusers[0];
        $this->set_audience_assignment($this->program, $audience, $originalcompletiontime);

        // Check that records exist.
        $this->assertEquals(1, $DB->count_records('prog_assignment',
            array('programid' => $this->program->id)));
        $this->assertEquals(0, $DB->count_records('prog_user_assignment',
            array('programid' => $this->program->id)));
        $this->assertEquals(0, $DB->count_records('prog_completion',
            array('programid' => $this->program->id)));

        // Apply assignment changes.
        $this->program->update_learner_assignments(true);

        $timeafter = time();

        // Check that records exist.
        $this->assertEquals(1, $DB->count_records('prog_assignment',
            array('programid' => $this->program->id)));
        $this->assertEquals(10, $DB->count_records('prog_user_assignment',
            array('programid' => $this->program->id)));
        $this->assertEquals(10, $DB->count_records('prog_completion',
            array('programid' => $this->program->id)));

        // Check prog_assignment record.
        $progassignment = $DB->get_record('prog_assignment',
            array('programid' => $this->program->id, 'assignmenttype' => ASSIGNTYPE_COHORT, 'assignmenttypeid' => $audience->id));
        $this->assertNotEmpty($progassignment);

        $this->assertEquals(0, $progassignment->includechildren);
        $this->assertEquals($originalduedate, $progassignment->completiontime); // Original.
        $this->assertEquals(0, $progassignment->completionevent);
        $this->assertEquals(0, $progassignment->completioninstance);

        // All audience members should be assigned.
        foreach ($audienceusers as $user) {
            // Check prog_user_assignment records.
            $proguserassignment = $DB->get_record('prog_user_assignment',
                array('programid' => $this->program->id, 'userid' => $user->id));
            $this->assertNotEmpty($proguserassignment);

            $this->assertEquals($progassignment->id, $proguserassignment->assignmentid);
            $this->assertGreaterThanOrEqual($timebefore, $proguserassignment->timeassigned);
            $this->assertLessThanOrEqual($timeafter, $proguserassignment->timeassigned);
            $this->assertEquals(0, $proguserassignment->exceptionstatus);

            // Check prog_completion records.
            $progcompletion = $DB->get_record('prog_completion',
                array('programid' => $this->program->id, 'userid' => $user->id));
            $this->assertNotEmpty($progcompletion);

            $this->assertEquals(0, $progcompletion->coursesetid);
            $this->assertEquals(0, $progcompletion->status);
            $this->assertGreaterThanOrEqual($timebefore, $progcompletion->timecreated);
            $this->assertLessThanOrEqual($timeafter, $progcompletion->timecreated);
            $this->assertEquals(0, $progcompletion->timestarted);
            $this->assertEquals($originalduedate, $progcompletion->timedue); // Original.
            $this->assertEquals(0, $progcompletion->timecompleted);
        }

        // Mark the audience members complete in the program. Just hack the records.
        $DB->execute("UPDATE {prog_completion}
                         SET status = :statuscomplete, timecompleted = :timecompleted
                       WHERE programid = :programid",
            array('statuscomplete' => STATUS_PROGRAM_COMPLETE,
                  'timecompleted' => $timebefore,
                  'programid' => $this->program->id));

        // Change audience assignment's completion time.
        $this->set_audience_assignment($this->program, $audience, $newcompletiontime);

        // Apply assignment changes.
        $this->program->update_learner_assignments(true);

        // Check that records exist.
        $this->assertEquals(1, $DB->count_records('prog_assignment',
            array('programid' => $this->program->id)));
        $this->assertEquals(10, $DB->count_records('prog_user_assignment',
            array('programid' => $this->program->id)));
        $this->assertEquals(10, $DB->count_records('prog_completion',
            array('programid' => $this->program->id)));

        // Check prog_assignment record.
        $progassignment = $DB->get_record('prog_assignment',
            array('programid' => $this->program->id, 'assignmenttype' => ASSIGNTYPE_COHORT, 'assignmenttypeid' => $audience->id));
        $this->assertNotEmpty($progassignment);

        $this->assertEquals(0, $progassignment->includechildren);
        $this->assertEquals($newduedate, $progassignment->completiontime); // New!
        $this->assertEquals(0, $progassignment->completionevent);
        $this->assertEquals(0, $progassignment->completioninstance);

        // All audience members should be assigned.
        foreach ($audienceusers as $user) {
            // Check prog_user_assignment records.
            $proguserassignment = $DB->get_record('prog_user_assignment',
                array('programid' => $this->program->id, 'userid' => $user->id));
            $this->assertNotEmpty($proguserassignment);

            $this->assertEquals($progassignment->id, $proguserassignment->assignmentid);
            $this->assertGreaterThanOrEqual($timebefore, $proguserassignment->timeassigned);
            $this->assertLessThanOrEqual($timeafter, $proguserassignment->timeassigned);
            $this->assertEquals(0, $proguserassignment->exceptionstatus);

            // Check prog_completion records.
            $progcompletion = $DB->get_record('prog_completion',
                array('programid' => $this->program->id, 'userid' => $user->id));
            $this->assertNotEmpty($progcompletion);

            $this->assertEquals(0, $progcompletion->coursesetid);
            $this->assertEquals(STATUS_PROGRAM_COMPLETE, $progcompletion->status); // Hacked to complete.
            $this->assertGreaterThanOrEqual($timebefore, $progcompletion->timecreated);
            $this->assertLessThanOrEqual($timeafter, $progcompletion->timecreated);
            $this->assertEquals(0, $progcompletion->timestarted);
            $this->assertEquals($originalduedate, $progcompletion->timedue); // Still original!!!
            $this->assertEquals($timebefore, $progcompletion->timecompleted);
        }

        $this->check_control_program();
    }

    /**
     * Assign a user via individual and audience, both with fixed assignment completion dates.
     *
     * Check that the user has the correct due date in their program completion record.
     * The correct due date is the later of the two, as seen in $program->make_timedue().
     */
    public function test_multiple_assignments_with_assignment_due_dates() {
        global $DB;

        $timebefore = time();
        $individialcompletiontime = date($this->updateassignmentsdateformat, $timebefore + DAYSECS * 20);
        $individualduedate = totara_date_parse_from_format($this->updateassignmentsdateformat, $individialcompletiontime);
        // Give the audience the bigger date, so that all assigned users should have this due date.
        $audiencecompletiontime = date($this->updateassignmentsdateformat, $timebefore + DAYSECS * 30);
        $audienceduedate = totara_date_parse_from_format($this->updateassignmentsdateformat, $audiencecompletiontime);

        // Add audience assignment.
        $audience = $this->audiences[0];
        $audienceusers = $this->audienceusers[0];
        $this->set_audience_assignment($this->program, $audience, $audiencecompletiontime);

        // Add individual assignment.
        $user = $this->users[0]; // Also included in audience.
        $this->set_individual_assignments($this->program, array($user), $individialcompletiontime);

        // Apply assignment changes.
        $this->program->update_learner_assignments(true);

        $timeafter = time();

        // Check that records exist.
        $this->assertEquals(2, $DB->count_records('prog_assignment',
            array('programid' => $this->program->id)));
        $this->assertEquals(11, $DB->count_records('prog_user_assignment',
            array('programid' => $this->program->id)));
        $this->assertEquals(10, $DB->count_records('prog_completion',
            array('programid' => $this->program->id)));

        // Check audience prog_assignment record.
        $audienceprogassignment = $DB->get_record('prog_assignment',
            array('programid' => $this->program->id, 'assignmenttype' => ASSIGNTYPE_COHORT,
                'assignmenttypeid' => $audience->id));
        $this->assertNotEmpty($audienceprogassignment);

        $this->assertEquals(0, $audienceprogassignment->includechildren);
        $this->assertEquals($audienceduedate, $audienceprogassignment->completiontime);
        $this->assertEquals(0, $audienceprogassignment->completionevent);
        $this->assertEquals(0, $audienceprogassignment->completioninstance);

        // Check individual prog_assignment record.
        $individualprogassignment = $DB->get_record('prog_assignment',
            array('programid' => $this->program->id, 'assignmenttype' => ASSIGNTYPE_INDIVIDUAL,
                'assignmenttypeid' => $user->id));
        $this->assertNotEmpty($individualprogassignment);

        $this->assertEquals(0, $individualprogassignment->includechildren);
        $this->assertEquals($individualduedate, $individualprogassignment->completiontime);
        $this->assertEquals(0, $individualprogassignment->completionevent);
        $this->assertEquals(0, $individualprogassignment->completioninstance);

        // All audience members should be assigned.
        foreach ($audienceusers as $user) {
            // Skip prog_user_assignment records (they aren't interesting).

            // Check prog_completion records.
            $progcompletion = $DB->get_record('prog_completion',
                array('programid' => $this->program->id, 'userid' => $user->id));
            $this->assertNotEmpty($progcompletion);

            $this->assertEquals(0, $progcompletion->coursesetid);
            $this->assertEquals(0, $progcompletion->status);
            $this->assertGreaterThanOrEqual($timebefore, $progcompletion->timecreated);
            $this->assertLessThanOrEqual($timeafter, $progcompletion->timecreated);
            $this->assertEquals(0, $progcompletion->timestarted);
            $this->assertEquals($audienceduedate, $progcompletion->timedue); // Audience due date!
            $this->assertEquals(0, $progcompletion->timecompleted);
        }

        $this->check_control_program();
    }

    /**
     * Assign a user first via individual and later via audience.
     *
     * Check that the user has the correct due date in their program completion record.
     * The correct due date is the later of the two, as seen in $program->make_timedue().
     */
    public function test_adding_additional_assignments() {
        global $DB;

        $timebefore = time();
        $originalcompletiontime = date($this->updateassignmentsdateformat, $timebefore + DAYSECS * 100);
        $originalduedate = totara_date_parse_from_format($this->updateassignmentsdateformat, $originalcompletiontime);
        // Give the audience the smaller date, and we will make sure that it is not being applied.
        $newdurationstring = "10 " . TIME_SELECTOR_DAYS;
        $newduration = DAYSECS * 10;

        // Add audience assignment.
        $audience = $this->audiences[0];
        $audienceusers = $this->audienceusers[0];
        $this->set_audience_assignment($this->program, $audience, $originalcompletiontime);

        // Apply assignment changes.
        $this->program->update_learner_assignments(true);

        $timeafter = time();

        // Check that records exist.
        $this->assertEquals(1, $DB->count_records('prog_assignment',
            array('programid' => $this->program->id)));
        $this->assertEquals(10, $DB->count_records('prog_user_assignment',
            array('programid' => $this->program->id)));
        $this->assertEquals(10, $DB->count_records('prog_completion',
            array('programid' => $this->program->id)));

        // Check audience prog_assignment record.
        $audienceprogassignment = $DB->get_record('prog_assignment',
            array('programid' => $this->program->id, 'assignmenttype' => ASSIGNTYPE_COHORT,
                'assignmenttypeid' => $audience->id));
        $this->assertNotEmpty($audienceprogassignment);

        $this->assertEquals(0, $audienceprogassignment->includechildren);
        $this->assertEquals($originalduedate, $audienceprogassignment->completiontime);
        $this->assertEquals(0, $audienceprogassignment->completionevent);
        $this->assertEquals(0, $audienceprogassignment->completioninstance);

        // All audience members should be assigned.
        foreach ($audienceusers as $user) {
            // Skip prog_user_assignment records (they aren't interesting).

            // Check prog_completion records.
            $progcompletion = $DB->get_record('prog_completion',
                array('programid' => $this->program->id, 'userid' => $user->id));
            $this->assertNotEmpty($progcompletion);

            $this->assertEquals(0, $progcompletion->coursesetid);
            $this->assertEquals(0, $progcompletion->status);
            $this->assertGreaterThanOrEqual($timebefore, $progcompletion->timecreated);
            $this->assertLessThanOrEqual($timeafter, $progcompletion->timecreated);
            $this->assertEquals($originalduedate, $progcompletion->timedue, // Original due date.
                'Not relative date: ' . ($newduration + $progcompletion->timecreated));
            $this->assertEquals(0, $progcompletion->timestarted);
            $this->assertEquals(0, $progcompletion->timecompleted);
        }

        // Mark the audience members complete in the program. Just hack the records.
        $DB->execute("UPDATE {prog_completion}
                         SET status = :statuscomplete, timecompleted = :timecompleted
                       WHERE programid = :programid",
            array('statuscomplete' => STATUS_PROGRAM_COMPLETE,
                  'timecompleted' => $timebefore,
                  'programid' => $this->program->id));

        // Add individual assignment.
        $user = $this->users[0]; // Also included in audience.
        $this->set_individual_assignments($this->program, array($user),  $newdurationstring, COMPLETION_EVENT_ENROLLMENT_DATE);

        // Apply assignment changes.
        $this->program->update_learner_assignments(true);

        // Check that records exist.
        $this->assertEquals(2, $DB->count_records('prog_assignment',
            array('programid' => $this->program->id)));
        $this->assertEquals(11, $DB->count_records('prog_user_assignment',
            array('programid' => $this->program->id)));
        $this->assertEquals(10, $DB->count_records('prog_completion',
            array('programid' => $this->program->id)));

        // Check audience prog_assignment record.
        $audienceprogassignment = $DB->get_record('prog_assignment',
            array('programid' => $this->program->id, 'assignmenttype' => ASSIGNTYPE_COHORT,
                'assignmenttypeid' => $audience->id));
        $this->assertNotEmpty($audienceprogassignment);

        $this->assertEquals(0, $audienceprogassignment->includechildren);
        $this->assertEquals($originalduedate, $audienceprogassignment->completiontime);
        $this->assertEquals(0, $audienceprogassignment->completionevent);
        $this->assertEquals(0, $audienceprogassignment->completioninstance);

        // Check individual prog_assignment record.
        $individualprogassignment = $DB->get_record('prog_assignment',
            array('programid' => $this->program->id, 'assignmenttype' => ASSIGNTYPE_INDIVIDUAL,
                'assignmenttypeid' => $user->id));
        $this->assertNotEmpty($individualprogassignment);

        $this->assertEquals(0, $individualprogassignment->includechildren);
        $this->assertEquals($newduration, $individualprogassignment->completiontime);
        $this->assertEquals(COMPLETION_EVENT_ENROLLMENT_DATE, $individualprogassignment->completionevent);
        $this->assertEquals(0, $individualprogassignment->completioninstance);

        // All audience members should be assigned.
        foreach ($audienceusers as $user) {
            // Skip prog_user_assignment records (they aren't interesting).

            // Check prog_completion records.
            $progcompletion = $DB->get_record('prog_completion',
                array('programid' => $this->program->id, 'userid' => $user->id));
            $this->assertNotEmpty($progcompletion);

            $this->assertEquals(0, $progcompletion->coursesetid);
            $this->assertEquals(STATUS_PROGRAM_COMPLETE, $progcompletion->status);
            $this->assertGreaterThanOrEqual($timebefore, $progcompletion->timecreated);
            $this->assertLessThanOrEqual($timeafter, $progcompletion->timecreated);
            $this->assertEquals($originalduedate, $progcompletion->timedue, // Still original due date!
                'Not relative date: ' . ($newduration + $progcompletion->timecreated));
            $this->assertEquals(0, $progcompletion->timestarted);
            $this->assertEquals($timebefore, $progcompletion->timecompleted);
        }

        $this->check_control_program();
    }

    /**
     * Make sure it doesn't fail with huge data sets.
     */
    public function test_bulk_assignment_functions() {
        global $DB;

        return; // This isn't practical to run every time gerrit is run, as it takes a long time.

        $usercount = 1000; // 50k took 6 minutes and 600MBs of RAM on MySQL.

        // Add audience assignment.
        $timebefore = time();
        $audience = $this->getDataGenerator()->create_cohort();
        for ($i = 0; $i < $usercount; $i++) {
            $user = $this->getDataGenerator()->create_user(array('fullname' => 'audthree' . $i));
            cohort_add_member($audience->id, $user->id);
            unset($user);
        }
        $timeafter = time();
        $duration = $timeafter - $timebefore;
        echo("\nTotal duration creating {$usercount} users (in seconds): " . $duration);

        $this->set_audience_assignment($this->program, $audience);

        // Apply assignment changes, which will be deferred (pass nothing, defaults to false).
        $queriesbefore = $DB->perf_get_queries();
        $timebefore = time();
        $this->program->update_learner_assignments(true);
        $timeafter = time();
        $queriesafter = $DB->perf_get_queries();

        $duration = $timeafter - $timebefore;
        $queries = $queriesafter - $queriesbefore;
        echo("\nTotal duration of update_learner_assignments adding {$usercount} users (in seconds): " . $duration);
        echo("\nTotal queries of update_learner_assignments adding {$usercount} users: " . $queries);

        // Check that records exist.
        $this->assertEquals(1, $DB->count_records('prog_assignment',
            array('programid' => $this->program->id)));
        $this->assertEquals($usercount, $DB->count_records('prog_user_assignment',
            array('programid' => $this->program->id)));
        $this->assertEquals($usercount, $DB->count_records('prog_completion',
            array('programid' => $this->program->id)));

        // Repeat update to test performance - doesn't actually change any users this time.
        $queriesbefore = $DB->perf_get_queries();
        $timebefore = time();
        $this->program->update_learner_assignments(true);
        $timeafter = time();
        $queriesafter = $DB->perf_get_queries();

        $duration = $timeafter - $timebefore;
        $queries = $queriesafter - $queriesbefore;
        echo("\nTotal duration of update_learner_assignments making no changes (in seconds): " . $duration);
        echo("\nTotal queries of update_learner_assignments making no changes: " . $queries);

        // Check that records exist.
        $this->assertEquals(1, $DB->count_records('prog_assignment',
            array('programid' => $this->program->id)));
        $this->assertEquals($usercount, $DB->count_records('prog_user_assignment',
            array('programid' => $this->program->id)));
        $this->assertEquals($usercount, $DB->count_records('prog_completion',
            array('programid' => $this->program->id)));

        // Set empty audience, to test bulk removal.
        $audience = $this->getDataGenerator()->create_cohort();
        $this->set_audience_assignment($this->program, $audience);

        // Apply assignment changes, which will be deferred (pass nothing, defaults to false).
        $queriesbefore = $DB->perf_get_queries();
        $timebefore = time();
        $this->program->update_learner_assignments(true);
        $timeafter = time();
        $queriesafter = $DB->perf_get_queries();

        $duration = $timeafter - $timebefore;
        $queries = $queriesafter - $queriesbefore;
        echo("\nTotal duration of update_learner_assignments removing {$usercount} users (in seconds): " . $duration);
        echo("\nTotal queries of update_learner_assignments removing {$usercount} users: " . $queries);

        // Check that records exist.
        $this->assertEquals(1, $DB->count_records('prog_assignment',
            array('programid' => $this->program->id)));
        $this->assertEquals(0, $DB->count_records('prog_user_assignment',
            array('programid' => $this->program->id)));
        $this->assertEquals(0, $DB->count_records('prog_completion',
            array('programid' => $this->program->id)));

        $this->check_control_program();
    }

    /**
     * Assign a manager hierarchy to a program.
     */
    public function test_assigning_manager_hierarchy() {
        global $DB;

        $topmanager1 = $this->getDataGenerator()->create_user();
        $topmanager1ja = \totara_job\job_assignment::create_default($topmanager1->id);
        $topmanager2 = $this->getDataGenerator()->create_user();
        $topmanager2ja = \totara_job\job_assignment::create_default($topmanager2->id);
        $manager1 = $this->getDataGenerator()->create_user();
        $manager1ja = \totara_job\job_assignment::create_default($manager1->id, array('managerjaid' => $topmanager1ja->id));
        $manager2 = $this->getDataGenerator()->create_user();
        $manager2ja = \totara_job\job_assignment::create_default($manager2->id, array('managerjaid' => $topmanager1ja->id));
        $manager3 = $this->getDataGenerator()->create_user();
        $manager3ja = \totara_job\job_assignment::create_default($manager3->id);
        $manager4 = $this->getDataGenerator()->create_user();
        $manager4ja = \totara_job\job_assignment::create_default($manager4->id,  array('managerjaid' => $topmanager2ja->id));
        // Test a user with manager assigned via first (sortorder=1) job assignment only.
        $user1 = $this->users[1];
        $user1ja = \totara_job\job_assignment::create_default($user1->id, array('managerjaid' => $manager1ja->id));
        // Test a user with the same manager assigned via two job assignments.
        $user2 = $this->users[2];
        $user2ja1 = \totara_job\job_assignment::create_default($user2->id, array('managerjaid' => $manager1ja->id));
        $user2ja2 = \totara_job\job_assignment::create_default($user2->id, array('managerjaid' => $manager1ja->id));
        // Test a user with the manager assigned via their second (sortorder=2) job assignment only.
        $user3 = $this->users[3];
        $user3ja1 = \totara_job\job_assignment::create_default($user3->id, array());
        $user3ja2 = \totara_job\job_assignment::create_default($user3->id, array('managerjaid' => $manager2ja->id));
        $user4 = $this->users[4];
        $user4ja = \totara_job\job_assignment::create_default($user4->id, array('managerjaid' => $manager3ja->id));
        $user5 = $this->users[5];
        $user5ja = \totara_job\job_assignment::create_default($user5->id, array('managerjaid' => $manager4ja->id));

        $assignedusers = array($manager1, $manager2, $manager4, $user1, $user2, $user3);
        $notassignedusers = array($topmanager1, $topmanager2, $manager3, $user4, $user5);

        // Check we have a clean slate.
        $this->assertEquals(0, $DB->count_records('prog_assignment',
            array('programid' => $this->program->id)));
        $this->assertEquals(0, $DB->count_records('prog_user_assignment',
            array('programid' => $this->program->id)));
        $this->assertEquals(0, $DB->count_records('prog_completion',
            array('programid' => $this->program->id)));

        $timebefore = time();

        // Update_learner_assignments is also run within the assign_to_program generator methods.
        $generator = $this->getDataGenerator();
        $generator->assign_to_program($this->program->id, ASSIGNTYPE_MANAGERJA, $topmanager1ja->id,
            array('includechildren' => 1));
        $generator->assign_to_program($this->program->id, ASSIGNTYPE_MANAGERJA, $topmanager2ja->id);

        $timeafter = time();

        // Check that records exist.
        $this->assertEquals(2, $DB->count_records('prog_assignment',
            array('programid' => $this->program->id)));
        $this->assertEquals(6, $DB->count_records('prog_user_assignment',
            array('programid' => $this->program->id)));
        $this->assertEquals(6, $DB->count_records('prog_completion',
            array('programid' => $this->program->id)));

        // Check prog_assignment records.
        $progassignment = $DB->get_record('prog_assignment',
            array('programid' => $this->program->id, 'assignmenttype' => ASSIGNTYPE_MANAGERJA, 'assignmenttypeid' => $topmanager1ja->id));
        $this->assertNotEmpty($progassignment);
        $this->assertEquals(1, $progassignment->includechildren);
        $this->assertEquals(-1, $progassignment->completiontime);
        $this->assertEquals(0, $progassignment->completionevent);
        $this->assertEquals(0, $progassignment->completioninstance);

        $progassignment = $DB->get_record('prog_assignment',
            array('programid' => $this->program->id, 'assignmenttype' => ASSIGNTYPE_MANAGERJA, 'assignmenttypeid' => $topmanager2ja->id));
        $this->assertNotEmpty($progassignment);
        $this->assertEquals(0, $progassignment->includechildren);
        $this->assertEquals(-1, $progassignment->completiontime);
        $this->assertEquals(0, $progassignment->completionevent);
        $this->assertEquals(0, $progassignment->completioninstance);

        // Check records of assigned users.
        foreach ($assignedusers as $user) {
            // Check prog_user_assignment records.
            $proguserassignment = $DB->get_record('prog_user_assignment',
                array('programid' => $this->program->id, 'userid' => $user->id));
            $this->assertNotEmpty($proguserassignment);

            $this->assertGreaterThanOrEqual($timebefore, $proguserassignment->timeassigned);
            $this->assertLessThanOrEqual($timeafter, $proguserassignment->timeassigned);
            $this->assertEquals(0, $proguserassignment->exceptionstatus);

            // Check prog_completion records.
            $progcompletion = $DB->get_record('prog_completion',
                array('programid' => $this->program->id, 'userid' => $user->id));
            $this->assertNotEmpty($progcompletion);

            $this->assertEquals(0, $progcompletion->coursesetid);
            $this->assertEquals(0, $progcompletion->status);
            $this->assertGreaterThanOrEqual($timebefore, $progcompletion->timecreated);
            $this->assertLessThanOrEqual($timeafter, $progcompletion->timecreated);
            $this->assertEquals(0, $progcompletion->timestarted);
            $this->assertEquals(-1, $progcompletion->timedue);
            $this->assertEquals(0, $progcompletion->timecompleted);
        }

        // Check records of users that aren't assigned.
        foreach ($notassignedusers as $user) {
            $proguserassignment = $DB->get_record('prog_user_assignment',
                array('programid' => $this->program->id, 'userid' => $user->id));
            // It should have come back false as no record exists.
            $this->assertFalse($proguserassignment);
        }

        $this->check_control_program();
    }

    /**
     * Assigns users to program via manager hierarchy, then changes staff within that
     * hierarchy and ensures the correct records exist.
     *
     * @throws \Horde\Socket\Client\Exception
     * @throws coding_exception
     */
    public function test_change_in_manager_hierarchy() {
        global $DB;

        $topmanager1 = $this->getDataGenerator()->create_user();
        $topmanager1ja = \totara_job\job_assignment::create_default($topmanager1->id);
        $topmanager2 = $this->getDataGenerator()->create_user();
        $topmanager2ja = \totara_job\job_assignment::create_default($topmanager2->id);
        $manager1 = $this->getDataGenerator()->create_user();
        $manager1ja = \totara_job\job_assignment::create_default($manager1->id, array('managerjaid' => $topmanager1ja->id));
        $manager2 = $this->getDataGenerator()->create_user();
        $manager2ja = \totara_job\job_assignment::create_default($manager2->id, array('managerjaid' => $topmanager1ja->id));
        $manager3 = $this->getDataGenerator()->create_user();
        $manager3ja = \totara_job\job_assignment::create_default($manager3->id);
        $manager4 = $this->getDataGenerator()->create_user();
        $manager4ja = \totara_job\job_assignment::create_default($manager4->id,  array('managerjaid' => $topmanager2ja->id));
        $user1 = $this->users[1];
        $user1ja = \totara_job\job_assignment::create_default($user1->id, array('managerjaid' => $manager1ja->id));
        $user2 = $this->users[2];
        $user2ja = \totara_job\job_assignment::create_default($user2->id, array('managerjaid' => $manager1ja->id));
        $user3 = $this->users[3];
        $user3ja = \totara_job\job_assignment::create_default($user3->id, array('managerjaid' => $manager2ja->id));
        $user4 = $this->users[4];
        $user4ja = \totara_job\job_assignment::create_default($user4->id, array('managerjaid' => $manager3ja->id));
        $user5 = $this->users[5];
        $user5ja = \totara_job\job_assignment::create_default($user5->id, array('managerjaid' => $manager4ja->id));

        // Check we have a clean slate.
        $this->assertEquals(0, $DB->count_records('prog_assignment',
            array('programid' => $this->program->id)));
        $this->assertEquals(0, $DB->count_records('prog_user_assignment',
            array('programid' => $this->program->id)));
        $this->assertEquals(0, $DB->count_records('prog_completion',
            array('programid' => $this->program->id)));

        $timebefore = time();

        // Update_learner_assignments is also run within the assign_to_program generator methods.
        $generator = $this->getDataGenerator();
        $generator->assign_to_program($this->program->id, ASSIGNTYPE_MANAGERJA, $topmanager1ja->id,
            array('includechildren' => 1));
        $generator->assign_to_program($this->program->id, ASSIGNTYPE_MANAGERJA, $topmanager2ja->id);

        // Check that records exist.
        $this->assertEquals(2, $DB->count_records('prog_assignment',
            array('programid' => $this->program->id)));
        $this->assertEquals(6, $DB->count_records('prog_user_assignment',
            array('programid' => $this->program->id)));
        $this->assertEquals(6, $DB->count_records('prog_completion',
            array('programid' => $this->program->id)));

        // This should now be in the same state as what we have in test_assigning_manager_hierarchy.
        // So no need to check each record and user.

        // Now change the management hierarchy by adding/removing managers in a couple of job assignments.
        $manager1ja->update(array('managerjaid' => 0));
        $manager3ja->update(array('managerjaid' => $topmanager1ja->id));

        // Update learner assignments and then we'll check each record is correct.
        $this->program->update_learner_assignments(true);

        $timeafter = time();

        // $manager3 and $user4 should now been assigned.
        $assignedusers = array($manager2, $manager4, $user3, $manager3, $user4);
        // $manager1, $user1 and $user2 should no longer be assigned.
        $notassignedusers = array($topmanager1, $topmanager2, $user5, $manager1, $user1, $user2);

        $this->assertEquals(2, $DB->count_records('prog_assignment',
            array('programid' => $this->program->id)));
        $this->assertEquals(5, $DB->count_records('prog_user_assignment',
            array('programid' => $this->program->id)));
        $this->assertEquals(5, $DB->count_records('prog_completion',
            array('programid' => $this->program->id)));

        // Check prog_assignment records.
        $progassignment = $DB->get_record('prog_assignment',
            array('programid' => $this->program->id, 'assignmenttype' => ASSIGNTYPE_MANAGERJA, 'assignmenttypeid' => $topmanager1ja->id));
        $this->assertNotEmpty($progassignment);
        $this->assertEquals(1, $progassignment->includechildren);
        $this->assertEquals(-1, $progassignment->completiontime);
        $this->assertEquals(0, $progassignment->completionevent);
        $this->assertEquals(0, $progassignment->completioninstance);

        $progassignment = $DB->get_record('prog_assignment',
            array('programid' => $this->program->id, 'assignmenttype' => ASSIGNTYPE_MANAGERJA, 'assignmenttypeid' => $topmanager2ja->id));
        $this->assertNotEmpty($progassignment);
        $this->assertEquals(0, $progassignment->includechildren);
        $this->assertEquals(-1, $progassignment->completiontime);
        $this->assertEquals(0, $progassignment->completionevent);
        $this->assertEquals(0, $progassignment->completioninstance);

        // Check records of assigned users.
        foreach ($assignedusers as $user) {
            // Check prog_user_assignment records.
            $proguserassignment = $DB->get_record('prog_user_assignment',
                array('programid' => $this->program->id, 'userid' => $user->id));
            $this->assertNotEmpty($proguserassignment);

            $this->assertGreaterThanOrEqual($timebefore, $proguserassignment->timeassigned);
            $this->assertLessThanOrEqual($timeafter, $proguserassignment->timeassigned);
            $this->assertEquals(0, $proguserassignment->exceptionstatus);

            // Check prog_completion records.
            $progcompletion = $DB->get_record('prog_completion',
                array('programid' => $this->program->id, 'userid' => $user->id));
            $this->assertNotEmpty($progcompletion);

            $this->assertEquals(0, $progcompletion->coursesetid);
            $this->assertEquals(0, $progcompletion->status);
            $this->assertGreaterThanOrEqual($timebefore, $progcompletion->timecreated);
            $this->assertLessThanOrEqual($timeafter, $progcompletion->timecreated);
            $this->assertEquals(0, $progcompletion->timestarted);
            $this->assertEquals(-1, $progcompletion->timedue);
            $this->assertEquals(0, $progcompletion->timecompleted);
        }

        // Check records of users that aren't assigned.
        foreach ($notassignedusers as $user) {
            $proguserassignment = $DB->get_record('prog_user_assignment',
                array('programid' => $this->program->id, 'userid' => $user->id));
            // It should have come back false as no record exists.
            $this->assertFalse($proguserassignment);
        }

        $this->check_control_program();
    }

    /**
     * Assign users to a program based on postion.
     */
    public function test_assigning_position() {
        global $DB;

        $generator = $this->getDataGenerator();
        /** @var totara_hierarchy_generator $hierarchy_generator */
        $hierarchy_generator = $generator->get_plugin_generator('totara_hierarchy');
        $positionfw = $hierarchy_generator->create_pos_frame(array());
        $position1 = $hierarchy_generator->create_pos(array('frameworkid' => $positionfw->id, 'fullname' => 'position1'));
        $position2 = $hierarchy_generator->create_pos(array('frameworkid' => $positionfw->id, 'fullname' => 'position2', 'parentid' => $position1->id));
        $position3 = $hierarchy_generator->create_pos(array('frameworkid' => $positionfw->id, 'fullname' => 'position3'));
        $position4 = $hierarchy_generator->create_pos(array('frameworkid' => $positionfw->id, 'fullname' => 'position4', 'parentid' => $position3->id));
        $position5 = $hierarchy_generator->create_pos(array('frameworkid' => $positionfw->id, 'fullname' => 'position5'));

        // Test a user with position assigned via first (sortorder=1) job assignment only.
        $user1 = $this->users[1];
        $user1ja = \totara_job\job_assignment::get_first($user1->id);
        $user1ja->update(array('positionid' => $position1->id));
        // Test a user with the same position assigned via two job assignments.
        $user2 = $this->users[2];
        $user2ja1 = \totara_job\job_assignment::get_first($user2->id);
        $user2ja1->update(array('positionid' => $position1->id));
        $user2ja2 = \totara_job\job_assignment::create_default($user2->id, array('positionid' => $position1->id));
        // Test a user with the position assigned via their second (sortorder=2) job assignment only.
        $user3 = $this->users[3];
        $user3ja1 = \totara_job\job_assignment::get_first($user3->id);
        $user3ja2 = \totara_job\job_assignment::create_default($user3->id, array('positionid' => $position1->id));
        $user4 = $this->users[4];
        // Test a user with a child position.
        $user4ja = \totara_job\job_assignment::get_first($user4->id);
        $user4ja->update(array('positionid' => $position2->id));
        $user5 = $this->users[5];
        $user5ja = \totara_job\job_assignment::get_first($user5->id);
        $user5ja->update(array('positionid' => $position3->id));
        $user6 = $this->users[6];
        // Test a user in child position who is not added to the program.
        $user6ja = \totara_job\job_assignment::get_first($user6->id);
        $user6ja->update(array('positionid' => $position4->id));
        // Test a user with a position that is not add to the program.
        $user7 = $this->users[7];
        $user7ja = \totara_job\job_assignment::get_first($user7->id);
        $user7ja->update(array('positionid' => $position5->id));
        // Test a user that is not been assigned a position.
        $user8 = $this->users[8];
        $user8ja = \totara_job\job_assignment::get_first($user8->id);

        $assignedusers = array($user1, $user2, $user3, $user4, $user5);
        $notassignedusers = array($user6, $user7, $user8);

        // Check we have a clean slate.
        $this->assertEquals(0, $DB->count_records('prog_assignment',
            array('programid' => $this->program->id)));
        $this->assertEquals(0, $DB->count_records('prog_user_assignment',
            array('programid' => $this->program->id)));
        $this->assertEquals(0, $DB->count_records('prog_completion',
            array('programid' => $this->program->id)));

        $timebefore = time();

        // Update_learner_assignments is also run within the assign_to_program generator methods.
        $generator = $this->getDataGenerator();
        $generator->assign_to_program($this->program->id, ASSIGNTYPE_POSITION, $position1->id,
            array('includechildren' => 1));
        $generator->assign_to_program($this->program->id, ASSIGNTYPE_POSITION, $position3->id,
            array('includechildren' => null));

        $timeafter = time();

        // Check that records exist.
        $this->assertEquals(2, $DB->count_records('prog_assignment',
            array('programid' => $this->program->id)));
        $this->assertEquals(5, $DB->count_records('prog_user_assignment',
            array('programid' => $this->program->id)));
        $this->assertEquals(5, $DB->count_records('prog_completion',
            array('programid' => $this->program->id)));

        // Check prog_assignment records.
        $progassignment = $DB->get_record('prog_assignment',
            array('programid' => $this->program->id, 'assignmenttype' => ASSIGNTYPE_POSITION, 'assignmenttypeid' => $position1->id));
        $this->assertNotEmpty($progassignment);
        $this->assertEquals(1, $progassignment->includechildren);
        $this->assertEquals(-1, $progassignment->completiontime);
        $this->assertEquals(0, $progassignment->completionevent);
        $this->assertEquals(0, $progassignment->completioninstance);

        $progassignment = $DB->get_record('prog_assignment',
            array('programid' => $this->program->id, 'assignmenttype' => ASSIGNTYPE_POSITION, 'assignmenttypeid' => $position3->id));

        $this->assertNotEmpty($progassignment);
        $this->assertEquals(0, $progassignment->includechildren);
        $this->assertEquals(-1, $progassignment->completiontime);
        $this->assertEquals(0, $progassignment->completionevent);
        $this->assertEquals(0, $progassignment->completioninstance);

        // Check records of assigned users.
        foreach ($assignedusers as $user) {
            // Check prog_user_assignment records.
            $proguserassignment = $DB->get_record('prog_user_assignment',
                array('programid' => $this->program->id, 'userid' => $user->id));
            $this->assertNotEmpty($proguserassignment);

            $this->assertGreaterThanOrEqual($timebefore, $proguserassignment->timeassigned);
            $this->assertLessThanOrEqual($timeafter, $proguserassignment->timeassigned);
            $this->assertEquals(0, $proguserassignment->exceptionstatus);

            // Check prog_completion records.
            $progcompletion = $DB->get_record('prog_completion',
                array('programid' => $this->program->id, 'userid' => $user->id));
            $this->assertNotEmpty($progcompletion);

            $this->assertEquals(0, $progcompletion->coursesetid);
            $this->assertEquals(0, $progcompletion->status);
            $this->assertGreaterThanOrEqual($timebefore, $progcompletion->timecreated);
            $this->assertLessThanOrEqual($timeafter, $progcompletion->timecreated);
            $this->assertEquals(0, $progcompletion->timestarted);
            $this->assertEquals(-1, $progcompletion->timedue);
            $this->assertEquals(0, $progcompletion->timecompleted);
        }

        // Check records of users that aren't assigned.
        foreach ($notassignedusers as $user) {
            $proguserassignment = $DB->get_record('prog_user_assignment',
                array('programid' => $this->program->id, 'userid' => $user->id));
            // It should have come back false as no record exists.
            $this->assertFalse($proguserassignment);
        }

        $this->check_control_program();
    }

    /**
     * Assigns users to program via position, then changes learners within that
     * hierarchy and ensures the correct records exist.
     *
     * @throws \Horde\Socket\Client\Exception
     * @throws coding_exception
     */
    public function test_change_in_position() {
        global $DB;

        $generator = $this->getDataGenerator();
        /** @var totara_hierarchy_generator $hierarchy_generator */
        $hierarchy_generator = $generator->get_plugin_generator('totara_hierarchy');
        $positionfw = $hierarchy_generator->create_pos_frame(array());
        $position1 = $hierarchy_generator->create_pos(array('frameworkid' => $positionfw->id, 'fullname' => 'position1'));
        $position2 = $hierarchy_generator->create_pos(array('frameworkid' => $positionfw->id, 'fullname' => 'position2', 'parentid' => $position1->id));
        $position3 = $hierarchy_generator->create_pos(array('frameworkid' => $positionfw->id, 'fullname' => 'position3'));
        $position4 = $hierarchy_generator->create_pos(array('frameworkid' => $positionfw->id, 'fullname' => 'position4', 'parentid' => $position3->id));
        $position5 = $hierarchy_generator->create_pos(array('frameworkid' => $positionfw->id, 'fullname' => 'position5'));

        // Test a user with position assigned via first (sortorder=1) job assignment only.
        $user1 = $this->users[1];
        $user1ja = \totara_job\job_assignment::get_first($user1->id);
        $user1ja->update(array('positionid' => $position1->id));
        // Test a user with the same position assigned via two job assignments.
        $user2 = $this->users[2];
        $user2ja1 = \totara_job\job_assignment::get_first($user2->id);
        $user2ja1->update(array('positionid' => $position1->id));
        $user2ja2 = \totara_job\job_assignment::create_default($user2->id, array('positionid' => $position1->id));
        // Test a user with the position assigned via their second (sortorder=2) job assignment only.
        $user3 = $this->users[3];
        $user3ja1 = \totara_job\job_assignment::get_first($user3->id);
        $user3ja2 = \totara_job\job_assignment::create_default($user3->id, array('positionid' => $position1->id));
        $user4 = $this->users[4];
        // Test a user with a child position.
        $user4ja = \totara_job\job_assignment::get_first($user4->id);
        $user4ja->update(array('positionid' => $position2->id));
        $user5 = $this->users[5];
        $user5ja = \totara_job\job_assignment::get_first($user5->id);
        $user5ja->update(array('positionid' => $position3->id));
        $user6 = $this->users[6];
        // Test a user in child position who is not added to the program.
        $user6ja = \totara_job\job_assignment::get_first($user6->id);
        $user6ja->update(array('positionid' => $position4->id));
        // Test a user with a position that is not add to the program.
        $user7 = $this->users[7];
        $user7ja = \totara_job\job_assignment::get_first($user7->id);
        $user7ja->update(array('positionid' => $position5->id));
        // Test a user that is not been assigned a position.
        $user8 = $this->users[8];
        $user8ja = \totara_job\job_assignment::get_first($user8->id);

        $assignedusers = array($user1, $user2, $user3, $user4, $user5);
        $notassignedusers = array($user6, $user7, $user8);

        // Check we have a clean slate.
        $this->assertEquals(0, $DB->count_records('prog_assignment',
            array('programid' => $this->program->id)));
        $this->assertEquals(0, $DB->count_records('prog_user_assignment',
            array('programid' => $this->program->id)));
        $this->assertEquals(0, $DB->count_records('prog_completion',
            array('programid' => $this->program->id)));

        $timebefore = time();

        // Update_learner_assignments is also run within the assign_to_program generator methods.
        $generator = $this->getDataGenerator();
        $generator->assign_to_program($this->program->id, ASSIGNTYPE_POSITION, $position1->id,
            array('includechildren' => 1));
        $generator->assign_to_program($this->program->id, ASSIGNTYPE_POSITION, $position3->id,
            array('includechildren' => null));

        // Check that records exist.
        $this->assertEquals(2, $DB->count_records('prog_assignment',
            array('programid' => $this->program->id)));
        $this->assertEquals(5, $DB->count_records('prog_user_assignment',
            array('programid' => $this->program->id)));
        $this->assertEquals(5, $DB->count_records('prog_completion',
            array('programid' => $this->program->id)));

        // This should now be in the same state as what we have in test_assigning_manager_hierarchy.
        // So no need to check each record and user.

        // Now change the management hierarchy by adding/removing managers in a couple of job assignments.
        $user5ja->update(array('positionid' => 0));
        $user7ja->update(array('positionid' => $position3->id));

        // Update learner assignments and then we'll check each record is correct.
        $this->program->update_learner_assignments(true);

        $timeafter = time();

        // User7 should now be assigned.
        $assignedusers = array($user1, $user2, $user3, $user4, $user7);
        // User5 should no longer be assigned.
        $notassignedusers = array($user5, $user6, $user8);

        $this->assertEquals(2, $DB->count_records('prog_assignment',
            array('programid' => $this->program->id)));
        $this->assertEquals(5, $DB->count_records('prog_user_assignment',
            array('programid' => $this->program->id)));
        $this->assertEquals(5, $DB->count_records('prog_completion',
            array('programid' => $this->program->id)));

        // Check prog_assignment records.
        $progassignment = $DB->get_record('prog_assignment',
            array('programid' => $this->program->id, 'assignmenttype' => ASSIGNTYPE_POSITION, 'assignmenttypeid' => $position1->id));
        $this->assertNotEmpty($progassignment);
        $this->assertEquals(1, $progassignment->includechildren);
        $this->assertEquals(-1, $progassignment->completiontime);
        $this->assertEquals(0, $progassignment->completionevent);
        $this->assertEquals(0, $progassignment->completioninstance);

        $progassignment = $DB->get_record('prog_assignment',
            array('programid' => $this->program->id, 'assignmenttype' => ASSIGNTYPE_POSITION, 'assignmenttypeid' => $position3->id));

        $this->assertNotEmpty($progassignment);
        $this->assertEquals(0, $progassignment->includechildren);
        $this->assertEquals(-1, $progassignment->completiontime);
        $this->assertEquals(0, $progassignment->completionevent);
        $this->assertEquals(0, $progassignment->completioninstance);

        // Check records of assigned users.
        foreach ($assignedusers as $user) {
            // Check prog_user_assignment records.
            $proguserassignment = $DB->get_record('prog_user_assignment',
                array('programid' => $this->program->id, 'userid' => $user->id));
            $this->assertNotEmpty($proguserassignment, 'prog_user_assignment was empty for ' . $user->firstname);

            $this->assertGreaterThanOrEqual($timebefore, $proguserassignment->timeassigned);
            $this->assertLessThanOrEqual($timeafter, $proguserassignment->timeassigned);
            $this->assertEquals(0, $proguserassignment->exceptionstatus);

            // Check prog_completion records.
            $progcompletion = $DB->get_record('prog_completion',
                array('programid' => $this->program->id, 'userid' => $user->id));
            $this->assertNotEmpty($progcompletion);

            $this->assertEquals(0, $progcompletion->coursesetid);
            $this->assertEquals(0, $progcompletion->status);
            $this->assertGreaterThanOrEqual($timebefore, $progcompletion->timecreated);
            $this->assertLessThanOrEqual($timeafter, $progcompletion->timecreated);
            $this->assertEquals(0, $progcompletion->timestarted);
            $this->assertEquals(-1, $progcompletion->timedue);
            $this->assertEquals(0, $progcompletion->timecompleted);
        }

        // Check records of users that aren't assigned.
        foreach ($notassignedusers as $user) {
            $proguserassignment = $DB->get_record('prog_user_assignment',
                array('programid' => $this->program->id, 'userid' => $user->id));
            // It should have come back false as no record exists.
            $this->assertFalse($proguserassignment);
        }

        $this->check_control_program();
    }

    /**
     * Assign users to a program based on organisation.
     */
    public function test_assigning_organisation() {
        global $DB;

        $generator = $this->getDataGenerator();
        /** @var totara_hierarchy_generator $hierarchy_generator */
        $hierarchy_generator = $generator->get_plugin_generator('totara_hierarchy');
        $organisationfw = $hierarchy_generator->create_org_frame(array());
        $organisation1 = $hierarchy_generator->create_org(array('frameworkid' => $organisationfw->id, 'fullname' => 'organisation1'));
        $organisation2 = $hierarchy_generator->create_org(array('frameworkid' => $organisationfw->id, 'fullname' => 'organisation2', 'parentid' => $organisation1->id));
        $organisation3 = $hierarchy_generator->create_org(array('frameworkid' => $organisationfw->id, 'fullname' => 'organisation3'));
        $organisation4 = $hierarchy_generator->create_org(array('frameworkid' => $organisationfw->id, 'fullname' => 'organisation4', 'parentid' => $organisation3->id));
        $organisation5 = $hierarchy_generator->create_org(array('frameworkid' => $organisationfw->id, 'fullname' => 'organisation5'));

        // Test a user with organisation assigned via first (sortorder=1) job assignment only.
        $user1 = $this->users[1];
        $user1ja = \totara_job\job_assignment::get_first($user1->id);
        $user1ja->update(array('organisationid' => $organisation1->id));
        // Test a user with the same organisation assigned via two job assignments.
        $user2 = $this->users[2];
        $user2ja1 = \totara_job\job_assignment::get_first($user2->id);
        $user2ja1->update(array('organisationid' => $organisation1->id));
        $user2ja2 = \totara_job\job_assignment::create_default($user2->id, array('organisationid' => $organisation1->id));
        // Test a user with the organisation assigned via their second (sortorder=2) job assignment only.
        $user3 = $this->users[3];
        $user3ja1 = \totara_job\job_assignment::get_first($user3->id);
        $user3ja2 = \totara_job\job_assignment::create_default($user3->id, array('organisationid' => $organisation1->id));
        $user4 = $this->users[4];
        // Test a user with a child organisation.
        $user4ja = \totara_job\job_assignment::get_first($user4->id);
        $user4ja->update(array('organisationid' => $organisation2->id));
        $user5 = $this->users[5];
        $user5ja = \totara_job\job_assignment::get_first($user5->id);
        $user5ja->update(array('organisationid' => $organisation3->id));
        $user6 = $this->users[6];
        // Test a user in child organisation who is not added to the program.
        $user6ja = \totara_job\job_assignment::get_first($user6->id);
        $user6ja->update(array('organisationid' => $organisation4->id));
        // Test a user with a organisation that is not add to the program.
        $user7 = $this->users[7];
        $user7ja = \totara_job\job_assignment::get_first($user7->id);
        $user7ja->update(array('organisationid' => $organisation5->id));
        // Test a user that is not been assigned a organisation.
        $user8 = $this->users[8];
        $user8ja = \totara_job\job_assignment::get_first($user8->id);

        $assignedusers = array($user1, $user2, $user3, $user4, $user5);
        $notassignedusers = array($user6, $user7, $user8);

        // Check we have a clean slate.
        $this->assertEquals(0, $DB->count_records('prog_assignment',
            array('programid' => $this->program->id)));
        $this->assertEquals(0, $DB->count_records('prog_user_assignment',
            array('programid' => $this->program->id)));
        $this->assertEquals(0, $DB->count_records('prog_completion',
            array('programid' => $this->program->id)));

        $timebefore = time();

        // Update_learner_assignments is also run within the assign_to_program generator methods.
        $generator = $this->getDataGenerator();
        $generator->assign_to_program($this->program->id, ASSIGNTYPE_ORGANISATION, $organisation1->id,
            array('includechildren' => 1));
        $generator->assign_to_program($this->program->id, ASSIGNTYPE_ORGANISATION, $organisation3->id,
            array('includechildren' => null));

        $timeafter = time();

        // Check that records exist.
        $this->assertEquals(2, $DB->count_records('prog_assignment',
            array('programid' => $this->program->id)));
        $this->assertEquals(5, $DB->count_records('prog_user_assignment',
            array('programid' => $this->program->id)));
        $this->assertEquals(5, $DB->count_records('prog_completion',
            array('programid' => $this->program->id)));

        // Check prog_assignment records.
        $progassignment = $DB->get_record('prog_assignment',
            array('programid' => $this->program->id, 'assignmenttype' => ASSIGNTYPE_ORGANISATION, 'assignmenttypeid' => $organisation1->id));
        $this->assertNotEmpty($progassignment);
        $this->assertEquals(1, $progassignment->includechildren);
        $this->assertEquals(-1, $progassignment->completiontime);
        $this->assertEquals(0, $progassignment->completionevent);
        $this->assertEquals(0, $progassignment->completioninstance);

        $progassignment = $DB->get_record('prog_assignment',
            array('programid' => $this->program->id, 'assignmenttype' => ASSIGNTYPE_ORGANISATION, 'assignmenttypeid' => $organisation3->id));

        $this->assertNotEmpty($progassignment);
        $this->assertEquals(0, $progassignment->includechildren);
        $this->assertEquals(-1, $progassignment->completiontime);
        $this->assertEquals(0, $progassignment->completionevent);
        $this->assertEquals(0, $progassignment->completioninstance);

        // Check records of assigned users.
        foreach ($assignedusers as $user) {
            // Check prog_user_assignment records.
            $proguserassignment = $DB->get_record('prog_user_assignment',
                array('programid' => $this->program->id, 'userid' => $user->id));
            $this->assertNotEmpty($proguserassignment);

            $this->assertGreaterThanOrEqual($timebefore, $proguserassignment->timeassigned);
            $this->assertLessThanOrEqual($timeafter, $proguserassignment->timeassigned);
            $this->assertEquals(0, $proguserassignment->exceptionstatus);

            // Check prog_completion records.
            $progcompletion = $DB->get_record('prog_completion',
                array('programid' => $this->program->id, 'userid' => $user->id));
            $this->assertNotEmpty($progcompletion);

            $this->assertEquals(0, $progcompletion->coursesetid);
            $this->assertEquals(0, $progcompletion->status);
            $this->assertGreaterThanOrEqual($timebefore, $progcompletion->timecreated);
            $this->assertLessThanOrEqual($timeafter, $progcompletion->timecreated);
            $this->assertEquals(0, $progcompletion->timestarted);
            $this->assertEquals(-1, $progcompletion->timedue);
            $this->assertEquals(0, $progcompletion->timecompleted);
        }

        // Check records of users that aren't assigned.
        foreach ($notassignedusers as $user) {
            $proguserassignment = $DB->get_record('prog_user_assignment',
                array('programid' => $this->program->id, 'userid' => $user->id));
            // It should have come back false as no record exists.
            $this->assertFalse($proguserassignment);
        }

        $this->check_control_program();
    }

    /**
     * Assigns users to program via organisation, then changes learners within that
     * hierarchy and ensures the correct records exist.
     *
     * @throws \Horde\Socket\Client\Exception
     * @throws coding_exception
     */
    public function test_change_in_organisation() {
        global $DB;

        $generator = $this->getDataGenerator();
        /** @var totara_hierarchy_generator $hierarchy_generator */
        $hierarchy_generator = $generator->get_plugin_generator('totara_hierarchy');
        $organisationfw = $hierarchy_generator->create_org_frame(array());
        $organisation1 = $hierarchy_generator->create_org(array('frameworkid' => $organisationfw->id, 'fullname' => 'organisation1'));
        $organisation2 = $hierarchy_generator->create_org(array('frameworkid' => $organisationfw->id, 'fullname' => 'organisation2', 'parentid' => $organisation1->id));
        $organisation3 = $hierarchy_generator->create_org(array('frameworkid' => $organisationfw->id, 'fullname' => 'organisation3'));
        $organisation4 = $hierarchy_generator->create_org(array('frameworkid' => $organisationfw->id, 'fullname' => 'organisation4', 'parentid' => $organisation3->id));
        $organisation5 = $hierarchy_generator->create_org(array('frameworkid' => $organisationfw->id, 'fullname' => 'organisation5'));

        // Test a user with organisation assigned via first (sortorder=1) job assignment only.
        $user1 = $this->users[1];
        $user1ja = \totara_job\job_assignment::get_first($user1->id);
        $user1ja->update(array('organisationid' => $organisation1->id));
        // Test a user with the same organisation assigned via two job assignments.
        $user2 = $this->users[2];
        $user2ja1 = \totara_job\job_assignment::get_first($user2->id);
        $user2ja1->update(array('organisationid' => $organisation1->id));
        $user2ja2 = \totara_job\job_assignment::create_default($user2->id, array('organisationid' => $organisation1->id));
        // Test a user with the organisation assigned via their second (sortorder=2) job assignment only.
        $user3 = $this->users[3];
        $user3ja1 = \totara_job\job_assignment::get_first($user3->id);
        $user3ja2 = \totara_job\job_assignment::create_default($user3->id, array('organisationid' => $organisation1->id));
        $user4 = $this->users[4];
        // Test a user with a child organisation.
        $user4ja = \totara_job\job_assignment::get_first($user4->id);
        $user4ja->update(array('organisationid' => $organisation2->id));
        $user5 = $this->users[5];
        $user5ja = \totara_job\job_assignment::get_first($user5->id);
        $user5ja->update(array('organisationid' => $organisation3->id));
        $user6 = $this->users[6];
        // Test a user in child organisation who is not added to the program.
        $user6ja = \totara_job\job_assignment::get_first($user6->id);
        $user6ja->update(array('organisationid' => $organisation4->id));
        // Test a user with a organisation that is not add to the program.
        $user7 = $this->users[7];
        $user7ja = \totara_job\job_assignment::get_first($user7->id);
        $user7ja->update(array('organisationid' => $organisation5->id));
        // Test a user that is not been assigned a organisation.
        $user8 = $this->users[8];
        $user8ja = \totara_job\job_assignment::get_first($user8->id);

        $assignedusers = array($user1, $user2, $user3, $user4, $user5);
        $notassignedusers = array($user6, $user7, $user8);

        // Check we have a clean slate.
        $this->assertEquals(0, $DB->count_records('prog_assignment',
            array('programid' => $this->program->id)));
        $this->assertEquals(0, $DB->count_records('prog_user_assignment',
            array('programid' => $this->program->id)));
        $this->assertEquals(0, $DB->count_records('prog_completion',
            array('programid' => $this->program->id)));

        $timebefore = time();

        // Update_learner_assignments is also run within the assign_to_program generator methods.
        $generator = $this->getDataGenerator();
        $generator->assign_to_program($this->program->id, ASSIGNTYPE_ORGANISATION, $organisation1->id,
            array('includechildren' => 1));
        $generator->assign_to_program($this->program->id, ASSIGNTYPE_ORGANISATION, $organisation3->id,
            array('includechildren' => null));

        // Check that records exist.
        $this->assertEquals(2, $DB->count_records('prog_assignment',
            array('programid' => $this->program->id)));
        $this->assertEquals(5, $DB->count_records('prog_user_assignment',
            array('programid' => $this->program->id)));
        $this->assertEquals(5, $DB->count_records('prog_completion',
            array('programid' => $this->program->id)));

        // This should now be in the same state as what we have in test_assigning_manager_hierarchy.
        // So no need to check each record and user.

        // Now change the management hierarchy by adding/removing managers in a couple of job assignments.
        $user5ja->update(array('organisationid' => 0));
        $user7ja->update(array('organisationid' => $organisation3->id));

        // Update learner assignments and then we'll check each record is correct.
        $this->program->update_learner_assignments(true);

        $timeafter = time();

        // User7 should now be assigned.
        $assignedusers = array($user1, $user2, $user3, $user4, $user7);
        // User5 should no longer be assigned.
        $notassignedusers = array($user5, $user6, $user8);

        $this->assertEquals(2, $DB->count_records('prog_assignment',
            array('programid' => $this->program->id)));
        $this->assertEquals(5, $DB->count_records('prog_user_assignment',
            array('programid' => $this->program->id)));
        $this->assertEquals(5, $DB->count_records('prog_completion',
            array('programid' => $this->program->id)));

        // Check prog_assignment records.
        $progassignment = $DB->get_record('prog_assignment',
            array('programid' => $this->program->id, 'assignmenttype' => ASSIGNTYPE_ORGANISATION, 'assignmenttypeid' => $organisation1->id));
        $this->assertNotEmpty($progassignment);
        $this->assertEquals(1, $progassignment->includechildren);
        $this->assertEquals(-1, $progassignment->completiontime);
        $this->assertEquals(0, $progassignment->completionevent);
        $this->assertEquals(0, $progassignment->completioninstance);

        $progassignment = $DB->get_record('prog_assignment',
            array('programid' => $this->program->id, 'assignmenttype' => ASSIGNTYPE_ORGANISATION, 'assignmenttypeid' => $organisation3->id));

        $this->assertNotEmpty($progassignment);
        $this->assertEquals(0, $progassignment->includechildren);
        $this->assertEquals(-1, $progassignment->completiontime);
        $this->assertEquals(0, $progassignment->completionevent);
        $this->assertEquals(0, $progassignment->completioninstance);

        // Check records of assigned users.
        foreach ($assignedusers as $user) {
            // Check prog_user_assignment records.
            $proguserassignment = $DB->get_record('prog_user_assignment',
                array('programid' => $this->program->id, 'userid' => $user->id));
            $this->assertNotEmpty($proguserassignment, 'prog_user_assignment was empty for ' . $user->firstname);

            $this->assertGreaterThanOrEqual($timebefore, $proguserassignment->timeassigned);
            $this->assertLessThanOrEqual($timeafter, $proguserassignment->timeassigned);
            $this->assertEquals(0, $proguserassignment->exceptionstatus);

            // Check prog_completion records.
            $progcompletion = $DB->get_record('prog_completion',
                array('programid' => $this->program->id, 'userid' => $user->id));
            $this->assertNotEmpty($progcompletion);

            $this->assertEquals(0, $progcompletion->coursesetid);
            $this->assertEquals(0, $progcompletion->status);
            $this->assertGreaterThanOrEqual($timebefore, $progcompletion->timecreated);
            $this->assertLessThanOrEqual($timeafter, $progcompletion->timecreated);
            $this->assertEquals(0, $progcompletion->timestarted);
            $this->assertEquals(-1, $progcompletion->timedue);
            $this->assertEquals(0, $progcompletion->timecompleted);
        }

        // Check records of users that aren't assigned.
        foreach ($notassignedusers as $user) {
            $proguserassignment = $DB->get_record('prog_user_assignment',
                array('programid' => $this->program->id, 'userid' => $user->id));
            // It should have come back false as no record exists.
            $this->assertFalse($proguserassignment);
        }

        $this->check_control_program();
    }

    /**
     * First complete users in the courses, then assign the users to the program. They should immediately
     * be marked complete.
     *
     * This test is based on test_assigning_individuals.
     */
    public function test_already_complete_when_assigned() {
        global $DB;

        $timebefore = time();

        // Create users and courses.
        $user0 = $this->users[0];
        $user1 = $this->users[1];
        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();

        // Assign courses to program. Reload program after changing it.
        $this->getDataGenerator()->add_courseset_program($this->program->id, array($course1->id, $course2->id));
        $this->program = new program($this->program->id);

        // Assign users to courses.
        $this->getDataGenerator()->enrol_user($user0->id, $course1->id);
        $this->getDataGenerator()->enrol_user($user1->id, $course2->id);

        // Complete courses.
        $completion = new completion_completion(array('userid' => $user0->id, 'course' => $course1->id));
        $completion->mark_complete($timebefore);
        $completion = new completion_completion(array('userid' => $user0->id, 'course' => $course2->id));
        $completion->mark_complete($timebefore);
        $completion = new completion_completion(array('userid' => $user1->id, 'course' => $course1->id));
        $completion->mark_complete($timebefore);
        $completion = new completion_completion(array('userid' => $user1->id, 'course' => $course2->id));
        $completion->mark_complete($timebefore);

        // Add individual assignments.
        $this->set_individual_assignments($this->program, array($user0, $user1));

        // Check that records exist.
        $this->assertEquals(2, $DB->count_records('prog_assignment',
            array('programid' => $this->program->id)));
        $this->assertEquals(0, $DB->count_records('prog_user_assignment',
            array('programid' => $this->program->id)));
        $this->assertEquals(0, $DB->count_records('prog_completion', // See here that the users aren't already complete!
            array('programid' => $this->program->id)));

        // Apply assignment changes.
        $this->program->update_learner_assignments(true);

        $timeafter = time();

        // Check that records exist.
        $this->assertEquals(2, $DB->count_records('prog_assignment',
            array('programid' => $this->program->id)));
        $this->assertEquals(2, $DB->count_records('prog_user_assignment',
            array('programid' => $this->program->id)));
        $this->assertEquals(2, $DB->count_records('prog_completion', // Completion records exist, check their status below.
            array('programid' => $this->program->id, 'coursesetid' => 0)));
        $this->assertEquals(4, $DB->count_records('prog_completion', // Course set completion records were also created.
            array('programid' => $this->program->id)));

        // Check prog_assignment records.
        $progassignment0 = $DB->get_record('prog_assignment',
            array('programid' => $this->program->id, 'assignmenttype' => 5, 'assignmenttypeid' => $user0->id));
        $this->assertNotEmpty($progassignment0);

        $progassignment1 = $DB->get_record('prog_assignment',
            array('programid' => $this->program->id, 'assignmenttype' => 5, 'assignmenttypeid' => $user1->id));
        $this->assertNotEmpty($progassignment1);

        $this->assertEquals(0, $progassignment0->includechildren);
        $this->assertEquals(0, $progassignment1->includechildren);
        $this->assertEquals(-1, $progassignment0->completiontime);
        $this->assertEquals(-1, $progassignment1->completiontime);
        $this->assertEquals(0, $progassignment0->completionevent);
        $this->assertEquals(0, $progassignment1->completionevent);
        $this->assertEquals(0, $progassignment0->completioninstance);
        $this->assertEquals(0, $progassignment1->completioninstance);

        // Check prog_user_assignment records.
        $proguserassignment0 = $DB->get_record('prog_user_assignment',
            array('programid' => $this->program->id, 'userid' => $user0->id));
        $this->assertNotEmpty($proguserassignment0);

        $proguserassignment1 = $DB->get_record('prog_user_assignment',
            array('programid' => $this->program->id, 'userid' => $user1->id));
        $this->assertNotEmpty($proguserassignment1);

        $this->assertEquals($progassignment0->id, $proguserassignment0->assignmentid);
        $this->assertEquals($progassignment1->id, $proguserassignment1->assignmentid);
        $this->assertGreaterThanOrEqual($timebefore, $proguserassignment0->timeassigned);
        $this->assertGreaterThanOrEqual($timebefore, $proguserassignment1->timeassigned);
        $this->assertLessThanOrEqual($timeafter, $proguserassignment0->timeassigned);
        $this->assertLessThanOrEqual($timeafter, $proguserassignment1->timeassigned);
        $this->assertEquals(0, $proguserassignment0->exceptionstatus);
        $this->assertEquals(0, $proguserassignment1->exceptionstatus);

        // Check prog_completion course set 0 records.
        $progcompletion0 = $DB->get_record('prog_completion',
            array('programid' => $this->program->id, 'userid' => $user0->id, 'coursesetid' => 0));
        $this->assertNotEmpty($progcompletion0);

        $progcompletion1 = $DB->get_record('prog_completion',
            array('programid' => $this->program->id, 'userid' => $user1->id, 'coursesetid' => 0));
        $this->assertNotEmpty($progcompletion1);

        $this->assertEquals(0, $progcompletion0->coursesetid);
        $this->assertEquals(0, $progcompletion1->coursesetid);
        $this->assertEquals(STATUS_PROGRAM_COMPLETE, $progcompletion0->status);
        $this->assertEquals(STATUS_PROGRAM_COMPLETE, $progcompletion1->status);
        $this->assertGreaterThanOrEqual($timebefore, $progcompletion0->timestarted);
        $this->assertGreaterThanOrEqual($timebefore, $progcompletion1->timestarted);
        $this->assertLessThanOrEqual($timeafter, $progcompletion0->timestarted);
        $this->assertLessThanOrEqual($timeafter, $progcompletion1->timestarted);
        $this->assertEquals(-1, $progcompletion0->timedue);
        $this->assertEquals(-1, $progcompletion1->timedue);
        $this->assertGreaterThanOrEqual($timebefore, $progcompletion0->timecompleted);
        $this->assertGreaterThanOrEqual($timebefore, $progcompletion1->timecompleted);
        $this->assertLessThanOrEqual($timeafter, $progcompletion0->timecompleted);
        $this->assertLessThanOrEqual($timeafter, $progcompletion1->timecompleted);

        // Check prog_completion course set non-0 records.
        $sql = "SELECT pc.*
                  FROM {prog_completion} pc
                 WHERE pc.programid = :programid
                   AND pc.userid = :userid
                   AND coursesetid != 0";
        $progcoursesetcompletion0 = $DB->get_record_sql($sql, array('programid' => $this->program->id, 'userid' => $user0->id));
        $this->assertNotEmpty($progcoursesetcompletion0);
        $progcoursesetcompletion1 = $DB->get_record_sql($sql, array('programid' => $this->program->id, 'userid' => $user1->id));
        $this->assertNotEmpty($progcoursesetcompletion1);

        $this->assertNotEquals(0, $progcoursesetcompletion0->coursesetid);
        $this->assertNotEquals(0, $progcoursesetcompletion1->coursesetid);
        $this->assertEquals(STATUS_COURSESET_COMPLETE, $progcoursesetcompletion0->status);
        $this->assertEquals(STATUS_COURSESET_COMPLETE, $progcoursesetcompletion1->status);
        $this->assertGreaterThanOrEqual($timebefore, $progcoursesetcompletion0->timecompleted);
        $this->assertGreaterThanOrEqual($timebefore, $progcoursesetcompletion1->timecompleted);
        $this->assertLessThanOrEqual($timeafter, $progcoursesetcompletion0->timecompleted);
        $this->assertLessThanOrEqual($timeafter, $progcoursesetcompletion1->timecompleted);

        $this->check_control_program();
    }

    /**
     * When exceptions occur immediately on assignment, the timedue will be COMPLETION_TIME_NOT_SET. A bug was causing invalid
     * timedues to be put on prog_completion records which had exceptions. This test prevents that problem from coming back.
     */
    public function test_exception_results_in_valid_timedue() {
        global $DB;

        // Assign a user with a completion time relative to a program that they are not in, which should result in an exception.
        $user = $this->getDataGenerator()->create_user();
        $otherprogid = -1;
        $this->set_individual_assignments($this->program, array($user), '1 day', COMPLETION_EVENT_PROGRAM_COMPLETION, $otherprogid);

        // Apply assignment changes.
        $this->program->update_learner_assignments(true);

        // Make sure the exception was generated.
        $this->assertCount(1, $DB->get_records('prog_exception'));

        // Make sure that the program completion record is still valid.
        $progcompletion = prog_load_completion($this->program->id, $user->id);
        $this->assertEmpty(prog_get_completion_errors($progcompletion));
        $this->assertEquals(COMPLETION_TIME_NOT_SET, $progcompletion->timedue);
    }

    /*
     * Other things that could be tested, although some may be better tested seperately (or may be already) as they
     * may be things used by update_learner_assignments rather than things that it does itself.
     *      - Extensions
     *      - Future assignments
     *      - make_timedue()
     *      - Program unassignment messages
     *      - Exceptions
     *      - Pos/org assignments
     */
}
