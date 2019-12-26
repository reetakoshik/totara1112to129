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
 * Class signup_status_list represents status in Seminar signups
 */
final class signup_status_list implements \Iterator {

    use traits\seminar_iterator;

    /**
     * signup_status_list constructor.
     *
     * @param array $conditions optional array $fieldname => requestedvalue with AND in between
     * @param string $sort an order to sort the results in.
     */
    public function __construct(array $conditions = null, string $sort = 'timecreated') {
        global $DB;

        $signupstatuses = $DB->get_records('facetoface_signups_status', $conditions, $sort, '*');
        foreach ($signupstatuses as $signupstatus) {
            $status = new signup_status();
            $this->add($status->from_record($signupstatus));
        }
    }

    /**
     * Add signup_status to item list.
     *
     * @param signup_status $item
     */
    public function add(signup_status $item) {
        $this->items[$item->get_id()] = $item;
    }
}