<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2018 Totara Learning Solutions Ltd
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
 * @author Valerii Kuznetsov <valerii.kuznetsov@totaralearning.com>
 * @package mod_facetoface
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/mod/facetoface/lib.php');

class mod_facetoface_notifications_multilang_testcase extends advanced_testcase {

    /**
     * Test that admin request notice works correctly
     */
    public function test_facetoface_send_adminrequest_notice() {
        global $DB;
        $seed = $this->seed_data();
        $sysadminapprover = $this->getDataGenerator()->create_user(['lang' => 'sv']);
        $actadminapprover = $this->getDataGenerator()->create_user(['lang' => 'hu']);

        // Create system admin approver user.
        $systemcontext = context_system::instance();
        $sitemanagerrole = $DB->get_record('role', array('shortname' => 'manager'), '*', MUST_EXIST);
        role_assign($sitemanagerrole->id, $sysadminapprover->id, $systemcontext->id);
        assign_capability('mod/facetoface:approveanyrequest', CAP_ALLOW, $sitemanagerrole->id, $systemcontext->id);
        set_config('facetoface_adminapprovers', $sysadminapprover->id);

        // Add activity approver.
        $seed->facetoface->approvaladmins = $actadminapprover->id;
        $DB->set_field('facetoface', 'approvaladmins', $seed->facetoface->approvaladmins, ['id' => $seed->facetoface->id]);

        $this->prepare_notification($seed->facetoface->id, MDL_F2F_CONDITION_BOOKING_REQUEST_ADMIN);

        \mod_facetoface\notice_sender::request_admin(
            \mod_facetoface\signup::create($seed->student1->id, new mod_facetoface\seminar_event($seed->session->id))
        );
        $messages = $this->fetch_messages();

        $this->assertCount(5, $messages);

        $this->assertEquals('fr title', $messages[$seed->student1->id]->subject);
        $this->assertEquals('fr body', $messages[$seed->student1->id]->fullmessage);
        $this->assertEquals('fr body', $messages[$seed->student1->id]->fullmessagehtml);
        $this->assertEquals('fr body', $messages[$seed->student1->id]->smallmessage);

        $this->assertEquals('he title', $messages[$seed->manager1->id]->subject);
        $this->assertEquals('he prefixhe body', $messages[$seed->manager1->id]->fullmessage);
        $this->assertEquals('he prefixhe body', $messages[$seed->manager1->id]->fullmessagehtml);
        $this->assertEquals('he prefixhe body', $messages[$seed->manager1->id]->smallmessage);

        $this->assertEquals('ja title', $messages[$seed->tempmanager->id]->subject);
        $this->assertEquals('ja prefixja body', $messages[$seed->tempmanager->id]->fullmessage);
        $this->assertEquals('ja prefixja body', $messages[$seed->tempmanager->id]->fullmessagehtml);
        $this->assertEquals('ja prefixja body', $messages[$seed->tempmanager->id]->smallmessage);

        $this->assertEquals('sv title', $messages[$sysadminapprover->id]->subject);
        $this->assertEquals('sv prefixsv body', $messages[$sysadminapprover->id]->fullmessage);
        $this->assertEquals('sv prefixsv body', $messages[$sysadminapprover->id]->fullmessagehtml);
        $this->assertEquals('sv prefixsv body', $messages[$sysadminapprover->id]->smallmessage);

        $this->assertEquals('hu title', $messages[$actadminapprover->id]->subject);
        $this->assertEquals('hu prefixhu body', $messages[$actadminapprover->id]->fullmessage);
        $this->assertEquals('hu prefixhu body', $messages[$actadminapprover->id]->fullmessagehtml);
        $this->assertEquals('hu prefixhu body', $messages[$actadminapprover->id]->smallmessage);
    }

    /**
     * Test that registration closure notice works correctly
     */
    public function test_facetoface_send_registration_closure_notice() {
        $seed = $this->seed_data();
        $this->prepare_notification($seed->facetoface->id, MDL_F2F_CONDITION_BEFORE_REGISTRATION_ENDS);

        facetoface_send_registration_closure_notice($seed->facetoface, $seed->session, $seed->student1->id);
        $messages = $this->fetch_messages();

        $this->assertCount(3, $messages);

        $this->assertEquals('fr title', $messages[$seed->student1->id]->subject);
        $this->assertEquals('fr body', $messages[$seed->student1->id]->fullmessage);
        $this->assertEquals('fr body', $messages[$seed->student1->id]->fullmessagehtml);
        $this->assertEquals('fr body', $messages[$seed->student1->id]->smallmessage);

        $this->assertEquals('he title', $messages[$seed->manager1->id]->subject);
        $this->assertEquals('he prefixhe body', $messages[$seed->manager1->id]->fullmessage);
        $this->assertEquals('he prefixhe body', $messages[$seed->manager1->id]->fullmessagehtml);
        $this->assertEquals('he prefixhe body', $messages[$seed->manager1->id]->smallmessage);

        $this->assertEquals('ja title', $messages[$seed->tempmanager->id]->subject);
        $this->assertEquals('ja prefixja body', $messages[$seed->tempmanager->id]->fullmessage);
        $this->assertEquals('ja prefixja body', $messages[$seed->tempmanager->id]->fullmessagehtml);
        $this->assertEquals('ja prefixja body', $messages[$seed->tempmanager->id]->smallmessage);
    }

    /**
     * Test that waitlisted confirmation notice works correctly
     */
    public function test_facetoface_send_confirmation_notice_waitlisted() {
        $seed = $this->seed_data();
        $this->prepare_notification($seed->facetoface->id, MDL_F2F_CONDITION_WAITLISTED_CONFIRMATION);

        \mod_facetoface\notice_sender::confirm_waitlist(
            \mod_facetoface\signup::create($seed->student1->id, new \mod_facetoface\seminar_event($seed->session->id)),
            MDL_F2F_BOTH
        );
        $messages = $this->fetch_messages();

        $this->assertCount(1, $messages);

        $this->assertEquals('fr title', $messages[$seed->student1->id]->subject);
        $this->assertEquals('fr body', $messages[$seed->student1->id]->fullmessage);
        $this->assertEquals('fr body', $messages[$seed->student1->id]->fullmessagehtml);
        $this->assertEquals('fr body', $messages[$seed->student1->id]->smallmessage);
    }

    /**
     * Test that booking confirmation notice works correctly
     */
    public function test_facetoface_send_confirmation_notice_booked() {
        $seed = $this->seed_data();
        $this->prepare_notification($seed->facetoface->id, MDL_F2F_CONDITION_BOOKING_CONFIRMATION);

        \mod_facetoface\notice_sender::confirm_booking(
            \mod_facetoface\signup::create($seed->student1->id, new \mod_facetoface\seminar_event($seed->session->id)),
            MDL_F2F_TEXT
        );
        $messages = $this->fetch_messages();

        $this->assertCount(3, $messages);

        $this->assertEquals('fr title', $messages[$seed->student1->id]->subject);
        $this->assertEquals('fr body', $messages[$seed->student1->id]->fullmessage);
        $this->assertEquals('fr body', $messages[$seed->student1->id]->fullmessagehtml);
        $this->assertEquals('fr body', $messages[$seed->student1->id]->smallmessage);

        $this->assertEquals('he title', $messages[$seed->manager1->id]->subject);
        $this->assertEquals('he prefixhe body', $messages[$seed->manager1->id]->fullmessage);
        $this->assertEquals('he prefixhe body', $messages[$seed->manager1->id]->fullmessagehtml);
        $this->assertEquals('he prefixhe body', $messages[$seed->manager1->id]->smallmessage);

        $this->assertEquals('ja title', $messages[$seed->tempmanager->id]->subject);
        $this->assertEquals('ja prefixja body', $messages[$seed->tempmanager->id]->fullmessage);
        $this->assertEquals('ja prefixja body', $messages[$seed->tempmanager->id]->fullmessagehtml);
        $this->assertEquals('ja prefixja body', $messages[$seed->tempmanager->id]->smallmessage);
    }

    /**
     * Test that date/time change notice works correctly
     */
    public function test_facetoface_send_datetime_change_notice() {
        $seed = $this->seed_data();
        $this->prepare_notification($seed->facetoface->id, MDL_F2F_CONDITION_SESSION_DATETIME_CHANGE);

        \mod_facetoface\notice_sender::signup_datetime_changed(
            \mod_facetoface\signup::create($seed->student1->id, new \mod_facetoface\seminar_event($seed->session->id)),
            []
        );
        $messages = $this->fetch_messages();

        $this->assertCount(1, $messages);

        $this->assertEquals('fr title', $messages[$seed->student1->id]->subject);
        $this->assertEquals('fr body', $messages[$seed->student1->id]->fullmessage);
        $this->assertEquals('fr body', $messages[$seed->student1->id]->fullmessagehtml);
        $this->assertEquals('fr body', $messages[$seed->student1->id]->smallmessage);
    }

    /**
     * Test that request declined notice works correctly
     */
    public function test_facetoface_send_decline_notice() {
        $seed = $this->seed_data();
        $this->prepare_notification($seed->facetoface->id, MDL_F2F_CONDITION_DECLINE_CONFIRMATION);

        \mod_facetoface\notice_sender::decline(
            \mod_facetoface\signup::create($seed->student1->id, new \mod_facetoface\seminar_event($seed->session->id))
        );
        $messages = $this->fetch_messages();

        $this->assertCount(1, $messages);

        $this->assertEquals('fr title', $messages[$seed->student1->id]->subject);
        $this->assertEquals('fr body', $messages[$seed->student1->id]->fullmessage);
        $this->assertEquals('fr body', $messages[$seed->student1->id]->fullmessagehtml);
        $this->assertEquals('fr body', $messages[$seed->student1->id]->smallmessage);
    }

    /**
     * Test that registration closure notice works correctly
     */
    public function test_facetoface_send_oneperday_notice() {
        $seed = $this->seed_data();
        $this->prepare_notification($seed->facetoface->id, MDL_F2F_CONDITION_BOOKING_CONFIRMATION);

        $params = array(
            'facetofaceid'  => $seed->facetoface->id,
            'type'          => MDL_F2F_NOTIFICATION_AUTO,
            'conditiontype' => MDL_F2F_CONDITION_BOOKING_CONFIRMATION
        );

        // Ensure expected attributes are initialised.
        if (!isset($seed->session->notifyuser)) {
            $seed->session->notifyuser = true;
        }

        if (!isset($seed->session->notifymanager)) {
            $seed->session->notifymanager = true;
        }

        facetoface_send_oneperday_notice($seed->facetoface, $seed->session, $seed->student1->id, $params);
        $messages = $this->fetch_messages(true);

        $this->assertCount(3, $messages);

        for ($i = 0; $i <= 1; $i++) {
            $this->assertEquals('fr title', $messages[$seed->student1->id][$i]->subject);
            $this->assertEquals('fr body', $messages[$seed->student1->id][$i]->fullmessage);
            $this->assertEquals('fr body', $messages[$seed->student1->id][$i]->fullmessagehtml);
            $this->assertEquals('fr body', $messages[$seed->student1->id][$i]->smallmessage);

            $this->assertEquals('he title', $messages[$seed->manager1->id][$i]->subject);
            $this->assertEquals('he prefixhe body', $messages[$seed->manager1->id][$i]->fullmessage);
            $this->assertEquals('he prefixhe body', $messages[$seed->manager1->id][$i]->fullmessagehtml);
            $this->assertEquals('he prefixhe body', $messages[$seed->manager1->id][$i]->smallmessage);

            $this->assertEquals('ja title', $messages[$seed->tempmanager->id][$i]->subject);
            $this->assertEquals('ja prefixja body', $messages[$seed->tempmanager->id][$i]->fullmessage);
            $this->assertEquals('ja prefixja body', $messages[$seed->tempmanager->id][$i]->fullmessagehtml);
            $this->assertEquals('ja prefixja body', $messages[$seed->tempmanager->id][$i]->smallmessage);
        }
    }

    /**
     * Test that booking request notice works correctly
     */
    public function test_facetoface_send_request_notice() {
        $seed = $this->seed_data();
        $this->prepare_notification($seed->facetoface->id, MDL_F2F_CONDITION_BOOKING_REQUEST_MANAGER);

        \mod_facetoface\notice_sender::request_manager(
            \mod_facetoface\signup::create($seed->student1->id, new mod_facetoface\seminar_event($seed->session->id))
        );
        $messages = $this->fetch_messages();

        $this->assertCount(3, $messages);

        $this->assertEquals('fr title', $messages[$seed->student1->id]->subject);
        $this->assertEquals('fr body', $messages[$seed->student1->id]->fullmessage);
        $this->assertEquals('fr body', $messages[$seed->student1->id]->fullmessagehtml);
        $this->assertEquals('fr body', $messages[$seed->student1->id]->smallmessage);

        $this->assertEquals('he title', $messages[$seed->manager1->id]->subject);
        $this->assertEquals('he prefixhe body', $messages[$seed->manager1->id]->fullmessage);
        $this->assertEquals('he prefixhe body', $messages[$seed->manager1->id]->fullmessagehtml);
        $this->assertEquals('he prefixhe body', $messages[$seed->manager1->id]->smallmessage);

        $this->assertEquals('ja title', $messages[$seed->tempmanager->id]->subject);
        $this->assertEquals('ja prefixja body', $messages[$seed->tempmanager->id]->fullmessage);
        $this->assertEquals('ja prefixja body', $messages[$seed->tempmanager->id]->fullmessagehtml);
        $this->assertEquals('ja prefixja body', $messages[$seed->tempmanager->id]->smallmessage);
    }

    /**
     * Test that role request notice works correctly
     */
    public function test_facetoface_send_rolerequest_notice() {
        global $DB;
        $seed = $this->seed_data();

        // Add role approvers.
        $roleapprover1 = $this->getDataGenerator()->create_user(['lang' => 'sv']);
        $roleapprover2 = $this->getDataGenerator()->create_user(['lang' => 'hu']);

        $trainerrole = $DB->get_record('role', array('shortname' => 'teacher'));
        $seed->facetoface->approvalrole = $trainerrole->id;
        $DB->set_field('facetoface', 'approvalrole', $seed->facetoface->approvalrole, ['id' => $seed->facetoface->id]);
        $DB->insert_record('facetoface_session_roles', (object)['sessionid'=> $seed->session->id, 'roleid' => $trainerrole->id, 'userid' => $roleapprover1->id]);
        $DB->insert_record('facetoface_session_roles', (object)['sessionid'=> $seed->session->id, 'roleid' => $trainerrole->id, 'userid' => $roleapprover2->id]);

        $this->prepare_notification($seed->facetoface->id, MDL_F2F_CONDITION_BOOKING_REQUEST_ROLE);

        \mod_facetoface\notice_sender::request_role(
            \mod_facetoface\signup::create($seed->student1->id, new mod_facetoface\seminar_event($seed->session->id))
        );
        $messages = $this->fetch_messages();

        $this->assertCount(3, $messages);

        $this->assertEquals('fr title', $messages[$seed->student1->id]->subject);
        $this->assertEquals('fr body', $messages[$seed->student1->id]->fullmessage);
        $this->assertEquals('fr body', $messages[$seed->student1->id]->fullmessagehtml);
        $this->assertEquals('fr body', $messages[$seed->student1->id]->smallmessage);

        $this->assertEquals('sv title', $messages[$roleapprover1->id]->subject);
        $this->assertEquals('sv prefixsv body', $messages[$roleapprover1->id]->fullmessage);
        $this->assertEquals('sv prefixsv body', $messages[$roleapprover1->id]->fullmessagehtml);
        $this->assertEquals('sv prefixsv body', $messages[$roleapprover1->id]->smallmessage);

        $this->assertEquals('hu title', $messages[$roleapprover2->id]->subject);
        $this->assertEquals('hu prefixhu body', $messages[$roleapprover2->id]->fullmessage);
        $this->assertEquals('hu prefixhu body', $messages[$roleapprover2->id]->fullmessagehtml);
        $this->assertEquals('hu prefixhu body', $messages[$roleapprover2->id]->smallmessage);
    }

    /**
     * Test that traniner confirmation notice works correctly
     */
    public function test_facetoface_send_trainer_confirmation_notice() {
        $seed = $this->seed_data();

        $this->prepare_notification($seed->facetoface->id, MDL_F2F_CONDITION_TRAINER_CONFIRMATION);

        \mod_facetoface\notice_sender::trainer_confirmation($seed->student1->id, new \mod_facetoface\seminar_event($seed->session->id));
        $messages = $this->fetch_messages();

        $this->assertCount(1, $messages);

        $this->assertEquals('fr title', $messages[$seed->student1->id]->subject);
        $this->assertEquals('fr body', $messages[$seed->student1->id]->fullmessage);
        $this->assertEquals('fr body', $messages[$seed->student1->id]->fullmessagehtml);
        $this->assertEquals('fr body', $messages[$seed->student1->id]->smallmessage);
    }

    /**
     * Test that trainer session cancellation notice works correctly
     */
    public function test_facetoface_send_trainer_session_cancellation_notice() {
        $seed = $this->seed_data();

        $this->prepare_notification($seed->facetoface->id, MDL_F2F_CONDITION_TRAINER_SESSION_CANCELLATION);

        \mod_facetoface\notice_sender::event_trainer_cancellation($seed->student1->id, new \mod_facetoface\seminar_event($seed->session->id));
        $messages = $this->fetch_messages();

        $this->assertCount(1, $messages);

        $this->assertEquals('fr title', $messages[$seed->student1->id]->subject);
        $this->assertEquals('fr body', $messages[$seed->student1->id]->fullmessage);
        $this->assertEquals('fr body', $messages[$seed->student1->id]->fullmessagehtml);
        $this->assertEquals('fr body', $messages[$seed->student1->id]->smallmessage);
    }

    /**
     * Test that trainer unassignment notice works correctly
     */
    public function test_facetoface_send_trainer_session_unassignment_notice() {
        global $DB;
        $seed = $this->seed_data();

        $this->prepare_notification($seed->facetoface->id, MDL_F2F_CONDITION_TRAINER_SESSION_UNASSIGNMENT);

        \mod_facetoface\notice_sender::event_trainer_unassigned($seed->student1->id, new \mod_facetoface\seminar_event($seed->session->id));
        $messages = $this->fetch_messages();

        $this->assertCount(1, $messages);

        $this->assertEquals('fr title', $messages[$seed->student1->id]->subject);
        $this->assertEquals('fr body', $messages[$seed->student1->id]->fullmessage);
        $this->assertEquals('fr body', $messages[$seed->student1->id]->fullmessagehtml);
        $this->assertEquals('fr body', $messages[$seed->student1->id]->smallmessage);
    }

    /**
     * Create course, users, seminar, event.
     *
     * @return \stdClass $session
     */
    private function seed_data() {
        global $DB;

        $this->resetAfterTest();

        $this->mock_languages();

        // Enable multilang filter.
        filter_set_global_state('multilang', TEXTFILTER_ON);
        filter_set_applies_to_strings('multilang', 1);
        $filtermanager = filter_manager::instance();
        $filtermanager->reset_caches();

        $this->setAdminUser();

        $teacher1 = $this->getDataGenerator()->create_user(['lang' => 'de']);
        $student1 = $this->getDataGenerator()->create_user(['lang' => 'fr']);
        $student2 = $this->getDataGenerator()->create_user(['lang' => 'en']);
        $manager1 = $this->getDataGenerator()->create_user(['lang' => 'he']);
        $manager2 = $this->getDataGenerator()->create_user(['lang' => 'tr']);
        $tempmanager = $this->getDataGenerator()->create_user(['lang' => 'ja']);

        $manager1ja = \totara_job\job_assignment::create_default($manager1->id);
        $tempmanagerja = \totara_job\job_assignment::create_default($tempmanager->id);
        \totara_job\job_assignment::create_default($student1->id, [
            'managerjaid' => $manager1ja->id,
            'tempmanagerjaid' => $tempmanagerja->id,
            'tempmanagerexpirydate' => time() + DAYSECS
        ]);

        $manager2ja = \totara_job\job_assignment::create_default($manager2->id);
        \totara_job\job_assignment::create_default($student2->id, array('managerjaid' => $manager2ja->id));

        $course = $this->getDataGenerator()->create_course();

        $teacherrole = $DB->get_record('role', array('shortname' => 'editingteacher'));
        $studentrole = $DB->get_record('role', array('shortname' => 'student'));

        $this->getDataGenerator()->enrol_user($teacher1->id, $course->id, $teacherrole->id);
        $this->getDataGenerator()->enrol_user($student1->id, $course->id, $studentrole->id);
        $this->getDataGenerator()->enrol_user($student2->id, $course->id, $studentrole->id);

        /** @var mod_facetoface_generator $facetofacegenerator */
        $facetofacegenerator = $this->getDataGenerator()->get_plugin_generator('mod_facetoface');

        $facetofacedata = array(
            'name' => 'facetoface',
            'course' => $course->id
        );
        $facetoface = $facetofacegenerator->create_instance($facetofacedata);

        // Add third party.


        $sessiondate1 = new stdClass();
        $sessiondate1->sessiontimezone = 'Pacific/Auckland';
        $sessiondate1->timestart = time() + WEEKSECS;
        $sessiondate1->timefinish = time() + WEEKSECS + 60;
        $sessiondate1->assetids = array();

        $sessiondate2 = new stdClass();
        $sessiondate2->sessiontimezone = 'Pacific/Auckland';
        $sessiondate2->timestart = time() + WEEKSECS + 86400;
        $sessiondate2->timefinish = time() + WEEKSECS + 86460;
        $sessiondate2->assetids = array();

        $sessiondata = array(
            'facetoface' => $facetoface->id,
            'capacity' => 3,
            'allowoverbook' => 1,
            'sessiondates' => array($sessiondate1, $sessiondate2),
            'mincapacity' => '1',
            'cutoff' => DAYSECS - 60
        );

        $sessionid = $facetofacegenerator->add_session($sessiondata);
        $session = facetoface_get_session($sessionid);
        $session->sessiondates = facetoface_get_session_dates($session->id);

        $seed = new stdClass();
        $seed->facetoface = $facetoface;
        $seed->session = $session;
        $seed->teacher1 = $teacher1;
        $seed->student1 = $student1;
        $seed->student2 = $student2;
        $seed->manager1 = $manager1;
        $seed->manager2 = $manager2;
        $seed->tempmanager = $tempmanager;

        return $seed;
    }

    /**
     * List of languages
     * @return array
     */
    private function languages() {
        // Don't forget to add fixture if adding language.
        return ['de', 'fr', 'en', 'he', 'tr', 'sv', 'hu', 'ja'];
    }

    /**
     * Hack to make system believe that it has some extra languages.
     */
    private function mock_languages() {
        global $CFG;
        foreach($this->languages() as $lang) {
            if ($lang == 'en') {
                continue;
            }
            make_writable_directory($CFG->dataroot . '/lang/' . $lang);
            copy(
                $CFG->dirroot . "/install/lang/{$lang}/langconfig.php",
                $CFG->dataroot . "/lang/{$lang}/langconfig.php"
            );

        }
        get_string_manager()->reset_caches();
    }

    /**
     * Make notification ready for multi-lang filter
     * @param int $seminarid
     * @param int $conditiontype
     * @return mixed stdClass or false
     */
    private function prepare_notification(int $seminarid, int $conditiontype) {
        global $DB;
        $notification = $DB->get_record('facetoface_notification', ['facetofaceid' => $seminarid, 'conditiontype' => $conditiontype]);
        $notification->title = '';
        $notification->body = '';
        $notification->managerprefix = '';
        foreach ($this->languages() as $lang) {
            $notification->title .= '<span lang="' . $lang . '" class="multilang">' . $lang . ' title</span>';
            $notification->body .= '<span lang="' . $lang . '" class="multilang">' . $lang . ' body</span>';
            $notification->managerprefix .= '<span lang="' . $lang . '" class="multilang">' . $lang . ' prefix</span>';
        }
        $DB->update_record('facetoface_notification', $notification);
        return $notification;
    }

    /**
     * Return array of messages where key is user id and value is message or array of messages
     * @param bool $multiple Return only last message when false, or return arra of all message for user when true
     * @return array
     * @throws moodle_exception
     */
    private function fetch_messages($multiple = false) {
        $emailsink = $this->redirectMessages();

        while ($task = \core\task\manager::get_next_adhoc_task(time())) {
            $task->execute();
            \core\task\manager::adhoc_task_complete($task);
        }
        $emailsink->close();
        $emails = $emailsink->get_messages();
        $result = [];
        foreach($emails as $email) {
            if ($multiple) {
                if (empty($result[$email->useridto])) {
                    $result[$email->useridto] = [];
                }
                $result[$email->useridto][] = $email;
            } else {
                $result[$email->useridto] = $email;
            }
        }
        return $result;
    }
}
