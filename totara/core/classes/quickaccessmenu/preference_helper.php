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
 * @author Alastair Munro <alastair.munro@totaralearning.com>
 * @package totara_core
 */

namespace totara_core\quickaccessmenu;

defined('MOODLE_INTERNAL') || die();

final class preference_helper {

    /**
     * Sets a quickaccess preference for the specified user.
     *
     * @param int $userid
     * @param string $name The key to set as preference for the specified user
     * @param mixed $value The value to set for the $name key in the specified user's
     *                     record, null means delete current value.
     *                     The value gets json_encoded for storage in the database.
     *
     * @return bool Always true
     */
    public static function set_preference(int $userid, string $name, $value): bool {
        global $DB;

        if (is_null($value)) {
            // Null means delete current.
            return self::unset_preference($userid, $name);
        }

        if (isguestuser($userid)) {
            // No permanent storage for not-logged-in users and guest.
            throw new \coding_exception('Preferences cannot be set for the guest user.');
        }

        $value_encoded = json_encode($value);

        if ($preference = $DB->get_record('quickaccess_preferences', array('userid' => $userid, 'name' => $name))) {
            if ($preference->value === $value_encoded) {
                // Preference already set to this value.
                return true;
            }
            $DB->set_field('quickaccess_preferences', 'value', $value_encoded, array('id' => $preference->id));

        } else {
            $preference = new \stdClass();
            $preference->userid = $userid;
            $preference->name   = $name;
            $preference->value  = $value_encoded;
            $DB->insert_record('quickaccess_preferences', $preference);
        }

        // Clear cache
        $cache = \cache::make('totara_core', 'quickaccessmenu');
        $cache->delete($userid);

        return true;
    }

    /**
     * Unsets a preference completely by deleting it from the database and clearing cache.
     *
     * @param int    $userid
     * @param string $name The key to unset as preference for the specified user
     *
     * @return bool  Always true
     */
    public static function unset_preference(int $userid, string $name): bool {
        global $DB;

        if (isguestuser($userid)) {
            // No permanent storage for not-logged-in user and guest.
            throw new \coding_exception('Preferences cannot be set for the guest user.');
        }

        // Delete from DB.
        $DB->delete_records('quickaccess_preferences', array('userid' => $userid, 'name' => $name));

        // Clear cache
        $cache = \cache::make('totara_core', 'quickaccessmenu');
        $cache->delete($userid);

        return true;
    }

    /**
     * Resets all quick access menu preferences for the given user.
     *
     * @param int $userid
     * @return bool Always true
     */
    public static function reset_for_user(int $userid): bool {
        global $DB;

        if (isguestuser($userid)) {
            // No permanent storage for not-logged-in user and guest.
            throw new \coding_exception('Preferences cannot be set for the guest user.');
        }

        // Delete from DB.
        $DB->delete_records('quickaccess_preferences', ['userid' => $userid]);
        // Clear cache
        $cache = \cache::make('totara_core', 'quickaccessmenu');
        $cache->delete($userid);
        return true;
    }

    /**
     * Used to fetch user's quickaccess preference
     *
     * If $name isn't supplied this function will return all of the user preferences as an array.
     *
     * If a $name is specified then this function attempts to return that particular preference
     * value. If none is found, then the optional value $default is returned, otherwise null.
     *
     * @param int        $userid
     * @param string     $name    Name of the key to use in finding a preference value
     * @param mixed|null $default Value to be returned if the $name key is not set in the user preferences
     *
     * @return string|mixed|null  A string containing the value of a single preference. An
     *                            array with all of the preferences or null
     */
    public static function get_preference(int $userid, string $name = null, $default = null) {
        global $DB;

        // Make cache
        $itemcache = \cache::make('totara_core', 'quickaccessmenu');
        $data = $itemcache->get($userid);

        // If the cache is not populated then load from database
        if ($data === false) {
            $data = $DB->get_records_menu('quickaccess_preferences', array('userid' => $userid), '', 'name,value'); // All values.

            $data = array_map('json_decode', $data);

            // Save to cache for next time
            $itemcache->set($userid, $data);
        }

        if (empty($name)) {
            // All values.
            return $data;
        } else if (isset($data[$name])) {
            // The single string value.
            return $data[$name];
        } else {
            // Default value (null if not specified).
            return $default;
        }
    }
}
