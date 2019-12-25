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
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 * @package totara_userdata
 */

namespace totara_userdata\task;

use totara_userdata\userdata\item;
use \totara_userdata\userdata\manager;

/**
 * Ad-hoc tack for export of user data.
 */
final class export extends \core\task\adhoc_task {
    /**
     * Execute task.
     */
    public function execute() {
        global $DB;

        $export = $DB->get_record('totara_userdata_export', array('id' => $this->get_custom_data()));
        if (!$export) {
            // This should not happen!
            return;
        }
        $results = manager::get_results();

        // We may need a LOT of memory.
        raise_memory_limit(MEMORY_HUGE);

        mtrace("Processing export request for user id " . $export->userid);
        $result = manager::execute_export($export->id);

        mtrace('Export - ' . $results[$result]);

        // Load the export results.
        $export = $DB->get_record('totara_userdata_export', array('id' => $export->id), '*', MUST_EXIST);
        $this->notify_result($export);
    }

    /**
     * Send notification to user.
     *
     * @param \stdClass $export
     */
    private function notify_result(\stdClass $export) {
        global $DB;

        if ($export->origin !== 'self') {
            // Not implemented.
            return;
        }

        $userto = $DB->get_record('user', array('id' => $export->usercreated), '*', MUST_EXIST);

        $subject = get_string('notificationexportselfsubject', 'totara_userdata');
        if ($export->result == item::RESULT_STATUS_SUCCESS) {
            $availableuntil = userdate($export->timefinished + \totara_userdata\local\export::MAX_FILE_AVAILABILITY_TIME);
            $body = get_string('notificationexportselfmessage', 'totara_userdata', $availableuntil);
        } else {
            $body = get_string('notificationexportselfmessage_unsuccessful', 'totara_userdata');
        }

        $message = new \core\message\message();
        $message->courseid          = 0;
        $message->notification      = 1;
        $message->component         = 'totara_userdata';
        $message->name              = 'export_self_finished';
        $message->userfrom          = \core_user::get_noreply_user();
        $message->userto            = $userto;
        $message->subject           = $subject;
        $message->fullmessage       = $body;
        $message->fullmessageformat = FORMAT_MARKDOWN;
        $message->fullmessagehtml   = markdown_to_html($body);
        $message->smallmessage      = $subject;
        $message->contexturl        = new \moodle_url('/totara/userdata/export_request.php');
        $message->contexturlname    = get_string('export', 'totara_userdata');

        message_send($message);
    }
}

