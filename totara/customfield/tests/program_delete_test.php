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
 * @author Valerii Kuznetsov <valerii.kuznetsov@totaralms.com>
 * @package totara_customfield
 */

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot . '/totara/program/lib.php');

class totara_customfield_program_delete_testcase extends advanced_testcase {

    protected $program1 = null;
    protected $program2 = null;

    protected function tearDown() {
        $this->program1 = null;
        $this->program2 = null;
        parent::tearDown();
    }

    public function setUp() {
        parent::setUp();

        // Create program customfields.
        $cfgenerator = $this->getDataGenerator()->get_plugin_generator('totara_customfield');
        $textids = $cfgenerator->create_text('prog', array('text1'));
        $multids = $cfgenerator->create_multiselect('prog', array('multi1'=>array('opt1', 'opt2')));

        // Create program 1.
        $programgenerator = $this->getDataGenerator()->get_plugin_generator('totara_program');
        $this->program1 = $programgenerator->create_program(array('fullname'=> 'Program 1'));
        // Add customfields data to program 1.
        $cfgenerator->set_text($this->program1, $textids['text1'], 'value1', 'program', 'prog');
        $cfgenerator->set_multiselect($this->program1, $multids['multi1'], array('opt1', 'opt2'), 'program', 'prog');

        // Create program 2.
        $this->program2 = $programgenerator->create_program(array('fullname'=> 'Program 2'));
        // Add customfields data to program 2.
        $cfgenerator->set_text($this->program2, $textids['text1'], 'value1', 'program', 'prog');
        $cfgenerator->set_multiselect($this->program2, $multids['multi1'], array('opt1', 'opt2'), 'program', 'prog');
    }

    /**
     * Test that customfield data removed with the program
     */
    public function test_customfield_deleted_on_event() {
        global $DB;
        $this->resetAfterTest();

        // Assert that records exist.
        $before = $DB->get_records('prog_info_data', array('programid' => $this->program1->id));
        $this->assertCount(2, $before);

        // Get data_param before deletion.
        list($sqlin, $paramin) = $DB->get_in_or_equal(array_keys($before));
        $parambefore = $DB->get_records_sql('SELECT id FROM {prog_info_data_param} WHERE dataid ' . $sqlin, $paramin);
        $this->assertCount(2, $parambefore);

        // Delete program 1.
        $program = new program($this->program1->id);
        $program->delete();

        // Check that data of customfields for program 1 are deleted.
        $afterc1 = $DB->get_records('prog_info_data', array('programid' => $this->program1->id));
        $this->assertCount(0, $afterc1);

        // Check that data of customfields for program 2 still exist.
        $afterc2 = $DB->get_records('prog_info_data', array('programid' => $this->program2->id));
        $this->assertCount(2, $afterc2);

        // Check that data_param of customfield for program 1 are deleted.
        $paramsafter = $DB->get_records_sql('SELECT id FROM {prog_info_data_param} WHERE dataid ' . $sqlin, $paramin);
        $this->assertEmpty($paramsafter);

        // Check that data_param of customfield for program 2 still exist.
        $program2data =  $DB->get_records('prog_info_data', array('programid' => $this->program2->id));
        list($sql2in, $param2in) = $DB->get_in_or_equal(array_keys($program2data));
        $program2dataparam = $DB->get_records_sql('SELECT id FROM {prog_info_data_param} WHERE dataid ' . $sql2in, $param2in);
        $this->assertCount(2, $program2dataparam);
    }

    /**
     * Test that upgrade will clean removed programs customfileds data
     */
    public function test_customfield_upgrade_remove_deleted() {
        global $DB, $CFG;
        $this->resetAfterTest();
        // Redirect events.
        $sink = $this->redirectEvents();

        // Delete program 2.
        $program = new program($this->program2->id);
        $program->delete();

        $sink->clear();

        // Check that data for program 2 exist (no event was triggered).
        $before = $DB->get_records('prog_info_data', array('programid' => $this->program2->id));
        $this->assertCount(2, $before);

        // Get data_param before upgrade.
        list($sqlin, $paramin) = $DB->get_in_or_equal(array_keys($before));
        $parambefore = $DB->get_records_sql('SELECT id FROM {prog_info_data_param} WHERE dataid ' . $sqlin, $paramin);
        $this->assertCount(2, $parambefore);
    }
}