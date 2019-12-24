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
 * @author Sam Hemelryk <sam.hemelryk@totaralms.com>
 * @package totara_core
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/totara/reportbuilder/tests/reportcache_advanced_testcase.php');
require_once($CFG->libdir.'/reminderlib.php');
require_once($CFG->libdir.'/completionlib.php');
require_once($CFG->dirroot . '/completion/criteria/completion_criteria_self.php');

/**
 * Send reminder messages task tests.
 */
class totara_core_task_send_reminder_messages_test extends reportcache_advanced_testcase {

    /** @var stdClass */
    protected $course;
    /** @var stdClass */
    protected $feedback;
    /** @var reminder */
    protected $reminder;
    /** @var stdClass */
    protected $manager;
    /** @var stdClass */
    protected $learner1;
    /** @var stdClass */
    protected $learner2;
    /** @var stdClass */
    protected $learner3;

    protected function tearDown() {
        $this->course = null;
        $this->feedback = null;
        $this->reminder = null;
        $this->manager = null;
        $this->learner1 = null;
        $this->learner2 = null;
        $this->learner3 = null;
        parent::tearDown();
    }

    /**
     * Set up for each test.
     */
    public function setUp() {
        global $CFG, $DB;

        // We must reset after this test.
        $this->resetAfterTest();
        // Completion must be enabled.
        $CFG->enablecompletion = true;

        // Grab a generator, we're going to need this.
        $generator = $this->getDataGenerator();

        // Generate a course with completion enabled and set to start on enrol.
        $coursedefaults = array(
            'enablecompletion' => COMPLETION_ENABLED,
            'completionstartonenrol' => 1,
            'completionprogressonview' => 1
        );
        $this->course = $generator->create_course($coursedefaults, array('createsections' => true));

        // Generate a feedback module. Needed for reminders.
        $this->feedback = $generator->create_module('feedback', array('course' => $this->course->id), array(
            'completion' => COMPLETION_TRACKING_AUTOMATIC,
            'completionview' => COMPLETION_VIEW_REQUIRED
        ));
        // Create reminders for the course now that we have a feedback module.
        $this->reminder = $this->create_reminder($this->feedback->id, $this->course->id);

        // Set self completion = true for this course.
        $criterion = new completion_criteria_self();
        $criterion->update_config((object)array(
            'criteria_self' => 1,
            'criteria_self_value' => 1,
            'id' => $this->course->id
        ));

        // Create a manager and a learner with that same position assignment.
        $this->manager = $generator->create_user(array('username' => 'manager'));
        $this->learner1 = $generator->create_user(array('username' => 'learner1', 'managerid' => $this->manager->id));
        $this->learner2 = $generator->create_user(array('username' => 'learner2', 'managerid' => $this->manager->id));
        $this->learner3 = $generator->create_user(array('username' => 'learner3', 'managerid' => $this->manager->id));

        // Give each user a second manager.
        $secondmanager = $generator->create_user(['username' => 'butter_manager']);
        $managerja = \totara_job\job_assignment::create_default($secondmanager->id);
        \totara_job\job_assignment::create_default($this->learner1->id, array('managerjaid' => $managerja->id, 'fullname' => 'Head banana ripener'));
        \totara_job\job_assignment::create_default($this->learner2->id, array('managerjaid' => $managerja->id, 'fullname' => 'Internet explorer'));

        // Enrol the user into the course.
        $generator->enrol_user($this->learner1->id, $this->course->id);
        $generator->enrol_user($this->learner2->id, $this->course->id);
        $generator->enrol_user($this->learner3->id, $this->course->id);

        // Test the reminder structure.
        $reminders = get_course_reminders($this->course->id);
        $this->assertCount(1, $reminders);
        $reminder = reset($reminders);
        $messages = $reminder->get_messages();
        $this->assertCount(3, $messages);

        // Test that there is no completion for this course yet.
        $completioninfo = new completion_info($this->course);
        $this->assertEquals(COMPLETION_ENABLED, $completioninfo->is_enabled());
        $this->assertEquals(0, $DB->count_records('course_completion_crit_compl'),'Record count mismatch for completion');
        $this->assertTrue($completioninfo->is_tracked_user($this->learner1));
        $this->assertTrue($completioninfo->is_tracked_user($this->learner2));
    }

    /**
     * Creates a reminder and the default message types.
     *
     * @param int $feedbackid
     * @param int $courseid
     * @param array $reminderparams
     * @param array $messageparams
     * @return reminder
     */
    protected function create_reminder($feedbackid, $courseid, array $reminderparams = array(), array $messageparams = array()) {
        global $USER;

        $mod = get_coursemodule_from_instance('feedback', $feedbackid);

        $config = array(
            'tracking' => 0,
            'requirement' => $mod->id
        );

        $reminder = new reminder();
        // Create the reminder object
        $reminder->timemodified = time();
        $reminder->modifierid = $USER->id;
        $reminder->deleted = '0';
        $reminder->title = 'Test reminder';
        $reminder->type = 'completion';
        $reminder->config = serialize($config);
        $reminder->timecreated = time() - (DAYSECS * 5);
        foreach ($reminderparams as $key => $value) {
            $reminder->$key = $value;
        }
        $reminder->courseid = $courseid;
        $reminder->id = $reminder->insert();

        // Create the messages
        $messageproperties = array(
            'subject',
            'message',
            'period',
            'dontsend',
            'copyto', // skipmanager
            'deleted',
        );
        foreach (array('invitation', 'reminder', 'escalation') as $type) {
            $message = new reminder_message(
                array(
                    'reminderid'    => $reminder->id,
                    'type'          => $type,
                    'deleted'       => 0
                )
            );
            $message->period = 0;
            $message->copyto = '';
            $message->subject = 'Subject for type '.$type;
            $message->message = 'Message for type '.$type;
            $message->deleted = 0;

            foreach ($messageproperties as $key) {
                if (isset($messageparams[$key])) {
                    $message->$key = $messageparams[$key];
                }
            }
            if (!$message->insert()) {
                throw new coding_exception('Failed to create course reminder message');
            }
        }

        return $reminder;
    }

    /**
     * Test that no notifications get sent on a fresh site.
     */
    public function test_no_notifications_by_default() {
        $sink = $this->redirectMessages();
        $task = new \totara_core\task\send_reminder_messages_task();
        ob_start();
        $task->execute();
        $output = ob_get_clean();

        $this->assertSame(0, $sink->count());
        $this->assertContains('no users to send invitation message', $output);
        $this->assertContains('no users to send reminder message', $output);
        $this->assertContains('no users to send escalation message', $output);
        $sink->close();
    }

    /**
     * Test course reminders get sent.
     */
    public function test_reminders_get_sent() {
        $sink = $this->redirectMessages();

        // Make user1 to complete the certification with completion date 1 day before today.
        $completion = new completion_info($this->course);
        $completion = $completion->get_completion($this->learner1->id, COMPLETION_CRITERIA_TYPE_SELF);
        $this->assertFalse($completion->is_complete());
        $completion->mark_complete(time() - DAYSECS);
        $this->assertTrue($completion->is_complete());

        $task = new \totara_core\task\send_reminder_messages_task();
        ob_start();
        $task->execute();
        $output = ob_get_clean();

        // There should be four messages, three to the learner and 1 to the learners manager.
        $this->assertSame(4, $sink->count());
        $this->assertContains('1 "invitation" type messages sent', $output);
        $this->assertContains('1 "reminder" type messages sent', $output);
        $this->assertContains('2 "escalation" type messages sent', $output);

        // Make sure that an escalation email did indeed go to the manager.
        $messages = $sink->get_messages();
        $managergotemail = false;
        foreach($messages as $message) {
            if (($message->subject === 'Subject for type escalation') and ($message->useridto == $this->manager->id)) {
                $managergotemail = true;
            }
        }
        $this->assertTrue($managergotemail);

        $sink->close();
    }

    /**
     * Test course reminders do not get sent to users who were unenrolled or whose enrolments were
     * suspended. But this should not prevent messages to a valid user.
     */
    public function test_reminders_not_sent_to_unenrolled_or_suspended() {
        global $DB;

        $sink = $this->redirectMessages();

        $completion = new completion_info($this->course);
        $completion = $completion->get_completion($this->learner1->id, COMPLETION_CRITERIA_TYPE_SELF);
        $this->assertFalse($completion->is_complete());
        $completion->mark_complete(time() - DAYSECS);
        $this->assertTrue($completion->is_complete());

        $completion = new completion_info($this->course);
        $completion = $completion->get_completion($this->learner2->id, COMPLETION_CRITERIA_TYPE_SELF);
        $this->assertFalse($completion->is_complete());
        $completion->mark_complete(time() - DAYSECS);
        $this->assertTrue($completion->is_complete());

        $completion = new completion_info($this->course);
        $completion = $completion->get_completion($this->learner3->id, COMPLETION_CRITERIA_TYPE_SELF);
        $this->assertFalse($completion->is_complete());
        $completion->mark_complete(time() - DAYSECS);
        $this->assertTrue($completion->is_complete());

        $plugin = enrol_get_plugin('manual');
        $enrolinstance = $DB->get_record('enrol', array('courseid' => $this->course->id, 'enrol' => 'manual'));

        $context = context_course::instance($this->course->id);

        // Keep learner 1 fully enrolled.
        // Suspend learner 2's enrolment.
        $plugin->update_user_enrol($enrolinstance, $this->learner2->id, ENROL_USER_SUSPENDED);
        // Unenrol learner 3.
        $plugin->unenrol_user($enrolinstance, $this->learner3->id);

        // Let's check the enrolments are correct.
        // Learner 1 continues to have an active enrolment.
        $this->assertTrue(is_enrolled($context, $this->learner1, '', true)); // Check for active enrolment only.
        // Learner 2's enrolment is suspended.
        $this->assertTrue(is_enrolled($context, $this->learner2, '', false)); // Check for any enrolment.
        $this->assertFalse(is_enrolled($context, $this->learner2, '', true)); // Check for active enrolment only.
        // Learner 3 is unenrolled.
        $this->assertFalse(is_enrolled($context, $this->learner3, '', false)); // Check for any enrolment.

        $task = new \totara_core\task\send_reminder_messages_task();
        ob_start();
        $task->execute();
        $output = ob_get_clean();

        // There should be four messages, three to the learner and 1 to the learners manager.
        $this->assertSame(4, $sink->count());
        $this->assertContains('1 "invitation" type messages sent', $output);
        $this->assertContains('1 "reminder" type messages sent', $output);
        $this->assertContains('2 "escalation" type messages sent', $output);

        // Make sure that an escalation email did indeed go to the manager.
        $messages = $sink->get_messages();
        $managergotemail = false;
        foreach($messages as $message) {
            if (($message->subject === 'Subject for type escalation') and ($message->useridto == $this->manager->id)) {
                $managergotemail = true;
            } else if ($message->useridto != $this->learner1->id) {
                // If it's not for the manager, then these should be for learner1. Learner2 or 3 should not get any message.
                $this->fail('Messages sent to learner that were not to learner1.');
            }
        }
        $this->assertTrue($managergotemail);

        $sink->close();
    }

    /**
     * Test that we don't send backdated escalation notices when changing the escalation dontsend option.
     */
    public function test_changing_escalation_nosend_value() {
        global $DB;

        $sink = $this->redirectMessages();

        // Disable the escalation reminder.
        $config = unserialize($this->reminder->config);
        $config['escalationmodified'] = time() - (DAYSECS * 2);
        $this->reminder->config = serialize($config);
        $this->reminder->update();
        $messages = $this->reminder->get_messages();
        foreach ($messages as $message) {
            /* @var reminder_message $message */
            if ($message->type === 'escalation' && empty($message->deleted)) {
                $message->deleted = 1;
                $message->update();
                break;
            }
        }

        // Mark learner1 as complete.
        $coursecompletion = new completion_info($this->course);
        $completion = $coursecompletion->get_completion($this->learner1->id, COMPLETION_CRITERIA_TYPE_SELF);
        $this->assertFalse($completion->is_complete());
        $completion->mark_complete(time() - DAYSECS);
        $this->assertTrue($completion->is_complete());

        // Trigger the task for the first time.
        // The user has been marked complete so they should receive invitation and reminder type messages.
        $task = new \totara_core\task\send_reminder_messages_task();
        ob_start();
        $task->execute();
        $output = ob_get_clean();
        $this->assertSame(2, $sink->count());
        $this->assertContains('1 "invitation" type messages sent', $output);
        $this->assertContains('1 "reminder" type messages sent', $output);
        $this->assertNotContains('escalation', $output);
        // Clear the sink we don't want to keep track of this.
        $sink->clear();

        // Update the reminder and create the new message escalation.
        $config = unserialize($this->reminder->config);
        $config['escalationmodified'] = time() - (DAYSECS / 2);
        $this->reminder->config = serialize($config);
        $this->reminder->update();
        // This is truly horrid but it IS how it actually works.
        // We need to insert a second escalation reminder message with delete = 0.
        unset($message->id);
        $message->deleted = 0;
        $message->insert();

        // Trigger the task for the second time. The invitation and reminder messages have already been sent
        // and we've only just enabled the escalation reminder again so this should result in no notifications.
        $task = new \totara_core\task\send_reminder_messages_task();
        ob_start();
        $task->execute();
        $output = ob_get_clean();
        $this->assertSame(0, $sink->count());
        $this->assertContains('no users to send invitation message', $output);
        $this->assertContains('no users to send reminder message', $output);
        $this->assertContains('no users to send escalation message', $output);

        // Now mark learner 2 as complete.
        $completion = $coursecompletion->get_completion($this->learner2->id, COMPLETION_CRITERIA_TYPE_SELF);
        $this->assertFalse($completion->is_complete());
        $completion->mark_complete(time() - (DAYSECS / 4));
        $this->assertTrue($completion->is_complete());

        // Trigger the task for the third time.
        $task = new \totara_core\task\send_reminder_messages_task();
        ob_start();
        $task->execute();
        $output = ob_get_clean();
        // There should be four messages, three to the learner and 1 to the learners first manager.
        // The learner has a second manager, but only the first should get messaged.
        $this->assertSame(4, $sink->count());
        $this->assertContains('1 "invitation" type messages sent', $output);
        $this->assertContains('1 "reminder" type messages sent', $output);
        $this->assertContains('2 "escalation" type messages sent', $output);

        $sink->close();
    }

    /**
     * Tests the effect of the admin setting reminder_maxtimesincecompletion.
     *
     * By default, there should be no limit.
     */
    public function test_maxtimesincecompletion_default_no_limit() {
        global $CFG;

        $messages = $this->reminder->get_messages();
        // Set some different values for the period (number of days after completion) for the messages.
        foreach ($messages as $message) {
            /* @var reminder_message $message */
            if ($message->type === 'reminder') {
                $message->period = 1;
                $message->update();
                continue;
            }
            /* @var reminder_message $message */
            if ($message->type === 'escalation') {
                $message->period = 2;
                $message->update();
                continue;
            }
        }

        // By default, the setting we're testing should not be empty.
        // An empty value, such as where it's not set, represents no limit.
        $this->assertTrue(empty($CFG->reminder_maxtimesincecompletion));

        $sink = $this->redirectMessages();

        // Make learner1 complete the course with completion date 3 days before today.
        $completion = new completion_info($this->course);
        $completion = $completion->get_completion($this->learner1->id, COMPLETION_CRITERIA_TYPE_SELF);
        $this->assertFalse($completion->is_complete());
        $completion->mark_complete(time() - (3 * DAYSECS));
        $this->assertTrue($completion->is_complete());

        // Make learner2 complete the course with completion date before the reminder was created.
        // See setUp() to see that the reminder was created 5 days ago.
        $completion = new completion_info($this->course);
        $completion = $completion->get_completion($this->learner2->id, COMPLETION_CRITERIA_TYPE_SELF);
        $this->assertFalse($completion->is_complete());
        $completion->mark_complete(time() - (8 * DAYSECS));
        $this->assertTrue($completion->is_complete());

        // Add another user with a different completion time.
        $learner3 = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($learner3->id, $this->course->id);
        $completion = new completion_info($this->course);
        $completion = $completion->get_completion($learner3->id, COMPLETION_CRITERIA_TYPE_SELF);
        $this->assertFalse($completion->is_complete());
        $completion->mark_complete(time() - (1.5 * DAYSECS));
        $this->assertTrue($completion->is_complete());

        $task = new \totara_core\task\send_reminder_messages_task();
        ob_start();
        $task->execute();
        $output = ob_get_clean();

        // There should be 6 messages, 4 for learner1 and 2 for learner3.
        // There are none sent to learner2 because that user's completion date + period value (-8 + 2 = 6 days ago)
        // is before the reminder creation date (5 days ago).
        $this->assertSame(6, $sink->count());
        $this->assertContains('2 "invitation" type messages sent', $output);
        $this->assertContains('2 "reminder" type messages sent', $output);
        $this->assertContains('2 "escalation" type messages sent', $output);

        $sink->close();
    }

    /**
     * Tests the effect of the admin setting reminder_maxtimesincecompletion.
     *
     * When the setting is greater than zero, feedback is only sent for completions
     * where the date is less than that number of days ago.
     *
     * The effect of this limit would only be noticed with a reminder that is older than the
     * number of days specified.
     */
    public function test_maxtimesincecompletion_limit_less_than_reminder() {
        global $CFG;

        $messages = $this->reminder->get_messages();
        // Set some different values for the period (number of days after completion) for the messages.
        foreach ($messages as $message) {
            /* @var reminder_message $message */
            if ($message->type === 'reminder') {
                $message->period = 1;
                $message->update();
                continue;
            }
            /* @var reminder_message $message */
            if ($message->type === 'escalation') {
                $message->period = 2;
                $message->update();
                continue;
            }
        }

        // We set the limit to 2 days. The reminder creation date was set to 5 days ago,
        // so this limit won't be influenced by that.
        set_config('reminder_maxtimesincecompletion', 2);

        $sink = $this->redirectMessages();

        // Make learner1 complete the course with completion date 3 days before today.
        $completion = new completion_info($this->course);
        $completion = $completion->get_completion($this->learner1->id, COMPLETION_CRITERIA_TYPE_SELF);
        $this->assertFalse($completion->is_complete());
        $completion->mark_complete(time() - (3 * DAYSECS));
        $this->assertTrue($completion->is_complete());

        // Make learner2 complete the course with completion date before the reminder was created.
        // See setUp() to see that the reminder was created 5 days ago.
        $completion = new completion_info($this->course);
        $completion = $completion->get_completion($this->learner2->id, COMPLETION_CRITERIA_TYPE_SELF);
        $this->assertFalse($completion->is_complete());
        $completion->mark_complete(time() - (8 * DAYSECS));
        $this->assertTrue($completion->is_complete());

        // Add another user with a different completion time.
        $learner3 = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($learner3->id, $this->course->id);
        $completion = new completion_info($this->course);
        $completion = $completion->get_completion($learner3->id, COMPLETION_CRITERIA_TYPE_SELF);
        $this->assertFalse($completion->is_complete());
        $completion->mark_complete(time() - (1.5 * DAYSECS));
        $this->assertTrue($completion->is_complete());

        $task = new \totara_core\task\send_reminder_messages_task();
        ob_start();
        $task->execute();
        $output = ob_get_clean();

        // There should be 2 messages since only learner3 had a completion within the last 2 days.
        // At this stage they are only due the invitation and reminder, not yet the escalation.
        // There are none sent to learner1 because their completion date is before 2 days ago.
        // There are none sent to learner2 because that user's completion date + period value (-8 + 2 = 6 days ago)
        // is before the reminder creation date (5 days ago). Plus their completion is before the limit anyway.
        $this->assertSame(2, $sink->count());
        $this->assertContains('1 "invitation" type messages sent', $output);
        $this->assertContains('1 "reminder" type messages sent', $output);
        $this->assertContains('no users to send escalation message to', $output);

        $sink->close();
    }

    /**
     * Tests the effect of the admin setting reminder_maxtimesincecompletion.
     *
     * When the setting is greater than zero, feedback is only sent for completions
     * where the date is less than that number of days ago,
     * BUT still takes into account whether completions are before the reminder was created.
     *
     * The effect of this limit would not be noticed for a reminder while it has been created more recently
     * than the number of days specified.
     */
    public function test_maxtimesincecompletion_limit_more_than_reminder() {
        global $CFG;

        $messages = $this->reminder->get_messages();
        // Set some different values for the period (number of days after completion) for the messages.
        foreach ($messages as $message) {
            /* @var reminder_message $message */
            if ($message->type === 'reminder') {
                $message->period = 1;
                $message->update();
                continue;
            }
            /* @var reminder_message $message */
            if ($message->type === 'escalation') {
                $message->period = 2;
                $message->update();
                continue;
            }
        }

        // Now let's set the limit to 10 days. The reminder we're dealing with has a creation date of
        // only 5 days ago.
        set_config('reminder_maxtimesincecompletion', 10);

        $sink = $this->redirectMessages();

        // Make learner1 complete the course with completion date 3 days before today.
        $completion = new completion_info($this->course);
        $completion = $completion->get_completion($this->learner1->id, COMPLETION_CRITERIA_TYPE_SELF);
        $this->assertFalse($completion->is_complete());
        $completion->mark_complete(time() - (3 * DAYSECS));
        $this->assertTrue($completion->is_complete());

        // Make learner2 complete the course with completion date before the reminder was created.
        // See setUp() to see that the reminder was created 5 days ago.
        $completion = new completion_info($this->course);
        $completion = $completion->get_completion($this->learner2->id, COMPLETION_CRITERIA_TYPE_SELF);
        $this->assertFalse($completion->is_complete());
        $completion->mark_complete(time() - (8 * DAYSECS));
        $this->assertTrue($completion->is_complete());

        // Add another user with a different completion time.
        $learner3 = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($learner3->id, $this->course->id);
        $completion = new completion_info($this->course);
        $completion = $completion->get_completion($learner3->id, COMPLETION_CRITERIA_TYPE_SELF);
        $this->assertFalse($completion->is_complete());
        $completion->mark_complete(time() - (1.5 * DAYSECS));
        $this->assertTrue($completion->is_complete());

        $task = new \totara_core\task\send_reminder_messages_task();
        ob_start();
        $task->execute();
        $output = ob_get_clean();

        // The limit had no effect because the more recent reminder creation date applied instead.
        // There should be 6 messages, 4 for learner1 and 2 for learner3.
        // There are none sent to learner2 because that user's completion date + period value (-8 + 2 = 6 days ago)
        // is before the reminder creation date (5 days ago).
        $this->assertSame(6, $sink->count());
        $this->assertContains('2 "invitation" type messages sent', $output);
        $this->assertContains('2 "reminder" type messages sent', $output);
        $this->assertContains('2 "escalation" type messages sent', $output);

        $sink->close();
    }

    /**
     * We need to make sure that if a user has no job assignment, messages will still go out to the user.
     */
    public function test_no_manager_if_no_job_assignment() {
        global $DB;

        // Create a learner, the generator gives them a job assignment by default - delete that.
        $learner3 =  $this->getDataGenerator()->create_user();
        $learner3_ja = \totara_job\job_assignment::get_first($learner3->id);
        $DB->delete_records('job_assignment', array('id' => $learner3_ja->id));

        $sink = $this->redirectMessages();

        // Complete the course with completion date 1 day before today.
        $this->getDataGenerator()->enrol_user($learner3->id, $this->course->id);
        $completion = new completion_info($this->course);
        $completion = $completion->get_completion($learner3->id, COMPLETION_CRITERIA_TYPE_SELF);
        $this->assertFalse($completion->is_complete());
        $completion->mark_complete(time() - DAYSECS);
        $this->assertTrue($completion->is_complete());

        $task = new \totara_core\task\send_reminder_messages_task();
        ob_start();
        $task->execute();
        $output = ob_get_clean();

        // There should be three messages to the learner.
        $this->assertSame(3, $sink->count());
        $this->assertContains('1 "invitation" type messages sent', $output);
        $this->assertContains('1 "reminder" type messages sent', $output);
        $this->assertContains('1 "escalation" type messages sent', $output);

        // Do the run through to check the user got the escalation email, rather than there being an
        // attempt to send it to some non-existent manager.
        $messages = $sink->get_messages();
        $learnergotescalation = false;
        foreach($messages as $message) {
            if (($message->subject === 'Subject for type escalation') and ($message->useridto == $learner3->id)) {
                $learnergotescalation = true;
            }
        }
        $this->assertTrue($learnergotescalation);

        $sink->close();
    }
}