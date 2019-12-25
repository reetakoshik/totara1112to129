<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2015 onwards Totara Learning Solutions LTD
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
 * @author Nathan Lewis <nathan.lewis@totaralearning.com>
 * @package tool_totara_sync
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/admin/tool/totara_sync/db/upgradelib.php');

/**
 * Class tool_totara_sync_upgradelib_testcase
 *
 * To test, run this from the command line from the $CFG->dirroot.
 * vendor/bin/phpunit --verbose tool_totara_sync_upgradelib_testcase admin/tool/totara_sync/tests/upgradelib_test.php
 *
 * @group tool_totara_sync
 */
class tool_totara_sync_upgradelib_testcase extends advanced_testcase {

    public function test_tool_totara_sync_upgrade_link_job_assignment_mismatch() {

        $this->resetAfterTest();

        // Make sure there's no problem if the current settings haven't been defined.
        unset_config('linkjobassignmentidnumber', 'totara_sync_element_user');
        unset_config('linkjobassignmentidnumber', 'totara_sync');
        unset_config('previouslylinkedonjobassignmentidnumber', 'totara_sync_element_user');

        // Run the upgrade.
        tool_totara_sync_upgrade_link_job_assignment_mismatch();

        // See that the new config setting is also empty.
        $linksetting = get_config('totara_sync_element_user', 'linkjobassignmentidnumber');
        $this->assertEmpty($linksetting);

        $previousimport = get_config('totara_sync_element_user', 'previouslylinkedonjobassignmentidnumber');
        $this->assertEmpty($previousimport);

        $oldpreviousimport = get_config('totara_sync', 'linkjobassignmentidnumber');
        $this->assertSame(false, $oldpreviousimport);

        // See that upgrade is ok when import has never been linked on job assignment id number.
        set_config('linkjobassignmentidnumber', 0, 'totara_sync_element_user');
        set_config('linkjobassignmentidnumber', 0, 'totara_sync');
        unset_config('previouslylinkedonjobassignmentidnumber', 'totara_sync_element_user');

        // Run the upgrade.
        tool_totara_sync_upgrade_link_job_assignment_mismatch();

        // See that the new config setting is also empty.
        $linksetting = get_config('totara_sync_element_user', 'linkjobassignmentidnumber');
        $this->assertSame('0', $linksetting);

        $previousimport = get_config('totara_sync_element_user', 'previouslylinkedonjobassignmentidnumber');
        $this->assertSame('0', $previousimport);

        $oldpreviousimport = get_config('totara_sync', 'linkjobassignmentidnumber');
        $this->assertSame(false, $oldpreviousimport);

        // See that upgrade is ok when import has been linked on job assignment id number - check the settings are fixed.
        set_config('linkjobassignmentidnumber', 0, 'totara_sync_element_user');
        set_config('linkjobassignmentidnumber', 1, 'totara_sync');
        unset_config('previouslylinkedonjobassignmentidnumber', 'totara_sync_element_user');

        // Run the upgrade.
        tool_totara_sync_upgrade_link_job_assignment_mismatch();

        // See that the new config setting is also empty.
        $linksetting = get_config('totara_sync_element_user', 'linkjobassignmentidnumber');
        $this->assertSame('1', $linksetting);

        $previousimport = get_config('totara_sync_element_user', 'previouslylinkedonjobassignmentidnumber');
        $this->assertSame('1', $previousimport);

        $oldpreviousimport = get_config('totara_sync', 'linkjobassignmentidnumber');
        $this->assertSame(false, $oldpreviousimport);

        // See that the upgrade uses the new config setting if it already exists.
        set_config('previouslylinkedonjobassignmentidnumber', 1, 'totara_sync_element_user');
        set_config('linkjobassignmentidnumber', 0, 'totara_sync_element_user'); // Will be fixed.
        set_config('linkjobassignmentidnumber', 0, 'totara_sync'); // Existing new setting will override this.

        // Run the upgrade.
        tool_totara_sync_upgrade_link_job_assignment_mismatch();

        // See that the new config setting is also empty.
        $linksetting = get_config('totara_sync_element_user', 'linkjobassignmentidnumber');
        $this->assertSame('1', $linksetting);

        $previousimport = get_config('totara_sync_element_user', 'previouslylinkedonjobassignmentidnumber');
        $this->assertSame('1', $previousimport);

        $oldpreviousimport = get_config('totara_sync', 'linkjobassignmentidnumber');
        $this->assertSame(false, $oldpreviousimport);
    }

}
