<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2017 onwards Totara Learning Solutions LTD
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
 * @author Brendan Cox <brendan.cox@totaralearning.com>
 * @package totara_program
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/totara/program/program_message.class.php');

/**
 * Class totara_program_message_testcase
 *
 * Tests the classes and their methods that are in program_message.class.php.
 */
class totara_program_message_testcase extends advanced_testcase {

    /**
     * @var phpunit_message_sink
     */
    private $messagesink;

    /**
     * @var stdClass
     */
    private $user1;

    /**
     * @var program
     */
    private $program1;

    public function setUp() {
        parent::setUp();
        $this->resetAfterTest();
        $this->messagesink = $this->redirectMessages();
        $this->user1 = $this->getDataGenerator()->create_user();
        /** @var totara_program_generator $programgenerator */
        $programgenerator = $this->getDataGenerator()->get_plugin_generator('totara_program');
        $this->program1 = $programgenerator->create_program(array('fullname' => 'Program One'));

        // Clear the sink of any messages in case any were created by the processes above.
        $this->messagesink->clear();
    }

    public function tearDown() {
        $this->messagesink->clear();
        $this->messagesink->close();
        $this->messagesink = null;
        $this->user1 = null;
        $this->program1 = null;
        parent::tearDown();
    }

    /**
     * Use this class to send a generic non event-based message. The class prog_noneventbased_message is
     * abtract, but we're not interested in the functioning of its subclasses here as we're just testing
     * methods in the abtract class itself. So this creates a mock object based on the class.
     *
     * @param $user
     * @param $program
     * @param null $message
     * @param null $subject
     */
    private function send_noneventbased_message($user, $program, $message = null, $subject = null) {
        $messageob = new stdClass();
        $messageob->id = 0;
        $messageob->programid = $program->id;
        $messageob->sortorder = 0;
        $messageob->messagesubject = (empty($subject) ? 'TestSubject1' : $subject);
        $messageob->mainmessage = (empty($message) ? 'TestMessage1' : $message);
        $messageob->notifymanager = false;
        $messageob->managersubject = '';
        $messageob->managermessage = '';
        $messageob->triggertime = 0;

        /** @var prog_noneventbased_message $stub - this will be a subclass of the given abstract class */
        $stub = $this->getMockForAbstractClass('prog_noneventbased_message', array($program->id, $messageob));
        $stub->send_message($user);
    }

    /**
     * Send a message with no placeholders.
     */
    public function test_noneventbased_message_replacements_no_placeholders() {
        $this->send_noneventbased_message($this->user1, $this->program1,'TestMessage1');

        $messages = $this->messagesink->get_messages();
        $this->assertCount(1, $messages);
        $this->assertEquals('TestMessage1', $messages[0]->fullmessage);
    }

    /**
     * Send a message with the %programfullname% placeholder.
     */
    public function test_noneventbased_message_replacements_program_fullname() {
        $messagetext = 'TestMessage1 %programfullname%';
        $this->send_noneventbased_message($this->user1, $this->program1, $messagetext);

        $messages = $this->messagesink->get_messages();
        $this->assertCount(1, $messages);
        $this->assertEquals('TestMessage1 Program One', $messages[0]->fullmessage);
    }

    /**
     * Send a message with the %duedate% placeholder, but no due date is set.
     */
    public function test_noneventbased_message_replacements_duedate_notset() {
        /** @var totara_program_generator $programgenerator */
        $programgenerator = $this->getDataGenerator()->get_plugin_generator('totara_program');
        $programgenerator->assign_to_program($this->program1->id, ASSIGNTYPE_INDIVIDUAL, $this->user1->id,
            array('completiontime' => COMPLETION_TIME_NOT_SET), true);

        $messagetext = 'TestMessage1 %duedate%';
        $this->send_noneventbased_message($this->user1, $this->program1, $messagetext);

        $messages = $this->messagesink->get_messages();
        $this->assertCount(1, $messages);
        $this->assertEquals('TestMessage1 No due date set', $messages[0]->fullmessage);
    }

    /**
     * Send a message with the %duedate% placeholder and have a due date set.
     */
    public function test_noneventbased_message_replacements_duedate_set() {
        /** @var totara_program_generator $programgenerator */
        $programgenerator = $this->getDataGenerator()->get_plugin_generator('totara_program');
        $programgenerator->assign_to_program($this->program1->id, ASSIGNTYPE_INDIVIDUAL, $this->user1->id,
            array('completiontime' => '05/05/2030'), true);

        $messagetext = 'TestMessage1 %duedate%';
        $this->send_noneventbased_message($this->user1, $this->program1, $messagetext);

        $messages = $this->messagesink->get_messages();
        $this->assertCount(1, $messages);
        $this->assertEquals('TestMessage1 05/05/2030', $messages[0]->fullmessage);
    }

    /**
     * Send a message with the %completioncriteria% placeholder, but no completion criteria has been set.
     */
    public function test_noneventbased_message_replacements_completioncriteria_notset() {
        /** @var totara_program_generator $programgenerator */
        $programgenerator = $this->getDataGenerator()->get_plugin_generator('totara_program');
        $programgenerator->assign_to_program($this->program1->id, ASSIGNTYPE_INDIVIDUAL, $this->user1->id,
            array('completiontime' => COMPLETION_TIME_NOT_SET), true);

        $messagetext = 'TestMessage1 %completioncriteria%';
        $this->send_noneventbased_message($this->user1, $this->program1, $messagetext);

        $messages = $this->messagesink->get_messages();
        $this->assertCount(1, $messages);
        $this->assertEquals('TestMessage1 Completion criteria not defined', $messages[0]->fullmessage);
    }

    /**
     * Send a message with the %completioncriteria% placeholder and some criteria has been set.
     */
    public function test_noneventbased_message_replacements_completioncriteria_set() {
        /** @var totara_program_generator $programgenerator */
        $programgenerator = $this->getDataGenerator()->get_plugin_generator('totara_program');
        $programgenerator->assign_to_program($this->program1->id, ASSIGNTYPE_INDIVIDUAL, $this->user1->id,
            array('completiontime' => '5 ' . TIME_SELECTOR_DAYS, 'completionevent' => COMPLETION_EVENT_ENROLLMENT_DATE), true);

        $messagetext = 'TestMessage1 %completioncriteria%';
        $this->send_noneventbased_message($this->user1, $this->program1, $messagetext);

        $messages = $this->messagesink->get_messages();
        $this->assertCount(1, $messages);
        $this->assertEquals('TestMessage1 Complete within 5 Day(s) of Program enrollment date ', $messages[0]->fullmessage);
    }

    /**
     * Assign a user via two methods, each completion criteria. Send a message with the %completioncriteria% placeholder.
     *
     * This tests for a bug where a debugging error was thrown because more than one prog_assignment record
     * was found.
     */
    public function test_noneventbased_message_replacements_completioncriteria_multiple() {
        /** @var totara_program_generator $programgenerator */
        $programgenerator = $this->getDataGenerator()->get_plugin_generator('totara_program');
        $programgenerator->assign_to_program($this->program1->id, ASSIGNTYPE_INDIVIDUAL, $this->user1->id,
            array('completiontime' => '5 ' . TIME_SELECTOR_DAYS, 'completionevent' => COMPLETION_EVENT_ENROLLMENT_DATE), true);

        // The latest assignment will be used for the displayed completion criteria. Sleep for 1 second so
        // the timestamps are different.
        $this->waitForSecond();

        $audience1 = $this->getDataGenerator()->create_cohort();
        /** @var totara_cohort_generator $cohortgenerator */
        $cohortgenerator = $this->getDataGenerator()->get_plugin_generator('totara_cohort');
        $cohortgenerator->cohort_assign_users($audience1->id, array($this->user1->id));

        $programgenerator->assign_to_program($this->program1->id, ASSIGNTYPE_COHORT, $audience1->id,
            array('completiontime' => '8 ' . TIME_SELECTOR_DAYS, 'completionevent' => COMPLETION_EVENT_ENROLLMENT_DATE), true);

        $messagetext = 'TestMessage1 %completioncriteria%';
        $this->send_noneventbased_message($this->user1, $this->program1, $messagetext);

        $messages = $this->messagesink->get_messages();
        $this->assertCount(1, $messages);
        $this->assertEquals('TestMessage1 Complete within 8 Day(s) of Program enrollment date ', $messages[0]->fullmessage);
    }
}