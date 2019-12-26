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
 * @author Brendan Cox <brendan.cox@totaralearning.com>
 * @package core_calendar
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/calendar/lib.php');

/**
 * Class core_calendar_calendar_information_testcase
 *
 * This tests the calendar_information classs, currently found in calendar/lib.php.
 */
class core_calendar_calendar_information_testcase extends advanced_testcase {

    /** @var  testing_data_generator */
    private $data_generator;

    /** @var  mod_facetoface_generator */
    private $facetoface_generator;

    private $course1, $course2, $course3, $course4, $course5, $course6;

    protected function tearDown() {
        $this->data_generator = null;
        $this->facetoface_generator = null;
        $this->course1 = $this->course2 = $this->course3 = $this->course4 = $this->course5 = $this->course6 = null;

        parent::tearDown();
    }

    protected function setUp() {
        $this->resetAfterTest(true);

        $this->data_generator = $this->getDataGenerator();
        $this->facetoface_generator = $this->data_generator->get_plugin_generator('mod_facetoface');
    }

    /**
     * This tests the calendar_information::get_time_boundaries method.
     *
     * By default, the output from this function should include
     * a time that represents the start of the previous month along with
     * a time that represents the end of the next month.
     *
     * This applies to 'month', 'day', 'sideblockonly', but does not always apply to 'upcoming'.
     */
    public function test_get_time_boundaries_default() {
        $time1 = 1476072000; // Represents 10 October 2016, 04:00:00 UTC.
        $starttime1 = 1472688000; // Represents 01 September 2016, 00:00:00 UTC.
        $endtime1 = 1480550399; // Represents 30 November 2016, 23:59:59 UTC
        $timezone1 = 'UTC';

        $calender_information = new calendar_information(0,0,0, $time1);
        $boundaries = $calender_information->get_time_boundaries('month', $timezone1);
        $this->assertEquals($starttime1, $boundaries['start']);
        $this->assertEquals($endtime1, $boundaries['end']);

        // Test with a different timezone.
        // This timezone does not currently observe daylight savings (and did not in 2013).
        $time2 = 1361314800; // Represents 20 February 2013, 08:00:00 Asia/Seoul.
        $starttime2 = 1356966000; // Represents 01 January 2013, 00:00:00 Asia/Seoul.
        $endtime2 = 1364741999; // Represents 31 March 2013, 23:59:59 Asia/Seoul.
        $timezone2 = 'Asia/Seoul';

        $calender_information = new calendar_information(0,0,0, $time2);
        $boundaries = $calender_information->get_time_boundaries('month', $timezone2);
        $this->assertEquals($starttime2, $boundaries['start']);
        $this->assertEquals($endtime2, $boundaries['end']);

        // Repeat the above with other strings for $view to get the same results.
        // 'Upcoming' will be tested in a separate test.

        $calender_information = new calendar_information(0,0,0, $time1);
        $boundaries = $calender_information->get_time_boundaries('day', $timezone1);
        $this->assertEquals($starttime1, $boundaries['start']);
        $this->assertEquals($endtime1, $boundaries['end']);

        $calender_information = new calendar_information(0,0,0, $time2);
        $boundaries = $calender_information->get_time_boundaries('day', $timezone2);
        $this->assertEquals($starttime2, $boundaries['start']);
        $this->assertEquals($endtime2, $boundaries['end']);

        $calender_information = new calendar_information(0,0,0, $time1);
        $boundaries = $calender_information->get_time_boundaries('sideblockonly', $timezone1);
        $this->assertEquals($starttime1, $boundaries['start']);
        $this->assertEquals($endtime1, $boundaries['end']);

        $calender_information = new calendar_information(0,0,0, $time2);
        $boundaries = $calender_information->get_time_boundaries('sideblockonly', $timezone2);
        $this->assertEquals($starttime2, $boundaries['start']);
        $this->assertEquals($endtime2, $boundaries['end']);

        // The get_time_boundaries method will work with strings for views that are not currently supported.
        // But it should be noted that the get_default_courses method, which calls get_time_boundaries, may
        // need to be modified.
        $calender_information = new calendar_information(0,0,0, $time1);
        $boundaries = $calender_information->get_time_boundaries('somecustomstring', $timezone1);
        $this->assertEquals($starttime1, $boundaries['start']);
        $this->assertEquals($endtime1, $boundaries['end']);

        $calender_information = new calendar_information(0,0,0, $time2);
        $boundaries = $calender_information->get_time_boundaries('somecustomstring', $timezone2);
        $this->assertEquals($starttime2, $boundaries['start']);
        $this->assertEquals($endtime2, $boundaries['end']);
    }

    /**
     * Tests the calendar_information::get_time_boundaries method
     * using the 'upcoming' view.
     */
    public function test_get_time_boundaries_upcoming() {
        $time1 = 1476072000; // Represents 10 October 2016, 04:00:00 UTC.
        $starttime1 = 1472688000; // Represents 01 September 2016, 00:00:00 UTC.
        $endtime1 = 1480550399; // Represents 30 November 2016, 23:59:59 UTC
        $timezone1 = 'UTC';

        // By default, the 'upcoming' view will supply the same results as other strings such as
        // 'month'.

        $calender_information = new calendar_information(0,0,0, $time1);
        $boundaries = $calender_information->get_time_boundaries('upcoming', $timezone1);
        $this->assertEquals($starttime1, $boundaries['start']);
        $this->assertEquals($endtime1, $boundaries['end']);

        // Test with a different timezone.
        // This timezone does not currently observe daylight savings (and did not in 2013).
        $time2 = 1361314800; // Represents 20 February 2013, 08:00:00 Asia/Seoul.
        $starttime2 = 1356966000; // Represents 01 January 2013, 00:00:00 Asia/Seoul.
        $endtime2 = 1364741999; // Represents 31 March 2013, 23:59:59 Asia/Seoul.
        $timezone2 = 'Asia/Seoul';

        $calender_information = new calendar_information(0,0,0, $time2);
        $boundaries = $calender_information->get_time_boundaries('upcoming', $timezone2);
        $this->assertEquals($starttime2, $boundaries['start']);
        $this->assertEquals($endtime2, $boundaries['end']);

        // Now update the calendar lookahead setting.
        // To start with, let's make it less than a month.
        set_config('calendar_lookahead', 10);

        // The results don't change. This is because we need to load courses through til the end of the next
        // month for the sideblock that is often loaded on these pages.

        $calender_information = new calendar_information(0,0,0, $time1);
        $boundaries = $calender_information->get_time_boundaries('upcoming', $timezone1);
        $this->assertEquals($starttime1, $boundaries['start']);
        $this->assertEquals($endtime1, $boundaries['end']);

        $calender_information = new calendar_information(0,0,0, $time2);
        $boundaries = $calender_information->get_time_boundaries('upcoming', $timezone2);
        $this->assertEquals($starttime2, $boundaries['start']);
        $this->assertEquals($endtime2, $boundaries['end']);

        // Now let's update the setting to go beyond the end of the following month.
        set_config('calendar_lookahead', 100);

        // The end times are end of the day (for the given timezone),
        // 100 days ahead of the supplied $time1 or $time2.
        $newendtime1 = 1484697599;
        $newendtime2 = 1369925999;

        $calender_information = new calendar_information(0,0,0, $time1);
        $boundaries = $calender_information->get_time_boundaries('upcoming', $timezone1);
        $this->assertEquals($starttime1, $boundaries['start']);
        $this->assertEquals($newendtime1, $boundaries['end']);

        $calender_information = new calendar_information(0,0,0, $time2);
        $boundaries = $calender_information->get_time_boundaries('upcoming', $timezone2);
        $this->assertEquals($starttime2, $boundaries['start']);
        $this->assertEquals($newendtime2, $boundaries['end']);
    }

    /**
     * Creates a small number of courses and adds events in the past and future
     * to most of them.
     */
    private function create_courses_with_events() {
        global $DB;

        $now = time();
        $this->course1 = $this->data_generator->create_course(); // To have an event in recent past.
        $this->course2 = $this->data_generator->create_course(); // To have an event in recent past.
        $this->course3 = $this->data_generator->create_course(); // To have no events
        $this->course4 = $this->data_generator->create_course(); // To have an event in the near future.
        $this->course5 = $this->data_generator->create_course(); // To have an event far back in the past.
        $this->course6 = $this->data_generator->create_course(); // To have an event far into the future.

        $facetoface1 = $this->facetoface_generator->create_instance(array('course' => $this->course1->id));
        $sessiondate = new stdClass();
        $sessiondate->timestart = $now - 10 * DAYSECS;
        $sessiondate->timefinish = $now - 9 * DAYSECS;
        $sessiondate->sessiontimezone = '99';
        $sessiondate->assetids = array();
        $this->facetoface_generator->add_session(array('facetoface' => $facetoface1->id, 'sessiondates' => array($sessiondate)));

        $facetoface2 = $this->facetoface_generator->create_instance(array('course' => $this->course2->id));
        $sessiondate = new stdClass();
        $sessiondate->timestart = $now - 10 * DAYSECS;
        $sessiondate->timefinish = $now - 9 * DAYSECS;
        $sessiondate->sessiontimezone = '99';
        $sessiondate->assetids = array();
        $this->facetoface_generator->add_session(array('facetoface' => $facetoface2->id, 'sessiondates' => array($sessiondate)));

        $facetoface4 = $this->facetoface_generator->create_instance(array('course' => $this->course4->id));
        $sessiondate = new stdClass();
        $sessiondate->timestart = $now + 9 * DAYSECS;
        $sessiondate->timefinish = $now + 10 * DAYSECS;
        $sessiondate->sessiontimezone = '99';
        $sessiondate->assetids = array();
        $this->facetoface_generator->add_session(array('facetoface' => $facetoface4->id, 'sessiondates' => array($sessiondate)));

        $facetoface5 = $this->facetoface_generator->create_instance(array('course' => $this->course5->id));
        $sessiondate = new stdClass();
        $sessiondate->timestart = $now - 2 * YEARSECS;
        $sessiondate->timefinish = $now - 2 * YEARSECS + 3 * HOURSECS;
        $sessiondate->sessiontimezone = '99';
        $sessiondate->assetids = array();
        $this->facetoface_generator->add_session(array('facetoface' => $facetoface5->id, 'sessiondates' => array($sessiondate)));

        $facetoface6 = $this->facetoface_generator->create_instance(array('course' => $this->course6->id));
        $sessiondate = new stdClass();
        $sessiondate->timestart = $now + 2 * YEARSECS;
        $sessiondate->timefinish = $now + 2 * YEARSECS + 3 * HOURSECS;
        $sessiondate->sessiontimezone = '99';
        $sessiondate->assetids = array();
        $this->facetoface_generator->add_session(array('facetoface' => $facetoface6->id, 'sessiondates' => array($sessiondate)));

        // We still need to add the calendar entries.
        $seminarevents = \mod_facetoface\seminar_event_list::get_all();
        foreach ($seminarevents as $seminarevent) {
            \mod_facetoface\calendar::update_entries($seminarevent);
        }
    }

    /**
     * Create a number of courses which will all contain an event that starts now.
     *
     * @param int $number - number of courses to add.
     */
    public function create_many_courses_with_events($number) {
        global $DB;

        $now = time();
        for ($i = 0; $i < $number; $i++) {
            $course = $this->data_generator->create_course();
            $facetoface = $this->facetoface_generator->create_instance(array('course' => $course->id));
            $sessiondate = new stdClass();
            $sessiondate->timestart = $now;
            $sessiondate->timefinish = $now + 3 * HOURSECS;
            $sessiondate->sessiontimezone = '99';
            $sessiondate->assetids = array();
            $this->facetoface_generator->add_session(array('facetoface' => $facetoface->id, 'sessiondates' => array($sessiondate)));
        }

        // We still need to add the calendar entries.
        $seminarevents = \mod_facetoface\seminar_event_list::get_all();
        foreach ($seminarevents as $seminarevent) {
            \mod_facetoface\calendar::update_entries($seminarevent);
        }
    }

    /**
     * Tests the calendar_information::get_default_courses method.
     *
     * Tests the output for a learner with standard user permissions.
     */
    public function test_get_default_courses_learner() {
        $this->create_courses_with_events();
        $now = time();

        $user1 = $this->data_generator->create_user();
        $user2 = $this->data_generator->create_user();

        $this->data_generator->enrol_user($user1->id, $this->course2->id);
        $this->data_generator->enrol_user($user1->id, $this->course3->id);
        $this->data_generator->enrol_user($user2->id, $this->course4->id);

        $this->setUser($user1);

        // Regardless of view, all enrolled courses should be returned and no others.
        // Whether events are linked to these courses is not taken into account.
        $calendar_information = new calendar_information(0,0,0,$now);
        $courses = $calendar_information->get_default_courses('month');

        $this->assertCount(2, $courses);
        $this->assertArrayNotHasKey($this->course1->id, $courses);
        $this->assertArrayHasKey($this->course2->id, $courses);
        $this->assertArrayHasKey($this->course3->id, $courses);
        $this->assertArrayNotHasKey($this->course4->id, $courses);
        $this->assertArrayNotHasKey($this->course5->id, $courses);
        $this->assertArrayNotHasKey($this->course6->id, $courses);

        // Using a different view makes no difference.
        $courses = $calendar_information->get_default_courses('upcoming');

        $this->assertCount(2, $courses);
        $this->assertArrayNotHasKey($this->course1->id, $courses);
        $this->assertArrayHasKey($this->course2->id, $courses);
        $this->assertArrayHasKey($this->course3->id, $courses);
        $this->assertArrayNotHasKey($this->course4->id, $courses);
        $this->assertArrayNotHasKey($this->course5->id, $courses);
        $this->assertArrayNotHasKey($this->course6->id, $courses);
    }

    /**
     * Tests the calendar_information::get_default_courses method.
     *
     * Tests the output for a site admin but without calendar_adminseesall setting turned off.
     */
    public function test_get_default_courses_admin_doesntseeall() {
        global $CFG;

        $this->create_courses_with_events();
        $now = time();

        $user1 = $this->data_generator->create_user();
        $admin = get_admin();

        $this->data_generator->enrol_user($user1->id, $this->course2->id);
        $this->data_generator->enrol_user($admin->id, $this->course3->id);
        $this->data_generator->enrol_user($admin->id, $this->course4->id);

        $this->setAdminUser();

        // The adminseesall setting should be off by default.
        $this->assertEmpty($CFG->calendar_adminseesall);

        // Without the adminseesall setting turned on, the admin will only get
        // enrolled courses just like any other users.
        // Regardless of view, all enrolled courses should be returned and no others.
        // Whether events are linked to these courses is not taken into account.
        $calendar_information = new calendar_information(0,0,0,$now);
        $courses = $calendar_information->get_default_courses('month');

        $this->assertArrayNotHasKey($this->course1->id, $courses);
        $this->assertArrayNotHasKey($this->course2->id, $courses);
        $this->assertArrayHasKey($this->course3->id, $courses);
        $this->assertArrayHasKey($this->course4->id, $courses);
        $this->assertArrayNotHasKey($this->course5->id, $courses);
        $this->assertArrayNotHasKey($this->course6->id, $courses);
        $this->assertCount(2, $courses);

        // Using a different view makes no difference.
        $courses = $calendar_information->get_default_courses('upcoming');

        $this->assertArrayNotHasKey($this->course1->id, $courses);
        $this->assertArrayNotHasKey($this->course2->id, $courses);
        $this->assertArrayHasKey($this->course3->id, $courses);
        $this->assertArrayHasKey($this->course4->id, $courses);
        $this->assertArrayNotHasKey($this->course5->id, $courses);
        $this->assertArrayNotHasKey($this->course6->id, $courses);
        $this->assertCount(2, $courses);
    }

    /**
     * Tests the calendar_information::get_default_courses method.
     *
     * Tests the output for a site admin but without calendar_adminseesall setting turned on.
     */
    public function test_get_default_courses_adminseesall_timedependent() {
        global $CFG;

        $this->create_courses_with_events();
        $now = time();

        $user1 = $this->data_generator->create_user();
        $admin = get_admin();

        $this->data_generator->enrol_user($user1->id, $this->course2->id);

        $this->data_generator->enrol_user($admin->id, $this->course1->id);
        $this->data_generator->enrol_user($admin->id, $this->course3->id);
        $this->data_generator->enrol_user($admin->id, $this->course4->id);
        $this->data_generator->enrol_user($admin->id, $this->course5->id);
        $this->data_generator->enrol_user($admin->id, $this->course6->id);

        $this->setAdminUser();

        // The adminseesall setting should be off by default.
        $this->assertEmpty($CFG->calendar_adminseesall);

        // Change the setting.
        set_config('calendar_adminseesall', 1);

        // When the adminseesall setting is turned on, the admin will get courses where
        // there is an event that is between beginning of last month and end of next month.
        // See the method in this test class, create_courses_with_events for times used.
        $calendar_information = new calendar_information(0,0,0,$now);
        $courses = $calendar_information->get_default_courses('month');

        $this->assertArrayHasKey($this->course1->id, $courses); // Definitely since beginning of last month.
        $this->assertArrayHasKey($this->course2->id, $courses); // Doesn't matter that not enrolled.
        $this->assertArrayNotHasKey($this->course3->id, $courses); // No events.
        $this->assertArrayHasKey($this->course4->id, $courses); // Definitely before end of next month).
        $this->assertArrayNotHasKey($this->course5->id, $courses); // Well before start of last month.
        $this->assertArrayNotHasKey($this->course6->id, $courses); // Well after end of next month.
        $this->assertCount(3, $courses);

        // Same results with the 'day' view.
        $courses = $calendar_information->get_default_courses('day');

        $this->assertArrayHasKey($this->course1->id, $courses);
        $this->assertArrayHasKey($this->course2->id, $courses);
        $this->assertArrayNotHasKey($this->course3->id, $courses);
        $this->assertArrayHasKey($this->course4->id, $courses);
        $this->assertArrayNotHasKey($this->course5->id, $courses);
        $this->assertArrayNotHasKey($this->course6->id, $courses);
        $this->assertCount(3, $courses);

        // Same results with the sideblockonly view.
        $courses = $calendar_information->get_default_courses('sideblockonly');

        $this->assertArrayHasKey($this->course1->id, $courses);
        $this->assertArrayHasKey($this->course2->id, $courses);
        $this->assertArrayNotHasKey($this->course3->id, $courses);
        $this->assertArrayHasKey($this->course4->id, $courses);
        $this->assertArrayNotHasKey($this->course5->id, $courses);
        $this->assertArrayNotHasKey($this->course6->id, $courses);
        $this->assertCount(3, $courses);

        // With unsupported view, the old function is used. This does not restrict by time.
        // The site has less than 20 courses with events, so all those with events are returned.
        // IMPORTANT: This part should fail when deprecated function calendar_get_default_courses is removed.
        // At that point, alter calendar_information::get_default_courses to throw an exception
        // instead of calling that function. Change this test to reflect that.
        $courses = $calendar_information->get_default_courses('somecustomstring');
        $this->assertDebuggingCalled('calendar_get_default_courses has been deprecated since Totara 10.0. Please use calendar_information::get_default_courses instead.');
        $this->assertArrayHasKey($this->course1->id, $courses);
        $this->assertArrayHasKey($this->course2->id, $courses);
        $this->assertArrayNotHasKey($this->course3->id, $courses); // Only the course without events is excluded.
        $this->assertArrayHasKey($this->course4->id, $courses);
        $this->assertArrayHasKey($this->course5->id, $courses);
        $this->assertArrayHasKey($this->course6->id, $courses);
        $this->assertCount(5, $courses);

        // Providing we update the lookahead value, 'upcoming' will give us different results from
        // other supported views.
        set_config('calendar_lookahead', 3 * YEARSECS);
        $courses = $calendar_information->get_default_courses('upcoming');

        $this->assertArrayHasKey($this->course1->id, $courses);
        $this->assertArrayHasKey($this->course2->id, $courses);
        $this->assertArrayNotHasKey($this->course3->id, $courses);
        $this->assertArrayHasKey($this->course4->id, $courses);
        $this->assertArrayNotHasKey($this->course5->id, $courses);
        $this->assertArrayHasKey($this->course6->id, $courses); // Included as far into future, but within 3 years.
        $this->assertCount(4, $courses);
    }

    /**
     * Tests the calendar_information::get_default_courses method.
     *
     * This uses site admin and calendar_adminseesall turned on.
     *
     * This is not for testing that specific courses appear. But simply checks the effect
     * of the calendar_adminallcourseslimit on the number of courses returned.
     */
    public function test_get_default_courses_adminseesall_defaultlimit() {

        // Set the admin sees all setting.
        set_config('calendar_adminseesall', 1);
        $this->setAdminUser();

        $now = time();

        // The default limit 50. So at 49, the limit should have no effect.
        $this->create_many_courses_with_events(49);

        $calendar_information = new calendar_information(0,0,0,$now);
        $courses = $calendar_information->get_default_courses('month');
        $this->assertCount(49, $courses);
        $this->assertDebuggingNotCalled();

        // We add one more course to bring the total to exactly 50 courses with events.
        $this->create_many_courses_with_events(1);

        $calendar_information = new calendar_information(0,0,0,$now);
        $courses = $calendar_information->get_default_courses('month');
        $this->assertCount(50, $courses);
        $this->assertDebuggingCalled('The number of courses returned when loading the calendar has reached the current limit. Some events may not be shown.');

        // Now add more in order to go over the limit. We should get the same results, at least
        // with the number of courses. The courses returned could be a different 50 courses this time,
        // but that's okay, we're not applying any logic to ordering of courses.
        $this->create_many_courses_with_events(10);

        $calendar_information = new calendar_information(0,0,0,$now);
        $courses = $calendar_information->get_default_courses('month');
        $this->assertCount(50, $courses);
        $this->assertDebuggingCalled('The number of courses returned when loading the calendar has reached the current limit. Some events may not be shown.');
    }

    /**
     * Tests the calendar_information::get_default_courses method.
     *
     * This uses site admin and calendar_adminseesall turned on.
     *
     * This is not for testing that specific courses appear. But simply checks the effect
     * of the calendar_adminallcourseslimit on the number of courses returned.
     */
    public function test_get_default_courses_adminseesall_lowerlimit() {

        // Set the admin sees all setting.
        set_config('calendar_adminseesall', 1);
        $this->setAdminUser();

        $now = time();

        // Change the limit.
        set_config('calendar_adminallcourseslimit', 10);

        $this->create_many_courses_with_events(9);

        $calendar_information = new calendar_information(0,0,0,$now);
        $courses = $calendar_information->get_default_courses('sideblockonly');
        $this->assertCount(9, $courses);
        $this->assertDebuggingNotCalled();

        // We add one more course to bring the total to exactly 10 courses with events.
        $this->create_many_courses_with_events(1);

        $calendar_information = new calendar_information(0,0,0,$now);
        $courses = $calendar_information->get_default_courses('sideblockonly');
        $this->assertCount(10, $courses);
        $this->assertDebuggingCalled('The number of courses returned when loading the calendar has reached the current limit. Some events may not be shown.');

        // Now add more in order to go over the limit. We should get the same course count.
        $this->create_many_courses_with_events(15);

        $calendar_information = new calendar_information(0,0,0,$now);
        $courses = $calendar_information->get_default_courses('month');
        $this->assertCount(10, $courses);
        $this->assertDebuggingCalled('The number of courses returned when loading the calendar has reached the current limit. Some events may not be shown.');
    }

    /**
     * Tests the calendar_information::get_default_courses method.
     *
     * This uses site admin and calendar_adminseesall turned on.
     *
     * This is not for testing that specific courses appear. But simply checks the effect
     * of the calendar_adminallcourseslimit on the number of courses returned.
     */
    public function test_get_default_courses_adminseesall_higherlimit() {

        // Set the admin sees all setting.
        set_config('calendar_adminseesall', 1);
        $this->setAdminUser();

        $now = time();

        // Change the limit.
        set_config('calendar_adminallcourseslimit', 70);

        $this->create_many_courses_with_events(69);

        $calendar_information = new calendar_information(0,0,0,$now);
        $courses = $calendar_information->get_default_courses('day');
        $this->assertCount(69, $courses);
        $this->assertDebuggingNotCalled();

        // We add one more course to bring the total to exactly 70 courses with events.
        $this->create_many_courses_with_events(1);

        $calendar_information = new calendar_information(0,0,0,$now);
        $courses = $calendar_information->get_default_courses('day');
        $this->assertCount(70, $courses);
        $this->assertDebuggingCalled('The number of courses returned when loading the calendar has reached the current limit. Some events may not be shown.');

        // Now add more in order to go over the limit. We should get the same course count.
        $this->create_many_courses_with_events(5);

        $calendar_information = new calendar_information(0,0,0,$now);
        $courses = $calendar_information->get_default_courses('day');
        $this->assertCount(70, $courses);
        $this->assertDebuggingCalled('The number of courses returned when loading the calendar has reached the current limit. Some events may not be shown.');
    }

    /**
     * Tests the calendar_information::get_default_courses method.
     *
     * This uses site admin and calendar_adminseesall turned on.
     *
     * This is not for testing that specific courses appear. But simply checks the effect
     * of the calendar_adminallcourseslimit on the number of courses returned.
     */
    public function test_get_default_courses_adminseesall_nolimit() {

        // Set the admin sees all setting.
        set_config('calendar_adminseesall', 1);
        $this->setAdminUser();

        $now = time();

        // Change the limit.
        set_config('calendar_adminallcourseslimit', 0);

        // We haven't created any courses with events. The debugging message should not be shown.
        $calendar_information = new calendar_information(0,0,0,$now);
        $courses = $calendar_information->get_default_courses('sideblockonly');
        $this->assertCount(0, $courses);
        $this->assertDebuggingNotCalled();

        // Create whole lot of courses. They should all be returned and there should never be a debugging message.
        $this->create_many_courses_with_events(75);

        $calendar_information = new calendar_information(0,0,0,$now);
        $courses = $calendar_information->get_default_courses('sideblockonly');
        $this->assertCount(75, $courses);
        $this->assertDebuggingNotCalled();
    }

    /**
     * Tests the calendar_information::get_month_time_settings static method.
     */
    public function test_get_month_time_settings() {
        $time1 = 1473465600; // Represents 10 September 2016, 04:00:00 UTC.
        $timezone1 = 'UTC';

        $timesettings = calendar_information::get_month_time_settings($time1, $timezone1);
        $this->assertEquals(10, $timesettings->date['mday']);
        $this->assertEquals(9, $timesettings->date['mon']);
        $this->assertEquals(2016, $timesettings->date['year']);

        // The starting day of the week for this month.
        // This represents Thursday and we'll check that below.
        $this->assertEquals(4, $timesettings->startwday);
        $this->assertEquals('Thursday', $timesettings->daynames[4]['fullname']);

        // Number of days in the supplied month.
        $this->assertEquals(30, $timesettings->maxdays);

        // Is the supplied time for this month we are in now.
        $this->assertEquals(false, $timesettings->thismonth);

        // The timestamps for the start and end of the month (for the given timezone).
        $this->assertEquals(1472688000, $timesettings->tstart);
        $this->assertEquals(1475279999, $timesettings->tend);

        // The starting weekday. Default is 1, which is Monday.
        $this->assertEquals(1, $timesettings->minwday);

        $time2 = 1361314800; // Represents 20 February 2013, 08:00:00 Asia/Seoul.
        $timezone2 = 'Asia/Seoul';

        $timesettings = calendar_information::get_month_time_settings($time2, $timezone2);
        $this->assertEquals(20, $timesettings->date['mday']);
        $this->assertEquals(2, $timesettings->date['mon']);
        $this->assertEquals(2013, $timesettings->date['year']);
        $this->assertEquals(5, $timesettings->startwday);
        $this->assertEquals('Friday', $timesettings->daynames[5]['fullname']);
        $this->assertEquals(28, $timesettings->maxdays);
        $this->assertEquals(false, $timesettings->thismonth);
        $this->assertEquals(1359644400, $timesettings->tstart);
        $this->assertEquals(1362063599, $timesettings->tend);
        $this->assertEquals(1, $timesettings->minwday);

        // Now check the thismonth setting works when we are dealing with the current month.
        $timesettings = calendar_information::get_month_time_settings(time());
        $this->assertEquals(true, $timesettings->thismonth);
    }
}