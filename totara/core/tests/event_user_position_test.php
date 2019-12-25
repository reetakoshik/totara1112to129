<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2014 onwards Totara Learning Solutions LTD
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
 * @author Valerii Kuznetsov <valerii.kuznetsov@totaralms.com>
 * @package totara_core
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/totara/hierarchy/prefix/position/lib.php');

class event_user_position_test extends advanced_testcase {

    public function test_job_assignment_updated_event() {
        global $DB;

        $this->resetAfterTest();
        $sink = $this->redirectEvents();

        $user = $this->getDataGenerator()->create_user();
        $manager = $this->getDataGenerator()->create_user();
        $managerja = \totara_job\job_assignment::create_default($manager->id);

        $sink->clear();

        $data = array(
            'userid' => $user->id,
            'fullname' => 'ja1',
            'shortname' => 'ja1',
            'idnumber' => 'ja1',
            'managerjaid' => $managerja->id,
        );
        $jobassignment = \totara_job\job_assignment::create($data);

        $events = $sink->get_events();
        $sink->clear();

        $this->assertEquals(count($events), 2);
        $eventdata1 = $events[0]->get_data();
        if ($eventdata1['eventname'] == '\totara_job\event\job_assignment_updated') {
            $eventdata2 = $events[1]->get_data();
        } else {
            $eventdata2 = $eventdata1;
            $eventdata1 = $events[1]->get_data();
        }

        $this->assertEquals('totara_job', $eventdata1['component']);
        $this->assertEquals('\totara_job\event\job_assignment_updated', $eventdata1['eventname']);
        $this->assertEquals('updated', $eventdata1['action']);
        $this->assertEquals($jobassignment->id, $eventdata1['objectid']);

        $this->assertEquals('core', $eventdata2['component']);
        $this->assertEquals('\core\event\role_assigned', $eventdata2['eventname']);
        $this->assertEquals('assigned', $eventdata2['action']);
        $managerroleid = $DB->get_field('role', 'id', array('shortname' => 'staffmanager'));
        $this->assertEquals($managerroleid, $eventdata2['objectid']);
        $this->assertEquals($manager->id, $eventdata2['relateduserid']);
    }

    public function test_job_assignment_viewed_event() {
        $this->resetAfterTest();
        // Create user and course.
        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();

        $data = array(
            'userid' => $user->id,
            'fullname' => 'ja1',
            'shortname' => 'ja1',
            'idnumber' => 'ja1',
        );
        $jobassignment = \totara_job\job_assignment::create($data);

        // Trigger event of viewing his position.
        $coursecontext = context_course::instance($course->id);

        $event = \totara_job\event\job_assignment_viewed::create_from_instance($jobassignment, $coursecontext);
        $event->trigger();
        $data = $event->get_data();

        $this->assertEquals($coursecontext, $event->get_context());
        $this->assertSame('r', $data['crud']);
        $this->assertSame(\core\event\base::LEVEL_OTHER, $data['edulevel']);
        $this->assertSame($user->id, $data['relateduserid']);
        $this->assertSame($jobassignment->id, $data['objectid']);
        $this->assertEventContextNotUsed($event);
    }
}