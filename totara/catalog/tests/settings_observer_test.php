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
 * @package totara_catalog
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/adminlib.php');

/**
 * @group totara_catalog
 */
class totara_catalog_settings_observer_testcase extends advanced_testcase {

    public function setUp() {
        $this->setAdminUser();
        $this->resetAfterTest();
    }

    public function test_turn_off_grid_catalog_observer() {
        global $DB;

        set_config('catalogtype', 'totara');
        // create a course
        $DB->delete_records('catalog');
        $this->getDataGenerator()->create_course();

        // create a program
        $program_generator = $this->getDataGenerator()->get_plugin_generator('totara_program');
        $program_generator->create_program();

        $this->assertSame(2, $DB->count_records('catalog'));

        // turn off grid catalog
        set_config('catalogtype', 'enhanced');
        $event = core\event\admin_settings_changed::create(
            [
                'context' => \context_system::instance(),
                'other'   =>
                    [
                        'olddata' => ['s__catalogtype' => 'totara'],
                    ],
            ]
        );
        $event->trigger();

        // check the result after event triggered
        $this->assertSame(0, $DB->count_records('catalog'));
    }

    public function test_turn_on_grid_catalog_observer() {
        global $DB;

        set_config('catalogtype', 'enhanced');
        // create a course
        $DB->delete_records('catalog');
        $DB->delete_records('task_adhoc');
        $this->getDataGenerator()->create_course();

        // create a program
        $program_generator = $this->getDataGenerator()->get_plugin_generator('totara_program');
        $program_generator->create_program();
        $DB->delete_records('catalog');
        $this->assertSame(0, $DB->count_records('catalog'));

        // turn on grid catalog
        set_config('catalogtype', 'totara');
        $event = core\event\admin_settings_changed::create(
            [
                'context' => \context_system::instance(),
                'other'   =>
                    [
                        'olddata' => ['s__catalogtype' => 'enhanced'],
                    ],
            ]
        );
        $event->trigger();

        $this->assertEquals(1, $DB->count_records('task_adhoc'));
        $task = \core\task\manager::get_next_adhoc_task(time());
        $task->execute();
        \core\task\manager::adhoc_task_complete($task);

        // check the result after adhoc task completed
        $this->assertSame(2, $DB->count_records('catalog'));
    }
}
