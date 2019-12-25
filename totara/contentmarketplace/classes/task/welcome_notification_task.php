<?php
/*
 * This file is part of Totara LMS
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
 * @author Simon Coggins <simon.coggins@totaralearning.com>
 * @package totara_contentmarketplace
 */

namespace totara_contentmarketplace\task;

class welcome_notification_task extends \core\task\adhoc_task {
    /**
     * Constructor.
     */
    public function __construct() {
        $this->set_component('totara_contentmarketplace');
    }

    /**
     * Send out messages.
     */
    public function execute() {
        global $CFG, $OUTPUT;
        $users = array_keys(get_users_by_capability(\context_system::instance(), 'totara/contentmarketplace:config', 'u.id'));
        $admins = explode(',', $CFG->siteadmins);
        $userids = array_merge($users, $admins);

        $url = new \moodle_url('/totara/contentmarketplace/setup.php');
        $button = $OUTPUT->single_button($url, get_string('setup_content_marketplaces', 'totara_contentmarketplace'), 'get');
        foreach ($userids as $userid) {
            $user = \core_user::get_user($userid);
            if (empty($user)) {
                // User doesn't exist. Should never happen, nothing to do.
                mtrace("User {$userid} not found.");
                continue;
            }
            $context = \context_user::instance($user->id, IGNORE_MISSING);
            if ($context === false) {
                // User context doesn't exist. Should never happen, nothing to do.
                mtrace("No user context for user {$userid}.");
                continue;
            }
            $msgdata = new \core\message\message();
            $msgdata->courseid          =  SITEID;
            $msgdata->component         = 'totara_contentmarketplace'; // Your component name.
            $msgdata->name              = 'notification'; // This is the message name from messages.php.
            $msgdata->userfrom          = \core_user::get_noreply_user();
            $msgdata->userto            = $user;
            $msgdata->subject           = get_string('marketplacenotificationsubject', 'totara_contentmarketplace');
            $msgdata->fullmessage       = get_string('marketplacenotificationbodytext', 'totara_contentmarketplace', $url->out());
            $msgdata->fullmessageformat = FORMAT_HTML;
            $msgdata->fullmessagehtml   = get_string('marketplacenotificationbodyhtml', 'totara_contentmarketplace', $button);
            $msgdata->smallmessage      = '';
            $msgdata->notification      = 1; // This is only set to 0 for personal messages between users.

            mtrace("Sending message to the user with id " . $msgdata->userto->id . "...");
            message_send($msgdata);
            mtrace("Sent.");
        }
    }
}
