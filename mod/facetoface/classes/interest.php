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
 * @author  Oleg Demeshev <oleg.demeshev@totaralearning.com>
 * @package mod_facetoface
 */

namespace mod_facetoface;

defined('MOODLE_INTERNAL') || die();

/**
 * Class seminar represents Seminar Interest
 */
final class interest {

    use traits\crud_mapper;

    /**
     * @var int {facetoface_interest}.id
     */
    private $id = 0;
    /**
     * @var int {facetoface_interest}.facetoface
     */
    private $facetoface = 0;
    /**
     * @var int {facetoface_interest}.userid
     */
    private $userid = 0;
    /**
     * @var int {facetoface_interest}.timedeclared
     */
    private $timedeclared = 0;
    /**
     * @var string {facetoface_interest}.reason
     */
    private $reason = '';
    /**
     * @var seminar
     */
    private $seminar = null;
    /**
     * @var string facetoface_interest table name
     */
    const DBTABLE = 'facetoface_interest';

    /**
     * Seminar Interest constructor.
     * @param int $id {facetoface_interest}.id If 0 - new Session will be created
     */
    public function __construct(int $id = 0) {

        $this->id = $id;
        $this->load();
    }

    /**
     * Determines whether the user has already expressed interest in this activity.
     *
     * @return boolean
     */
    public function is_user_declared() : bool {

        return (bool)$this->id;
    }

    /**
     * Determines whether the user can declare interest in the activity.
     *
     * @return boolean
     */
    public function can_user_declare() : bool {
        global $DB;

        if (empty($this->seminar)) {
            $this->seminar = new seminar($this->facetoface);
        }

        // "Declare interest" must be turned on for the activity.
        if (!$this->seminar->get_declareinterest()) {
            return false;
        }

        // If user already declared interest, cannot declare again.
        if ($this->is_user_declared()) {
            return false;
        }

        // If "only when full" is turned on, allow only when all sessions are fully booked.
        if ($this->seminar->get_interestonlyiffull()) {
            // If user signed - no declare interest.
            $submission = facetoface_get_user_submissions($this->facetoface, $this->userid, \mod_facetoface\signup\state\requested::get_code(),
                \mod_facetoface\signup\state\fully_attended::get_code());
            if (!empty($submission)) {
                return false;
            }

            $now = time();
            $sql = "SELECT DISTINCT fs.id FROM {facetoface_sessions} fs
                    INNER JOIN {facetoface_sessions_dates} fsd ON (fsd.sessionid = fs.id)
                        WHERE fsd.timestart > :now AND fs.facetoface = :facetoface";

            $sessions = $DB->get_records_sql($sql, ['now' => $now, 'facetoface' => $this->facetoface]);
            foreach ($sessions as $sessionrec) {
                $signup = signup::create($this->userid, new seminar_event((int)$sessionrec->id));
                if (signup_helper::can_signup($signup)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Get interest instance by seminar id and user id if exists, if not, create a new one.
     *
     * @param  seminar $seminar instance for the seminar activity
     * @param  int $userid Current user, optional
     * @return interest instance
     */
    public static function from_seminar(\mod_facetoface\seminar $seminar, int $userid = 0) : interest {
        global $DB, $USER;

        $userid = $userid === 0 ? $USER->id : $userid;

        $record = $DB->get_record(self::DBTABLE, ['facetoface' => $seminar->get_id(), 'userid' => $userid], '*');
        $interest = new interest();
        $interest->seminar = $seminar;
        if ($record) {
            return $interest->map_instance($record);
        } else {
            $interest->facetoface = $seminar->get_id();
            $interest->userid = $userid;
            $interest->timedeclared = time();
        }
        return $interest;
    }

    /**
     * Load interest data from DB
     * @return interest this
     */
    public function load() : interest {

        return $this->crud_load();
    }

    /**
     * Create/update {facetoface_interest}.record
     */
    public function declare() {

        if ($this->timedeclared == 0) {
            $this->timedeclared = time();
        }
        $this->crud_save();
    }

    /**
     * Map data object to interest instance.
     *
     * @param \stdClass $object
     * @return interest instance
     */
    public function map_instance(\stdClass $object) : interest {

        return $this->map_object($object);
    }

    /**
     * Map interest instance properties to data object.
     *
     * @return \stdClass
     */
    public function get_properties() : \stdClass {

        return $this->unmap_object();
    }

    /**
     * Delete {facetoface_interest}.record where id from database
     */
    public function withdraw() {
        global $DB;

        $DB->delete_records(self::DBTABLE, ['id' => $this->id]);
        // Re-load instance with default values.
        $this->map_object((object)get_object_vars(new self()));
    }

    /**
     * Delete {facetoface_interest}.record where id from database
     */
    public function delete() {
        $this->withdraw();
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
    public function get_facetoface() : int {
        return (int)$this->facetoface;
    }
    /**
     * @param int $seminarid
     */
    public function set_facetoface(int $seminarid) : interest {
        $this->facetoface = $seminarid;
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
    public function set_userid(int $userid) : interest {
        $this->userid = $userid;
        return $this;
    }

    /**
     * @return int
     */
    public function get_timedeclared() : int {
        return (int)$this->timedeclared;
    }
    /**
     * @param int $timedesclared
     */
    public function set_timedeclared(int $timedeclared) : interest {
        $this->timedeclared = $timedeclared;
        return $this;
    }

    /**
     * @return string
     */
    public function get_reason() : string {
        return (string)$this->reason;
    }
    /**
     * @param int $reason
     */
    public function set_reason(string $reason) : interest {
        $this->reason = $reason;
        return $this;
    }
}