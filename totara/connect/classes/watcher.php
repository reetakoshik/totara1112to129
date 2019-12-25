<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2017 onwards Totara Learning Solutions LTD
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
 * @package totara_connect
 */

namespace totara_connect;


/**
 * Class with hooks watchers.
 */
class watcher {

    /**
     * Hook watcher that overrides return url when editing user profile
     * in user/edit.php or user/editadvanced.php page.
     *
     * @param \core_user\hook\profile_edit_returnto $hook
     */
    public static function profile_edit_returnto(\core_user\hook\profile_edit_returnto $hook) {
        global $CFG, $DB;
        if (empty($CFG->enableconnectserver)) {
            return;
        }

        $returnto = $hook->returnto;
        $prefix = 'tc_';

        if (strpos($returnto, $prefix) !== 0) {
            return;
        }

        $clientidnumber = substr($returnto, strlen($prefix));
        $client = $DB->get_record('totara_connect_clients', array('clientidnumber' => $clientidnumber));

        if (!$client) {
            return;
        }

        $hook->returnurl = new \moodle_url($client->clienturl . '/auth/connect/user_edit_finish.php', array('serveruserid' => $hook->user->id, 'clientidnumber' => $clientidnumber));
    }
}