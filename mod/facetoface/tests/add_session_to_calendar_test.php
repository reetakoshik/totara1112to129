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
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package mod_facetoface
 */

defined('MOODLE_INTERNAL') || die();

class mod_facetoface_add_session_to_calendar_testcase extends advanced_testcase {

    protected $facetofacegenerator = null;
    protected $facetoface = null;
    protected $course = null;
    protected $context = null;


    protected function tearDown() {
        $this->facetofacegenerator = null;
        $this->facetoface = null;
        $this->course = null;
        $this->context = null;
        parent::tearDown();
    }

    public function setUp() {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        $this->facetofacegenerator = $this->getDataGenerator()->get_plugin_generator('mod_facetoface');
        $this->course = $this->getDataGenerator()->create_course();
        $this->facetoface = $this->getDataGenerator()->create_module('facetoface', array('course' => $this->course->id));
        $this->context = context_module::instance($this->facetoface->cmid);

        $this->adminid = $DB->get_field('user', 'id', array('username' => 'admin'));
    }

    private function verifySessionDate($event, $timestart, $timeduration, $visible) {
        $this->assertEquals($timestart, $event->timestart);
        $this->assertEquals($timeduration, $event->timeduration);
        $this->assertEquals($visible, $event->visible);
    }


    public function test_single_session_one_date() {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();

        $now = time();
        $sessiondate = new stdClass();
        $sessiondate->timestart = $now;
        $sessiondate->timefinish = $now + 3 * HOURSECS;
        $sessiondate->sessiontimezone = '99';
        $sessiondate->assetids = array();
        $sid = $this->facetofacegenerator->add_session(array('facetoface' => $this->facetoface->id, 'sessiondates' => array($sessiondate)));

        // We still need to add the calendar entries.
        $seminarevent = new \mod_facetoface\seminar_event($sid);
        \mod_facetoface\calendar::update_entries($seminarevent);

        $events = $DB->get_records('event', array('modulename' => 'facetoface', 'eventtype' => 'facetofacesession', 'courseid' => $this->course->id),
            'timestart');

        $this->assertEquals(1, count($events));
        $event = array_shift($events);
        $this->verifySessionDate($event, $sessiondate->timestart, 3 * HOURSECS, 1);
    }

    public function test_multi_sessions_one_date_each() {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();

        $now = time();
        $sessiondates = array();
        $sids = array();
        for ($i = 0; $i < 3; $i++) {
            $sessiondates[$i] = new stdClass();
            $sessiondates[$i]->timestart = $now + $i * WEEKSECS;
            $sessiondates[$i]->timefinish = $sessiondates[$i]->timestart + 3 * HOURSECS;
            $sessiondates[$i]->sessiontimezone = '99';
            $sessiondates[$i]->assetids = array();
            $sids[$i]= $this->facetofacegenerator->add_session(array('facetoface' => $this->facetoface->id, 'sessiondates' => array($sessiondates[$i])));

            // We still need to add the calendar entries.
            $seminarevent = new \mod_facetoface\seminar_event($sids[$i]);
            \mod_facetoface\calendar::update_entries($seminarevent);
        }

        $events = $DB->get_records('event', array('modulename' => 'facetoface', 'eventtype' => 'facetofacesession', 'courseid' => $this->course->id),
            'timestart');

        $this->assertEquals(3, count($events));
        for ($i = 0; $i < 3; $i++) {
            $event = array_shift($events);
            $this->verifySessionDate($event, $sessiondates[$i]->timestart, 3 * HOURSECS, 1);
        }
    }

    public function test_single_session_multiple_dates() {
        global $DB;

        $this->resetAfterTest();
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
        $sid = $this->facetofacegenerator->add_session(array('facetoface' => $this->facetoface->id, 'sessiondates' => $sessiondates));

        // We still need to add the calendar entries.
        $seminarevent = new \mod_facetoface\seminar_event($sid);
        \mod_facetoface\calendar::update_entries($seminarevent);

        $events = $DB->get_records('event', array('modulename' => 'facetoface', 'eventtype' => 'facetofacesession', 'courseid' => $this->course->id),
            'timestart');

        $this->assertEquals(3, count($events));
        for ($i = 0; $i < 3; $i++) {
            $event = array_shift($events);
            $this->verifySessionDate($event, $sessiondates[$i]->timestart, 3 * HOURSECS, 1);
        }
    }

}
