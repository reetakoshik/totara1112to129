<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2010 onwards Totara Learning Solutions LTD
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
 * @author Valerii Kuznetsov <valerii.kuznetsov@totaralms.com>
 * @package totara_core
 */

/**
 * Check if registration information should be sent, and if so send it
 */
namespace totara_core\task;

class send_registration_data_task extends \core\task\scheduled_task {

    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('sendregistrationdatatask', 'totara_core');
    }

    /**
     * Check if registration information should be sent, and if so send it
     */
    public function execute() {
        global $CFG;
        require_once($CFG->dirroot.'/admin/registerlib.php');

        if (empty($CFG->registrationenabled)) {
            mtrace("Registration updates are disabled");
            return;
        }

        if (isset($CFG->registered) and $CFG->registered <= time()) {
            if (time() - $CFG->registered < 60*60*24*3) {
                mtrace("Registration was already updated less than 3 days ago");
                return;
            }
        }

        mtrace("Performing registration update:");
        $registerdata = get_registration_data();
        send_registration_data($registerdata);
        mtrace("Registration update done");
    }
}