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
 * @author Ciaran Irvine <ciaran.irvine@totaralms.com>
 * @package totara_feedback360
 */

namespace totara_feedback360\task;

/**
 * Clean up deleted users still assigned to feedback.
 */
class cleanup_task extends \core\task\scheduled_task {

    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('cleanuptask', 'totara_feedback360');
    }

    /**
     * Periodic cron cleanup.
     */
    public function execute() {
        global $DB, $CFG;
        require_once($CFG->dirroot.'/totara/feedback360/lib.php');
        // Get all deleted users still assigned to a feedback in user_assignment or resp_assignment.
        $sql = "SELECT DISTINCT u.id, u.username, u.email, u.idnumber, u.picture, u.mnethostid
                  FROM {user} u
       LEFT OUTER JOIN {feedback360_user_assignment} fua ON u.id = fua.userid
       LEFT OUTER JOIN {feedback360_resp_assignment} fra ON u.id = fra.userid
                 WHERE u.deleted <> 0
                   AND (fua.id IS NOT NULL OR fra.id IS NOT NULL)";
        $deletedusers = $DB->get_recordset_sql($sql, array());
        // This could take some time and use a lot of resources.
        \core_php_time_limit::raise(0);
        raise_memory_limit(MEMORY_EXTRA);
        $context = \context_system::instance();
        foreach ($deletedusers as $user) {
            $event = \core\event\user_deleted::create(
                array(
                    'relateduserid' => $user->id,
                    'objectid' => $user->id,
                    'context' => $context,
                    'other' => array(
                        'username' => $user->username,
                        'email' => $user->email,
                        'idnumber' => $user->idnumber,
                        'picture' => $user->picture,
                        'mnethostid' => $user->mnethostid
                    )
            ));
            \feedback360_event_handler::feedback360_user_deleted($event);
        }
        $deletedusers->close();
    }
}
