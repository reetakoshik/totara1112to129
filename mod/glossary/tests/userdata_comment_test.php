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

use mod_glossary\userdata\comments;
use totara_userdata\userdata\item;
use totara_userdata\userdata\target_user;

defined('MOODLE_INTERNAL') || die();

/**
 * Test purge, export and count of glossary entries comment user data item.
 *
 * @group totara_userdata
 * @group mod_glossary
 */
class mod_glossary_userdata_comment_test extends advanced_testcase {

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
        require_once($CFG->dirroot . '/comment/lib.php');
        require_once($CFG->dirroot . '/rating/lib.php');
    }

    /**
     * Test the abilities to purge, export and count
     */
    public function test_abilities() {
        $contextlevels = comments::get_compatible_context_levels();

        $this->assertCount(4, $contextlevels);
        $this->assertContains(CONTEXT_SYSTEM, $contextlevels);
        $this->assertContains(CONTEXT_COURSE, $contextlevels);
        $this->assertContains(CONTEXT_COURSECAT, $contextlevels);
        $this->assertContains(CONTEXT_MODULE, $contextlevels);

        $this->assertTrue(comments::is_exportable());
        $this->assertTrue(comments::is_countable());
        $this->assertTrue(comments::is_purgeable(target_user::STATUS_ACTIVE));
        $this->assertTrue(comments::is_purgeable(target_user::STATUS_SUSPENDED));
        $this->assertTrue(comments::is_purgeable(target_user::STATUS_DELETED));
    }

    /**
     * test count when glossary has no comments
     */
    public function test_count_when_glossary_has_empty_comment() {
        global $DB;

        // init control user
        $controluser = $this->getDataGenerator()->create_user();
        $this->add_glossary_comment($controluser);

        // init user
        $user = $this->getDataGenerator()->create_user();
        $targetuser = new target_user($user);

        // prove that control user has comments
        $this->assertCount(1, $DB->get_records('comments', ['userid' => $controluser->id]));

        // check count
        $result = comments::execute_count($targetuser, context_system::instance());
        $this->assertEquals(0, $result);
    }

    /**
     * test count in system context
     */
    public function test_count_in_system_context() {
        global $DB;

        // init control user
        $controluser = $this->getDataGenerator()->create_user();
        $data_controll = $this->add_glossary_comment($controluser);

        // init user
        $user = $this->getDataGenerator()->create_user();

        // add comment to own glossary
        $data = $this->add_glossary_comment($user);
        // add comment for control user glossary
        $data = $this->add_glossary_comment($user, $data);
        // add comment to export glossary
        $this->add_export_glossary_comment($user, $data);

        $targetuser = new target_user($user);

        // prove that control user has comments for own glossary
        $this->assertCount(1, $DB->get_records('comments', ['userid' => $controluser->id, 'itemid' => $data_controll->glossary_entry->id]));

        // check count
        $result = comments::execute_count($targetuser, $this->build_context(CONTEXT_SYSTEM, $data));
        $this->assertEquals(3, $result);
    }

    /**
     * test count in course context
     */
    public function test_count_in_course_context() {
        global $DB;

        // init control user
        $controluser = $this->getDataGenerator()->create_user();
        $data_controll = $this->add_glossary_comment($controluser);

        // init user
        $user = $this->getDataGenerator()->create_user();

        // add comment course A
        $data_course_A = $this->add_glossary_comment($user);
        $this->add_glossary_comment($user, $data_course_A);
        $this->add_export_glossary_comment($user, $data_course_A);

        // add comment course B
        $data_course_B = $this->add_glossary_comment($user);
        $this->add_export_glossary_comment($user, $data_course_B);

        $targetuser = new target_user($user);

        // prove that control user has comments for own glossary
        $this->assertCount(1, $DB->get_records('comments', ['userid' => $controluser->id, 'itemid' => $data_controll->glossary_entry->id]));

        // check count for course A
        $result = comments::execute_count($targetuser, $this->build_context(CONTEXT_COURSE, $data_course_A));
        $this->assertEquals(3, $result);

        // check count for course B
        $result = comments::execute_count($targetuser, $this->build_context(CONTEXT_COURSE, $data_course_B));
        $this->assertEquals(2, $result);
    }

    /**
     * test count in course module context
     */
    public function test_count_in_course_module_context() {
        global $DB;

        // init control user
        $controluser = $this->getDataGenerator()->create_user();
        $data_controll = $this->add_glossary_comment($controluser);

        // init user
        $user = $this->getDataGenerator()->create_user();

        // add comment course module A
        $data_module_A = $this->add_glossary_comment($user);
        $this->add_glossary_comment($user, $data_module_A);
        $this->add_export_glossary_comment($user, $data_module_A);

        // add comment course B
        $data_module_B = $this->add_glossary_comment($user);
        $this->add_export_glossary_comment($user, $data_module_B);

        $targetuser = new target_user($user);

        // prove that control user has comments for own glossary
        $this->assertCount(1, $DB->get_records('comments', ['userid' => $controluser->id, 'itemid' => $data_controll->glossary_entry->id]));

        // check count for module A
        $result = comments::execute_count($targetuser, $this->build_context(CONTEXT_MODULE, $data_module_A));
        $this->assertEquals(3, $result);

        // check count for module B
        $result = comments::execute_count($targetuser, $this->build_context(CONTEXT_MODULE, $data_module_B));
        $this->assertEquals(2, $result);
    }

    /**
     * test count in course module category context
     */
    public function test_count_in_course_category_context() {
        global $DB;

        // init control user
        $controluser = $this->getDataGenerator()->create_user();
        $data_controll = $this->add_glossary_comment($controluser);

        // init user
        $user = $this->getDataGenerator()->create_user();

        // add comment course module A
        $data_course_category_A = $this->add_glossary_comment($user);
        $this->add_glossary_comment($user, $data_course_category_A);
        $this->add_export_glossary_comment($user, $data_course_category_A);

        // add comment course B
        $data_course_category_B = $this->add_glossary_comment($user);
        $this->add_export_glossary_comment($user, $data_course_category_B);

        $targetuser = new target_user($user);

        // prove that control user has comments for own glossary
        $this->assertCount(1, $DB->get_records('comments', ['userid' => $controluser->id, 'itemid' => $data_controll->glossary_entry->id]));

        // check count course cat A
        $result = comments::execute_count($targetuser, $this->build_context(CONTEXT_COURSECAT, $data_course_category_A));
        $this->assertEquals(3, $result);

        // check count course cat B
        $result = comments::execute_count($targetuser, $this->build_context(CONTEXT_COURSECAT, $data_course_category_B));
        $this->assertEquals(2, $result);
    }

    /**
     * test export when glossary has no comments
     */
    public function test_export_when_glossary_has_empty_comment() {
        // init control user
        $controluser = $this->getDataGenerator()->create_user();
        $this->add_glossary_comment($controluser);

        // init user
        $user = $this->getDataGenerator()->create_user();
        $this->add_glossary_entry($user);
        $targetuser = new target_user($user);

        // check export data for user
        $result = comments::execute_export($targetuser, context_system::instance());
        $this->assertEmpty($result->data);
        $this->assertEmpty($result->files);
    }

    /**
     * test export in system context
     */
    public function test_export_in_system_context() {

        // init control user
        $controluser = $this->getDataGenerator()->create_user();
        $data_control = $this->add_glossary_comment($controluser);

        // init user
        $user = $this->getDataGenerator()->create_user();

        // add comment to own glossary
        $data = $this->add_glossary_comment($user);
        // add comment to export glossary
        $this->add_export_glossary_comment($user, $data);

        $targetuser = new target_user($user);

        // check export data for user
        $result = comments::execute_export($targetuser, $this->build_context(CONTEXT_SYSTEM, $data));

        $this->assertCount(2, $result->data);
        $this->assertEmpty($result->files);

        foreach ($result->data as $exportitem) {
            $this->assertContains("user:" . $targetuser->id, $exportitem->content);
            foreach (['content', 'glossaryid', 'itemid'] as $attribute) {
                $this->assertObjectHasAttribute($attribute, $exportitem);
            }
        }
    }

    /**
     * test export in course context
     */
    public function test_export_in_course_context() {

        // init control user
        $controluser = $this->getDataGenerator()->create_user();
        $this->add_glossary_comment($controluser);

        // init user
        $user = $this->getDataGenerator()->create_user();

        // add comment course A
        $data_course_A = $this->add_glossary_comment($user);

        // add comment course B
        $data_course_B = $this->add_glossary_comment($user);
        $this->add_export_glossary_comment($user, $data_course_B);

        $targetuser = new target_user($user);

        // check export data for course A
        $result = comments::execute_export($targetuser, $this->build_context(CONTEXT_COURSE, $data_course_A));
        $this->assertCount(1, $result->data);
        $this->assertEmpty($result->files);
        foreach ($result->data as $exportitem) {
            $this->assertContains("user:" . $targetuser->id, $exportitem->content);
            foreach (['content', 'glossaryid', 'itemid'] as $attribute) {
                $this->assertObjectHasAttribute($attribute, $exportitem);
            }
        }

        // check export data for course B
        $result = comments::execute_export($targetuser, $this->build_context(CONTEXT_COURSE, $data_course_B));
        $this->assertCount(2, $result->data);
        $this->assertEmpty($result->files);
        foreach ($result->data as $exportitem) {
            $this->assertContains("user:" . $targetuser->id, $exportitem->content);
            foreach (['content', 'glossaryid', 'itemid'] as $attribute) {
                $this->assertObjectHasAttribute($attribute, $exportitem);
            }
        }
    }

    /**
     * test export in course category context
     */
    public function test_export_in_course_category_context() {

        // init control user
        $controluser = $this->getDataGenerator()->create_user();
        $this->add_glossary_comment($controluser);

        // init user
        $user = $this->getDataGenerator()->create_user();

        // add comment course module A
        $data_course_category_A = $this->add_glossary_comment($user);

        // add comment course B
        $data_course_category_B = $this->add_glossary_comment($user);
        $this->add_export_glossary_comment($user, $data_course_category_B);

        $targetuser = new target_user($user);

        // check export data for course cat A
        $result = comments::execute_export($targetuser, $this->build_context(CONTEXT_COURSECAT, $data_course_category_A));
        $this->assertCount(1, $result->data);
        $this->assertEmpty($result->files);
        foreach ($result->data as $exportitem) {
            $this->assertContains("user:" . $targetuser->id, $exportitem->content);
            foreach (['content', 'glossaryid', 'itemid'] as $attribute) {
                $this->assertObjectHasAttribute($attribute, $exportitem);
            }
        }

        // check export data for course cat B
        $result = comments::execute_export($targetuser, $this->build_context(CONTEXT_COURSECAT, $data_course_category_B));
        $this->assertCount(2, $result->data);
        $this->assertEmpty($result->files);
        foreach ($result->data as $exportitem) {
            $this->assertContains("user:" . $targetuser->id, $exportitem->content);
            foreach (['content', 'glossaryid', 'itemid'] as $attribute) {
                $this->assertObjectHasAttribute($attribute, $exportitem);
            }
        }
    }

    /**
     * test export in course module context
     */
    public function test_export_in_course_module_context() {

        // init control user
        $controluser = $this->getDataGenerator()->create_user();
        $this->add_glossary_comment($controluser);

        // init user
        $user = $this->getDataGenerator()->create_user();

        // add comment course module A
        $data_module_A = $this->add_glossary_comment($user);

        // add comment course B
        $data_module_B = $this->add_glossary_comment($user);
        $this->add_export_glossary_comment($user, $data_module_B);

        $targetuser = new target_user($user);

        // check export data for course module A
        $result = comments::execute_export($targetuser, $this->build_context(CONTEXT_MODULE, $data_module_A));
        $this->assertCount(1, $result->data);
        $this->assertEmpty($result->files);
        foreach ($result->data as $exportitem) {
            $this->assertContains("user:" . $targetuser->id, $exportitem->content);
            foreach (['content', 'glossaryid', 'itemid'] as $attribute) {
                $this->assertObjectHasAttribute($attribute, $exportitem);
            }
        }

        // check export data for course module B
        $result = comments::execute_export($targetuser, $this->build_context(CONTEXT_MODULE, $data_module_B));
        $this->assertCount(2, $result->data);
        $this->assertEmpty($result->files);
        foreach ($result->data as $exportitem) {
            $this->assertContains("user:" . $targetuser->id, $exportitem->content);
            foreach (['content', 'glossaryid', 'itemid'] as $attribute) {
                $this->assertObjectHasAttribute($attribute, $exportitem);
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
        $this->add_glossary_comment($suspendeduser);

        // init active user
        $activeuser = $this->getDataGenerator()->create_user();

        // add comment to own glossary
        $data = $this->add_glossary_comment($activeuser);

        // add comment to export glossary
        $this->add_export_glossary_comment($activeuser, $data);

        $targetactiveuser = new target_user($activeuser);

        // before purge
        $resultcount = comments::execute_count($targetactiveuser, $this->build_context(CONTEXT_SYSTEM, $data));
        $this->assertEquals(2, $resultcount);

        // purge records
        $result = comments::execute_purge($targetactiveuser, $this->build_context(CONTEXT_SYSTEM, $data));

        // after purge
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $result);

        $this->assertEmpty($DB->get_records('comments', ['userid' => $activeuser->id]));

        // check suspended users not affected
        $this->assertCount(1, $DB->get_records('comments', ['userid' => $suspendeduser->id]));
    }

    /**
     * test purge in system context for suspended user
     */
    public function test_purge_in_system_context_for_suspended_user() {
        global $DB;

        // init active user
        $activeuser = $this->getDataGenerator()->create_user();
        $this->add_glossary_comment($activeuser);

        // init suspended user
        $suspendeduser = $this->getDataGenerator()->create_user(['suspended' => 1]);

        // suspended user add comment to own glossary
        $data = $this->add_glossary_comment($suspendeduser);

        // add comment to export glossary
        $this->add_export_glossary_comment($suspendeduser, $data);
        $targetsuspendeduser = new target_user($suspendeduser);

        // before purge
        $resultcount = comments::execute_count($targetsuspendeduser, $this->build_context(CONTEXT_SYSTEM, $data));
        $this->assertEquals(2, $resultcount);

        // purge records
        $result = comments::execute_purge($targetsuspendeduser, $this->build_context(CONTEXT_SYSTEM, $data));

        // after purge
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $result);

        $this->assertEmpty($DB->get_records('comments', ['userid' => $suspendeduser->id]));

        // check active users not affected
        $this->assertCount(1, $DB->get_records('comments', ['userid' => $activeuser->id]));
    }

    /**
     * test purge in system context for deleted users
     */
    public function test_purge_in_system_context_for_deleted_user() {
        global $DB;

        // init active user
        $activeuser = $this->getDataGenerator()->create_user();
        $data_active = $this->add_glossary_comment($activeuser);

        // init deleted user
        $deleteduser = $this->getDataGenerator()->create_user();

        // deleted user add comment to own glossary
        $data = $this->add_glossary_comment($deleteduser);

        // add comment to export glossary
        $this->add_export_glossary_comment($deleteduser, $data);
        $deleteduser->deleted = 1;
        $targetdeleteduser = new target_user($deleteduser);

        // before purge
        $resultcount = comments::execute_count($targetdeleteduser, $this->build_context(CONTEXT_SYSTEM, $data));
        $this->assertEquals(2, $resultcount);

        // purge records
        $result = comments::execute_purge($targetdeleteduser, $this->build_context(CONTEXT_SYSTEM, $data));

        // after purge
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $result);

        $this->assertEmpty($DB->get_records('comments', ['userid' => $deleteduser->id]));

        // check active users not affected
        $this->assertCount(1, $DB->get_records('comments', ['userid' => $activeuser->id]));
    }

    /**
     * test purge in course context
     */
    public function test_purge_in_course_context() {
        global $DB;

        // init user
        $user = $this->getDataGenerator()->create_user();

        // add comment course A
        $data_course_A = $this->add_glossary_comment($user);
        $this->add_glossary_comment($user, $data_course_A);
        $this->add_export_glossary_comment($user, $data_course_A);

        // add comment course B
        $data_course_B = $this->add_glossary_comment($user);
        $this->add_export_glossary_comment($user, $data_course_B);

        $targetactiveuser = new target_user($user);

        // before purge course A
        $resultcount = comments::execute_count($targetactiveuser, $this->build_context(CONTEXT_COURSE, $data_course_A));
        $this->assertEquals(3, $resultcount);

        // purge course A
        $result = comments::execute_purge($targetactiveuser, $this->build_context(CONTEXT_COURSE, $data_course_A));

        // after purge course A
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $result);
        $this->assertEmpty($DB->get_records('comments', ['userid' => $user->id, 'itemid'=>$data_course_A->course->id]));
        $this->assertCount(2, $DB->get_records('comments', ['userid' => $user->id]));

        // before purge course B
        $resultcount = comments::execute_count($targetactiveuser, $this->build_context(CONTEXT_COURSE, $data_course_B));
        $this->assertEquals(2, $resultcount);

        // purge course B
        $result = comments::execute_purge($targetactiveuser, $this->build_context(CONTEXT_COURSE, $data_course_B));

        // after purge course B
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $result);
        $this->assertEmpty($DB->get_records('comments', ['userid' => $user->id]));
    }

    /**
     * test purge in course context
     */
    public function test_purge_in_course_category_context() {
        global $DB;

        // init user
        $user = $this->getDataGenerator()->create_user();

        // add comment course category A
        $data_course_category_A = $this->add_glossary_comment($user);
        $this->add_glossary_comment($user, $data_course_category_A);
        $this->add_export_glossary_comment($user, $data_course_category_A);

        // add comment course category B
        $data_course_category_B = $this->add_glossary_comment($user);
        $this->add_export_glossary_comment($user, $data_course_category_B);

        $targetactiveuser = new target_user($user);

        // before purge course category A
        $resultcount = comments::execute_count($targetactiveuser, $this->build_context(CONTEXT_COURSECAT, $data_course_category_A));
        $this->assertEquals(3, $resultcount);

        // purge course category A
        $result = comments::execute_purge($targetactiveuser, $this->build_context(CONTEXT_COURSECAT, $data_course_category_A));

        // after purge course category A
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $result);
        $this->assertEmpty($DB->get_records('comments', ['userid' => $user->id, 'itemid'=>$data_course_category_A->course->id]));
        $this->assertCount(2, $DB->get_records('comments', ['userid' => $user->id]));

        // before purge course category B
        $resultcount = comments::execute_count($targetactiveuser, $this->build_context(CONTEXT_COURSECAT, $data_course_category_B));
        $this->assertEquals(2, $resultcount);

        // purge course category B
        $result = comments::execute_purge($targetactiveuser, $this->build_context(CONTEXT_COURSECAT, $data_course_category_B));

        // after purge course category B
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $result);
        $this->assertEmpty($DB->get_records('comments', ['userid' => $user->id]));
    }

    /**
     * test purge in course module context
     */
    public function test_purge_in_course_module_context() {
        global $DB;

        // init user
        $user = $this->getDataGenerator()->create_user();

        // add comment course module A
        $data_course_module_A = $this->add_glossary_comment($user);
        $this->add_glossary_comment($user, $data_course_module_A);
        $this->add_export_glossary_comment($user, $data_course_module_A);

        // add comment course module B
        $data_course_module_B = $this->add_glossary_comment($user);
        $this->add_export_glossary_comment($user, $data_course_module_B);

        $targetactiveuser = new target_user($user);

        // before purge course module A
        $resultcount = comments::execute_count($targetactiveuser, $this->build_context(CONTEXT_MODULE, $data_course_module_A));
        $this->assertEquals(3, $resultcount);

        // purge course module A
        $result = comments::execute_purge($targetactiveuser, $this->build_context(CONTEXT_MODULE, $data_course_module_A));

        // after purge course module A
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $result);
        $this->assertEmpty($DB->get_records('comments', ['userid' => $user->id, 'itemid'=>$data_course_module_A->course->id]));
        $this->assertCount(2, $DB->get_records('comments', ['userid' => $user->id]));

        // before purge course module B
        $resultcount = comments::execute_count($targetactiveuser, $this->build_context(CONTEXT_MODULE, $data_course_module_B));
        $this->assertEquals(2, $resultcount);

        // purge course module B
        $result = comments::execute_purge($targetactiveuser, $this->build_context(CONTEXT_MODULE, $data_course_module_B));

        // after purge course module B
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $result);
        $this->assertEmpty($DB->get_records('comments', ['userid' => $user->id]));
    }

    /**
     * Add Glossary Entry
     *
     * @param stdClass $user
     *
     * @return stdClass
     */
    private function add_glossary_entry(stdClass $user): stdClass {
        $this->setUser($user);

        $data = new stdClass();

        // create category
        $data->category = $this->getDataGenerator()->create_category();

        // create course
        $data->course = $this->getDataGenerator()->create_course(['category' => $data->category->id]);
        $completiongenerator = $this->getDataGenerator()->get_plugin_generator('core_completion');
        $completiongenerator->enable_completion_tracking($data->course);

        // create a glossary
        $glossary_generator = self::getDataGenerator()->get_plugin_generator('mod_glossary');
        $common_options = [
            'allowcomments'     => 1,
            'assessed'          => RATING_AGGREGATE_AVERAGE,
            'scale'             => 100,
            'completion'        => COMPLETION_TRACKING_AUTOMATIC,
            'completionentries' => 1,
        ];

        $data->glossary = $glossary_generator->create_instance(['course' => $data->course->id] + $common_options);

        // create glossary entry
        $data->glossary_entry = $glossary_generator->create_content($data->glossary, ['approved' => 1, 'userid' => $user->id], ['aliastest']);

        return $data;
    }

    /**
     * Add export glossary comment
     *
     * @param stdClass $user
     *
     * @return stdClass
     */
    private function add_export_glossary_comment(stdClass $user, stdClass $data): stdClass {
        $this->setUser($user);
        $glossary_generator = self::getDataGenerator()->get_plugin_generator('mod_glossary');

        $data->export_glossary_entry = $glossary_generator->create_content(
            $data->glossary,
            ['approved' => 1, 'userid' => $user->id, 'sourceglossaryid' => $data->glossary_entry->id],
            ['aliastest']
        );

        $data->export_comment = $this->add_glossary_comment($user, $data);

        return $data;
    }

    /**
     * Add glossary comment
     *
     * @param stdClass      $user
     * @param stdClass|null $data
     *
     * @return stdClass
     */
    private function add_glossary_comment(stdClass $user, stdClass $data = null): stdClass {

        $this->setUser($user);
        if (empty($data)) {
            $data = $this->add_glossary_entry($user);
        }

        // create glossary comment
        $context = context_module::instance($data->glossary->cmid);
        $course_module = get_coursemodule_from_instance('glossary', $data->glossary->id, $data->glossary->course);
        $comment_data = new stdClass();
        $comment_data->component = 'mod_glossary';
        $comment_data->context = $context;
        $comment_data->courseid = $data->glossary->course;
        $comment_data->cm = $course_module;
        $comment_data->area = 'glossary_entry';
        $comment_data->showcount = true;
        if (isset($data->export_glossary_entry)) {
            $comment_data->itemid = $data->export_glossary_entry->id;
        }
        else {
            $comment_data->itemid = $data->glossary_entry->id;
        }
        $comment = new \comment($comment_data);

        $data->comment = $comment->add('New comment from user:' . $user->id);

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
