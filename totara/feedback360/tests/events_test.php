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
 * @author David Curry <david.curry@totaralms.com>
 * @package totara
 * @subpackage feedback360
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

global $CFG;
require_once($CFG->dirroot.'/totara/feedback360/tests/feedback360_testcase.php');

class feedback360_event_test extends feedback360_testcase {

    /**
     * Test the events thrown when a feedback360 is created, updated, and deleted.
     */
    public function test_feedback360_events() {
        $this->resetAfterTest();
        $sink = $this->redirectEvents();

        // Create a feedback to throw a creation event.
        $feedback360 = new feedback360(0);
        $feedback360->name = 'Test Feedback360';
        $feedback360->save();

        $events = $sink->get_events();
        $sink->clear();

        // There should be one event.
        $this->assertEquals(count($events), 1);

        // Check the event data meets feedback360_created expectations.
        $eventdata = $events[0]->get_data();
        $this->assertEquals('totara_feedback360', $eventdata['component']);
        $this->assertEquals('\totara_feedback360\event\feedback360_created', $eventdata['eventname']);
        $this->assertEquals('created', $eventdata['action']);
        $this->assertEquals('feedback360', $eventdata['objecttable']);
        $this->assertEquals($feedback360->id, $eventdata['objectid']);

        // Set the description to throw an update event.
        $feedback360->description = 'Test Description';
        $feedback360->save();

        $events = $sink->get_events();
        $sink->clear();

        // There should be one event.
        $this->assertEquals(count($events), 1);

        // Check the event data meets feedback360_updated expectations.
        $eventdata = $events[0]->get_data();
        $this->assertEquals('totara_feedback360', $eventdata['component']);
        $this->assertEquals('\totara_feedback360\event\feedback360_updated', $eventdata['eventname']);
        $this->assertEquals('updated', $eventdata['action']);
        $this->assertEquals('feedback360', $eventdata['objecttable']);
        $this->assertEquals($feedback360->id, $eventdata['objectid']);

        // Delete the feedback to throw a deletion event.
        $feedback360->delete();

        $events = $sink->get_events();
        $sink->clear();

        // There should be one event.
        $this->assertEquals(count($events), 1);

        // Check the event data meets feedback360_deleted expectations.
        $eventdata = $events[0]->get_data();
        $this->assertEquals('totara_feedback360', $eventdata['component']);
        $this->assertEquals('\totara_feedback360\event\feedback360_deleted', $eventdata['eventname']);
        $this->assertEquals('deleted', $eventdata['action']);
        $this->assertEquals('feedback360', $eventdata['objecttable']);
        $this->assertEquals($feedback360->id, $eventdata['objectid']);

        $sink->close();
    }

    /**
     * Test the events thrown when feedback is requested and cancelled.
     */
    public function test_request_events() {
        global $DB;

        $this->preventResetByRollback();
        $this->resetAfterTest();
        $sink = $this->redirectEvents();

        $user_assign = $this->getDataGenerator()->create_user();
        $resp_assign = $this->getDataGenerator()->create_user();

        list($feedback360, $users, $quests) = $this->prepare_feedback_with_users(array($user_assign));
        $feedback360->activate();

        // Clear any creation events, they're tested above.
        $sink->clear();

        $this->assign_resp($feedback360, $user_assign->id, $resp_assign->id);
        $user_assignment = $DB->get_record('feedback360_user_assignment', array('feedback360id' => $feedback360->id, 'userid' => $user_assign->id));

        $events = $sink->get_events();
        $sink->clear();

        // There should be three events, one is a message sent event
        // and one is a task sent event.
        $this->assertEquals(count($events), 3);

        // Check the event data meets request_created expectations.
        $eventdata = $events[2]->get_data();
        $onlyrespassignment = $DB->get_record('feedback360_resp_assignment', array(), '*', MUST_EXIST);
        $this->assertEquals('totara_feedback360', $eventdata['component']);
        $this->assertEquals('\totara_feedback360\event\request_created', $eventdata['eventname']);
        $this->assertEquals('created', $eventdata['action']);
        $this->assertEquals('feedback360_resp_assignment', $eventdata['objecttable']);
        $this->assertEquals($onlyrespassignment->id, $eventdata['objectid']);
        $this->assertEquals($user_assign->id, $eventdata['relateduserid']);
        $this->assertEquals($user_assignment->id, $eventdata['other']['assignmentid']);
        $this->assertEquals($resp_assign->id, $eventdata['other']['userid']);
        $this->assertEquals('', $eventdata['other']['email']);

        $this->unassign_resp($feedback360, $user_assign->id, $resp_assign->id);

        $events = $sink->get_events();
        $sink->clear();

        // There should be three events, one is a message sent event
        // and one is an alert sent event.
        $this->assertEquals(count($events), 3);

        // Check the event data meets request_deleted expectations.
        $eventdata = $events[2]->get_data();
        $this->assertEquals('totara_feedback360', $eventdata['component']);
        $this->assertEquals('\totara_feedback360\event\request_deleted', $eventdata['eventname']);
        $this->assertEquals('deleted', $eventdata['action']);
        $this->assertEquals('feedback360_resp_assignment', $eventdata['objecttable']);
        $this->assertEquals($onlyrespassignment->id, $eventdata['objectid']); // This is no longer in the db, but was in the event.
        $this->assertEquals($user_assign->id, $eventdata['relateduserid']);
        $this->assertEquals($user_assignment->id, $eventdata['other']['assignmentid']);
        $this->assertEquals($resp_assign->id, $eventdata['other']['userid']);
        $this->assertEquals('', $eventdata['other']['email']);

        $sink->close();
    }

    /**
     * Test the legacy data for old add_to_log calls, events to test:
     *      - feedback_request_deleted
     *      - feedback_updated
     */
    public function test_legacy_events() {
        global $DB;

        $this->preventResetByRollback();
        $this->resetAfterTest();
        $sink = $this->redirectEvents();

        // Set up some users.
        $user_assign = $this->getDataGenerator()->create_user();
        $resp_assign = $this->getDataGenerator()->create_user();

        // Set up the feedback360 and user_assignment.
        list($feedback360, $users, $quests) = $this->prepare_feedback_with_users(array($user_assign));

        $sink->clear();

        // Test the legacy data for the feedback_updated event.
        $feedback360->activate();

        $events = $sink->get_events();
        $sink->clear();

        $oldurl = new moodle_url('/totara/feedback360/general.php', array('id' => $feedback360->id));
        $olddata = array(SITEID, 'feedback360', 'update feedback360', $oldurl, 'General Settings: feedback360 ID=' . $feedback360->id);
        $legacydata = $events[0]->get_legacy_logdata();

        $this->assertEquals($legacydata[0], $olddata[0]);
        $this->assertEquals($legacydata[1], $olddata[1]);
        $this->assertEquals($legacydata[2], $olddata[2]);
        $this->assertEquals($legacydata[3]->out(), $olddata[3]->out());
        $this->assertEquals($legacydata[4], $olddata[4]);

        // Set up the resp_assignment.
        $this->assign_resp($feedback360, $user_assign->id, $resp_assign->id);
        $user_assignment = $DB->get_record('feedback360_user_assignment', array('feedback360id' => $feedback360->id, 'userid' => $user_assign->id));

        $sink->clear();

        // Test the legacy data for the feedback_request_deleted event.
        $this->unassign_resp($feedback360, $user_assign->id, $resp_assign->id);

        $events = $sink->get_events();
        $sink->clear();

        $params = array('action' => 'users', 'userid' => $user_assign->id, 'formid' => $user_assignment->id);
        $oldurl = new moodle_url("/totara/feedback360/request.php", $params);
        $olddata = array(SITEID, 'feedback360', 'delete feedback request', $oldurl);

        // There should be three events, one is a message sent event
        // and one is an alert sent event.
        $legacydata = $events[2]->get_legacy_logdata();

        $this->assertEquals($legacydata[0], $olddata[0]);
        $this->assertEquals($legacydata[1], $olddata[1]);
        $this->assertEquals($legacydata[2], $olddata[2]);
        $this->assertEquals($legacydata[3]->out(), $olddata[3]->out());
    }

}
