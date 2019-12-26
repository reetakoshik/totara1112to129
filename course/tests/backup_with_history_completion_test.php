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
 * @package core_course
 */

defined('MOODLE_INTERNAL') || die();

class core_course_backup_with_history_completion_testcase extends advanced_testcase {
    /**
     * A test suite to check whether the process of completion history will be executed or not depending on the course's
     * completion setting (enabled/disabled). When a course has a completion history and it had disabled completion,
     * then the restore process should progress on completion history instead of exception thrown.
     *
     * @return void
     */
    public function test_duplicating_course_with_completion_record_and_disabling_completion(): void {
        global $CFG, $DB;

        require_once("{$CFG->dirroot}/course/lib.php");
        require_once("{$CFG->dirroot}/course/externallib.php");

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $gen = $this->getDataGenerator();
        $course = $gen->create_course(
            [
                'enablecompletion' => 1,
                'completionstartonenrol' => 1
            ],
            []
        );

        // Setting up the completion criteria of the course
        /** @var \core_completion_generator $completiongen */
        $completiongen = $gen->get_plugin_generator('core_completion');
        $completiongen->set_completion_criteria($course, [COMPLETION_CRITERIA_TYPE_SELF => 1]);
        $userstobechecked = [];

        for ($i = 0; $i < 2; $i++) {
            // Enrol user to the course
            $user = $gen->create_user();
            $gen->enrol_user($user->id, $course->id, 'student');
            $completiongen->complete_course($course, $user);

            // After the completion has been set up successfully, start archiving it and then backup-restore the course.
            archive_course_completion($user->id, $course->id);

            $userstobechecked[] = $user->id;
        }

        // Third user without completion record archived.
        $user = $gen->create_user();
        $gen->enrol_user($user->id, $course->id, 'student');
        $completiongen->complete_course($course, $user);

        // Start disabling `enablecompletion` for the course, so that we can test whether the restore is
        // being processed with completion history or not.
        $course->enablecompletion = 0;
        $DB->update_record('course', $course);

        $result = \core_course_external::duplicate_course(
            $course->id,
            'Duplicated course',
            'dccc',
            $course->category
        );

        $this->assertArrayHasKey('id', $result);
        $newcourseid = (int) $result['id'];

        foreach ($userstobechecked as $userid) {
            $this->assertFalse(
                $DB->record_exists(
                    'course_completions',
                    [
                        'course' => $newcourseid,
                        'userid' => $userid
                    ]
                )
            );

            $this->assertTrue(
                $DB->record_exists(
                    'course_completion_history',
                    [
                        'courseid' => $newcourseid,
                        'userid' => $userid
                    ]
                )
            );
        }
    }

    /**
     * @return void
     */
    public function test_duplicating_course_with_completion_record_and_enabling_completion(): void {
        global $DB, $CFG;

        require_once("{$CFG->dirroot}/course/lib.php");
        require_once("{$CFG->dirroot}/course/externallib.php");

        $this->resetAfterTest();
        $this->setAdminUser();

        $gen = $this->getDataGenerator();
        $course = $gen->create_course(
            [
                'enablecompletion' => 1,
                'completionstartonenrol' => 1
            ]
        );

        /** @var \core_completion_generator $completiongen */
        $completiongen = $gen->get_plugin_generator('core_completion');
        $completiongen->set_completion_criteria($course, [COMPLETION_CRITERIA_TYPE_SELF => 1]);
        $userstobechecked = [];

        for ($i = 0; $i < 2; $i++) {
            $user = $gen->create_user();
            $gen->enrol_user($user->id, $course->id);

            $completiongen->complete_course($course, $user);
            archive_course_completion($user->id, $course->id);

            $userstobechecked[] = $user->id;
        }

        // Third user without completion archived.
        $user = $gen->create_user();
        $gen->enrol_user($user->id, $course->id);
        $completiongen->complete_course($course, $user);

        $result = \core_course_external::duplicate_course(
            $course->id,
            'Hello world',
            'hello',
            $course->category
        );

        $this->assertArrayHasKey('id', $result);
        $newcourseid = (int) $result['id'];

        foreach ($userstobechecked as $userid) {
            // Because the course enable the completion, therefore, the completion should not be deleted, as it was
            // meant to be created anyway.
            $this->assertTrue(
                $DB->record_exists(
                    'course_completions',
                    [
                        'course' => $newcourseid,
                        'userid' => $userid
                    ]
                )
            );

            $this->assertTrue(
                $DB->record_exists(
                    'course_completion_history',
                    [
                        'courseid' =>  $newcourseid,
                        'userid' => $userid
                    ]
                )
            );
        }
    }
}