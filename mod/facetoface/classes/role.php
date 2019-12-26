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
* Class role represents Seminar event roles
*/
final class role {

    use traits\crud_mapper;

    /**
     * @var int {facetoface_session_roles}.id
     */
    private $id = 0;
    /**
     * @var int {facetoface_session_roles}.sessionid
     */
    private $sessionid = 0;
    /**
     * @var int {facetoface_session_roles}.roleid
     */
    private $roleid = 0;
    /**
     * @var int {facetoface_session_roles}.userid
     */
    private $userid = 0;
    /**
     * @var string facetoface_session_roles table name
     */
    const DBTABLE = 'facetoface_session_roles';

    /**
     * Sesseion Roles constructor.
     * @param int $id {facetoface_session_roles}.id If 0 - new session_roles will be created
     */
    public function __construct(int $id = 0) {

        $this->id = $id;
        $this->load();
    }

    /**
     * Load session roles data from DB.
     * 
     * @return session_role this
     */
    public function load() : role {

        return $this->crud_load();
    }

    /**
     * Create/update {facetoface_session_roles}.record
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
     * Delete {facetoface_session_roles}.record where id from database
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
    public function set_sessionid(int $sessionid) : role {
        $this->sessionid = $sessionid;
        return $this;
    }

    /**
     * @return int
     */
    public function get_roleid() : int {
        return (int)$this->roleid;
    }
    /**
     * @param int $roleid
     */
    public function set_roleid(int $roleid) : role {
        $this->roleid = $roleid;
        return $this;
    }

    /**
     * @return int
     */
    public function get_userid() : int {
        return (int)$this->userid;
    }
    /**
     * @param int $userid
     */
    public function set_userid(int $userid) : role {
        $this->userid = $userid;
        return $this;
    }
}