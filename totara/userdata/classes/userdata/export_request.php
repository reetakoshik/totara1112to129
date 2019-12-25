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
 * @author Samantha Jayasinghe <samantha.jayasinghe@totaralearning.com>
 * @package totara_userdata
 */
namespace totara_userdata\userdata;

use totara_userdata\local\export;

defined('MOODLE_INTERNAL') || die();

class export_request extends item {

    /**
     * Can user data of this item data be purged from system?
     *
     * @param int $userstatus target_user::STATUS_ACTIVE, target_user::STATUS_DELETED or target_user::STATUS_SUSPENDED
     *
     * @return bool
     */
    public static function is_purgeable(int $userstatus) {
        return true;
    }

    /**
     * Purge user data for this item.
     *
     * NOTE: Remember that context record does not exist for deleted users any more,
     *       it is also possible that we do not know the original user context id.
     *
     * @param target_user $user
     * @param \context    $context restriction for purging e.g., system context for everything, course context for
     *                             purging one course
     *
     * @return int result self::RESULT_STATUS_SUCCESS, self::RESULT_STATUS_ERROR or status::RESULT_STATUS_SKIPPED
     */
    protected static function purge(target_user $user, \context $context) {
        global $DB;

        $transaction = $DB->start_delegated_transaction();

        $sql = "SELECT e.id " . self::get_export_request_join();
        $exportfiles = $DB->get_records_sql($sql, ['userid' => $user->id]);

        foreach ($exportfiles as $exportfile) {
            export::delete_result_file($exportfile->id);
        }

        $transaction->allow_commit();

        return self::RESULT_STATUS_SUCCESS;
    }

    /**
     * Can user data of this item data be exported from system?
     *
     * @return bool
     */
    public static function is_exportable() {
        return false;
    }

    /**
     * Can user data of this item be somehow counted?
     * How much date is there?
     *
     * @return bool
     */
    public static function is_countable() {
        return true;
    }

    /**
     * Count user data for this item.
     *
     * @param target_user $user
     * @param \context    $context restriction for counting i.e., system context for everything and course context for
     *                             course data
     *
     * @return int is the count >= 0, negative number is error result self::RESULT_STATUS_ERROR or
     *             self::RESULT_STATUS_SKIPPED
     */
    protected static function count(target_user $user, \context $context) {
        global $DB;

        $sql = "SELECT count(e.id) " . self::get_export_request_join();

        return $DB->count_records_sql($sql, ['userid' => $user->id]);
    }

    /**
     * Get Export request join
     *
     * @return string
     */
    private static function get_export_request_join() {
        return "FROM {totara_userdata_export} e
                 JOIN {files} f ON e.id = f.itemid
                 WHERE f.filearea = 'export'  AND f.component='totara_userdata'
                        AND filename !='.' AND filesize > 0 AND e.userid = :userid";
    }
}
