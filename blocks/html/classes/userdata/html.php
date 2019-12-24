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
 * @package block_html
 */

namespace block_html\userdata;

use totara_userdata\userdata\export;
use totara_userdata\userdata\target_user;

defined('MOODLE_INTERNAL') || die();

/**
 * Purge, export and counting of HTML blocks.
 */
class html extends \totara_userdata\userdata\item {

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

        if ($user->contextid) {
            $select = "blockname = 'html' AND parentcontextid = :cid";
            $params = ['cid' => $user->contextid];
            $blockids = $DB->get_fieldset_select('block_instances', 'id', $select, $params);
            if (!empty($blockids)) {
                blocks_delete_instances($blockids);
            }
        }

        return self::RESULT_STATUS_SUCCESS;
    }

    /**
     * Can user data of this item data be exported from system?
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
     * @return export|int result object or integer error code self::RESULT_STATUS_ERROR or self::RESULT_STATUS_SKIPPED
     */
    protected static function export(target_user $user, \context $context) {
        global $DB;
        $export = new export();

        if ($user->contextid) {
            $fs = get_file_storage();

            $blockinstances = $DB->get_records('block_instances', ['blockname' => 'html', 'parentcontextid' => $user->contextid]);
            foreach ($blockinstances as $blockinstance) {
                $block = block_instance($blockinstance->blockname, $blockinstance);

                $files = [];
                $blockfiles = $fs->get_area_files($block->context->id, 'block_html', 'content', 0, '', false);
                foreach ($blockfiles as $file) {
                    $files[] = $export->add_file($file);
                }

                $export->data[] = [
                    'title' => $block->get_title(),
                    'content' => $block->get_content(),
                    'files' => $files
                ];
            }
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

        if ($user->contextid) {
            return $DB->count_records('block_instances', ['blockname' => 'html', 'parentcontextid' => $user->contextid]);
        }

        return 0;
    }

}