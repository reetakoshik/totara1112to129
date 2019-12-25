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

/**
 * Unit test of assuring the users that are waitlisted
 * in an event of a seminar would still appear in
 * the report querying
 */
class mod_facetoface_seminar_waitlisted_testcase extends advanced_testcase {
    use totara_reportbuilder\phpunit\report_testing;

    /**
     * @return int report id
     */
    private function create_facetoface_session_report(): int {
        $rid = $this->create_report('facetoface_sessions', 'Seminar Sign-ups test', false, 0);
        $report = reportbuilder::create($rid, null, false);

        $columnsrequired = array(
            'sessiondate',
            'namelink',
            'courselink',
            'statuscode'
        );

        foreach ($report->columnoptions as $columnoption) {
            if (in_array($columnoption->value, $columnsrequired, false)) {
                $this->add_column($report, $columnoption->type, $columnoption->value, null, null, null, 0);
            }
        }

        return $report->_id;
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
     * @return moodle_recordset
     */
    private function query_records(reportbuilder $reportbuilder): moodle_recordset {
        $refClass = new ReflectionClass($reportbuilder);
        $method = $refClass->getMethod("get_data");
        $method->setAccessible(true);
        $recordset = $method->invoke($reportbuilder);
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
        $rid = $this->create_facetoface_session_report();

        $reportbuilder = reportbuilder::create($rid);
        $this->assertEquals(2, $reportbuilder->get_filtered_count());
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
        $rid = $this->create_facetoface_session_report();

        $reportbuilder = reportbuilder::create($rid);

        $recordset = $this->query_records($reportbuilder);
        $expected = array("kian nguyen", "james lebron");

        foreach ($recordset as $record) {
            $this->assertContains((string) @$record->user_namelink, $expected);
        }

    }
}