<?php
/**
 * This file is part of Totara LMS
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
 * @author Andrew McGhie <andrew.mcghie@totaralearning.com>
 * @package core_calendar
 */

use core_calendar\userdata\event_subscriptions;
use totara_userdata\userdata\target_user;

/**
 * Tests the {@see core_calendar\userdata\core_calendar_event_subscription} class
 *
 * @group totara_userdata
 */
class core_calendar_userdata_event_subscriptions_test extends advanced_testcase {

    /**
     * Makes 2 users and an array of subscriptions
     */
    private function get_data() {
        global $CFG;
        require_once($CFG->libdir . '/bennu/bennu.inc.php');
        require_once($CFG->dirroot . '/calendar/lib.php');
        $data = new class() {
            /** @var target_user */
            public $user1, $user2;
            /** @var array */
            public $user1subscriptions, $user2subscriptions;
        };
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $data->user1 = new target_user($user1);
        $data->user2 = new target_user($user2);

        // Set up subscription.
        $subscription = new stdClass();
        $subscription->name = 'Google Gmail';
        $subscription->importfrom = CALENDAR_IMPORT_FROM_FILE;
        $subscription->eventtype = 'user';
        $this->setUser($user1);
        $id = calendar_add_subscription($subscription);
        $sub = calendar_get_subscription($id);

        // Import events.
        $calendar = file_get_contents($CFG->dirroot . '/lib/tests/fixtures/google_gmail.ics');
        $ical = new iCalendar();
        $ical->unserialize($calendar);
        calendar_import_icalendar_events($ical, $sub->courseid, $sub->id);
        $data->user1subscriptions[] = $sub;

        // Set up subscription.
        $subscription = new stdClass();
        $subscription->name = 'Microsoft Outlook 2010';
        $subscription->importfrom = CALENDAR_IMPORT_FROM_FILE;
        $subscription->eventtype = 'site';
        $id = calendar_add_subscription($subscription);

        // Import event.
        $calendar = file_get_contents($CFG->dirroot . '/lib/tests/fixtures/ms_outlook_2010.ics');
        $ical = new iCalendar();
        $ical->unserialize($calendar);
        $sub = calendar_get_subscription($id);
        calendar_import_icalendar_events($ical, $sub->courseid, $sub->id);

        // Set up subscription.
        $subscription = new stdClass();
        $subscription->name = 'OSX Yosemite';
        $subscription->importfrom = CALENDAR_IMPORT_FROM_FILE;
        $subscription->eventtype = 'user';
        $this->setUser($user2);
        $id = calendar_add_subscription($subscription);
        $sub = calendar_get_subscription($id);

        // Import events.
        $calendar = file_get_contents($CFG->dirroot . '/lib/tests/fixtures/osx_yosemite.ics');
        $ical = new iCalendar();
        $ical->unserialize($calendar);
        calendar_import_icalendar_events($ical, $sub->courseid, $sub->id);
        $data->user2subscriptions[] = $sub;
        return $data;
    }

    /**
     * Check that the right data is deleted from the database.
     */
    public function test_purge_removes_subscriptions() {
        global $DB;
        $this->resetAfterTest();
        $data = $this->get_data();
        $systemcontext = context_system::instance();

        $this->assertEquals(
            event_subscriptions::RESULT_STATUS_SUCCESS,
            event_subscriptions::execute_purge($data->user1, $systemcontext)
        );

        $this->assertEquals(
            0,
            $DB->count_records('event_subscriptions', [
                'userid' => $data->user1->id,
                'eventtype' => 'user'
            ])
        );
        $this->assertEquals(
            count($data->user2subscriptions),
            event_subscriptions::execute_count($data->user2, $systemcontext)
        );
        $this->assertTrue(
            $DB->record_exists('event_subscriptions', [
                'userid' => $data->user1->id,
                'eventtype' => 'site'
            ])
        );
    }

    /**
     * No problems when the user has no subscriptions to purge.
     */
    public function test_purge_succeeds_when_no_subscriptions() {
        $this->resetAfterTest();
        $data = $this->get_data();
        $systemcontext = context_system::instance();
        $user = $this->getDataGenerator()->create_user();
        $usertarget = new target_user($user);
        $this->assertEquals(
            event_subscriptions::RESULT_STATUS_SUCCESS,
            event_subscriptions::execute_purge($usertarget, $systemcontext)
        );
    }

    /**
     * Check count is 0 after purging the data.
     */
    public function test_count_zero_after_purge() {
        $this->resetAfterTest();
        $data = $this->get_data();
        $systemcontext = context_system::instance();

        // User 1.
        $this->assertNotEquals(
            0,
            event_subscriptions::execute_count($data->user1, $systemcontext)
        );
        event_subscriptions::execute_purge($data->user1, $systemcontext);
        $this->assertEquals(
            0,
            event_subscriptions::execute_count($data->user1, $systemcontext)
        );

        // User 2.
        $this->assertNotEquals(
            0,
            event_subscriptions::execute_count($data->user2, $systemcontext)
        );
        event_subscriptions::execute_purge($data->user2, $systemcontext);
        $this->assertEquals(
            0,
            event_subscriptions::execute_count($data->user2, $systemcontext)
        );
    }

    /**
     * Check that the count returns the correct result.
     */
    public function test_count_correct_result() {
        $this->resetAfterTest();
        $data = $this->get_data();
        $systemcontext = context_system::instance();

        $this->assertEquals(
            count($data->user1subscriptions),
            event_subscriptions::execute_count($data->user1, $systemcontext)
        );
        $this->assertEquals(
            count($data->user2subscriptions),
            event_subscriptions::execute_count($data->user2, $systemcontext)
        );
    }

    /**
     * Test that purging works on a user that has being deleted.
     */
    public function test_purge_works_on_deleted_user() {
        global $DB;
        $this->resetAfterTest();
        $data = $this->get_data();
        $systemcontext = context_system::instance();

        $user = $DB->get_record('user', ['id' => $data->user1->id]);
        delete_user($user);
        $user = $DB->get_record('user', ['id' => $data->user1->id]);
        $deleteduser = new target_user($user);

        $this->assertEquals(
            event_subscriptions::RESULT_STATUS_SUCCESS,
            event_subscriptions::execute_purge($deleteduser, $systemcontext)
        );

        $this->assertEquals(
            0,
            $DB->count_records('event_subscriptions', [
                'userid' => $deleteduser->id,
                'eventtype' => 'user'
            ])
        );
    }

    /**
     * Tests that the count executes correctly when the user has being deleted.
     */
    public function test_count_works_on_deleted_user() {
        global $DB;
        $this->resetAfterTest();
        $data = $this->get_data();
        $systemcontext = context_system::instance();

        $countbefore = event_subscriptions::execute_count($data->user1, $systemcontext);

        $user = $DB->get_record('user', ['id' => $data->user1->id]);
        delete_user($user);
        $user = $DB->get_record('user', ['id' => $data->user1->id]);
        $deleteduser = new target_user($user);

        $this->assertEquals(
            $countbefore,
            event_subscriptions::execute_count($deleteduser, $systemcontext)
        );

        $nocontextuser = new target_user($user);

        $this->assertEquals(
            $countbefore,
            event_subscriptions::execute_count($nocontextuser, $systemcontext)
        );
    }
}
