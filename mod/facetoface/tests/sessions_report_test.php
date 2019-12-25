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
 * @package mod_facetoface
 */

defined('MOODLE_INTERNAL') || die();

use \mod_facetoface\signup;
use \mod_facetoface\signup\state\{fully_attended};
use \mod_facetoface\signup_helper;
use \mod_facetoface\seminar_event;

class mod_facetoface_sessions_report_testcase extends advanced_testcase {

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

        // Session will be moved to past
        $sessiondate = new stdClass();
        $sessiondate->timestart = time() + (2 * DAYSECS);
        $sessiondate->timefinish = time() + (2 * DAYSECS) + 60;
        $sessiondate->sessiontimezone = 'Pacific/Auckland';

        $sessiondata = array(
            'facetoface' => $this->facetoface->id,
            'capacity' => 3,
            'sessiondates' => array($sessiondate),
        );
        $sessionid = $this->facetofacegenerator->add_session($sessiondata);
        $sessiondata['datetimeknown'] = '1';
        $session = facetoface_get_session($sessionid);

        $seminarevent = new \mod_facetoface\seminar_event($session->id);
        $signup11 = \mod_facetoface\signup_helper::signup(\mod_facetoface\signup::create($student->id, $seminarevent));

        $sessiondate->timestart = time() - (2 * DAYSECS);
        $sessiondate->timefinish = time() - (2 * DAYSECS) + 60;
        facetoface_save_dates($session->id, [$sessiondate]);

        // Totara hack: "move time to forward"?
        $sessiondate = new stdClass();
        $sessiondate->timestart = time() - DAYSECS;
        $sessiondate->timefinish = time() - DAYSECS + 60;
        $sessiondate->sessiontimezone = 'Pacific/Auckland';
        $sessiondates = array($sessiondate);
        facetoface_save_dates($session->id, $sessiondates);

        $signup = $DB->get_record('facetoface_signups', array('sessionid' => $session->id, 'userid' => $student->id));
        // Take the user time signup.
        $timesignup = $DB->get_record('facetoface_signups_status', array('signupid' => $signup->id, 'statuscode' => \mod_facetoface\signup\state\booked::get_code()), 'timecreated');

        // Take attendees action and change user status to Fully attended.
        signup_helper::process_attendance($seminarevent, [$signup11->get_id() => fully_attended::get_code()]);

        // Get signup time after user changed the status by using report builder.
        $shortname = 'facetoface_sessions';
        $attendancestatuses = array(\mod_facetoface\signup\state\booked::get_code(), \mod_facetoface\signup\state\fully_attended::get_code(), \mod_facetoface\signup\state\not_set::get_code(),
            \mod_facetoface\signup\state\no_show::get_code(), \mod_facetoface\signup\state\partially_attended::get_code());
        $config = (new rb_config())->set_embeddata(['sessionid' => $session->id, 'status' => $attendancestatuses]);
        $report = reportbuilder::create_embedded($shortname, $config);

        list($sql, $params, $cache) = $report->build_query(false, true);
        $record = $DB->get_record_sql($sql, $params);

        $this->assertEquals($timesignup->timecreated, $record->status_timecreated);
    }
}
