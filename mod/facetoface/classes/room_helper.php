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

final class room_helper {

    /**
     * Room data
     *
     * @param object $data to be saved includes:
     *      @var int {facetoface_room}.id
     *      @var string {facetoface_room}.name
     *      @var int {facetoface_room}.capacity
     *      @var int {facetoface_room}.allowconflicts
     *      @var string {facetoface_room}.description
     *      @var bool {facetoface_room}.custom (optional)
     *      @var int {facetoface_room}.hidden
     */
    public static function save($data) {
        global $TEXTAREA_OPTIONS;

        $custom = $data->custom ?? false; // $data->custom is not always passed
        if ($data->id) {
            $room = new room($data->id);
            if (!$custom && $room->get_custom()) {
                $room->publish();
            } else {
                // NOTE: Do nothing if the room is already published because we can't unpublish it
            }
        } else {
            if ($custom) {
                $room = room::create_custom_room();
            } else {
                $room = new room();
            }
        }
        $room->set_name($data->name);
        $room->set_allowconflicts($data->allowconflicts);
        $room->set_capacity($data->roomcapacity);

        if (!$room->exists()) {
            $room->save();
        }

        // Export data to store in customfields and description.
        $data->id = $room->get_id();
        customfield_save_data($data, 'facetofaceroom', 'facetoface_room');

        // Update description.
        $data = file_postupdate_standard_editor(
            $data,
            'description',
            $TEXTAREA_OPTIONS,
            $TEXTAREA_OPTIONS['context'],
            'mod_facetoface',
            'room',
            $room->get_id()
        );
        $room->set_description($data->description);

        $room->save();
        // Return new/updated asset.
        return $room;
    }
}