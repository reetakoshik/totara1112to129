<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package core_course
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Class course_visibility_with_audience_test
 */
class course_visibility_with_audience_test extends advanced_testcase {
    /**
     * Data provider - [ course.audiencevisible, course.visible, course.showgrades, expects_no_audience, expects_yes_audience ]
     *
     * The expects_xxx arrays contain six elements for the visibility of courses for each user.
     *
     * user id       cohort enrol
     * ---- -------- ------ -----
     * 0    user1    no     no
     * 1    user2    yes    no
     * 2    student1 no     student
     * 3    student2 yes    student
     * 4    teacher1 no     teacher
     * 5    teacher2 yes    teacher
     *
     * Each element represents the visibility of two courses:
     * - course2 has cohort association where course1 does not.
     *
     * cohort  (no)    (yes)
     * value  course1 course2
     * -----  ------- -------
     * 0      no      no
     * 1      yes     no
     * 2      no      yes
     * 3      yes     yes
     *
     * @return array
     */
    public function data_provider_visibility() {
        $data = [];

        //          -------  course settings   -------                        -audiencevisibility- //
        //          audiencevisible             vis sg    u  uc  s sc  t tc     u  uc  s sc  t tc  //
        $data[] = [ COHORT_VISIBLE_ENROLLED, /**/ 0, 0, [ 0, 0, 0, 0, 0, 0 ], [ 0, 0, 0, 0, 0, 0 ] ];
        $data[] = [ COHORT_VISIBLE_AUDIENCE, /**/ 0, 0, [ 0, 0, 0, 0, 0, 0 ], [ 0, 0, 0, 0, 0, 0 ] ];
        $data[] = [ COHORT_VISIBLE_ALL, /*     */ 0, 0, [ 0, 0, 0, 0, 0, 0 ], [ 0, 0, 0, 0, 0, 0 ] ];
        $data[] = [ COHORT_VISIBLE_NOUSERS, /* */ 0, 0, [ 0, 0, 0, 0, 0, 0 ], [ 0, 0, 0, 0, 0, 0 ] ];
        $data[] = [ COHORT_VISIBLE_ENROLLED, /**/ 1, 0, [ 0, 0, 0, 0, 0, 0 ], [ 0, 0, 0, 0, 0, 0 ] ];
        $data[] = [ COHORT_VISIBLE_AUDIENCE, /**/ 1, 0, [ 0, 0, 0, 0, 0, 0 ], [ 0, 0, 0, 0, 0, 0 ] ];
        $data[] = [ COHORT_VISIBLE_ALL, /*     */ 1, 0, [ 0, 0, 0, 0, 0, 0 ], [ 0, 0, 0, 0, 0, 0 ] ];
        $data[] = [ COHORT_VISIBLE_NOUSERS, /* */ 1, 0, [ 0, 0, 0, 0, 0, 0 ], [ 0, 0, 0, 0, 0, 0 ] ];

        $data[] = [ COHORT_VISIBLE_ENROLLED, /**/ 0, 1, [ 0, 0, 0, 0, 0, 0 ], [ 0, 0, 3, 3, 0, 0 ] ];
        $data[] = [ COHORT_VISIBLE_AUDIENCE, /**/ 0, 1, [ 0, 0, 0, 0, 0, 0 ], [ 0, 0, 3, 3, 0, 0 ] ];
        $data[] = [ COHORT_VISIBLE_ALL, /*     */ 0, 1, [ 0, 0, 0, 0, 0, 0 ], [ 0, 0, 3, 3, 0, 0 ] ];
        $data[] = [ COHORT_VISIBLE_NOUSERS, /* */ 0, 1, [ 0, 0, 0, 0, 0, 0 ], [ 0, 0, 0, 0, 0, 0 ] ];
        $data[] = [ COHORT_VISIBLE_ENROLLED, /**/ 1, 1, [ 0, 0, 3, 3, 0, 0 ], [ 0, 0, 3, 3, 0, 0 ] ];
        $data[] = [ COHORT_VISIBLE_AUDIENCE, /**/ 1, 1, [ 0, 0, 3, 3, 0, 0 ], [ 0, 0, 3, 3, 0, 0 ] ];
        $data[] = [ COHORT_VISIBLE_ALL, /*     */ 1, 1, [ 0, 0, 3, 3, 0, 0 ], [ 0, 0, 3, 3, 0, 0 ] ];
        $data[] = [ COHORT_VISIBLE_NOUSERS, /* */ 1, 1, [ 0, 0, 3, 3, 0, 0 ], [ 0, 0, 0, 0, 0, 0 ] ];

        return $data;
    }

    /**
     * @param int $audiencevisible
     * @param int $visible
     * @param int $showgrades
     * @param array $expects_no_audience
     * @param array $expects_yes_audience
     * @dataProvider data_provider_visibility
     */
    public function test_course_visibility_with_audience_visibility($audiencevisible, $visible, $showgrades, $expects_no_audience, $expects_yes_audience) {
        global $DB, $CFG;

        $this->resetAfterTest();

        /** @var \testing_data_generator */
        $gen = $this->getDataGenerator();

        // cohort: user1 & student1 & teacher1
        $users = [];
        foreach (explode(',', 'user1,user2,student1,student2,teacher1,teacher2') as $id) {
            $users[] = $gen->create_user(['username' => $id]);
        }

        // create courses
        $category = $gen->create_category();
        $recs = [ 'category' => $category->id, 'visible' => $visible, 'audiencevisible' => $audiencevisible, 'showgrades' => $showgrades, 'summary' => '?' ];
        $opts = [ 'createsections' => true ];
        $course = $gen->create_course($recs, $opts)->id;
        $course_cohort = $gen->create_course($recs, $opts)->id;

        // create audience
        $audience = $gen->create_cohort();
        cohort_add_member($audience->id, $users[1]->id);
        cohort_add_member($audience->id, $users[3]->id);
        cohort_add_member($audience->id, $users[5]->id);
        totara_cohort_add_association($audience->id, $course_cohort, COHORT_ASSN_ITEMTYPE_COURSE, COHORT_ASSN_VALUE_VISIBLE);

        // enrol students & teachers
        $studentrole = $DB->get_record('role', [ 'shortname' => 'student' ]);
        $this->assertNotEmpty($studentrole);
        $teacherrole = $DB->get_record('role', [ 'shortname' => 'teacher' ]);
        $this->assertNotEmpty($teacherrole);

        /** @var \enrol_plugin */
        $manual = enrol_get_plugin('manual');
        $this->assertNotEmpty($manual);
        $inst = $DB->get_record('enrol', [ 'courseid' => $course, 'enrol' => 'manual'], '*', MUST_EXIST);
        $inst_cohort = $DB->get_record('enrol', [ 'courseid' => $course_cohort, 'enrol' => 'manual'], '*', MUST_EXIST);

        for ($i = 2; $i < 4; $i++) {
            $manual->enrol_user($inst, $users[$i]->id, $studentrole->id);
            $manual->enrol_user($inst_cohort, $users[$i]->id, $studentrole->id);
        }
        for ($i = 4; $i < 6; $i++) {
            $manual->enrol_user($inst, $users[$i]->id, $teacherrole->id);
            $manual->enrol_user($inst_cohort, $users[$i]->id, $teacherrole->id);
        }

        require_once($CFG->dirroot . '/grade/lib.php');
        require_once($CFG->dirroot . '/grade/report/overview/lib.php');

        $get_setup_courses_data = function ($uid, $ctx) use ($course, $course_cohort) {
            self::setUser($uid);
            $o = new grade_report_overview($uid, new grade_plugin_return(), $ctx);
            $courses = $o->setup_courses_data(true);
            $x = array_key_exists($course, $courses);
            $y = array_key_exists($course_cohort, $courses);
            return (int)$x + (int)$y * 2;
        };

        $context = context_course::instance($course);

        $CFG->audiencevisibility = 0;
        for ($i = 0; $i < 6; $i++) {
            $existence = $get_setup_courses_data($users[$i]->id, $context);
            $this->assertEquals($expects_no_audience[$i], $existence, $users[$i]->username);
        }

        $CFG->audiencevisibility = 1;
        for ($i = 0; $i < 6; $i++) {
            $existence = $get_setup_courses_data($users[$i]->id, $context);
            $this->assertEquals($expects_yes_audience[$i], $existence, $users[$i]->username);
        }
    }
}
