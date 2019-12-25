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
 * @author Aleksandr Baishev <aleksandr.baishev@@totaralearning.com>
 * @package mod_chat
 */

use mod_chat\userdata\sessions;
use totara_userdata\userdata\target_user;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/chat/lib.php');
require_once($CFG->dirroot . '/mod/chat/tests/chat_testcase.php');

/**
 * This class tests purging and exporting userdata chat messages item.
 * Please note that these tests fully cover the functionality of
 * sessions_helper class.
 *
 * Class mod_chat_userdata_sessions_test
 *
 * @group totara_userdata
 */
class mod_chat_userdata_sessions_test extends chat_testcase {

    /**
     * Reusable human-readable error messages
     *
     * @param string $error Error slug
     * @return array|string Error message(s)
     */
    protected function errors($error = '') {
        $errors = [
            'purge_failed' => 'Session user_data purge failed',
            'nothing_to_purge' => 'No chat user sessions to purge',
            'underdone_purge' => 'Some items required to purge are still there',
            'excessive_purge' => 'Something that should have stayed was purged',
        ];

        if ($error != '') {
            return in_array($error, $errors) ? $errors[$error] : 'Something went wrong';
        }

        return $errors;
    }

    public function test_it_is_not_countable() {
        $this->assertFalse(sessions::is_countable(), 'Session user_data item should not be countable');
    }

    public function test_it_is_not_exportable() {
        $this->assertFalse(sessions::is_exportable(), 'Session user_data item should not be exportable');
    }

    public function test_it_is_purgeable() {
        $this->assertTrue(sessions::is_purgeable(target_user::STATUS_ACTIVE), 'Session user_data item should be purgeable');
        $this->assertTrue(sessions::is_purgeable(target_user::STATUS_DELETED), 'Session user_data item should be purgeable');
        $this->assertTrue(sessions::is_purgeable(target_user::STATUS_SUSPENDED), 'Session user_data item should be purgeable');
    }

    public function test_it_is_compatible_with_wide_range_of_contexts() {
        $expected = [
            CONTEXT_SYSTEM,
            CONTEXT_COURSECAT,
            CONTEXT_COURSE,
            CONTEXT_MODULE
        ];

        $contexts = sessions::get_compatible_context_levels();

        $this->assertEqualsCanonicalizing($expected, $contexts,
            "Session user_data item expect to work with a wide range of contexts");
    }


    public function test_it_purges_chat_sessions_for_system_context() {
        global $DB;

        $data = $this->seed();

        $user = array_values($data['users'])[0];

        $this->assertGreaterThan(0, $DB->count_records('chat_users', ['userid' => $user->id]),
            $this->errors('nothing_to_purge'));

        $sql = "SELECT count(users.id) FROM {chat_users} users WHERE userid <> " . intval($user->id);

        $unrelated = $DB->count_records_sql($sql);

        // Initializing mighty purger.
        $status = sessions::execute_purge(new target_user($user), context_system::instance());

        // Purged successfully.
        $this->assertEquals(sessions::RESULT_STATUS_SUCCESS, $status, $this->errors('purge_failed'));

        // Purged what had to be purged.
        $this->assertEquals(0, $DB->count_records('chat_users', ['userid' => $user->id]),
            $this->errors('underdone_purge'));

        // Did touch what should not have been touched.
        $this->assertEquals($unrelated, $DB->count_records_sql($sql), $this->errors('excessive_purge'));

        $this->resetAfterTest();
    }

    public function test_it_purges_chat_sessions_for_course_category_context() {
        global $DB;

        $data = $this->seed();

        $context = context_coursecat::instance($cat = array_keys($data['cats'])[0]);
        $user = array_values($data['users'])[0];

        $courses = array_map('intval', array_keys(get_courses($cat)));

        $countunrelatedsql = "SELECT count(users.id)
                              FROM {chat_users} users
                              WHERE (users.course NOT IN (" . implode(', ', $courses).  "))
                                OR (users.course IN (" . implode(', ', $courses).  ")
                                    AND users.userid <> " . intval($user->id) . ')';

        $countrelatedsql = "SELECT count(users.id)
                              FROM {chat_users} users
                              WHERE users.course IN (" . implode(', ', $courses).  ")
                              AND users.userid = " . intval($user->id);

        $unrelated = $DB->count_records_sql($countunrelatedsql);

        $this->assertGreaterThan(0, $DB->count_records_sql($countrelatedsql), $this->errors('nothing_to_purge'));

        // Initializing mighty purger.
        $status = sessions::execute_purge(new target_user($user), $context);

        // Purged successfully.
        $this->assertEquals(sessions::RESULT_STATUS_SUCCESS, $status, $this->errors('purge_failed'));

        // Purged what had to be purged.
        $this->assertEquals(0, $DB->count_records_sql($countrelatedsql), $this->errors('underdone_purge'));

        // Did touch what should not have been touched.
        $this->assertEquals($unrelated, $DB->count_records_sql($countunrelatedsql), $this->errors('excessive_purge'));

        $this->resetAfterTest();
    }

    public function test_it_purges_chat_sessions_for_course_context() {
        global $DB;

        $data = $this->seed();

        $context = context_course::instance($course = intval(array_keys(array_values($data['cats'])[0])[0]));
        $user = array_values($data['users'])[0];

        $countunrelatedsql = "SELECT count(users.id)
                              FROM {chat_users} users
                              WHERE (users.course <> {$course})
                                OR (users.course = {$course} AND users.userid <> " . intval($user->id) . ')';

        $countrelatedsql = "SELECT count(users.id)
                              FROM {chat_users} users
                              WHERE users.course = {$course}
                              AND users.userid = " . intval($user->id);

        $unrelated = $DB->count_records_sql($countunrelatedsql);

        $this->assertGreaterThan(0, $DB->count_records_sql($countrelatedsql), $this->errors('nothing_to_purge'));

        // Initializing mighty purger.
        $status = sessions::execute_purge(new target_user($user), $context);

        // Purged successfully.
        $this->assertEquals(sessions::RESULT_STATUS_SUCCESS, $status, $this->errors('purge_failed'));

        // Purged what had to be purged.
        $this->assertEquals(0, $DB->count_records_sql($countrelatedsql), $this->errors('underdone_purge'));

        // Did not touch what should not have been touched.
        $this->assertEquals($unrelated, $DB->count_records_sql($countunrelatedsql), $this->errors('excessive_purge'));

        $this->resetAfterTest();
    }

    public function test_it_purges_chat_sessions_for_module_context() {
        global $DB;

        $data = $this->seed();

        $module = get_coursemodule_from_instance('chat',
            array_keys(array_values(array_values($data['cats'])[0])[0])[0]);

        $context = context_module::instance($module->id);

        $id = $module->instance;
        $user = array_values($data['users'])[1];

        $countunrelatedsql = "SELECT count(users.id)
                              FROM {chat_users} users
                              WHERE users.chatid <> {$id}
                                OR (users.chatid = {$id} AND users.userid <> " . intval($user->id) . ')';

        $countrelatedsql = "SELECT count(users.id)
                              FROM {chat_users} users
                              WHERE users.chatid = {$id}
                              AND users.userid = " . intval($user->id);

        $unrelated = $DB->count_records_sql($countunrelatedsql);

        $this->assertGreaterThan(0, $DB->count_records_sql($countrelatedsql), $this->errors('nothing_to_purge'));

        // Initializing mighty purger.
        $status = sessions::execute_purge(new target_user($user), $context);

        // Purged successfully.
        $this->assertEquals(sessions::RESULT_STATUS_SUCCESS, $status, $this->errors('purge_failed'));

        // Purged what had to be purged.
        $this->assertEquals(0, $DB->count_records_sql($countrelatedsql), $this->errors('underdone_purge'));

        // Did not touch what should not have been touched.
        $this->assertEquals($unrelated, $DB->count_records_sql($countunrelatedsql), $this->errors('excessive_purge'));

        $this->resetAfterTest();
    }
}
