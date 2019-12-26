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
 * @author Matthias Bonk <matthias.bonk@totaralearning.com>
 * @package mod_facetoface
 */

namespace mod_facetoface\userdata;

defined('MOODLE_INTERNAL') || die();

use advanced_testcase;
use context_course;
use context_coursecat;
use context_module;
use context_system;
use phpunit_util;
use totara_userdata\userdata\export;
use totara_userdata\userdata\target_user;

global $CFG;
require_once($CFG->dirroot . '/mod/facetoface/lib.php');
require_once($CFG->dirroot . '/user/lib.php');
require_once($CFG->dirroot . '/mod/facetoface/tests/lib_test.php');

/**
 * Test customfields item
 *
 * @group totara_userdata
 */
class mod_facetoface_userdata_customfields_testcase extends advanced_testcase {

    /**
     * Set up tests.
     */
    protected function setUp() {
        parent::setUp();
        $this->resetAfterTest(true);
    }

    /**
     * Test count.
     */
    public function test_count() {
        $this->setAdminUser(); // Necessary for file handling.
        $datagenerator = phpunit_util::get_data_generator();
        /** @var \mod_facetoface_generator $f2fgenerator */
        $f2fgenerator = $datagenerator->get_plugin_generator('mod_facetoface');

        $category1 = $datagenerator->create_category();
        $category2 = $datagenerator->create_category();

        $course1 = $datagenerator->create_course(['category' => $category1->id]);
        $course2 = $datagenerator->create_course(['category' => $category2->id]);
        $course3 = $datagenerator->create_course(['category' => $category2->id]);

        $student1 = $datagenerator->create_user();
        $student2 = $datagenerator->create_user();

        $this->getDataGenerator()->enrol_user($student1->id ,$course1->id);
        $this->getDataGenerator()->enrol_user($student1->id ,$course2->id);
        $this->getDataGenerator()->enrol_user($student1->id ,$course3->id);
        $this->getDataGenerator()->enrol_user($student2->id ,$course1->id);
        $this->getDataGenerator()->enrol_user($student2->id ,$course2->id);
        $this->getDataGenerator()->enrol_user($student2->id ,$course3->id);

        $session1 = $f2fgenerator->create_session_for_course($course1);
        $session2 = $f2fgenerator->create_session_for_course($course1, 2);
        $session3 = $f2fgenerator->create_session_for_course($course2, 3);
        $session4 = $f2fgenerator->create_session_for_course($course3, 4);

        $signups = [];
        $signups[11] = $f2fgenerator->create_signup($student1, $session1);
        $signups[12] = $f2fgenerator->create_signup($student1, $session2);
        $signups[13] = $f2fgenerator->create_signup($student1, $session3);
        $signups[14] = $f2fgenerator->create_signup($student1, $session4);

        $signupcustomfieldids = [];
        $signupcustomfieldids[11] = $f2fgenerator->create_customfield_data($signups[11], 'signup', 1, 2);
        $signupcustomfieldids[12] = $f2fgenerator->create_customfield_data($signups[12], 'signup', 2, 3);
        $signupcustomfieldids[13] = $f2fgenerator->create_customfield_data($signups[13], 'signup', 3, 4);
        $signupcustomfieldids[14] = $f2fgenerator->create_customfield_data($signups[14], 'signup', 4, 5);

        $signups[21] = $f2fgenerator->create_signup($student2, $session1);
        $signups[22] = $f2fgenerator->create_signup($student2, $session2);
        $signups[23] = $f2fgenerator->create_signup($student2, $session3);

        $signupcustomfieldids[21] = $f2fgenerator->create_customfield_data($signups[21], 'signup', 5, 6);
        $signupcustomfieldids[22] = $f2fgenerator->create_customfield_data($signups[22], 'signup', 6, 7);
        $signupcustomfieldids[23] = $f2fgenerator->create_customfield_data($signups[23], 'signup', 7, 8);

        $f2fgenerator->create_cancellation($student1, $session1);

        $cancellationcustomfieldids = [];
        $cancellationcustomfieldids[11] = $f2fgenerator->create_customfield_data($signups[11], 'cancellation', 3, 1);

        $f2fgenerator->create_cancellation($student2, $session2);
        $cancellationcustomfieldids[21] = $f2fgenerator->create_customfield_data($signups[22], 'cancellation', 5, 3);

        // Create some file customfields. These will add to the final customfield count.
        $f2fgenerator->create_file_customfield($signups[11], 'signup', 'testfile1.txt', 1);
        $f2fgenerator->create_file_customfield($signups[11], 'signup', 'testfile2.txt', 1);
        $f2fgenerator->create_file_customfield($signups[13], 'signup', 'testfile3.txt', 2);
        $f2fgenerator->create_file_customfield($signups[22], 'cancellation', 'testfile4.txt', 3);

        $targetuser1 = new target_user($student1);
        $targetuser2 = new target_user($student2);

        // System context.
        $this->assertEquals(15, customfields::execute_count($targetuser1, context_system::instance()));
        $this->assertEquals(24, customfields::execute_count($targetuser2, context_system::instance()));

        // Course context.
        $coursecontext1 = context_course::instance($course1->id);
        $coursecontext2 = context_course::instance($course2->id);
        $coursecontext3 = context_course::instance($course3->id);
        $this->assertEquals(7, customfields::execute_count($targetuser1, $coursecontext1));
        $this->assertEquals(17, customfields::execute_count($targetuser2, $coursecontext1));
        $this->assertEquals(4, customfields::execute_count($targetuser1, $coursecontext2));
        $this->assertEquals(7, customfields::execute_count($targetuser2, $coursecontext2));
        $this->assertEquals(4, customfields::execute_count($targetuser1, $coursecontext3));
        $this->assertEquals(0, customfields::execute_count($targetuser2, $coursecontext3));

        // Category context.
        $categorycontext1 = context_coursecat::instance($category1->id);
        $categorycontext2 = context_coursecat::instance($category2->id);
        $this->assertEquals(7, customfields::execute_count($targetuser1, $categorycontext1));
        $this->assertEquals(17, customfields::execute_count($targetuser2, $categorycontext1));
        $this->assertEquals(8, customfields::execute_count($targetuser1, $categorycontext2));
        $this->assertEquals(7, customfields::execute_count($targetuser2, $categorycontext2));

        // Module context.
        $coursemodule2 = get_coursemodule_from_instance('facetoface', $session2->facetoface);
        $coursemodule3 = get_coursemodule_from_instance('facetoface', $session3->facetoface);
        $modulecontext2 = context_module::instance($coursemodule2->id);
        $modulecontext3 = context_module::instance($coursemodule3->id);
        $this->assertEquals(2, customfields::execute_count($targetuser1, $modulecontext2));
        $this->assertEquals(12, customfields::execute_count($targetuser2, $modulecontext2));
        $this->assertEquals(4, customfields::execute_count($targetuser1, $modulecontext3));
        $this->assertEquals(7, customfields::execute_count($targetuser2, $modulecontext3));
    }

    public function test_export() {
        $this->setAdminUser(); // Necessary for file handling.
        /** @var \mod_facetoface_generator $f2fgenerator */
        $datagenerator = phpunit_util::get_data_generator();
        $f2fgenerator = $datagenerator->get_plugin_generator('mod_facetoface');

        $category1 = $datagenerator->create_category();
        $category2 = $datagenerator->create_category();

        $course1 = $datagenerator->create_course(['category' => $category1->id]);
        $course2 = $datagenerator->create_course(['category' => $category2->id]);
        $course3 = $datagenerator->create_course(['category' => $category2->id]);

        $student1 = $datagenerator->create_user();
        $student2 = $datagenerator->create_user();

        $this->getDataGenerator()->enrol_user($student1->id ,$course1->id);
        $this->getDataGenerator()->enrol_user($student1->id ,$course2->id);
        $this->getDataGenerator()->enrol_user($student1->id ,$course3->id);
        $this->getDataGenerator()->enrol_user($student2->id ,$course1->id);
        $this->getDataGenerator()->enrol_user($student2->id ,$course2->id);
        $this->getDataGenerator()->enrol_user($student2->id ,$course3->id);

        $session1 = $f2fgenerator->create_session_for_course($course1);
        $session2 = $f2fgenerator->create_session_for_course($course1, 2);
        $session3 = $f2fgenerator->create_session_for_course($course2, 3);
        $session4 = $f2fgenerator->create_session_for_course($course3, 4);

        // Create signups including customfield data and data params.

        // Create 1 field with 2 params for session1 and student1.
        // Create 1 field with 3 params + 1 field with 0 params for session2 and student1.
        // Create 1 field with 4 params + 2 field with 0 params for session3 and student1.
        // Create 1 field with 5 params + 3 field with 0 params for session4 and student1.
        // Create 1 field with 6 params + 4 field with 0 params for session1 and student2.
        // Create 1 field with 7 params + 5 field with 0 params for session2 and student2.
        // Create 1 field with 8 params + 6 field with 0 params for session3 and student2.
        $signups = [];
        $signups[11] = $f2fgenerator->create_signup($student1, $session1);
        $signups[12] = $f2fgenerator->create_signup($student1, $session2);
        $signups[13] = $f2fgenerator->create_signup($student1, $session3);
        $signups[14] = $f2fgenerator->create_signup($student1, $session4);

        $signupcustomfieldids = [];

        $signupcustomfieldids[11] = $f2fgenerator->create_customfield_data($signups[11], 'signup', 1, 2);
        $signupcustomfieldids[12] = $f2fgenerator->create_customfield_data($signups[12], 'signup', 2, 3);
        $signupcustomfieldids[13] = $f2fgenerator->create_customfield_data($signups[13], 'signup', 3, 4);
        $signupcustomfieldids[14] = $f2fgenerator->create_customfield_data($signups[14], 'signup', 4, 5);

        $signups[21] = $f2fgenerator->create_signup($student2, $session1);
        $signups[22] = $f2fgenerator->create_signup($student2, $session2);
        $signups[23] = $f2fgenerator->create_signup($student2, $session3);

        $signupcustomfieldids[21] = $f2fgenerator->create_customfield_data($signups[21], 'signup', 5, 6);
        $signupcustomfieldids[22] = $f2fgenerator->create_customfield_data($signups[22], 'signup', 6, 7);
        $signupcustomfieldids[23] = $f2fgenerator->create_customfield_data($signups[23], 'signup', 7, 8);

        $f2fgenerator->create_cancellation($student1, $session1);
        $f2fgenerator->create_cancellation($student1, $session3);

        $cancellationcustomfieldids = [];

        $cancellationcustomfieldids[11] = $f2fgenerator->create_customfield_data($signups[11], 'cancellation', 3, 1);
        $cancellationcustomfieldids[12] = $f2fgenerator->create_customfield_data($signups[13], 'cancellation', 4, 2);

        $f2fgenerator->create_cancellation($student2, $session2);

        $cancellationcustomfieldids[21] = $f2fgenerator->create_customfield_data($signups[22], 'cancellation', 5, 3);

        // Create some file customfields. These will add to the final customfield count.
        $file1 = $f2fgenerator->create_file_customfield($signups[11], 'signup', 'testfile1.txt', 1);
        $file2 = $f2fgenerator->create_file_customfield($signups[11], 'signup', 'testfile2.txt', 1);
        $file3 = $f2fgenerator->create_file_customfield($signups[13], 'signup', 'testfile3.txt', 2);
        $file4 = $f2fgenerator->create_file_customfield($signups[22], 'cancellation', 'testfile4.txt', 3);

        $targetuser1 = new target_user($student1);
        $targetuser2 = new target_user($student2);

        /*************************
         * SYSTEM CONTEXT.
         *************************/

        // Student 1.
        $export = customfields::execute_export($targetuser1, context_system::instance());
        $data = $export->data;
        $this->assertCount(12, $data['signup']);
        $this->assertCount(7, $data['cancellation']);

        // Assert that all previously generated custom fields are in the export.
        $this->assert_export_contains_custom_fields($signupcustomfieldids[11], $data['signup']);
        $this->assert_export_contains_custom_fields($signupcustomfieldids[12], $data['signup']);
        $this->assert_export_contains_custom_fields($signupcustomfieldids[13], $data['signup']);
        $this->assert_export_contains_custom_fields($signupcustomfieldids[14], $data['signup']);
        $this->assert_export_contains_custom_fields($cancellationcustomfieldids[11], $data['cancellation']);
        $this->assert_export_contains_custom_fields($cancellationcustomfieldids[12], $data['cancellation']);

        $this->assert_export_contains_file($file1, 'signup', $export);
        $this->assert_export_contains_file($file2, 'signup', $export);
        $this->assert_export_contains_file($file3, 'signup', $export);

        // Student 2.
        $export = customfields::execute_export($targetuser2, context_system::instance());
        $data = $export->data;
        $this->assertCount(18, $data['signup']);
        $this->assertCount(6, $data['cancellation']);

        // Assert that all previously generated custom fields are in the export.
        $this->assert_export_contains_custom_fields($signupcustomfieldids[21], $data['signup']);
        $this->assert_export_contains_custom_fields($signupcustomfieldids[22], $data['signup']);
        $this->assert_export_contains_custom_fields($signupcustomfieldids[23], $data['signup']);
        $this->assert_export_contains_custom_fields($cancellationcustomfieldids[21], $data['cancellation']);

        $this->assert_export_contains_file($file4, 'cancellation', $export);

        /*************************
         * COURSE CONTEXT.
         *************************/

        $coursecontext1 = context_course::instance($course1->id);
        $export = customfields::execute_export($targetuser1, $coursecontext1);
        $data = $export->data;
        $this->assertCount(4, $data['signup']);
        $this->assertCount(3, $data['cancellation']);

        // Assert that all previously generated custom fields are in the export.
        $this->assert_export_contains_custom_fields($signupcustomfieldids[12], $data['signup']);
        $this->assert_export_contains_custom_fields($signupcustomfieldids[12], $data['signup']);
        $this->assert_export_contains_custom_fields($cancellationcustomfieldids[11], $data['cancellation']);

        $this->assert_export_contains_file($file1, 'signup', $export);
        $this->assert_export_contains_file($file2, 'signup', $export);

        /*************************
         * CATEGORY CONTEXT.
         *************************/

        $categorycontext2 = context_coursecat::instance($category2->id);
        $export = customfields::execute_export($targetuser2, $categorycontext2);
        $data = $export->data;
        $this->assertCount(7, $data['signup']);
        $this->assertCount(0, $data['cancellation']);

        // Assert that all previously generated custom fields are in the export.
        $this->assert_export_contains_custom_fields($signupcustomfieldids[23], $data['signup']);

        $this->assertEmpty($export->files);

        /*************************
         * MODULE CONTEXT.
         *************************/

        $coursemodule3 = get_coursemodule_from_instance('facetoface', $session3->facetoface);
        $modulecontext3 = context_module::instance($coursemodule3->id);
        $export = customfields::execute_export($targetuser1, $modulecontext3);
        $data = $export->data;
        $this->assertCount(4, $data['signup']);
        $this->assertCount(4, $data['cancellation']);

        // Assert that all previously generated custom fields are in the export.
        $this->assert_export_contains_custom_fields($signupcustomfieldids[13], $data['signup']);
        $this->assert_export_contains_custom_fields($cancellationcustomfieldids[12], $data['cancellation']);

        $this->assert_export_contains_file($file3, 'signup', $export);
    }

    /**
     * Assert that the export contains the expected files with the given filenames.
     *
     * @param \stored_file $file
     * @param string $type
     * @param export $export
     * @internal param array $expectedfilenames
     */
    private function assert_export_contains_file(\stored_file $file, string $type, export $export) {
        $files = $export->files;
        $this->assertArrayHasKey($file->get_id(), $files);

        $customfieldid = $file->get_itemid();
        $this->assertArrayHasKey($customfieldid, $export->data[$type]);
        $this->assertArrayHasKey('files', $export->data[$type][$customfieldid]);
        $this->assertContains(
            [
                'fileid' => $file->get_id(),
                'filename' => $file->get_filename(),
                'contenthash' => $file->get_contenthash()
            ],
            $export->data[$type][$customfieldid]['files']
        );
    }

    /**
     * Helper method for export data assertions.
     *
     * @param array $customfieldids
     * @param array $data
     */
    private function assert_export_contains_custom_fields(array $customfieldids, array $data) {
        foreach ($customfieldids as $customfieldid) {
            $this->assertArrayHasKey($customfieldid, $data);
        }
    }
}
