<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2014 onwards Totara Learning Solutions LTD
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
 * @author Oleg Demeshev <oleg.demeshev@totaralearning.com>
 * @package facetoface
 */

defined('MOODLE_INTERNAL') || die();

class facetoface_session_report_testcase extends advanced_testcase {

    protected $facetofacegenerator = null;
    protected $facetoface = null;
    protected $course = null;
    protected $context = null;
    protected $session = null;


    protected function tearDown() {
        $this->facetofacegenerator = null;
        $this->facetoface = null;
        $this->course = null;
        $this->context = null;
        $this->session = null;
        parent::tearDown();
    }

    protected function setUp() {

        $this->resetAfterTest();
        $this->setAdminUser();

        $this->facetofacegenerator = $this->getDataGenerator()->get_plugin_generator('mod_facetoface');
        $this->course = $this->getDataGenerator()->create_course();
        $this->facetoface = $this->getDataGenerator()->create_module('facetoface', array('course' => $this->course->id));
    }

    public function test_session_timecreated() {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();

        $student = $this->getDataGenerator()->create_user();
        $studentrole = $DB->get_record('role', array('shortname' => 'student'));

        $this->getDataGenerator()->enrol_user($student->id, $this->course->id, $studentrole->id);

        // Session that starts in the past.
        $sessiondate = new stdClass();
        $sessiondate->timestart = time() - (2 * DAYSECS);
        $sessiondate->timefinish = time() - (2 * DAYSECS) + 60;
        $sessiondate->sessiontimezone = 'Pacific/Auckland';

        $sessiondata = array(
            'facetoface' => $this->facetoface->id,
            'capacity' => 3,
            'sessiondates' => array($sessiondate),
            'datetimeknown' => '1',
        );
        $sessionid = $this->facetofacegenerator->add_session($sessiondata);
        $session = facetoface_get_session($sessionid);

        // Sign user up.
        facetoface_user_signup($session, $this->facetoface, $this->course, '', MDL_F2F_NONE, MDL_F2F_STATUS_BOOKED, $student->id);

        // Totara hack: "move time to forward"?
        $sessiondate = new stdClass();
        $sessiondate->timestart = time() - DAYSECS;
        $sessiondate->timefinish = time() - DAYSECS + 60;
        $sessiondate->sessiontimezone = 'Pacific/Auckland';
        $sessiondates = array($sessiondate);
        facetoface_save_dates($session->id, $sessiondates);

        $signup = $DB->get_record('facetoface_signups', array('sessionid' => $session->id, 'userid' => $student->id));
        // Take the user time signup.
        $timesignup = $DB->get_record('facetoface_signups_status', array('signupid' => $signup->id, 'statuscode' => MDL_F2F_STATUS_BOOKED), 'timecreated');

        // Take attendees action and change user status to Fully attended.
        $grade = 100;
        facetoface_update_signup_status($signup->id, MDL_F2F_STATUS_FULLY_ATTENDED, $student->id, $grade);
        facetoface_take_individual_attendance($signup->id, $grade);

        // Get signup time after user changed the status by using report builder.
        $shortname = 'facetoface_sessions';
        $attendancestatuses = array(MDL_F2F_STATUS_BOOKED, MDL_F2F_STATUS_FULLY_ATTENDED, MDL_F2F_STATUS_NOT_SET,
            MDL_F2F_STATUS_NO_SHOW, MDL_F2F_STATUS_PARTIALLY_ATTENDED);
        $report = reportbuilder_get_embedded_report($shortname, array('sessionid' => $session->id, 'status' => $attendancestatuses), false, 0);
        list($sql, $params, $cache) = $report->build_query(false, true);
        $record = $DB->get_record_sql($sql, $params);

        $this->assertEquals($timesignup->timecreated, $record->status_timecreated);
    }
}
