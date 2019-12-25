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
 * @author Matthias Bonk <matthias.bonk@totaralearning.com>
 * @package mod_facetoface
 */

namespace mod_facetoface\userdata;

use coding_exception;
use context;
use context_system;
use totara_userdata\userdata\export;
use totara_userdata\userdata\target_user;

defined('MOODLE_INTERNAL') || die();

/**
 * This item is only for the export of signup and cancellation custom fields.
 *
 * Purging of this data is covered by signups item because it's directly
 * connected to signups. It was decided to make it exportable separately because
 * the data stored in it could be of various kinds, depending on how it's
 * used in an organisation.
 */
class customfields extends signups_item {

    /**
     * Can user data of this item data be purged from system?
     *
     * @param int $userstatus target_user::STATUS_ACTIVE, target_user::STATUS_DELETED or target_user::STATUS_SUSPENDED
     * @return bool
     */
    public static function is_purgeable(int $userstatus) {
        return false;
    }

    /**
     * Execute user data export for this item.
     *
     * Signup and cancellation custom field data are both attached to the export here.
     *
     * @param target_user $user
     * @param context $context restriction for exporting i.e., system context for everything and course context for course export
     * @return export|int result object or integer error code self::RESULT_STATUS_ERROR or self::RESULT_STATUS_SKIPPED
     */
    protected static function export(target_user $user, context $context) {
        $export = new export();

        $signup = self::get_signup_customfield_data($user, $context);
        $cancellation = self::get_cancellation_customfield_data($user, $context);

        $fileareassignup = ['facetofacesignup_filemgr', 'facetofacesignup'];
        $fileareascancellation = ['facetofacecancellation_filemgr', 'facetofacecancellation'];

        $export->data['signup'] = self::add_files($export, $user, $signup, $fileareassignup);
        $export->data['cancellation'] = self::add_files($export, $user, $cancellation, $fileareascancellation);

        return $export;
    }

    /**
     * Get signup customfield data.
     *
     * @param target_user $user
     * @param context $context
     * @return array
     */
    private static function get_signup_customfield_data(target_user $user, context $context) {
        return self::get_customfield_data('signup', $user, $context);
    }

    /**
     * Get cancellation customfield data.
     *
     * @param target_user $user
     * @param context $context
     * @return array
     */
    private static function get_cancellation_customfield_data(target_user $user, context $context) {
        return self::get_customfield_data('cancellation', $user, $context);
    }

    /**
     * Get customfield data.
     *
     * @param $customfieldtype
     * @param target_user $user
     * @param context $context
     * @return array
     * @throws coding_exception
     */
    private static function get_customfield_data($customfieldtype, target_user $user, context $context) {
        global $DB;

        if (!in_array($customfieldtype, ['signup', 'cancellation'])) {
            throw new coding_exception('Unknown custom field type: ' . $customfieldtype);
        }

        $signups = self::get_signups($user, $context);
        $signupids = array_column($signups, 'id');
        if (empty($signupids)) {
            return [];
        }

        [$signupsqlin, $signupinparams] = $DB->get_in_or_equal($signupids);

        // For one *_info_data record there can be any number of records in the *_info_data_param table that
        // we want to include in the export, so left join to that and expect multiple rows for every *_info_data.id.
        $sql = "SELECT d.id, d.data, d.fieldid, 
                       d.facetoface{$customfieldtype}id AS signupid,
                       dp.value AS paramvalue
                  FROM {facetoface_{$customfieldtype}_info_data} d
             LEFT JOIN {facetoface_{$customfieldtype}_info_data_param} dp ON dp.dataid = d.id
                 WHERE facetoface{$customfieldtype}id $signupsqlin";

        $records = $DB->get_recordset_sql($sql, $signupinparams);
        $customfielddata = [];
        foreach ($records as $record) {
            if (!isset($customfielddata[$record->id])) {
                $customfielddata[$record->id]['signupid'] = $record->signupid;
                $customfielddata[$record->id]['data'] = $record->data;
                $customfielddata[$record->id]['params'] = [];
            }
            if (!empty($record->paramvalue)) {
                $customfielddata[$record->id]['params'][] = $record->paramvalue;
            }
        }

        return $customfielddata;
    }

    /**
     * Add relevant files for export.
     *
     * @param export $export
     * @param target_user $user
     * @param array $exportdata
     * @param array $fileareas
     * @return array
     */
    private static function add_files(export $export, target_user $user, array $exportdata, array $fileareas): array {
        // Custom field files are stored with system context, so we filter by userid.
        $fs = get_file_storage();
        $systemcontext = context_system::instance();

        $files = $fs->get_area_files(
            $systemcontext->id,
            'totara_customfield',
            $fileareas,
            false,
            'filename ASC',
            false,
            0,
            $user->id
        );

        foreach ($exportdata as $id => $data) {
            $exportdata[$id]['files'] = [];

            foreach ($files as $file) {
                if ($file->get_itemid() == $id) {
                    $exportdata[$id]['files'][] = $export->add_file($file);
                }
            }
        }

        return $exportdata;
    }

    /**
     * Count customfield data.
     *
     * @param target_user $user
     * @param context $context restriction for counting i.e., system context for everything and course context for course data
     * @return null|int null if result unknown or counting does not make sense, integer is the count >= 0
     */
    protected static function count(target_user $user, context $context) {
        $signupdatacount = count(self::get_signup_customfield_data($user, $context));
        $cancellationdatacount = count(self::get_cancellation_customfield_data($user, $context));
        return $signupdatacount + $cancellationdatacount;
    }

    /**
     * Returns sort order.
     *
     * @return int
     */
    public static function get_sortorder() {
        return 15000;
    }
}
