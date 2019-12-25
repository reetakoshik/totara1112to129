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
 * @package mod_assign
 */
defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/assign/locallib.php');

use assignfeedback_editpdf\page_editor;
use mod_assign\userdata\singleassignments;
use totara_userdata\userdata\target_user;


/**
 * Unit tests for mod/assign/classes/userdata/singleassigments.php.
 *
 * @group totara_userdata
 */
class mod_assign_userdata_singleassignments_testcase extends advanced_testcase {
    /**
     * Compares the record counts in various mod assign related tables with the
     * expected values.
     *
     * NB: this assumes the test setup was generated via $this->generate().
     *
     * @param int $totalassignments expected assignment count ie course count x
     *        assignment count per course.
     * @param int $totalsubmissions expected submission count (ie across all
     *        assignments, across all courses). No generic formula for this; it
     *        depends what context is being used in the purge.
     * @param int $totallearners expected learner count across all course ie
     *        course count x learner count per course.
     */
    private function expected_counts($totalassignments, $totalsubmissions, $totallearners) {
        global $DB;

        $this->assertEquals($totalassignments, $DB->count_records('assign'));

        $assignsubtables = [
            'assign_submission',
            'assign_user_mapping',
            'assign_user_flags',
            'assign_grades',
            'assignsubmission_file',
            'assignsubmission_onlinetext',
            'assignfeedback_file',
            'assignfeedback_comments',
            'assignfeedback_editpdf_queue',
            'assignfeedback_editpdf_annot',
            'assignfeedback_editpdf_cmnt'
        ];
        foreach ($assignsubtables as $table) {
            $this->assertEquals($totalsubmissions, $DB->count_records($table));
        }

        $commentsfilter = [
            'commentarea' => 'submission_comments',
            'component' => 'assignsubmission_comments'
        ];
        $this->assertEquals($totalsubmissions, $DB->count_records('comments', $commentsfilter));

        $fileareas = [
            [ASSIGNSUBMISSION_ONLINETEXT_FILEAREA, 'assignsubmission_onlinetext'],
            [ASSIGNSUBMISSION_FILE_FILEAREA, 'assignsubmission_file'],
            [ASSIGNFEEDBACK_FILE_FILEAREA, 'assignfeedback_file']
        ];
        foreach ($fileareas as $tuple) {
            list($filearea, $component) = $tuple;
            $filter = ['filearea' => $filearea, 'component' => $component];

            // For some reason, uploading a file always creates extra entries per file.
            // And the extra entries' component and filearea are NOT related to mod assign!
            $this->assertEquals($totalsubmissions*2, $DB->count_records('files', $filter));
        }

        // Not only is mod assign directly coupled to the gradebook module, the
        // way the gradebook stores assignment grades is also very messy. The
        // grades_grade table not only has user assignment grades; it also has
        // records linking the user, assignment and the course in which the
        // assignment lives! Horribly convoluted and overly complex.
        $this->assertEquals($totalsubmissions+$totallearners, $DB->count_records('grade_grades'));

        // Two fillings are created per submission.
        $this->assertCount($totalsubmissions * 2, $DB->get_records('gradingform_guide_fillings'));
        $this->assertCount($totalsubmissions * 2, $DB->get_records('gradingform_rubric_fillings'));

        // Instance is created per submission per definition and must be gone!
        $this->assertCount($totalsubmissions * 2, $DB->get_records('grading_instances'));
    }


    /**
     * Generates a single assignment.
     *
     * @param \stdClass $course course details.
     * @param string $name course name.
     *
     * @return \assign the assignment wrapper.
     */
    private function generate_assignment(\stdClass $course, $name) {
        $values = [
            'name' => $name,
            'course' => $course->id,
            'intro' => $name,
            'introformat' => FORMAT_MOODLE,
            'alwaysshowdescription' => false,
            'submissiondrafts' => false,
            'requiresubmissionstatement' => false,
            'sendnotifications' => false,
            'sendlatenotifications' => false,
            'duedate' => 0,
            'cutoffdate' => 0,
            'allowsubmissionsfromdate' => 0,
            'grade' => 100,
            'completionsubmit' => true,
            'blindmarking' => false, // otherwise the gradebook will not be updated!
            'teamsubmission' => false,
            'requireallteammemberssubmit' => false,
            'teamsubmissiongroupingid' => 0,
            'markingworkflow' => false,
            'markingallocation' => true
        ];

        $module = $this->getDataGenerator()->create_module('assign', $values);
        $cm = get_coursemodule_from_instance('assign', $module->id, $course->id);
        $context = context_module::instance($cm->id);

        // This populates the assign table.
        return new assign($context, $cm, $course);
    }


    /**
     * Generates submission and feedback for the specified users in the given
     * assignment.
     *
     * @param \assign $assignment the parent assignment for which to create
     *        submissions.
     * @param \stdClass $course course details.
     * @param array $users array of \stdClass objects representing learners for
     *        whom submissions are to be created.
     * @param \stdClass $teacher teacher's details.
     *
     * @return \assign the parent assignment.
     */
    private function generate_submissions(assign $assignment, \stdClass $course, array $users, \stdClass $teacher) {
        global $DB, $CFG;

        $studentrole = $DB->get_record('role', ['shortname' => 'student'])->id;
        $teacherrole = $DB->get_record('role', ['shortname' => 'editingteacher'])->id;

        $onlinesubmission = $assignment->get_submission_plugin_by_type('onlinetext');
        $filesubmission = $assignment->get_submission_plugin_by_type('file');
        $commentfeedback = $assignment->get_feedback_plugin_by_type('comments');
        $filefeedback = $assignment->get_feedback_plugin_by_type('file');

        $generator = $this->getDataGenerator();
        $generator->enrol_user($teacher->id, $course->id, $teacherrole);

        foreach ($users as $user) {
            // Test data is generated using mod assign APIs; however, we have to
            // make use of low level APIs and workflows to do so. Unfortunately,
            // that is error prone and too tightly coupled. But no choice.
            $generator->enrol_user($user->id, $course->id, $studentrole);
            $this->setUser($user);

            // This populates the assignsubmission_onlinetext and
            // assignfeedback_editpdf_queue tables.
            $submission = $assignment->get_user_submission($user->id, true);


            // This populates the mdl_assignsubmission_onlinetext table. It has
            // to be done before "uploading" a file for the online text plugin.
            // Otherwise the "uploaded" file will mysteriously disappear from
            // the mdl_files table.
            $text = [
                'onlinetext_editor' => [
                    // This should have a reference to the uploaded file but is omitted here.
                    'text' => sprintf("%s's submission text", $user->username),
                    'itemid' => $submission->id,
                    'format' => FORMAT_HTML
                ]
            ];
            $onlinesubmission->save($submission, (object)$text);

            // This populates the *global* mdl_file table with assignsubmission_onlinetxt
            // plugin files. Yes, the online text submission is supposed to be for *text*
            // but you can also upload files into the textarea. Completely and totally
            // separate from the assignsubmission_file plugin.
            $file = 'submission.txt';
            $context = $assignment->get_context();
            $onlinesubmissionfile = [
                'contextid' => $context->id,
                'component' => 'assignsubmission_onlinetext',
                'filearea' => ASSIGNSUBMISSION_ONLINETEXT_FILEAREA,
                'itemid' => $submission->id,
                'filepath' => '/',
                'filename' => $file
            ];

            $fs = get_file_storage();
            $sourcefile = $CFG->dirroot . "/mod/assign/feedback/file/tests/fixtures/$file";
            $fs->create_file_from_pathname((object)$onlinesubmissionfile, $sourcefile);


            // This populates the global mdl_file table with assignsubmission_file
            // plugin files.
            $file = 'submission.pdf';
            $filesubmissionfile = [
                'contextid' => $context->id,
                'component' => 'assignsubmission_file',
                'filearea' => ASSIGNSUBMISSION_FILE_FILEAREA,
                'itemid' => $submission->id,
                'filepath' => '/',
                'filename' => $file
            ];

            $sourcefile = $CFG->dirroot . "/mod/assign/feedback/editpdf/tests/fixtures/$file";
            $fs->create_file_from_pathname((object)$filesubmissionfile, $sourcefile);

            // This populates the assignsubmission_file table. Notice it is
            // decoupled from the details in the mdl_files table. Which
            // makes it totally different from uploading files to the online text
            // plugin.
            $filesubmission->save($submission, new stdClass());


            // This populates the *global* mdl_comment table with submission
            // comments
            $submissioncomments = [
                'area' => 'submission_comments',
                'course' => $assignment->get_course(),
                'context' => $context,
                'itemid' => $submission->id,
                'component' => 'assignsubmission_comments',
                'showcount' => true,
                'displaycancel' => true,
            ];

            $comment = new comment((object)$submissioncomments);
            $comment->add(sprintf("%s's submission comment", $user->username));


            // This populates the assign_grades table.
            $this->setUser($teacher);
            $grading = [
                'grade' => 67,
                'attemptnumber' => 0,
                'allocatedmarker' => $teacher->id, // This forces assign_user_flags table to be populated
                'addattempt' => true // This forces grades_grade table to be populated
            ];
            $assignment->save_grade($user->id, (object)$grading);

            // This populates the mdl_assignfeedback_comments table. Incredibly,
            // submission comments are in the *global* mdl_comments table while
            // feedback comments are in a separate, mod assign related table!
            $feedbackcomments = [
                'assignfeedbackcomments_editor' => [
                    'text' => sprintf("feedback for '%s'", $user->username),
                    'format' => 1
                ]
            ];
            $grade = $assignment->get_user_grade($user->id, false);
            $commentfeedback->save($grade, (object)$feedbackcomments);

            // Let's create advanced grading madness here.
            $this->generate_advanced_grading_data($grade->id);

            // This populates the *global* mdl_file table with feedback files.
            $file = 'feedback.txt';
            $feedbackfile = [
                'contextid' => $context->id,
                'component' => 'assignfeedback_file',
                'filearea' => ASSIGNFEEDBACK_FILE_FILEAREA,
                'itemid' => $submission->id,
                'filepath' => '/',
                'filename' => $file
            ];
            $sourcefile = $CFG->dirroot . "/mod/assign/feedback/file/tests/fixtures/$file";
            $fs->create_file_from_pathname((object)$feedbackfile, $sourcefile);

            // This populates the mdl_assignfeedback_comments table.
            $filefeedbackdata = [
                sprintf('files_%d_filemanager', $user->id) => $submission->id
            ];
            $filefeedback->save($grade, (object)$filefeedbackdata);


            // This populates the assignfeedback_editpdf_cmnt table.
            $pdffeedbackcomments = [
                'rawtext' => sprintf('pdf comment for', $user->username),
                'width' => 100,
                'x' => 100,
                'y' => 100,
                'colour' => 'red'
            ];

            $comment = new \assignfeedback_editpdf\comment((object)$pdffeedbackcomments);
            page_editor::set_comments($grade->id, 0, [$comment]);

            // This populates the mdl_assignfeedback_editpdf_annot table.
            $pdffeedbackannotation = [
                'path' => '',
                'x' => 100,
                'y' => 100,
                'endx' => 200,
                'endy' => 200,
                'type' => 'line',
                'colour' => 'red'
            ];

            $annotation = new \assignfeedback_editpdf\annotation((object)$pdffeedbackannotation);
            page_editor::set_annotations($grade->id, 0, [$annotation]);
        }

        // This populates assign_user_mapping table.
        assign::allocate_unique_ids($assignment->get_instance()->id);

        return $assignment;
    }


    /**
     * Generates test assignments and submissions.
     *
     * @param int $noofcourses no of courses to generate.
     * @param int $noofassignments no of assignments to generate *per* course.
     * @param int $noofusers no of "normal" learners to generate *per* course.
     *        These are the learners that are NOT going to be "purged".
     *
     * @return array test data in this order:
     *         - user to be purged (\totara_userdata\userdata\target_user)
     *         - courses: list of (stdClass course, \stdClass category, array
     *           assignment) tuples, one for each generated course.
     */
    private function generate($noofcourses, $noofassignments, $noofusers) {
        $generator = $this->getDataGenerator();
        $teacher = $generator->create_user();
        $targetuser = new target_user($generator->create_user());

        $users = [$targetuser->get_user_record()];
        for ($i = 0; $i < $noofusers; $i++) {
            $users[] = $generator->create_user();
        }

        $courseassignments = [];
        for ($i = 0; $i < $noofcourses; $i++) {
            $category = $generator->create_category();
            $course = $generator->create_course(['category' => $category->id]);

            $assignments = [];
            for ($j = 0; $j < $noofassignments; $j++) {
                $name = sprintf('individual assignment #%d (%s)', $j, $course->fullname);
                $assignment = $this->generate_assignment($course, $name);
                $this->generate_submissions($assignment, $course, $users, $teacher);

                $assignments[] = $assignment;
            }

            $courseassignments[] = [$course, $category, $assignments];
        }

        $learnerspercourse = $noofusers + 1;
        $totalassignments = $noofcourses * $noofassignments;
        $totalsubmissions = $totalassignments * $learnerspercourse;
        $totallearners = $learnerspercourse * $noofcourses;
        $this->expected_counts($totalassignments, $totalsubmissions, $totallearners);

        return [$targetuser, $courseassignments];
    }


    /**
     * Tests the count, purge and export functions for the given context.
     *
     * @param \stdClass $env test environment comprising the following fields:
     *        - \context context: test context
     *        - \totara_userdata\userdata\target_user purgeduser: the user to be
     *          be removed.
     *        - int purgecount: no of assignments the purged user should have
     *          *before* removal.
     *        - int finalsubmissioncount expected no of submissions after a user
     *          has been removed. NB: the purged user may still have submissions
     *          after removal because what is removed depends on the context in
     *          force.
     *        - array courses: list of (course, category, assignments) tuples
     *          returned from $this->generate().
     *        - int learnerspercourse: no of learners per course.
     */
    private function purge_count_export_test(\stdClass $env) {
        $assignmentnames = [];
        foreach ($env->courses as $tuple) {
            list($course, $category, $assignments) = $tuple;

            foreach ($assignments as $assignment) {
                $assignmentnames[] = $assignment->get_instance()->name;
            }
        }

        $count = singleassignments::execute_count($env->purgeduser, $env->context);
        $this->assertSame($env->purgecount, $count, "wrong count before purge");

        $exported = singleassignments::execute_export($env->purgeduser, $env->context);
        $this->assertCount($env->purgecount*2, $exported->files, "wrong exported data count"); // 1 submission_file, 1 submission_onlinetxt
        $this->assertCount($env->purgecount, $exported->data, "wrong exported data count");

        foreach ($exported->data as $data) {
            $this->assertCount(1, $data['submission text'], "wrong exported online text before purge");
            $this->assertCount(1, $data['comments'], "wrong exported comments before purge");
            $this->assertCount(2, $data['files'], "wrong exported files before purge"); // 1 submission_file, 1 submission_onlinetxt
            $this->assertCount(1, $data['attempts'], "wrong exported grades before purge");
            $this->assertContains($data['assignment'], $assignmentnames, "unknown exported assignment name before purge");

            foreach ($data['attempts'] as $attempt) {
                $this->assertCount(2, $attempt['advanced_guide_fillings'], "wrong exported grades before purge");
                $this->assertCount(2, $attempt['advanced_rubric_fillings'], "wrong exported grades before purge");
            }
        }

        singleassignments::execute_purge($env->purgeduser, $env->context);
        $totalassignments = count($assignmentnames); // because only user submissions are removed. not the assignment.
        $totallearners = count($env->courses) * $env->learnerspercourse;
        $this->expected_counts($totalassignments, $env->finalsubmissioncount, $totallearners);

        $count = singleassignments::execute_count($env->purgeduser, $env->context);
        $this->assertSame(0, $count, "wrong count after purge");

        $exported =  singleassignments::execute_export($env->purgeduser, $env->context);
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
        $assignmentspercourse = 2;
        $learnerspercourse = 4;
        list($purgeduser, $courses) = $this->generate($noofcourses, $assignmentspercourse, $learnerspercourse-1);

        // Unfortunately, the mod assign public APIs are very UI centric. Which
        // means test data generation will fail when the module is hidden in the
        // UI. So data generation has to be done with a visible module, then the
        // module hidden just before the tests are carried out.
        $DB->set_field('modules', 'visible', '0', ['name' => 'assign']);

        $purgecount = $noofcourses * $assignmentspercourse; // ie all the user's assignment in all courses.
        $finalsubmissioncount = $noofcourses * $assignmentspercourse * ($learnerspercourse - 1); // ie everybody else's submissions.

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
        $assignmentspercourse = 1;
        $learnerspercourse = 3;
        list($purgeduser, $courses) = $this->generate($noofcourses, $assignmentspercourse, $learnerspercourse-1);

        $purgecount = $assignmentspercourse; // ie all that user's assignments in a specific course since 1 course == 1 category
        $totalsubmissions = $noofcourses * $assignmentspercourse * $learnerspercourse;
        $finalsubmissioncount = $totalsubmissions - $purgecount;  // ie all submissions less the ones for a specific course.

        list($course, $category, $assignments) = $courses[$noofcourses-1];
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
        $assignmentspercourse = 1;
        $learnerspercourse = 4;
        list($purgeduser, $courses) = $this->generate($noofcourses, $assignmentspercourse, $learnerspercourse-1);

        $purgecount = $assignmentspercourse; // ie all that user's assignments in a specific course
        $totalsubmissions = $noofcourses * $assignmentspercourse * $learnerspercourse;
        $finalsubmissioncount = $totalsubmissions - $purgecount; // ie all submissions less the ones for a specific course.

        list($course, $category, $assignments) = $courses[0];
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
        $assignmentspercourse = 3;
        $learnerspercourse = 3;
        list($purgeduser, $courses) = $this->generate($noofcourses, $assignmentspercourse, $learnerspercourse-1);

        $purgecount = 1; // ie remove specific assignment.
        $totalsubmissions = $noofcourses * $assignmentspercourse * $learnerspercourse;
        $finalsubmissioncount = $totalsubmissions - $purgecount;

        list($course, $category, $assignments) = $courses[$noofcourses-1];
        $assignment = $assignments[0];
        $cm = get_coursemodule_from_instance('assign', $assignment->get_instance()->id, $course->id);

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

    /**
     * Generate data for advanced grading (when in use)
     *
     * @param int $id Assignment submission grade id (who knows what is that?)
     * @return array of created objects
     */
    protected function generate_advanced_grading_data($id) {
        // It's right to use grading API for all these, but it's kind of sloppy and inconsistent,
        // So it's easier to create the required entries manually.
        $rubric = $this->create_rubric_definition();
        $guide = $this->create_guide_definition();

        // Create guide criteria
        $guidecriteria = [
            $this->create_guide_criterion($guide),
            $this->create_guide_criterion($guide),
        ];

        // Create rubric criteria
        $rubriccriteria = [
            $this->create_rubric_criterion($rubric),
            $this->create_rubric_criterion($rubric),
        ];

        // Create advanced grading instance
        $rubricinstance = $this->create_grading_instance($rubric, $id);
        $guideinstance = $this->create_grading_instance($guide, $id);

        // Create fillings
        return [
            'guide' => [
                $this->create_guide_filling($guidecriteria[0], $guideinstance),
                $this->create_guide_filling($guidecriteria[1], $guideinstance),
            ],
            'rubric' => [
                $this->create_rubric_filling($rubriccriteria[0]->levels[0], $rubricinstance, ['criterionid' => $rubriccriteria[0]->id]),
                $this->create_rubric_filling($rubriccriteria[1]->levels[1], $rubricinstance, ['criterionid' => $rubriccriteria[1]->id]),
            ],
        ];
    }

    /**
     * Create advanced grading guide definition
     *
     * @param array $attributes data attributes
     * @return stdClass Created definition
     */
    protected function create_guide_definition(array $attributes = []): \stdClass {
        global $DB;

        $default = [
            'name' => 'Guide',
            'description' => 'Advanced grading guide definition',
        ];

        $attributes = array_merge($default, $attributes);
        $attributes['method'] = 'guide';

        return (object) array_merge($attributes, [
            'id' => $DB->insert_record('grading_definitions', $this->default_definition_attributes($attributes))
        ]);
    }

    /**
     * Create advanced grading rubric definition
     *
     * @param array $attributes
     * @return stdClass Created definition
     */
    protected function create_rubric_definition(array $attributes = []): \stdClass {
        global $DB;

        $default = [
            'name' => 'Rubric',
            'description' => 'Advanced grading rubric definition',
        ];

        $attributes = array_merge($default, $attributes);
        $attributes['method'] = 'rubric';

        return (object) array_merge($attributes, [
            'id' => $DB->insert_record('grading_definitions', $this->default_definition_attributes($attributes))
        ]);
    }

    /**
     * Create advanced grading guide criterion
     *
     * @param \stdClass|int $definition Advanced grading guide definition instance or id
     * @param array $attributes data attributes
     * @return \stdClass
     */
    protected function create_guide_criterion($definition, array $attributes = []): \stdClass {
        global $DB;

        if ($definition instanceof \stdClass) {
            $definition = $definition->id;
        }

        $attributes = array_merge([
            'definitionid' => $definition,
            'shortname' => 'New guide criterion',
            'description' => 'Description',
            'descriptionformat' => 0,
            'descriptionmarkers' => 'Description markers',
            'descriptionmarkersformat' => 0,
            'maxscore' => rand(25, 75),
        ], $attributes);

        if (!isset($attributes['sortorder'])) {
            $attributes['sortorder'] = $DB->count_records('gradingform_guide_criteria', ['definitionid' => $attributes['definitionid']]) + 1;
        }

        return (object) array_merge($attributes, [
            'id' => $DB->insert_record('gradingform_guide_criteria', $attributes)
        ]);
    }

    /**
     * Create advanced grading rubric criterion
     *
     * @param \stdClass|int $definition Advanced grading guide definition instance or id
     * @param array $attributes data attributes
     * @param int $levels Number of advanced grading rubric criterion levels to create
     * @return \stdClass
     */
    protected function create_rubric_criterion($definition, array $attributes = [], int $levels = 2): \stdClass {
        global $DB;

        if ($definition instanceof \stdClass) {
            $definition = $definition->id;
        }

        $attributes = array_merge([
            'definitionid' => $definition,
            'description' => 'Rubric description',
            'descriptionformat' => 0,
        ], $attributes);

        if (!isset($attributes['sortorder'])) {
            $attributes['sortorder'] = $DB->count_records('gradingform_rubric_criteria', ['definitionid' => $attributes['definitionid']]) + 1;
        }

        $id = $DB->insert_record('gradingform_rubric_criteria', $attributes);

        if ($levels) {
            $attributes['levels'] = [];

            for ($i = 0; $i < $levels; $i++) {
                $attributes['levels'][] = $this->create_rubric_level($id);
            }
        }

        return (object) array_merge($attributes, [
            'id' => $id,
        ]);
    }

    /**
     * Create advanced grading rubric criterion level
     *
     * @param \stdClass|int $criterion Advanced grading rubric criterion instance or ID
     * @param array $attributes Attributes to override defaults
     * @return stdClass created rubric level object
     */
    protected function create_rubric_level($criterion, array $attributes = []): \stdClass {
        global $DB;
        if ($criterion instanceof \stdClass) {
            $criterion = $criterion->id;
        }

        $attributes = array_merge([
            'criterionid' => $criterion,
            'score' => rand(5,25),
            'definition' => 'Rubric level definition',
            'definitionformat' => 0
        ], $attributes);

        return (object) array_merge($attributes, [
            'id' => $DB->insert_record('gradingform_rubric_levels', $attributes)
        ]);
    }

    /**
     * Create advanced grading guide criterion filling
     *
     * @param \stdClass|int $criterion Advanced grading criterion instance id
     * @param \stdClass|int $instance Advanced grading instance or id
     * @param array $attributes Attributes to override defaults
     * @return stdClass created filling
     */
    protected function create_guide_filling($criterion, $instance, array $attributes = []): \stdClass {
        global $DB;

        if ($criterion instanceof \stdClass) {
            $criterion = $criterion->id;
        }
        if ($instance instanceof \stdClass) {
            $instance = $instance->id;
        }

        $attributes = array_merge([
            'criterionid' => $criterion,
            'instanceid' => $instance,
            'remark' => 'Remark',
            'remarkformat' => 0,
            'score' => 10
        ], $attributes);

        return (object) array_merge($attributes, [
            'id' => $DB->insert_record('gradingform_guide_fillings', $attributes)
        ]);
    }

    /**
     * Create advanced grading guide criterion filling applying a certain level
     *
     * @param \stdClass|int $level Advanced grading criterion instance id
     * @param \stdClass|int $instance Advanced grading instance or id
     * @param array $attributes Attributes to override defaults
     * @return stdClass created filling
     */
    protected function create_rubric_filling($level, $instance, array $attributes = []): \stdClass {
        global $DB;

        if ($level instanceof \stdClass) {
            $level = $level->id;
        }
        if ($instance instanceof \stdClass) {
            $instance = $instance->id;
        }

        $attributes = array_merge([
            'instanceid' => $instance,
            'levelid' => $level,
            'remark' => 'Remark',
            'remarkformat' => 0,
        ], $attributes);

        if (!isset($attributes['criterionid'])) {
            $attributes['criterionid'] = $DB->get_field('gradingform_rubric_levels',
                'criterionid', ['id' => $attributes['levelid']], MUST_EXIST);
        }

        return (object) array_merge($attributes, [
            'id' => $DB->insert_record('gradingform_rubric_fillings', $attributes)
        ]);
    }

    /**
     * Create advanced grading instance
     *
     * @param \stdClass|int $definition Advanced grading definition instance or ID
     * @param \stdClass|int $item Assignment grade record (that what links user id with the feedback
     * @param array $attributes Custom attributes to override
     * @return stdClass Created instance
     */
    protected function create_grading_instance($definition, $item, array $attributes = []): \stdClass {
        global $DB;

        if ($definition instanceof \stdClass) {
            $definition = $definition->id;
        }

        if ($item instanceof \stdClass) {
            $item = $item->id;
        }

        if (!isset($attributes['definitionid'])) {
            $attributes['definitionid'] = $definition;
        }

        if (!isset($attributes['itemid'])) {
            $attributes['itemid'] = $item;
        }

        return (object) array_merge($attributes, [
            'id' => $DB->insert_record('grading_instances', $this->default_instance_attributes($attributes))
        ]);
    }

    /**
     * Return default advanced grading definition attributes
     *
     * @param array $attributes Custom attributes to be merged with (will override default)
     * @return array
     */
    protected function default_definition_attributes(array $attributes): array {
        global $DB,$USER;

        // Hijack user
        $currentuser = $USER;
        $this->setAdminUser();
        $user = $USER;

        // Return user
        $this->setUser($currentuser);

        return array_merge([
            'areaid' => $DB->count_records('grading_definitions') + 1,
            'name' => 'Advanced grading definition',
            'description' => 'Advanced grading definition description',
            'descriptionformat' => 1,
            'status' => 20,
            'copiedfrom' => null,
            'timecreated' => time(),
            'usercreated' => $user->id,
            'timemodified' => time(),
            'usermodified' => $user->id,
            'timecopied' => 0,
            'options' => json_encode(["alwaysshowdefinition" => 1,"showmarkspercriterionstudents" => 1])
        ], $attributes);
    }

    /**
     * Return default advanced grading instance attributes
     *
     * @param array $attributes Custom attributes to be merged with (will override default)
     * @return array
     */
    protected function default_instance_attributes(array $attributes = []): array {
        global $USER;

        // Hijack user
        $currentuser = $USER;
        $this->setAdminUser();
        $user = $USER;

        // Return user
        $this->setUser($currentuser);

        return array_merge([
            //'definitionid' => Must be set,
            'raterid' => $user->id,
            //'itemid' => Must be set
            'rawgrade' => null,
            'status' => 2,
            'feedback' => 'Feedback, don\t think it is used though',
            'feedbackformat' => 0,
            'timemodified' => time(),
        ], $attributes);
    }
}
