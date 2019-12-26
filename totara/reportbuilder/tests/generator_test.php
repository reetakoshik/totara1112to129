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

/**
 * @group totara_reportbuilder
 */
class totara_reportbuilder_generator_testcase extends advanced_testcase {
    public function test_create_global_restriction() {
        global $DB;
        $this->resetAfterTest();

        /** @var totara_reportbuilder_generator $reportgenerator */
        $reportgenerator = $this->getDataGenerator()->get_plugin_generator('totara_reportbuilder');

        $record = new stdClass();
        $record->name = 'Some restriction name';
        $record->description = 'Some restriction description';
        $record->active = '1';
        $record->allrecords = '0';
        $record->allusers = '0';
        $record->sortorder = '0';
        $record->timecreated = time();
        $record->timemodified = $record->timecreated;

        $id = $DB->insert_record('report_builder_global_restriction', $record);
        $restriction1 = $DB->get_record('report_builder_global_restriction', array('id' => $id), '*', MUST_EXIST);

        $this->assertObjectHasAttribute('name', $restriction1);
        $this->assertObjectHasAttribute('description', $restriction1);
        $this->assertObjectHasAttribute('active', $restriction1);
        $this->assertObjectHasAttribute('allrecords', $restriction1);
        $this->assertObjectHasAttribute('allusers', $restriction1);
        $this->assertObjectHasAttribute('sortorder', $restriction1);
        $this->assertObjectHasAttribute('timecreated', $restriction1);
        $this->assertObjectHasAttribute('timemodified', $restriction1);

        unset($record->sortorder);
        $this->setCurrentTimeStart();
        $restriction2 = $reportgenerator->create_global_restriction($record);
        $this->assertInstanceOf('rb_global_restriction', $restriction2);
        $this->assertEquals($id + 1, $restriction2->id);
        $this->assertSame($record->name, $restriction2->name);
        $this->assertSame($record->description, $restriction2->description);
        $this->assertSame($record->active, $restriction2->active);
        $this->assertSame($record->allrecords, $restriction2->allrecords);
        $this->assertSame($record->allusers, $restriction2->allusers);
        $this->assertEquals($restriction1->sortorder + 1, $restriction2->sortorder);
        $this->assertTimeCurrent($restriction2->timecreated);
        $this->assertTimeCurrent($restriction2->timemodified);

        unset($record->name);
        $restriction3 = $reportgenerator->create_global_restriction($record);
        $this->assertInstanceOf('rb_global_restriction', $restriction2);
        $this->assertEquals($id + 2, $restriction3->id);
        $this->assertSame('Global report restriction 2', $restriction3->name);
        $this->assertEquals($restriction2->sortorder + 1, $restriction3->sortorder);
    }

    public function test_assign_global_restriction_record() {
        global $DB;

        $this->resetAfterTest();

        /** @var totara_reportbuilder_generator $reportgenerator */
        $reportgenerator = $this->getDataGenerator()->get_plugin_generator('totara_reportbuilder');
        /** @var totara_hierarchy_generator $hierarchygenerator */
        $hierarchygenerator = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');

        $cohort = $this->getDataGenerator()->create_cohort();
        $posframework = $hierarchygenerator->create_framework('position');
        $pos = $hierarchygenerator->create_pos(array('frameworkid' => $posframework->id));
        $orgframework = $hierarchygenerator->create_framework('organisation');
        $org = $hierarchygenerator->create_pos(array('frameworkid' => $orgframework->id));
        $user = $this->getDataGenerator()->create_cohort();

        $restriction = $reportgenerator->create_global_restriction();

        // Test cohort.

        $item = new stdClass();
        $item->restrictionid = $restriction->id;
        $item->prefix = 'cohort';
        $item->itemid = $cohort->id;

        $record = $reportgenerator->assign_global_restriction_record($item);
        $this->assertTrue($DB->record_exists('reportbuilder_grp_cohort_record', array('id' => $record->id)));

        // Test position.

        $item = new stdClass();
        $item->restrictionid = $restriction->id;
        $item->prefix = 'pos';
        $item->itemid = $pos->id;

        $record = $reportgenerator->assign_global_restriction_record($item);
        $this->assertTrue($DB->record_exists('reportbuilder_grp_pos_record', array('id' => $record->id)));

        // Test organisation.

        $item = new stdClass();
        $item->restrictionid = $restriction->id;
        $item->prefix = 'org';
        $item->itemid = $org->id;

        $record = $reportgenerator->assign_global_restriction_record($item);
        $this->assertTrue($DB->record_exists('reportbuilder_grp_org_record', array('id' => $record->id)));

        // Test user.

        $item = new stdClass();
        $item->restrictionid = $restriction->id;
        $item->prefix = 'user';
        $item->itemid = $user->id;

        $record = $reportgenerator->assign_global_restriction_record($item);
        $this->assertTrue($DB->record_exists('reportbuilder_grp_user_record', array('id' => $record->id)));
    }

    public function test_assign_global_restriction_user() {
        global $DB;

        $this->resetAfterTest();

        /** @var totara_reportbuilder_generator $reportgenerator */
        $reportgenerator = $this->getDataGenerator()->get_plugin_generator('totara_reportbuilder');
        /** @var totara_hierarchy_generator $hierarchygenerator */
        $hierarchygenerator = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');

        $cohort = $this->getDataGenerator()->create_cohort();
        $posframework = $hierarchygenerator->create_framework('position');
        $pos = $hierarchygenerator->create_pos(array('frameworkid' => $posframework->id));
        $orgframework = $hierarchygenerator->create_framework('organisation');
        $org = $hierarchygenerator->create_pos(array('frameworkid' => $orgframework->id));
        $user = $this->getDataGenerator()->create_cohort();

        $restriction = $reportgenerator->create_global_restriction();

        // Test cohort.

        $item = new stdClass();
        $item->restrictionid = $restriction->id;
        $item->prefix = 'cohort';
        $item->itemid = $cohort->id;

        $record = $reportgenerator->assign_global_restriction_user($item);
        $this->assertTrue($DB->record_exists('reportbuilder_grp_cohort_user', array('id' => $record->id)));

        // Test position.

        $item = new stdClass();
        $item->restrictionid = $restriction->id;
        $item->prefix = 'pos';
        $item->itemid = $pos->id;

        $record = $reportgenerator->assign_global_restriction_user($item);
        $this->assertTrue($DB->record_exists('reportbuilder_grp_pos_user', array('id' => $record->id)));

        // Test organisation.

        $item = new stdClass();
        $item->restrictionid = $restriction->id;
        $item->prefix = 'org';
        $item->itemid = $org->id;

        $record = $reportgenerator->assign_global_restriction_user($item);
        $this->assertTrue($DB->record_exists('reportbuilder_grp_org_user', array('id' => $record->id)));

        // Test user.

        $item = new stdClass();
        $item->restrictionid = $restriction->id;
        $item->prefix = 'user';
        $item->itemid = $user->id;

        $record = $reportgenerator->assign_global_restriction_user($item);
        $this->assertTrue($DB->record_exists('reportbuilder_grp_user_user', array('id' => $record->id)));
    }

    /**
     * Test Saved Search generator
     */
    public function test_create_saved_search() {
        global $DB;

        $this->resetAfterTest();

        $generator = $this->getDataGenerator();

        /** @var totara_reportbuilder_generator $reportgenerator */
        $reportgenerator = $this->getDataGenerator()->get_plugin_generator('totara_reportbuilder');

        $user = $generator->create_user();
        $report = (object)['fullname' => 'Users', 'shortname' => 'user', 'source' => 'user', 'hidden'=>1, 'embedded' => 1];
        $report->id = $DB->insert_record('report_builder', $report);

        // Test default
        $defaultsearch = $reportgenerator->create_saved_search($report, $user);
        $record = $DB->get_record('report_builder_saved', ['id' => $defaultsearch->id]);
        $this->assertEquals($record, $defaultsearch);
        $this->assertGreaterThan(0, $record->id);
        $this->assertEquals($report->id, $record->reportid);
        $this->assertEquals($user->id, $record->userid);
        $this->assertRegExp("/Saved \d/", $record->name);

        $filterfvalues = unserialize($record->search);
        $this->assertNotEmpty($filterfvalues);

        $this->assertEquals(0, $record->ispublic);
        $this->assertLessThanOrEqual(time(), $record->timemodified);

        // Csutom search item.
        $time = time() - 100;
        $customitem = [
            'name' => 'Custom name',
            'search' => ['user-email' => ['operator' => 1, 'value' => 'example']],
            'ispublic' => 1,
            'timemodified' => $time
        ];
        $customsearch = $reportgenerator->create_saved_search($report, $user, $customitem);
        $record = $DB->get_record('report_builder_saved', ['id' => $customsearch->id]);

        $this->assertEquals($record, $customsearch);
        $this->assertGreaterThan(0, $record->id);
        $this->assertEquals($report->id, $record->reportid);
        $this->assertEquals($user->id, $record->userid);
        $this->assertEquals("Custom name", $record->name);

        $filterfvalues = unserialize($record->search);
        $this->assertEquals($customitem['search'], $filterfvalues);

        $this->assertEquals(1, $record->ispublic);
        $this->assertEquals($time, $record->timemodified);
    }

    /**
     * Test Scheduled Report generator
     */
    public function test_create_scheduled_report() {
        global $CFG, $DB;

        require_once($CFG->dirroot . '/totara/reportbuilder/lib.php');

        $this->resetAfterTest();

        $generator = $this->getDataGenerator();

        /** @var totara_reportbuilder_generator $reportgenerator */
        $reportgenerator = $this->getDataGenerator()->get_plugin_generator('totara_reportbuilder');

        $user = $generator->create_user();
        $report = (object)['fullname' => 'Users', 'shortname' => 'user', 'source' => 'user', 'hidden'=>1, 'embedded' => 1];
        $report->id = $DB->insert_record('report_builder', $report);

        // Default Scheduled reports.
        $default = $reportgenerator->create_scheduled_report($report, $user);
        $record = $DB->get_record('report_builder_schedule', ['id' => $default->id]);

        $this->assertEquals($record, $default);
        $this->assertGreaterThan(0, $record->id);
        $this->assertEquals($report->id, $record->reportid);
        $this->assertEquals(0, $record->savedsearchid);
        $this->assertEquals('csv', $record->format);
        $this->assertEquals(1, $record->frequency);
        $this->assertEquals(0, $record->schedule);
        $this->assertEquals(REPORT_BUILDER_EXPORT_EMAIL, $record->exporttofilesystem);
        $this->assertEquals(0, $record->nextreport);
        $this->assertEquals($user->id, $record->userid);
        $this->assertEquals($record->userid, $record->usermodified);
        $this->assertLessThanOrEqual(time(), $record->lastmodified);

        // Custom scheduled reports.
        $search = $reportgenerator->create_saved_search($report, $user);
        $otheruser = $generator->create_user();
        $time = time() - 100;
        $scheduleitem = [
            'savedsearch' => $search,
            'usermodified' => $otheruser,
            'format' => 'xls',
            'frequency' => 42,
            'schedule' => 10,
            'exporttofilesystem' => REPORT_BUILDER_EXPORT_EMAIL_AND_SAVE,
            'nextreport' => 202,
            'lastmodified' => $time
        ];
        $custom = $reportgenerator->create_scheduled_report($report, $user, $scheduleitem);
        $record = $DB->get_record('report_builder_schedule', ['id' => $custom->id]);

        $this->assertEquals($record, $custom);
        $this->assertGreaterThan(0, $record->id);
        $this->assertEquals($report->id, $record->reportid);
        $this->assertEquals($search->id, $record->savedsearchid);
        $this->assertEquals('xls', $record->format);
        $this->assertEquals(42, $record->frequency);
        $this->assertEquals(10, $record->schedule);
        $this->assertEquals(REPORT_BUILDER_EXPORT_EMAIL_AND_SAVE, $record->exporttofilesystem);
        $this->assertEquals(202, $record->nextreport);
        $this->assertEquals($user->id, $record->userid);
        $this->assertEquals($otheruser->id, $record->usermodified);
        $this->assertEquals($time, $record->lastmodified);
    }

    /**
     * Test Adding Audience to scheduled report generator
     */
    public function test_add_scheduled_audience() {
        global $CFG, $DB;

        require_once($CFG->dirroot . '/totara/reportbuilder/lib.php');

        $this->resetAfterTest();

        $generator = $this->getDataGenerator();

        /** @var totara_reportbuilder_generator $reportgenerator */
        $reportgenerator = $this->getDataGenerator()->get_plugin_generator('totara_reportbuilder');

        $report = (object)['fullname' => 'Users', 'shortname' => 'user', 'source' => 'user', 'hidden'=>1, 'embedded' => 1];
        $report->id = $DB->insert_record('report_builder', $report);
        $user = $generator->create_user();
        $scheduled = $reportgenerator->create_scheduled_report($report, $user);
        $cohort = $generator->create_cohort(['name' => 'The cohort']);

        $added = $reportgenerator->add_scheduled_audience($scheduled, $cohort);
        $record = $DB->get_record('report_builder_schedule_email_audience', ['id' => $added->id]);

        $this->assertEquals($record, $added);
        $this->assertGreaterThan(0, $record->id);
        $this->assertEquals($scheduled->id, $record->scheduleid);
        $this->assertEquals($cohort->id, $record->cohortid);
    }

    /**
     * Test Adding External User's Email to scheduled report generator
     */
    public function test_add_scheduled_email() {
        global $CFG, $DB;

        require_once($CFG->dirroot . '/totara/reportbuilder/lib.php');

        $this->resetAfterTest();

        $generator = $this->getDataGenerator();

        /** @var totara_reportbuilder_generator $reportgenerator */
        $reportgenerator = $this->getDataGenerator()->get_plugin_generator('totara_reportbuilder');

        $report = (object)['fullname' => 'Users', 'shortname' => 'user', 'source' => 'user', 'hidden'=>1, 'embedded' => 1];
        $report->id = $DB->insert_record('report_builder', $report);
        $user = $generator->create_user();
        $scheduled = $reportgenerator->create_scheduled_report($report, $user);

        // Default.
        $added = $reportgenerator->add_scheduled_email($scheduled);
        $record = $DB->get_record('report_builder_schedule_email_external', ['id' => $added->id]);

        $this->assertEquals($record, $added);
        $this->assertGreaterThan(0, $record->id);
        $this->assertEquals($scheduled->id, $record->scheduleid);
        $this->assertRegExp('/.*\@example\.com/', $record->email);

        // Custom.
        $addedcustom = $reportgenerator->add_scheduled_email($scheduled, 'test@example.org');
        $record = $DB->get_record('report_builder_schedule_email_external', ['id' => $addedcustom->id]);

        $this->assertEquals($record, $addedcustom);
        $this->assertGreaterThan(0, $record->id);
        $this->assertEquals($scheduled->id, $record->scheduleid);
        $this->assertEquals('test@example.org', $record->email);
    }

    /**
     * Test Adding System User to scheduled report generator
     */
    public function test_add_scheduled_user() {
        global $CFG, $DB;

        require_once($CFG->dirroot . '/totara/reportbuilder/lib.php');

        $this->resetAfterTest();

        $generator = $this->getDataGenerator();

        /** @var totara_reportbuilder_generator $reportgenerator */
        $reportgenerator = $this->getDataGenerator()->get_plugin_generator('totara_reportbuilder');

        $report = (object)['fullname' => 'Users', 'shortname' => 'user', 'source' => 'user', 'hidden'=>1, 'embedded' => 1];
        $report->id = $DB->insert_record('report_builder', $report);
        $user = $generator->create_user();
        $scheduled = $reportgenerator->create_scheduled_report($report, $user);

        $otheruser = $generator->create_user();
        $added = $reportgenerator->add_scheduled_user($scheduled, $otheruser);
        $record = $DB->get_record('report_builder_schedule_email_systemuser', ['id' => $added->id]);

        $this->assertEquals($record, $added);
        $this->assertGreaterThan(0, $record->id);
        $this->assertEquals($scheduled->id, $record->scheduleid);
        $this->assertEquals($otheruser->id, $record->userid);

    }
}
