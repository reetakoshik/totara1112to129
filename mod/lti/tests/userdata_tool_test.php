<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
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
 * @author Murali Nair <murali.nair@totaralearning.com>
 * @package mod_lti
 */
defined('MOODLE_INTERNAL') || die();

use mod_lti\userdata\tool;

use totara_userdata\userdata\target_user;


/**
 * Unit tests for mod/lti/classes/userdata/tool.php.
 *
 * @group totara_userdata
 */
class mod_lti_userdata_tool_testcase extends advanced_testcase {
    /**
     * @var float submission grade.
     */
    private $grade = 0.54;

    /**
     * @var string lti tool url.
     */
    private $ltiurl = "http://some.lti.org";



    /**
     * {@inheritdoc}
     */
    public static function setUpBeforeClass() {
        global $CFG;

        parent::setUpBeforeClass();
        require_once($CFG->dirroot . '/mod/lti/servicelib.php');
    }


    /**
     * Generates a single LTI instance and the submissions for it.
     *
     * @param \stdClass $course course details.
     * @param array $users array of \stdClass objects representing learners for
     *        whom submissions are to be created.
     *
     * @return \stdClass the lti instance.
     */
    private function generate_lti_submissions(\stdClass $course, array $users) {
        global $DB;
        $values = [
            'course' => $course->id,
            'toolurl' => $this->ltiurl
        ];
        $instance = $this->getDataGenerator()->create_module('lti', $values);

        foreach ($users as $user) {
            lti_update_grade($instance, $user->id, $user->id + 99, $this->grade);

            // Add a test historic submission.
            $history = new stdClass();
            $history->ltiid = $instance->id;
            $history->userid = $user->id;
            $history->launchid = $user->id + 99;
            $history->timecreated = time();
            $DB->insert_record('lti_submission_history', $history);
        };

        return $instance;
    }


    /**
     * Generates test LTI instances and submissions.
     *
     * @param int $noofcourses no of courses to generate.
     * @param int $nooflti no of LTI instances to generate *per* course.
     * @param int $noofusers no of "normal" learners to generate *per* course.
     *        These are the learners that are NOT going to be "purged".
     *
     * @return array test data in this order:
     *         - user to be purged (\totara_userdata\userdata\target_user)
     *         - courses: list of (stdClass course, \stdClass category, array
     *           lti data from generate_lti_submissions()) tuples, one for each
     *           generated course.
     */
    private function generate($noofcourses, $nooflti, $noofusers) {
        global $DB;

        $generator = $this->getDataGenerator();
        $targetuser = new target_user($generator->create_user());

        $users = [$targetuser->get_user_record()];
        for ($i = 0; $i < $noofusers; $i++) {
            $users[] = $generator->create_user();
        }

        $courselti = [];
        for ($i = 0; $i < $noofcourses; $i++) {
            $category = $generator->create_category();
            $course = $generator->create_course(['category' => $category->id]);

            $ltiinstances = [];
            for ($j = 0; $j < $nooflti; $j++) {
                $ltiinstances[] = $this->generate_lti_submissions($course, $users);
            }

            $courselti[] = [$course, $category, $ltiinstances];
        }

        $learnerspercourse = $noofusers + 1;
        $totallti = $noofcourses * $nooflti;
        $totalsubmissions = $totallti * $learnerspercourse;

        $this->assertEquals($totallti, $DB->count_records('lti'));
        $this->assertEquals($totalsubmissions, $DB->count_records('lti_submission'));
        $this->assertEquals($totalsubmissions, $DB->count_records('lti_submission_history'));

        return [$targetuser, $courselti];
    }


    /**
     * Tests the count, purge and export functions for the given context.
     *
     * @param \stdClass $env test environment comprising the following fields:
     *        - \context context: test context
     *        - \totara_userdata\userdata\target_user purgeduser: the user to be
     *          be removed.
     *        - int purgecount: no of lti submissions the purged user should have
     *          *before* removal.
     *        - int finalsubmissioncount expected no of submissions after a user
     *          has been removed. NB: the purged user may still have submissions
     *          after removal because what is removed depends on the context in
     *          force.
     *        - array courses: list of (course, category, lti data) tuples from
     *          $this->generate().
     *        - int learnerspercourse: no of learners per course.
     */
    private function purge_count_export_test(\stdClass $env) {
        global $DB;

        $count = tool::execute_count($env->purgeduser, $env->context);
        $this->assertSame($env->purgecount, $count, "wrong count before purge");

        $exported = tool::execute_export($env->purgeduser, $env->context);
        $this->assertCount(0, $exported->files, "wrong exported files count");
        $this->assertCount($env->purgecount, $exported->data, "wrong exported data count");

        $grade = sprintf("%.02f%%", $this->grade*100);
        foreach ($exported->data as $data) {
            $this->assertSame($this->ltiurl, $data['lti url'], "wrong url");
            $this->assertSame($grade, $data['grade'], "wrong grade");
        }

        tool::execute_purge($env->purgeduser, $env->context);

        $totallti = 0;
        foreach ($env->courses as $tuple) {
            list(, , $ltiinstances) = $tuple;

            $totallti += count($ltiinstances);
        }

        $this->assertEquals($totallti, $DB->count_records('lti'));
        $this->assertEquals($env->finalsubmissioncount, $DB->count_records('lti_submission'));
        $this->assertEquals($env->finalsubmissioncount, $DB->count_records('lti_submission_history'));

        $count = tool::execute_count($env->purgeduser, $env->context);
        $this->assertSame(0, $count, "wrong count after purge");

        $exported =  tool::execute_export($env->purgeduser, $env->context);
        $this->assertCount(0, $exported->files, "wrong exported data count");
        $this->assertCount(0, $exported->data, "wrong exported data count after purge");
    }


    /**
     * Test operations in the system context.
     */
    public function test_system_context_hidden_module() {
        global $DB;

        $this->resetAfterTest();

        $noofcourses = 3;
        $ltipercourse = 2;
        $learnerspercourse = 4;
        list($purgeduser, $courses) = $this->generate($noofcourses, $ltipercourse, $learnerspercourse-1);

        $DB->set_field('modules', 'visible', '0', ['name' => 'lti']);

        $purgecount = $noofcourses * $ltipercourse; // ie all the user's submissions in all courses.
        $finalsubmissioncount = $noofcourses * $ltipercourse * ($learnerspercourse - 1); // ie everybody else's submissions.

        $env = [
            "context" => context_system::instance(),
            "purgeduser" => $purgeduser,
            "purgecount" => $purgecount,
            "finalsubmissioncount" => $finalsubmissioncount,
            "courses" => $courses,
            "learnerspercourse" => $learnerspercourse
        ];
        $this->purge_count_export_test((object)$env);
    }


    /**
     * Test operations in the category context.
     */
    public function test_category_context() {
        $this->resetAfterTest();

        $noofcourses = 3;
        $ltipercourse = 1;
        $learnerspercourse = 3;
        list($purgeduser, $courses) = $this->generate($noofcourses, $ltipercourse, $learnerspercourse-1);

        $purgecount = $ltipercourse; // ie all that user's submissions in a specific course since 1 course == 1 category
        $totalsubmissions = $noofcourses * $ltipercourse * $learnerspercourse;
        $finalsubmissioncount = $totalsubmissions - $purgecount;  // ie all submissions less the ones for a specific course.

        list(, $category, ) = $courses[$noofcourses-1];
        $env = [
            "context" => context_coursecat::instance($category->id),
            "purgeduser" => $purgeduser,
            "purgecount" => $purgecount,
            "finalsubmissioncount" => $finalsubmissioncount,
            "courses" => $courses,
            "learnerspercourse" => $learnerspercourse
        ];
        $this->purge_count_export_test((object)$env);
    }


    /**
     * Test operations in the course context.
     */
    public function test_course_context() {
        $this->resetAfterTest();

        $noofcourses = 3;
        $ltipercourse = 1;
        $learnerspercourse = 4;
        list($purgeduser, $courses) = $this->generate($noofcourses, $ltipercourse, $learnerspercourse-1);

        $purgecount = $ltipercourse; // ie all that user's submissions in a specific course
        $totalsubmissions = $noofcourses * $ltipercourse * $learnerspercourse;
        $finalsubmissioncount = $totalsubmissions - $purgecount; // ie all submissions less the ones for a specific course.

        list($course, , ) = $courses[0];
        $env = [
            "context" => context_course::instance($course->id),
            "purgeduser" => $purgeduser,
            "purgecount" => $purgecount,
            "finalsubmissioncount" => $finalsubmissioncount,
            "courses" => $courses,
            "learnerspercourse" => $learnerspercourse
        ];
        $this->purge_count_export_test((object)$env);
    }


    /**
     * Test operations in the module context.
     */
    public function test_module_context() {
        $this->resetAfterTest();

        $noofcourses = 1;
        $ltipercourse = 3;
        $learnerspercourse = 3;
        list($purgeduser, $courses) = $this->generate($noofcourses, $ltipercourse, $learnerspercourse-1);

        $purgecount = 1; // ie remove specific lti submission.
        $totalsubmissions = $noofcourses * $ltipercourse * $learnerspercourse;
        $finalsubmissioncount = $totalsubmissions - $purgecount;

        list($course, , $ltiinstances) = $courses[$noofcourses-1];
        $instance = $ltiinstances[0];
        $cm = get_coursemodule_from_instance('lti', $instance->id, $course->id);

        $env = [
            "context" => \context_module::instance($cm->id),
            "purgeduser" => $purgeduser,
            "purgecount" => $purgecount,
            "finalsubmissioncount" => $finalsubmissioncount,
            "courses" => $courses,
            "learnerspercourse" => $learnerspercourse
        ];
        $this->purge_count_export_test((object)$env);
    }
}
