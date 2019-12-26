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
 * @author  David Curry <david.curry@totaralearning.com>
 * @package mod_facetoface
 */

namespace mod_facetoface;

defined('MOODLE_INTERNAL') || die();

/**
 * Class room_list represents all rooms
 */
final class room_list implements \Iterator {

    use traits\seminar_iterator;

    /**
     * room_list constructor.
     *
     * @param string $sql       a sql query that will return the desired rooms.
     * @param array  $params    Either the variables to go with the sql, or the parameters for the get_records call
     * @param string $sort      an order to sort the results in.
     */
    public function __construct(string $sql = '', array $params = [], string $sort = '') {
        global $DB;

        if (empty($sql)) {
            // Get all of the rooms, restricted by any params handed through.
            // Note: in this case the params MUST match a record in the facetoface_room table
            $roomsdata = $DB->get_records('facetoface_room', $params, $sort, '*');
        } else {
            if (!empty($sort)) {
                $sql .= " ORDER BY {$sort}";
            }

            $roomsdata = $DB->get_records_sql($sql, $params);
        }

        foreach ($roomsdata as $roomdata) {
            $room = new room();
            $this->add($room->from_record($roomdata));
        }
    }

    /**
     * Add room to list
     * @param room $item
     */
    public function add(room $item) {
        $this->items[$item->get_id()] = $item;
    }

    /**
     * Get the relevant session rooms for a seminar activity
     *
     * @param int $seminarid
     * @return \room_list $this
     */
    public static function get_seminar_rooms(int $seminarid) : room_list {
        $sql = "SELECT DISTINCT fr.*
                  FROM {facetoface_sessions} fs
                  JOIN {facetoface_sessions_dates} fsd
                    ON (fsd.sessionid = fs.id)
                  JOIN {facetoface_room} fr
                    ON (fsd.roomid = fr.id)
                 WHERE fs.facetoface = :facetofaceid
              ORDER BY fr.name ASC, fr.id ASC";

        return new room_list($sql, ['facetofaceid' => $seminarid]);
    }

    /**
     * Get the room record for the specified session
     *
     * @param int $eventid
     * @return \room_list
     */
    public static function get_event_rooms(int $eventid) : room_list {
        $sql = "SELECT DISTINCT fr.*
                  FROM {facetoface_room} fr
                  JOIN {facetoface_sessions_dates} fsd ON (fsd.roomid = fr.id)
                  JOIN {facetoface_sessions} fs ON (fs.id = fsd.sessionid)
                 WHERE fs.id = :eventid
              ORDER BY fr.name ASC, fr.id ASC";

        return new room_list($sql, ['eventid' => $eventid]);
    }

    /**
     * Get available rooms for the specified time slot, or all rooms if $timestart and $timefinish are empty.
     *
     * NOTE: performance is not critical here because this function should be used only when assigning rooms to sessions.
     *
     * @param int $timestart start of requested slot
     * @param int $timefinish end of requested slot
     * @param string $fields db fields for which data should be retrieved, with mandatory 'fr.' prefix
     * @param int $sessionid current session id, 0 if session is being created, all current session rooms are always included
     * @param int $facetofaceid facetofaceid custom rooms can be used in all dates of one seminar activity
     * @return \room_list
     */
    public static function get_available_rooms($timestart, $timefinish, seminar_event $event) : room_list {
        global $DB, $USER;

        $list = new room_list('', ['id' => 0]); // Create an empty list
        $eventid = $event->get_id();
        $seminarid = $event->get_facetoface();

        $params = array();
        $params['timestart'] = (int)$timestart;
        $params['timefinish'] = (int)$timefinish;
        $params['sessionid'] = $eventid;
        $params['facetofaceid'] = $seminarid;
        $params['userid'] = $USER->id;

        // First get all site rooms that either allow conflicts
        // or are not occupied at the given times
        // or are already used from the current event.
        // Note that hidden rooms may be reused in the same session if already there,
        // but are completely hidden everywhere else.
        if (!empty($eventid)) {
            $sql = "SELECT DISTINCT fr.*
                      FROM {facetoface_room} fr
                 LEFT JOIN {facetoface_sessions_dates} fsd ON fr.id = fsd.roomid
                     WHERE fr.custom = 0 AND (fr.hidden = 0 OR fsd.sessionid = :sessionid)
                  ORDER BY fr.name ASC, fr.id ASC";
        } else {
            $sql = "SELECT fr.*
                      FROM {facetoface_room} fr
                     WHERE fr.custom = 0 AND fr.hidden = 0
                  ORDER BY fr.name ASC, fr.id ASC";
        }
        $rooms = $DB->get_records_sql($sql, $params);

        // Now exclude any rooms that don't allow over booking that are booked during the times given.
        $bookedrooms = array();
        if ($timestart and $timefinish) {
            if ($timestart > $timefinish) {
                debugging('Invalid slot specified, start cannot be later than finish', DEBUG_DEVELOPER);
            }
            $sql = "SELECT DISTINCT fr.*
                      FROM {facetoface_room} fr
                      JOIN {facetoface_sessions_dates} fsd ON fr.id = fsd.roomid
                     WHERE fr.allowconflicts = 0 AND fsd.sessionid <> :sessionid
                           AND (fsd.timestart < :timefinish AND fsd.timefinish > :timestart)";
            $bookedrooms = $DB->get_records_sql($sql, $params);

            foreach ($bookedrooms as $rid => $unused) {
                unset($rooms[$rid]);
            }
        }

        // Then include any custom rooms that are in the current facetoface activity.
        if (!empty($seminarid)) {
            $sql = "SELECT DISTINCT fr.*
                      FROM {facetoface_room} fr
                      JOIN {facetoface_sessions_dates} fsd ON fr.id = fsd.roomid
                      JOIN {facetoface_sessions} fs ON fs.id = fsd.sessionid
                     WHERE fr.custom = 1 AND fs.facetoface = :facetofaceid
                  ORDER BY fr.name ASC, fr.id ASC";
            $customrooms = $DB->get_records_sql($sql, $params);

            foreach ($customrooms as $room) {
                if (!isset($bookedrooms[$room->id])) {
                    $rooms[$room->id] = $room;
                }
            }
            unset($customrooms);
        }

        // Add custom rooms of the current user that are not assigned yet or any more.
        $sql = "SELECT fr.*
                  FROM {facetoface_room} fr
             LEFT JOIN {facetoface_sessions_dates} fsd ON fr.id = fsd.roomid
                 WHERE fsd.id IS NULL AND fr.custom = 1 AND fr.usercreated = :userid
              ORDER BY fr.name ASC, fr.id ASC";
        $userrooms = $DB->get_records_sql($sql, $params);
        foreach ($userrooms as $room) {
            $rooms[$room->id] = $room;
        }

        // Construct all the rooms and add them to the iterator list.
        foreach ($rooms as $roomdata) {
            $room = new room();
            $room->from_record($roomdata);
            $list->add($room);
        }

        return $list;
    }
}
