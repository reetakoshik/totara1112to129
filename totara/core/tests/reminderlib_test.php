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
 * @author Brendan Cox <brendan.cox@totaralearning.com>
 * @package core_reminder
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/reminderlib.php');

/**
 * Class core_reminderlib_testcase
 *
 * This tests the functions and methods within lib/reminderlib.php. Put here to avoid conflicts with
 * any merges we make from Moodle.
 */
class core_reminderlib_testcase extends advanced_testcase {

    protected function setUp() {
        parent::setUp();
        $this->resetAfterTest(true);
    }

    /**
     * Creates a generic reminder for testing.
     *
     * @return reminder
     */
    private function create_reminder() {
        $course = $this->getDataGenerator()->create_course();
        $admin = get_admin();

        // Create a reminder.
        $remindersettings = array(
            'courseid' => $course->id,
            'title' => 'Test reminder',
            'type' => 'completion',
            'timecreated' => time(),
            'timemodified' => time(),
            'modifierid' => $admin->id,
            'deleted' => 0
        );
        $reminder = new reminder($remindersettings, false);
        $reminder->insert();

        return $reminder;
    }

    /**
     * Tests the has_message_with_period_greater_or_equal() method.
     */
    public function test_has_message_with_period_greater_or_equal() {

        // Create a bunch of reminders where the messages have various period settings.

        $reminder1 = $this->create_reminder();
        // Create the messages.
        $messagetypes = array(0 => 'invitation', 2 => 'reminder', 5 => 'escalation');
        foreach ($messagetypes as $period => $mtype) {
            $message = new reminder_message(
                array(
                    'reminderid'    => $reminder1->id,
                    'type'          => $mtype,
                    'deleted'       => 0,
                    'period'        => $period,
                )
            );
            $message->insert();
        }

        $reminder2 = $this->create_reminder();
        // Create the messages.
        $messagetypes = array(0 => 'invitation', 2 => 'reminder', 3 => 'escalation');
        foreach ($messagetypes as $period => $mtype) {
            $message = new reminder_message(
                array(
                    'reminderid'    => $reminder2->id,
                    'type'          => $mtype,
                    'deleted'       => 0,
                    'period'        => $period,
                )
            );
            $message->insert();
        }

        $reminder3 = $this->create_reminder();
        // Create the messages.
        $messagetypes = array(0 => 'invitation', 2 => 'reminder', 7 => 'escalation');
        foreach ($messagetypes as $period => $mtype) {
            if ($mtype === 'escalation') {
                // Sometimes the escalation might have been deleted when someone ticks 'Don't send this message'.
                $message = new reminder_message(
                    array(
                        'reminderid'    => $reminder3->id,
                        'type'          => $mtype,
                        'deleted'       => 1,
                        'period'        => $period,
                    )
                );
            } else {
                $message = new reminder_message(
                    array(
                        'reminderid' => $reminder3->id,
                        'type' => $mtype,
                        'deleted' => 0,
                        'period' => $period,
                    )
                );
            }
            $message->insert();
        }

        // Now check that the method we're testing returns the correct result for each.

        $this->assertTrue($reminder1->has_message_with_period_greater_or_equal(4));
        $this->assertFalse($reminder1->has_message_with_period_greater_or_equal(10));

        // Reminder 2 does not contain messages with period greater than 4.
        $this->assertFalse($reminder2->has_message_with_period_greater_or_equal(4));
        // But it does contain a message with a period equal to 3.
        $this->assertTrue($reminder2->has_message_with_period_greater_or_equal(3));

        // Reminder 3 shouldn't return true against 4 days, because the only message with a
        // period greater than 4 is deleted.
        $this->assertFalse($reminder3->has_message_with_period_greater_or_equal(4));
        // But it does have a reminder message equal to 2 days.
        $this->assertTrue($reminder3->has_message_with_period_greater_or_equal(2));
    }
}