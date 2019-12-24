<?php
/*
 * This file is part of Totara LMS
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
 * @author Alastair Munro <alastair.munro@totaralearning.com>
 * @package totara
 * @subpackage totara_sync
 *
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/admin/tool/totara_sync/lib.php');

/**
 * @group tool_totara_sync
 */
class tool_totara_sync_org_partial_sync_testcase extends advanced_testcase {

    private $filedir = null;
    private $configcsv = array();
    private $config = array();

    protected $org_framework_data1 = array(
        'id' => 1, 'fullname' => 'Organisation Framework 1', 'shortname' => 'orgf1', 'idnumber' => 'OFW1', 'description' => 'Description 1',
        'sortorder' => 1, 'visible' => 1, 'hidecustomfields' => 0, 'timecreated' => 1265963591, 'timemodified' => 1265963591, 'usermodified' => 2,
    );

    protected $org_data1 = array(
        'id' => 1, 'fullname' => 'Organisation 1', 'shortname' => 'org1', 'idnumber' => '111', 'description' => 'Organisation 1', 'frameworkid' => 1,
        'path' => '/1', 'depthlevel' => 1, 'parentid' => 0, 'sortthread' => '01', 'visible' => 1, 'timevalidfrom' => 0, 'timevalidto' => 0,
        'timecreated' => 0, 'timemodified' => 0, 'usermodified' => 2, 'totarasync' => 1
    );

    protected $org_data2 = array(
        'id' => 2, 'fullname' => 'Organisation 2', 'shortname' => 'org2', 'idnumber' => '222', 'description' => 'Organisation 2', 'frameworkid' => 1,
        'path' => '/2', 'depthlevel' => 1, 'parentid' => 0, 'sortthread' => '02', 'visible' => 1, 'timevalidfrom' => 0, 'timevalidto' => 0,
        'timecreated' => 0, 'timemodified' => 0, 'usermodified' => 2, 'totarasync' => 1
    );

    protected $org_data3 = array(
        'id' => 3, 'fullname' => 'Organisation 3', 'shortname' => 'org3', 'idnumber' => '333', 'description' => 'Organisation 3', 'frameworkid' => 1,
        'path' => '/3', 'depthlevel' => 1, 'parentid' => 0, 'sortthread' => '03', 'visible' => 1, 'timevalidfrom' => 0, 'timevalidto' => 0,
        'timecreated' => 0, 'timemodified' => 0, 'usermodified' => 2, 'totarasync' => 1
    );

    protected $org_data4 = array(
        'id' => 4, 'fullname' => 'Organisation 4', 'shortname' => 'org4', 'idnumber' => '444', 'description' => 'Organisation 4', 'frameworkid' => 1,
        'path' => '/4', 'depthlevel' => 1, 'parentid' => 0, 'sortthread' => '04', 'visible' => 1, 'timevalidfrom' => 0, 'timevalidto' => 0,
        'timecreated' => 0, 'timemodified' => 0, 'usermodified' => 2, 'totarasync' => 1
    );

    protected function tearDown() {
        $this->filedir = null;
        $this->configcsv = null;
        $this->config = null;
        $this->org_framework_data1 = null;
        $this->org_data1 = null;
        $this->org_data2 = null;
        $this->org_data3 = null;
        $this->org_data4 = null;
        parent::tearDown();
    }

    public function setUp() {
        global $CFG;

        parent::setUp();

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $this->filedir = $CFG->dataroot . '/totara_sync';
        mkdir($this->filedir . '/csv/ready', 0777, true);

        set_config('element_org_enabled', 1, 'totara_sync');
        set_config('source_org', 'totara_sync_source_org_csv', 'totara_sync');
        set_config('fileaccess', FILE_ACCESS_DIRECTORY, 'totara_sync');
        set_config('filesdir', $this->filedir, 'totara_sync');

        // Create a Position framework.
        $this->loadDataSet($this->createArrayDataset(array(
            'org_framework' => array($this->org_framework_data1),
            'org' => array($this->org_data1, $this->org_data2, $this->org_data3, $this->org_data4)
        )));

        $this->configcsv = array(
            'csvuserencoding' => 'UTF-8',
            'delimiter' => ',',
            'csvsaveemptyfields' => true,

            'fieldmapping_idnumber' => '',
            'fieldmapping_fullname' => '',
            'fieldmapping_frameworkidnumber' => '',
            'fieldmapping_timemodified' => '',

            'fieldmapping_shortname' => '',
            'fieldmapping_description' => '',
            'fieldmapping_parentidnumber' => '',
            'fieldmapping_typeidnumber' => '',

            'import_shortname' => '0',
            'import_description' => '0',
            'import_parentidnumber' => '0',
            'import_typeidnumber' => '0',
            'import_frameworkidnumber' => '1',
            'import_deleted' => '0',

            // Customfields.
            'fieldmapping_customfield_textinput' => '',
            'import_customfield_textinput' => '0',

        );
        $this->config = array(
            'allow_create' => '1',
            'allow_delete' => '1',
            'allow_update' => '1',
        );
    }

    public function set_config($config, $plugin) {
        foreach ($config as $k => $v) {
            set_config($k, $v, $plugin);
        }
    }


    public function importfields() {
        $importfields = array();

        $importfields['import_idnumber'] = 1;
        $importfields['import_fullname'] = 1;
        $importfields['import_frameworkidnumber'] = 1;
        $importfields['import_timemodified'] = 1;

        return $importfields;
    }

    public function get_element() {
        $elements = totara_sync_get_elements(true);
        /** @var totara_sync_element_org $element */
        return $elements['org'];
    }

    public function test_file_contains_all_records() {
        global $DB;

        // Set the config.
        $config = array_merge($this->configcsv, $this->importfields());
        $this->set_config($config, 'totara_sync_source_org_csv');
        $config = array_merge($this->config, array('sourceallrecords' => 1));
        $this->set_config($config, 'totara_sync_element_org');

        // Import file that doesn't contain all records,
        // the missing records should be removed.
        $data = file_get_contents(__DIR__ . '/fixtures/org_partial_sync_1.csv');
        $filepath = $this->filedir . '/csv/ready/org.csv';
        file_put_contents($filepath, $data);

        $this->assertCount(4, $DB->get_records('org', array('frameworkid' => $this->org_framework_data1['id']))); // Initially we should have 4 organisations.
        $this->assertTrue($this->get_element()->sync()); // Run the sync.

        $organisations = $DB->get_records('org', array('frameworkid' => $this->org_framework_data1['id']));
        $this->assertCount(3, $organisations);

        // Check the records that exist are the ones we expect.
    }


    public function test_file_doesnt_contain_all_records() {
        global $DB;

        $extraimportfields = array(
            'import_deleted' => '1'
        );

        // Set the config.
        $config = array_merge($this->configcsv, $this->importfields(), $extraimportfields);
        $this->set_config($config, 'totara_sync_source_org_csv');
        $config = array_merge($this->config, array('sourceallrecords' => 0));
        $this->set_config($config, 'totara_sync_element_org');

        // Import file that doesn't contain all records,
        // the missing records should be removed.
        $data = file_get_contents(__DIR__ . '/fixtures/org_partial_sync_2.csv');
        $filepath = $this->filedir . '/csv/ready/org.csv';
        file_put_contents($filepath, $data);

        $this->assertCount(4, $DB->get_records('org', array('frameworkid' => $this->org_framework_data1['id']))); // Initially we should have 4 users.
        $this->assertTrue($this->get_element()->sync()); // Run the sync.

        $organisations = $DB->get_records('org', array('frameworkid' => $this->org_framework_data1['id']));
        $this->assertCount(3, $organisations); // After the sync we should have 3 organisations.

        // Check the records that exist are the ones we expect.
        $this->assertEquals($this->org_data1['fullname'], $organisations['1']->fullname);
        $this->assertSame($this->org_data3['fullname'], $organisations['3']->fullname);
        $this->assertSame($this->org_data4['fullname'], $organisations['4']->fullname);
        $this->assertArrayNotHasKey('2', $organisations);
    }

    public function test_parial_sync_update_records() {
        global $DB;

        $extraimportfields = array(
            'import_deleted' => '1'
        );

        // Set the config.
        $config = array_merge($this->configcsv, $this->importfields(), $extraimportfields);
        $this->set_config($config, 'totara_sync_source_org_csv');
        $config = array_merge($this->config, array('sourceallrecords' => 0));
        $this->set_config($config, 'totara_sync_element_org');

        // Import file that doesn't contain all records,
        // the missing records should be removed.
        $data = file_get_contents(__DIR__ . '/fixtures/org_partial_sync_3.csv');
        $filepath = $this->filedir . '/csv/ready/org.csv';
        file_put_contents($filepath, $data);

        $this->assertCount(4, $DB->get_records('org', array('frameworkid' => $this->org_framework_data1['id']))); // Initially we should have 4 users.
        $this->assertTrue($this->get_element()->sync()); // Run the sync.

        $organisations = $DB->get_records('org', array('frameworkid' => $this->org_framework_data1['id']));
        $this->assertCount(3, $organisations); // After the sync we should have 3 organisations.

        // Check the records that exist are the ones we expect and the update has been made correctly.
        $this->assertEquals('Organisation 1 Updated', $organisations['1']->fullname);
        $this->assertSame('Organisation 3', $organisations['3']->fullname);
        $this->assertSame('Organisation 4', $organisations['4']->fullname);
        $this->assertArrayNotHasKey('2', $organisations);
    }

    public function test_full_sync_update_including_delete() {
        global $DB;

        // Set config
        $config = array_merge($this->configcsv, $this->importfields());
        $this->set_config($config, 'totara_sync_source_org_csv');
        $config = array_merge($this->config, array('sourceallrecords' => 1));
        $this->set_config($config, 'totara_sync_element_org');

        // Import file that doesn't contain all records,
        // the missing records should be removed.
        $data = file_get_contents(__DIR__ . '/fixtures/org_partial_sync_3.csv');
        $filepath = $this->filedir . '/csv/ready/org.csv';
        file_put_contents($filepath, $data);

        // Initially we should have 4 users.
        $this->assertCount(4, $DB->get_records('org', array('frameworkid' => $this->org_framework_data1['id'])));
        $this->assertTrue($this->get_element()->sync()); // Run the sync.

        $this->assertCount(3, $DB->get_records('org', array('frameworkid' => $this->org_framework_data1['id'])));
    }
}

