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

final class site_admin_update extends base {
    /**
     * @return void
     * @inheritdoc
     */
    protected function init() {
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->data['crud'] = 'u';
    }

    /**
     * Create an event that add/assign a new user into admin group.
     *
     * @param int $targetuser       Target user's id
     * @return site_admin_update
     */
    public static function add(int $targetuser): site_admin_update {
        return self::create_event($targetuser, 'add');
    }

    /**
     * Create an event that remove/un-assign a user from admin group
     *
     * @param int $targetuser       Target user's id
     * @return site_admin_update
     */
    public static function remove(int $targetuser): site_admin_update {
        return self::create_event($targetuser, 'remove');
    }

    /**
     * The target user that is being added/removed into the site admin. Returning an event of
     * updating/assigning/unassign the user into site admin group.
     *
     * @param int $targetuser       Target user's id
     * @return site_admin_update
     */
    private static function create_event(int $targetuser, string $updatetype): site_admin_update {
        global $USER;

        /** @var site_admin_update $event */
        $event = self::create(
            [
                'userid' => $USER->id,
                'relateduserid' => $targetuser,
                'contextid' => \context_system::instance()->id,
                'other' => [
                    'updatetype' => $updatetype
                ]
            ]
        );

        return $event;
    }

    /**
     * Returning true if this event is adding new user to admin group.
     *
     * @return bool
     */
    public function is_add(): bool {
        return $this->is_type_of('add');
    }

    /**
     * Returning true if this event is removing user from admin group.
     *
     * @return bool
     */
    public function is_remove(): bool {
        return $this->is_type_of('remove');
    }

    /**
     * Chekc whether the update type is match with each other or not.
     *
     * @param string $updatetype
     * @return bool
     */
    private function is_type_of(string $updatetype): bool {
        return $updatetype === $this->data['other']['updatetype'];
    }

    /**
     * @return string
     * @inheritdoc
     */
    public function get_description() {
        $data = $this->data['other'];
        $updatetype = isset($data['updatetype']) ? $data['updatetype'] : '';

        if ('add' === $updatetype) {
            return
                "The user with id '{$this->userid}' added user with id '{$this->relateduserid}' into admin group";
        } else if ('remove' === $updatetype) {
            return
                "The user with id '{$this->userid}' removed user with id '{$this->relateduserid}' from admin group";
        }

        // None is applied, just an empty string for the case, this seems to be un-neccessary code, because
        // updatetype will always be appearing to either 'add' or 'remove'. Though, who would know what could possibly
        // happen.
        return '';
    }

    /**
     * @return string
     * @inheritdoc
     */
    public static function get_name() {
        return \get_string('eventupdateadmin', 'totara_core');
    }
}