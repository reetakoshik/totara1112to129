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
 * @author Rob Tyler <rob.tyler@totaralearning.com>
 * @package mod_certificate
 */

namespace mod_certificate\userdata;

use totara_userdata\userdata\target_user;

defined('MOODLE_INTERNAL') || die();

/**
 * Handles certificate issue history user data.
 */
class certificate_issues_history extends \totara_userdata\userdata\item {

    /**
     * String used for human readable name of this item.
     *
     * @return array parameters of get_string($identifier, $component) to get full item name and optionally help.
     */
    public static function get_fullname_string() {
        return ['userdata_certificate_issues_history', 'mod_certificate'];
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
     * Purge user data for this item.
     *
     * NOTE: Remember that context record does not exist for deleted users any more,
     *       it is also possible that we do not know the original user context id.
     *
     * @param target_user $user
     * @param \context $context restriction for purging e.g., system context for everything, course context for purging one course
     * @return int result self::RESULT_STATUS_SUCCESS, self::RESULT_STATUS_ERROR or self::RESULT_STATUS_SKIPPED
     */
    protected static function purge(target_user $user, \context $context) {
        global $DB;

        $join = self::get_activities_join($context, 'certificate', 'cih.certificateid', 'c');

        $sql = "SELECT cih.id, cih.idarchived, ctx.id AS contextid
                FROM {certificate_issues_history} cih
                {$join}
                WHERE cih.userid = :userid";
        $params = ['userid' => $user->id];

        $records = $DB->get_records_sql($sql, $params);

        $fs = get_file_storage();

        if ($user->status == target_user::STATUS_ACTIVE) {
            $transaction = $DB->start_delegated_transaction();
        }

        foreach ($records as $record) {
            // The certificate document will be associated with the certificate issue
            // rather than the history record so use idarchived to reference the file.
            $fs->delete_area_files($record->contextid, 'mod_certificate', 'issue', $record->idarchived);
            $DB->delete_records('certificate_issues_history', ['id' => $record->id]);
        }

        if ($user->status == target_user::STATUS_ACTIVE) {
            $transaction->allow_commit();
        }

        return self::RESULT_STATUS_SUCCESS;
    }

    /**
     * Returns all contexts this item is compatible with, defaults to CONTEXT_SYSTEM.
     *
     * @return array
     */
    public static function get_compatible_context_levels() {
        return [CONTEXT_SYSTEM, CONTEXT_COURSECAT, CONTEXT_COURSE, CONTEXT_MODULE];
    }
    
    /**
     * Can user data of this item data be exported from the system?
     *
     * For the certificate notify users field, it can be any email address.
     * We can only handle Totara user email addresses here, which is a copy
     * of the users email address, so there's no value providing an export.
     *
     * @return bool
     */
    public static function is_exportable() {
        return true;
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

        $join = self::get_activities_join($context, 'certificate', 'cih.certificateid', 'c');

        $sql = "SELECT cih.*, c.name, c.intro, ctx.id AS contextid
                FROM {certificate_issues_history} cih
                {$join}
                WHERE cih.userid = :userid";
        $params = ['userid' => $user->id];

        $records = $DB->get_records_sql($sql, $params);

        $export = new \totara_userdata\userdata\export();
        $fs = get_file_storage();

        foreach ($records as $record) {
            $record = (array) $record;

            // The certificate document will be assocated with the certificate issue
            // rather than the history record so use idarchived to reference the file.
            $files = $fs->get_area_files($record['contextid'], 'mod_certificate', 'issue', $record['idarchived'], null, false);

            foreach ($files as $file) {
                $record['files'][] = $export->add_file($file);
            }

            // Remove any fields we don't want to export.
            unset($record['id']);
            unset($record['userid']);
            unset($record['idarchived']);
            unset($record['contextid']);

            $export->data[] = $record;
        }

        return $export;
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
     * Count user data for this item.
     *
     * @param target_user $user
     * @param \context $context restriction for counting i.e., system context for everything and course context for course data
     * @return int amount of data or negative integer status code (self::RESULT_STATUS_ERROR or self::RESULT_STATUS_SKIPPED)
     */
    protected static function count(target_user $user, \context $context) {
        global $DB;

        $join = self::get_activities_join($context, 'certificate', 'cih.certificateid', 'c');

        $sql = "SELECT COUNT(cih.id)
                FROM {certificate_issues_history} cih
                {$join}
                WHERE cih.userid = :userid";
        $params = ['userid' => $user->id];

        $count = $DB->count_records_sql($sql, $params);

        return $count;
    }
}