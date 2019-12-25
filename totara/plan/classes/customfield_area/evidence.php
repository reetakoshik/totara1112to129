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
 * @package totara_plan
 * @category totara_customfield
 */

namespace totara_plan\customfield_area;

/**
 * Evidence custom field management class.
 *
 * @package totara_plan
 * @category totara_customfield
 */
class evidence implements \totara_customfield\area {

    /**
     * Returns the area name for this area.
     *
     * @return string
     */
    public static function get_area_name() {
        return 'evidence';
    }

    /**
     * Returns the table prefix used by this custom field area.
     *
     * @return string
     */
    public static function get_prefix() {
        return 'dp_plan_evidence';
    }


    /**
     * Returns the component for this area.
     *
     * @return string
     */
    public static function get_component() {
        return 'totara_plan';
    }

    /**
     * Returns an array of fileareas owned by this customfield area.
     *
     * @return string[]
     */
    public static function get_fileareas() {
        return array(
            'evidence',
            'evidence_filemgr',
        );
    }

    /**
     * Returns true if the user can view the custom field area for the given instance.
     *
     * @param \stdClass|int $evidenceorid An instance record OR the id of the instance. If a record is given it must be complete.
     * @return bool
     */
    public static function can_view($evidenceorid) {
        global $DB, $USER;

        if (!is_object($evidenceorid)) {
            $evidence = $DB->get_record('dp_plan_evidence', array('id' => $evidenceorid), '*', MUST_EXIST);
        } else {
            $evidence = $evidenceorid;
        }

        // OK first up we need to verify if the user can access this.
        $allowaccess = false;
        if (has_any_capability(array('totara/plan:accessanyplan', 'totara/plan:editsiteevidence'), \context_system::instance())) {
            // If you have this capability you can certainly access this file.
            $allowaccess = true;
        } else {
            // OK, we need to know who the evidence belongs to.
            if ($evidence->userid == $USER->id) {
                // The current user can see their own evidence.
                $allowaccess = true;
            } else if (\totara_job\job_assignment::is_managing($USER->id, $evidence->userid)) {
                $allowaccess = true;
            }
        }

        return $allowaccess;
    }

    /**
     * Serves a file belonging to this customfield area.
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

        // Access control matches that in totara/plan/record/evidence/view.php and edit.php

        if (totara_feature_disabled('recordoflearning')) {
            // Return and let the calling function call send_file_not_found().
            send_file_not_found();
        }

        if (!in_array($filearea, self::get_fileareas())) {
            // The given file area does not belong to this customfield area, or is not real.
            send_file_not_found();
        }

        require_login($course, false, $cm, false, true);

        // OK first up we need to verify if the user can access this.
        $id = reset($args);
        $sql = 'SELECT e.*
                      FROM {dp_plan_evidence_info_data} eid
                      JOIN {dp_plan_evidence} e ON e.id = eid.evidenceid
                     WHERE eid.id = :id';
        $evidence = $DB->get_record_sql($sql, array('id' => $id), MUST_EXIST);
        $allowaccess = self::can_view($evidence);

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