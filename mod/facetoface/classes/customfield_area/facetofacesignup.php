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
 * Seminar Signup custom field management class.
 *
 * @package mod_facetoface
 * @category totara_customfield
 */
class facetofacesignup implements \totara_customfield\area {

    /**
     * Returns the component for the Seminar User Signup custom field area.
     *
     * @return string
     */
    public static function get_component() {
        return 'mod_facetoface';
    }

    /**
     * Returns the area name for the Seminar User Signup custom field area.
     *
     * @return string
     */
    public static function get_area_name() {
        return 'facetofacesignup';
    }

    /**
     * Returns an array of fileareas owned by the Seminar User Signup custom field area.
     *
     * @return string[]
     */
    public static function get_fileareas() {
        return array(
            'facetofacesignup',
            'facetofacesignup_filemgr',
        );
    }

    /**
     * Returns the table prefix used by the Seminar User Signup custom field area.
     *
     * @return string
     */
    public static function get_prefix() {
        return 'facetoface_signup';
    }

    /**
     * Returns true if the user can view the Seminar User Signup custom field area.
     *
     * There are several ways a user can view signup customfield data.
     *
     *   1. The current user is the user who signed up.
     *   2. They have the capability to view the attendees.
     *   3. The current user is the manager of the user who signed up.
     *   4. The current user was selected as a manager.
     *   5. The current user is a facetoface trainer.
     *   6. The current user is an admin approver.
     *
     * Remember that
     *
     * @param \stdClass|int $signuporid An instance record OR the id of the instance. If a record is given it must be complete.
     * @return bool
     */
    public static function can_view($signuporid) {
        global $DB, $USER;
        if (!is_object($signuporid)) {
            // If its a full blown object with an id then we will assume you can see it.
            $signup = $DB->get_record('facetoface_signups', array('id' => $signuporid), '*', MUST_EXIST);
        } else {
            $signup = $signuporid;
        }

        // 1. The current user is the user who signed up.
        if ($USER->id == $signup->userid) {
            // The current user is allowed to view this.
            return true;
        }

        // 2. They have the capability to view the attendees.
        $ctxfields = \context_helper::get_preload_record_columns_sql('ctx');
        $sql = "SELECT f.*, fs.id AS sessionid, cm.id AS cmid, {$ctxfields}
                  FROM {facetoface_sessions} fs
                  JOIN {facetoface} f ON f.id = fs.facetoface
                  JOIN {course_modules} cm ON cm.instance = f.id
                  JOIN {modules} m ON m.id = cm.module
                  JOIN {context} ctx ON ctx.instanceid = cm.id
                 WHERE fs.id = :sessionid
                   AND m.name = 'facetoface'
                   AND ctx.contextlevel = :modulelevel";
        $params = array(
            'sessionid' => $signup->sessionid,
            'modulelevel' => CONTEXT_MODULE
        );

        $record = $DB->get_record_sql($sql, $params, MUST_EXIST);
        \context_helper::preload_from_record($record);
        $context = \context_module::instance($record->cmid);

        $capabilities = array(
            'mod/facetoface:viewattendees',
        );
        if (has_any_capability($capabilities, $context)) {
            // Anyone who can view the attendees tab can view signup custom fields.
            return true;
        }

        // 3. The current user is the manager of the user who signed up.
        if (\totara_job\job_assignment::is_managing($USER->id, $signup->userid)) {
            return true;
        }

        // 4. The current user was selected as a manager.
        if (get_config(null, 'facetoface_managerselect') && $signup->managerid == $USER->id) {
            // The current user has been selected as the manager for this signup.
            return true;
        }

        // 5. The current user is a facetoface trainer.
        if ($record->approvaltype == \mod_facetoface\seminar::APPROVAL_ROLE) {
            $sessionroles = facetoface_get_trainers($record->sessionid, $record->approvalrole);
            if (!empty($sessionroles)) {
                foreach ($sessionroles as $user) {
                    if ($user->id == $USER->id) {
                        return true;
                    }
                }
            }
        }

        // 6. The current user is an admin approver.
        if ($record->approvaltype == \mod_facetoface\seminar::APPROVAL_ADMIN) {
            if (facetoface_is_adminapprover($USER->id, $record)) {
                return true;
            }
        }

        // If none of the above have passed then this user can not view.
        return false;
    }

    /**
     * Serves a file belonging to the Seminar User Signup custom field area.
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

        // Require login without course and cm.
        // Managers who are not enrolled need to be able to see these images without enrolling during approval.
        // See the hacks in mod/facetoface/attendees/view.php
        require_login();

        // OK first up we need to verify if the user can access this.
        $id = reset($args);
        $sql = 'SELECT fs.*
                      FROM {facetoface_signup_info_data} fsid
                      JOIN {facetoface_signups} fs ON fs.id = fsid.facetofacesignupid
                     WHERE fsid.id = :id';
        $signup = $DB->get_record_sql($sql, array('id' => $id), MUST_EXIST);
        $allowaccess = self::can_view($signup);

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
