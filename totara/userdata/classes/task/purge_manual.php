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

use \totara_userdata\userdata\manager;

/**
 * Ad-hoc tack for manual purging of user data.
 */
final class purge_manual extends \core\task\adhoc_task {
    /**
     * Execute task.
     */
    public function execute() {
        global $DB;

        $purge = $DB->get_record('totara_userdata_purge', array('id' => $this->get_custom_data(), 'origin' => 'manual'));
        if (!$purge) {
            // This should not happen!
            return;
        }
        $results = manager::get_results();

        mtrace("Processing purge request for user id " . $purge->userid);
        $result = manager::execute_purge($purge->id);

        mtrace('Purge - ' . $results[$result]);

        // Load the purge results.
        $purge = $DB->get_record('totara_userdata_purge', array('id' => $purge->id), '*', MUST_EXIST);
        $this->notify_result($purge);
    }

    /**
     * Send notification to user.
     *
     * @param \stdClass $purge
     */
    private function notify_result(\stdClass $purge) {
        global $DB;

        $userto = $DB->get_record('user', array('id' => $purge->usercreated), '*', MUST_EXIST);
        $user = $DB->get_record('user', array('id' => $purge->userid), '*', MUST_EXIST);

        $subject = get_string('notificationpurgemanualsubject', 'totara_userdata');

        $results = manager::get_results();

        $a = new \stdClass();
        $a->result = $results[$purge->result];
        $a->fullnameuser = fullname($user);;
        $body = get_string('notificationpurgemanualmessage', 'totara_userdata', $a);

        $message = new \core\message\message();
        $message->courseid          = 0;
        $message->notification      = 1;
        $message->component         = 'totara_userdata';
        $message->name              = 'purge_manual_finished';
        $message->userfrom          = \core_user::get_noreply_user();
        $message->userto            = $userto;
        $message->subject           = $subject;
        $message->fullmessage       = $body;
        $message->fullmessageformat = FORMAT_MARKDOWN;
        $message->fullmessagehtml   = markdown_to_html($body);
        $message->smallmessage      = $subject;
        $message->contexturl        = new \moodle_url('/totara/userdata/user_info.php', array('id' => $user->id));
        $message->contexturlname    = get_string('purgemanually', 'totara_userdata');

        message_send($message);
    }
}

