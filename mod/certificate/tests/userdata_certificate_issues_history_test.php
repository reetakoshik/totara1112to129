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
 * @package mod_certificate
 */

use mod_certificate\userdata\certificate_issues_history;
use totara_userdata\userdata\target_user;

global $CFG;

require_once("{$CFG->dirroot}/mod/certificate/locallib.php");

/**
 * @group totara_userdata
 * @group mod_certificate
 */
class mod_certificate_userdata_certificate_issues_history_testcase extends advanced_testcase {

    /**
     * Set up and return the data we need to run the test cases.
     *
     * @return object
     */
    private function setup_data() {
        global $DB;

        /**
         * Set up and return the data we need to run the test cases.
         *
         * @return object
         */
        $data = new class() {
            /*
             * @var coursecat $category1 course category data.
             */
            public $category1;

            /*
             * @var coursecat $category2 course category data.
             */
            public $category2;

            /*
             * @var stdClass $course1 course data.
             */
            public $course1;

            /*
             * @var stdClass $course2 course data.
             */
            public $course2;

            /*
             * @var $certificate1 \stdClass Certificate data.
             */
            public $certificate1;

            /*
             * @var $certificate2 \stdClass Certificate data.
             */
            public $certificate2;

            /*
             * @var $certificate3 \stdClass Certificate data.
             */
            public $certificate3;

            /*
             * @var $certificate1 \stdClass Certificate data.
             */
            public $certificate_issue1;

            /*
             * @var $certificate2 \stdClass Certificate issue data.
             */
            public $certificate_issue2;

            /*
             * @var $certificate3 \stdClass Certificate issue data.
             */
            public $certificate_issue3;

            /*
             * @var target_ser1 $target_user User data.
             */
            public $target_user1;

            /*
             * @var target_ser2 $target_user User data.
             */
            public $target_user2;
        };

        $this->resetAfterTest(true);

        $generator = $this->getDataGenerator();

        $user1 = $generator->create_user(['email' => 'dave@example.com']);
        $user2 = $generator->create_user(['email' => 'bob@example.com']);

        // Create a target user object that uses the email address we want to test with.
        $data->target_user1 = new target_user($user1);
        $data->target_user2 = new target_user($user2);

        $data->category1 = $generator->create_category();
        $data->category2 = $generator->create_category();

        // Create a course to hang any certificates on.
        $data->course1 = $generator->create_course(['category' => $data->category1->id]);
        course_create_sections_if_missing($data->course1, array(0, 1));

        $data->course2 = $generator->create_course(['category' => $data->category2->id]);
        course_create_sections_if_missing($data->course2, array(0, 1));

        // So we can generate a file we need to make sure the users are enroled on the course.
        $role = $DB->get_record('role', array('shortname'=>'student'));
        $instance = $DB->get_record('enrol', array('courseid' => $data->course1->id, 'enrol' => 'manual'), '*', MUST_EXIST);
        $manual = enrol_get_plugin('manual');
        $manual->enrol_user($instance, $data->target_user1->id, $role->id);
        $manual->enrol_user($instance, $data->target_user2->id, $role->id);

        $role = $DB->get_record('role', array('shortname'=>'student'));
        $instance = $DB->get_record('enrol', array('courseid' => $data->course2->id, 'enrol' => 'manual'), '*', MUST_EXIST);
        $manual = enrol_get_plugin('manual');
        $manual->enrol_user($instance, $data->target_user1->id, $role->id);
        $manual->enrol_user($instance, $data->target_user2->id, $role->id);

        // Define some certificate data with different notify other email data.
        $params1 = [];
        $params1['course'] = $data->course1;

        $params2 = [];
        $params2['course'] = $data->course2;

        // Create three certificates from the given data.
        $data->certificate1 = $generator->create_module('certificate', $params1);
        $data->certificate2 = $generator->create_module('certificate', $params2);
        $data->certificate3 = $generator->create_module('certificate', $params2);
        $this->assertEquals(3, $DB->count_records('certificate'));

        // Get the course module data is the standard way before we use certificate_get_issue;
        $cm1 = get_coursemodule_from_id('certificate', $data->certificate1->cmid);
        $cm2 = get_coursemodule_from_id('certificate', $data->certificate2->cmid);
        $cm3 = get_coursemodule_from_id('certificate', $data->certificate3->cmid);

        // Create an issue for the certificate.
        $data->certificate_issue1 = certificate_get_issue($data->course1, $data->target_user1, $data->certificate1, $cm1);
        $data->certificate_issue2 = certificate_get_issue($data->course2, $data->target_user1, $data->certificate2, $cm2);
        $data->certificate_issue3 = certificate_get_issue($data->course2, $data->target_user2, $data->certificate3, $cm3);
        $this->assertEquals(3, $DB->count_records('certificate_issues'));

        return $data;
    }

    /*
     * Create PDF files for the certificate issues.
     *
     * @param class $data The data object containing test data.
     */
    private function create_pdfs($data) {
        global $DB;

        certificate_save_pdf(
            $data->certificate1->name,
            $data->certificate_issue1->id,
            str_replace(' ', '_', $data->certificate1->name) . '.pdf',
            context_module::instance($data->certificate1->cmid)->id,
            $data->target_user1
        );
        certificate_save_pdf(
            $data->certificate2->name,
            $data->certificate_issue2->id,
            str_replace(' ', '_', $data->certificate2->name) . '.pdf',
            context_module::instance($data->certificate2->cmid)->id,
            $data->target_user1
        );
        certificate_save_pdf(
            $data->certificate3->name,
            $data->certificate_issue3->id,
            str_replace(' ', '_', $data->certificate3->name) . '.pdf',
            context_module::instance($data->certificate3->cmid)->id,
            $data->target_user2
        );

        // There should be no existing archived certificate issues.
        $this->assertEquals(0, $DB->count_records('certificate_issues_history'));

        // Archive the certificates so the history table is populated.
        certificate_archive_completion($data->target_user1->id, $data->course1->id);
        certificate_archive_completion($data->target_user1->id, $data->course2->id);
        certificate_archive_completion($data->target_user2->id, $data->course2->id);

        // Check there the issue records have gone and the history table is populated.
        $this->assertEquals(0, $DB->count_records('certificate_issues'));
        $this->assertEquals(3, $DB->count_records('certificate_issues_history'));
    }

    /**
     * Assert that the certificate pdf file entry exists.
     *
     * @param int $itemid The certificate id of teh file to cehck.
     * @param int $userid User id of the file to check.
     * @param int $count The expected result.
     */
    private function assert_certificate_file_record_exists($itemid = 0, $userid = 0, $expected_result = 1) {
        global $DB;

        $params = [
            'component' => 'mod_certificate',
            'mimetype' => 'application/pdf'
        ];

        if ($itemid) {
            $params['itemid'] = $itemid;
        }
        if ($userid) {
            $params['userid'] = $userid;
        }

        $this->assertEquals($expected_result, $DB->count_records('files', $params));
    }

    /**
     * Test function is_compatible_context_level with all possible contexts.
     */
    public function test_get_compatible_context_levels() {
        $this->assertEquals(
            [CONTEXT_SYSTEM, CONTEXT_COURSECAT, CONTEXT_COURSE, CONTEXT_MODULE],
            certificate_issues_history::get_compatible_context_levels()
        );
    }

    /**
     * Test function is_purgeable.
     */
    public function test_is_purgeable() {
        $this->assertTrue(certificate_issues_history::is_purgeable(target_user::STATUS_ACTIVE));
        $this->assertTrue(certificate_issues_history::is_purgeable(target_user::STATUS_SUSPENDED));
        $this->assertTrue(certificate_issues_history::is_purgeable(target_user::STATUS_DELETED));
    }

    /**
     * Test function is_exportable.
     */
    public function test_is_exportable() {
        $this->assertTrue(certificate_issues_history::is_exportable());
    }

    /**
     * Test function is_countable.
     */
    public function test_is_countable() {
        $this->assertTrue(certificate_issues_history::is_countable());
    }

    /**
     * Test counts of certificate issues history for each user at the system context.
     */
    public function test_count_at_system_context() {
        $data = $this->setup_data();
        $this->create_pdfs($data);

        $count = certificate_issues_history::execute_count($data->target_user1, context_system::instance());
        $this->assertEquals(2, $count);

        $count = certificate_issues_history::execute_count($data->target_user2, context_system::instance());
        $this->assertEquals(1, $count);
    }

    /**
     * Test counts of certificate issues history for each user at the category context.
     */
    public function test_count_at_category_context() {
        $data = $this->setup_data();
        $this->create_pdfs($data);

        $count = certificate_issues_history::execute_count($data->target_user1, context_coursecat::instance($data->category1->id));
        $this->assertEquals(1, $count);

        $count = certificate_issues_history::execute_count($data->target_user2, context_coursecat::instance($data->category1->id));
        $this->assertEquals(0, $count);

        $count = certificate_issues_history::execute_count($data->target_user1, context_coursecat::instance($data->category2->id));
        $this->assertEquals(1, $count);

        $count = certificate_issues_history::execute_count($data->target_user2, context_coursecat::instance($data->category2->id));
        $this->assertEquals(1, $count);
    }

    /**
     * Test counts of certificate issues history for each user at the course context.
     */
    public function test_count_at_course_context() {
        $data = $this->setup_data();
        $this->create_pdfs($data);

        $count = certificate_issues_history::execute_count($data->target_user1, context_course::instance($data->course1->id));
        $this->assertEquals(1, $count);

        $count = certificate_issues_history::execute_count($data->target_user2, context_course::instance($data->course1->id));
        $this->assertEquals(0, $count);

        $count = certificate_issues_history::execute_count($data->target_user1, context_course::instance($data->course2->id));
        $this->assertEquals(1, $count);

        $count = certificate_issues_history::execute_count($data->target_user2, context_course::instance($data->course2->id));
        $this->assertEquals(1, $count);
    }

    /**
     * Test counts of certificate issues history for each user at the module context.
     */
    public function test_count_at_module_context() {
        $data = $this->setup_data();
        $this->create_pdfs($data);

        $count = certificate_issues_history::execute_count($data->target_user1, context_module::instance($data->certificate1->cmid));
        $this->assertEquals(1, $count);

        $count = certificate_issues_history::execute_count($data->target_user1, context_module::instance($data->certificate2->cmid));
        $this->assertEquals(1, $count);

        $count = certificate_issues_history::execute_count($data->target_user2, context_module::instance($data->certificate3->cmid));
        $this->assertEquals(1, $count);
    }

    /**
     * Test a purge of certificate issues history for each user at the system context.
     */
    public function test_purge_at_system_context() {
        $data = $this->setup_data();
        $this->create_pdfs($data);

        // Purge the certificate records of the user issues we want to remove.
        $result = certificate_issues_history::execute_purge($data->target_user1, context_system::instance());
        $this->assertEquals(mod_certificate\userdata\certificate_issues_history::RESULT_STATUS_SUCCESS, $result);

        // Check that all of user 1's certificate issue files have been deleted.
        $this->assert_certificate_file_record_exists(0, $data->target_user1->id, 0);

        // Use the count method to check no instances of the user issues exists.
        $count = certificate_issues_history::execute_count($data->target_user1, context_system::instance());
        $this->assertEquals(0, $count);

        // Check the certificate for our second user still exists.
        $count = certificate_issues_history::execute_count($data->target_user2, context_system::instance());
        $this->assertEquals(1, $count);

        // Check the document for user2's certificate issue 3 hasn't been deleted.
        $this->assert_certificate_file_record_exists($data->certificate_issue3->id, $data->target_user2->id, 1);
    }

    /**
     * Test a purge of certificate issues history for each user at the category context.
     */
    public function test_purge_at_category_context() {
        $data = $this->setup_data();
        $this->create_pdfs($data);

        // Purge the certificate records of the user issues we want to remove.
        $result = certificate_issues_history::execute_purge($data->target_user1, context_coursecat::instance($data->category1->id));
        $this->assertEquals(mod_certificate\userdata\certificate_issues_history::RESULT_STATUS_SUCCESS, $result);

        // Use the count method to check no instances of the user issues exists.
        $count = certificate_issues_history::execute_count($data->target_user1, context_coursecat::instance($data->category1->id));
        $this->assertEquals(0, $count);

        // Check that only 1 of user 1's certificate issue files have been deleted.
        $this->assert_certificate_file_record_exists(0, $data->target_user1->id, 1);

        // Check the user's other certificate still exists.
        $count = certificate_issues_history::execute_count($data->target_user1, context_coursecat::instance($data->category2->id));
        $this->assertEquals(1, $count);

        // Check the document for user2's certificate issue 3 hasn't been deleted.
        $this->assert_certificate_file_record_exists($data->certificate_issue2->id, $data->target_user1->id, 1);

        // Check the certificate for our second user still exists.
        $count = certificate_issues_history::execute_count($data->target_user2, context_coursecat::instance($data->category2->id));
        $this->assertEquals(1, $count);

        // Check the document for user2's certificate issue 3 hasn't been deleted.
        $this->assert_certificate_file_record_exists($data->certificate_issue3->id, $data->target_user2->id, 1);
    }

    /**
     * Test a purge of certificate issues history for each user at the course context.
     */
    public function test_purge_at_course_context() {
        $data = $this->setup_data();
        $this->create_pdfs($data);

        // Purge the certificate records of the user issues we want to remove.
        $result = certificate_issues_history::execute_purge($data->target_user1, context_course::instance($data->course1->id));
        $this->assertEquals(mod_certificate\userdata\certificate_issues_history::RESULT_STATUS_SUCCESS, $result);

        // Use the count method to check no instances of the user issues exists.
        $count = certificate_issues_history::execute_count($data->target_user1, context_course::instance($data->course1->id));
        $this->assertEquals(0, $count);

        // Check that only 1 of user 1's certificate issue files have been deleted.
        $this->assert_certificate_file_record_exists(0, $data->target_user1->id, 1);

        // Check the user's other certificate still exists.
        $count = certificate_issues_history::execute_count($data->target_user1, context_course::instance($data->course2->id));
        $this->assertEquals(1, $count);

        // Check the document for user2's certificate issue 3 hasn't been deleted.
        $this->assert_certificate_file_record_exists($data->certificate_issue2->id, $data->target_user1->id, 1);

        // Check the certificate for our second user still exists.
        $count = certificate_issues_history::execute_count($data->target_user2, context_course::instance($data->course2->id));
        $this->assertEquals(1, $count);

        // Check the document for user2's certificate issue 3 hasn't been deleted.
        $this->assert_certificate_file_record_exists($data->certificate_issue3->id, $data->target_user2->id, 1);
    }

    /**
     * Test a purge of certificate issues history for each user at the module context.
     */
    public function test_purge_at_module_context() {
        $data = $this->setup_data();
        $this->create_pdfs($data);
        
        // Purge the certificate records of the user issues we want to remove.
        $result = certificate_issues_history::execute_purge($data->target_user1, context_module::instance($data->certificate1->cmid));
        $this->assertEquals(mod_certificate\userdata\certificate_issues_history::RESULT_STATUS_SUCCESS, $result);

        // Use the count method to check no instances of the user issues exists.
        $count = certificate_issues_history::execute_count($data->target_user1, context_module::instance($data->certificate1->cmid));
        $this->assertEquals(0, $count);

        // Check that only 1 of user 1's certificate issue files have been deleted.
        $this->assert_certificate_file_record_exists(0, $data->target_user1->id, 1);

        // Check the user's other certificate still exists.
        $count = certificate_issues_history::execute_count($data->target_user1, context_module::instance($data->certificate2->cmid));
        $this->assertEquals(1, $count);
        $this->assert_certificate_file_record_exists($data->certificate_issue2->id, $data->target_user1->id, 1);

        // Check the certificate for our second user still exists.
        $count = certificate_issues_history::execute_count($data->target_user2, context_module::instance($data->certificate3->cmid));
        $this->assertEquals(1, $count);

        // Check the document for user2's certificate issue 3 hasn't been deleted.
        $this->assert_certificate_file_record_exists($data->certificate_issue3->id, $data->target_user2->id, 1);
    }

    /**
     * Test an export of certificate issues history for each user at the system context.
     */
    public function test_export_at_system_context() {
        $data = $this->setup_data();
        $this->create_pdfs($data);

        // Export data for the first user.
        $export = certificate_issues_history::execute_export($data->target_user1, context_system::instance());

        // Check that we've got two lots of certificate data and two files.
        $this->assertCount(2, $export->data);
        $certificate1_export = [];
        $certificate2_export = [];
        foreach ($export->data as $certificate) {
            if ($certificate['certificateid'] == $data->certificate1->id) {
                $certificate1_export = $certificate;
            } else if ($certificate['certificateid'] == $data->certificate2->id) {
                $certificate2_export = $certificate;
            } else {
                $this->fail('Unexpected data in export');
            }
        }
        $this->assertNotEmpty($certificate1_export);
        $this->assertNotEmpty($certificate2_export);

        $this->assertCount(2, $export->files);
        $this->assertArrayHasKey($certificate1_export['files'][0]['fileid'], $export->files);
        $this->assertArrayHasKey($certificate2_export['files'][0]['fileid'], $export->files);
        $this->assertEquals('Certificate_1.pdf', $certificate1_export['files'][0]['filename']);
        $this->assertEquals('Certificate_3.pdf', $certificate2_export['files'][0]['filename']);

        // Check the we've got the certificate 3 data and file for the second user.
        $export = certificate_issues_history::execute_export($data->target_user2, context_system::instance());

        // Check we've got just one issue and one file.
        $this->assertCount(1, $export->data);
        $this->assertEquals($data->certificate3->id, $export->data[0]['certificateid']);
        $this->assertCount(1, $export->files);
        $this->assertArrayHasKey($export->data[0]['files'][0]['fileid'], $export->files);
        $this->assertEquals('Certificate_5.pdf', $export->data[0]['files'][0]['filename']);

        // Check the data returned has the correct structure.
        $this->assertCount(10, $export->data[0]);
        $this->assertArrayHasKey('certificateid', $export->data[0]);
        $this->assertArrayHasKey('name', $export->data[0]);
        $this->assertArrayHasKey('intro', $export->data[0]);
        $this->assertArrayHasKey('code', $export->data[0]);
        $this->assertArrayHasKey('timecreated', $export->data[0]);
        $this->assertArrayHasKey('timecreated', $export->data[0]);
        $this->assertArrayHasKey('timearchived', $export->data[0]);
        $this->assertArrayHasKey('outcome', $export->data[0]);
        $this->assertArrayHasKey('grade', $export->data[0]);
        $this->assertArrayHasKey('files', $export->data[0]);
    }

    /**
     * Test an export of certificate issues history for each user at the system context.
     */
    public function test_export_at_category_context() {
        $data = $this->setup_data();
        $this->create_pdfs($data);

        // Export data for the first user.
        $export = certificate_issues_history::execute_export($data->target_user1, context_coursecat::instance($data->category1->id));

        // Check that we've got one certificate and one files.
        $this->assertCount(1, $export->data);
        $this->assertEquals($data->certificate1->id, $export->data[0]['certificateid']);
        $this->assertCount(1, $export->files);
        $this->assertArrayHasKey($export->data[0]['files'][0]['fileid'], $export->files);
        $this->assertEquals('Certificate_1.pdf', $export->data[0]['files'][0]['filename']);

        // Export data for the first user.
        $export = certificate_issues_history::execute_export($data->target_user1, context_coursecat::instance($data->category2->id));

        // Check we've got just one issue and one file.
        $this->assertCount(1, $export->data);
        $this->assertEquals($data->certificate2->id, $export->data[0]['certificateid']);
        $this->assertCount(1, $export->files);
        $this->assertArrayHasKey($export->data[0]['files'][0]['fileid'], $export->files);
        $this->assertEquals('Certificate_3.pdf', $export->data[0]['files'][0]['filename']);

        // Check the we've got the certificate 3 data and file for the second user.
        $export = certificate_issues_history::execute_export($data->target_user2, context_coursecat::instance($data->category2->id));

        // Check we've got just one issue and one file.
        $this->assertCount(1, $export->data);
        $this->assertEquals($data->certificate3->id, $export->data[0]['certificateid']);
        $this->assertCount(1, $export->files);
        $this->assertArrayHasKey($export->data[0]['files'][0]['fileid'], $export->files);
        $this->assertEquals('Certificate_5.pdf', $export->data[0]['files'][0]['filename']);

        // Check the data returned has the correct structure.
        $this->assertCount(10, $export->data[0]);
        $this->assertArrayHasKey('certificateid', $export->data[0]);
        $this->assertArrayHasKey('name', $export->data[0]);
        $this->assertArrayHasKey('intro', $export->data[0]);
        $this->assertArrayHasKey('code', $export->data[0]);
        $this->assertArrayHasKey('timecreated', $export->data[0]);
        $this->assertArrayHasKey('timecreated', $export->data[0]);
        $this->assertArrayHasKey('timearchived', $export->data[0]);
        $this->assertArrayHasKey('outcome', $export->data[0]);
        $this->assertArrayHasKey('grade', $export->data[0]);
        $this->assertArrayHasKey('files', $export->data[0]);
    }

    /**
     * Test an export of certificate issues history for each user at the course context.
     */
    public function test_export_at_course_context() {
        $data = $this->setup_data();
        $this->create_pdfs($data);

        // Export data for the first user.
        $export = certificate_issues_history::execute_export($data->target_user1, context_course::instance($data->course1->id));

        // Check that we've got one certificate and one file.
        $this->assertCount(1, $export->data);
        $this->assertEquals($data->certificate1->id, $export->data[0]['certificateid']);
        $this->assertCount(1, $export->files);
        $this->assertArrayHasKey($export->data[0]['files'][0]['fileid'], $export->files);
        $this->assertEquals('Certificate_1.pdf', $export->data[0]['files'][0]['filename']);

        // Export data for the first user.
        $export = certificate_issues_history::execute_export($data->target_user1, context_course::instance($data->course2->id));

        // Check that we've got one certificate and one file.
        $this->assertCount(1, $export->data);
        $this->assertEquals($data->certificate2->id, $export->data[0]['certificateid']);
        $this->assertCount(1, $export->files);
        $this->assertArrayHasKey($export->data[0]['files'][0]['fileid'], $export->files);
        $this->assertEquals('Certificate_3.pdf', $export->data[0]['files'][0]['filename']);

        // Check the we've got the certificate 3 data and file for the second user.
        $export = certificate_issues_history::execute_export($data->target_user2, context_course::instance($data->course2->id));

        // Check we've got just one issue and one file.
        $this->assertCount(1, $export->data);
        $this->assertEquals($data->certificate3->id, $export->data[0]['certificateid']);
        $this->assertCount(1, $export->files);
        $this->assertArrayHasKey($export->data[0]['files'][0]['fileid'], $export->files);
        $this->assertEquals('Certificate_5.pdf', $export->data[0]['files'][0]['filename']);

        // Check the data returned has the correct structure.
        $this->assertCount(10, $export->data[0]);
        $this->assertArrayHasKey('certificateid', $export->data[0]);
        $this->assertArrayHasKey('name', $export->data[0]);
        $this->assertArrayHasKey('intro', $export->data[0]);
        $this->assertArrayHasKey('code', $export->data[0]);
        $this->assertArrayHasKey('timecreated', $export->data[0]);
        $this->assertArrayHasKey('timecreated', $export->data[0]);
        $this->assertArrayHasKey('timearchived', $export->data[0]);
        $this->assertArrayHasKey('outcome', $export->data[0]);
        $this->assertArrayHasKey('grade', $export->data[0]);
        $this->assertArrayHasKey('files', $export->data[0]);
    }

    /**
     * Test an export of certificate issues history for each user at the module context.
     */
    public function test_export_at_module_context() {
        $data = $this->setup_data();
        $this->create_pdfs($data);

        // Export data for the first user.
        $export = certificate_issues_history::execute_export($data->target_user1, context_module::instance($data->certificate1->cmid));

        // Check that we've got one certificate and one file.
        $this->assertCount(1, $export->data);
        $this->assertEquals($data->certificate1->id, $export->data[0]['certificateid']);
        $this->assertCount(1, $export->files);
        $this->assertArrayHasKey($export->data[0]['files'][0]['fileid'], $export->files);
        $this->assertEquals('Certificate_1.pdf', $export->data[0]['files'][0]['filename']);

        // Export data for the first user.
        $export = certificate_issues_history::execute_export($data->target_user1, context_module::instance($data->certificate2->cmid));

        // Check that we've got one certificate and one file.
        $this->assertCount(1, $export->data);
        $this->assertEquals($data->certificate2->id, $export->data[0]['certificateid']);
        $this->assertCount(1, $export->files);
        $this->assertArrayHasKey($export->data[0]['files'][0]['fileid'], $export->files);
        $this->assertEquals('Certificate_3.pdf', $export->data[0]['files'][0]['filename']);

        // Check the we've got the certificate 3 data and file for the second user.
        $export = certificate_issues_history::execute_export($data->target_user2, context_module::instance($data->certificate3->cmid));

        // Check we've got just one issue and one file.
        $this->assertCount(1, $export->data);
        $this->assertEquals($data->certificate3->id, $export->data[0]['certificateid']);
        $this->assertCount(1, $export->files);
        $this->assertArrayHasKey($export->data[0]['files'][0]['fileid'], $export->files);
        $this->assertEquals('Certificate_5.pdf', $export->data[0]['files'][0]['filename']);

        // Check the data returned has the correct structure.
        $this->assertCount(10, $export->data[0]);
        $this->assertArrayHasKey('certificateid', $export->data[0]);
        $this->assertArrayHasKey('name', $export->data[0]);
        $this->assertArrayHasKey('intro', $export->data[0]);
        $this->assertArrayHasKey('code', $export->data[0]);
        $this->assertArrayHasKey('timecreated', $export->data[0]);
        $this->assertArrayHasKey('timecreated', $export->data[0]);
        $this->assertArrayHasKey('timearchived', $export->data[0]);
        $this->assertArrayHasKey('outcome', $export->data[0]);
        $this->assertArrayHasKey('grade', $export->data[0]);
        $this->assertArrayHasKey('files', $export->data[0]);
    }

}
