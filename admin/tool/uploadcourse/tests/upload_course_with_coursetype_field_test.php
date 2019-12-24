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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package tool_uploadcourse
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . "/course/lib.php");


/**
 * Unit test for the class tool_uploadcourse_course
 * to process with the new field coursetype
 *
 * Class coursetype_test
 */
class upload_course_with_coursetype_field_test extends advanced_testcase {
    /**
     * Create the instance of tool_uploadcourse_course
     *
     * @param array $data
     * @return tool_uploadcourse_course
     */
    private function get_tool_uploadcourse_course(array $data): tool_uploadcourse_course {
        return new tool_uploadcourse_course(
            tool_uploadcourse_processor::MODE_CREATE_NEW,
            tool_uploadcourse_processor::UPDATE_NOTHING,
            $data
        );
    }

    /**
     * Provide data for tool_uploadcourse_course
     * @return array
     */
    public function provide_data(): array {
        return
            [
                [
                    [
                        'fullname' => "something",
                        'shortname'=> "s101",
                        'category' => 1, //Miscellaneous
                        'coursetype' => TOTARA_COURSE_TYPE_FACETOFACE
                    ],
                    true
                ],
                [
                    [
                        'fullname' => "something",
                        'shortname' => "s101",
                        'category' => 1, //Miscellaneous
                        'coursetype' => "e-learning",
                    ],
                    false
                ]
            ];
    }

    /**
     * Test suite to check whether the result of validating the data is going as expected or not,
     * as there is an update within the tool_uploadcourse_course
     *
     * @dataProvider provide_data
     * @param array $data       The array data to set up the instance of tool_uploadcourse_course
     * @param bool  $result     The expected result
     */
    public function test_prepare_data(array $data, bool $result): void {
        $object = $this->get_tool_uploadcourse_course($data);

        $actual = $object->prepare();
        $this->assertEquals($result, $actual);
    }

    /**
     * The test suite for uploading the course (saving the course data into the database)
     * whereas, the coursetype field is added as well. And it is expecting the
     * coursetype field should be honoured and added into the database as well
     *
     * @throws coding_exception
     * @throws dml_exception
     * @throws moodle_exception
     */
    public function test_procceed_data(): void {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $object = $this->get_tool_uploadcourse_course([
            'fullname' => "Programming with KianBomba Course101",
            'shortname' => "KB101",
            'category' => 1,
            'coursetype' => TOTARA_COURSE_TYPE_BLENDED
        ]);

        $object->prepare();
        $object->proceed();

        $record = $DB->get_record('course', ['shortname' => 'KB101']);
        if (is_null($record)) {
            $this->fail(implode(" ", [
                "Unable to upload the course through the class",
                tool_uploadcourse_course::class,
                "with the new field `coursetype`"
            ]));
        }

        $this->assertEquals(TOTARA_COURSE_TYPE_BLENDED, $record->coursetype);
    }
}