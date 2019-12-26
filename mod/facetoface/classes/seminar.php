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
 * @author  Valerii Kuznetsov <valerii.kuznetsov@totaralearning.com>
 * @author  Oleg Demeshev <oleg.demeshev@totaralearning.com>
 * @package mod_facetoface
 */

namespace mod_facetoface;

use Dompdf\Exception;
use mod_facetoface\signup\state\{no_show, partially_attended, fully_attended, declined, not_set};

defined('MOODLE_INTERNAL') || die();

/**
 * Class seminar represents Seminar Activity
 */
final class seminar {

    use traits\crud_mapper;

    /**
     * Approval types
     */
    const APPROVAL_NONE = 0;
    const APPROVAL_SELF = 1;
    const APPROVAL_ROLE = 2;
    const APPROVAL_MANAGER = 4;
    const APPROVAL_ADMIN = 8;

    /**
     * @var int {facetoface}.id
     */
    private $id = 0;
    /**
     * @var int {facetoface}.course
     */
    private $course = 0;
    /**
     * @var string {facetoface}.name
     */
    private $name = "";
    /**
     * @var string {facetoface}.intro
     */
    private $intro = "";
    /**
     * @var int {facetoface}.introformat
     */
    private $introformat = 0;
    /**
     * @var string {facetoface}.thirdparty
     */
    private $thirdparty = "";
    /**
     * @var int {facetoface}.thirdpartywaitlist
     */
    private $thirdpartywaitlist = "";
    /**
     * @var int {facetoface}.waitlistautoclean
     */
    private $waitlistautoclean = 0;
    /**
     * @var int {facetoface}.display
     */
    private $display = 0;
    /**
     * @var int {facetoface}.timecreated
     */
    private $timecreated = 0;
    /**
     * @var int {facetoface}.timemodified
     */
    private $timemodified = 0;
    /**
     * @var string {facetoface}.shortname
     */
    private $shortname = "";
    /**
     * @var int {facetoface}.showoncalendar
     */
    private $showoncalendar = 1;
    /**
     * @var int {facetoface}.usercalentry
     */
    private $usercalentry = 1;
    /**
     * Note: saved in the database as multiplesessions,
     *       referred to elsewhere as multiplesignups.
     * @var int {facetoface}.multiplesessions
     */
    private $multiplesessions = 0;
    /**
     * @var int {facetoface}.multisignupfully
     */
    private $multisignupfully = 0;
    /**
     * @var int {facetoface}.multisignuppartly
     */
    private $multisignuppartly = 0;
    /**
     * @var int {facetoface}.multisignupnoshow
     */
    private $multisignupnoshow = 0;
    /**
     * @var int {facetoface}.multisignupmaximum
     */
    private $multisignupmaximum = 0;
    /**
     * @var string {facetoface}.completionstatusrequired
     */
    private $completionstatusrequired = null;
    /**
     * @var int {facetoface}.managerreserve
     */
    private $managerreserve = 0;
    /**
     * @var int {facetoface}.maxmanagerreserves
     */
    private $maxmanagerreserves = 1;
    /**
     * @var int {facetoface}.reservecanceldays
     */
    private $reservecanceldays = 1;
    /**
     * @var int {facetoface}.reservedays
     */
    private $reservedays = 2;
    /**
     * @var int {facetoface}.declareinterest
     */
    private $declareinterest = 0;
    /**
     * @var int {facetoface}.interestonlyiffull
     */
    private $interestonlyiffull = 0;
    /**
     * @var int {facetoface}.allowcancellationsdefault
     */
    private $allowcancellationsdefault  = 1;
    /**
     * @var int {facetoface}.cancellationscutoffdefault
     */
    private $cancellationscutoffdefault  = 86400;
    /**
     * @var int {facetoface}.selectjobassignmentonsignup
     */
    private $selectjobassignmentonsignup  = 0;
    /**
     * @var int {facetoface}.forceselectjobassignment
     */
    private $forceselectjobassignment  = 0;
    /**
     * @var int {facetoface}.approvaltype
     */
    private $approvaltype = 0;
    /**
     * @var int {facetoface}.approvalrole
     */
    private $approvalrole = 0;
    /**
     * @var string {facetoface}.approvalterms
     */
    private $approvalterms = "";
    /**
     * @var string {facetoface}.approvaladmins
     */
    private $approvaladmins = "";
    /**
     * @var string facetoface table name
     */
    const DBTABLE = 'facetoface';

    /**
     * Seminar constructor.
     *
     * @param int $id {facetoface}.id If 0 - new Seminar Activity will be created
     */
    public function __construct(int $id = 0) {

        $this->id = $id;
        $this->load();
    }

    /**
     * Return course module.
     *
     * @return \stdClass
     */
    public function get_coursemodule() : \stdClass {
        return get_coursemodule_from_instance('facetoface', $this->id, $this->course, false, MUST_EXIST);
    }

    /**
     * Load facetoface data from DB
     *
     * @return seminar this
     */
    public function load() : seminar {

        return $this->crud_load();
    }

    public function save() {

        $this->timemodified = time();

        if (!$this->id) {
            $this->timecreated = time();
        }

        $this->crud_save();
    }

    public function delete() {
        global $DB;

        $sessions = facetoface_get_sessions($this->id);
        foreach ($sessions as $session) {
            facetoface_delete_session($session);
        }

        $seminarinterests = new interest_list(['facetoface' => $this->get_id()]);
        $seminarinterests->delete();

        $notifications = $DB->get_records('facetoface_notification', ['facetofaceid' => $this->get_id()], '', 'id');
        foreach ($notifications as $notification) {
            $notification = new \facetoface_notification(['id' => $notification->id]);
            $notification->delete();
        }

        $seminarevents = seminar_event_list::from_seminar($this);
        $seminarevents->delete();

        $DB->delete_records('event', array('modulename' => 'facetoface', 'instance' => $this->get_id()));

        $this->grade_item_delete();

        $DB->delete_records(self::DBTABLE, ['id' => $this->id]);

        // Re-load instance with default values.
        $this->map_object((object)get_object_vars(new self()));
    }

    /**
     * Get seminar events
     * @return seminar_event_list
     */
    public function get_events() : seminar_event_list {
        return seminar_event_list::from_seminar($this);
    }

    /**
     * Delete grade item for given facetoface
     *
     * @param object $facetoface object
     * @return object facetoface
     */
    private function grade_item_delete() {
        grade_update('mod/facetoface', $this->course, 'mod', 'facetoface', $this->id, 0, NULL, ['deleted' => 1]);
    }

    /**
     * Does this seminar require approval of any kind
     * Notice: If seminar required approval, it doesn't mean that signup will require approval, use state of signup to determine it
     * @return bool
     */
    public function is_approval_required(): bool {
        return $this->approvaltype == static::APPROVAL_MANAGER
        || $this->approvaltype == static::APPROVAL_ROLE
        || $this->approvaltype == static::APPROVAL_ADMIN;
    }

    /**
     * Check if current seminar approval settings require manager or admin approval.
     * @return bool
     */
    public function is_manager_required() : bool {
        return $this->approvaltype == static::APPROVAL_MANAGER || $this->approvaltype == static::APPROVAL_ADMIN;
    }

    /**
     * Check if current seminar approval settings require role approval.
     * @return bool
     */
    public function is_role_required() : bool {
        return $this->approvaltype == static::APPROVAL_ROLE;
    }

    /**
     * Map data object to seminar instance.
     *
     * @param \stdClass $object
     * @return seminar instance
     */
    public function map_instance(\stdClass $object) : seminar {

        return $this->map_object($object);
    }

    /**
     * Map seminar instance properties to data object.
     *
     * @return \stdClass
     */
    public function get_properties() : \stdClass {

        return $this->unmap_object();
    }

    /**
     * Check whether the seminar exists yet or not.
     * If the asset has been saved into the database the $id field should be non-zero.
     *
     * @return bool - true if the asset has an $id, false if it hasn't
     */
    public function exists() : bool {
        return !empty($this->id);
    }

    /** Check if the user has any signups that don't have any of the following
     *     not being archived
     *     cancelled by user
     *     declined
     *     session cancelled
     *     status not set
     *
     * @param int $userid
     * @return bool
     */
    public function has_unarchived_signups(int $userid = 0) : bool {
        global $DB, $USER;

        $userid = $userid == 0 ? $USER->id : $userid;

        $sql  = "SELECT 1 FROM {facetoface_signups} ftf_sign
               JOIN {facetoface_sessions} sess
                    ON sess.facetoface = :facetofaceid
               JOIN {facetoface_signups_status} sign_stat
                    ON sign_stat.signupid = ftf_sign.id
                    AND sign_stat.superceded <> 1
              WHERE ftf_sign.userid = :userid
                AND ftf_sign.sessionid = sess.id
                AND ftf_sign.archived <> 1
                AND sign_stat.statuscode > :statusdeclined
                AND sign_stat.statuscode <> :statusnotset";
        $params = [
            'facetofaceid' => $this->id,
            'userid' => $userid,
            'statusdeclined' => declined::get_code(),
            'statusnotset' => not_set::get_code()
        ];

        // Check if user is already signed up to a session in the facetoface and it has not been archived.
        return $DB->record_exists_sql($sql, $params);
    }

    /**
     * Get list of approval admins for current seminar
     * @return array
     */
    public function get_approvaladmins_list() : array {
        return explode(',', $this->get_approvaladmins());
    }

    /**
     * @return int
     */
    public function get_id() : int {
        return (int)$this->id;
    }

    /**
     * Get course id
     * There is no course class, so use id
     * @return int
     */
    public function get_course(): int {
        return (int)$this->course;
    }
    /**
     * Set course id
     * There is no course class, so use id
     * @param int $course
     */
    public function set_course(int $course) : seminar {
        $this->course = $course;
        return $this;
    }
    /**
     * @return string
     */
    public function get_name(): string {
        return $this->name;
    }
    /**
     * @param string $name
     */
    public function set_name(string $name) : seminar {
        $this->name = $name;
        return $this;
    }

    /**
     * @return string
     */
    public function get_intro(): string {
        return (string)$this->intro;
    }
    /**
     * @param string $intro
     */
    public function set_intro(string $intro) : seminar {
        $this->intro = $intro;
        return $this;
    }

    /**
     * @return int
     */
    public function get_introformat(): int {
        return (int)$this->introformat;
    }
    /**
     * @param int $introformat
     */
    public function set_introformat(int $introformat) : seminar {
        $this->introformat = $introformat;
        return $this;
    }

    /**
     * @return string
     */
    public function get_thirdparty(): string {
        return (string)$this->thirdparty;
    }
    /**
     * @param string $thirdparty
     */
    public function set_thirdparty(string $thirdparty) : seminar {
        $this->thirdparty = $thirdparty;
        return $this;
    }

    /**
     * @return int
     */
    public function get_thirdpartywaitlist(): int {
        return (int)$this->thirdpartywaitlist;
    }
    /**
     * @param string $thirdpartywaitlist
     */
    public function set_thirdpartywaitlist(string $thirdpartywaitlist) : seminar {
        $this->thirdpartywaitlist = $thirdpartywaitlist;
        return $this;
    }

    /**
     * @return bool
     */
    public function get_waitlistautoclean(): bool {
        return (bool)$this->waitlistautoclean;
    }
    /**
     * @param bool $waitlistautoclean
     */
    public function set_waitlistautoclean(bool $waitlistautoclean) : seminar {
        $this->waitlistautoclean = (int) $waitlistautoclean;
        return $this;
    }

    /**
     * @return int
     */
    public function get_display(): int {
        return (int)$this->display;
    }
    /**
     * @param int $display
     */
    public function set_display(int $display) : seminar {
        $this->display = $display;
        return $this;
    }

    /**
     * @return int
     */
    public function get_timecreated(): int {
        return (int)$this->timecreated;
    }
    /**
     * @param int $timecreated
     */
    public function set_timecreated(int $timecreated) : seminar {
        $this->timecreated = $timecreated;
        return $this;
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
    public function set_timemodified(int $timemodified) : seminar {
        $this->timemodified = $timemodified;
        return $this;
    }

    /**
     * @return string
     */
    public function get_shortname(): string {
        return (string)$this->shortname;
    }
    /**
     * @param string $shortname
     */
    public function set_shortname(string $shortname) : seminar {
        $this->shortname = $shortname;
        return $this;
    }

    /**
     * @return int
     */
    public function get_showoncalendar(): int {
        return (int)$this->showoncalendar;
    }
    /**
     * @param int $showoncalendar
     */
    public function set_showoncalendar(int $showoncalendar) : seminar {
        $this->showoncalendar = $showoncalendar;
        return $this;
    }

    /**
     * @return int
     */
    public function get_usercalentry(): int {
        return (int)$this->usercalentry;
    }
    /**
     * @param int $usercalentry
     */
    public function set_usercalentry(int $usercalentry) : seminar {
        $this->usercalentry = $usercalentry;
        return $this;
    }

    /**
     * Note: saved in the database as multiplesessions,
     *       referred to elsewhere as multiplesignups.
     * @return int
     */
    public function get_multiplesessions(): int {
        return (int)$this->multiplesessions;
    }
    /**
     * Note: saved in the database as multiplesessions,
     *       referred to elsewhere as multiplesignups.
     * @param int $multiplesessions
     */
    public function set_multiplesessions(int $multiplesignups) : seminar {
        $this->multiplesessions = $multiplesignups;
        return $this;
    }

    /**
     * Group all the state restrictions settings into one array
     * @return []
     */
    public function get_multisignup_states() : array {
        $states = [];

        if (!empty($this->multisignupfully)) {
            $states[fully_attended::get_code()] = fully_attended::class;
        }

        if (!empty($this->multisignuppartly)) {
            $states[partially_attended::get_code()] = partially_attended::class;
        }

        if (!empty($this->multisignupnoshow)) {
            $states[no_show::get_code()] = no_show::class;
        }

        return $states;
    }

    public function get_multisignup_maximum() : int {
        return $this->multisignupmaximum;
    }

    /**
     * @param int $multisignupfully
     * @return this
     */
    public function set_multisignupfully(bool $multisignupfully) : seminar {
        $this->multisignupfully = (int)$multisignupfully;
        return $this;
    }

    /**
     * @param int $multisignuppartly
     * @return this
     */
    public function set_multisignuppartly(bool $multisignuppartly) : seminar {
        $this->multisignuppartly = (int)$multisignuppartly;
        return $this;
    }

    /**
     * @param int $multisignupnoshow
     * @return this
     */
    public function set_multisignupnoshow(bool $multisignupnoshow) : seminar {
        $this->multisignupnoshow = (int)$multisignupnoshow;
        return $this;
    }

    /**
     * @param int $multisignupmaximum
     * @return this
     */
    public function set_multisignupmaximum(int $multisignupmaximum) : seminar {
        $this->multisignupmaximum = $multisignupmaximum;
        return $this;
    }

    /**
     * @return string
     */
    public function get_completionstatusrequired(): string {
        return (string)$this->completionstatusrequired;
    }
    /**
     * @param string $completionstatusrequired
     */
    public function set_completionstatusrequired(string $completionstatusrequired) : seminar {
        $this->completionstatusrequired = $completionstatusrequired;
        return $this;
    }

    /**
     * @return int
     */
    public function get_managerreserve(): int {
        return (int)$this->managerreserve;
    }
    /**
     * @param int $managerreserve
     */
    public function set_managerreserve(int $managerreserve) : seminar {
        $this->managerreserve = $managerreserve;
        return $this;
    }

    /**
     * @return int
     */
    public function get_maxmanagerreserves(): int {
        return (int)$this->maxmanagerreserves;
    }
    /**
     * @param int $maxmanagerreserves
     */
    public function set_maxmanagerreserves(int $maxmanagerreserves) : seminar {
        $this->maxmanagerreserves = $maxmanagerreserves;
        return $this;
    }

    /**
     * @return int
     */
    public function get_reservecanceldays(): int {
        return (int)$this->reservecanceldays;
    }
    /**
     * @param int $reservecanceldays
     */
    public function set_reservecanceldays(int $reservecanceldays) : seminar {
        $this->reservecanceldays = $reservecanceldays;
        return $this;
    }

    /**
     * @return int
     */
    public function get_reservedays(): int {
        return (int)$this->reservedays;
    }
    /**
     * @param int $reservedays
     */
    public function set_reservedays(int $reservedays) : seminar {
        $this->reservedays = $reservedays;
        return $this;
    }

    /**
     * @return int
     */
    public function get_declareinterest(): int {
        return (int)$this->declareinterest;
    }
    /**
     * @param int $declareinterest
     */
    public function set_declareinterest(int $declareinterest) : seminar {
        $this->declareinterest = $declareinterest;
        return $this;
    }

    /**
     * @return int
     */
    public function get_interestonlyiffull(): int {
        return (int)$this->interestonlyiffull;
    }
    /**
     * @param int $interestonlyiffull
     */
    public function set_interestonlyiffull(int $interestonlyiffull) : seminar {
        $this->interestonlyiffull = $interestonlyiffull;
        return $this;
    }

    /**
     * @return int
     */
    public function get_allowcancellationsdefault(): int {
        return (int)$this->allowcancellationsdefault;
    }
    /**
     * @param int $allowcancellationsdefault
     */
    public function set_allowcancellationsdefault(int $allowcancellationsdefault) : seminar {
        $this->allowcancellationsdefault = $allowcancellationsdefault;
        return $this;
    }

    /**
     * @return int
     */
    public function get_cancellationscutoffdefault(): int {
        return (int)$this->cancellationscutoffdefault;
    }
    /**
     * @param int $cancellationscutoffdefault
     */
    public function set_cancellationscutoffdefault(int $cancellationscutoffdefault) : seminar {
        $this->cancellationscutoffdefault = $cancellationscutoffdefault;
        return $this;
    }

    /**
     * @return int
     */
    public function get_selectjobassignmentonsignup(): int {
        return (int)$this->selectjobassignmentonsignup;
    }
    /**
     * @param int $selectjobassignmentonsignup
     */
    public function set_selectjobassignmentonsignup(int $selectjobassignmentonsignup) : seminar {
        $this->selectjobassignmentonsignup = $selectjobassignmentonsignup;
        return $this;
    }

    /**
     * @return int
     */
    public function get_forceselectjobassignment(): int {
        return (int)$this->forceselectjobassignment;
    }
    /**
     * @param int $forceselectjobassignment
     */
    public function set_forceselectjobassignment(int $forceselectjobassignment) : seminar {
        $this->forceselectjobassignment = $forceselectjobassignment;
        return $this;
    }

    /**
     * @return int
     */
    public function get_approvaltype(): int {
        return (int)$this->approvaltype;
    }
    /**
     * @param int $approvaltype
     */
    public function set_approvaltype(int $approvaltype) : seminar {
        $this->approvaltype = $approvaltype;
        return $this;
    }

    /**
     * @return int
     */
    public function get_approvalrole(): int {
        return (int)$this->approvalrole;
    }
    /**
     * @param int $approvalrole
     */
    public function set_approvalrole(int $approvalrole) : seminar {
        $this->approvalrole = $approvalrole;
        return $this;
    }

    /**
     * @return string
     */
    public function get_approvalterms(): string {
        return (string)$this->approvalterms;
    }
    /**
     * @param string $approvalterms
     */
    public function set_approvalterms(string $approvalterms) : seminar {
        $this->approvalterms = $approvalterms;
        return $this;
    }

    /**
     * @return string
     */
    public function get_approvaladmins(): string {
        return (string)$this->approvaladmins;
    }
    /**
     * @param string $approvaladmins
     */
    public function set_approvaladmins(string $approvaladmins) : seminar {
        $this->approvaladmins = $approvaladmins;
        return $this;
    }
}
