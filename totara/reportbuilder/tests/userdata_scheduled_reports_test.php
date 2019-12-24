<?php
/*
 * This file is part of Totara Learn
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
 * @author Valerii Kuznetsov <valerii.kuznetsov@totaralearning.com>
 * @package totara_reportbuilder
 */

use totara_reportbuilder\userdata\scheduled_reports;
use totara_userdata\userdata\target_user;

defined('MOODLE_INTERNAL') || die();

global $CFG;

/**
 * This class tests purging and exporting scheduled report userdata item.
 *
 * @group totara_reportbuilder
 * @group totara_userdata
 */
class totara_reportbuilder_userdata_scheduled_reports_test extends advanced_testcase {

    /**
     * Generate data for purge, export, and count test
     * It generates: 2 users, 1 report, 1 private search, 2 scheduled reports (1 per user)
     * each scheduled report has 1 audience, 2 sys users, 1 external
     * @return array generated data
     */
    public function seed() {
        global $DB;
        $this->resetAfterTest(true);

        $gen = $this->getDataGenerator();
        /** @var \totara_reportbuilder_generator $rbgen */
        $rbgen = $gen->get_plugin_generator('totara_reportbuilder');

        // 3 users
        $user = $gen->create_user();
        $otheruser = $gen->create_user();
        $thirduser = $gen->create_user();

        // Audience
        $cohort = $gen->create_cohort();

        // Report.
        $report = (object)['fullname' => 'Users', 'shortname' => 'user',
            'source' => 'user', 'hidden'=>1, 'embedded' => 1];
        $report->id = $DB->insert_record('report_builder', $report);

        // Saved search.
        $usersearch = $rbgen->create_saved_search($report, $user, ['ispublic' => 1]);

        // Scheduled reports.
        $usersched = $rbgen->create_scheduled_report($report, $user, ['savedsearch' => $usersearch]);
        $otherusersched = $rbgen->create_scheduled_report($report, $otheruser, ['savedsearch' => $usersearch]);

        foreach([$usersched, $otherusersched] as $schedule) {
            $rbgen->add_scheduled_audience($schedule, $cohort);
            $rbgen->add_scheduled_email($schedule);
            $rbgen->add_scheduled_user($schedule, $user);
            $rbgen->add_scheduled_user($schedule, $otheruser);
        }
        $rbgen->add_scheduled_user($otherusersched, $thirduser);

        $this->assertTrue($DB->record_exists('report_builder_schedule', ['userid' => $user->id]));

        $this->assertTrue($DB->record_exists('report_builder_schedule_email_audience', ['scheduleid' => $usersched->id]));
        $this->assertTrue($DB->record_exists('report_builder_schedule_email_audience', ['scheduleid' => $otherusersched->id]));

        $this->assertTrue($DB->record_exists('report_builder_schedule_email_external', ['scheduleid' => $usersched->id]));
        $this->assertTrue($DB->record_exists('report_builder_schedule_email_external', ['scheduleid' => $otherusersched->id]));

        $this->assertTrue($DB->record_exists('report_builder_schedule_email_systemuser',
            ['scheduleid' => $usersched->id, 'userid' => $user->id]));

        $this->assertTrue($DB->record_exists('report_builder_schedule_email_systemuser',
            ['scheduleid' => $usersched->id, 'userid' => $otheruser->id]));

        $this->assertTrue($DB->record_exists('report_builder_schedule_email_systemuser',
            ['scheduleid' => $otherusersched->id, 'userid' => $user->id]));

        $this->assertTrue($DB->record_exists('report_builder_schedule_email_systemuser',
            ['scheduleid' => $otherusersched->id, 'userid' => $otheruser->id]));

        return [
            'user' => $user,
            'otheruser' => $otheruser,
            'report' => $report,
            'cohort' => $cohort,
            'usersearch' => $usersearch,
            'usersched' => $usersched,
            'otherusersched' => $otherusersched,
        ];
    }

    /**
     * Test that data are purged correctly
     */
    public function test_purge() {
        global $DB;
        $this->resetAfterTest(true);

        $seed = (object)$this->seed();

        // Run purge.
        $targetuser = new target_user($seed->user);
        $otheruser = new target_user($seed->otheruser);

        $precount = scheduled_reports::execute_count($targetuser, context_system::instance());
        $precountother = scheduled_reports::execute_count($otheruser, context_system::instance());

        scheduled_reports::execute_purge($targetuser, context_system::instance());

        $postcount = scheduled_reports::execute_count($targetuser, context_system::instance());
        $postcountother = scheduled_reports::execute_count($otheruser, context_system::instance());

        $this->assertEquals(1, $precount);
        $this->assertEquals(1, $precountother);
        $this->assertEquals(0, $postcount);
        $this->assertEquals(1, $postcountother);

        // Confirm
        $this->assertCount(1, $DB->get_records('report_builder_saved', ['userid' => $seed->user->id]));
        $usersaved = $DB->get_record('report_builder_saved', ['userid' => $seed->user->id], '*', MUST_EXIST);
        $this->assertEquals(1, $usersaved->ispublic);
        $this->assertEquals($seed->usersearch, $usersaved);

        $this->assertCount(1, $DB->get_records('report_builder_schedule', ['userid' => $seed->otheruser->id]));

        $otheruserschedule = $DB->get_record('report_builder_schedule', ['userid' => $seed->otheruser->id], '*', MUST_EXIST);
        $this->assertEquals($seed->usersearch->id, $otheruserschedule->savedsearchid);
        $this->assertEquals($seed->otherusersched, $otheruserschedule);

        $this->assertFalse($DB->record_exists('report_builder_schedule', ['userid' => $seed->user->id]));

        $this->assertFalse($DB->record_exists('report_builder_schedule_email_audience', ['scheduleid' => $seed->usersched->id]));
        $this->assertTrue($DB->record_exists('report_builder_schedule_email_audience', ['scheduleid' => $seed->otherusersched->id]));

        $this->assertFalse($DB->record_exists('report_builder_schedule_email_external', ['scheduleid' => $seed->usersched->id]));
        $this->assertTrue($DB->record_exists('report_builder_schedule_email_external', ['scheduleid' => $seed->otherusersched->id]));

        $this->assertFalse($DB->record_exists('report_builder_schedule_email_systemuser',
                ['scheduleid' => $seed->usersched->id, 'userid' => $seed->user->id]));
        $this->assertFalse($DB->record_exists('report_builder_schedule_email_systemuser',
                ['scheduleid' => $seed->usersched->id, 'userid' => $seed->otheruser->id]));
        $this->assertFalse($DB->record_exists('report_builder_schedule_email_systemuser',
                ['scheduleid' => $seed->otherusersched->id, 'userid' => $seed->user->id]));
        $this->assertTrue($DB->record_exists('report_builder_schedule_email_systemuser',
            ['scheduleid' => $seed->otherusersched->id, 'userid' => $seed->otheruser->id]));
    }

    /**
     * Test that data are exported correctly
     */
    public function test_export_count() {
        $seed = (object)$this->seed();

        // Run export.
        $targetuser = new target_user($seed->user);
        $export = scheduled_reports::execute_export($targetuser, context_system::instance());
        $count = scheduled_reports::execute_count($targetuser, context_system::instance());
        $this->assertEquals(1, $count);
        $this->assertCount(1, $export->data);
        $data = current($export->data);

        $this->assertEquals('Users', $data['reportname']);
        $this->assertEquals($seed->usersearch->name, $data['searchname']);

        $this->assertCount(1, $data['audiences']);
        $this->assertEquals($seed->cohort->name, current($data['audiences']));

        $this->assertCount(2, $data['users']);
        $this->assertContains(fullname($seed->user), $data['users']);
        $this->assertContains(fullname($seed->otheruser), $data['users']);

        $this->assertCount(1, $data['external']);
        $this->assertContains('@example.com', current($data['external']));
    }
}