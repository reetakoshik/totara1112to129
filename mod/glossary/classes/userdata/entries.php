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
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 * @author Matthias Bonk <matthias.bonk@totaralearning.com>
 * @package mod_glossary
 */

namespace mod_glossary\userdata;

use comment;
use completion_info;
use context;
use context_module;
use mod_glossary\event\entry_deleted;
use mod_glossary\local\concept_cache;
use rating_manager;
use totara_userdata\userdata\export;
use totara_userdata\userdata\target_user;

defined('MOODLE_INTERNAL') || die();

/**
 * Glossary entries user content.
 */
class entries extends \totara_userdata\userdata\item {
    /**
     * String used for human readable name of this item.
     *
     * @return array parameters of get_string($identifier, $component) to get full item name and optionally help.
     */
    public static function get_fullname_string() {
        return ['entries', 'mod_glossary'];
    }

    /**
     * Returns sort order.
     *
     * @return int
     */
    public static function get_sortorder() {
        return 100;
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
     * @return int result self::RESULT_STATUS_SUCCESS, self::RESULT_STATUS_ERROR or self::RESULT_STATUS_SKIPPED
     */
    protected static function purge(target_user $user, context $context) {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/mod/glossary/lib.php');
        require_once($CFG->dirroot . '/mod/glossary/rsslib.php');
        require_once($CFG->dirroot . '/rating/lib.php');
        require_once($CFG->dirroot . '/comment/lib.php');

        $fs = get_file_storage();
        $rm = new rating_manager();
        $glossaries = [];

        // Remove original exported items first.
        $join = self::get_activities_join($context, 'glossary', 'e.sourceglossaryid', 'g');
        $sql = "SELECT e.*, cm.id AS cmid, cm.idnumber AS cmidnumber
                  FROM {glossary_entries} e
                 $join
                 WHERE e.userid = :userid
              ORDER BY e.id";
        $entries = $DB->get_recordset_sql($sql, ['userid' => $user->id]);
        foreach ($entries as $entry) {
            if (!isset($glossaries[$entry->sourceglossaryid])) {
                $glossaries[$entry->sourceglossaryid] = [
                    'id' => $entry->sourceglossaryid,
                    'cmid' => $entry->cmid,
                    'cmidnumber' => $entry->cmidnumber,
                    'reset' => 0,
                ];
            }
            $glossarycontext = context_module::instance($entry->cmid);

            // Delete ratings.
            $rm->delete_ratings((object)[
                'contextid' => $glossarycontext->id,
                'component' => 'mod_glossary',
                'ratingarea' => 'entry',
                'itemid' => $entry->id
            ]);

            // Delete comments.
            comment::delete_comments([
                'contextid' => $glossarycontext->id,
                'component' =>'mod_glossary',
                'commentarea' => 'glossary_entry',
                'itemid' => $entry->id
            ]);

            // Delete attachment and inline (editor) files.
            $fs->delete_area_files($glossarycontext->id, 'mod_glossary', 'attachment', $entry->id);
            $fs->delete_area_files($glossarycontext->id, 'mod_glossary', 'entry', $entry->id);

            if ($entry->usedynalink and $entry->approved) {
                $glossaries[$entry->sourceglossaryid]['reset'] = 1;
            }
            // Exported entries are a mess, better not trigger anything here.
            $DB->set_field('glossary_entries', 'sourceglossaryid', 0, ['id' => $entry->id]);
        }
        $entries->close();

        $join = self::get_activities_join($context, 'glossary', 'e.glossaryid', 'g');
        $sql = "SELECT e.*, cm.id AS cmid, cm.idnumber AS cmidnumber
                  FROM {glossary_entries} e
                 $join
                 WHERE e.userid = :userid
                   AND e.sourceglossaryid = 0
              ORDER BY e.id";
        $entries = $DB->get_recordset_sql($sql, ['userid' => $user->id]);
        foreach ($entries as $entry) {
            if (!isset($glossaries[$entry->glossaryid])) {
                $glossaries[$entry->glossaryid] = [
                    'id' => $entry->glossaryid,
                    'cmid' => $entry->cmid,
                    'cmidnumber' => $entry->cmidnumber,
                    'reset' => 0
                ];
            }
            $glossarycontext = context_module::instance($entry->cmid);

            // Delete ratings.
            $rm->delete_ratings((object)[
                'contextid' => $glossarycontext->id,
                'component' => 'mod_glossary',
                'ratingarea' => 'entry',
                'itemid' => $entry->id
            ]);

            // Delete comments.
            comment::delete_comments([
                'contextid' => $glossarycontext->id,
                'component' => 'mod_glossary',
                'commentarea' => 'glossary_entry',
                'itemid' => $entry->id
            ]);

            // Delete attachment and inline (editor) files.
            $fs->delete_area_files($glossarycontext->id, 'mod_glossary', 'attachment', $entry->id);
            $fs->delete_area_files($glossarycontext->id, 'mod_glossary', 'entry', $entry->id);

            $DB->delete_records('glossary_alias', ['entryid' => $entry->id]);
            $DB->delete_records('glossary_entries', ['id' => $entry->id]);

            if ($entry->usedynalink and $entry->approved) {
                $glossaries[$entry->glossaryid]['reset'] = 1;
            }

            // Trigger event.
            unset($entry->cmid);
            unset($entry->cmidnumber);
            $event = entry_deleted::create([
                'context' => $glossarycontext,
                'objectid' => $entry->id,
                'other' => [
                    'concept' => $entry->concept
                ]
            ]);
            $event->add_record_snapshot('glossary_entries', $entry);
            $event->trigger();
        }
        $entries->close();

        foreach ($glossaries as $gdata) {
            $glossary = $DB->get_record('glossary', ['id' => $gdata['id']]);
            $glossary->cmidnumber = $gdata['cmidnumber'];
            $course = $DB->get_record('course', ['id' => $glossary->course]);
            $cm = $DB->get_record('course_modules', ['id' => $gdata['cmid']]);

            // Purge concept cache if necessary.
            if ($gdata['reset']) {
                concept_cache::reset_glossary($glossary);
            }

            // Delete cached RSS feeds.
            if (!empty($CFG->enablerssfeeds)) {
                glossary_rss_delete_file($glossary);
            }

            if ($user->status !== target_user::STATUS_DELETED) {
                // Update completion state.
                $completion = new completion_info($course);
                if ($completion->is_enabled($cm) == COMPLETION_TRACKING_AUTOMATIC && $glossary->completionentries) {
                    $completion->update_state($cm, COMPLETION_INCOMPLETE, $user->id);
                }

                // Regrade if assessed.
                if ($glossary->assessed) {
                    glossary_update_grades($glossary, $user->id, true);
                }
            }
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
     * @param context $context restriction for exporting i.e., system context for everything and course context for course export
     * @return export|int result object or integer error code self::RESULT_STATUS_ERROR or self::RESULT_STATUS_SKIPPED
     */
    protected static function export(target_user $user, context $context) {
        global $DB;

        $fs = get_file_storage();

        $export = new export();

        $join = self::get_activities_join($context, 'glossary', 'e.glossaryid', 'g');
        $sql = "SELECT e.*, cm.id AS cmid
                  FROM {glossary_entries} e
                 $join
                 WHERE e.userid = :userid
                   AND e.sourceglossaryid = 0
              ORDER BY e.id";
        $entries = $DB->get_recordset_sql($sql, ['userid' => $user->id]);
        foreach ($entries as $entry) {
            $e = ['id' => $entry->id, 'glossaryid' => $entry->glossaryid, 'concept' => $entry->concept, 'definition' => $entry->definition];
            $filecontext = context_module::instance($entry->cmid, IGNORE_MISSING);
            if ($filecontext) {
                $files = $fs->get_area_files($filecontext->id, 'mod_glossary', 'entry', $entry->id, "timemodified", false, 0, $user->id);
                if ($files) {
                    $e['files'] = [];
                    foreach ($files as $file) {
                        $e['files'][] = $export->add_file($file);
                    }
                }
                if ($entry->attachment) {
                    $files = $fs->get_area_files($filecontext->id, 'mod_glossary', 'attachment', $entry->id, "timemodified", false, 0, $user->id);
                    if ($files) {
                        $e['attachments'] = [];
                        foreach ($files as $file) {
                            $e['attachments'][] = $export->add_file($file);
                        }
                    }
                }
            }
            $export->data[$entry->id] = $e;
        }
        $entries->close();

        // Add export of attachment and inline (editor) files here when fixing bug TL-17394.
        $join = self::get_activities_join($context, 'glossary', 'e.sourceglossaryid', 'g');
        $sql = "SELECT e.*, cm.id AS cmid
                  FROM {glossary_entries} e
                 $join
                 WHERE e.userid = :userid
              ORDER BY e.id";
        $entries = $DB->get_recordset_sql($sql, ['userid' => $user->id]);
        foreach ($entries as $entry) {
            $e = ['id' => $entry->id, 'glossaryid' => $entry->glossaryid, 'concept' => $entry->concept, 'definition' => $entry->definition];
            $export->data[$entry->id] = $e;
        }
        $entries->close();

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
     * @return int  integer is the count >= 0, negative number is error result self::RESULT_STATUS_ERROR or self::RESULT_STATUS_SKIPPED
     */
    protected static function count(target_user $user, context $context) {
        global $DB;

        $join = self::get_activities_join($context, 'glossary', 'e.glossaryid', 'g');
        $sql = "SELECT COUNT(e.id)
                  FROM {glossary_entries} e
                 $join
                 WHERE e.userid = :userid
                   AND sourceglossaryid = 0";
        $count = $DB->count_records_sql($sql, ['userid' => $user->id]);

        // Exported entries.
        $join = self::get_activities_join($context, 'glossary', 'e.sourceglossaryid', 'g');
        $sql = "SELECT COUNT(e.id)
                  FROM {glossary_entries} e
                 $join
                 WHERE e.userid = :userid";
        return $count + $DB->count_records_sql($sql, ['userid' => $user->id]);
    }
}