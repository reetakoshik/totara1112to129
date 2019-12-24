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
 * @author  Petr Skoda <petr.skoda@totaralearning.com>
 * @package mod_facetoface
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Tests allocation related functions.
 */
class mod_facetoface_allocation_testcase extends advanced_testcase {
    public function test_notification_ical_attachement() {
        global $DB, $CFG;
        require_once("$CFG->dirroot/mod/facetoface/lib.php");
        $this->resetAfterTest();
        $this->preventResetByRollback(); // Transactions prevent email redirection!

        /** @var mod_facetoface_generator $seminargenerator */
        $seminargenerator = $this->getDataGenerator()->get_plugin_generator('mod_facetoface');

        $studentrole = $DB->get_record('role', array('shortname' => 'student'));
        $managerrole = $DB->get_record('role', array('shortname' => 'manager'));

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $this->getDataGenerator()->enrol_user($user1->id, $course->id, $managerrole->id);
        $this->getDataGenerator()->enrol_user($user2->id, $course->id, $studentrole->id);
        $user1ja = \totara_job\job_assignment::create_default($user1->id); // Manager.
        \totara_job\job_assignment::create_default($user2->id, array('managerjaid' => $user1ja->id));

        $facetoface = $this->getDataGenerator()->create_module('facetoface', array('course' => $course->id, 'name' => 'Test seminar'));
        $facetoface = $DB->get_record('facetoface', array('id' => $facetoface->id), '*', MUST_EXIST);

        // Use future session date to get consistent notifications.
        $sessiondate = new stdClass();
        $sessiondate->timestart = time() + (DAYSECS * 1);;
        $sessiondate->timefinish = $sessiondate->timestart + (DAYSECS * 1);
        $sessiondate->sessiontimezone = 'Pacific/Auckland';
        $sessionid = $seminargenerator->add_session(array('facetoface' => $facetoface->id, 'sessiondates' => array($sessiondate)));
        $session = facetoface_get_session($sessionid);

        $this->setUser($user1);

        $sink = $this->redirectEmails();
        facetoface_allocate_spaces($session, $facetoface, $course, $user1->id, array($user2->id), 1);
        $messages = $sink->get_messages();
        $sink->close();
        $this->assertCount(2, $messages);

        // The learner.
        $message = $messages[0];
        $this->assertSame($user2->email, $message->to);
        $this->assertStringStartsWith('Seminar booking confirmation: Test seminar', $message->subject);
        $this->assertContains('BEGIN:VCALENDAR', $message->body);

        // The manager.
        $message = $messages[1];
        $this->assertSame($user1->email, $message->to);
        $this->assertStringStartsWith('Seminar booking confirmation: Test seminar', $message->subject);
        $this->assertNotContains('BEGIN:VCALENDAR', $message->body);
    }
}
