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
 * @package quiz_responses
 */

namespace quiz_responses\userdata;

/**
 * Manages user preferences belonging to the responses quiz report
 */
class preferences extends \core_user\userdata\plugin_preferences {

    /**
     * Returns an array of user preferences as strings.
     *
     * @param int $userid The user we are getting preferences for.
     * @return string[]
     */
    protected static function get_user_preferences(int $userid): array {
        return [
            'quiz_report_responses_qtext',
            'quiz_report_responses_resp',
            'quiz_report_responses_right',
            'quiz_report_responses_which_tries',
        ];
    }

    /**
     * Get main Frankenstyle component name (core subsystem or plugin).
     * This is used for UI purposes to group items into components.
     *
     * NOTE: this can be overridden to move item to a different form group in UI,
     *       for example local plugins and items to standard activities
     *       or blocks may move items to their related plugins.
     */
    public static function get_main_component() {

        // NOTE: override to move item to different component in user interfaces.

        return 'mod_quiz';
    }

    /**
     * String used for human readable name of user preferences. Defaults to preferences.
     *
     * @return array parameters of get_string($identifier, $component) to get full item name and optionally help.
     */
    public static function get_fullname_string() {
        return ['userdataitem-preferences', self::get_component()];
    }

}
