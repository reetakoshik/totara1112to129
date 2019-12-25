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

use core_grades\userdata\temp_import;
use totara_userdata\userdata\target_user;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ .'/grade_testcase.php');

/**
 * This class tests purging and exporting grades user data item.
 * Please note that these tests fully cover the functionality of
 * related helper classes.
 *
 * Class core_grades_userdata_temp_import_test
 *
 * @group core_grades
 * @group totara_userdata
 */
class core_grades_userdata_temp_import_test extends grade_testcase {

    public function test_it_is_not_countable() {
        $this->assertFalse(temp_import::is_countable(), 'Temporary grades import item must not be countable');
    }

    public function test_it_is_not_exportable() {
        $this->assertFalse(temp_import::is_exportable(), 'Temporary grades import item must not be exportable');
    }

    public function test_it_is_purgeable() {
        $error = 'Temporary grades import item must be purgeable';

        $this->assertTrue(temp_import::is_purgeable(target_user::STATUS_ACTIVE), $error);
        $this->assertTrue(temp_import::is_purgeable(target_user::STATUS_DELETED), $error);
        $this->assertTrue(temp_import::is_purgeable(target_user::STATUS_SUSPENDED), $error);
    }

    public function test_it_is_compatible_with_system_context_only() {
        $expected = [
            CONTEXT_SYSTEM,
        ];

        $contexts = temp_import::get_compatible_context_levels();

        $this->assertEqualsCanonicalizing($expected, $contexts,
            "Temporary grades import item is expected to work with system context only");
    }

    public function test_it_purges_data_for_system_context() {
        $data = $this->scaffold();
        $user = new target_user($data['users'][0]);

        // Populate the table
        $this->seed_grades_import_table($data['users']);

        $this->assertEquals(10, $this->count_temp_import_records());
        $this->assertEquals(5, $this->count_temp_import_records_for_user($user));

        // Purge
        $result = temp_import::execute_purge($user, context_system::instance());

        // Successful purge.
        $this->assertEquals(temp_import::RESULT_STATUS_SUCCESS, $result);

        // Grades are gone for the target user and stayed for the other one
        $this->assertEquals(5, $this->count_temp_import_records());
        $this->assertEquals(0, $this->count_temp_import_records_for_user($user));
    }

    /**
     * Seed grade_import_values table with dummy data
     *
     * @param \stdClass|array $users User(s) to create entries for
     * @param int $number Number of entries to create
     * @return array Array of created entry IDs
     */
    protected function seed_grades_import_table($users, int $number = 5): array {
        global $DB;

        if ($users instanceof \stdClass) {
            $users = [$users];
        }

        $records = [];

        foreach ($users as $user) {
            for ($i = 0; $i < $number; $i++) {
                $records[] = $DB->insert_record('grade_import_values', (object) [
                    'importcode' => 69,
                    'userid' => $user->id,
                    'itemid' => rand(1,100),
                    'newgradeitem' => rand(1,100),
                    'finalgrade' => rand(1,100),
                    'feedback' => random_string(),
                    'importer' => rand(0,10),
                    'importonlyfeedback' => rand(0,1),
                ]);
            }
        }

        return $records;
    }

    /**
     * Count the number of records in the grade_import_values_table
     *
     * @param stdClass|null $user User object or null for all records
     * @return int
     */
    protected function count_temp_import_records(\stdClass $user = null): int {
        global $DB;

        return $DB->count_records('grade_import_values', $user ? ['userid' => $user->id] : []);
    }

    /**
     * Count the number of records in the grade_import_values_table
     *
     * @param stdClass $user User object
     * @return int
     */
    protected function count_temp_import_records_for_user(\stdClass $user): int {
        return $this->count_temp_import_records($user);
    }
}