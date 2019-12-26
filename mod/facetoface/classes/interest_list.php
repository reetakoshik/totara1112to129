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
 * Class interest_list represents all interest in one activity
 */
final class interest_list implements \Iterator {

    use traits\seminar_iterator;

    /**
     * interest_list constructor.
     *
     * @param array $conditions optional array $fieldname => requestedvalue with AND in between
     * @param string $sort an order to sort the results in.
     */
    public function __construct(array $conditions = null, string $sort = '') {
        global $DB;

        $seminarinterests = $DB->get_records('facetoface_interest', $conditions, $sort, '*');
        foreach ($seminarinterests as $seminarinterest) {
            $item = new interest();
            $this->add($item->map_instance($seminarinterest));
        }
    }

    /**
     * Add seminar interest to item list
     * @param interest $item
     */
    public function add(interest $item) {
        $this->items[$item->get_id()] = $item;
    }
}