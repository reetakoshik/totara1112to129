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
use mod_facetoface\exception\signup_exception;
use mod_facetoface\signup\state\state;

defined('MOODLE_INTERNAL') || die();

/**
 * Class signup_status represents Session signups status
 */
final class signup_status {

    use traits\crud_mapper;

    /**
     * @var int {facetoface_signups_status}.id
     */
    private $id = 0;
    /**
     * @var int {facetoface_signups_status}.signupid
     */
    private $signupid = 0;
    /**
     * @var int {facetoface_signups_status}.statuscode
     */
    private $statuscode = 0;
    /**
     * @var int {facetoface_signups_status}.superceded
     */
    private $superceded = 0;
    /**
     * @var float|null {facetoface_signups_status}.grade
     */
    private $grade = null;
    /**
     * @var int {facetoface_signups_status}.createdby
     */
    private $createdby = 0;
    /**
     * @var int {facetoface_signups_status}.timecreated
     */
    private $timecreated = 0;
    /**
     * @var string facetoface_signups table name
     */
    const DBTABLE = 'facetoface_signups_status';

    /**
     * Seminar signup status constructor.
     *
     * @param int $id {facetoface_signups_status}.id If 0 - new signup_status will be created
     */
    public function __construct(int $id = 0) {

        $this->id = $id;
        $this->load();
    }

    /**
     * Load seminar signup status data from DB
     *
     * @return signup_status this
     */
    public function load() : signup_status {

        return $this->crud_load();
    }

    /**
     * Create/update {facetoface_signups_status}.record
     */
    public function save() : signup_status {
        global $DB;
        if ($this->id || $this->superceded) {
            throw new signup_exception('Cannot update status that was already saved or superceded');
        }
        if (empty($this->statuscode)) {
            throw new signup_exception('Cannot update status without state set');
        }
        if (empty($this->signupid)) {
            throw new signup_exception('Cannot update status without signup set');
        }

        $trans = $DB->start_delegated_transaction();
        $DB->execute('update {facetoface_signups_status} set superceded = 1 where signupid = :sid', ['sid' => $this->signupid]);
        $this->crud_save();
        $trans->allow_commit();

        return $this;
    }

    /**
     * Get current signup state. If no current status, then not_set will be returned
     * @return state
     */
    public function get_state() : state {
        if (signup_status::has_current($this)) {
            $stateclass = signup_status::from_current($this)->get_state_class();
            return new $stateclass($this);
        }

        return new not_set($this);
    }

    /**
     * Return if signup has current status
     * @param signup $signup
     * @return bool
     */
    public static function has_current(signup $signup) : bool {
        global $DB;
        return $DB->record_exists('facetoface_signups_status',  ['signupid' => $signup->get_id(), 'superceded'=> 0]);
    }

    /**
     * @param signup $signup
     * @return signup_status
     */
    public static function from_current(signup $signup) : signup_status {
        global $DB;
        $id = (int)$DB->get_field('facetoface_signups_status', 'id', ['signupid' => $signup->get_id(), 'superceded'=> 0], MUST_EXIST);
        return new signup_status($id);
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
     * Delete {facetoface_signups_status}.record
     */
    public function delete() {
        global $DB;

        $DB->delete_records(self::DBTABLE, ['id' => $this->id]);
        // Re-load instance with default values.
        $this->map_object((object)get_object_vars(new self()));
    }

    public function get_state_class() : string {
        return state::from_code((int)$this->statuscode);
    }

    /**
     * Create new signup status from state
     * @param signup $signup
     * @param state $state
     * @param int $timecreated
     * @param float|null $grade
     * @param null $reserved must be null
     * @return signup_status
     */
    public static function create(signup $signup, state $state, int $timecreated = 0, float $grade = null, $reserved = null) : signup_status {
        if (empty($timecreated)) {
            $timecreated = time();
        }
        $status = new signup_status();
        $status->statuscode = $state->get_code();
        $status->signupid = $signup->get_id();
        $status->timecreated = $timecreated;
        $status->grade = $grade;

        return $status;
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
    public function get_signupid() : int {
        return (int)$this->signupid;
    }
    /**
     * @param int $signupid
     */
    public function set_signupid(int $signupid) : signup_status {
        $this->signupid = $signupid;
        return $this;
    }

    /**
     * @return int
     */
    public function get_statuscode() : int {
        return (int)$this->statuscode;
    }
    /**
     * @param int $statuscode
     */
    public function set_statuscode(int $statuscode) : signup_status {
        $this->statuscode = $statuscode;
        return $this;
    }

    /**
     * @return int
     */
    public function get_superceded() : int {
        return (int)$this->superceded;
    }
    /**
     * @param int $superceded
     */
    public function set_superceded(int $superceded) : signup_status {
        $this->superceded = $superceded;
        return $this;
    }


    /**
     * @return float|null
     */
    public function get_grade() : ?float {
        return $this->grade;
    }
    /**
     * @param float|null $grade
     */
    public function set_grade(?float $grade) : signup_status {
        $this->grade = $grade;
        return $this;
    }


    /**
     * @return int
     */
    public function get_createdby() : int {
        return (int)$this->createdby;
    }
    /**
     * @param int $createdby
     */
    public function set_createdby(int $createdby) : signup_status {
        $this->createdby = $createdby;
        return $this;
    }

    /**
     * @return int
     */
    public function get_timecreated() : int {
        return (int)$this->timecreated;
    }
    /**
     * @param int $
     */
    public function set_timecreated(int $timecreated) : signup_status {
        $this->timecreated = $timecreated;
        return $this;
    }

}
