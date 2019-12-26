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
 * Class seminar_session_list represents Seminar event sessions date list
 */
final class seminar_session_list implements \Iterator {

    use traits\seminar_iterator;

    /**
     * Add seminar session to item list
     * @param seminar_session $item
     */
    public function add(seminar_session $item) {
        $this->items[$item->get_id()] = $item;
    }

    /**
     * Create list of seminar sessions from seminar event
     * @param seminar_event $seminarevent
     * @return seminar_session_list
     */
    public static function from_seminar_event(seminar_event $seminarevent) : seminar_session_list {
        global $DB;
        $list = new seminar_session_list();
        $sessionrecords = $DB->get_records('facetoface_sessions_dates', ['sessionid' => $seminarevent->get_id()], 'timestart');
        foreach ($sessionrecords as $sessionrecords) {
            $session = new seminar_session();
            $list->add($session->from_record($sessionrecords));
        }
        return $list;
    }
}
