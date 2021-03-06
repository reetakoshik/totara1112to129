<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2010 onwards Totara Learning Solutions LTD
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
 * Tests importing generated from a csv file
 *
 * To test, run this from the command line from the $CFG->dirroot
 * vendor/bin/phpunit completion/tests/course_completion_test.php
 *
 * @package    completion
 * @subpackage tests
 * @author     Maria Torres <maria.torres@totaralms.com>
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/completion/cron.php');
require_once($CFG->dirroot . '/mod/certificate/locallib.php');
require_once($CFG->dirroot . '/totara/reportbuilder/tests/reportcache_advanced_testcase.php');
require_once($CFG->dirroot . '/completion/criteria/completion_criteria_activity.php');

define('COMPLETION_TEST_COURSES_CREATED', 3);

class core_completion_course_completion_testcase extends reportcache_advanced_testcase {

    /** This setUp will create: three users (user1, user2, user3), three courses (course1, course2, course3),
     *  one certification program with course1 as certification content path and course2 as re-certification path.
     *  Each course will have a certification activity which will be used as a criterion for completion.
     *  The enrollments will be as follow:
     *  user1 will be enrolled to course1 and course2 via certification program and course3 via manual,
     *  user2 will be enrolled to course1 and course2 via certification program and
     *  user3 will be enrolled to the course1 and course3 via manual.
     */
    protected function setUp() {
        global $CFG, $DB;
        parent::setup();

        $this->resetAfterTest();
        $CFG->enablecompletion = true;

        $this->assertEquals(2, $DB->count_records('user')); // Guest + Admin

        // Create three users.
        $this->user1 = $this->getDataGenerator()->create_user();
        $this->user2 = $this->getDataGenerator()->create_user();
        $this->user3 = $this->getDataGenerator()->create_user();
        $this->users = array($this->user1, $this->user2, $this->user3);

        // Verify users were created.
        $this->assertEquals(5, $DB->count_records('user')); // Guest + Admin + these users.

        // Set default settings for courses.
        $coursedefaults = array(
            'enablecompletion' => COMPLETION_ENABLED,
            'completionstartonenrol' => 1,
            'completionprogressonview' => 1);

        // Create three courses
        for ($i = 1; $i <= COMPLETION_TEST_COURSES_CREATED; $i++) {
            $this->{"course".$i} = $this->getDataGenerator()->create_course($coursedefaults, array('createsections' => true));
            $this->{"completioninfo".$i} = new completion_info($this->{"course".$i});
            $this->assertEquals(COMPLETION_ENABLED, $this->{"completioninfo".$i}->is_enabled());
        }

        // Courses to complete.
        $this->coursestocomplete = array($this->course1->id, $this->course3->id);

        // Verify there isn't any certificate activity.
        $this->assertEquals(0, $DB->count_records('certificate'));

        // Assign a certificate activity to each course. Could be any other activity. It's necessary for the criteria completion.
        $completiondefaults = array(
            'completion' => COMPLETION_TRACKING_AUTOMATIC,
            'completionview' => COMPLETION_VIEW_REQUIRED
        );
        for ($i = 1; $i <= COMPLETION_TEST_COURSES_CREATED; $i++) {
            $courseid = $this->{"course".$i}->id;
            $this->{"certificate".$i} = $this->getDataGenerator()->create_module(
                'certificate',
                array('course' => $courseid),
                $completiondefaults
            );
            $this->{"coursemodule".$i} = get_coursemodule_from_instance('certificate', $this->{"certificate".$i}->id, $courseid);
            $this->assertEquals(COMPLETION_TRACKING_AUTOMATIC, $this->{"completioninfo".$i}->is_enabled($this->{"coursemodule".$i}));
        }
        $this->assertEquals(3, $DB->count_records('certificate'));

        // Create completion based on the certificate activity that each course has.
        for ($i = 1; $i <= COMPLETION_TEST_COURSES_CREATED; $i++) {
            $courseid = $this->{"course".$i}->id;
            $activityid = $this->{"certificate".$i}->id;
            $data = new stdClass();
            $data->course = $courseid;
            $data->id = $activityid;
            $data->overall_aggregation = COMPLETION_AGGREGATION_ANY;
            $data->criteria_activity_value = array($activityid => 1);
            $criterion = new completion_criteria_activity();
            $criterion->update_config($data);
        }

        // Create a certification program.
        $data = array(
            'cert_learningcomptype' => CERTIFTYPE_PROGRAM,
            'cert_activeperiod' => '3 day',
            'cert_windowperiod' => '3 day',
            'cert_recertifydatetype' => CERTIFRECERT_EXPIRY,
            'cert_timemodified' => time(),
            'prog_fullname' => 'Certification Program1',
            'prog_shortname' => 'CP1',
        );
        $this->assertEquals(0, $DB->count_records('certif'), "Certif table isn't empty");
        $this->assertEquals(0, $DB->count_records('prog'), "Prog table isn't empty");
        $this->program = $this->getDataGenerator()->create_certification($data);
        $this->assertEquals(1, $DB->count_records('certif'),'Record count mismatch for certif');
        $this->assertEquals(1, $DB->count_records('prog'), "Record count mismatch for prog");

        // Add course1 and course2 as part of the certification's content.
        $this->getDataGenerator()->add_courseset_program($this->program->id, array($this->course1->id), CERTIFPATH_CERT);
        $this->getDataGenerator()->add_courseset_program($this->program->id, array($this->course2->id), CERTIFPATH_RECERT);
        $this->assertEquals(2, $DB->count_records('prog_courseset_course'), 'Record count mismatch for coursetsets in certification');

        $sink = $this->redirectMessages();
        // Enrol user1 and user2 to the certification program.
        $this->getDataGenerator()->assign_program($this->program->id, array($this->user1->id, $this->user2->id));
        $sink->close();

        // Enrol user1, user2 and user3 to the course1 ... and user1 and user3 to course3 (via manual).
        $this->getDataGenerator()->enrol_user($this->user1->id, $this->course1->id);
        $this->getDataGenerator()->enrol_user($this->user2->id, $this->course1->id);
        $this->getDataGenerator()->enrol_user($this->user3->id, $this->course1->id);
        $this->getDataGenerator()->enrol_user($this->user1->id, $this->course3->id);
        $this->getDataGenerator()->enrol_user($this->user3->id, $this->course3->id);
        $this->assertEquals(5, $DB->count_records('user_enrolments'), 'Record count mismatch for enrollments');
    }

    /** This function will make users to complete the courses via criteria completion by viewing the certificate activity,
     *  make the user1 to complete the certification program one day before today and run the certification_cron to open
     *  the re-certification window. So, we can test that criteria completion records are deleted (when the cron runs)
     *  for the users who complete courses in the certification program path and not for all users enrolled in that course.
     */
    public function test_course_completion() {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        // Check there isn't data in course_completion_crit_compl.
        $this->assertEquals(0, $DB->count_records('course_completion_crit_compl'),'Record count mismatch for completion');

        // Make all users complete courses by viewing the certifications.
        $this->assertEquals(0, $DB->count_records('certificate_issues'));
        for ($i = 1; $i <= COMPLETION_TEST_COURSES_CREATED; $i++) {
            $courseid = $this->{"course".$i}->id;
            // Verify the course is in the courses group we want to complete.
            if (!in_array($courseid, $this->coursestocomplete)) {
                continue;
            }

            $coursecontext = context_course::instance($courseid);
            foreach ($this->users as $user) {
                if (!is_enrolled($coursecontext, $user->id)) {
                    continue;
                }
                // Create a certificate for the user - this replicates a user going to mod/certificate/view.php.
                certificate_get_issue($this->{"course".$i}, $user, $this->{"certificate".$i}, $this->{"coursemodule".$i});
                $params = array('userid' => $user->id, 'coursemoduleid' => $this->{"coursemodule".$i}->id);

                // Check it isn't complete.
                $completionstate = $DB->get_field('course_modules_completion', 'completionstate', $params);
                $this->assertEmpty($completionstate);

                // Complete the certificate.
                $this->{"completioninfo".$i}->set_module_viewed($this->{"coursemodule".$i}, $user->id);

                // Check its completed.
                $completionstate = $DB->get_field('course_modules_completion', 'completionstate', $params, MUST_EXIST);
                $this->assertEquals(COMPLETION_COMPLETE, $completionstate);

                // Call function to complete the activities for the courses.
                $params = array(
                    'userid'     => $user->id,
                    'course'     => $courseid,
                    'criteriaid' => COMPLETION_CRITERIA_TYPE_ACTIVITY
                );
                $completion = new completion_criteria_completion($params);
                $completion->mark_complete();
            }
        }
        // Check records in course_completion_crit_compl.
        $this->assertEquals(5, $DB->count_records('course_completion_crit_compl'), 'Record count mismatch for crit_compl');

        // Make user1 to complete the certification with completion date 1 day before today.
        $paramscompletion = array('userid' => $this->user1->id, 'course' => $this->course1->id);
        $completion_completion = new completion_completion($paramscompletion);
        $completion_completion->mark_complete(time() - DAYSECS);

        // Verify timecomplete for the certification is not null.
        $certification = $DB->get_record('certif_completion', array('certifid' => $this->program->certifid, 'userid' => $this->user1->id));
        $this->assertNotNull($certification->timecompleted, 'Time completed is NULL');

        // Run the cron.
        ob_start();
        $certcron = new \totara_certification\task\update_certification_task();
        $certcron->execute();
        ob_end_clean();

        /* As the re-certification windows is opened, the completion record for user1-course1 should be deleted
         * because it is part of the certification program and user1 already complete course1.
         * So we should see completion records just for user2-course1, user1-course3, user3-course3 and user3-course1. */
        $this->assertEquals(4, $DB->count_records('course_completion_crit_compl'));
        $completions = array(
            $this->user1->id => $this->course3->id,
            $this->user2->id => $this->course1->id,
            $this->user3->id => $this->course3->id,
            $this->user3->id => $this->course1->id
        );

        foreach ($completions as $key => $value) {
            $conditions = array('userid' => $key, 'course' => $value);
            $this->assertTrue($DB->record_exists('course_completions', $conditions));
            $this->assertTrue($DB->record_exists('course_completion_crit_compl', $conditions));
        }
    }

    /** This function will test the delete_course_completion_data function should behave as follow:
     *  No records that were mark via rpl should be deleted when the function is called without parameter userid.
     *  If userid is passed it should delete only records related to the course-userid.
     */
    public function test_delete_course_completion_data() {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        // Case #1: Activity completion made by users.
        // Make users to complete course1 via criteria completion.
        $this->assertEquals(0, $DB->count_records('course_completion_crit_compl'), 'Record count mismatch for completion');
        $this->assertEquals(0, $DB->count_records('certificate_issues'));
        $course = $this->course1;
        foreach ($this->users as $user) {
            // Create a certificate for the user - this replicates a user going to mod/certificate/view.php.
            certificate_get_issue($course, $user, $this->certificate1, $this->coursemodule1);
            $params = array('userid' => $user->id, 'coursemoduleid' => $this->coursemodule1->id);

            // Check it isn't complete.
            $completionstate = $DB->get_field('course_modules_completion', 'completionstate', $params);
            $this->assertEmpty($completionstate);

            // Complete the certificate.
            $this->completioninfo1->set_module_viewed($this->coursemodule1, $user->id);

            // Check its completed.
            $completionstate = $DB->get_field('course_modules_completion', 'completionstate', $params, MUST_EXIST);
            $this->assertEquals(COMPLETION_COMPLETE, $completionstate);

            // Call function to complete the activities for the courses.
            $params = array(
                'userid'     => $user->id,
                'course'     => $course->id,
                'criteriaid' => COMPLETION_CRITERIA_TYPE_ACTIVITY
            );
            $completion = new completion_criteria_completion($params);
            $completion->mark_complete();
        }
        // Check records in course_completion_crit_compl.
        $this->assertEquals(3, $DB->count_records('course_completion_crit_compl'));
        $this->assertEquals(5, $DB->count_records('course_completions'));

        // Call delete_course_completion_data for course1.
        $completion = new completion_info($course);
        $completion->delete_course_completion_data();

        // There shouldn't be records for course1, because it was not completed via RPL.
        $this->assertEquals(0, $DB->count_records('course_completions', array('course' => $this->course1->id)));
        $this->assertEquals(0, $DB->count_records('course_completion_crit_compl', array('course' => $this->course1->id)));
        // But should exists two records for course3.
        $this->assertEquals(2, $DB->count_records('course_completions', array('course' => $this->course3->id)));

        // Case #2: Course completion made via RPL.
        // Now lets complete course3 for user1 and user3 via RPL.
        $userstocomplete = array($this->user1, $this->user3);
        foreach ($userstocomplete as $user) {
            $completionrpl = new completion_completion(array('userid' => $user->id, 'course' => $this->course3->id));
            $completionrpl->rpl = 'Course completed via rpl';
            $completionrpl->status = COMPLETION_STATUS_COMPLETEVIARPL;
            $completionrpl->mark_complete();
        }

        // Verify course3 has been marked as completed for user1 and user3.
        $completion = new completion_info($this->course3);
        $this->assertTrue($completion->is_course_complete($this->user1->id));
        $this->assertTrue($completion->is_course_complete($this->user3->id));
        $this->assertEquals(0, $DB->count_records('course_completion_crit_compl', array('course' => $this->course3->id)));

        // Delete completion for course3.
        $completion->delete_course_completion_data();
        // TOTARA: As it was completed via RPL no records should be deleted in the course_completions table.
        $this->assertEquals(2, $DB->count_records('course_completions'));

        // Case #3: Activity completion made via RPL.
        // Let's complete activities via RPL for all users in course1.
        foreach ($this->users as $user) {
            $completionrpl = new completion_criteria_completion(array(
                'userid' => $user->id,
                'course' => $this->course1->id,
                'criteriaid' => COMPLETION_CRITERIA_TYPE_ACTIVITY
            ));
            $completionrpl->rpl = 'Activity completed via RPL';
            $completionrpl->mark_complete();
        }

        // Ensure Course1 was not completed via RPL.
        $params = array($this->course1->id, COMPLETION_STATUS_COMPLETEVIARPL);
        $this->assertEquals(3, $DB->count_records_select('course_completions', 'course = ? AND status != ?', $params));
        // Check records before calling to delete_course_completion_data function.
        $this->assertEquals(3, $DB->count_records('course_completion_crit_compl', array('course' => $this->course1->id)));

        // Delete completion for course1.
        $completion = new completion_info($this->course1);
        $completion->delete_course_completion_data();
        // Because the activity was completed via RPL the completion records should be intact.
        $this->assertEquals(3, $DB->count_records('course_completion_crit_compl', array('course' => $this->course1->id)));
        // Course completions for the course should be deleted as they weren't completed via RPL.
        $this->assertEquals(0, $DB->count_records('course_completions', array('course' => $this->course1->id)));
    }

    /** This function will test the delete_course_completion_data function with a userid should behave as follow:
     *  All course completion records (including those marked via RPL) for the user given should be deleted
     *  when this function is called
     */
    public function test_delete_course_completion_data_with_userid() {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        // Case #1: Activity completion made by users.
        // Make users to complete course1 via criteria completion.
        $this->assertEquals(0, $DB->count_records('course_completion_crit_compl'), 'Record count mismatch for completion');
        $this->assertEquals(0, $DB->count_records('certificate_issues'));
        $course = $this->course1;
        foreach ($this->users as $user) {
            // Create a certificate for the user - this replicates a user going to mod/certificate/view.php.
            certificate_get_issue($course, $user, $this->certificate1, $this->coursemodule1);
            $params = array('userid' => $user->id, 'coursemoduleid' => $this->coursemodule1->id);

            // Check it isn't complete.
            $completionstate = $DB->get_field('course_modules_completion', 'completionstate', $params);
            $this->assertEmpty($completionstate);

            // Complete the certificate.
            $this->completioninfo1->set_module_viewed($this->coursemodule1, $user->id);

            // Check its completed.
            $completionstate = $DB->get_field('course_modules_completion', 'completionstate', $params, MUST_EXIST);
            $this->assertEquals(COMPLETION_COMPLETE, $completionstate);

            // Call function to complete the activities for the courses.
            $params = array(
                'userid'     => $user->id,
                'course'     => $course->id,
                'criteriaid' => COMPLETION_CRITERIA_TYPE_ACTIVITY
            );
            $completion = new completion_criteria_completion($params);
            $completion->mark_complete();
        }
        // Check records in course_completion_crit_compl.
        $this->assertEquals(3, $DB->count_records('course_completion_crit_compl'));

        // Call delete_course_completion_data with user1 id.
        $completion = new completion_info($course);
        $completion->delete_course_completion_data($this->user1->id);

        $this->assertEquals(4, $DB->count_records('course_completions'));
        // Now should be two records in completions. One for user2 and other for user3 in course1.
        $this->assertEquals(2, $DB->count_records('course_completion_crit_compl'));
        $conditions = array('userid' => $this->user1->id, 'course' => $course->id);
        $this->assertFalse($DB->record_exists('course_completions', $conditions));
        $this->assertFalse($DB->record_exists('course_completion_crit_compl', $conditions));
        $conditions['userid'] = $this->user2->id;
        $this->assertTrue($DB->record_exists('course_completions', $conditions));
        $this->assertTrue($DB->record_exists('course_completion_crit_compl', $conditions));
        $conditions['userid'] = $this->user3->id;
        $this->assertTrue($DB->record_exists('course_completions', $conditions));
        $this->assertTrue($DB->record_exists('course_completion_crit_compl', $conditions));

        // Case #2: Course completion made via RPL.
        // Now lets complete course3 for user1 and user3 via RPL.
        $userstocomplete = array($this->user1, $this->user3);
        foreach ($userstocomplete as $user) {
            $completionrpl = new completion_completion(array('userid' => $user->id, 'course' => $this->course3->id));
            $completionrpl->rpl = 'Course completed via RPL';
            $completionrpl->status = COMPLETION_STATUS_COMPLETEVIARPL;
            $completionrpl->mark_complete();
        }

        // Verify course3 has been marked as completed for user1 and user3.
        $completion = new completion_info($this->course3);
        $this->assertTrue($completion->is_course_complete($this->user1->id));
        $this->assertTrue($completion->is_course_complete($this->user3->id));
        $this->assertEquals(4, $DB->count_records('course_completions'));
        $this->assertEquals(0, $DB->count_records('course_completion_crit_compl', array('course' => $this->course3->id)));

        // Delete completion records for course3-user1.
        // Changes should be reflected in course_completions table.
        $completion->delete_course_completion_data($this->user1->id);
        $this->assertEquals(3, $DB->count_records('course_completions'));
        $this->assertEquals(0, $DB->count_records('course_completion_crit_compl', array('course' => $this->course3->id)));

        // Case #3: Activity completion made via RPL.
        // Let's complete activities via RPL for user1 and user3 in course3.
        foreach ($userstocomplete as $user) {
            $completionrpl = new completion_criteria_completion(array(
                'userid' => $user->id,
                'course' => $this->course3->id,
                'criteriaid' => COMPLETION_CRITERIA_TYPE_ACTIVITY
            ));
            $completionrpl->rpl = 'Activity completed via RPL';
            $completionrpl->mark_complete();
        }

        // Check records in course_completion_crit_compl before deleting.
        $this->assertEquals(2, $DB->count_records('course_completion_crit_compl', array('course' => $this->course3->id)));

        // Delete completion for user1-course3.
        // Note that it doesn't matter that the user completed the activity via RPl, the activity completion is deleted.
        $completion = new completion_info($this->course3);
        $completion->delete_course_completion_data($this->user1->id);
        $this->assertEquals(1, $DB->count_records('course_completion_crit_compl', array('course' => $this->course3->id)));
        $this->assertEquals(3, $DB->count_records('course_completions'));
        $conditions = array('userid' => $this->user1->id, 'course' => $this->course3->id);
        $this->assertFalse($DB->record_exists('course_completions', $conditions));
        $this->assertFalse($DB->record_exists('course_completion_crit_compl', $conditions));
    }
}
