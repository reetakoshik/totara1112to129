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
 * @package totara_program
 * @category totara_catalog
 */

defined('MOODLE_INTERNAL') || die();

/**
 * @group totara_catalog
 */
class totara_program_totara_catalog_settings_observer_testcase extends advanced_testcase {

    public function setUp() {
        global $DB;

        $this->setAdminUser();
        $DB->delete_records('catalog');
        $DB->delete_records('task_adhoc');
        $this->resetAfterTest();
    }

    public function test_turn_off_program_observer() {
        global $DB;

        // create one course , one program and one certification
        set_config('enableprograms', 1);
        $this->create_catalog_objects();

        $this->assertSame(1, $DB->count_records('catalog', ['objecttype' => 'program']));
        $this->assertSame(3, $DB->count_records('catalog'));

        // turn off programs
        set_config('enableprograms', TOTARA_DISABLEFEATURE);
        $event = core\event\admin_settings_changed::create(
            [
                'context' => \context_system::instance(),
                'other'   =>
                    [
                        'olddata' => ['s__enableprograms' => 1],
                    ],
            ]
        );
        $event->trigger();

        // check the result after event triggered
        $this->assertSame(0, $DB->count_records('catalog', ['objecttype' => 'program']));
        $this->assertSame(2, $DB->count_records('catalog'));
    }

    public function test_turn_on_program_observer() {
        global $DB;

        // create one course , one program and one certification
        set_config('enableprograms', TOTARA_DISABLEFEATURE);
        $this->create_catalog_objects();

        $this->assertSame(0, $DB->count_records('catalog', ['objecttype' => 'program']));
        $this->assertSame(2, $DB->count_records('catalog'));

        // turn on programs
        set_config('enableprograms', 1);
        $event = core\event\admin_settings_changed::create(
            [
                'context' => \context_system::instance(),
                'other'   =>
                    [
                        'olddata' => ['s__enableprograms' => TOTARA_DISABLEFEATURE],
                    ],
            ]
        );
        $event->trigger();

        $this->assertEquals(1, $DB->count_records('task_adhoc'));
        $task = \core\task\manager::get_next_adhoc_task(time());
        totara_catalog\cache_handler::reset_all_caches();
        $task->execute();
        \core\task\manager::adhoc_task_complete($task);

        // check the result after adhoc task completed
        $this->assertSame(1, $DB->count_records('catalog', ['objecttype' => 'program']));
        $this->assertSame(3, $DB->count_records('catalog'));
    }

    private function create_catalog_objects() {

        // create a program
        $program_generator = $this->getDataGenerator()->get_plugin_generator('totara_program');
        $program_generator->create_program();

        // create a course
        $this->getDataGenerator()->create_course();

        // create a certification
        $program_generator->create_certification();
    }
}
