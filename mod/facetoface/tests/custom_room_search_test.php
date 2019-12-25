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

class mod_facetoface_custom_room_search_testcase extends advanced_testcase {

    /**
     * Creating a seminar
     * @param stdClass $course
     * @return stdClass
     */
    private function create_seminar(stdClass $course): stdClass {
        global $DB;
        $time = time();
        $data = [
            'course' => $course->id,
            'name' => 'Seminar 1',
            'timecreated' => $time,
            'timemodified' => $time,
        ];

        $object = (object)$data;
        $id = $DB->insert_record("facetoface", $object);
        $object->id = $id;

        return $object;
    }

    /**
     * Create an event and assign it to a seminar,
     * if the room i specified, assign the room to this event
     *
     * @param stdClass $facetoface
     * @param stdClass $user        The user who is responsible for the action
     *
     * @return stdClass             Event record
     */
    private function create_facetoface_event(stdClass $facetoface, stdClass $user): stdClass {
        global $DB;

        $data = array(
            'facetoface' => $facetoface->id,
            'capacity' => 20,
            'allowoeverbook' => 1,
            'waitlisteveryone' => 0,
            'usermodified' => $user->id,
        );

        $obj = (object) $data;
        $id = $DB->insert_record("facetoface_sessions", $obj);
        $obj->id = $id;
        return $obj;
    }

    /**
     * Creating event session date for seminar
     *
     * @param stdClass $event
     * @param stdClass $room
     */
    private function create_event_session(stdClass $event, stdClass $room): void {
        global $DB, $CFG;

        $time = time();
        $data = array(
            'sessionid' => $event->id,
            'sessiontimezone' => isset($CFG->timezone) ?  $CFG->timezone : 99,
            'roomid' => $room->id,
            'timestart' => $time,
            'timefinish' => $time + 3600
        );

        $DB->insert_record("facetoface_sessions_dates", (object)$data);
    }


    /**
     * Creating a seminar custom room
     *
     * @param stdClass $user
     * @return stdClass
     */
    private function create_custom_room(stdClass $user): stdClass {
        global $DB;

        $time = time();
        $data = [
            'name' => 'Seminar Room',
            'capacity' => 50,
            'custom' => 1,
            'hidden' => 0,
            'usercreated' => $user->id,
            'usermodified' => $user->id,
            'timecreated' => $time,
            'timemodified' => $time
        ];

        $object = (object)$data;
        $id = $DB->insert_record("facetoface_room", $object);
        $object->id = $id;

        return $object;
    }

    /**
     * Test suite of whether the search dialog class is able
     * to find the custom room or not
     */
    public function test_custom_room_is_appearing_in_search_result(): void {
        global $USER;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $facetoface = $this->create_seminar($course);
        $room = $this->create_custom_room($USER);

        $time = time();
        $dialog = new totara_dialog_content();
        $dialog->searchtype = 'facetoface_room';

        $dialog->proxy_dom_data(['id', 'name', 'custom', 'capacity']);
        $dialog->items = [$room];
        $dialog->disabled_items = [];
        $dialog->lang_file = 'facetoface';
        $dialog->customdata = [
            'facetofaceid' => $facetoface->id,
            'timestart' => $time,
            'timefinish' => $time + 3600,
            'sessionid' => 0,
            'selected' => $room->id,
            'offset' => 0
        ];

        $dialog->string_nothingtodisplay = 'error:nopredefinedrooms';
        $dialog->urlparams = [
            'facetofaceid' => $facetoface->id,
            'sessionid' => 0,
            'timestart' => $time,
            'timefinish' => $time + 3600,
            'offset' => 0,
        ];

        $_POST = [
            'search' => 1,
            'query' => 'seminar room'
        ];

        $messages = array(
            'The rendered search does not',
            'contain the custom room name:',
            'Seminar Room'
        );
        $markup = $dialog->generate_search();
        $this->assertContains("Seminar Room", $markup, implode(" ", $messages));
    }

    /**
     * Test suite instruction:
     *
     * Create a course,
     * Create a seminar,
     * Create an event for the seminar
     * Create a session date for event
     * Create a room and assign this room to the event, that has a session,
     * Create another seminar and try to search for the room within this seminar.
     *
     * As the result, the room that is being used elsewhere would not be found in other seminar,
     * but it would be found in the same seminar where it is being assigned.
     */
    public function test_used_custom_room_is_not_appearing_in_search_result(): void {
        global $USER;

        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $usedfacetoface = $this->create_seminar($course);
        $room = $this->create_custom_room($USER);
        $event = $this->create_facetoface_event($usedfacetoface, $USER);
        $this->create_event_session($event, $room);

        $facetoface = $this->create_seminar($course);
        $time = time();

        $dialog = new totara_dialog_content();
        $dialog->searchtype = "facetoface_room";
        $dialog->proxy_dom_data(['id', 'name', 'custom', 'capacity']);
        $dialog->items =  [$room];
        $dialog->disabled_items = [];
        $dialog->lang_file = "facetoface";
        $dialog->customdata = [
            'facetofaceid' => $facetoface->id,
            'timestart' => $time,
            'timefinish' => $time + 3600,
            'sessionid' => 0,
            'selected' => 0,
            'offset' => 0,
        ];

        $dialog->urlparams = [
            'facetofaceid' => $facetoface->id,
            'sessionid' => 0,
            'timestart' => $time,
            'timefinish' => $time + 3600,
            'offset' => 0,
        ];

        $_POST = [
            'search' => 1,
            'query' => 'Seminar Room',
        ];

        $markup = $dialog->generate_search();
        $this->assertContains('No results found for "Seminar Room"', $markup);
    }

    /**
     * Test suite of assuring that the custom room that has been used in one facetoface must not
     * appear in a different seminar.
     */
    public function test_custom_room_is_not_appearing_in_different_seminar(): void {
        global $USER;

        $this->resetAfterTest();
        $this->setAdminUser();
        $gen = $this->getDataGenerator();

        $course1 = $gen->create_course([], ['createsections' => 1]);
        $f2f1 = $this->create_seminar($course1);
        $room1 = $this->create_custom_room($USER);
        $event = $this->create_facetoface_event($f2f1, $USER);
        $sessiondate = $this->create_event_session($event, $room1);

        $course2 = $gen->create_course([], ['createsections' => 1]);
        $f2f2 = $this->create_seminar($course2);

        $time = time() + (42 * 3600);
        $dialog = new totara_dialog_content();
        $dialog->searchtype = 'facetoface_room';
        $dialog->proxy_dom_data(['id', 'name', 'custom', 'capacity']);
        $dialog->items = array();
        $dialog->lang_file = 'facetoface';
        $dialog->disabled_items = array();
        $dialog->customdata = [
            'facetofaceid' => $f2f2->id,
            'timestart' => $time,
            'timefinish' => $time + 3600,
            'sessionid' => 0,
            'selected' => 0,
            'offset' => 0,
        ];

        $dialog->urlparams = [
            'facetofaceid' => $f2f2->id,
            'sessionid' => 0,
            'timestart' => $time,
            'timefinish' => $time + 3600,
            'offset' => 0,
        ];

        $_POST = [
            'search' => 1,
            'query' => 'Seminar Room',
        ];

        $markup = $dialog->generate_search();
        $this->assertContains('No results found for "Seminar Room"', $markup);
    }
}
