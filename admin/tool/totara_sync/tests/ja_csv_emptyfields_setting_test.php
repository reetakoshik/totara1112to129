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
 * @author Alastair Munro <alastair.munro@totaralearning.com>
 * @package tool_totara_sync
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/admin/tool/totara_sync/lib.php');
require_once($CFG->dirroot . '/admin/tool/totara_sync/tests/source_csv_testcase.php');
require_once($CFG->dirroot . '/admin/tool/totara_sync/sources/source_jobassignment_csv.php');

/**
 * Test Job assignment CSV empty fields
 *
 * @group tool_totara_sync
 */
class tool_totara_sync_jobassignment_csv_emptyfields_setting_testcase extends totara_sync_csv_testcase {

    protected $filedir = null;
    protected $configcsv = array();
    protected $config = array();

    protected $elementname = 'jobassignment';
    protected $sourcename = 'totara_sync_source_jobassignment_csv';
    protected $source = null;

    protected function tearDown() {
        $this->filedir = null;
        $this->configcsv = null;
        $this->config = null;
        $this->source = null;

        parent::tearDown();
    }

    protected function setUp() {
        parent::setUp();

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $this->source = new $this->sourcename();

        $this->filedir = $this->create_filedir();

        set_config('element_jobassignment_enabled', 1, 'totara_sync');
        set_config('source_jobassignment', 'totara_sync_source_jobassignment_csv', 'totara_sync');
        set_config('fileaccess', TOTARA_SYNC_FILE_ACCESS_MEMORY, 'totara_sync');
        set_config('filesdir', $this->filedir, 'totara_sync');

        //Data for creating mocked db tables.
        $org_framework_data1 = array(
            'id' => 1, 'fullname' => 'Organisation Framework 1', 'shortname' => 'OFW1', 'idnumber' => '1', 'description' => 'Description 1',
            'sortorder' => 1, 'visible' => 1, 'hidecustomfields' => 0, 'timecreated' => 1265963591, 'timemodified' => 1265963591, 'usermodified' => 2,
        );

        $org_data1 = array(
            'id' => 1, 'fullname' => 'Top Organisation', 'shortname' => 'toporg', 'idnumber' => 'ORG1', 'description' => 'Top level organisation', 'frameworkid' => 1,
            'path' => '/1', 'depthlevel' => 1, 'parentid' => 0, 'sortthread' => '01', 'visible' => 1, 'timevalidfrom' => 0, 'timevalidto' => 0,
            'timecreated' => 0, 'timemodified' => 0, 'usermodified' => 2,
        );

        $pos_framework_data1 = array(
            'id' => 1, 'fullname' => 'Position Framework 1', 'shortname' => 'PFW1', 'idnumber' => '1', 'description' => 'Description 1',
            'sortorder' => 1, 'visible' => 1, 'hidecustomfields' => 0, 'timecreated' => 1265963591, 'timemodified' => 1265963591, 'usermodified' => 2,
        );

        $pos_data1 = array(
            'id' => 1, 'fullname' => 'Top Position', 'shortname' => 'toppos', 'idnumber' => 'POS1', 'description' => 'Top level position', 'frameworkid' => 1,
            'path' => '/1', 'depthlevel' => 1, 'parentid' => 0, 'sortthread' => '01', 'visible' => 1, 'timevalidfrom' => 0, 'timevalidto' => 0,
            'timecreated' => 0, 'timemodified' => 0, 'usermodified' => 2,
        );

        $user_data1 = array(
            'id' => 3, 'firstname' => 'User', 'lastname' => 'One', 'username' => 'user1', 'email' => 'user.one@example.com', 'idnumber' => '11235',
            'auth' => 'manual', 'city' => 'Brighton', 'country' => 'GB'
        );

        $user_data2 = array(
            'id' => 4, 'firstname' => 'Manager', 'lastname' => 'One', 'username' => 'manager1', 'email' => 'manager.one@example.com', 'idnumber' => 'mgr1',
            'auth' => 'manual', 'city' => 'Brighton', 'country' => 'GB'
        );

        $user_data3 = array(
            'id' => 5, 'firstname' => 'Appraiser', 'lastname' => 'One', 'username' => 'appraiser1', 'email' => 'appraiser.one@example.com', 'idnumber' => 'aprs1',
            'auth' => 'manual', 'city' => 'Brighton', 'country' => 'GB'
        );

        $ja_data1 = array(
            'id' => 1, 'fullname' => 'iManager', 'shortname' => 'manager', 'idnumber' => 1, 'timecreated' => '1476783884', 'timemodified' => '1476783884',
            'usermodified' => '2', 'userid' => '4', 'sortorder' => '1', 'positionassignmentdate' => '1476784550', 'totarasync' => 1, 'synctimemodified' => '0'
        );

        // Create a Organisation framework.
        $this->loadDataSet($this->createArrayDataset(array(
            'org_framework' => array($org_framework_data1),
            'org' => array($org_data1),
            'pos_framework' => array($pos_framework_data1),
            'pos' => array($pos_data1),
            'user' => array(
                $user_data1,
                $user_data2,
                $user_data3
            ),
            'job_assignment' => array($ja_data1)
        )));

        $this->configcsv = array(
            'csvuserencoding' => 'UTF-8',
            'delimiter' => ',',
            'csvsaveemptyfields' => true,

            'fieldmapping_idnumber' => '',
            'fieldmapping_useridnumber' => '',
            'fieldmapping_timemodified' => '',

            'fieldmapping_fullname' => '',
            'fieldmapping_startdate' => '',
            'fieldmapping_enddate' => '',
            'fieldmapping_orgidnumber' => '',
            'fieldmapping_posidnumber' => '',
            'fieldmapping_manageridnumber' => '',
            'fieldmapping_appraiseridnumber' => '',

            'import_fullname' => '1',
            'import_startdate' => '1',
            'import_enddate' => '1',
            'import_orgidnumber' => '1',
            'import_posidnumber' => '1',
            'import_manageridnumber' => '1',
            'import_appraiseridnumber' => '1',
        );

        $this->config = array(
            'sourceallrecords' => '1',
            'allow_create' => '1',
            'allow_delete' => '0',
            'allow_update' => '1',
        );
    }

    /**
     * Returns a list of common import fields for this test
     *
     * @return Array list of fields to be imported
     */
    public function importfields() {
        $importfields = array();

        $importfields['import_idnumber'] = 1;
        $importfields['import_useridnumber'] = 1;
        $importfields['import_timemodified'] = 1;

        return $importfields;
    }

    /**
     * Test creating Job assignments with "Save empty fields" off and
     * all fields in the CSV are populated with data.
     */
    public function test_sync_add_ja_emptyfields_setting_off_populated_fields() {
        global $DB;

        // Set the config.
        $config = array_merge($this->configcsv, $this->importfields());
        $this->set_source_config($config);
        $extraconfig = array(
            'updateidnumbers' => true,
            'csvsavemeptyfields' => false
        );
        $config = array_merge($this->config, $extraconfig);
        $this->set_element_config($config);

        $element = $this->get_element();

        $csv = "idnumber,useridnumber,timemodified,deleted,fullname,orgidnumber,posidnumber,manageridnumber,appraiseridnumber,startdate,enddate\n";
        $csv .= "1, 11235, 1510154500, 0, \"my job\", ORG1, POS1, mgr1, aprs1,1510154510,1510554500";
        $element->get_source()->set_csv_in_memory($csv);

        $this->assertTrue($element->sync()); // Run the sync.
        $jas = $DB->get_records('job_assignment');
        $this->assertCount(2, $jas); // Check the correct number of Job assignments.

        $record1 = $jas[1];
        $this->assertEquals('iManager', $record1->fullname);
        $this->assertEquals(1, $record1->idnumber);
        $this->assertEquals(4, $record1->userid);

        $record2 = $jas[2];
        $this->assertEquals('my job', $record2->fullname);
        $this->assertEquals(1, $record2->idnumber);
        $this->assertEquals(3, $record2->userid);
    }

    /**
     * Test creating Job assignment with "Save empty fields" on and
     * all fields in the CSV are populated with data.
     */
    public function test_sync_add_ja_emptyfields_setting_on_populated_fields() {
        global $DB;

        // Set the config.
        $config = array_merge($this->configcsv, $this->importfields());
        $this->set_source_config($config);
        $extraconfig = array(
            'updateidnumbers' => true,
            'csvsavemeptyfields' => true
        );
        $config = array_merge($this->config, $extraconfig);
        $this->set_element_config($config);

        $element = $this->get_element();

        $csv = "idnumber,useridnumber,timemodified,deleted,fullname,orgidnumber,posidnumber,manageridnumber,appraiseridnumber,startdate,enddate\n";
        $csv .= "1, 11235, 1510154500, 0, \"my job\", ORG1, POS1, mgr1, aprs1,1510154510,1510554500";
        $element->get_source()->set_csv_in_memory($csv);

        $this->assertTrue($element->sync()); // Run the sync.
        $jas = $DB->get_records('job_assignment');
        $this->assertCount(2, $jas); // Check the correct number of Job assignments.

        $record1 = $jas[1];
        $this->assertEquals('iManager', $record1->fullname);
        $this->assertEquals(1, $record1->idnumber);
        $this->assertEquals(4, $record1->userid);

        $record2 = $jas[2];
        $this->assertEquals('my job', $record2->fullname);
        $this->assertEquals(1, $record2->idnumber);
        $this->assertEquals(3, $record2->userid);
    }

    /**
     * Test updating Job assignments with "Save empty fields" off and
     * all fields in the CSV are populated with data.
     */
    public function test_sync_update_ja_emptyfields_setting_off_populated_fields() {
        global $DB;

        $jobassignment = array(
            'id' => 2,
            'idnumber' => 2,
            'userid' => 3,
            'timemodified' => 1510154500,
            'deleted' => 0,
            'fullname' => 'my job',
            'organisationid' => '1',
            'positionidnumber' => '1',
            'managerjaid' => '1',
            'managerjapath' => '/1',
            'appraiserid' => '5',
            'startdate' => '1510154510',
            'enddate' => '1510554500',
            'timecreated' => 1510154510,
            'usermodified' => '2',
            'positionassignmentdate' => '1476784550',
            'totarasync' => '1',
            'synctimemodified' => '0',
            'sortorder' => '1'

        );

        $this->loadDataSet($this->createArrayDataset(array(
            'job_assignment' => array($jobassignment)
        )));

        // Set the config.
        $config = array_merge($this->configcsv, $this->importfields());
        $this->set_source_config($config);
        $extraconfig = array(
            'updateidnumbers' => true,
            'csvsavemeptyfields' => false
        );
        $config = array_merge($this->config, $extraconfig);
        $this->set_element_config($config);

        $element = $this->get_element();

        $csv = "idnumber,useridnumber,timemodified,deleted,fullname,orgidnumber,posidnumber,manageridnumber,appraiseridnumber,startdate,enddate\n";
        $csv .= "2, 11235, 1510154500, 0, \"my awesome job\", ORG1, POS1, mgr1, aprs1,1510154510,1510554500";
        $element->get_source()->set_csv_in_memory($csv);

        $this->assertTrue($element->sync()); // Run the sync.
        $this->assertCount(2, $DB->get_records('job_assignment')); // Check the correct number of Job assignments.

        $record = $DB->get_record('job_assignment', array('id' => 2));
        $this->assertEquals('my awesome job', $record->fullname);

    }

    /**
     * Test updating Job assignments with "Save empty fields" on and
     * all fields in the CSV are populated with data.
     */
    public function test_sync_update_ja_emptyfields_setting_on_populated_fields() {
        global $DB;

        $jobassignment = array(
            'id' => 2,
            'idnumber' => 2,
            'userid' => 3,
            'timemodified' => 1510154500,
            'deleted' => 0,
            'fullname' => 'my job',
            'organisationid' => '1',
            'positionid' => '1',
            'managerjaid' => '1',
            'managerjapath' => '/1',
            'appraiserid' => '5',
            'startdate' => '1510154510',
            'enddate' => '1510554500',
            'timecreated' => 1510154510,
            'usermodified' => '2',
            'positionassignmentdate' => '1476784550',
            'totarasync' => '1',
            'synctimemodified' => '0',
            'sortorder' => '1'
        );

        $this->loadDataSet($this->createArrayDataset(array(
            'job_assignment' => array($jobassignment)
        )));

        // Set the config.
        $config = array_merge($this->configcsv, $this->importfields());
        $this->set_source_config($config);
        $extraconfig = array(
            'updateidnumbers' => true,
            'csvsavemeptyfields' => true
        );
        $config = array_merge($this->config, $extraconfig);
        $this->set_element_config($config);

        $element = $this->get_element();

        $csv = "idnumber,useridnumber,timemodified,deleted,fullname,orgidnumber,posidnumber,manageridnumber,appraiseridnumber,startdate,enddate\n";
        $csv .= "2, 11235, 1510154500, 0, \"my awesome job\", ORG1, POS1, mgr1, aprs1,1510154510,1510554500";
        $element->get_source()->set_csv_in_memory($csv);

        $this->assertTrue($element->sync()); // Run the sync.
        $this->assertCount(2, $DB->get_records('job_assignment')); // Check the correct number of Job assignments.

        $record = $DB->get_record('job_assignment', array('id' => 2));
        $this->assertEquals('my awesome job', $record->fullname);
    }

    /**
     * Test updating Job assignments with "Save empty fields" off and
     * there are empty fields.
     */
    public function test_sync_update_ja_emptyfields_setting_off_empty_fields() {
        global $DB;

        $jobassignment = array(
            'id' => 2,
            'idnumber' => 2,
            'userid' => 3,
            'timemodified' => 1510154500,
            'deleted' => 0,
            'fullname' => 'my job',
            'organisationid' => '1',
            'positionid' => '1',
            'managerjaid' => '1',
            'managerjapath' => '/1',
            'appraiserid' => '5',
            'startdate' => '1510154510',
            'enddate' => '1510554500',
            'timecreated' => 1510154510,
            'usermodified' => '2',
            'positionassignmentdate' => '1476784550',
            'totarasync' => '1',
            'synctimemodified' => '0',
            'sortorder' => '1'
        );

        $this->loadDataSet($this->createArrayDataset(array(
            'job_assignment' => array($jobassignment)
        )));

        // Set the config.
        $config = array_merge($this->configcsv, $this->importfields());
        $this->set_source_config($config);
        $extraconfig = array(
            'updateidnumbers' => true,
            'csvsavemeptyfields' => false
        );
        $config = array_merge($this->config, $extraconfig);
        $this->set_element_config($config);

        $element = $this->get_element();

        $csv = "idnumber,useridnumber,timemodified,deleted,fullname,orgidnumber,posidnumber,manageridnumber,appraiseridnumber,startdate,enddate\n";
        $csv .= "2,11235,0,0,,,,,,,";
        $element->get_source()->set_csv_in_memory($csv);

        $this->assertTrue($element->sync()); // Run the sync.
        $this->assertCount(2, $DB->get_records('job_assignment')); // Check the correct number of Job assignments.

        $record = $DB->get_record('job_assignment', array('id' => 2));
        $this->assertEquals('my job', $record->fullname);
        $this->assertEquals('1', $record->organisationid);
        $this->assertEquals('1', $record->positionid);
    }

    /**
     * Test updating Job assignments with "Save empty fields" on and
     * there are empty fields.
     */
    public function test_sync_update_ja_emptyfields_setting_on_empty_fields() {
        global $DB;

        $jobassignment = array(
            'id' => 2,
            'idnumber' => 2,
            'userid' => 3,
            'timemodified' => 1510154500,
            'deleted' => 0,
            'fullname' => 'my job',
            'organisationid' => '1',
            'positionid' => '1',
            'managerjaid' => '1',
            'managerjapath' => '/1',
            'appraiserid' => '5',
            'startdate' => '1510154510',
            'enddate' => '1510554500',
            'timecreated' => 1510154510,
            'usermodified' => '2',
            'positionassignmentdate' => '1476784550',
            'totarasync' => '1',
            'synctimemodified' => '0',
            'sortorder' => '1'
        );

        $this->loadDataSet($this->createArrayDataset(array(
            'job_assignment' => array($jobassignment)
        )));

        // Set the config.
        $config = array_merge($this->configcsv, $this->importfields());
        $this->set_source_config($config);
        $extraconfig = array(
            'updateidnumbers' => true,
            'csvsaveemptyfields' => true,
            'previouslylinkedonjobassignmentidnumber' => 0
        );
        $config = array_merge($this->config, $extraconfig);
        $this->set_element_config($config);

        $element = $this->get_element();

        $csv = "idnumber,useridnumber,timemodified,deleted,fullname,orgidnumber,posidnumber,manageridnumber,appraiseridnumber,startdate,enddate\n";
        $csv .= "2,11235,0,0,,,,,,,";
        $element->get_source()->set_csv_in_memory($csv);

        $this->assertTrue($element->sync()); // Run the sync.
        $this->assertCount(2, $DB->get_records('job_assignment')); // Check the correct number of Job assignments.

        // We are saving empty fields so all these should be empty.
        $record = $DB->get_record('job_assignment', array('id' => 2));
        $this->assertEquals('', $record->fullname);
        $this->assertEquals(0, $record->organisationid);
        $this->assertEquals(0, $record->positionid);
        $this->assertEquals(0, $record->appraiserid);
        $this->assertEquals(0, $record->startdate);
        $this->assertEquals(0, $record->enddate);

        $this->assertEquals('/'.$jobassignment['id'], $record->managerjapath);
        $this->assertEquals(0, $record->managerjaid);
    }
}
