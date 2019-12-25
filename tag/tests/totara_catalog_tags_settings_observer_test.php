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
 * @package core_tag
 * @category totara_catalog
 */

defined('MOODLE_INTERNAL') || die();

/**
 * @group totara_catalog
 */
class core_tag_totara_catalog_settings_observer_testcase extends advanced_testcase {

    public function setUp() {
        global $DB;

        $this->setAdminUser();
        $DB->delete_records('catalog');
        $DB->delete_records('task_adhoc');
        $this->resetAfterTest();
    }

    public function test_tags_observer() {
        global $DB;

        // create a course
        set_config('usetags', 1);
        $DB->delete_records('task_adhoc');
        $course = $this->getDataGenerator()->create_course();

        core_tag_tag::add_item_tag(
            'core',
            'course',
            $course->id,
            context_system::instance(),
            'newtagname'
        );

        // trigger course update event to update catalog data
        $course_update_event = \core\event\course_updated::create(
            [
                'objectid' => $course->id,
                'context'  => context_system::instance(),
                'other'    => ['fulname' => 'newfullname'],
            ]
        );

        $course_update_event->trigger();
        $data = $DB->get_record('catalog', ['objecttype' => 'course']);
        $this->assertContains('newtagname', $data->ftsmedium);

        // turn off tags
        set_config('usetags', 0);
        $event = core\event\admin_settings_changed::create(
            [
                'context' => \context_system::instance(),
                'other'   =>
                    [
                        'olddata' => ['s__usetags' => 1],
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
        $this->assertSame(1, $DB->count_records('catalog'));
        $data = $DB->get_record('catalog', ['objecttype' => 'course']);
        $this->assertNotContains('newtagname', $data->ftsmedium);
    }
}
