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
 * @author Alastair Munro <alastair.munro@totaralearning.com>
 * @package block_current_learning
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot.'/blocks/current_learning/tests/fixtures/block_current_learning_testcase_base.php');

class block_current_learning_testcase extends block_current_learning_testcase_base {

    private $user1;

    private $generator;

    protected function tearDown() {
        $this->user1 = null;
        $this->generator = null;
        parent::tearDown();
    }

    protected function setUp() {
        global $CFG, $DB;

        $this->resetAfterTest(true);
        parent::setUp();

        $this->generator = $this->getDataGenerator();

        // Create some users.
        $this->user1 = $this->generator->create_user();
    }

    /**
     * There is a special case for plan courses.
     * Courses within a plan are secondary items but should
     * take precedence over standard courses.
     */
    function test_ensure_user_learning_items_unique_plan_courses() {
        global $DB, $CFG;

        $course1 = $this->generator->create_course();

        $plan_generator = $this->generator->get_plugin_generator('totara_plan');

        $this->setAdminUser();

        // Create a plan and add course1.
        $plan1 = $plan_generator->create_learning_plan(array('userid' => $this->user1->id));
        $plan_generator->add_learning_plan_course($plan1->id, $course1->id);

        $items = array();

        // Plan course item.
        $items[] = \totara_plan\user_learning\course::one($this->user1->id, $course1->id);

        // Course item.
        $items[] = \core_course\user_learning\item::one($this->user1->id, $course1->id);

        require_once($CFG->dirroot . '/blocks/current_learning/block_current_learning.php');

        $this->assertCount(2, $items);

        $block_instance = new block_current_learning();

        // Run duplicate check.
        $rm = new ReflectionMethod('block_current_learning', 'ensure_user_learning_items_unique');
        $rm->setAccessible(true);

        $new_items = $rm->invoke($block_instance, $items);

        $this->assertCount(1, $new_items);
        $this->assertInstanceOf('\totara_plan\user_learning\course', array_values($new_items)[0]);

        // Enrol user into the course.
        $this->generator->enrol_user($this->user1->id, $course1->id, null, 'manual', time() - 864000, time() - 604800);

        // Check the item again.
        $new_items = $rm->invoke($block_instance, $items);
        $this->assertCount(1, $new_items);
        $plan_course = array_values($new_items)[0];
        $this->assertInstanceOf('\totara_plan\user_learning\course', $plan_course);
    }


    /**
     * Check that when the same course is in multiple plans it is only shown
     * once
     */
    function test_ensure_user_learning_items_unique_with_multiple_plans() {
        global $CFG;

        require_once($CFG->dirroot . '/blocks/current_learning/block_current_learning.php');

        $course1 = $this->generator->create_course();

        $plan_generator = $this->generator->get_plugin_generator('totara_plan');

        $this->setAdminUser();

        $plan1 = $plan_generator->create_learning_plan(array('userid' => $this->user1->id));
        $plan1 = new development_plan($plan1->id);
        $plan_generator->add_learning_plan_course($plan1->id, $course1->id);
        $plan1->set_status(DP_PLAN_STATUS_APPROVED);

        // Add the same course to a second plan.
        $plan2 = $plan_generator->create_learning_plan(array('userid' => $this->user1->id));
        $plan2 = new development_plan($plan2->id);
        $plan_generator->add_learning_plan_course($plan2->id, $course1->id);
        $plan2->set_status(DP_PLAN_STATUS_APPROVED);

        $items = array();

        $plans = \totara_plan\user_learning\item::all($this->user1->id);

        foreach ($plans as $plan) {
            $courses = $plan->get_courses();
            $items = array_merge($items, array_values($courses));
        }

        // Run duplicate check.
        $block_instance = new block_current_learning();

        // Run duplicate check.
        $rm = new ReflectionMethod('block_current_learning', 'ensure_user_learning_items_unique');
        $rm->setAccessible(true);

        $new_items = $rm->invoke($block_instance, $items);

        // Check there is only one course.
        $this->assertCount(1, $new_items);
    }
}
