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
 * @package totara_certification
 */

namespace totara_certification\userdata;

use context;
use totara_program\userdata\base_assignment_completion;
use totara_userdata\userdata\export;
use totara_userdata\userdata\target_user;

/**
 * This item takes care of purging, exporting and counting certification assignments and completion.
 * This only targets certifications. Program completion is handled by {@link \totara_program\userdata\assignment_completion}.
 * History and logs are included within this item.
 */
class assignment_completion extends base_assignment_completion {

    /**
     * This is the certification item
     *
     * @var bool
     */
    protected static $iscertification = true;

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

        $transaction = $DB->start_delegated_transaction();

        if (!empty($programids)) {
            self::unassign_from_programs($user, $programids);

            // Even after unassigning the learner there might be entries left
            // for the completion, we need to make sure all of them are gone.
            self::purge_certification_completion($user, $programids);
            self::purge_program_completion($user, $programids);
        } else {
            // there are no records in {prog_user_assignment} by the time manual purge occurs.
            self::purge_any_certification_completion($user);
        }

        $transaction->allow_commit();

        return self::RESULT_STATUS_SUCCESS;
    }

    /**
     * @param target_user $user
     * @param array $programids
     */
    private static function purge_certification_completion(target_user $user, array $programids) {
        global $DB;

        list($sqlinorequal, $params) = $DB->get_in_or_equal($programids, SQL_PARAMS_NAMED);

        // Delete certification completions.
        $select = "userid = :userid AND certifid IN (
            SELECT certifid
              FROM {prog}
             WHERE id $sqlinorequal
        )";
        $params['userid'] = $user->id;
        $DB->delete_records_select('certif_completion', $select, $params);
        $DB->delete_records_select('certif_completion_history', $select, $params);
    }

    /**
     * @param target_user $user
     */
    private static function purge_any_certification_completion(target_user $user) {
        global $DB;

        $condition = [ 'userid' => $user->id ];
        $DB->delete_records('certif_completion', $condition);
        $DB->delete_records('certif_completion_history', $condition);
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
              JOIN {prog} p ON a.programid = p.id $contextsql
              JOIN {certif} c ON p.certifid = c.id
             WHERE a.userid = :userid
        ";
        $export->data['assignment'] = $DB->get_records_sql($sql, ['userid' => $user->id]);

        $sql = "
            SELECT a.*,
                p.fullname,
                p.shortname
              FROM {prog_future_user_assignment} a
              JOIN {prog} p ON a.programid = p.id $contextsql
              JOIN {certif} c ON p.certifid = c.id
             WHERE a.userid = :userid
        ";
        // Used to track an assignment that cannot be made yet, but will be added
        // at some later time (e.g. first login assignments which will be applied the
        // first time the user logs in).
        $export->data['future_assignment'] = $DB->get_records_sql($sql, ['userid' => $user->id]);

        $sql = "
            SELECT cc.*,
                p.fullname,
                p.shortname,
                c.activeperiod,
                c.minimumactiveperiod,
                c.windowperiod
              FROM {certif_completion} cc
              JOIN {prog} p ON cc.certifid = p.certifid $contextsql
              JOIN {certif} c ON cc.certifid = c.id
             WHERE cc.userid = :userid
        ";
        $export->data['completion'] = $DB->get_records_sql($sql, ['userid' => $user->id]);

        $sql = "
            SELECT h.*,
                p.fullname,
                p.shortname
              FROM {certif_completion_history} h
              JOIN {prog} p ON h.certifid = p.certifid $contextsql
              JOIN {certif} c ON h.certifid = c.id
             WHERE h.userid = :userid
        ";
        $export->data['history'] = $DB->get_records_sql($sql, ['userid' => $user->id]);

        $sql = "
            SELECT e.*,
                p.fullname,
                p.shortname
              FROM {prog_extension} e
              JOIN {prog} p ON e.programid = p.id $contextsql
              JOIN {certif} c ON p.certifid = c.id
             WHERE e.userid = :userid
        ";
        $export->data['extension'] = $DB->get_records_sql($sql, ['userid' => $user->id]);

        return $export;
    }

}
