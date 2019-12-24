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
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 * @package core_user
 */

namespace core_user\userdata;

/**
 * Miscellaneous preferences belonging to the user.
 *
 * Please note that only preferences which are used system wide should be here.
 * Otherwise they should be located with their component.
 *
 * These cannot be exported, as there may be sensitive data in there.
 *
 * @package core_user\userdata
 */
class miscellaneous_preferences extends \core_user\userdata\plugin_preferences {

    /**
     * String used for human readable name of user preferences. Defaults to preferences.
     *
     * @return array parameters of get_string($identifier, $component) to get full item name and optionally help.
     */
    public static function get_fullname_string() {
        return ['userdataitem-user-miscellaneous_preferences', 'core'];
    }

    /**
     * These cannot be exported, they may contain sensitive information.
     *
     * @return bool
     */
    public static function is_exportable() {
        // This can't be exported, it is secret stuff.
        return false;
    }

    /**
     * Returns an array of user preferences.
     *
     * @param int $userid
     * @return string[]
     */
    protected static function get_user_preferences(int $userid): array {
        // These get purged, but not exported.
        $preferences = [
            'auth_forcepasswordchange',
            'create_password',
            'login_lockout',
            'login_lockout_secret',
            'login_failed_last',
            'login_failed_count',
            'login_failed_count_since_success',
            'user_home_page_preference',
            'user_home_totara_dashboard_id',
            'definerole_showadvanced',
            'newemailattemptsleft',
            'newemail',
            'newemailkey',
            'calendar_savedflt',
            'userselector_preserveselected',
            'userselector_autoselectunique',
            'userselector_searchanywhere',
        ];

        foreach (get_user_preferences(null, null, $userid) as $preference => $value) {
            if (strpos($preference, 'docked_block_instance_') === 0) {
                $preferences[] = $preference;
                continue;
            }
            if (strpos($preference, 'filepicker_') === 0) {
                $preferences[] = $preference;
                continue;
            }
            if (strpos($preference, 'flextable_') === 0) {
                $preferences[] = $preference;
                continue;
            }
            if (strpos($preference, 'switchdevice') === 0) {
                $preferences[] = $preference;
                continue;
            }
        }

        return $preferences;
    }

}
