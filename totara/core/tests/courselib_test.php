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
 * @package core
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/totara/reportbuilder/tests/reportcache_advanced_testcase.php');
require_once($CFG->dirroot . '/totara/certification/lib.php');
require_once($CFG->dirroot . '/mod/facetoface/lib.php');

/**
 * Class totara_core_courselib_testcase
 *
 * This tests functions in the course/lib.php file, particularly those added by Totara. It is here because
 * there is an existing courselib_test file, but we require one using reportcache_advanced_testcase. Also we
 * can avoid merge conflicts this way.
 */
class totara_core_courselib_testcase extends reportcache_advanced_testcase {

    /** @var totara_reportbuilder_cache_generator $generator */
    private $data_generator;

    /** @var core_completion_generator $completion_generator */
    private $completion_generator;

    /** @var mod_facetoface_generator $facetoface_generator */
    private $facetoface_generator;

    /** @var phpunit_message_sink $messagesink */
    private $messagesink;

    private $user1, $user2, $user3, $user4, $user5, $user6;

    public function setUp() {
        parent::setUp();
        $this->resetAfterTest();

        // Ignore messages and silence debug output in cron.
        $this->messagesink = $this->redirectMessages();
        ob_start();

        set_config('enablecompletion', 1);

        $this->data_generator = $this->getDataGenerator();

        $this->completion_generator = $this->data_generator->get_plugin_generator('core_completion');

        $this->facetoface_generator = $this->data_generator->get_plugin_generator('mod_facetoface');

        $this->user1 = $this->data_generator->create_user();
        $this->user2 = $this->data_generator->create_user();
        $this->user3 = $this->data_generator->create_user();
        $this->user4 = $this->data_generator->create_user();
        $this->user5 = $this->data_generator->create_user();
        $this->user6 = $this->data_generator->create_user();
    }

    protected function tearDown() {
        ob_end_clean();
        $this->messagesink->close();
        $this->data_generator = null;
        $this->completion_generator = null;
        $this->facetoface_generator = null;
        $this->messagesink = null;
        $this->user1 = null;
        parent::tearDown();
    }

    /**
     * Create several activities to add to the course.
     *
     * About the choice of activities:
     * There's several factors that come into play when resetting activities in
     * archive_course_activities that determine how completion is processed.
     * These are if it supports FEATURE_ARCHIVE_COMPLETION,
     * FEATURE_COMPLETION_TRACKS_VIEWS and whether it has a _grade_item_update function.
     * So the activities were chosen for the following reasons:
     *
     * facetoface - supports FEATURE_ARCHIVE_COMPLETION.
     * url - doesn't support FEATURE_ARCHIVE_COMPLETION but does support FEATURE_COMPLETION_TRACKS_VIEWS.
     * label - doesn't support FEATURE_ARCHIVE_COMPLETION nor FEATURE_COMPLETION_TRACKS_VIEWS.
     * All of the activities that have _grade_item_update functions currently also support
     * FEATURE_ARCHIVE_COMPLETION, which means none will arrive at the _grade_item_update function,
     * so it's not necessary to account for here.
     */
    private function set_up_activities_for_course($course) {
        // Face-to-face.
        $facetofacedata = array(
            'name' => 'facetoface1',
            'course' => $course->id
        );
        $f2fmoduleoptions = array(
            'completion' => COMPLETION_TRACKING_AUTOMATIC,
            'completionstatusrequired' => json_encode(array(MDL_F2F_STATUS_FULLY_ATTENDED))
        );
        $facetoface = $this->facetoface_generator->create_instance($facetofacedata, $f2fmoduleoptions);

        // Session that has already finished.
        $sessiondate = new stdClass();
        $sessiondate->timestart = time() - DAYSECS;
        $sessiondate->timefinish = time() - DAYSECS + 60;
        $sessiondate->sessiontimezone = 'Pacific/Auckland';
        $sessiondata = array(
            'facetoface' => $facetoface->id,
            'capacity' => 10,
            'allowoverbook' => 1,
            'sessiondates' => array($sessiondate),
            'mincapacity' => '4',
            'cutoff' => "86400"
        );
        $sessionid = $this->facetoface_generator->add_session($sessiondata);
        $session = facetoface_get_session($sessionid);

        // URL.
        $url = $this->data_generator->create_module('url', array('course' => $course->id),
            array('completion' => COMPLETION_TRACKING_AUTOMATIC, 'completionview' => COMPLETION_VIEW_REQUIRED));

        // Label.
        $label = $this->data_generator->create_module('label', array('course' => $course->id),
            array('completion' => COMPLETION_TRACKING_MANUAL));

        $this->completion_generator->set_activity_completion($course->id, array($facetoface, $url, $label), COMPLETION_AGGREGATION_ANY);

        return array($facetoface, $session, $url, $label);
    }

    /**
     * User1 will complete the f2f only.
     * User2 will complete the url only.
     * User3 will complete the label only.
     * User4 will complete all activities.
     * User5 will complete none.
     * User6 will complete all activities (user4 can have activities archived, while user6 doesn't).
     */
    private function users_complete_modules($course, $facetoface, $session, $url, $label) {
        global $DB;

        $this->data_generator->enrol_user($this->user1->id, $course->id);
        $this->data_generator->enrol_user($this->user2->id, $course->id);
        $this->data_generator->enrol_user($this->user3->id, $course->id);
        $this->data_generator->enrol_user($this->user4->id, $course->id);
        $this->data_generator->enrol_user($this->user5->id, $course->id);
        $this->data_generator->enrol_user($this->user6->id, $course->id);

        // First check no one is yet complete for course and certs.
        $course1info = new completion_info($course);
        $this->assertFalse($course1info->is_course_complete($this->user1->id));
        $this->assertFalse($course1info->is_course_complete($this->user2->id));
        $this->assertFalse($course1info->is_course_complete($this->user3->id));
        $this->assertFalse($course1info->is_course_complete($this->user4->id));
        $this->assertFalse($course1info->is_course_complete($this->user5->id));
        $this->assertFalse($course1info->is_course_complete($this->user6->id));

        // User1, 4 and 6 attend the facetoface.
        facetoface_user_signup($session, $facetoface, $course, NULL, MDL_F2F_INVITE, MDL_F2F_STATUS_BOOKED, $this->user1->id);
        facetoface_user_signup($session, $facetoface, $course, NULL, MDL_F2F_INVITE, MDL_F2F_STATUS_BOOKED, $this->user4->id);
        facetoface_user_signup($session, $facetoface, $course, NULL, MDL_F2F_INVITE, MDL_F2F_STATUS_BOOKED, $this->user6->id);
        $f2fsignups =
            $DB->get_records('facetoface_signups', array('sessionid' => $session->id), '', 'userid, id');
        $attendancedata = new stdClass();
        $attendancedata->s = $session->id;
        $attendancedata->{'submissionid_'.$f2fsignups[$this->user1->id]->id} = MDL_F2F_STATUS_FULLY_ATTENDED;
        $attendancedata->{'submissionid_'.$f2fsignups[$this->user4->id]->id} = MDL_F2F_STATUS_FULLY_ATTENDED;
        $attendancedata->{'submissionid_'.$f2fsignups[$this->user6->id]->id} = MDL_F2F_STATUS_FULLY_ATTENDED;
        facetoface_take_attendance($attendancedata);

        $completion_task = new \core\task\completion_regular_task();
        $completion_task->execute();

        // Checking that only those who attended are marked complete so far.
        $course1info = new completion_info($course);
        $this->assertTrue($course1info->is_course_complete($this->user1->id));
        $this->assertFalse($course1info->is_course_complete($this->user2->id));
        $this->assertFalse($course1info->is_course_complete($this->user3->id));
        $this->assertTrue($course1info->is_course_complete($this->user4->id));
        $this->assertFalse($course1info->is_course_complete($this->user5->id));
        $this->assertTrue($course1info->is_course_complete($this->user6->id));

        // Users 2, 4 and 6 view the url activity.
        $urlmodulerecord = $DB->get_record('course_modules', array('id' => $url->cmid));
        $course1info->set_module_viewed($urlmodulerecord, $this->user2->id);
        $course1info->set_module_viewed($urlmodulerecord, $this->user4->id);
        $course1info->set_module_viewed($urlmodulerecord, $this->user6->id);

        // Users 3, 4 and 6 manually complete the label activity.
        $labelmodulerecord = $DB->get_record('course_modules', array('id' => $label->cmid));
        $course1info->update_state($labelmodulerecord, COMPLETION_COMPLETE, $this->user3->id);
        $course1info->update_state($labelmodulerecord, COMPLETION_COMPLETE, $this->user4->id);
        $course1info->update_state($labelmodulerecord, COMPLETION_COMPLETE, $this->user6->id);

        // All except User5 should be complete now.
        $course1info = new completion_info($course);
        $this->assertTrue($course1info->is_course_complete($this->user1->id));
        $this->assertTrue($course1info->is_course_complete($this->user2->id));
        $this->assertTrue($course1info->is_course_complete($this->user3->id));
        $this->assertTrue($course1info->is_course_complete($this->user4->id));
        $this->assertFalse($course1info->is_course_complete($this->user5->id));
        $this->assertTrue($course1info->is_course_complete($this->user6->id));

        // Let's check the completion states of each module for each user are where we expect them to be.
        $f2fmodulecompletions =
            $DB->get_records('course_modules_completion', array('coursemoduleid' => $facetoface->cmid), '', 'userid, completionstate');
        $this->assertEquals(COMPLETION_COMPLETE, $f2fmodulecompletions[$this->user1->id]->completionstate);
        $this->assertFalse(isset($f2fmodulecompletions[$this->user2->id]));
        $this->assertFalse(isset($f2fmodulecompletions[$this->user3->id]));
        $this->assertEquals(COMPLETION_COMPLETE, $f2fmodulecompletions[$this->user4->id]->completionstate);
        $this->assertFalse(isset($f2fmodulecompletions[$this->user5->id]));
        $this->assertEquals(COMPLETION_COMPLETE, $f2fmodulecompletions[$this->user6->id]->completionstate);

        $urlmodulecompletions =
            $DB->get_records('course_modules_completion', array('coursemoduleid' => $url->cmid), '', 'userid, completionstate, viewed');
        $this->assertFalse(isset($urlmodulecompletions[$this->user1->id]));
        $this->assertEquals(COMPLETION_COMPLETE, $urlmodulecompletions[$this->user2->id]->completionstate);
        $this->assertEquals(COMPLETION_VIEWED, $urlmodulecompletions[$this->user2->id]->viewed);
        $this->assertFalse(isset($urlmodulecompletions[$this->user3->id]));
        $this->assertEquals(COMPLETION_COMPLETE, $urlmodulecompletions[$this->user4->id]->completionstate);
        $this->assertEquals(COMPLETION_VIEWED, $urlmodulecompletions[$this->user4->id]->viewed);
        $this->assertFalse(isset($urlmodulecompletions[$this->user5->id]));
        $this->assertEquals(COMPLETION_COMPLETE, $urlmodulecompletions[$this->user6->id]->completionstate);
        $this->assertEquals(COMPLETION_VIEWED, $urlmodulecompletions[$this->user6->id]->viewed);

        $labelmodulecompletions =
            $DB->get_records('course_modules_completion', array('coursemoduleid' => $label->cmid), '', 'userid, completionstate');
        $this->assertFalse(isset($labelmodulecompletions[$this->user1->id]));
        $this->assertFalse(isset($labelmodulecompletions[$this->user2->id]));
        $this->assertEquals(COMPLETION_COMPLETE, $labelmodulecompletions[$this->user3->id]->completionstate);
        $this->assertEquals(COMPLETION_COMPLETE, $labelmodulecompletions[$this->user4->id]->completionstate);
        $this->assertFalse(isset($labelmodulecompletions[$this->user5->id]));
        $this->assertEquals(COMPLETION_COMPLETE, $labelmodulecompletions[$this->user6->id]->completionstate);
    }

    /**
     * Tests archive_course_activities.
     *
     * We're not using the window param, meaning that it will default to null, and
     * within the function, will be set to now.
     *
     * We also check there's no interference between courses. e.g. if it's run for course1, it
     * shouldn't affect course2.
     */
    public function test_archive_course_activities_nowindowparam() {
        $this->resetAfterTest(true);
        global $DB;

        $course1 = $this->data_generator->create_course();
        $course2 = $this->data_generator->create_course();
        $this->completion_generator->enable_completion_tracking($course1);
        $this->completion_generator->enable_completion_tracking($course2);

        list($facetoface1, $session1, $url1, $label1) = $this->set_up_activities_for_course($course1);
        list($facetoface2, $session2, $url2, $label2) = $this->set_up_activities_for_course($course2);
        $this->users_complete_modules($course1, $facetoface1, $session1, $url1, $label1);
        $this->users_complete_modules($course2, $facetoface2, $session2, $url2, $label2);

        // Run the function for all users in the certification.
        archive_course_activities($this->user1->id, $course1->id);
        archive_course_activities($this->user2->id, $course1->id);
        archive_course_activities($this->user3->id, $course1->id);
        archive_course_activities($this->user4->id, $course1->id);
        archive_course_activities($this->user5->id, $course1->id);

        // Now check all that those that should have been reset were, and those that shouldn't are still in the same state.
        // First of all the modules in course1.
        $f2fmodulecompletions =
            $DB->get_records('course_modules_completion', array('coursemoduleid' => $facetoface1->cmid), '', 'userid, completionstate');
        $this->assertFalse(isset($f2fmodulecompletions[$this->user1->id]));
        $this->assertFalse(isset($f2fmodulecompletions[$this->user2->id]));
        $this->assertFalse(isset($f2fmodulecompletions[$this->user3->id]));
        $this->assertFalse(isset($f2fmodulecompletions[$this->user4->id]));
        $this->assertFalse(isset($f2fmodulecompletions[$this->user5->id]));
        $this->assertEquals(COMPLETION_COMPLETE, $f2fmodulecompletions[$this->user6->id]->completionstate);

        $urlmodulecompletions =
            $DB->get_records('course_modules_completion', array('coursemoduleid' => $url1->cmid), '', 'userid, completionstate, viewed');
        $this->assertFalse(isset($urlmodulecompletions[$this->user1->id]));
        $this->assertFalse(isset($urlmodulecompletions[$this->user2->id]));
        $this->assertFalse(isset($urlmodulecompletions[$this->user3->id]));
        $this->assertFalse(isset($urlmodulecompletions[$this->user4->id]));
        $this->assertFalse(isset($urlmodulecompletions[$this->user5->id]));
        $this->assertEquals(COMPLETION_COMPLETE, $urlmodulecompletions[$this->user6->id]->completionstate);
        $this->assertEquals(COMPLETION_VIEWED, $urlmodulecompletions[$this->user6->id]->viewed);

        $labelmodulecompletions =
            $DB->get_records('course_modules_completion', array('coursemoduleid' => $label1->cmid), '', 'userid, completionstate');
        $this->assertFalse(isset($labelmodulecompletions[$this->user1->id]));
        $this->assertFalse(isset($labelmodulecompletions[$this->user2->id]));
        $this->assertFalse(isset($labelmodulecompletions[$this->user3->id]));
        $this->assertFalse(isset($labelmodulecompletions[$this->user4->id]));
        $this->assertFalse(isset($labelmodulecompletions[$this->user5->id]));
        $this->assertEquals(COMPLETION_COMPLETE, $labelmodulecompletions[$this->user6->id]->completionstate);

        // For course2. The function was not run at all. So these assertions just need
        // to match what is done at the end of users_complete_modules.
        $f2fmodulecompletions =
            $DB->get_records('course_modules_completion', array('coursemoduleid' => $facetoface2->cmid), '', 'userid, completionstate');
        $this->assertEquals(COMPLETION_COMPLETE, $f2fmodulecompletions[$this->user1->id]->completionstate);
        $this->assertFalse(isset($f2fmodulecompletions[$this->user2->id]));
        $this->assertFalse(isset($f2fmodulecompletions[$this->user3->id]));
        $this->assertEquals(COMPLETION_COMPLETE, $f2fmodulecompletions[$this->user4->id]->completionstate);
        $this->assertFalse(isset($f2fmodulecompletions[$this->user5->id]));
        $this->assertEquals(COMPLETION_COMPLETE, $f2fmodulecompletions[$this->user6->id]->completionstate);

        $urlmodulecompletions =
            $DB->get_records('course_modules_completion', array('coursemoduleid' => $url2->cmid), '', 'userid, completionstate, viewed');
        $this->assertFalse(isset($urlmodulecompletions[$this->user1->id]));
        $this->assertEquals(COMPLETION_COMPLETE, $urlmodulecompletions[$this->user2->id]->completionstate);
        $this->assertEquals(COMPLETION_VIEWED, $urlmodulecompletions[$this->user2->id]->viewed);
        $this->assertFalse(isset($urlmodulecompletions[$this->user3->id]));
        $this->assertEquals(COMPLETION_COMPLETE, $urlmodulecompletions[$this->user4->id]->completionstate);
        $this->assertEquals(COMPLETION_VIEWED, $urlmodulecompletions[$this->user4->id]->viewed);
        $this->assertFalse(isset($urlmodulecompletions[$this->user5->id]));
        $this->assertEquals(COMPLETION_COMPLETE, $urlmodulecompletions[$this->user6->id]->completionstate);
        $this->assertEquals(COMPLETION_VIEWED, $urlmodulecompletions[$this->user6->id]->viewed);

        $labelmodulecompletions =
            $DB->get_records('course_modules_completion', array('coursemoduleid' => $label2->cmid), '', 'userid, completionstate');
        $this->assertFalse(isset($labelmodulecompletions[$this->user1->id]));
        $this->assertFalse(isset($labelmodulecompletions[$this->user2->id]));
        $this->assertEquals(COMPLETION_COMPLETE, $labelmodulecompletions[$this->user3->id]->completionstate);
        $this->assertEquals(COMPLETION_COMPLETE, $labelmodulecompletions[$this->user4->id]->completionstate);
        $this->assertFalse(isset($labelmodulecompletions[$this->user5->id]));
        $this->assertEquals(COMPLETION_COMPLETE, $labelmodulecompletions[$this->user6->id]->completionstate);
    }

    /**
     * Tests archive_course_activities.
     *
     * We'll set the window open param to various times here and check we get
     * appropriate results.
     *
     * -- Resetting module despite the completion time --
     * Currently, the windowopen time is only taken into account when running the _archive_completion
     * for modules that support FEATURE_ARCHIVE_COMPLETION.  It is not taken into account for others.
     * For example, a user may complete an activity after their window open time, but before cron has run.
     * That activity would then be reset. This may be intended behaviour or at least not worth
     * complicating the code in order to fix. The windowopen param appears to have been intended to
     * deal with facetoface booking times in the future rather than issues such as these.
     */
    public function test_archive_course_activities_setwindowparam() {
        $this->resetAfterTest(true);
        global $DB;

        $course1 = $this->data_generator->create_course();
        $this->completion_generator->enable_completion_tracking($course1);

        list($facetoface1, $session1, $url1, $label1) = $this->set_up_activities_for_course($course1);
        $this->users_complete_modules($course1, $facetoface1, $session1, $url1, $label1);

        $twodaysago = time() - DAYSECS * 2;
        $onehourago = time() - HOURSECS;
        $onedayinfuture = time() + DAYSECS;
        // Run the function for all users in the certification.
        archive_course_activities($this->user1->id, $course1->id, $twodaysago);
        archive_course_activities($this->user2->id, $course1->id, $onedayinfuture);
        archive_course_activities($this->user3->id, $course1->id, $onehourago);
        archive_course_activities($this->user4->id, $course1->id, $onehourago);
        archive_course_activities($this->user5->id, $course1->id, $onedayinfuture);

        // Now check all that those that should have been reset were, and those that shouldn't are still in the same state.
        // First of all the modules in course1.
        $f2fmodulecompletions =
            $DB->get_records('course_modules_completion', array('coursemoduleid' => $facetoface1->cmid), '', 'userid, completionstate');
        // User1's window was before the f2f, so it shouldn't have been reset.
        $this->assertEquals(COMPLETION_COMPLETE, $f2fmodulecompletions[$this->user1->id]->completionstate);
        $this->assertFalse(isset($f2fmodulecompletions[$this->user2->id]));
        $this->assertFalse(isset($f2fmodulecompletions[$this->user3->id]));
        // User4's window was in the past but will have definitely been after the f2f. It should have been reset.
        $this->assertFalse(isset($f2fmodulecompletions[$this->user4->id]));
        $this->assertFalse(isset($f2fmodulecompletions[$this->user5->id]));
        $this->assertEquals(COMPLETION_COMPLETE, $f2fmodulecompletions[$this->user6->id]->completionstate);

        $urlmodulecompletions =
            $DB->get_records('course_modules_completion', array('coursemoduleid' => $url1->cmid), '', 'userid, completionstate, viewed');
        $this->assertFalse(isset($urlmodulecompletions[$this->user1->id]));
        $this->assertFalse(isset($urlmodulecompletions[$this->user2->id]));
        $this->assertFalse(isset($urlmodulecompletions[$this->user3->id]));
        // User4's modules are reset even though the window open is before they were completed. See docs for this test.
        $this->assertFalse(isset($urlmodulecompletions[$this->user4->id]));
        $this->assertFalse(isset($urlmodulecompletions[$this->user5->id]));
        $this->assertEquals(COMPLETION_COMPLETE, $urlmodulecompletions[$this->user6->id]->completionstate);
        $this->assertEquals(COMPLETION_VIEWED, $urlmodulecompletions[$this->user6->id]->viewed);

        $labelmodulecompletions =
            $DB->get_records('course_modules_completion', array('coursemoduleid' => $label1->cmid), '', 'userid, completionstate');
        $this->assertFalse(isset($labelmodulecompletions[$this->user1->id]));
        $this->assertFalse(isset($labelmodulecompletions[$this->user2->id]));
        // User3's modules are reset even though the window open is before they were completed. See docs for this test.
        $this->assertFalse(isset($labelmodulecompletions[$this->user3->id]));
        // User4's modules are reset even though the window open is before they were completed. See docs for this test.
        $this->assertFalse(isset($labelmodulecompletions[$this->user4->id]));
        $this->assertFalse(isset($labelmodulecompletions[$this->user5->id]));
        $this->assertEquals(COMPLETION_COMPLETE, $labelmodulecompletions[$this->user6->id]->completionstate);
    }

    /**
     * Tests archive_course_activities.
     *
     * There was an issue where modules were not being reset if they were hidden. This test is to
     * catch that in case it happens again.
     */
    public function test_archive_course_activities_hidden() {
        $this->resetAfterTest(true);
        global $DB;

        $course1 = $this->data_generator->create_course();
        $this->completion_generator->enable_completion_tracking($course1);

        list($facetoface1, $session1, $url1, $label1) = $this->set_up_activities_for_course($course1);
        $this->users_complete_modules($course1, $facetoface1, $session1, $url1, $label1);

        // Now set the modules to hidden.
        $f2fmodulerecord = $DB->get_record('course_modules', array('id' => $facetoface1->cmid));
        $f2fmodulerecord->visible = 0;
        $f2fmodulerecord->visibleold = 0;
        $DB->update_record('course_modules', $f2fmodulerecord);
        $urlmodulerecord = $DB->get_record('course_modules', array('id' => $url1->cmid));
        $urlmodulerecord->visible = 0;
        $urlmodulerecord->visibleold = 0;
        $DB->update_record('course_modules', $urlmodulerecord);
        $labelmodulerecord = $DB->get_record('course_modules', array('id' => $label1->cmid));
        $labelmodulerecord->visible = 0;
        $labelmodulerecord->visibleold = 0;
        $DB->update_record('course_modules', $labelmodulerecord);

        // Run the function for all users in the certification.
        archive_course_activities($this->user1->id, $course1->id);
        archive_course_activities($this->user2->id, $course1->id);
        archive_course_activities($this->user3->id, $course1->id);
        archive_course_activities($this->user4->id, $course1->id);
        archive_course_activities($this->user5->id, $course1->id);

        // Now check all that those that should have been reset were, and those that shouldn't are still in the same state.
        $f2fmodulecompletions =
            $DB->get_records('course_modules_completion', array('coursemoduleid' => $facetoface1->cmid), '', 'userid, completionstate');
        $this->assertFalse(isset($f2fmodulecompletions[$this->user1->id]));
        $this->assertFalse(isset($f2fmodulecompletions[$this->user2->id]));
        $this->assertFalse(isset($f2fmodulecompletions[$this->user3->id]));
        $this->assertFalse(isset($f2fmodulecompletions[$this->user4->id]));
        $this->assertFalse(isset($f2fmodulecompletions[$this->user5->id]));
        $this->assertEquals(COMPLETION_COMPLETE, $f2fmodulecompletions[$this->user6->id]->completionstate);

        $urlmodulecompletions =
            $DB->get_records('course_modules_completion', array('coursemoduleid' => $url1->cmid), '', 'userid, completionstate, viewed');
        $this->assertFalse(isset($urlmodulecompletions[$this->user1->id]));
        $this->assertFalse(isset($urlmodulecompletions[$this->user2->id]));
        $this->assertFalse(isset($urlmodulecompletions[$this->user3->id]));
        $this->assertFalse(isset($urlmodulecompletions[$this->user4->id]));
        $this->assertFalse(isset($urlmodulecompletions[$this->user5->id]));
        $this->assertEquals(COMPLETION_COMPLETE, $urlmodulecompletions[$this->user6->id]->completionstate);
        $this->assertEquals(COMPLETION_VIEWED, $urlmodulecompletions[$this->user6->id]->viewed);

        $labelmodulecompletions =
            $DB->get_records('course_modules_completion', array('coursemoduleid' => $label1->cmid), '', 'userid, completionstate');
        $this->assertFalse(isset($labelmodulecompletions[$this->user1->id]));
        $this->assertFalse(isset($labelmodulecompletions[$this->user2->id]));
        $this->assertFalse(isset($labelmodulecompletions[$this->user3->id]));
        $this->assertFalse(isset($labelmodulecompletions[$this->user4->id]));
        $this->assertFalse(isset($labelmodulecompletions[$this->user5->id]));
        $this->assertEquals(COMPLETION_COMPLETE, $labelmodulecompletions[$this->user6->id]->completionstate);
    }
}
