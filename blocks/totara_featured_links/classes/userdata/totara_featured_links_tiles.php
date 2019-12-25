<?php
/**
 * This file is part of Totara LMS
 *
 * Copyright (C) 2017 onwards Totara Learning Solutions LTD
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
 * @author Andrew McGhie <andrew.mcghie@totaralearning.com>
 * @package block_totara_featured_links
 */

namespace block_totara_featured_links\userdata;

use block_totara_featured_links\tile\base;
use context_block;
use totara_userdata\userdata\export;
use totara_userdata\userdata\item;
use totara_userdata\userdata\target_user;

/**
 * Only featured links blocks that are visible to only the user being purged
 * is considered to be personal data. This means blocks in that users dashboards
 * so any block inside the users context is to be deleted.
 */
class totara_featured_links_tiles extends item {

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
     * @return export|int result object or integer error code self::RESULT_STATUS_ERROR or self::RESULT_STATUS_SKIPPED
     */
    protected static function export(target_user $user, \context $context) {
        global $DB;
        // If the user had being deleted before this patch then they will defiantly not have any blocks.
        if (!isset($user->contextid)) {
            return new export();
        }
        // If the user has being deleted after this patch they could have blocks if the delete_user function is changed.
        $export = new export();
        $blocks = $DB->get_records('block_instances', [
            'parentcontextid' => $user->contextid,
            'blockname' => 'totara_featured_links'
        ]);
        foreach ($blocks as $block) {
            $files = $DB->get_records_sql(
                "SELECT *
                   FROM {files}
                  WHERE contextid = :contextid
                    AND filename != '.'",
                ['contextid' => context_block::instance($block->id)->id]
            );
            $fs = get_file_storage();
            $filedata = [];
            foreach ($files as $file) {
                $filedata[$file->itemid][] = $export->add_file($fs->get_file_instance($file));
            }

            $tiles = $DB->get_records('block_totara_featured_links_tiles', ['blockid' => $block->id]);
            foreach ($tiles as &$tile) {
                if (!isset($filedata[$tile->id])) {
                    $tile->files = [];
                    continue;
                }
                $tile->files = $filedata[$tile->id];
            }
            $block->tiles = $tiles;
        }
        $export->data = $blocks;

        return $export;
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
     * Execute user data counting for this item.
     *
     * @param target_user $user
     * @param \context $context restriction for counting i.e., system context for everything and course context for course data
     * @return null|int null if result unknown or counting does not make sense, integer is the count >= 0
     */
    protected static function count(target_user $user, \context $context) {
        global $DB;
        // If the user was deleted before the userdata then their blocks were deleted then.
        if (!isset($user->contextid)) {
            return 0;
        }
        // If the user was deleted after the userdata patch then the delete_user function might have changed.
        return (int)$DB->count_records(
            'block_instances', [
                'parentcontextid' => $user->contextid,
                'blockname' => 'totara_featured_links'
            ]
        );
    }
}
