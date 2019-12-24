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
 * @package completion
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.'); // It must be included from a Moodle page.
}

global $CFG;
require_once($CFG->dirroot . '/completion/criteria/completion_criteria.php');
require_once($CFG->dirroot . '/completion/criteria/completion_criteria_course.php');

class completion_completion_criteria_course_test extends advanced_testcase {

    /** @var  testing_data_generator $data_generator */
    protected $generator;

    /** @var core_completion_generator $completion_generator */
    protected $completion_generator;

    protected function tearDown() {
        $this->generator = null;
        $this->completion_generator = null;
        parent::tearDown();
    }

    protected function setUp() {
        parent::setup();
        $this->resetAfterTest();

        set_config('enablecompletion', '1');

        $this->generator = $this->getDataGenerator();
        $this->completion_generator = $this->getDataGenerator()->get_plugin_generator('core_completion');
    }

    /**
     * Tests the method completion_criteria_course->review.
     *
     * In this case, course1 is the only criteria for completing course2.
     * We subsequently set course1 to complete and also make sure the
     * completion record generated after the call to review contains the
     * correct completion time.
     */
    public function test_completion_criteria_course_review_complete() {
        $this->resetAfterTest(true);
        global $DB;

        $course1 = $this->generator->create_course();
        $this->completion_generator->enable_completion_tracking($course1);
        // We won't enable completion tracking for course2 yet, otherwise the user is completed on enrolment
        // and we want to test the review method.
        $course2 = $this->generator->create_course();

        $user1 = $this->generator->create_user();

        $testcompletiontime = 1000000;

        // Completion of course1 is the criteria for completion of course2.
        $this->completion_generator->set_course_criteria_course_completion($course2,
            array($course1->id), COMPLETION_AGGREGATION_ALL);

        $this->generator->enrol_user($user1->id, $course1->id);

        // Ensure that course1 is not complete yet.
        $course1info = new completion_info($course1);
        $this->assertFalse($course1info->is_course_complete($user1->id));

        // We'll mark user1 complete for course 1 for a specific time in the past.
        $this->completion_generator->complete_course($course1, $user1, $testcompletiontime);

        // Now we can enrol the user enable completion in course2.
        $this->generator->enrol_user($user1->id, $course2->id);
        $this->completion_generator->enable_completion_tracking($course2);

        // This is where we set up the objects necessary for testing the review method.
        $completion_criteria_course = new completion_criteria_course(array(
            'course' => $course2->id,
            'criteriatype' => COMPLETION_CRITERIA_TYPE_COURSE,
            'courseinstance' => $course1->id
        ));
        $course2completion = new completion_criteria_completion(array(
            'course' => $course2->id,
            'userid' => $user1->id,
            'criteriaid' => $completion_criteria_course->id
        ));

        // Ensure that course1 is complete but course 2 isn't yet.
        $course1info = new completion_info($course1);
        $this->assertTrue($course1info->is_course_complete($user1->id));
        $course2info = new completion_info($course2);
        $this->assertFalse($course2info->is_course_complete($user1->id));

        // Now test the review method.
        $completion_criteria_course->review($course2completion);

        // Ensure that course1 is still complete.
        $course1info = new completion_info($course1);
        $this->assertTrue($course1info->is_course_complete($user1->id));
        // Course 2 is now complete as well.
        $course2info = new completion_info($course2);
        $this->assertTrue($course2info->is_course_complete($user1->id));
        // Check that the record for the course2 has the same completion time as course1.
        $coursecompletionrecord = $DB->get_record('course_completions',
            array('course' => $course2->id, 'userid' => $user1->id), 'timecompleted', MUST_EXIST);
        $this->assertEquals($testcompletiontime, $coursecompletionrecord->timecompleted);
    }

    /**
     * Tests the method completion_criteria_course->review.
     *
     * In this case, course1 is the only criteria for completing course2.
     * However, we don't complete course1 in this test. This means that
     * course2 should not be completed after the call to review.
     */
    public function test_completion_criteria_course_review_incomplete() {
        $this->resetAfterTest(true);

        $course1 = $this->generator->create_course();
        $this->completion_generator->enable_completion_tracking($course1);
        $course2 = $this->generator->create_course();
        $this->completion_generator->enable_completion_tracking($course2);
        $user1 = $this->generator->create_user();

        // Set up completion of course 1 as a completion criteria for course 2.
        $this->completion_generator->set_course_criteria_course_completion($course2,
            array($course1->id), COMPLETION_AGGREGATION_ALL);

        // Enrol user1 in both courses.
        $this->generator->enrol_user($user1->id, $course1->id);
        $this->generator->enrol_user($user1->id, $course2->id);

        // Set up the objects necessary for testing the review method.
        $completion_criteria_course = new completion_criteria_course(array(
            'course' => $course2->id,
            'criteriatype' => COMPLETION_CRITERIA_TYPE_COURSE,
            'courseinstance' => $course1->id
        ));
        $course2completion = new completion_criteria_completion(array(
            'course' => $course2->id,
            'userid' => $user1->id,
            'criteriaid' => $completion_criteria_course->id
        ));

        // Ensure that neither course is complete yet. We haven't used mark_complete on course1 at all.
        $course1info = new completion_info($course1);
        $this->assertFalse($course1info->is_course_complete($user1->id));
        $course2info = new completion_info($course2);
        $this->assertFalse($course2info->is_course_complete($user1->id));

        // Now run the review method.
        $completion_criteria_course->review($course2completion);

        // Still, neither course should be complete. In particular, we're making sure that
        // course2 hasn't been incorrectly marked as complete after the call to review above.
        $course1info = new completion_info($course1);
        $this->assertFalse($course1info->is_course_complete($user1->id));
        $course2info = new completion_info($course2);
        $this->assertFalse($course2info->is_course_complete($user1->id));
    }

    /**
     * Tests the method completion_criteria_course->review.
     *
     * There are multiple courses to complete. One user completes all necessary courses
     * and one completes just one. Only the user who completes all
     * (when the aggregation method is set to all), should then be complete for the other course.
     */
    public function test_completion_criteria_course_review_multiple() {
        $this->resetAfterTest(true);
        global $DB;

        $course1a = $this->generator->create_course(array('enablecompletion' => 1));
        $course1b = $this->generator->create_course(array('enablecompletion' => 1));
        $course2 = $this->generator->create_course(array('enablecompletion' => 1));

        $user1 = $this->generator->create_user();
        $user2 = $this->generator->create_user();

        $testcompletiontime_later = 2000000;
        $testcompletiontime_early = 1000000;

        // Set up completion of course 1a and course 1b as a completion criteria for course 2.
        $this->completion_generator->set_course_criteria_course_completion($course2,
            array($course1a->id, $course1b->id), COMPLETION_AGGREGATION_ALL);

        // Enrol in first course only at this point, as we don't want to trigger
        // instant completion of course2 when course1 is completed. Instead we
        // want to specifically test the review function.
        $this->generator->enrol_user($user1->id, $course1a->id);
        $this->generator->enrol_user($user2->id, $course1a->id);

        // Ensure that course1 is not complete yet.
        $course1ainfo = new completion_info($course1a);
        $this->assertFalse($course1ainfo->is_course_complete($user1->id));
        $this->assertFalse($course1ainfo->is_course_complete($user2->id));

        // We'll mark user1 complete for course 1 for a specific time in the past.
        $this->completion_generator->complete_course($course1a, $user1, $testcompletiontime_later);

        // Now we can enrol in course2.
        $this->generator->enrol_user($user1->id, $course2->id);
        $this->generator->enrol_user($user2->id, $course2->id);

        // Ensure that course1 is complete but course 2 isn't yet for user1.
        $course1info = new completion_info($course1a);
        $this->assertTrue($course1info->is_course_complete($user1->id));
        $course2info = new completion_info($course2);
        $this->assertFalse($course2info->is_course_complete($user1->id));

        // Run review on the completion of course 1a criteria for user1.
        $completion_criteria_course = new completion_criteria_course(array(
            'course' => $course2->id,
            'criteriatype' => COMPLETION_CRITERIA_TYPE_COURSE,
            'courseinstance' => $course1a->id
        ));
        $criteria_completion = new completion_criteria_completion(array(
            'course' => $course2->id,
            'userid' => $user1->id,
            'criteriaid' => $completion_criteria_course->id
        ));
        $completion_criteria_course->review($criteria_completion);

        // Run review on the completion of course 1a criteria for user2.
        $completion_criteria_course = new completion_criteria_course(array(
            'course' => $course2->id,
            'criteriatype' => COMPLETION_CRITERIA_TYPE_COURSE,
            'courseinstance' => $course1a->id
        ));
        $criteria_completion = new completion_criteria_completion(array(
            'course' => $course2->id,
            'userid' => $user2->id,
            'criteriaid' => $completion_criteria_course->id
        ));
        $completion_criteria_course->review($criteria_completion);

        // Ensure that neither user is complete for course2 yet (no one has completed course1b).
        $course2info = new completion_info($course2);
        $this->assertFalse($course2info->is_course_complete($user1->id));
        $this->assertFalse($course2info->is_course_complete($user2->id));

        // Now complete course 3 for both, using the other completion time.
        $this->completion_generator->complete_course($course1b, $user1, $testcompletiontime_early);
        $this->completion_generator->complete_course($course1b, $user2, $testcompletiontime_early);

        // Now we can enrol in course2.
        $this->generator->enrol_user($user1->id, $course2->id);
        $this->generator->enrol_user($user2->id, $course2->id);

        // Run review again for each user, this time on course1b.
        // Review user 1.
        $completion_criteria_course = new completion_criteria_course(array(
            'course' => $course2->id,
            'criteriatype' => COMPLETION_CRITERIA_TYPE_COURSE,
            'courseinstance' => $course1b->id
        ));
        $criteria_completion = new completion_criteria_completion(array(
            'course' => $course2->id,
            'userid' => $user1->id,
            'criteriaid' => $completion_criteria_course->id
        ));
        $completion_criteria_course->review($criteria_completion);

        // Review user 2.
        $completion_criteria_course = new completion_criteria_course(array(
            'course' => $course2->id,
            'criteriatype' => COMPLETION_CRITERIA_TYPE_COURSE,
            'courseinstance' => $course1b->id
        ));
        $criteria_completion = new completion_criteria_completion(array(
            'course' => $course2->id,
            'userid' => $user2->id,
            'criteriaid' => $completion_criteria_course->id
        ));
        $completion_criteria_course->review($criteria_completion);

        // User1 should be complete for course2. User 2 shouldn't be.
        $course2info = new completion_info($course2);
        $this->assertTrue($course2info->is_course_complete($user1->id));
        $this->assertFalse($course2info->is_course_complete($user2->id));

        // Check that the record for the course2 has the same completion time as course1.
        $coursecompletionrecord = $DB->get_record('course_completions',
            array('course' => $course2->id, 'userid' => $user1->id), 'timecompleted', MUST_EXIST);
        $this->assertEquals($testcompletiontime_later, $coursecompletionrecord->timecompleted);
    }
}
