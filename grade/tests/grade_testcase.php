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
 * @author Aleksandr Baishev <aleksandr.baishev@totaralearning.com>
 * @package core_grades
 */

defined('MOODLE_INTERNAL') || die();

/**
 * This is a base class for all grade tests, contains a few useful helpers to create dummy test data
 *
 * Class grade_testcase
 *
 * @group core_grades
 * @group totara_userdata
 */
abstract class grade_testcase extends advanced_testcase {

    /**
     * Return component data generator
     *
     * @return \core_grades_generator
     */
    protected function generator() {
        return $this->getDataGenerator()->get_plugin_generator('core_grades');
    }

    /**
     * Create 2 users, 2 course categories with two courses inside and enrol users.
     *
     * @return array of users and created course cat and course ids
     */
    protected function scaffold() {
        global $DB;

        $this->resetAfterTest();

        $student = $DB->get_record('role', ['shortname' => 'student'])->id;

        $data = [
            'users' => [
                $this->getDataGenerator()->create_user(),
                $this->getDataGenerator()->create_user(),
            ],
            'categories' => [
                $cat = $this->getDataGenerator()->create_category()->id => [
                    $this->getDataGenerator()->create_course(['category' => $cat])->id,
                    $this->getDataGenerator()->create_course(['category' => $cat])->id,
                ],
                $cat = $this->getDataGenerator()->create_category()->id => [
                    $this->getDataGenerator()->create_course(['category' => $cat])->id,
                    $this->getDataGenerator()->create_course(['category' => $cat])->id,
                ],
            ],
        ];

        // Enrol users.
        foreach ($data['categories'] as $courses) {
            foreach ($courses as $course) {
                foreach ($data['users'] as $user) {
                    $this->getDataGenerator()->enrol_user($user->id, $course, $student);
                }
            }
        }

        return $data;
    }

    /**
     * Seed course with some course modules and grades for selected users.
     *
     * @param int $id Target course id
     * @param array|\stdClass $users User(s) to create grades for
     * @param array|null $data supply an array of module => [grades] to override default values.
     * @return array
     */
    protected function seed_course($id, $users, $data = null) {
        $modules = [
            'assign' => $this->generator()->get_item_for_module($id, 'assign',
                $this->getDataGenerator()->create_module('assign', ['course' => $id])->id),

            'f2f' => $this->generator()->get_item_for_module($id, 'facetoface',
                $this->getDataGenerator()->create_module('facetoface', ['course' => $id])->id),
        ];

        if (!is_array($users)) {
            $users = [$users];
        }

        if (is_null($data)) {
            $data = [
                'assign' => [10, 20, 30],
                'f2f' => [40, 50, 60],
            ];
        }

        $result = [];

        foreach ($users as $user) {
            foreach ($modules as $module => $item) {
                if (isset($data[$module])) {
                    foreach ($data[$module] as $value) {
                        $result[] = [
                            'module' => $module == 'f2f' ? 'facetoface' : 'assign',
                            'item' => $item,
                            'grade' => $this->generator()->new_grade_for_item($item, $value, $user),
                            'user' => $user,
                        ];
                    }
                }
            }
        }

        return $result;
    }
}