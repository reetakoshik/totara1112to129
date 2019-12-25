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
use mod_facetoface\signup\state\not_set;
use mod_facetoface\signup\state\interface_event;
use mod_facetoface\signup\transition;
use \stdClass;

defined('MOODLE_INTERNAL') || die();

/**
 * Class signup represents Session SignUps
 * This class must not know anything about specific states (e.g. difference between booked, waitlisted, or cancelled).
 * If specific state class needs to be considered use signup_helper instead.
 */
final class signup {

    use \mod_facetoface\traits\crud_mapper;

    /**
     * @var int {facetoface_signups}.id
     */
    private $id = 0;
    /**
     * @var int {facetoface_signups}.sessionid
     */
    private $sessionid = 0;
    /**
     * @var int {facetoface_signups}.userid
     */
    private $userid = 0;
    /**
     * @var string {facetoface_signups}.discountcode
     */
    private $discountcode = null;
    /**
     * @var int {facetoface_signups}.notificationtype
     */
    private $notificationtype = 0;
    /**
     * @var int {facetoface_signups}.archived
     */
    private $archived = 0;
    /**
     * @var int {facetoface_signups}.bookedby
     */
    private $bookedby = null;
    /**
     * @var int {facetoface_signups}.managerid
     */
    private $managerid = null;
    /**
     * @var int {facetoface_signups}.jobassignmentid
     */
    private $jobassignmentid = null;
    /**
     * @var string facetoface_signups table name
     */
    const DBTABLE = 'facetoface_signups';
    /**
     * @var seminar_event linked instance
     */
    private $seminarevent = null;

    /**
     * @var array Instance settings for signup.
     * These settings are not persistable (ephemeral) at this stage
     */
    private $settings = [];

    /**
     * Seminar signup constructor.
     *
     * @param int $id {facetoface_signups}.id If 0 - new signup will be created
     */
    public function __construct(int $id = 0) {

        $this->id = $id;
        $this->load();
    }

    /**
     * A function to create a signup from userid and seminar eventid
     * Will return an existing signup, or create a new one if none exists.
     *
     * @param int userid
     * @param seminar_event $seminarevent
     * @param int notificationtype - Default 3 = MDL_F2F_BOTH
     * @return signup
     */
    public static function create($userid, seminar_event $seminarevent, int $notificationtype = 3) : signup {
        global $DB;

        if (empty($seminarevent->get_id())) {
            throw new signup_exception("Cannot create signup: Seminar event id is not set (it must be saved before signup created)");
        }

        $signup = new signup();
        $signup->seminarevent = $seminarevent;
        $signup->set_notificationtype($notificationtype);
        $signup->userid = $userid;
        $signup->sessionid = $seminarevent->get_id();
        if ($signup->userid > 0) {
            $existing = $DB->get_record('facetoface_signups', ['userid' => $userid, 'sessionid' => $seminarevent->get_id(), 'archived' => 0]);
            if (!empty($existing)) {
                return $signup->map_object($existing);
            }
        }

        return $signup;
    }

    /**
     * Returning true if this object has associated id existing in the table.
     * @return bool
     */
    public function exists(): bool {
        return !empty($this->id);
    }

    /**
     * Set signup instance skipapproval setting
     * @param bool $skip
     * @return signup
     */
    public function set_skipapproval($skip = true) {
        $this->settings['skipapproval'] = $skip;
        return $this;
    }

    /**
     * Get signup instance skipapproval setting
     * @return bool
     */
    public function get_skipapproval() : bool {
        return empty($this->settings['skipapproval']) ? false : $this->settings['skipapproval'];
    }

    /**
     * Set signup instance ignoreconflicts setting
     * @param bool $ignore
     * @return signup
     */
    public function set_ignoreconflicts($ignore = true) {
        $this->settings['ignoreconflicts'] = $ignore;
        return $this;
    }

    /**
     * Get signup instance ignoreconflicts setting
     * @return bool
     */
    public function get_ignoreconflicts() : bool {
        return empty($this->settings['ignoreconflicts']) ? false : $this->settings['ignoreconflicts'];
    }

    /**
     * Set signup instance skipusernotification setting
     * @param bool $skip
     * @return signup
     */
    public function set_skipusernotification($skip = true) {
        $this->settings['skipusernotification'] = $skip;
        return $this;
    }

    /**
     * Get signup instance skipusernotification setting
     * @return bool
     */
    public function get_skipusernotification() : bool {
        return empty($this->settings['skipusernotification']) ? false : $this->settings['skipusernotification'];
    }

    /**
     * Set signup instance skipmanagernotification setting
     * @param bool $skip
     * @return signup
     */
    public function set_skipmanagernotification($skip = true) {
        $this->settings['skipmanagernotification'] = $skip;
        return $this;
    }

    /**
     * Get signup instance skipmanagernotification setting
     * @return bool
     */
    public function get_skipmanagernotification() : bool {
        return empty($this->settings['skipmanagernotification']) ? false : $this->settings['skipmanagernotification'];
    }

    /**
     * Set signup notification sender user instance
     * @param stdClass $user
     * @return signup
     */
    public function set_fromuser(stdClass $user) {
        $this->settings['fromuser'] = $user;
        return $this;
    }
    /**
     * Get signup notification sender user instance
     * @return stdClass or null
     */
    public function get_fromuser() {
        return empty($this->settings['fromuser']) ? null : $this->settings['fromuser'];
    }

    /**
     * Load seminar signup data from DB
     *
     * @return signup this
     */
    public function load() : signup {

        return $this->crud_load();
    }

    /**
     * Create/update {facetoface_sessions_dates}.record
     * @return signup
     */
    public function save() {
        $this->crud_save();
        return $this;
    }

    /**
     * Map data object to signup instance.
     *
     * @param \stdClass $object
     */
    public function map_instance(\stdClass $object) : signup {

        return $this->map_object($object);
    }

    /**
     * Delete {facetoface_signups}.record where id
     */
    public function delete() : signup {
        global $DB;

        $this->delete_customfields();

        $signupstatuses = new signup_status_list(['signupid' => $this->get_id()]);
        $signupstatuses->delete();

        $DB->delete_records(self::DBTABLE, ['id' => $this->id]);
        // Re-load instance with default values.
        $this->map_object((object)get_object_vars(new self()));

        return $this;
    }

    /**
     * Check availability of states to switch for signup.
     * @param string ...$newstates class names
     * @return boolean
     */
    public function can_switch(string ...$newstates) : bool {
        return $this->get_state()->can_switch(...$newstates);
    }

    /**
     * Switch signup state.
     * This function must be used for any state changes
     * @param string ...$newstates class names
     * @return \signup
     */
    public function switch_state(string ...$newstates) {
        global $DB;
        $trans = $DB->start_delegated_transaction();
        $oldstate = $this->get_state();
        $newstate = $oldstate->switch_to(...$newstates);
        $this->update_status($newstate);

        /**
         * @var state $newstate
         */
        if ($newstate instanceof interface_event) {
            $newstate->get_event()->trigger();
        }
        $newstate->on_enter();

        $trans->allow_commit();

        return $this;
    }

    /**
     * Switch signup state and set grade.
     * This function must be used for any state changes
     * @param mixed $grade grade
     * @param null $reserved must be null
     * @param string ...$newstates class names
     * @return \signup
     */
    public function switch_state_with_grade(?float $grade, ?string $reserved, string ...$newstates): signup {
        global $DB;
        if ($reserved !== null) {
            throw new \coding_exception('the argument `$reserved` must be null at this moment.');
        }

        $trans = $DB->start_delegated_transaction();
        $oldstate = $this->get_state();
        $newstate = $oldstate->switch_to(...$newstates);
        $this->update_status($newstate, 0, 0, $grade, $reserved);

        /**
         * @var state $newstate
         */
        if ($newstate instanceof interface_event) {
            $newstate->get_event()->trigger();
        }
        $newstate->on_enter();

        $trans->allow_commit();

        return $this;
    }

    /**
     * Print debug information for all states transitions
     * @param bool $return return debug instead of outputting it (like in print_r)
     * @return array
     */
    public function debug_state_transitions(bool $return=false) : array {
        $results = [];
        $currentstate = $this->get_state();
        /**
         * @var transition $transition
         */
        foreach ($currentstate->get_map() as $transition) {
            $results[] = [get_class($transition->get_to()) => $transition->debug_conditions()];
        }
        $output = ['current' => get_class($currentstate), 'transitions' => $results];
        if (!$return) {
            echo \html_writer::tag('pre', print_r($output, true));
            return [];
        }
        return $output;
    }

    /**
     * Get reasons why transition to any of states is impossible for current user
     * If transition is possible then will
     * @param string ...$newstates class names
     * @return array
     */
    public function get_failures(string ...$newstates) : array {
        $newstates = state::validate_state_classes($newstates);

        $results = [];
        $currentstate = $this->get_state();

        $map = $currentstate->get_map();
        $found = false;
        foreach ($newstates as $desiredstate) {
            /**
             * @var transition $transition
             */
            foreach ($map as $transition) {
                if ($transition->get_to() instanceof $desiredstate) {
                    $found = true;
                    if ($transition->possible()) {
                        return [];
                    } else {
                        $results = array_merge($results, $transition->get_failures());
                    }
                }
            }
        }
        if (!$found || empty($results)) {
            $results['notfound'] = get_string('error:nostatetransitionfound', 'mod_facetoface');
        }

        return $results;
    }

    /**
     * Delete records from facetoface_signup_info_data/facetoface_cancellation_info_data
     */
    protected function delete_customfields() : signup {
        global $DB;

        // Get all associated signup customfield data to delete.
        $signupinfoids = $DB->get_fieldset_select(
            'facetoface_signup_info_data',
            'id',
            'facetofacesignupid = :facetofacesignupid',
            ['facetofacesignupid' => $this->get_id()]
        );
        if ($signupinfoids) {
            list($sqlin, $inparams) = $DB->get_in_or_equal($signupinfoids);
            $DB->delete_records_select('facetoface_signup_info_data_param', "dataid {$sqlin}", $inparams);
            $DB->delete_records_select('facetoface_signup_info_data', "id {$sqlin}", $inparams);
        }

        // Get all associated cancellation customfield data to delete.
        $cancellationids = $DB->get_fieldset_select(
            'facetoface_cancellation_info_data',
            'id',
            'facetofacecancellationid = :facetofacecancellationid',
            ['facetofacecancellationid' => $this->get_id()]
        );
        if ($cancellationids) {
            list($sqlin, $inparams) = $DB->get_in_or_equal($cancellationids);
            $DB->delete_records_select('facetoface_cancellation_info_data_param', "dataid {$sqlin}", $inparams);
            $DB->delete_records_select('facetoface_cancellation_info_data', "id {$sqlin}", $inparams);
        }

        return $this;
    }

    /**
     * Add new current signup status with a new state.
     * To change state of signup use signup::switch_state()
     * @param state $state
     * @param int $timecreated
     * @param int $userbyid
     * @param float|null $grade
     * @param null $reserved must be null
     */
    protected function update_status(state $state, int $timecreated = 0, int $userbyid = 0, ?float $grade = null, ?string $reserved = null) : signup_status {
        global $USER, $CFG;

        // We need the completionlib for \completion_info and the COMPLETION_UNKNOWN constant.
        require_once($CFG->libdir . '/completionlib.php');

        $status = signup_status::create($this, $state, $timecreated, $grade, $reserved);

        if (empty($userbyid)) {
            $userbyid = (int)$USER->id;
        }
        $status->set_createdby($userbyid);
        $status->save();

        $seminarevent = $this->get_seminar_event();
        $seminar = $seminarevent->get_seminar();
        $cm = $seminar->get_coursemodule();
        $context = \context_module::instance($cm->id);

        // Check for completions.
        // Note: This code block was taken from facetoface_set_completion();
        $course = new stdClass();
        $course->id = $seminar->get_course();
        $completion = new \completion_info($course);
        if ($completion->is_enabled() && !empty($cm) && $completion->is_enabled($cm)) {
            $completion->update_state($cm, COMPLETION_UNKNOWN, $this->get_userid());
            $completion->invalidatecache($seminar->get_course(), $this->get_userid(), true);
        }

        // The signup status has been updated, throw the generic event.
        \mod_facetoface\event\signup_status_updated::create_from_items($status, $context, $this)->trigger();

        return $status;
    }

    /**
     * @return int
     */
    public function get_id() : int {
        return (int)$this->id;
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
     * @return int
     */
    public function get_sessionid() : int {
        return (int)$this->sessionid;
    }
    /**
     * @param int $sessionid
     */
    public function set_sessionid(int $sessionid) : signup {
        $this->sessionid = $sessionid;
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
    public function set_userid(int $userid) : signup {
        $this->userid = $userid;
        return $this;
    }

    /**
     * @return string
     */
    public function get_discountcode() : string {
        return $this->discountcode;
    }
    /**
     * @param string $discountcode
     */
    public function set_discountcode(string $discountcode) : signup {
        $this->discountcode = $discountcode;
        return $this;
    }

    /**
     * @return int
     */
    public function get_notificationtype() : int {
        return (int)$this->notificationtype;
    }
    /**
     * @param int $notificationtype
     */
    public function set_notificationtype(int $notificationtype) : signup {
        $this->notificationtype = $notificationtype;
        return $this;
    }

    /**
     * @return int
     */
    public function get_archived() : int {
        return (int)$this->archived;
    }
    /**
     * @param int $archived
     */
    public function set_archived(int $archived) : signup {
        $this->archived = $archived;
        return $this;
    }

    /**
     * @return int
     */
    public function get_bookedby() : int {
        return (int)$this->bookedby;
    }

    /**
     * @param int $bookedby
     */
    public function set_bookedby(int $bookedby) : signup {
        $this->bookedby = $bookedby;
        return $this;
    }

    /**
     * @return int
     */
    public function get_managerid() : int {
        return (int)$this->managerid;
    }
    /**
     * @param int $managerid
     */
    public function set_managerid(int $managerid) : signup {
        $this->managerid = $managerid;
        return $this;
    }

    /**
     * @return int
     */
    public function get_jobassignmentid() : int {
        return (int)$this->jobassignmentid;
    }
    /**
     * @param int $jobassignmentid
     */
    public function set_jobassignmentid(int $jobassignmentid) : signup {
        $this->jobassignmentid = $jobassignmentid;
        return $this;
    }

    /**
     * Get linked seminar event
     * @return seminar_event
     */
    public function get_seminar_event() {
        if (is_null($this->seminarevent) || $this->seminarevent->get_id() != $this->sessionid) {
            $this->seminarevent = new seminar_event((int)$this->sessionid);
        }
        return $this->seminarevent;
    }

    /**
     * @param int $actorid
     * @return signup
     */
    public function set_actorid(int $actorid): signup {
        $this->settings['actorid'] = $actorid;
        return $this;
    }

    /**
     * @return int
     */
    public function get_actorid(): int {
        global $USER;
        if (!isset($this->settings['actorid'])) {
            return (int)$USER->id;
        }
        return (int)$this->settings['actorid'];
    }

    /**
     * Returning null if the actorid is not being set, or a full record information (stdClass) of a user retrieved
     * from the database
     * @return stdClass
     */
    public function get_actor(): stdClass {
        global $DB, $USER;
        $actorid = $this->get_actorid();
        if ($actorid == $USER->id || $actorid == 0) {
            return $USER;
        }
        return $DB->get_record("user", ['id' => $actorid]);
    }
}
