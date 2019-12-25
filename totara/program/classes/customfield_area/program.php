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
 * @package totara_program
 * @category totara_customfield
 */

namespace totara_program\customfield_area;

/**
 * Program and Certification custom field management class.
 *
 * @package totara_program
 * @category totara_customfield
 */
class program implements \totara_customfield\area {

    /**
     * Returns the component for this area.
     *
     * @return string
     */
    public static function get_component() {
        return 'totara_program';
    }

    /**
     * Returns the area name for this area.
     *
     * @return string
     */
    public static function get_area_name() {
        return 'program';
    }

    /**
     * Returns an array of fileareas owned by this customfield area.
     *
     * @return string[]
     */
    public static function get_fileareas() {
        return array(
            'program',
            'program_filemgr',
        );
    }

    /**
     * Returns the table prefix used by this custom field area.
     *
     * @return string
     */
    public static function get_prefix() {
        return 'prog';
    }

    /**
     * Returns true if the user can view the program custom fields.
     *
     * At the time of writing this function custom fields are shown in two places:
     *  - on the Overview screen.
     *  - In report builder.
     *
     * The checks here are looser than the overview screen, but are inline with report builder.
     *
     * @param \stdClass|int $programorid An program record OR the id of the program. If a record is given it must be complete.
     * @return bool
     */
    public static function can_view($programorid) {
        global $CFG;

        if ($CFG->forcelogin && !isloggedin()) {
            return false;
        }

        require_once($CFG->dirroot . '/totara/program/program.class.php');

        // This takes either a stdClass or an id.. YAY!
        $program = new \program($programorid);

        if (empty($program->certifid)) {
            if (totara_feature_disabled('programs')) {
                return false;
            }
        } else {
            if (totara_feature_disabled('certifications')) {
                return false;
            }
        }

        return $program->is_viewable();
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
        global $CFG, $DB;

        if (totara_feature_disabled('programs') && totara_feature_disabled('certifications')) {
            // Return and let the calling function call send_file_not_found().
            send_file_not_found();
        }

        if (!in_array($filearea, self::get_fileareas())) {
            // The given file area does not belong to this customfield area, or is not real.
            send_file_not_found();
        }

        if ($CFG->forcelogin) {
            require_login($course, false, $cm, false, true);
        }

        // OK first up we need to verify if the user can access this.
        $id = reset($args);
        $ctxfields = \context_helper::get_preload_record_columns_sql('ctx');
        $sql = "SELECT pid.*, {$ctxfields}
                      FROM {prog_info_data} pid
                      JOIN {context} ctx ON (ctx.instanceid = pid.programid AND ctx.contextlevel = :level)
                     WHERE pid.id = :id";
        $data = $DB->get_record_sql($sql, array('id' => $id, 'level' => CONTEXT_PROGRAM), MUST_EXIST);
        \context_helper::preload_from_record($data);

        $allowaccess = self::can_view($data->programid);

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