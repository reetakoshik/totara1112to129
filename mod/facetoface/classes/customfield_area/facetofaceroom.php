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
 * Room custom field management class.
 *
 * @package mod_facetoface
 * @category totara_customfield
 */
class facetofaceroom implements \totara_customfield\area {

    /**
     * Returns the component for the Seminar Room custom field area.
     *
     * @return string
     */
    public static function get_component() {
        return 'mod_facetoface';
    }

    /**
     * Returns the area name for the Seminar Room custom field area.
     *
     * @return string
     */
    public static function get_area_name() {
        return 'facetofaceroom';
    }

    /**
     * Returns an array of fileareas owned by the Seminar Room custom field area.
     *
     * @return string[]
     */
    public static function get_fileareas() {
        return array(
            'facetofaceroom',
            'facetofaceroom_filemgr',
        );
    }

    /**
     * Returns the table prefix used by the Seminar Room custom field area.
     *
     * @return string
     */
    public static function get_prefix() {
        return 'facetoface_room';
    }

    /**
     * Returns true if the user can view the custom field area for the given instance.
     *
     * @param \stdClass|int $instanceorid An instance record OR the id of the instance. If a record is given it must be complete.
     * @return bool
     */
    public static function can_view($instanceorid) {
        global $DB;
        if (is_object($instanceorid)) {
            // If its a full blown object with an id then we will assume you can see it.
            return isset($instanceorid->id);
        }
        // Check that the given ID is valid.
        return $DB->record_exists('facetoface_room', array('id' => $instanceorid));
    }

    /**
     * Serves a file belonging to the Seminar Room custom field area.
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
        $sql = 'SELECT fr.*
                      FROM {facetoface_room_info_data} frid
                      JOIN {facetoface_room} fr ON fr.id = frid.facetofaceroomid
                     WHERE frid.id = :id';
        $room = $DB->get_record_sql($sql, array('id' => $id), MUST_EXIST);
        $allowaccess = self::can_view($room);

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