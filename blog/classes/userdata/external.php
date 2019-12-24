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
 * @package core_blog
 */

namespace core_blog\userdata;

use blog_entry;
use context;
use context_system;
use context_user;
use core\event\blog_comment_deleted;
use core\event\blog_external_removed;
use core_tag_tag;
use totara_userdata\userdata\export;
use totara_userdata\userdata\item;
use totara_userdata\userdata\target_user;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/blog/locallib.php');
require_once($CFG->dirroot . '/tag/lib.php');

/**
 * Purge, export and counting of external blogs entries and posts.
 */
class external extends item {

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
     * @param context $context restriction for purging e.g., system context for everything, course context for purging one course
     * @return int result self::RESULT_STATUS_SUCCESS, self::RESULT_STATUS_ERROR or status::RESULT_STATUS_SKIPPED
     */
    protected static function purge(target_user $user, context $context) {
        global $DB;

        $blogentries = $DB->get_records('post', ['userid' => $user->id, 'module' => 'blog_external']);
        foreach ($blogentries as $blogentry) {
            // To delete comments we don't use the existing comment->delete() methods as this
            // dependes on capabilities of the user who runs this purge.
            $comments = $DB->get_records('comments', ['itemid' => $blogentry->id, 'component' => 'blog']);
            $DB->delete_records_list('comments', 'id', array_keys($comments));

            // If the user has not been deleted then fire events.
            if ($user->status != target_user::STATUS_DELETED) {
                foreach ($comments as $comment) {
                    $event = blog_comment_deleted::create(
                        [
                            'context' => context_user::instance($user->id),
                            'objectid' => $comment->id,
                            'other' => [
                                'itemid' => $comment->itemid
                            ]
                        ]);
                    $event->add_record_snapshot('comments', $comment);
                    $event->trigger();
                }
            }

            // Remove the blog.
            $blogentry = new blog_entry($blogentry->id);
            $blogentry->delete();
        }

        $blogs = $DB->get_records('blog_external', ['userid' => $user->id]);

        $DB->delete_records_list('blog_external', 'id', array_keys($blogs));

        foreach ($blogs as $blog) {
            // Remove the tags before removing the blog to prevent exceptions due to missing context in events.
            core_tag_remove_instances('core', 'blog_external', $blog->id);

            $eventparms = [
                'context' => context_system::instance(),
                'objectid' => $blog->id
            ];
            $event = blog_external_removed::create($eventparms);
            $event->add_record_snapshot('blog_external', $blog);
            $event->trigger();
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
     * @param context $context restriction for exporting i.e., system context for everything and course context for course export
     * @return export|int result object or integer error code self::RESULT_STATUS_ERROR or self::RESULT_STATUS_SKIPPED
     */
    protected static function export(target_user $user, context $context) {
        global $DB;

        $export = new export();

        $blogs = $DB->get_records('blog_external', ['userid' => $user->id]);
        foreach ($blogs as $blog) {
            $blog = (array)$blog;
            $blog['tags'] = core_tag_tag::get_item_tags_array('core', 'blog_external', $blog['id']);
            $blog['posts'] = [];

            $select = "module = 'blog_external' AND userid = :userid AND ".$DB->sql_compare_text('content')." = :content";
            $posts = $DB->get_records_select('post', $select, ['userid' => $user->id, 'content' => $blog['id']]);
            foreach ($posts as $post) {
                $params = ['component' => 'blog', 'userid' => $user->id, 'itemid' => $post->id];
                $comments = $DB->get_records('comments', $params);

                $postexport = [
                    'id' => $post->id,
                    'subject' => $post->subject,
                    'summary' => $post->summary,
                    'uniquehash' => $post->uniquehash,
                    'created' => $post->created,
                    'comments' => $comments
                ];

                $blog['posts'][] = $postexport;
            }
            $export->data[] = $blog;
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
     * @param context $context restriction for counting i.e., system context for everything and course context for course data
     * @return int amount of data or negative integer status code (self::RESULT_STATUS_ERROR or self::RESULT_STATUS_SKIPPED)
     */
    protected static function count(target_user $user, context $context) {
        global $DB;

        return $DB->count_records('blog_external', ['userid' => $user->id]);
    }

}