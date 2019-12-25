<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2017 onwards Totara Learning Solutions LTD
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
 * @author Brendan Cox <brendan.cox@totaralearning.com>
 * @package tool_totara_sync
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/admin/tool/totara_sync/elements/jobassignment.php');
require_once($CFG->dirroot . '/admin/tool/totara_sync/sources/classes/source.jobassignment.class.php');

/**
 * @group tool_totara_sync
 */
class tool_totara_sync_elements_jobassignment_testcase extends advanced_testcase {

    /**
     * Creates the source table with all possible fields added. However only those that are
     * set to be imported in the actual unit test will be used. e.g. with:
     * $source->set_config('import_fullname', '1');
     *
     * The $source object returned has only the minimal fields set to be imported,
     * which are idnumber, useridnumber and timemodified.
     *
     * To add data to the table, created a stdClass object and insert into $source->temptablename.
     *
     * @return \PHPUnit\Framework\MockObject\MockObject|totara_sync_source_jobassignment
     */
    private function prepare_source_table() {
        // Build the table with all columns. Only those set to be imported during tests will actually be used.

        /** @var totara_sync_source_jobassignment|\PHPUnit\Framework\MockObject\MockObject $source_tablebuilder */
        $source_tablebuilder = $this->getMockBuilder('totara_sync_source_jobassignment')
            ->setMethods(array('import_data', 'is_importing_field'))
            ->getMock();
        $source_tablebuilder->method('is_importing_field')->willReturn(true);
        $source_tablebuilder->temptablename = 'test_totara_sync';
        $source_tablebuilder->prepare_temp_table();

        /** @var totara_sync_source_jobassignment|\PHPUnit\Framework\MockObject\MockObject $source */
        $source = $this->getMockBuilder('totara_sync_source_jobassignment')
            ->setMethods(array('import_data'))
            ->enableOriginalConstructor()
            ->getMock();
        $source->temptablename = 'test_totara_sync';
        return $source;
    }

    /**
     * Gets a totara_sync_element_jobassignment, but with methods replaced that would
     * send HR Import looking for the php file for our generic totara_sync_source_jobassignment
     * created with prepare_source_table.
     *
     * @param totara_sync_source_jobassignment|\PHPUnit\Framework\MockObject\MockObject $source
     * @return totara_sync_element_jobassignment|\PHPUnit\Framework\MockObject\MockObject
     */
    private function get_element_mock($source) {
        $element = $this->getMockBuilder('totara_sync_element_jobassignment')
                        ->setMethods(array('get_source_sync_table', 'get_source'))
                        ->getMock();
        $element->method('get_source_sync_table')->willReturn($source->temptablename);
        $element->method('get_source')->willReturn($source);

        return $element;
    }

    /**
     * Creates a small number of users + a job assignment that is set to not be updated
     * via HR Import (totarasync = 0).
     *
     * @return stdClass[]
     */
    private function create_test_users() {
        $users = array();
        $users['user1'] = $this->getDataGenerator()->create_user(['idnumber' => 'user1']);
        $users['user2'] = $this->getDataGenerator()->create_user(['idnumber' => 'user2']);
        // This job assignment has totarasync=0 so should never be affected by
        // and HR Import operations.
        \totara_job\job_assignment::create(
            array('userid' => $users['user2']->id, 'totarasync' => 0, 'idnumber' => 'nonsync'));

        return $users;
    }

    public function test_disallow_create_fullname() {
        global $DB;
        $this->resetAfterTest(true);
        $source = $this->prepare_source_table();
        $users = $this->create_test_users();

        $entry = new stdClass();
        $entry->idnumber = 'dev1';
        $entry->useridnumber = 'user1';
        $entry->fullname = 'Developer';
        $entry->timemodified = 0;
        $entry->deleted = 0;
        $entry->startdate = 10000;
        $DB->insert_record($source->temptablename, $entry);

        // First of all, we just want to test the very simple here. We're only importing the fullname
        // (plus idnumber since we have to).
        $source->set_config('import_fullname', '1');

        $element = $this->get_element_mock($source);
        $element->set_config('allow_create', '0');
        // Returns true even if there are errors.
        $this->assertTrue($element->sync());

        $jobassignments = \totara_job\job_assignment::get_all($users['user1']->id);
        $this->assertCount(0, $jobassignments);

        // Nothing is logged for actions that were disallowed.
        $this->assertEquals(2, $DB->count_records('totara_sync_log'));
        $this->assertEquals(1, $DB->count_records('totara_sync_log', array(
            'element' => 'jobassignment',
            'logtype' => 'info',
            'action' => 'sync',
            'info' => 'HR Import started')));
        $this->assertEquals(1, $DB->count_records('totara_sync_log', array(
            'element' => 'jobassignment',
            'logtype' => 'info',
            'action' => 'sync',
            'info' => 'HR Import finished')));
    }

    public function test_create_fullname() {
        global $DB;
        $this->resetAfterTest(true);
        $source = $this->prepare_source_table();
        $users = $this->create_test_users();

        $entry = new stdClass();
        $entry->idnumber = 'dev1';
        $entry->useridnumber = 'user1';
        $entry->fullname = 'Developer';
        $entry->timemodified = 0;
        $entry->deleted = 0;
        $entry->startdate = 10000;
        $DB->insert_record($source->temptablename, $entry);

        $source->set_config('import_fullname', '1');

        $element = $this->get_element_mock($source);
        $element->set_config('allow_create', '1');
        $this->assertTrue($element->sync());

        $jobassignments = \totara_job\job_assignment::get_all($users['user1']->id);
        $this->assertCount(1, $jobassignments);
        $this->assertEquals('dev1', $jobassignments[1]->idnumber);
        $this->assertEquals('Developer', $jobassignments[1]->fullname);
        $this->assertEquals(1, $jobassignments[1]->totarasync);
        // There was data for startdate but we didn't want it. It should have been ignored.
        $this->assertEquals(0, $jobassignments[1]->startdate);

        $this->assertEquals(1, $DB->count_records('totara_sync_log', array(
            'element' => 'jobassignment',
            'logtype' => 'info',
            'action' => 'create',
            'info' => 'Created job assignment \'dev1\' for user \'user1\'.')));
    }

    public function test_disallow_update_fullname() {
        global $DB;
        $this->resetAfterTest(true);
        $source = $this->prepare_source_table();
        $users = $this->create_test_users();

        \totara_job\job_assignment::create(
            array('userid' => $users['user1']->id, 'totarasync' => 1, 'idnumber' => 'dev1', 'fullname' => 'Developer'));

        $entry = new stdClass();
        $entry->idnumber = 'dev1';
        $entry->useridnumber = 'user1';
        $entry->fullname = 'Programmer';
        $entry->timemodified = 0;
        $entry->deleted = 0;
        $entry->startdate = 10000;
        $DB->insert_record($source->temptablename, $entry);

        $source->set_config('import_fullname', '1');

        $element = $this->get_element_mock($source);
        $element->set_config('allow_create', '1');
        $element->set_config('allow_update', '0');
        $element->sync();

        $jobassignments = \totara_job\job_assignment::get_all($users['user1']->id);
        $this->assertCount(1, $jobassignments);
        $this->assertEquals('dev1', $jobassignments[1]->idnumber);
        $this->assertEquals('Developer', $jobassignments[1]->fullname);
    }

    public function test_update_fullname_no_totarasync() {
        global $DB;
        $this->resetAfterTest(true);
        $source = $this->prepare_source_table();
        $users = $this->create_test_users();

        $jobassignment = \totara_job\job_assignment::create(
            array('userid' => $users['user1']->id, 'totarasync' => 0, 'idnumber' => 'dev1', 'fullname' => 'Developer'));

        $entry = new stdClass();
        $entry->idnumber = 'dev1';
        $entry->useridnumber = 'user1';
        $entry->fullname = 'Programmer';
        $entry->timemodified = 0;
        $entry->deleted = 0;
        $entry->startdate = 10000;
        $DB->insert_record($source->temptablename, $entry);

        $source->set_config('import_fullname', '1');

        $element = $this->get_element_mock($source);
        $element->set_config('allow_update', '1');
        $element->sync();

        $jobassignments = \totara_job\job_assignment::get_all($users['user1']->id);
        $this->assertCount(1, $jobassignments);
        $this->assertEquals('dev1', $jobassignments[1]->idnumber);
        $this->assertEquals('Developer', $jobassignments[1]->fullname);

        \totara_job\job_assignment::delete($jobassignment);
    }

    public function test_update_fullname() {
        global $DB;
        $this->resetAfterTest(true);
        $source = $this->prepare_source_table();
        $users = $this->create_test_users();

        \totara_job\job_assignment::create(
            array('userid' => $users['user1']->id, 'totarasync' => 1, 'idnumber' => 'dev1', 'fullname' => 'Developer'));

        $entry = new stdClass();
        $entry->idnumber = 'dev1';
        $entry->useridnumber = 'user1';
        $entry->fullname = 'Programmer';
        $entry->timemodified = 0;
        $entry->deleted = 0;
        $entry->startdate = 10000;
        $DB->insert_record($source->temptablename, $entry);

        $source->set_config('import_fullname', '1');

        $element = $this->get_element_mock($source);
        $element->set_config('allow_update', '1');
        $element->sync();

        $jobassignments = \totara_job\job_assignment::get_all($users['user1']->id);
        $this->assertCount(1, $jobassignments);
        $this->assertEquals('dev1', $jobassignments[1]->idnumber);
        $this->assertEquals('Programmer', $jobassignments[1]->fullname);
        $this->assertEquals(1, $jobassignments[1]->totarasync);
        // There was data for startdate but we didn't want it. It should have been ignored.
        $this->assertEquals(0, $jobassignments[1]->startdate);

        $this->assertEquals(1, $DB->count_records('totara_sync_log', array(
            'element' => 'jobassignment',
            'logtype' => 'info',
            'action' => 'update',
            'info' => 'Updated job assignment \'dev1\' for user \'user1\'.')));
    }

    public function test_containsall_disallow_delete() {
        global $DB;
        $this->resetAfterTest(true);
        $source = $this->prepare_source_table();
        $users = $this->create_test_users();

        \totara_job\job_assignment::create(
            array('userid' => $users['user1']->id, 'totarasync' => 1, 'idnumber' => 'dev1'));
        \totara_job\job_assignment::create(
            array('userid' => $users['user2']->id, 'totarasync' => 1, 'idnumber' => 'user2job'));

        $entry = new stdClass();
        $entry->idnumber = 'dev1';
        $entry->useridnumber = 'user1';
        $entry->fullname = 'Programmer';
        $entry->timemodified = 0;
        $entry->deleted = 0;
        $entry->startdate = 10000;
        $DB->insert_record($source->temptablename, $entry);

        $element = $this->get_element_mock($source);
        $element->set_config('sourceallrecords', '1');
        $element->set_config('allow_delete', '0');
        $element->sync();

        $jobassignments = \totara_job\job_assignment::get_all($users['user1']->id);
        $this->assertCount(1, $jobassignments);
        $this->assertEquals('dev1', $jobassignments[1]->idnumber);
        $jobassignments = \totara_job\job_assignment::get_all($users['user2']->id);
        $this->assertCount(2, $jobassignments);
        $this->assertEquals('nonsync', $jobassignments[1]->idnumber);
        $this->assertEquals('user2job', $jobassignments[2]->idnumber);
    }

    public function test_notcontainsall_disallow_delete() {
        global $DB;
        $this->resetAfterTest(true);
        $source = $this->prepare_source_table();
        $users = $this->create_test_users();

        \totara_job\job_assignment::create(
            array('userid' => $users['user1']->id, 'totarasync' => 1, 'idnumber' => 'dev1'));
        \totara_job\job_assignment::create(
            array('userid' => $users['user2']->id, 'totarasync' => 1, 'idnumber' => 'user2job'));

        $entry = new stdClass();
        $entry->idnumber = 'dev1';
        $entry->useridnumber = 'user1';
        $entry->fullname = 'Programmer';
        $entry->timemodified = 0;
        $entry->deleted = 0;
        $entry->startdate = 10000;
        $DB->insert_record($source->temptablename, $entry);

        // Adding a record with deleted = 1. It should ignore that column anyway.
        $deleteentry = new stdClass();
        $deleteentry->idnumber = 'user2job';
        $deleteentry->useridnumber = 'user2';
        $deleteentry->fullname = '';
        $deleteentry->timemodified = 0;
        $deleteentry->deleted = 1;
        $DB->insert_record($source->temptablename, $deleteentry);

        $source->set_config('import_deleted', '1');

        $element = $this->get_element_mock($source);
        $element->set_config('sourceallrecords', '0');
        $element->set_config('allow_delete', '0');
        $element->sync();

        $jobassignments = \totara_job\job_assignment::get_all($users['user1']->id);
        $this->assertCount(1, $jobassignments);
        $this->assertEquals('dev1', $jobassignments[1]->idnumber);
        $jobassignments = \totara_job\job_assignment::get_all($users['user2']->id);
        $this->assertCount(2, $jobassignments);
        $this->assertEquals('nonsync', $jobassignments[1]->idnumber);
        $this->assertEquals('user2job', $jobassignments[2]->idnumber);
    }

    public function test_notcontainsall_delete() {
        global $DB;
        $this->resetAfterTest(true);
        $source = $this->prepare_source_table();
        $users = $this->create_test_users();

        $keepjob = \totara_job\job_assignment::create(
            array('userid' => $users['user1']->id, 'totarasync' => 1, 'idnumber' => 'dev1'));
        \totara_job\job_assignment::create(
            array('userid' => $users['user2']->id, 'totarasync' => 1, 'idnumber' => 'user2job'));

        $entry = new stdClass();
        $entry->idnumber = 'dev1';
        $entry->useridnumber = 'user1';
        $entry->fullname = 'Programmer';
        $entry->timemodified = 0;
        $entry->deleted = 0;
        $entry->startdate = 10000;
        $DB->insert_record($source->temptablename, $entry);

        // Adding a record with deleted = 1. It should ignore that column anyway.
        $deleteentry = new stdClass();
        $deleteentry->idnumber = 'user2job';
        $deleteentry->useridnumber = 'user2';
        $deleteentry->fullname = '';
        $deleteentry->timemodified = 0;
        $deleteentry->deleted = 1;
        $DB->insert_record($source->temptablename, $deleteentry);

        $source->set_config('import_deleted', '1');

        $element = $this->get_element_mock($source);
        $element->set_config('sourceallrecords', '0');
        $element->set_config('allow_delete', '1');
        $element->sync();

        $jobassignments = \totara_job\job_assignment::get_all($users['user1']->id);
        $this->assertCount(1, $jobassignments);
        $this->assertEquals('dev1', $jobassignments[1]->idnumber);
        // The id should be the same (not really a necessary check in this test, but see next one).
        $this->assertEquals($keepjob->id, $jobassignments[1]->id);

        $jobassignments = \totara_job\job_assignment::get_all($users['user2']->id);
        $this->assertCount(1, $jobassignments);
        $this->assertEquals('nonsync', $jobassignments[1]->idnumber);
    }

    public function test_containsall_delete() {
        global $DB;
        $this->resetAfterTest(true);
        $source = $this->prepare_source_table();
        $users = $this->create_test_users();

        $keepjob = \totara_job\job_assignment::create(
            array('userid' => $users['user1']->id, 'totarasync' => 1, 'idnumber' => 'dev1'));
        \totara_job\job_assignment::create(
            array('userid' => $users['user2']->id, 'totarasync' => 1, 'idnumber' => 'user2job'));

        $entry = new stdClass();
        $entry->idnumber = 'dev1';
        $entry->useridnumber = 'user1';
        $entry->fullname = 'Programmer';
        $entry->timemodified = 0;
        $entry->deleted = 0;
        $entry->startdate = 10000;
        $DB->insert_record($source->temptablename, $entry);

        $element = $this->get_element_mock($source);
        $element->set_config('sourceallrecords', '1');
        $element->set_config('allow_delete', '1');
        $element->sync();

        $jobassignments = \totara_job\job_assignment::get_all($users['user1']->id);
        $this->assertCount(1, $jobassignments);
        $this->assertEquals('dev1', $jobassignments[1]->idnumber);
        // This is important as we need to make sure the kept job assignment hasn't actually been
        // deleted and recreated.
        $this->assertEquals($keepjob->id, $jobassignments[1]->id);

        $jobassignments = \totara_job\job_assignment::get_all($users['user2']->id);
        $this->assertCount(1, $jobassignments);
        $this->assertEquals('nonsync', $jobassignments[1]->idnumber);
    }

    public function test_startdate_valid() {
        global $DB;
        $this->resetAfterTest(true);
        $source = $this->prepare_source_table();
        $users = $this->create_test_users();

        \totara_job\job_assignment::create(
            ['userid' => $users['user1']->id, 'idnumber' => 'dev1', 'startdate' => 10000, 'totarasync' => 1]);

        $entry = new stdClass();
        $entry->idnumber = 'dev1';
        $entry->useridnumber = 'user1';
        $entry->timemodified = 0;
        $entry->startdate = 20000;
        $DB->insert_record($source->temptablename, $entry);

        $source->set_config('import_startdate', '1');

        $element = $this->get_element_mock($source);
        $element->set_config('allow_update', '1');
        $element->sync();

        $jobassignments = \totara_job\job_assignment::get_all($users['user1']->id);
        $this->assertCount(1, $jobassignments);
        $this->assertEquals('dev1', $jobassignments[1]->idnumber);
        $this->assertEquals(20000, $jobassignments[1]->startdate);
    }

    public function test_startdate_enddate_valid() {
        global $DB;
        $this->resetAfterTest(true);
        $source = $this->prepare_source_table();
        $users = $this->create_test_users();

        \totara_job\job_assignment::create(
            ['userid' => $users['user1']->id, 'idnumber' => 'dev1', 'startdate' => 10000, 'totarasync' => 1]);

        $entry = new stdClass();
        $entry->idnumber = 'dev1';
        $entry->useridnumber = 'user1';
        $entry->timemodified = 0;
        $entry->startdate = 5000;
        $entry->enddate = 8000;
        $DB->insert_record($source->temptablename, $entry);

        $source->set_config('import_startdate', '1');
        $source->set_config('import_enddate', '1');

        $element = $this->get_element_mock($source);
        $element->set_config('allow_update', '1');
        $element->sync();

        $jobassignments = \totara_job\job_assignment::get_all($users['user1']->id);
        $this->assertCount(1, $jobassignments);
        $this->assertEquals('dev1', $jobassignments[1]->idnumber);
        $this->assertEquals(5000, $jobassignments[1]->startdate);
        $this->assertEquals(8000, $jobassignments[1]->enddate);
    }

    public function test_startdate_after_enddate() {
        global $DB;
        $this->resetAfterTest(true);
        $source = $this->prepare_source_table();
        $users = $this->create_test_users();

        \totara_job\job_assignment::create(
            ['userid' => $users['user1']->id, 'idnumber' => 'dev1', 'startdate' => 10000, 'totarasync' => 1]);

        $entry = new stdClass();
        $entry->idnumber = 'dev1';
        $entry->useridnumber = 'user1';
        $entry->timemodified = 0;
        $entry->startdate = 20000;
        $entry->enddate = 15000;
        $DB->insert_record($source->temptablename, $entry);

        $source->set_config('import_startdate', '1');
        $source->set_config('import_enddate', '1');

        $element = $this->get_element_mock($source);
        $element->set_config('allow_update', '1');
        $element->sync();

        $jobassignments = \totara_job\job_assignment::get_all($users['user1']->id);
        $this->assertCount(1, $jobassignments);
        $this->assertEquals('dev1', $jobassignments[1]->idnumber);
        $this->assertEquals(10000, $jobassignments[1]->startdate);
        $this->assertEquals(0, $jobassignments[1]->enddate);
        $this->assertEquals(1, $DB->count_records('totara_sync_log', array(
            'element' => 'jobassignment',
            'logtype' => 'error',
            'action' => 'create/update',
            'info' => 'Start date cannot be later than end date. Skipped job assignment \'dev1\' for user \'user1\'.')));
    }

    public function test_enddate_only() {
        global $DB;
        $this->resetAfterTest(true);
        $source = $this->prepare_source_table();
        $users = $this->create_test_users();

        \totara_job\job_assignment::create(
            ['userid' => $users['user1']->id, 'idnumber' => 'dev1', 'enddate' => 10000, 'totarasync' => 1]);

        $entry = new stdClass();
        $entry->idnumber = 'dev1';
        $entry->useridnumber = 'user1';
        $entry->timemodified = 0;
        $entry->enddate = 15000;
        $DB->insert_record($source->temptablename, $entry);

        $source->set_config('import_enddate', '1');

        $element = $this->get_element_mock($source);
        $element->set_config('allow_update', '1');
        $element->sync();

        $jobassignments = \totara_job\job_assignment::get_all($users['user1']->id);
        $this->assertCount(1, $jobassignments);
        $this->assertEquals('dev1', $jobassignments[1]->idnumber);
        $this->assertEquals(0, $jobassignments[1]->startdate);
        $this->assertEquals(15000, $jobassignments[1]->enddate);
    }

    public function test_orgidnumber_exists() {
        global $DB;
        $this->resetAfterTest(true);
        $source = $this->prepare_source_table();
        $users = $this->create_test_users();

        /** @var totara_hierarchy_generator $hierarchy_generator */
        $hierarchy_generator = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');
        $orgframework = $hierarchy_generator->create_org_frame(array());
        $org1 = $hierarchy_generator->create_org(array('frameworkid' => $orgframework->id, 'idnumber' => 'org1'));
        $org2 = $hierarchy_generator->create_org(array('frameworkid' => $orgframework->id, 'idnumber' => 'org2'));
        $org3 = $hierarchy_generator->create_org(array('frameworkid' => $orgframework->id, 'idnumber' => 'org3'));

        \totara_job\job_assignment::create(
            ['userid' => $users['user1']->id, 'idnumber' => 'dev1', 'organisationid' => $org3->id, 'totarasync' => 1]);

        $entry = new stdClass();
        $entry->idnumber = 'dev1';
        $entry->useridnumber = 'user1';
        $entry->timemodified = 0;
        $entry->orgidnumber = 'org2';
        $DB->insert_record($source->temptablename, $entry);

        $source->set_config('import_orgidnumber', '1');

        $element = $this->get_element_mock($source);
        $element->set_config('allow_update', '1');
        $element->sync();

        $jobassignments = \totara_job\job_assignment::get_all($users['user1']->id);
        $this->assertCount(1, $jobassignments);
        $this->assertEquals('dev1', $jobassignments[1]->idnumber);
        $this->assertEquals($org2->id, $jobassignments[1]->organisationid);
    }

    public function test_orgidnumber_nonexisting() {
        global $DB;
        $this->resetAfterTest(true);
        $source = $this->prepare_source_table();
        $users = $this->create_test_users();

        /** @var totara_hierarchy_generator $hierarchy_generator */
        $hierarchy_generator = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');
        $orgframework = $hierarchy_generator->create_org_frame(array());
        $org1 = $hierarchy_generator->create_org(array('frameworkid' => $orgframework->id, 'idnumber' => 'org1'));
        $org2 = $hierarchy_generator->create_org(array('frameworkid' => $orgframework->id, 'idnumber' => 'org2'));
        $org3 = $hierarchy_generator->create_org(array('frameworkid' => $orgframework->id, 'idnumber' => 'org3'));

        \totara_job\job_assignment::create(
            ['userid' => $users['user1']->id, 'idnumber' => 'dev1', 'organisationid' => $org3->id, 'totarasync' => 1]);

        $entry = new stdClass();
        $entry->idnumber = 'dev1';
        $entry->useridnumber = 'user1';
        $entry->timemodified = 0;
        $entry->orgidnumber = 'orgX';
        $DB->insert_record($source->temptablename, $entry);

        $source->set_config('import_orgidnumber', '1');

        $element = $this->get_element_mock($source);
        $element->set_config('allow_update', '1');
        $element->sync();

        $jobassignments = \totara_job\job_assignment::get_all($users['user1']->id);
        $this->assertCount(1, $jobassignments);
        $this->assertEquals('dev1', $jobassignments[1]->idnumber);
        // The organisation id was unchanged.
        $this->assertEquals($org3->id, $jobassignments[1]->organisationid);
        $this->assertEquals(1, $DB->count_records('totara_sync_log', array(
            'element' => 'jobassignment',
            'logtype' => 'error',
            'action' => 'checksanity',
            'info' => 'Organisation \'orgX\' does not exist. Skipped job assignment \'dev1\' for user \'user1\'.')));
    }

    public function test_appraiseridnumber_valid() {
        global $DB;
        $this->resetAfterTest(true);
        $source = $this->prepare_source_table();
        $users = $this->create_test_users();

        $appraiser1 = $this->getDataGenerator()->create_user(array('idnumber' => 'appraiser1'));
        $appraiser2 = $this->getDataGenerator()->create_user(array('idnumber' => 'appraiser2'));
        $appraiser3 = $this->getDataGenerator()->create_user(array('idnumber' => 'appraiser3'));

        \totara_job\job_assignment::create(
            ['userid' => $users['user1']->id, 'idnumber' => 'dev1', 'appraiserid' => $appraiser1->id, 'totarasync' => 1]);

        $entry = new stdClass();
        $entry->idnumber = 'dev1';
        $entry->useridnumber = 'user1';
        $entry->timemodified = 0;
        $entry->appraiseridnumber = 'appraiser2';
        $DB->insert_record($source->temptablename, $entry);

        $source->set_config('import_appraiseridnumber', '1');

        $element = $this->get_element_mock($source);
        $element->set_config('allow_update', '1');
        $element->sync();

        $jobassignments = \totara_job\job_assignment::get_all($users['user1']->id);
        $this->assertCount(1, $jobassignments);
        $this->assertEquals('dev1', $jobassignments[1]->idnumber);
        $this->assertEquals($appraiser2->id, $jobassignments[1]->appraiserid);
    }

    public function test_appraiseridnumber_no_matching() {
        global $DB;
        $this->resetAfterTest(true);
        $source = $this->prepare_source_table();
        $users = $this->create_test_users();

        $appraiser1 = $this->getDataGenerator()->create_user(array('idnumber' => 'appraiser1'));
        $appraiser2 = $this->getDataGenerator()->create_user(array('idnumber' => 'appraiser2'));
        $appraiser3 = $this->getDataGenerator()->create_user(array('idnumber' => 'appraiser3'));

        \totara_job\job_assignment::create(
            ['userid' => $users['user1']->id, 'idnumber' => 'dev1', 'appraiserid' => $appraiser1->id, 'totarasync' => 1]);

        $entry = new stdClass();
        $entry->idnumber = 'dev1';
        $entry->useridnumber = 'user1';
        $entry->timemodified = 0;
        $entry->appraiseridnumber = 'appraiserX';
        $DB->insert_record($source->temptablename, $entry);

        $source->set_config('import_appraiseridnumber', '1');

        $element = $this->get_element_mock($source);
        $element->set_config('allow_update', '1');
        $element->sync();

        $jobassignments = \totara_job\job_assignment::get_all($users['user1']->id);
        $this->assertCount(1, $jobassignments);
        $this->assertEquals('dev1', $jobassignments[1]->idnumber);
        $this->assertEquals($appraiser1->id, $jobassignments[1]->appraiserid);
        $this->assertEquals(1, $DB->count_records('totara_sync_log', array(
            'element' => 'jobassignment',
            'logtype' => 'error',
            'action' => 'checksanity',
            'info' => 'User \'appraiserX\' does not exist and was set to be assigned as appraiser. Skipped job assignment \'dev1\' for user \'user1\'.')));
    }

    public function test_self_assigned_as_appraiser() {
        global $DB;
        $this->resetAfterTest(true);
        $source = $this->prepare_source_table();
        $users = $this->create_test_users();

        $appraiser1 = $this->getDataGenerator()->create_user(array('idnumber' => 'appraiser1'));

        \totara_job\job_assignment::create(
            ['userid' => $users['user1']->id, 'idnumber' => 'dev1', 'appraiserid' => $appraiser1->id, 'totarasync' => 1]);

        $entry = new stdClass();
        $entry->idnumber = 'dev1';
        $entry->useridnumber = 'user1';
        $entry->timemodified = 0;
        $entry->appraiseridnumber = 'user1';
        $DB->insert_record($source->temptablename, $entry);

        $source->set_config('import_appraiseridnumber', '1');

        $element = $this->get_element_mock($source);
        $element->set_config('allow_update', '1');
        $element->sync();

        $jobassignments = \totara_job\job_assignment::get_all($users['user1']->id);
        $this->assertCount(1, $jobassignments);
        $this->assertEquals('dev1', $jobassignments[1]->idnumber);
        $this->assertEquals($appraiser1->id, $jobassignments[1]->appraiserid);
        $this->assertEquals(1, $DB->count_records('totara_sync_log', array(
            'element' => 'jobassignment',
            'logtype' => 'error',
            'action' => 'checksanity',
            'info' => 'User \'user1\' cannot be their own appraiser. Skipped job assignment \'dev1\' for user \'user1\'.')));
    }

    public function test_empty_required_fields() {
        global $DB;
        $this->resetAfterTest(true);
        $source = $this->prepare_source_table();
        $users = $this->create_test_users();

        $goodentry = new stdClass();
        $goodentry->idnumber = 'dev1';
        $goodentry->useridnumber = 'user1';
        $goodentry->fullname = 'Developer';
        $goodentry->timemodified = 0;
        $DB->insert_record($source->temptablename, $goodentry);

        $missingidnumber = new stdClass();
        $missingidnumber->idnumber = '';
        $missingidnumber->useridnumber = 'user2';
        $missingidnumber->fullname = 'Developer';
        $missingidnumber->timemodified = 0;
        $DB->insert_record($source->temptablename, $missingidnumber);

        $missinguseridnumber = new stdClass();
        $missinguseridnumber->idnumber = 'dev2';
        $missinguseridnumber->useridnumber = '';
        $missinguseridnumber->fullname = 'Developer';
        $missinguseridnumber->timemodified = 0;
        $DB->insert_record($source->temptablename, $missinguseridnumber);

        $source->set_config('import_fullname', '1');

        $element = $this->get_element_mock($source);
        $element->set_config('allow_update', '1');
        $element->set_config('allow_create', '1');
        $element->sync();

        $jobassignments = \totara_job\job_assignment::get_all($users['user1']->id);
        $this->assertCount(1, $jobassignments);
        $this->assertEquals('dev1', $jobassignments[1]->idnumber);
        $this->assertEquals('Developer', $jobassignments[1]->fullname);

        // The above should have been the only one created by HR Import.
        $this->assertEquals(1, $DB->count_records('job_assignment', array('totarasync' => 1)));

        // There's no point creating a error log entry for each bad record since we can't provide
        // any info on which they are anyway.
        $this->assertEquals(1, $DB->count_records('totara_sync_log', array(
            'element' => 'jobassignment',
            'logtype' => 'error',
            'action' => 'checksanity',
            'info' => 'Some records are missing their idnumber and/or useridnumber. These records were skipped.')));
    }

    public function test_multiple_entries_for_job_assignment() {
        global $DB;
        $this->resetAfterTest(true);
        $source = $this->prepare_source_table();
        $users = $this->create_test_users();

        $entry1 = new stdClass();
        $entry1->idnumber = 'dev1';
        $entry1->useridnumber = 'user1';
        $entry1->fullname = 'Developer';
        $entry1->timemodified = 0;
        $DB->insert_record($source->temptablename, $entry1);

        $entry2 = new stdClass();
        $entry2->idnumber = 'dev1';
        $entry2->useridnumber = 'user1';
        $entry2->fullname = 'Programmer';
        $entry2->timemodified = 0;
        $DB->insert_record($source->temptablename, $entry2);

        $source->set_config('import_fullname', '1');

        $element = $this->get_element_mock($source);
        $element->set_config('allow_update', '1');
        $element->set_config('allow_create', '1');
        $element->sync();

        $jobassignments = \totara_job\job_assignment::get_all($users['user1']->id);
        $this->assertCount(0, $jobassignments);
        $this->assertEquals(0, $DB->count_records('job_assignment', array('totarasync' => 1)));

        $this->assertEquals(1, $DB->count_records('totara_sync_log', array(
            'element' => 'jobassignment',
            'logtype' => 'error',
            'action' => 'checksanity',
            'info' => 'Multiple entries found for job assignment \'dev1\' for user \'user1\'. No updates made to this job assignment.')));
    }

    public function test_timemodified() {
        global $DB;
        $this->resetAfterTest(true);
        $source = $this->prepare_source_table();
        $users = $this->create_test_users();

        \totara_job\job_assignment::create(
            ['userid' => $users['user1']->id, 'idnumber' => 'dev1', 'fullname' => 'Programmer', 'totarasync' => 1]);
        $zero = new stdClass();
        $zero->idnumber = 'dev1';
        $zero->useridnumber = 'user1';
        $zero->fullname = 'Developer';
        $zero->timemodified = 0;
        $DB->insert_record($source->temptablename, $zero);

        \totara_job\job_assignment::create(
            ['userid' => $users['user1']->id, 'idnumber' => 'dev2', 'fullname' => 'Programmer', 'totarasync' => 1]);
        $future = new stdClass();
        $future->idnumber = 'dev2';
        $future->useridnumber = 'user1';
        $future->fullname = 'Developer';
        $future->timemodified = 1900000000; // March 2030.
        $DB->insert_record($source->temptablename, $future);

        \totara_job\job_assignment::create(
            ['userid' => $users['user1']->id, 'idnumber' => 'dev3', 'fullname' => 'Programmer', 'totarasync' => 1]);
        $past = new stdClass();
        $past->idnumber = 'dev3';
        $past->useridnumber = 'user1';
        $past->fullname = 'Developer';
        $past->timemodified = 100;
        $DB->insert_record($source->temptablename, $past);

        $jobassignment4 = \totara_job\job_assignment::create(
            ['userid' => $users['user1']->id, 'idnumber' => 'dev4', 'fullname' => 'Programmer', 'totarasync' => 1, 'synctimemodified' => 100]);
        // This shouldn't be updated so we'll save for later.
        $jobassign4timemodified = $jobassignment4->timemodified;
        $same = new stdClass();
        $same->idnumber = 'dev4';
        $same->useridnumber = 'user1';
        $same->fullname = 'Developer';
        $same->timemodified = 100;
        $DB->insert_record($source->temptablename, $same);

        $source->set_config('import_fullname', '1');

        $element = $this->get_element_mock($source);
        $element->set_config('allow_update', '1');

        // If you start to get intermittent failures, don't move this back. Timemodified should
        // be updated here to the current time for all except $jobassignment4 (idnumber = dev4).
        $this->waitForSecond();
        $this->setCurrentTimeStart();
        $element->sync();

        $jobassignments = \totara_job\job_assignment::get_all($users['user1']->id);
        $this->assertCount(4, $jobassignments);
        $this->assertEquals('dev1', $jobassignments[1]->idnumber);
        $this->assertEquals('Developer', $jobassignments[1]->fullname);
        $this->assertTimeCurrent($jobassignments[1]->timemodified);
        $this->assertEquals('dev2', $jobassignments[2]->idnumber);
        $this->assertEquals('Developer', $jobassignments[2]->fullname);
        $this->assertTimeCurrent($jobassignments[2]->timemodified);
        $this->assertEquals('dev3', $jobassignments[3]->idnumber);
        $this->assertEquals('Developer', $jobassignments[3]->fullname);
        $this->assertTimeCurrent($jobassignments[3]->timemodified);
        $this->assertEquals('dev4', $jobassignments[4]->idnumber);
        $this->assertEquals('Programmer', $jobassignments[4]->fullname);
        $this->assertEquals($jobassign4timemodified, $jobassignments[4]->timemodified);

        // Now just check that only the timemodified = 0 source record gets updated.
        $source = $this->prepare_source_table();

        $zero = new stdClass();
        $zero->idnumber = 'dev1';
        $zero->useridnumber = 'user1';
        $zero->fullname = 'Updated';
        $zero->timemodified = 0;
        $DB->insert_record($source->temptablename, $zero);

        $future = new stdClass();
        $future->idnumber = 'dev2';
        $future->useridnumber = 'user1';
        $future->fullname = 'Updated';
        $future->timemodified = 1900000000; // March 2030.
        $DB->insert_record($source->temptablename, $future);

        $past = new stdClass();
        $past->idnumber = 'dev3';
        $past->useridnumber = 'user1';
        $past->fullname = 'Updated';
        $past->timemodified = 100;
        $DB->insert_record($source->temptablename, $past);

        $same = new stdClass();
        $same->idnumber = 'dev4';
        $same->useridnumber = 'user1';
        $same->fullname = 'Updated';
        $same->timemodified = 100;
        $DB->insert_record($source->temptablename, $same);

        $element->sync();

        $jobassignments = \totara_job\job_assignment::get_all($users['user1']->id);
        $this->assertCount(4, $jobassignments);
        $this->assertEquals('dev1', $jobassignments[1]->idnumber);
        $this->assertEquals('Updated', $jobassignments[1]->fullname);
        $this->assertEquals('dev2', $jobassignments[2]->idnumber);
        $this->assertEquals('Developer', $jobassignments[2]->fullname);
        $this->assertEquals('dev3', $jobassignments[3]->idnumber);
        $this->assertEquals('Developer', $jobassignments[3]->fullname);
        $this->assertEquals('dev4', $jobassignments[4]->idnumber);
        $this->assertEquals('Programmer', $jobassignments[4]->fullname);
    }

    public function test_manager_valid() {
        global $DB;
        $this->resetAfterTest(true);
        $source = $this->prepare_source_table();
        $users = $this->create_test_users();

        $manager1 = $this->getDataGenerator()->create_user(array('idnumber' => 'manager1'));
        $manager2 = $this->getDataGenerator()->create_user(array('idnumber' => 'manager2'));
        $manager3 = $this->getDataGenerator()->create_user(array('idnumber' => 'manager3'));
        $manager1job1 = \totara_job\job_assignment::create(array('userid' => $manager1->id, 'idnumber' => 'job1'));
        $manager1job2 = \totara_job\job_assignment::create(array('userid' => $manager1->id, 'idnumber' => 'job2'));
        $manager2job1 = \totara_job\job_assignment::create(array('userid' => $manager2->id, 'idnumber' => 'job1'));
        $manager2job2 = \totara_job\job_assignment::create(array('userid' => $manager2->id, 'idnumber' => 'job2'));
        // We want extra managers with the same job idnumber so this fails when it just chooses the last matching job.
        $manager3job2 = \totara_job\job_assignment::create(array('userid' => $manager3->id, 'idnumber' => 'job2'));

        \totara_job\job_assignment::create(
            ['userid' => $users['user1']->id, 'idnumber' => 'dev1', 'managerjaid' => $manager1job1->id, 'totarasync' => 1]);

        $entry = new stdClass();
        $entry->idnumber = 'dev1';
        $entry->useridnumber = 'user1';
        $entry->timemodified = 0;
        $entry->manageridnumber = 'manager2';
        $entry->managerjobassignmentidnumber = 'job2';
        $DB->insert_record($source->temptablename, $entry);

        $source->set_config('import_manageridnumber', '1');

        $element = $this->get_element_mock($source);
        $element->set_config('allow_update', '1');
        $element->sync();

        $jobassignments = \totara_job\job_assignment::get_all($users['user1']->id);
        $this->assertCount(1, $jobassignments);
        $this->assertEquals('dev1', $jobassignments[1]->idnumber);
        $this->assertEquals($manager2->id, $jobassignments[1]->managerid);
        $this->assertEquals($manager2job2->id, $jobassignments[1]->managerjaid);
    }

    public function test_manager_no_matching_idnumber() {
        global $DB;
        $this->resetAfterTest(true);
        $source = $this->prepare_source_table();
        $users = $this->create_test_users();

        $manager1 = $this->getDataGenerator()->create_user(array('idnumber' => 'manager1'));
        $manager2 = $this->getDataGenerator()->create_user(array('idnumber' => 'manager2'));
        $manager3 = $this->getDataGenerator()->create_user(array('idnumber' => 'manager3'));
        $manager1job1 = \totara_job\job_assignment::create(array('userid' => $manager1->id, 'idnumber' => 'job1'));
        $manager1job2 = \totara_job\job_assignment::create(array('userid' => $manager1->id, 'idnumber' => 'job2'));
        $manager2job1 = \totara_job\job_assignment::create(array('userid' => $manager2->id, 'idnumber' => 'job1'));
        $manager2job2 = \totara_job\job_assignment::create(array('userid' => $manager2->id, 'idnumber' => 'job2'));
        // We want extra managers with the same job idnumber so this fails when it just chooses the last matching job.
        $manager3job2 = \totara_job\job_assignment::create(array('userid' => $manager3->id, 'idnumber' => 'job2'));

        \totara_job\job_assignment::create(
            ['userid' => $users['user1']->id, 'idnumber' => 'dev1', 'managerjaid' => $manager1job1->id, 'totarasync' => 1]);

        $entry = new stdClass();
        $entry->idnumber = 'dev1';
        $entry->useridnumber = 'user1';
        $entry->timemodified = 0;
        $entry->manageridnumber = 'managerX';
        $entry->managerjobassignmentidnumber = 'job2';
        $DB->insert_record($source->temptablename, $entry);

        $source->set_config('import_manageridnumber', '1');

        $element = $this->get_element_mock($source);
        $element->set_config('allow_update', '1');
        $element->sync();

        $jobassignments = \totara_job\job_assignment::get_all($users['user1']->id);
        $this->assertCount(1, $jobassignments);
        $this->assertEquals('dev1', $jobassignments[1]->idnumber);
        $this->assertEquals($manager1->id, $jobassignments[1]->managerid);
        $this->assertEquals($manager1job1->id, $jobassignments[1]->managerjaid);
        $this->assertEquals(1, $DB->count_records('totara_sync_log', array(
            'element' => 'jobassignment',
            'logtype' => 'error',
            'action' => 'checksanity',
            'info' => 'User \'managerX\' does not exist and was set to be assigned as manager. Skipped job assignment \'dev1\' for user \'user1\'.')));
    }

    public function test_manager_no_matching_jobassignment_idnumber() {
        global $DB;
        $this->resetAfterTest(true);
        $source = $this->prepare_source_table();
        $users = $this->create_test_users();

        $manager1 = $this->getDataGenerator()->create_user(array('idnumber' => 'manager1'));
        $manager2 = $this->getDataGenerator()->create_user(array('idnumber' => 'manager2'));
        $manager3 = $this->getDataGenerator()->create_user(array('idnumber' => 'manager3'));
        $manager1job1 = \totara_job\job_assignment::create(array('userid' => $manager1->id, 'idnumber' => 'job1'));
        $manager1job2 = \totara_job\job_assignment::create(array('userid' => $manager1->id, 'idnumber' => 'job2'));
        $manager2job1 = \totara_job\job_assignment::create(array('userid' => $manager2->id, 'idnumber' => 'job1'));
        $manager2job2 = \totara_job\job_assignment::create(array('userid' => $manager2->id, 'idnumber' => 'job2'));
        // We want extra managers with the same job idnumber so this fails when it just chooses the last matching job.
        $manager3job2 = \totara_job\job_assignment::create(array('userid' => $manager3->id, 'idnumber' => 'job2'));

        \totara_job\job_assignment::create(
            ['userid' => $users['user1']->id, 'idnumber' => 'dev1', 'managerjaid' => $manager1job1->id, 'totarasync' => 1]);

        $entry = new stdClass();
        $entry->idnumber = 'dev1';
        $entry->useridnumber = 'user1';
        $entry->timemodified = 0;
        $entry->manageridnumber = 'manager2';
        $entry->managerjobassignmentidnumber = 'jobX';
        $DB->insert_record($source->temptablename, $entry);

        $source->set_config('import_manageridnumber', '1');

        $element = $this->get_element_mock($source);
        $element->set_config('allow_update', '1');
        $element->sync();

        $jobassignments = \totara_job\job_assignment::get_all($users['user1']->id);
        $this->assertCount(1, $jobassignments);
        $this->assertEquals('dev1', $jobassignments[1]->idnumber);
        $this->assertEquals($manager1->id, $jobassignments[1]->managerid);
        $this->assertEquals($manager1job1->id, $jobassignments[1]->managerjaid);
        $this->assertEquals(1, $DB->count_records('totara_sync_log', array(
            'element' => 'jobassignment',
            'logtype' => 'error',
            'action' => 'create/update',
            'info' => 'Job assignment \'jobX\' for manager \'manager2\' does not exist. Skipped job assignment \'dev1\' for user \'user1\'.')));
    }

    public function test_managerjobassignmentidnumber_blank() {
        global $DB;
        $this->resetAfterTest(true);
        $source = $this->prepare_source_table();
        $users = $this->create_test_users();

        $manager1 = $this->getDataGenerator()->create_user(array('idnumber' => 'manager1'));
        $manager2 = $this->getDataGenerator()->create_user(array('idnumber' => 'manager2'));
        $manager3 = $this->getDataGenerator()->create_user(array('idnumber' => 'manager3'));
        $manager1job1 = \totara_job\job_assignment::create(array('userid' => $manager1->id, 'idnumber' => 'job1'));
        $manager1job2 = \totara_job\job_assignment::create(array('userid' => $manager1->id, 'idnumber' => 'job2'));
        $manager2job1 = \totara_job\job_assignment::create(array('userid' => $manager2->id, 'idnumber' => 'job1'));
        $manager2job2 = \totara_job\job_assignment::create(array('userid' => $manager2->id, 'idnumber' => 'job2'));
        // We want extra managers with the same job idnumber so this fails when it just chooses the last matching job.
        $manager3job2 = \totara_job\job_assignment::create(array('userid' => $manager3->id, 'idnumber' => 'job2'));

        \totara_job\job_assignment::create(
            ['userid' => $users['user1']->id, 'idnumber' => 'dev1', 'managerjaid' => $manager1job1->id, 'totarasync' => 1]);

        $entry = new stdClass();
        $entry->idnumber = 'dev1';
        $entry->useridnumber = 'user1';
        $entry->timemodified = 0;
        $entry->manageridnumber = 'manager2';
        $entry->managerjobassignmentidnumber = '';
        $DB->insert_record($source->temptablename, $entry);

        $source->set_config('import_manageridnumber', '1');

        $element = $this->get_element_mock($source);
        $element->set_config('allow_update', '1');
        $element->sync();

        $jobassignments = \totara_job\job_assignment::get_all($users['user1']->id);
        $this->assertCount(1, $jobassignments);
        $this->assertEquals('dev1', $jobassignments[1]->idnumber);
        $this->assertEquals($manager1->id, $jobassignments[1]->managerid);
        $this->assertEquals($manager1job1->id, $jobassignments[1]->managerjaid);
        $this->assertEquals(1, $DB->count_records('totara_sync_log', array(
            'element' => 'jobassignment',
            'logtype' => 'error',
            'action' => 'create/update',
            'info' => 'Missing manager\'s job assignment id number for assigning manager \'manager2\'. Skipped job assignment \'dev1\' for user \'user1\'.')));
    }

    public function test_manager_job_assignments_created_later() {
        global $DB;
        $this->resetAfterTest(true);
        $source = $this->prepare_source_table();
        $users = $this->create_test_users();

        $manager1 = $this->getDataGenerator()->create_user(array('idnumber' => 'manager1'));
        $manager2 = $this->getDataGenerator()->create_user(array('idnumber' => 'manager2'));
        $manager3 = $this->getDataGenerator()->create_user(array('idnumber' => 'manager3'));
        $user = $this->getDataGenerator()->create_user(['idnumber' => 'user1']);
        \totara_job\job_assignment::create(
            ['userid' => $user->id, 'idnumber' => 'dev1', 'totarasync' => 1]);

        $entry = new stdClass();
        $entry->idnumber = 'dev1';
        $entry->useridnumber = 'user1';
        $entry->timemodified = 0;
        $entry->manageridnumber = 'manager1';
        $entry->managerjobassignmentidnumber = 'job1';
        $DB->insert_record($source->temptablename, $entry);
        $entry = new stdClass();
        $entry->idnumber = 'job1';
        $entry->useridnumber = 'manager1';
        $entry->timemodified = 0;
        $entry->manageridnumber = 'manager2';
        $entry->managerjobassignmentidnumber = 'job1';
        $DB->insert_record($source->temptablename, $entry);
        $entry = new stdClass();
        $entry->idnumber = 'job1';
        $entry->useridnumber = 'manager2';
        $entry->timemodified = 0;
        $entry->manageridnumber = 'manager3';
        $entry->managerjobassignmentidnumber = 'jobA';
        $DB->insert_record($source->temptablename, $entry);
        $entry = new stdClass();
        $entry->idnumber = 'jobA';
        $entry->useridnumber = 'manager3';
        $entry->timemodified = 0;
        $entry->manageridnumber = '';
        $entry->managerjobassignmentidnumber = '';
        $DB->insert_record($source->temptablename, $entry);

        $source->set_config('import_manageridnumber', '1');

        $element = $this->get_element_mock($source);
        $element->set_config('allow_create', '1');
        $element->set_config('allow_update', '1');
        $element->sync();

        $jobassignments = \totara_job\job_assignment::get_all($users['user1']->id);
        $this->assertCount(1, $jobassignments);
        $this->assertEquals('dev1', $jobassignments[1]->idnumber);
        $this->assertEquals($manager1->id, $jobassignments[1]->managerid);

        $jobassignments = \totara_job\job_assignment::get_all($manager1->id);
        $this->assertCount(1, $jobassignments);
        $this->assertEquals('job1', $jobassignments[1]->idnumber);
        $this->assertEquals($manager2->id, $jobassignments[1]->managerid);

        $jobassignments = \totara_job\job_assignment::get_all($manager2->id);
        $this->assertCount(1, $jobassignments);
        $this->assertEquals('job1', $jobassignments[1]->idnumber);
        $this->assertEquals($manager3->id, $jobassignments[1]->managerid);

        $jobassignments = \totara_job\job_assignment::get_all($manager3->id);
        $this->assertCount(1, $jobassignments);
        $this->assertEquals('jobA', $jobassignments[1]->idnumber);
        $this->assertEmpty($jobassignments[1]->managerid);
    }

    public function test_self_assigned_as_manager() {
        global $DB;
        $this->resetAfterTest(true);
        $source = $this->prepare_source_table();
        $users = $this->create_test_users();

        $manager1 = $this->getDataGenerator()->create_user(array('idnumber' => 'manager1'));
        $manager1job1 = \totara_job\job_assignment::create(array('userid' => $manager1->id, 'idnumber' => 'job1'));

        \totara_job\job_assignment::create(
            ['userid' => $users['user1']->id, 'idnumber' => 'dev1', 'managerjaid' => $manager1job1->id, 'totarasync' => 1]);
        \totara_job\job_assignment::create(
            ['userid' => $users['user1']->id, 'idnumber' => 'job2']);

        $entry = new stdClass();
        $entry->idnumber = 'dev1';
        $entry->useridnumber = 'user1';
        $entry->timemodified = 0;
        $entry->manageridnumber = 'user1';
        $entry->managerjobassignmentidnumber = 'job2';
        $DB->insert_record($source->temptablename, $entry);

        $source->set_config('import_manageridnumber', '1');

        $element = $this->get_element_mock($source);
        $element->set_config('allow_update', '1');
        $element->sync();

        $jobassignments = \totara_job\job_assignment::get_all($users['user1']->id);
        $this->assertCount(2, $jobassignments);
        $this->assertEquals('dev1', $jobassignments[1]->idnumber);
        $this->assertEquals($manager1->id, $jobassignments[1]->managerid);
        $this->assertEquals($manager1job1->id, $jobassignments[1]->managerjaid);
        $this->assertEquals(1, $DB->count_records('totara_sync_log', array(
            'element' => 'jobassignment',
            'logtype' => 'error',
            'action' => 'checksanity',
            'info' => 'User \'user1\' cannot be their own manager. Skipped job assignment \'dev1\' for user \'user1\'.')));
    }

    public function test_updateidnumbers_create_when_no_jobs() {
        global $DB;
        $this->resetAfterTest(true);
        $source = $this->prepare_source_table();
        $users = $this->create_test_users();

        $initialjobassignments = \totara_job\job_assignment::get_all($users['user1']->id);
        $this->assertCount(0, $initialjobassignments);

        $entry = new stdClass();
        $entry->idnumber = 'dev1';
        $entry->useridnumber = 'user1';
        $entry->timemodified = 0;
        $DB->insert_record($source->temptablename, $entry);

        $element = $this->get_element_mock($source);
        $element->set_config('allow_create', '1');
        $element->set_config('updateidnumbers', '1');
        $element->sync();

        $jobassignments = \totara_job\job_assignment::get_all($users['user1']->id);
        $this->assertCount(1, $jobassignments);
        $this->assertEquals('dev1', $jobassignments[1]->idnumber);
    }

    public function test_updateidnumbers_update_first_job() {
        global $DB;
        $this->resetAfterTest(true);
        $source = $this->prepare_source_table();
        $users = $this->create_test_users();

        \totara_job\job_assignment::create(
            array('userid' => $users['user1']->id, 'idnumber' => 'dev1', 'totarasync' => 1));

        $entry = new stdClass();
        $entry->idnumber = 'job1'; // Changing the id number.
        $entry->useridnumber = 'user1';
        $entry->timemodified = 0;
        $DB->insert_record($source->temptablename, $entry);

        $element = $this->get_element_mock($source);
        // We don't want to create, so allow it and make sure it doesn't do so.
        $element->set_config('allow_create', '1');
        $element->set_config('allow_update', '1');
        $element->set_config('updateidnumbers', '1');
        $element->sync();

        $jobassignments = \totara_job\job_assignment::get_all($users['user1']->id);
        $this->assertCount(1, $jobassignments);
        $this->assertEquals('job1', $jobassignments[1]->idnumber);
    }

    public function test_updateidnumbers_first_must_be_totarasync() {
        global $DB;
        $this->resetAfterTest(true);
        $source = $this->prepare_source_table();
        $users = $this->create_test_users();

        \totara_job\job_assignment::create(
            array('userid' => $users['user1']->id, 'idnumber' => 'dev1', 'totarasync' => 0));

        $entry = new stdClass();
        $entry->idnumber = 'job1'; // Changing the id number.
        $entry->useridnumber = 'user1';
        $entry->timemodified = 0;
        $DB->insert_record($source->temptablename, $entry);

        $element = $this->get_element_mock($source);
        // We don't want to create, so allow it and make sure it doesn't do so.
        $element->set_config('allow_create', '1');
        $element->set_config('allow_update', '1');
        $element->set_config('updateidnumbers', '1');
        $element->sync();

        $jobassignments = \totara_job\job_assignment::get_all($users['user1']->id);
        $this->assertCount(1, $jobassignments);
        $this->assertEquals('dev1', $jobassignments[1]->idnumber);
    }

    // Todo: Test for managers when updateidnumbers is on.


    public function test_circular_management_structure() {
        global $DB;
        $this->resetAfterTest(true);
        $source = $this->prepare_source_table();
        $users = $this->create_test_users();
        $manager1 = $this->getDataGenerator()->create_user(array('idnumber' => 'manager1'));
        $manager2 = $this->getDataGenerator()->create_user(array('idnumber' => 'manager2'));

        $source->set_config('import_manageridnumber', '1');

        // Management loop between user1, manager1 and manager2.
        $entry = new stdClass();
        $entry->idnumber = 'dev1';
        $entry->useridnumber = 'user1';
        $entry->timemodified = 0;
        $entry->manageridnumber = 'manager1';
        $entry->managerjobassignmentidnumber = 'job1';
        $DB->insert_record($source->temptablename, $entry);
        $entry = new stdClass();
        $entry->idnumber = 'job1';
        $entry->useridnumber = 'manager1';
        $entry->timemodified = 0;
        $entry->manageridnumber = 'manager2';
        $entry->managerjobassignmentidnumber = 'jobA';
        $DB->insert_record($source->temptablename, $entry);
        $entry = new stdClass();
        $entry->idnumber = 'jobA';
        $entry->useridnumber = 'manager2';
        $entry->timemodified = 0;
        $entry->manageridnumber = 'user1';
        $entry->managerjobassignmentidnumber = 'dev1';
        $DB->insert_record($source->temptablename, $entry);

        $element = $this->get_element_mock($source);
        $element->set_config('allow_create', '1');
        $element->set_config('allow_update', '0');
        $element->sync();

        // At one point, our old code still created job assignments but just didn't assign the
        // managers to all of them.
        // This is not consistent with the general behaviour that if a records wrong, don't
        // do it at all. But if we do put that back, we need to make sure that it still works if
        // allow_update is off (because are creating them first and then *updating* them on retries?)
        $this->assertEmpty(\totara_job\job_assignment::get_all($users['user1']->id));
        $this->assertEmpty($manager1jobassignments = \totara_job\job_assignment::get_all($manager1->id));
        $this->assertEmpty($manager2jobassignments = \totara_job\job_assignment::get_all($manager2->id));
    }

    public function test_circular_management_structure_due_to_existing_assignments() {
        global $DB;
        $this->resetAfterTest(true);
        $source = $this->prepare_source_table();
        $users = $this->create_test_users();
        $manager1 = $this->getDataGenerator()->create_user(array('idnumber' => 'manager1'));
        $manager2 = $this->getDataGenerator()->create_user(array('idnumber' => 'manager2'));

        // Manager 2 is already managing manager1. The import will be what completes the loop.
        $manager2ja = \totara_job\job_assignment::create(
            array('userid' => $manager2->id, 'idnumber' => 'jobA', 'totarasync' => 1));
        \totara_job\job_assignment::create(
            array('userid' => $manager1->id, 'idnumber' => 'job1', 'managerjaid' => $manager2ja->id));

        $source->set_config('import_manageridnumber', '1');

        $entry = new stdClass();
        $entry->idnumber = 'dev1';
        $entry->useridnumber = 'user1';
        $entry->timemodified = 0;
        $entry->manageridnumber = 'manager1';
        $entry->managerjobassignmentidnumber = 'job1';
        $DB->insert_record($source->temptablename, $entry);

        // No entry for manager1, that's already in the database. We want the import to be aware of this.

        $entry = new stdClass();
        $entry->idnumber = 'jobA';
        $entry->useridnumber = 'manager2';
        $entry->timemodified = 0;
        $entry->manageridnumber = 'user1';
        $entry->managerjobassignmentidnumber = 'dev1';
        $DB->insert_record($source->temptablename, $entry);

        $element = $this->get_element_mock($source);
        // We don't want to create, so allow it and make sure it doesn't do so.
        $element->set_config('allow_create', '1');
        $element->set_config('allow_update', '1');
        $element->sync();

        $user1jobassignments = \totara_job\job_assignment::get_all($users['user1']->id);
        $manager1jobassignments = \totara_job\job_assignment::get_all($manager1->id);
        $manager2jobassignments = \totara_job\job_assignment::get_all($manager2->id);

        $this->assertCount(1, $user1jobassignments);
        $this->assertCount(1, $manager1jobassignments);
        $this->assertCount(1, $manager2jobassignments);

        $this->assertEquals($manager1->id, $user1jobassignments[1]->managerid);
        $this->assertEquals($manager2->id, $manager1jobassignments[1]->managerid);
        $this->assertEmpty($manager2jobassignments[1]->managerid);
    }
}
