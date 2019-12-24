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
 * @package totara_plan
 */

use totara_plan\userdata\evidence;
use totara_userdata\userdata\item;
use totara_userdata\userdata\target_user;
use totara_userdata\userdata\export;

global $CFG;

defined('MOODLE_INTERNAL') || die();

/**
 * Testing evidence userdata item
 *
 * @group totara_plan
 * @group totara_userdata
 */
class totara_plan_userdata_evidence_test extends advanced_testcase {

    /**
     * Setup data used for purge, export and count functions
     *
     * @return stdClass Data for tests
     */
    private function setupdata() {
        global $DB;

        $data = new stdClass();

        $data->user1 = $this->getDataGenerator()->create_user();
        $data->user2 = $this->getDataGenerator()->create_user();

        $plan_generator = $this->getDataGenerator()->get_plugin_generator('totara_plan');

        $evidencetype_data = new stdClass();
        $evidencetype_data->name = 'Test evidence type';
        $evidencetype_data->description = 'Testing evidence types for fun';

        $data->evidencetype1 = $plan_generator->create_evidence_type($evidencetype_data);

        // Create a file to use for upload to description
        $fs = get_file_storage();
        $systemcontext = context_system::instance();

        $data->ev_description_file = (object)[
            'contextid' => $systemcontext->id,
            'component' => 'totara_plan',
            'filearea' => 'dp_evidence_type',
            'itemid' => $data->evidencetype1->id,
            'filepath' => '/',
            'filename' => 'testfile22.txt'
        ];
        $data->ev_description_file_info = $fs->create_file_from_string($data->ev_description_file, 'test description file');

        // Create custom fields for evidence.
        $customfield_generator = $this->getDataGenerator()->get_plugin_generator('totara_customfield');
        $text_data = ['Text field 1'];
        $data->evidence_text = $customfield_generator->create_text('dp_plan_evidence', $text_data);
        $textarea_data = ['Text area 1'];
        $data->evidence_textarea = $customfield_generator->create_textarea('dp_plan_evidence', $textarea_data);
        $multiselect_data = ['Multi select 1' => ['shortname' => 'ms1', 'opt1' => 'Option 1', 'opt2' => 'Option 2', 'opt3' => 'Option 3']];
        $data->evidence_multi = $customfield_generator->create_multiselect('dp_plan_evidence', $multiselect_data);
        $datetime_data = ['Date time 1' => ['startyear' => '1995', 'endyear' => '2030', 'shortname' => 'dt1']];
        $data->evidence_datetime = $customfield_generator->create_datetime('dp_plan_evidence', $datetime_data);
        $location_data = ['Location 1' => ['shortname' => 'loc1', 'latitude' => '50.828831', 'longitude' => '-0.156095']];
        $data->evidence_location = $customfield_generator->create_location('dp_plan_evidence', $location_data);
        $file_data = ['File 1' => ['shortname' => 'f1']];
        $data->evidence_file = $customfield_generator->create_file('dp_plan_evidence', $file_data);

        $evidence_data1 = new stdClass();
        $evidence_data1->userid = $data->user1->id;
        $evidence_data1->evidencetypeid = $data->evidencetype1->id;
        $data->evidenceitem1 = $plan_generator->create_evidence($evidence_data1);

        $evidence_data2 = new stdClass();
        $evidence_data2->userid = $data->user2->id;
        $evidence_data2->evidencetypeid = $data->evidencetype1->id;
        $data->evidenceitem2 = $plan_generator->create_evidence($evidence_data2);

        $evidence_data3 = new stdClass();
        $evidence_data3->userid = $data->user2->id;
        $evidence_data3->evidencetypeid = $data->evidencetype1->id;
        $data->evidenceitem3 = $plan_generator->create_evidence($evidence_data3);

        // Add customfield data to evidence item.
        $customfield_generator->set_text($data->evidenceitem1, $data->evidence_text[reset($text_data)], 'Testing evidence text cf', 'evidence', 'dp_plan_evidence');
        $customfield_generator->set_textarea($data->evidenceitem1, $data->evidence_textarea[reset($textarea_data)], 'Testing evidence textarea cf', 'evidence', 'dp_plan_evidence');
        $customfield_generator->set_multiselect($data->evidenceitem1, $data->evidence_multi[array_keys($multiselect_data)[0]], ['Option 1'], 'evidence', 'dp_plan_evidence');
        $customfield_generator->set_datetime($data->evidenceitem1, $data->evidence_datetime[array_keys($datetime_data)[0]], '1519127185', 'evidence', 'dp_plan_evidence');
        $customfield_generator->set_location_address($data->evidenceitem1, $data->evidence_location[array_keys($location_data)[0]], 'Rodhus (back entrance), Freehold Terrace, Brighton BN2 4AB', 'evidence', 'dp_plan_evidence');

        $this->setUser($data->user1);

        $fileid = 42;
        $data->file = $customfield_generator->create_test_file_from_content('testfile.txt', 'somefilecontent', $fileid);
        $customfield_generator->set_file($data->evidenceitem1, $data->evidence_file[array_keys($file_data)[0]], $fileid, 'evidence', 'dp_plan_evidence');

        $infodata_attachment = $DB->get_record('dp_plan_evidence_info_data',
                        ['evidenceid' => $data->evidenceitem1->id, 'fieldid' => $data->evidence_textarea['Text area 1']]);

        $textareaid = $data->evidence_textarea['Text area 1'];

        $file1 = (object)[
            'contextid' => $systemcontext->id,
            'component' => 'totara_customfield',
            'filearea' => 'evidence',
            'itemid' => $infodata_attachment->id,
            'filepath' => '/',
            'filename' => 'testfile42.txt'
        ];
        $file2 = (object)[
            'contextid' => $systemcontext->id,
            'component' => 'totara_customfield',
            'filearea' => 'evidence',
            'itemid' => 0042,
            'filepath' => '/',
            'filename' => 'testfile74.txt'
        ];
        $fs->create_file_from_string($file1, 'testfile');
        $fs->create_file_from_string($file2, 'testfile');

        return $data;
    }

    /**
     * Test function is_purgeable
     */
    public function test_is_purgeable() {
        $this->assertTrue(evidence::is_purgeable(target_user::STATUS_ACTIVE));
        $this->assertTrue(evidence::is_purgeable(target_user::STATUS_SUSPENDED));
        $this->assertTrue(evidence::is_purgeable(target_user::STATUS_DELETED));
    }

    /**
     * Test purging function
     */
    public function test_purge () {
        global $DB;

        $this->resetAfterTest();

        $data = $this->setupdata();

        $infodata_file = $DB->get_record('dp_plan_evidence_info_data',
                        ['evidenceid' => $data->evidenceitem1->id, 'fieldid' => $data->evidence_file['File 1']]);

        $infodata_attachment = $DB->get_record('dp_plan_evidence_info_data',
                        ['evidenceid' => $data->evidenceitem1->id, 'fieldid' => $data->evidence_textarea['Text area 1']]);

        // Check we have data to purge.
        $target_user1 = new target_user($data->user1);

        $this->assertEquals(1, $DB->count_records('dp_plan_evidence', ['userid' => $data->user1->id]));
        $this->assertEquals(2, $DB->count_records('dp_plan_evidence', ['userid' => $data->user2->id]));

        $this->assertEquals(1, $DB->count_records('files', ['filearea' => 'evidence_filemgr', 'filename' => 'testfile.txt', 'itemid' => $infodata_file->id]));
        $this->assertEquals(1, $DB->count_records('files', ['filearea' => 'evidence', 'filename' => 'testfile42.txt', 'itemid' => $infodata_attachment->id]));
        $this->assertEquals(1, $DB->count_records('files', ['filearea' => 'dp_evidence_type', 'filename' => 'testfile22.txt', 'itemid' => $data->evidenceitem1->evidencetypeid]));

        $result = evidence::purge($target_user1, context_system::instance());
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $result);

        // Check we purged the right stuff.
        $this->assertEquals(0, $DB->count_records('dp_plan_evidence', ['userid' => $data->user1->id]));
        $this->assertEquals(2, $DB->count_records('dp_plan_evidence', ['userid' => $data->user2->id]));

        // Test file has been removed
        $this->assertEquals(0, $DB->count_records('files', ['filearea' => 'evidence_filemgr', 'filename' => 'testfile.txt', 'itemid' => $infodata_file->id]));
        $this->assertEquals(0, $DB->count_records('files', ['filearea' => 'evidence', 'filename' => 'testfile42.txt', 'itemid' => $infodata_file->id]));

        // Make sure we didn't remove the evidence type file.
        $this->assertEquals(1, $DB->count_records('files', ['filearea' => 'dp_evidence_type', 'filename' => 'testfile22.txt', 'itemid' => $data->evidenceitem1->evidencetypeid]));
    }

    /**
     * Test function is_exportable
     */
    public function test_is_exportable() {
        $this->assertTrue(evidence::is_exportable());
    }

    /**
     * Test the export function
     */
    public function test_export() {
        $this->resetAfterTest();

        $data = $this->setupdata();

        $target_user = new target_user($data->user1);
        $export = evidence::execute_export($target_user, context_system::instance());

        $this->assertCount(1, $export->data);
        $this->assertEquals('Evidence 1', reset($export->data)->name);
        $this->assertEquals('Testing evidence text cf', reset($export->data)->{'Text field 1'});
        $this->assertEquals('1519127185', reset($export->data)->{'Date time 1'});

        $this->assertCount(3, $export->files);

        // Check the files.
        $filedata_records = reset($export->data)->files;
        foreach ($filedata_records as $record) {
            $this->assertEquals($record['filename'], $export->files[$record['fileid']]->get_filename());
        }
    }

    /**
     * Test function is_countable
     */
    public function test_is_countable() {
        $this->assertTrue(evidence::is_countable());
    }

    /**
     * Test the count function
     */
    public function test_count() {
        $this->resetAfterTest();

        $data = $this->setupdata();

        $target_user = new target_user($data->user1);

        $result = evidence::execute_count($target_user, context_system::instance());

        $this->assertNotEquals(item::RESULT_STATUS_ERROR, $result);
        $this->assertEquals(1, $result);
    }
}
