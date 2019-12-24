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
 * @author Valerii Kuznetsov <valerii.kuznetsov@totaralearning.com>
 * @package totara_reportbuilder
 */

namespace totara_reportbuilder\userdata;

use totara_userdata\userdata\item;
use totara_userdata\userdata\target_user;

defined('MOODLE_INTERNAL') || die();


/**
 * This item takes care of purging and exporting all scheduled reports made by the user
 */
final class scheduled_reports extends item {

    /**
     * Can user data of this item data be purged from system?
     *
     * @param int $userstatus target_user::STATUS_ACTIVE, target_user::STATUS_DELETED or target_user::STATUS_SUSPENDED
     * @return bool
     */
    public static function is_purgeable(int $userstatus) {
        return true;
    }

    /**
     * Can user data of this item data be exported from the system?
     *
     * @return bool
     */
    public static function is_exportable() {
        return true;
    }

    /**
     * Can user data of this item be somehow counted?
     *
     * @return bool
     */
    public static function is_countable() {
        return true;
    }

    /**
     * Returns all contexts this item is compatible with, defaults to CONTEXT_SYSTEM.
     *
     * @return array
     */
    public static function get_compatible_context_levels() {
        return [CONTEXT_SYSTEM];
    }

    /**
     * Execute user data purging for this item.
     *
     * NOTE: Remember that context record does not exist for deleted users any more,
     *       it is also possible that we do not know the original user context id.
     *
     * @param target_user $user
     * @param \context $context restriction for purging e.g., system context for everything, course context for purging one course
     * @return int result self::RESULT_STATUS_SUCCESS, self::RESULT_STATUS_ERROR or status::RESULT_STATUS_SKIPPED
     */
    protected static function purge(target_user $user, \context $context) {
        global $DB;

        $select = "scheduleid IN (SELECT s.id FROM {report_builder_schedule} s WHERE s.userid = :userid)";

        $DB->delete_records_select('report_builder_schedule_email_audience', $select, ['userid' => $user->id]);
        $DB->delete_records_select('report_builder_schedule_email_systemuser', $select, ['userid' => $user->id]);
        $DB->delete_records_select('report_builder_schedule_email_external', $select, ['userid' => $user->id]);
        $DB->delete_records('report_builder_schedule_email_systemuser', ['userid' => $user->id]);
        $DB->delete_records('report_builder_schedule', ['userid' => $user->id]);

        // Clean usermodified.
        $DB->execute("UPDATE {report_builder_schedule} SET usermodified = 0 WHERE usermodified = :userid",
            ['userid' => $user->id]);

        return self::RESULT_STATUS_SUCCESS;
    }

    /**
     * Export user data from this item.
     *
     * @param target_user $user
     * @param \context $context restriction for exporting i.e., system context for everything and course context for course export
     * @return \totara_userdata\userdata\export|int result object or integer error code self::RESULT_STATUS_ERROR or self::RESULT_STATUS_SKIPPED
     */
    protected static function export(target_user $user, \context $context) {
        global $DB;
        $usernamefields = get_all_user_name_fields(true, 'u');

        $savedsql = "
            SELECT 
              rbsc.id, 
              rb.fullname AS reportname,
              rbs.name AS searchname, 
              rbsc.format, 
              rbsc.frequency, 
              c.name AS cohortname,
              u.id AS systemuserid,
              $usernamefields,
              rbscee.email
              
            FROM {report_builder_schedule} rbsc
              LEFT JOIN {report_builder_saved} rbs ON (rbs.id = rbsc.savedsearchid)
              LEFT JOIN {report_builder} rb ON (rb.id = rbsc.reportid)
              LEFT JOIN {report_builder_schedule_email_audience} rbscea ON (rbscea.scheduleid = rbsc.id)
              LEFT JOIN {cohort} c ON (c.id = rbscea.cohortid)
              LEFT JOIN {report_builder_schedule_email_systemuser} rbsces ON (rbsces.scheduleid = rbsc.id)
              LEFT JOIN {user} u ON (u.id = rbsces.userid)
              LEFT JOIN {report_builder_schedule_email_external} rbscee ON (rbscee.scheduleid = rbsc.id)
            WHERE rbsc.userid = :userid
            ORDER BY rbsc.id
        ";
        $records = $DB->get_recordset_sql($savedsql, ['userid' => $user->id]);
        $result = [];
        foreach ($records as $record) {
            if (!isset($result[$record->id])) {
                $result[$record->id] = [
                    'reportname' => $record->reportname,
                    'searchname' => $record->searchname,
                    'format' => $record->format,
                    'frequency' => $record->frequency,
                    'audiences' => [],
                    'users' => [],
                    'external' => []
                ];
            }
            if (!empty($record->cohortname) && !in_array($record->cohortname, $result[$record->id]['audiences'])) {
                $result[$record->id]['audiences'][] = $record->cohortname;
            }
            if (!empty($record->systemuserid) && !in_array(fullname($record), $result[$record->id]['users'])) {
                $result[$record->id]['users'][] = fullname($record);
            }
            if (!empty($record->email) && !in_array($record->email, $result[$record->id]['external'])) {
                $result[$record->id]['external'][] = $record->email;
            }

        }
        $export = new \totara_userdata\userdata\export();
        $export->data = $result;
        return $export;
    }

    /**
     * Count user data for this item.
     *
     * @param target_user $user
     * @param \context $context restriction for counting i.e., system context for everything and course context for course data
     * @return int amount of data or negative integer status code (self::RESULT_STATUS_ERROR or self::RESULT_STATUS_SKIPPED)
     */
    protected static function count(target_user $user, \context $context) {
        global $DB;
        return $DB->count_records('report_builder_schedule', ['userid' => $user->id]);
    }
}