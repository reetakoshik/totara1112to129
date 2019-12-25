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
 * @author Rob Tyler <rob.tyler@totaralearning.com>
 * @package mod_data
 */

use mod_data\userdata\entries;
use totara_userdata\userdata\target_user;

/**
 * @group totara_userdata
 * @group mod_data
 */
class mod_data_userdata_entries_testcase extends advanced_testcase {

    public function setUp_data() {
        global $CFG, $DB;

        $data = new class() {
            /**
             * @var coursecat $category1 course category data.
             * @var coursecat $category2 course category data.
             */
            public $category1, $category2;

            /**
             * @var stdClass $course1 course data.
             * @var stdClass $course2 course data.
             */
            public $course1, $course2;

            /**
             * @var stdClass Database module 1 data.
             * @var stdClass Database module 2 data.
             * @var stdClass Database module 3 data.
             */
            public $module1, $module2, $module3;

            /**
             * @var target_user $target_user1 User data.
             * @var target_user $target_user2 User data.
             */
            public $target_user1, $target_user2;
        };

        $this->resetAfterTest(true);

        $this->setAdminUser();

        $generator = $this->getDataGenerator();

        // Create a couple of users so we can create target_user objects.
        $user1 = $generator->create_user(['email' => 'dave@example.com']);
        $user2 = $generator->create_user(['email' => 'bob@example.com']);
        // Create a target user object that uses the email address we want to test with.
        $data->target_user1 = new target_user($user1);
        $data->target_user2 = new target_user($user2);

        // Create a couple of course categories.
        $data->category1 = $generator->create_category();
        $data->category2 = $generator->create_category();

        // Create a couple of courses.
        $data->course1 = $generator->create_course(['category' => $data->category1->id]);
        course_create_sections_if_missing($data->course1, array(0, 1));
        $data->course2 = $generator->create_course(['category' => $data->category2->id]);
        course_create_sections_if_missing($data->course2, array(0, 1));

        // Enrole the users on the course.
        $role = $DB->get_record('role', array('shortname'=>'student'));
        $instance = $DB->get_record('enrol', array('courseid' => $data->course1->id, 'enrol' => 'manual'), '*', MUST_EXIST);
        $manual = enrol_get_plugin('manual');
        $manual->enrol_user($instance, $data->target_user1->id, $role->id);
        $manual->enrol_user($instance, $data->target_user2->id, $role->id);

        $instance = $DB->get_record('enrol', array('courseid' => $data->course2->id, 'enrol' => 'manual'), '*', MUST_EXIST);
        $manual = enrol_get_plugin('manual');
        $manual->enrol_user($instance, $data->target_user1->id, $role->id);
        $manual->enrol_user($instance, $data->target_user2->id, $role->id);

        // Set up the module data and create the database modules.
        $params1 = [];
        $params1['course'] = $data->course1;
        $params2 = [];
        $params2['course'] = $data->course2;

        // Create three data instances from the given data.
        $data->module1 = $generator->create_module('data', $params1);
        $data->module2 = $generator->create_module('data', $params2);
        $data->module3 = $generator->create_module('data', $params2);
        $this->assertEquals(3, $DB->count_records('data'));

        // Define a list of all the field types we're going to create and add data to.
        $fieldtypes = ['checkbox', 'date', 'file', 'latlong', 'menu', 'multimenu', 'number', 'picture', 'radiobutton', 'text', 'textarea', 'url'];

        // Loop over each of the modules and create all the data required for testing.
        for($i = 1; $i <= 3; $i++) {
            switch($i) {
                case 1:
                    $data_object = $data->module1;
                    $user_object = $data->target_user1;
                    break;
                case 2:
                    $data_object = $data->module2;
                    $user_object = $data->target_user1;
                    break;
                case 3:
                    $data_object = $data->module3;
                    $user_object = $data->target_user2;
            }

            // Create the field types with default parameter values.
            foreach ($fieldtypes as $fieldtype) {
                // Creating variables dynamically.
                $fieldname = ucwords($fieldtype) . " {$i}";
                $record = new StdClass();
                $record->name = $fieldname;
                $record->type = $fieldtype;
                $record->required = 1;

                $field = $generator->get_plugin_generator('mod_data')->create_field($record, $data_object);
                $this->assertInstanceOf('data_field_' . $fieldtype, $field);
            }

            // Check that all the field types have been created for the module.
            $this->assertEquals(count($fieldtypes), $DB->count_records('data_fields', array('dataid' => $data_object->id)));

            /* Create some files to be added to the file and picture field types.
             *
             * The file system is complex and heavily relies on the correct process being followed to upload
             * and 'attach' the file to a data record.
             *
             * When a file is uploaded (via the interface), the Totara file system creates files as a 'draft'
             * in the user context initially and then is moves the file to a second context (module in this case)
             * when it is properly uploaded.
             *
             * As the process relies on globalised use of $USER it's not possible to simulate the correct process
             * for uploaded file because core changes would be required, so, we'll have to hack it for testing.
             *
             * To get the uploaded file into the correct state using the correct item id, we'll use a unique draft
             * itemid first and then update it in generator/lib.php once the itemid is known
             */

            // Define the data required to create the file field type file.
            $draftfileitemid = file_get_unused_draft_itemid();
            $filename = "Database_{$i}_file.text";
            $fileinfo = array(
                'contextid' => context_module::instance($data_object->cmid)->id,
                'component' => 'mod_data',
                'filearea'  => 'content',
                'itemid'    => $draftfileitemid,
                'filepath'  => '/',
                'filename'  => $filename,
                'mimetype'  => 'text/plain',
                'userid'    => $user_object->id
            );

            // Create the file with some simple content.
            $fs = get_file_storage();
            $fs->create_file_from_string($fileinfo, "Content of file upload " . $filename);

            // Create the data required to create the picture field type file.
            $draftpictureitemid = file_get_unused_draft_itemid();
            $filename = "Database_{$i}_picture.gif";
            $fileinfo = array(
                'contextid' => context_module::instance($data_object->cmid)->id,
                'component' => 'mod_data',
                'filearea'  => 'content',
                'itemid'    => $draftpictureitemid,
                'filepath'  => '/',
                'filename'  => $filename,
                'mimetype'  => 'image/gif',
                'userid'    => $user_object->id
            );

            // Create the picture file. The content doesn't have to be an actual image.
            $fs = get_file_storage();
            $fs->create_file_from_string($fileinfo, "Content of picture upload " . $filename);

            // Define some data for all the field types.
            $contents = $fieldcontents = [];
            $contents['checkbox'] = ['opt1', 'opt3'];
            $contents['date'] = '01-01-2037';
            $contents['file'] = $draftfileitemid;
            $contents['latlong'] = ['50.8225', '0.1372'];
            $contents['menu'] = 'menu1';
            $contents['multimenu'] = ['multimenu1', 'multimenu4'];
            $contents['number'] = '12345';
            $contents['picture'] = $draftpictureitemid;
            $contents['radiobutton'] = 'radioopt1';
            $contents['text'] = 'Simple single line plan text';
            $contents['textarea'] = '<p><strong>HTML</strong> text area</p>';
            $contents['url'] = ['example.url', 'sampleurl'];

            // Get the record ids of all the defined field types for the module.
            // Then we can create the data in the format we need to get the data created.
            $fields = $DB->get_records('data_fields', ['dataid' => $data_object->id], 'id', 'id, type');

            foreach ($fields as $fieldrecord) {
                $fieldcontents[$fieldrecord->id] = $contents[$fieldrecord->type];
            }

            // Create the test data for the module.
            $generator->get_plugin_generator('mod_data')->create_entry($data_object, $fieldcontents, 0, $user_object->id);
        }

        return $data;
    }

    /**
     * Get a count of the data_record and data_content records
     * that exists for the user. (Usinga user data would only
     * count the data_record records.)
     *
     * @param int $userid User id of the records to count.
     */
    private function get_record_counts($userid) {
        global $DB;

        $count_sql = 'SELECT COUNT(DISTINCT(dr.id)) AS data_record_count,
                             COUNT(DISTINCT(dc.id)) AS data_content_count
                      FROM {data_records} dr
                      LEFT JOIN {data_content} dc ON dc.recordid = dr.id
                      WHERE dr.userid = :userid';

        // Get counts of the user 1 records.
        $record_counts = $DB->get_record_sql($count_sql, ['userid' => $userid]);

        return $record_counts;
    }

    /**
     * Test function is_compatible_context_level with all possible contexts.
     */
    public function test_get_compatible_context_levels() {
        $this->assertEquals(
            [CONTEXT_SYSTEM, CONTEXT_COURSECAT, CONTEXT_COURSE, CONTEXT_MODULE],
            entries::get_compatible_context_levels()
        );
    }

    /**
     * Test function is_purgeable.
     */
    public function test_is_purgeable() {
        $this->assertTrue(entries::is_purgeable(target_user::STATUS_ACTIVE));
        $this->assertTrue(entries::is_purgeable(target_user::STATUS_SUSPENDED));
        $this->assertTrue(entries::is_purgeable(target_user::STATUS_DELETED));
    }

    /**
     * Test function is_exportable.
     */
    public function test_is_exportable() {
        $this->assertTrue(entries::is_exportable());
    }

    /**
     * Test function is_countable.
     */
    public function test_is_countable() {
        $this->assertTrue(entries::is_countable());
    }

    /**
     * Test counts of database module entries for each user at the system context.
     */
    public function test_count_at_system_context() {
        $data = $this->setup_data();

        // Check we've got the right number of database entries for target_iser1.
        $count = entries::execute_count($data->target_user1, context_system::instance());
        $this->assertEquals(2, $count);

        // Check we've got the right number of database entries for target_iser2.
        $count = entries::execute_count($data->target_user2, context_system::instance());
        $this->assertEquals(1, $count);
    }

    /**
     * Test counts of database module entries for each user at the category context.
     */
    public function test_count_at_category_context() {
        $data = $this->setup_data();

        $count = entries::execute_count($data->target_user1, context_coursecat::instance($data->category1->id));
        $this->assertEquals(1, $count);

        $count = entries::execute_count($data->target_user2, context_coursecat::instance($data->category1->id));
        $this->assertEquals(0, $count);

        $count = entries::execute_count($data->target_user1, context_coursecat::instance($data->category2->id));
        $this->assertEquals(1, $count);

        $count = entries::execute_count($data->target_user2, context_coursecat::instance($data->category2->id));
        $this->assertEquals(1, $count);
    }

    /**
     * Test counts of database module entries for each user at the course context.
     */
    public function test_count_at_course_context() {
        $data = $this->setup_data();

        $count = entries::execute_count($data->target_user1, context_course::instance($data->course1->id));
        $this->assertEquals(1, $count);

        $count = entries::execute_count($data->target_user2, context_course::instance($data->course1->id));
        $this->assertEquals(0, $count);

        $count = entries::execute_count($data->target_user1, context_course::instance($data->course2->id));
        $this->assertEquals(1, $count);

        $count = entries::execute_count($data->target_user2, context_course::instance($data->course2->id));
        $this->assertEquals(1, $count);
    }

    /**
     * Test counts of database module entries for each user at the module context.
     */
    public function test_count_at_module_context() {
        $data = $this->setup_data();

        $count = entries::execute_count($data->target_user1, context_module::instance($data->module1->cmid));
        $this->assertEquals(1, $count);

        $count = entries::execute_count($data->target_user1, context_module::instance($data->module2->cmid));
        $this->assertEquals(1, $count);

        $count = entries::execute_count($data->target_user2, context_module::instance($data->module3->cmid));
        $this->assertEquals(1, $count);
    }

    /**
     * Test a purge of database module entries for each user at the system context.
     */
    public function test_purge_at_system_context() {
        $data = $this->setup_data();

        // Get counts of the user 1 records.
        $record_counts = $this->get_record_counts($data->target_user1->id);
        $this->assertEquals(2, $record_counts->data_record_count);
        $this->assertEquals(24, $record_counts->data_content_count);

        // Get counts of the user 2 records.
        $record_counts = $this->get_record_counts($data->target_user2->id);
        $this->assertEquals(1, $record_counts->data_record_count);
        $this->assertEquals(12, $record_counts->data_content_count);

        // Purge the user 1 record.
        $result = entries::execute_purge($data->target_user1, context_system::instance());
        $this->assertEquals(entries::RESULT_STATUS_SUCCESS, $result);

        // Check the user 1 records have been deleted.
        $record_counts = $this->get_record_counts($data->target_user1->id);
        $this->assertEquals(0, $record_counts->data_record_count);
        $this->assertEquals(0, $record_counts->data_content_count);

        // Check the user 2 records remain.
        $record_counts = $this->get_record_counts($data->target_user2->id);
        $this->assertEquals(1, $record_counts->data_record_count);
        $this->assertEquals(12, $record_counts->data_content_count);

        // Purge the user 2 records.
        $result = entries::execute_purge($data->target_user2, context_system::instance());
        $this->assertEquals(entries::RESULT_STATUS_SUCCESS, $result);

        // Check the user 2 records have been deleted.
        $record_counts = $this->get_record_counts($data->target_user2->id);
        $this->assertEquals(0, $record_counts->data_record_count);
        $this->assertEquals(0, $record_counts->data_content_count);
    }

    /**
     * Test a purge of database module entries for each user at the category context.
     */
    public function test_purge_at_category_context() {
        $data = $this->setup_data();

        // Get counts of the user 1 records.
        $record_counts = $this->get_record_counts($data->target_user1->id);
        $this->assertEquals(2, $record_counts->data_record_count);
        $this->assertEquals(24, $record_counts->data_content_count);

        // Get counts of the user 2 records.
        $record_counts = $this->get_record_counts($data->target_user2->id);
        $this->assertEquals(1, $record_counts->data_record_count);
        $this->assertEquals(12, $record_counts->data_content_count);

        // Purge the user 1 category 1 records.
        $result = entries::execute_purge($data->target_user1, context_coursecat::instance($data->category1->id));
        $this->assertEquals(entries::RESULT_STATUS_SUCCESS, $result);

        // Check that only the user 1 category 1 records have been deleted.
        $count = entries::execute_count($data->target_user1, context_coursecat::instance($data->category1->id));
        $this->assertEquals(0, $count);

        // Check category 2 records remain.
        $record_counts = $this->get_record_counts($data->target_user1->id);
        $this->assertEquals(1, $record_counts->data_record_count);
        $this->assertEquals(12, $record_counts->data_content_count);

        // Check the user 2 records remain.
        $record_counts = $this->get_record_counts($data->target_user2->id);
        $this->assertEquals(1, $record_counts->data_record_count);
        $this->assertEquals(12, $record_counts->data_content_count);

        // Purge the user 1 category 2 records.
        $result = entries::execute_purge($data->target_user1, context_coursecat::instance($data->category2->id));
        $this->assertEquals(entries::RESULT_STATUS_SUCCESS, $result);

        // Check the user 1 category 2 records have been deleted.
        $record_counts = $this->get_record_counts($data->target_user1->id);
        $this->assertEquals(0, $record_counts->data_record_count);
        $this->assertEquals(0, $record_counts->data_content_count);

        // Check the user 2 records remain.
        $record_counts = $this->get_record_counts($data->target_user2->id);
        $this->assertEquals(1, $record_counts->data_record_count);
        $this->assertEquals(12, $record_counts->data_content_count);

        // Purge the user 2 category 2 records.
        $result = entries::execute_purge($data->target_user2, context_coursecat::instance($data->category2->id));
        $this->assertEquals(entries::RESULT_STATUS_SUCCESS, $result);

        // Check the user 2 records have been deleted.
        $record_counts = $this->get_record_counts($data->target_user2->id);
        $this->assertEquals(0, $record_counts->data_record_count);
        $this->assertEquals(0, $record_counts->data_content_count);
    }

    /**
     * Test a purge of database module entries for each user at the course context.
     */
    public function test_purge_at_course_context() {
        $data = $this->setup_data();

        // Get counts of the user 1 records.
        $record_counts = $this->get_record_counts($data->target_user1->id);
        $this->assertEquals(2, $record_counts->data_record_count);
        $this->assertEquals(24, $record_counts->data_content_count);

        // Get counts of the user 2 records.
        $record_counts = $this->get_record_counts($data->target_user2->id);
        $this->assertEquals(1, $record_counts->data_record_count);
        $this->assertEquals(12, $record_counts->data_content_count);

        // Purge the user 1 course 1 records.
        $result = entries::execute_purge($data->target_user1, context_course::instance($data->course1->id));
        $this->assertEquals(entries::RESULT_STATUS_SUCCESS, $result);

        // Check that only the user 1 course 1 records have been deleted.
        $count = entries::execute_count($data->target_user1, context_course::instance($data->course1->id));
        $this->assertEquals(0, $count);

        // Course 2 records will remain
        $record_counts = $this->get_record_counts($data->target_user1->id);
        $this->assertEquals(1, $record_counts->data_record_count);
        $this->assertEquals(12, $record_counts->data_content_count);

        // Check the user 2 records remain.
        $record_counts = $this->get_record_counts($data->target_user2->id);
        $this->assertEquals(1, $record_counts->data_record_count);
        $this->assertEquals(12, $record_counts->data_content_count);

        // Purge the user 1 course 2 records.
        $result = entries::execute_purge($data->target_user1, context_course::instance($data->course2->id));
        $this->assertEquals(entries::RESULT_STATUS_SUCCESS, $result);

        // Check the user 1 course 2 records have been deleted.
        $record_counts = $this->get_record_counts($data->target_user1->id);
        $this->assertEquals(0, $record_counts->data_record_count);
        $this->assertEquals(0, $record_counts->data_content_count);

        // Check the user 2 records remain.
        $record_counts = $this->get_record_counts($data->target_user2->id);
        $this->assertEquals(1, $record_counts->data_record_count);
        $this->assertEquals(12, $record_counts->data_content_count);

        // Purge the user 2 course 2 records.
        $result = entries::execute_purge($data->target_user2, context_course::instance($data->course2->id));
        $this->assertEquals(entries::RESULT_STATUS_SUCCESS, $result);

        // Check the user 2 records have been deleted.
        $record_counts = $this->get_record_counts($data->target_user2->id);
        $this->assertEquals(0, $record_counts->data_record_count);
        $this->assertEquals(0, $record_counts->data_content_count);
    }

    /**
     * Test a purge of database module entries for each user at the module context.
     */
    public function test_purge_at_module_context() {
        $data = $this->setup_data();

        // Get counts of the user 1 records.
        $record_counts = $this->get_record_counts($data->target_user1->id);
        $this->assertEquals(2, $record_counts->data_record_count);
        $this->assertEquals(24, $record_counts->data_content_count);

        // Get counts of the user 2 records.
        $record_counts = $this->get_record_counts($data->target_user2->id);
        $this->assertEquals(1, $record_counts->data_record_count);
        $this->assertEquals(12, $record_counts->data_content_count);

        // Purge the user 1 module 1 records.
        $result = entries::execute_purge($data->target_user1, context_module::instance($data->module1->cmid));
        $this->assertEquals(entries::RESULT_STATUS_SUCCESS, $result);

        // Check that only the user 1 module 1 records have been deleted.
        $count = entries::execute_count($data->target_user1, context_module::instance($data->module1->cmid));
        $this->assertEquals(0, $count);

        // Module 2 records will remain.
        $record_counts = $this->get_record_counts($data->target_user1->id);
        $this->assertEquals(1, $record_counts->data_record_count);
        $this->assertEquals(12, $record_counts->data_content_count);

        // Check the user 2 records remain.
        $record_counts = $this->get_record_counts($data->target_user2->id);
        $this->assertEquals(1, $record_counts->data_record_count);
        $this->assertEquals(12, $record_counts->data_content_count);

        // Purge the user 1 module 2 records.
        $result = entries::execute_purge($data->target_user1, context_module::instance($data->module2->cmid));
        $this->assertEquals(entries::RESULT_STATUS_SUCCESS, $result);

        // Check the user 1 module 2 records have been deleted.
        $record_counts = $this->get_record_counts($data->target_user1->id);
        $this->assertEquals(0, $record_counts->data_record_count);
        $this->assertEquals(0, $record_counts->data_content_count);

        // Check the user 2 records remain.
        $record_counts = $this->get_record_counts($data->target_user2->id);
        $this->assertEquals(1, $record_counts->data_record_count);
        $this->assertEquals(12, $record_counts->data_content_count);

        // Purge the user 2 module 2 records.
        $result = entries::execute_purge($data->target_user2, context_module::instance($data->module3->cmid));
        $this->assertEquals(entries::RESULT_STATUS_SUCCESS, $result);

        // Check the user 2 records have been deleted.
        $record_counts = $this->get_record_counts($data->target_user2->id);
        $this->assertEquals(0, $record_counts->data_record_count);
        $this->assertEquals(0, $record_counts->data_content_count);
    }

    /**
     * Test export of database module entries for each user at the system context.
     */
    public function test_export_at_system_context() {
        $data = $this->setup_data();

        // Export data for user 1 in the system context.
        $export = entries::execute_export($data->target_user1, context_system::instance());
        // Check we've got two lots of data with two files for each.
        $this->assertCount(2, $export->data);
        $this->assertCount(4, $export->files);

        // Check we've got the correct database modules.
        $this->assertEquals($data->module1->id, $export->data[0]['databaseid']);
        $this->assertEquals($data->module2->id, $export->data[1]['databaseid']);
        // Check the files have been exported correctly.
        $this->assertCount(2, $export->data[0]['files']);
        $this->assertEquals('Database_1_file.text', $export->data[0]['files'][0]['filename']);
        $this->assertEquals('Database_1_picture.gif', $export->data[0]['files'][1]['filename']);
        $this->assertCount(2, $export->data[1]['files']);
        $this->assertEquals('Database_2_file.text', $export->data[1]['files'][0]['filename']);
        $this->assertEquals('Database_2_picture.gif', $export->data[1]['files'][1]['filename']);

        // Export data for user 2 in the system context.
        $export = entries::execute_export($data->target_user2, context_system::instance());
        // Check we've got one lot of data and two files.
        $this->assertCount(1, $export->data);
        $this->assertCount(2, $export->files);

        // Check we've got the right database module and files.
        $this->assertEquals($data->module3->id, $export->data[0]['databaseid']);
        $this->assertCount(2, $export->data[0]['files']);
        $this->assertEquals('Database_3_file.text', $export->data[0]['files'][0]['filename']);
        $this->assertEquals('Database_3_picture.gif', $export->data[0]['files'][1]['filename']);

        // Check the data returned has the correct structure.
        $this->assertCount(8, $export->data[0]);
        $this->assertArrayHasKey('databaseid', $export->data[0]);
        $this->assertArrayHasKey('entryid', $export->data[0]);
        $this->assertArrayHasKey('groupid', $export->data[0]);
        $this->assertArrayHasKey('timecreated', $export->data[0]);
        $this->assertArrayHasKey('timemodified', $export->data[0]);
        $this->assertArrayHasKey('approved', $export->data[0]);
        $this->assertArrayHasKey('entry', $export->data[0]);
        $this->assertArrayHasKey('files', $export->data[0]);
    }

    /**
     * Test export of database module entries for each user at the category context.
     */
    public function test_export_at_category_context() {
        $data = $this->setup_data();

        // Export data for user 1 in the category context for
        // category 1 and check we've got the right amount of data.
        $export = entries::execute_export($data->target_user1, context_coursecat::instance($data->category1->id));
        $this->assertCount(1, $export->data);
        $this->assertCount(2, $export->files);

        // Check we've got the correct database module.
        $this->assertEquals($data->module1->id, $export->data[0]['databaseid']);
        // Check the files have been exported correctly.
        $this->assertCount(2, $export->data[0]['files']);
        $this->assertEquals('Database_1_file.text', $export->data[0]['files'][0]['filename']);
        $this->assertEquals('Database_1_picture.gif', $export->data[0]['files'][1]['filename']);

        // Export data for user 1 in the category context for
        // category 2 and check we've got the right amount of data.
        $export = entries::execute_export($data->target_user1, context_coursecat::instance($data->category2->id));
        $this->assertCount(1, $export->data);
        $this->assertCount(2, $export->files);

        // Check we've got the correct database module.
        $this->assertEquals($data->module2->id, $export->data[0]['databaseid']);
        // Check the files have been exported correctly.
        $this->assertCount(2, $export->data[0]['files']);
        $this->assertEquals('Database_2_file.text', $export->data[0]['files'][0]['filename']);
        $this->assertEquals('Database_2_picture.gif', $export->data[0]['files'][1]['filename']);

        // Export data for user 2 in the category context for category 1 and check we've got no data.
        $export = entries::execute_export($data->target_user2, context_coursecat::instance($data->category1->id));
        $this->assertCount(0, $export->data);
        $this->assertCount(0, $export->files);

        // Export data for user 2 in the category context for
        // category 2 and check we've got the right amount of data.
        $export = entries::execute_export($data->target_user2, context_coursecat::instance($data->category2->id));
        $this->assertCount(1, $export->data);
        $this->assertCount(2, $export->files);

        // Check we've got the correct database module.
        $this->assertEquals($data->module3->id, $export->data[0]['databaseid']);
        // Check the files have been exported correctly.
        $this->assertCount(2, $export->data[0]['files']);
        $this->assertEquals('Database_3_file.text', $export->data[0]['files'][0]['filename']);
        $this->assertEquals('Database_3_picture.gif', $export->data[0]['files'][1]['filename']);

        // Check the data returned has the correct structure.
        $this->assertCount(8, $export->data[0]);
        $this->assertArrayHasKey('databaseid', $export->data[0]);
        $this->assertArrayHasKey('entryid', $export->data[0]);
        $this->assertArrayHasKey('groupid', $export->data[0]);
        $this->assertArrayHasKey('timecreated', $export->data[0]);
        $this->assertArrayHasKey('timemodified', $export->data[0]);
        $this->assertArrayHasKey('approved', $export->data[0]);
        $this->assertArrayHasKey('entry', $export->data[0]);
        $this->assertArrayHasKey('files', $export->data[0]);
    }

    /**
     * Test export of database module entries for each user at the course context.
     */
    public function test_export_at_course_context() {
        $data = $this->setup_data();

        // Export data for user 1 in the course context for
        // course 1 and check we've got the right amount of data.
        $export = entries::execute_export($data->target_user1, context_course::instance($data->course1->id));
        $this->assertCount(1, $export->data);
        $this->assertCount(2, $export->files);

        // Check we've got the correct database module.
        $this->assertEquals($data->module1->id, $export->data[0]['databaseid']);
        // Check the files have been exported correctly.
        $this->assertCount(2, $export->data[0]['files']);
        $this->assertEquals('Database_1_file.text', $export->data[0]['files'][0]['filename']);
        $this->assertEquals('Database_1_picture.gif', $export->data[0]['files'][1]['filename']);

        // Export data for user 1 in the course context for
        // course 2 and check we've got the right amount of data.
        $export = entries::execute_export($data->target_user1, context_course::instance($data->course2->id));
        $this->assertCount(1, $export->data);
        $this->assertCount(2, $export->files);

        // Check we've got the correct database module.
        $this->assertEquals($data->module2->id, $export->data[0]['databaseid']);
        // Check the files have been exported correctly.
        $this->assertCount(2, $export->data[0]['files']);
        $this->assertEquals('Database_2_file.text', $export->data[0]['files'][0]['filename']);
        $this->assertEquals('Database_2_picture.gif', $export->data[0]['files'][1]['filename']);

        // Export data for user 2 in the course context for course 1 and check we've got no data.
        $export = entries::execute_export($data->target_user2, context_course::instance($data->course1->id));
        $this->assertCount(0, $export->data);
        $this->assertCount(0, $export->files);

        // Export data for user 2 in the course context for
        // course 2 and check we've got the right amount of data.
        $export = entries::execute_export($data->target_user2, context_course::instance($data->course2->id));
        $this->assertCount(1, $export->data);
        $this->assertCount(2, $export->files);

        // Check we've got the correct database module.
        $this->assertEquals($data->module3->id, $export->data[0]['databaseid']);
        // Check the files have been exported correctly.
        $this->assertCount(2, $export->data[0]['files']);
        $this->assertEquals('Database_3_file.text', $export->data[0]['files'][0]['filename']);
        $this->assertEquals('Database_3_picture.gif', $export->data[0]['files'][1]['filename']);

        // Check the data returned has the correct structure.
        $this->assertCount(8, $export->data[0]);
        $this->assertArrayHasKey('databaseid', $export->data[0]);
        $this->assertArrayHasKey('entryid', $export->data[0]);
        $this->assertArrayHasKey('groupid', $export->data[0]);
        $this->assertArrayHasKey('timecreated', $export->data[0]);
        $this->assertArrayHasKey('timemodified', $export->data[0]);
        $this->assertArrayHasKey('approved', $export->data[0]);
        $this->assertArrayHasKey('entry', $export->data[0]);
        $this->assertArrayHasKey('files', $export->data[0]);
    }

    /**
     * Test export of database module entries for each user at the module context.
     */
    public function test_export_at_module_context() {
        $data = $this->setup_data();

        // Export data for user 1 in the module context for
        // module 1 and check we've got the right amount of data.
        $export = entries::execute_export($data->target_user1, context_module::instance($data->module1->cmid));
        $this->assertCount(1, $export->data);
        $this->assertCount(2, $export->files);

        // Check we've got the correct database module.
        $this->assertEquals($data->module1->id, $export->data[0]['databaseid']);
        // Check the files have been exported correctly.
        $this->assertCount(2, $export->data[0]['files']);
        $this->assertEquals('Database_1_file.text', $export->data[0]['files'][0]['filename']);
        $this->assertEquals('Database_1_picture.gif', $export->data[0]['files'][1]['filename']);

        // Export data for user 1 in the module context for
        // module 2 and check we've got the right amount of data.
        $export = entries::execute_export($data->target_user1, context_module::instance($data->module2->cmid));
        $this->assertCount(1, $export->data);
        $this->assertCount(2, $export->files);

        // Check we've got the correct database module.
        $this->assertEquals($data->module2->id, $export->data[0]['databaseid']);
        // Check the files have been exported correctly.
        $this->assertCount(2, $export->data[0]['files']);
        $this->assertEquals('Database_2_file.text', $export->data[0]['files'][0]['filename']);
        $this->assertEquals('Database_2_picture.gif', $export->data[0]['files'][1]['filename']);

        // Export data for user 2 in the module context for module 3 and check we've got no data.
        $export = entries::execute_export($data->target_user1, context_module::instance($data->module3->cmid));
        $this->assertCount(0, $export->data);
        $this->assertCount(0, $export->files);

        // Export data for user 2 in the module context for module 1 and check we've got no data.
        $export = entries::execute_export($data->target_user2, context_module::instance($data->module1->cmid));
        $this->assertCount(0, $export->data);
        $this->assertCount(0, $export->files);

        // Export data for user 2 in the module context for module 2 and check we've got no data.
        $export = entries::execute_export($data->target_user2, context_module::instance($data->module2->cmid));
        $this->assertCount(0, $export->data);
        $this->assertCount(0, $export->files);

        // Export data for user 2 in the module context for
        // module 3 and check we've got the right amount of data.
        $export = entries::execute_export($data->target_user2, context_module::instance($data->module3->cmid));
        $this->assertCount(1, $export->data);
        $this->assertCount(2, $export->files);

        // Check we've got the correct database module.
        $this->assertEquals($data->module3->id, $export->data[0]['databaseid']);
        // Check the files have been exported correctly.
        $this->assertCount(2, $export->data[0]['files']);
        $this->assertEquals('Database_3_file.text', $export->data[0]['files'][0]['filename']);
        $this->assertEquals('Database_3_picture.gif', $export->data[0]['files'][1]['filename']);

        // Check the data returned has the correct structure.
        $this->assertCount(8, $export->data[0]);
        $this->assertArrayHasKey('databaseid', $export->data[0]);
        $this->assertArrayHasKey('entryid', $export->data[0]);
        $this->assertArrayHasKey('groupid', $export->data[0]);
        $this->assertArrayHasKey('timecreated', $export->data[0]);
        $this->assertArrayHasKey('timemodified', $export->data[0]);
        $this->assertArrayHasKey('approved', $export->data[0]);
        $this->assertArrayHasKey('entry', $export->data[0]);
        $this->assertArrayHasKey('files', $export->data[0]);
    }
}