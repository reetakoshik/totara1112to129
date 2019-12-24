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
 * @package totara_userdata
 */

namespace totara_userdata\local;

use \totara_userdata\userdata\item;
use totara_userdata\userdata\target_user;

defined('MOODLE_INTERNAL') || die();

/**
 * Helpers for user data export.
 *
 * NOTE: This is not a public API - do not use in plugins or 3rd party code!
 */
final class export {
    /* Maximum allowed time for execution of one item */
    public const MAX_ITEM_EXECUTION_TIME = 60 * 10;

    /** How long do we allow export to run? */
    public const MAX_TOTAL_EXECUTION_TIME = 60 * 60 * 24 * 1;

    /** When do we remove the generated file? This is both for privacy and data storage usage reasons */
    public const MAX_FILE_AVAILABILITY_TIME = 60 * 60 * 24 * 5;

    /* Maximum allowed time for creation of archive */
    public const MAX_ZIP_EXECUTION_TIME = 60 * 60 * 1;

    /**
     * Returns names of allowed export origins.
     *
     * @return string[]
     */
    public static function get_origins() {
        return array(
            'self' => get_string('exportoriginself', 'totara_userdata'),
            'other' => get_string('exportoriginother', 'totara_userdata'),
        );
    }

    /**
     * Returns list of item classes available in the system
     * that support user data export.
     *
     * @return string[] list of class names
     */
    public static function get_exportable_item_classes() {
        $classes = array();

        /** @var \totara_userdata\userdata\item $class this is not an instance, but it helps with autocomplete */
        foreach (util::get_item_classes() as $class) {
            if (!$class::is_exportable()) {
                continue;
            }
            $classes[] = $class;
        }

        return $classes;
    }

    /**
     * Returns list of all item classes that allow exporting grouped by main component.
     *
     * This is intended for UI item visual grouping.
     *
     * @return array nested lists of classes grouped by component
     */
    public static function get_exportable_items_grouped_list() {
        $classes = array();

        /** @var item $class this is not an instance, but it helps with autocomplete */
        foreach (self::get_exportable_item_classes() as $class) {
            $maincomponent = $class::get_main_component();
            if (!isset($classes[$maincomponent])) {
                $classes[$maincomponent] = array();
            }
            $classes[$maincomponent][$class] = $class::get_sortorder();
        }

        // Move 'User' to the top of the list.
        uksort($classes, function($a, $b) { return $b === 'core_user'; });

        // Sort user data items within components using sortorder defined in items.
        foreach ($classes as $maincomponent => $items) {
            asort($items, SORT_NUMERIC);
            $classes[$maincomponent] = array_keys($items);
        }

        return $classes;
    }

    /**
     * Execute user data export.
     *
     * Export file is created only if there is any result data,
     * the success result is returned.
     *
     * @param \stdClass $export
     * @return int export result
     */
    public static function export_items(\stdClass $export) {
        global $DB, $CFG;

        $oldtimelimit = ini_get('max_execution_time');

        $requester =  $DB->get_record('user', array('id' => $export->usercreated, 'deleted' => 0), '*', MUST_EXIST);
        cron_setup_user($requester);

        $exporttype = $DB->get_record('totara_userdata_export_type', array('id' => $export->exporttypeid), '*', MUST_EXIST);

        $user = $DB->get_record('user', array('id' => $export->userid));
        if (!$user) {
            return item::RESULT_STATUS_ERROR;
        }
        $targetuser = new target_user($user);

        $context = \context::instance_by_id($export->contextid, IGNORE_MISSING);
        if (!$context) {
            // The requested context was deleted in the meantime, stop!
            return item::RESULT_STATUS_ERROR;
        }

        $results = array();
        /** @var \stored_file[] $exportfiles */
        $exportfiles = array();

        $items = $DB->get_records('totara_userdata_export_type_item', array('exporttypeid' => $export->exporttypeid, 'exportdata' => 1));
        $enabled = array();
        foreach ($items as $item) {
            $enabled[$item->component . '\\' . 'userdata' . '\\' . $item->name] = true;
        }
        unset($items);
        $classes = array();
        $groups = self::get_exportable_items_grouped_list(); // Keep the order the same as in UI to prevent unexpected dependency problems.
        foreach ($groups as $list) {
            foreach ($list as $class) {
                if (empty($enabled[$class])) {
                    // Not enabled, skip it.
                    continue;
                }
                $classes[] = $class;
            }
        }
        unset($groups);
        foreach ($classes as $class) {
            /** @var item $class this is not an instance, but it helps with autocomplete */

            if (!$class::is_compatible_context_level($context->contextlevel)) {
                // Item not compatible with this level, no point adding record for this item.
                error_log('User data export: item ' . $class . ' not compatible with context level ' . $context->contextlevel);
                continue;
            }

            $record = new \stdClass();
            $record->exportid = $export->id;
            $record->component = $class::get_component();
            $record->name = $class::get_name();
            $record->timestarted = time();
            $record->id = $DB->insert_record('totara_userdata_export_item', $record);

            try {
                // We need to set some higher time limit, but we must not leave this unlimited.
                set_time_limit(self::MAX_ITEM_EXECUTION_TIME);

                $exportresult = $class::execute_export($targetuser, $context);
                if ($exportresult === item::RESULT_STATUS_ERROR or $exportresult === item::RESULT_STATUS_SKIPPED) {
                    // Cancelled is not allowed here!
                    /** @var int $exportresult */
                    $result = $exportresult;
                } else {
                    /** @var \totara_userdata\userdata\export $exportresult */
                    $result = item::RESULT_STATUS_SUCCESS;
                    $results[$record->component . '-' . $record->name] = $exportresult->data;
                    if (!empty($exportresult->files)) {
                        foreach ($exportresult->files as $exportfile) {
                            if (!($exportfile instanceof \stored_file)) {
                                throw new \coding_exception('Invalid stored file instance returned from export method');
                            }
                            // NOTE: developers must make sure user is allowed to get the file,
                            //       we cannot test file authorship here because sometimes it is not recorded properly.
                            if ($exporttype->includefiledir) {
                                $exportfiles[$exportfile->get_id()] = $exportfile;
                            }
                        }
                    }
                }
                unset($exportresult); // Release memory.

            } catch (\Throwable $ex) {
                $result = item::RESULT_STATUS_ERROR;
                $message = $ex->getMessage();
                if ($ex instanceof \moodle_exception) {
                    $message .= ' - ' . $ex->debuginfo;
                }
                debugging("bug in item export {$record->component} - {$record->name}: " . $message, DEBUG_DEVELOPER);
            }
            $record->timefinished = time();
            $record->result = $result;
            unset($record->exportid);
            unset($record->component);
            unset($record->name);
            unset($record->timestarted);
            $DB->update_record('totara_userdata_export_item', $record);
        }

        set_time_limit($oldtimelimit);

        if (!$results) {
            mtrace('No user data returned from export ' . $export->id);
            return item::RESULT_STATUS_ERROR;
        }

        $results = json_encode($results);
        if ($results === false) {
            $jsonerrormsg = json_last_error_msg();
            mtrace('Json encoding error (' . $jsonerrormsg . ') in export ' . $export->id);
            return item::RESULT_STATUS_ERROR;
        }

        set_time_limit(self::MAX_ZIP_EXECUTION_TIME);

        $tempdir = make_request_directory();
        file_put_contents($tempdir . '/data.json', $results);
        @chmod($tempdir . '/data.json', $CFG->filepermissions);
        unset($results); // Free memory.
        $archivefiles = array('data.json' => $tempdir . '/data.json');

        if ($exportfiles) {
            // Include list of files sorted by id and optionally the file contents.
            ksort($exportfiles, SORT_NUMERIC);
            mkdir($tempdir . '/filedir', $CFG->directorypermissions, true);
            $filelist = array();
            foreach ($exportfiles as $exportfile) {
                $contenthash = $exportfile->get_contenthash();
                $tempfilepath = $tempdir . '/filedir/' . $contenthash;
                if (!file_exists($tempfilepath)) {
                    $exportfile->copy_content_to($tempfilepath);
                    @chmod($tempfilepath, $CFG->filepermissions);
                    $archivefiles['filedir/' . $contenthash] = $tempfilepath;
                }
                $filelist[] = array('id' => $exportfile->get_id(), 'name' => $exportfile->get_filename(), 'content' => 'filedir/' . $contenthash);
            }
            file_put_contents($tempdir . '/files.json', json_encode($filelist));
            @chmod($tempdir . '/files.json', $CFG->filepermissions);
            $archivefiles['files.json'] = $tempdir . '/files.json';
            unset($filelist);
            unset($exportfiles);
        }

        $packer = get_file_packer('application/x-gzip');
        $storedfile = $packer->archive_to_storage($archivefiles, SYSCONTEXTID, 'totara_userdata', 'export', $export->id, '/', 'export.tgz');

        set_time_limit($oldtimelimit);

        if (!$storedfile) {
            mtrace('Cannot write file for user data export ' . $export->id);
            return item::RESULT_STATUS_ERROR;
        }

        // Aways return ok result if we get here, the individual items have separate result fields.
        return item::RESULT_STATUS_SUCCESS;
    }

    /**
     * Returns the export file record for given export if it exists.
     *
     * @param int $exportid
     * @return false|\stdClass
     */
    public static function get_result_file_record($exportid) {
        global $DB;
        $cutoff = time() - self::MAX_FILE_AVAILABILITY_TIME;
        $params = array('cutoff' => $cutoff, 'syscontextid' => SYSCONTEXTID, 'exportid' => $exportid, 'success' => item::RESULT_STATUS_SUCCESS);
        $sql = "SELECT f.*
                  FROM {files} f
                  JOIN {context} c ON (c.id = f.contextid)
                  JOIN {totara_userdata_export} e ON e.id = f.itemid
                 WHERE c.id = :syscontextid AND f.component = 'totara_userdata' AND f.filearea = 'export'
                       AND e.timefinished > :cutoff AND e.id = :exportid AND e.result = :success
                       AND f.filepath = '/' AND f.filename = 'export.tgz'";

        return $DB->get_record_sql($sql, $params);
    }

    /**
     * Delete result file of this export if it exists.
     *
     * @param int $exportid
     */
    public static function delete_result_file($exportid) {
        $fs = get_file_storage();
        $fs->delete_area_files(SYSCONTEXTID, 'totara_userdata', 'export', $exportid);
    }

    /**
     * Time out slow running tasks, purge old files, etc.
     *
     * NOTE: this should be pretty fast
     */
    public static function internal_cleanup() {
        global $DB;

        // Time out exports that take too long to generate.
        $cutoff = time() - self::MAX_TOTAL_EXECUTION_TIME;
        $select = "result IS NULL AND timestarted < :cutoff";
        $params = array('cutoff' => $cutoff);
        $DB->set_field_select('totara_userdata_export', 'result', item::RESULT_STATUS_TIMEDOUT, $select, $params);

        // Delete old files.
        $cutoff = time() - self::MAX_FILE_AVAILABILITY_TIME;
        $params = array('cutoff' => $cutoff, 'syscontextid' => SYSCONTEXTID);
        $sql = "SELECT f.*
                  FROM {files} f
                  JOIN {context} c ON (c.id = f.contextid)
                  JOIN {totara_userdata_export} e ON e.id = f.itemid
                 WHERE c.id = :syscontextid AND f.component = 'totara_userdata' AND f.filearea = 'export'
                       AND e.timefinished < :cutoff";
        $fs = get_file_storage();
        $filerecords = $DB->get_recordset_sql($sql, $params);
        foreach ($filerecords as $filerecord) {
            $file = $fs->get_file_instance($filerecord);
            $file->delete();
        }
        $filerecords->close();
    }

    /**
     * Returns last self export record of current user.
     *
     * @return bool|\stdClass
     */
    public static function get_my_last_export() {
        global $DB, $USER;

        $lastexportid = $DB->get_field('totara_userdata_export', 'MAX(id)', array('userid' => $USER->id, 'usercreated' => $USER->id, 'origin' => 'self'));
        if (!$lastexportid) {
            return false;
        }
        return $DB->get_record('totara_userdata_export', array('id' => $lastexportid));
    }

    /**
     * Is export file available for current user?
     *
     * @param \stdClass $export
     * @return bool
     */
    public static function is_export_file_available(\stdClass $export) {
        global $USER, $DB;

        if ($export->origin !== 'self') {
            return false;
        }

        if (!get_config('totara_userdata', 'selfexportenable')) {
            return false;
        }

        if ($export->userid != $USER->id) {
            return false;
        }

        if ($export->usercreated != $USER->id) {
            return false;
        }

        $usercontext = \context_user::instance($USER->id);
        if (!has_capability('totara/userdata:exportself', $usercontext)) {
            return false;
        }

        $type = $DB->get_record('totara_userdata_export_type', array('id' => $export->exporttypeid));
        if (!$type) {
            return false;
        }

        if (!$type->allowself) {
            return false;
        }

        $filerecord = self::get_result_file_record($export->id);
        if (!$filerecord) {
            return false;
        }

        return true;
    }
}
