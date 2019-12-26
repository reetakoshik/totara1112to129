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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package totara_catalog
 */

defined('MOODLE_INTERNAL') || die();

use totara_core\hook\manager;
use totara_catalog\hook\exclude_item;
use totara_catalog\catalog_retrieval;
use totara_catalog\output\catalog;

class totara_catalog_hook_testcase extends advanced_testcase {
    /**
     * @param int $max
     * @return stdClass[]
     */
    private function create_courses(int $max = 5): array {
        $i = 0;
        $data = [];
        $gen = $this->getDataGenerator();

        while ($i < $max) {
            $course = $gen->create_course([], ['createsections' => true]);
            $data[$course->id] = $course;

            $i++;
        }

        return $data;
    }

    /**
     * @param callable $callback
     * @return void
     */
    private function add_watcher(callable $callback): void {
        $refclass = new ReflectionClass(manager::class);
        $method = $refclass->getMethod('add_watchers');
        $method->setAccessible(true);

        $method->invokeArgs(
            null,
            [
                [
                    [
                        'hookname' => exclude_item::class,
                        'callback' => $callback,
                    ]
                ],
                __FILE__
            ]
        );
    }

    /**
     * @return void
     */
    public function test_remove_learning_items_in_course_catalog_hook(): void {
        $this->resetAfterTest(true);

        $this->create_courses(5);
        $courses = $this->create_courses();

        $callback = function (exclude_item $hook) use ($courses) {
            $item = $hook->get_item();
            if (isset($courses[$item->objectid])) {
                $hook->set_exclude(true);
            }
        };

        $this->add_watcher($callback);

        $catalog = new catalog_retrieval();
        $page = $catalog->get_page_of_objects(10, 0);

        $this->assertCount(5, $page->objects);

        // There were 5 items that got removed, therefore, expecting the $maxcount of the
        // page to be 5. Because, the maxcount was computed base on the number of skipped items.
        $this->assertEquals(5, $page->maxcount);

        $this->assertEquals(10, $page->limitfrom);
    }
}
