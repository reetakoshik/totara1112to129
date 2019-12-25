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
 * @author Brendan Cox <brendan.cox@totaralearning.com>
 * @package mod_workshop
 */

defined('MOODLE_INTERNAL') || die();

use totara_userdata\userdata\target_user;
use mod_workshop\userdata\assessments;

/**
 * Class mod_workshop_userdata_assessments_testcase
 *
 * @group totara_userdata
 */
class mod_workshop_userdata_assessments_testcase extends advanced_testcase {

    private function create_submission_with_assessment_and_grades($workshop, $submittinguser, $assessors) {
        /* @var mod_workshop_generator $workshop_generator */
        $workshop_generator = $this->getDataGenerator()->get_plugin_generator('mod_workshop');

        $dimensionid = $workshop_generator->get_accumulative_dimensionid($workshop);

        $submissionid = $workshop_generator->create_submission($workshop->id, $submittinguser->id);

        $filestorage = get_file_storage();

        $subcontentfile = [
            'contextid' => context_module::instance($workshop->cmid)->id,
            'component' => 'mod_workshop',
            'filearea' => 'submission_content',
            'itemid' => $submissionid,
            'filepath' => '/',
            'filename' => $submittinguser->username . '_subcontent_' . $workshop->name
        ];
        $filestorage->create_file_from_string($subcontentfile, 'content file contents');

        $subattachmentfile = [
            'contextid' => context_module::instance($workshop->cmid)->id,
            'component' => 'mod_workshop',
            'filearea' => 'submission_attachment',
            'itemid' => $submissionid,
            'filepath' => '/',
            'filename' => $submittinguser->username . '_subattachment_' . $workshop->name
        ];
        $filestorage->create_file_from_string($subattachmentfile, 'attachment file contents');

        foreach($assessors as $assessor) {
            $assessmentid = $workshop_generator->create_assessment(
                $submissionid,
                $assessor->id,
                ['feedbackauthor' => 'Assessed by ' . $assessor->username . ' for ' . $workshop->name]
            );

            $contentfile = [
                'contextid' => context_module::instance($workshop->cmid)->id,
                'component' => 'mod_workshop',
                'filearea' => 'overallfeedback_content',
                'itemid' => $assessmentid,
                'filepath' => '/',
                'filename' => $assessor->username . '_content_' . $workshop->name
            ];
            $filestorage->create_file_from_string($contentfile, 'content file contents');

            $attachmentfile = [
                'contextid' => context_module::instance($workshop->cmid)->id,
                'component' => 'mod_workshop',
                'filearea' => 'overallfeedback_attachment',
                'itemid' => $assessmentid,
                'filepath' => '/',
                'filename' => $assessor->username . '_attachment_' . $workshop->name
            ];
            $filestorage->create_file_from_string($attachmentfile, 'attachment file contents');

            $workshop_generator->create_grade(
                $assessmentid,
                $dimensionid,
                rand(0, 10),
                'Graded by ' . $assessor->username . ' for ' . $workshop->name
            );
        }
    }

    public function create_test_data() {
        global $DB;

        // To load a \workshop instance, the workshop module must be visible, which it isn't by default.
        // This why we have a separate test to make sure that at least purging works without the module being visible.
        $DB->set_field('modules', 'visible', '1', ['name' => 'workshop']);

        $this->setAdminUser();

        $user1 = $this->getDataGenerator()->create_user(['username' => 'user1']);
        $user2 = $this->getDataGenerator()->create_user(['username' => 'user2']);

        $allusers = [
            $user1,
            $user2,
            $this->getDataGenerator()->create_user(['username' => 'assessoronly'])
        ];

        $category1 = $this->getDataGenerator()->create_category();
        $category2 = $this->getDataGenerator()->create_category();

        $course1 = $this->getDataGenerator()->create_course(['category' => $category1->id]);
        $course2 = $this->getDataGenerator()->create_course(['category' => $category2->id]);
        $course3 = $this->getDataGenerator()->create_course(['category' => $category2->id]);

        /* @var mod_workshop_generator $workshop_generator */
        $workshop_generator = $this->getDataGenerator()->get_plugin_generator('mod_workshop');

        $workshop1 = $workshop_generator->create_instance(['course' => $course1, 'name' => 'workshop1']);
        $workshop2 = $workshop_generator->create_instance(['course' => $course2, 'name' => 'workshop2']);
        $workshop3 = $workshop_generator->create_instance(['course' => $course3, 'name' => 'workshop3']);
        $workshop4 = $workshop_generator->create_instance(['course' => $course3, 'name' => 'workshop4']);

        // Each user can only have one submission per workshop.

        $this->create_submission_with_assessment_and_grades($workshop1, $user1, $allusers);
        $this->create_submission_with_assessment_and_grades($workshop1, $user2, $allusers);

        $this->create_submission_with_assessment_and_grades($workshop2, $user1, $allusers);
        $this->create_submission_with_assessment_and_grades($workshop2, $user2, $allusers);

        $this->create_submission_with_assessment_and_grades($workshop3, $user1, $allusers);
        $this->create_submission_with_assessment_and_grades($workshop3, $user2, $allusers);

        $this->create_submission_with_assessment_and_grades($workshop4, $user1, $allusers);
        $this->create_submission_with_assessment_and_grades($workshop4, $user2, $allusers);


        $plugindefaults = get_config('workshopeval_best');
        $evalsettings = new stdClass();
        $evalsettings->comparison = $plugindefaults->comparison;

        // Generate the grade aggregations.
        $workshop1instance = new \workshop($workshop1, (object)['id' => $workshop1->cmid], $course1);
        $workshop1instance->grading_evaluation_instance()->update_grading_grades($evalsettings);
        $workshop1instance->aggregate_submission_grades();
        $workshop1instance->aggregate_grading_grades();

        $workshop2instance = new \workshop($workshop2, (object)['id' => $workshop2->cmid], $course2);
        $workshop2instance->grading_evaluation_instance()->update_grading_grades($evalsettings);
        $workshop2instance->aggregate_submission_grades();
        $workshop2instance->aggregate_grading_grades();

        $workshop3instance = new \workshop($workshop3, (object)['id' => $workshop3->cmid], $course3);
        $workshop3instance->grading_evaluation_instance()->update_grading_grades($evalsettings);
        $workshop3instance->aggregate_submission_grades();
        $workshop3instance->aggregate_grading_grades();

        $workshop4instance = new \workshop($workshop4, (object)['id' => $workshop4->cmid], $course3);
        $workshop4instance->grading_evaluation_instance()->update_grading_grades($evalsettings);
        $workshop4instance->aggregate_submission_grades();
        $workshop4instance->aggregate_grading_grades();

        return [
            'user1' => $user1,
            'user2' => $user2,
            'category1' => $category1,
            'category2' => $category2,
            'course1' => $course1,
            'course2' => $course2,
            'course3' => $course3,
            'workshop1' => $workshop1,
            'workshop2' => $workshop2,
            'workshop3' => $workshop3,
            'workshop4' => $workshop4
        ];
    }

    /**
     * Following a purge, we've checked that the correct assessments exist.
     *
     * From there, we just need to confirm that only files exist that relate to a given set of assessment ids.
     *
     * Assertions are done internally within this method. It will fail the test itself if anything is wrong.
     *
     * @param array $assessmentids
     */
    private function assert_correct_files_exist($assessmentids) {
        global $DB;

        // There should be no files in the used for assessment feedback that have an item id that is not a remaining assessment id.
        list($notinsql, $notinparams) = $DB->get_in_or_equal($assessmentids, SQL_PARAMS_NAMED, 'param', false);
        $contentfilecount = $DB->count_records_select(
            'files',
            'filearea = :filearea AND itemid ' . $notinsql,
            array_merge(['filearea' => 'overallfeedback_content'], $notinparams));
        $this->assertEquals(0, $contentfilecount);

        $attachmentfilecount = $DB->count_records_select(
            'files',
            'filearea = :filearea AND itemid ' . $notinsql,
            array_merge(['filearea' => 'overallfeedback_attachment'], $notinparams));
        $this->assertEquals(0, $attachmentfilecount);

        // For assessments that do remain, there will be a file each area, plus a directory entry, this number should remain.
        list($isinsql, $isinparams) = $DB->get_in_or_equal($assessmentids, SQL_PARAMS_NAMED);
        $contentfilecount = $DB->count_records_select(
            'files',
            'filearea = :filearea AND itemid ' . $isinsql,
            array_merge(['filearea' => 'overallfeedback_content'], $isinparams));
        $this->assertEquals(count($assessmentids) * 2, $contentfilecount);

        $attachmentfilecount = $DB->count_records_select(
            'files',
            'filearea = :filearea AND itemid ' . $isinsql,
            array_merge(['filearea' => 'overallfeedback_attachment'], $isinparams));
        $this->assertEquals(count($assessmentids) * 2, $attachmentfilecount);

        // Files linked to the submissions were untouched, 1 file and 1 directory entry per file area per submission.
        $submissionfilecount = $DB->count_records_select(
            'files',
            'filearea = :subcontent OR filearea = :subattachment',
            ['subcontent' => 'submission_content', 'subattachment' => 'submission_attachment']
        );
        $this->assertEquals(32, $submissionfilecount);
    }

    public function test_export_system_context() {
        $data = $this->create_test_data();

        $export = assessments::execute_export(new target_user($data['user1']), context_system::instance());

        $expectedcontent = [
            'Assessed by user1 for workshop1' => 'Graded by user1 for workshop1',
            'Assessed by user1 for workshop2' => 'Graded by user1 for workshop2',
            'Assessed by user1 for workshop3' => 'Graded by user1 for workshop3',
            'Assessed by user1 for workshop4' => 'Graded by user1 for workshop4',
        ];

        $this->assertCount(8, $export->data['assessments']);
        $this->assertCount(4, $export->data['aggregations']);

        foreach ($export->data['assessments'] as $assessment) {
            $this->assertArrayHasKey($assessment->feedbackauthor, $expectedcontent);
            $this->assertCount(1, $assessment->grades);
            $this->assertEquals($expectedcontent[$assessment->feedbackauthor], reset($assessment->grades)->peercomment);
            $this->assertCount(2, $assessment->files['/']);
            foreach ($assessment->files['/'] as $filedata) {
                $this->assertStringStartsWith($data['user1']->username, $filedata['filename']);
            }
        }

        $this->assertCount(16, $export->files);
        foreach ($export->files as $file) {
            $this->assertStringStartsWith($data['user1']->username, $file->get_filename());
        }
    }

    public function test_count_system_context() {
        $data = $this->create_test_data();

        $count = assessments::execute_count(new target_user($data['user1']), context_system::instance());

        $this->assertEquals(8, $count);

        return $data;
    }

    public function test_purge_system_context() {
        global $DB;

        $data = $this->create_test_data();

        assessments::execute_purge(new target_user($data['user1']), context_system::instance());

        // No submissions were purged, including those belong to user1.
        $this->assertEquals(8, $DB->count_records('workshop_submissions'));
        $this->assertEquals(4, $DB->count_records('workshop_submissions', ['authorid' => $data['user1']->id]));

        // User1 should have no assessments remaining in the system.
        $this->assertEquals(0, $DB->count_records('workshop_assessments', ['reviewerid' => $data['user1']->id]));

        $assessments = $DB->get_records('workshop_assessments');
        $this->assertCount(16, $assessments);

        // The remaining assessments should always be linked to remaining submissions.
        $remainingsubmissionids = $DB->get_fieldset_select('workshop_submissions', 'id', '1=1');
        foreach ($assessments as $assessment) {
            $this->assertContains($assessment->submissionid, $remainingsubmissionids);
        }

        $grades = $DB->get_records('workshop_grades');
        $this->assertCount(16, $grades);
        $assessmentids = array_keys($assessments);
        foreach ($grades as $grade) {
            $this->assertContains($grade->assessmentid, $assessmentids);
        }

        $this->assertEquals(8, $DB->count_records('workshop_aggregations'));
        $this->assertEquals(0, $DB->count_records('workshop_aggregations', ['userid' => $data['user1']->id]));

        $this->assert_correct_files_exist($assessmentids);
    }

    public function test_export_coursecat_context() {
        $data = $this->create_test_data();

        $export = assessments::execute_export(new target_user($data['user1']), context_coursecat::instance($data['category2']->id));

        $expectedcontent = [
            'Assessed by user1 for workshop2' => 'Graded by user1 for workshop2',
            'Assessed by user1 for workshop3' => 'Graded by user1 for workshop3',
            'Assessed by user1 for workshop4' => 'Graded by user1 for workshop4',
        ];

        $this->assertCount(6, $export->data['assessments']);
        $this->assertCount(3, $export->data['aggregations']);

        foreach ($export->data['assessments'] as $assessment) {
            $this->assertArrayHasKey($assessment->feedbackauthor, $expectedcontent);
            $this->assertCount(1, $assessment->grades);
            $this->assertEquals($expectedcontent[$assessment->feedbackauthor], reset($assessment->grades)->peercomment);
            $this->assertCount(2, $assessment->files['/']);
            foreach ($assessment->files['/'] as $filedata) {
                $this->assertStringStartsWith($data['user1']->username, $filedata['filename']);
            }
        }

        $this->assertCount(12, $export->files);
        foreach ($export->files as $file) {
            $this->assertStringStartsWith($data['user1']->username, $file->get_filename());
            $this->assertStringEndsNotWith('workshop1', $file->get_filename());
        }
    }

    public function test_count_coursecat_context() {
        $data = $this->create_test_data();

        $count = assessments::execute_count(new target_user($data['user1']), context_coursecat::instance($data['category2']->id));

        $this->assertEquals(6, $count);
    }

    public function test_purge_coursecat_context() {
        global $DB;

        $data = $this->create_test_data();

        assessments::execute_purge(new target_user($data['user1']), context_coursecat::instance($data['category2']->id));

        $this->assertEquals(8, $DB->count_records('workshop_submissions'));
        $this->assertEquals(4, $DB->count_records('workshop_submissions', ['authorid' => $data['user1']->id]));

        $this->assertEquals(2, $DB->count_records('workshop_assessments', ['reviewerid' => $data['user1']->id]));

        $assessments = $DB->get_records('workshop_assessments');
        $this->assertCount(18, $assessments);

        // The remaining assessments should always be linked to remaining submissions.
        $remainingsubmissionids = $DB->get_fieldset_select('workshop_submissions', 'id', '1=1');
        foreach ($assessments as $assessment) {
            $this->assertContains($assessment->submissionid, $remainingsubmissionids);
        }

        $grades = $DB->get_records('workshop_grades');
        $this->assertCount(18, $grades);
        $assessmentids = array_keys($assessments);
        foreach ($grades as $grade) {
            $this->assertContains($grade->assessmentid, $assessmentids);
        }

        $this->assertEquals(9, $DB->count_records('workshop_aggregations'));
        $this->assertEquals(1, $DB->count_records('workshop_aggregations', ['userid' => $data['user1']->id]));

        $this->assert_correct_files_exist($assessmentids);
    }

    public function test_export_course_context() {
        $data = $this->create_test_data();

        $export = assessments::execute_export(new target_user($data['user1']), context_course::instance($data['course3']->id));

        $expectedcontent = [
            'Assessed by user1 for workshop3' => 'Graded by user1 for workshop3',
            'Assessed by user1 for workshop4' => 'Graded by user1 for workshop4',
        ];

        $this->assertCount(4, $export->data['assessments']);
        $this->assertCount(2, $export->data['aggregations']);

        foreach ($export->data['assessments'] as $assessment) {
            $this->assertArrayHasKey($assessment->feedbackauthor, $expectedcontent);
            $this->assertCount(1, $assessment->grades);
            $this->assertEquals($expectedcontent[$assessment->feedbackauthor], reset($assessment->grades)->peercomment);
            $this->assertCount(2, $assessment->files['/']);
            foreach ($assessment->files['/'] as $filedata) {
                $this->assertStringStartsWith($data['user1']->username, $filedata['filename']);
            }
        }

        $this->assertCount(8, $export->files);
        foreach ($export->files as $file) {
            $this->assertStringStartsWith($data['user1']->username, $file->get_filename());
            $this->assertStringEndsNotWith('workshop1', $file->get_filename());
            $this->assertStringEndsNotWith('workshop2', $file->get_filename());
        }
    }

    public function test_count_course_context() {
        $data = $this->create_test_data();

        $count = assessments::execute_count(new target_user($data['user1']), context_course::instance($data['course3']->id));

        $this->assertEquals(4, $count);
    }

    public function test_purge_course_context() {
        global $DB;

        $data = $this->create_test_data();

        assessments::execute_purge(new target_user($data['user1']), context_course::instance($data['course3']->id));

        $this->assertEquals(8, $DB->count_records('workshop_submissions'));
        $this->assertEquals(4, $DB->count_records('workshop_submissions', ['authorid' => $data['user1']->id]));

        $this->assertEquals(4, $DB->count_records('workshop_assessments', ['reviewerid' => $data['user1']->id]));

        $assessments = $DB->get_records('workshop_assessments');
        $this->assertCount(20, $assessments);

        // The remaining assessments should always be linked to remaining submissions.
        $remainingsubmissionids = $DB->get_fieldset_select('workshop_submissions', 'id', '1=1');
        foreach ($assessments as $assessment) {
            $this->assertContains($assessment->submissionid, $remainingsubmissionids);
        }

        $grades = $DB->get_records('workshop_grades');
        $this->assertCount(20, $grades);
        $assessmentids = array_keys($assessments);
        foreach ($grades as $grade) {
            $this->assertContains($grade->assessmentid, $assessmentids);
        }

        $this->assertEquals(10, $DB->count_records('workshop_aggregations'));
        $this->assertEquals(2, $DB->count_records('workshop_aggregations', ['userid' => $data['user1']->id]));

        $this->assert_correct_files_exist($assessmentids);
    }

    public function test_export_module_context() {
        $data = $this->create_test_data();

        $export = assessments::execute_export(new target_user($data['user1']), context_module::instance($data['workshop4']->cmid));

        $expectedcontent = [
            'Assessed by user1 for workshop4' => 'Graded by user1 for workshop4',
        ];

        $this->assertCount(2, $export->data['assessments']);
        $this->assertCount(1, $export->data['aggregations']);

        foreach ($export->data['assessments'] as $assessment) {
            $this->assertArrayHasKey($assessment->feedbackauthor, $expectedcontent);
            $this->assertCount(1, $assessment->grades);
            $this->assertEquals($expectedcontent[$assessment->feedbackauthor], reset($assessment->grades)->peercomment);
            $this->assertCount(2, $assessment->files['/']);
            foreach ($assessment->files['/'] as $filedata) {
                $this->assertStringStartsWith($data['user1']->username, $filedata['filename']);
            }
        }

        $this->assertCount(4, $export->files);
        foreach ($export->files as $file) {
            $this->assertStringStartsWith($data['user1']->username, $file->get_filename());
            $this->assertStringEndsWith('workshop4', $file->get_filename());
        }
    }

    public function test_count_module_context() {
        $data = $this->create_test_data();

        $count = assessments::execute_count(new target_user($data['user1']), context_module::instance($data['workshop4']->cmid));

        $this->assertEquals(2, $count);
    }

    public function test_purge_module_context() {
        global $DB;

        $data = $this->create_test_data();

        assessments::execute_purge(new target_user($data['user1']), context_module::instance($data['workshop4']->cmid));

        $this->assertEquals(8, $DB->count_records('workshop_submissions'));
        $this->assertEquals(4, $DB->count_records('workshop_submissions', ['authorid' => $data['user1']->id]));

        $this->assertEquals(6, $DB->count_records('workshop_assessments', ['reviewerid' => $data['user1']->id]));

        $assessments = $DB->get_records('workshop_assessments');
        $this->assertCount(22, $assessments);

        // The remaining assessments should always be linked to remaining submissions.
        $remainingsubmissionids = $DB->get_fieldset_select('workshop_submissions', 'id', '1=1');
        foreach ($assessments as $assessment) {
            $this->assertContains($assessment->submissionid, $remainingsubmissionids);
        }

        $grades = $DB->get_records('workshop_grades');
        $this->assertCount(22, $grades);
        $assessmentids = array_keys($assessments);
        foreach ($grades as $grade) {
            $this->assertContains($grade->assessmentid, $assessmentids);
        }

        $this->assertEquals(11, $DB->count_records('workshop_aggregations'));
        $this->assertEquals(3, $DB->count_records('workshop_aggregations', ['userid' => $data['user1']->id]));

        $this->assert_correct_files_exist($assessmentids);
    }

    /**
     * Ensures that the purge can still be completed on a deleted user.
     */
    public function test_purge_deleted_user() {
        global $DB;

        $data = $this->create_test_data();

        delete_user($data['user1']);
        $data['user1'] = $DB->get_record('user', ['id' => $data['user1']->id]);

        $this->assertEquals(8, $DB->count_records('workshop_submissions'));
        $this->assertEquals(
            1,
            $DB->count_records('workshop_submissions',
                [
                    'workshopid' => $data['workshop1']->id,
                    'authorid' => $data['user1']->id
                ]
            )
        );
        $this->assertEquals(24, $DB->count_records('workshop_assessments'));
        $this->assertEquals(
            2,
            $DB->count_records_sql(
            'SELECT COUNT(wa.id)
                   FROM {workshop_assessments} wa
                   JOIN {workshop_submissions} ws
                     ON wa.submissionid = ws.id
                  WHERE ws.workshopid = :workshopid
                    AND wa.reviewerid = :reviewerid',
                [
                    'workshopid' => $data['workshop1']->id,
                    'reviewerid' => $data['user1']->id
                ]
            )
        );

        assessments::execute_purge(new target_user($data['user1']), context_module::instance($data['workshop1']->cmid));

        $this->assertEquals(8, $DB->count_records('workshop_submissions'));
        $this->assertEquals(
            1,
            $DB->count_records('workshop_submissions',
                [
                    'workshopid' => $data['workshop1']->id,
                    'authorid' => $data['user1']->id
                ]
            )
        );
        $this->assertEquals(22, $DB->count_records('workshop_assessments'));
        $this->assertEquals(
            0,
            $DB->count_records_sql(
            'SELECT COUNT(wa.id)
                   FROM {workshop_assessments} wa
                   JOIN {workshop_submissions} ws
                     ON wa.submissionid = ws.id
                  WHERE ws.workshopid = :workshopid
                    AND wa.reviewerid = :reviewerid',
                [
                    'workshopid' => $data['workshop1']->id,
                    'reviewerid' => $data['user1']->id
                ]
            )
        );
    }

    /**
     * The workshop module is not visible by default. Whether it is or not should not matter, but we have the added risk
     * that if the class being tested were to try to load an instance of the \workshop class, it may fail while the
     * module is not set to visible due to internal code within the \workshop class.
     */
    public function test_purge_assessments_module_not_visible() {
        global $DB;

        $this->setAdminUser();

        $user = $this->getDataGenerator()->create_user();
        $otheruser = $this->getDataGenerator()->create_user();

        // Create submissions for each user in a range of different activities,
        // courses and categories.

        $course1 = $this->getDataGenerator()->create_course();

        /* @var mod_workshop_generator $workshop_generator */
        $workshop_generator = $this->getDataGenerator()->get_plugin_generator('mod_workshop');

        $workshop = $workshop_generator->create_instance(['course' => $course1]);

        $submissionid = $workshop_generator->create_submission($workshop->id, $user->id);
        $workshop_generator->create_assessment($submissionid, $otheruser->id);
        $submissionid = $workshop_generator->create_submission($workshop->id, $otheruser->id);
        $workshop_generator->create_assessment($submissionid, $user->id);

        $this->assertEquals(2, $DB->count_records('workshop_submissions', ['workshopid' => $workshop->id]));
        $this->assertEquals(2, $DB->count_records('workshop_assessments', []));

        // Workshop is not visible by default, but let's explicitly make it not visible.
        $DB->set_field('modules', 'visible', '0', ['name' => 'workshop']);

        assessments::execute_purge(new target_user($user), context_system::instance());

        $this->assertEquals(2, $DB->count_records('workshop_submissions', ['workshopid' => $workshop->id]));
        $this->assertEquals(1, $DB->count_records('workshop_assessments', []));
    }
}