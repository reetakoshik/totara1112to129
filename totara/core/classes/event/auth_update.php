<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package totara_core
 */

namespace totara_core\event;
use core\event\base;

defined('MOODLE_INTERNAL') || die();

class auth_update extends base {
    /**
     * @return void
     */
    protected function init() {
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_OTHER;
    }

    /**
     * $auth type that is being enabled.
     *
     * @param string $auth
     *
     * @return auth_update
     */
    public static function enabled(string $auth): auth_update {
        return static::create_event($auth, 'enabled');
    }

    /**
     * $auth type that is being disabled.
     *
     * @param string $auth
     *
     * @return auth_update
     */
    public static function disabled(string $auth): auth_update {
        return static::create_event($auth, 'disabled');
    }


    /**
     * @param string $auth
     * @param string $action
     *
     * @return auth_update
     */
    private static function create_event(string $auth, string $action): auth_update {
        global $USER;

        $data = [
            'userid' => $USER->id,
            'contextid' => \context_system::instance()->id,
            'other' => [
                'action' => $action,
                'authtype' => $auth
            ]
        ];

        /** @var auth_update $event */
        $event = static::create($data);
        return $event;
    }

    /**
     * @return string
     */
    public function get_description() {
        $otherdata = $this->data['other'];
        $action = isset($otherdata['action']) ? $otherdata['action'] : '';
        $authtype = isset($otherdata['authtype']) ? $otherdata['authtype'] : '';

        if ('' === $action) {
            // This should never be a case, but who knows ?
            return '';
        }

        return "The user with id '{$this->userid}' {$action} the authentication method: '{$authtype}'";
    }

    /**
     * @return string
     */
    public static function get_name() {
        return get_string('eventupdateauth', 'totara_core');
    }

    /**
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url("/admin/settings.php", ['section' => 'manageauths']);
    }
}