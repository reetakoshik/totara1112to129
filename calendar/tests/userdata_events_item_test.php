<?php
/**
 * This file is part of Totara LMS
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
 * @author Andrew McGhie <andrew.mcghie@totaralearning.com>
 * @package core_calendar
 */

use core_calendar\userdata\events;
use totara_userdata\userdata\target_user;

/**
 * Tests that the {@see core_calendar\userdata\core_calendar_events} class
 * purges, exports and counts the user events correctly.
 *
 * @group totara_userdata
 */
class core_calendar_userdata_events_testcase extends advanced_testcase {

    /**
     * Creates
     *  - 2 users with events and 1 with no events
     *  - 2 arrays of user events
     *  - an array of nonuser events
     *  - the number of events for the second user including repeats
     */
    private function get_data() {
        global $CFG;
        require_once("$CFG->dirroot/calendar/lib.php");
        $data = new class() {
            /** @var target_user */
            public $user1, $user2, $emptyuser;
            /** @var array */
            public $user1events, $user2events, $nonuserevents;
            /** @var int */
            public $numuser2eventplusrepeats;

        };
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $emptyuser = $this->getDataGenerator()->create_user();
        $data->user1 = new target_user($user1);
        $data->user2 = new target_user($user2);
        $data->emptyuser = new target_user($emptyuser);

        $course = $this->getDataGenerator()->create_course();

        // User events.
        $eventdata = new stdClass();
        $eventdata->name = 'name';
        $eventdata->description = 'description';
        $eventdata->modulename = 0;
        $eventdata->instance = 0;
        $eventdata->eventtype = 'user';
        $eventdata->repeat = 0;
        $eventdata->timestart = 1000000;
        $eventdata->timeduration = 0;

        $eventdata->userid = $user1->id;
        $data->user1events[] = calendar_event::create(clone($eventdata), false);
        $eventcontainingfile = calendar_event::create(clone($eventdata), false);
        $file_record = [
            'contextid' => $eventcontainingfile->context->id,
            'component' => 'calendar',
            'filearea' => 'event_description',
            'itemid' => $eventcontainingfile->id,
            'filepath' => '/',
            'filename' => 'test.png'
        ];
        get_file_storage()->create_file_from_string($file_record, '');
        $data->user1events[] = $eventcontainingfile;

        $eventdata->eventtype = 'user';
        $eventdata->userid = $user1->id;
        $eventdata->visible = 0;
        $data->user1events[] = calendar_event::create(clone($eventdata), false);

        $eventdata->userid = $user2->id;
        $eventdata->repeat = 1;
        $eventdata->repeats = 5;
        $data->user2events[] = calendar_event::create(clone($eventdata), false);
        $data->numuser2eventplusrepeats =
            count($data->user2events) + end($data->user2events)->count_repeats();

        $eventdata->courseid = $course->id;
        $eventdata->eventtype = 'course';
        $eventdata->userid = $user1->id;
        $data->nonuserevents[] = calendar_event::create(clone($eventdata), false);

        $eventdata->eventtype = 'site';
        $eventdata->userid = $user2->id;
        $data->nonuserevents[] = calendar_event::create(clone($eventdata), false);
        return $data;
    }

    /**
     * Creates some events by creating some seminars.
     * TODO check
     *
     *
     * @param object $data the data from {@see getdata}
     * @return stdClass
     */
    private function make_course_module_events($data): stdClass {
        $this->setAdminUser();
        $extradata = new stdClass();
        /** @var mod_facetoface_generator $facetofacegenerator */
        $facetofacegenerator = $this->getDataGenerator()->get_plugin_generator('mod_facetoface');
        /** @var mod_chat_generator $chatgenerator */
        $chatgenerator = $this->getDataGenerator()->get_plugin_generator('mod_chat');
        /** @var mod_feedback_generator $feedbackgenerator */
        $feedbackgenerator = $this->getDataGenerator()->get_plugin_generator('mod_feedback');
        /** @var mod_quiz_generator $quizgenerator */
        $quizgenerator = $this->getDataGenerator()->get_plugin_generator('mod_quiz');
        /** @var mod_lesson_generator $lessongenerator */
        $lessongenerator = $this->getDataGenerator()->get_plugin_generator('mod_lesson');
        /** @var mod_workshop_generator $workshopgenerator */
        $workshopgenerator = $this->getDataGenerator()->get_plugin_generator('mod_workshop');

        $course = $this->getDataGenerator()->create_course();
        $this->getDataGenerator()->enrol_user($data->user1->id, $course->id);

        $facetoface = $this->getDataGenerator()->create_module('facetoface', [
            'course' => $course->id,
            'showoncalendar' => 1
        ]);
        // Make sure that the chat events are not included.
        $chatgenerator->create_instance([
            'course' => $course->id,
            'chattime' => time() + DAYSECS * 2,
            'schedule' => 2
        ]);
        $feedbackgenerator->create_instance([
            'course' => $course->id,
            'timeopen' => time() + DAYSECS * 2
        ]);
        $quizgenerator->create_instance([
            'course' => $course->id,
            'timeopen' => time() + DAYSECS * 2
        ]);
        $lessongenerator->create_instance([
            'course' => $course->id,
            'available' => time() + DAYSECS * 2
        ]);
        $workshopgenerator->create_instance([
            'course' => $course->id,
            'submissionstart' => time() + DAYSECS * 1,
            'assessmentstart' => time() + DAYSECS * 2
        ]);

        $sessiondate = new stdClass();
        $sessiondate->timestart = time() + DAYSECS;
        $sessiondate->timefinish = $sessiondate->timestart + (DAYSECS * 2);
        $sessiondate->sessiontimezone = 'Pacific/Auckland';
        $sessionid = $facetofacegenerator->add_session(['facetoface' => $facetoface->id, 'sessiondates' => [$sessiondate]]);
        $sessiondata = facetoface_get_session($sessionid);
        $seminarevent = new \mod_facetoface\seminar_event($sessionid);
        \mod_facetoface\calendar::add_seminar_event($seminarevent, 'user', $data->user1->id, 'session');
        $extradata->sessions[] = $sessiondata;

        $sessiondate = new stdClass();
        $sessiondate->timestart = time() + DAYSECS * 3;
        $sessiondate->timefinish = $sessiondate->timestart + (DAYSECS * 4);
        $sessiondate->sessiontimezone = 'Pacific/Auckland';
        $sessionid = $facetofacegenerator->add_session(['facetoface' => $facetoface->id, 'sessiondates' => [$sessiondate]]);
        $sessiondata = facetoface_get_session($sessionid);
        $seminarevent = new \mod_facetoface\seminar_event($sessionid);
        \mod_facetoface\calendar::add_seminar_event($seminarevent, 'user', $data->user1->id, 'session');
        $extradata->sessions[] = $sessiondata;

        return $extradata;
    }

    /**
     * Test that the purge removes events created by course modules.
     */
    public function test_purge_effects_events_that_are_created_by_a_module() {
        global $DB;
        $this->resetAfterTest();
        $data = $this->get_data();
        $extradata = $this->make_course_module_events($data);
        $systemcontext = context_system::instance();

        $this->assertEquals(
            events::RESULT_STATUS_SUCCESS,
            events::execute_purge($data->user1, $systemcontext)
        );
        foreach ($extradata->sessions as $session) {
            $this->assertFalse(
                $DB->record_exists('event', [
                    'eventtype' => 'facetoface',
                    'instance' => $session->id,
                    'userid' => $data->user1->id,
                    'courseid' => 0,
                    'groupid' => 0
                ])
            );
        }
    }

    /**
     * Test that the count includes events from course modules such as
     * seminars in a facetoface.
     */
    public function test_count_includes_events_from_course_modules() {
        $this->resetAfterTest();
        $data = $this->get_data();
        $systemcontext = context_system::instance();
        $countbefore = events::execute_count($data->user1, $systemcontext);
        $extradata = $this->make_course_module_events($data);

        $this->assertEquals(
            $countbefore + count($extradata->sessions),
            events::execute_count($data->user1, $systemcontext)
        );
    }

    /**
     * Test that the exports include the events from course modules.
     */
    public function test_export_includes_event_from_course_modules() {
        global $DB;
        $this->resetAfterTest();
        $data = $this->get_data();
        $systemcontext = context_system::instance();

        // Creates two facetoface session events in the calendar.
        $this->make_course_module_events($data);

        $export = events::execute_export($data->user1, $systemcontext);
        // Make sure export didn't return an error code.
        $this->assertTrue(is_object($export));

        // Get all facetodace session events.
        $events = $DB->get_records('event', [
            'eventtype' => 'facetofacesession',
            'userid' => $data->user1->id,
            'courseid' => 0,
            'groupid' => 0
        ]);
        // 2 events where added to the three standard ones.
        $this->assertEquals(5, count($export->data));
        $eventidsfound = [];
        foreach ($export->data as $event) {
            $eventidsfound[] = $event->id;
        }
        // Make sure the facetofacesession events are found in the exported data.
        foreach ($events as $event) {
            $this->assertContains($event->id, $eventidsfound);
        }
    }

    /**
     * Test that purging doesnt delete any data it shouldn't and that it deletes data that it should
     */
    public function test_purge_removes_only_users_event() {
        global $DB;
        $this->resetAfterTest();
        $data = $this->get_data();
        $systemcontext = context_system::instance();

        $this->assertEquals(
            events::RESULT_STATUS_SUCCESS,
            events::execute_purge($data->user1, $systemcontext)
        );

        $fs = get_file_storage();
        // User 1 events have being removed.
        foreach ($data->user1events as $event) {
            $this->assertFalse(
                $DB->record_exists('event', ['id' => $event->id])
            );
            // Check files have being removed.
            $this->assertEmpty(
                $fs->get_area_files(
                    $event->context->id,
                    'calendar',
                    'event_description',
                    $event->id
                )
            );
        }
        // User 2 events still exist.
        foreach ($data->user2events as $event) {
            $this->assertTrue(
                $DB->record_exists('event', ['id' => $event->id])
            );
        }
        // All other reports still exist.
        foreach ($data->nonuserevents as $event) {
            $this->assertTrue(
                $DB->record_exists('event', ['id' => $event->id])
            );
        }
    }

    /**
     * Test that purging will work on a user that has being deleted.
     */
    public function test_purge_works_on_deleted_user() {
        global $DB;
        $this->resetAfterTest();
        $data = $this->get_data();
        $systemcontext = context_system::instance();

        $user = $DB->get_record('user', ['id' => $data->user1->id]);
        delete_user($user);
        $user = $DB->get_record('user', ['id' => $data->user1->id]);
        $reloadeduser = new target_user($user);

        $this->assertEquals(
            events::RESULT_STATUS_SUCCESS,
            events::execute_purge($reloadeduser, $systemcontext)
        );

        $this->assertEquals(
            0,
            events::execute_count($reloadeduser, $systemcontext)
        );

        $fs = get_file_storage();
        foreach ($data->user1events as $event) {
            $this->assertEmpty(
                $fs->get_area_files(
                    $event->context->id,
                    'calendar',
                    'event_description',
                    $event->id
                )
            );
        }
    }

    /**
     * Test that the count is 0 after purge
     */
    public function test_count_zero_after_purge() {
        $this->resetAfterTest();
        $data = $this->get_data();
        $systemcontext = context_system::instance();

        events::execute_purge($data->user1, $systemcontext);
        events::execute_purge($data->user2, $systemcontext);

        $this->assertEquals(
            0,
            events::execute_count($data->user1, $systemcontext)
        );
        $this->assertEquals(
            0,
            events::execute_count($data->user2, $systemcontext)
        );
    }

    /**
     * Test that the count returns the number of userevents for a user
     */
    public function test_count_returns_correct_value() {
        $this->resetAfterTest();
        $data = $this->get_data();
        $systemcontext = context_system::instance();

        $this->assertEquals(
            count($data->user1events),
            events::execute_count($data->user1, $systemcontext)
        );
        $this->assertEquals(
            $data->numuser2eventplusrepeats,
            events::execute_count($data->user2, $systemcontext)
        );
        $this->assertEquals(
            0,
            events::execute_count($data->emptyuser, $systemcontext)
        );
    }

    /**
     * Test that the number of data items exported matches the count
     */
    public function test_count_matches_num_export_data() {
        $this->resetAfterTest();
        $data = $this->get_data();
        $systemcontext = context_system::instance();

        $user1export = events::execute_export($data->user1, $systemcontext);
        $this->assertEquals(
            events::execute_count($data->user1, $systemcontext),
            count($user1export->data)
        );
        $user2export = events::execute_export($data->user2, $systemcontext);
        $this->assertEquals(
            events::execute_count($data->user2, $systemcontext),
            count($user2export->data)
        );
        $emptyuserexport = events::execute_export($data->emptyuser, $systemcontext);
        $this->assertEquals(
            events::execute_count($data->emptyuser, $systemcontext),
            count($emptyuserexport->data)
        );
    }

    /**
     * Tests that count works the same on a user that has being deleted.
     */
    public function test_count_works_on_deleted_user() {
        global $DB;
        $this->resetAfterTest();
        $data = $this->get_data();
        $systemcontext = context_system::instance();

        $countbefore = events::execute_count($data->user1, $systemcontext);

        $user = $DB->get_record('user', ['id' => $data->user1->id]);
        delete_user($user);
        $user = $DB->get_record('user', ['id' => $data->user1->id]);
        $reloadeduser = new target_user($user);

        $this->assertEquals(
            $countbefore,
            events::execute_count($reloadeduser, $systemcontext)
        );
    }

    /**
     * Test that the export data contains the expected values and files.
     */
    public function test_export_data_correct_values() {
        global $DB;
        $this->resetAfterTest();
        $data = $this->get_data();
        $systemcontext = context_system::instance();

        $export = events::execute_export($data->user1, $systemcontext);
        $fs = get_file_storage();

        // Reverse the order to match the function events::get_all_user_events.
        usort($data->user1events, function($event1, $event2) {
            return $event2->id <=> $event1->id;
        });

        foreach ($export->data as $index => $event) {
            $eventdata = $DB->get_record('event', ['id' => $data->user1events[$index]->id]);
            $eventobject = calendar_event::load(clone($event));

            $eventdata->files = [];
            $files = $fs->get_area_files(
                $eventobject->context->id,
                'calendar',
                'event_description',
                $eventobject->id,
                '',
                false
            );
            foreach ($files as $file) {
                $this->assertArrayHasKey($file->get_id(), $export->files);

                $filedata = [
                    'fileid' => $file->get_id(),
                    'filename' => $file->get_filename(),
                    'contenthash' => $file->get_contenthash()
                ];
                $eventdata->files[] = (object)$filedata;
            }

            $this->assertEquals(
                $eventdata,
                $event
            );
        }
    }

    /**
     * Test export works on a user that has being deleted.
     * Files get deleted when the user is deleted so those can no longer be exported.
     */
    public function test_export_works_on_deleted_user() {
        global $DB;
        $this->resetAfterTest();
        $data = $this->get_data();
        $systemcontext = context_system::instance();

        $exportbefore = events::execute_export($data->user1, $systemcontext);

        $user = $DB->get_record('user', ['id' => $data->user1->id]);
        delete_user($user);
        $user = $DB->get_record('user', ['id' => $data->user1->id]);
        $reloadeduser = new target_user($user);

        // Files get deleted so they will not be here.
        foreach ($exportbefore->data as $expectedevent) {
            unset($expectedevent->files);
        }

        $result = events::execute_export($reloadeduser, $systemcontext);
        $this->assertEquals($exportbefore->data, $result->data);
    }
}
