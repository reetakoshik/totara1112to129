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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package core_user
 */

use core_course\userdata\requests;
use totara_userdata\userdata\export;
use totara_userdata\userdata\item;
use totara_userdata\userdata\target_user;

defined('MOODLE_INTERNAL') || die();

/**
 * To test, run this from the command line from the $CFG->dirroot.
 * vendor/bin/phpunit --verbose core_course_userdata_request_testcase course/tests/userdata_requests_test.php
 *
 * @group totara_userdata
 */
class core_course_userdata_request_testcase extends advanced_testcase {

    protected function setUp() {
        global $CFG;

        parent::setUp();

        require_once($CFG->dirroot . '/course/lib.php');
    }

    /**
     * Test function is_compatible_context_level with all possible contexts.
     */
    public function test_get_compatible_context_levels() {
        $this->assertEquals(array(CONTEXT_SYSTEM), requests::get_compatible_context_levels());
    }

    /**
     * Test function is_purgeable.
     */
    public function test_is_purgeable() {
        $this->assertTrue(requests::is_purgeable(target_user::STATUS_ACTIVE));
        $this->assertTrue(requests::is_purgeable(target_user::STATUS_SUSPENDED));
        $this->assertTrue(requests::is_purgeable(target_user::STATUS_DELETED));
    }

    /**
     * Test function is_exportable.
     */
    public function test_is_exportable() {
        $this->assertTrue(requests::is_exportable());
    }

    /**
     * Test function is_countable.
     */
    public function test_is_countable() {
        $this->assertTrue(requests::is_countable());
    }

    private function setup_data() {
        $this->resetAfterTest(true);

        $data = new class() {
            /** @var stdClass */
            public $activeuser, $suspendeduser, $deleteduser;

            /** @var target_user */
            public $activetarget, $suspendedtarget, $deletedtarget;
        };

        $data->activeuser = $this->getDataGenerator()->create_user();
        $data->activetarget = new target_user($data->activeuser);
        $data->suspendeduser = $this->getDataGenerator()->create_user(['suspended' => 1]);
        $data->suspendedtarget = new target_user($data->suspendeduser);
        $data->deleteduser = $this->getDataGenerator()->create_user(['deleted' => 1]);
        $data->deletedtarget = new target_user($data->deleteduser);

        $this->setUser($data->activeuser);
        $record = new stdClass();
        $record->reason = 'This is a reason';
        $record->summary_editor['text'] = 'This is some text';
        $record->summary_editor['format'] = '';
        course_request::create($record);
        $record->reason = 'This is another reason';
        $record->summary_editor['text'] = 'This is another item';
        course_request::create($record);

        $this->setUser($data->suspendeduser);
        $record = new stdClass();
        $record->reason = 'This is a reason';
        $record->summary_editor['text'] = 'This is some text';
        $record->summary_editor['format'] = '';
        course_request::create($record);

        $this->setUser($data->deleteduser);
        $record = new stdClass();
        $record->reason = 'This is a reason';
        $record->summary_editor['text'] = 'This is some text';
        $record->summary_editor['format'] = '';
        course_request::create($record);

        return $data;
    }

    /**
     * Test if data is correctly purged.
     */
    public function test_purge() {
        global $DB;

        $data = $this->setup_data();

        $expectedrequests = $DB->get_records('course_request', [], 'id');
        $this->assertEquals(4, count($expectedrequests));

        // Purge delete user.
        foreach ($expectedrequests as $key => $expectedrequest) {
            if ($expectedrequest->requester == $data->deleteduser->id) {
                unset($expectedrequests[$key]);
            }
        }
        $result = requests::execute_purge($data->deletedtarget, context_system::instance());
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $result);
        $actualrequests = $DB->get_records('course_request', [], 'id');
        $this->assertEquals($expectedrequests, $actualrequests);

        $this->assertEquals(3, $DB->count_records('course_request', []));

        // Purge active user.
        foreach ($expectedrequests as $key => $expectedrequest) {
            if ($expectedrequest->requester == $data->activeuser->id) {
                unset($expectedrequests[$key]);
            }
        }
        $result = requests::execute_purge($data->activetarget, context_system::instance());
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $result);
        $actualrequests = $DB->get_records('course_request', [], 'id');
        $this->assertEquals($expectedrequests, $actualrequests);

        $this->assertEquals(1, $DB->count_records('course_request', []));
    }

    /**
     * Test if data is correctly counted.
     */
    public function test_count() {
        $data = $this->setup_data();

        // Do the count.
        $result = requests::execute_count($data->activetarget, context_system::instance());
        $this->assertEquals(2, $result);
        $result = requests::execute_count($data->suspendedtarget, context_system::instance());
        $this->assertEquals(1, $result);
        $result = requests::execute_count($data->deletedtarget, context_system::instance());
        $this->assertEquals(1, $result);

        requests::execute_purge(new target_user($data->suspendedtarget), context_system::instance());

        // Recount.
        $result = requests::execute_count($data->activetarget, context_system::instance());
        $this->assertEquals(2, $result);
        $result = requests::execute_count($data->suspendedtarget, context_system::instance());
        $this->assertEquals(0, $result);
        $result = requests::execute_count($data->deletedtarget, context_system::instance());
        $this->assertEquals(1, $result);
    }

    /**
     * Test if data is correctly exported.
     */
    public function test_export() {
        global $DB;

        $data = $this->setup_data();

        $expectedexport = new export();
        $expectedexport->data = $DB->get_records('course_request', ['requester' => $data->activeuser->id], "id",
            "id, fullname, shortname, summary, summaryformat, category, reason");
        $this->assertEquals($expectedexport, requests::execute_export($data->activetarget, context_system::instance()));

        $expectedexport = new export();
        $expectedexport->data = $DB->get_records('course_request', ['requester' => $data->deleteduser->id], "id",
            "id, fullname, shortname, summary, summaryformat, category, reason");
        $this->assertEquals($expectedexport, requests::execute_export($data->deletedtarget, context_system::instance()));
    }

}