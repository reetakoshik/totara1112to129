<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2016 onwards Totara Learning Solutions LTD
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
 * @author Simon Player <simon.player@totaralms.com>
 * @package totara_customfield
 */

defined('MOODLE_INTERNAL') || die();

class totara_customfield_evidence_delete_testcase extends advanced_testcase {

    protected $evidence1 = null;
    protected $evidence2 = null;
    protected $evidence3 = null;

    protected $multiselect = [];
    protected $text = [];
    protected $files = [];

    protected function tearDown() {
        $this->evidence1 = $this->evidence2 = $this->evidence3 = null;
        $this->multiselect = [];
        $this->text = [];
        $this->files = [];

        parent::tearDown();
    }

    public function setUp() {
        parent::setUp();

        $user = $this->getDataGenerator()->create_user();

        // Set user for file handling.
        $this->setUser($user->id);

        // Create evidence customfields.
        $cfgenerator = $this->getDataGenerator()->get_plugin_generator('totara_customfield');
        $this->text = $cfgenerator->create_text('dp_plan_evidence', ['text1']);
        $this->multiselect = $cfgenerator->create_multiselect('dp_plan_evidence', ['multi1' => ['opt1', 'opt2']]);
        $this->files = $cfgenerator->create_file('dp_plan_evidence', ['file1' => ['shortname' => 'f1'], 'file2' => ['shortname' => 'f2']]);

        $plangenerator = $this->getDataGenerator()->get_plugin_generator('totara_plan');
        $evidencetype = $plangenerator->create_evidence_type();

        // Create evidence 1.
        $this->evidence1 = $plangenerator->create_evidence(['evidencetypeid' => $evidencetype->id, 'userid' => $user->id]);

        // Add customfields data to evidence 1.
        $cfgenerator->set_text($this->evidence1, $this->text['text1'], 'value1', 'evidence', 'dp_plan_evidence');
        $cfgenerator->set_multiselect($this->evidence1, $this->multiselect['multi1'], ['opt1', 'opt2'], 'evidence', 'dp_plan_evidence');

        // Create evidence 2.
        $this->evidence2 = $plangenerator->create_evidence(['evidencetypeid' => $evidencetype->id, 'userid' => $user->id]);

        // Add customfields data to evidence 2.
        $cfgenerator->set_text($this->evidence2, $this->text['text1'], 'value1', 'evidence', 'dp_plan_evidence');
        $cfgenerator->set_multiselect($this->evidence2, $this->multiselect['multi1'], ['opt1', 'opt2'], 'evidence', 'dp_plan_evidence');
        $cfgenerator->create_test_file_from_content('testfile1.txt', 'Test file content 1', 1);
        $cfgenerator->set_file($this->evidence2, $this->files['file1'], 1, 'evidence', 'dp_plan_evidence');

        // Create evidence 3.
        $this->evidence3 = $plangenerator->create_evidence(['evidencetypeid' => $evidencetype->id, 'userid' => $user->id]);

        // Add customfields data to evidence 3.
        $cfgenerator->create_test_file_from_content('testfile2.txt', 'Test file content 2', 2);
        $cfgenerator->set_file($this->evidence3, $this->files['file2'], 2, 'evidence', 'dp_plan_evidence');
    }

    /**
     * Test that customfield data removed with the evidence
     */
    public function test_customfield_deleted_on_event() {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/totara/plan/record/evidence/lib.php');

        $this->resetAfterTest();

        // Assert that records exist.
        $before = $DB->get_records('dp_plan_evidence_info_data', array('evidenceid' => $this->evidence1->id));
        $this->assertCount(2, $before);

        // Get data_param before deletion.
        list($sqlin, $paramin) = $DB->get_in_or_equal(array_keys($before));
        $parambefore = $DB->get_records_sql('SELECT id FROM {dp_plan_evidence_info_data_param} WHERE dataid ' . $sqlin, $paramin);
        $this->assertCount(2, $parambefore);

        // Delete evidence 1.
        evidence_delete($this->evidence1->id);

        // Check that data of customfields for evidence 1 are deleted.
        $afterc1 = $DB->get_records('dp_plan_evidence_info_data', array('evidenceid' => $this->evidence1->id));
        $this->assertCount(0, $afterc1);

        // Check that data of customfields for evidence 2 still exist.
        $afterc2 = $DB->get_records('dp_plan_evidence_info_data', array('evidenceid' => $this->evidence2->id));
        $this->assertCount(3, $afterc2);

        // Check that data_param of customfield for evidence 1 are deleted.
        $paramsafter = $DB->get_records_sql('SELECT id FROM {dp_plan_evidence_info_data_param} WHERE dataid ' . $sqlin, $paramin);
        $this->assertEmpty($paramsafter);

        // Check that data_param of customfield for evidence 2 still exist.
        $program2data =  $DB->get_records('dp_plan_evidence_info_data', array('evidenceid' => $this->evidence2->id));
        list($sql2in, $param2in) = $DB->get_in_or_equal(array_keys($program2data));
        $evidence2dataparam = $DB->get_records_sql('SELECT id FROM {dp_plan_evidence_info_data_param} WHERE dataid ' . $sql2in, $param2in);
        $this->assertCount(2, $evidence2dataparam);

        // Check that the files for evidence 2 and 3 still exist.
        $file1 = $DB->get_field('dp_plan_evidence_info_data', 'id', ['evidenceid' => $this->evidence2->id, 'fieldid' => $this->files['file1']]);
        $this->assertEquals(1, $DB->count_records('files', ['filearea' => 'evidence_filemgr', 'filename' => 'testfile1.txt', 'itemid' => $file1]));
        $file2 = $DB->get_field('dp_plan_evidence_info_data', 'id', ['evidenceid' => $this->evidence3->id, 'fieldid' => $this->files['file2']]);
        $this->assertEquals(1, $DB->count_records('files', ['filearea' => 'evidence_filemgr', 'filename' => 'testfile2.txt', 'itemid' => $file2]));

        // Delete evidence 3.
        evidence_delete($this->evidence3->id);

        // Check that evidence 3 file was deleted, but evidence 2 file remains.
        $afterc3 = $DB->get_records('dp_plan_evidence_info_data', ['evidenceid' => $this->evidence3->id]);
        $this->assertCount(0, $afterc3);
        $this->assertEquals(0, $DB->count_records('files', ['filearea' => 'evidence_filemgr', 'filename' => 'testfile2.txt', 'itemid' => $file2]));
        $this->assertEquals(1, $DB->count_records('files', ['filearea' => 'evidence_filemgr', 'filename' => 'testfile1.txt', 'itemid' => $file1]));
    }
}
