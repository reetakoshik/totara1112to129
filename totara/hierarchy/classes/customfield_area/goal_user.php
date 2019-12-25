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
 * @package totara_hierarchy
 * @category totara_customfield
 */

namespace totara_hierarchy\customfield_area;

/**
 * User goal custom field management class.
 *
 * @package totara_hierarchy
 * @category totara_customfield
 */
class goal_user implements \totara_customfield\area {

    /**
     * Returns the component for this area.
     *
     * @return string
     */
    public static function get_component() {
        return 'totara_hierarchy';
    }

    /**
     * Returns the area name for this area.
     *
     * @return string
     */
    public static function get_area_name() {
        return 'goal_user';
    }

    /**
     * Returns an array of fileareas owned by this customfield area.
     *
     * @return string[]
     */
    public static function get_fileareas() {
        return array(
            'goal_user',
            'goal_user_filemgr',
        );
    }

    /**
     * Returns the table prefix used by this custom field area.
     *
     * @return string
     */
    public static function get_prefix() {
        return 'goal_user';
    }

    /**
     * Returns true if the user can view the custom field area for the given instance.
     *
     * Access is copied from totara/hierarchy/prefix/goal/item/view.php
     *
     * @param \stdClass|int $instanceorid An instance record OR the id of the instance. If a record is given it must be complete.
     * @return bool
     */
    public static function can_view($instanceorid) {
        global $CFG, $USER;

        require_once($CFG->dirroot . '/totara/hierarchy/prefix/goal/lib.php');

        if (is_object($instanceorid)) {
            $id = $instanceorid->id;
        } else {
            $id = $instanceorid;
        }

        // Load the item just to confirm it exists.
        $goalpersonal = \goal::get_goal_item(array('id' => $id), \goal::SCOPE_PERSONAL);
        if (!$goalpersonal) {
            error_log('Goal file area: personal goal with id "' . $id . '" does not exist');
            return false;
        }

        $goal = new \goal();
        $permissions = $goal->get_permissions(null, $USER->id);
        if (!$permissions) {
            return false;
        }
        return ($permissions['canview'] || $permissions['can_view_company']);
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

        if (!in_array($filearea, self::get_fileareas())) {
            // The given file area does not belong to this customfield area, or is not real.
            send_file_not_found();
        }

        require_login($course, false, $cm, false, true);

        // OK first up we need to verify if the user can access this.
        $sql = "SELECT g.*
                  FROM {goal_user_info_data} gid
                  JOIN {goal_personal} g ON g.id = gid.goal_userid
                 WHERE gid.id = :id";
        $params = array(
            'id' => reset($args)
        );
        $goal = $DB->get_record_sql($sql, $params, MUST_EXIST);
        $allowaccess = self::can_view($goal);

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
