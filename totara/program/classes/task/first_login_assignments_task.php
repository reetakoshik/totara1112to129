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
 * @author Ben Lobo <ben.lobo@kineo.com>
 * @author Valerii Kuznetsov <valerii.kuznetsov@totaralms.com>
 * @package totara_program
 */

namespace totara_program\task;

class first_login_assignments_task extends \core\task\scheduled_task {
    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('firstloginassignmentstask', 'totara_program');
    }

    /**
     * Looks for users with future assignment records who have logged in
     *
     * If any are found an event is triggered to activate the future assignment.
     * This function should only be needed to catch logins via third-party
     * authentication plugins, since all the existing auth plugins have had an
     * event trigger added.
     */
    public function execute() {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/totara/program/lib.php');
        require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');
        require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');

        // Don't run programs cron if programs and certifications are disabled.
        if (totara_feature_disabled('programs') &&
            totara_feature_disabled('certifications')) {
            return false;
        }

        $pending_user_sql = "SELECT u.*, pfa.programid
                            FROM {user} u
                            INNER JOIN {prog_future_user_assignment} pfa
                            ON pfa.userid = u.id
                            WHERE u.firstaccess > 0";

        $pending_users = $DB->get_records_sql($pending_user_sql);
        foreach ($pending_users as $pending_user) {
            // Skip update if the program is not accesible for the user.
            $program = new \program($pending_user->programid);
            if ($program->is_viewable($pending_user)) {
                prog_assignments_firstlogin($pending_user);
            }
        }
    }
}

