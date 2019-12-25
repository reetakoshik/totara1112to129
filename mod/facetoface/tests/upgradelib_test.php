<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2016 onwards Totara Learning Solutions LTD
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
 * @author  Valerii Kuznetsov <valerii.kuznetsov@totaralearning.com>
 * @package mod_facetoface
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot.'/mod/facetoface/db/upgradelib.php');

/**
 * Test facetoface upgradelib related functions
 */
class mod_facetoface_upgradelib_testcase extends advanced_testcase {
    /**
     * Test facetoface_upgradelib_managerprefix_clarification()
     */
    public function test_facetoface_upgradelib_managerprefix_clarification() {
        global $DB;
        $this->resetAfterTest();

        // Prepate data.
        $tpl_cancellation = $DB->get_record('facetoface_notification_tpl', array('reference' => 'cancellation'));
        $tpl_cancellation->managerprefix = text_to_html(get_string('setting:defaultcancellationinstrmngrdefault', 'facetoface') . "test");
        $DB->update_record('facetoface_notification_tpl', $tpl_cancellation);

        $tpl_reminder = $DB->get_record('facetoface_notification_tpl', array('reference' => 'reminder'));
        $tpl_reminder->managerprefix = text_to_html(get_string('setting:defaultreminderinstrmngrdefault', 'facetoface'));
        $DB->update_record('facetoface_notification_tpl', $tpl_reminder);

        $tpl_request = new stdClass();
        $tpl_request->status = 1;
        $tpl_request->title =  "Test title 3";
        $tpl_request->type =  4;
        $tpl_request->courseid =  1;
        $tpl_request->facetofaceid =  1;
        $tpl_request->courseid =  1;
        $tpl_request->templateid =  1;
        $tpl_request->body = text_to_html(get_string('setting:defaultrequestmessagedefault_v9', 'facetoface'));
        $tpl_request->managerprefix = text_to_html(get_string('setting:defaultrequestinstrmngrdefault', 'facetoface'));
        $DB->insert_record('facetoface_notification', $tpl_request);

        $tpl_rolerequest = new stdClass();
        $tpl_rolerequest->status = 1;
        $tpl_rolerequest->title =  "Test title 4";
        $tpl_rolerequest->type =  4;
        $tpl_rolerequest->courseid =  1;
        $tpl_rolerequest->facetofaceid =  1;
        $tpl_rolerequest->courseid =  1;
        $tpl_rolerequest->templateid =  1;
        $tpl_rolerequest->body = text_to_html(get_string('setting:defaultrolerequestmessagedefault_v9', 'facetoface'));
        $tpl_rolerequest->managerprefix = text_to_html("test".get_string('setting:defaultrolerequestinstrmngrdefault', 'facetoface'));
        $DB->insert_record('facetoface_notification', $tpl_rolerequest);

        // Do upgrade.
        facetoface_upgradelib_managerprefix_clarification();

        // Check that changed strings are not updated.
        $cancellation = $DB->get_field('facetoface_notification_tpl', 'managerprefix', array('reference' => 'cancellation'));
        $cancellationexp = text_to_html(get_string('setting:defaultcancellationinstrmngrdefault', 'facetoface') . "test");
        $this->assertEquals($cancellationexp, $cancellation);

        $rolerequest = $DB->get_field('facetoface_notification', 'managerprefix', array('title' => 'Test title 4'));
        $rolerequestexp = text_to_html("test" . get_string('setting:defaultrolerequestinstrmngrdefault', 'facetoface'));
        $this->assertEquals($rolerequestexp, $rolerequest);

        // Check that not changed string are updated.
        $reminder = $DB->get_field('facetoface_notification_tpl', 'managerprefix', array('reference' => 'reminder'));
        $reminderexp = text_to_html(get_string('setting:defaultreminderinstrmngrdefault_v92', 'facetoface'));
        $this->assertEquals($reminderexp, $reminder);

        $request = $DB->get_field('facetoface_notification', 'managerprefix', array('title' => 'Test title 3'));
        $requestexp = text_to_html(get_string('setting:defaultrequestinstrmngrdefault_v92', 'facetoface'));
        $this->assertEquals($requestexp, $request);
    }


    private function verifySessionDate($event, $timestart, $timeduration, $visible) {
        $this->assertEquals($timestart, $event->timestart);
        $this->assertEquals($timeduration, $event->timeduration);
        $this->assertEquals($visible, $event->visible);
    }

    public function test_facetoface_upgradelib_calendar_events_for_sessiondates() {
        global $DB;
        $this->resetAfterTest();

        // Setup the data
        $facetofacegenerator = $this->getDataGenerator()->get_plugin_generator('mod_facetoface');
        $course = $this->getDataGenerator()->create_course();
        $facetoface = $this->getDataGenerator()->create_module('facetoface', array('course' => $course->id));
        $context = context_module::instance($facetoface->cmid);

        $this->setAdminUser();

        $now = time();
        $sessiondates = array();
        for ($i = 0; $i < 3; $i++) {
            $sessiondates[$i] = new stdClass();
            $sessiondates[$i]->timestart = $now + $i * WEEKSECS;
            $sessiondates[$i]->timefinish = $sessiondates[$i]->timestart + 3 * HOURSECS;
            $sessiondates[$i]->sessiontimezone = '99';
            $sessiondates[$i]->assetids = array();
        }
        $sid = $facetofacegenerator->add_session(array('facetoface' => $facetoface->id, 'sessiondates' => $sessiondates));

        // We still need to add the calendar entries.
        $session = facetoface_get_session($sid);
        facetoface_update_calendar_entries($session);

        $events = $DB->get_records('event', array('modulename' => 'facetoface', 'eventtype' => 'facetofacesession', 'courseid' => $course->id),
            'timestart');

        $this->assertEquals(3, count($events));
        for ($i = 0; $i < 3; $i++) {
            $event = array_shift($events);
            $this->verifySessionDate($event, $sessiondates[$i]->timestart, 3 * HOURSECS, 1);
        }

        // The database is now in the correct state that
        // First test that facetoface_upgradelib_calendar_events_for_sessiondates
        // doesn't braeak this

        facetoface_upgradelib_calendar_events_for_sessiondates();

        $events = $DB->get_records('event', array('modulename' => 'facetoface', 'eventtype' => 'facetofacesession', 'courseid' => $course->id),
            'timestart');

        $ids = array();
        $this->assertEquals(3, count($events));
        for ($i = 0; $i < 3; $i++) {
            $event = array_shift($events);
            $this->verifySessionDate($event, $sessiondates[$i]->timestart, 3 * HOURSECS, 1);

            if ($i < 2) {
                $ids[] = $event->id;
            }
        }

        // Now remove all but one of the events to simulate the state prior to this patch
        $sql = 'DELETE FROM {event} WHERE id in (' . implode(',', $ids) . ')';
        $DB->execute($sql);

        // Verify
        $events = $DB->get_records('event', array('modulename' => 'facetoface', 'eventtype' => 'facetofacesession', 'courseid' => $course->id),
            'timestart');

        $this->assertEquals(1, count($events));
        $event = array_shift($events);
        $this->verifySessionDate($event, $sessiondates[2]->timestart, 3 * HOURSECS, 1);

        // Now verify that facetoface_upgradelib_calendar_events_for_sessiondates restores the events

        facetoface_upgradelib_calendar_events_for_sessiondates();

        $events = $DB->get_records('event', array('modulename' => 'facetoface', 'eventtype' => 'facetofacesession', 'courseid' => $course->id),
            'timestart');

        $this->assertEquals(3, count($events));
        for ($i = 0; $i < 3; $i++) {
            $event = array_shift($events);
            $this->verifySessionDate($event, $sessiondates[$i]->timestart, 3 * HOURSECS, 1);
        }
    }
}
