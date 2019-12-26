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
 * @author Maria Torres <maria.torres@totaralms.com>
 * @package totara_core
 */

global $CFG;
require_once($CFG->dirroot . '/totara/reportbuilder/tests/reportcache_advanced_testcase.php');

class totara_core_messaging_testcase extends advanced_testcase {

    /** @var totara_plan_generator $plangenerator */
    private $plangenerator = null;

    /** @var totara_program_generator $programgenerator */
    private $programgenerator = null;

    private $user1, $user2, $user3, $manager1, $manager2;

    protected function tearDown() {
        $this->plangenerator = null;
        $this->programgenerator = null;
        $this->user1 = $this->user2 = $this->user3 = null;
        $this->manager1 = $this->manager2 = null;

        parent::tearDown();
    }

    public function setUp() {
        parent::setup();

        $this->programgenerator = $this->getDataGenerator()->get_plugin_generator('totara_program');
        $this->plangenerator = $this->getDataGenerator()->get_plugin_generator('totara_plan');
        $this->audiencegenerator = $this->getDataGenerator()->get_plugin_generator('totara_cohort');

        // Create some users to work with.
        $this->user1 = $this->getDataGenerator()->create_user(array('email' => 'user1@example.com'));
        $this->user2 = $this->getDataGenerator()->create_user(array('email' => 'user2@example.com'));
        $this->user3 = $this->getDataGenerator()->create_user(array('email' => 'user3@example.com'));

        $this->manager1 = $this->getDataGenerator()->create_user(array('email' => 'manager1@example.com'));
        $this->manager2 = $this->getDataGenerator()->create_user(array('email' => 'manager2@example.com'));

        // Assign managers to students.
        $manager1ja = \totara_job\job_assignment::create_default($this->manager1->id);
        $manager2ja = \totara_job\job_assignment::create_default($this->manager2->id);
        \totara_job\job_assignment::create_default($this->user1->id, array('managerjaid' => $manager1ja->id));
        \totara_job\job_assignment::create_default($this->user2->id, array('managerjaid' => $manager2ja->id));
        \totara_job\job_assignment::create_default($this->user3->id, array('managerjaid' => $manager1ja->id));
    }

    /**
     * Test from user is correctly set according to settings.
     */
    public function test_messages_from_no_reply() {
        global $USER, $CFG;
        $this->preventResetByRollback();
        $this->resetAfterTest(true);
        $this->setAdminUser();

        $noreplyaddress = 'no-reply@example.com';

        // Set the no reply address.
        set_config('noreplyaddress', $noreplyaddress);

        $sink = $this->redirectEmails();

        // Messages in Programs.
        $program1 = $this->programgenerator->create_program();
        $this->programgenerator->assign_program($program1->id, array($this->user1->id, $this->user2->id));

        $this->waitForSecond(); // Messages are only sent if they were created before "now", so we need to wait one second.

        // Attempt to send any program messages.
        $task = new \totara_program\task\send_messages_task();
        ob_start(); // Start a buffer to catch all the mtraces in the task.
        $task->execute();
        ob_end_clean();

        // Check user from.
        $fromuser = $USER;
        $expectedname = fullname($fromuser);
        $expectedemail = $noreplyaddress;
        $checkformat = '%s (%s)';
        $expected = sprintf($checkformat, $expectedname, $expectedemail);

        // Check that that one email was sent and the from adress corresponds to the noreply address.
        $emails = $sink->get_messages();
        $this->assertCount(2, $emails);
        foreach ($emails as $email) {
            $actual = sprintf($checkformat, $email->fromname, $email->from);
            $this->assertEquals($expected, $actual);
        }
        $sink->clear();

        // Messages in Learning plan.
        $sink = $this->redirectEmails();
        $plan = $this->plangenerator->create_learning_plan(array('userid' => $this->user1->id));
        $this->plangenerator->create_learning_plan_objective($plan->id, $this->user1->id, null);

        // Check emails.
        $emails = $sink->get_messages();
        $this->assertCount(1, $emails);
        foreach ($emails as $email) {
            $actual = sprintf($checkformat, $email->fromname, $email->from);
            $this->assertEquals($expected, $actual);
        }
        $sink->clear();
    }
}
