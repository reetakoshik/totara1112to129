<?php
/**
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
 * @author Andrew McGhie <andrew.mcghie@totaralearning.com>
 * @package core_blog
 */

namespace core_blog\userdata;

defined('MOODLE_INTERNAL') || die();

use comment;
use totara_userdata\userdata\export;
use totara_userdata\userdata\item;
use totara_userdata\userdata\target_user;

require_once($CFG->dirroot . '/blog/locallib.php');
require_once($CFG->dirroot . '/comment/lib.php');
require_once($CFG->dirroot . '/tag/lib.php');

/**
 * Blogs are considered personal data.
 * Therefore any users blog on anything will be purged and exported.
 * Comments cannot exist without a blog so they will be deleted on purge.
 */
class blog extends item {

    /**
     * Get main Frankenstyle component name (core subsystem or plugin).
     * This is used for UI purposes to group items into components.
     *
     * NOTE: this can be overridden to move item to a different form group in UI,
     *       for example local plugins and items to standard activities
     *       or blocks may move items to their related plugins.
     */
    public static function get_main_component() {
        return "core_user";
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
     * @param \context $context
     * @return int
     */
    protected static function purge(target_user $user, \context $context) {
        global $DB;
        $blogs = $DB->get_records('post', ['userid' => $user->id, 'module' => 'blog']);
        foreach ($blogs as $blog) {
            // If the user has not being deleted then we can delete the comments on the blog.
            if ($user->status != target_user::STATUS_DELETED) {
                // Clean up comments cause deleting the blog doesnt delete them.
                $commentparams = new \stdClass();
                $commentparams->itemid = $blog->id;
                $commentparams->component = 'blog';
                $commentparams->context = \context_user::instance($user->id);
                $commentarea = new comment($commentparams);

                $comments = $DB->get_records('comments', ['itemid' => $blog->id, 'component' => 'blog']);
                foreach ($comments as $comment) {
                    $commentarea->delete($comment->id);
                }
            }

            // Remove the tags before removing the blog to prevent exceptions due to missing context in events.
            core_tag_remove_instances('core', 'post', $blog->id);
            // Remove the blog.
            $blogentry = new \blog_entry($blog->id);
            $blogentry->delete();
        }
        return self::RESULT_STATUS_SUCCESS;
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
        $export = new export();
        $blogs = $DB->get_records('post', ['userid' => $user->id, 'module' => 'blog']);
        foreach ($blogs as $blog) {
            $blog = (array)$blog;
            $blog['files'] = ['attachments' => [], 'post' => []];
            // Add the attachments.
            $fs = get_file_storage();
            $files = $fs->get_area_files(SYSCONTEXTID, 'blog', 'attachment', $blog['id'], '', false);
            foreach ($files as $file) {
                $blog['files']['attachments'][] = $export->add_file($file);
            }
            $files = $fs->get_area_files(SYSCONTEXTID, 'blog', 'post', $blog['id'], '', false);
            foreach ($files as $file) {
                $blog['files']['post'][] = $export->add_file($file);
            }
            $export->data[] = $blog;
        }
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
     * Count user data for this item.
     *
     * @param target_user $user
     * @param \context $context
     * @return int amount of data or negative integer status code (self::RESULT_STATUS_ERROR or self::RESULT_STATUS_SKIPPED)
     */
    protected static function count(target_user $user, \context $context) {
        global $DB;
        return $DB->count_records('post', ['module' => 'blog', 'userid' => $user->id]);
    }
}