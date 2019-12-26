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
 * @package tool_uploadcourse
 */

/**
 * A unit test for uploading course with singleactivity format, and restoring afterward using
 * the multiple activities course templates.
 *
 * Class tool_uploadcourse_singleactivity_testcase
 */
class tool_uploadcourse_singleactivity_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_upload_course_with_singleactivity(): void {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();
        $gen = $this->getDataGenerator();

        $course1 = $gen->create_course(
            [
                'idnumber' => 'abcde',
                'shortname' => 'course_1'
            ],
            ['createsections' => true]
        );

        /** @var mod_facetoface_generator $f2fgen */
        $f2fgen = $gen->get_plugin_generator('mod_facetoface');
        for ($i = 0; $i < 5; $i++) {
            $f2fgen->create_instance(['course' => $course1->id]);
        }

        $tooluploadcourse = new tool_uploadcourse_course(
            tool_uploadcourse_processor::MODE_CREATE_OR_UPDATE,
            tool_uploadcourse_processor::UPDATE_NOTHING,
            [
                'fullname' => 'Single Activity',
                'category' => 1,
                'shortname' => 'single',
                'format' => 'singleactivity'
            ],
            (array) get_config('moodlecourse'),
            ['restoredir' => tool_uploadcourse_helper::get_restore_content_dir(null, 'course_1')]
        );

        $rs = $tooluploadcourse->prepare();
        if (!$rs) {
            $this->fail(implode("\n", $tooluploadcourse->get_errors()));
        }

        $tooluploadcourse->proceed();
        $sql = "SELECT * FROM {course_modules} AS cm 
                INNER JOIN {course} AS c ON c.id = cm.course
                WHERE c.shortname = 'single' AND cm.visible = ?
        ";

        $this->assertFalse($DB->record_exists_sql($sql, [1]));
        $this->assertTrue($DB->record_exists_sql($sql, [0]));
    }
}