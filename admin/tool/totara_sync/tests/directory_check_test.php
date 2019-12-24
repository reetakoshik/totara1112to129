<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2017 onwards Totara Learning Solutions LTD
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
 * @author Simon Player <simon.player@totaralearning.com>
 * @package tool_totara_sync
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/admin/tool/totara_sync/lib.php');

/**
 * @group tool_totara_sync
 */
class tool_totara_sync_directory_check_testcase extends advanced_testcase {

    private $filedir = null;

    public function setUp() {
        global $CFG;

        parent::setup();
        $this->resetAfterTest(true);

        $this->filedir = $CFG->dataroot . '/totara_sync';
    }

    protected function tearDown() {
        $this->filedir = null;
        parent::tearDown();
    }

    public function test_totara_sync_make_dirs() {
        global $CFG;

        //
        // Create a new directory structure.
        //
        $dirpath = $this->filedir . '/test/make/dirs';

        // First check it doesn't already exist.
        $this->assertFalse(is_dir($dirpath));

        // Ok, now create it.
        $result = totara_sync_make_dirs($dirpath);
        $this->assertTrue($result);
        $this->assertTrue(is_dir($dirpath));

        //
        // Attempt to recreate the directory structure.
        //
        $dirpath = $this->filedir . '/test/make/dirs';
        $result = totara_sync_make_dirs($dirpath);
        $this->assertTrue($result);
        $this->assertTrue(is_dir($dirpath));

        // NOTE: It's not possible to test the function returning false without changing server config etc..
    }

}
