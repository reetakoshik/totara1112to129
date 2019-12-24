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
 * @package core_course
 */

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once("{$CFG->dirroot}/lib/coursecatlib.php");
require_once("{$CFG->dirroot}/totara/core/totara.php");

use core_course\management\helper;

/**
 * Class course_visibility_icon_reflecting_in_search_test
 */
class course_visibility_icon_reflecting_in_search_test extends advanced_testcase {
    /**
     * Creating course, setting up for the unit test
     * @return stdClass
     */
    private function create_course(): stdClass {
        $record = (object)[
            'audiencevisible' => COHORT_VISIBLE_NOUSERS,
            'shortname' => 'Course101',
            'fullname' => 'Course101'
        ];

        $options = [
            'createsections' => true
        ];

        $course = $this->getDataGenerator()->create_course($record, $options);
        return $course;
    }

    /**
     * Test suite rendering the course via searching management. In a scenario that the course has a visibility setting
     * ($audiencevisible) set to COHOR _VISIBLE_NOUSERS. And the test suite is expecting that data-visiblity attribute
     * within an element from search rendered should reflect to the course visibility setting
     * @return void
     */
    public function test_search_course_with_visibility_reflection(): void {
        global $PAGE, $CFG;
        $this->resetAfterTest(true);
        $this->setAdminUser();

        $PAGE->set_url("/");
        // Changing the configuration for enabling the audiencevisibility, if it is empty by default,
        // it should be changed back after by the test
        $resetconfig = false;
        if (empty ($CFG->audiencevisibility)) {
            $reset = true;
        }

        $CFG->audiencevisibility = 1;

        /** @var \core_course_management_renderer $renderer */
        $renderer = $PAGE->get_renderer("core_course", "management");

        // mocking up the parameters required here
        $search = "Course101";
        $page = 0;
        $perpage = 50;
        $blocklist = 0;
        $modulelist = "";

        $course = $this->create_course();

        list($courses, $coursescount, $coursestotal) = helper::search_courses($search, $blocklist, $modulelist, $page, $perpage);

        $this->assertCount(1, $courses);
        $content = $renderer->search_listing($courses, $coursestotal, null, $page, $perpage, $search);

        // The data visible should be 0, as the $course->audiencevisible is set to COHORT_VISIBLE_NOUSERS
        $datavisible = "data-visible=\"0\"";
        $this->assertContains($datavisible, $content);

        if ($resetconfig) {
            $CFG->audiencevisibility = 0;
        }
    }
}