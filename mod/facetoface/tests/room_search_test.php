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
 * @package mod_facetoface
 */

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once("{$CFG->dirroot}/mod/facetoface/lib.php");

/**
 * Test suite of searching the room with distinct entries, and pagination is correctly rendered
 */
class mod_facetoface_room_search_testcase extends advanced_testcase {

    /**
     * Creating a course, and a seminar activity for the course
     *
     * Returning an array of course and seminar
     * @return array
     * @throws coding_exception
     */
    private function create_course_with_seminar() {
        /** @var mod_facetoface_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator("mod_facetoface");
        $course = $this->getDataGenerator()->create_course();

        $facetoface = $generator->create_instance((object)[
            'shortname' => 'hello_world',
            'description' => uniqid('desc_'),
            'course' => $course->id
        ]);

        return array($course, $facetoface);
    }

    /**
     * Generating a rooms and sessions date that associated with it With 50 of global rooms, there should have
     * 25 sessions dates that inserted into the database
     *
     * Returning a session (event) that created for facetoface
     * associating with the session dates
     *
     * @param stdclass $user
     * @param stdClass $facetoface
     * @param int $numberofrooms
     * @throws coding_exception
     * @return stdClass
     */
    private function create_session_with_rooms(stdClass $user, stdClass $facetoface, $numberofrooms=50) {
        /** @var mod_facetoface_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator("mod_facetoface");
        $time = time();
        $sessiontime = time();

        $sessionid = $generator->add_session(['facetoface' => $facetoface->id]);
        if (!$sessionid) {
            throw new Exception("Unable to add a session");
        }

        for ($i=0; $i < $numberofrooms; $i++) {
            $room = $generator->add_site_wide_room([
                'name' => "room_{$i}",
                'capacity' => rand(1, 10),
                'usercreated' => $user->id,
                'usermodified' => $user->id,
                'timecreated' => $time,
                'timemodified' => $time
            ]);

            if ($i % 2 === 0) {
                $sessiondate =  (object)[
                    'timestart' => $sessiontime,
                    'timefinish' => $sessiontime + 3600,
                    'sessiontimezone' => 'Pacific/Auckland',
                    'roomid' => $room->id,
                ];

                facetoface_save_dates($sessionid, [$sessiondate]);
                $sessiontime += 7200;
            }
        }
        $session = new stdClass;
        $session->id = $sessionid;
        return $session;
    }

    /**
     * Test suite of rendering the search result, whereas the test is checking for the pagination to assure that the
     * pagination is rendered correctly
     *
     * @throws coding_exception
     */
    public function test_search_room_with_distinct_record() {
        global $USER;
        $this->resetAfterTest(true);
        $this->setAdminUser();

        list($course, $facetoface) = $this->create_course_with_seminar();
        $session = $this->create_session_with_rooms($USER, $facetoface);

        $dialog = new totara_dialog_content();
        $dialog->searchtype = 'facetoface_room';
        $dialog->proxy_dom_data(['id', 'name', 'custom', 'capacity']);
        $dialog->lang_file = 'facetoface';
        $dialog->customdata = array(
            'facetofaceid' => $facetoface->id,
            'timestart' => time(),
            'timefinish' => time(),
            'sessionid' => $session->id,
            'selected' => 0,
            'offset' => 0
        );

        $dialog->urlparams = array(
            'facetofaceid' => $facetoface->id,
            'sessionid' => $session->id,
            'timestart' =>  time(),
            'timefinish' => time(),
            'offset' => 0,
        );
        $_POST = [
            'query' => 'room',
            'page' => 0
        ];

        // As the searching is no loging including the duplicated entries within count, therefore, with 50
        // rooms records (barely the maximum per page) the test method should expecting no pagination at all
        $content = $dialog->generate_search();
        $paging_rendering_expected = '<div class="search-paging"><div class="paging"></div></div>';

        $this->assertContains($paging_rendering_expected, $content);
    }
}