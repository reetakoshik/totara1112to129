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
 * @package course_management
 */

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once("{$CFG->dirroot}/lib/coursecatlib.php");


/**
 * Class get_category_courses_visibility_test
 */
class get_category_courses_visibility_test extends advanced_testcase {
    /**
     * @return array
     */
    public function provide_data() {
        return array(
            array(COHORT_VISIBLE_NOUSERS, 0, 0), // If the cohort_visible_nousers is set
            array(COHORT_VISIBLE_ALL, 0, 1),
        );
    }


    /**
     * A test suite of checking whether the course's visibility is reflecting with the setting of audience visibility
     * @return void
     * @dataProvider provide_data
     *
     * @param int $audiencevisibility
     * @param int $visible
     * @param int $expect
     */
    public function test_get_courses_visibility_with_audiencevisibility_settings($audiencevisibility, $visible, $expect) {
        global $CFG;
        $this->resetAfterTest(true);

        $reset = false;
        if (empty($CFG->audiencevisibility)) {
            $CFG->audiencevisibility = 1;
            $reset = true;
        }

        $course = $this->getDataGenerator()->create_course((object)[
            'visible' => $visible,
            'audiencevisible' => $audiencevisibility
        ]);
        $category = coursecat::get_default();

        $courses = \core_course\management\helper::get_category_courses_visibility($category->id);
        foreach ($courses as $singlecourse) {
            $this->assertEquals($expect, $singlecourse->visible);
        }

        if ($reset) {
            $CFG->audiencevisibility = 0;
        }
    }
}