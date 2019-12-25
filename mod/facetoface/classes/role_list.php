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
* @author Oleg Demeshev <oleg.demeshev@totaralearning.com>
* @package mod_facetoface
*/

namespace mod_facetoface;

defined('MOODLE_INTERNAL') || die();

/**
 * Class role_list represents all roles in one event
 */
final class role_list implements \Iterator {

    use traits\seminar_iterator;

    /**
     * role_list constructor.
     *
     * @param array $conditions optional array $fieldname => requestedvalue with AND in between
     * @param string $sort an order to sort the results in.
     */
    public function __construct(array $conditions = null, string $sort = 'roleid') {
        global $DB;

        $sessionroles = $DB->get_records('facetoface_session_roles', $conditions, $sort, '*');
        foreach ($sessionroles as $sessionrole) {
            $role = new role();
            $this->add($role->from_record($sessionrole));
        }
    }

    /**
     * Add session_role to item list
     * @param session_role $item
     */
    public function add(role $item) {
        $this->items[$item->get_id()] = $item;
    }
}
