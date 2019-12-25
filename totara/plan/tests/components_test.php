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
 * @author Alastair Munro <alastair.munro@totaralms.com>
 * @package totara_plan
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

/**
 * To test, run this from the command line from the $CFG->dirroot.
 * vendor/bin/phpunit --verbose totara_plan_components_testcase totara/plan/tests/components_test.php
 */
class totara_plan_components_testcase extends advanced_testcase {
    /** @var phpunit_message_sink $messagesink */
    private $messagesink;

    /** @var testing_data_generator */
    private $data_generator;

    /** @var totara_plan_generator */
    private $plan_generator;

    /** @var  totara_program_generator */
    private $program_generator;

    protected function setUp() {
        parent::setUp();
        $this->resetAfterTest();
        $this->messagesink = $this->redirectMessages();
        $this->data_generator = $this->getDataGenerator();
        $this->plan_generator = $this->data_generator->get_plugin_generator('totara_plan');
        $this->program_generator = $this->data_generator->get_plugin_generator('totara_program');
    }

    protected function tearDown() {
        $this->messagesink->clear();
        $this->messagesink->close();
        $this->messagesink = null;
        $this->data_generator = null;
        $this->plan_generator = null;
        $this->program_generator = null;
        parent::tearDown();
    }

    /**
     * Test get_assigned_items for a competency.
     */
    public function test_competency_get_assigned_items() {
        // Create a user.
        $user = $this->getDataGenerator()->create_user();

        $plangenerator = $this->getDataGenerator()->get_plugin_generator('totara_plan');
        $planrecord = $plangenerator->create_learning_plan(array('userid' => $user->id));
        $plan = new development_plan($planrecord->id);

        // The plan should have no items.
        $this->assertCount(0, $plan->get_assigned_items());

        // There should be no competencies assigned either.
        $competencycomponent = $plan->get_component('competency');
        $this->assertCount(0, $competencycomponent->get_assigned_items());

        $hierarchygenerator = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');
        $competencyframework = $hierarchygenerator->create_framework('competency');

        // Create some competencies.
        $competency1 = $hierarchygenerator->create_hierarchy($competencyframework->id, 'competency', array('fullname' => 'Competency 1'));
        $competency2 = $hierarchygenerator->create_hierarchy($competencyframework->id, 'competency', array('fullname' => 'Competency 2'));

        // Stupid access control.
        $this->setAdminUser();

        $plangenerator->add_learning_plan_competency($plan->id, $competency1->id);
        $plangenerator->add_learning_plan_competency($plan->id, $competency2->id);

        $assignedcompetencies = $competencycomponent->get_assigned_items();

        // The plan should now have 2 items.
        $this->assertCount(2, $assignedcompetencies);
    }


    /**
     * Test get_assigned_items for a course.
     */
    public function test_course_get_assigned_items() {
        $datagenerator = $this->getDataGenerator();

        // Create a user.
        $user = $datagenerator->create_user();

        $plangenerator = $datagenerator->get_plugin_generator('totara_plan');
        $planrecord = $plangenerator->create_learning_plan(array('userid' => $user->id));
        $plan = new development_plan($planrecord->id);

        // The plan should have no items.
        $this->assertCount(0, $plan->get_assigned_items());

        // There should be no competencies assigned either.
        $coursecomponent = $plan->get_component('course');
        $this->assertCount(0, $coursecomponent->get_assigned_items());

        // Create some courses.
        $course1 = $datagenerator->create_course();
        $course2 = $datagenerator->create_course();
        $course3 = $datagenerator->create_course();

        $this->setAdminUser();

        $result = $plangenerator->add_learning_plan_course($plan->id, $course1->id);
        $result = $plangenerator->add_learning_plan_course($plan->id, $course2->id);
        $result = $plangenerator->add_learning_plan_course($plan->id, $course3->id);

        $assignedcourses = $coursecomponent->get_assigned_items();

        // The plan should now have 3 courses assigned.
        $this->assertCount(3, $assignedcourses);
    }


    /**
     * Test get_assigned_items for objectives.
     */
    public function test_objective_get_assigned_items() {
        $datagenerator = $this->getDataGenerator();

        // Create a user.
        $user = $datagenerator->create_user();

        $plangenerator = $datagenerator->get_plugin_generator('totara_plan');
        $planrecord = $plangenerator->create_learning_plan(array('userid' => $user->id));
        $plan = new development_plan($planrecord->id);

        // The plan should have no items.
        $this->assertCount(0, $plan->get_assigned_items());

        // There should be no competencies assigned either.
        $objectivecomponent = $plan->get_component('objective');
        $this->assertCount(0, $objectivecomponent->get_assigned_items());

        // Stupid access control.
        $this->setAdminUser();

        // Add objectives to plan.
        $objective1 = $plangenerator->create_learning_plan_objective($plan->id, 2);
        $objective2 = $plangenerator->create_learning_plan_objective($plan->id, 2);

        $assignedobjectives = $objectivecomponent->get_assigned_items();

        // The plan should have 2 objectives assigned.
        $this->assertCount(2, $assignedobjectives);
    }


    /**
     * Test get_assigned_items for a programs assigned to a plan.
     */
    public function test_program_get_assigned_items() {
        $datagenerator = $this->getDataGenerator();

        // Create a user.
        $user = $datagenerator->create_user();

        $plangenerator = $datagenerator->get_plugin_generator('totara_plan');
        $planrecord = $plangenerator->create_learning_plan(array('userid' => $user->id));
        $plan = new development_plan($planrecord->id);

        // The plan should have no items.
        $this->assertCount(0, $plan->get_assigned_items());

        $programcomponent = $plan->get_component('program');
        $this->assertCount(0, $programcomponent->get_assigned_items());

        $programgenerator = $datagenerator->get_plugin_generator('totara_program');
        $program1 = $programgenerator->create_program();
        $program2 = $programgenerator->create_program();

        // Stupid access control.
        $this->setAdminUser();

        // Add program to plan.
        $plangenerator->add_learning_plan_program($plan->id, $program1->id);
        $plangenerator->add_learning_plan_program($plan->id, $program2->id);

        $assignedprograms = $programcomponent->get_assigned_items();

        // The plan should have 1 program assigned.
        $this->assertCount(2, $assignedprograms);
    }


    /**
     * Test get_assigned_items with linked items for the competencies component.
     */
    public function test_get_assigned_competencies_linked() {
        global $DB, $CFG;

        require_once($CFG->dirroot.'/totara/plan/components/evidence/evidence.class.php');

        $datagenerator = $this->getDataGenerator();

        // Create a user.
        $user = $datagenerator->create_user();

        $hierarchygenerator = $datagenerator->get_plugin_generator('totara_hierarchy');
        $competencyframework = $hierarchygenerator->create_framework('competency');

        // Create a competency.
        $competency1 = $hierarchygenerator->create_hierarchy($competencyframework->id, 'competency', array('fullname' => 'Competency 1'));

        // Create some courses.
        $course1 = $datagenerator->create_course();
        $course2 = $datagenerator->create_course();

        // Add Some evidence.
        $data = array('userid' => $user->id);
        $plangenerator = $datagenerator->get_plugin_generator('totara_plan');
        $evidence1 = $plangenerator->create_evidence($data);
        $evidence2 = $plangenerator->create_evidence($data);
        $evidence3 = $plangenerator->create_evidence($data);
        $evidence4 = $plangenerator->create_evidence($data);

        $hierarchygenerator->assign_linked_course_to_competency($competency1, $course1);
        $hierarchygenerator->assign_linked_course_to_competency($competency1, $course2);

        // Competency should have 2 linked courses.
        $this->assertCount(2, $DB->get_records('comp_criteria', array('competencyid' => $competency1->id)));

        // Create a learning plan.
        $plangenerator = $datagenerator->get_plugin_generator('totara_plan');
        $planrecord = $plangenerator->create_learning_plan(array('userid' => $user->id));
        $plan = new development_plan($planrecord->id);

        // Stupid access control.
        $this->setAdminUser();

        // Get competency component and check there are no assigned items.
        $competencycomponent = $plan->get_component('competency');
        $this->assertCount(0, $competencycomponent->get_assigned_items());

        // Get the course component nd check there are no assigned items.
        $coursecomponent = $plan->get_component('course');
        $this->assertCount(0, $coursecomponent->get_assigned_items());

        // Add competency to plan.
        $result = $plangenerator->add_learning_plan_competency($plan->id, $competency1->id);

        // Get plan assignment IDs.
        $competencyassignments = $DB->get_records('dp_plan_competency_assign', array('planid' => $plan->id), '', 'competencyid, id');

        // Add linked evidence.
        $evidence = new dp_evidence_relation($plan->id, 'competency', $competencyassignments[$competency1->id]->id);
        $evidence->update_linked_evidence(array($evidence1->id, $evidence2->id, $evidence3->id, $evidence4->id));

        // Check that competency was assigned.
        $assignedcomps = $competencycomponent->get_assigned_items();
        $this->assertCount(1, $assignedcomps);
        $assignedcompetency = reset($assignedcomps);
        $this->assertObjectNotHasAttribute('linkedcourses', $assignedcompetency);
        $this->assertObjectNotHasAttribute('linkedevidence', $assignedcompetency);

        // Check the linked courses were also assigned.
        $assignedcourses = $coursecomponent->get_assigned_items();
        $this->assertCount(2, $assignedcourses);

        // Check linked counts.
        $assignedcomps = $competencycomponent->get_assigned_items(null, '', '', '', true);
        $assignedcompetency = reset($assignedcomps);
        $this->assertObjectHasAttribute('linkedcourses', $assignedcompetency);
        $this->assertEquals(2, $assignedcompetency->linkedcourses);
        $this->assertObjectHasAttribute('linkedevidence', $assignedcompetency);
        $this->assertEquals(4, $assignedcompetency->linkedevidence);
    }


    /**
     * Test get_assigned_items with linked items for courses component.
     */
    public function test_get_assigned_courses_linked() {
        global $DB, $CFG;

        require_once($CFG->dirroot.'/totara/plan/components/evidence/evidence.class.php');

        $datagenerator = $this->getDataGenerator();

        // Create a user.
        $user = $datagenerator->create_user();

        $hierarchygenerator = $datagenerator->get_plugin_generator('totara_hierarchy');
        $competencyframework = $hierarchygenerator->create_framework('competency');

        // Create a competency.
        $competency1 = $hierarchygenerator->create_hierarchy($competencyframework->id, 'competency', array('fullname' => 'Competency 1'));
        $competency2 = $hierarchygenerator->create_hierarchy($competencyframework->id, 'competency', array('fullname' => 'Competency 2'));

        // Add Some evidence.
        $data = array('userid' => $user->id);
        $plangenerator = $datagenerator->get_plugin_generator('totara_plan');
        $evidence1 = $plangenerator->create_evidence($data);
        $evidence2 = $plangenerator->create_evidence($data);
        $evidence3 = $plangenerator->create_evidence($data);

        // Create a plan.
        $planrecord = $plangenerator->create_learning_plan(array('userid' => $user->id));
        $plan = new development_plan($planrecord->id);

        $course1 = $datagenerator->create_course();

        $this->setAdminUser();

        $plangenerator->add_learning_plan_course($plan->id, $course1->id);

        $plangenerator->add_learning_plan_competency($plan->id, $competency1->id);
        $plangenerator->add_learning_plan_competency($plan->id, $competency2->id);

        // Get components.
        $coursecomponent = $plan->get_component('course');
        $competencycomponent = $plan->get_component('competency');

        // Get plan assignment IDs.
        $competencyassignments = $DB->get_records('dp_plan_competency_assign', array('planid' => $plan->id), '', 'competencyid, id');
        $courseassignments = $DB->get_records('dp_plan_course_assign', array('planid' => $plan->id), '', 'courseid, id');

        $data = array($competencyassignments[$competency1->id]->id, $competencyassignments[$competency2->id]->id);
        $coursecomponent->update_linked_components($courseassignments[$course1->id]->id, 'competency', $data);

        // Add linked evidence.
        $evidence = new dp_evidence_relation($plan->id, 'course', $courseassignments[$course1->id]->id);
        $evidence->update_linked_evidence(array($evidence1->id, $evidence2->id, $evidence3->id));

        // Check assigned courses.
        $assignedcourses = $coursecomponent->get_assigned_items();
        $this->assertCount(1, $assignedcourses);

        // Check assigned competencies.
        $assignedcompetencies = $competencycomponent->get_assigned_items();
        $this->assertCount(2, $assignedcompetencies);

        $assignedcourse = reset($assignedcourses);
        $this->assertObjectNotHasAttribute('linkedcompetencies', $assignedcourse);
        $this->assertObjectNotHasAttribute('linkedevidence', $assignedcourse);

        // Check linked counts are correct.
        $assignedcourses = $coursecomponent->get_assigned_items(null, '', '', '', true);
        // Get first (and only item of the array to check).
        $assignedcourse = reset($assignedcourses);
        $this->assertObjectHasAttribute('linkedcompetencies', $assignedcourse);
        $this->assertEquals(2, $assignedcourse->linkedcompetencies);

        // Check linked evidence count.
        $this->assertObjectHasAttribute('linkedevidence', $assignedcourse);
        $this->assertEquals(3, $assignedcourse->linkedevidence);
    }

    /**
     * Test get_assigned_items with linked items for the objectives component.
     */
    public function test_get_assigned_objectives_linked() {
        global $DB, $CFG;

        require_once($CFG->dirroot.'/totara/plan/components/evidence/evidence.class.php');

        $datagenerator = $this->getDataGenerator();

        // Create a user.
        $user = $datagenerator->create_user();

        // Create a plan.
        $plangenerator = $datagenerator->get_plugin_generator('totara_plan');
        $planrecord = $plangenerator->create_learning_plan(array('userid' => $user->id));
        $plan = new development_plan($planrecord->id);

        $course1 = $datagenerator->create_course();
        $course2 = $datagenerator->create_course();
        $course3 = $datagenerator->create_course();

        $this->setAdminUser();

        // Add courses to plan.
        $plangenerator->add_learning_plan_course($plan->id, $course1->id);
        $plangenerator->add_learning_plan_course($plan->id, $course2->id);
        $plangenerator->add_learning_plan_course($plan->id, $course3->id);

        // Get components.
        $coursecomponent = $plan->get_component('course');
        $objectivecomponent = $plan->get_component('objective');

        // Check that courses are assigned.
        $assignedcourses = $coursecomponent->get_assigned_items();
        $this->assertCount(3, $assignedcourses);

        // Create an objective.
        $objective1 = $plangenerator->create_learning_plan_objective($plan->id, 2);

        // Get plan assignment IDs.
        $courseassignments = $DB->get_records('dp_plan_course_assign', array('planid' => $plan->id), '', 'courseid, id');

        // Link courses to objective.
        $data = array($courseassignments[$course1->id]->id, $courseassignments[$course2->id]->id, $courseassignments[$course3->id]->id);
        $objectivecomponent->update_linked_components($objective1->id, 'course', $data);

        // Create some evidence.
        $evidencedata = array('userid' => $user->id);
        $evidence1 = $plangenerator->create_evidence($evidencedata);
        $evidence2 = $plangenerator->create_evidence($evidencedata);

        // Assign evidence to objective.
        $evidence = new dp_evidence_relation($plan->id, 'objective', $objective1->id);
        $evidence->update_linked_evidence(array($evidence1->id, $evidence2->id));

        // Check objective is created.
        $assignedobjectives = $objectivecomponent->get_assigned_items();
        $this->assertCount(1, $assignedobjectives);

        // Check that linked counts are not included.
        $assignedobjective = reset($assignedobjectives);
        $this->assertObjectNotHasAttribute('linkedcourses', $assignedobjective);
        $this->assertObjectNotHasAttribute('linkedevidence', $assignedobjective);

        // Check linked course count.
        $assignedobjectives = $objectivecomponent->get_assigned_items(null, '', '', '', true);
        $assignedobjective = reset($assignedobjectives);
        $this->assertObjectHasAttribute('linkedcourses', $assignedobjective);
        $this->assertEquals(3, $assignedobjective->linkedcourses);

        // Check linked evidence count.
        $this->assertObjectHasAttribute('linkedevidence', $assignedobjective);
        $this->assertEquals(2, $assignedobjective->linkedevidence);
    }

    /**
     * Test get_assigned_items with linked items for the programs component.
     */
    public function test_get_assigned_programs_linked() {
        global $DB, $CFG;

        require_once($CFG->dirroot.'/totara/plan/components/evidence/evidence.class.php');

        $datagenerator = $this->getDataGenerator();

        // Create a user.
        $user = $datagenerator->create_user();

        // Create a plan.
        $plangenerator = $datagenerator->get_plugin_generator('totara_plan');
        $planrecord = $plangenerator->create_learning_plan(array('userid' => $user->id));
        $plan = new development_plan($planrecord->id);

        // Create some evidence.
        $evidencedata = array('userid' => $user->id);
        $evidence1 = $plangenerator->create_evidence($evidencedata);
        $evidence2 = $plangenerator->create_evidence($evidencedata);

        $programgenerator = $datagenerator->get_plugin_generator('totara_program');
        $program1 = $programgenerator->create_program();

        // Stupid access control.
        $this->setAdminUser();

        // Add program to plan.
        $plangenerator->add_learning_plan_program($plan->id, $program1->id);

        // Get program component.
        $programcomponent = $plan->get_component('program');

        // Get plan assignment IDs.
        $programassignments = $DB->get_records('dp_plan_program_assign', array('planid' => $plan->id), '', 'programid, id');

        // Add linked evidence.
        $evidence = new dp_evidence_relation($plan->id, 'program', $programassignments[$program1->id]->id);
        $evidence->update_linked_evidence(array($evidence1->id, $evidence2->id));

        // Check program was assigned.
        $assignedprograms = $programcomponent->get_assigned_items();
        $this->assertCount(1, $assignedprograms);

        // Make sure linked evidence is not included.
        $assignedprogram = reset($assignedprograms);
        $this->assertObjectNotHasAttribute('linkedevidence', $assignedprogram);

        // Get assigned items again with linked counts.
        $assignedprograms = $programcomponent->get_assigned_items(null, '', '', '', true);
        $assignedprogram = reset($assignedprograms);
        $this->assertObjectHasAttribute('linkedevidence', $assignedprogram);
        $this->assertEquals(2, $assignedprogram->linkedevidence);
    }

    /**
     * Tests dp_program_component->assign_new_item
     *
     * This ensures that we have the correct saved records when no due date
     * is required for programs added to a learning plan.
     *
     * The user's time due should be set to COMPLETION_TIME_NOT_SET.
     *
     * @throws coding_exception
     */
    public function test_dp_component_program_assign_new_item_no_duedate() {
        $this->resetAfterTest(true);
        global $DB;

        $datagenerator = $this->getDataGenerator();

        $user = $datagenerator->create_user();
        $this->setUser($user);

        /** @var totara_plan_generator $plangenerator */
        $plangenerator = $datagenerator->get_plugin_generator('totara_plan');
        $enddate = time() + DAYSECS;
        $planrecord = $plangenerator->create_learning_plan(array('userid' => $user->id, 'enddate' => $enddate));
        $plan = new development_plan($planrecord->id);

        // We're initialising the settings but not changing anything. The defaults
        // should give us the results that we're checking for.
        $plan->initialize_settings();

        /** @var totara_program_generator $programgenerator */
        $programgenerator = $datagenerator->get_plugin_generator('totara_program');
        $program1 = $programgenerator->create_program();

        /** @var dp_program_component $component_program */
        $component_program = $plan->get_component('program');

        $assigneditem = $component_program->assign_new_item($program1->id);

        // Check that the item was successfully saved to the database.
        $this->assertTrue($DB->record_exists('dp_plan_program_assign',
            array('planid' => $planrecord->id, 'programid' => $program1->id)));

        // The user should now have a program completion record. We'll ensure the values are correct.
        $progcompletion = $DB->get_record('prog_completion',
            array('programid' => $program1->id, 'userid' => $user->id, 'coursesetid' => 0));

        $this->assertEquals(STATUS_PROGRAM_INCOMPLETE, $progcompletion->status);
        $this->assertEquals(0, $progcompletion->timecompleted);

        // No due date was required. Therefore the timedue should be not set.
        $this->assertEquals(COMPLETION_TIME_NOT_SET, $progcompletion->timedue);
    }

    /**
     * Tests dp_program_component->assign_new_item
     *
     * This ensures that we have the correct saved records when a due date
     * is required for programs added to a learning plan.
     *
     * The user's time due should be set to the end date of the plan.
     *
     * @throws coding_exception
     */
    public function test_dp_component_program_assign_new_item_require_duedate() {
        $this->resetAfterTest(true);
        global $DB;

        $datagenerator = $this->getDataGenerator();

        $user = $datagenerator->create_user();
        $this->setUser($user);

        /** @var totara_plan_generator $plangenerator */
        $plangenerator = $datagenerator->get_plugin_generator('totara_plan');
        $enddate = time() + DAYSECS;
        $planrecord = $plangenerator->create_learning_plan(array('userid' => $user->id, 'enddate' => $enddate));
        $plan = new development_plan($planrecord->id);

        // Have a due date be required for any programs added to this plan.
        $plan->initialize_settings();
        $plan->settings['program_duedatemode'] = DP_DUEDATES_REQUIRED;

        /** @var totara_program_generator $programgenerator */
        $programgenerator = $datagenerator->get_plugin_generator('totara_program');
        $program1 = $programgenerator->create_program();

        /** @var dp_program_component $component_program */
        $component_program = $plan->get_component('program');

        $assigneditem = $component_program->assign_new_item($program1->id);

        // Check that the item was successfully saved to the database.
        $this->assertTrue($DB->record_exists('dp_plan_program_assign',
            array('planid' => $planrecord->id, 'programid' => $program1->id)));

        // The user should now have a program completion record. We'll ensure the values are correct.
        $progcompletion = $DB->get_record('prog_completion',
            array('programid' => $program1->id, 'userid' => $user->id, 'coursesetid' => 0));

        $this->assertEquals(STATUS_PROGRAM_INCOMPLETE, $progcompletion->status);
        $this->assertEquals(0, $progcompletion->timecompleted);

        // When due dates are required for programs, the due date will be the plans end date.
        $this->assertEquals($enddate, $progcompletion->timedue);
    }

    /**
     * Tests dp_program_component->assign_new_item
     *
     * This ensures that the correct existing due date is used if it already exists.
     *
     * The user's time due should be the program due date.
     */
    public function test_dp_component_program_assign_new_item_existing_completion() {
        $this->resetAfterTest(true);
        global $DB;

        $datagenerator = $this->getDataGenerator();

        $user = $datagenerator->create_user();
        $this->setUser($user);

        /* @var totara_plan_generator $plangenerator */
        $plangenerator = $datagenerator->get_plugin_generator('totara_plan');
        $enddate = time() + DAYSECS * 100; // Further in the future than the program due date.
        $planrecord = $plangenerator->create_learning_plan(array('userid' => $user->id, 'enddate' => $enddate));
        $plan = new development_plan($planrecord->id);

        // We're initialising the settings but not changing anything. The defaults
        // should give us the results that we're checking for.
        $plan->initialize_settings();

        $program1 = $this->program_generator->create_program();
        $duedate = time() + DAYSECS * 10;
        $this->program_generator->assign_to_program($program1->id, ASSIGNTYPE_INDIVIDUAL, $user->id, null, true);

        // Check that records exist.
        $this->assertEquals(1, $DB->count_records('prog_assignment',
            array('programid' => $program1->id)));
        $this->assertEquals(1, $DB->count_records('prog_user_assignment',
            array('programid' => $program1->id)));
        $this->assertEquals(1, $DB->count_records('prog_completion', // Will be 2 with TL-11020.
            array('programid' => $program1->id)));

        // Set the program course set 0 record's due date.
        $progcompletion = prog_load_completion($program1->id, $user->id);
        $progcompletion->timedue = $duedate;
        prog_write_completion($progcompletion);

        // Set the program course set 1 record's due date, to make sure it's not accidentally used.
        $sql = "UPDATE {prog_completion}
                   SET timedue = :timedue
                 WHERE programid = :programid
                   AND userid = :userid
                   AND coursesetid <> 0";
        $DB->execute($sql, array('timedue' => $enddate, 'programid' => $program1->id, 'userid' => $user->id));

        /* @var dp_program_component $component_program */
        $component_program = $plan->get_component('program');

        $assigneditem = $component_program->assign_new_item($program1->id);

        // Check that the item was successfully saved to the database.
        $this->assertTrue($DB->record_exists('dp_plan_program_assign',
            array('planid' => $planrecord->id, 'programid' => $program1->id)));

        // See that the plan due date is equal to the program due date.
        $progcompletion = prog_load_completion($program1->id, $user->id);
        $this->assertEquals($duedate, $progcompletion->timedue);
        $this->assertEquals($duedate, $assigneditem->duedate);
    }

    public function test_send_component_update_alert() {

        // Create users.
        $learner1 = $this->data_generator->create_user();
        $learner2 = $this->data_generator->create_user();
        $manager1 = $this->data_generator->create_user();
        $manager2 = $this->data_generator->create_user();
        $manager3 = $this->data_generator->create_user();

        // Some permission checks require $USER to be set to someone. We change it before we test the actual
        // method we're interested in, so let's just go with admin who will pass everything up til then.
        $this->setAdminUser();

        $manager1ja = \totara_job\job_assignment::create_default($manager1->id);
        $manager2ja = \totara_job\job_assignment::create_default($manager2->id);
        $manager3ja = \totara_job\job_assignment::create_default($manager3->id);

        $learner1ja1 = \totara_job\job_assignment::create_default($learner1->id, array('managerjaid' => $manager1ja->id));
        $learner1ja2 = \totara_job\job_assignment::create_default($learner1->id, array('managerjaid' => $manager2ja->id));
        $learner1ja3 = \totara_job\job_assignment::create_default($learner1->id, array('managerjaid' => $manager3ja->id));

        $planrecord = $this->plan_generator->create_learning_plan(array('userid' => $learner1->id));
        $plan = new development_plan($planrecord->id);
        $plan->set_status(DP_PLAN_STATUS_APPROVED);
        // Reload to get change in status.
        $plan = new development_plan($planrecord->id);

        // We need a component on the plan to truly test this. We'll add a program.
        $program1 = $this->program_generator->create_program();
        /** @var dp_program_component $component_program */
        $component_program = $plan->get_component('program');
        $assigneditem = $component_program->assign_new_item($program1->id);

        $messages = $this->messagesink->get_messages();
        $this->assertCount(0, $messages);

        // Check that all managers get the message if it is a learner logged in.
        $this->setUser($learner1);
        $component_program->send_component_update_alert('Some custom update info');

        $messages = $this->messagesink->get_messages();
        $this->assertCount(3, $messages);
        foreach ($messages as $message) {
            $this->assertContains(fullname($learner1), $message->subject);
            $this->assertContains('Programs in learning plan "'.$plan->name .'" updated:', $message->fullmessage);
            $this->assertContains('Some custom update info', $message->fullmessage);
            $this->assertContains($component_program->get_url()->out(true), $message->fullmessagehtml);
            $this->assertContains($message->useridto, array($manager1->id, $manager2->id, $manager3->id));
            $this->assertNotEquals($learner1->id, $message->useridto);
            $this->assertNotEquals($learner2->id, $message->useridto);
        }

        $this->messagesink->clear();

        // Again, this time with a manager logged in. The learner should receive an email.
        $this->setUser($manager2);
        $component_program->send_component_update_alert('Some other update info');

        $messages = $this->messagesink->get_messages();
        $this->assertCount(1, $messages);

        $message = array_shift($messages);
        $this->assertContains('Programs updated', $message->subject);
        $this->assertContains('Programs in learning plan "'.$plan->name .'" updated:', $message->fullmessage);
        $this->assertContains('Some other update info', $message->fullmessage);
        $this->assertContains($component_program->get_url()->out(true), $message->fullmessagehtml);
        $this->assertNotContains($message->useridto, array($manager1->id, $manager2->id, $manager3->id));
        $this->assertEquals($learner1->id, $message->useridto);
        $this->assertNotEquals($learner2->id, $message->useridto);
    }

    public function test_send_component_approval_alert() {

        // Create users.
        $learner1 = $this->data_generator->create_user();
        $learner2 = $this->data_generator->create_user();
        $manager1 = $this->data_generator->create_user();
        $manager2 = $this->data_generator->create_user();
        $manager3 = $this->data_generator->create_user();

        // Some permission checks require $USER to be set to someone. We change it before we test the actual
        // method we're interested in, so let's just go with admin who will pass everything up til then.
        $this->setAdminUser();

        $manager1ja = \totara_job\job_assignment::create_default($manager1->id);
        $manager2ja = \totara_job\job_assignment::create_default($manager2->id);
        $manager3ja = \totara_job\job_assignment::create_default($manager3->id);

        $learner1ja1 = \totara_job\job_assignment::create_default($learner1->id, array('managerjaid' => $manager1ja->id));
        $learner1ja2 = \totara_job\job_assignment::create_default($learner1->id, array('managerjaid' => $manager2ja->id));
        $learner1ja3 = \totara_job\job_assignment::create_default($learner1->id, array('managerjaid' => $manager3ja->id));

        $planrecord = $this->plan_generator->create_learning_plan(array('userid' => $learner1->id));
        $plan = new development_plan($planrecord->id);
        $plan->set_status(DP_PLAN_STATUS_APPROVED);
        // Reload to get change in status.
        $plan = new development_plan($planrecord->id);

        // We need a component on the plan to truly test this. We'll add a program.
        $program1 = $this->program_generator->create_program();
        /** @var dp_program_component $component_program */
        $component_program = $plan->get_component('program');
        $assigneditem = $component_program->assign_new_item($program1->id);

        $approval = new stdClass();
        $approval->text = 'Some approval text';
        $approval->itemid = $program1->id;
        $approval->itemname = $program1->fullname;
        $approval->before = DP_APPROVAL_UNAPPROVED;
        $approval->after = DP_APPROVAL_APPROVED;
        $approval->reasonfordecision = 'The approval reason';

        $messages = $this->messagesink->get_messages();
        $this->assertCount(0, $messages);

        // Check that all managers get the message if it is a learner logged in.
        $this->setUser($learner1);
        $component_program->send_component_approval_alert($approval);

        $messages = $this->messagesink->get_messages();
        $this->assertCount(3, $messages);
        foreach ($messages as $message) {
            $this->assertContains(fullname($learner1), $message->subject);
            $this->assertContains('Programs in learning plan "'.$plan->name .'" approved:', $message->fullmessage);
            $this->assertContains('The reason given for this decision was: The approval reason', $message->fullmessage);
            $this->assertContains($component_program->get_url()->out(true), $message->fullmessagehtml);
            $this->assertContains($message->useridto, array($manager1->id, $manager2->id, $manager3->id));
            $this->assertNotEquals($learner1->id, $message->useridto);
            $this->assertNotEquals($learner2->id, $message->useridto);
        }

        $this->messagesink->clear();

        $approval->after = DP_APPROVAL_DECLINED;
        $approval->reasonfordecision = 'The disapproval reason';

        // Again, this time with a manager logged in. The learner should receive an email.
        $this->setUser($manager2);
        $component_program->send_component_approval_alert($approval);

        $messages = $this->messagesink->get_messages();
        $this->assertCount(1, $messages);

        $message = array_shift($messages);
        $this->assertContains($program1->fullname . ' declined', $message->subject);
        $this->assertContains('Programs in learning plan "'.$plan->name .'" declined:', $message->fullmessage);
        $this->assertContains('The reason given for this decision was: The disapproval reason', $message->fullmessage);
        $this->assertContains($component_program->get_url()->out(true), $message->fullmessagehtml);
        $this->assertNotContains($message->useridto, array($manager1->id, $manager2->id, $manager3->id));
        $this->assertEquals($learner1->id, $message->useridto);
        $this->assertNotEquals($learner2->id, $message->useridto);
    }

    public function test_send_component_complete_alert() {

        // Create users.
        $learner1 = $this->data_generator->create_user();
        $learner2 = $this->data_generator->create_user();
        $manager1 = $this->data_generator->create_user();
        $manager2 = $this->data_generator->create_user();
        $manager3 = $this->data_generator->create_user();

        // Some permission checks require $USER to be set to someone. We change it before we test the actual
        // method we're interested in, so let's just go with admin who will pass everything up til then.
        $this->setAdminUser();

        $manager1ja = \totara_job\job_assignment::create_default($manager1->id);
        $manager2ja = \totara_job\job_assignment::create_default($manager2->id);
        $manager3ja = \totara_job\job_assignment::create_default($manager3->id);

        $learner1ja1 = \totara_job\job_assignment::create_default($learner1->id, array('managerjaid' => $manager1ja->id));
        $learner1ja2 = \totara_job\job_assignment::create_default($learner1->id, array('managerjaid' => $manager2ja->id));
        $learner1ja3 = \totara_job\job_assignment::create_default($learner1->id, array('managerjaid' => $manager3ja->id));

        $planrecord = $this->plan_generator->create_learning_plan(array('userid' => $learner1->id));
        $plan = new development_plan($planrecord->id);
        $plan->set_status(DP_PLAN_STATUS_APPROVED);
        // Reload to get change in status.
        $plan = new development_plan($planrecord->id);

        // We need a component on the plan to truly test this. We'll add a program.
        $program1 = $this->program_generator->create_program();
        /** @var dp_program_component $component_program */
        $component_program = $plan->get_component('program');
        $assigneditem = $component_program->assign_new_item($program1->id);

        $completion = new stdClass();
        $completion->text = 'Some completion text';
        $completion->itemname = $program1->fullname;

        $messages = $this->messagesink->get_messages();
        $this->assertCount(0, $messages);

        // Check that all managers get the message if it is a learner logged in.
        $this->setUser($learner1);
        $component_program->send_component_complete_alert($completion);

        $messages = $this->messagesink->get_messages();
        $this->assertCount(3, $messages);
        foreach ($messages as $message) {
            $this->assertContains(fullname($learner1), $message->subject);
            $this->assertContains('Programs in learning plan "'.$plan->name .'" completed:', $message->fullmessage);
            $this->assertContains('Some completion text', $message->fullmessage);
            $this->assertContains($component_program->get_url()->out(true), $message->fullmessagehtml);
            $this->assertContains($message->useridto, array($manager1->id, $manager2->id, $manager3->id));
            $this->assertNotEquals($learner1->id, $message->useridto);
            $this->assertNotEquals($learner2->id, $message->useridto);
        }

        $this->messagesink->clear();

        // Again, this time with a manager logged in. The learner should receive an email.
        $this->setUser($manager2);
        $component_program->send_component_complete_alert($completion);

        $messages = $this->messagesink->get_messages();
        $this->assertCount(1, $messages);

        $message = array_shift($messages);
        $this->assertContains($program1->fullname . ' completed', $message->subject);
        $this->assertContains('Programs in learning plan "'.$plan->name .'" completed:', $message->fullmessage);
        $this->assertContains('Some completion text', $message->fullmessage);
        $this->assertContains($component_program->get_url()->out(true), $message->fullmessagehtml);
        $this->assertNotContains($message->useridto, array($manager1->id, $manager2->id, $manager3->id));
        $this->assertEquals($learner1->id, $message->useridto);
        $this->assertNotEquals($learner2->id, $message->useridto);
    }
}
