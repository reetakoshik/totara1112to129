<?php
/*
* This file is part of Totara LMS
*
* Copyright (C) 2016 onwards Totara Learning Solutions LTD
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
* @package mod_facetoface
*/

defined('MOODLE_INTERNAL') || die();

/**
 * Get the relevant session rooms for a seminar activity
 *
 * @param int $facetofaceid
 * @param string $fields fields with prefix fr, name and id are required
 * @return stdClass[] containing facetoface_room table db objects
 * @deprecated since Totara 12
 */
function facetoface_get_used_rooms($facetofaceid, $fields = 'fr.id, fr.name') {
    global $DB;

    debugging('This function has been deprecated, please use "\mod_facetoface\room_list::get_seminar_room()" instead', DEBUG_DEVELOPER);

    $params = array('facetofaceid' => $facetofaceid);

    if ($fields !== 'fr.*') {
        if (strpos($fields, 'fr.id') === false or strpos($fields, 'fr.name') === false) {
            throw new coding_exception('r.id and r.name are required columns');
        }
    }

    $sql = "SELECT DISTINCT {$fields}
              FROM {facetoface_sessions} fs
              JOIN {facetoface_sessions_dates} fsd ON (fsd.sessionid = fs.id)
              JOIN {facetoface_room} fr ON (fsd.roomid = fr.id)
             WHERE fs.facetoface = :facetofaceid
          ORDER BY fr.name ASC, fr.id ASC";

    return $DB->get_records_sql($sql, $params);
}

/**
 * Get the session room for a seminar activity
 *
 * @param int $roomid
 * @return stdClass|false room with loaded custom fields or false if not found
 * @deprecated since Totara 12
 */
function facetoface_get_room($roomid) {
    global $DB, $CFG;
    require_once($CFG->dirroot . '/totara/customfield/fieldlib.php');

    debugging('This function has been deprecated, please use "new \mod_facetoface\room($id)" instead', DEBUG_DEVELOPER);
    if (!$roomid) {
        return false;
    }

    $room = $DB->get_record('facetoface_room', array('id' => $roomid));
    if (empty($room)) {
        return false;
    }

    customfield_load_data($room, 'facetofaceroom', 'facetoface_room');
    return $room;
}

/**
 * Get the room record for the specified session
 *
 * @param int $sessionid
 * @return stdClass[] the room record or empty array if no room found
 * @deprecated since Totara 12
 */
function facetoface_get_session_rooms($sessionid) {
    global $DB, $CFG;
    require_once($CFG->dirroot . '/totara/customfield/fieldlib.php');

    debugging('Function facetoface_get_session_rooms() has been deprecated, please use "\mod_facetoface\room_list::get_event_rooms()" instead', DEBUG_DEVELOPER);

    $sql = "SELECT DISTINCT fr.*
              FROM {facetoface_room} fr
              JOIN {facetoface_sessions_dates} fsd ON (fsd.roomid = fr.id)
              JOIN {facetoface_sessions} fs ON (fs.id = fsd.sessionid)
             WHERE fs.id = ?
          ORDER BY fr.name ASC, fr.id ASC";
    $rooms = $DB->get_records_sql($sql, array($sessionid));

    foreach ($rooms as &$room) {
        customfield_load_data($room, "facetofaceroom", "facetoface_room");
    }

    return $rooms;
}

/**
 * Process room edit form and call related handlers
 *
 * @param stdClass|false $room
 * @param stdClass|false $facetoface non-false means we are editing session via ajax
 * @param stdClass|false $session non-false means we are editing existing session via ajax
 * @param callable $successhandler function($id) where $id is roomid
 * @param callable $cancelhandler
 * @return mod_facetoface_room_form
 * @deprecated since Totara 12
 */
function facetoface_process_room_form($room, $facetoface, $session, callable $successhandler, callable $cancelhandler = null) {
    global $DB, $TEXTAREA_OPTIONS, $USER, $CFG;
    require_once($CFG->dirroot . '/totara/customfield/fieldlib.php');
    require_once($CFG->dirroot . '/mod/facetoface/form/room/room_form.php');

    debugging('This function has been deprecated, this is now handled by the form', DEBUG_DEVELOPER);

    $editoroptions = $TEXTAREA_OPTIONS;
    if ($facetoface) {
        // Do not use autosave in editor when nesting forms.
        $editoroptions['autosave'] = false;
    }

    if (!$room) {
        $room = new stdClass();
        $room->id = 0;
        $room->description = '';
        $room->descriptionformat = FORMAT_HTML;
        $room->capacity = '';
        $room->allowconflicts = 0;
        if ($facetoface) {
            $room->custom = 1;
        } else {
            $room->custom = 0;
        }
    } else {
        $room->descriptionformat = FORMAT_HTML;
        customfield_load_data($room, 'facetofaceroom', 'facetoface_room');
        $room = file_prepare_standard_editor($room, 'description', $editoroptions, $editoroptions['context'], 'mod_facetoface', 'room', $room->id);
    }
    $room->roomcapacity = $room->capacity;
    unset($room->capacity);

    $customdata = array();
    $customdata['room'] = $room;
    $customdata['facetoface'] = $facetoface;
    $customdata['session'] = $session;
    $customdata['editoroptions'] = $editoroptions;

    $form = new mod_facetoface_room_form(null, $customdata, 'post', '', array('class' => 'dialog-nobind'), true, null, 'mform_modal');

    if ($form->is_cancelled()) {
        if (is_callable($cancelhandler)) {
            $cancelhandler();
        }
    }

    if ($data = $form->get_data()) {
        $todb = new stdClass();
        $todb->name = $data->name;
        $todb->capacity = $data->roomcapacity;
        $todb->allowconflicts = $data->allowconflicts;
        if ($facetoface) {
            if (!empty($data->notcustom)) {
                $todb->custom = 0;
            } else {
                $todb->custom = 1;
            }
        } else {
            $todb->custom = $room->custom;
        }
        // NOTE: usually the time created and updated are set to the same value when adding new items,
        //       do the same here and later compare timestamps to find out if it was not updated yet.
        if (empty($data->id)) {
            $todb->timemodified = $todb->timecreated = time();
            $todb->usercreated = $USER->id;
            $todb->usermodified = $USER->id;
            $data->id = $DB->insert_record('facetoface_room', $todb);
            $todb->id = $data->id;
        } else {
            $todb->timemodified = time();
            $todb->usermodified = $USER->id;
            $todb->id = $data->id;
            $DB->update_record('facetoface_room', $todb);
        }

        /**
         * Need to combine the location data here since the preprocess isn't called enough before the save and fails.
         * But first check to see if the location custom field is present.
         * $_customlocationfieldname added in @see customfield_location::edit_field_add()
         */
        if (property_exists($form->_form, '_customlocationfieldname')) {
            customfield_define_location::prepare_form_location_data_for_db($data, $form->_form->_customlocationfieldname);
        }

        customfield_save_data($data, 'facetofaceroom', 'facetoface_room');

        // Update description.
        $descriptiondata = file_postupdate_standard_editor(
            $data,
            'description',
            $editoroptions,
            $editoroptions['context'],
            'mod_facetoface',
            'room',
            $data->id
        );

        $DB->set_field('facetoface_room', 'description', $descriptiondata->description, array('id' => $data->id));

        $room = $DB->get_record('facetoface_room', array('id' => $data->id), '*', MUST_EXIST);

        $successhandler($room);
    }
    return $form;
}

/**
 * Delete room and all related information.
 *
 * If any session is still using this room, the room is unassigned.
 *
 * @param int $id
 * @deprecated since Totara 12
 */
function facetoface_delete_room($id) {
    global $DB, $CFG;
    require_once("$CFG->dirroot/totara/customfield/fieldlib.php");

    debugging('This function has been deprecated, please use "room::delete()" instead', DEBUG_DEVELOPER);
    $room = $DB->get_record('facetoface_room', array('id' => $id));
    if (!$room) {
        // Nothing to delete.
        return;
    }

    // Delete all custom fields related to room.
    $roomfields = $DB->get_records('facetoface_room_info_field');
    foreach($roomfields as $roomfield) {
        /** @var customfield_base $customfieldentry */
        $customfieldentry = customfield_get_field_instance($room, $roomfield->id, 'facetoface_room', 'facetofaceroom');
        if (!empty($customfieldentry)) {
            $customfieldentry->delete();
        }
    }

    // Delete all files embedded in the room description.
    $fs = get_file_storage();
    $syscontext = context_system::instance();
    $fs->delete_area_files($syscontext->id, 'mod_facetoface', 'room', $room->id);

    // Unlink this room from any session.
    $DB->set_field('facetoface_sessions_dates', 'roomid', 0, array('roomid' => $room->id));

    // Finally delete the room record itself.
    $DB->delete_records('facetoface_room', array('id' => $id));
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
 * @return stdClass[] rooms
 * @deprecated since Totara 12
 */
function facetoface_get_available_rooms($timestart, $timefinish, $fields='fr.*', $sessionid, $facetofaceid) {
    global $DB, $USER;

     debugging('facetoface_get_available_rooms has been deprecated. Use \mod_facetoface\room_list::get_available() instead.', DEBUG_DEVELOPER);

    $params = array();
    $params['timestart'] = (int)$timestart;
    $params['timefinish'] = (int)$timefinish;
    $params['sessionid'] = (int)$sessionid;
    $params['facetofaceid'] = (int)$facetofaceid;
    $params['userid'] = $USER->id;

    if ($fields !== 'fr.*' and strpos($fields, 'fr.id') !== 0) {
        throw new coding_exception('Invalid $fields parameter specified, must be fr.* or must start with fr.id');
    }

    $bookedrooms = array();
    if ($timestart and $timefinish) {
        if ($timestart > $timefinish) {
            debugging('Invalid slot specified, start cannot be later than finish', DEBUG_DEVELOPER);
        }
        $sql = "SELECT DISTINCT fr.id
                  FROM {facetoface_room} fr
                  JOIN {facetoface_sessions_dates} fsd ON fr.id = fsd.roomid
                 WHERE fr.allowconflicts = 0 AND fsd.sessionid <> :sessionid
                       AND (fsd.timestart < :timefinish AND fsd.timefinish > :timestart)";
        $bookedrooms = $DB->get_records_sql($sql, $params);
    }

    // First get all site rooms that either allow conflicts
    // or are not occupied at the given times
    // or are already used from the current event.
    // Note that hidden rooms may be reused in the same session if already there,
    // but are completely hidden everywhere else.
    if ($sessionid) {
        $sql = "SELECT DISTINCT {$fields}
                  FROM {facetoface_room} fr
             LEFT JOIN {facetoface_sessions_dates} fsd ON fr.id = fsd.roomid
                 WHERE fr.custom = 0 AND (fr.hidden = 0 OR fsd.sessionid = :sessionid)";
        if (strpos($fields, 'fr.*') !== false or strpos($fields, 'fr.name') !== false) {
            $sql .= " ORDER BY fr.name ASC, fr.id ASC";
        }
    } else {
        $sql = "SELECT {$fields}
                  FROM {facetoface_room} fr
                 WHERE fr.custom = 0 AND fr.hidden = 0
              ORDER BY fr.name ASC, fr.id ASC";
    }
    $rooms = $DB->get_records_sql($sql, $params);
    foreach ($bookedrooms as $rid => $unused) {
        unset($rooms[$rid]);
    }

    // Custom rooms in the current facetoface activity.
    if ($facetofaceid) {
        $sql = "SELECT DISTINCT {$fields}
                  FROM {facetoface_room} fr
                  JOIN {facetoface_sessions_dates} fsd ON fr.id = fsd.roomid
                  JOIN {facetoface_sessions} fs ON fs.id = fsd.sessionid
                 WHERE fr.custom = 1 AND fs.facetoface = :facetofaceid";
        if (strpos($fields, 'fr.*') !== false or strpos($fields, 'fr.name') !== false) {
            $sql .= " ORDER BY fr.name ASC, fr.id ASC";
        }
        $customrooms = $DB->get_records_sql($sql, $params);
        foreach ($customrooms as $room) {
            if (!isset($bookedrooms[$room->id])) {
                $rooms[$room->id] = $room;
            }
        }
        unset($customrooms);
    }

    // Add custom rooms of the current user that are not assigned yet or any more.
    $sql = "SELECT {$fields}
              FROM {facetoface_room} fr
         LEFT JOIN {facetoface_sessions_dates} fsd ON fr.id = fsd.roomid
             WHERE fsd.id IS NULL AND fr.custom = 1 AND fr.usercreated = :userid
          ORDER BY fr.name ASC, fr.id ASC";
    $userrooms = $DB->get_records_sql($sql, $params);
    foreach ($userrooms as $room) {
        $rooms[$room->id] = $room;
    }

    return $rooms;
}

/**
 * Check if room is available during certain time slot.
 *
 * Available rooms are rooms where the start- OR end times don't fall within that of another session's room,
 * as well as rooms where the start- AND end times don't encapsulate that of another session's room
 *
 * @param int $timestart
 * @param int $timefinish
 * @param stdClass $room
 * @param int $sessionid current session id, 0 if adding new session
 * @param int $facetofaceid current facetoface id
 * @return boolean
 * @deprecated since Totara 12
 */
function facetoface_is_room_available($timestart, $timefinish, stdClass $room, $sessionid, $facetofaceid) {
    global $DB, $USER;

    debugging('facetoface_is_room_available has been deprecated. Use \mod_facetoface\room::is_available() instead.', DEBUG_DEVELOPER);

    if ($room->hidden) {
        // Hidden rooms can be assigned only if they are already used in the session.
        if (!$sessionid) {
            return false;
        }
        if (!$DB->record_exists('facetoface_sessions_dates', array('roomid' => $room->id, 'sessionid' => $sessionid))) {
            return false;
        }
    }

    if ($room->custom) {
        // Custom rooms can be used only if already used in seminar
        // or not used anywhere and created by current user.
        $sql = "SELECT 'x'
                  FROM {facetoface_sessions_dates} fsd
                  JOIN {facetoface_sessions} fs ON (fs.id = fsd.sessionid)
                 WHERE fsd.roomid = :roomid AND fs.facetoface = :facetofaceid";

        if (!$DB->record_exists_sql($sql, array('roomid' => $room->id, 'facetofaceid' => $facetofaceid))) {
            if ($room->usercreated == $USER->id) {
                if ($DB->record_exists('facetoface_sessions_dates', array('roomid' => $room->id))) {
                    return false;
                }
            } else {
                return false;
            }
        }
    }

    if (!$timestart and !$timefinish) {
        // Time not specified, no need to verify conflicts.
        return true;
    }

    if ($room->allowconflicts) {
        // No need to worry about time slots.
        return true;
    }

    if ($timestart > $timefinish) {
        debugging('Invalid slot specified, start cannot be later than finish', DEBUG_DEVELOPER);
    }

    // Is there any other event using this room in this slot?
    // Note that there cannot be collisions in session dates of one event because they cannot overlap.
    $params = array('timestart' => $timestart, 'timefinish' => $timefinish, 'roomid' => $room->id, 'sessionid' => $sessionid);

    $sql = "SELECT 'x'
              FROM {facetoface_sessions_dates} fsd
              JOIN {facetoface_sessions} fs ON (fs.id = fsd.sessionid)
             WHERE fsd.roomid = :roomid AND fs.id <> :sessionid
                   AND :timefinish > fsd.timestart AND :timestart < fsd.timefinish";
    return !$DB->record_exists_sql($sql, $params);
}

/**
 * Find out if room has scheduling conflicts.
 *
 * @param int $roomid
 * @return bool
 * @deprecated since Totara 12
 */
function facetoface_room_has_conflicts($roomid) {
    global $DB;

    debugging('facetoface_room_has_conflicts has been deprecated. Use \mod_facetoface\room::has_conflicts() instead.', DEBUG_DEVELOPER);

    $sql = "SELECT 'x'
              FROM {facetoface_sessions_dates} fsd
              JOIN {facetoface_sessions_dates} fsd2 ON (fsd2.roomid = fsd.roomid AND fsd2.id <> fsd.id)
             WHERE fsd.roomid = :roomid AND
                   ((fsd.timestart >= fsd2.timestart AND fsd.timestart < fsd2.timefinish)
                    OR (fsd.timefinish > fsd2.timestart AND fsd.timefinish <= fsd2.timefinish))";
    return $DB->record_exists_sql($sql, array('roomid' => $roomid));
}

/**
 * Returns the address string from the Room's 'location' customfield (if available). Note this function expects that
 * the passed room parameter has been loaded with customfield data already
 *
 * @param stdClass $room
 *
 * @return string
 * @deprecated since Totara 12
 */
function facetoface_room_get_address($room) {
    global $CFG;

    debugging('facetoface_room_get_address has been deprecated. Please use room::get_customfield_array() instead.', DEBUG_DEVELOPER);

    require_once($CFG->dirroot . '/totara/customfield/field/location/define.class.php');

    // We're assuming the relevant location field is named 'location' and the building customfield is
    // named 'building'. Doesn't seem to be a way to make this more dynamic, it's just expected that if a
    // room has these customfields, they're named this way
    $locationdata = customfield_define_location::prepare_db_location_data_for_form(
        $room->{"customfield_location"}
    );

    return (isset($locationdata->address) && !empty($locationdata->address)) ? $locationdata->address : "";
}

/**
 * Render detailed room description to a string
 *
 * @param stdClass $room room details with customfields info
 * @return string
 * @deprecated since Totara 12
 */
function facetoface_room_to_string($room) {

    debugging('facetoface_room_to_string has been deprecated. Please use room::__toString() instead.', DEBUG_DEVELOPER);

    $stringitems = [];
    $stringitems[] = isset($room->name) ? $room->name : null;
    $stringitems[] = isset($room->{"customfield_building"}) ? $room->{"customfield_building"} : null;
    $stringitems[] = facetoface_room_get_address($room);

    return implode(", ", array_filter($stringitems));
}

/**
 * Formats HTML for room details..
 *
 * @param object $room        DB record of a facetoface room.
 *
 * @return string containing room details with relevant html tags.
 * @deprecated since Totara 12
 */
function facetoface_room_html($room, $backurl=null) {
    global $OUTPUT;

    debugging('facetoface_room_html has been deprecated. Use the renderer function get_room_details_html() instead.', DEBUG_DEVELOPER);

    $roomhtml = [];

    if (!empty($room)) {
        $roomhtml[] = !empty($room->name) ? html_writer::span(format_string($room->name), 'room room_name') : '';

        $roomhtml[] = !empty($room->customfield_building) ?
            html_writer::span(format_string($room->customfield_building), 'room room_building') :
            '';

        $url = new moodle_url('/mod/facetoface/reports/rooms.php', array(
            'roomid' => $room->id,
            'b' => $backurl
        ));

        $popupurl = clone($url);
        $popupurl->param('popup', 1);
        $action = new popup_action('click', $popupurl, 'popup', array('width' => 800,'height' => 600));
        $link = $OUTPUT->action_link($url, get_string('roomdetails', 'facetoface'), $action);
        $roomhtml[] = html_writer::span('(' . $link . ')', 'room room_details');
    }

    $roomhtml = implode('', $roomhtml);

    return $roomhtml;
}
