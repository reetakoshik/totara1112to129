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
 * @author Rob Tyler <rob.tyler@totaralms.com>
 * @package totara
 * @subpackage appraisal
 **/
global $CFG;

require_once($CFG->dirroot.'/totara/appraisal/tests/appraisal_testcase.php');

class totara_appraisal_generator_testcase extends advanced_testcase {

    /** @var totara_appraisal_generator $appraisalgenerator */
    public $appraisalgenerator = null;
    public $appraisal = null;

    // Array's can't be defined as a constant so declare it as a private variable.
    private $allroles = array(appraisal::ROLE_LEARNER, appraisal::ROLE_MANAGER, appraisal::ROLE_TEAM_LEAD, appraisal::ROLE_APPRAISER);

    protected function tearDown() {
        $this->appraisalgenerator = null;
        $this->appraisal = null;
        $this->allroles = null;
        parent::tearDown();
    }

    public function setUp() {
        $this->resetAfterTest();

        // All tests need the generator and an appraisal object.
        $this->appraisalgenerator = $this->getDataGenerator()->get_plugin_generator('totara_appraisal');
        $this->appraisal = $this->appraisalgenerator->create_appraisal();
    }

    public function test_create_appraisal() {
        $this->resetAfterTest();

        // Retrieve the appraisal to verify it's been created as required.
        $verify = new appraisal($this->appraisal->id);
        $this->assertEquals($verify->name, $this->appraisal->name);
        $this->assertEquals($verify->description, $this->appraisal->description);
    }

    public function test_create_stage() {
        $this->resetAfterTest();

        // Create a stage for the appraisal.
        $stage = $this->appraisalgenerator->create_stage($this->appraisal->id);

        // Retrieve the stage to verify it's been created as required.
        $verify = new appraisal_stage($stage->id);
        $this->assertEquals($verify->name, $stage->name);
        $this->assertEquals($verify->description, $stage->description);
        $this->assertEquals($verify->timedue, $stage->timedue);
    }

    public function test_create_stage_with_role_completion_locks() {
        $this->resetAfterTest();

        // Create some custom data for this test.
        $data = array ('locks' => array(appraisal::ROLE_LEARNER => 1, appraisal::ROLE_APPRAISER => 1));

        // Create a stage for the appraisal.
        $stage = $this->appraisalgenerator->create_stage($this->appraisal->id, $data);

        // Retrieve the stage record to verify it's been created as required.
        // We have to use the get method to prevent the locks being returned as protected.
        $newstage = new appraisal_stage($stage->id);
        $verify = $newstage->get();

        $this->assertEquals($verify->name, $stage->name);
        $this->assertEquals($verify->description, $stage->description);
        $this->assertEquals($verify->timedue, $stage->timedue);
        $this->assertArrayHasKey(appraisal::ROLE_LEARNER, $verify->locks);
        $this->assertArrayHasKey(appraisal::ROLE_APPRAISER, $verify->locks);
    }

    public function test_create_page() {
        $this->resetAfterTest();

        // Create a stage for the appraisal.
        $stage = $this->appraisalgenerator->create_stage($this->appraisal->id);

        // Create a page for the stage.
        $page = $this->appraisalgenerator->create_page($stage->id);

        // Retrieve the page to verify it's been created as required.
        $verify = new appraisal_page($page->id);
        $this->assertEquals($verify->name, $page->name);
    }

    public function  test_create_question() {
        $this->resetAfterTest();

        // Create a stage for the appraisal.
        $stage = $this->appraisalgenerator->create_stage($this->appraisal->id);

        // Create a page for the stage.
        $page = $this->appraisalgenerator->create_page($stage->id);

        // Create a question for the stage.
        $question = $this->appraisalgenerator->create_question($page->id);

        // Retrieve the page to verify it's been created as required.
        $verify = new appraisal_question($question->id);
        $this->assertEquals($verify->name, $question->name);
    }

    public function test_assign_users_appraisal() {
        global $DB;
        $this->resetAfterTest();

        /** @var totara_cohort_generator $cohortgenerator */
        $cohortgenerator = $this->getDataGenerator()->get_plugin_generator('totara_cohort');

        // Create a learner account and assign it to a cohort.
        $learner = $this->getDataGenerator()->create_user(array('username' => 'user1'));
        $cohort = $cohortgenerator->create_cohort(array('name' => 'cohort1'));
        $cohortgenerator->cohort_assign_users($cohort->id, array($learner->id));

        // Check the user was assigned to the audience.
        $this->assertTrue($DB->record_exists('cohort_members', array('cohortid' => $cohort->id, 'userid' => $learner->id)));

        // Assign cohort to the appraisal.
        $this->appraisalgenerator->create_group_assignment($this->appraisal, 'cohort', $cohort->id);

        // Check the user is assigned to the appraisal.
        $this->assertTrue($DB->record_exists('appraisal_grp_cohort', array('appraisalid' => $this->appraisal->id, 'cohortid' => $cohort->id, )));
    }

    public function test_create_message() {
        global $CFG;

        $this->resetAfterTest();

        // Create a message for the appraisal.
        $message = $this->appraisalgenerator->create_message($this->appraisal->id);

        // Verify the created message has the correct data.
        $verify = new appraisal_message($message->id);
        $this->assertEquals($this->appraisal->id, $verify->appraisalid);
        $this->assertEquals(0, $verify->stageid);
        $this->assertEquals('appraisal_activation', $verify->type);
        $this->assertEquals(0, $verify->delta);
        $this->assertEquals(0, $verify->deltaperiod);
        $allroles = $this->allroles;
        $verifyroles = $verify->roles;
        sort($allroles);
        sort($verifyroles);
        $this->assertEquals($allroles, $verifyroles);

        foreach ($this->allroles as $role) {
            $content = $verify->get_message($role);
            $this->assertEquals('Test Message 1', $content->name);
            $this->assertEquals('Test Message 1 body', $content->content);
        }

        // Create a stage for the appraisal to use in a message.
        $stage = $this->appraisalgenerator->create_stage($this->appraisal->id);
        // Prepare some message data for a new message.
        $roles = array ('learner' => appraisal::ROLE_LEARNER, 'manager' => appraisal::ROLE_MANAGER);
        $data = array ('stageid' => $stage->id,
            'event' => appraisal_message::EVENT_STAGE_DUE,
            'delta' => 5,
            'deltaperiod' => appraisal_message::PERIOD_DAY,
            'messageto' => 'each',
            'roles' => array_values($roles),
            'name' => 'Appraisal Overdue Reminder'
        );

        // Create a message with the data.
        $message = $this->appraisalgenerator->create_message($this->appraisal->id, $data);

        // Verify the created message has the correct data.
        $verify = new appraisal_message($message->id);
        $this->assertEquals($this->appraisal->id, $verify->appraisalid);
        $this->assertEquals($stage->id, $verify->stageid);
        $this->assertEquals('stage_due', $verify->type);
        $this->assertEquals(5, $verify->delta);
        $this->assertEquals(1, $verify->deltaperiod);
        $this->assertEquals(array_values($roles), $verify->roles);

        //Verify the content of the messages created.
        $content = $verify->get_message(appraisal::ROLE_LEARNER);
        $this->assertEquals('Appraisal Overdue Reminder for Learner', $content->name);
        $this->assertEquals('Appraisal Overdue Reminder body for Learner', $content->content);
        $content = $verify->get_message(appraisal::ROLE_MANAGER);
        $this->assertEquals('Appraisal Overdue Reminder for Manager', $content->name);
        $this->assertEquals('Appraisal Overdue Reminder body for Manager', $content->content);
    }

    public function test_activate_appraisal() {
        global $DB;
        $this->resetAfterTest();

        /** @var totara_cohort_generator $cohortgenerator */
        $cohortgenerator = $this->getDataGenerator()->get_plugin_generator('totara_cohort');

        $appraisalid = $this->appraisal->id;

        // Create a stage for the appraisal.
        $stage = $this->appraisalgenerator->create_stage($appraisalid);

        // Create a page for the stage.
        $page = $this->appraisalgenerator->create_page($stage->id);

        // Create a page for the stage.
        $question = $this->appraisalgenerator->create_question($page->id);

        // Create cohort and assign a user to it.
        $learner = $this->getDataGenerator()->create_user(array('username' => 'user1'));
        $cohort = $cohortgenerator->create_cohort(array('name' => 'cohort1'));
        $cohortgenerator->cohort_assign_users($cohort->id, array($learner->id));

        // Assign cohort to the appraisal.
        $this->appraisalgenerator->create_group_assignment($this->appraisal, 'cohort', $cohort->id);

        // Check the appraisal is not active at this point.
        $this->assertEquals(appraisal::STATUS_DRAFT, $DB->get_field('appraisal', 'status', array('id' => $appraisalid)));

        // Activate appraisal.
        $this->appraisalgenerator->activate($appraisalid);

        // Check the appraisal is active.
        $this->assertEquals(appraisal::STATUS_ACTIVE, $DB->get_field('appraisal', 'status', array('id' => $appraisalid)));
    }

    public function test_create_large_appraisal() {
        global $DB;
        $this->resetAfterTest();

        $cohortgenerator = $this->getDataGenerator()->get_plugin_generator('totara_cohort');

        $appraisalid = $this->appraisal->id;

        $stage = $this->appraisalgenerator->create_stage($appraisalid);
        $page = $this->appraisalgenerator->create_page($stage->id);

        // MySQL has relatively low limits on number of varchar/text table columns, but should be able to deal with at least 180 text questions.
        $num_questions = 180;
        for ($i = 1; $i <= $num_questions; $i++) {
            $this->appraisalgenerator->create_question($page->id, ['datatype' => 'text']);
        }

        $questions = $DB->get_records('appraisal_quest_field', array('appraisalstagepageid' => $page->id));
        $this->assertEquals($num_questions, count($questions));

        // Create cohort and assign a user to it.
        $learner = $this->getDataGenerator()->create_user(array('username' => 'user1'));
        $cohort = $cohortgenerator->create_cohort(array('name' => 'cohort1'));
        $cohortgenerator->cohort_assign_users($cohort->id, array($learner->id));

        // Assign cohort to the appraisal.
        $this->appraisalgenerator->create_group_assignment($this->appraisal, 'cohort', $cohort->id);

        // Check the appraisal is not active at this point.
        $this->assertEquals(appraisal::STATUS_DRAFT, $DB->get_field('appraisal', 'status', array('id' => $appraisalid)));

        // Activate appraisal.
        $this->appraisalgenerator->activate($appraisalid);

        // Check the appraisal is active.
        $this->assertEquals(appraisal::STATUS_ACTIVE, $DB->get_field('appraisal', 'status', array('id' => $appraisalid)));
    }
}
