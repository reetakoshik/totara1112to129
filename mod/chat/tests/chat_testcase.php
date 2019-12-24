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

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/chat/lib.php');

abstract class chat_testcase extends advanced_testcase {

    /**
     * Shorthand for data generator
     *
     * @return testing_data_generator
     */
    protected function generator() {
        return $this->getDataGenerator();
    }

    /**
     * Seed initial dummy data
     *
     *[
     *  'users' => [user objects]
     *  'cats' => [
     *      'category_id' => [
     *          'course_id' => [
     *              'chat_activity_id' => [
     *                  'user_id' => [Chat message ids posted by this user],
     *                  ...
     *              ],
     *              ...
     *          ],
     *          ...
     *      ],
     *      ...
     *  ]
     * ]
     *
     * @param int $count How many items to create
     * @return array Generated data ID's See above for data structure
     * @throws coding_exception
     */
    protected function seed($count = 2) {
        $data = [];

        // Need to create a few course categories
        // Then create a few different courses
        // Then create a few different chat instances
        // Then chat a lot (seed messages).

        for($i = 1; $i <= $count; $i++) {
            $user = $this->generator()->create_user();
            $data['users'][$user->id] = $user;
        }

        for($i = 1; $i <= $count; $i++) {
            $cat = $this->generator()->create_category();

            $data['cats'][$cat->id] = [];

            for($j = 1; $j <= $count; $j++) {
                $course = $this->generator()->create_course(['category' => $cat->id]);

                $data['cats'][$cat->id][$course->id] = [];

                for($k = 1; $k <= $count; $k++) {
                    $chat = $this->create_chat($course);

                    $data['cats'][$cat->id][$course->id][$chat->id] = [];

                    foreach ($data['users'] as $user) {
                        $messages = $this->create_chat_message($this->create_chat_user($user, $chat),
                            "{$cat->name} / {$course->shortname} / {$chat->name} /", $count);

                        $data['cats'][$cat->id][$course->id][$chat->id][$user->id] = $messages;
                    }
                }
            }
        }

        return $data;
    }

    /**
     * Create new chat-room with a given parameters
     *
     * @param array|int $params Array of parameters or course id as a shorthand.
     * @return stdClass
     */
    protected function create_chat($params = []) {
        $default = [
            'name' => 'Chat room #' . rand(1, 1000),
            'intro' => random_string(),
            'introformat' => 1,
            'keepdays' => 0,
            'studentlogs' => 0,
            'chattime' => time() - DAYSECS,
            'schedule' => 0,
            'timemodified' => time(),
        ];

        if (!is_array($params)) {
            $params = ['course' => $params];
        } elseif (empty($params)) {
            $params = ['course' => $this->course];
        }

        $params = array_merge($default, $params);

        return $this->generator()->create_module('chat', $params);
    }

    /**
     * Create a chat user
     *
     * @param stdClass $user User object
     * @param stdClass|int $chat Chat object or id
     * @return false|stdClass Returns chat user object or false in case of failure
     */
    protected function create_chat_user($user, $chat) {
        global $USER, $DB;

        // Hijack the user.
        $currentuser = $USER;

        $USER = $user;

        if (!is_object($chat)) {
            $chat = $DB->get_record('chat', ['id' => $chat]);
        }

        $sid = chat_login_user($chat->id, 'ajax', 0, get_course($chat->course));

        // Return the user.
        $USER = $currentuser;

        if ($sid) {
            return $DB->get_record('chat_users', ['sid' => $sid]);
        }

        return false;
    }

    /**
     * Create a chat message via chat API
     *
     * @param stdClass $chatuser User object
     * @param string $text Message text
     * @param int $count Number of messages to create
     * @return array|int returns ID of chat messages or array messages for IDs
     * @throws coding_exception
     */
    protected function create_chat_message(stdClass $chatuser, $text = 'This is a sample message', $count = 1) {
        $cm = get_coursemodule_from_instance('chat', $chatuser->chatid, $chatuser->course);

        if ($count == 1) {
            return chat_send_chatmessage($chatuser, $text, in_array($text, ['enter', 'exit']), $cm);
        }

        $messages = [];

        for ($i = 0; $i < $count; $i++) {

            $txt = $text . ' #' . ($i + 1);

            $messages[] = chat_send_chatmessage($chatuser, $txt, in_array($txt, ['enter', 'exit']), $cm);
        }

        return $messages;
    }
}
