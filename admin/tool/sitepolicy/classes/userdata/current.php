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
 * @author Valerii Kuznetsov <valerii.kuznetsov@@totaralearning.com>
 * @package tool_sitepolicy
 */

namespace tool_sitepolicy\userdata;

use totara_userdata\userdata\item;
use totara_userdata\userdata\target_user;

defined('MOODLE_INTERNAL') || die();


/**
 * This item takes care of exporting, counting and purging of users consents on current site policies.
 * User can export consents that they have made.
 */
final class current extends item {
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
     * Can user data of this item data be purged from system?
     *
     * @param int $userstatus target_user::STATUS_ACTIVE, target_user::STATUS_DELETED or target_user::STATUS_SUSPENDED
     * @return bool
     */
    public static function is_purgeable(int $userstatus) {
        return true;
    }

    /**
     * Get common from part with all joins required to get user consent data.
     * @return string
     */
    private static function get_consent_sql_from_part() {
        return "
            {tool_sitepolicy_user_consent} tsuc
            JOIN {tool_sitepolicy_consent_options} tsco ON (tsco.id = tsuc.consentoptionid)
            JOIN {tool_sitepolicy_policy_version} tspv ON (tspv.id = tsco.policyversionid)
        ";
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

        $from = static::get_consent_sql_from_part();
        $select = "
          id IN (
            SELECT tsuc.id 
            FROM $from
            WHERE tsuc.userid = :userid 
              AND tspv.timepublished IS NOT NULL 
              AND tspv.timearchived IS NULL
          )
        ";
        $records = $DB->get_records_select_menu('tool_sitepolicy_user_consent', $select, ['userid' => $user->id], "", "id, userid");
        $DB->delete_records_list('tool_sitepolicy_user_consent', 'id', array_keys($records));

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

        $from = static::get_consent_sql_from_part();
        $sql = "
            SELECT 
              tsuc.id,
              tslp.title,
              tslc.statement,
              tsuc.language,
              tsuc.timeconsented,
              CASE 
                WHEN tsuc.hasconsented = 1 THEN tslc.consentoption
                WHEN tsuc.hasconsented = 0 THEN tslc.nonconsentoption
              ELSE NULL END AS response
            FROM $from
              JOIN {tool_sitepolicy_localised_policy} tslp ON (tslp.policyversionid = tspv.id AND tslp.language = tsuc.language)
              JOIN {tool_sitepolicy_localised_consent} tslc ON (tslc.consentoptionid = tsco.id AND tslc.localisedpolicyid = tslp.id) 
            WHERE tsuc.userid = :userid 
              AND tspv.timepublished IS NOT NULL 
              AND tspv.timearchived IS NULL
            ORDER BY tsuc.id
        ";
        $consentrecords = $DB->get_recordset_sql($sql, ['userid' => $user->id]);

        $result = [];
        foreach($consentrecords as $consentrecord) {
            $result[] = [
                'policy' => $consentrecord->title,
                'language' => $consentrecord->language,
                'statement' => $consentrecord->statement,
                'response' => $consentrecord->response,
                'time' => $consentrecord->timeconsented
            ];
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

        $from = static::get_consent_sql_from_part();
        $sql = "
            SELECT COUNT('x') 
            FROM $from
            WHERE tsuc.userid = :userid 
              AND tspv.timepublished IS NOT NULL 
              AND tspv.timearchived IS NULL
        ";


        $count = $DB->count_records_sql($sql, ['userid' => $user->id]);

        return $count;
    }
}