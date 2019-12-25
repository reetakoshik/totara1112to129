<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Unit tests for core\notification.
 *
 * @package   core
 * @category  phpunit
 * @copyright 2016 Andrew Nicols <andrew@nicols.co.uk>
 * @author    2017 Joby Harding <joby.harding@totaralearining.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Unit tests for core\notification.
 *
 * @package   core
 * @category  phpunit
 * @category  phpunit
 * @copyright 2016 Andrew Nicols <andrew@nicols.co.uk>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class core_notification_testcase extends advanced_testcase {

    /**
     * Setup required for all notification tests.
     *
     * This includes emptying the list of notifications on the session, resetting any session which exists, and setting
     * up a new moodle_page object.
     */
    public function setUp() {
        global $PAGE, $SESSION;
        $this->resetAfterTest(); //Totara: ignore Moodle SESSION hacks!

        parent::setUp();
        $PAGE = new moodle_page();
        \core\session\manager::init_empty_session();
        $SESSION->notifications = [];
    }

    /**
     * Test the way in which notifications are added to the session in different stages of the page load.
     */
    public function test_add_during_output_stages() {
        global $PAGE, $SESSION;

        \core\notification::add('Example before header', \core\notification::INFO);
        $this->assertCount(1, $SESSION->notifications);

        $PAGE->set_state(\moodle_page::STATE_PRINTING_HEADER);
        \core\notification::add('Example during header', \core\notification::INFO);
        $this->assertCount(2, $SESSION->notifications);

        $PAGE->set_state(\moodle_page::STATE_IN_BODY);
        \core\notification::add('Example in body', \core\notification::INFO);
        $this->expectOutputRegex('/Example in body/');
        $this->assertCount(2, $SESSION->notifications);

        $PAGE->set_state(\moodle_page::STATE_DONE);
        \core\notification::add('Example after page', \core\notification::INFO);
        $this->assertCount(3, $SESSION->notifications);
    }

    /**
     * Test fetching of notifications from the session.
     */
    public function test_fetch() {
        // Initially there won't be any notifications.
        $this->assertCount(0, \core\notification::fetch());

        // Adding a notification should make one available to fetch.
        \core\notification::success('Notification created');
        $this->assertCount(1, \core\notification::fetch());
        $this->assertCount(0, \core\notification::fetch());
    }

    /**
     * Test that session notifications are persisted across session clears.
     */
    public function test_session_persistance() {
        global $PAGE, $SESSION;

// Totara: we do not want this stuff to break tests all over the place, this hack is disabled in tests.
return;
        // Initially there won't be any notifications.
        $this->assertCount(0, $SESSION->notifications);

        // Adding a notification should make one available to fetch.
        \core\notification::success('Notification created');
        $this->assertCount(1, $SESSION->notifications);

        // Re-creating the session will not empty the notification bag.
        \core\session\manager::init_empty_session();
        $this->assertCount(1, $SESSION->notifications);
    }

    /*
     * It should set renderable 'closebutton' property to true when fetching.
     *
     * Totara: Only queued notifications rendered by this class
     * should be dismissable by default ($OUTPUT->notification_<type>
     * methods should not).
     *
     */
    public function test_fetch_sets_closebutton() {

        // Fetched notifications.
        \core\notification::info('Bar');
        $renderables = \core\notification::fetch();
        $renderable = array_pop($renderables);

        $closebuttonreflection = new ReflectionProperty('\core\output\notification', 'closebutton');
        $closebuttonreflection->setAccessible(true);

        $this->assertTrue($closebuttonreflection->getValue($renderable));
    }

    /**
     * It should add an item to the session queue with a 'totara' flag.
     */
    public function test_add_totara_legacy_appends_to_queue() {
        global $SESSION;

        \core\notification::add_totara_legacy('Foo');
        $stored = array_pop($SESSION->notifications);
        $this->assertTrue($stored->totara);
    }

    /**
     * It should support storing options as per totara_set_notification().
     */
    public function test_add_totara_legacy_appends_to_queue_with_options() {
        global $SESSION;

        \core\notification::add_totara_legacy('Foo', null, ['option1' => 7, 'option2' => 'bar']);
        $stored = array_pop($SESSION->notifications);
        $this->assertEquals(7, $stored->option1);
        $this->assertEquals('bar', $stored->option2);
    }

    /**
     * It should not return legacy Totara notifications.
     */
    public function test_totara_fetch_as_array_filters_notifications() {
        global $SESSION, $OUTPUT;

        \core\notification::add('Foo');
        \core\notification::add_totara_legacy('Bar');
        \core\notification::add('Baz');

        // Return non-legacy notifications.
        $this->assertCount(2, \core\notification::totara_fetch_as_array($OUTPUT));

        // The totara notification should remain in the queue.
        $this->assertCount(1, $SESSION->notifications);
        $this->assertEquals('Bar', $SESSION->notifications[0]->message);
    }

    /**
     * It should convert legacy type to one of the class constants.
     *
     * This is something of a functional test to ensure the correct
     * methods of \core\output\notification are being called to resolve
     * legacy type class strings to class NOTIFY constants.
     *
     * This provides similar functionality to \core_renderer\notification
     * but ensures that if the type is passed as a legacy string of classes
     * we are able to resolve it. This is for compatibility with legacy
     * Totara notifications.
     */
    public function test_fetch_resolves_legacy_type() {

        //
        // If it's already a class constant there should be no change.
        //
        \core\notification::add_totara_legacy('Foo', \core\output\notification::NOTIFY_INFO);
        $notifications = \core\notification::fetch();
        $notification = array_pop($notifications);
        $this->assertEquals(\core\output\notification::NOTIFY_INFO, $notification->get_message_type());

        \core\notification::add_totara_legacy('Foo', \core\output\notification::NOTIFY_SUCCESS);
        $notifications = \core\notification::fetch();
        $notification = array_pop($notifications);
        $this->assertEquals(\core\output\notification::NOTIFY_SUCCESS, $notification->get_message_type());

        \core\notification::add_totara_legacy('Foo', \core\output\notification::NOTIFY_WARNING);
        $notifications = \core\notification::fetch();
        $notification = array_pop($notifications);
        $this->assertEquals(\core\output\notification::NOTIFY_WARNING, $notification->get_message_type());

        \core\notification::add_totara_legacy('Foo', \core\output\notification::NOTIFY_ERROR);
        $notifications = \core\notification::fetch();
        $notification = array_pop($notifications);
        $this->assertEquals(\core\output\notification::NOTIFY_ERROR, $notification->get_message_type());

        //
        // It should also recognise a class constant 'hidden' among custom classes.
        //
        \core\notification::add_totara_legacy('Foo', 'foo '. \core\output\notification::NOTIFY_INFO . ' bar baz');
        $notifications = \core\notification::fetch();
        $notification = array_pop($notifications);
        $this->assertEquals(\core\output\notification::NOTIFY_INFO, $notification->get_message_type());

        //
        // Legacy classes.
        //
        \core\notification::add_totara_legacy('Foo', 'notifyproblem');
        $notifications = \core\notification::fetch();
        $notification = array_pop($notifications);
        $this->assertEquals(\core\output\notification::NOTIFY_ERROR, $notification->get_message_type());

        \core\notification::add_totara_legacy('Foo', 'notifytiny');
        $notifications = \core\notification::fetch();
        $notification = array_pop($notifications);
        $this->assertEquals(\core\output\notification::NOTIFY_ERROR, $notification->get_message_type());

        // It should use the first legacy indentifier it finds.
        \core\notification::add_totara_legacy('Foo', '    notifyerror bar notifysuccess baz');
        $notifications = \core\notification::fetch();
        $notification = array_pop($notifications);
        $this->assertEquals(\core\output\notification::NOTIFY_ERROR, $notification->get_message_type());

        \core\notification::add_totara_legacy('Foo', 'foo bar notifysuccess baz');
        $notifications = \core\notification::fetch();
        $notification = array_pop($notifications);
        $this->assertEquals(\core\output\notification::NOTIFY_SUCCESS, $notification->get_message_type());

        \core\notification::add_totara_legacy('Foo', 'foo bar notifymessage baz');
        $notifications = \core\notification::fetch();
        $notification = array_pop($notifications);
        $this->assertEquals(\core\output\notification::NOTIFY_INFO, $notification->get_message_type());

        \core\notification::add_totara_legacy('Foo', 'notifyredirect');
        $notifications = \core\notification::fetch();
        $notification = array_pop($notifications);
        $this->assertEquals(\core\output\notification::NOTIFY_INFO, $notification->get_message_type());

        \core\notification::add_totara_legacy('Foo', 'redirectmessage');
        $notifications = \core\notification::fetch();
        $notification = array_pop($notifications);
        $this->assertEquals(\core\output\notification::NOTIFY_INFO, $notification->get_message_type());
    }

    /**
     * Legacy notifications' addtional classes should be preserved.
     *
     * Another functional test to check that fetch preserves any
     * additional classes passed as part of 'type' by legacy notifications
     * and applies them to the notification templatable.
     */
    public function test_fetch_preserves_classes() {
        global $OUTPUT;

        \core\notification::add_totara_legacy('Foo', 'foo bar redirectmessage baz');
        $notifications = \core\notification::fetch();
        $notification = array_pop($notifications);
        $exported = $notification->export_for_template($OUTPUT);

        $this->assertEquals('foo bar baz', $exported['extraclasses']);

        // It is also possible that type was set to null.
        \core\notification::add_totara_legacy('Foo');
        $notifications = \core\notification::fetch();
        $notification = array_pop($notifications);
        $exported = $notification->export_for_template($OUTPUT);

        $this->assertEquals('', $exported['extraclasses']);
    }

}
