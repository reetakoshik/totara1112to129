<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @author Alastair Munro <alastair.munro@totaralearning.com>
 * @package totara_program
 */

namespace totara_program\assignment;

class base {

    protected $id = 0;
    protected $programid;
    protected $typeid;
    protected $instanceid;

    protected $name = '';
    protected $completionevent = 0;
    protected $completioninstance = 0;
    protected $completiontime = -1;
    protected $includechildren = 0;

    protected $program = null;
    protected $category = null;

    protected function __construct(int $id = 0) {
        global $DB;

        // Load from DB
        if ($id !== 0) {
            $record = $DB->get_record('prog_assignment', ['id' => $id], '*', MUST_EXIST);

            // Load into object
            $this->id = $record->id;
            $this->programid = $record->programid;
            $this->includechildren = $record->includechildren;
            $this->completiontime = $record->completiontime;
            $this->completionevent = $record->completionevent;
            $this->completioninstance = $record->completioninstance;

            $this->typeid = $record->assignmenttype;
            $this->instanceid = $record->assignmenttypeid;
        }
    }

    /**
     * Load program object for this assignment
     */
    private function ensure_program_loaded() {
        global $CFG;

        require_once($CFG->dirroot . '/totara/program/program.class.php');

        if ($this->program === null) {
            $this->program = new \program($this->programid);
        }
    }

    /**
     * Load category for the assignment
     */
    private function ensure_category_loaded() {
        global $CFG;

        require_once($CFG->dirroot . '/totara/program/program_assignments.class.php');

        if ($this->category === null) {
            $this->category = \prog_assignments::factory($this->typeid);
        }
    }

    /**
     * Create a new assignment given an instance id
     *
     * @param int $programid
     * @param int $type
     * @param int $instanceid
     */
    public static function create_from_instance_id(int $programid, int $typeid, int $instanceid): base {

        $types = helper::get_types();
        $class = $types[$typeid];
        $classpath = '\\totara_program\\assignment\\' . $class;
        $assignment = new $classpath();

        $assignment->instanceid = $instanceid;
        $assignment->typeid = $typeid;
        $assignment->programid = $programid;

        // Set the name of the assignment
        $assignment->name = $assignment->get_name();

        return $assignment;
    }

    /**
     * Create instances of assignment given an assignemnt id
     *
     * @param int $id Id of program assignment
     *
     * @return base $assignment
     */
    public static function create_from_id(int $id): base {
        global $DB;

        // Get record
        $record = $DB->get_record('prog_assignment', ['id' => $id], '*', MUST_EXIST);
        $types = helper::get_types();
        $class = $types[$record->assignmenttype];
        $classpath = '\\totara_program\\assignment\\' . $class;

        // Create instance of correct type for this assignment
        $assignment = new $classpath($id);

        return $assignment;
    }


    /**
     * Create from object (database record). Must contain
     * id and assignmenttype properties
     *
     * @param \stdClass $record
     *
     * @return base Instance of assignment
     */
    public static function create_from_record(\stdClass $record): base {
        $types = helper::get_types();
        $class = $types[$record->assignmenttype];
        $classpath = '\\totara_program\\assignment\\' . $class;

        $assignment = new $classpath($record->id);

        return $assignment;
    }

    /**
     * Get type for this assignment
     */
    public function get_type(): int {
        return $this->typeid;
    }

    public function get_name(): string {
        return $this->name;
    }

    public function get_programid(): int {
        return $this->programid;
    }

    public function get_includechildren(): int {
        return $this->includechildren;
    }

    public function get_id(): int {
        return $this->id;
    }

    /**
     * Set include children value
     *
     * @param bool
     */
    public function set_includechildren(bool $value) {
        global $DB;

        $this->includechildren = $value;
        $this->save();

        if ($this->includechildren == 1) {
            $this->create_user_assignment_records();
            if ($this->completiontime !== -1) {
                // If a completion time has been set then all new users need to be updated.
                $this->set_duedate($this->completiontime, $this->completionevent, $this->completioninstance);
            }
        } else {
            $this->ensure_program_loaded();

            // Figure out which ones are not part of parent...
            $children = $this->get_children();

            if (!empty($children)) {
                list($insql, $inparams) = $DB->get_in_or_equal($children, SQL_PARAMS_NAMED);
                $params = array_merge(['programid' => $this->programid, 'assignid' => $this->id], $inparams);
                $DB->delete_records_select('prog_user_assignment', "programid = :programid AND assignmentid = :assignid AND userid $insql", $params);

                // Check to see if user is still assigned by another method
                // if they aren't then delete prog_completion (and maybe certif_completion?)
                // for this program/certification
                foreach ($children as $userid) {
                    $isassigned = $this->program->user_is_assigned($userid);
                    if (!$isassigned) {
                        $DB->delete_records('prog_completion', ['programid' => $this->programid, 'userid' => $userid]);

                        if (!empty($this->program->certifid)) {
                            // Delete certification completion records
                            $DB->delete_records('certif_completion', ['certifid' => $this->program->certifid, 'userid' => $userid]);
                        }
                    }
                }
            }
        }
    }

    /**
     * Get the duedate string given the due date
     * Note: This logic is copied from the get_completion function
     * in prog_assignment_category class
     *
     * @return \stdClass
     */
    public function get_duedate(): \stdClass {
        global $CFG;

        require_once($CFG->dirroot . '/totara/program/program_assignments.class.php');

        $canupdate = helper::can_update($this->programid);

        $completiondate = new \stdClass();

        if ($canupdate) {
            $completiondate->string = '';
        } else {
            $completiondate->string = get_string('noduedate', 'totara_program');
        }

        $completiondate->changeable = true;

        if (empty($this->completiontime)) {
            $this->completiontime = COMPLETION_TIME_NOT_SET;
        }

        if (!isset($this->completionevent)) {
            $this->completionevent = 0;
        }

        if (!isset($this->completioninstance)) {
            $this->completioninstance = 0;
        }

        if ($this->completionevent == COMPLETION_EVENT_NONE) {
            // Completiontime must be a timestamp.
            if ($this->completiontime != COMPLETION_TIME_NOT_SET) {
                $completiondate->string = self::get_completion_string();
                $completiondate->changeable = true;
            }
        } else {
            $completiondate->string = self::get_completion_string();
            $completiondate->changeable = true;
        }

        return $completiondate;
    }

    /**
     * Can user update due date
     * true by default
     *
     * Note: This doesn't check users permission, rather determines if
     * the date should be updatable given the program/certification
     * assignment state.
     *
     * @return bool
     */
    public function can_update_date(): bool {
        return true;
    }

    /**
     * Get actual duedate null by default
     */
    public function get_actual_duedate() {
        if (!helper::can_update($this->programid)) {
            return get_string('noduedate', 'totara_program');
        }
        return null;
    }

    /**
     * Get count of user for this assignment
     *
     * @return int
     */
    public function get_user_count(): int {
        return 0;
    }

    /**
     * Gets list of children for an assignment type
     */
    public function get_children(): array {
        return [];
    }

    /**
     * Create or update prog_assignment record
     * If a user doesn't have permission it will not update
     *
     * @return bool
     */
    public function save(): bool {
        global $DB;

        $canupdate = helper::can_update($this->programid);
        if (!$canupdate) {
            return false;
        }

        if ($this->id === 0) {
            // Prevent duplicate assignments being added to DB
            $params = ['programid' => $this->programid, 'assignmenttype' => $this->typeid, 'assignmenttypeid' => $this->instanceid];
            $alreadyexists = $DB->record_exists('prog_assignment', $params);

            if ($alreadyexists) {
                return false;
            }

            // Create new prog_assignment record
            $data = new \stdClass();
            $data->programid = $this->programid;
            $data->assignmenttype = $this->typeid;
            $data->assignmenttypeid = $this->instanceid;
            $data->includechildren = (int)$this->includechildren;
            $data->completionevent = $this->completionevent;
            $data->completioninstance = $this->completioninstance;
            $data->completiontime = $this->completiontime;

            $assignmentid = $DB->insert_record('prog_assignment', $data);

            // Set the assignment id now we have one
            $this->id = $assignmentid;

            // For new assignment we need to create
            // prog_completion and prog_user_assignment records
            $this->create_user_assignment_records();
        } else {
            // Update existing.
            $data = new \stdClass();

            $data->id = $this->id;
            $data->includechildren = (int)$this->includechildren;
            $data->completionevent = $this->completionevent;
            $data->completioninstance = $this->completioninstance;
            $data->completiontime = $this->completiontime;

            $DB->update_record('prog_assignment', $data);
        }

        return true;
    }

    /**
     * Remove program assignment
     *
     * @return bool
     */
    public function remove(): bool {
        global $DB;

        $canupdate = helper::can_update($this->programid);
        if (!$canupdate) {
            return false;
        }

        $this->ensure_category_loaded();
        $this->ensure_program_loaded();

        // Dummy assignment object
        $assignment = new \stdClass();
        $assignment->id = $this->id;
        $assignment->assignmenttypeid = $this->instanceid;

        $users = $this->category->get_affected_users_by_assignment($assignment);

        if (count($users) > PROG_UPDATE_ASSIGNMENTS_DEFER_COUNT) {
            $DB->set_field('prog', 'assignmentsdeferred', 1, ['id' => $this->programid]);
            $DB->delete_records('prog_assignment', ['id' => $this->id]);

            return true;
        }

        // IDs of all users assigned via this assignment
        $userids = array_map(function($o) { return $o->id; }, $users);

        // Get array of users that are still assigned
        $sql = "SELECT id, userid FROM {prog_user_assignment}
                 WHERE programid = :programid
                 AND assignmentid != :assignmentid";
        $otherassignmentusers = $DB->get_records_sql_menu($sql, ['programid' => $this->programid, 'assignmentid' => $this->get_id()]);

        foreach ($userids as $id => $userid) {
            if (in_array($userid, $otherassignmentusers)) {
                // Remove prog_user_assignment record
                $DB->delete_records('prog_user_assignment', ['programid' => $this->programid, 'userid' => $userid, 'assignmentid' => $this->get_id()]);

                // Remove user from removed from the program
                unset($userids[$id]);
            }
        }

        // Remove all learners from the assignment
        $this->program->unassign_learners($userids);

        // Delete the assignment itself
        $DB->delete_records('prog_assignment', ['id' => $this->id]);

        return true;
    }


    /**
     * Set the due date for the assignment
     *
     * @param int $duedate
     * @param int $completionevent
     * @param int $completioninstance
     */
    public function set_duedate(int $duedate, int $completionevent = 0, int $completioninstance = 0) {
        global $DB, $CFG;

        $canupdate = helper::can_update($this->programid);
        if (!$canupdate) {
            return false;
        }

        require_once($CFG->dirroot . '/totara/program/lib.php');

        $this->completiontime = $duedate;
        $this->completionevent = $completionevent;
        $this->completioninstance = $completioninstance;

        // Update completion record for all users in this assignment
        // - Get all users
        // - Loop through users
        // - Check for exceptions
        // - Set completion
        $this->ensure_category_loaded();
        $this->ensure_program_loaded();

        $assignment = new \stdClass();
        $assignment->id = $this->id;
        $assignment->assignmenttypeid = $this->instanceid;
        $assignment->completionevent = $this->completionevent;
        $assignment->completiontime = $this->completiontime;
        $assignment->completioninstance = $this->completioninstance;

        $users = $this->category->get_affected_users_by_assignment($assignment);

        if (count($users) > PROG_UPDATE_ASSIGNMENTS_DEFER_COUNT) {
            // Save prog_assignment and update deferred flag
            $this->save();
            $DB->set_field('prog', 'assignmentsdeferred', 1, ['id' => $this->program->id]);
        }

        $futureassignments = [];
        $updateassignusersbuffer = [];
        $newassignusersbuffer = [];

        foreach ($users as $user) {
            $params = ['programid' => $this->programid, 'userid' => $user->id, 'certifid' => $this->program->certifid];
            $sql = "SELECT pc.userid, pc.timedue, pc.status, cc.certifpath, cc.status AS certifstatus
                FROM {prog_completion} pc
                LEFT JOIN {certif_completion} cc ON cc.certifid = :certifid AND pc.userid = cc.userid
                WHERE pc.programid = :programid AND pc.coursesetid = 0 AND pc.userid = :userid";
            $completionrecord = $DB->get_record_sql($sql, $params);
            $userassignment = $DB->get_record('prog_user_assignment', ['assignmentid' => $this->id, 'userid' => $user->id]);

            // Get the new timedue for completion event
            $timedue = $completionrecord ? $completionrecord->timedue : false;
            // Time due should be the actual timestamp if we can calculate it
            // not the interval of the relative event!!
            $timedue = $this->program->make_timedue($user->id, $assignment, $timedue);

            if ($completionevent == COMPLETION_EVENT_FIRST_LOGIN && $timedue == false) {
                // Add to future assignment list
                $futureassignments[$user->id] = $user->id;
                continue;
            }

            if (!empty($userassignment)) {
                $sendmessage = false;
                // Update user assignment record
                if (empty($completionrecord)) {
                    // This is bad and shouldn't happen
                    continue;
                }

                if (!empty($userassignment->programexceptionid) &&
                    $userassignment->programexceptiontimeraised == $userassignment->timeassigned) {
                    // This exception was raised the first time they were assigned, meaning they haven't received
                    // an assignment message yet.
                    $sendmessage = true;
                }

                // Skip completed programs (includes certifications which are certified and window is not yet open).
                if ($completionrecord->status == STATUS_PROGRAM_COMPLETE) {
                    continue;
                }

                // Skip certifications which are on the recert path or are expired.
                if (!empty($this->program->certifid)) {
                    if ($completionrecord->certifpath == CERTIFPATH_RECERT ||
                        $completionrecord->certifstatus == CERTIFSTATUS_EXPIRED) {
                        continue;
                    }
                }

                // Make sure that the exceptionstatus property is present (protection against a previous bug).
                if (!isset($userassignment->exceptionstatus)) {
                    throw new \coding_exception('The property "exceptionstatus" is missing.');
                }

                if ($timedue > $completionrecord->timedue || ($timedue === false && (int)$completionrecord->timedue === -1)) {
                    // The timedue has increased, we'll need to update it and check for exceptions.

                    // If it currently has no timedue then we need to check exceptions.
                    // If there was a previously unresolved or dismissed exception then we need to recheck.
                    if ($completionrecord->timedue <= 0 ||
                        in_array($userassignment->exceptionstatus, array(PROGRAM_EXCEPTION_RAISED, PROGRAM_EXCEPTION_DISMISSED))) {
                        if ($this->program->update_exceptions($user->id, $assignment, $timedue)) {
                            $userassignment->exceptionstatus = PROGRAM_EXCEPTION_RAISED;
                        } else {
                            $userassignment->exceptionstatus = PROGRAM_EXCEPTION_NONE;
                        }
                        if ($userassignment->exceptionstatus == PROGRAM_EXCEPTION_RAISED) {
                            // Store raised exception status (was reset by update_exceptions).
                            $updateduserassignment = new \stdClass();
                            $updateduserassignment->id = $userassignment->id;
                            $updateduserassignment->exceptionstatus = PROGRAM_EXCEPTION_RAISED;
                            $DB->update_record('prog_user_assignment', $updateduserassignment);
                        }
                    }

                    // Update user's due date.
                    $completionrecord->timedue = $timedue; // Updates $allpreviousprogcompletions, for following assignments.
                    $this->program->set_timedue($user->id, $timedue, 'Due date updated for existing program assignment');

                    if ($userassignment->exceptionstatus == PROGRAM_EXCEPTION_NONE && $sendmessage) {
                        // Trigger event for observers to deal with resolved exception from first assignment.
                        // We don't add this to the new assignments buffer because we're not creating a new assignment.
                        $updateassignusersbuffer[$user->id] = 0;
                    }
                } // Else no change or decrease, skipped. If we want to allow decrease then it should be added here.

            } else {
                // If the user is already complete, or has a timedue, skip checking for time allowance exceptions and carry on with assignments.
                if (!empty($completionrecord) && ($completionrecord->status == STATUS_PROGRAM_COMPLETE || $completionrecord->timedue > 0)) {
                    $exceptions = $this->program->update_exceptions($user->id, $assignment, COMPLETION_TIME_NOT_SET);
                } else {
                    $exceptions = $this->program->update_exceptions($user->id, $assignment, $timedue);
                }

                // Fix the timedue before we put it into the database. Empty includes COMPLETION_TIME_UNKNOWN, null, 0, ''.
                $timedue = empty($timedue) ? COMPLETION_TIME_NOT_SET : $timedue;
                $newassignusersbuffer[$user->id] = array('timedue' => $timedue, 'exceptions' => $exceptions);

                if (empty($completionrecord)) {
                    // Maybe not needed as we are just setting duedates
                    $newassignusersbuffer[$user->id]['needscompletionrecord'] = true;
                } else if ($timedue > $completionrecord->timedue) {
                    // Update user's due date.
                    $this->program->set_timedue($user->id, $timedue, 'Due date updated for new program assignment');
                }
            }
        }

        $context = \context_program::instance($this->program->id); // Used for events.

        // Flush future user assignments after program assignment loop finished.
        if (!empty($futureassignments)) {
            $eventdata = array('other' => array('programid' => $this->program->id, 'assignmentid' => $this->id));
            \totara_program\event\bulk_future_assignments_started::create_from_data($eventdata)->trigger();

            $this->program->create_future_assignments_bulk($this->program->id, $futureassignments, $this->id);

            // Trigger each individual event.
            foreach ($futureassignments as $userid) {
                $event = \totara_program\event\program_future_assigned::create(
                    array(
                        'objectid' => $this->program->id,
                        'context' => $context,
                        'userid' => $userid,
                    )
                );
                $event->trigger();
            }

            \totara_program\event\bulk_future_assignments_ended::create()->trigger();
            unset($futureassignments);
        }

        // Flush new user assignments after program assignment loop finished.
        if (!empty($newassignusersbuffer) || !empty($updateassignusersbuffer)) {
            $eventdata = array('other' => array('programid' => $this->program->id, 'assignmentid' => $this->id));
            \totara_program\event\bulk_learner_assignments_started::create_from_data($eventdata)->trigger();

            // We need to do this after every program assignment so that the records will exist and be updated in case
            // the same user is present in a following assignment.
            if (!empty($newassignusersbuffer)) {
                $this->program->assign_learners_bulk($newassignusersbuffer, $assignment);
            }

            // Both new and updated user assignments need to trigger the program_assigned event (note "+" to preserve keys).
            $allassignusers = $newassignusersbuffer + $updateassignusersbuffer;

            // Trigger each individual event.
            // If this is a certification, certification_event_handler creates the certif_completion records.
            foreach ($allassignusers as $userid => $data) {
                $event = \totara_program\event\program_assigned::create(
                    array(
                        'objectid' => $this->program->id,
                        'context' => $context,
                        'userid' => $userid,
                    )
                );
                $event->trigger();
            }

            \totara_program\event\bulk_learner_assignments_ended::create()->trigger();

            // Update completion of all just-assigned users. This will mark them complete if they have already completed
            // all program content, or else create the first non-0 course set record (with course set group timedue).
            foreach ($allassignusers as $userid => $data) {
                prog_update_completion($userid, $this->program);
            }

            unset($newassignusersbuffer);
        }

        // Update prog_assignment record
        $this->save();
    }

    /**
     * Get date string given the event
     *
     * @return String
     */
    private function get_completion_string() {
        global $CFG;

        require_once($CFG->dirroot . '/totara/program/program_assignments.class.php');
        require_once($CFG->dirroot . '/totara/program/program.class.php');

        // This has to be done after including the program assignments class.
        global $COMPLETION_EVENTS_CLASSNAMES;

        if ((int)$this->completionevent !== COMPLETION_EVENT_NONE) {
            $class = $COMPLETION_EVENTS_CLASSNAMES[$this->completionevent];

            $eventobject = new $class;

            if ($this->completiontime > 0) {
                $relative_completion = \program_utilities::get_duration_num_and_period($this->completiontime);
            }

            $a = new \stdClass();
            $a->num = $relative_completion->num;
            if (isset($relative_completion->periodkey)) {
                $a->period = get_string($relative_completion->periodkey, 'totara_program');
            } else {
                return '';
            }
            $a->event = $eventobject->get_completion_string();
            $a->instance = $eventobject->get_item_name($this->completioninstance);

            if (!empty($a->instance)) {
                $a->instance = "'$a->instance'";
            }

            $date_string = get_string('completewithinevent', 'totara_program', $a);
        } else {
            if ($this->completiontime == COMPLETION_TIME_NOT_SET) {
                $date_string = '';
            } else {
                $timestamp = $this->completiontime;
                $completiontimestring = userdate($timestamp, get_string('strfdateattime', 'langconfig'), 99);

                $date_string = get_string('completebytime', 'totara_program', $completiontimestring);
            }
        }

        return $date_string;
    }

    /**
     * Create prog_completion records for new assignments
     * no due dates are set when we first create assignment records
     * so the logic is quite simple.
     *
     */
    private function create_user_assignment_records() {
        global $DB;

        // Create a dummy assignment object to use in this function.
        $progassignment = new \stdClass();
        $progassignment->id = $this->id;
        $progassignment->assignmenttypeid = $this->instanceid;
        $progassignment->completionevent = $this->completionevent;
        $progassignment->completiontime = $this->completiontime;
        $progassignment->includechildren = $this->includechildren;
        $progassignment->timedue = -1; // No due time for new records

        $this->ensure_category_loaded();
        $this->ensure_program_loaded();

        // Get users who are affected by this assignment
        $affectedusers = $this->category->get_affected_users_by_assignment($progassignment);
        if (count($affectedusers) == 0) {
            // Nothing to do
            return;
        } else if (count($affectedusers) > PROG_UPDATE_ASSIGNMENTS_DEFER_COUNT) {
            // Set deferred and return
            $DB->set_field('prog', 'assignmentsdeferred', 1, array('id' => $this->program->id));
            return;
        }

        // Get array of userids
        $affecteduserids = array_map(function($o) { return $o->id; }, $affectedusers);

        // Find out if users already have a completion record for this
        // program (via another assignment type)
        list($insql, $params) = $DB->get_in_or_equal($affecteduserids, SQL_PARAMS_NAMED, 'param', false);
        $sql = "SELECT userid FROM {prog_completion} WHERE programid = :programid AND coursesetid = 0";
        $params = ['programid' => $this->programid];
        $existingcompletion = $DB->get_records_sql($sql, $params);
        $existinguserids = array_map(function($o) { return $o->userid; }, $existingcompletion);

        // Calculate who needs completion records
        $requiredusers = array_diff($affecteduserids, $existinguserids);

        // Timedue is not set for new records.
        $timedue = -1;

        $users = [];
        $existing_user_assignments = $DB->get_records('prog_user_assignment', ['programid' => $this->programid, 'assignmentid' => $this->id], '', 'userid');

        foreach ($affecteduserids as $userid) {
            if (!empty($existing_user_assignments[$userid])) {
                // Existing user assignments so skip
                continue;
            }

            $exceptions = $this->program->update_exceptions($userid, $progassignment, $timedue);

            if (in_array($userid, $requiredusers)) {
                $users[$userid] = ['timedue' => $timedue, 'exceptions' => $exceptions, 'needscompletionrecord' => true];
            } else {
                $users[$userid] = ['timedue' => $timedue, 'exceptions' => $exceptions];
            }
        }

        unset($existing_user_assignments);

        // Create completion and user_assignment records
        $this->program->assign_learners_bulk($users, $progassignment);

        // Load context for event trigger
        $context = \context_program::instance($this->programid); // Used for events.

        // Must be run after assigning learners
        foreach ($affecteduserids as $userid) {
            // Trigger event to create completion records for certifications
            $event = \totara_program\event\program_assigned::create(
                array(
                    'objectid' => $this->programid,
                    'context' => $context,
                    'userid' => $userid,
                )
            );
            $event->trigger();
        }
    }
}
