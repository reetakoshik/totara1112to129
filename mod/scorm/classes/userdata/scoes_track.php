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
 * @package mod_scorm
 */

namespace mod_scorm\userdata;

use totara_userdata\userdata\item;
use totara_userdata\userdata\target_user;

defined('MOODLE_INTERNAL') || die();


/**
 * This item takes care of exporting, counting and purging of scorm activity data.
 * User can export scorm attempts that they have made.
 */
final class scoes_track extends item {
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
     * Returns all contexts this item is compatible with, defaults to CONTEXT_SYSTEM.
     *
     * @return array
     */
    public static function get_compatible_context_levels() {
        return [CONTEXT_SYSTEM, CONTEXT_COURSECAT, CONTEXT_COURSE, CONTEXT_MODULE];
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

        $join = self::get_activities_join($context, 'scorm', 's.id');
        $select = "scormid IN (SELECT s.id FROM {scorm} s $join) AND userid = :userid";
        $DB->delete_records_select('scorm_scoes_track', $select, ['userid' => $user->id]);

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

        $join = self::get_activities_join($context, 'scorm', 's.id');

        [$elemsql, $elemparams] = $DB->get_in_or_equal(['cmi.core.score.raw', 'cmi.core.lesson_status', 'cmi.completion_status'], SQL_PARAMS_NAMED);
        $sql = "
            SELECT sst.id, s.id AS scormid, s.name, ss.id AS scoid, ss.title, sst.attempt, sst.element, sst.value
            FROM {scorm_scoes_track} sst
            JOIN {scorm} s ON (s.id = sst.scormid)
            $join
            JOIN {scorm_scoes} ss ON (ss.id = sst.scoid)
            WHERE sst.element $elemsql AND sst.userid = :userid
        ";
        $params = array_merge(['userid' => $user->id], $elemparams);
        $records = $DB->get_recordset_sql($sql, $params);

        $result = [];
        foreach ($records as $record) {
            if (!isset($result[$record->scormid])) {
                $result[$record->scormid] = [
                    'scorm' => $record->name,
                    'sco' => []
                ];
            }
            if (!isset($result[$record->scormid]['sco'][$record->scoid])) {
                $result[$record->scormid]['sco'][$record->scoid] = [
                    'title' => $record->title,
                    'track' => []
                ];
            }
            $result[$record->scormid]['sco'][$record->scoid]['track'][] = [
                'attempt' => $record->attempt,
                $record->element => $record->value
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

        $join = self::get_activities_join($context, 'scorm', 'sst.scormid');

        [$elemsql, $elemparams] = $DB->get_in_or_equal(['cmi.core.score.raw', 'cmi.core.lesson_status', 'cmi.completion_status'], SQL_PARAMS_NAMED);
        $sql = "
            SELECT COUNT(DISTINCT sst.scormid)
            FROM {scorm_scoes_track} sst
            $join
            JOIN {scorm_scoes} ss ON (ss.id = sst.scoid)
            WHERE sst.element $elemsql AND sst.userid = :userid
        ";
        $params = array_merge(['userid' => $user->id], $elemparams);

        return $DB->count_records_sql($sql, $params);
    }
}