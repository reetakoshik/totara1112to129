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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Rob Tyler <rob.tyler@totaralearning.com>
 * @package totara_cohort
 */

defined('MOODLE_INTERNAL') || die();


class create_learning_plans_testcase extends advanced_testcase {

    /**
     * @var array User objects used in the test.
     */
    private $user = array();

    /**
     * @var stdclass The cohort object used.
     */
    private $cohort = null;

    /**
     * @var totara_plan_generator
     */
    private $plan_generator = null;

    /**
     * Common set up for all the tests in the file.
     */
    public function setUp(){
        global $DB;

        parent::setup();

        $this->resetAfterTest(true);
        $this->setAdminUser();

        // Create some users.
        for($i = 0; $i < 2; $i++) {
            $this->user[] = $this->getDataGenerator()->create_user();
        }

        // Check the users have been created.
        foreach($this->user as $index => $user) {
            $this->assertEquals(1, $DB->count_records('user', array('id' => $this->user[$index]->id)));
        }

        // Create an audience.
        $this->cohort = $this->getDataGenerator()->create_cohort();

        // Add the users to the audience.
        foreach($this->user as $index => $user) {
            cohort_add_member($this->cohort->id, $this->user[$index]->id);
        }

        // Check that the users have been added to the audience.
        foreach($this->user as $index => $user) {
            $this->assertEquals(1, $DB->count_records('cohort_members', array(
                'cohortid' => $this->cohort->id,
                'userid' => $this->user[$index]->id
            )));
        }

        // Create a plan generator.
        $this->generator = $this->getDataGenerator();
        $this->plan_generator = $this->generator->get_plugin_generator('totara_plan');
    }

    /**
     * Clear down the class variables so they don't hog any memory.
     */
    public function tearDown() {

        $this->user = array();
        $this->cohort = null;
        $this->plan_generator = null;

        parent::tearDown();
    }

    /**
     * Test it's possible for duplicate learning plans to be automatically created.
     */
    public function test_duplicate_automatically_generated_plans_are_created() {
        global $DB, $USER;

        // Create learning plan config object.
        $planconfig = \totara_cohort\learning_plan_config::get_config($this->cohort->id);
        $planconfig->plantemplateid = 1;
        $planconfig->planstatus = DP_PLAN_STATUS_APPROVED;
        $planconfig->excludecreatedmanual = false;
        $planconfig->excludecreatedauto = false;
        $planconfig->excludecompleted = false;
        $planconfig->autocreatenew = false;
        $planconfig->timecreated = time();
        $planconfig->usercreated = $USER->id;
        $planconfig->save();

        // Check the learning plan config record exists.
        $this->assertEquals(1, $DB->count_records('cohort_plan_config', array('id' => $planconfig->id, 'cohortid' => $this->cohort->id)));

        // Create the learning plans based on the config.
        $plancount = \totara_cohort\learning_plan_helper::create_plans($planconfig, $USER->id);

        // Check how many plans have been created.
        $this->assertEquals(2, $plancount);

        // Create some more plans.
        $plancount = \totara_cohort\learning_plan_helper::create_plans($planconfig, $USER->id);

        // Check for the additional plans have been created.
        $this->assertEquals(2, $plancount);

        // Check that two plans from the same template have been created for each of the users.
        foreach($this->user as $index => $user) {
            $this->assertEquals(2, $DB->count_records('dp_plan', array('userid' => $this->user[$index]->id, 'templateid' => 1)));
        }
    }

    /**
     * Test it's not possible for a duplicate of an automatically created learning plan
     * to be automatically generated when the 'exclude automatic duplicates' option is selected.
     */
    public function test_duplicate_automatically_generated_plans_are_not_created() {
        global $DB, $USER;

        // Create learning plan config object.
        $planconfig = \totara_cohort\learning_plan_config::get_config($this->cohort->id);
        $planconfig->plantemplateid = 1;
        $planconfig->planstatus = DP_PLAN_STATUS_APPROVED;
        $planconfig->excludecreatedmanual = false;
        $planconfig->excludecreatedauto = true;
        $planconfig->excludecompleted = false;
        $planconfig->autocreatenew = false;
        $planconfig->timecreated = time();
        $planconfig->usercreated = $USER->id;
        $planconfig->save();

        // Check the learning plan config record exists.
        $this->assertEquals(1, $DB->count_records('cohort_plan_config', array('id' => $planconfig->id, 'cohortid' => $this->cohort->id)));

        // Create the learning plans based on the config.
        $plancount = \totara_cohort\learning_plan_helper::create_plans($planconfig, $USER->id);

        // Check how many plans have been created.
        $this->assertEquals(2, $plancount);

        // Create some more plans.
        $plancount = \totara_cohort\learning_plan_helper::create_plans($planconfig, $USER->id);

        // Check for the additional plans have been created.
        $this->assertEquals(0, $plancount);

        // Check that only one plan from the same template have been created for each of the users.
        foreach($this->user as $index => $user) {
            $this->assertEquals(1, $DB->count_records('dp_plan', array('userid' => $this->user[$index]->id, 'templateid' => 1)));
        }
    }

    /**
     * Test it's possible for a duplicate of a manually created learning plan to be automatically generated.
     */
    public function test_duplicate_manually_generated_plans_are_created_automatically() {
        global $DB, $USER;

        // Create a 'manually created' learning plan for user 0.
        $plan = $this->plan_generator->create_learning_plan(array('userid' => $this->user[0]->id));

        // Check the plan has been created for user 0.
        $this->assertEquals(1, $DB->count_records('dp_plan', array('userid' => $this->user[0]->id, 'templateid' => 1)));

        // Create learning plan config object.
        $planconfig = \totara_cohort\learning_plan_config::get_config($this->cohort->id);
        $planconfig->plantemplateid = 1;
        $planconfig->planstatus = DP_PLAN_STATUS_APPROVED;
        $planconfig->excludecreatedmanual = false;
        $planconfig->excludecreatedauto = false;
        $planconfig->excludecompleted = false;
        $planconfig->autocreatenew = false;
        $planconfig->timecreated = time();
        $planconfig->usercreated = $USER->id;
        $planconfig->save();

        // Check the learning plan config record exists.
        $this->assertEquals(1, $DB->count_records('cohort_plan_config', array('id' => $planconfig->id, 'cohortid' => $this->cohort->id)));

        // Create the learning plans based on the config.
        $plancount = \totara_cohort\learning_plan_helper::create_plans($planconfig, $USER->id);

        // Check how many plans have been created.
        $this->assertEquals(2, $plancount);

        // Check that two plans exist for user 0 and only 1 exists for user 1.
        $this->assertEquals(2, $DB->count_records('dp_plan', array('userid' => $this->user[0]->id, 'templateid' => 1)));
        $this->assertEquals(1, $DB->count_records('dp_plan', array('userid' => $this->user[1]->id, 'templateid' => 1)));
    }

    /**
     * Test it's not possible for a duplicate of a manually created learning plan
     * to be automatically generated when the 'exclude manual duplicates' option is selected.
     */
    public function test_duplicate_manually_generated_plans_are_not_created_automatically() {
        global $DB, $USER;

        // Create a 'manually created' learning plan for user 0.
        $plan = $this->plan_generator->create_learning_plan(array('userid' => $this->user[0]->id));

        // Check the plan has been created for user 0.
        $this->assertEquals(1, $DB->count_records('dp_plan', array('userid' => $this->user[0]->id, 'templateid' => 1)));

        // Create learning plan config object.
        $planconfig = \totara_cohort\learning_plan_config::get_config($this->cohort->id);
        $planconfig->plantemplateid = 1;
        $planconfig->planstatus = DP_PLAN_STATUS_APPROVED;
        $planconfig->excludecreatedmanual = true;
        $planconfig->excludecreatedauto = false;
        $planconfig->excludecompleted = false;
        $planconfig->autocreatenew = false;
        $planconfig->timecreated = time();
        $planconfig->usercreated = $USER->id;
        $planconfig->save();

        // Check the learning plan config record exists.
        $this->assertEquals(1, $DB->count_records('cohort_plan_config', array('id' => $planconfig->id, 'cohortid' => $this->cohort->id)));

        // Create the learning plans based on the config.
        $plancount = \totara_cohort\learning_plan_helper::create_plans($planconfig, $USER->id);

        // Check how many plans have been created.
        $this->assertEquals(1, $plancount);

        // Check that both of the users only have 1 plan each.
        foreach($this->user as $index => $user) {
            $this->assertEquals(1, $DB->count_records('dp_plan', array('userid' => $this->user[$index]->id, 'templateid' => 1)));
        }
    }

}
