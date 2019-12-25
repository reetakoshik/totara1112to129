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

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/totara/reportbuilder/tests/generator/lib.php');

/**
 * Program generator.
 *
 * @package totara_program
 * @subpackage test
 */
class core_message_generator extends component_generator_base {

    /**
     * Create mock programs.
     *
     * @param int $userid userid
     *
     * @return array(int $messageid, int $messagereadid)
     */
    public function create_message_data(
        int $useridfrom,
        int $useridto,
        string $type = 'instantmessage',
        string $subject = 'Test message'
    ) {
        global $DB, $CFG;

        require_once($CFG->dirroot . '/totara/message/messagelib.php');

        $message = [
            'useridfrom' => $useridfrom,
            'useridto' => $useridto,
            'subject' => $subject,
            'timecreated' => time(),
            'eventtype' => $type
        ];
        $messageid = $DB->insert_record('message', (object)$message);

        $DB->insert_record('message_metadata', (object)[
            'messageid' => $messageid,
            'msgtype' => TOTARA_MSG_TYPE_UNKNOWN,
            'msgstatus' => TOTARA_MSG_STATUS_UNDECIDED,
            'processorid' => 1,
            'urgency' => TOTARA_MSG_URGENCY_NORMAL
        ]);

        $messageread = [
            'useridfrom' => $useridfrom,
            'useridto' => $useridto,
            'subject' => $subject,
            'timecreated' => time(),
            'eventtype' => $type
        ];
        $messagereadid = $DB->insert_record('message_read', (object)$messageread);

        $DB->insert_record('message_metadata', (object)[
            'messagereadid' => $messagereadid,
            'msgtype' => TOTARA_MSG_TYPE_UNKNOWN,
            'msgstatus' => TOTARA_MSG_STATUS_UNDECIDED,
            'processorid' => 1,
            'urgency' => TOTARA_MSG_URGENCY_NORMAL
        ]);

        $DB->insert_record('message_popup', (object)[
            'idread' => 0,
            'messageid' => $messageid
        ]);

        $DB->insert_record('message_working', (object)[
            'processorid' => 0,
            'unreadmessageid' => $messageid
        ]);

        return [$messageid, $messagereadid];
    }

}
