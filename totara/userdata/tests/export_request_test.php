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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Samantha Jayasinghe <samantha.jayasinghe@totaralearning.com>
 * @package totara_userdata
 */

use totara_userdata\userdata\export_request;
use totara_userdata\userdata\target_user;
use totara_userdata\userdata\item;
use totara_userdata\userdata\manager;

defined('MOODLE_INTERNAL') || die();

/**
 * Test purging, exporting and counting of userdata export
 * @group totara_userdata
 */
class totara_userdata_export_request_testcase extends advanced_testcase {

    /**
     * @var file_storage
     */
    private $filestorage = null;

    /**
     * @var context
     */
    private $systemcontext = null;

    protected function setUp() {
        parent::setUp();
        $this->systemcontext = context_system::instance();
        $this->filestorage = get_file_storage();
    }

    protected function tearDown() {
        $this->filestorage = null;
        $this->systemcontext = null;

        parent::tearDown();
    }

    /**
     * Test the abilities to purge, export and count
     */
    public function test_abilities() {
        $this->assertTrue(export_request::is_countable());
        $this->assertFalse(export_request::is_exportable());
        $this->assertTrue(export_request::is_purgeable(target_user::STATUS_ACTIVE));
        $this->assertTrue(export_request::is_purgeable(target_user::STATUS_SUSPENDED));
        $this->assertTrue(export_request::is_purgeable(target_user::STATUS_DELETED));
    }

    /**
     * test count when user has no export request
     */
    public function test_count_when_user_has_no_export_request() {
        $this->resetAfterTest(true);

        $user = new target_user($this->getDataGenerator()->create_user());
        $result = export_request::execute_count($user, $this->systemcontext);
        $this->assertEquals(0, $result);
    }

    /**
     * test count when user has export request
     */
    public function test_count_when_user_has_export_request() {
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        $targetuser = new target_user($user);
        $this->create_export_file($user);
        $result = export_request::execute_count($targetuser, $this->systemcontext);

        $this->assertEquals(1, $result);
    }

    /**
     * test purge when user has export request
     */
    public function test_purge_when_user_has_export_request() {
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        $targetuser = new target_user($user);
        $exportfileid = $this->create_export_file($user);

        //before purge
        $filecount = export_request::execute_count($targetuser, $this->systemcontext);
        $this->assertEquals(1, $filecount);

        //purge export request
        $result = export_request::execute_purge($targetuser, $this->systemcontext);
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $result);

        //after purge
        $filecount = export_request::execute_count($targetuser, $this->systemcontext);
        $this->assertEquals(0, $filecount);

        $exportfile = $this->filestorage->get_area_files($this->systemcontext->id, 'totara_userdata', 'export', $exportfileid);
        $this->assertEmpty($exportfile);
    }

    /**
     * test purge when user has no export request
     */
    public function test_purge_when_user_has_no_export_request() {
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        $targetuser = new target_user($user);

        //before purge
        $filecount = export_request::execute_count($targetuser, $this->systemcontext);
        $this->assertEquals(0, $filecount);

        //purge export request
        $result = export_request::execute_purge($targetuser, $this->systemcontext);
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $result);

        //after purge
        $filecount = export_request::execute_count($targetuser, $this->systemcontext);
        $this->assertEquals(0, $filecount);
    }

    /**
     * Create export file
     *
     * @param stdClass $user
     *
     * @return int
     */
    private function create_export_file(stdClass $user) {
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_userdata');
        $type = $generator->create_export_type(array('allowself' => 1));

        $exportid = manager::create_export($user->id, $this->systemcontext->id, $type->id, 'self');

        $filerecord = [
            'component' => 'totara_userdata',
            'filearea'  => 'export',
            'contextid' => $this->systemcontext->id,
            'itemid'    => $exportid,
            'filename'  => 'export.tgz',
            'filepath'  => '/test/',
        ];
        $this->filestorage->create_file_from_string($filerecord, 'test1');

        return $exportid;
    }
}
