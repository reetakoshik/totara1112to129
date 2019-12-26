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
 * @author Simon Player <simon.player@totaralearning.com>
 * @package block_current_learning
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot.'/blocks/current_learning/tests/fixtures/block_current_learning_testcase_base.php');

class block_current_learning_plan_data_testcase extends block_current_learning_testcase_base {

    private $generator;
    private $plan_generator;
    private $program_generator;

    private $user1, $user2;
    private $course1, $course2;
    private $program1, $program2;
    private $planrecord1;

    protected function tearDown() {
        $this->generator = null;
        $this->plan_generator = null;
        $this->program_generator = null;
        $this->user1 = $this->user2 = null;
        $this->course1 = $this->course2 = null;
        $this->program1 = $this->program2 = null;
        $this->planrecord1 = null;
        parent::tearDown();
    }

    protected function setUp() {
        global $CFG;
        parent::setUp();

        $this->setAdminUser();

        $this->generator = $this->getDataGenerator();
        $this->plan_generator = $this->generator->get_plugin_generator('totara_plan');
        $this->program_generator = $this->generator->get_plugin_generator('totara_program');

        $this->resetAfterTest();
        $CFG->enablecompletion = true;

        // Create some users.
        $this->user1 = $this->generator->create_user();
        $this->user2 = $this->generator->create_user();

        // Create some courses.
        $this->course1 = $this->generator->create_course();
        $this->course2 = $this->generator->create_course();

        // Create some programs.
        $this->program1 = $this->program_generator->create_program();
        $this->program2 = $this->program_generator->create_program();

        // Add some courses to the programs.
        $this->program_generator->add_courses_and_courseset_to_program($this->program1, [ [$this->course1]]);

        // Create a learning plan.
        $this->planrecord1 = $this->plan_generator->create_learning_plan(array('userid' => $this->user1->id));
    }

    public function test_plans_disabled() {
        global $CFG;

        $plan = new development_plan($this->planrecord1->id);

        // Add a course to the plan.
        $this->plan_generator->add_learning_plan_course($plan->id, $this->course1->id);

        // Approve the plan.
        $plan->set_status(DP_PLAN_STATUS_APPROVED, DP_PLAN_REASON_CREATE);

        // The course should appear in the learning data.
        $learning_data = $this->get_learning_data($this->user1->id);
        $this->assertTrue($this->course_in_learning_data($this->course1->id, $learning_data));

        // Now lets disable plan.
        $CFG->enablelearningplans = 3;

        // The course should not appear in the learning data.
        $learning_data = $this->get_learning_data($this->user1->id);
        $this->assertNotTrue($this->course_in_learning_data($this->course1->id, $learning_data));
    }

    public function test_courses_from_plan() {

        // All courses added to any of the learner's active learning plans (i.e., learning plans that are not draft or
        // complete) and approved (i.e. the courses are not pending or declined) within those learning plans should be
        // displayed.

        $plan = new development_plan($this->planrecord1->id);

        // Add a course to the plan.
        $this->plan_generator->add_learning_plan_course($plan->id, $this->course1->id);

        // Plan approved.
        $plan->set_status(DP_PLAN_STATUS_APPROVED, DP_PLAN_REASON_CREATE);
        $learning_data = $this->get_learning_data($this->user1->id);
        $this->assertTrue($this->course_in_learning_data($this->course1->id, $learning_data));

        // Plan not approved.
        $plan->set_status(DP_PLAN_STATUS_UNAPPROVED, DP_PLAN_REASON_CREATE);
        $learning_data = $this->get_learning_data($this->user1->id);
        $this->assertNotTrue($this->course_in_learning_data($this->course1->id, $learning_data));

        // Plan completed.
        $plan->set_status(DP_PLAN_STATUS_COMPLETE, DP_PLAN_REASON_CREATE);
        $learning_data = $this->get_learning_data($this->user1->id);
        $this->assertNotTrue($this->course_in_learning_data($this->course1->id, $learning_data));
    }

    public function test_courses_duplication() {

        // If a user in enrolled into a course directly and also via a plan, the course should only de displayed once.

        // Enroll user directly into course1.
        $this->generator->enrol_user($this->user1->id, $this->course1->id);
        $learning_data = $this->get_learning_data($this->user1->id);
        $this->assertTrue($this->course_in_learning_data($this->course1->id, $learning_data));

        // Now add the same course to an approved learning plan.
        $plan = new development_plan($this->planrecord1->id);
        $this->plan_generator->add_learning_plan_course($plan->id, $this->course1->id);
        $plan->set_status(DP_PLAN_STATUS_APPROVED, DP_PLAN_REASON_CREATE);

        // Get the new learning data.
        $learning_data = $this->get_learning_data($this->user1->id);

        // The course should only appear once.
        $count = 0;
        foreach ($learning_data['learningitems'] as $item) {
            if ($item->id == $this->course1->id && $item->type == 'course') {
                $count++;
            }
        }
        $this->assertEquals(1, $count);
    }

    public function test_programs_from_plan() {

        // All programs added to any of the learner's active learning plans should be displayed.

        $plan = new development_plan($this->planrecord1->id);

        // Add a program to the plan.
        $this->plan_generator->add_learning_plan_program($plan->id, $this->program1->id);

        // Plan approved.
        $plan->set_status(DP_PLAN_STATUS_APPROVED, DP_PLAN_REASON_CREATE);

        $learning_data = $this->get_learning_data($this->user1->id);
        $this->assertTrue($this->program_in_learning_data($this->program1, $learning_data));

        // Plan not approved.
        $plan->set_status(DP_PLAN_STATUS_UNAPPROVED, DP_PLAN_REASON_CREATE);
        $learning_data = $this->get_learning_data($this->user1->id);
        $this->assertNotTrue($this->program_in_learning_data($this->program1, $learning_data));

        // Plan completed.
        $plan->set_status(DP_PLAN_STATUS_COMPLETE, DP_PLAN_REASON_CREATE);
        $learning_data = $this->get_learning_data($this->user1->id);
        $this->assertNotTrue($this->program_in_learning_data($this->program1, $learning_data));
    }

    public function test_object_instances() {

        // Create a plan with a course and a program.
        $plan = new development_plan($this->planrecord1->id);
        $this->plan_generator->add_learning_plan_course($plan->id, $this->course1->id);
        $this->plan_generator->add_learning_plan_program($plan->id, $this->program1->id);
        $plan->set_status(DP_PLAN_STATUS_APPROVED, DP_PLAN_REASON_CREATE);

        // Get the learning items.
        $items = totara_plan\user_learning\item::all($this->user1);

        // We expect one plan in the correct instance.
        $this->assertCount(1, $items);
        $item = current($items);
        $this->assertInstanceOf('totara_plan\user_learning\item', $item);

        // We expect one program in the correct instance.
        $programs = $item->get_programs();
        $this->assertCount(1, $programs);
        $program = current($programs);
        $this->assertInstanceOf('totara_plan\user_learning\program', $program);
        // Should also be an instance of totara_program through inheritance
        $this->assertInstanceOf('totara_program\user_learning\item', $program);

        // We expect one course in the correct instance.
        $courses = $item->get_courses();
        $this->assertCount(1, $courses);
        $course = current($courses);
        $this->assertInstanceOf('totara_plan\user_learning\course', $course);
        // Should also be an instance of core_course item through inheritance
        $this->assertInstanceOf('core_course\user_learning\item', $course);
    }

    public function test_object_empty_plan() {

        // Create a plan with a course and a program.
        $plan = new development_plan($this->planrecord1->id);
        $plan->set_status(DP_PLAN_STATUS_APPROVED, DP_PLAN_REASON_CREATE);

        // Get the learning items.
        $items = totara_plan\user_learning\item::all($this->user1);

        // We expect one plan in the correct instance.
        $this->assertCount(1, $items);
        $item = current($items);
        $this->assertInstanceOf('totara_plan\user_learning\item', $item);

        // We expect an empty array.
        $courses = $item->get_courses();
        $this->assertEquals(array(), $courses);

        // We expect an empty array.
        $programs = $item->get_programs();
        $this->assertEquals(array(), $programs);
    }
}
