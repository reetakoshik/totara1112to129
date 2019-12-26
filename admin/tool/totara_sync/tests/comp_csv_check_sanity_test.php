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
 * @author Simon Player <simon.player@totaralearning.com>
 * @package tool_totara_sync
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/admin/tool/totara_sync/lib.php');
require_once($CFG->dirroot . '/admin/tool/totara_sync/tests/source_csv_testcase.php');
require_once($CFG->dirroot . '/admin/tool/totara_sync/sources/source_comp_csv.php');

/**
 * @group tool_totara_sync
 */
class tool_totara_sync_comp_csv_check_sanity_testcase extends totara_sync_csv_testcase {

    protected $filedir      = null;
    protected $configcsv    = [];
    protected $config       = [];
    protected $elementname  = 'comp';
    protected $sourcename   = 'totara_sync_source_comp_csv';
    protected $source       = null;

    protected $comp_framework_data1 = [
        'id'                => 1,
        'fullname'          => 'Competency Framework 1',
        'shortname'         => 'CFW1',
        'idnumber'          => 'CF1',
        'description'       => 'Description 1',
        'sortorder'         => 1,
        'visible'           => 1,
        'hidecustomfields'  => 0,
        'timecreated'       => 1265963591,
        'timemodified'      => 1265963591,
        'usermodified'      => 2
    ];

    protected function tearDown() {
        $this->filedir                      = null;
        $this->configcsv                    = null;
        $this->config                       = null;
        $this->comp_framework_data1         = null;
        $this->type_data1                   = null;
        $this->source                       = null;
        parent::tearDown();
    }

    public function setUp() {
        global $CFG;

        parent::setUp();

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $this->source = new $this->sourcename();

        $this->filedir = $CFG->dataroot . '/totara_sync';
        mkdir($this->filedir . '/csv/ready', 0777, true);

        set_config('element_comp_enabled', 1, 'totara_sync');
        set_config('source_comp', 'totara_sync_source_comp_csv', 'totara_sync');
        set_config('fileaccess', FILE_ACCESS_DIRECTORY, 'totara_sync');
        set_config('filesdir', $this->filedir, 'totara_sync');

        // Create a Position framework.
        $this->loadDataSet($this->createArrayDataset([
            'comp_framework' => [$this->comp_framework_data1]
        ]));

        $this->configcsv = [
            'csvuserencoding'                   => 'UTF-8',
            'delimiter'                         => ',',
            'csvsaveemptyfields'                => true,

            'fieldmapping_idnumber'             => '',
            'fieldmapping_fullname'             => '',
            'fieldmapping_frameworkidnumber'    => '',
            'fieldmapping_timemodified'         => '',

            'fieldmapping_shortname'            => '',
            'fieldmapping_description'          => '',
            'fieldmapping_parentidnumber'       => '',
            'fieldmapping_typeidnumber'         => '',
            'fieldmapping_aggregationmethod'    => '',

            'import_shortname'                  => '0',
            'import_description'                => '0',
            'import_parentidnumber'             => '0',
            'import_typeidnumber'               => '0',
            'import_frameworkidnumber'          => '1',
            'import_aggregationmethod'          => '1'
        ];
        $this->config = [
            'sourceallrecords'  => '1',
            'allow_create'      => '1',
            'allow_delete'      => '0',
            'allow_update'      => '1',
        ];
    }

    public function importfields() {
        $importfields = [];
        $importfields['import_idnumber']            = 1;
        $importfields['import_fullname']            = 1;
        $importfields['import_shortname']           = 0;
        $importfields['import_frameworkidnumber']   = 1;
        $importfields['import_timemodified']        = 1;
        $importfields['import_description']         = 0;
        $importfields['import_deleted']             = 0;
        $importfields['import_aggregationmethod']   = 1;

        return $importfields;
    }

    public function test_comp_csv_field_mappings_incorrect() {
        global $DB;

        $this->assertCount(0, $DB->get_records('comp'));

        // Set the element config.
        $this->set_element_config($this->config);

        // Using a mapping of fullname to name.
        $additional_fields = ['fieldmapping_fullname' => 'compname'];
        $config = array_merge($this->configcsv, $this->importfields(), $additional_fields);
        $this->set_source_config($config);
        $this->add_csv('competencies_field_mapping_1.csv', 'comp');
        $error = '';
        try {
            $this->sync();
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
        $this->assertEquals("CSV file not valid, missing field \"fullname\" (mapping for \"compname\")", $error);
        $this->assertCount(0, $DB->get_records('comp'));
    }

    public function test_comp_csv_field_mappings_correct() {
        global $DB;

        $this->assertCount(0, $DB->get_records('comp'));

        // Set the element config.
        $this->set_element_config($this->config);

        // Using a mapping of fullname to name.
        $additional_fields = ['fieldmapping_fullname' => 'name'];
        $config = array_merge($this->configcsv, $this->importfields(), $additional_fields);
        $this->set_source_config($config);
        $this->add_csv('competencies_field_mapping_1.csv', 'comp');
        $this->sync();

        $records = $DB->get_records('comp');
        $this->assertCount(1, $records);
        $this->assertEquals('Competency 1', current($records)->fullname);
    }

}
