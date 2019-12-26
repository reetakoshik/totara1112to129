<?php
/*
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
 * @author  Valerii Kuznetsov <valerii.kuznetsov@totaralearning.com>
 * @package mod_facetoface
 */

/*
 * Testing of send notification tasks
 */
if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    // It must be included from a Moodle page.
}
global $CFG;
require_once($CFG->dirroot.'/mod/facetoface/lib.php');
require_once($CFG->dirroot.'/mod/facetoface/tests/facetoface_testcase.php');

class mod_facetoface_send_notification_task_testcase extends mod_facetoface_facetoface_testcase {
    /**
     * Test simple run
     */
    public function test_send_notifications_task() {
        $this->resetAfterTest();
        $cron = new \mod_facetoface\task\send_notifications_task();
        $cron->testing = true;
        $cron->execute();
        $this->execute_adhoc_tasks();
    }

    /**
     * Test manual notifications
     */
    public function test_send_manual_notifications() {
        global $DB;
        $this->resetAfterTest();
        $seed = $this->seed_data();
        $seminarevent = new \mod_facetoface\seminar_event($seed['session']->id);

        $sink = $this->redirectEmails();
        $cron = new \mod_facetoface\task\send_notifications_task();
        $cron->testing = true;

        // Signup, and clear automated message (booking confirmation).
        $signup = \mod_facetoface\signup::create($seed['users'][0]->id, $seminarevent);
        \mod_facetoface\signup_helper::signup($signup);
        $cron->execute();
        $this->execute_adhoc_tasks();

        $sink->clear();

        // Make notification manual
        $notificationrec = $DB->get_record('facetoface_notification', ['conditiontype'=> 32]);
        $notificationrec->type = MDL_F2F_NOTIFICATION_MANUAL;
        $notificationrec->issent = 0;
        $notificationrec->status = 1;
        $notificationrec->booked = 1;
        $notificationrec->title = 'TEST';
        $DB->update_record('facetoface_notification', $notificationrec);

        $cron->execute();
        $this->execute_adhoc_tasks();

        $messages = $sink->get_messages();
        $sink->clear();
        $this->assertCount(1, $messages);
        $message = current($messages);
        $this->assertEquals('TEST', $message->subject);
        $this->assertEquals('test@example.com', $message->to);

        // Confirm that messages sent only once
        $cron->execute();
        $this->execute_adhoc_tasks();
        $this->assertEmpty($sink->get_messages());
        $sink->close();
    }

    /**
     * Test scheduled notifications
     */
    public function test_send_scheduled_notifications() {
        global $DB;
        $this->resetAfterTest();
        $seed = $this->seed_data();
        $seminarevent = new \mod_facetoface\seminar_event($seed['session']->id);

        $sink = $this->redirectEmails();
        $cron = new \mod_facetoface\task\send_notifications_task();
        $cron->testing = true;

        // Signup, and clear automated message (booking confirmation).
        $signup = \mod_facetoface\signup::create($seed['users'][0]->id, $seminarevent);
        \mod_facetoface\signup_helper::signup($signup);

        // Move it back in time a bit.
        $DB->execute(
            "UPDATE {facetoface_signups_status} SET timecreated = :timecreated ",
            ['timecreated' => time()-100]
            );
        $cron->execute();
        $this->execute_adhoc_tasks();
        $sink->clear();

        // Make notification manual
        $notificationrec = $DB->get_record('facetoface_notification', ['conditiontype'=> 32]);
        $notificationrec->type = MDL_F2F_NOTIFICATION_SCHEDULED;
        $notificationrec->scheduletime = DAYSECS+2;
        $notificationrec->conditiontype = MDL_F2F_CONDITION_BEFORE_SESSION;
        $notificationrec->issent = 0;
        $notificationrec->status = 1;
        $notificationrec->booked = 1;
        $notificationrec->title = 'TEST';
        $DB->update_record('facetoface_notification', $notificationrec);
        $cron->execute();
        $this->execute_adhoc_tasks();

        $messages = $sink->get_messages();
        $sink->clear();
        $this->assertCount(1, $messages);
        $message = current($messages);
        $this->assertEquals('TEST', $message->subject);
        $this->assertEquals('test@example.com', $message->to);

        // Confirm that messages sent only once
        $cron->execute();
        $this->execute_adhoc_tasks();
        $this->assertEmpty($sink->get_messages());
        $sink->close();
    }

    /**
     * Test registration ended
     */
    public function test_registration_ended() {
        global $CFG, $DB;
        $this->resetAfterTest();

        $seed = $this->seed_data();

        $teacherrole = $DB->get_record('role', array('shortname' => 'editingteacher'));
        $CFG->facetoface_session_rolesnotify = $teacherrole->id;

        // Add user to session with role that will receive expiring notification
        $sessrole = new stdClass();
        $sessrole->roleid = $teacherrole->id;
        $sessrole->sessionid = $seed['session']->id;
        $sessrole->userid = $seed['users'][0]->id;
        $DB->insert_record('facetoface_session_roles', $sessrole);

        $time = time();

        // Set last cron run timestamp before registration expired.
        $conditions = array('component' => 'mod_facetoface', 'classname' => '\mod_facetoface\task\send_notifications_task');
        $DB->set_field('task_scheduled', 'lastruntime', $time-100, $conditions);
        $DB->set_field('facetoface_sessions', 'registrationtimefinish', $time-50, ['id' => $seed['session']->id]);

        $notificationrec = $DB->get_record('facetoface_notification', ['conditiontype'=> MDL_F2F_CONDITION_REGISTRATION_DATE_EXPIRED]);
        $notificationrec->title = 'TEST';
        $DB->update_record('facetoface_notification', $notificationrec);

        $sink = $this->redirectEmails();
        facetoface_notify_registration_ended();
        $this->execute_adhoc_tasks();
        $messages = $sink->get_messages();
        $sink->clear();
        $this->assertCount(1, $messages);
        $message = current($messages);
        $this->assertEquals('TEST', $message->subject);
        $this->assertEquals('test@example.com', $message->to);

        // Confirm that messages not sent again
        facetoface_notify_registration_ended();
        $this->execute_adhoc_tasks();
        $this->assertEmpty($sink->get_messages());
        $sink->close();

    }

    /**
     * Test of cleaning reservations after dead line
     */
    public function test_remove_reservations_after_deadline () {
        global $DB;
        $this->resetAfterTest();

        $seed = $this->seed_data();

        $seminarevent = new \mod_facetoface\seminar_event($seed['session']->id);
        \mod_facetoface\reservations::add($seminarevent, $seed['users'][0]->id, 1, 0);

        $DB->set_field('facetoface', 'reservecanceldays', 2, ['id' => $seed['session']->facetoface]);

        $notificationrec = $DB->get_record('facetoface_notification', ['conditiontype'=> MDL_F2F_CONDITION_RESERVATION_ALL_CANCELLED]);
        $notificationrec->title = 'TEST';
        $DB->update_record('facetoface_notification', $notificationrec);

        $sink = $this->redirectEmails();
        \mod_facetoface\reservations::remove_after_deadline(true);
        $this->execute_adhoc_tasks();
        $messages = $sink->get_messages();
        $sink->clear();
        $this->assertCount(1, $messages);
        $message = current($messages);
        $this->assertEquals('TEST', $message->subject);
        $this->assertEquals('test@example.com', $message->to);

        // Confirm that messages not sent again
        ob_start();
        \mod_facetoface\reservations::remove_after_deadline(true);
        $this->execute_adhoc_tasks();
        ob_get_clean();
        $this->assertEmpty($sink->get_messages());
        $sink->close();
    }

    /**
     * Prepare course, seminar, event, session, three users enrolled on course.
     */
    protected function seed_data() {
        $course1 = $this->getDataGenerator()->create_course();
        $facetofacegenerator = $this->getDataGenerator()->get_plugin_generator('mod_facetoface');
        $facetofacedata = array(
            'name' => 'facetoface1',
            'course' => $course1->id
        );
        $facetoface1 = $facetofacegenerator->create_instance($facetofacedata);

        // Session that starts in 24hrs time.
        // This session should trigger a mincapacity warning now as cutoff is 24:01 hrs before start time.
        $sessiondate = new stdClass();
        $sessiondate->timestart = time() + DAYSECS;
        $sessiondate->timefinish = time() + DAYSECS + 60;
        $sessiondate->sessiontimezone = 'Pacific/Auckland';

        $sessiondata = array(
            'facetoface' => $facetoface1->id,
            'capacity' => 3,
            'allowoverbook' => 1,
            'sessiondates' => array($sessiondate),
            'mincapacity' => '1',
            'cutoff' => DAYSECS - 60
        );
        $sessionid = $facetofacegenerator->add_session($sessiondata);

        $student1 = $this->getDataGenerator()->create_user(['email' => 'test@example.com']);
        $student2 = $this->getDataGenerator()->create_user();
        $student3 = $this->getDataGenerator()->create_user();

        $this->getDataGenerator()->enrol_user($student1->id, $course1->id, 'student');
        $this->getDataGenerator()->enrol_user($student2->id, $course1->id, 'student');
        $this->getDataGenerator()->enrol_user($student3->id, $course1->id, 'student');

        return [
            'course' => $course1,
            'session' => facetoface_get_session($sessionid),
            'users' => [$student1, $student2, $student3]
        ];
    }
}