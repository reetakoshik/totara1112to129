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
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package totara_program
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/totara/reportbuilder/tests/reportcache_advanced_testcase.php');

/**
 * Tests program progress information functions
 */
class totara_program_progress_testcase extends reportcache_advanced_testcase {

    /**
     * Setup data used in test functions
     *
     * @return object $data
     */
    private function setup_common() {
        global $DB;

        $this->resetAfterTest(true);

        $that = new class() {
            /** @var testing_data_generator */
            public $data_generator;

            /** @var totara_program_generator */
            public $program_generator;

            /** @var core_completion_generator */
            public $comp_generator;

            /** @var stdClass */
            public $student, $teacher;

            /** @var program */
            public $program1;

            /** @var stdClass[] */
            public $courses;

            /** @var stdClass[] */
            public $data, $forums, $assigns, $labels;

            /** @var stdClass[] */
            public $coursekeys;

            /** @var int[] */
            public $cfids;

            /** @var int */
            public $num_test_courses = 11;

        };

        $that->data_generator = $this->getDataGenerator();
        $that->program_generator = $that->data_generator->get_plugin_generator('totara_program');
        $that->comp_generator = $that->data_generator->get_plugin_generator('core_completion');

        $that->student = $that->data_generator->create_user();
        $that->teacher = $that->data_generator->create_user();
        $studentrole = $DB->get_record('role', array('shortname' => 'student'));
        $teacherrole = $DB->get_record('role', array('shortname' => 'editingteacher'));

        for ($i = 1; $i <= $that->num_test_courses; $i++) {
            $that->courses[$i] = $that->data_generator->create_course();
            $that->comp_generator->enable_completion_tracking($that->courses[$i]);

            $that->data[$i] = $that->data_generator->create_module('data',
                array('course' => $that->courses[$i]->id,
                      'completion' => COMPLETION_TRACKING_MANUAL));
            $that->forums[$i] = $that->data_generator->create_module('forum',
                array('course' => $that->courses[$i]->id,
                      'completion' => COMPLETION_TRACKING_MANUAL));
            $that->assigns[$i] = $that->data_generator->create_module('assign',
                array('course' => $that->courses[$i]->id,
                      'completion' => COMPLETION_TRACKING_MANUAL));
            $that->labels[$i] = $that->data_generator->create_module('label',
                array('course' => $that->courses[$i]->id,
                      'completion' => COMPLETION_TRACKING_MANUAL));

            $that->data_generator->enrol_user($that->student->id, $that->courses[$i]->id, $studentrole->id);
            $that->data_generator->enrol_user($that->teacher->id, $that->courses[$i]->id, $teacherrole->id);

            $that->comp_generator->set_activity_completion($that->courses[$i]->id,
                array($that->data[$i], $that->forums[$i], $that->assigns[$i], $that->labels[$i]));
        }

        // Reload courses. Otherwise when we compare the courses with the returned courses,
        // we get subtle differences in sone values such as cacherev and sortorder.
        for ($i = 1; $i <= $that->num_test_courses; $i++) {
            $that->courses[$i] = $DB->get_record('course', array('id' => $that->courses[$i]->id));
            $that->coursekeys[$i] = 'course_' . $that->courses[$i]->id . '_' . $that->courses[$i]->fullname;
        }

        $that->program1 = $that->program_generator->create_program();

        $cfgenerator = $this->getDataGenerator()->get_plugin_generator('totara_customfield');
        $that->cfids = $cfgenerator->create_multiselect('course', array('score' => array('1', '2', '3')));

        return $that;
    }

    /**
     * Verify the content of a single level progressinfo
     */
    protected function verify_info($info, $agg_method, $weight, $score, $customdata = null) {
        $this->assertTrue($info instanceof \totara_core\progressinfo\progressinfo);
        $this->assertEquals($agg_method, $info->get_agg_method());
        $this->assertEquals($weight, $info->get_weight());
        $this->assertEquals($score, $info->get_score());
        if (!is_null($customdata)) {
            $this->assertEquals($customdata, $info->get_customdata());
        }
    }


    /***********************************
     * Test building of progressinfo
     ***********************************/

    /**
     * Tests get_user_progressinfo function with single courseset and single course
     * S1 (course1)
     * No user progress
     */
    public function test_get_user_progressinfo_single_courseset_single_course() {

        $this->resetAfterTest(true);
        $that = $this->setup_common();

        // Add a course to the program in a single courseset
        $detail = array();
        $detail[] = array('type' => CONTENTTYPE_MULTICOURSE,
                         'nextsetoperator' => NEXTSETOPERATOR_THEN,
                         'completiontype' => COMPLETIONTYPE_ALL,
                         'courses' => array($that->courses[1]));

        $that->data_generator->create_coursesets_in_program($that->program1, $detail);

        // Reload the program, because the content has changed.
        $that->program1 = new program($that->program1->id);
        $progressinfo = \totara_program\progress\program_progress::get_user_progressinfo($that->program1, $that->student->id);

        // We need the courseset and course keys
        $progcontent = $that->program1->get_content();
        $coursesets = $progcontent->get_course_sets();
        $coursesetkey = $coursesets[0]->get_progressinfo_key();

        // Program
        $this->verify_info($progressinfo, \totara_core\progressinfo\progressinfo::AGGREGATE_ALL, 1, 0);

        // One courseset_group
        $this->assertEquals(1, $progressinfo->count_criteria());
        $groupinfo = $progressinfo->get_criteria('coursesetgroup_0');
        $this->verify_info($groupinfo, \totara_core\progressinfo\progressinfo::AGGREGATE_ALL, 1, 0);

        // One AND
        $this->assertEquals(1, $groupinfo->count_criteria());
        $andinfo = $groupinfo->get_criteria('coursesetgroup_0_and_0');
        $this->verify_info($andinfo, \totara_core\progressinfo\progressinfo::AGGREGATE_ALL, 1, 0);

        // One courseset
        $this->assertEquals(1, $andinfo->count_criteria());
        $setinfo = $andinfo->get_criteria($coursesetkey);
        $this->verify_info($setinfo, \totara_core\progressinfo\progressinfo::AGGREGATE_ALL, 1, 0);

        // One course in the set
        $this->assertEquals(1, $setinfo->count_criteria());
        $courseinfo = $setinfo->get_criteria($that->coursekeys[1]);
        $this->verify_info($courseinfo, \totara_core\progressinfo\progressinfo::AGGREGATE_ALL, 1, 0,
            array('timestarted' => 0, 'timecompleted' => null));
    }

    /**
     * Tests get_user_progressinfo function with single courseset, multiple courses - all AND
     * S1 (course1, course2 and course3)
     * No user progress
     */
    public function test_get_user_progressinfo_single_courseset_multi_course_and() {

        $this->resetAfterTest(true);
        $that = $this->setup_common();

        // Add courses to the program in a single courseset
        $detail = array();
        $detail[] = array('type' => CONTENTTYPE_MULTICOURSE,
                         'nextsetoperator' => NEXTSETOPERATOR_THEN,
                         'completiontype' => COMPLETIONTYPE_ALL,
                         'courses' => array($that->courses[1], $that->courses[2], $that->courses[3]));

        $that->data_generator->create_coursesets_in_program($that->program1, $detail);

        // Reload the programs, because their content has changed.
        $that->program1 = new program($that->program1->id);
        $progressinfo = \totara_program\progress\program_progress::get_user_progressinfo($that->program1, $that->student->id);

        // We need the courseset key
        $progcontent = $that->program1->get_content();
        $coursesets = $progcontent->get_course_sets();
        $coursesetkey = $coursesets[0]->get_progressinfo_key();

        // Program
        $this->verify_info($progressinfo, \totara_core\progressinfo\progressinfo::AGGREGATE_ALL, 3, 0);

        // One courseset_group
        $this->assertEquals(1, $progressinfo->count_criteria());
        $groupinfo = $progressinfo->get_criteria('coursesetgroup_0');
        $this->verify_info($groupinfo, \totara_core\progressinfo\progressinfo::AGGREGATE_ALL, 3, 0);

        // One AND
        $this->assertEquals(1, $groupinfo->count_criteria());
        $andinfo = $groupinfo->get_criteria('coursesetgroup_0_and_0');
        $this->verify_info($andinfo, \totara_core\progressinfo\progressinfo::AGGREGATE_ALL, 3, 0);

        // One courseset
        $this->assertEquals(1, $andinfo->count_criteria());
        $setinfo = $andinfo->get_criteria($coursesetkey);
        $this->verify_info($setinfo, \totara_core\progressinfo\progressinfo::AGGREGATE_ALL, 3, 0);

        // Three courses in the set
        $this->assertEquals(3, $setinfo->count_criteria());

        for ($i = 1; $i <= 3; $i++) {
            $courseinfo = $setinfo->get_criteria($that->coursekeys[$i]);
            $this->verify_info($courseinfo, \totara_core\progressinfo\progressinfo::AGGREGATE_ALL, 1, 0,
                array('timestarted' => 0, 'timecompleted' => null));
        }
    }

    /**
     * Tests get_user_progressinfo function with multipl coursesets
     * multiple courses - some OR, some AND
     * S1 (course1 or course2)
     * OR
     * S2 (course3 and course4)
     * AND
     * S3 (min 1 of course5, course6)
     * THEN
     * S4 (min score 2 of course7, course8)
     * AND
     * S5 (min 1 of AND min score of 2 of course9, course10)
     * OR
     * S6 (course11)
     * No user progress
     */
    public function test_get_user_progressinfo_multiple_coursesets() {

        $this->resetAfterTest(true);
        $that = $this->setup_common();

        // Add coursesets to the program
        // S1 : one of course1, course2
        $detail = array();
        $detail[] = array('type' => CONTENTTYPE_MULTICOURSE,
                         'nextsetoperator' => NEXTSETOPERATOR_OR,
                         'completiontype' => COMPLETIONTYPE_ANY,
                         'courses' => array($that->courses[1], $that->courses[2]));
        // OR

        // S2 : all of course3, course4
        $detail[] = array('type' => CONTENTTYPE_MULTICOURSE,
                          'nextsetoperator' => NEXTSETOPERATOR_AND,
                          'completiontype' => COMPLETIONTYPE_ALL,
                          'courses' => array($that->courses[3], $that->courses[4]));
        // AND

        // S3 : some of course5, course6 with at least 1 completed
        $detail[] = array('type' => CONTENTTYPE_MULTICOURSE,
                          'nextsetoperator' => NEXTSETOPERATOR_THEN,
                          'completiontype' => COMPLETIONTYPE_SOME,
                          'mincourses' => 1,
                          'courses' => array($that->courses[5], $that->courses[6]));
        // THEN

        // S4 : some of course7, course8 with at min score
        $detail[] = array('type' => CONTENTTYPE_MULTICOURSE,
                          'nextsetoperator' => NEXTSETOPERATOR_AND,
                          'completiontype' => COMPLETIONTYPE_SOME,
                          'coursesumfield' => $that->cfids['score'],
                          'coursesumfieldtotal' => 2,
                          'courses' => array($that->courses[7], $that->courses[8]));
        // AND

        // S5 : some of course9, course10 with a min course and min score
        $detail[] = array('type' => CONTENTTYPE_MULTICOURSE,
                          'nextsetoperator' => NEXTSETOPERATOR_OR,
                          'completiontype' => COMPLETIONTYPE_SOME,
                          'mincourses' => 1,
                          'coursesumfield' => $that->cfids['score'],
                          'coursesumfieldtotal' => 2,
                          'courses' => array($that->courses[9], $that->courses[10]));
        // OR

        // S6 : all course11
        $detail[] = array('type' => CONTENTTYPE_MULTICOURSE,
                          'nextsetoperator' => NEXTSETOPERATOR_THEN,
                          'completiontype' => COMPLETIONTYPE_ALL,
                          'courses' => array($that->courses[11]));

        $that->data_generator->create_coursesets_in_program($that->program1, $detail);

        // Reload the programs, because their content has changed.
        $that->program1 = new program($that->program1->id);
        $progressinfo = \totara_program\progress\program_progress::get_user_progressinfo($that->program1, $that->student->id);

        // We need the courseset keys
        $progcontent = $that->program1->get_content();
        $coursesets = $progcontent->get_course_sets();
        $coursesetkeys = array();
        foreach ($coursesets as $idx => $courseset) {
            $coursesetkeys['S' . ($idx + 1)] = $courseset->get_progressinfo_key();
        }

        // Program
        $this->verify_info($progressinfo, \totara_core\progressinfo\progressinfo::AGGREGATE_ALL, 5, 0);

        // Two courseset_groups
        $this->assertEquals(2, $progressinfo->count_criteria());

        // First group containing S1, S2, S3
        $groupinfo = $progressinfo->get_criteria('coursesetgroup_0');
        $this->verify_info($groupinfo, \totara_core\progressinfo\progressinfo::AGGREGATE_ALL, 3, 0); // Weight == sum

        // One OR
        $this->assertEquals(1, $groupinfo->count_criteria());
        $orinfo = $groupinfo->get_criteria('coursesetgroup_0_or');
        $this->verify_info($orinfo, \totara_core\progressinfo\progressinfo::AGGREGATE_ANY, 3, 0);  // Weight == max

        // Two ANDs
        $this->assertEquals(2, $orinfo->count_criteria());

        // First AND for S1
        $andinfo = $orinfo->get_criteria('coursesetgroup_0_and_0');
        $this->verify_info($andinfo, \totara_core\progressinfo\progressinfo::AGGREGATE_ALL, 1, 0);

        // One courseset
        $this->assertEquals(1, $andinfo->count_criteria());
        $setinfo = $andinfo->get_criteria($coursesetkeys['S1']);
        $this->verify_info($setinfo, \totara_core\progressinfo\progressinfo::AGGREGATE_ANY, 1, 0);  // weight == max

        // Two courses in the set (course1 OR course2)
        $this->assertEquals(2, $setinfo->count_criteria());

        for ($i = 1; $i <= 2; $i++) {
            $courseinfo = $setinfo->get_criteria($that->coursekeys[$i]);
            $this->verify_info($courseinfo, \totara_core\progressinfo\progressinfo::AGGREGATE_ALL, 1, 0,
                array('timestarted' => 0, 'timecompleted' => null));
        }

        // Second AND for S2 and S3
        $andinfo = $orinfo->get_criteria('coursesetgroup_0_and_1');
        $this->verify_info($andinfo, \totara_core\progressinfo\progressinfo::AGGREGATE_ALL, 3, 0);  // weight == sum

        // Two coursesets
        $this->assertEquals(2, $andinfo->count_criteria());

        // First courseset (S2)
        $setinfo = $andinfo->get_criteria($coursesetkeys['S2']);
        $this->verify_info($setinfo, \totara_core\progressinfo\progressinfo::AGGREGATE_ALL, 2, 0);

        // Two courses in the set (course3 AND course4)
        $this->assertEquals(2, $setinfo->count_criteria());

        for ($i = 3; $i <= 4; $i++) {
            $courseinfo = $setinfo->get_criteria($that->coursekeys[$i]);
            $this->verify_info($courseinfo, \totara_core\progressinfo\progressinfo::AGGREGATE_ALL, 1, 0,
                array('timestarted' => 0, 'timecompleted' => null));
        }

        // Second courseset (S3)
        $setinfo = $andinfo->get_criteria($coursesetkeys['S3']);
        $this->verify_info($setinfo, \totara_core\progressinfo\progressinfo::AGGREGATE_ALL, 1, 0,  // weight = 1 for min num or min score
            array('requiredcourses' => 1,
                  'requiredpoints' => 0,
                  'totalcourses' => 0,
                  'totalpoints' => 0));

        // Two courses in the set (min number - course5, course6)
        $this->assertEquals(2, $setinfo->count_criteria());

        for ($i = 5; $i <= 6; $i++) {
            $courseinfo = $setinfo->get_criteria($that->coursekeys[$i]);
            $this->verify_info($courseinfo, \totara_core\progressinfo\progressinfo::AGGREGATE_ALL, 1, 0,
                array('timestarted' => 0, 'timecompleted' => null));
        }

        // Second courseset group containing S4, S5, S6
        $groupinfo = $progressinfo->get_criteria('coursesetgroup_1');
        $this->verify_info($groupinfo, \totara_core\progressinfo\progressinfo::AGGREGATE_ALL, 2, 0);

        // One OR
        $this->assertEquals(1, $groupinfo->count_criteria());
        $orinfo = $groupinfo->get_criteria('coursesetgroup_1_or');
        $this->verify_info($orinfo, \totara_core\progressinfo\progressinfo::AGGREGATE_ANY, 2, 0);  // weight = max

        // Two ANDs
        $this->assertEquals(2, $orinfo->count_criteria());

        // First AND for S4 and S5
        $andinfo = $orinfo->get_criteria('coursesetgroup_1_and_0');
        $this->verify_info($andinfo, \totara_core\progressinfo\progressinfo::AGGREGATE_ALL, 2, 0);  // weight = sum

        // Two coursesets
        $this->assertEquals(2, $andinfo->count_criteria());

        // First courseset (S4)
        $setinfo = $andinfo->get_criteria($coursesetkeys['S4']);
        $this->verify_info($setinfo, \totara_core\progressinfo\progressinfo::AGGREGATE_ALL, 1, 0,
            array('requiredcourses' => 0,
                  'requiredpoints' => 2,
                  'totalcourses' => 0,
                  'totalpoints' => 0));

        // Two courses in the set (min  score - course7, course8)
        $this->assertEquals(2, $setinfo->count_criteria());

        for ($i = 7; $i <= 8; $i++) {
            $courseinfo = $setinfo->get_criteria($that->coursekeys[$i]);
            $this->verify_info($courseinfo, \totara_core\progressinfo\progressinfo::AGGREGATE_ALL, 1, 0,
                array('timestarted' => 0, 'timecompleted' => null, 'coursepoints' => 0));
        }

        // Second courseset (S5)
        $setinfo = $andinfo->get_criteria($coursesetkeys['S5']);
        $this->verify_info($setinfo, \totara_core\progressinfo\progressinfo::AGGREGATE_ALL, 1, 0,
            array('requiredcourses' => 1,
                  'requiredpoints' => 2,
                  'totalcourses' => 0,
                  'totalpoints' => 0));

        // Two courses in the set (min courses and min points - course9, course10)
        $this->assertEquals(2, $setinfo->count_criteria());

        for ($i = 9; $i <= 10; $i++) {
            $courseinfo = $setinfo->get_criteria($that->coursekeys[$i]);
            $this->verify_info($courseinfo, \totara_core\progressinfo\progressinfo::AGGREGATE_ALL, 1, 0,
                array('timestarted' => 0, 'timecompleted' => null, 'coursepoints' => 0));
        }

        // Second AND for S6
        $andinfo = $orinfo->get_criteria('coursesetgroup_1_and_1');
        $this->verify_info($andinfo, \totara_core\progressinfo\progressinfo::AGGREGATE_ALL, 1, 0);

        // One courseset
        $this->assertEquals(1, $andinfo->count_criteria());
        $setinfo = $andinfo->get_criteria($coursesetkeys['S6']);
        $this->verify_info($setinfo, \totara_core\progressinfo\progressinfo::AGGREGATE_ALL, 1, 0);

        // One courses in the set (course11)
        $this->assertEquals(1, $setinfo->count_criteria());

        $courseinfo = $setinfo->get_criteria($that->coursekeys[11]);
        $this->verify_info($courseinfo, \totara_core\progressinfo\progressinfo::AGGREGATE_ALL, 1, 0,
            array('timestarted' => 0, 'timecompleted' => null));
    }

    /**
     * Tests same course in multiple coursesets
     * S1 (course1 or course2)
     * OR
     * S2 (course1 and course3)
     * No user progress
     */
    public function test_get_user_progressinfo_one_course_multiple_coursesets() {

        $this->resetAfterTest(true);
        $that = $this->setup_common();

        // Add coursesets to the program
        // S1 : any of course1, course2
        $detail = array();
        $detail[] = array('type' => CONTENTTYPE_MULTICOURSE,
                         'nextsetoperator' => NEXTSETOPERATOR_OR,
                         'completiontype' => COMPLETIONTYPE_ANY,
                         'courses' => array($that->courses[1], $that->courses[2]));
        // OR

        // S2 : all of course1, course3
        $detail[] = array('type' => CONTENTTYPE_MULTICOURSE,
                          'nextsetoperator' => NEXTSETOPERATOR_AND,
                          'completiontype' => COMPLETIONTYPE_ALL,
                          'courses' => array($that->courses[1], $that->courses[3]));

        $that->data_generator->create_coursesets_in_program($that->program1, $detail);

        // Reload the programs, because their content has changed.
        $that->program1 = new program($that->program1->id);
        $progressinfo = \totara_program\progress\program_progress::get_user_progressinfo($that->program1, $that->student->id);

        // We need the courseset and course keys
        $progcontent = $that->program1->get_content();
        $coursesets = $progcontent->get_course_sets();
        $coursesetkeys = array();
        foreach ($coursesets as $idx => $courseset) {
            $coursesetkeys['S' . ($idx + 1)] = $courseset->get_progressinfo_key();
        }

        // Program
        $this->verify_info($progressinfo, \totara_core\progressinfo\progressinfo::AGGREGATE_ALL, 2, 0);

        // One courseset_group
        $this->assertEquals(1, $progressinfo->count_criteria());
        $groupinfo = $progressinfo->get_criteria('coursesetgroup_0');
        $this->verify_info($groupinfo, \totara_core\progressinfo\progressinfo::AGGREGATE_ALL, 2, 0);

        // One OR
        $this->assertEquals(1, $groupinfo->count_criteria());
        $orinfo = $groupinfo->get_criteria('coursesetgroup_0_or');
        $this->verify_info($orinfo, \totara_core\progressinfo\progressinfo::AGGREGATE_ANY, 2, 0);

        // Two ANDs
        $this->assertEquals(2, $orinfo->count_criteria());

        // First AND for S1
        $andinfo = $orinfo->get_criteria('coursesetgroup_0_and_0');
        $this->verify_info($andinfo, \totara_core\progressinfo\progressinfo::AGGREGATE_ALL, 1, 0);

        // One courseset
        $this->assertEquals(1, $andinfo->count_criteria());
        $setinfo = $andinfo->get_criteria($coursesetkeys['S1']);
        $this->verify_info($setinfo, \totara_core\progressinfo\progressinfo::AGGREGATE_ANY, 1, 0);

        // Two courses in the set (course1 OR course2)
        $this->assertEquals(2, $setinfo->count_criteria());

        for ($i = 1; $i <= 2; $i++) {
            $courseinfo = $setinfo->get_criteria($that->coursekeys[$i]);
            $this->verify_info($courseinfo, \totara_core\progressinfo\progressinfo::AGGREGATE_ALL, 1, 0,
                array('timestarted' => 0, 'timecompleted' => null));
        }

        // Second AND for S2
        $andinfo = $orinfo->get_criteria('coursesetgroup_0_and_1');
        $this->verify_info($andinfo, \totara_core\progressinfo\progressinfo::AGGREGATE_ALL, 2, 0);

        // One courseset
        $this->assertEquals(1, $andinfo->count_criteria());
        $setinfo = $andinfo->get_criteria($coursesetkeys['S2']);
        $this->verify_info($setinfo, \totara_core\progressinfo\progressinfo::AGGREGATE_ALL, 2, 0);

        // Two courses in the set (course1 AND course3)
        $this->assertEquals(2, $setinfo->count_criteria());

        $courseinfo = $setinfo->get_criteria($that->coursekeys[1]);
        $this->verify_info($courseinfo, \totara_core\progressinfo\progressinfo::AGGREGATE_ALL, 1, 0,
            array('timestarted' => 0, 'timecompleted' => null));

        $courseinfo = $setinfo->get_criteria($that->coursekeys[3]);
        $this->verify_info($courseinfo, \totara_core\progressinfo\progressinfo::AGGREGATE_ALL, 1, 0,
            array('timestarted' => 0, 'timecompleted' => null));
    }

    /**
     * Tests get_user_progressinfo function with single competency courseset all courses
     * No user progress
     */
    public function test_get_user_progressinfo_single_competency_courseset_all() {

        $this->resetAfterTest(true);
        $that = $this->setup_common();

        // Create a competency based on courses 2 and 3.
        /** @var totara_hierarchy_generator $hierarchygenerator */
        $hierarchygenerator = $that->data_generator->get_plugin_generator('totara_hierarchy');
        $competencyframework = $hierarchygenerator->create_comp_frame(array());
        $competencydata = array('frameworkid' => $competencyframework->id);
        $competency = $hierarchygenerator->create_comp($competencydata);
        // Completions for courses 2 and 3 will be assigned to this competency.
        $hierarchygenerator->assign_linked_course_to_competency($competency, $that->courses[2]);
        $hierarchygenerator->assign_linked_course_to_competency($competency, $that->courses[3]);

        // Add competency to the program in a single courseset
        $detail = array();
        $detail[] = array('type' => CONTENTTYPE_COMPETENCY,
                         'nextsetoperator' => NEXTSETOPERATOR_THEN,
                         'completiontype' => COMPLETIONTYPE_ALL,
                         'competency' => $competency);

        $that->data_generator->create_coursesets_in_program($that->program1, $detail);

        // Reload the programs, because their content has changed.
        $that->program1 = new program($that->program1->id);
        $progressinfo = \totara_program\progress\program_progress::get_user_progressinfo($that->program1, $that->student->id);

        // We need the courseset key
        $progcontent = $that->program1->get_content();
        $coursesets = $progcontent->get_course_sets();
        $coursesetkey = $coursesets[0]->get_progressinfo_key();

        // Program
        $this->verify_info($progressinfo, \totara_core\progressinfo\progressinfo::AGGREGATE_ALL, 2, 0);

        // One courseset_group
        $this->assertEquals(1, $progressinfo->count_criteria());
        $groupinfo = $progressinfo->get_criteria('coursesetgroup_0');
        $this->verify_info($groupinfo, \totara_core\progressinfo\progressinfo::AGGREGATE_ALL, 2, 0);

        // One AND
        $this->assertEquals(1, $groupinfo->count_criteria());
        $andinfo = $groupinfo->get_criteria('coursesetgroup_0_and_0');
        $this->verify_info($andinfo, \totara_core\progressinfo\progressinfo::AGGREGATE_ALL, 2, 0);

        // One courseset
        $this->assertEquals(1, $andinfo->count_criteria());
        $setinfo = $andinfo->get_criteria($coursesetkey);
        $this->verify_info($setinfo, \totara_core\progressinfo\progressinfo::AGGREGATE_ALL, 2, 0);

        // Three courses in the set
        $this->assertEquals(2, $setinfo->count_criteria());

        for ($i = 2; $i <= 3; $i++) {
            $courseinfo = $setinfo->get_criteria($that->coursekeys[$i]);
            $this->verify_info($courseinfo, \totara_core\progressinfo\progressinfo::AGGREGATE_ALL, 1, 0,
                array('timestarted' => 0, 'timecompleted' => null));
        }
    }

    /**
     * Tests get_user_progressinfo function with single competency courseset all courses
     * No user progress
     */
    public function test_get_user_progressinfo_single_competency_courseset_any() {

        global $COMP_AGGREGATION;

        $this->resetAfterTest(true);
        $that = $this->setup_common();

        // Create a competency based on courses 2 and 3.
        /** @var totara_hierarchy_generator $hierarchygenerator */
        $hierarchygenerator = $that->data_generator->get_plugin_generator('totara_hierarchy');
        $competencyframework = $hierarchygenerator->create_comp_frame(array());
        $competencydata = array('frameworkid' => $competencyframework->id, 'aggregationmethod' => $COMP_AGGREGATION['ANY']);
        $competency = $hierarchygenerator->create_comp($competencydata);
        // Completions for courses 2 and 3 will be assigned to this competency.
        $hierarchygenerator->assign_linked_course_to_competency($competency, $that->courses[2]);
        $hierarchygenerator->assign_linked_course_to_competency($competency, $that->courses[3]);

        // Add competency to the program in a single courseset
        $detail = array();
        $detail[] = array('type' => CONTENTTYPE_COMPETENCY,
                         'nextsetoperator' => NEXTSETOPERATOR_THEN,
                         'completiontype' => COMPLETIONTYPE_ALL, // courseset is set to ALL, competency set to ANY
                         'competency' => $competency);

        $that->data_generator->create_coursesets_in_program($that->program1, $detail);

        // Reload the programs, because their content has changed.
        $that->program1 = new program($that->program1->id);
        $progressinfo = \totara_program\progress\program_progress::get_user_progressinfo($that->program1, $that->student->id);

        // We need the courseset key
        $progcontent = $that->program1->get_content();
        $coursesets = $progcontent->get_course_sets();
        $coursesetkey = $coursesets[0]->get_progressinfo_key();

        // Program
        $this->verify_info($progressinfo, \totara_core\progressinfo\progressinfo::AGGREGATE_ALL, 1, 0);

        // One courseset_group
        $this->assertEquals(1, $progressinfo->count_criteria());
        $groupinfo = $progressinfo->get_criteria('coursesetgroup_0');
        $this->verify_info($groupinfo, \totara_core\progressinfo\progressinfo::AGGREGATE_ALL, 1, 0);

        // One AND
        $this->assertEquals(1, $groupinfo->count_criteria());
        $andinfo = $groupinfo->get_criteria('coursesetgroup_0_and_0');
        $this->verify_info($andinfo, \totara_core\progressinfo\progressinfo::AGGREGATE_ALL, 1, 0);

        // One courseset
        $this->assertEquals(1, $andinfo->count_criteria());
        $setinfo = $andinfo->get_criteria($coursesetkey);
        $this->verify_info($setinfo, \totara_core\progressinfo\progressinfo::AGGREGATE_ANY, 1, 0);

        // Three courses in the set
        $this->assertEquals(2, $setinfo->count_criteria());

        for ($i = 2; $i <= 3; $i++) {
            $courseinfo = $setinfo->get_criteria($that->coursekeys[$i]);
            $this->verify_info($courseinfo, \totara_core\progressinfo\progressinfo::AGGREGATE_ALL, 1, 0,
                array('timestarted' => 0, 'timecompleted' => null));
        }
    }


   /***********************************************************************************
   * Test aggregation of scores and weights after some completions
   ***********************************************************************************/

   /**
    * One set
    * User must complete one of course1(50%), course2(75%)
    * Expected progress = 75%
    */
    public function test_program_progress_one_courseset_one_of() {

        $this->resetAfterTest(true);
        $that = $this->setup_common();

        // Add courses to the program in a single courseset
        $detail = array();
        $detail[] = array('type' => CONTENTTYPE_MULTICOURSE,
                         'nextsetoperator' => NEXTSETOPERATOR_THEN,
                         'completiontype' => COMPLETIONTYPE_ANY,
                         'courses' => array($that->courses[1], $that->courses[2]));

        $that->data_generator->create_coursesets_in_program($that->program1, $detail);

        // Reload the programs, because their content has changed.
        $that->program1 = new program($that->program1->id);

        // Complete course activities to get the correct course completion
        // course1 = 50%
        $that->comp_generator->complete_activity($that->courses[1]->id, $that->student->id, $that->data[1]->cmid);
        $that->comp_generator->complete_activity($that->courses[1]->id, $that->student->id, $that->labels[1]->cmid);
        // course2 = 75%
        $that->comp_generator->complete_activity($that->courses[2]->id, $that->student->id, $that->data[2]->cmid);
        $that->comp_generator->complete_activity($that->courses[2]->id, $that->student->id, $that->assigns[2]->cmid);
        $that->comp_generator->complete_activity($that->courses[2]->id, $that->student->id, $that->labels[2]->cmid);

        $progressinfo = \totara_program\progress\program_progress::get_user_progressinfo($that->program1, $that->student->id);

        // We need the courseset and course keys
        $progcontent = $that->program1->get_content();
        $coursesets = $progcontent->get_course_sets();
        $coursesetkey = $coursesets[0]->get_progressinfo_key();

        // Program
        $this->assertEquals(75.0, $progressinfo->get_percentagecomplete());
        $this->verify_info($progressinfo, \totara_core\progressinfo\progressinfo::AGGREGATE_ALL, 1, 0.75);

        // One courseset_group
        $this->assertEquals(1, $progressinfo->count_criteria());
        $groupinfo = $progressinfo->get_criteria('coursesetgroup_0');
        $this->verify_info($groupinfo, \totara_core\progressinfo\progressinfo::AGGREGATE_ALL, 1, 0.75);

        // One AND
        $this->assertEquals(1, $groupinfo->count_criteria());
        $andinfo = $groupinfo->get_criteria('coursesetgroup_0_and_0');
        $this->verify_info($andinfo, \totara_core\progressinfo\progressinfo::AGGREGATE_ALL, 1, 0.75);

        // One courseset
        $this->assertEquals(1, $andinfo->count_criteria());
        $setinfo = $andinfo->get_criteria($coursesetkey);
        $this->verify_info($setinfo, \totara_core\progressinfo\progressinfo::AGGREGATE_ANY, 1, 0.75);

        // Two courses in the set
        $this->assertEquals(2, $setinfo->count_criteria());

        $courseinfo = $setinfo->get_criteria($that->coursekeys[1]);
        $this->verify_info($courseinfo, \totara_core\progressinfo\progressinfo::AGGREGATE_ALL, 1, 0.5);
        $customdata = $courseinfo->get_customdata();
        $this->assertNotEquals(0, $customdata['timestarted']);
        $this->assertNull($customdata['timecompleted']);

        $courseinfo = $setinfo->get_criteria($that->coursekeys[2]);
        $this->verify_info($courseinfo, \totara_core\progressinfo\progressinfo::AGGREGATE_ALL, 1, 0.75);
        $customdata = $courseinfo->get_customdata();
        $this->assertNotEquals(0, $customdata['timestarted']);
        $this->assertNull($customdata['timecompleted']);
    }

   /**
    * One set
    * User must complete all of course1(50%), course2(75%)
    * Expected progress = 62%
    */
    public function test_program_progress_one_courseset_all_of() {

        $this->resetAfterTest(true);
        $that = $this->setup_common();

        // Add courses to the program in a single courseset
        $detail = array();
        $detail[] = array('type' => CONTENTTYPE_MULTICOURSE,
                         'nextsetoperator' => NEXTSETOPERATOR_THEN,
                         'completiontype' => COMPLETIONTYPE_ALL,
                         'courses' => array($that->courses[1], $that->courses[2]));

        $that->data_generator->create_coursesets_in_program($that->program1, $detail);

        // Reload the programs, because their content has changed.
        $that->program1 = new program($that->program1->id);

        // Complete course activities to get the correct course completion
        // course1 = 50%
        $that->comp_generator->complete_activity($that->courses[1]->id, $that->student->id, $that->data[1]->cmid);
        $that->comp_generator->complete_activity($that->courses[1]->id, $that->student->id, $that->labels[1]->cmid);
        // course2 = 75%
        $that->comp_generator->complete_activity($that->courses[2]->id, $that->student->id, $that->data[2]->cmid);
        $that->comp_generator->complete_activity($that->courses[2]->id, $that->student->id, $that->assigns[2]->cmid);
        $that->comp_generator->complete_activity($that->courses[2]->id, $that->student->id, $that->labels[2]->cmid);

        $progressinfo = \totara_program\progress\program_progress::get_user_progressinfo($that->program1, $that->student->id);

        // We need the courseset and course keys
        $progcontent = $that->program1->get_content();
        $coursesets = $progcontent->get_course_sets();
        $coursesetkey = $coursesets[0]->get_progressinfo_key();

        // Program
        $this->assertEquals(62, $progressinfo->get_percentagecomplete());

        $this->verify_info($progressinfo, \totara_core\progressinfo\progressinfo::AGGREGATE_ALL, 2, 1.25);

        // One courseset_group
        $this->assertEquals(1, $progressinfo->count_criteria());
        $groupinfo = $progressinfo->get_criteria('coursesetgroup_0');
        $this->verify_info($groupinfo, \totara_core\progressinfo\progressinfo::AGGREGATE_ALL, 2, 1.25);

        // One AND
        $this->assertEquals(1, $groupinfo->count_criteria());
        $andinfo = $groupinfo->get_criteria('coursesetgroup_0_and_0');
        $this->verify_info($andinfo, \totara_core\progressinfo\progressinfo::AGGREGATE_ALL, 2, 1.25);

        // One courseset
        $this->assertEquals(1, $andinfo->count_criteria());
        $setinfo = $andinfo->get_criteria($coursesetkey);
        $this->verify_info($setinfo, \totara_core\progressinfo\progressinfo::AGGREGATE_ALL, 2, 1.25);

        // Two courses in the set
        $this->assertEquals(2, $setinfo->count_criteria());

        $courseinfo = $setinfo->get_criteria($that->coursekeys[1]);
        $this->verify_info($courseinfo, \totara_core\progressinfo\progressinfo::AGGREGATE_ALL, 1, 0.5);
        $customdata = $courseinfo->get_customdata();
        $this->assertNotEquals(0, $customdata['timestarted']);
        $this->assertNull($customdata['timecompleted']);

        $courseinfo = $setinfo->get_criteria($that->coursekeys[2]);
        $this->verify_info($courseinfo, \totara_core\progressinfo\progressinfo::AGGREGATE_ALL, 1, 0.75);
        $customdata = $courseinfo->get_customdata();
        $this->assertNotEquals(0, $customdata['timestarted']);
        $this->assertNull($customdata['timecompleted']);
    }

   /**
    * One set progress towards minimum number
    * Complete at least 3 of course1(0%), course2(25%), course3(50%), course4(75%), course5(100%)
    * Expected progress = (0.5 + 0.75 + 1)/3 ==> 75%
    */
    public function test_program_progress_one_courseset_minimum_number() {

        $this->resetAfterTest(true);
        $that = $this->setup_common();

        // Add courses to the program in a single courseset
        $detail = array();
        $detail[] = array('type' => CONTENTTYPE_MULTICOURSE,
                          'nextsetoperator' => NEXTSETOPERATOR_THEN,
                          'completiontype' => COMPLETIONTYPE_SOME,
                          'mincourses' => 3,
                          'courses' => array($that->courses[1],
                                             $that->courses[2],
                                             $that->courses[3],
                                             $that->courses[4],
                                             $that->courses[5]));

        $that->data_generator->create_coursesets_in_program($that->program1, $detail);

        // Reload the programs, because their content has changed.
        $that->program1 = new program($that->program1->id);

        // Complete course activities to get the correct course completion
        // course1 = 0%
        // course2 = 25%
        $that->comp_generator->complete_activity($that->courses[2]->id, $that->student->id, $that->data[2]->cmid);
        // course3 = 50%
        $that->comp_generator->complete_activity($that->courses[3]->id, $that->student->id, $that->assigns[3]->cmid);
        $that->comp_generator->complete_activity($that->courses[3]->id, $that->student->id, $that->labels[3]->cmid);
        // course4 = 75%
        $that->comp_generator->complete_activity($that->courses[4]->id, $that->student->id, $that->data[4]->cmid);
        $that->comp_generator->complete_activity($that->courses[4]->id, $that->student->id, $that->assigns[4]->cmid);
        $that->comp_generator->complete_activity($that->courses[4]->id, $that->student->id, $that->labels[4]->cmid);
        // course5 = 100%
        $that->comp_generator->complete_activity($that->courses[5]->id, $that->student->id, $that->data[5]->cmid);
        $that->comp_generator->complete_activity($that->courses[5]->id, $that->student->id, $that->forums[5]->cmid);
        $that->comp_generator->complete_activity($that->courses[5]->id, $that->student->id, $that->assigns[5]->cmid);
        $that->comp_generator->complete_activity($that->courses[5]->id, $that->student->id, $that->labels[5]->cmid);

        $progressinfo = \totara_program\progress\program_progress::get_user_progressinfo($that->program1, $that->student->id);

        // We need the courseset and course keys
        $progcontent = $that->program1->get_content();
        $coursesets = $progcontent->get_course_sets();
        $coursesetkey = $coursesets[0]->get_progressinfo_key();

        // Program
        $this->assertEquals(75, $progressinfo->get_percentagecomplete());

        $this->verify_info($progressinfo, \totara_core\progressinfo\progressinfo::AGGREGATE_ALL, 1, 0.75);

        // One courseset_group
        $this->assertEquals(1, $progressinfo->count_criteria());
        $groupinfo = $progressinfo->get_criteria('coursesetgroup_0');
        $this->verify_info($groupinfo, \totara_core\progressinfo\progressinfo::AGGREGATE_ALL, 1, 0.75);

        // One AND
        $this->assertEquals(1, $groupinfo->count_criteria());
        $andinfo = $groupinfo->get_criteria('coursesetgroup_0_and_0');
        $this->verify_info($andinfo, \totara_core\progressinfo\progressinfo::AGGREGATE_ALL, 1, 0.75);

        // One courseset
        $this->assertEquals(1, $andinfo->count_criteria());
        $setinfo = $andinfo->get_criteria($coursesetkey);
        $this->verify_info($setinfo, \totara_core\progressinfo\progressinfo::AGGREGATE_ALL, 1, 0.75,
            array('requiredcourses' => 3,
                  'requiredpoints' => 0,
                  'totalcourses' => 2.25,
                  'totalpoints' => 0));

        // Five courses in the set
        $this->assertEquals(5, $setinfo->count_criteria());

        $courseinfo = $setinfo->get_criteria($that->coursekeys[1]);
        $this->verify_info($courseinfo, \totara_core\progressinfo\progressinfo::AGGREGATE_ALL, 1, 0);
        $customdata = $courseinfo->get_customdata();
        $this->assertEquals(0, $customdata['timestarted']);
        $this->assertNull($customdata['timecompleted']);

        $courseinfo = $setinfo->get_criteria($that->coursekeys[2]);
        $this->verify_info($courseinfo, \totara_core\progressinfo\progressinfo::AGGREGATE_ALL, 1, 0.25);
        $customdata = $courseinfo->get_customdata();
        $this->assertNotEquals(0, $customdata['timestarted']);
        $this->assertNull($customdata['timecompleted']);

        $courseinfo = $setinfo->get_criteria($that->coursekeys[3]);
        $this->verify_info($courseinfo, \totara_core\progressinfo\progressinfo::AGGREGATE_ALL, 1, 0.5);
        $customdata = $courseinfo->get_customdata();
        $this->assertNotEquals(0, $customdata['timestarted']);
        $this->assertNull($customdata['timecompleted']);

        $courseinfo = $setinfo->get_criteria($that->coursekeys[4]);
        $this->verify_info($courseinfo, \totara_core\progressinfo\progressinfo::AGGREGATE_ALL, 1, 0.75);
        $customdata = $courseinfo->get_customdata();
        $this->assertNotEquals(0, $customdata['timestarted']);
        $this->assertNull($customdata['timecompleted']);

        $courseinfo = $setinfo->get_criteria($that->coursekeys[5]);
        $this->verify_info($courseinfo, \totara_core\progressinfo\progressinfo::AGGREGATE_ALL, 1, 1.0);
        $customdata = $courseinfo->get_customdata();
        $this->assertNotEquals(0, $customdata['timestarted']);
        $this->assertNotNull($customdata['timecompleted']);
    }

    /**
     * Two coursesets
     * S1 (course1(50%) AND course2(75%))
     * OR
     * S2 (competency based on course7(100%) or course7(25%))
     *
     * Expected progress = ((0.5 + 0.75) + max(1, 0.25))/3 ==> 75%
     */
    public function test_program_progress_multi_courseset_competency() {

        global $COMP_AGGREGATION;

        $this->resetAfterTest(true);
        $that = $this->setup_common();

        // Create a competency based on courses 7 and 8.
        /** @var totara_hierarchy_generator $hierarchygenerator */
        $hierarchygenerator = $that->data_generator->get_plugin_generator('totara_hierarchy');
        $competencyframework = $hierarchygenerator->create_comp_frame(array());
        $competencydata = array('frameworkid' => $competencyframework->id, 'aggregationmethod' => $COMP_AGGREGATION['ANY']);
        $competency = $hierarchygenerator->create_comp($competencydata);
        // Completions for courses 7 and 8 will be assigned to this competency.
        $hierarchygenerator->assign_linked_course_to_competency($competency, $that->courses[7]);
        $hierarchygenerator->assign_linked_course_to_competency($competency, $that->courses[8]);

        // Add coursesets
        $detail = array();
        $detail[] = array('type' => CONTENTTYPE_MULTICOURSE,
                         'nextsetoperator' => NEXTSETOPERATOR_THEN,
                         'completiontype' => COMPLETIONTYPE_ALL,
                         'courses' => array($that->courses[1], $that->courses[2]));

        $detail[] = array('type' => CONTENTTYPE_COMPETENCY,
                         'nextsetoperator' => NEXTSETOPERATOR_THEN,
                         'completiontype' => COMPLETIONTYPE_ALL,  // Competency aggregation used here - this all is ignored
                         'competency' => $competency);

        $that->data_generator->create_coursesets_in_program($that->program1, $detail);

        // Reload the programs, because their content has changed.
        $that->program1 = new program($that->program1->id);

        // We complete course activities to get the correct course completion and check progress
        // course1 = 50%
        $that->comp_generator->complete_activity($that->courses[1]->id, $that->student->id, $that->data[1]->cmid);
        $that->comp_generator->complete_activity($that->courses[1]->id, $that->student->id, $that->labels[1]->cmid);
        // course2 = 75%
        $that->comp_generator->complete_activity($that->courses[2]->id, $that->student->id, $that->data[2]->cmid);
        $that->comp_generator->complete_activity($that->courses[2]->id, $that->student->id, $that->assigns[2]->cmid);
        $that->comp_generator->complete_activity($that->courses[2]->id, $that->student->id, $that->labels[2]->cmid);
        // course7 = 100%
        $that->comp_generator->complete_activity($that->courses[7]->id, $that->student->id, $that->data[7]->cmid);
        $that->comp_generator->complete_activity($that->courses[7]->id, $that->student->id, $that->forums[7]->cmid);
        $that->comp_generator->complete_activity($that->courses[7]->id, $that->student->id, $that->assigns[7]->cmid);
        $that->comp_generator->complete_activity($that->courses[7]->id, $that->student->id, $that->labels[7]->cmid);
        // course8 = 25%
        $that->comp_generator->complete_activity($that->courses[8]->id, $that->student->id, $that->data[8]->cmid);

        $progressinfo = \totara_program\progress\program_progress::get_user_progressinfo($that->program1, $that->student->id);

        // We need the courseset and course keys
        $progcontent = $that->program1->get_content();
        $coursesets = $progcontent->get_course_sets();
        $coursesetkeys = array();
        foreach ($coursesets as $idx => $courseset) {
            $coursesetkeys['S' . ($idx + 1)] = $courseset->get_progressinfo_key();
        }

        // Program
        $this->assertEquals(75, $progressinfo->get_percentagecomplete());

        $this->verify_info($progressinfo, \totara_core\progressinfo\progressinfo::AGGREGATE_ALL, 3, 2.25);

        // Two courseset_groups
        $this->assertEquals(2, $progressinfo->count_criteria());

        // First group
        $groupinfo = $progressinfo->get_criteria('coursesetgroup_0');
        $this->verify_info($groupinfo, \totara_core\progressinfo\progressinfo::AGGREGATE_ALL, 2, 1.25);

        // First AND
        $this->assertEquals(1, $groupinfo->count_criteria());
        $andinfo = $groupinfo->get_criteria('coursesetgroup_0_and_0');
        $this->verify_info($andinfo, \totara_core\progressinfo\progressinfo::AGGREGATE_ALL, 2, 1.25);

        // One courseset
        $this->assertEquals(1, $andinfo->count_criteria());
        $setinfo = $andinfo->get_criteria($coursesetkeys['S1']);
        $this->verify_info($setinfo, \totara_core\progressinfo\progressinfo::AGGREGATE_ALL, 2, 1.25);

        // Two courses in the set
        $this->assertEquals(2, $setinfo->count_criteria());

        $courseinfo = $setinfo->get_criteria($that->coursekeys[1]);
        $this->verify_info($courseinfo, \totara_core\progressinfo\progressinfo::AGGREGATE_ALL, 1, 0.5);
        $customdata = $courseinfo->get_customdata();
        $this->assertNotEquals(0, $customdata['timestarted']);
        $this->assertNull($customdata['timecompleted']);

        $courseinfo = $setinfo->get_criteria($that->coursekeys[2]);
        $this->verify_info($courseinfo, \totara_core\progressinfo\progressinfo::AGGREGATE_ALL, 1, 0.75);
        $customdata = $courseinfo->get_customdata();
        $this->assertNotEquals(0, $customdata['timestarted']);
        $this->assertNull($customdata['timecompleted']);


        // Second group
        $groupinfo = $progressinfo->get_criteria('coursesetgroup_1');
        $this->verify_info($groupinfo, \totara_core\progressinfo\progressinfo::AGGREGATE_ALL, 1, 1);

        // First AND
        $this->assertEquals(1, $groupinfo->count_criteria());
        $andinfo = $groupinfo->get_criteria('coursesetgroup_1_and_0');
        $this->verify_info($andinfo, \totara_core\progressinfo\progressinfo::AGGREGATE_ALL, 1, 1);

        // One courseset
        $this->assertEquals(1, $andinfo->count_criteria());
        $setinfo = $andinfo->get_criteria($coursesetkeys['S2']);
        $this->verify_info($setinfo, \totara_core\progressinfo\progressinfo::AGGREGATE_ANY, 1, 1);

        // Two courses in the set
        $this->assertEquals(2, $setinfo->count_criteria());

        $courseinfo = $setinfo->get_criteria($that->coursekeys[7]);
        $this->verify_info($courseinfo, \totara_core\progressinfo\progressinfo::AGGREGATE_ALL, 1, 1);
        $customdata = $courseinfo->get_customdata();
        $this->assertNotEquals(0, $customdata['timestarted']);
        $this->assertNotNull($customdata['timecompleted']);

        $courseinfo = $setinfo->get_criteria($that->coursekeys[8]);
        $this->verify_info($courseinfo, \totara_core\progressinfo\progressinfo::AGGREGATE_ALL, 1, 0.25);
        $customdata = $courseinfo->get_customdata();
        $this->assertNotEquals(0, $customdata['timestarted']);
        $this->assertNull($customdata['timecompleted']);
    }
}
