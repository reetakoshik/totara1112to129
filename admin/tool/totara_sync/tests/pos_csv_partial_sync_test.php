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
class tool_totara_sync_pos_partial_sync_testcase extends advanced_testcase {

    private $filedir = null;
    private $configcsv = array();
    private $config = array();

    protected $pos_framework_data1 = array(
        'id' => 1, 'fullname' => 'Postion Framework 1', 'shortname' => 'posf1', 'idnumber' => 'PFW1', 'description' => 'Description 1',
        'sortorder' => 1, 'visible' => 1, 'hidecustomfields' => 0, 'timecreated' => 1265963591, 'timemodified' => 1265963591, 'usermodified' => 2,
    );

    protected $pos_data1 = array(
        'id' => 1, 'fullname' => 'Position 1', 'shortname' => 'pos1', 'idnumber' => '111', 'description' => 'Position 1', 'frameworkid' => 1,
        'path' => '/1', 'depthlevel' => 1, 'parentid' => 0, 'sortthread' => '01', 'visible' => 1, 'timevalidfrom' => 0, 'timevalidto' => 0,
        'timecreated' => 0, 'timemodified' => 0, 'usermodified' => 2, 'totarasync' => 1
    );

    protected $pos_data2 = array(
        'id' => 2, 'fullname' => 'Position 2', 'shortname' => 'pos2', 'idnumber' => '222', 'description' => 'Position 2', 'frameworkid' => 1,
        'path' => '/2', 'depthlevel' => 1, 'parentid' => 0, 'sortthread' => '02', 'visible' => 1, 'timevalidfrom' => 0, 'timevalidto' => 0,
        'timecreated' => 0, 'timemodified' => 0, 'usermodified' => 2, 'totarasync' => 1
    );

    protected $pos_data3 = array(
        'id' => 3, 'fullname' => 'Position 3', 'shortname' => 'pos3', 'idnumber' => '333', 'description' => 'Position 3', 'frameworkid' => 1,
        'path' => '/3', 'depthlevel' => 1, 'parentid' => 0, 'sortthread' => '03', 'visible' => 1, 'timevalidfrom' => 0, 'timevalidto' => 0,
        'timecreated' => 0, 'timemodified' => 0, 'usermodified' => 2, 'totarasync' => 1
    );

    protected $pos_data4 = array(
        'id' => 4, 'fullname' => 'Position 4', 'shortname' => 'pos4', 'idnumber' => '444', 'description' => 'Position 4', 'frameworkid' => 1,
        'path' => '/4', 'depthlevel' => 1, 'parentid' => 0, 'sortthread' => '04', 'visible' => 1, 'timevalidfrom' => 0, 'timevalidto' => 0,
        'timecreated' => 0, 'timemodified' => 0, 'usermodified' => 2, 'totarasync' => 1
    );

    protected function tearDown() {
        $this->filedir = null;
        $this->configcsv = null;
        $this->config = null;
        $this->pos_framework_data1 = null;
        $this->pos_data1 = null;
        $this->pos_data2 = null;
        $this->pos_data3 = null;
        $this->pos_data4 = null;
        parent::tearDown();
    }

    public function setUp() {
        global $CFG;

        parent::setUp();

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $this->filedir = $CFG->dataroot . '/totara_sync';
        mkdir($this->filedir . '/csv/ready', 0777, true);

        set_config('element_pos_enabled', 1, 'totara_sync');
        set_config('source_pos', 'totara_sync_source_pos_csv', 'totara_sync');
        set_config('fileaccess', FILE_ACCESS_DIRECTORY, 'totara_sync');
        set_config('filesdir', $this->filedir, 'totara_sync');

        // Create a Position framework.
        $this->loadDataSet($this->createArrayDataset(array(
            'pos_framework' => array($this->pos_framework_data1),
            'pos' => array($this->pos_data1, $this->pos_data2, $this->pos_data3, $this->pos_data4)
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
        /** @var totara_sync_element_pos $element */
        return $elements['pos'];
    }

    public function test_file_contains_all_records() {
        global $DB;

        // Set the config.
        $config = array_merge($this->configcsv, $this->importfields());
        $this->set_config($config, 'totara_sync_source_pos_csv');
        $config = array_merge($this->config, array('sourceallrecords' => 1));
        $this->set_config($config, 'totara_sync_element_pos');

        // Import file that doesn't contain all records,
        // the missing records should be removed.
        $data = file_get_contents(__DIR__ . '/fixtures/pos_partial_sync_1.csv');
        $filepath = $this->filedir . '/csv/ready/pos.csv';
        file_put_contents($filepath, $data);

        $this->assertCount(4, $DB->get_records('pos', array('frameworkid' => $this->pos_framework_data1['id']))); // Initially we should have 4 positions.
        $this->assertTrue($this->get_element()->sync()); // Run the sync.

        $positions = $DB->get_records('pos', array('frameworkid' => $this->pos_framework_data1['id']));
        $this->assertCount(3, $positions);

        // Check the records that exist are the ones we expect.
    }


    public function test_file_doesnt_contain_all_records() {
        global $DB;

        $extraimportfields = array(
            'import_deleted' => '1'
        );

        // Set the config.
        $config = array_merge($this->configcsv, $this->importfields(), $extraimportfields);
        $this->set_config($config, 'totara_sync_source_pos_csv');
        $config = array_merge($this->config, array('sourceallrecords' => 0));
        $this->set_config($config, 'totara_sync_element_pos');

        // Import file that doesn't contain all records,
        // the missing records should be removed.
        $data = file_get_contents(__DIR__ . '/fixtures/pos_partial_sync_2.csv');
        $filepath = $this->filedir . '/csv/ready/pos.csv';
        file_put_contents($filepath, $data);

        $this->assertCount(4, $DB->get_records('pos', array('frameworkid' => $this->pos_framework_data1['id']))); // Initially we should have 4 users.
        $this->assertTrue($this->get_element()->sync()); // Run the sync.

        $positions = $DB->get_records('pos', array('frameworkid' => $this->pos_framework_data1['id']));
        $this->assertCount(3, $positions); // After the sync we should have 3 positions.

        // Check the records that exist are the ones we expect.
        $this->assertEquals($this->pos_data1['fullname'], $positions['1']->fullname);
        $this->assertSame($this->pos_data3['fullname'], $positions['3']->fullname);
        $this->assertSame($this->pos_data4['fullname'], $positions['4']->fullname);
        $this->assertArrayNotHasKey('2', $positions);
    }


    public function test_parial_sync_update_records() {
        global $DB;

        $extraimportfields = array(
            'import_deleted' => '1'
        );

        // Set the config.
        $config = array_merge($this->configcsv, $this->importfields(), $extraimportfields);
        $this->set_config($config, 'totara_sync_source_pos_csv');
        $config = array_merge($this->config, array('sourceallrecords' => 0));
        $this->set_config($config, 'totara_sync_element_pos');

        // Import file that doesn't contain all records,
        // the missing records should be removed.
        $data = file_get_contents(__DIR__ . '/fixtures/pos_partial_sync_3.csv');
        $filepath = $this->filedir . '/csv/ready/pos.csv';
        file_put_contents($filepath, $data);

        $this->assertCount(4, $DB->get_records('pos', array('frameworkid' => $this->pos_framework_data1['id']))); // Initially we should have 4 users.
        $this->assertTrue($this->get_element()->sync()); // Run the sync.

        $positions = $DB->get_records('pos', array('frameworkid' => $this->pos_framework_data1['id']));
        $this->assertCount(3, $positions); // After the sync we should have 3 positions.

        // Check the records that exist are the ones we expect and the update has been made successfully.
        $this->assertEquals('Position 1 Updated', $positions['1']->fullname);
        $this->assertSame('Position 3', $positions['3']->fullname);
        $this->assertSame('Position 4', $positions['4']->fullname);
        $this->assertArrayNotHasKey('2', $positions);
    }

    public function test_full_sync_update_including_delete() {
        global $DB;

        // Set config
        $config = array_merge($this->configcsv, $this->importfields());
        $this->set_config($config, 'totara_sync_source_pos_csv');
        $config = array_merge($this->config, array('sourceallrecords' => 1));
        $this->set_config($config, 'totara_sync_element_pos');

        // Import file that doesn't contain all records,
        // the missing records should be removed.
        $data = file_get_contents(__DIR__ . '/fixtures/pos_partial_sync_3.csv');
        $filepath = $this->filedir . '/csv/ready/pos.csv';
        file_put_contents($filepath, $data);

        // Initially we should have 4 users.
        $this->assertCount(4, $DB->get_records('pos', array('frameworkid' => $this->pos_framework_data1['id'])));
        $this->assertTrue($this->get_element()->sync()); // Run the sync.

        $this->assertCount(3, $DB->get_records('pos', array('frameworkid' => $this->pos_framework_data1['id'])));
    }
}

