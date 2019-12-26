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

class individual extends base {

    const ASSIGNTYPE_INDIVIDUAL = 5;

    private $userfullname;
    private $programstatus;
    private $timedue;
    private $certificationid;
    private $certificationpath;
    private $renewalstatus;

    protected function __construct($id = 0) {
        global $DB;

        if ($id !== 0) {
            $sql =
                "SELECT prog_assignment.assignmenttypeid AS id, prog_assignment.programid AS programid,
                        prog_assignment.includechildren, prog_assignment.completiontime,
                        prog_assignment.completionevent, prog_assignment.completioninstance
                   FROM {prog_assignment} prog_assignment
                  WHERE prog_assignment.id = :assignmentid";

            $record = $DB->get_record_sql($sql, ['assignmentid' => $id]);

            // Load into object
            $this->id = $id;
            $this->programid = $record->programid;
            $this->includechildren = $record->includechildren;
            $this->completiontime = $record->completiontime;
            $this->completionevent = $record->completionevent;
            $this->completioninstance = $record->completioninstance;

            $this->typeid = self::ASSIGNTYPE_INDIVIDUAL;
            $this->instanceid = $record->id;
        }
    }

    /**
     * Get type for this assignment
     */
    public function get_type(): int {
        return self::ASSIGNTYPE_INDIVIDUAL;
    }

    /**
     * Get fullname for user
     *
     * @return String
     */
    public function get_name(): string {
        global $DB;

        if (empty($this->userfullname)) {
            $usernamefields = get_all_user_name_fields(true);

            $sql = "SELECT id, $usernamefields FROM {user} WHERE id = :userid";
            $user = $DB->get_record_sql($sql, ['userid' => $this->instanceid]);
            $this->userfullname = fullname($user);
        }

        return $this->userfullname;
    }

    /**
     * Return learner count, for individuals this
     * is always 1
     *
     * @return int
     */
    public function get_user_count(): int {
        return 1;
    }

    private function load_due_date_info() {
        global $DB;

        $sql =
            "SELECT prog.certifid, pc.timedue, pc.status AS programstatus,
                    cc.certifpath, cc.renewalstatus
               FROM {prog_assignment} prog_assignment
               JOIN {user} individual
                 ON individual.id = prog_assignment.assignmenttypeid
               JOIN {prog} prog
                 ON prog.id = prog_assignment.programid
          LEFT JOIN {prog_completion} pc
                 ON pc.programid = prog_assignment.programid AND pc.userid = individual.id AND pc.coursesetid = 0
          LEFT JOIN {certif_completion} cc
                 ON cc.certifid = prog.certifid AND cc.userid = pc.userid
              WHERE prog_assignment.id = :assignmentid";

        $record = $DB->get_record_sql($sql, ['assignmentid' => $this->id]);

        // Extrafields needed for date calculations
        $this->programstatus = $record->programstatus;
        $this->timedue = $record->timedue;
        $this->certificationid = $record->certifid;
        $this->certificationpath = $record->certifpath;
        $this->renewalstatus = $record->renewalstatus;
    }

    public function get_duedate() :\stdClass {
        global $CFG;

        require_once($CFG->dirroot . '/totara/program/program_assignments.class.php');
        require_once($CFG->dirroot . '/totara/program/program.class.php');

        $this->load_due_date_info();

        $completiondate = new \stdClass();

        if (isset($this->programid)) {
            $isprogram = empty($this->certificationid);
            if ($isprogram && $this->programstatus == STATUS_PROGRAM_COMPLETE) {
                // Program which is complete.
                $completiondate->string = get_string('timeduefixedprog', 'totara_program');
                $completiondate->changeable = false;
            } else if (!$isprogram && ($this->certificationpath == CERTIFPATH_RECERT || $this->renewalstatus == CERTIFRENEWALSTATUS_EXPIRED)) {
                // Certification which is complete.
                $completiondate->string = get_string('timeduefixedcert', 'totara_program');
                $completiondate->changeable = false;
            } else if (empty($this->timedue) || $this->timedue == COMPLETION_TIME_NOT_SET) {
                // No date set.
                $completiondate = parent::get_duedate();
            } else {
                // Date set.
                $this->completiontime = COMPLETION_TIME_NOT_SET;
                $this->completionevent = COMPLETION_EVENT_NONE;
                $completiondate = parent::get_duedate();
            }
        } else {
            // New individual assignment.
            $this->completiontime = COMPLETION_TIME_NOT_SET;
            $this->completionevent = COMPLETION_EVENT_NONE;
            $completiondate = parent::get_duedate();
        }

        return $completiondate;
    }

    /**
     * Can the due date be updated, returns false if the
     * user has completed the program/certification
     *
     * Note: This doesn't check users permission, rather determines if
     * the date should be updatable given the program/certification
     * assignment state.
     *
     * @return bool
     */
    public function can_update_date(): bool {

        if (isset($this->programid)) {
            $isprogram = empty($this->certificationid);
            if ($isprogram && $this->programstatus == STATUS_PROGRAM_COMPLETE) {
                return false;
            } else if (!$isprogram && ($this->certificationpath == CERTIFPATH_RECERT || $this->renewalstatus == CERTIFRENEWALSTATUS_EXPIRED)) {
                return false;
            } else if (empty($this->timedue) || $this->timedue == COMPLETION_TIME_NOT_SET) {
                return parent::can_update_date();
            } else {
                return parent::can_update_date();
            }
        } else {
            return parent::can_update_date();
        }
    }

    /**
     * Return the actual due date for an individual
     * assignment
     *
     * @return string Due date string
     */
    public function get_actual_duedate() {
        global $CFG;

        if (!helper::can_update($this->programid)) {
            return false;
        }

        require_once($CFG->dirroot . '/totara/program/program_assignments.class.php');
        require_once($CFG->dirroot . '/totara/program/program.class.php');

        $this->load_due_date_info();

        if (isset($this->programid)) {
            $isprogram = empty($this->certificationid);
            if ($isprogram && $this->programstatus == STATUS_PROGRAM_COMPLETE) {
                // Program which is complete.
                if (empty($this->timedue) || $this->timedue == COMPLETION_TIME_NOT_SET) {
                    $actualdatestring = get_string('noduedate', 'totara_program');
                } else {
                    $actualdatestring = trim(userdate($this->timedue, get_string('strfdateattime', 'langconfig'), 99, false));
                }
            } else if (!$isprogram && ($this->certificationpath == CERTIFPATH_RECERT || $this->renewalstatus == CERTIFRENEWALSTATUS_EXPIRED)) {
                // Certification which is complete.
                if (empty($this->timedue) || $this->timedue == COMPLETION_TIME_NOT_SET) {
                    $actualdatestring = get_string('noduedate', 'totara_program');
                } else {
                    $actualdatestring = trim(userdate($this->timedue, get_string('strfdateattime', 'langconfig'), 99, false));
                }
            } else if (empty($this->timedue) || $this->timedue == COMPLETION_TIME_NOT_SET) {
                // No date set.
                if ($this->completionevent == COMPLETION_EVENT_NONE) {
                    $actualdatestring = get_string('noduedate', 'totara_program');
                } else {
                    $actualdatestring = get_string('notyetknown', 'totara_program');
                }
            } else {
                // Date set.
                $actualdatestring = trim(userdate($this->timedue,
                    get_string('strfdateattime', 'langconfig'), 99, false));
            }
        } else {
            // New individual assignment.
            $actualdatestring = get_string('notyetset', 'totara_program');
        }

        return $actualdatestring;
    }
}
