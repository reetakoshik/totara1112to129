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
 * @author Maria Torres <maria.torres@totaralearning.com>
 * @package editor_atto
 */

namespace editor_atto\userdata;

use totara_userdata\userdata\item;
use totara_userdata\userdata\target_user;

defined('MOODLE_INTERNAL') || die();

/**
 * Userdata item for the autosave data recorded in atto editors.
 */
class editor_atto extends item {
    /**
     * Returns all contexts this item is compatible with, defaults to CONTEXT_SYSTEM.
     * Atto editors are everywhere, so it is compatible with almost all contexts.
     *
     * @return array
     */
    public static function get_compatible_context_levels() {
        return [CONTEXT_SYSTEM, CONTEXT_COURSE, CONTEXT_COURSECAT, CONTEXT_MODULE, CONTEXT_PROGRAM, CONTEXT_USER, CONTEXT_BLOCK];
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
     * @return int result self::RESULT_STATUS_SUCCESS, self::RESULT_STATUS_ERROR or status::RESULT_STATUS_SKIPPED
     */
    protected static function purge(target_user $user, \context $context) {
        global $DB;

        // Default sql fragment and params to be used when
        $select = 'userid =:userid AND contextid =:contextid';
        $params = ['userid' => $user->id, 'contextid' => $context->id];
        if ($context->contextlevel == CONTEXT_SYSTEM) {
            // Delete everything related to the user passed.
            $select = 'userid =:userid';
            $params = ['userid' => $user->id];
        }

        // Delete draft files related to the autosave draft item.
        if ($user->contextid) {
            $transaction = $DB->start_delegated_transaction();
            $fs = get_file_storage();
            $drafts = $DB->get_fieldset_select('editor_atto_autosave', 'draftid', $select, $params);
            if (!empty($drafts)) {
                foreach ($drafts as $draftid) {
                    $fs->delete_area_files($user->contextid, 'user', 'draft', $draftid);
                }
            }
        }

        // Delete editor atto autosave records.
        $DB->delete_records('editor_atto_autosave', $params);
        if ($user->contextid) {
            $transaction->allow_commit();
        }

        return self::RESULT_STATUS_SUCCESS;
    }

    /**
     * Can user data of this item data be exported from system?
     * The content in autosave are drafts and it does not seem likely they will be required for export
     *
     * @return bool
     */
    public static function is_exportable() {
        return false;
    }

    /**
     * Can user data of this item be somehow counted?
     * How much data is there?
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

        $params = ['userid' => $user->id, 'contextid' => $context->id];
        if ($context->contextlevel == CONTEXT_SYSTEM) {
            // Count all records the user have as draft in atto editor.
            $params = ['userid' => $user->id];
        }

        return $DB->count_records('editor_atto_autosave', $params);
    }
}