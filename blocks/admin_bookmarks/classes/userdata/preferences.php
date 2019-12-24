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
 * @package block_admin_bookmarks
 */

namespace block_admin_bookmarks\userdata;

/**
 * Manages user preferences belonging to the admin bookmarks block.
 */
class preferences extends \core_user\userdata\plugin_preferences {

    /**
     * String used for human readable name of user preferences. Defaults to preferences.
     *
     * @return array parameters of get_string($identifier, $component) to get full item name and optionally help.
     */
    public static function get_fullname_string() {
        return ['pluginname', self::get_component()];
    }

    /**
     * Returns an array of user preference strings.
     *
     * @param int $userid
     * @return string[]
     */
    protected static function get_user_preferences(int $userid): array {
        return [
            'admin_bookmarks',
        ];
    }

}
