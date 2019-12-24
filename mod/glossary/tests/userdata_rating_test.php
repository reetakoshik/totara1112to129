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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Samantha Jayasinghe <samantha.jayasinghe@totaralearning.com>
 * @package mod_glossary
 */

use mod_glossary\userdata\ratings;
use totara_userdata\userdata\item;
use totara_userdata\userdata\target_user;

defined('MOODLE_INTERNAL') || die();

/**
 * Test purge, export and count of glossary entries rating user data item.
 *
 * @group totara_userdata
 * @group mod_glossary
 */
class mod_glossary_userdata_rating_test extends advanced_testcase {

    /**
     * Set up tests.
     */
    protected function setUp() {
        parent::setUp();

        $this->resetAfterTest(true);
    }

    /**
     * Set up required classes
     */
    public static function setUpBeforeClass() {
        parent::setUpBeforeClass();

        global $CFG;
        require_once($CFG->dirroot . '/rating/lib.php');
    }

    /**
     * Test the abilities to purge, export and count
     */
    public function test_abilities() {
        $contextlevels = ratings::get_compatible_context_levels();

        $this->assertCount(4, $contextlevels);
        $this->assertContains(CONTEXT_SYSTEM, $contextlevels);
        $this->assertContains(CONTEXT_COURSE, $contextlevels);
        $this->assertContains(CONTEXT_COURSECAT, $contextlevels);
        $this->assertContains(CONTEXT_MODULE, $contextlevels);

        $this->assertTrue(ratings::is_exportable());
        $this->assertTrue(ratings::is_countable());
        $this->assertTrue(ratings::is_purgeable(target_user::STATUS_ACTIVE));
        $this->assertTrue(ratings::is_purgeable(target_user::STATUS_SUSPENDED));
        $this->assertTrue(ratings::is_purgeable(target_user::STATUS_DELETED));
    }

    /**
     * test count when glossary has no comments
     */
    public function test_count_when_glossary_has_no_rating() {
        global $DB;

        // init control user
        $controluser = $this->getDataGenerator()->create_user();
        $this->add_glossary_rating($controluser, 90);

        // init user
        $user = $this->getDataGenerator()->create_user();
        $targetuser = new target_user($user);

        // prove that control user has ratings
        $this->assertCount(1, $DB->get_records('rating', ['userid' => $controluser->id]));

        // check count
        $result = ratings::execute_count($targetuser, context_system::instance());
        $this->assertEquals(0, $result);
    }

    /**
     * test count in system context
     */
    public function test_count_in_system_context() {
        global $DB;

        // init control user
        $controluser = $this->getDataGenerator()->create_user();
        $data_controll = $this->add_glossary_rating($controluser, 60);

        // init user
        $user = $this->getDataGenerator()->create_user();

        // add rating to other glossary
        $this->add_glossary_rating($user, 70);
        $data = $this->add_glossary_rating($user, 80);

        // add rating to export glossary
        $this->add_export_glossary_rating($user, 90, $data);

        $targetuser = new target_user($user);

        // prove that control user has comments for own glossary
        $this->assertCount(1, $DB->get_records('rating', ['userid' => $controluser->id, 'itemid' => $data_controll->entry->id]));

        // check count
        $result = ratings::execute_count($targetuser, $this->build_context(CONTEXT_SYSTEM, $data));
        $this->assertEquals(3, $result);
    }

    /**
     * test count in course context
     */
    public function test_count_in_course_context() {
        global $DB;

        // init control user
        $controluser = $this->getDataGenerator()->create_user();
        $data_controll = $this->add_glossary_rating($controluser, 60);

        // init user
        $user = $this->getDataGenerator()->create_user();

        // add rating to course A
        $data_course_A = $this->add_glossary_rating($user, 70);

        // add rating to course B
        $data_course_B = $this->add_glossary_rating($user, 70);
        $this->add_export_glossary_rating($user, 90, $data_course_B);

        $targetuser = new target_user($user);

        // prove that control user has comments for own glossary
        $this->assertCount(1, $DB->get_records('rating', ['userid' => $controluser->id, 'itemid' => $data_controll->entry->id]));

        // check count for course A
        $result = ratings::execute_count($targetuser, $this->build_context(CONTEXT_COURSE, $data_course_A));
        $this->assertEquals(1, $result);

        // check count for course B
        $result = ratings::execute_count($targetuser, $this->build_context(CONTEXT_COURSE, $data_course_B));
        $this->assertEquals(2, $result);
    }

    /**
     * test count in course category context
     */
    public function test_count_in_course_category_context() {
        global $DB;

        // init control user
        $controluser = $this->getDataGenerator()->create_user();
        $data_controll = $this->add_glossary_rating($controluser, 60);

        // init user
        $user = $this->getDataGenerator()->create_user();

        // add rating to course category A
        $data_course_category_A = $this->add_glossary_rating($user, 70);

        // add rating to course category B
        $data_course_category_B = $this->add_glossary_rating($user, 70);
        $this->add_export_glossary_rating($user, 90, $data_course_category_B);

        $targetuser = new target_user($user);

        // prove that control user has comments for own glossary
        $this->assertCount(1, $DB->get_records('rating', ['userid' => $controluser->id, 'itemid' => $data_controll->entry->id]));

        // check count for course category A
        $result = ratings::execute_count($targetuser, $this->build_context(CONTEXT_COURSECAT, $data_course_category_A));
        $this->assertEquals(1, $result);

        // check count for course category B
        $result = ratings::execute_count($targetuser, $this->build_context(CONTEXT_COURSECAT, $data_course_category_B));
        $this->assertEquals(2, $result);
    }

    /**
     * test count in course module context
     */
    public function test_count_in_course_module_context() {
        global $DB;

        // init control user
        $controluser = $this->getDataGenerator()->create_user();
        $data_controll = $this->add_glossary_rating($controluser, 60);

        // init user
        $user = $this->getDataGenerator()->create_user();

        // add rating to course module A
        $data_course_module_A = $this->add_glossary_rating($user, 70);

        // add rating to course module B
        $data_course_module_B = $this->add_glossary_rating($user, 70);
        $this->add_export_glossary_rating($user, 90, $data_course_module_B);

        $targetuser = new target_user($user);

        // prove that control user has comments for own glossary
        $this->assertCount(1, $DB->get_records('rating', ['userid' => $controluser->id, 'itemid' => $data_controll->entry->id]));

        // check count for course module A
        $result = ratings::execute_count($targetuser, $this->build_context(CONTEXT_COURSECAT, $data_course_module_A));
        $this->assertEquals(1, $result);

        // check count for course module B
        $result = ratings::execute_count($targetuser, $this->build_context(CONTEXT_COURSECAT, $data_course_module_B));
        $this->assertEquals(2, $result);
    }

    /**
     * test export when glossary has no ratings
     */
    public function test_export_when_glossary_has_empty_rating() {
        // init control user
        $controluser = $this->getDataGenerator()->create_user();
        $this->add_glossary_rating($controluser, 90);

        // init user
        $user = $this->getDataGenerator()->create_user();
        $targetuser = new target_user($user);

        //check export data for user
        $result = ratings::execute_export($targetuser, context_system::instance());
        $this->assertEmpty($result->data);
        $this->assertEmpty($result->files);
    }

    /**
     * test export in system context
     */
    public function test_export_in_system_context() {

        // init control user
        $controluser = $this->getDataGenerator()->create_user();
        $this->add_glossary_rating($controluser, 80);

        // init user
        $user = $this->getDataGenerator()->create_user();

        // add comment to own glossary
        $this->add_glossary_rating($user, 70);
        $data = $this->add_glossary_rating($user, 70);
        // add comment to export glossary
        $this->add_export_glossary_rating($user, 70, $data);

        $targetuser = new target_user($user);

        // check export data for user
        $result = ratings::execute_export($targetuser, $this->build_context(CONTEXT_SYSTEM, $data));
        $this->assertCount(3, $result->data);

        $this->assertEmpty($result->files);

        foreach ($result->data as $exportitem) {
            $this->assertEquals(70, $exportitem['value']);
            foreach (['value', 'glossaryid', 'entryid'] as $key) {
                $this->assertArrayHasKey($key, $exportitem);
            }
        }
    }

    /**
     * test export in course context
     */
    public function test_export_in_course_context() {

        // init control user
        $controluser = $this->getDataGenerator()->create_user();
        $this->add_glossary_rating($controluser, 80);

        // init user
        $user = $this->getDataGenerator()->create_user();

        // add rating to course A
        $data_course_A = $this->add_glossary_rating($user, 70);

        // add rating to course B
        $data_course_B = $this->add_glossary_rating($user, 90);
        $this->add_export_glossary_rating($user, 90, $data_course_B);

        $targetuser = new target_user($user);

        // check export for course A
        $result = ratings::execute_export($targetuser, $this->build_context(CONTEXT_COURSE, $data_course_A));
        $this->assertCount(1, $result->data);
        $this->assertEmpty($result->files);
        foreach ($result->data as $exportitem) {
            $this->assertEquals(70, $exportitem['value']);
            foreach (['value', 'glossaryid', 'entryid'] as $key) {
                $this->assertArrayHasKey($key, $exportitem);
            }
        }

        // check export for course B
        $result = ratings::execute_export($targetuser, $this->build_context(CONTEXT_COURSE, $data_course_B));
        $this->assertCount(2, $result->data);
        $this->assertEmpty($result->files);
        foreach ($result->data as $exportitem) {
            $this->assertEquals(90, $exportitem['value']);
            foreach (['value', 'glossaryid', 'entryid'] as $key) {
                $this->assertArrayHasKey($key, $exportitem);
            }
        }
    }

    /**
     * test export in course category context
     */
    public function test_export_in_course_category_context() {

        // init control user
        $controluser = $this->getDataGenerator()->create_user();
        $this->add_glossary_rating($controluser, 80);

        // init user
        $user = $this->getDataGenerator()->create_user();

        // add rating to course category A
        $data_course_category_A = $this->add_glossary_rating($user, 70);

        // add rating to course category B
        $data_course_category_B = $this->add_glossary_rating($user, 60);
        $this->add_export_glossary_rating($user, 60, $data_course_category_B);

        $targetuser = new target_user($user);

        // check export for course category A
        $result = ratings::execute_export($targetuser, $this->build_context(CONTEXT_COURSECAT, $data_course_category_A));
        $this->assertCount(1, $result->data);
        $this->assertEmpty($result->files);
        foreach ($result->data as $exportitem) {
            $this->assertEquals(70, $exportitem['value']);
            foreach (['value', 'glossaryid', 'entryid'] as $key) {
                $this->assertArrayHasKey($key, $exportitem);
            }
        }

        // check export for course category B
        $result = ratings::execute_export($targetuser, $this->build_context(CONTEXT_COURSECAT, $data_course_category_B));
        $this->assertCount(2, $result->data);
        $this->assertEmpty($result->files);
        foreach ($result->data as $exportitem) {
            $this->assertEquals(60, $exportitem['value']);
            foreach (['value', 'glossaryid', 'entryid'] as $key) {
                $this->assertArrayHasKey($key, $exportitem);
            }
        }
    }

    /**
     * test export in course module context
     */
    public function test_export_in_course_module_context() {

        // init control user
        $controluser = $this->getDataGenerator()->create_user();
        $this->add_glossary_rating($controluser, 80);

        // init user
        $user = $this->getDataGenerator()->create_user();

        // add rating to course module A
        $data_course_module_A = $this->add_glossary_rating($user, 70);

        // add rating to course module B
        $data_course_category_B = $this->add_glossary_rating($user, 100);
        $this->add_export_glossary_rating($user, 100, $data_course_category_B);

        $targetuser = new target_user($user);

        // check export for course module A
        $result = ratings::execute_export($targetuser, $this->build_context(CONTEXT_COURSECAT, $data_course_module_A));
        $this->assertCount(1, $result->data);
        $this->assertEmpty($result->files);
        foreach ($result->data as $exportitem) {
            $this->assertEquals(70, $exportitem['value']);
            foreach (['value', 'glossaryid', 'entryid'] as $key) {
                $this->assertArrayHasKey($key, $exportitem);
            }
        }

        // check export for course module B
        $result = ratings::execute_export($targetuser, $this->build_context(CONTEXT_COURSECAT, $data_course_category_B));
        $this->assertCount(2, $result->data);
        $this->assertEmpty($result->files);
        foreach ($result->data as $exportitem) {
            $this->assertEquals(100, $exportitem['value']);
            foreach (['value', 'glossaryid', 'entryid'] as $key) {
                $this->assertArrayHasKey($key, $exportitem);
            }
        }
    }

    /**
     * test purge in system context for active user
     */
    public function test_purge_in_system_context_for_active_user() {
        global $DB;

        // init suspended user
        $suspendeduser = $this->getDataGenerator()->create_user(['suspended' => 1]);
        $this->add_glossary_rating($suspendeduser, 80);

        // init active user
        $activeuser = $this->getDataGenerator()->create_user();

        // add rating to other glossary
        $data = $this->add_glossary_rating($activeuser, 70);
        // add comment to export glossary
        $this->add_export_glossary_rating($activeuser, 70, $data);
        $targetactiveuser = new target_user($activeuser);

        // before purge
        $resultcount = ratings::execute_count($targetactiveuser, $this->build_context(CONTEXT_SYSTEM, $data));
        $this->assertEquals(2, $resultcount);

        // purge records
        $result = ratings::execute_purge($targetactiveuser, $this->build_context(CONTEXT_SYSTEM, $data));

        // after purge
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $result);

        $this->assertEmpty($DB->get_records('rating', ['userid' => $activeuser->id]));

        // check suspended users not affected
        $this->assertCount(1, $DB->get_records('rating', ['userid' => $suspendeduser->id]));

    }

    /**
     * test purge in sysrem context for suspended users
     */
    public function test_purge_in_system_context_for_suspended_user() {
        global $DB;

        // init active user
        $activeuser = $this->getDataGenerator()->create_user();
        $this->add_glossary_rating($activeuser, 80);

        // init suspended user
        $suspendeduser = $this->getDataGenerator()->create_user(['suspended' => 1]);

        // add rating to other glossary
        $data = $this->add_glossary_rating($suspendeduser, 70);

        // add rating to export glossary
        $this->add_export_glossary_rating($suspendeduser, 70, $data);
        $targetsuspendeduser = new target_user($suspendeduser);

        // before purge
        $resultcount = ratings::execute_count($targetsuspendeduser, $this->build_context(CONTEXT_SYSTEM, $data));
        $this->assertEquals(2, $resultcount);

        // purge records
        $result = ratings::execute_purge($targetsuspendeduser, $this->build_context(CONTEXT_SYSTEM, $data));

        // after purge
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $result);

        $this->assertEmpty($DB->get_records('rating', ['userid' => $suspendeduser->id]));

        // check active users not affected
        $this->assertCount(1, $DB->get_records('rating', ['userid' => $activeuser->id]));

    }

    /**
     * test purge in system context for deleted users
     */
    public function test_purge_in_system_context_for_deleted_user() {
        global $DB;

        // init active user
        $activeuser = $this->getDataGenerator()->create_user();
        $this->add_glossary_rating($activeuser, 80);

        // init deleted user
        $deleteduser = $this->getDataGenerator()->create_user();

        // add rating to other glossary
        $data = $this->add_glossary_rating($deleteduser, 70);

        // add rating to export glossary
        $this->add_export_glossary_rating($deleteduser, 70, $data);
        $deleteduser->deleted = 1;
        $targetdeleteduser = new target_user($deleteduser);

        // before purge
        $resultcount = ratings::execute_count($targetdeleteduser, $this->build_context(CONTEXT_SYSTEM, $data));
        $this->assertEquals(2, $resultcount);

        // purge records
        $result = ratings::execute_purge($targetdeleteduser, $this->build_context(CONTEXT_SYSTEM, $data));

        // after purge
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $result);

        $this->assertEmpty($DB->get_records('rating', ['userid' => $deleteduser->id]));

        // check active users not affected
        $this->assertCount(1, $DB->get_records('rating', ['userid' => $activeuser->id]));
    }

    /**
     * test purge in course context
     */
    public function test_purge_in_course_context() {
        global $DB;

        // init user
        $user = $this->getDataGenerator()->create_user();

        // add rating to course A
        $data_course_A = $this->add_glossary_rating($user, 70);

        // add rating to course B
        $data_course_B = $this->add_glossary_rating($user, 70);
        $this->add_export_glossary_rating($user, 90, $data_course_B);

        $targetuser = new target_user($user);

        // before purge course A
        $resultcount = ratings::execute_count($targetuser, $this->build_context(CONTEXT_COURSE, $data_course_A));
        $this->assertEquals(1, $resultcount);

        // purge course A
        $result = ratings::execute_purge($targetuser, $this->build_context(CONTEXT_COURSE, $data_course_A));

        // after purge course A
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $result);
        $this->assertEmpty($DB->get_records('rating', ['userid' => $user->id, 'itemid'=>$data_course_A->course->id]));
        $this->assertCount(2, $DB->get_records('rating', ['userid' => $user->id]));

        // before purge course B
        $resultcount = ratings::execute_count($targetuser, $this->build_context(CONTEXT_COURSE, $data_course_B));
        $this->assertEquals(2, $resultcount);

        // purge course B
        $result = ratings::execute_purge($targetuser, $this->build_context(CONTEXT_COURSE, $data_course_B));

        // after purge course B
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $result);
        $this->assertEmpty($DB->get_records('rating', ['userid' => $user->id]));
    }

    /**
     * test purge in course category context
     */
    public function test_purge_in_course_category_context() {
        global $DB;

        // init user
        $user = $this->getDataGenerator()->create_user();

        // add rating to course category A
        $data_course_category_A = $this->add_glossary_rating($user, 70);

        // add rating to course category B
        $data_course_category_B = $this->add_glossary_rating($user, 70);
        $this->add_export_glossary_rating($user, 90, $data_course_category_B);

        $targetuser = new target_user($user);

        // before purge course category A
        $resultcount = ratings::execute_count($targetuser, $this->build_context(CONTEXT_COURSECAT, $data_course_category_A));
        $this->assertEquals(1, $resultcount);

        // purge course category A
        $result = ratings::execute_purge($targetuser, $this->build_context(CONTEXT_COURSECAT, $data_course_category_A));

        // after purge course category A
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $result);
        $this->assertEmpty($DB->get_records('rating', ['userid' => $user->id, 'itemid'=>$data_course_category_A->course->id]));
        $this->assertCount(2, $DB->get_records('rating', ['userid' => $user->id]));

        // before purge course category B
        $resultcount = ratings::execute_count($targetuser, $this->build_context(CONTEXT_COURSECAT, $data_course_category_B));
        $this->assertEquals(2, $resultcount);

        // purge course category B
        $result = ratings::execute_purge($targetuser, $this->build_context(CONTEXT_COURSECAT, $data_course_category_B));

        // after purge course category B
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $result);
        $this->assertEmpty($DB->get_records('rating', ['userid' => $user->id]));
    }

    /**
     * test purge in course module context
     */
    public function test_purge_in_course_module_context() {
        global $DB;

        // init user
        $user = $this->getDataGenerator()->create_user();

        // add rating to course module A
        $data_course_module_A = $this->add_glossary_rating($user, 70);

        // add rating to course module B
        $data_course_module_B = $this->add_glossary_rating($user, 70);
        $this->add_export_glossary_rating($user, 90, $data_course_module_B);

        $targetuser = new target_user($user);

        // before purge course module A
        $resultcount = ratings::execute_count($targetuser, $this->build_context(CONTEXT_MODULE, $data_course_module_A));
        $this->assertEquals(1, $resultcount);

        // purge course module A
        $result = ratings::execute_purge($targetuser, $this->build_context(CONTEXT_MODULE, $data_course_module_A));

        // after purge course module A
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $result);
        $this->assertEmpty($DB->get_records('rating', ['userid' => $user->id, 'itemid'=>$data_course_module_A->course->id]));
        $this->assertCount(2, $DB->get_records('rating', ['userid' => $user->id]));

        // before purge course module B
        $resultcount = ratings::execute_count($targetuser, $this->build_context(CONTEXT_MODULE, $data_course_module_B));
        $this->assertEquals(2, $resultcount);

        // purge course module B
        $result = ratings::execute_purge($targetuser, $this->build_context(CONTEXT_MODULE, $data_course_module_B));

        // after purge course module B
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $result);
        $this->assertEmpty($DB->get_records('rating', ['userid' => $user->id]));
    }

    /**
     * Add glossary rating
     *
     * @param stdClass $user
     * @param int      $rating
     *
     * @return stdClass
     */
    private function add_glossary_rating(stdClass $user, int $rating, stdClass $data = null): stdClass {

        if (empty($data)) {
            $data = $this->add_glossary_entry($user);
        }

        // Add comments and ratings for purge user.
        $this->setUser($data->user);

        $context = context_module::instance($data->glossary->cmid);
        $cm = get_coursemodule_from_instance('glossary', $data->glossary->id, $data->glossary->course);
        $rm = new rating_manager();
        if (isset($data->export_entry)) {
            $rm->add_rating($cm, $context, 'mod_glossary', 'entry', $data->export_entry->id, 100, $rating, $data->export_entry->userid, 1);
        }
        else {
            $rm->add_rating($cm, $context, 'mod_glossary', 'entry', $data->entry->id, 100, $rating, $data->entry->userid, 1);
        }

        return $data;
    }

    /**
     * Add export glossary ratings
     *
     * @param stdClass $user
     * @param int      $rating
     * @param stdClass $data
     *
     * @return stdClass
     */
    private function add_export_glossary_rating(stdClass $user, int $rating, stdClass $data): stdClass {
        $this->setUser($user);
        $glossary_generator = self::getDataGenerator()->get_plugin_generator('mod_glossary');

        $data->export_entry = $glossary_generator->create_content(
            $data->glossary,
            ['approved' => 1, 'userid' => $data->entry->userid, 'sourceglossaryid' => $data->entry->id],
            ['aliastest']
        );

        $this->add_glossary_rating($user, $rating, $data);

        return $data;
    }

    /**
     * Create test glossary entry
     *
     * @param stdClass $user
     *
     * @return stdClass
     */
    private function add_glossary_entry(stdClass $user): stdClass {
        global $DB;

        $data = new stdClass();

        // Create users.
        $data->user = $user;
        $data->glossary_user = $this->getDataGenerator()->create_user();

        // Create categories.
        $data->category = $this->getDataGenerator()->create_category();

        // Create courses.
        $data->course = $this->getDataGenerator()->create_course(['category' => $data->category->id]);

        // Enrol users.
        $student_role = $DB->get_record('role', array('shortname' => 'student'));
        $this->getDataGenerator()->enrol_user($data->user->id, $data->course->id, $student_role->id);

        /** @var mod_glossary_generator $glossary_generator */
        $glossary_generator = self::getDataGenerator()->get_plugin_generator('mod_glossary');
        /** @var core_completion_generator $completiongenerator */
        $completiongenerator = $this->getDataGenerator()->get_plugin_generator('core_completion');
        $completiongenerator->enable_completion_tracking($data->course);

        // Create one glossary for each course.
        $common_options = [
            'allowcomments'     => 1,
            'assessed'          => RATING_AGGREGATE_AVERAGE,
            'scale'             => 100,
            'completion'        => COMPLETION_TRACKING_AUTOMATIC,
            'completionentries' => 1,
        ];
        $data->glossary = $glossary_generator->create_instance(['course' => $data->course->id] + $common_options);

        $context = context_module::instance($data->glossary->cmid);
        assign_capability('mod/glossary:rate', CAP_ALLOW, $student_role->id, $context->id, true);

        // Add glossary entries
        $common_options = [
            'approved' => 1,
        ];

        $data->entry = $glossary_generator->create_content($data->glossary, ['userid' => $data->glossary_user->id] + $common_options, ['alias1']);

        return $data;
    }

    /**
     * @param int      $contextlevel
     * @param stdClass $data
     *
     * @return context
     */
    private function build_context(int $contextlevel, stdClass $data): context {
        switch ($contextlevel) {
            case CONTEXT_SYSTEM:
                return context_system::instance();
                break;
            case CONTEXT_COURSE:
                return context_course::instance($data->course->id);
                break;
            case CONTEXT_MODULE:
                return context_module::instance($data->glossary->cmid);
                break;
            case CONTEXT_COURSECAT:
                return context_coursecat::instance($data->category->id);
                break;
        }
    }
}
