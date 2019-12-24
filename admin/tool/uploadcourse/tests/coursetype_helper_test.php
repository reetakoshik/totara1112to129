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
 * Class coursetype_helper_test
 */
class coursetype_helper_test extends advanced_testcase {
    /**
     * Data provider for the test
     * @see coursetype_helper_test::test_get_coursetypeid_from_string
     * @return array
     */
    public function provide_coursetype_string_data(): array {
        return [
            ['elearning', 0],
            ['blended', 1],
            ['facetoface', 2],
            ['lebron james', null]
        ];
    }

    /**
     * Data provider for the test
     * @see coursetype_helper_test::test_get_coursetype
     * @return array
     */
    public function provide_coursetype_data(): array {
        return [
            [0, 'E-learning'],
            [1, 'Blended'],
            [2, 'Seminar'],
            [2500, null],
            ['elearning', 'E-learning'],
            ['blended', 'Blended'],
            ['facetoface', 'Seminar'],
            ['lebronjames', null],
            [null, null],
            ['', null]
        ];
    }

    /**
     * Test of converting the string of course type into
     * the type id
     *
     * @dataProvider  provide_coursetype_string_data
     * @param string $inputtype
     * @param int | null $expectedresult
     */
    public function test_get_coursetypeid_from_string($inputtype, $expectedresult): void {
        $result = tool_uploadcourse_helper::get_coursetypeid_from_string($inputtype);
        $this->assertEquals($expectedresult, $result);
    }

    /**
     * Test of converting the type
     * (given by either a string or integer)
     * into a nice name for course type
     *
     * @dataProvider  provide_coursetype_data
     * @param int|string|null $type
     * @param string | null $expectedresult
     */
    public function test_get_coursetype($type, $expectedresult): void {
        $result = tool_uploadcourse_helper::get_course_type_name($type);
        $this->assertEquals($expectedresult, $result);
    }
}
