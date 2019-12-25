<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2015 onwards Totara Learning Solutions LTD
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
 * @author Petr Skoda <petr.skoda@totaralms.com>
 * @package totara_reportbuilder
 */

defined('MOODLE_INTERNAL') || die();

/**
 * @group totara_reportbuilder
 */
class totara_reportbuilder_scheduled_export_testcase extends advanced_testcase {
    use totara_reportbuilder\phpunit\report_testing;

    public function test_reportbuilder_get_schduled_report() {
        global $DB, $CFG;
        require_once("$CFG->dirroot/totara/reportbuilder/lib.php");

        $this->resetAfterTest();
        $this->setAdminUser(); // We need permissions to access all reports.

        set_config('exporttofilesystem', '0', 'reportbuilder');

        $rid = $this->create_report('user', 'Test user report 1');
        $DB->set_field('report_builder', 'defaultsortcolumn', 'user_id', array('id' => $rid));
        $DB->set_field('report_builder', 'defaultsortorder', SORT_ASC, array('id' => $rid));

        $config = (new rb_config())->set_nocache(true);
        $report = reportbuilder::create($rid, $config);
        $this->add_column($report, 'user', 'id', null, null, null, 0);
        $this->add_column($report, 'user', 'firstname', null, null, null, 0);
        $this->add_column($report, 'user', 'lastname', null, null, null, 0);

        $user = $this->getDataGenerator()->create_user();

        $schedule = new stdClass();
        $schedule->reportid = $report->_id;
        $schedule->savedsearchid = 0;
        $schedule->format = 'csv';
        $schedule->frequency = 1; // Means daily.
        $schedule->schedule = 0; // Means midnight.
        $schedule->exporttofilesystem = REPORT_BUILDER_EXPORT_EMAIL;
        $schedule->nextreport = 0; // Means asap.
        $schedule->userid = $user->id;
        $schedule->usermodified = $user->id;
        $schedule->lastmodified = time();
        $schedule->id = $DB->insert_record('report_builder_schedule', $schedule);
        $schedule = $DB->get_record('report_builder_schedule', array('id' => $schedule->id));

        $reportrecord = $DB->get_record('report_builder', array('id' => $rid));

        $report = reportbuilder_get_schduled_report($schedule, $reportrecord);
        $this->assertInstanceOf('reportbuilder', $report);

        $reportrecord->id = $reportrecord->id + 1;
        try {
            reportbuilder_get_schduled_report($schedule, $reportrecord);
            $this->fail('Exception expected');
        } catch (moodle_exception $e) {
            $this->assertInstanceOf('coding_exception', $e);
            $this->assertEquals('Coding error detected, it must be fixed by a programmer: Invalid parameters', $e->getMessage());
        }
    }

    /**
     * Test basic stuff.
     */
    public function test_reportbuilder_send_scheduled_report() {
        global $DB, $CFG;
        require_once("$CFG->dirroot/totara/reportbuilder/lib.php");

        $this->resetAfterTest();
        $this->setAdminUser(); // We need permissions to access all reports.

        $testdir = make_writable_directory($CFG->dataroot . '/mytest');
        $testdir = realpath($testdir);
        $this->assertFileExists($testdir);

        set_config('exporttofilesystem', '1', 'reportbuilder');
        set_config('exporttofilesystempath', $testdir, 'reportbuilder');

        $admin = get_admin();
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $audience = $this->getDataGenerator()->create_cohort();
        cohort_add_member($audience->id, $user2->id);

        $rid = $this->create_report('user', 'Test user report 1');
        $DB->set_field('report_builder', 'defaultsortcolumn', 'user_id', array('id' => $rid));
        $DB->set_field('report_builder', 'defaultsortorder', SORT_ASC, array('id' => $rid));

        $config = (new rb_config())->set_nocache(true);
        $report = reportbuilder::create($rid, $config);
        $this->add_column($report, 'user', 'id', null, null, null, 0);
        $this->add_column($report, 'user', 'firstname', null, null, null, 0);
        $this->add_column($report, 'user', 'lastname', null, null, null, 0);

        $report = reportbuilder::create($rid);

        $schedule1 = new stdClass();
        $schedule1->reportid = $report->_id;
        $schedule1->savedsearchid = 0;
        $schedule1->format = 'csv';
        $schedule1->frequency = 1; // Means daily.
        $schedule1->schedule = 0; // Means midnight.
        $schedule1->exporttofilesystem = REPORT_BUILDER_EXPORT_EMAIL_AND_SAVE;
        $schedule1->nextreport = 0; // Means asap.
        $schedule1->userid = $admin->id;
        $schedule1->usermodified = $admin->id;
        $schedule1->lastmodified = time();
        $schedule1->id = $DB->insert_record('report_builder_schedule', $schedule1);
        $schedule1 = $DB->get_record('report_builder_schedule', array('id' => $schedule1->id));
        $DB->insert_record('report_builder_schedule_email_systemuser', array('scheduleid' => $schedule1->id, 'userid' => $user1->id));
        $DB->insert_record('report_builder_schedule_email_external', array('scheduleid' => $schedule1->id, 'email' => 'xxx@example.com'));
        $DB->insert_record('report_builder_schedule_email_audience', array('scheduleid' => $schedule1->id, 'cohortid' => $audience->id));
        ob_start(); // Verify diagnostic output.
        $result = reportbuilder_send_scheduled_report($schedule1);
        $this->assertTrue($result);
        $info = ob_get_contents();
        ob_end_clean();
        $expected = "Scheduled report {$schedule1->id} was saved in file system\nScheduled report {$schedule1->id} was emailed to 3 users\n";
        $this->assertSame($expected, $info);

        $schedule2 = new stdClass();
        $schedule2->reportid = $report->_id;
        $schedule2->savedsearchid = 0;
        $schedule2->format = 'csv';
        $schedule2->frequency = 1; // Means daily.
        $schedule2->schedule = 0; // Means midnight.
        $schedule2->exporttofilesystem = REPORT_BUILDER_EXPORT_SAVE;
        $schedule2->nextreport = 0; // Means asap.
        $schedule2->userid = $admin->id;
        $schedule2->usermodified = $admin->id;
        $schedule2->lastmodified = time();
        $schedule2->id = $DB->insert_record('report_builder_schedule', $schedule2);
        $schedule2 = $DB->get_record('report_builder_schedule', array('id' => $schedule2->id));
        $DB->insert_record('report_builder_schedule_email_systemuser', array('scheduleid' => $schedule2->id, 'userid' => $user1->id));
        $DB->insert_record('report_builder_schedule_email_external', array('scheduleid' => $schedule2->id, 'email' => 'xxx@example.com'));
        $DB->insert_record('report_builder_schedule_email_audience', array('scheduleid' => $schedule2->id, 'cohortid' => $audience->id));
        ob_start(); // Verify diagnostic output.
        $result = reportbuilder_send_scheduled_report($schedule2);
        $this->assertTrue($result);
        $info = ob_get_contents();
        ob_end_clean();
        $expected = "Scheduled report {$schedule2->id} was saved in file system\n";
        $this->assertSame($expected, $info);

        $schedule3 = new stdClass();
        $schedule3->reportid = $report->_id;
        $schedule3->savedsearchid = 0;
        $schedule3->format = 'csv';
        $schedule3->frequency = 1; // Means daily.
        $schedule3->schedule = 0; // Means midnight.
        $schedule3->exporttofilesystem = REPORT_BUILDER_EXPORT_EMAIL;
        $schedule3->nextreport = 0; // Means asap.
        $schedule3->userid = $admin->id;
        $schedule3->usermodified = $admin->id;
        $schedule3->lastmodified = time();
        $schedule3->id = $DB->insert_record('report_builder_schedule', $schedule3);
        $schedule3 = $DB->get_record('report_builder_schedule', array('id' => $schedule3->id));
        $DB->insert_record('report_builder_schedule_email_systemuser', array('scheduleid' => $schedule3->id, 'userid' => $user1->id));
        $DB->insert_record('report_builder_schedule_email_external', array('scheduleid' => $schedule3->id, 'email' => 'xxx@example.com'));
        $DB->insert_record('report_builder_schedule_email_audience', array('scheduleid' => $schedule3->id, 'cohortid' => $audience->id));
        ob_start(); // Verify diagnostic output.
        $result = reportbuilder_send_scheduled_report($schedule3);
        $this->assertTrue($result);
        $info = ob_get_contents();
        ob_end_clean();
        $expected = "Scheduled report {$schedule3->id} was emailed to 3 users\n";
        $this->assertSame($expected, $info);

        // Invalid current user.
        $schedule4 = new stdClass();
        $schedule4->reportid = $report->_id;
        $schedule4->savedsearchid = 0;
        $schedule4->format = 'csv';
        $schedule4->frequency = 1; // Means daily.
        $schedule4->schedule = 0; // Means midnight.
        $schedule4->exporttofilesystem = REPORT_BUILDER_EXPORT_EMAIL;
        $schedule4->nextreport = 0; // Means asap.
        $schedule4->userid = $user1->id;
        $schedule4->usermodified = $user1->id;
        $schedule4->lastmodified = time();
        $schedule4->id = $DB->insert_record('report_builder_schedule', $schedule4);
        $schedule4 = $DB->get_record('report_builder_schedule', array('id' => $schedule4->id));
        $this->setUser($user2);
        try {
            reportbuilder_send_scheduled_report($schedule4);
            $this->fail('Exception expected when users not matching');
        } catch (moodle_exception $e) {
            $this->assertInstanceOf('coding_exception', $e);
            $this->assertEquals('Coding error detected, it must be fixed by a programmer: reportbuilder_send_scheduled_report() requires $USER->id to be the same as sched->userid!', $e->getMessage());
        }
        $this->setUser($user1);
        ob_start();
        $result = reportbuilder_send_scheduled_report($schedule4);
        $this->assertTrue($result);
        $result = reportbuilder_send_scheduled_report($schedule4);
        $this->assertTrue($result);
        ob_end_clean();

        $DB->set_field('user', 'suspended', 1, array('id' => $user1->id));
        $user1->suspended = '1';
        $this->setUser($user1);
        try {
            reportbuilder_send_scheduled_report($schedule4);
            $this->fail('Exception expected when user suspended');
        } catch (moodle_exception $e) {
            $this->assertInstanceOf('coding_exception', $e);
            $this->assertEquals('Coding error detected, it must be fixed by a programmer: reportbuilder_send_scheduled_report() requires active user!', $e->getMessage());
        }

        $DB->set_field('user', 'suspended', 0, array('id' => $user1->id));
        $user1->suspended = '0';
        $DB->set_field('user', 'deleted', 1, array('id' => $user1->id));
        $user1->deleted = '1';
        $this->setUser($user1);
        try {
            reportbuilder_send_scheduled_report($schedule4);
            $this->fail('Exception expected when user suspended');
        } catch (moodle_exception $e) {
            $this->assertInstanceOf('coding_exception', $e);
            $this->assertEquals('Coding error detected, it must be fixed by a programmer: reportbuilder_send_scheduled_report() requires active user!', $e->getMessage());
        }
    }

    public function test_process_scheduled_task() {
        global $DB, $CFG;
        require_once("$CFG->dirroot/totara/reportbuilder/lib.php");

        $this->resetAfterTest();
        $this->setAdminUser(); // We need permissions to access all reports.

        $testdir = make_writable_directory($CFG->dataroot . '/mytest');
        $testdir = realpath($testdir);
        $this->assertFileExists($testdir);

        set_config('exporttofilesystem', '1', 'reportbuilder');
        set_config('exporttofilesystempath', $testdir, 'reportbuilder');

        $admin = get_admin();

        $rid = $this->create_report('user', 'Test user report 1');
        $DB->set_field('report_builder', 'defaultsortcolumn', 'user_id', array('id' => $rid));
        $DB->set_field('report_builder', 'defaultsortorder', SORT_ASC, array('id' => $rid));

        $config = (new rb_config())->set_nocache(true);
        $report = reportbuilder::create($rid, $config);
        $this->add_column($report, 'user', 'id', null, null, null, 0);
        $this->add_column($report, 'user', 'firstname', null, null, null, 0);
        $this->add_column($report, 'user', 'lastname', null, null, null, 0);

        $report = reportbuilder::create($rid);

        $schedule1 = new stdClass();
        $schedule1->reportid = $report->_id;
        $schedule1->savedsearchid = 0;
        $schedule1->format = 'csv';
        $schedule1->frequency = 1; // Means daily.
        $schedule1->schedule = 0; // Means midnight.
        $schedule1->exporttofilesystem = REPORT_BUILDER_EXPORT_EMAIL_AND_SAVE;
        $schedule1->nextreport = 0; // Means asap.
        $schedule1->userid = $admin->id;
        $schedule1->usermodified = $admin->id;
        $schedule1->lastmodified = time();
        $schedule1->id = $DB->insert_record('report_builder_schedule', $schedule1);
        $schedule1 = $DB->get_record('report_builder_schedule', array('id' => $schedule1->id));
        $DB->insert_record('report_builder_schedule_email_systemuser', array('scheduleid' => $schedule1->id, 'userid' => $admin->id));

        $sink = $this->redirectEmails();

        $task = new \totara_reportbuilder\task\process_scheduled_task();
        ob_start(); // Verify diagnostic output.
        $task->execute();
        $info = ob_get_contents();
        ob_end_clean();
        $expected = "Processing 1 scheduled reports\nScheduled report {$schedule1->id} was saved in file system\nScheduled report {$schedule1->id} was emailed to 1 users\n";
        $this->assertSame($expected, $info);
        $messages = $sink->get_messages();
        $this->assertCount(1, $messages);
        $this->assertSame($admin->email, $messages[0]->to);

        $newwschedule1 = $DB->get_record('report_builder_schedule', array('id' => $schedule1->id));
        $this->assertGreaterThan(time(), $newwschedule1->nextreport);

        set_config('exporttofilesystem', '0', 'reportbuilder');
        $DB->set_field('report_builder_schedule', 'nextreport', 0, array('id' => $schedule1->id));
        $task = new \totara_reportbuilder\task\process_scheduled_task();
        ob_start(); // Verify diagnostic output.
        $sink->clear();
        $task->execute();
        $info = ob_get_contents();
        ob_end_clean();
        $expected = "Processing 1 scheduled reports\nExporting of scheduled reports to file system is disabled\nScheduled report {$schedule1->id} was emailed to 1 users\n";
        $this->assertSame($expected, $info);
        $messages = $sink->get_messages();
        $this->assertCount(1, $messages);
        $this->assertSame($admin->email, $messages[0]->to);

        set_config('exporttofilesystem', '0', 'reportbuilder');
        $DB->set_field('report_builder_schedule', 'nextreport', 0, array('id' => $schedule1->id));
        $DB->set_field('report_builder_schedule', 'exporttofilesystem', REPORT_BUILDER_EXPORT_SAVE, array('id' => $schedule1->id));
        $task = new \totara_reportbuilder\task\process_scheduled_task();
        ob_start(); // Verify diagnostic output.
        $sink->clear();
        $task->execute();
        $info = ob_get_contents();
        ob_end_clean();
        $expected = "Processing 1 scheduled reports\nExporting of scheduled reports to file system is disabled\nError: Scheduled report {$schedule1->id} is set to export to filesystem only\n";
        $this->assertSame($expected, $info);
        $messages = $sink->get_messages();
        $this->assertCount(0, $messages);

        set_config('exporttofilesystem', '1', 'reportbuilder');
        $DB->set_field('report_builder_schedule', 'nextreport', 0, array('id' => $schedule1->id));
        $DB->set_field('report_builder_schedule', 'exporttofilesystem', REPORT_BUILDER_EXPORT_EMAIL_AND_SAVE, array('id' => $schedule1->id));
        $DB->set_field('report_builder_schedule', 'format', 'xxxx', array('id' => $schedule1->id));
        $task = new \totara_reportbuilder\task\process_scheduled_task();
        ob_start(); // Verify diagnostic output.
        $sink->clear();
        $task->execute();
        $info = ob_get_contents();
        ob_end_clean();
        $expected = "Processing 1 scheduled reports\nError: Scheduled report {$schedule1->id} uses unknown or disabled format 'xxxx'\n";
        $this->assertSame($expected, $info);
        $messages = $sink->get_messages();
        $this->assertCount(0, $messages);

        $sink->close();
    }

    public function test_process_scheduled_task_skip_inactive_users() {
        global $DB, $CFG;
        require_once("$CFG->dirroot/totara/reportbuilder/lib.php");

        $this->resetAfterTest();
        $this->setAdminUser(); // We need permissions to access all reports.

        set_config('exporttofilesystem', '0', 'reportbuilder');

        $rid = $this->create_report('user', 'Test user report 1');
        $DB->set_field('report_builder', 'defaultsortcolumn', 'user_id', array('id' => $rid));
        $DB->set_field('report_builder', 'defaultsortorder', SORT_ASC, array('id' => $rid));

        $config = (new rb_config())->set_nocache(true);
        $report = reportbuilder::create($rid, $config);
        $this->add_column($report, 'user', 'id', null, null, null, 0);
        $this->add_column($report, 'user', 'firstname', null, null, null, 0);
        $this->add_column($report, 'user', 'lastname', null, null, null, 0);

        $report = reportbuilder::create($rid);

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user(array('deleted' => 1));
        $user3 = $this->getDataGenerator()->create_user(array('suspended' => 1));

        $schedule1 = new stdClass();
        $schedule1->reportid = $report->_id;
        $schedule1->savedsearchid = 0;
        $schedule1->format = 'csv';
        $schedule1->frequency = 1; // Means daily.
        $schedule1->schedule = 0; // Means midnight.
        $schedule1->exporttofilesystem = REPORT_BUILDER_EXPORT_EMAIL;
        $schedule1->nextreport = 0; // Means asap.
        $schedule1->userid = $user1->id;
        $schedule1->usermodified = $user1->id;
        $schedule1->lastmodified = time();
        $schedule1->id = $DB->insert_record('report_builder_schedule', $schedule1);
        $schedule1 = $DB->get_record('report_builder_schedule', array('id' => $schedule1->id));
        $DB->insert_record('report_builder_schedule_email_systemuser', array('scheduleid' => $schedule1->id, 'userid' => $user1->id));

        $schedule2 = new stdClass();
        $schedule2->reportid = $report->_id;
        $schedule2->savedsearchid = 0;
        $schedule2->format = 'csv';
        $schedule2->frequency = 1; // Means daily.
        $schedule2->schedule = 0; // Means midnight.
        $schedule2->exporttofilesystem = REPORT_BUILDER_EXPORT_EMAIL;
        $schedule2->nextreport = 0; // Means asap.
        $schedule2->userid = $user2->id;
        $schedule2->usermodified = $user2->id;
        $schedule2->lastmodified = time();
        $schedule2->id = $DB->insert_record('report_builder_schedule', $schedule2);
        $schedule2 = $DB->get_record('report_builder_schedule', array('id' => $schedule2->id));
        $DB->insert_record('report_builder_schedule_email_systemuser', array('scheduleid' => $schedule2->id, 'userid' => $user2->id));

        $schedule3 = new stdClass();
        $schedule3->reportid = $report->_id;
        $schedule3->savedsearchid = 0;
        $schedule3->format = 'csv';
        $schedule3->frequency = 1; // Means daily.
        $schedule3->schedule = 0; // Means midnight.
        $schedule3->exporttofilesystem = REPORT_BUILDER_EXPORT_EMAIL;
        $schedule3->nextreport = 0; // Means asap.
        $schedule3->userid = $user3->id;
        $schedule3->usermodified = $user3->id;
        $schedule3->lastmodified = time();
        $schedule3->id = $DB->insert_record('report_builder_schedule', $schedule3);
        $schedule3 = $DB->get_record('report_builder_schedule', array('id' => $schedule3->id));
        $DB->insert_record('report_builder_schedule_email_systemuser', array('scheduleid' => $schedule3->id, 'userid' => $user3->id));

        $this->setAdminUser();

        $task = new \totara_reportbuilder\task\process_scheduled_task();
        $sink = $this->redirectEmails();
        ob_start(); // Verify diagnostic output.
        $task->execute();
        $info = ob_get_contents();
        ob_end_clean();
        $expected = "Processing 1 scheduled reports\nExporting of scheduled reports to file system is disabled\nScheduled report {$schedule1->id} was emailed to 1 users\n";
        $this->assertSame($expected, $info);
        $messages = $sink->get_messages();
        $sink->close();
        $this->assertCount(1, $messages);
        $this->assertSame($user1->email, $messages[0]->to);

        $newwschedule1 = $DB->get_record('report_builder_schedule', array('id' => $schedule1->id));
        $this->assertGreaterThan(time(), $newwschedule1->nextreport);

        $newwschedule2 = $DB->get_record('report_builder_schedule', array('id' => $schedule2->id));
        $this->assertEquals(0, $newwschedule2->nextreport);

        $newwschedule3 = $DB->get_record('report_builder_schedule', array('id' => $schedule3->id));
        $this->assertEquals(0, $newwschedule3->nextreport);
    }

    /**
     * Test saved search.
     */
    public function test_reportbuilder_send_scheduled_report_saved_search() {
        global $DB, $CFG;
        require_once("$CFG->dirroot/totara/reportbuilder/lib.php");

        $this->resetAfterTest();
        $this->setAdminUser(); // We need permissions to access all reports.

        $testdir = make_writable_directory($CFG->dataroot . '/mytest');
        $testdir = realpath($testdir);
        $this->assertFileExists($testdir);

        set_config('exporttofilesystem', '1', 'reportbuilder');
        set_config('exporttofilesystempath', $testdir, 'reportbuilder');

        $admin = get_admin();
        $user1 = $this->getDataGenerator()->create_user();

        $rid = $this->create_report('user', 'Test user report 1');
        $DB->set_field('report_builder', 'defaultsortcolumn', 'user_id', array('id' => $rid));
        $DB->set_field('report_builder', 'defaultsortorder', SORT_ASC, array('id' => $rid));

        $config = (new rb_config())->set_nocache(true);
        $report = reportbuilder::create($rid, $config);
        $this->add_column($report, 'user', 'id', null, null, null, 0);
        $this->add_column($report, 'user', 'firstname', null, null, null, 0);
        $this->add_column($report, 'user', 'lastname', null, null, null, 0);

        $report = reportbuilder::create($rid);

        $rbsaved = new stdClass();
        $rbsaved->reportid = $report->_id;
        $rbsaved->userid = $user1->id;
        $rbsaved->name = 'Saved Search';
        $rbsaved->search = 'a:1:{s:13:"user-fullname";a:2:{s:8:"operator";i:0;s:5:"value";s:5:"Admin";}}';
        $rbsaved->ispublic = 1;
        $rbsaved->id = $DB->insert_record('report_builder_saved', $rbsaved);

        $schedule1 = new stdClass();
        $schedule1->reportid = $report->_id;
        $schedule1->savedsearchid = $rbsaved->id;
        $schedule1->format = 'csv';
        $schedule1->frequency = 1; // Means daily.
        $schedule1->schedule = 0; // Means midnight.
        $schedule1->exporttofilesystem = REPORT_BUILDER_EXPORT_EMAIL_AND_SAVE;
        $schedule1->nextreport = 0; // Means asap.
        $schedule1->userid = $admin->id;
        $schedule1->usermodified = $admin->id;
        $schedule1->lastmodified = time();
        $schedule1->id = $DB->insert_record('report_builder_schedule', $schedule1);
        $schedule1 = $DB->get_record('report_builder_schedule', array('id' => $schedule1->id));
        $DB->insert_record('report_builder_schedule_email_systemuser', array('scheduleid' => $schedule1->id, 'userid' => $admin->id));
        ob_start(); // Verify diagnostic output.
        $result = reportbuilder_send_scheduled_report($schedule1);
        $this->assertTrue($result);
        $info = ob_get_contents();
        ob_end_clean();
        $expected = "Scheduled report {$schedule1->id} was saved in file system\nScheduled report {$schedule1->id} was emailed to 1 users\n";
        $this->assertSame($expected, $info);

        $DB->set_field('report_builder_saved', 'ispublic', 0, array('id' => $rbsaved->id));
        ob_start(); // Verify diagnostic output.
        $result = reportbuilder_send_scheduled_report($schedule1);
        $this->assertFalse($result);
        $info = ob_get_contents();
        ob_end_clean();
        $expected = "Error: Scheduled report {$schedule1->id} uses non-public saved search {$rbsaved->id} of other user\n";
        $this->assertSame($expected, $info);

        $DB->set_field('report_builder_saved', 'userid', $admin->id, array('id' => $rbsaved->id));
        ob_start(); // Verify diagnostic output.
        $result = reportbuilder_send_scheduled_report($schedule1);
        $this->assertTrue($result);
        $info = ob_get_contents();
        ob_end_clean();
        $expected = "Scheduled report {$schedule1->id} was saved in file system\nScheduled report {$schedule1->id} was emailed to 1 users\n";
        $this->assertSame($expected, $info);
    }

    /**
     * Test report access.
     */
    public function test_reportbuilder_send_scheduled_report_report_access() {
        global $DB, $CFG;
        require_once("$CFG->dirroot/totara/reportbuilder/lib.php");

        $this->resetAfterTest();
        $this->setAdminUser(); // We need permissions to access all reports.

        $testdir = make_writable_directory($CFG->dataroot . '/mytest');
        $testdir = realpath($testdir);
        $this->assertFileExists($testdir);

        set_config('exporttofilesystem', '1', 'reportbuilder');
        set_config('exporttofilesystempath', $testdir, 'reportbuilder');

        $user1 = $this->getDataGenerator()->create_user();

        $rid = $this->create_report('user', 'Test user report 1');
        $DB->set_field('report_builder', 'defaultsortcolumn', 'user_id', array('id' => $rid));
        $DB->set_field('report_builder', 'defaultsortorder', SORT_ASC, array('id' => $rid));
        $DB->set_field('report_builder', 'accessmode', 1, array('id' => $rid));

        $config = (new rb_config())->set_nocache(true);
        $report = reportbuilder::create($rid, $config);
        $this->add_column($report, 'user', 'id', null, null, null, 0);
        $this->add_column($report, 'user', 'firstname', null, null, null, 0);
        $this->add_column($report, 'user', 'lastname', null, null, null, 0);

        $this->setUser($user1);

        $schedule1 = new stdClass();
        $schedule1->reportid = $rid;
        $schedule1->savedsearchid = 0;
        $schedule1->format = 'csv';
        $schedule1->frequency = 1; // Means daily.
        $schedule1->schedule = 0; // Means midnight.
        $schedule1->exporttofilesystem = REPORT_BUILDER_EXPORT_EMAIL_AND_SAVE;
        $schedule1->nextreport = 0; // Means asap.
        $schedule1->userid = $user1->id;
        $schedule1->usermodified = $user1->id;
        $schedule1->lastmodified = time();
        $schedule1->id = $DB->insert_record('report_builder_schedule', $schedule1);
        $schedule1 = $DB->get_record('report_builder_schedule', array('id' => $schedule1->id));
        $DB->insert_record('report_builder_schedule_email_systemuser', array('scheduleid' => $schedule1->id, 'userid' => $user1->id));
        ob_start(); // Verify diagnostic output.
        $result = reportbuilder_send_scheduled_report($schedule1);
        $this->assertFalse($result);
        $info = ob_get_contents();
        ob_end_clean();
        $expected = "Error: Scheduled report {$schedule1->id} references report {$rid} that cannot be accessed by user {$user1->id}\n";
        $this->assertSame($expected, $info);

        $syscontext = context_system::instance();
        $managerrole = $DB->get_record('role', array('shortname' => 'manager'));
        role_assign($managerrole->id, $user1->id, $syscontext->id);
        $this->setUser($user1);
        ob_start(); // Verify diagnostic output.
        $result = reportbuilder_send_scheduled_report($schedule1);
        $this->assertTrue($result);
        $info = ob_get_contents();
        ob_end_clean();
        $expected = "Scheduled report {$schedule1->id} was saved in file system\nScheduled report {$schedule1->id} was emailed to 1 users\n";
        $this->assertSame($expected, $info);
    }

    /**
     * Verify all report export formats work in scheduled reports.
     */
    public function test_export_formats() {
        global $DB, $CFG;
        require_once("$CFG->dirroot/totara/reportbuilder/lib.php");

        $this->resetAfterTest();
        $this->setAdminUser(); // We need permissions to access all reports.

        $testdir = make_writable_directory($CFG->dataroot . '/mytest');
        $testdir = realpath($testdir);
        $this->assertFileExists($testdir);

        set_config('exporttofilesystem', '1', 'reportbuilder');
        set_config('exporttofilesystempath', $testdir, 'reportbuilder');

        $admin = get_admin();

        $rid = $this->create_report('user', 'Test user report 1');
        $DB->set_field('report_builder', 'defaultsortcolumn', 'user_id', array('id' => $rid));
        $DB->set_field('report_builder', 'defaultsortorder', SORT_ASC, array('id' => $rid));

        $config = (new rb_config())->set_nocache(true);
        $report = reportbuilder::create($rid, $config);
        $this->add_column($report, 'user', 'id', null, null, null, 0);
        $this->add_column($report, 'user', 'firstname', null, null, null, 0);
        $this->add_column($report, 'user', 'lastname', null, null, null, 0);

        $report = reportbuilder::create($rid);

        $schedules = array();
        $plugins = \totara_core\tabexport_writer::get_export_classes();

        foreach ($plugins as $plugin => $classname) {
            if (!$classname::is_ready()) {
                // We cannot test plugins that are not ready.
                continue;
            }
            $schedule = new stdClass();
            $schedule->id = 0;
            $schedule->reportid = $report->_id;
            $schedule->savedsearchid = 0;
            $schedule->format = $plugin;
            $schedule->frequency = 1; // Means daily.
            $schedule->schedule = 0; // Means midnight.
            $schedule->exporttofilesystem = REPORT_BUILDER_EXPORT_EMAIL_AND_SAVE;
            $schedule->nextreport = 0; // Means asap.
            $schedule->userid = $admin->id;
            $schedule->usermodified = $admin->id;
            $schedule->lastmodified = time();
            $schedule->id = $DB->insert_record('report_builder_schedule', $schedule);
            $schedules[$schedule->id] = $DB->get_record('report_builder_schedule', array('id' => $schedule->id));
        }
        $this->assertNotEmpty($schedules);

        // Everything is ready, now create and test the files.
        foreach ($schedules as $schedule) {
            $writer = $plugins[$schedule->format];
            $this->assertTrue(class_exists($writer));
            ob_start(); // Verify diagnostic output.
            $result = reportbuilder_send_scheduled_report($schedule);
            $this->assertTrue($result);
            $reportfilepathname = reportbuilder_get_export_filename($report, $admin->id, $schedule->id) . '.' . $writer::get_file_extension();
            $info = ob_get_contents();
            ob_end_clean();
            $expected = "Scheduled report {$schedule->id} was saved in file system\nScheduled report {$schedule->id} was not emailed to any users\n";
            $this->assertSame($expected, $info);
            $this->assertFileExists($reportfilepathname);
            unlink($reportfilepathname);
        }

        $schedule = new stdClass();
        $schedule->id = 0;
        $schedule->reportid = $report->_id;
        $schedule->savedsearchid = 0;
        $schedule->format = 'xxxxxxxdfdsfdfds';
        $schedule->frequency = 1; // Means daily.
        $schedule->schedule = 0; // Means midnight.
        $schedule->exporttofilesystem = REPORT_BUILDER_EXPORT_EMAIL_AND_SAVE;
        $schedule->nextreport = 0; // Means asap.
        $schedule->userid = $admin->id;
        $schedule->usermodified = $admin->id;
        $schedule->lastmodified = time();
        $schedule->id = $DB->insert_record('report_builder_schedule', $schedule);
        $schedule = $DB->get_record('report_builder_schedule', array('id' => $schedule->id));
        ob_start(); // Verify diagnostic output.
        $result = reportbuilder_send_scheduled_report($schedule);
        $this->assertFalse($result);
        $info = ob_get_contents();
        ob_end_clean();
        $expected = "Error: Scheduled report {$schedule->id} uses unknown or disabled format 'xxxxxxxdfdsfdfds'\n";
        $this->assertSame($expected, $info);
    }
}
