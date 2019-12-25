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
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 * @package core_badges
 */

namespace core_badges\userdata;

use totara_userdata\userdata\export;
use totara_userdata\userdata\item;
use totara_userdata\userdata\target_user;

defined('MOODLE_INTERNAL') || die();

/**
 * Handles badges issued to the user.
 *
 * Notes:
 *   - Issued badges can be purged from the system. This does not however remove them from the
 *     users backpack. That is impossible. Once earned and exported that is it.
 *   - While issued badges can be purged if they are purged for an active user that user may be re-issued
 *     the badge next time cron runs. That is fine, it is explained in help.
 *   - This purges both automatically issued badges, and manually issued badges. We have not separated this
 *     into two settings. In the future if requested it can be reconsidered.
 *     Worth noting the system doesn't distinguish in all cases. Manual issue is just a criteria type.
 *
 * Events:
 *   - When an issued badge is deleted the badge_revoked event is triggered. This is consistent with deleting an issued badge.
 *     While not strictly true, in that you can't fully revoke a badge, it is consistent with API at the moment.
 *   - No other relevant events.
 *
 * Files:
 *   - Badge images generated for users in the users context when the badge is issued to them.
 *     It contains embedded meta data.
 *   - There are no other files used by badges that belong to the user.
 *
 * Caches:
 *   - None
 *
 * @package core_badge
 */
class issuedbadges extends item {

    /**
     * Returns an array of compatible context levels when looking at issued badges.
     *
     * @return int[] One or more CONTEXT_
     */
    public static function get_compatible_context_levels() {
        return [CONTEXT_SYSTEM, CONTEXT_COURSECAT, CONTEXT_COURSE];
    }

    /**
     * Can issued badges be purged for the given user status.
     *
     * @param int $userstatus target_user::STATUS_ACTIVE, target_user::STATUS_DELETED or target_user::STATUS_SUSPENDED
     * @return bool
     */
    public static function is_purgeable(int $userstatus) {
        return true;
    }

    /**
     * Purge issued badges for the given user in the given context and all child contexts.
     *
     * @param target_user $user
     * @param \context $context restriction for purging e.g., system context for everything, course context for purging one course
     * @return int result self::RESULT_STATUS_SUCCESS, self::RESULT_STATUS_ERROR or self::RESULT_STATUS_SKIPPED
     */
    protected static function purge(target_user $user, \context $context) {
        global $CFG, $DB;

        require_once($CFG->dirroot . '/lib/badgeslib.php');

        /** @var \stdClass[] $badges Use the API to get the badges issued to the user. */
        $badges = self::get_badges_issued_to_user($user, $context);
        $fs = get_file_storage();

        // Iterate over each badge and delete each one individually.
        // This is very purposeful, we are not bulk deleting badges.
        // Each one will be done individually to ensure that it is deleted completely before starting the next.
        // There is approach was taken as there are concurrency issues within
        foreach ($badges as $badge) {

            // Please note that there is a function process_manual_revoke() however that checks the issuing user, and does not
            // clean up criteria.

            // These are done per badge very purposefully. I want this to succeed per badge, and there is no need to do this
            // in bulk.
            // The transaction is needed as if cron runs it may try to reaggregate and/or reissue the badge.
            // Add data for a single badge must be cleaned up in a single transaction to avoid concurrency issues.
            $transaction = $DB->start_delegated_transaction();
            $DB->delete_records('badge_criteria_met', ['issuedid' => $badge->issuedid]); // This will exist.
            $DB->delete_records('badge_manual_award', ['badgeid' => $badge->id, 'recipientid' => $user->id]); // This may exist.
            $DB->delete_records('badge_issued', ['badgeid' => $badge->id, 'userid' => $user->id]); // This is the main target.
            $transaction->allow_commit();

            if ($user->contextid) {
                // Now delete the stored, downloadable badge that belongs to this user.
                $fs->delete_area_files($user->contextid, 'badges', 'userbadge', $badge->id);
            }

            // Events for active and suspended users only.
            if ($user->status != target_user::STATUS_DELETED) {
                // Prepare to fire the badge revoked event.
                if ($badge->type == BADGE_TYPE_SITE) {
                    $context = \context_system::instance();
                } else if ($badge->type == BADGE_TYPE_COURSE) {
                    $context = \context_course::instance($badge->courseid);
                } else {
                    // This is not expected. It is not an error, we will guess the system context and if wrong the consequence
                    // is that the event is triggered in wrong context.
                    // We could throw an exception, but won't as this would not necessarily be picked up in testing if
                    // someone introduced a new badge type and forget to deal with this.
                    debugging('Unexpected situation, the badge type was not known.', DEBUG_DEVELOPER);
                    $context = \context_system::instance();
                }
                // Trigger event, badge revoked.
                $eventparams = array(
                    'objectid' => $badge->id,
                    'relateduserid' => $user->id,
                    'context' => $context
                );
                $event = \core\event\badge_revoked::create($eventparams);
                $event->trigger();
            }
        }

        return item::RESULT_STATUS_SUCCESS;
    }

    /**
     * Can issued badges be exported?
     *
     * @return bool
     */
    public static function is_exportable() {
        return true;
    }

    /**
     * Export issued badges for the given user in the given context
     *
     * @param target_user $user
     * @param \context $context restriction for exporting i.e., system context for everything and course context for course export
     * @return \totara_userdata\userdata\export an export object, with data containing an array of badges, and files containing the
     *     downloadable badges.
     */
    protected static function export(target_user $user, \context $context) {

        $rawbadges = self::get_badges_issued_to_user($user, $context);

        $result = new export();

        $fs = get_file_storage();

        // Now, add some basic data to the export as well.
        // Technically we don't need to provide this level of detail, it is encapsulated in the above exported files.
        // However as we have it on hand I will *subjectively* build a small object containing interesting information
        // that the user can access through the image, and provide it here.
        foreach ($rawbadges as $rawbadge) {

            $badge = new \stdClass;
            $badge->badgeid = $rawbadge->id;
            $badge->courseid = $rawbadge->courseid;
            $badge->dateissued = $rawbadge->dateissued;
            $badge->dateexpire = $rawbadge->dateexpire;
            $badge->name = $rawbadge->name;
            $badge->description = $rawbadge->description;
            $badge->issuername = $rawbadge->issuername;
            $badge->issuerurl = $rawbadge->issuerurl;
            $badge->issuercontact = $rawbadge->issuercontact;
            $badge->file = false;

            // First lets get the badge files for this user.
            if ($user->contextid) {
                // We can only do this if we know the users context id.
                // Also please note that if the user has been deleted then the files have already been deleted.
                // This is because they are stored in the user context and when that is deleted so are all files.
                $file = $fs->get_file($user->contextid, 'badges', 'userbadge', $rawbadge->id, '/', $rawbadge->uniquehash . '.png');
                if ($file) {
                    // Add this file to the array of files to export.
                    // And provide some data about it within the badge data that also gets exported.
                    $badge->file = (object)$result->add_file($file);
                }
            }

            $result->data[] = $badge;
        }

        return $result;
    }

    /**
     * Can issued badges be counted?
     *
     * @return bool
     */
    public static function is_countable() {
        return true;
    }

    /**
     * Count the number of issued badges for the given user in the given context and all child contexts.
     *
     * @param target_user $user
     * @param \context $context restriction for counting i.e., system context for everything and course context for course data
     * @return int amount of data or negative integer status code (self::RESULT_STATUS_ERROR or self::RESULT_STATUS_SKIPPED)
     */
    protected static function count(target_user $user, \context $context) {
        $badges = self::get_badges_issued_to_user($user, $context);
        return count($badges);
    }

    /**
     * Returns all of the badges issued to the user in the given context, and any child contexts.
     *
     * @param target_user $user
     * @param \context $context
     * @return \stdClass[]
     */
    private static function get_badges_issued_to_user(target_user $user, \context $context): array {
        global $DB;

        // Copied from \badges_get_user_badges() but modified to work in bulk, and optimised.
        $preloadcontexts = false;
        if ($context->contextlevel == CONTEXT_SYSTEM) {

            $sql = 'SELECT bi.uniquehash, bi.dateissued, bi.dateexpire, bi.id as issuedid, bi.visible, b.*
                      FROM {badge_issued} bi
                      JOIN {badge} b ON b.id = bi.badgeid
                     WHERE bi.userid = :userid
                  ORDER BY bi.dateissued DESC';
            $params = ['userid' => $user->id];

        } else if ($context->contextlevel == CONTEXT_COURSECAT) {

            $preloadcontexts = true;
            $likepathsql = $DB->sql_like('path', ':path');
            $contextfields = \context_helper::get_preload_record_columns_sql('ctx');

            $sql = "SELECT bi.uniquehash, bi.dateissued, bi.dateexpire, bi.id as issuedid, bi.visible, b.*, {$contextfields}
                      FROM {badge_issued} bi
                      JOIN {badge} b ON b.id = bi.badgeid
                      JOIN {context} ctx ON ctx.instanceid = b.courseid
                     WHERE bi.userid = :userid
                       AND ctx.contextlevel = :contextlevel
                       AND {$likepathsql}
                  ORDER BY bi.dateissued DESC";
            $params = [
                'userid' => $user->id,
                'contextlevel' => CONTEXT_COURSE,
                'path' => $context->path . '/%'
            ];

        } else  if ($context->contextlevel == CONTEXT_COURSE) {

            $sql = "SELECT bi.uniquehash, bi.dateissued, bi.dateexpire, bi.id as issuedid, bi.visible, b.*
                      FROM {badge_issued} bi
                      JOIN {badge} b ON b.id = bi.badgeid
                     WHERE bi.userid = :userid
                       AND b.courseid = :courseid
                  ORDER BY bi.dateissued DESC";
            $params = [
                'userid' => $user->id,
                'courseid' => $context->instanceid
            ];

        } else {

            debugging('Unexpected context provided when requesting badges issued to a user. Context level ' . $context->contextlevel, DEBUG_DEVELOPER);
            return [];

        }

        $badges = $DB->get_records_sql($sql, $params);
        if ($preloadcontexts) {
            foreach ($badges as &$badge) {
                // This also unsets context fields.
                \context_helper::preload_from_record($badge);
            }
        }

        return $badges;
    }
}
