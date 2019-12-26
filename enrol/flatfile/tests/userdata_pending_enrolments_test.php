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
 * @author Brendan Cox <brendan.cox@totaralearning.com>
 * @package enrol_flatfile
 */

use enrol_flatfile\userdata\pending_enrolments;
use totara_userdata\userdata\target_user;

/**
 * Class enrol_flatfile_userdata_pending_enrolments_testcase
 *
 * @group totara_userdata
 */
class enrol_flatfile_userdata_pending_enrolments_testcase extends advanced_testcase {

    public function test_with_no_data() {
        $this->resetAfterTest(true);

        $user  = $this->getDataGenerator()->create_user();

        $result = pending_enrolments::execute_purge(
            new target_user($user),
            context_system::instance()
        );

        $this->assertEquals(pending_enrolments::RESULT_STATUS_SUCCESS, $result);

        $export = pending_enrolments::execute_export(
            new target_user($user),
            context_system::instance()
        );

        $this->assertEmpty($export->files);
        $this->assertEmpty($export->data);

        $count = pending_enrolments::execute_count(
            new target_user($user),
            context_system::instance()
        );

        $this->assertEquals(0, $count);
    }

    public function create_test_data() {
        global $DB;

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $category1 = $this->getDataGenerator()->create_category();
        $category2 = $this->getDataGenerator()->create_category();

        $course1 = $this->getDataGenerator()->create_course(['category' => $category1->id]);
        $course2 = $this->getDataGenerator()->create_course(['category' => $category2->id]);
        $course3 = $this->getDataGenerator()->create_course(['category' => $category2->id]);

        // Add 2 records per user per course.
        // There is no point going through the process of reading from a fixture file.
        // How data is transferred from csv file to database does not really affect testing here
        // and we also want to make sure the enrol process doesn't complete as that would then
        // remove these records before we could test on them.

        $newentry = new stdClass();
        $newentry->action = 'add';
        $newentry->roleid = 4; // We don't need to look for real role ids.
        $newentry->userid = $user1->id;
        $newentry->courseid = $course1->id;
        $newentry->timestart = 1234567890;
        $newentry->timeend = 1234567891;
        $newentry->timemodified = time();
        $DB->insert_record('enrol_flatfile', $newentry);

        $newentry->action = 'del';
        $DB->insert_record('enrol_flatfile', $newentry);

        $newentry->userid = $user2->id;
        $DB->insert_record('enrol_flatfile', $newentry);

        $newentry->action = 'add';
        $DB->insert_record('enrol_flatfile', $newentry);

        // On to course 2.
        $newentry->courseid = $course2->id;
        $DB->insert_record('enrol_flatfile', $newentry);

        $newentry->timeend = 0;
        $DB->insert_record('enrol_flatfile', $newentry);

        $newentry->userid = $user1->id;
        $DB->insert_record('enrol_flatfile', $newentry);

        $newentry->action = 'del';
        $DB->insert_record('enrol_flatfile', $newentry);

        // And course 3.
        $newentry->courseid = $course3->id;
        $DB->insert_record('enrol_flatfile', $newentry);

        $newentry->roleid = 7;
        $DB->insert_record('enrol_flatfile', $newentry);

        $newentry->userid = $user2->id;
        $DB->insert_record('enrol_flatfile', $newentry);

        $newentry->timestart = 0;
        $DB->insert_record('enrol_flatfile', $newentry);

        return [
            'user1' => $user1,
            'user2' => $user2,
            'category1' => $category1,
            'category2' => $category2,
            'course1' => $course1,
            'course2' => $course2,
            'course3' => $course3
        ];
    }

    public function test_export_system_context() {
        $data = $this->create_test_data();

        $export = pending_enrolments::execute_export(
            new target_user($data['user1']),
            context_system::instance()
        );

        $this->assertEmpty($export->files);
        $this->assertCount(6, $export->data);
        foreach($export->data as $record) {
            $this->assertEquals($data['user1']->id, $record->userid);
        }
    }

    public function test_count_system_context() {
        $data = $this->create_test_data();

        $count = pending_enrolments::execute_count(
            new target_user($data['user1']),
            context_system::instance()
        );

        $this->assertEquals(6, $count);

        return $data;
    }

    public function test_purge_system_context() {
        global $DB;

        $data = $this->create_test_data();

        $result = pending_enrolments::execute_purge(
            new target_user($data['user1']),
            context_system::instance()
        );

        $this->assertEquals(pending_enrolments::RESULT_STATUS_SUCCESS, $result);

        $this->assertEquals(0, $DB->count_records('enrol_flatfile', ['userid' => $data['user1']->id]));
        $this->assertEquals(6, $DB->count_records('enrol_flatfile', ['userid' => $data['user2']->id]));
    }

    public function test_export_category_context() {
        $data = $this->create_test_data();

        $export = pending_enrolments::execute_export(
            new target_user($data['user1']),
            context_coursecat::instance($data['category2']->id)
        );

        $this->assertEmpty($export->files);
        $this->assertCount(4, $export->data);
        foreach($export->data as $record) {
            $this->assertEquals($data['user1']->id, $record->userid);
            $this->assertNotEquals($data['course1']->id, $record->courseid);
        }
    }

    public function test_count_category_context() {
        $data = $this->create_test_data();

        $count = pending_enrolments::execute_count(
            new target_user($data['user1']),
            context_coursecat::instance($data['category2']->id)
        );

        $this->assertEquals(4, $count);
    }

    public function test_purge_category_context() {
        global $DB;

        $data = $this->create_test_data();

        $result = pending_enrolments::execute_purge(
            new target_user($data['user1']),
            context_coursecat::instance($data['category2']->id)
        );

        $this->assertEquals(pending_enrolments::RESULT_STATUS_SUCCESS, $result);

        $user1records = $DB->get_records('enrol_flatfile', ['userid' => $data['user1']->id]);
        $this->assertCount(2, $user1records);
        foreach ($user1records as $user1record) {
            $this->assertEquals($data['course1']->id, $user1record->courseid);
        }

        $this->assertEquals(6, $DB->count_records('enrol_flatfile', ['userid' => $data['user2']->id]));
    }

    public function test_export_course_context() {
        $data = $this->create_test_data();

        $export = pending_enrolments::execute_export(
            new target_user($data['user1']),
            context_course::instance($data['course3']->id)
        );

        $this->assertEmpty($export->files);
        $this->assertCount(2, $export->data);
        foreach($export->data as $record) {
            $this->assertEquals($data['user1']->id, $record->userid);
            $this->assertEquals($data['course3']->id, $record->courseid);
        }
    }

    public function test_count_course_context() {
        $data = $this->create_test_data();

        $count = pending_enrolments::execute_count(
            new target_user($data['user1']),
            context_course::instance($data['course3']->id)
        );

        $this->assertEquals(2, $count);
    }

    public function test_purge_course_context() {
        global $DB;
        $data = $this->create_test_data();

        $result = pending_enrolments::execute_purge(
            new target_user($data['user1']),
            context_course::instance($data['course3']->id)
        );

        $this->assertEquals(pending_enrolments::RESULT_STATUS_SUCCESS, $result);

        $user1records = $DB->get_records('enrol_flatfile', ['userid' => $data['user1']->id]);
        $this->assertCount(4, $user1records);
        foreach ($user1records as $user1record) {
            $this->assertNotEquals($data['course3']->id, $user1record->courseid);
        }

        $this->assertEquals(6, $DB->count_records('enrol_flatfile', ['userid' => $data['user2']->id]));
    }
}
