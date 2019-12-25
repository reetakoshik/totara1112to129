<?php
/*
 * This file is part of Totara LMS
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
 * @author Brendan Cox <brendan.cox@totaralearning.com>
 * @package tool_totara_sync
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/admin/tool/totara_sync/lib.php');
require_once($CFG->dirroot . '/admin/tool/totara_sync/elements/comp.php');
require_once($CFG->dirroot . '/admin/tool/totara_sync/sources/source_comp_csv.php');

/**
 * @group tool_totara_sync
 */
class tool_totara_sync_comp_partial_sync_testcase extends advanced_testcase {

    /* @var totara_sync_element_comp */
    private $element;

    protected $comp_framework_data1 = array(
        'id' => 1, 'fullname' => 'Competency Framework 1', 'shortname' => 'compf1', 'idnumber' => 'OFW1', 'description' => 'Description 1',
        'sortorder' => 1, 'visible' => 1, 'hidecustomfields' => 0, 'timecreated' => 1265963591, 'timemodified' => 1265963591, 'usermodified' => 2,
    );

    protected $comp_data1 = array(
        'id' => 1, 'fullname' => 'Competency 1', 'shortname' => 'comp1', 'idnumber' => '111', 'description' => 'Competency 1', 'frameworkid' => 1,
        'path' => '/1', 'depthlevel' => 1, 'parentid' => 0, 'sortthread' => '01', 'visible' => 1, 'timevalidfrom' => 0, 'timevalidto' => 0,
        'timecreated' => 0, 'timemodified' => 0, 'usermodified' => 2, 'totarasync' => 1, 'aggregationmethod' => 1, 'proficiencyexpected' => 1,
        'evidencecount' => 0,
    );

    protected $comp_data2 = array(
        'id' => 2, 'fullname' => 'Competency 2', 'shortname' => 'comp2', 'idnumber' => '222', 'description' => 'Competency 2', 'frameworkid' => 1,
        'path' => '/2', 'depthlevel' => 1, 'parentid' => 0, 'sortthread' => '02', 'visible' => 1, 'timevalidfrom' => 0, 'timevalidto' => 0,
        'timecreated' => 0, 'timemodified' => 0, 'usermodified' => 2, 'totarasync' => 1, 'aggregationmethod' => 1, 'proficiencyexpected' => 1,
        'evidencecount' => 0,
    );

    protected $comp_data3 = array(
        'id' => 3, 'fullname' => 'Competency 3', 'shortname' => 'comp3', 'idnumber' => '333', 'description' => 'Competency 3', 'frameworkid' => 1,
        'path' => '/3', 'depthlevel' => 1, 'parentid' => 0, 'sortthread' => '03', 'visible' => 1, 'timevalidfrom' => 0, 'timevalidto' => 0,
        'timecreated' => 0, 'timemodified' => 0, 'usermodified' => 2, 'totarasync' => 1, 'aggregationmethod' => 1, 'proficiencyexpected' => 1,
        'evidencecount' => 0,
    );

    protected $comp_data4 = array(
        'id' => 4, 'fullname' => 'Competency 4', 'shortname' => 'comp4', 'idnumber' => '444', 'description' => 'Competency 4', 'frameworkid' => 1,
        'path' => '/4', 'depthlevel' => 1, 'parentid' => 0, 'sortthread' => '04', 'visible' => 1, 'timevalidfrom' => 0, 'timevalidto' => 0,
        'timecreated' => 0, 'timemodified' => 0, 'usermodified' => 2, 'totarasync' => 1, 'aggregationmethod' => 1, 'proficiencyexpected' => 1,
        'evidencecount' => 0,
    );

    protected function tearDown() {
        $this->element = null;
        $this->comp_framework_data1 = null;
        $this->comp_data1 = null;
        $this->comp_data2 = null;
        $this->comp_data3 = null;
        $this->comp_data4 = null;
        parent::tearDown();
    }

    public function setUp() {
        parent::setUp();

        $this->resetAfterTest(true);
        $this->setAdminUser();

        set_config('element_comp_enabled', 1, 'totara_sync');
        set_config('source_comp', 'totara_sync_source_comp_csv', 'totara_sync');
        set_config('fileaccess', TOTARA_SYNC_FILE_ACCESS_MEMORY, 'totara_sync');

        // Create a Position framework.
        $this->loadDataSet($this->createArrayDataset(array(
            'comp_framework' => array($this->comp_framework_data1),
            'comp' => array($this->comp_data1, $this->comp_data2, $this->comp_data3, $this->comp_data4)
        )));

        $sourceconfig = array(
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
            'import_aggregationmethod' => '1',
            'import_idnumber' => '1',
            'import_fullname' => '1',
            'import_timemodified' => '1',

            // Customfields.
            'fieldmapping_customfield_textinput' => '',
            'import_customfield_textinput' => '0',

        );
        $this->set_config($sourceconfig, 'totara_sync_source_comp_csv');

        $this->element = new totara_sync_element_comp();
        $this->element->source = new totara_sync_source_comp_csv();

        $this->element->set_config('allow_create', 1);
        $this->element->set_config('allow_delete', 1);
        $this->element->set_config('allow_update', 1);
    }

    public function set_config($config, $plugin) {
        foreach ($config as $k => $v) {
            set_config($k, $v, $plugin);
        }
    }

    public function test_file_contains_all_records() {
        global $DB;

        $this->element->set_config('sourceallrecords', '1');

        $csv = "idnumber,fullname,frameworkidnumber,timemodified,aggregationmethod\n";
        $csv .= "111,Competency 1,OFW1,0,1\n";
        $csv .= "222,Competency 2,OFW1,0,1\n";
        $csv .= "333,Competency 3,OFW1,0,1";
        $this->element->source->set_csv_in_memory($csv);

        $this->assertCount(4, $DB->get_records('comp', array('frameworkid' => $this->comp_framework_data1['id']))); // Initially we should have 4 Competencies.
        $this->assertTrue($this->element->sync()); // Run the sync.

        $competencies = $DB->get_records('comp', array('frameworkid' => $this->comp_framework_data1['id']));
        $this->assertCount(3, $competencies);

        // Check the records that exist are the ones we expect.
    }

    public function test_file_doesnt_contain_all_records() {
        global $DB;

        $this->element->source->set_config('import_deleted', '1');
        $this->element->set_config('sourceallrecords', '0');

        $csv = "idnumber,fullname,deleted,frameworkidnumber,timemodified,aggregationmethod\n";
        $csv .= "111,Competency 1,0,OFW1,0,1\n";
        $csv .= "222,Competency 2,1,OFW1,0,1\n";
        $csv .= "333,Competency 3,0,OFW1,0,1";
        $this->element->source->set_csv_in_memory($csv);

        $this->assertCount(4, $DB->get_records('comp', array('frameworkid' => $this->comp_framework_data1['id']))); // Initially we should have 4 users.
        $this->assertTrue($this->element->sync()); // Run the sync.

        $competencies = $DB->get_records('comp', array('frameworkid' => $this->comp_framework_data1['id']));
        $this->assertCount(3, $competencies); // After the sync we should have 3 competencies.

        // Check the records that exist are the ones we expect.
        $this->assertEquals($this->comp_data1['fullname'], $competencies['1']->fullname);
        $this->assertSame($this->comp_data3['fullname'], $competencies['3']->fullname);
        $this->assertSame($this->comp_data4['fullname'], $competencies['4']->fullname);
        $this->assertArrayNotHasKey('2', $competencies);
    }

    public function test_parial_sync_update_records() {
        global $DB;

        $this->element->source->set_config('import_deleted', '1');
        $this->element->set_config('sourceallrecords', '0');

        $csv = "idnumber,fullname,deleted,frameworkidnumber,timemodified,aggregationmethod\n";
        $csv .= "111,Competency 1 Updated,0,OFW1,0,1\n";
        $csv .= "222,Competency 2 Updated,1,OFW1,0,1\n";
        $csv .= "333,Competency 3,0,OFW1,0,1";
        $this->element->source->set_csv_in_memory($csv);

        $this->assertCount(4, $DB->get_records('comp', array('frameworkid' => $this->comp_framework_data1['id']))); // Initially we should have 4 users.
        $this->assertTrue($this->element->sync()); // Run the sync.

        $competencies = $DB->get_records('comp', array('frameworkid' => $this->comp_framework_data1['id']));
        $this->assertCount(3, $competencies); // After the sync we should have 3 competencies.

        // Check the records that exist are the ones we expect and the update has been made correctly.
        $this->assertEquals('Competency 1 Updated', $competencies['1']->fullname);
        $this->assertSame('Competency 3', $competencies['3']->fullname);
        $this->assertSame('Competency 4', $competencies['4']->fullname);
        $this->assertArrayNotHasKey('2', $competencies);
    }

    public function test_full_sync_update_including_delete() {
        global $DB;

        $this->element->set_config('sourceallrecords', '1');

        $csv = "idnumber,fullname,deleted,frameworkidnumber,timemodified,aggregationmethod\n";
        $csv .= "111,Competency 1 Updated,0,OFW1,0,1\n";
        $csv .= "222,Competency 2 Updated,1,OFW1,0,1\n";
        $csv .= "333,Competency 3,0,OFW1,0,1";
        $this->element->source->set_csv_in_memory($csv);

        // Initially we should have 4 users.
        $this->assertCount(4, $DB->get_records('comp', array('frameworkid' => $this->comp_framework_data1['id'])));
        $this->assertTrue($this->element->sync()); // Run the sync.

        $this->assertCount(3, $DB->get_records('comp', array('frameworkid' => $this->comp_framework_data1['id'])));
    }
}

