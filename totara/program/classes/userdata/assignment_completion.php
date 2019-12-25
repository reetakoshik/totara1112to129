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
 * @package totara_program
 */

namespace totara_program\userdata;

use context;
use totara_userdata\userdata\export;
use totara_userdata\userdata\target_user;

/**
 * This item takes care of purging, exporting and counting program assignments and completion.
 * This only targets programs. Certification completion is handled by {@link \totara_certification\userdata\assignment_completion}.
 * History and logs are included within this item.
 */
class assignment_completion extends base_assignment_completion {

    /**
     * Purge user data for this item.
     *
     * NOTE: Remember that context record does not exist for deleted users any more,
     *       it is also possible that we do not know the original user context id.
     *
     * @param target_user $user
     * @param context $context restriction for purging e.g., system context for everything, course context for purging one course
     * @return int result self::RESULT_STATUS_SUCCESS, self::RESULT_STATUS_ERROR or self::RESULT_STATUS_SKIPPED
     */
    protected static function purge(target_user $user, context $context) {
        global $DB;

        $programids = self::get_assigned_programids($user, $context);

        if (!empty($programids)) {
            $transaction = $DB->start_delegated_transaction();

            self::unassign_from_programs($user, $programids);

            // Even after unassigning the learner there might be entries left
            // for the completion, we need to make sure all of them are gone.
            self::purge_program_completion($user, $programids);

            $transaction->allow_commit();
        }

        return self::RESULT_STATUS_SUCCESS;
    }

    /**
     * Export user data from this item.
     *
     * @param target_user $user
     * @param \context $context restriction for exporting i.e., system context for everything and course context for course export
     * @return export|int result object or integer error code self::RESULT_STATUS_ERROR or self::RESULT_STATUS_SKIPPED
     */
    protected static function export(target_user $user, context $context) {
        global $DB;

        $export = new export();

        $contextsql = self::get_context_sql($context, 'p');

        $sql = "
            SELECT a.*,
                p.fullname,
                p.shortname
              FROM {prog_user_assignment} a
              JOIN {prog} p ON a.programid = p.id AND p.certifid IS NULL $contextsql
             WHERE a.userid = :userid
        ";
        $export->data['assignment'] = $DB->get_records_sql($sql, ['userid' => $user->id]);

        $sql = "
            SELECT a.*,
                p.fullname,
                p.shortname
              FROM {prog_future_user_assignment} a
              JOIN {prog} p ON a.programid = p.id AND p.certifid IS NULL $contextsql
             WHERE a.userid = :userid
        ";
        // Used to track an assignment that cannot be made yet, but will be added
        // at some later time (e.g. first login assignments which will be applied the
        // first time the user logs in).
        $export->data['future_assignment'] = $DB->get_records_sql($sql, ['userid' => $user->id]);

        $sql = "
            SELECT pc.*,
                p.fullname,
                p.shortname
              FROM {prog_completion} pc
              JOIN {prog} p ON pc.programid = p.id AND p.certifid IS NULL $contextsql
             WHERE pc.userid = :userid
        ";
        $export->data['completion'] = $DB->get_records_sql($sql, ['userid' => $user->id]);

        $sql = "
            SELECT h.*,
                p.fullname,
                p.shortname
              FROM {prog_completion_history} h
              JOIN {prog} p ON h.programid = p.id AND p.certifid IS NULL $contextsql
             WHERE h.userid = :userid
        ";
        $export->data['history'] = $DB->get_records_sql($sql, ['userid' => $user->id]);

        $sql = "
            SELECT e.*,
                p.fullname,
                p.shortname
              FROM {prog_extension} e
              JOIN {prog} p ON e.programid = p.id AND p.certifid IS NULL $contextsql
             WHERE e.userid = :userid
        ";
        $export->data['extension'] = $DB->get_records_sql($sql, ['userid' => $user->id]);

        return $export;
    }

}
