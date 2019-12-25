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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package core_message
 */

namespace core_message\userdata;

/**
 * Helper class
 */
class messages_purging_helper {

    const USERID_DIRECTION_FROM = 'useridfrom';
    const USERID_DIRECTION_TO = 'useridto';

    /**
     * @param int $userid
     * @param array $eventtypes
     */
    public function delete_sent_messages(int $userid, array $eventtypes): void {
        $this->delete_messages($userid, $eventtypes, self::USERID_DIRECTION_FROM);
    }

    /**
     * @param int $userid
     * @param array $eventtypes
     */
    public function delete_received_messages(int $userid, array $eventtypes): void {
        $this->delete_messages($userid, $eventtypes, self::USERID_DIRECTION_TO);
    }

    /**
     * Delete messages by given eventtype (instant_messages, etc.) and direction (from, to)
     * @param int $userid
     * @param array $eventtypes
     * @param string $direction (self::USERID_DIRECTION_TO, self::USERID_DIRECTION_FROM)
     */
    private function delete_messages(int $userid, array $eventtypes, string $direction): void {
        global $DB;

        if ($direction != self::USERID_DIRECTION_FROM && $direction != self::USERID_DIRECTION_TO) {
            throw new \moodle_exception('Invalid direction given!');
        }

        $eventtypesjoined = sprintf("'%s'", implode("', '", $eventtypes));

        // Delete constraints for useridfrom.
        $condition = '%s IN (
            SELECT id
            FROM {%s}
            WHERE %s = ?
                AND eventtype IN ('.$eventtypesjoined.')
        )';

        // Delete message popup data.
        // -------------------------------
        $useridcondition = sprintf($condition, 'messageid', 'message', $direction);
        $DB->delete_records_select('message_popup', $useridcondition, [$userid]);

        // Delete message metadata.
        // -------------------------------
        $useridcondition = sprintf($condition, 'messagereadid', 'message_read', $direction);
        $DB->delete_records_select('message_metadata', $useridcondition, [$userid]);

        $useridcondition = sprintf($condition, 'messageid', 'message', $direction);
        $DB->delete_records_select('message_metadata', $useridcondition, [$userid]);

        // Delete message working data.
        // -------------------------------
        $useridcondition = sprintf($condition, 'unreadmessageid', 'message', $direction);
        $DB->delete_records_select('message_working', $useridcondition, [$userid]);

        // Delete messages.
        // -------------------------------
        $useridcondition = sprintf('%s = ? AND eventtype IN (%s)', $direction, $eventtypesjoined);
        $DB->delete_records_select('message', $useridcondition, [$userid]);
        $DB->delete_records_select('message_read', $useridcondition, [$userid]);
    }

}
