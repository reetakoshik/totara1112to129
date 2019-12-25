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
 * @author Nathan Lewis <nathan.lewis@totaralms.com>
 * @package totara_certification
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/totara/certification/db/upgradelib.php');
require_once($CFG->dirroot . '/totara/reportbuilder/tests/reportcache_advanced_testcase.php');

/**
 * Certification module PHPUnit archive test class.
 *
 * To test, run this from the command line from the $CFG->dirroot.
 * vendor/bin/phpunit --verbose totara_certification_upgradelib_testcase totara/certification/tests/upgradelib_test.php
 */
class totara_certification_upgradelib_testcase extends reportcache_advanced_testcase {

    /**
     * Tests totara_certification_upgrade_non_zero_prog_completions. This test is pretty much overkill. We really only
     * need to prove that the correct records are deleted and others are not, but this test also shows that the
     * correct records will be recreated when cron next runs.
     */
    public function test_totara_certification_upgrade_non_zero_prog_completions() {
        global $DB;

        $this->resetAfterTest(true);

        $now = time();

        $user1 = $this->getDataGenerator()->create_user(); // Main test user. Also has program with target problem.
        $user2 = $this->getDataGenerator()->create_user(); // Control, same cert, certified already.
        $user3 = $this->getDataGenerator()->create_user(); // Control, same cert, has valid non-zero prog_completion.
        $user4 = $this->getDataGenerator()->create_user(); // Second test user, same cert, should be certified.
        $user5 = $this->getDataGenerator()->create_user(); // Third test user, different cert.
        $user6 = $this->getDataGenerator()->create_user(); // Fourth test user, missing non-zero prog_completion.

        $prog1 = $this->getDataGenerator()->create_program();

        $cert1 = $this->getDataGenerator()->create_certification();
        $cert2 = $this->getDataGenerator()->create_certification();

        // Set default settings for courses.
        set_config('enablecompletion', '1');
        $coursedefaults = array(
            'enablecompletion' => COMPLETION_ENABLED,
            'completionstartonenrol' => 1,
            'completionprogressonview' => 1);
        $course1 = $this->getDataGenerator()->create_course($coursedefaults);
        $course2 = $this->getDataGenerator()->create_course($coursedefaults);
        $course3 = $this->getDataGenerator()->create_course($coursedefaults);
        $course4 = $this->getDataGenerator()->create_course($coursedefaults);

        $this->getDataGenerator()->add_courseset_program($prog1->id, array($course1->id), CERTIFPATH_STD);
        $this->getDataGenerator()->add_courseset_program($cert1->id, array($course2->id), CERTIFPATH_CERT);
        $this->getDataGenerator()->add_courseset_program($cert1->id, array($course2->id), CERTIFPATH_RECERT);
        $this->getDataGenerator()->add_courseset_program($cert2->id, array($course3->id), CERTIFPATH_CERT);
        $this->getDataGenerator()->add_courseset_program($cert2->id, array($course4->id), CERTIFPATH_RECERT);

        $this->getDataGenerator()->assign_to_program($prog1->id, ASSIGNTYPE_INDIVIDUAL, $user1->id);
        $this->getDataGenerator()->assign_to_program($cert1->id, ASSIGNTYPE_INDIVIDUAL, $user1->id);
        $this->getDataGenerator()->assign_to_program($cert1->id, ASSIGNTYPE_INDIVIDUAL, $user2->id);
        $this->getDataGenerator()->assign_to_program($cert1->id, ASSIGNTYPE_INDIVIDUAL, $user3->id);
        $this->getDataGenerator()->assign_to_program($cert1->id, ASSIGNTYPE_INDIVIDUAL, $user4->id);
        $this->getDataGenerator()->assign_to_program($cert2->id, ASSIGNTYPE_INDIVIDUAL, $user5->id);
        $this->getDataGenerator()->assign_to_program($cert1->id, ASSIGNTYPE_INDIVIDUAL, $user6->id);

        // Check that everything is in the correct state to start with.
        $this->assertEquals(14, $DB->count_records('prog_completion')); // Each user assignment also has a non-zero prog_completion.
        $this->assertEquals(7, $DB->count_records('prog_completion', array('coursesetid' => 0, 'status' => STATUS_PROGRAM_INCOMPLETE)));
        $this->assertEquals(4, $DB->count_records('prog_completion', array('userid' => $user1->id)));

        $where = "coursesetid <> 0 AND status = " . STATUS_COURSESET_INCOMPLETE;
        $this->assertEquals(7, $DB->count_records_select('prog_completion', $where));
        $this->assertEquals(0, $DB->count_records_select('prog_completion', $where . " AND timestarted = 7"));
        $this->assertEquals(0, $DB->count_records_select('prog_completion', $where . " AND timecreated = 0"));
        $this->assertEquals(0, $DB->count_records_select('prog_completion', $where . " AND timedue = 0"));
        $this->assertEquals(7, $DB->count_records_select('prog_completion', $where . " AND timecompleted = 0"));

        // Put data in a state that should either be fixed or left alone.

        // User 2 is certified. The non-zero record should be deleted but shouldn't be recreated.
        list($certcompletion, $progcompletion) = certif_load_completion($cert1->id, $user2->id);
        $certcompletion->status = CERTIFSTATUS_COMPLETED;
        $certcompletion->renewalstatus = CERTIFRENEWALSTATUS_NOTDUE;
        $certcompletion->certifpath = CERTIFPATH_RECERT;
        $certcompletion->timecompleted = $now - DAYSECS * 10;
        $certcompletion->timewindowopens = $now + DAYSECS * 10;
        $certcompletion->timeexpires = $now + DAYSECS * 20;
        $certcompletion->baselinetimeexpires = $certcompletion->timeexpires;
        $progcompletion->status = STATUS_PROGRAM_COMPLETE;
        $progcompletion->timecompleted = $now - DAYSECS * 10;
        $progcompletion->timedue = $now + DAYSECS * 20;
        $this->assertTrue(certif_write_completion($certcompletion, $progcompletion));

        // User 3's non-zero record is already correct and shouldn't be touched.
        $where = "userid = :userid AND coursesetid <> 0";
        $user3prenonzerocompletion = $DB->get_record_select('prog_completion', $where, array('userid' => $user3->id));
        $user3prenonzerocompletion->timestarted = 123;
        $user3prenonzerocompletion->timedue = 234;
        $user3prenonzerocompletion->timecompleted = 345;
        $DB->update_record('prog_completion', $user3prenonzerocompletion);

        // User 4 has already completed the course requirements for certification, but hasn't been certified.
        $this->getDataGenerator()->enrol_user($user4->id, $course2->id);
        $completion = new completion_completion(array('userid' => $user4->id, 'course' => $course2->id));
        $completion->mark_enrolled();
        $coursecompletion = $DB->get_record('course_completions', array('course' => $course2->id, 'userid' => $user4->id));
        $coursecompletion->timestarted = $now;
        $coursecompletion->timecompleted = $now;
        $coursecompletion->reaggregate = 0;
        $coursecompletion->status = COMPLETION_STATUS_COMPLETE;
        $DB->update_record('course_completions', $coursecompletion);
        cache::make('core', 'coursecompletion')->purge();

        // User 6 is already missing their non-zero record.
        $sql = "DELETE FROM {prog_completion}
                 WHERE userid = :userid
                   AND coursesetid <> 0";
        $DB->execute($sql, array('userid' => $user6->id));

        // Make the non-zero records look like they were incorrectly created by pre-patch window open.
        $allparams = array(
            array('programid' => $prog1->id, 'userid' => $user1->id),
            array('programid' => $cert1->id, 'userid' => $user1->id),
            array('programid' => $cert1->id, 'userid' => $user2->id),
            array('programid' => $cert1->id, 'userid' => $user4->id),
            array('programid' => $cert2->id, 'userid' => $user5->id),
        );
        foreach ($allparams as $params) {
            $sql = "UPDATE {prog_completion}
                       SET timestarted = 0,
                           timedue = 0
                     WHERE programid = :programid
                       AND userid = :userid
                       AND coursesetid <> 0";
            $DB->execute($sql, $params);
        }

        // See the dodgy records before upgrading.
        $where = "coursesetid <> 0 AND status = " . STATUS_COURSESET_INCOMPLETE;
        $this->assertEquals(13, $DB->count_records('prog_completion'));
        $this->assertEquals(6, $DB->count_records_select('prog_completion', $where));
        $this->assertEquals(5, $DB->count_records_select('prog_completion', $where . " AND timestarted = 0"));
        $this->assertEquals(5, $DB->count_records_select('prog_completion', $where . " AND timedue = 0"));
        $this->assertEquals(5, $DB->count_records_select('prog_completion', $where . " AND timecompleted = 0"));

        // Save user1's program prog_completion record - it should be unaffected by the upgrade.
        $where = "userid = :userid AND coursesetid <> 0 AND programid = :programid";
        $params = array('userid' => $user1->id, 'programid' => $prog1->id);
        $user1prog1prenonzerocompletion = $DB->get_record_select('prog_completion', $where, $params);

        // Wait one second, so that the existing timestamps will all be older.
        $this->waitForSecond();

        // Run the upgrade.
        totara_certification_upgrade_non_zero_prog_completions();

        // Check the results immediately after upgrade.
        $this->assertEquals(9, $DB->count_records('prog_completion'));
        $this->assertEquals(7, $DB->count_records('prog_completion', array('coursesetid' => 0))); // Every user has a course set zero record.
        $where = "userid = :userid AND coursesetid <> 0 AND programid = :programid";
        $params = array('userid' => $user1->id, 'programid' => $prog1->id);
        $this->assertEquals(1, $DB->count_records_select('prog_completion', $where, $params)); // User1's record for their program is still there.
        $params = array('userid' => $user3->id, 'programid' => $cert1->id);
        $this->assertEquals(1, $DB->count_records_select('prog_completion', $where, $params)); // User3's record which is complete is still there.

        // Run the scheduled task which recreates missing non-zero course set group prog_completion records.
        $completiontask = new \totara_program\task\completions_task();
        $completiontask->execute();

        // Check the results after cron has run. This is the overkill bit.
        $this->assertEquals(13, $DB->count_records('prog_completion')); // All except user 2 has two records.
        $this->assertEquals(7, $DB->count_records('prog_completion', array('coursesetid' => 0))); // Every user has a course set zero record.

        // Check user 1.
        $this->assertEquals(4, $DB->count_records('prog_completion', array('userid' => $user1->id)));
        // Program hasn't been affected.
        $where = "userid = :userid AND coursesetid <> 0 AND programid = :programid";
        $params = array('userid' => $user1->id, 'programid' => $prog1->id);
        $user1prog1postnonzerocompletion = $DB->get_record_select('prog_completion', $where, $params);
        $this->assertEquals($user1prog1prenonzerocompletion, $user1prog1postnonzerocompletion);
        // Record has been created for the cert.
        $where = "userid = :userid AND coursesetid <> 0 AND programid = :programid";
        $params = array('userid' => $user1->id, 'programid' => $cert1->id);
        $this->assertEquals(1, $DB->count_records_select('prog_completion', $where, $params));
        $this->assertEquals(1, $DB->count_records_select('prog_completion', $where . " AND timestarted = 0", $params));
        $this->assertEquals(0, $DB->count_records_select('prog_completion', $where . " AND timecreated = 0", $params));
        $this->assertEquals(0, $DB->count_records_select('prog_completion', $where . " AND timedue = 0", $params));
        $this->assertEquals(1, $DB->count_records_select('prog_completion', $where . " AND timecompleted = 0", $params));

        // Check user 2 only has course set zero record.
        $this->assertEquals(1, $DB->count_records('prog_completion', array('userid' => $user2->id)));
        $this->assertEquals(1, $DB->count_records('prog_completion', array('userid' => $user2->id, 'coursesetid' => 0)));

        // Check user 3's record has not been touched.
        $where = "userid = :userid AND coursesetid <> 0";
        $user3postnonzerocompletion = $DB->get_record_select('prog_completion', $where, array('userid' => $user3->id));
        $this->assertEquals($user3prenonzerocompletion, $user3postnonzerocompletion);

        // Check user 4 has a new course set zero record and has been certified.
        $this->assertEquals(2, $DB->count_records('prog_completion', array('userid' => $user4->id)));
        $this->assertEquals(1, $DB->count_records('prog_completion', array('userid' => $user4->id, 'coursesetid' => 0)));
        $where = "userid = :userid AND coursesetid <> 0 AND programid = :programid AND status = :status";
        $params = array('userid' => $user4->id, 'programid' => $cert1->id, 'status' => STATUS_COURSESET_COMPLETE);
        $this->assertEquals(1, $DB->count_records_select('prog_completion', $where, $params));
        list($certcompletion, $progcompletion) = certif_load_completion($cert1->id, $user4->id);
        $this->assertEquals(CERTIFCOMPLETIONSTATE_CERTIFIED, certif_get_completion_state($certcompletion));

        // Check user 5 record has been created.
        $where = "userid = :userid AND coursesetid <> 0 AND programid = :programid";
        $params = array('userid' => $user5->id, 'programid' => $cert2->id);
        $this->assertEquals(1, $DB->count_records_select('prog_completion', $where, $params));
        $this->assertEquals(1, $DB->count_records_select('prog_completion', $where . " AND timestarted = 0", $params));
        $this->assertEquals(0, $DB->count_records_select('prog_completion', $where . " AND timecreated = 0", $params));
        $this->assertEquals(0, $DB->count_records_select('prog_completion', $where . " AND timedue = 0", $params));
        $this->assertEquals(1, $DB->count_records_select('prog_completion', $where . " AND timecompleted = 0", $params));

        // Check user 6 record has been created.
        $where = "userid = :userid AND coursesetid <> 0 AND programid = :programid";
        $params = array('userid' => $user6->id, 'programid' => $cert1->id);
        $this->assertEquals(1, $DB->count_records_select('prog_completion', $where, $params));
        $this->assertEquals(1, $DB->count_records_select('prog_completion', $where . " AND timestarted = 0", $params));
        $this->assertEquals(0, $DB->count_records_select('prog_completion', $where . " AND timecreated = 0", $params));
        $this->assertEquals(0, $DB->count_records_select('prog_completion', $where . " AND timedue = 0", $params));
        $this->assertEquals(1, $DB->count_records_select('prog_completion', $where . " AND timecompleted = 0", $params));
    }

    public function test_totara_certification_upgrade_reset_messages() {
        global $DB;

        $this->resetAfterTest(true);

        $certs = array();
        $users = array();

        $certs[1] = $this->getDataGenerator()->create_certification(); // Logs after the dates, messages were sent after the events.
        $certs[2] = $this->getDataGenerator()->create_certification(); // Logs before the dates, messages might be from earlier events.

        $users[1] = $this->getDataGenerator()->create_user(); // Newly assigned.
        $users[2] = $this->getDataGenerator()->create_user(); // Certified.
        $users[3] = $this->getDataGenerator()->create_user(); // Window open.
        $users[4] = $this->getDataGenerator()->create_user(); // Expired.

        $this->getDataGenerator()->assign_to_program($certs[1]->id, ASSIGNTYPE_INDIVIDUAL, $users[1]->id);
        $this->getDataGenerator()->assign_to_program($certs[1]->id, ASSIGNTYPE_INDIVIDUAL, $users[2]->id);
        $this->getDataGenerator()->assign_to_program($certs[1]->id, ASSIGNTYPE_INDIVIDUAL, $users[3]->id);
        $this->getDataGenerator()->assign_to_program($certs[1]->id, ASSIGNTYPE_INDIVIDUAL, $users[4]->id);

        $this->getDataGenerator()->assign_to_program($certs[2]->id, ASSIGNTYPE_INDIVIDUAL, $users[1]->id);
        $this->getDataGenerator()->assign_to_program($certs[2]->id, ASSIGNTYPE_INDIVIDUAL, $users[2]->id);
        $this->getDataGenerator()->assign_to_program($certs[2]->id, ASSIGNTYPE_INDIVIDUAL, $users[3]->id);
        $this->getDataGenerator()->assign_to_program($certs[2]->id, ASSIGNTYPE_INDIVIDUAL, $users[4]->id);

        // User 2.
        list($certcompletion, $progcompletion) = certif_load_completion($certs[1]->id, $users[2]->id);
        $certcompletion->status = CERTIFSTATUS_COMPLETED;
        $certcompletion->renewalstatus = CERTIFRENEWALSTATUS_NOTDUE;
        $certcompletion->certifpath = CERTIFPATH_RECERT;
        $certcompletion->timecompleted = 1100;
        $certcompletion->timewindowopens = 1200;
        $certcompletion->timeexpires = 1300;
        $certcompletion->baselinetimeexpires = 1300;
        $progcompletion->timedue = 1300;
        $progcompletion->timecompleted = 1100;
        $progcompletion->status = STATUS_PROGRAM_COMPLETE;
        $this->assertEquals(array(), certif_get_completion_errors($certcompletion, $progcompletion));
        $this->assertTrue(certif_write_completion($certcompletion, $progcompletion));

        // User 3.
        list($certcompletion, $progcompletion) = certif_load_completion($certs[1]->id, $users[3]->id);
        $certcompletion->status = CERTIFSTATUS_COMPLETED;
        $certcompletion->renewalstatus = CERTIFRENEWALSTATUS_DUE;
        $certcompletion->certifpath = CERTIFPATH_RECERT;
        $certcompletion->timecompleted = 1100;
        $certcompletion->timewindowopens = 1200;
        $certcompletion->timeexpires = 1300;
        $certcompletion->baselinetimeexpires = 1300;
        $progcompletion->timedue = 1300;
        $progcompletion->timecompleted = 0;
        $progcompletion->status = STATUS_PROGRAM_INCOMPLETE;
        $this->assertEquals(array(), certif_get_completion_errors($certcompletion, $progcompletion));
        $this->assertTrue(certif_write_completion($certcompletion, $progcompletion));

        // User 4.
        list($certcompletion, $progcompletion) = certif_load_completion($certs[1]->id, $users[4]->id);
        $certcompletion->status = CERTIFSTATUS_EXPIRED;
        $certcompletion->renewalstatus = CERTIFRENEWALSTATUS_EXPIRED;
        $certcompletion->certifpath = CERTIFPATH_CERT;
        $certcompletion->timecompleted = 0;
        $certcompletion->timewindowopens = 0;
        $certcompletion->timeexpires = 0;
        $certcompletion->baselinetimeexpires = 0;
        $progcompletion->timedue = 1300;
        $progcompletion->timecompleted = 0;
        $progcompletion->status = STATUS_PROGRAM_INCOMPLETE;
        $this->assertEquals(array(), certif_get_completion_errors($certcompletion, $progcompletion));
        $this->assertTrue(certif_write_completion($certcompletion, $progcompletion));

        // User 2.
        list($certcompletion, $progcompletion) = certif_load_completion($certs[2]->id, $users[2]->id);
        $certcompletion->status = CERTIFSTATUS_COMPLETED;
        $certcompletion->renewalstatus = CERTIFRENEWALSTATUS_NOTDUE;
        $certcompletion->certifpath = CERTIFPATH_RECERT;
        $certcompletion->timecompleted = 9100;
        $certcompletion->timewindowopens = 9200;
        $certcompletion->timeexpires = 9300;
        $certcompletion->baselinetimeexpires = 9300;
        $progcompletion->timedue = 9300;
        $progcompletion->timecompleted = 9100;
        $progcompletion->status = STATUS_PROGRAM_COMPLETE;
        $this->assertEquals(array(), certif_get_completion_errors($certcompletion, $progcompletion));
        $this->assertTrue(certif_write_completion($certcompletion, $progcompletion));

        // User 3.
        list($certcompletion, $progcompletion) = certif_load_completion($certs[2]->id, $users[3]->id);
        $certcompletion->status = CERTIFSTATUS_COMPLETED;
        $certcompletion->renewalstatus = CERTIFRENEWALSTATUS_DUE;
        $certcompletion->certifpath = CERTIFPATH_RECERT;
        $certcompletion->timecompleted = 9100;
        $certcompletion->timewindowopens = 9200;
        $certcompletion->timeexpires = 9300;
        $certcompletion->baselinetimeexpires = 9300;
        $progcompletion->timedue = 9300;
        $progcompletion->timecompleted = 0;
        $progcompletion->status = STATUS_PROGRAM_INCOMPLETE;
        $this->assertEquals(array(), certif_get_completion_errors($certcompletion, $progcompletion));
        $this->assertTrue(certif_write_completion($certcompletion, $progcompletion));

        // User 4.
        list($certcompletion, $progcompletion) = certif_load_completion($certs[2]->id, $users[4]->id);
        $certcompletion->status = CERTIFSTATUS_EXPIRED;
        $certcompletion->renewalstatus = CERTIFRENEWALSTATUS_EXPIRED;
        $certcompletion->certifpath = CERTIFPATH_CERT;
        $certcompletion->timecompleted = 0;
        $certcompletion->timewindowopens = 0;
        $certcompletion->timeexpires = 0;
        $certcompletion->baselinetimeexpires = 0;
        $progcompletion->timedue = 9300;
        $progcompletion->timecompleted = 0;
        $progcompletion->status = STATUS_PROGRAM_INCOMPLETE;
        $this->assertEquals(array(), certif_get_completion_errors($certcompletion, $progcompletion));
        $this->assertTrue(certif_write_completion($certcompletion, $progcompletion));

        // Set up the messages.
        $allmessagetypes = array(
            MESSAGETYPE_ENROLMENT,
            MESSAGETYPE_UNENROLMENT,
            MESSAGETYPE_PROGRAM_COMPLETED,
            MESSAGETYPE_PROGRAM_DUE,
            MESSAGETYPE_PROGRAM_OVERDUE,
            MESSAGETYPE_COURSESET_DUE,
            MESSAGETYPE_COURSESET_OVERDUE,
            MESSAGETYPE_COURSESET_COMPLETED,
            MESSAGETYPE_RECERT_WINDOWOPEN,
            MESSAGETYPE_RECERT_WINDOWDUECLOSE,
            MESSAGETYPE_RECERT_FAILRECERT,
            MESSAGETYPE_LEARNER_FOLLOWUP,
            MESSAGETYPE_EXCEPTION_REPORT,
        );

        $messagemanager1 = $certs[1]->get_messagesmanager();
        $messagemanager2 = $certs[2]->get_messagesmanager();
        $messagemanager1->delete();
        $messagemanager2->delete();
        foreach ($allmessagetypes as $messagetype) {
            $messagemanager1->add_message($messagetype);
            $messagemanager2->add_message($messagetype);
        }
        $messagemanager1->save_messages();
        $messagemanager2->save_messages();

        // Add message logs for each user.
        $messagelogs = array();
        foreach ($certs as $cert) {
            foreach ($users as $user) {
                foreach ($allmessagetypes as $messagetype) {
                    $message = $DB->get_record('prog_message', array('programid' => $cert->id, 'messagetype' => $messagetype));
                    $messagelog = array(
                        'messageid' => $message->id,
                        'userid' => $user->id,
                        'coursesetid' => 0,
                        'timeissued' => 5000, // All logs are right in the middle.
                    );
                    $messagelogs[] = (object)$messagelog;
                }
            }
        }
        $DB->insert_records_via_batch('prog_messagelog', $messagelogs);

        $totalcount = count($certs) * count($users) * count($allmessagetypes);
        $this->assertEquals($totalcount, $DB->count_records('prog_messagelog'));

        // Execute the upgrade.
        totara_certification_upgrade_reset_messages();

        $finalcount = $totalcount - 10 - 5 - 3;
        $this->assertEquals($finalcount, $DB->count_records('prog_messagelog'));

        // Check that the correct messages were reset, and no more.
        foreach ($certs as $cert) {
            foreach ($users as $user) {
                foreach ($allmessagetypes as $messagetype) {
                    $stillexists = true;
                    if ($cert->id == $certs[1]->id) {
                        // Cert 1 dates are all earlier than the logs, so all kept.
                    } else {
                        switch ($user->id) {
                            case $users[1]->id:
                                // User 1 is newly assigned, so nothing should be reset.
                                break;
                            case $users[2]->id:
                                // User 2 is certified.
                                $messagetypes = array(
                                    MESSAGETYPE_PROGRAM_COMPLETED,
                                    MESSAGETYPE_RECERT_WINDOWOPEN,
                                    MESSAGETYPE_LEARNER_FOLLOWUP,
                                );
                                if (in_array($messagetype, $messagetypes)) {
                                    $stillexists = false;
                                }
                                break;
                            case $users[3]->id:
                                // User 3 is window open.
                                $messagetypes = array(
                                    MESSAGETYPE_PROGRAM_COMPLETED,
                                    MESSAGETYPE_PROGRAM_DUE,
                                    MESSAGETYPE_PROGRAM_OVERDUE,
                                    MESSAGETYPE_COURSESET_DUE,
                                    MESSAGETYPE_COURSESET_OVERDUE,
                                    MESSAGETYPE_COURSESET_COMPLETED,
                                    MESSAGETYPE_RECERT_WINDOWOPEN,
                                    MESSAGETYPE_RECERT_WINDOWDUECLOSE,
                                    MESSAGETYPE_RECERT_FAILRECERT,
                                    MESSAGETYPE_LEARNER_FOLLOWUP,
                                );
                                if (in_array($messagetype, $messagetypes)) {
                                    $stillexists = false;
                                }
                                break;
                            case $users[4]->id:
                                // User 4 is expired.
                                $messagetypes = array(
                                    MESSAGETYPE_PROGRAM_COMPLETED,
                                    MESSAGETYPE_PROGRAM_OVERDUE,
                                    MESSAGETYPE_RECERT_WINDOWOPEN,
                                    MESSAGETYPE_RECERT_FAILRECERT,
                                    MESSAGETYPE_LEARNER_FOLLOWUP,
                                );
                                if (in_array($messagetype, $messagetypes)) {
                                    $stillexists = false;
                                }
                                break;
                        }
                    }

                    $message = $DB->get_record('prog_message', array('programid' => $cert->id, 'messagetype' => $messagetype));
                    $params = array(
                        'messageid' => $message->id,
                        'userid' => $user->id,
                        'coursesetid' => 0,
                        'timeissued' => 5000,
                    );
                    $record = $DB->get_record('prog_messagelog', $params);

                    if ($stillexists) {
                        $this->assertNotFalse($record);
                    } else {
                        $this->assertFalse($record);
                    }
                }
            }
        }
    }
}
