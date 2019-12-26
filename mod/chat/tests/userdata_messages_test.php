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

use mod_chat\userdata\messages;
use totara_userdata\userdata\export;
use totara_userdata\userdata\target_user;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/chat/lib.php');
require_once($CFG->dirroot . '/mod/chat/tests/chat_testcase.php');

/**
 * This class tests purging and exporting userdata chat messages item.
 * Please note that these tests fully cover the functionality of
 * messages_helper class.
 *
 * Class mod_chat_userdata_messages_test
 *
 * @group totara_userdata
 */
class mod_chat_userdata_messages_test extends chat_testcase {

    /**
     * Reusable human-readable error messages
     *
     * @param string $error Error slug
     * @return array|string Error message(s)
     */
    protected function errors($error = '') {
        $errors = [
            'purge_failed' => 'Message user_data purge failed',
            'nothing_to_purge' => 'No chat messages to purge',
            'underdone_purge' => 'Some items required to purge are still there',
            'excessive_purge' => 'Something that should have stayed was purged',
            'message_does_not_match' => 'Exported message does not match the original',
            'exported_count_wrong' => 'The number of exported messages does not match the number of original messages',
            'count_does_not_match' => 'The expected number of messages does not match the actual number of messages',
        ];

        if ($error != '') {
            return in_array($error, $errors) ? $errors[$error] : 'Something went wrong';
        }

        return $errors;
    }

    /**
     * Chat messages tables (chat stores identical messages in the current session messages table & messages archive table)
     *
     * @return array
     */
    protected function tables() {
        return [
            'chat_messages',
            'chat_messages_current'
        ];
    }

    /**
     * Find message in the export by message id
     *
     * @param \totara_userdata\userdata\export $export
     * @param $id
     * @return stdClass|false Message or false if not
     */
    private function find_exported_message_by_id(export $export, $id) {
        foreach ($export->data as $item) {
            foreach ($item['Messages'] as $message) {
                if ($message->id == $id) {
                    return $message;
                }
            }
        }

        return false;
    }

    /**
     * Check whether the exported message matches the original message
     *
     * @param \stdClass $expected Original message form the database
     * @param array|\stdClass $actual Given exported message
     * @param \stdClass $user User object
     * @return bool
     * @throws coding_exception
     */
    private function messages_match($expected, $actual, $user) {
        // If no message has been supplied so, obviously nothing matches.
        if (!$actual) {
            return false;
        }

        $date = new DateTime("@{$expected->timestamp}");
        $date->setTimezone(new DateTimeZone(core_date::normalise_timezone($user->timezone)));

        $message = [
            'id' => $expected->id,
            'message' => trim($expected->message),
            'timestamp' => $expected->timestamp,
            'human_time' => $date->format('F j, Y, g:i a T'),
        ];

        if ($expected->issystem) {
            switch ($expected->message) {
                case 'enter':
                    $message['message'] = get_string('messageenter', 'chat', fullname($user));
                    break;

                case 'exit':
                    $message['message'] = get_string('messageexit', 'chat', fullname($user));
                    break;
            }
        } else {
            if (mb_substr($message['message'], 0, strlen('/me')) === '/me') {
                $message['message'] = "*** {$user->firstname} " . trim(mb_substr($message['message'], 4));
            }
        }

        $actual = is_array($actual) ? (object) $actual : $actual;
        $message = (object) $message;

        return !!($message->id == $actual->id
                && $message->message == $actual->message
                && $message->timestamp == $actual->timestamp
                && $message->human_time == $actual->human_time);
    }

    /**
     * Count the number of exported messages
     *
     * @param  \totara_userdata\userdata\export $export Export object
     * @return int
     */
    private function count_exported_messages(export $export) {
        return array_reduce($export->data, function ($total, $item) {
            return $total + count($item['Messages']);
        }, 0);
    }

    /**
     * Count related to the current user & context data items
     *
     * @param string $table Table
     * @param context $context
     * @param target_user $user
     * @param int|null $itemid Id of item
     * @param string $courseid Course id field in the database (inconsistent column naming course vs courseid)
     * @return int Number of items found
     * @throws coding_exception
     */
    protected function count_related_data($table, context $context, target_user $user, $itemid = null, $courseid = 'course') {
        global $DB;

        $courseid = clean_param($courseid, PARAM_ALPHANUM);

        switch ($context->contextlevel) {
            case CONTEXT_SYSTEM:
                return $DB->count_records($table, ['userid' => $user->id]);

            case CONTEXT_COURSECAT:
                $courses = array_map('intval', array_keys(get_courses($itemid ?: $context->instanceid)));

                $sql = "SELECT count(target_table.id)
                        FROM {{$table}} target_table
                        JOIN {chat} chat on target_table.chatid = chat.id
                        WHERE chat.{$courseid} IN (" . implode(', ', $courses).  ")
                            AND target_table.userid = " . intval($user->id);
                break;

            case CONTEXT_COURSE:
                $course = $itemid ?: $context->instanceid;

                $sql = "SELECT count(target_table.id)
                        FROM {{$table}} target_table
                        JOIN {chat} chat on target_table.chatid = chat.id
                        WHERE chat.course = {$course} AND target_table.userid = " . intval($user->id);
                break;

            case CONTEXT_MODULE:
                $module = get_coursemodule_from_instance('chat', $itemid);
                $id = intval($module->instance);

                $sql = "SELECT count(target_table.id)
                        FROM {{$table}} target_table
                        WHERE target_table.chatid = {$id} AND target_table.userid = " . intval($user->id);

                break;

            default:
                throw new coding_exception('This context "' . $context->contextlevel . '" is unacceptable here.');
        }

        return $DB->count_records_sql($sql);
    }

    /**
     * Count unrelated to the current user & context data items
     *
     * @param string $table Table
     * @param context $context
     * @param target_user $user
     * @param int|null $itemid Id of item
     * @param string $courseid Course id field in the database (inconsistent column naming course vs courseid)
     * @return int Number of items found
     * @throws coding_exception
     */
    protected function count_unrelated_data($table, context $context, target_user $user, $itemid = null, $courseid = 'course') {
        global $DB;

        $courseid = clean_param($courseid, PARAM_ALPHANUM);

        switch ($context->contextlevel) {
            case CONTEXT_SYSTEM:
                $sql = "SELECT count(target_table.id) FROM {{$table}} target_table WHERE userid <> " . intval($user->id);
                break;

            case CONTEXT_COURSECAT:
                $courses = array_map('intval', array_keys(get_courses($itemid ?: $context->instanceid)));

                $sql = "SELECT count(target_table.id)
                        FROM {{$table}} target_table
                        JOIN {chat} chat on target_table.chatid = chat.id
                        WHERE (chat.{$courseid} NOT IN (" . implode(', ', $courses).  "))
                            OR (chat.{$courseid} IN (" . implode(', ', $courses).  ")
                                AND target_table.userid <> " . intval($user->id) . ')';
                break;

            case CONTEXT_COURSE:
                $course = $itemid ?: $context->instanceid;

                $sql = "SELECT count(target_table.id)
                        FROM {{$table}} target_table
                        JOIN {chat} chat on target_table.chatid = chat.id
                        WHERE (chat.course <> {$course})
                          OR (chat.course = {$course} AND target_table.userid <> " . intval($user->id) . ')';
                break;

            case CONTEXT_MODULE:
                $module = get_coursemodule_from_instance('chat', $itemid);
                $id = intval($module->instance);

                $sql = "SELECT count(target_table.id)
                        FROM {{$table}} target_table
                        WHERE target_table.chatid <> {$id}
                          OR (target_table.chatid = {$id} AND target_table.userid <> " . intval($user->id) . ')';

                break;

            default:
                throw new coding_exception('This context "' . $context->contextlevel . '" is unacceptable here.');
        }

        return $DB->count_records_sql($sql);
    }

    public function test_it_is_countable() {
        $this->assertTrue(messages::is_countable(), 'Message user_data item should be countable');
    }

    public function test_it_is_exportable() {
        $this->assertTrue(messages::is_exportable(), 'Chat message user_data item should be exportable');
    }

    public function test_it_is_purgeable() {
        $this->assertTrue(messages::is_purgeable(target_user::STATUS_ACTIVE), 'Chat message user_data item should be purgeable');
        $this->assertTrue(messages::is_purgeable(target_user::STATUS_SUSPENDED), 'Chat message user_data item should be purgeable');
        $this->assertTrue(messages::is_purgeable(target_user::STATUS_DELETED), 'Chat message user_data item should be purgeable');
    }

    public function test_it_is_compatible_with_wide_range_of_contexts() {
        $expected = [
            CONTEXT_SYSTEM,
            CONTEXT_COURSECAT,
            CONTEXT_COURSE,
            CONTEXT_MODULE
        ];

        $this->assertEqualsCanonicalizing($expected, messages::get_compatible_context_levels(),
            "Chat message user_data item is expected to work with a wide range of contexts");
    }

    public function test_it_purges_chat_messages_for_system_context() {
        $data = $this->seed();
        $context = context_system::instance();
        $user = new target_user(array_values($data['users'])[0]);

        $topurge = 0;
        $unrelated = [];

        foreach ($this->tables() as $table) {
            $topurge +=  $this->count_related_data($table, $context, $user);
            $unrelated[$table] = $this->count_unrelated_data('chat_messages', $context, $user);
        }

        // We need something to test to purge.
        $this->assertGreaterThan(0, $topurge, $this->errors('nothing_to_purge'));

        // Initializing mighty purger.
        $status = messages::execute_purge($user, $context);

        // Purged successfully.
        $this->assertEquals(messages::RESULT_STATUS_SUCCESS, $status, $this->errors('purge_failed'));

        foreach ($this->tables() as $table) {
            // Purged what had to be purged.
            $this->assertEquals(0, $this->count_related_data($table, $context, $user),
                $this->errors('underdone_purge'));

            // Did not touch what should not have been touched.
            $this->assertEquals($unrelated[$table], $this->count_unrelated_data($table, $context, $user),
                $this->errors('excessive_purge'));
        }

        $this->resetAfterTest();
    }

    public function test_it_purges_chat_messages_for_course_category_context() {
        $data = $this->seed();
        $context = context_coursecat::instance($cat = array_keys($data['cats'])[0]);
        $user = new target_user(array_values($data['users'])[0]);

        $topurge = 0;
        $unrelated = [];

        foreach ($this->tables() as $table) {
            $topurge += $this->count_related_data($table, $context, $user);
            $unrelated[$table] = $this->count_unrelated_data($table, $context, $user);
        }

        // Initializing mighty purger.
        $status = messages::execute_purge($user, $context);

        // Purged successfully.
        $this->assertEquals(messages::RESULT_STATUS_SUCCESS, $status, $this->errors('purge_failed'));

        foreach ($this->tables() as $table) {
            // Purged what had to be purged.
            $this->assertEquals(0, $this->count_related_data($table, $context, $user),
                $this->errors('underdone_purge'));

            // Did not touch what should not have been touched.
            $this->assertEquals($unrelated[$table], $this->count_unrelated_data($table, $context, $user),
                $this->errors('excessive_purge'));
        }

        $this->resetAfterTest();
    }

    public function test_it_purges_chat_messages_for_course_context() {
        $data = $this->seed();
        $context = context_course::instance($course = intval(array_keys(array_values($data['cats'])[0])[0]));
        $user = new target_user(array_values($data['users'])[0]);

        $topurge = 0;
        $unrelated = [];

        foreach ($this->tables() as $table) {
            $topurge += $this->count_related_data($table, $context, $user);
            $unrelated[$table] = $this->count_unrelated_data($table, $context, $user);
        }

        $this->assertGreaterThan(0, $topurge, $this->errors('nothing_to_purge'));

        // Initializing mighty purger.
        $status = messages::execute_purge($user, $context);

        // Purged successfully.
        $this->assertEquals(messages::RESULT_STATUS_SUCCESS, $status, $this->errors('purge_failed'));

        foreach ($this->tables() as $table) {
            // Purged what had to be purged.
            $this->assertEquals(0, $this->count_related_data($table, $context, $user),
                $this->errors('underdone_purge'));

            // Did not touch what should not have been touched.
            $this->assertEquals($unrelated[$table], $this->count_unrelated_data($table, $context, $user),
                $this->errors('excessive_purge'));
        }

        $this->resetAfterTest();
    }

    public function test_it_purges_chat_messages_for_module_context() {
        $data = $this->seed();
        $module = get_coursemodule_from_instance('chat',
            array_keys(array_values(array_values($data['cats'])[0])[0])[0]);
        $user = new target_user(array_values($data['users'])[0]);
        $context = context_module::instance($module->id);

        $topurge = 0;
        $unrelated = [];

        foreach ($this->tables() as $table) {
            $topurge += $this->count_related_data($table, $context, $user, $module->instance);
            $unrelated[$table] = $this->count_unrelated_data($table, $context, $user, $module->instance);
        }

        $this->assertGreaterThan(0, $topurge, $this->errors('nothing_to_purge'));

        // Initializing mighty purger.
        $status = messages::execute_purge(new target_user($user), $context);

        // Purged successfully.
        $this->assertEquals(messages::RESULT_STATUS_SUCCESS, $status, $this->errors('purge_failed'));

        foreach ($this->tables() as $table) {
            // Purged what had to be purged.
            $this->assertEquals(0, $this->count_related_data($table, $context, $user, $module->instance),
                $this->errors('underdone_purge'));

            // Did not touch what should not have been touched.
            $this->assertEquals($unrelated[$table], $this->count_unrelated_data($table, $context, $user, $module->instance),
                $this->errors('excessive_purge'));
        }

        $this->resetAfterTest();
    }

    public function test_it_exports_chat_messages_for_system_context() {
        $data = $this->seed();
        global $DB;

        $user = array_values($data['users'])[0];
        $messages = $DB->get_records('chat_messages', ['userid' => $user->id]);

        // Doing export
        $export = messages::execute_export(new target_user($user), context_system::instance());

        foreach ($messages as $message) {
            $this->assertTrue($this->messages_match($message,
                $this->find_exported_message_by_id($export, $message->id), $user), $this->errors('message_does_not_match'));
        }

        $this->assertEquals(count($messages), $this->count_exported_messages($export), $this->errors('exported_count_wrong'));

        $this->resetAfterTest();
    }

    public function test_it_exports_chat_messages_for_course_category_context() {
        $data = $this->seed();
        global $DB;

        $user = array_values($data['users'])[1];
        $context = context_coursecat::instance($cat = array_keys($data['cats'])[0]);

        $joins = messages::get_activities_join($context, 'chat', 'messages.chatid');

        $messages = $DB->get_records_sql("SELECT messages.* FROM {chat_messages} messages $joins 
                                                WHERE userid = :user_id", ['user_id' => $user->id]);

        // Doing export
        $export = messages::execute_export(new target_user($user), $context);

        foreach ($messages as $message) {
            $this->assertTrue($this->messages_match($message,
                $this->find_exported_message_by_id($export, $message->id), $user), $this->errors('message_does_not_match'));
        }

        $this->assertEquals(count($messages), $this->count_exported_messages($export), $this->errors('exported_count_wrong'));

        $this->resetAfterTest();
    }

    public function test_it_exports_chat_messages_for_course_context() {
        $data = $this->seed();
        global $DB;

        $user = array_values($data['users'])[1];
        $context = context_course::instance($course = intval(array_keys(array_values($data['cats'])[0])[0]));

        $joins = messages::get_activities_join($context, 'chat', 'messages.chatid');

        $messages = $DB->get_records_sql("SELECT messages.* FROM {chat_messages} messages $joins 
                                                WHERE userid = :user_id", ['user_id' => $user->id]);

        // Doing export
        $export = messages::execute_export(new target_user($user), $context);

        foreach ($messages as $message) {
            $this->assertTrue($this->messages_match($message,
                $this->find_exported_message_by_id($export, $message->id), $user), $this->errors('message_does_not_match'));
        }

        $this->assertEquals(count($messages), $this->count_exported_messages($export), $this->errors('exported_count_wrong'));

        $this->resetAfterTest();
    }

    public function test_it_exports_chat_messages_for_course_module_context() {
        $data = $this->seed();
        global $DB;

        $module = get_coursemodule_from_instance('chat',
            array_keys(array_values(array_values($data['cats'])[0])[0])[0]);

        $user = array_values($data['users'])[0];
        $context = context_module::instance($module->id);

        $joins = messages::get_activities_join($context, 'chat', 'messages.chatid');

        $messages = $DB->get_records_sql("SELECT messages.* FROM {chat_messages} messages $joins 
                                                WHERE userid = :user_id", ['user_id' => $user->id]);

        // Doing export
        $export = messages::execute_export(new target_user($user), $context);

        foreach ($messages as $message) {
            $this->assertTrue($this->messages_match($message,
                $this->find_exported_message_by_id($export, $message->id), $user), $this->errors('message_does_not_match'));
        }

        $this->assertEquals(count($messages), $this->count_exported_messages($export), $this->errors('exported_count_wrong'));

        $this->resetAfterTest();
    }

    public function test_it_counts_chat_messages_for_system_context() {
        $data = $this->seed();
        $user = new target_user(array_values($data['users'])[1]);
        $context = context_system::instance();

        $this->assertEquals($this->count_related_data('chat_messages', $context, $user),
            messages::execute_count($user, $context), $this->errors('count_does_not_match'));

        $this->resetAfterTest();
    }

    public function test_it_counts_chat_messages_for_course_category_context() {
        $data = $this->seed();
        $user = new target_user(array_values($data['users'])[1]);
        $context = context_coursecat::instance($cat = array_keys($data['cats'])[1]);

        $this->assertEquals($this->count_related_data('chat_messages', $context, $user),
            messages::execute_count($user, $context), $this->errors('count_does_not_match'));

        $this->resetAfterTest();
    }

    public function test_it_counts_chat_messages_for_course_context() {
        $data = $this->seed();
        $user = new target_user(array_values($data['users'])[1]);
        $context = context_course::instance($course = intval(array_keys(array_values($data['cats'])[1])[1]));

        $this->assertEquals($this->count_related_data('chat_messages', $context, $user),
            messages::execute_count($user, $context), $this->errors('count_does_not_match'));

        $this->resetAfterTest();
    }

    public function test_it_counts_chat_messages_for_module_context() {
        $data = $this->seed();
        $module = get_coursemodule_from_instance('chat',
            array_keys(array_values(array_values($data['cats'])[0])[1])[0]);

        $user = new target_user(array_values($data['users'])[1]);
        $context = context_module::instance($module->id);

        $this->assertEquals($this->count_related_data('chat_messages', $context, $user, $module->instance),
            messages::execute_count($user, $context), $this->errors('count_does_not_match'));

        $this->resetAfterTest();
    }
}
