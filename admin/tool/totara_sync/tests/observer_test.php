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
 * @author Alastair Munro <alastair.munro@totaralearning.com>
 * @package tool_totara_sync
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/admin/tool/totara_sync/lib.php');

/**
 * @group tool_totara_sync
 */
class tool_totara_sync_observer_testcase extends advanced_testcase {
    /**
     * Test set up.
     */
    public function setUp() {
        $this->resetAfterTest(true);
    }

    function test_profilefield_updated() {
        $csvplugin = 'totara_sync_source_user_csv';
        $databaseplugin = 'totara_sync_source_user_csv';

        // CSV
        set_config('import_customfield_Test1', '1', $csvplugin);
        set_config('fieldmapping_customfield_Test1', 'chicken', $csvplugin);

        // Also test database
        set_config('import_customfield_Test1', '1', $databaseplugin);
        set_config('fieldmapping_customfield_Test1', 'chicken', $databaseplugin);

        $eventdata = new stdClass();
        $eventdata->objectid = 1;
        $eventdata->oldshortname = 'Test1';
        $eventdata->shortname = 'Working1';

        $event = \totara_customfield\event\profilefield_updated::create_from_field($eventdata);

        \tool_totara_sync\observer::profilefield_updated($event);

        $actual = get_config($csvplugin, 'import_customfield_Working1');
        $this->assertEquals(1, $actual);

        $actual = get_config($csvplugin, 'fieldmapping_customfield_Working1');
        $this->assertEquals('chicken', $actual);

        // Check database fields.
        $actual = get_config($databaseplugin, 'import_customfield_Working1');
        $this->assertEquals(1, $actual);

        $actual = get_config($databaseplugin, 'fieldmapping_customfield_Working1');
        $this->assertEquals('chicken', $actual);
    }
}
