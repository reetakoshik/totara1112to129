<?php
/*
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
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 * @package mod_facetoface
 * @category totara_customfield
 */

namespace mod_facetoface\customfield_area;

/**
 * Seminar User Attendance Cancellation custom field area management class.
 *
 * @package mod_facetoface
 * @category totara_customfield
 */
class facetofacesessioncancel implements \totara_customfield\area {

    /**
     * Returns the component for the Seminar User Attendance Cancellation custom field area.
     *
     * @return string
     */
    public static function get_component() {
        return 'mod_facetoface';
    }

    /**
     * Returns the area name for the Seminar User Attendance Cancellation custom field area.
     *
     * @return string
     */
    public static function get_area_name() {
        return 'facetofacesessioncancel';
    }

    /**
     * Returns an array of fileareas owned by the Seminar User Attendance Cancellation custom field area.
     *
     * @return string[]
     */
    public static function get_fileareas() {
        return array(
            'facetofacesessioncancel',
            'facetofacesessioncancel_filemgr',
        );
    }

    /**
     * Returns the table prefix used by the Seminar User Attendance Cancellation custom field area.
     *
     * @return string
     */
    public static function get_prefix() {
        return 'facetoface_sessioncancel';
    }

    /**
     * Returns true if the user can view the custom field area for the Seminar User Attendance Cancellation custom field area.
     *
     * Event cancellation custom fields are shown when viewing the Seminar instance on the attendees page.
     * This is just like the facetofacesession custom fields.
     *
     * @param \stdClass|int $sessionorid An instance record OR the id of the instance. If a record is given it must be complete.
     * @return bool
     */
    public static function can_view($sessionorid) {
        global $DB;

        if (!is_object($sessionorid)) {
            // If its a full blown object with an id then we will assume you can see it.
            $session = $DB->get_record('facetoface_sessions', array('id' => $sessionorid), '*', MUST_EXIST);
        } else {
            $session = $sessionorid;
        }

        // 2. They have the capability to view the attendees.
        $ctxfields = \context_helper::get_preload_record_columns_sql('ctx');
        $sql = "SELECT f.*, cm.id AS cmid, {$ctxfields}
                  FROM {facetoface} f
                  JOIN {course_modules} cm ON cm.instance = f.id
                  JOIN {modules} m ON m.id = cm.module
                  JOIN {context} ctx ON ctx.instanceid = cm.id
                 WHERE f.id = :facetoface
                   AND m.name = 'facetoface'
                   AND ctx.contextlevel = :modulelevel";
        $params = array(
            'facetoface' => $session->facetoface,
            'modulelevel' => CONTEXT_MODULE
        );

        $record = $DB->get_record_sql($sql, $params, MUST_EXIST);
        \context_helper::preload_from_record($record);
        $context = \context_module::instance($record->cmid);

        $capabilities = array(
            'mod/facetoface:view',
        );
        if (has_any_capability($capabilities, $context)) {
            // Anyone who can view the attendees tab can view signup custom fields.
            return true;
        }

        // If none of the above have passed then this user can not view.
        return false;
    }

    /**
     * Serves a file belonging to the Seminar User Attendance Cancellation custom field area.
     *
     * @param \stdClass $course
     * @param \stdClass $cm
     * @param \context $context
     * @param string $filearea
     * @param array $args
     * @param bool $forcedownload
     * @param array $options
     * @return void
     */
    public static function pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, $options = array()) {
        global $DB;

        if (!in_array($filearea, self::get_fileareas())) {
            // The given file area does not belong to this customfield area, or is not real.
            send_file_not_found();
        }

        require_login($course, false, $cm, false, true);

        // OK first up we need to verify if the user can access this.
        $id = reset($args);
        $sql = 'SELECT fs.*
                      FROM {facetoface_sessioncancel_info_data} fsid
                      JOIN {facetoface_sessions} fs ON fs.id = fsid.facetofacesessioncancelid
                     WHERE fsid.id = :id';
        $session = $DB->get_record_sql($sql, array('id' => $id), MUST_EXIST);
        $allowaccess = self::can_view($session);

        if ($allowaccess) {
            $fs = get_file_storage();
            $fullpath = "/{$context->id}/totara_customfield/$filearea/$args[0]/$args[1]";
            if (!$file = $fs->get_file_by_hash(sha1($fullpath)) or $file->is_directory()) {
                send_file_not_found();
            }
            // Finally send the file.
            send_stored_file($file, 86400, 0, true, $options); // download MUST be forced - security!
        }

        send_file_not_found();
    }

}
