<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @package totara_reportbuilder
 */

defined('MOODLE_INTERNAL') || die();

use totara_reportbuilder\rb\display\user_link;

class totara_reportbuilder_user_link_display_testcase extends advanced_testcase {
    /**
     * @param stdClass $user
     *
     * @return stdClass
     */
    private function build_data_row(\stdClass $user): \stdClass {
        $row = new \stdClass();
        $keys = [
            'id' => $user->id,
            'lastname' => $user->lastname,
            'firstname' => $user->firstname,
            'deleted' => $user->deleted
        ];

        foreach ($keys as $key => $value) {
            $ef = \reportbuilder_get_extrafield_alias('user', 'namelink', $key);
            $row->{$ef} = $value;
        }

        return $row;
    }

    /**
     * @param reportbuilder $report
     * @param string        $key    Which is a {$column->type}-{$column->value}
     *
     * @return \rb_column
     */
    private function find_column(\reportbuilder $report, string $key): \rb_column {
        $columns = $report->get_columns();
        if (!isset($columns[$key])) {
            throw new \coding_exception("No column found for key: '{$key}'");
        }

        return $columns[$key];
    }

    /**
     * @param stdClass  $course
     * @param int       $userid
     * @param string    $enroltype
     *
     * @return void
     */
    private function un_enroll_user(\stdClass $course, int $userid, string $enroltype = 'manual'): void {
        global $DB;

        $plugin = enrol_get_plugin($enroltype);
        if (!$plugin) {
            throw new \coding_exception('No enrol plugin found');
        }


        $instance = $DB->get_record(
            'enrol',
            [
                'courseid' => $course->id,
                'enrol' => 'manual'
            ]
        );

        $plugin->unenrol_user($instance, $userid);
    }
    /**
     * @return void
     */
    public function test_rendering_userlink_in_completion_editor(): void {
        global $USER, $PAGE;

        $this->setAdminUser();
        $this->resetAfterTest(true);

        $gen = $this->getDataGenerator();
        $course = $gen->create_course();

        $gen->enrol_user($USER->id, $course->id, 'student');
        $PAGE->set_course($course);

        $users = [];
        for ($i = 0; $i < 2; $i++) {
            $user = $gen->create_user();
            $gen->enrol_user($user->id, $course->id, 'student');

            $users[] = $user;
        }

        $config = new \rb_config();
        $config->set_embeddata(['courseid' => $course->id]);

        $report = \reportbuilder::create_embedded('course_membership', $config);
        $column = $this->find_column($report, 'user-namelink');

        foreach ($users as $user) {
            $row = $this->build_data_row($user);

            $value = user_link::display(
                "{$user->firstname} {$user->lastname}",
                'html',
                $row,
                $column,
                $report
            );

            $this->assertNotEmpty($value);
            $this->assertContains("course={$course->id}", $value);
        }

        foreach ($users as $user) {
            $this->un_enroll_user($course, $user->id);
            $row = $this->build_data_row($user);

            $value = user_link::display(
                "{$user->firstname} {$user->lastname}",
                'html',
                $row,
                $column,
                $report
            );

            $this->assertNotEmpty($value);
            $this->assertNotContains("course={$course->id}", $value);
            $this->assertContains("id={$user->id}", $value);
        }
    }
}