<?php
/*
 * This file is part of Totara LMS
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
 * @author Carl Anderson <carl.anderson@totaralearning.com>
 * @author Oleg Demeshev <oleg.demeshev@totaralearning.com>
 * @package report_completion
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

/**
 * Class report_completion_events_testcase.
 */
class report_completion_lib_testcase extends advanced_testcase {

    protected function tearDown() {
        parent::tearDown();
    }

    public function setUp() {
        set_config('enablecompletion', true);
        $this->resetAfterTest();
    }

    /**
     * Tests the report_completion_myprofile_navigation() function as an admin viewing completion report for a user.
     */
    public function test_report_completion_myprofile_navigation() {
        global $CFG;
        require_once($CFG->dirroot.'/report/completion/lib.php');

        // Set as the admin.
        $this->setAdminUser();

        $user = $this->getDataGenerator()->create_user();
        $tree = new \core_user\output\myprofile\tree();

        // Check the node tree is correct.
        report_completion_myprofile_navigation($tree, $user, true, null);
        $reflector = new ReflectionObject($tree);
        $nodes = $reflector->getProperty('nodes');
        $nodes->setAccessible(true);
        $this->assertArrayHasKey('completion', $nodes->getValue($tree));
    }

    /**
     * Tests the report_completion_myprofile_navigation() function as a user without permission.
     */
    public function test_report_completion_myprofile_navigation_without_permission() {
        global $CFG;
        require_once($CFG->dirroot.'/report/completion/lib.php');

        $user = $this->getDataGenerator()->create_user();
        $tree = new \core_user\output\myprofile\tree();

        // Set to the other user.
        $this->setUser($user);

        // Check the node tree is correct.
        report_completion_myprofile_navigation($tree, $user, true, null);
        $reflector = new ReflectionObject($tree);
        $nodes = $reflector->getProperty('nodes');
        $nodes->setAccessible(true);
        $this->assertArrayNotHasKey('completion', $nodes->getValue($tree));
    }
}
