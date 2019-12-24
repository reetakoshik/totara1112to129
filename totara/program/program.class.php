<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2010 onwards Totara Learning Solutions LTD
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
 * @author Ben Lobo <ben.lobo@kineo.com>
 * @package totara
 * @subpackage program
*/

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

require_once($CFG->dirroot . '/totara/program/program_content.class.php');
require_once($CFG->dirroot . '/totara/program/program_courseset.class.php');
require_once($CFG->dirroot . '/totara/program/program_assignments.class.php');
require_once($CFG->dirroot . '/totara/program/program_messages.class.php');
require_once($CFG->dirroot . '/totara/program/program_message.class.php');
require_once($CFG->dirroot . '/totara/program/program_exceptions.class.php');
require_once($CFG->dirroot . '/totara/program/program_exception.class.php');
require_once($CFG->dirroot . '/totara/program/program_user_assignment.class.php');
require_once($CFG->dirroot . '/totara/program/lib.php');

define('STATUS_PROGRAM_INCOMPLETE', 0);
define('STATUS_PROGRAM_COMPLETE', 1);
define('STATUS_COURSESET_INCOMPLETE', 2);
define('STATUS_COURSESET_COMPLETE', 3);

define('TIME_SELECTOR_HOURS', 1);
define('TIME_SELECTOR_DAYS', 2);
define('TIME_SELECTOR_WEEKS', 3);
define('TIME_SELECTOR_MONTHS', 4);
define('TIME_SELECTOR_YEARS', 5);
define('TIME_SELECTOR_INFINITY', 6); // Deprecated.
define('TIME_SELECTOR_NOMINIMUM', 6);

define('DURATION_MINUTE', 60);
define('DURATION_HOUR',   60 * DURATION_MINUTE);
define('DURATION_DAY',    24 * DURATION_HOUR);
define('DURATION_WEEK',   7  * DURATION_DAY);
define('DURATION_MONTH',  30 * DURATION_DAY);
define('DURATION_YEAR',   365 * DURATION_DAY);

define('AVAILABILITY_NOT_TO_STUDENTS',0);
define('AVAILABILITY_TO_STUDENTS', 1);

define('PROGRAM_EXCEPTION_NONE', 0);
define('PROGRAM_EXCEPTION_RAISED', 1);
define('PROGRAM_EXCEPTION_DISMISSED', 2);
define('PROGRAM_EXCEPTION_RESOLVED', 3);

define('PROG_EXTENSION_GRANT', 1);
define('PROG_EXTENSION_DENY', 2);

define('PROG_UPDATE_ASSIGNMENTS_UNAVAILABLE', 0);
define('PROG_UPDATE_ASSIGNMENTS_COMPLETE', 1);
define('PROG_UPDATE_ASSIGNMENTS_DEFERRED', 2);

// The maximum number of user assignments that will be processed while the user waits, otherwise processing is deferred until cron.
define('PROG_UPDATE_ASSIGNMENTS_DEFER_COUNT', 200);

global $TIMEALLOWANCESTRINGS;

$TIMEALLOWANCESTRINGS = array(
    TIME_SELECTOR_HOURS => 'hours',
    TIME_SELECTOR_DAYS => 'days',
    TIME_SELECTOR_WEEKS => 'weeks',
    TIME_SELECTOR_MONTHS => 'months',
    TIME_SELECTOR_YEARS => 'years',
    TIME_SELECTOR_NOMINIMUM => 'nominimumtime',
);


/**
 * Quick and light function for returning a program context
 *
 * @access  public
 * @param   $int    integer     Program id
 * @return  object  context instance
 */
function program_get_context($id) {
    // Quickly get context from program id
    return context_program::instance($id);
}


class program {

    public $id, $category, $sortorder, $fullname, $shortname;
    public $idnumber, $summary, $endnote, $visible;
    public $availablefrom, $availableuntil, $available;
    public $timecreated, $timemodified, $usermodified, $icon;
    public $content, $allowextensionrequests;

    protected $assignments, $messagesmanager;
    protected $exceptionsmanager, $context, $studentroleid;

    /**
     * Constructs a new program.
     *
     * @throws coding_exception if the given record does not contain all of the expected fields.
     * @throws ProgramException if the program does not exist.
     * @param int|stdClass $idorrecord Either a program id OR a program record with all fields from the database.
     *     The ability to pass in a record was added in Totara 10.0.
     *     It is expected if a record is passed that it is a complete row from the prog table.
     */
    public function __construct($idorrecord) {
        global $CFG, $DB;

        // get program db record
        if (is_object($idorrecord) && isset($idorrecord->id)) {
            $id = $idorrecord->id;
            $program = $idorrecord;
            if (debugging() || (defined('PHPUNIT_TEST') && PHPUNIT_TEST)) {
                // Debugging is turned on OR this is a PHPUNIT test, lets validate the object contains at least the properties
                // we expect and use.
                $columns = [
                    'id', 'category', 'sortorder', 'fullname', 'shortname', 'idnumber', 'summary', 'endnote',
                    'visible', 'availablefrom', 'availableuntil', 'available', 'timecreated', 'timemodified',
                    'usermodified', 'icon', 'allowextensionrequests'
                ];
                $missing = array_filter($columns, function($column) use ($program) {
                    return !property_exists($program, $column);
                });
                if (count($missing)) {
                    throw new coding_exception('Program created with incomplete program record', "Missing: ".join(',', $missing));
                }

            }
        } else {
            $id =(int)$idorrecord;
            $program = $DB->get_record('prog', array('id' => $id));
            if (!$program) {
                throw new ProgramException(get_string('programidnotfound', 'totara_program', $id));
            }
        }

        // set details about this program
        $this->id = $id;
        $this->category = $program->category;
        $this->sortorder = $program->sortorder;
        $this->fullname = $program->fullname;
        $this->shortname = $program->shortname;
        $this->idnumber = $program->idnumber;
        $this->summary = $program->summary;
        $this->endnote = $program->endnote;
        $this->visible = $program->visible;
        $this->availablefrom = $program->availablefrom;
        $this->availableuntil = $program->availableuntil;
        $this->available = $program->available;
        $this->timecreated = $program->timecreated;
        $this->timemodified = $program->timemodified;
        $this->usermodified = $program->usermodified;
        $this->icon = $program->icon;
        $this->audiencevisible = $program->audiencevisible;
        $this->certifid = $program->certifid;
        $this->assignmentsdeferred = $program->assignmentsdeferred;
        $this->allowextensionrequests = $program->allowextensionrequests;

        $this->content = new prog_content($id);
        $this->assignments = new prog_assignments($id);

        $this->messagesmanager = prog_messages_manager::get_program_messages_manager($id);
        $this->exceptionsmanager = new prog_exceptions_manager($id);

        $this->context = context_program::instance($this->id);
        $this->studentroleid = $CFG->learnerroleid;

        if (!$this->studentroleid) {
            print_error('error:failedtofindstudentrole', 'totara_program');
        }
    }

    /**
     * Create a new program.
     *
     * @since totara-2.7.2
     * @param mixed $data stdClass or array of program settings.
     * @return program
     */
    public static function create($data) {
        global $DB, $USER;

        // Convert stdClass object to array.
        if (is_object($data)) {
            $data = (array)$data;
        }

        if (isset($data['available'])) {
            throw new coding_exception("Property 'available' is automatically calculated based on the given from and until dates " .
                "and should not be manually specified");
        }

        // Set up the defaults.
        $now = time();
        $sortorder = $DB->get_field('prog', 'MAX(sortorder) + 1', array());
        $sortorder = !empty($sortorder) ? $sortorder : 0;

        $defaults = array(
            'timecreated' => $now,
            'timestarted' => 0,
            'timemodified' => $now,
            'usermodified' => $USER->id,
            'sortorder' => $sortorder,
            'exceptionssent' => 0,
            'summary' => '',
            'endnote' => '',
            'availablefrom' => 0,
            'availableuntil' => 0,
        );

        // Merge the defaults and given data. The given data overrides the defaults.
        $todb = (object)array_merge($defaults, (array)$data);

        // Set up some properties that depend on the data.
        $todb->available = prog_check_availability($todb->availablefrom, $todb->availableuntil);

        // Create the program, and messages. Done inside a transaction so that failure will undo it.
        $transaction = $DB->start_delegated_transaction();
        $programid = $DB->insert_record('prog', $todb);

        // Create message manager to add default messages.
        new prog_messages_manager($programid, true);
        $transaction->allow_commit();

        $program = new program($programid);

        // Return the program that was just created.
        return $program;
    }

    /**
     * Returns the program content.
     *
     * @return prog_content
     */
    public function get_content() {
        return $this->content;
    }

    /**
     * Returns the program context.
     *
     * @return context_program
     */
    public function get_context() {
        return $this->context;
    }

    /**
     * Returns the program assignments object.
     *
     * @return prog_assignments
     */
    public function get_assignments() {
        return $this->assignments;
    }

    /**
     * Resets the program assignments to ensure they are accurate.
     */
    public function reset_assignments() {
        $this->assignments->reset();
    }

    public function get_messagesmanager() {
        return $this->messagesmanager;
    }

    public function get_exceptionsmanager() {
        return $this->exceptionsmanager;
    }

    /**
     * Deletes an entire program and all its data (content, assignments,
     * messages, exceptions)
     *
     * @return bool Success
     */
    public function delete() {
        global $DB, $USER;
        $result = true;

        // First delete this program from any users' learning plans
        // We do this before calling begin_sql() as we need these records to be
        // fully removed from the database before we call $this->unassign_learners()
        // or the users won't be properly unassigned
        $result = $result && $DB->delete_records('dp_plan_program_assign', array('programid' => $this->id));

        // Get all users who are automatically assigned, as we want to unassign them all.
        $users_to_unassign = $DB->get_fieldset_sql("SELECT DISTINCT userid FROM {prog_user_assignment} WHERE programid = ?", array($this->id));

        $transaction = $DB->start_delegated_transaction();

        // Unassign the users.
        if (!empty($users_to_unassign)) {
            $this->unassign_learners($users_to_unassign);
        }

        // delete all exceptions and exceptions data
        $this->exceptionsmanager->delete();

        // delete all messages and the log of sent messages
        $this->messagesmanager->delete();

        // delete all assignments
        $this->assignments->delete();

        // delete all content
        $this->content->delete();

        // delete certification
        if ($this->certifid) {
            certif_delete(CERTIFTYPE_PROGRAM, $this->certifid);
        }

        // delete all tag instances.
        core_tag_tag::remove_all_item_tags('totara_program', 'prog', $this->id);

        // delete the program itself
        $DB->delete_records('prog', array('id' => $this->id));

        $transaction->allow_commit();

        $event = \totara_program\event\program_deleted::create(
            array(
                'objectid' => $this->id,
                'context' => context_program::instance($this->id),
                'userid' => $USER->id,
                'other' => array(
                    'certifid' => empty($this->certifid) ? 0 : $this->certifid,
                ),
            )
        );
        $event->trigger();

        return true;
    }


    /**
     * @deprecated since Totara 10. Use prog_conditionally_delete_completion instead.
     *
     * Deletes the completion records for the program for the specified user.
     *
     * @param int $userid
     * @param bool $deletecompleted Whether to force deletion of records for completed programs
     * @return bool Deletion true|Exception
     */
    public function delete_completion_record($userid, $deletecompleted=false) {
        debugging('certification_event_handler::unassigned() is deprecated, call certif_conditionally_delete_completion directly instead.', DEBUG_DEVELOPER);

        global $DB;

        if ($deletecompleted === true || !prog_is_complete($this->id, $userid)) {
            $DB->delete_records('prog_completion', array('programid' => $this->id, 'userid' => $userid));
            prog_log_completion($this->id, $userid, 'Deleted prog_completion in deprecated function program::delete_completion_record');
        }

        return true;
    }

    /**
     * Checks all the assignments for the program and assigns and unassigns
     * learners to the program if they meet or don't meet the current
     * assignment criteria.
     *
     * Under certain conditions (e.g. completion date not allowing enough time
     * for a student to complete the program) users will not be assigned to the
     * program and exceptions will be raised instead.
     *
     * @param bool $forcerun Force the function to run, otherwise it may decide to delay execution until next cron.
     * @return int PROG_UPDATE_ASSIGNMENTS_XXX
     * @throws coding_exception
     */
    public function update_learner_assignments($forcerun = false) {
        global $DB, $ASSIGNMENT_CATEGORY_CLASSNAMES;

        // Clear the deferred flag before starting, so that any change made by users while this
        // function is running will be processed the next time the deferred task is run.
        $DB->set_field('prog', 'assignmentsdeferred', 0, array('id' => $this->id));
        $this->assignmentsdeferred = 0;

        // Check program availability.
        if (!prog_check_availability($this->availablefrom, $this->availableuntil)) {
            return PROG_UPDATE_ASSIGNMENTS_UNAVAILABLE;
        }

        // Get program assignments.
        $progassignments = $this->assignments->get_assignments();
        if (!$progassignments) {
            $progassignments = $DB->get_records('prog_assignment', array('programid' => $this->id));
        }

        if (!$forcerun) {
            // If there are too many previous users then defer processing until next cron run.
            $rawprevioususerassignmentscount = $DB->count_records('prog_user_assignment', array('programid' => $this->id));
            if ($rawprevioususerassignmentscount > PROG_UPDATE_ASSIGNMENTS_DEFER_COUNT) {
                totara_set_notification(get_string('programassignmentsdeferred', 'totara_program'), null,
                    array('class' => 'notifynotice'));
                $DB->set_field('prog', 'assignmentsdeferred', 1, array('id' => $this->id));
                $this->assignmentsdeferred = 1;
                return PROG_UPDATE_ASSIGNMENTS_DEFERRED;
            }
        }

        // Get all the raw previous user assignments and proccess them into an array partitioned by the assignment id, so
        // lookup is efficient.
        $sql = "SELECT pua.id, pua.userid, pua.assignmentid, pua.timeassigned, pua.exceptionstatus,
                       pe.id AS programexceptionid, pe.timeraised AS programexceptiontimeraised
                  FROM {prog_user_assignment} pua
             LEFT JOIN {prog_exception} pe ON pe.userid = pua.userid AND pe.assignmentid = pua.id
                 WHERE pua.programid = :programid";
        $params = array('programid' => $this->id);
        $rawprevioususerassignments = $DB->get_recordset_sql($sql, $params);
        $allprevioususerassignments = array();
        $allprevioususerids = array();
        foreach ($rawprevioususerassignments as $rawuserassign) {
            $allprevioususerids[$rawuserassign->userid] = $rawuserassign->userid;
            $allprevioususerassignments[$rawuserassign->assignmentid][$rawuserassign->userid] = $rawuserassign;
        }
        $rawprevioususerassignments->close();

        // Get all previous completion records. We will add to and update $allpreviousprogcompletions as we change
        // records. This only works because each user can only belong to each prog_assignment just once (but can
        // be in multiple prog_assignments), and we flush all data to the database after each prog_assignment is
        // processed, so if the same user is encountered again then it must be the case that the previous
        // prog_completion record change has already been written to the db.
        $sql = "SELECT pc.userid, pc.timedue, pc.status, cc.certifpath, cc.status AS certifstatus
                  FROM {prog_completion} pc
             LEFT JOIN {certif_completion} cc ON cc.certifid = :certifid AND pc.userid = cc.userid
                 WHERE pc.programid = :programid AND pc.coursesetid = 0";
        $params = array('programid' => $this->id, 'certifid' => $this->certifid);
        $rawprogcompletions = $DB->get_recordset_sql($sql, $params);
        $allpreviousprogcompletions = array();
        foreach ($rawprogcompletions as $rawprogcompletion) {
            $allpreviousprogcompletions[$rawprogcompletion->userid] = $rawprogcompletion;
        }
        $rawprogcompletions->close();

        $allvalidassignuserids = array();

        if ($progassignments) {
            // First create and store the set of users who should be assigned in each program, so that we can decide if we want
            // to update the user assignments now or later on cron.
            $cumulativeshouldbeusercount = 0;
            foreach ($progassignments as $progassignment) {
                // Create instance of assignment type so we can call functions on it.
                $progassignment->category = new $ASSIGNMENT_CATEGORY_CLASSNAMES[$progassignment->assignmenttype]();

                // Get users who should be in the program due to this program assignment.
                $progassignment->shouldbeusers = $progassignment->category->get_affected_users_by_assignment($progassignment);

                if (!$forcerun) {
                    // If too many users should be assigned then defer processing until next cron run.
                    $cumulativeshouldbeusercount += count($progassignment->shouldbeusers);
                    if ($cumulativeshouldbeusercount > PROG_UPDATE_ASSIGNMENTS_DEFER_COUNT) {
                        totara_set_notification(get_string('programassignmentsdeferred', 'totara_program'), null,
                            array('class' => 'notifynotice'));
                        $DB->set_field('prog', 'assignmentsdeferred', 1, array('id' => $this->id));
                        $this->assignmentsdeferred = 1;
                        return PROG_UPDATE_ASSIGNMENTS_DEFERRED;
                    }
                }
            }

            // Process each program assignment one at a time.
            foreach ($progassignments as $progassignment) {
                $previoususerassignments = isset($allprevioususerassignments[$progassignment->id]) ?
                    $allprevioususerassignments[$progassignment->id] : array();

                // Remove prog_user_assignment records for users who no longer match this program assignment.
                // These users might be assigned due to other program assignments, so we don't unassign here.
                if (!empty($previoususerassignments)) {
                    $previoususerids = array_keys($previoususerassignments);

                    // Transform the list of users who should be assigned into an array of userids.
                    $shouldbeuserids = array();
                    foreach ($progassignment->shouldbeusers as $user) {
                        $shouldbeuserids[] = $user->id;
                    }

                    // Get users that no longer match the category assignment.
                    $useridstoremove = array_diff($previoususerids, $shouldbeuserids);

                    if (!empty($useridstoremove)) {
                        $progassignment->category->remove_outdated_assignments($this->id,
                            $progassignment->assignmenttypeid, $useridstoremove);
                    }
                }

                // Set up some variables for use inside the foreach.
                $context = context_program::instance($this->id); // Used for events.
                $newassignusersbuffer = array(); // Buffer for doing bulk inserts.
                $updateassignusersbuffer = array(); // Buffer for doing bulk events.
                $futureassignusersbuffer = array(); // Buffer for doing bulk inserts.

                // Check each user which should be assigned.
                foreach ($progassignment->shouldbeusers as $user) {
                    $allvalidassignuserids[$user->id] = $user->id; // Record this for later.

                    // Get the existing prog_completion record, if it exists. This includes corresponding certif_completion.
                    $progcompletion = isset($allpreviousprogcompletions[$user->id]) ?
                        $allpreviousprogcompletions[$user->id] : false;

                    // Calculate the new timedue, taking into account the current timedue.
                    // TODO: If in future we allow reducing timedue, change make_timedue to ignore current timedue.
                    $timedue = $progcompletion ? $progcompletion->timedue : false;
                    $timedue = $this->make_timedue($user->id, $progassignment, $timedue);

                    if ($progassignment->completionevent == COMPLETION_EVENT_FIRST_LOGIN && $timedue === false) {
                        // This is a future assignment.
                        // This means that the user hasn't logged in yet.
                        // Create or update the future assignment so we can assign them when they do login.
                        $futureassignusersbuffer[$user->id] = $user->id;
                        // We want to create the completion record now, and it will be updated with the correct date later.
                        $timedue = COMPLETION_TIME_NOT_SET;
                    }

                    if (isset($previoususerassignments[$user->id])) {
                        // The prog_user_assignment record already exists.
                        $sendmessage = false;
                        $userassignment = $previoususerassignments[$user->id];

                        // Make sure we have a current program completion record.
                        if (!$progcompletion) {
                            // This shouldn't occur, because there is an existing prog_user_assignment record.
                            debugging('prog_completion record missing for userid ' . $user->id . ', programid ' . $this->id .
                                '. If this problem perists then it should be reported to Totara support.');
                            continue;
                        }

                        if (!empty($userassignment->programexceptionid) &&
                            $userassignment->programexceptiontimeraised == $userassignment->timeassigned) {
                            // This exception was raised the first time they were assigned, meaning they haven't received
                            // an assignment message yet.
                            $sendmessage = true;
                        }

                        // Skip completed programs (includes certifications which are certified and window is not yet open).
                        if ($progcompletion->status == STATUS_PROGRAM_COMPLETE) {
                            continue;
                        }

                        // Skip certifications which are on the recert path or are expired.
                        if (!empty($this->certifid)) {
                            if ($progcompletion->certifpath == CERTIFPATH_RECERT ||
                                $progcompletion->certifstatus == CERTIFSTATUS_EXPIRED) {
                                continue;
                            }
                        }

                        // Make sure that the exceptionstatus property is present (protection against a previous bug).
                        if (!isset($userassignment->exceptionstatus)) {
                            throw new coding_exception('The property "exceptionstatus" is missing.');
                        }

                        if ($timedue > $progcompletion->timedue) {
                            // The timedue has increased, we'll need to update it and check for exceptions.

                            // If it currently has no timedue then we need to check exceptions.
                            // If there was a previously unresolved or dismissed exception then we need to recheck.
                            if ($progcompletion->timedue <= 0 ||
                                in_array($userassignment->exceptionstatus, array(PROGRAM_EXCEPTION_RAISED, PROGRAM_EXCEPTION_DISMISSED))) {
                                if ($this->update_exceptions($user->id, $progassignment, $timedue)) {
                                    $userassignment->exceptionstatus = PROGRAM_EXCEPTION_RAISED;
                                } else {
                                    $userassignment->exceptionstatus = PROGRAM_EXCEPTION_NONE;
                                }
                                if ($userassignment->exceptionstatus == PROGRAM_EXCEPTION_RAISED) {
                                    // Store raised exception status (was reset by update_exceptions).
                                    $updateduserassignment = new stdClass();
                                    $updateduserassignment->id = $userassignment->id;
                                    $updateduserassignment->exceptionstatus = PROGRAM_EXCEPTION_RAISED;
                                    $DB->update_record('prog_user_assignment', $updateduserassignment);
                                }
                            }

                            // Update user's due date.
                            $progcompletion->timedue = $timedue; // Updates $allpreviousprogcompletions, for following assignments.
                            $this->set_timedue($user->id, $timedue, 'Due date updated for existing program assignment');

                            if ($userassignment->exceptionstatus == PROGRAM_EXCEPTION_NONE && $sendmessage) {
                                // Trigger event for observers to deal with resolved exception from first assignment.
                                // We don't add this to the new assignments buffer because we're not creating a new assignment.
                                $updateassignusersbuffer[$user->id] = 0;
                            }
                        } // Else no change or decrease, skipped. If we want to allow decrease then it should be added here.
                    } else {
                        // This is a new assignment and it might be a future assignment.

                        // If the user is already complete, or has a timedue, skip checking for time allowence exceptions and carry on with assignments.
                        if (!empty($progcompletion) && ($progcompletion->status == STATUS_PROGRAM_COMPLETE || $progcompletion->timedue > 0)) {
                            $exceptions = $this->update_exceptions($user->id, $progassignment, COMPLETION_TIME_NOT_SET);
                        } else {
                            $exceptions = $this->update_exceptions($user->id, $progassignment, $timedue);
                        }

                        // Fix the timedue before we put it into the database. Empty includes COMPLETION_TIME_UNKNOWN, null, 0, ''.
                        $timedue = empty($timedue) ? COMPLETION_TIME_NOT_SET : $timedue;
                        $newassignusersbuffer[$user->id] = array('timedue' => $timedue, 'exceptions' => $exceptions);

                        if (empty($progcompletion)) {
                            // Prog_completion record will be created by assign_learners_bulk.
                            // Certif_completion record will be created by program_assigned event observer.
                            $newassignusersbuffer[$user->id]['needscompletionrecord'] = true;

                            // Manually put it into $allpreviousprogcompletions now, so it's available for following assignments.
                            $allpreviousprogcompletions[$user->id] = (object)array(
                                'userid' => $user->id,
                                'timedue' => $timedue,
                                'status' => STATUS_PROGRAM_INCOMPLETE,
                                'certpath' => CERTIFPATH_CERT,
                                'certstatus' => CERTIFSTATUS_ASSIGNED
                            );
                        } else if ($timedue > $progcompletion->timedue) {
                            // Update user's due date.
                            $progcompletion->timedue = $timedue; // Updates $allpreviousprogcompletions, for following assignments.
                            $this->set_timedue($user->id, $timedue, 'Due date updated for new program assignment');
                        } // Else no change or decrease, skipped. If we want to allow decrease then it should be added here.

                    }
                } // End user assignments loop.

                // Flush future user assignments after program assignment loop finished.
                if (!empty($futureassignusersbuffer)) {
                    $eventdata = array('other' => array('programid' => $this->id, 'assignmentid' => $progassignment->id));
                    \totara_program\event\bulk_future_assignments_started::create_from_data($eventdata)->trigger();

                    $this->create_future_assignments_bulk($this->id, $futureassignusersbuffer, $progassignment->id);

                    // Trigger each individual event.
                    foreach ($futureassignusersbuffer as $userid) {
                        $event = \totara_program\event\program_future_assigned::create(
                            array(
                                'objectid' => $this->id,
                                'context' => $context,
                                'userid' => $userid,
                            )
                        );
                        $event->trigger();
                    }

                    \totara_program\event\bulk_future_assignments_ended::create()->trigger();
                    unset($futureassignusersbuffer);
                }

                // Flush new user assignments after program assignment loop finished.
                if (!empty($newassignusersbuffer) || !empty($updateassignusersbuffer)) {
                    $eventdata = array('other' => array('programid' => $this->id, 'assignmentid' => $progassignment->id));
                    \totara_program\event\bulk_learner_assignments_started::create_from_data($eventdata)->trigger();

                    // We need to do this after every program assignment so that the records will exist and be updated in case
                    // the same user is present in a following assignment.
                    if (!empty($newassignusersbuffer)) {
                        $this->assign_learners_bulk($newassignusersbuffer, $progassignment);
                    }

                    // Both new and updated user assignments need to trigger the program_assigned event (note "+" to preserve keys).
                    $allassignusers = $newassignusersbuffer + $updateassignusersbuffer;

                    // Trigger each individual event.
                    // If this is a certification, certification_event_handler creates the certif_completion records.
                    foreach ($allassignusers as $userid => $data) {
                        $event = \totara_program\event\program_assigned::create(
                            array(
                                'objectid' => $this->id,
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
                        prog_update_completion($userid, $this);
                    }

                    unset($newassignusersbuffer);
                }

            } // End program assignments loop.
        }

        // Unassign all learners who are no longer included in this program by any assignment. Simply find those users who were
        // assigned at the start but who were not in any of the assignment categories. These may include users whose
        // prog_assignment record still exists but they no longer meet the criteria of the assignment, e.g. a dynamic audience.
        $userstounassign = array_diff($allprevioususerids, $allvalidassignuserids);
        if (!empty($userstounassign)) {
            $this->unassign_learners($userstounassign);
        }

        // The main for-loop above was over EXISTING program assignments. This looks for users assigned to the
        // program where the program assignment record no longer exists (and there is no other program assignment
        // for the same user).
        $sql = "SELECT DISTINCT pua.userid
                  FROM {prog_user_assignment} pua
                 WHERE programid = :programid
                   AND NOT EXISTS (SELECT 1
                                     FROM {prog_assignment} pa
                                    WHERE pa.id = pua.assignmentid)";
        $userstounassign = $DB->get_records_sql($sql, array('programid' => $this->id));
        $userstounassign = array_diff(array_keys($userstounassign), $allvalidassignuserids);
        if (!empty($userstounassign)) {
            $this->unassign_learners($userstounassign);
        }

        // Users can have multiple assignments to a program.
        // We need this to clean up unnecessary redundant assignments caused when removing an assignment type.
        $DB->execute("DELETE FROM {prog_user_assignment}
                       WHERE programid = :programid
                         AND NOT EXISTS (SELECT 1
                                           FROM {prog_assignment} pa
                                          WHERE pa.id = {prog_user_assignment}.assignmentid)",
            array('programid' => $this->id));
        $DB->execute("DELETE FROM {prog_future_user_assignment}
                       WHERE programid = :programid
                         AND NOT EXISTS (SELECT 1
                                           FROM {prog_assignment} pa
                                          WHERE pa.id = {prog_future_user_assignment}.assignmentid)",
            array('programid' => $this->id));

        // Delete program enrolment messages for all non-assigned users, who are not complete, so that they can be re-sent if the user is re-assigned.
        $enrolmentmessageids = $DB->get_fieldset_select(
            'prog_message',
            'id',
            "programid = :programid AND messagetype = :messagetype",
            array('programid' => $this->id, 'messagetype' => MESSAGETYPE_ENROLMENT)
        );
        if (!empty($enrolmentmessageids)) {
            list($enrolmentmessageidssql, $enrolmentmessageidsparams) =
                $DB->get_in_or_equal($enrolmentmessageids, SQL_PARAMS_NAMED);
            $sql = "DELETE FROM {prog_messagelog}
                     WHERE messageid {$enrolmentmessageidssql}
                       AND NOT EXISTS (SELECT 1
                                         FROM {prog_user_assignment} pua
                                        WHERE pua.programid = :programid
                                          AND pua.userid = {prog_messagelog}.userid)
                       AND NOT EXISTS (SELECT 1
                                         FROM {prog_completion} pc
                                        WHERE pc.programid = :programid2
                                          AND pc.userid = {prog_messagelog}.userid
                                          AND pc.coursesetid = 0
                                          AND pc.status = :status)";
            $params = array_merge(array('programid' => $this->id, 'programid2' => $this->id, 'status' => STATUS_PROGRAM_COMPLETE),
                $enrolmentmessageidsparams);
            $DB->execute($sql, $params);
        }

        // Delete program unenrolment messages for all assigned users, so that they can be re-sent if the user is re-unassigned.
        $unenrolmentmessageids = $DB->get_fieldset_select(
            'prog_message',
            'id',
            "programid = :programid AND messagetype = :messagetype",
            array('programid' => $this->id, 'messagetype' => MESSAGETYPE_UNENROLMENT)
        );
        if (!empty($unenrolmentmessageids)) {
            list($unenrolmentmessageidssql, $unenrolmentmessageidsparams) =
                $DB->get_in_or_equal($unenrolmentmessageids, SQL_PARAMS_NAMED);
            $sql = "DELETE FROM {prog_messagelog}
                     WHERE messageid {$unenrolmentmessageidssql}
                       AND userid IN (SELECT pua.userid
                                        FROM {prog_user_assignment} pua
                                       WHERE pua.programid = :programid)";
            $params = array_merge(array('programid' => $this->id), $unenrolmentmessageidsparams);
            $DB->execute($sql, $params);
        }

        // Ensure the assignments object has been reset so that it will force a load next time someone tries to use it.
        $this->get_assignments()->reset();

        return PROG_UPDATE_ASSIGNMENTS_COMPLETE;
    }


    /**
     * Bulk create records in the future assignment table
     *
     * Used to track an assignment that cannot be made yet, but will be added
     * at some later time (e.g. first login assignments which will be applied the
     * first time the user logs in).
     *
     * @param integer $programid ID of the program
     * @param array(int) $userids IDs of the user being assigned
     * @param integer $assignmentid ID of the assignment (record in prog_assignment table)
     *
     * @return boolean True if the future assignment is saved successfully or already exists
     */
    function create_future_assignments_bulk($programid, $userids, $assignmentid) {
        global $DB;

        $fassignments = array();

        // Divide the users into batches to prevent sql problems.
        $batches = array_chunk($userids, $DB->get_max_in_params());
        unset($userids);

        // Process each batch of user ids.
        foreach ($batches as $userids) {
            list($sqlin, $sqlparams) = $DB->get_in_or_equal($userids);
            $sqlparams[] = $programid;
            $sqlparams[] = $assignmentid;
            $sql = "SELECT u.id
                FROM {user} u
                WHERE u.id {$sqlin}
                AND u.id NOT IN (
                    SELECT userid
                    FROM {prog_future_user_assignment}
                    WHERE programid = ?
                    AND assignmentid = ?
                )";
            $users = $DB->get_records_sql($sql, $sqlparams);

            foreach ($users as $user) {
                $assignment = new stdClass();
                $assignment->programid = $programid;
                $assignment->userid = $user->id;
                $assignment->assignmentid = $assignmentid;

                $fassignments[] = $assignment;
            }
        }

        return $DB->insert_records_via_batch('prog_future_user_assignment', $fassignments);
    }

    /**
     * This function is ONLY used to create the initial user assignment
     * and check for exceptions when creating it
     *
     * Assigns a user to the program. Any users assigned to a program in this
     * way will have this program as part of their required (mandatory) learning
     * (as opposed to part of a learning plan).
     *
     * A 'program_assigned' event is triggered to notify any listening modules.
     *
     * This function will cause users to be re-enrolled in all related courses where they
     * have a suspended program enrolment.
     *
     * @param array $users Keys are user ids, values are arrays with timedue and exceptions.
     * @param object $assignment_record A record from prog_assignment, only id is used.
     */
    public function assign_learners_bulk($users, $assignment_record) {
        global $DB, $USER;

        if (empty($users)) {
            return;
        }

        $now = time();

        // insert a completion record to store the status of the user's progress on the program
        // TO DO: eventually we need to have multiple completion records, linked to the assignment that made them
        $prog_completions = array();
        $progcompletionlogs = array();
        foreach ($users as $userid => $assigndata) {
            // Only create program completion records if needed.
            if (empty($assigndata['needscompletionrecord'])) {
                continue;
            }
            $pc = new stdClass();
            $pc->programid = $this->id;
            $pc->userid = $userid;
            $pc->coursesetid = 0;
            $pc->status = STATUS_PROGRAM_INCOMPLETE;
            $pc->timecreated = $now;
            $pc->timestarted = 0;
            $pc->timecompleted = 0;
            $pc->timedue = $assigndata['timedue'];
            $prog_completions[] = $pc;

            // Prepare a program log which can be written to the db in bulk, by bypassing the program log function.
            $pcl = new stdClass();
            $pcl->programid = $this->id;
            $pcl->userid = $userid;
            $pcl->changeuserid = $USER->id;
            $pcl->description = prog_calculate_completion_description($pc, 'Program completion created in assign_learners_bulk');
            $pcl->timemodified = $now;
            $progcompletionlogs[] = $pcl;
        }
        $DB->insert_records_via_batch('prog_completion', $prog_completions);
        unset($prog_completions);
        $DB->insert_records_via_batch('prog_completion_log', $progcompletionlogs);
        unset($progcompletionlogs);

        // Insert a user assignment record to store the details of how this user was assigned to the program.
        $user_assignments = array();
        $progcompletionlogs = array();
        foreach ($users as $userid => $assigndata) {
            $ua = new stdClass();
            $ua->programid = $this->id;
            $ua->userid = $userid;
            $ua->assignmentid = $assignment_record->id;
            $ua->timeassigned = $now;
            $ua->exceptionstatus = $assigndata['exceptions'] ? PROGRAM_EXCEPTION_RAISED : PROGRAM_EXCEPTION_NONE;
            $user_assignments[] = $ua;

            // Prepare a program log which can be written to the db in bulk, by bypassing the program log function.
            $pcl = new stdClass();
            $pcl->programid = $this->id;
            $pcl->userid = $userid;
            $pcl->changeuserid = $USER->id;
            $pcl->description = 'User assignment created for program assignment ' . $assignment_record->id;
            $pcl->timemodified = $now;
            $progcompletionlogs[] = $pcl;
        }
        $DB->insert_records_via_batch('prog_user_assignment', $user_assignments);
        unset($user_assignments);
        $DB->insert_records_via_batch('prog_completion_log', $progcompletionlogs);
        unset($progcompletionlogs);

        $userids = array_keys($users);

        // Assign the student role to the user in the program context
        // This is what identifies the program as required learning.
        role_assign_bulk($this->studentroleid, $userids, $this->context->id);

        // Get the courses in this program or certification.
        $sql = "SELECT DISTINCT courseid
                  FROM {prog_courseset_course} csc
            INNER JOIN {prog_courseset} cs
                    ON csc.coursesetid = cs.id
                   AND cs.programid = :programid";
        $courses = $DB->get_fieldset_sql($sql, array('programid' => $this->id));

        if (!empty($courses)) {
            // Get program course enrolment plugin.
            /* @var enrol_totara_program_plugin $programenrolmentplugin */
            $programenrolmentplugin = enrol_get_plugin('totara_program');
            foreach ($courses as $courseid) {
                $courseinstance = $programenrolmentplugin->get_instance_for_course($courseid);
                if ($courseinstance) {
                    $programenrolmentplugin->process_program_reassignments($courseinstance, $userids);
                }
            }
        }
    }

    /**
     * Receives an array containing userids and unassigns all the users from the
     * program.
     *
     * A 'program_unassigned' event is triggered to notify any listening modules.
     *
     * @param array $userids Array containing userids
     * @return bool
     */
    public function unassign_learners($userids) {
        global $DB, $USER;

        //get the courses in this program
        $sql = "SELECT DISTINCT courseid
                  FROM {prog_courseset_course} csc
            INNER JOIN {prog_courseset} cs
                    ON csc.coursesetid = cs.id
                   AND cs.programid = :pid1
                UNION
                SELECT DISTINCT cc.iteminstance as courseid
                  FROM {prog_courseset} cs
                  JOIN {comp_criteria} cc
                    ON cc.competencyid = cs.competencyid AND cc.itemtype = :itemtype
                 WHERE cs.programid = :pid2
                   AND cs.competencyid != 0";
        $params = array('pid1' => $this->id, 'itemtype' => 'coursecompletion', 'pid2' => $this->id);
        $courses = $DB->get_fieldset_sql($sql, $params);

        if (!empty($courses)) {
            //get program course enrolment plugin
            $program_plugin = enrol_get_plugin('totara_program');
            foreach ($courses as $courseid) {
                $instance = $program_plugin->get_instance_for_course($courseid);
                if ($instance) {
                    $program_plugin->process_program_unassignments($instance, $userids);
               }
            }
        }

        // Divide the users into batches to prevent sql problems.
        $batches = array_chunk($userids, $DB->get_max_in_params());
        unset($userids);

        // Process each batch of user ids.
        $now = time();
        foreach ($batches as $userids) {

            // Un-assign the student role from the users in the program context.
            $params = array('roleid' => $this->studentroleid, 'userids' => $userids, 'contextid' => $this->context->id);
            role_unassign_all_bulk($params);

            // Set up sql and params for the following delete functions.
            list($userssql, $params) = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);
            $params['programid'] = $this->id;

            // Delete the user assignment records for this program.
            $DB->execute("DELETE FROM {prog_user_assignment} WHERE programid = :programid AND userid " . $userssql,
                $params);

            // Delete future_user_assignment records too.
            $DB->execute("DELETE FROM {prog_future_user_assignment} WHERE programid = :programid AND userid " . $userssql,
                $params);

            // Delete all exceptions.
            $DB->execute("DELETE FROM {prog_exception} WHERE programid = :programid AND userid " . $userssql,
                $params);

            $progcompletionlogs = array();

            foreach ($userids as $userid) {
                // Log that the prog_user_assignment records were deleted by the code above.
                $log = new stdClass();
                $log->programid = $this->id;
                $log->userid = $userid;
                $log->changeuserid = $USER->id;
                $log->description = 'All program user assignments deleted';
                $log->timemodified = $now;
                $progcompletionlogs[] = $log;

                // Keep, save to history or delete the completion records.
                if ($this->is_certif()) {
                    certif_conditionally_delete_completion($this->id, $userid);
                } else {
                    prog_conditionally_delete_completion($this->id, $userid);
                }
            }

            // Record the completion logs relating to the unassignments.
            if (!empty($progcompletionlogs)) {
                $DB->insert_records_via_batch('prog_completion_log', $progcompletionlogs);
            }

            foreach ($userids as $userid) {
                // Trigger this event for any listening handlers to catch.
                $event = \totara_program\event\program_unassigned::create(
                    array(
                        'objectid' => $this->id,
                        'context' => context_program::instance($this->id),
                        'userid' => $userid,
                    )
                );
                $event->trigger();
            }
        }

        return true;
    }

    /**
     * Sets the time that a program is due for a learner
     *
     * This function bypasses error checks. This function is provided to provide backwards compatibility, so should not be
     * used for new functionality. Instead, use prog_load_completion() and prog_write_completion().
     *
     * @param int $userid The user's ID
     * @param int $timedue Timestamp indicating the date that the program is due to be completed by the user
     * @param string $message If provided, will override the default program completion log message "Due date set to".
     *                        Since Totara 2.9.20, 9.8, 10.
     * @return bool Success
     */
    public function set_timedue($userid, $timedue, $message = '') {
        global $DB;
        if ($completion = $DB->get_record('prog_completion', array('programid' => $this->id, 'userid' => $userid, 'coursesetid' => 0))) {
            $todb = new stdClass();
            $todb->id = $completion->id;
            $todb->timedue = $timedue;
            $result = $DB->update_record('prog_completion', $todb);

            if (empty($message)) {
                $message = 'Due date set to';
            }

            // Record the change in the program completion log.
            prog_log_completion(
                $this->id,
                $userid,
                $message . ': ' . prog_format_log_date($timedue)
            );

            return $result;
        } else {
            return false;
        }
    }

    /**
     * Function to determine if this program only contains a single course.
     *
     * @return false|course
     */
    public function is_single_course($userid) {
        // Count coursesets, if more than one then return false
        $certifpath = get_certification_path_user($this->certifid, $userid);

        $coursesets = $this->get_content()->get_course_sets_path($certifpath);
        if (count($coursesets) == 1) {
            $courseset = reset($coursesets);

            $courses = $courseset->get_courses();
            if (count($courses) == 1) {
                return reset($courses);
            }
        }

        return false;
    }

    /**
     * Calulates the date on which a program will be due for a learner when it
     * is first assigned based on the assignment record through which the user
     * is being assigned to the program.
     *
     * @global array $COMPLETION_EVENTS_CLASSNAMES
     * @param int $userid
     * @param object $assignment_record
     * @param int $timedue Timestamp possibly containing an extended due date
     * @return int A timestamp
     */
    public function make_timedue($userid, $assignment_record, $timedue) {

        if ($assignment_record->completionevent == COMPLETION_EVENT_NONE) {
            // Fixed time or Not Set?
            if ($assignment_record->completiontime == COMPLETION_TIME_UNKNOWN) {
                return COMPLETION_TIME_NOT_SET;
            } else if (is_numeric($timedue) && $timedue > $assignment_record->completiontime) {
                return $timedue;
            } else {
                return $assignment_record->completiontime;
            }
        }

        // Else it's a relative event, need to do a lookup.
        global $COMPLETION_EVENTS_CLASSNAMES;

        if (!isset($COMPLETION_EVENTS_CLASSNAMES[$assignment_record->completionevent])) {
            throw new ProgramException(get_string('eventnotfound', 'totara_program', $assignment_record->completionevent));
        }

        // See if we can retrieve the object form the cache.
        if (isset($this->completion_object_cache[$assignment_record->completionevent])) {
            $event_object = $this->completion_object_cache[$assignment_record->completionevent];
        }
        else {
            // Else make it it and add to the cache for future use.
            $event_object = new $COMPLETION_EVENTS_CLASSNAMES[$assignment_record->completionevent]();
            $this->completion_object_cache[$assignment_record->completionevent] = $event_object;
        }

        $basetime = $event_object->get_timestamp($userid, $assignment_record);

        if ($basetime == false) {
            return false;
        }

        $timedue = $basetime + $assignment_record->completiontime;

        return $timedue;
    }
    private $completion_object_cache = array();

    /**
     * Determines if the program is assigned to the speficied user's required
     * (mandatory) learning
     *
     * @global object $CFG
     * @param int $userid
     * @return bool True if the program is mandatory, false if not
     */
    public function assigned_to_users_required_learning($userid) {
        global $DB;
        $sql = "SELECT p.id
                FROM {prog_user_assignment} AS p
                WHERE p.userid = ?
                AND p.programid = ?";

        return $DB->record_exists_sql($sql, array($userid, $this->id));
    }

    /**
     * Determines if the program is assigned to the speficied user's non-required
     * learning (i.e. part of a learning plan)
     *
     * @global object $CFG
     * @param int $userid
     * @return bool True if the program is assigned to a learning plan, false if not
     */
    public function assigned_to_users_non_required_learning($userid) {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/totara/plan/lib.php');

        $sql = "SELECT p.id
                FROM {dp_plan} AS p
                JOIN {dp_plan_program_assign} AS ppa ON p.id = ppa.planid
                WHERE p.userid = ?
                AND ppa.programid = ?
                AND p.status != ?
                AND ppa.approved = ?";

        return $DB->record_exists_sql($sql, array($userid, $this->id, DP_PLAN_STATUS_UNAPPROVED, DP_APPROVAL_APPROVED));
    }

    /**
     * Looks for courses that have been assigned to this certification but exists in another certification
     * that this user has already been assigned to.
     *
     * @global object $DB
     * @param int $userid
     * @return bool true if the user is on another certification with the same course
     */
    public function duplicate_course($userid) {
        global $DB;
        if (!isset($this->certifid) || empty($this->certifid)) {
            return false;
        }
        $sql = "SELECT DISTINCT pcc.courseid
                FROM {prog} p
                JOIN {prog_user_assignment} pua ON pua.programid = p.id AND pua.userid = :userid
                JOIN {prog_courseset} pc ON pc.programid = p.id
                JOIN {prog_courseset_course} pcc ON pcc.coursesetid = pc.id
                WHERE p.certifid IS NOT NULL
                AND p.id <> :programid1
                AND EXISTS (SELECT thispcc.courseid
                            FROM {prog_courseset} thispc
                            JOIN {prog_courseset_course} thispcc ON thispcc.coursesetid = thispc.id
                            WHERE thispcc.courseid = pcc.courseid
                            AND thispc.programid = :programid2)";
        return $DB->record_exists_sql($sql, array('userid' => $userid, 'programid1' => $this->id, 'programid2' => $this->id));
    }

    /**
     * Return true or false depending on whether or not the specified user has
     * completed this program
     *
     * @param int $userid
     * @return bool
     */
    public function is_program_complete($userid) {
        debugging('$program->is_program_complete() is deprecated, use the lib function prog_is_complete() instead', DEBUG_DEVELOPER);
        return prog_is_complete($this->id, $userid);
    }

    /**
     * Return true if the user has started but not completed this program, false
     * if not
     *
     * @param int $userid
     * @return bool
     */
    public function is_program_inprogress($userid) {
        debugging('$program->is_program_inprogress() is deprecated, use the lib function prog_is_inprogress() instead', DEBUG_DEVELOPER);
        return prog_is_inprogress($this->id, $userid);
    }

    /**
     * Updates the completion record in the database for the specified user
     *
     * @param int $userid
     * @param array $completionsettings Contains the field values for the record
     * @return bool|int
     */
    public function update_program_complete($userid, $completionsettings) {
        global $CFG, $DB;

        $progcompleted_eventtrigger = false;

        // if the program is being marked as complete we need to trigger an
        // event to any listening modules
        if (array_key_exists('status', $completionsettings)) {
            if ($completionsettings['status'] == STATUS_PROGRAM_COMPLETE) {

                // flag that we need to trigger the program_completed event
                $progcompleted_eventtrigger = true;
            }
        }

        $startsql = "SELECT MIN(timestarted)
                       FROM {prog_completion}
                      WHERE timestarted > 0
                        AND userid = :uid
                        AND programid = :pid
                   GROUP BY programid";
        $minstarted = $DB->get_field_sql($startsql, array('uid' => $userid, 'pid' => $this->id));
        if ($completion = $DB->get_record('prog_completion', array('programid' => $this->id, 'userid' => $userid, 'coursesetid' => 0))) {
            if (empty($completion->timestarted)) {
                $completion->timestarted = !empty($minstarted) ? $minstarted : 0;
            }

            foreach ($completionsettings as $key => $val) {
                $completion->$key = $val;
            }

            if ($progcompleted_eventtrigger) {
                // Record the user's pos/org at time of completion.
                $jobassignment = \totara_job\job_assignment::get_first($userid, false);
                if ($jobassignment) {
                    $completion->positionid = $jobassignment->positionid;
                    $completion->organisationid = $jobassignment->organisationid;
                } else {
                    $completion->positionid = 0;
                    $completion->organisationid = 0;
                }
            }

            $update_success = $DB->update_record('prog_completion', $completion);

            prog_write_completion_log($completion->programid, $completion->userid, 'Program completion updated by update_program_complete');

            if ($progcompleted_eventtrigger) {
                // Trigger an event to notify any listeners that this program has been completed.
                $event = \totara_program\event\program_completed::create(
                    array(
                        'objectid' => $this->id,
                        'context' => context_program::instance($this->id),
                        'userid' => $userid,
                        'other' => array(
                            'certifid' => isset($this->certifid) ? $this->certifid : 0,
                        ),
                    )
                );
                $event->trigger();
            }

            return $update_success;

        } else {

            $now = time();

            $completion = new stdClass();
            $completion->programid = $this->id;
            $completion->userid = $userid;
            $completion->coursesetid = 0;
            $completion->status = STATUS_PROGRAM_INCOMPLETE;
            $completion->timecompleted = 0;
            $completion->timestarted = !empty($minstarted) ? $minstarted : 0;
            $completion->timedue = 0;
            $completion->timecreated = $now;
            if ($progcompleted_eventtrigger) {
                // record the user's pos/org at time of completion
                $jobassignment = \totara_job\job_assignment::get_first($userid, false);
                if ($jobassignment) {
                    $completion->positionid = $jobassignment->positionid;
                    $completion->organisationid = $jobassignment->organisationid;
                } else {
                    $completion->positionid = 0;
                    $completion->organisationid = 0;
                }
            }

            foreach ($completionsettings as $key => $val) {
                $completion->$key = $val;
            }

            $insert_success = $DB->insert_record('prog_completion', $completion);

            prog_write_completion_log($completion->programid, $completion->userid, 'Program completion created by update_program_complete');

            if ($progcompleted_eventtrigger) {
                // Trigger an event to notify any listeners that this program has been completed.
                $event = \totara_program\event\program_completed::create(
                    array(
                        'objectid' => $this->id,
                        'context' => context_program::instance($this->id),
                        'userid' => $userid,
                        'other' => array(
                            'certifid' => isset($this->certifid) ? $this->certifid : 0,
                        )
                    )
                );
                $event->trigger();
            }

            return $insert_success;
        }
    }

    /**
     * Returns an array containing all the userids who are currently registered
     * on the program. Optionally, will only return a subset of users with a
     * specific completion status
     *
     * @param int $status One of STATUS_PROGRAM_INCOMPLETE or STATUS_PROGRAM_COMPLETE
     * @return array of userids for users in the program
     */
    public function get_program_learners($status=false) {
        global $DB;

        // If status is not false then add a check for it.
        if ($status !== false) {
            $statussql = 'AND status = ?';
            $statusparams = array((int)$status);
        } else {
            $statussql = '';
            $statusparams = array();
        }

        // Query to retrive any users who are registered on the program
        $sql = "SELECT id FROM {user} WHERE id IN
            (SELECT DISTINCT userid FROM {prog_completion}
            WHERE coursesetid = 0 AND programid = ? {$statussql})";
        $params = array_merge(array($this->id), $statusparams);

        return $DB->get_fieldset_sql($sql, $params);
    }

    /**
     * Calculates how far through the program a specific user is and returns
     * the result as a percentage
     *
     * @param int $userid
     * @return float
     */
    public function get_progress($userid) {
        // first check if the whole program has been completed
        if (prog_is_complete($this->id, $userid)) {
            return (float)100;
        }

        $certifpath = get_certification_path_user($this->certifid, $userid);
        $courseset_groups = $this->content->get_courseset_groups($certifpath, true);
        $courseset_group_count = count($courseset_groups);
        $courseset_group_complete_count = 0;

        foreach ($courseset_groups as $courseset_group) {
            if (prog_courseset_group_complete($courseset_group, $userid, false)) {
                $courseset_group_complete_count++;
            }
        }

        if ($courseset_group_count > 0) {
            return (float)($courseset_group_complete_count / $courseset_group_count) * 100;
        }
        return 0;
    }

    /**
     * Gets completion date for a user.
     *
     * @param int|stdClass $userorid
     * @return stdClass A record from the prog_completion table.
     */
    public function get_completion_data($userorid) {
        global $DB;
        if (is_object($userorid)) {
            $userid = $userorid->id;
        } else {
            $userid = $userorid;
        }

        $completion = $DB->get_record('prog_completion', array('programid' => $this->id, 'userid' => $userid, 'coursesetid' => 0));

        return $completion;
    }

    /**
     * Returns true or false depending on whether or not the specified user
     * can access the specified course based on whether or not the program
     * contains the course in any of its course sets in the current path and whether
     * or not the user has completed all pre-requisite groups of course sets.
     *
     * This function is intended only for use when determining if a user can be
     * enrolled into the given course. In certifications, when a user is on a path
     * which doesn't contain this course, this function will return false, regardless
     * of whether or not the user is already assigned to the course. If the user is
     * not assigned then they will not be allowed to become assigned.
     *
     * @param int $userid
     * @param int $courseid
     * @return bool
     */
    public function can_enter_course($userid, $courseid) {
        $now = time();
        $available = !isset($this->available) || $this->available == AVAILABILITY_NOT_TO_STUDENTS;
        $from = !empty($this->availablefrom) && $this->availablefrom > $now;
        $until = !empty($this->availableuntil) && $this->availableuntil < $now;

        // Don't allow access to courses through unavailable programs.
        if ($available || $from || $until) {
            return false;
        }

        $certifpath = get_certification_path_user($this->certifid, $userid);
        $courseset_groups = $this->content->get_courseset_groups($certifpath);

        $courseset_group_completed = false;
        $maxcompletedgroup = -1;
        $coursegroup = -1;

        // Find the last completed groupset, and which groupset the course is in.
        foreach ($courseset_groups as $groupkey => $courseset_group) {
            if ($thisgroupcomplete = prog_courseset_group_complete($courseset_group, $userid, false)) {
                $maxcompletedgroup = $groupkey;
            }
            foreach ($courseset_group as $courseset) {
                if ($coursegroup == -1 && $courseset->contains_course($courseid)) {
                    // Get the first occurance of the course.
                    $coursegroup = $groupkey;
                }
                if ($thisgroupcomplete && $courseset->contains_course($courseid)) {
                    // Create completion record if it does not exist.
                    $courseset->update_courseset_complete($userid, array());
                }
            }
        }

        // Allow access if the course is in the first group...
        if ($coursegroup == 0) {
            return true;
        } else if ($maxcompletedgroup >= 0 && $coursegroup > 0) {
            // Or an already completed group...
            if ($coursegroup <= $maxcompletedgroup) {
                return true;
            }
            // Or the next uncompleted group.
            if ($coursegroup == ($maxcompletedgroup+1)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Checks if the user has a pending extension request for this program
     *
     * @global object $DB
     * @param int $userid If specified then it indicates the user is assigned to the program.
     * @return bool
     */
    private function has_pending_extension_request($userid) {
        global $DB;

        if ($DB->get_record('prog_extension', array('userid' => $userid, 'programid' => $this->id, 'status' => 0))) {
            return true;
        }
        return false;
    }

    /**
     * Checks if the user is allowed to request an extension
     *
     * @global object $DB
     * @global object $CFG
     * @param int $userid If specified then it indicates the user is assigned to the program.
     * @return bool
     */
    public function can_request_extension($userid) {
        global $CFG, $USER;

        // Only if program extension request is enabled in site level and for this program instance.
        if (empty($CFG->enableprogramextensionrequests) || !$this->allowextensionrequests) {
            return false;
        }

        // User can request extension only for himself
        if ($userid != $USER->id) {
            return false;
        }

        $certexpired = false;
        if (isset($this->certifid) && $this->certifid > 0) {
            list($certifcompletion, $prog_completion) = certif_load_completion($this->id, $userid, false);
            if (!$prog_completion) {
                return false;
            }
            if ($certifcompletion) {
                $certifstate = certif_get_completion_state($certifcompletion);
                $certexpired = ($certifstate == CERTIFCOMPLETIONSTATE_EXPIRED);
            }
        } else {
            $prog_completion = prog_load_completion($this->id, $userid, false);
            if (!$prog_completion) {
                return false;
            }
        }

        // Only show the extension link if the user is assigned via required learning, and it
        // has a due date and they haven't completed it yet.
        // For certifications, also prevent extension requests after the expiry date.
        if ($this->assigned_to_users_required_learning($userid)
                && $prog_completion->timedue != COMPLETION_TIME_NOT_SET
                && $prog_completion->timecompleted == 0
                && \totara_job\job_assignment::has_manager($userid)
                && !$certexpired) {
            return true;
        }

        return false;
    }

    /**
     * Return the HTML markup for displaying the view of the program. This can
     * vary depending on whether or not the viewer is enrolled on the program
     * and if the viewer is viewing someone else's program.
     *
     * @global object $CFG
     * @global object $USER
     * @param int $userid If specified then it indicates the user is assigned to the program.
     * @return string
     */
    public function display($userid=null) {
        global $CFG, $DB, $USER, $OUTPUT, $PAGE;

        $iscertif = (isset($this->certifid) && $this->certifid > 0) ? true : false;

        // Div created to show notifications. Needed when requesting extensions.
        $out = html_writer::tag('div', '', array('id' => 'totara-header-notifications'));

        if (!prog_is_accessible($this)) {
            // Return if program is not accessible
            return html_writer::tag('p', get_string('programnotcurrentlyavailable', 'totara_program'));
        }

        $message = '';

        $viewinganothersprogram = false;
        if ($userid && $userid != $USER->id) {
            $viewinganothersprogram = true;
            if (!$user = $DB->get_record('user', array('id' => $userid))) {
                print_error('error:failedtofinduser', 'totara_program', $userid);
            }
            $user->fullname = fullname($user);
            $user->wwwroot = $CFG->wwwroot;
            $message .= html_writer::tag('p', get_string('viewingxusersprogram', 'totara_program', $user));
        }

        $userassigned = $this->user_is_assigned($userid);

        if ($userassigned) {
            // Display the reason why this user has been assigned to the program (if it is mandatory for the user).
            $message .= $this->display_required_assignment_reason($userid);
        }

        // Show message box if there are any messages.
        if (!empty($message)) {
            $out .= html_writer::tag('div', $message, array('class' => 'notifymessage'));
        }

        if ($iscertif) {
            list($certifcompletion, $prog_completion) = certif_load_completion($this->id, $userid, false);

            if ($certifcompletion) {
                $certifstate = certif_get_completion_state($certifcompletion);
                $now = time();
                $out .= html_writer::start_tag('p', array('class' => 'certifpath'));
                if ($certifcompletion->certifpath == CERTIFPATH_CERT) {
                    if ($certifcompletion->renewalstatus == CERTIFRENEWALSTATUS_EXPIRED) {
                        $out .= get_string('certexpired', 'totara_certification');
                    } else {
                        $out .= get_string('certinprogress', 'totara_certification');
                    }
                } else {
                    if ($certifstate == CERTIFCOMPLETIONSTATE_CERTIFIED && $now > $certifcompletion->timewindowopens + DAYSECS) {
                        $out .= get_string('currentlycertified', 'totara_certification');
                        $lateopenwarning = get_string('recertwindowopendateverylate', 'totara_certification',
                            userdate($certifcompletion->timewindowopens, get_string('strftimedatetime', 'langconfig')));
                        $out .= html_writer::tag('div', $lateopenwarning, array('class' => 'notifyproblem'));

                    } else if ($certifstate == CERTIFCOMPLETIONSTATE_CERTIFIED && $now > $certifcompletion->timewindowopens) {
                        $out .= get_string('currentlycertified', 'totara_certification');
                        $lateopenwarning = get_string('recertwindowopendatelate', 'totara_certification',
                            userdate($certifcompletion->timewindowopens, get_string('strftimedatetime', 'langconfig')));
                        $out .= html_writer::tag('div', $lateopenwarning, array('class' => 'notifyproblem'));

                    } else if ($certifstate == CERTIFCOMPLETIONSTATE_CERTIFIED) {
                        $out .= get_string('currentlycertified', 'totara_certification');
                        $out .= get_string('recertwindowopendate', 'totara_certification',
                            userdate($certifcompletion->timewindowopens, get_string('strftimedatetime', 'langconfig')));

                    } else {
                        $out .= get_string('recertwindowopen', 'totara_certification');
                        $out .= get_string('recertwindowexpiredate', 'totara_certification',
                            userdate($certifcompletion->timeexpires, get_string('strftimedatetime', 'langconfig')));
                    }
                }
                $out .= html_writer::end_tag('p');
            }
        } else {
            $prog_completion = prog_load_completion($this->id, $userid, false);
        }

        // display the start date, due date and progress bar.
        if ($userassigned) {

            // Setup the request extension link.
            $request = '';

            if ($this->can_request_extension($userid)) {
                if ($this->has_pending_extension_request($userid)) {
                    // Show pending text if they have already requested an extension.
                    $request = ' ' . get_string('pendingextension', 'totara_program');
                } else {
                    // Show extension request link if it is their assignment and they have a manager to request it from.
                    $url = new moodle_url('/totara/program/view.php', array('id' => $this->id, 'extrequest' => '1'));
                    $request = ' ' . html_writer::link($url, get_string('requestextension', 'totara_program'), array('id' => 'extrequestlink'));
                }
            }

            if ($prog_completion) {
                $timedue = $prog_completion->timedue;
                $startdatestr = ($prog_completion->timecreated != 0
                                ? $this->display_date_as_text($prog_completion->timecreated)
                                : get_string('unknown', 'totara_program'));
                if ($iscertif) {
                    $duedatestr = prog_display_duedate($timedue, $this->id, $prog_completion->userid, $certifcompletion->certifpath, $certifcompletion->status);
                } else {
                    $duedatestr = prog_display_duedate($timedue, $this->id, $prog_completion->userid);
                }
                $duedatestr .= $request;

                $out .= html_writer::start_tag('div', array('id' => 'progressbar', 'class' => 'programprogress'));
                $out .= html_writer::tag('div', get_string('dateassigned', 'totara_program') . ': '
                                . $startdatestr, array('class' => 'item'));
                $out .= html_writer::tag('div', get_string('duedate', 'totara_program').': '
                                . $duedatestr, array('class' => 'item'));
                $out .= html_writer::tag('div', get_string('progress', 'totara_program') . ': '
                                . prog_display_progress($this->id, $userid), array('class' => 'item'));
                $out .= html_writer::end_tag('div');
            }
        }

        // Get summary and overview files.
        $programrenderer = $PAGE->get_renderer('totara_program');
        $progobj = new stdClass();
        $progobj->id = $this->id;
        $summary = $programrenderer->coursecat_programbox_content(new programcat_helper(), new program_in_list($progobj));
        $out .= $summary;
/* TODO: This needs fixing to work with the new catalogue
 *
        $summary = file_rewrite_pluginfile_urls($this->summary, 'pluginfile.php',
            context_program::instance($this->id)->id, 'totara_program', 'summary', 0);
        $out .= html_writer::tag('div', $summary, array('class' => 'summary'));
 t2-feature-certification */

        // course sets - for certify or recertify paths
        if ($iscertif) {
            // Before window opens, ideally we'd show the last path that they completed, but assuming the recert path because
            // of an existing history record is inaccurate, due to expiry and import. So instead we'll show both paths.
            if (is_siteadmin() || !$certifcompletion || $certifstate == CERTIFCOMPLETIONSTATE_CERTIFIED) {
                $out .= $OUTPUT->heading(get_string('oricertpath', 'totara_certification'), 2);
                $out .= $this->display_courseset(CERTIFPATH_CERT, $userid, $viewinganothersprogram);

                $out .= html_writer::start_tag('div', array('class' => 'programrecert'));
                $out .= $OUTPUT->heading(get_string('recertpath', 'totara_certification'), 2);
                $out .= html_writer::end_tag('div');

                $out .= $this->display_courseset(CERTIFPATH_RECERT, $userid, $viewinganothersprogram);
            } else { // Has a certification completion record.
                if ($certifcompletion->certifpath == CERTIFPATH_CERT) {
                    $out .= $OUTPUT->heading(get_string('oricertpath', 'totara_certification'), 2);
                    $out .= $this->display_courseset(CERTIFPATH_CERT, $userid, $viewinganothersprogram);
                } else {
                    $out .= html_writer::start_tag('div', array('class' => 'programrecert'));
                    $out .= $OUTPUT->heading(get_string('recertpath', 'totara_certification'), 2);
                    $out .= html_writer::end_tag('div');
                    $out .= $this->display_courseset(CERTIFPATH_RECERT, $userid, $viewinganothersprogram);
                }
            }
        } else {
            $out .= $this->display_courseset(CERTIFPATH_STD, $userid, $viewinganothersprogram);
        }

        // only show end note when a program is complete
        if ($prog_completion && $prog_completion->status == STATUS_PROGRAM_COMPLETE) {
            $out .= html_writer::start_tag('div', array('class' => 'programendnote'));
            $out .= $OUTPUT->heading(get_string('programends', 'totara_program'), 2);
            $out .= html_writer::tag('div',
                file_rewrite_pluginfile_urls($this->endnote, 'pluginfile.php', $this->context->id, 'totara_program', 'endnote', 0),
                array('class' => 'endnote'));
            $out .= html_writer::end_tag('div');
        }

        return $out;
    }

    /**
     * Display the course set groups of a given program or certification path.
     *
     * @param $certifpath
     * @param $userid If specified then it indicates the user is assigned to the program.
     * @param $viewinganothersprogram
     * @return string
     */
    function display_courseset($certifpath, $userid, $viewinganothersprogram) {
        $out = '';
        $courseset_groups = $this->content->get_courseset_groups($certifpath);

        // check if this is a recurring program
        if (count($courseset_groups) == 0) {
            $out .= html_writer::tag('p', get_string('nocoursecontent', 'totara_program'), array('class' => 'nocontent'));
        } else if (count($courseset_groups) == 1 && ($courseset_groups[0][0]->contenttype == CONTENTTYPE_RECURRING)) {
            $out .= $courseset_groups[0][0]->display($userid);
        } else {

            // Maintain a list of previous and future courseset, for use later
            $previous = array();
            $next = array();

            // get the course sets for this program
            $coursesets = $this->content->get_course_sets();

            // set up the array of next coursesets for use  later
            foreach ($coursesets as $courseset) {
                $next[] = $courseset;
            }

            // flag to determine whether or not to display active links to
            // courses in the course set groups in the program. The first group
            // will always be accessible.
            $courseset_group_accessible = true;;

            // display each course set
            foreach ($courseset_groups as $courseset_group) {

                // display each course set
                $prevnextsetoperator = '';
                foreach ($courseset_group as $courseset) {
                    $previous[] = $courseset;
                    $next = array_splice($next, 1);

                    if ($courseset->nextsetoperator == NEXTSETOPERATOR_AND && $prevnextsetoperator != NEXTSETOPERATOR_AND) {
                        // Group ANDs.
                        $out .= html_writer::start_tag('div', array('class' => 'nextsetoperator-group-and'));
                    }

                    $out .= $courseset->display($userid, $previous, $next, $courseset_group_accessible, $viewinganothersprogram);

                    if ($prevnextsetoperator == NEXTSETOPERATOR_AND && $courseset->nextsetoperator != NEXTSETOPERATOR_AND) {
                        $out .= html_writer::end_tag('div');
                    }

                    $out .= $courseset->display_nextsetoperator();

                    $prevnextsetoperator = $courseset->nextsetoperator;
                }

                // check if the current course set group is complete. If not,
                // set a flag to prevent access to the courses in the following
                // course sets
                $courseset_group_accessible = prog_courseset_group_complete($courseset_group, $userid, false) ? true : false;
            }
        }
        return $out;
    }

    /**
     * Display the due date for a program
     *
     * @param int $duedate
     * @param int $userid
     * @param int $certifpath   Optional param telling us the path of the certification
     * @param int $certstatus   Optional param telling us the status of the certification
     * @return string
     */
    function display_duedate($duedate, $userid, $certifpath = null, $certstatus = null) {
        debugging('$program->display_duedate() is deprecated, use the lib function display_duedate() instead', DEBUG_DEVELOPER);
        prog_display_duedate($duedate, $this->id, $userid, $certifpath, $certstatus);
    }

    /**
     * Display a date as text
     *
     * @param int $mydate
     * @return string
     */
    function display_date_as_text($mydate) {
        global $CFG;

        if (isset($mydate)) {
            return userdate($mydate, get_string('strftimedate', 'langconfig'), 99, false);
        } else {
            return '';
        }
    }

    /**
     * Display due date for a program with task info
     *
     * @param int $duedate
     * @return string
     */
    function display_duedate_highlight_info($duedate) {
        global $PAGE;

        debugging('$program->display_duedate_highligh_info() is deprecated, use the renderer function display_duedate_highlight_info() instead', DEBUG_DEVELOPER);
        $renderer = $PAGE->get_renderer('totara_program');
        $renderer->display_duedate_highlight_info($duedate);
    }

    /**
     * Determines and displays the progress of this program for a specified user.
     *
     * Progress is determined by course set completion statuses.
     *
     * @access  public
     * @param int $userid
     * @return  string
     */
    public function display_progress($userid) {
        // Deprecate instead of remove incase someone is using this.
        debugging('$program->display_progress() is deprecated, use the lib function prog_display_progress() instead', DEBUG_DEVELOPER);
        prog_display_progress($this->id, $userid);
    }

    public function display_timedue_date($completionstatus, $time, $format = '') {
        global $OUTPUT;

        if ($time == 0 || $time == COMPLETION_TIME_NOT_SET) {
            return get_string('noduedate', 'totara_plan');;
        }

        if (empty($format)) {
            $format = get_string('strftimedatefulllong', 'langconfig');
        }

        $out = userdate($time, $format);

        $days = '';
        if ($completionstatus != STATUS_PROGRAM_COMPLETE) {
            $days_remaining = floor(($time - time()) / DAYSECS);
            if ($days_remaining == 1) {
                $days = get_string('onedayremaining', 'totara_program');
            } else if ($days_remaining < 10 && $days_remaining > 0) {
                $days = get_string('daysremaining', 'totara_program', $days_remaining);
            } else if ($time < time()) {
                $days = get_string('overdue', 'totara_plan');
            }
        }
        if ($days != '') {
            $out .= html_writer::empty_tag('br') . $OUTPUT->error_text($days);
        }

        return $out;
    }

    public function display_link_program_icon($programname, $program_id, $program_icon, $userid = null) {
        // Deprecate instead of remove incase someone is using this.
        debugging('$program->display_link_program_icon() is deprecated, use the lib function prog_display_link_icon() instead', DEBUG_DEVELOPER);
        prog_display_link_icon($program_id, $userid);
    }

    /**
     * Generates the HTML to display the current number of exceptions and a link
     * to the exceptions report for the program
     *
     * @return string
     */
    public function display_exceptions_link() {
        global $PAGE;
        $out = '';
        $exceptionscount = $this->exceptionsmanager->count_exceptions();
        if ($exceptionscount && $exceptionscount>0) {
            $url = new moodle_url('/totara/program/exceptions.php', array('id' => $this->id));
            $renderer = $PAGE->get_renderer('totara_program');
            $out .= $renderer->print_exceptions_link($url, $exceptionscount);
        }
        return $out;
    }

    public function get_exception_count() {
        $exceptionscount = $this->exceptionsmanager->count_exceptions();

        if ($exceptionscount) {
            return $exceptionscount;
        } else {
            return false;
        }
    }

    /**
     * Generates the HTML to display the current stats of the program (live,
     * not available, etc)
     *
     * @return string
     */
    public function display_current_status() {
        global $PAGE, $CFG, $DB;
        require_once($CFG->dirroot . '/totara/cohort/lib.php');

        $data = new stdClass();
        $data->assignments = $this->assignments->count_active_user_assignments();
        $data->exceptions = $this->assignments->count_user_assignment_exceptions();
        $data->total = $this->assignments->count_total_user_assignments();
        $data->audiencevisibilitywarning = false;
        $data->assignmentsdeferred = $this->assignmentsdeferred;

        if (!empty($CFG->audiencevisibility)) {
            $coursesnovisible = $this->content->get_visibility_coursesets(TOTARA_SEARCH_OP_NOT_EQUAL, COHORT_VISIBLE_ALL);
            if (!empty($coursesnovisible)) {
                // Notify if there are courses in this program which don't have audience visibility to all.
                $data->audiencevisibilitywarning = true;
            }

            $audiencesql = "SELECT cm.id
                      FROM {cohort_visibility} cv
                      JOIN {cohort_members} cm
                        ON cv.cohortid = cm.cohortid
                       AND cv.instanceid = ?
                       AND cv.instancetype IN (?, ?)";
            $audienceparams = array($this->id, COHORT_ASSN_ITEMTYPE_PROGRAM, COHORT_ASSN_ITEMTYPE_CERTIF);
            if ($this->audiencevisible == COHORT_VISIBLE_ALL ||
                $data->assignments > 0 ||
                $this->audiencevisible == COHORT_VISIBLE_AUDIENCE && $DB->record_exists_sql($audiencesql, $audienceparams)) {
                $data->statusstr = 'programlive';
                $data->statusclass = 'notifynotice';
            } else {
                $data->statusstr = 'programnotlive';
                $data->statusclass = 'notifymessage';
            }

        } else {
            if ($this->visible ||
                $data->assignments > 0) {
                $data->statusstr = 'programlive';
                $data->statusclass = 'notifynotice';
            } else {
                $data->statusstr = 'programnotlive';
                $data->statusclass = 'notifymessage';
            }
        }

        if (!prog_is_accessible($this)) {
            $data->statusstr = 'programnotavailable';
        }

        $now = time();
        if (!empty($this->availablefrom) and ($now < $this->availablefrom)) {
            $data->statusstr = 'notduetostartuntil';
        }

        if (!empty($this->availableuntil) and ($now > $this->availableuntil)) {
            $data->statusstr = 'nolongeravailabletolearners';
        }

        $renderer = $PAGE->get_renderer('totara_program');
        return $renderer->render_current_status($data);
    }

    /**
     * Determines whether this program is viewable by the logged in user, or
     * the user passed in as the first parameter. This does not care whether
     * the user is enrolled or not.
     *
     * @global object $USER
     * @param object $user
     * @return boolean
     */
    public function is_viewable($user = null) {
        global $USER, $CFG, $DB;
        require_once($CFG->dirroot . '/totara/cohort/lib.php');

        if ($user == null) {
            $user = $USER;
        }

        if (empty($this->certifid)) {
            $isprogram = true;
            $instancetype = 'program';
        } else {
            $isprogram = false;
            $instancetype = 'certification';
        }

        list($visibilityjoinsql, $visibilityjoinparams) = totara_visibility_join($user->id, $instancetype, 'p');
        $params = array_merge(array('itemcontext' => CONTEXT_PROGRAM, 'instanceid' => $this->id), $visibilityjoinparams);

        // Get context data for preload.
        $ctxfields = context_helper::get_preload_record_columns_sql('ctx');
        $ctxjoin = "LEFT JOIN {context} ctx ON (ctx.instanceid = p.id AND ctx.contextlevel = :itemcontext)";

        $sql = "SELECT p.id, {$ctxfields}, visibilityjoin.isvisibletouser
                  FROM {prog} p
                       {$visibilityjoinsql}
                       {$ctxjoin}
                 WHERE p.id = :instanceid";

        $programs = $DB->get_records_sql($sql, $params);

        // Look for a program that is visible (should be checking either 0 or 1 records).
        foreach ($programs as $id => $program) {
            if ($program->isvisibletouser) {
                return true;
            } else {
                context_helper::preload_from_record($program);
                $context = context_program::instance($id);
                if ($isprogram && has_capability('totara/program:viewhiddenprograms', $context) ||
                    !$isprogram && has_capability('totara/certification:viewhiddencertifications', $context) ||
                    !empty($CFG->audiencevisibility) && has_capability('totara/coursecatalog:manageaudiencevisibility', $context)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Checks accessibility of the program for user if the user parameter is
     * passed to the function otherwise checks if the program is generally
     * accessible.
     *
     * @deprecated since Totara 10
     * @param object $user If this parameter is included check availability to this user
     * @return boolean
     */
    public function is_accessible($user = null) {
        debugging('$program->is_accessible() is deprecated, use the lib function prog_is_accessible() instead', DEBUG_DEVELOPER);
        return prog_is_accessible($this, $user);
    }

    /**
     * Checks for exceptions given an assignment
     *
     */
    public function update_exceptions($userid, $assignment, $timedue) {
        // Changes are being made so old exceptions are no longer relevant.
        prog_exceptions_manager::delete_exceptions_by_assignment($assignment->id, $userid);
        $now = time();

        if ($this->assigned_to_users_non_required_learning($userid)) {
            $this->exceptionsmanager->raise_exception(EXCEPTIONTYPE_ALREADY_ASSIGNED, $userid, $assignment->id, $now);
            return true;
        }

        if ($this->duplicate_course($userid)) {
            $this->exceptionsmanager->raise_exception(EXCEPTIONTYPE_DUPLICATE_COURSE, $userid, $assignment->id, $now);
            return true;
        }

        if ($timedue == COMPLETION_TIME_UNKNOWN) {
            $this->exceptionsmanager->raise_exception(EXCEPTIONTYPE_COMPLETION_TIME_UNKNOWN, $userid, $assignment->id, $now);
            return true;
        }

        if ($timedue != COMPLETION_TIME_NOT_SET) {
            $certifpath = get_certification_path_user($this->certifid, $userid);
            if ($certifpath == CERTIFPATH_UNSET) {
                $certifpath = CERTIFPATH_CERT;
            }
            $total_time_allowed = $this->content->get_total_time_allowance($certifpath);
            $time_until_duedate = $timedue - $now;

            if ($time_until_duedate < $total_time_allowed) {
                $this->exceptionsmanager->raise_exception(EXCEPTIONTYPE_TIME_ALLOWANCE, $userid, $assignment->id, $now);
                return true;
            }
        }

        return false;
    }

    /**
     * Checks if this program is required learning for
     * given user or current user
     *
     * @param int $userid User ID to check (optional)
     * @return bool Returns true if this program is required learning
     */
    public function is_required_learning($userid = 0) {
        global $USER;

        // Deprecate instead of remove incase someone is using this.
        debugging('$program->is_required_learning() is deprecated, use the lib function prog_required_for_user() instead', DEBUG_DEVELOPER);

        $userid = !empty($userid) ? $userid : $USER->id;
        return prog_required_for_user($this->id, $userid);
    }

    /**
     * Checks if a user is assigned to a program
     *
     * @param int $userid
     * @param bool Returns true if a learner is assigned to a program
     */
    public function user_is_assigned($userid) {
        global $DB;

        if (!$userid) {
            return false;
        }

        // Check if there is a user assignment
        // (user is assigned to program in admin interface)
        list($usql, $params) = $DB->get_in_or_equal(array(PROGRAM_EXCEPTION_NONE, PROGRAM_EXCEPTION_RESOLVED));
        $params[] = $this->id;
        $params[] = $userid;
        $record_count = $DB->count_records_select('prog_user_assignment', " exceptionstatus $usql AND programid = ? AND userid = ?", $params);
        if ($record_count > 0) {
            return true;
        }

        // Check if the program is part of a learning plan
        if ($this->assigned_through_plan($userid)) {
            return true;
        }

        return false;
    }

    /**
     * Checks whether a user is currently unassigned but has an existing
     * assignment with an exception which was previously dismissed.
     *
     * @param int $userid
     * @return bool
     */
    public function check_user_for_dismissed_exceptions($userid) {
        global $DB;

        $assigned = $this->user_is_assigned($userid);

        $params = array('programid' => $this->id, 'userid' => $userid, 'exceptionstatus' => PROGRAM_EXCEPTION_DISMISSED);
        $dismissed = $DB->record_exists('prog_user_assignment', $params);

        return (!$assigned && $dismissed);
    }

    /**
     * Checks to see if a program is assigned to a user
     * through a plan and approved
     *
     * @param int $userid
     * @return bool Returns true if program is assigned to user
     */
    public function assigned_through_plan($userid) {
        global $DB, $CFG;

        require_once($CFG->dirroot . '/totara/plan/lib.php');

        $sql = "SELECT COUNT(*) FROM
                {dp_plan} p
            JOIN
                {dp_plan_program_assign} pa
            ON
                p.id = pa.planid
            WHERE
                p.userid = ?
            AND pa.programid = ?
            AND pa.approved = ?
            AND p.status >= ?";
        $params = array($userid, $this->id, DP_APPROVAL_APPROVED, DP_PLAN_STATUS_APPROVED);
        if ($DB->count_records_sql($sql, $params) > 0) {
            return true;
        }

        return false;
    }

    /**
     * Display the reason why this user has been assigned to the program (if it is mandatory for the user).
     *
     * @param int $userid - id of the user.
     * @param bool $includefull - include the full message to be output. Set to false if this output will be included
     *  in a larger unordered list where those tags will be created separately.
     * @return string
     * @throws UserAssignmentException
     * @throws coding_exception
     */
    public function display_required_assignment_reason($userid, $includefull = true) {
        global $DB, $USER;

        $message = '';

        $user_assignments = $DB->get_records_select('prog_user_assignment', "programid = ? AND userid = ?", array($this->id, $userid));

        if (count($user_assignments) > 0) {
            if ($includefull) {
                if ($USER->id == $userid) {
                    // Viewing user's own records.
                    $message .= html_writer::tag('p', get_string('assignmentcriterialearner', 'totara_program'));
                } else {
                    // Viewing another's records.
                    $message .= html_writer::tag('p', get_string('assignmentcriteriamanager', 'totara_program'));
                }
                $message .= html_writer::start_tag('ul');
            }
            foreach ($user_assignments as $user_assignment) {
                if ($assignment = $DB->get_record('prog_assignment', array('id' => $user_assignment->assignmentid))) {
                    /** @var prog_user_assignment $user_assignment_ob */
                    $user_assignment_ob = prog_user_assignment::factory($assignment->assignmenttype, $user_assignment->id);
                    $message .= $user_assignment_ob->display_criteria();
                }
            }
            if ($includefull) {
                $message .= html_writer::end_tag('ul');
            }
        }

        return $message;
    }

    /**
     * Display reasons for why a user is assigned. Most common reason
     * is that they are assigned, as returned by $this->display_required_assignment_reason(), but
     * also tries to find other reasons if nothing comes back from that.
     *
     * @param stdClass $user - a user record.
     * @param null|stdClass $unused since Totara 10, 9.8, 2.9.20, 2.7.28, 2.6.45, 2.5.52
     * @return string explaining why completion record exists.
     * @throws coding_exception
     */
    public function display_completion_record_reason($user, $unused = null) {
        if (!is_null($unused)) {
            debugging('program::display_completion_record_reason() was passed a value in unused second paramter', DEBUG_DEVELOPER);
        }

        global $DB;
        $reasonlist = '';

        $userassigned = $this->user_is_assigned($user->id);
        if ($userassigned) {
            $reasonlist .= $this->display_required_assignment_reason($user->id, false);
        }

        // The display_required_assignment_reason method doesn't currently show a message if
        // the user is assigned via a plan as that is not considered required learning.
        if ($this->assigned_through_plan($user->id)) {
            $reasonlist .= html_writer::tag('li', get_string('assignedvialearningplan', 'totara_program'));
        }

        if (!empty($reasonlist)) {
            // We have found assignment records or similar reasons and added them to the message. Return
            // those so they can be displayed.
            $message = html_writer::tag('p', get_string('completionassignedbecause', 'totara_program'))
                 . html_writer::tag('ul', $reasonlist);
            if ($user->suspended) {
                // If the user is suspended, things such setting their window to open won't work,
                // so we'll point that out.
                $message .= html_writer::tag('p', get_string('completionrecordusersuspended', 'totara_program'));
            }
            return $message;
        } else {
            // Let's go through some other reasons why they might have a record.
            if ($user->deleted) {
                return html_writer::tag('p', get_string('completionassignedreasondeleted', 'totara_program'));
            }

            // They may have added a program to their learning plan. If it was approved, this
            // would have been added as a reason above. So if we find any other record, it
            // should be unapproved.
            $sql = "SELECT *
                      FROM {dp_plan} p
                      JOIN {dp_plan_program_assign} pa
                        ON p.id = pa.planid
                     WHERE p.userid = ?
                       AND pa.programid = ?";
            $params = array($user->id, $this->id);
            if ($DB->record_exists_sql($sql, $params)) {
                $message = html_writer::tag('p', get_string('completionassignedreasonunapprovedplan', 'totara_program'));
            }
        }

        if (empty($message)) {
            // Still nothing found.
            // The default is to say a completion record exists but no reason was found.
            $message = html_writer::tag('p', get_string('completionassignedreasonnotfound', 'totara_program'));
        }

        if ($user->suspended) {
            $message .= html_writer::tag('p', get_string('completionrecordusersuspended', 'totara_program'));
        }

        return $message;
    }

    /**
     * Checks if certifications/programs are enabled depending on what type
     * this is. Throws an exception if they're not.
     *
     * @throws moodle_exception
     */
    public function check_enabled() {
        // Check if programs or certifications are enabled.
        if ($this->is_certif()) {
            check_certification_enabled();
        } else {
            check_program_enabled();
        }
    }

    /**
     * Returns true if this is a certification, or false if not.
     *
     * @return bool
     */
    public function is_certif() {
        return !empty($this->certifid);
    }

    /**
     * Returns whether a program has passed its end date.
     *
     * @return bool true if program no longer available.
     */
    public function has_expired() {
        return (!empty($this->availableuntil) and (time() > $this->availableuntil));
    }

    /**
     * Returns all programs that have one or more users assigned who have not yet completed the program.
     * @return program[]
     */
    public static function get_all_programs_with_incomplete_users() {
        global $DB;
        // OK we want to find all programs that have at least one user assigned who has not completed the program already.
        // Once a user has completed the program we no longer need to check that user. Complete is complete.
        // Preloading the program contexts is going to save us 1 query per program.
        $contextsql = context_helper::get_preload_record_columns_sql('ctx');
        $sql = "SELECT p.*, {$contextsql}
                  FROM {prog} p
             LEFT JOIN {context} ctx ON ctx.instanceid = p.id AND ctx.contextlevel = :progctxlevel
                  JOIN (
                    SELECT DISTINCT programid
                      FROM {prog_completion}
                     WHERE coursesetid = 0 AND status = :statusincomplete
                       ) pc ON pc.programid = p.id";
        $params = [
            'progctxlevel' => CONTEXT_PROGRAM,
            'statusincomplete' => STATUS_PROGRAM_INCOMPLETE
        ];
        $rs = $DB->get_recordset_sql($sql, $params);
        $programs = array();
        foreach ($rs as $program_record) {
            context_helper::preload_from_record($program_record);
            $program = new self($program_record);
            $programs[$program->id] = $program;
        }
        $rs->close();
        return $programs;
    }

    /**
     * Checks if a user has one of the capabilities that allows them to view the program
     * or cert overview page for the relevant context.
     *
     * @param null|integer|stdClass $user to check the capability for. Is passed straight in to
     *  has_any_capability.
     * @return bool true if the user is allowed to access the page.
     */
    public function has_capability_for_overview_page($user = null) {
        global $CFG;

        $allowed_capabilities = array(
            'totara/program:configuredetails',
            'totara/program:configurecontent',
            'totara/program:configuremessages',
            'totara/program:configureassignments',
            'totara/program:handleexceptions'
        );

        if ($this->is_certif()) {
            $allowed_capabilities[] = 'totara/certification:configurecertification';
        }

        if (!empty($CFG->enableprogramcompletioneditor)) {
            $allowed_capabilities[] = 'totara/program:editcompletion';
        }

        return has_any_capability($allowed_capabilities, $this->get_context(), $user);
    }
}

/**
 * Class providing various utility functions for use by programs but which can
 * be used independently of and without instantiating a program object
 */
class program_utilities {

    /**
     * Given an integer and a time period (e.g. a day = 60*60*24) this function
     * calculates the length covered by the period and returns returns it as a
     * timestamp
     *
     * E.g. if $num = 4 and $period = 1 (hours) then the timestamp returned
     * would be the equivalent of 4 hours.
     *
     * @param int $num The number of units of the time pariod to calculate
     * @param int $period An integer denoting the time period (hours, days, weeks, etc)
     * @return int A timestamp
     */
    public static function duration_implode($num, $period) {

        $duration = 0;

        if ($period == TIME_SELECTOR_YEARS) {
            $duration = $num * DURATION_YEAR;
        } else if ($period == TIME_SELECTOR_MONTHS) {
            $duration = $num * DURATION_MONTH;
        } else if ($period == TIME_SELECTOR_WEEKS) {
            $duration = $num * DURATION_WEEK;
        } else if ($period == TIME_SELECTOR_DAYS) {
            $duration = $num * DURATION_DAY;
        } else if ($period == TIME_SELECTOR_HOURS) {
            $duration = $num * DURATION_HOUR;
        } else {
            $duration = 0;
        }

        return $duration;
    }

    /**
     * Given a timestamp representing a duration, this function factors the
     * timestamp out into a time period (e.g. an hour, a day, a week, etc)
     * and the number of units of the time period.
     *
     * This is mainly for use in forms which provide 2 fields for specifying
     * a duration.
     *
     * @global array $TIMEALLOWANCESTRINGS
     * @param int $duration
     * @return object Containing $num and $period properties
     */
    public static function duration_explode($duration) {
        global $TIMEALLOWANCESTRINGS;

        $ob = new stdClass();

        if ($duration == 0) {
            $ob->num = 0;
            $ob->period = TIME_SELECTOR_NOMINIMUM;
        } else if ($duration % DURATION_YEAR == 0) {
            $ob->num = $duration / DURATION_YEAR;
            $ob->period = TIME_SELECTOR_YEARS;
        } else if ($duration % DURATION_MONTH == 0) {
            $ob->num = $duration / DURATION_MONTH;
            $ob->period = TIME_SELECTOR_MONTHS;
        } else if ($duration % DURATION_WEEK == 0) {
            $ob->num = $duration / DURATION_WEEK;
            $ob->period = TIME_SELECTOR_WEEKS;
        } else if ($duration % DURATION_DAY == 0) {
            $ob->num = $duration / DURATION_DAY;
            $ob->period = TIME_SELECTOR_DAYS;
        } else if ($duration % DURATION_HOUR == 0) {
            $ob->num = $duration / DURATION_HOUR;
            $ob->period = TIME_SELECTOR_HOURS;
        } else {
            $ob->num = 0;
            $ob->period = 0;
        }

        if (array_key_exists($ob->period, $TIMEALLOWANCESTRINGS)) {
            $ob->periodstr = strtolower(get_string($TIMEALLOWANCESTRINGS[$ob->period], 'totara_program'));
        } else {
            $ob->periodstr = '';
        }

        return $ob;

    }

    /**
     * Given a timestamp representing a duration, this function factors the
     * timestamp out into a time period (e.g. an hour, a day, a week, etc)
     * and the number of units of the time period.
     *
     * The period is included in two forms:
     * $period - A constant such as TIME_SELECTOR_YEARS.
     * $periodkey - A string such as 'years' (not translated, but might be used as part of
     *  a lang string key).
     *
     * @param int $duration
     * @return object Containing $num, $period and $periodkey properties
     */
    public static function get_duration_num_and_period($duration) {
        $object = new stdClass();

        if ($duration == 0) {
            $object->num = 0;
            $object->period = TIME_SELECTOR_NOMINIMUM;
            $object->periodkey = 'nominimum';
        } else if ($duration % DURATION_YEAR == 0) {
            $object->num = $duration / DURATION_YEAR;
            $object->period = TIME_SELECTOR_YEARS;
            $object->periodkey = 'years';
        } else if ($duration % DURATION_MONTH == 0) {
            $object->num = $duration / DURATION_MONTH;
            $object->period = TIME_SELECTOR_MONTHS;
            $object->periodkey = 'months';
        } else if ($duration % DURATION_WEEK == 0) {
            $object->num = $duration / DURATION_WEEK;
            $object->period = TIME_SELECTOR_WEEKS;
            $object->periodkey = 'weeks';
        } else if ($duration % DURATION_DAY == 0) {
            $object->num = $duration / DURATION_DAY;
            $object->period = TIME_SELECTOR_DAYS;
            $object->periodkey = 'days';
        } else if ($duration % DURATION_HOUR == 0) {
            $object->num = $duration / DURATION_HOUR;
            $object->period = TIME_SELECTOR_HOURS;
            $object->periodkey = 'hours';
        } else {
            throw new ProgramException('Unrecognised datetime');
        }

        return $object;
    }

    /**
     * Prints or returns the html for the time allowance fields
     *
     * @param <type> $prefix
     * @param <type> $periodvalue
     * @param <type> $numbervalue
     * @param <type> $return
     * @return <type>
     */
    public static function print_duration_selector($prefix, $periodelementname, $periodvalue, $numberelementname, $numbervalue, $includehours=true) {

        $timeallowances = array();
        if ($includehours) {
            $timeallowances[TIME_SELECTOR_HOURS] = get_string('hours', 'totara_program');
        }
        $timeallowances[TIME_SELECTOR_DAYS] = get_string('days', 'totara_program');
        $timeallowances[TIME_SELECTOR_WEEKS] = get_string('weeks', 'totara_program');
        $timeallowances[TIME_SELECTOR_MONTHS] = get_string('months', 'totara_program');
        $timeallowances[TIME_SELECTOR_YEARS] = get_string('years', 'totara_program');
        if ($periodvalue == '') { $periodvalue = '' . TIME_SELECTOR_DAYS; }
        $m_name = $prefix.$periodelementname;
        $m_id = $prefix.$periodelementname;
        $m_selected = $periodvalue;
        $m_nothing = '';
        $m_nothingvalue = '';
        $m_disabled = false;
        $m_tabindex = 0;

        $out = '';
        $out .= html_writer::empty_tag('input', array('type' => 'text', 'id' => $prefix.$numberelementname, 'name' => $prefix.$numberelementname, 'value' => $numbervalue, 'size' => '4', 'maxlength' => '3'));

        $attributes = array();
        $attributes['disabled'] = $m_disabled;
        $attributes['tabindex'] = $m_tabindex;
        $attributes['multiple'] = null;
        $attributes['class'] = null;
        $attributes['id'] = $m_id;
        $out .= html_writer::select($timeallowances, $m_name, $m_selected, array($m_nothingvalue=>$m_nothing), $attributes);

        return $out;
    }

    public static function get_standard_time_allowance_options($includenominimum=false) {
        $timeallowances = array(
            TIME_SELECTOR_DAYS => get_string('days', 'totara_program'),
            TIME_SELECTOR_WEEKS => get_string('weeks', 'totara_program'),
            TIME_SELECTOR_MONTHS => get_string('months', 'totara_program'),
            TIME_SELECTOR_YEARS => get_string('years', 'totara_program')
        );
        if ($includenominimum) {
            $timeallowances[TIME_SELECTOR_NOMINIMUM] = get_string('nominimumtime', 'totara_program');
        }
        return $timeallowances;
    }

}

class ProgramException extends Exception {

}
