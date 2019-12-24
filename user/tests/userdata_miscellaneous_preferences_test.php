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

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/userdata_plugin_preferences_testcase.php');

/**
 * @group totara_userdata
 */
class core_user_userdata_miscellaneous_preferences_testcase extends \core_user_userdata_plugin_preferences_testcase {

    protected function get_preferences_class(): string {
        return miscellaneous_preferences::class;
    }

    protected function get_preferences(): array {
        return [
            'auth_forcepasswordchange' => [true, false],
            'create_password' => [true, false],
            'login_lockout' => [true, false],
            'login_lockout_secret' => [true, false],
            'login_failed_last' => [true, false],
            'login_failed_count' => [true, false],
            'login_failed_count_since_success' => [true, false],
            'user_home_page_preference' => [true, false],
            'user_home_totara_dashboard_id' => [true, false],
            'definerole_showadvanced' => [true, false],
            'newemailattemptsleft' => [true, false],
            'newemail' => [true, false],
            'newemailkey' => [true, false],
            'calendar_savedflt' => [true, false],
            'docked_block_instance_1' => [true, false],
            'docked_block_instance_15' => [true, false],
            'filepicker_8' => ['abc123', 'abc123'],
            'filepicker_10' => ['abc123', 'abc123'],
            'flextable_two' => [true, false],
            'flextable_one' => [true, false],
            'switchdevicetablet' => ['abc123', 'abc123'],
            'switchdevicedesktop' => ['abc123', 'abc123'],
            'userselector_preserveselected' => [true, false],
            'userselector_autoselectunique' => [true, false],
            'userselector_searchanywhere' => [true, false],
        ];
    }

    public function test_is_exportable() {
        $class = $this->get_preferences_class();
        $result = forward_static_call([$class, 'is_exportable']);
        self::assertFalse($result);
    }

}
