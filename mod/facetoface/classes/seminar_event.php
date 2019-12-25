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

use context_module;

defined('MOODLE_INTERNAL') || die();

/**
 * Class seminar_event represents Seminar event
 */
final class seminar_event {

    use traits\crud_mapper;

    /**
     * Cancellation options for $this->allowcancellation.
     */
    const ALLOW_CANCELLATION_NEVER = 0;
    const ALLOW_CANCELLATION_ANY_TIME = 1;
    const ALLOW_CANCELLATION_CUT_OFF = 2;

    /**
     * @var int {facetoface_sessions}.id
     */
    private $id = 0;
    /**
     * @var int {facetoface_sessions}.facetoface
     */
    private $facetoface = 0;
    /**
     * @var int {facetoface_sessions}.capacity
     */
    private $capacity = 10;
    /**
     * @var int {facetoface_sessions}.allowoverbook
     */
    private $allowoverbook = 0;
    /**
     * @var int {facetoface_sessions}.waitlisteveryone
     */
    private $waitlisteveryone = 0;
    /**
     * @var string {facetoface_sessions}.details
     */
    private $details = '';
    /**
     * @var int {facetoface_sessions}.normalcost
     */
    private $normalcost = 0;
    /**
     * @var int {facetoface_sessions}.discountcost
     */
    private $discountcost = 0;
    /**
     * @var int {facetoface_sessions}.allowcancellations
     */
    private $allowcancellations = 1;
    /**
     * @var int {facetoface_sessions}.cancellationcutoff
     */
    private $cancellationcutoff = 86400;
    /**
     * @var int {facetoface_sessions}.timecreated
     */
    private $timecreated = 0;
    /**
     * @var int {facetoface_sessions}.timemodified
     */
    private $timemodified = 0;
    /**
     * @var int {facetoface_sessions}.usermodified
     */
    private $usermodified = 0;
    /**
     * @var int {facetoface_sessions}.selfapproval
     */
    private $selfapproval = 0;
    /**
     * @var int {facetoface_sessions}.mincapacity
     */
    private $mincapacity = 0;
    /**
     * @var int {facetoface_sessions}.cutoff
     */
    private $cutoff = 86400;
    /**
     * @var int {facetoface_sessions}.sendcapacityemail
     */
    private $sendcapacityemail = 0;
    /**
     * @var int {facetoface_sessions}.registrationtimestart
     */
    private $registrationtimestart = 0;
    /**
     * @var int {facetoface_sessions}.registrationtimefinish
     */
    private $registrationtimefinish = 0;
    /**
     * @var int {facetoface_sessions}.cancelledstatus
     */
    private $cancelledstatus = 0;

    /**
     * Related seminar instance
     * @var seminar
     */
    private $seminar = null;
    /**
     * @var string facetoface_sessions table name
     */
    const DBTABLE = 'facetoface_sessions';

    /**
     * Seminar event constructor.
     *
     * @param int $id {facetoface_session}.id If 0 - new Seminar Event will be created
     */
    public function __construct(int $id = 0) {

        $this->id = $id;
        $this->load();
    }

    /**
     * Load seminar event data from DB
     *
     * @return seminar_event this
     */
    private function load() : seminar_event {

        return $this->crud_load();
    }

    /**
     * Create/update {facetoface_sessions}.record
     */
    public function save() {
        global $USER;

        $this->timemodified = time();
        $this->usermodified = $USER->id;
        $this->cleanup_capacity();

        if (!$this->id) {
            $this->timecreated = time();
        }

        $this->crud_save();
    }

    /**
     * Cancel the seminar event.
     *
     * @return bool
     */
    public function cancel() : bool {
        global $USER, $DB;

        if ($this->is_started()) {
            // Events can not be cancelled after they have started.
            return false;
        }

        if ($this->cancelledstatus != 0) {
            // Event is already cancelled, can not cancel twice.
            return false;
        }

        $notifylearners = [];
        $notifytrainers = [];

        // Wrap necessary DB updates in a transaction.
        $trans = $DB->start_delegated_transaction();

        $this->set_cancelledstatus(1);
        $this->save();

        // Remove entries from the calendars.
        \mod_facetoface\calendar::remove_all_entries($this);

        // Change all user sign-up statuses, the only exceptions are previously cancelled users and declined users.
        $signups = signup_list::from_conditions(['sessionid' => $this->get_id()]);
        foreach ($signups as $signup) {
            if ($signup->can_switch(\mod_facetoface\signup\state\event_cancelled::class)) {
                $signup->switch_state(\mod_facetoface\signup\state\event_cancelled::class);

                // Add them to the affected learners list for later notifications.
                $notifylearners[$signup->get_userid()] = $signup;
            }
        }

        // All necessary DB updates are finished, let's commit.
        $trans->allow_commit();

        $cm = get_coursemodule_from_instance('facetoface', $this->get_facetoface());
        $context = context_module::instance($cm->id);
        \mod_facetoface\event\session_cancelled::create_from_session($this->to_record(), $context)->trigger();

        // Notify trainers assigned to the session too.
        $sql = "SELECT DISTINCT sr.userid
                  FROM {facetoface_session_roles} sr
                  JOIN {user} u ON (u.id = sr.userid)
                 WHERE sr.sessionid = :sessionid AND u.deleted = 0";
        $trainers = $DB->get_recordset_sql($sql, array('sessionid' => $this->get_id()));
        foreach ($trainers as $trainer) {
            $notifytrainers[$trainer->userid] = $trainer;
        }
        $trainers->close();

        // Notify affected users.
        foreach ($notifylearners as $id => $user) {
            // Check if the user is waitlisted we should not attach an iCal.
            $state = $signup->get_state();
            $invite = !($state instanceof \mod_facetoface\signup\state\waitlisted);
            notice_sender::event_cancellation($id, $this, $invite);
        }

        // Notify affected trainers.
        foreach ($notifytrainers as $id => $trainer) {
            notice_sender::event_cancellation($id, $this);
        }

        // Notify managers who had reservations.
        $facetoface = $DB->get_record('facetoface', ['id' => $this->get_facetoface()]);
        facetoface_notify_reserved_session_deleted($facetoface, $this->to_record());

        // Start cleaning up the custom rooms, custom assets here at the very end of this cancellation task, because we would want
        // the information of custom rooms and custom assets to be included in the email sending to users which should have happened
        // before this stage.
        $sessions = $this->get_sessions();

        /** @var seminar_session $session */
        foreach ($sessions as $session) {
            // Unlink rooms, orphaned custom rooms are deleted from cleanup task.
            $session->set_roomid(0);
            $session->save();

            // Unlink assets, orphaned custom assets are deleted from cleanup task.
            $DB->delete_records('facetoface_asset_dates', ['sessionsdateid' => $session->get_id()]);
        }

        return true;
    }

    /**
     * Delete {facetoface_sessions}.record where id from database
     */
    public function delete() {
        global $DB;

        $sessiondates = new seminar_session_list(['sessionid' => $this->get_id()]);
        $sessiondates->delete();

        $seminarsignups = signup_list::from_conditions(['sessionid' => $this->get_id()]);
        $seminarsignups->delete();

        $seminarroles = new role_list(['sessionid' => $this->get_id()]);
        $seminarroles->delete();

        $this->delete_files();
        $this->delete_customfields();

        $DB->delete_records(self::DBTABLE, ['id' => $this->id]);
        // Re-load instance with default values.
        $this->map_object((object)get_object_vars(new self()));
    }

    protected function delete_customfields() : seminar_event {
        global $DB;

        // Get session data to delete.
        $sessiondataids = $DB->get_fieldset_select(
            'facetoface_session_info_data',
            'id',
            'facetofacesessionid = :facetofacesessionid',
            ['facetofacesessionid' => $this->get_id()]);

        if ($sessiondataids) {
            list($sqlin, $inparams) = $DB->get_in_or_equal($sessiondataids);
            $DB->delete_records_select('facetoface_session_info_data_param', "dataid {$sqlin}", $inparams);
            $DB->delete_records_select('facetoface_session_info_data', "id {$sqlin}", $inparams);
        }

        $sessioncancelids = $DB->get_fieldset_select(
            'facetoface_sessioncancel_info_data',
            'id',
            'facetofacesessioncancelid = :sessionid',
            ['sessionid' => $this->get_id()]
        );
        if ($sessioncancelids) {
            list($sqlin, $inparams) = $DB->get_in_or_equal($sessioncancelids);
            $DB->delete_records_select('facetoface_sessioncancel_info_data_param', "dataid $sqlin", $inparams);
            $DB->delete_records_select('facetoface_sessioncancel_info_data', "id {$sqlin}", $inparams);
        }

        return $this;
    }

    /**
     * Delete files embedded in details text associated with this seminar event
     *
     * @return seminar_event $this
     */
    protected function delete_files() : seminar_event {

        $seminar = new seminar($this->get_facetoface());
        $cm = get_coursemodule_from_instance('facetoface', $seminar->get_id(), $seminar->get_course(), false, MUST_EXIST);
        $context = \context_module::instance($cm->id);

        $fs = get_file_storage();
        $fs->delete_area_files($context->id, 'mod_facetoface', 'session', $this->id);

        return $this;
    }

    /**
     * Map object to class instance.
     *
     * @param \stdClass $object
     */
    public function from_record(\stdClass $object) {
        return $this->map_object($object);
    }

    /**
     * Map seminar event instance properties to data object.
     *
     * @return \stdClass
     */
    public function to_record() : \stdClass {
        return $this->unmap_object();
    }

    /**
     * Prepare the user data to go into the database.
     */
    protected function cleanup_capacity() : seminar_event {
        // Only numbers allowed here
        $capacity = preg_replace('/[^\d]/', '', $this->capacity);
        $MAX_CAPACITY = 100000;
        if ($capacity < 1) {
            $capacity = 1;
        } elseif ($capacity > $MAX_CAPACITY) {
            $capacity = $MAX_CAPACITY;
        }

        $this->set_capacity((int)$capacity);

        return $this;
    }

    /**
     * Check whether the seminar event exists yet or not.
     * If the asset has been saved into the database the $id field should be non-zero.
     *
     * @return bool - true if the asset has an $id, false if it hasn't
     */
    public function exists() : bool {
        return !empty($this->id);
    }

    /**
     * Dismiss approver from seminar event.
     *
     */
    public function dismiss_approver() {
        global $DB;

        $this->set_selfapproval(0);
        $DB->update_record(self::DBTABLE, ['selfapproval' => $this->selfapproval, 'id' => $this->id]);
    }

    /**
     * Return associated seminar instance
     * @return seminar
     */
    public function get_seminar(): seminar {
        $this->seminar = null;
        if (empty($this->facetoface)) {
            throw new coding_exception("Cannot get seminar from unassociated event");
        }
        if (empty($this->seminar) || $this->seminar->get_id() != $this->get_facetoface()) {
            $this->seminar = new seminar($this->get_facetoface());
        }
        return $this->seminar;
    }

    /**
     * Has this seminar event started at certain point of time
     * @param int $time
     * @return bool
     */
    public function is_started(int $time = 0): bool {
        $time = $time ? $time : time();
        $sessions = $this->get_sessions();

        // Check that a date has actually been set.
        if (!$sessions->count()) {
            return false;
        }

        $mintimestart = $this->get_mintimestart();
        if (empty($mintimestart)) {
            // There are no sessions so it can't have started.
            return false;
        }

        return $mintimestart < $time;
    }

    /**
     * Get the earliest start time from associated sessions.
     * @return int
     */
    public function get_mintimestart() {
        $mintimestart = 0;
        $sessions = $this->get_sessions();
        foreach ($sessions as $session) {
            // Check for minimum time start.
            if (empty($mintimestart) || $session->get_timestart() < $mintimestart) {
                $mintimestart = $session->get_timestart();
            }
        }

        return $mintimestart;
    }

    /**
     * Is seminar event in progress
     * @param int $time
     * @return bool
     */
    public function is_progress(int $time = 0) : bool {
        $timenow = $time ? $time : time();
        $dates = $this->get_sessions();
        foreach ($dates as $seminarsession) {
            /**
             * @var seminar_session $seminarsession
             */
            if ($seminarsession->get_timestart() < $timenow && $seminarsession->get_timefinish() >= $timenow) {
                return true;
            }
        }
        return false;
    }

    /**
     * Does this event have session(s)
     * @return bool
     */
    public function is_sessions() : bool {
        return $this->get_sessions()->count() > 0;
    }

    /**
     * Get sessions for this event
     * @return seminar_session_list
     */
    public function get_sessions(): seminar_session_list {
        return seminar_session_list::from_seminar_event($this);
    }

    public function get_id() : int {
        return (int)$this->id;
    }

    /**
     * Get facetoface id
     * @return int
     */
    public function get_facetoface() : int {
        return (int)$this->facetoface;
    }

    /**
     * Set facetoface id
     * @param int $facetoface
     */
    public function set_facetoface(int $facetoface) : seminar_event {
        $this->facetoface = $facetoface;
        return $this;
    }

    /**
     * Get capacity of event (total number of places to book)
     * @return int
     */
    public function get_capacity() : int {
        return (int)$this->capacity;
    }

    /**
     * Get amount of free capacity
     * @return int
     */
    public function get_free_capacity() {
        global $DB;
        $attendeesql = 'SELECT COUNT(ss.id)
                           FROM {facetoface_signups} su
                           JOIN {facetoface_signups_status} ss ON su.id = ss.signupid
                          WHERE sessionid = :sessionid
                            AND ss.superceded = 0
                            AND ss.statuscode >= :status';
        $numattendees = (int)$DB->count_records_sql($attendeesql, ['sessionid' => $this->id, 'status' => \mod_facetoface\signup\state\booked::get_code()]);
        return $this->get_capacity() - $numattendees;
    }

    /**
     * Set capacity of event
     * @param int
     */
    public function set_capacity(int $capacity) : seminar_event {
        $this->capacity = $capacity;
        return $this;
    }

    /**
     * Get event allowoverbook
     * @return int
     */
    public function get_allowoverbook() : int {
        return (int)$this->allowoverbook;
    }
    /**
     * Set allowoverbook of event
     * @param int
     */
    public function set_allowoverbook(int $allowoverbook) : seminar_event {
        $this->allowoverbook = $allowoverbook;
        return $this;
    }

    /**
     * Get event waitlisteveryone
     * @return int
     */
    public function get_waitlisteveryone() : int {
        return (int)$this->waitlisteveryone;
    }

    /**
     * Check if waitlist everyone is enabled globally and for the event.
     * @return bool
     */
    public function is_waitlisteveryone() : bool {
        return get_config(null, 'facetoface_allowwaitlisteveryone') && $this->waitlisteveryone;
    }

    /**
     * Set waitlisteveryone of event
     * @param int
     */
    public function set_waitlisteveryone(int $waitlisteveryone) : seminar_event {
        $this->waitlisteveryone = $waitlisteveryone;
        return $this;
    }

    /**
     * Get event details
     * @return string
     */
    public function get_details() : string {
        return (string)$this->details;
    }
    /**
     * Set event details
     * @param string
     */
    public function set_details(string $details) : seminar_event {
        $this->details = $details;
        return $this;
    }

    /**
     * Get event normalcost
     * @return int
     */
    public function get_normalcost() : int {
        return (int)$this->normalcost;
    }
    /**
     * Set event normalcost
     * @param int
     */
    public function set_normalcost(int $normalcost) : seminar_event {
        $this->normalcost = $normalcost;
        return $this;
    }

    /**
     * Get event discountcost
     * @return int
     */
    public function get_discountcost() : int {
        return (int)$this->discountcost;
    }
    /**
     * Set event discountcost
     * @param int
     */
    public function set_discountcost(int $discountcost) : seminar_event {
        $this->discountcost = $discountcost;
        return $this;
    }

    /**
     * Should discount cost be displayed taking into account global settings
     * @return bool
     */
    public function is_discountcost() : bool {
        return !get_config(null, 'facetoface_hidecost')
            && !get_config(null, 'facetoface_hidediscount')
            && $this->get_discountcost() > 0;
    }


    /**
     * Get event allowcancellations
     * @return int
     */
    public function get_allowcancellations() : int {
        return (int)$this->allowcancellations;
    }
    /**
     * Set event allowcancellations
     * @param int
     */
    public function set_allowcancellations(int $allowcancellations) : seminar_event {
        $this->allowcancellations = $allowcancellations;
        return $this;
    }

    /**
     * Get event cancellationcutoff
     * @return int
     */
    public function get_cancellationcutoff() : int {
        return (int)$this->cancellationcutoff;
    }
    /**
     * Set event cancellationcutoff
     * @param int
     */
    public function set_cancellationcutoff(int $cancellationcutoff) : seminar_event {
        $this->cancellationcutoff = $cancellationcutoff;
        return $this;
    }

    /**
     * Get event timecreated
     * @return int
     */
    public function get_timecreated() : int {
        return (int)$this->timecreated;
    }
    /**
     * Set event timecreated
     * @param int
     */
    public function set_timecreated(int $timecreated) : seminar_event {
        $this->timecreated = $timecreated;
        return $this;
    }

    /**
     * Get event timemodified
     * @return int
     */
    public function get_timemodified() : int {
        return (int)$this->timemodified;
    }
    /**
     * Set event timemodified
     * @param int
     */
    public function set_timemodified(int $timemodified) : seminar_event {
        $this->timemodified = $timemodified;
        return $this;
    }

    /**
     * Get event usermodified
     * @return int
     */
    public function get_usermodified() : int {
        return (int)$this->usermodified;
    }
    /**
     * Set event usermodified
     * @param int
     */
    public function set_usermodified(int $usermodified) : seminar_event {
        $this->usermodified = $usermodified;
        return $this;
    }

    /**
     * Get event selfapproval
     * @return int
     */
    public function get_selfapproval() : int {
        return (int)$this->selfapproval;
    }
    /**
     * Set event selfapproval
     * @param int
     */
    public function set_selfapproval(int $selfapproval) : seminar_event {
        $this->selfapproval = $selfapproval;
        return $this;
    }

    /**
     * Get event mincapacity
     * @return int
     */
    public function get_mincapacity() : int {
        return (int)$this->mincapacity;
    }
    /**
     * Set event mincapacity
     * @param int
     */
    public function set_mincapacity(int $mincapacity) : seminar_event {
        $this->mincapacity = $mincapacity;
        return $this;
    }

    /**
     * Get event cutoff
     * @return int
     */
    public function get_cutoff() : int {
        return (int)$this->cutoff;
    }
    /**
     * Set event cutoff
     * @param int
     */
    public function set_cutoff(int $cutoff) : seminar_event {
        $this->cutoff = $cutoff;
        return $this;
    }

    /**
     * Get event sendcapacityemail
     * @return int
     */
    public function get_sendcapacityemail() : int {
        return (int)$this->sendcapacityemail;
    }
    /**
     * Set event sendcapacityemail
     * @param int
     */
    public function set_sendcapacityemail(int $sendcapacityemail) : seminar_event {
        $this->sendcapacityemail = $sendcapacityemail;
        return $this;
    }

    /**
     * Get event registrationtimestart
     * @return int
     */
    public function get_registrationtimestart() : int {
        return (int)$this->registrationtimestart;
    }
    /**
     * Set event registrationtimestart
     * @param int
     */
    public function set_registrationtimestart(int $registrationtimestart) : seminar_event {
        $this->registrationtimestart = $registrationtimestart;
        return $this;
    }

    /**
     * Get event registrationtimefinish
     * @return int
     */
    public function get_registrationtimefinish() : int {
        return (int)$this->registrationtimefinish;
    }

    /**
     * Set event registrationtimefinish
     * @param int
     */
    public function set_registrationtimefinish(int $registrationtimefinish) : seminar_event {
        $this->registrationtimefinish = $registrationtimefinish;
        return $this;
    }

    /**
     * Get event cancelledstatus
     * @return int
     */
    public function get_cancelledstatus() : int {
        return (int)$this->cancelledstatus;
    }

    /**
     * Set event cancelledstatus
     * @param int
     */
    public function set_cancelledstatus(int $cancelledstatus) : seminar_event {
        $this->cancelledstatus = $cancelledstatus;
        return $this;
    }
}
