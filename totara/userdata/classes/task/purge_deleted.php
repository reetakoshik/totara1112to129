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
use totara_userdata\userdata\manager;
use totara_userdata\local\purge;

/**
 * Scheduled task for automatic purging of user data after user deletion.
 */
final class purge_deleted extends \core\task\scheduled_task {
    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('taskpurgedeleted', 'totara_userdata');
    }

    /**
     * Execute task.
     */
    public function execute() {
        $results = manager::get_results();

        self::detect_purge_timeouts();

        do {
            $info = self::get_next_user_to_purge();
            if (!$info) {
                break;
            }

            // Create purges as admin.
            $admin = get_admin();
            cron_setup_user($admin);

            list($purgetypeid, $userid) = $info;
            $purgeid = manager::create_purge($userid, SYSCONTEXTID, $purgetypeid, 'deleted');

            mtrace("Processing purge for deleted user id " . $userid);
            $result = manager::execute_purge($purgeid);
            mtrace('Purge finished - ' . $results[$result]);

        } while (true);
    }

    /**
     * Returns next user to purge.
     *
     * NOTE: we need to call this repeatedly because this job may be running in parallel.
     *
     * @return array|null
     */
    public static function get_next_user_to_purge() {
        global $DB;

        $sql = "SELECT u.id, tuu.deletedpurgetypeid
                  FROM {user} u
                  JOIN {totara_userdata_user} tuu ON tuu.userid = u.id
                  JOIN {totara_userdata_purge_type} pt ON pt.id = tuu.deletedpurgetypeid
             LEFT JOIN {totara_userdata_purge} p ON (p.userid = u.id AND p.origin = 'deleted' AND p.result IS NULL)
                 WHERE u.deleted = 1 AND p.id IS NULL
                       AND (tuu.timedeletedpurged IS NULL OR tuu.timedeletedpurged < tuu.timedeleted OR tuu.timedeletedpurged < pt.timechanged)";
        $users = $DB->get_records_sql($sql, array(), 0, 1);
        if ($users) {
            $user = reset($users);
            return array($user->deletedpurgetypeid, $user->id);
        }

        return null;
    }

    /**
     * Finds all auto purges that did not complete in one day and marks them as timed out.
     */
    public static function detect_purge_timeouts() {
        global $DB;

        $sql = "SELECT p.id
                  FROM {totara_userdata_purge} p
                 WHERE p.origin = 'deleted' AND p.result IS NULL AND p.timestarted < :cutoff";
        $purges = $DB->get_records_sql($sql, array('cutoff' => time() - purge::MAX_TOTAL_EXECUTION_TIME));

        foreach ($purges as $purge) {
            // This should not happen often, so performance does not really matter much here.
            $trans = $DB->start_delegated_transaction();
            $now = time();

            $DB->set_field_select('totara_userdata_purge_item', 'timefinished', $now, "purgeid = ? AND result IS NULL", array($purge->id));
            $DB->set_field_select('totara_userdata_purge_item', 'result', item::RESULT_STATUS_TIMEDOUT, "purgeid = ? AND result IS NULL", array($purge->id));

            $purge->timefinished = $now;
            $purge->result = item::RESULT_STATUS_TIMEDOUT;
            $DB->update_record('totara_userdata_purge', $purge);

            $trans->allow_commit();
        }
    }
}

