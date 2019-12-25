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
 * @package mod_chat
 */

namespace mod_chat\userdata;


use stdClass;

/**
 * Class messages_helper to perform count, purge or export of chat message userdata item
 * @package mod_chat\userdata
 */
final class messages_helper extends chat_userdata_helper {

    /**
     * Export chat messages
     *
     * @return array
     */
    public function export() {

        [$where, $joins, $params] = $this->prepare_query('target.id', '');

        $sql = "SELECT target.id, target.name, target.intro, course.fullname as course_name from {chat} target $joins
                JOIN {course} course ON course.id = target.course 
                WHERE EXISTS (SELECT messages.chatid, messages.userid FROM {chat_messages} messages
                      WHERE messages.chatid = target.id and messages.userid = :user_id)";

        $set = $this->db->get_recordset_sql($sql, $params);

        $output = [];

        foreach ($set as $chat) {
            $messages = array_map([$this, 'parse_message'], $this->db->get_records('chat_messages',
                ['chatid' => $chat->id, 'userid' => $this->user->id], 'timestamp asc'));

            $output[] = [
                'Course' => $chat->course_name,
                'Chat' => $chat->name,
                'Description' => $chat->intro,
                'Messages' => $messages,
            ];
        }

        $set->close();

        return $output;
    }

    /**
     * Purge user's messages
     *
     * @return bool
     */
    public function purge() {

        [$where, $joins, $params] = $this->prepare_query('messages.chatid', 'messages.userid');

        $tables = [
            'chat_messages',
            'chat_messages_current'
        ];

        foreach ($tables as $table) {
            $sql = "SELECT messages.id from {{$table}} messages $joins WHERE $where";

            $this->db->delete_records_list($table, 'id', array_keys($this->db->get_records_sql($sql, $params)));
        }

        // There is no actual point of checking what delete_records_list returns as it always returns true.
        return true;
    }

    /**
     * Count chat messages
     *
     * @return int Messages count
     */
    public function count() {

        [$where, $joins, $params] = $this->prepare_query('messages.chatid', 'messages.userid');

        $sql = "SELECT COUNT(messages.id) from {chat_messages} messages $joins WHERE $where";

        return $this->db->count_records_sql($sql, $params);
    }

    /**
     * Parse chat message returned from the database to the export format
     *
     * @param \stdClass $message Message retrieved from the database
     * @return \stdClass Parsed message
     */
    protected function parse_message(stdClass $message) {
        $text = trim($message->message);

        if ($message->issystem) {
            $text = $this->parse_system_message($text);
        } else {
            $text = $this->parse_special_message($text);
        }

        return (object) [
            'id' => $message->id,
            'timestamp' => $message->timestamp,
            'human_time' => $this->human_time($message->timestamp),
            'message' => $text,
        ];
    }

    /**
     * Parse special message
     *
     * @param string $text Message text
     * @param null|string $type Special message type
     * @return string
     */
    protected function parse_special_message($text, $type = null) {
        if (is_null($type)) {
            $type = $this->is_special($text);
        }

        switch ($type) {
            case 'me':
                $msg = "*** {$this->user->firstname} " . trim(mb_substr($text, 4));
                break;

            default:
                $msg = $text;
                break;
        }

        return $msg;
    }

    /**
     * Determine special message type
     *
     * @param string $text Message text
     * @return false|string Type or false if not special
     */
    protected function is_special($text) {
        $markers = [
            'me' => '/me ',
            'beep' => 'beep '
        ];

        foreach ($markers as $key => $marker) {
            if (mb_substr($text, 0, strlen($marker)) === (string) $marker) {
                return $key;
            }
        }

        return false;
    }

    /**
     * Parse system message
     *
     * @param string $text Message text
     * @return string Parsed message text
     */
    protected function parse_system_message(string $text) {
        switch ($text) {
            case 'enter':
                $output = get_string('messageenter', 'chat', $this->name);
                break;

            case 'exit':
                $output = get_string('messageexit', 'chat', $this->name);
                break;

            default:
                $output = $text;
                break;
        }

        return $output;
    }
}