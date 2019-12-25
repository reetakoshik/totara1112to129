<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2015 77 Gears Ltd.
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
 * @copyright  2015 77 Gears Ltd.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Joby Harding <joby@77gears.com>
 * @package    totara_message
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Test events in Totara message subpackage.
 *
 * To test, run this from the command line from the $CFG->dirroot
 * vendor/bin/phpunit totara_message_events_testcase
 *
 */
class totara_message_events_testcase extends advanced_testcase {

    private $userfrom;
    private $userto;
    private $sink;
    private $messagedata;

    protected function tearDown() {
        $this->userfrom = null;
        $this->userto = null;
        $this->sink = null;
        $this->messagedata = null;
        parent::tearDown();
    }

    /**
     * Implements setUp().
     */
    protected function setUp() {
        global $CFG;

        require_once($CFG->dirroot . '/totara/message/messagelib.php');

        parent::setup();

        $this->resetAfterTest();

        $this->userfrom = get_admin();
        $this->userto   = $this->getDataGenerator()->create_user();

        $this->messagedata = (object)array(
            'userfrom'    => $this->userfrom,
            'userto'      => $this->userto,
            'subject'     => 'Test message subject',
            'fullmessage' => 'Test message fullmessage.',
            'sendemail'   => TOTARA_MSG_EMAIL_NO,
            'msgstatus'   => TOTARA_MSG_STATUS_OK,
            'urgency'     => TOTARA_MSG_URGENCY_NORMAL,
            'msgtype'     => TOTARA_MSG_TYPE_COURSE,
            'contexturl'  => new moodle_url('/index.php'),
        );
    }

    /**
     * Filter given array for alert or task events.
     *
     * @param string $type Either 'alert' or 'task'.
     * @param array $events Array of events.
     * @return array
     */
    protected function filter_events($type, $events) {

        $class = "totara_message\\event\\{$type}_sent";

        return array_filter($events, function($item) use ($class) {
            return get_class($item) === $class;
        });
    }

    /**
     * Ensure triggering alert_sent populates expected data.
     */
    public function test_alert_sent() {
        $this->setAdminUser();

        // This test fails due to a transaction issue, preventing
        // reset by rollback fixes this issue.
        $this->preventResetByRollback();

        $sink = $this->redirectEvents();
        $result = tm_alert_send($this->messagedata);

        // There will also be a core\event\message_sent and
        // potentially other events added in future so don't
        // assume our event will always have the same key
        // in the array returned by sink->get_events().
        $event = $this->filter_events('alert', $sink->get_events());

        $event = array_shift($event);

        $this->message_sent_assertions($result, $event);
    }

    /**
     * Ensure triggering task_sent populates expected data.
     */
    public function test_task_sent() {
        global $DB;

        $this->setAdminUser();

        // This test fails due to a transaction issue, preventing
        // reset by rollback fixes this issue.
        $this->preventResetByRollback();

        $sink = $this->redirectEvents();
        $result = tm_task_send($this->messagedata);

        // There will also be a core\event\message_sent and
        // potentially other events added in future so don't
        // assume our event will always have the same key
        // in the array returned by sink->get_events().
        $event = $this->filter_events('task', $sink->get_events());
        $event = array_shift($event);

        $this->message_sent_assertions($result, $event);
    }

    /**
     * Make assertions.
     *
     * @param int $messageid ID of the sent message.
     * @param totara_message\event\alert_sent|totara_message\event\task_sent $event
     */
    protected function message_sent_assertions($messageid, $event) {
        global $DB;

        $eventdata = $event->get_data();

        $this->assertEventContextNotUsed($event);

        $metadata  = $DB->get_record('message_metadata', array('id' => $eventdata['objectid']));
        $message   = $DB->get_record('message', array('id' => $eventdata['other']['messageid']));

        $this->assertSame($event->get_context(), context_system::instance());

        $this->assertSame($eventdata['crud'], 'c');
        $this->assertSame($eventdata['edulevel'], core\event\base::LEVEL_OTHER);
        $this->assertSame($eventdata['objecttable'], 'message_metadata');
        $this->assertSame($eventdata['objectid'], $metadata->id);
        $this->assertSame($eventdata['userid'], $this->userfrom->id);
        $this->assertSame($eventdata['relateduserid'], $this->userto->id);
        $this->assertSame($eventdata['other']['messageid'], $messageid);

        $this->assertEquals($metadata, $event->get_record_snapshot('message_metadata', $metadata->id));
        $this->assertEquals($message, $event->get_record_snapshot('message', $messageid));
    }
}
