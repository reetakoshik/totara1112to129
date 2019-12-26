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
 * Class seminar_session represents Seminar event session dates
 */
final class seminar_session {

    use traits\crud_mapper;

    /**
     * @var int {facetoface_sessions_dates}.id
     */
    private $id = 0;
    /**
     * @var int {facetoface_sessions_dates}.sessionid
     */
    private $sessionid = 0;
    /**
     * @var string {facetoface_sessions_dates}.sessiontimezone
     */
    private $sessiontimezone = "99";
    /**
     * @var int {facetoface_sessions_dates}.roomid
     */
    private $roomid = 0;
    /**
     * @var int {facetoface_sessions_dates}.timestart
     */
    private $timestart = 0;
    /**
     * @var int {facetoface_sessions_dates}.timefinish
     */
    private $timefinish = 0;
    /**
     * @var string facetoface_sessions_dates table name
     */
    const DBTABLE = 'facetoface_sessions_dates';

    /**
     * Session constructor.
     * @param int $id {facetoface_sessions_dates}.id If 0 - new Session will be created
     */
    public function __construct(int $id = 0) {

        $this->id = $id;
        $this->load();
    }

    /**
     * Load seminar event dates data from DB
     *
     * @return seminar session this
     */
    public function load() : seminar_session {

        return $this->crud_load();
    }

    /**
     * Create/update {facetoface_sessions_dates}.record
     */
    public function save() {

        $this->crud_save();
    }

    /**
     * Map data object to class instance.
     *
     * @param \stdClass $object
     */
    public function from_record(\stdClass $object) {

        return $this->map_object($object);
    }

    /**
     * Remove event dates from database
     */
    public function delete() {
        global $DB;

        $DB->delete_records(self::DBTABLE, ['id' => $this->id]);

        // Re-load instance with default values.
        $this->map_object((object)get_object_vars(new self()));
    }

    /**
     * @return int
     */
    public function get_id() : int {
        return (int)$this->id;
    }

    /**
     * @return int
     */
    public function get_sessionid() : int {
        return (int)$this->sessionid;
    }
    /**
     * @param int $sessionid
     */
    public function set_sessionid(int $sessionid) : seminar_session {
        $this->sessionid = $sessionid;
        return $this;
    }

    /**
     * @return int
     */
    public function get_sessiontimezone() : string {
        return (string)$this->sessiontimezone;
    }
    /**
     * @param int $sessiontimezone
     */
    public function set_sessiontimezone(string $sessiontimezone) : seminar_session {
        $this->sessiontimezone = $sessiontimezone;
        return $this;
    }

    /**
     * @return int
     */
    public function get_roomid() : int {
        return (int)$this->roomid;
    }
    /**
     * @param int $roomid
     */
    public function set_roomid(int $roomid) : seminar_session {
        $this->roomid = $roomid;
        return $this;
    }

    /**
     * @return int
     */
    public function get_timestart() : int {
        return (int)$this->timestart;
    }
    /**
     * @param int $timestart
     */
    public function set_timestart(int $timestart) : seminar_session {
        $this->timestart = $timestart;
        return $this;
    }

    /**
     * @return int
     */
    public function get_timefinish() : int {
        return (int)$this->timefinish;
    }
    /**
     * @param int $timestart
     */
    public function set_timefinish(int $timefinish) : seminar_session {
        $this->timefinish = $timefinish;
        return $this;
    }
}
