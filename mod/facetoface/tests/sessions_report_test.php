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
require_once ($CFG->dirroot . "/mod/facetoface/lib.php");

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

    /**
     * @param stdClass $user
     * @return reportbuilder
     */
    private function create_facetoface_session_report(stdClass $user): reportbuilder {
        global $DB;

        $data = [
            'fullname' => "Seminar Sign-ups test",
            'shortname' => "short",
            'source' => "facetoface_sessions",
            'hidden' => 0,
            'cache' => 0,
            'accessmode' => 1,
            'contentmode' => 0,
            'description' => 'This is the report',
            'globalrestriction' => 0,
            'timemodified' => time(),
        ];

        $id = $DB->insert_record("report_builder", (object)$data, true);

        $data['id'] = $id;
        $reportdata = (object)$data;
        $this->set_up_columns((object)$reportdata);

        return new reportbuilder(
            $id,
            $reportdata->shortname,
            false,
            null,
            $user->id,
            false,
            [],
            null
        );
    }

    /**
     * @param stdClass $report
     */
    private function set_up_columns(stdClass $report): void {
        global $DB;

        /** @var rb_source_facetoface_sessions $source */
        $source = reportbuilder::get_source_object($report->source);
        $columnoptions = $source->columnoptions;
        $sortorder = 1;
        $columnsrequired = array(
            'sessiondate',
            'namelink',
            'courselink',
            'statuscode'
        );

        /** @var rb_column_option $columnoption */
        foreach ($columnoptions as $columnoption) {
            if (in_array($columnoption->value, $columnsrequired, false)) {
                $DB->insert_record("report_builder_columns", (object)[
                    'reportid' => $report->id,
                    'type' => $columnoption->type,
                    'value' => $columnoption->value,
                    'sortorder' => $sortorder,
                    'hidden' => 0,
                    'customheading' => 0
                ]);

                $sortorder += 1;
            }
        }
    }

    /**
     * Create user (1)
     * Create user (2)
     * Create course
     * Create Seminar
     * Create Seminar's event (facetoface_session)
     * Add user (1 and 2) to seminar's event
     *
     * @param stdClass $user
     */
    private function generate_data(stdClass $user): void {
        global $DB;

        $user1 = $this->getDataGenerator()->create_user([
            'firstname' => "kian",
            'lastname' => "nguyen"
        ]);

        $user2 = $this->getDataGenerator()->create_user([
            'firstname' => "james",
            'lastname' => "lebron"
        ]);

        $course = $this->getDataGenerator()->create_course();
        $time = time();

        $seminarid = $DB->insert_record("facetoface", (object)[
            'course' => $course->id,
            'name' => "Seminar_name",
            'timecreated' => $time,
            'timemodified' => $time
        ]);

        $sessionid = $DB->insert_record("facetoface_sessions", (object)[
            'facetoface' => $seminarid,
            'capacity' => 10,
            'timecreated' => $time,
            'timemodified' => $time,
            'usermodified' => $user->id,
        ]);

        $users = array($user1, $user2);
        foreach ($users as $normaluser) {
            $DB->insert_record("facetoface_signups", (object)[
                'sessionid' => $sessionid,
                'userid' => $normaluser->id,
                'notificationtype' => MDL_F2F_BOTH,
                'bookedby' => $user->id
            ]);
        }
    }

    /**
     * @param reportbuilder $reportbuilder
     * @return counted_recordset
     */
    private function query_records(reportbuilder $reportbuilder): counted_recordset {
        list ($sql, $params, $cache) = $reportbuilder->build_query(false, true);

        $refClass = new ReflectionClass($reportbuilder);
        $method = $refClass->getMethod("get_counted_recordset_sql");
        $method->setAccessible(true);
        $recordset = $method->invokeArgs($reportbuilder, [$sql, $params, 0, 100, true]);

        return $recordset;
    }

    /**
     * Test suite of report builder assuring that the number of
     * wait-listed users appearing in the counted_recordset instance
     */
    public function test_number_of_waitlist_user(): void {
        global $USER;
        $this->resetAfterTest(true);
        $this->setAdminUser();

        $this->generate_data($USER);
        $reportbuilder = $this->create_facetoface_session_report($USER);

        $recordset = $this->query_records($reportbuilder);
        $this->assertEquals(2, $recordset->get_count_without_limits());
    }

    /**
     * The test suite to assure that the record set
     * includes the users that are wait-listed in an
     * seminar event.
     */
    public function test_waitlist_user_in_records(): void {
        global $USER;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $this->generate_data($USER);
        $reportbuilder = $this->create_facetoface_session_report($USER);

        $recordset = $this->query_records($reportbuilder);
        $expected = array("kian nguyen", "james lebron");

        foreach ($recordset as $record) {
            $this->assertContains((string) @$record->user_namelink, $expected);
        }

    }
}
