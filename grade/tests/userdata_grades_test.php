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
 * @author Aleksandr Baishev <aleksandr.baishev@totaralearning.com>
 * @package core_grades
 */

use core_grades\userdata\grades;
use totara_userdata\userdata\export;
use totara_userdata\userdata\target_user;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ .'/grade_testcase.php');

/**
 * This class tests purging and exporting grades user data item.
 * Please note that these tests fully cover the functionality of
 * related helper classes.
 *
 * Class core_grade_userdata_grades_test
 *
 * @group core_grades
 * @group totara_userdata
 */
class core_grade_userdata_grades_test extends grade_testcase {

    public function test_it_is_countable() {
        $this->assertTrue(grades::is_countable(), 'Grades user_data item must be countable');
    }

    public function test_it_is_exportable() {
        $this->assertTrue(grades::is_exportable(), 'Grades user_data item must be exportable');
    }

    public function test_it_is_purgeable() {
        $error = 'Grades user_data item must be purgeable';

        $this->assertTrue(grades::is_purgeable(target_user::STATUS_ACTIVE), $error);
        $this->assertTrue(grades::is_purgeable(target_user::STATUS_DELETED), $error);
        $this->assertTrue(grades::is_purgeable(target_user::STATUS_SUSPENDED), $error);
    }

    public function test_it_is_compatible_with_wide_range_of_contexts() {
        $expected = [
            CONTEXT_SYSTEM,
            CONTEXT_COURSECAT,
            CONTEXT_COURSE,
            CONTEXT_MODULE
        ];

        $contexts = grades::get_compatible_context_levels();

        $this->assertEqualsCanonicalizing($expected, $contexts,
            "Grades user_data item is expected to work with a wide range of contexts");
    }

    public function test_it_purges_data_for_system_context() {
        $data = $this->scaffold();
        $user = new target_user($data['users'][0]);
        $context = context_system::instance();

        $this->seed_course($data['categories'][array_keys($data['categories'])[0]][0], $data['users'], ['assign' => [10, 20, 30]]);

        $this->assertEquals(2, $this->count_grades_by_context($context));
        $this->assertEquals(1, $this->count_grades_for_user_by_context($user, $context));

        $this->assertEquals(4, $this->count_grades_by_context($context, true));
        $this->assertEquals(2, $this->count_grades_for_user_by_context($user, $context, true));

        // Purge
        $result = grades::execute_purge($user, $context);

        // Successful purge.
        $this->assertEquals(grades::RESULT_STATUS_SUCCESS, $result);

        // Grades are gone for this user and stayed for the other one
        $this->assertEquals(1, $this->count_grades_by_context($context));
        $this->assertEquals(0, $this->count_grades_for_user_by_context($user, $context));

        // Grades history is gone for this user and stayed for the other one
        $this->assertEquals(2, $this->count_grades_by_context($context, true));
        $this->assertEquals(0, $this->count_grades_for_user_by_context($user, $context, true));
    }

    public function test_it_purges_data_for_course_category_context() {
        $data = $this->scaffold();
        $user = new target_user($data['users'][0]);
        $coursecat = array_keys($data['categories'])[0];
        $context = context_coursecat::instance($coursecat);
        $systemcontext = context_system::instance();

        // Seed course one
        $this->seed_course($data['categories'][$coursecat][0], $data['users'], ['assign' => [10, 20, 30]]);

        // Seed course two
        $this->seed_course($data['categories'][$coursecat][1], $data['users'], ['assign' => [40, 50, 60]]);

        // Seed irrelevant course
        $this->seed_course($data['categories'][array_keys($data['categories'])[1]][0], $data['users'], ['assign' => [70, 80, 90]]);

        $this->assertEquals(6, $this->count_grades_by_context($systemcontext));
        $this->assertEquals(2, $this->count_grades_for_user_by_context($user, $context));

        $this->assertEquals(12, $this->count_grades_by_context($systemcontext, true));
        $this->assertEquals(4, $this->count_grades_for_user_by_context($user, $context, true));

        // Purge
        $result = grades::execute_purge($user, $context);

        // Successful purge.
        $this->assertEquals(grades::RESULT_STATUS_SUCCESS, $result);

        // Grades are gone for this user and stayed for the other one
        $this->assertEquals(4, $this->count_grades_by_context($systemcontext));
        $this->assertEquals(0, $this->count_grades_for_user_by_context($user, $context));

        // Grades history is gone for this user and stayed for the other one
        $this->assertEquals(8, $this->count_grades_by_context($systemcontext, true));
        $this->assertEquals(0, $this->count_grades_for_user_by_context($user, $context, true));
    }

    public function test_it_purges_data_for_course_context() {
        $data = $this->scaffold();
        $user = new target_user($data['users'][0]);
        $course = $data['categories'][array_keys($data['categories'])[0]][0];
        $context = context_course::instance($course);
        $systemcontext = context_system::instance();

        // Seed course one
        $this->seed_course($course, $data['users'], ['assign' => [10, 20, 30]]);

        // Seed irrelevant course
        $this->seed_course($data['categories'][array_keys($data['categories'])[1]][0], $data['users'], ['assign' => [40, 50, 60]]);

        $this->assertEquals(4, $this->count_grades_by_context($systemcontext));
        $this->assertEquals(1, $this->count_grades_for_user_by_context($user, $context));

        $this->assertEquals(8, $this->count_grades_by_context($systemcontext, true));
        $this->assertEquals(2, $this->count_grades_for_user_by_context($user, $context, true));

        // Purge
        $result = grades::execute_purge($user, $context);

        // Successful purge.
        $this->assertEquals(grades::RESULT_STATUS_SUCCESS, $result);

        // Grades are gone for this user and stayed for the other one
        $this->assertEquals(3, $this->count_grades_by_context($systemcontext));
        $this->assertEquals(0, $this->count_grades_for_user_by_context($user, $context));

        // Grades history is gone for this user and stayed for the other one
        $this->assertEquals(6, $this->count_grades_by_context($systemcontext, true));
        $this->assertEquals(0, $this->count_grades_for_user_by_context($user, $context, true));
    }

    public function test_it_purges_data_for_course_module_context() {
        $data = $this->scaffold();
        $user = new target_user($data['users'][0]);

        // Seed course one
        $actual = $this->seed_course($data['categories'][array_keys($data['categories'])[0]][0],
            $data['users'], ['assign' => [10, 20, 30], 'f2f' => [40, 50, 60]]);

        // Over-complicated stuff to get proper id here.
        $context = $this->get_module_context_by_instance($actual[0]['module'], $actual[0]['item']->iteminstance);
        $systemcontext = context_system::instance();

        // Seed irrelevant course
        $this->seed_course($data['categories'][array_keys($data['categories'])[1]][0],
            $data['users'], ['assign' => [11, 22, 33], 'f2f' => [44, 55, 66]]);

        $this->assertEquals(8, $this->count_grades_by_context($systemcontext));
        $this->assertEquals(1, $this->count_grades_for_user_by_context($user, $context));

        $this->assertEquals(16, $this->count_grades_by_context($systemcontext, true));
        $this->assertEquals(2, $this->count_grades_for_user_by_context($user, $context, true));

        // Purge
        $result = grades::execute_purge($user, $context);

        // Successful purge.
        $this->assertEquals(grades::RESULT_STATUS_SUCCESS, $result);

        // Grades are gone for this user and stayed for the other one
        $this->assertEquals(7, $this->count_grades_by_context($systemcontext));
        $this->assertEquals(0, $this->count_grades_for_user_by_context($user, $context));

        // Grades history is gone for this user and stayed for the other one
        $this->assertEquals(14, $this->count_grades_by_context($systemcontext, true));
        $this->assertEquals(0, $this->count_grades_for_user_by_context($user, $context, true));
    }

    public function test_it_exports_data_for_system_context() {
        $data = $this->scaffold();
        $user = new target_user($data['users'][0]);
        $context = context_system::instance();

        $this->seed_course($data['categories'][array_keys($data['categories'])[0]][0], $data['users'], ['assign' => [10, 20, 30]]);
        $this->match_export($user, $context, grades::execute_export($user, $context));
    }

    public function test_it_exports_data_for_course_category_context() {
        $data = $this->scaffold();
        $user = new target_user($data['users'][0]);
        $context = context_coursecat::instance(array_keys($data['categories'])[0]);

        // Populate data for the selected course category course one
        $this->seed_course($data['categories'][array_keys($data['categories'])[0]][0], $data['users'], ['assign' => [10, 20, 30]]);

        // And two.
        $this->seed_course($data['categories'][array_keys($data['categories'])[0]][1], $data['users'], ['assign' => [40, 50, 60]]);

        // And just add some irrelevant data.
        $this->seed_course($data['categories'][array_keys($data['categories'])[1]][1], $data['users'], ['assign' => [70, 80, 90]]);

        $this->match_export($user, $context, grades::execute_export($user, $context));
    }

    public function test_it_exports_data_for_course_context() {
        $data = $this->scaffold();
        $user = new target_user($data['users'][0]);
        $course = $data['categories'][array_keys($data['categories'])[0]][0];
        $context = context_course::instance($course);

        // Populate data for the selected course
        $this->seed_course($course, $data['users'], ['assign' => [10, 20, 30]]);

        // And just add some irrelevant data.
        $this->seed_course($data['categories'][array_keys($data['categories'])[1]][1], $data['users'], ['assign' => [70, 80, 90]]);

        $this->match_export($user, $context, grades::execute_export($user, $context));
    }

    public function test_it_exports_data_for_course_module_context() {
        $data = $this->scaffold();
        $user = new target_user($data['users'][0]);

        $course = $data['categories'][array_keys($data['categories'])[0]][0];

        // Populate data for the selected course
        $actual = $this->seed_course($course, $data['users'], ['assign' => [10, 20, 30], 'f2f' => [40, 50, 60]]);

        // Over-complicated stuff to get proper id here.
        $context = $this->get_module_context_by_instance($actual[0]['module'], $actual[0]['item']->iteminstance);

        // Populate data for the selected course
        $this->seed_course($course, $data['users'], ['assign' => [10, 20, 30]]);

        // And just add some irrelevant data.
        $this->seed_course($data['categories'][array_keys($data['categories'])[1]][1], $data['users'], ['assign' => [70, 80, 90]]);

        $this->match_export($user, $context, grades::execute_export($user, $context));
    }

    public function test_it_counts_data_for_system_context() {
        $data = $this->scaffold();
        $user = new target_user($data['users'][0]);
        $context = context_system::instance();

        // Give it some data.
        $this->seed_course($data['categories'][array_keys($data['categories'])[0]][0], $data['users'], ['assign' => [10, 20, 30]]);

        // Give it some more data.
        $this->seed_course($data['categories'][array_keys($data['categories'])[0]][1], $data['users'], ['assign' => [10, 20, 30]]);

        $this->assertEquals($this->count_grades_for_user_by_context($user, $context), grades::execute_count($user, $context));
    }

    public function test_it_counts_data_for_course_category_context() {
        $data = $this->scaffold();
        $user = new target_user($data['users'][0]);
        $coursecat = array_keys($data['categories'])[0];
        $context = context_coursecat::instance($coursecat);

        // Seed relevant data
        $this->seed_course($data['categories'][$coursecat][0], $data['users'], ['assign' => [10, 20, 30]]);

        // Seed more relevant data
        $this->seed_course($data['categories'][$coursecat][1], $data['users'], ['assign' => [40, 50, 60]]);

        // Seed irrelevant data
        $this->seed_course($data['categories'][array_keys($data['categories'])[1]][0], $data['users'], ['assign' => [70, 80, 90]]);

        $this->assertEquals($this->count_grades_for_user_by_context($user, $context), grades::execute_count($user, $context));
    }

    public function test_it_counts_data_for_course_context() {
        $data = $this->scaffold();
        $user = new target_user($data['users'][0]);
        $course = $data['categories'][array_keys($data['categories'])[0]][0];
        $context = context_course::instance($course);

        // Seed relevant data
        $this->seed_course($course, $data['users'], ['assign' => [10, 20, 30]]);

        // Irrelevant data
        $this->seed_course($data['categories'][array_keys($data['categories'])[0]][1], $data['users'], ['assign' => [10, 20, 30]]);

        $this->assertEquals($this->count_grades_for_user_by_context($user, $context), grades::execute_count($user, $context));
    }

    public function test_it_counts_data_for_course_module_context() {$data = $this->scaffold();
        $user = new target_user($data['users'][0]);

        $course = $data['categories'][array_keys($data['categories'])[0]][0];

        // Populate data for the selected course
        $actual = $this->seed_course($course, $data['users'], ['assign' => [10, 20, 30], 'f2f' => [40, 50, 60]]);

        // Over-complicated stuff to get proper id here.
        $context = $this->get_module_context_by_instance($actual[0]['module'], $actual[0]['item']->iteminstance);

        // Irrelevant data
        $this->seed_course($data['categories'][array_keys($data['categories'])[1]][0], $data['users'], ['assign' => [10, 20, 30], 'f2f' => [40, 50, 60]]);

        $this->assertEquals($this->count_grades_for_user_by_context($user, $context), grades::execute_count($user, $context));
    }

    /**
     * Verify that proper things have been exported
     *
     * @param target_user $user User the export has been performed for
     * @param context $context Export context
     * @param export $export Export returned by data item class
     */
    protected function match_export(target_user $user, \context $context, export $export): void {
        $this->assertEmpty($export->files, $this->errors('no_files'));

        // Get needed item ids by context to figure out what should have been exported exactly and
        // get the supposed export.
        $grades = $this->get_grades_by_item_ids($user, $this->get_grade_item_ids_by_context($context));

        // Match the actual count
        $this->assertCount(count($grades), $export->data);

        // Compare exported grades one by one.
        foreach ($grades as $grade) {
            $this->assertArrayHasKey($grade->id, $export->data, $this->errors('missing_export_item'));
            $this->assert_export($grade, $export->data[$grade->id]);

            // Compare history
            $historical = $this->get_grades_by_item_ids($user, [$grade->itemid], true);

            if (!empty($historical)) {
                $this->assertArrayHasKey('history', $export->data[$grade->id], 'Export does not contain expected historical grades.');
                $this->assertIsArray($export->data[$grade->id]['history']);
                $this->assertCount(count($historical), $export->data[$grade->id]['history']);

                foreach ($historical as $item) {
                    $this->assertArrayHasKey($item->id, $export->data[$grade->id]['history'], $this->errors('missing_export_item'));
                    $this->assert_export($item, $export->data[$grade->id]['history'][$item->id]);
                }
            }
        }
    }

    /**
     * Assert that export row matches the actual data from the db
     *
     * @param stdClass $expected Exported grade row
     * @param array $actual Actual grade row
     */
    protected function assert_export(stdClass $expected, array $actual): void {
        // Match values (same - type aware, equals - not)
        $this->assertSame(intval($expected->id), $actual['id'], 'Grade export ID mismatch');
        $this->assertEquals($expected->course_name, $actual['course'], 'Grade export course_name mismatch');
        $this->assertEquals($expected->name, $actual['activity'], 'Grade export name mismatch');
        $this->assertSame(intval($expected->userid), $actual['user_id'], 'Grade export userid mismatch');
        $this->assertSame(floatval($expected->rawgrade), $actual['raw_grade'], 'Grade export rawgrade mismatch');
        $this->assertSame(floatval($expected->rawgrademin), $actual['raw_grade_min'], 'Grade export rawgrademin mismatch');
        $this->assertSame(floatval($expected->rawgrademax), $actual['raw_grade_max'], 'Grade export rawgrademax mismatch');
        $this->assertSame(floatval($expected->finalgrade), $actual['final_grade'], 'Grade export finalgrade mismatch');

        $this->assertEquals($expected->feedback, $actual['feedback'], 'Grade export feedback mismatch');
        $this->assertEquals($expected->information, $actual['information'], 'Grade export information mismatch');

        if (!empty($expected->timecreated)) {
            $this->assertSame(intval($expected->timecreated), $actual['created_at'], 'Grade export timecreated mismatch');
        } else {
            $this->assertNull($actual['created_at'], 'Grade export timecreated mismatch');
        }
        if (!empty($expected->timemodified)) {
            $this->assertSame(intval($expected->timemodified), $actual['modified_at'], 'Grade export timemodified mismatch');
        } else {
            $this->assertNull($actual['modified_at'], 'Grade export timemodified mismatch');
        }
    }

    /**
     * Does what it says, it counts grades for a given user by given context
     *
     * @param target_user $user User
     * @param context $context Context
     * @param bool $historical Whether to count data from _history table
     * @return int
     */
    protected function count_grades_for_user_by_context(target_user $user, \context $context, $historical = false): int {
        return count($this->get_grades_by_item_ids($user, $this->get_grade_item_ids_by_context($context), $historical));
    }

    /**
     * Does what it says, it counts grades by given context for all users
     *
     * @param context $context Context
     * @param bool $historical Whether to count data from _history table
     * @return int
     */
    protected function count_grades_by_context(\context $context, $historical = false): int {
        return count($this->get_grades_by_item_ids(null, $this->get_grade_item_ids_by_context($context), $historical));
    }

    /**
     * @param null|target_user $user User or null for all users
     * @param array $ids Array of grade item ids
     * @param bool $historical Whether to get historical grades
     * @return array of grade records
     */
    protected function get_grades_by_item_ids(?target_user $user, array $ids, bool $historical = false): array {
        global $DB;
        $table = $historical ? 'grade_grades_history' : 'grade_grades';

        $ids = !empty($ids) ? implode(', ', $ids) : '-1';
        $condition = $user ? "and grades_.userid = {$user->id}" : '';

        return  $DB->get_records_sql("
          SELECT grades_.*, grade_items.itemname as name, course_.fullname as course_name
          FROM {{$table}} grades_
          JOIN {grade_items} grade_items ON grade_items.id = grades_.itemid
          JOIN {course} course_ ON course_.id = grade_items.courseid
          WHERE grades_.itemid IN ({$ids}) $condition");
    }

    /**
     * Return array of grade item ids by a given context
     *
     * @param context $context "Given context"
     * @return array of item ids
     */
    protected function get_grade_item_ids_by_context(\context $context): array {
        global $DB;

        switch ($context->contextlevel) {
            case CONTEXT_MODULE:
                $mod = $DB->get_record_sql("
                    SELECT course_modules.*, mods.name as name
                    FROM {course_modules} course_modules
                    JOIN {modules} mods ON mods.id = course_modules.module
                    WHERE course_modules.id = {$context->instanceid}", [], MUST_EXIST);

                // There can be multiple grade items per module.
                return array_keys($DB->get_records('grade_items', [
                    'itemtype' => 'mod',
                    'itemmodule' => $mod->name,
                    'iteminstance' => $mod->instance,
                ], 'id', 'id'));

            case CONTEXT_COURSE:
                return array_keys($DB->get_records('grade_items', [
                    'courseid' => $context->instanceid,
                ], 'id', 'id'));

            case CONTEXT_COURSECAT:
                return array_keys($DB->get_records_sql("
                      SELECT id FROM {grade_items}
                      WHERE courseid IN (
                        SELECT instanceid as instance_id
                         FROM {context} ctx
                         WHERE ctx.contextlevel = " . CONTEXT_COURSE . "
                            AND ctx.path LIKE '{$context->path}/%'
                      )"));

            case CONTEXT_SYSTEM:
            default:
                return array_keys($DB->get_records('grade_items', [], 'id', 'id'));
        }
    }

    /**
     * Get context by module name and instance id
     *
     * @param string $name Module name
     * @param int $id Instance id
     * @return context_module
     */
    protected function get_module_context_by_instance($name, $id): \context_module {
        global $DB;

        $id = intval($id);
        $name = clean_param($name, PARAM_ALPHANUM);

        return context_module::instance($DB->get_field_sql("
            SELECT cms.id FROM {course_modules} cms
            JOIN {modules} modules_ ON cms.module = modules_.id
            WHERE cms.instance = {$id} AND modules_.name = '{$name}'"));
    }

    /**
     * Reusable human-readable error messages.
     *
     * @param string $error Error slug
     * @return array|string Error message(s)
     */
    protected function errors($error = '') {
        $errors = [
            'purge_failed' => 'Grades user_data purge failed',
            'initial_data' => 'Proper data haven\'t been generated',
            'underdone_purge' => 'Some items required to purge are still there',
            'excessive_purge' => 'Something that should have stayed was purged',
            'missing_export_item' => 'A grade that should have been exported was not found in the export.',
            'no_files' => 'Grades must not export files',
        ];

        if ($error != '') {
            return in_array($error, $errors) ? $errors[$error] : 'Something went wrong';
        }

        return $errors;
    }
}