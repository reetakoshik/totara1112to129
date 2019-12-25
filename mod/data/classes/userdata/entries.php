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
 * @package mod_data
 */

namespace mod_data\userdata;

use totara_userdata\userdata\target_user;

defined('MOODLE_INTERNAL') || die();

/**
 * Database activity user data.
 */
class entries extends \totara_userdata\userdata\item {

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

        $join = self::get_activities_join($context, 'data', 'dr.dataid', 'd');

        $sql = "SELECT dr.id, ctx.id AS contextid
                FROM {data_records} dr
                {$join}
                WHERE dr.userid = :userid";
        $params = ['userid' => $user->id];

        $records = $DB->get_records_sql($sql, $params);

        $fs = get_file_storage();

        if ($user->status == target_user::STATUS_ACTIVE) {
            $transaction = $DB->start_delegated_transaction();
        }

        foreach ($records as $record) {
            $fs->delete_area_files($record->contextid, 'mod_data', 'content', $record->id);
            $DB->delete_records('data_records', ['id' => $record->id]);
            $DB->delete_records('data_content', ['recordid' => $record->id]);
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

        $join = self::get_activities_join($context, 'data', 'dr.dataid', 'd');

        $sql = "SELECT dr.id, dr.dataid AS databaseid, dr.id AS entryid, dr.groupid, dr.timecreated, dr.timemodified, dr.approved, ctx.id AS contextid
                FROM {data_records} dr
                {$join}
                WHERE dr.userid = :userid
                ORDER BY dr.dataid ASC";
        $params = ['userid' => $user->id];

        $records = $DB->get_records_sql($sql, $params);

        $fs = get_file_storage();
        $export = new \totara_userdata\userdata\export();

        foreach ($records as $record) {
            $record = (array) $record;

            $sql = "SELECT c.id, c.content, c.content1, c.content2, c.content3, c.content4, f.name, f.type
                    FROM {data_content} c
                    INNER JOIN {data_fields} f ON c.fieldid = f.id
                    WHERE c.recordid = :recordid
                    ORDER BY c.id ASC";
            $params = ['recordid' => $record['entryid']];

            $data_content = $DB->get_records_sql($sql, $params);

            foreach ($data_content as $data) {
                $data = (array) $data;

                if ($data['type'] == 'file' || $data['type'] == 'picture') {
                    $files = $fs->get_area_files($record['contextid'], 'mod_data', 'content', $data['id'], null, false);
                    
                    foreach ($files as $file) {
                        $record['files'][] = $export->add_file($file);
                    }
                }

                // Most of the fields won't have any content beyond the default content
                // field, but to keep it simple, we'll collate what's necessary.
                $content = [];
                for($i = 1; $i < 4; $i++) {
                    $field = $data["content{$i}"];

                    if ($field) {
                        $content[] = $field;
                    }
                }

                // If we have some multi field/column content combine it into an array.
                if (implode($content, '')) {
                    array_unshift($content, $data['content']);
                } else {
                    $content = $data['content'];
                }

                $record['entry'][$data['name']] = $content;
            }

            // Remove any fields we don't want to export.
            unset($record['id']);
            unset($record['userid']);
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
     * @return int  integer is the count >= 0, negative number is error result self::RESULT_STATUS_ERROR or self::RESULT_STATUS_SKIPPED
     */
    protected static function count(target_user $user, \context $context) {
        global $DB;

        $join = self::get_activities_join($context, 'data', 'dr.dataid', 'd');

        $sql = "SELECT COUNT(dr.id)
                FROM {data_records} dr
                {$join}
                WHERE dr.userid = :userid";
        $params = ['userid' => $user->id];

        $count = $DB->count_records_sql($sql, $params);

        return $count;
    }
}