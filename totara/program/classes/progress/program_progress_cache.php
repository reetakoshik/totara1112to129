<?php
/*
 * This file is part of Totara Learn
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
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package totara
 * @subpackage program
 */

namespace totara_program\progress;

defined('MOODLE_INTERNAL') || die();

/**
 * Totara program completion cache class
 * @since Totara 12
 */
class program_progress_cache {

    /**
     * Returns the program_progressinfo cache.
     * Making this method public to allow it to be used in tests
     *
     * @return cache_application
     */
    public static function get_progressinfo_cache() {
        return \cache::make('totara_program', 'program_progressinfo');
    }

    /**
     * Returns the string to use as key in the progressinfo cache.
     * Making this method public to allow it to be used in tests
     *
     * @param int $programid Program id
     * @param int $userid User id
     * @return string
     */
    public static function get_progressinfo_cache_key($programid, $userid) {
        return "{$programid}_{$userid}";
    }

    /**
     * Marks the progressinfo cache stale for this entry
     * @param int $programid Program id
     * @param int $userid User id
     */
    public static function mark_progressinfo_stale($programid, $userid) {
        $cache = self::get_progressinfo_cache();
        $key = self::get_progressinfo_cache_key($programid, $userid);
        $cache->delete($key);

        // Also remove key from the program_user cache and user_program cache
        $progcache = self::get_program_users_cache();
        $progkey = self::get_program_users_cache_key($programid);
        $userkeys = $progcache->get($progkey);

        if (is_array($userkeys)) {
            if (($idx = array_search($key, $userkeys)) !== false) {
                unset($userkeys[$idx]);
                $progcache->set($progkey, $userkeys);
            }
        }

        $usercache = self::get_user_programs_cache();
        $userkey = self::get_user_programs_cache_key($userid);
        $progkeys = $usercache->get($userkey);

        if (is_array($progkeys)) {
            if (($idx = array_search($key, $progkeys)) !== false) {
                unset($progkeys[$idx]);
                $usercache->set($userkey, $progkeys);
            }
        }
    }

    /**
     * Returns the program_users cache.
     * Making this method public to allow it to be used in tests
     *
     * @return cache_application
     */
    public static function get_program_users_cache() {
        return \cache::make('totara_program', 'program_users');
    }

    /**
     * Returns the string to use as key in the program_users cache.
     * Making this method public to allow it to be used in tests
     *
     * @param int $programid Program id
     * @return string
     */
    public static function get_program_users_cache_key($programid) {
        return "{$programid}";
    }

    /**
     * Marks the program_users cache stale as well as the progressinfo cache
     * for users in the program
     * @param int $programid Program id
     */
    public static function mark_program_cache_stale($programid) {
        $progcache = self::get_program_users_cache();
        $key = self::get_program_users_cache_key($programid);

        // Also delete all cached data from the progressinfo cache for this program
        // It is after all the whole reason for having this cache
        $userkeys = $progcache->get($key);
        if (is_array($userkeys)) {
            $cache = self::get_progressinfo_cache();
            $cache->delete_many($userkeys);
        }

        $progcache->delete($key);
    }

    /**
     * Returns the users_program cache.
     * Making this method public to allow it to be used in tests
     *
     * @return cache_application
     */
    public static function get_user_programs_cache() {
        return \cache::make('totara_program', 'user_programs');
    }

    /**
     * Returns the string to use as key in the user_programs cache.
     * Making this method public to allow it to be used in tests
     *
     * @param int $userid User id
     * @return string
     */
    public static function get_user_programs_cache_key($userid) {
        return "{$userid}";
    }

    /**
     * Marks the user_programs cache stale as well as the progressinfo caches
     * for this user
     * @param int $userid User id
     */
    public static function mark_user_cache_stale($userid) {
        $usercache = self::get_user_programs_cache();
        $key = self::get_user_programs_cache_key($userid);

        // Also delete all cached data from the progressinfo cache for this user
        // It is after all the whole reason for having this cache
        $progkeys = $usercache->get($key);
        if (is_array($progkeys)) {
            $cache = self::get_progressinfo_cache();
            $cache->delete_many($progkeys);
        }

        $usercache->delete($key);
    }

    /**
     * Purge all program progressinfo caches
     */
    public static function purge_progressinfo_caches() {
        self::get_progressinfo_cache()->purge();
        self::get_program_users_cache()->purge();
        self::get_user_programs_cache()->purge();
    }

    /**
     * Add the progressinfo to the progressinfo cache as well as
     * the user key to the program cache and the program key to the
     * user cache
     * Making this method public to allow it to be used in tests
     *
     * @param int $programid Program id
     * @param int $userid User id
     * @param \totara_core\progressinfo\progressinfo $progressinfo
     */
    public static function add_progressinfo_to_cache($programid, $userid, $progressinfo) {
        $cache = self::get_progressinfo_cache();
        $key = self::get_progressinfo_cache_key($programid, $userid);
        $cache->set($key, $progressinfo);

        // Add the key to the program_users cache
        $progcache = self::get_program_users_cache();
        $progkey = self::get_program_users_cache_key($programid);
        $data = $progcache->get($progkey);
        if (!is_array($data)) {
            $data = array();
        }

        if (!in_array($key, $data)) {
            $data[] = $key;
            $progcache->set($progkey, $data);
        }

        // Add the key to the user_programs cache
        $usercache = self::get_user_programs_cache();
        $userkey = self::get_user_programs_cache_key($userid);
        $data = $usercache->get($userkey);
        if (!is_array($data)) {
            $data = array();
        }

        if (!in_array($key, $data)) {
            $data[] = $key;
            $usercache->set($userkey, $data);
        }
    }

    /**
     * Get the progressinfo from the progressinfo cache if it exists
     *
     * @param int $programid Program id
     * @param int $userid User id
     * @return \totara_core\progressinfo\progressinfo | false
     */
    public static function get_progressinfo_from_cache($programid, $userid) {
        $cache = self::get_progressinfo_cache();
        $key = self::get_progressinfo_cache_key($programid, $userid);

        $data = $cache->get($key);
        if ($data instanceof \totara_core\progressinfo\progressinfo) {
            return $data;
        }

        return false;
    }
}
