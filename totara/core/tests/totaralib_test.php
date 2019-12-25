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
 * @author Yuliya Bozhko <yuliya.bozhko@totaralms.com>
 * @package totara_core
 */
if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.'); // It must be included from a Moodle page.
}

global $CFG;
require_once($CFG->dirroot . '/totara/hierarchy/prefix/position/lib.php');
require_once($CFG->dirroot . '/totara/core/lib.php');
require_once($CFG->dirroot . '/completion/cron.php');

class totara_core_totaralib_testcase extends advanced_testcase {
    protected $user, $manager, $teamleader, $appraiser, $invaliduserid = 9999;

    protected function tearDown() {
        $this->user = null;
        $this->manager = null;
        $this->teamleader = null;
        $this->appraiser = null;

        parent::tearDown();
    }

    protected function setUp() {
        parent::setUp();

        $this->user = $this->getDataGenerator()->create_user();
        $this->manager = $this->getDataGenerator()->create_user();
        $this->teamleader = $this->getDataGenerator()->create_user();
        $this->appraiser = $this->getDataGenerator()->create_user();

        $teamleaderja = \totara_job\job_assignment::create_default($this->teamleader->id);
        $managerja = \totara_job\job_assignment::create_default($this->manager->id,
            array('managerjaid' => $teamleaderja->id));
        \totara_job\job_assignment::create_default($this->user->id,
            array('managerjaid' => $managerja->id, 'appraiserid' => $this->appraiser->id));
    }

    public function test_totara_create_icon_picker() {
        $this->resetAfterTest();

        // Test with js.
        $mform = new MoodleQuickForm('mform', 'post', '');
        $picker = totara_create_icon_picker($mform, 'edit', 'course', 'default', 0, '_tst');
        $this->assertArrayHasKey('icon_tst', $picker);
        $this->assertArrayHasKey('currenticon_tst', $picker);
        $this->assertCount(2, $picker);
        $this->assertInstanceOf('MoodleQuickForm_hidden', $picker['icon_tst']);
        $this->assertInstanceOf('MoodleQuickForm_static', $picker['currenticon_tst']);
        // Check for link to choose icon.
        $this->assertFalse(strpos($picker['currenticon_tst']->_text, '<a') === false);

        $mform = new MoodleQuickForm('mform', 'post', '');
        $picker = totara_create_icon_picker($mform, '', 'course', '', 0, '_tst');
        $this->assertArrayHasKey('currenticon_tst', $picker);
        $this->assertCount(1, $picker);
        $this->assertInstanceOf('MoodleQuickForm_static', $picker['currenticon_tst']);
        // No link to choose icon, only preview.
        $this->assertTrue(strpos($picker['currenticon_tst']->_text, '<a') === false);

        // Test with nojs.
        $mform = new MoodleQuickForm('mform', 'post', '');
        $picker = totara_create_icon_picker($mform, 'edit', 'course', '', 1, '_tst');
        $this->assertArrayHasKey('icon_tst', $picker);
        $this->assertArrayHasKey('currenticon_tst', $picker);
        $this->assertCount(2, $picker);
        $this->assertInstanceOf('MoodleQuickForm_select', $picker['icon_tst']);
        $this->assertInstanceOf('MoodleQuickForm_static', $picker['currenticon_tst']);
        $this->assertContainsOnly('array', $picker['icon_tst']->_options);

        $mform = new MoodleQuickForm('mform', 'post', '');
        $picker = totara_create_icon_picker($mform, '', 'course', '', 1, '_tst');
        $this->assertArrayHasKey('currenticon_tst', $picker);
        $this->assertCount(1, $picker);
        $this->assertInstanceOf('MoodleQuickForm_static', $picker['currenticon_tst']);
    }

    public function test_totara_get_sender_from_user_by_id(){
        $this->resetAfterTest();

        $this->assertEquals('admin', totara_get_sender_from_user_by_id(core_user::SUPPORT_USER)->username);
        $this->assertEquals('admin', totara_get_sender_from_user_by_id(0)->username);

        // Below return value not expected. Assertion added for awareness of potential issue.
        $this->assertEquals('admin', totara_get_sender_from_user_by_id('')->username);

        $this->assertEquals('noreply', totara_get_sender_from_user_by_id(core_user::NOREPLY_USER)->username);
        $this->assertEquals('noreply', totara_get_sender_from_user_by_id(\mod_facetoface\facetoface_user::FACETOFACE_USER)->username);

        $user1 = $this->getDataGenerator()->create_user(array('username' => 'testuser1'));
        $user2 = $this->getDataGenerator()->create_user(array('email' => 'testuser2@test.com'));
        $this->assertEquals('testuser1', totara_get_sender_from_user_by_id($user1->id)->username);
        $this->assertEquals('testuser2@test.com', totara_get_sender_from_user_by_id($user2->id)->email);

        // user id -46 can't exist
        $this->assertNotInstanceOf('stdClass', totara_get_sender_from_user_by_id(-46));
        $this->assertFalse(totara_get_sender_from_user_by_id(-46));
    }

    /**
     * Run totara_core_update_module_completion_data() when activity uses manual completion.
     *
     * Note that the previously created manual completion doesn't get restored on cron.
     *
     * This test is to cover current behaviour and to raise an alarm if manual completion simply breaks the function
     * being tested.
     * But if behaviour is intentionally changed so that a manual completion does get restored,
     * that would not necessarily be a bad thing and the test could be updated.
     */
    public function test_totara_core_update_module_completion_data_manual_completion() {
        $this->resetAfterTest(true);
        global $DB;

        set_config('enablecompletion', '1');

        $generator = $this->getDataGenerator();
        $course = $generator->create_course(array('enablecompletion' => 1));
        $learner = $generator->create_user();

        $completion = new completion_info($course);

        /** @var mod_quiz_generator $quizgenerator */
        $quizgenerator = $generator->get_plugin_generator('mod_quiz');
        $quizdata = new stdClass();
        $quizdata->course = $course;
        $quizdata->completion = COMPLETION_TRACKING_MANUAL;
        $quiz = $quizgenerator->create_instance($quizdata);

        $modinfo = get_fast_modinfo($course);
        $cminfo =  $modinfo->instances['quiz'][$quiz->id];

        // Should not be complete yet.
        $this->assertEquals(false, $DB->record_exists('course_modules_completion',
            array('coursemoduleid' => $cminfo->id, 'userid' => $learner->id)));

        $completion->update_state($cminfo, COMPLETION_COMPLETE, $learner->id);

        $this->assertEquals(true, $DB->record_exists('course_modules_completion',
            array('coursemoduleid' => $cminfo->id, 'userid' => $learner->id, 'completionstate' => COMPLETION_COMPLETE)));

        // This object is equivalent to what might be returned from a form using get_data().
        $moduleinfo = new stdClass();
        $moduleinfo->course = $course->id;
        $moduleinfo->coursemodule = $quiz->cmid;
        $moduleinfo->modulename = $quiz->name;
        $moduleinfo->instance = $cminfo->instance;
        $moduleinfo->completionunlocked = 1;
        $moduleinfo->completionunlockednoreset = 0;

        // Clear out any logs that might have been created earlier.
        $DB->delete_records('course_completion_log');

        totara_core_update_module_completion_data($cminfo, $moduleinfo, $course, $completion);

        // Check that some logs were created.
        $this->assertEquals(1, $DB->count_records('course_completion_log'));
        $this->assertEquals(1, $DB->count_records('course_completion_log',
            array('courseid' => $course->id, 'userid' => $learner->id)));

        // With unlock and delete, all activity completions should be set to incomplete.
        $this->assertEquals(true, $DB->record_exists('course_modules_completion',
            array('coursemoduleid' => $cminfo->id, 'userid' => $learner->id, 'completionstate' => COMPLETION_INCOMPLETE)));

        // Clear out any logs that might have been created earlier.
        $DB->delete_records('course_completion_log');

        $this->waitForSecond();
        totara_core_reaggregate_course_modules_completion();

        // Check that some logs were created.
        $this->assertEquals(1, $DB->count_records('course_completion_log'));
        $this->assertEquals(1, $DB->count_records('course_completion_log',
            array('courseid' => $course->id, 'userid' => $learner->id)));

        // MANUAL COMPLETIONS - Cron will not update to complete again.
        $this->assertEquals(true, $DB->record_exists('course_modules_completion',
            array('coursemoduleid' => $cminfo->id, 'userid' => $learner->id, 'completionstate' => COMPLETION_INCOMPLETE)));
    }

    /**
     * Run totara_core_update_module_completion_data() when activity uses completion when viewed.
     *
     * Reaggregation of activity completion is done via totara_core_reaggregate_course_modules_completion().
     */
    public function test_totara_core_update_module_completion_data_viewed_completion() {
        $this->resetAfterTest(true);
        global $DB;

        set_config('enablecompletion', '1');

        $generator = $this->getDataGenerator();
        $course = $generator->create_course(array('enablecompletion' => 1));
        $learner = $generator->create_user();

        $completion = new completion_info($course);

        /** @var mod_quiz_generator $quizgenerator */
        $quizgenerator = $generator->get_plugin_generator('mod_quiz');
        $quizdata = new stdClass();
        $quizdata->course = $course;
        $quizdata->completion = COMPLETION_TRACKING_AUTOMATIC;
        $quizdata->completionview = COMPLETION_VIEW_REQUIRED;
        $quiz = $quizgenerator->create_instance($quizdata);

        $modinfo = get_fast_modinfo($course);
        $cminfo =  $modinfo->instances['quiz'][$quiz->id];

        // Should not be complete yet.
        $this->assertEquals(false, $DB->record_exists('course_modules_completion',
            array('coursemoduleid' => $cminfo->id, 'userid' => $learner->id)));

        $completion->set_module_viewed($cminfo, $learner->id);

        $this->assertEquals(true, $DB->record_exists('course_modules_completion',
            array('coursemoduleid' => $cminfo->id, 'userid' => $learner->id, 'completionstate' => COMPLETION_COMPLETE)));

        // This object is equivalent to what might be returned from a form using $mform->get_data().
        $moduleinfo = new stdClass();
        $moduleinfo->course = $course->id;
        $moduleinfo->coursemodule = $quiz->cmid;
        $moduleinfo->modulename = $quiz->name;
        $moduleinfo->instance = $cminfo->instance;
        $moduleinfo->completionunlocked = 1;
        $moduleinfo->completionunlockednoreset = 0;

        // Clear out any logs that might have been created earlier.
        $DB->delete_records('course_completion_log');

        totara_core_update_module_completion_data($cminfo, $moduleinfo, $course, $completion);

        // Check that some logs were created.
        $this->assertEquals(1, $DB->count_records('course_completion_log'));
        $this->assertEquals(1, $DB->count_records('course_completion_log',
            array('courseid' => $course->id, 'userid' => $learner->id)));

        // With unlock and delete, all activity completions should be set to incomplete.
        $this->assertEquals(true, $DB->record_exists('course_modules_completion',
            array('coursemoduleid' => $cminfo->id, 'userid' => $learner->id, 'completionstate' => COMPLETION_INCOMPLETE)));

        // Clear out any logs that might have been created earlier.
        $DB->delete_records('course_completion_log');

        $this->waitForSecond();
        totara_core_reaggregate_course_modules_completion();

        // Check that some logs were created.
        $this->assertEquals(1, $DB->count_records('course_completion_log'));
        $this->assertEquals(1, $DB->count_records('course_completion_log',
            array('courseid' => $course->id, 'userid' => $learner->id)));

        // VIEWED COMPLETIONS - should be reaggregated to complete again.
        $this->assertEquals(true, $DB->record_exists('course_modules_completion',
            array('coursemoduleid' => $cminfo->id, 'userid' => $learner->id, 'completionstate' => COMPLETION_COMPLETE)));
    }

    /**
     * Run totara_core_update_module_completion_data() when there are values other than those for unlock and delete.
     *
     * If completion is not being unlocked, or if it is but they have opted to not reset data, then the function
     * should not delete that data.
     *
     * If completion is not enabled, data should not be deleted either.
     */
    public function test_totara_core_update_module_completion_data_unlock_delete_only() {
        $this->resetAfterTest(true);
        global $DB;

        set_config('enablecompletion', '1');

        $generator = $this->getDataGenerator();
        $course = $generator->create_course(array('enablecompletion' => 1));
        $learner = $generator->create_user();

        $completion = new completion_info($course);

        /** @var mod_quiz_generator $quizgenerator */
        $quizgenerator = $generator->get_plugin_generator('mod_quiz');
        $quizdata = new stdClass();
        $quizdata->course = $course;
        $quizdata->completion = COMPLETION_TRACKING_AUTOMATIC;
        $quizdata->completionview = COMPLETION_VIEW_REQUIRED;
        $quiz = $quizgenerator->create_instance($quizdata);

        $modinfo = get_fast_modinfo($course);
        $cminfo =  $modinfo->instances['quiz'][$quiz->id];

        // Should not be complete yet.
        $this->assertEquals(false, $DB->record_exists('course_modules_completion',
            array('coursemoduleid' => $cminfo->id, 'userid' => $learner->id)));

        $completion->set_module_viewed($cminfo, $learner->id);

        $this->assertEquals(true, $DB->record_exists('course_modules_completion',
            array('coursemoduleid' => $cminfo->id, 'userid' => $learner->id, 'completionstate' => COMPLETION_COMPLETE)));

        // This object is equivalent to what might be returned from a form using $mform->get_data().
        $moduleinfo = new stdClass();
        $moduleinfo->course = $course->id;
        $moduleinfo->coursemodule = $quiz->cmid;
        $moduleinfo->modulename = $quiz->name;
        $moduleinfo->instance = $cminfo->instance;
        $moduleinfo->completionunlocked = 0; // Not unlocking completions.
        $moduleinfo->completionunlockednoreset = 0;

        totara_core_update_module_completion_data($cminfo, $moduleinfo, $course, $completion);

        // No completions were unlocked, previous completion data should still be there.
        $this->assertEquals(true, $DB->record_exists('course_modules_completion',
            array('coursemoduleid' => $cminfo->id, 'userid' => $learner->id, 'completionstate' => COMPLETION_COMPLETE)));

        $moduleinfo = new stdClass();
        $moduleinfo->course = $course->id;
        $moduleinfo->coursemodule = $quiz->cmid;
        $moduleinfo->modulename = $quiz->name;
        $moduleinfo->instance = $cminfo->instance;
        $moduleinfo->completionunlocked = 1; // Unlocking completions.
        $moduleinfo->completionunlockednoreset = 1; // But we've requested no reset.

        totara_core_update_module_completion_data($cminfo, $moduleinfo, $course, $completion);

        // Completions were unlocked, but we asked for no reset, previous completion data should still be there.
        $this->assertEquals(true, $DB->record_exists('course_modules_completion',
            array('coursemoduleid' => $cminfo->id, 'userid' => $learner->id, 'completionstate' => COMPLETION_COMPLETE)));

        $moduleinfo = new stdClass();
        $moduleinfo->course = $course->id;
        $moduleinfo->coursemodule = $quiz->cmid;
        $moduleinfo->modulename = $quiz->name;
        $moduleinfo->instance = $cminfo->instance;
        $moduleinfo->completionunlocked = 1; // Unlocking completions.
        $moduleinfo->completionunlockednoreset = 0; // We've said we do want the reset.

        set_config('enablecompletion', '0'); // We've disabled completion.

        totara_core_update_module_completion_data($cminfo, $moduleinfo, $course, $completion);

        // Completions has been disabled on the site, previous completion data should still be there.
        $this->assertEquals(true, $DB->record_exists('course_modules_completion',
            array('coursemoduleid' => $cminfo->id, 'userid' => $learner->id, 'completionstate' => COMPLETION_COMPLETE)));
    }

    /**
     * Similar to test_totara_core_update_module_completion_data_viewed_completion but we're
     * not using totara_core_update_module_completion_data so that we can test totara_reaggregate_course_modules_completion
     * more specifically.
     */
    public function test_totara_reaggregate_course_modules_completion() {
        $this->resetAfterTest(true);
        global $DB;

        set_config('enablecompletion', '1');

        $generator = $this->getDataGenerator();
        $course = $generator->create_course(array('enablecompletion' => 1));
        $learner = $generator->create_user();

        $completion = new completion_info($course);

        /** @var mod_quiz_generator $quizgenerator */
        $quizgenerator = $generator->get_plugin_generator('mod_quiz');
        $quizdata = new stdClass();
        $quizdata->course = $course;
        $quizdata->completion = COMPLETION_TRACKING_AUTOMATIC;
        $quizdata->completionview = COMPLETION_VIEW_REQUIRED;
        $quiz = $quizgenerator->create_instance($quizdata);

        $modinfo = get_fast_modinfo($course);
        $cminfo =  $modinfo->instances['quiz'][$quiz->id];

        // Completion record should not exist yet.
        $this->assertEquals(false, $DB->record_exists('course_modules_completion',
            array('coursemoduleid' => $cminfo->id, 'userid' => $learner->id)));

        $completion->set_module_viewed($cminfo, $learner->id);

        $this->assertEquals(true, $DB->record_exists('course_modules_completion',
            array('coursemoduleid' => $cminfo->id, 'userid' => $learner->id, 'completionstate' => COMPLETION_COMPLETE)));

        // Now that the activity has been set up. We'll manually create and update records from here while testing
        //  totara_reaggregate_course_modules_completion() in between.

        $modulecompletion = $DB->get_record('course_modules_completion', array('coursemoduleid' => $cminfo->id, 'userid' => $learner->id));

        // Set to incomplete and add reaggregate flag. This is a typical scenario after using totara_uncomplete_course_modules_completion.
        $modulecompletion->completionstate = COMPLETION_INCOMPLETE;
        // Time - 1 means we don't have to sleep for 1 second so that reaggregate timestamp is before now.
        $modulecompletion->reaggregate = time() - 1;
        $DB->update_record('course_modules_completion', $modulecompletion);

        totara_core_reaggregate_course_modules_completion();

        // Viewed was still set to 1, should it should be completed again.
        $this->assertEquals(true, $DB->record_exists('course_modules_completion',
            array('coursemoduleid' => $cminfo->id, 'userid' => $learner->id, 'completionstate' => COMPLETION_COMPLETE)));

        $modulecompletion = $DB->get_record('course_modules_completion', array('coursemoduleid' => $cminfo->id, 'userid' => $learner->id));
        // Let's make sure the reaggregate flag was set to 0  afterwards.
        $this->assertEquals(0, $modulecompletion->reaggregate);

        // Now we'll set it to incomplete, but will leave reaggregate as 0.
        $modulecompletion->completionstate = COMPLETION_INCOMPLETE;
        $DB->update_record('course_modules_completion', $modulecompletion);

        totara_core_reaggregate_course_modules_completion();

        // Viewed was still set to 1, but it was not flagged for reaggregation so should have been ignored.
        // This means it should still be incomplete.
        $this->assertEquals(true, $DB->record_exists('course_modules_completion',
            array('coursemoduleid' => $cminfo->id, 'userid' => $learner->id, 'completionstate' => COMPLETION_INCOMPLETE)));

        // Now to set the reaggregate flag to the future. It should be ignored as it is not due to be
        // reaggregated.
        $future = time() + 2000;
        $modulecompletion->reaggregate = $future;
        $DB->update_record('course_modules_completion', $modulecompletion);

        totara_core_reaggregate_course_modules_completion();

        // Viewed was still set to 1, but it was not flagged for reaggregation so should have been ignored.
        // This means it should still be incomplete.
        $this->assertEquals(true, $DB->record_exists('course_modules_completion',
            array('coursemoduleid' => $cminfo->id, 'userid' => $learner->id, 'completionstate' => COMPLETION_INCOMPLETE)));
        $modulecompletion = $DB->get_record('course_modules_completion', array('coursemoduleid' => $cminfo->id, 'userid' => $learner->id));
        // Let's make sure the reaggregate flag has not been overwritten.
        $this->assertEquals($future, $modulecompletion->reaggregate);

        // This time. We'll set reaggregation for a time further back in the past.
        $modulecompletion->reaggregate = time() - 2000;
        $DB->update_record('course_modules_completion', $modulecompletion);

        totara_core_reaggregate_course_modules_completion();

        // Viewed was still set to 1, should it should be completed again. Reaggregation dates in the past
        // should still be reaggregated as is the case with those set to now.
        $this->assertEquals(true, $DB->record_exists('course_modules_completion',
            array('coursemoduleid' => $cminfo->id, 'userid' => $learner->id, 'completionstate' => COMPLETION_COMPLETE)));
        $modulecompletion = $DB->get_record('course_modules_completion', array('coursemoduleid' => $cminfo->id, 'userid' => $learner->id));
        // Let's make sure the reaggregate flag is now 0.
        $this->assertEquals(0, $modulecompletion->reaggregate);


        // Setting it back to a state where it will be reaggregated again, but we're leaving it as already complete.
        $modulecompletion->reaggregate = time() - 1;
        $DB->update_record('course_modules_completion', $modulecompletion);

        totara_core_reaggregate_course_modules_completion();

        // It should stay as complete. But the reaggregate flag should have been set to 0.
        $this->assertEquals(true, $DB->record_exists('course_modules_completion',
            array('coursemoduleid' => $cminfo->id, 'userid' => $learner->id, 'completionstate' => COMPLETION_COMPLETE)));
        $modulecompletion = $DB->get_record('course_modules_completion', array('coursemoduleid' => $cminfo->id, 'userid' => $learner->id));
        // Let's make sure the reaggregate flag is now 0.
        $this->assertEquals(0, $modulecompletion->reaggregate);


        // Setting it back to a state where it will be reaggregated again.
        $modulecompletion->completionstate = COMPLETION_INCOMPLETE;
        $modulecompletion->reaggregate = time() - 1;
        $DB->update_record('course_modules_completion', $modulecompletion);

        // This time, we'll make sure that the scheduled task that should be calling it does so.
        ob_start();
        $task = new \core\task\completion_regular_task();
        $task->execute();
        ob_end_clean();

        // The results should be the same as calling totara_reaggregate_course_modules_completion() directly.
        $this->assertEquals(true, $DB->record_exists('course_modules_completion',
            array('coursemoduleid' => $cminfo->id, 'userid' => $learner->id, 'completionstate' => COMPLETION_COMPLETE)));
        $modulecompletion = $DB->get_record('course_modules_completion', array('coursemoduleid' => $cminfo->id, 'userid' => $learner->id));
        // Let's make sure the reaggregate flag is now 0.
        $this->assertEquals(0, $modulecompletion->reaggregate);

        // This time we'll set viewed to 0.
        $modulecompletion->completionstate = COMPLETION_INCOMPLETE;
        $modulecompletion->reaggregate = time() - 1;
        $modulecompletion->viewed = 0;
        $DB->update_record('course_modules_completion', $modulecompletion);

        totara_core_reaggregate_course_modules_completion();

        // It should have reaggregated, but there was no proof that the module was viewed by the user,
        // therefore they should just be left as incomplete.
        $this->assertEquals(true, $DB->record_exists('course_modules_completion',
            array('coursemoduleid' => $cminfo->id, 'userid' => $learner->id, 'completionstate' => COMPLETION_INCOMPLETE)));
        $modulecompletion = $DB->get_record('course_modules_completion', array('coursemoduleid' => $cminfo->id, 'userid' => $learner->id));
        // But the reaggregate flag should now be 0.
        $this->assertEquals(0, $modulecompletion->reaggregate);
    }

    public function test_totara_uncomplete_course_modules_completion() {
        $this->resetAfterTest(true);
        global $DB;

        // We'll use this time in the past to determine whether things like timemodified were updated.
        $time1 = time() - 1000;

        // Create some records in the course_modules_completion table. We'll check if these change as expected.
        $record1 = array(
            'coursemoduleid' => 1,
            'userid' => 1,
            'completionstate' => COMPLETION_COMPLETE,
            'viewed' => 1,
            'timemodified' => $time1,
            'timecompleted' => $time1
        );
        $DB->insert_record('course_modules_completion', (object)$record1);

        $record2 = array(
            'coursemoduleid' => 1,
            'userid' => 2,
            'completionstate' => COMPLETION_INCOMPLETE,
            'viewed' => 0,
            'timemodified' => $time1,
            'timecompleted' => $time1
        );
        $DB->insert_record('course_modules_completion', (object)$record2);

        $record3 = array(
            'coursemoduleid' => 2,
            'userid' => 2,
            'completionstate' => COMPLETION_COMPLETE,
            'viewed' => 0,
            'timemodified' => $time1,
            'timecompleted' => $time1
        );
        $DB->insert_record('course_modules_completion', (object)$record3);

        // The course completion info won't really matter in this test but is a required argument.
        $course = $this->getDataGenerator()->create_course();
        $completion = new completion_info($course);

        // We'll test the course module with id = 1.
        $cm = new stdClass();
        $cm->id = 1;
        $cm->course = $this->getDataGenerator()->create_course()->id;

        // Clear out any logs that might have been created earlier.
        $DB->delete_records('course_completion_log');

        $now = time();
        totara_core_uncomplete_course_modules_completion($cm, $completion, $now);

        // Check that some logs were created.
        $this->assertEquals(2, $DB->count_records('course_completion_log'));
        $this->assertEquals(1, $DB->count_records('course_completion_log',
            array('courseid' => $cm->course, 'userid' => 1)));
        $this->assertEquals(1, $DB->count_records('course_completion_log',
            array('courseid' => $cm->course, 'userid' => 2)));

        // Updating the initial arrays to what we expect the records to be.

        // $record1 should be: incomplete (including timecompleted set to null), recently modified and set to be reaggregated.
        // Everything else should be the same.
        $record1['completionstate'] = COMPLETION_INCOMPLETE;
        $record1['timemodified'] = $now;
        $record1['timecompleted'] = null;
        $record1['reaggregate'] = $now;

        // $record2 was already incomplete, but it's timecompleted value should also have been set to null.
        // It should also be recently modified and set to reaggregate.
        $record2['timemodified'] = $now;
        $record2['timecompleted'] = null;
        $record2['reaggregate'] = $now;

        // $record3 was for a different module so should not have been changed at all.

        $this->assertEquals(true, $DB->record_exists('course_modules_completion', $record1));
        $this->assertEquals(true, $DB->record_exists('course_modules_completion', $record2));
        $this->assertEquals(true, $DB->record_exists('course_modules_completion', $record3));
    }
}
