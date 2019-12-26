<?php
/*
 * This file is part of Totara LMS
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
 * @author Moises Burgos <moises.burgos@totaralearning.com>
 * @package mod_facetoface
 */

namespace mod_facetoface;

defined('MOODLE_INTERNAL') || die();

/**
 * Class Room represents Seminar Room
 */
final class room {

    use traits\crud_mapper;

    /**
     * @var int {facetoface_room}.id
     */
    private $id = 0;

    /**
     *  @var string {facetoface_room}.name
     */
    private $name = '';

    /**
     *  @var int {facetoface_room}.capacity
     */
    private $capacity = null;

    /**
     *  @var int {facetoface_room}.allowconflicts
     */
    private $allowconflicts = 0;

    /**
     * @var string {facetoface_room}.description
     */
    private $description = '';

    /**
     *  @var int {facetoface_room}.custom
     */
    private $custom = 0;

    /**
     *  @var int {facetoface_room}.hidden
     */
    private $hidden = 0;

    /**
     *  @var int {facetoface_room}.usercreated
     */
    private $usercreated = 0;

    /**
     *  @var int {facetoface_room}.usermodified
     */
    private $usermodified = 0;

    /**
     * @var int {facetoface_room}.timecreated
     */
    private $timecreated = 0;

    /**
     * @var int {facetoface_room}.timemodified
     */
    private $timemodified = 0;

    /**
     * @var string facetoface rooms table name
     */
    const DBTABLE = 'facetoface_room';

    /**
     * Seminar room constructor
     * @param int $id {facetoface_room}.id If 0 - new Seminar Room will be created
     */
    public function __construct(int $id = 0) {
        $this->id = $id;

        if ($id) {
            $this->load();
        }
    }

    /**
     * Get names of customfields that should be displayed along with rooms name
     * @return array
     */
    protected static function get_display_customfields() : array {
        return [CUSTOMFIELD_BUILDING, CUSTOMFIELD_LOCATION];
    }

    /**
     * get a detailed room description as a string
     * @return string
     */
    public function __toString() : string {
        $customfields = $this->get_customfield_array();

        $displayfields = static::get_display_customfields();


        $items = [];
        $items[] = isset($this->name) ? $this->name : null;
        foreach ($displayfields as $field) {
            if (!empty($customfields[$field])) {
                $items[] = $customfields[$field];
            }
        }

        return implode(", ", array_filter($items));
    }

    /**
     * Create a new room with the custom flag set
     *
     * @return room
     */
    public static function create_custom_room() : room {
        $room = new room();
        $room->custom = 1;
        return $room;
    }

    /**
     * Load record from $id, if it is the invalid $id, that does not exist within the database, then we should probably not throw
     * any exceptions, rather than just return an object without default empty data set here.
     *
     * @param int $id
     *
     * @return room
     */
    public static function find(int $id): room {
        $o = new static();
        $o->id = $id;
        return $o->crud_load(IGNORE_MISSING);
    }

    /**
     * Loads a seminar room.
     *
     * @return room
     */
    public function load(): room {
        return $this->crud_load();
    }

    /**
     * Map data object to class instance.
     *
     * @param \stdClass $object
     * @return room this
     */
    public function from_record(\stdClass $object) : room {
        $this->map_object($object);
        return $this;
    }

    /**
     * Map class instance onto data object
     *
     * @return \stdClass
     */
    public function to_record() : \stdClass {
        return $this->unmap_object();
    }

    /**
     * Store room into database
     */
    public function save() {
        global $USER;

        $this->usermodified = $USER->id;
        $this->timemodified = time();

        if (!$this->id) {
            $this->usercreated = $USER->id;
            $this->timecreated = time();
        }

        $this->crud_save();
    }

    /**
     * Deletes a seminar room.
     */
    public function delete() {
        global $DB;

        // Nothing to delete.
        if ($this->id == 0) {
            return;
        }

        $this->delete_customfields();

        $this->delete_embedded_files();

        // Unlink this room from any session.
        $DB->set_field('facetoface_sessions_dates', 'roomid', 0, ['roomid' => $this->id]);

        // Finally delete the room record itself.
        $DB->delete_records('facetoface_room', ['id' => $this->id]);
    }

    /**
     * Deletes all custom fields related to a room.
     */
    private function delete_customfields() {
        global $DB, $CFG;

        // Room doesn't exist.
        if ($this->id == 0) {
            return;
        }

        require_once("$CFG->dirroot/totara/customfield/fieldlib.php");

        $roomdata = $this->to_record();
        $roomfields = $DB->get_records('facetoface_room_info_field');
        foreach ($roomfields as $roomfield) {
            /** @var customfield_base $customfieldentry */
            $customfieldentry = customfield_get_field_instance($roomdata, $roomfield->id, 'facetoface_room', 'facetofaceroom');
            if (!empty($customfieldentry)) {
                $customfieldentry->delete();
            }
        }
    }

    /**
     * Deletes all files embedded in the room description.
     */
    private function delete_embedded_files() {
        // Room doesn't exist.
        if ($this->id == 0) {
            return;
        }

        $fs = get_file_storage();
        $syscontext = \context_system::instance();
        $fs->delete_area_files($syscontext->id, 'mod_facetoface', 'room', $this->id);
    }

    /**
     * Check whether the room exists yet or not.
     * If the room has been saved into the database the $id field should be non-zero
     *
     * @return bool - true if the room has an $id, false if it hasn't
     */
    public function exists() : bool {
        return !empty($this->id);
    }

    /**
     * @return int
     */
    public function get_id(): int {
        return (int)$this->id;
    }

    /**
     * @return string
     */
    public function get_name(): string {
        return (string)$this->name;
    }

    /**
     * @param string $name
     */
    public function set_name(string $name) {
        $this->name = $name;
    }

    /**
     * @return int or null
     */
    public function get_capacity(): ?int {
        if (is_null($this->capacity)) {
            return null;
        }

        return (int)$this->capacity;
    }

    /**
     * @param int $capacity
     */
    public function set_capacity(int $capacity) {
        $this->capacity = $capacity;
    }

    /**
     * @return bool
     */
    public function get_allowconflicts(): bool {
        return (bool)$this->allowconflicts;
    }

    /**
     * @param int $allowconflicts
     */
    public function set_allowconflicts(bool $allowconflicts) {
        $this->allowconflicts = (int)$allowconflicts;
    }

    /**
     * @return string
     */
    public function get_description(): string {
        return (string)$this->description;
    }

    /**
     * @param string $description
     */
    public function set_description(string $description) {
        $this->description = $description;
    }

    /**
     * Get whether this room is hidden
     * Note: There is no setter for this field as it only moves
     *       in one direction, use the publish() function instead
     *
     * @return bool
     */
    public function get_custom(): bool {
        return (bool)$this->custom;
    }

    /**
     * Switch an room from a single use custom room to a site wide reusable room.
     * Note: that this function is instead of the set_custom() function, and it enforces
     *       the behaviour that an room can only become more public not less.
     *
     * @return room $this
     */
    public function publish() : room {
        if ($this->custom == false) {
            print_error('error:cannotrepublishroom', 'facetoface');
        }

        $this->custom = (int)false;

        return $this;
    }

    /**
     * Get whether this room is hidden
     * Note: There is no setter for this field, use
     *       the hide() and show() functions instead
     *
     * @return bool
     */
    public function get_hidden(): bool {
        return (bool)$this->hidden;
    }

    /**
     * Hides this room
     * Note: This is the equivalent of set_hidden(true);
     *
     * @return room $this
     */
    public function hide() : room {
        $this->hidden = (int)true;

        return $this;
    }

    /**
     * Shows this room
     * Note: This is the equivalent of set_hidden(false);
     *
     * @return room $this
     */
    public function show() : room {
        $this->hidden = (int)false;

        return $this;
    }

    /**
     * @return int
     */
    public function get_usercreated() : int {
        return (int)$this->usercreated;
    }

    /**
     * @return int
     */
    public function get_usermodified() : int {
        return (int)$this->usermodified;
    }

    /**
     * @param int $usermodified
     */
    public function set_usermodified(int $usermodified) {
        $this->usermodified = $usermodified;
    }

    /**
     * @return int
     */
    public function get_timecreated(): int {
        return (int)$this->timecreated;
    }

    /**
     * @return int
     */
    public function get_timemodified(): int {
        return (int)$this->timemodified;
    }

    /**
     * @param int $timemodified
     */
    public function set_timemodified(int $timemodified) {
        $this->timemodified = $timemodified;
    }

    /**
     * Check if room is available during certain time slot.
     *
     * Available rooms are rooms where the start OR end times don't fall within that of another session's room,
     * as well as rooms where the start AND end times don't encapsulate that of another session's room
     *
     * @param int $timestart
     * @param int $timefinish
     * @param seminar_event $sessionid
     * @return bool
     */
    public function is_available(int $timestart, int $timefinish, seminar_event $event) : bool {
        global $DB, $USER;

       if ($this->hidden) {
            // Hidden rooms can be assigned only if they are already used in the session.
            if (!$event->exists()) {
                return false;
            }
            if (!$DB->record_exists('facetoface_sessions_dates', ['roomid' => $this->id, 'sessionid' => $event->get_id()])) {
                return false;
            }
        }

        if ($this->custom) {
            // Custom rooms can be used only if already used in seminar
            // or not used anywhere and created by current user.
            $sql = "SELECT 'x'
                      FROM {facetoface_sessions_dates} fsd
                      JOIN {facetoface_sessions} fs ON (fs.id = fsd.sessionid)
                     WHERE fsd.roomid = :roomid AND fs.facetoface = :facetofaceid";

            if (!$DB->record_exists_sql($sql, ['roomid' => $this->id, 'facetofaceid' => $event->get_facetoface()])) {
                if ($this->usercreated == $USER->id) {
                    if ($DB->record_exists('facetoface_sessions_dates', ['roomid' => $this->id])) {
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

        if ($this->allowconflicts) {
            // No need to worry about time slots.
            return true;
        }

        if ($timestart > $timefinish) {
            debugging('Invalid slot specified, start cannot be later than finish', DEBUG_DEVELOPER);
        }

        // Is there any other event using this room in this slot?
        // Note that there cannot be collisions in session dates of one event because they cannot overlap.
        $params = ['timestart' => $timestart, 'timefinish' => $timefinish, 'roomid' => $this->id, 'sessionid' => $event->get_id()];

        $sql = "SELECT 'x'
                  FROM {facetoface_sessions_dates} fsd
                  JOIN {facetoface_sessions} fs ON (fs.id = fsd.sessionid)
                 WHERE fsd.roomid = :roomid AND fs.id <> :sessionid
                       AND :timefinish > fsd.timestart AND :timestart < fsd.timefinish";
        return !$DB->record_exists_sql($sql, $params);
    }

    /**
     * Find out if the room has any scheduling conflicts.
     * @return bool
     */
    public function has_conflicts() : bool {
        global $DB;

        $sql = "SELECT 'x'
                  FROM {facetoface_sessions_dates} fsd
                  JOIN {facetoface_sessions_dates} fsd2
                    ON (fsd2.roomid = fsd.roomid AND fsd2.id <> fsd.id)
                 WHERE fsd.roomid = :roomid
                   AND ((fsd.timestart >= fsd2.timestart AND fsd.timestart < fsd2.timefinish) OR
                        (fsd.timefinish > fsd2.timestart AND fsd.timefinish <= fsd2.timefinish))";

        return $DB->record_exists_sql($sql, ['roomid' => $this->id]);
    }

    /**
     * Switch the class to a stdClass, add all the custom fields, and format the location field.
     *
     * @return array
     */
    public function get_customfield_array() : array {
        global $CFG;
        require_once($CFG->dirroot . '/totara/customfield/fieldlib.php');
        require_once($CFG->dirroot . '/totara/customfield/field/location/define.class.php');

        $cf = $this->to_record();

        $cfdata = customfield_get_data($cf, "facetoface_room", "facetofaceroom", false, ['extended' => false]);

        return (array)$cfdata;
    }
}
