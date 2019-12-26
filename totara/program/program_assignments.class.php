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

require_once($CFG->dirroot.'/totara/hierarchy/prefix/position/lib.php');

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

define('ASSIGNTYPE_ORGANISATION', 1);
define('ASSIGNTYPE_POSITION', 2);
define('ASSIGNTYPE_COHORT', 3);
define('ASSIGNTYPE_INDIVIDUAL', 5);
define('ASSIGNTYPE_MANAGERJA', 6);

global $ASSIGNMENT_CATEGORY_CLASSNAMES;

$ASSIGNMENT_CATEGORY_CLASSNAMES = array(
    ASSIGNTYPE_ORGANISATION => 'organisations_category',
    ASSIGNTYPE_POSITION     => 'positions_category',
    ASSIGNTYPE_COHORT       => 'cohorts_category',
    ASSIGNTYPE_MANAGERJA    => 'managers_category',
    ASSIGNTYPE_INDIVIDUAL   => 'individuals_category'
);

define('COMPLETION_TIME_NOT_SET', -1);
define('COMPLETION_TIME_UNKNOWN', 0);
define('COMPLETION_EVENT_NONE', 0);
define('COMPLETION_EVENT_FIRST_LOGIN', 1);
define('COMPLETION_EVENT_POSITION_ASSIGNED_DATE', 2);
define('COMPLETION_EVENT_PROGRAM_COMPLETION', 3);
define('COMPLETION_EVENT_COURSE_COMPLETION', 4);
define('COMPLETION_EVENT_PROFILE_FIELD_DATE', 5);
define('COMPLETION_EVENT_ENROLLMENT_DATE', 6);
define('COMPLETION_EVENT_POSITION_START_DATE', 7);

global $COMPLETION_EVENTS_CLASSNAMES;

$COMPLETION_EVENTS_CLASSNAMES = array(
    COMPLETION_EVENT_FIRST_LOGIN            => 'prog_assigment_completion_first_login',
    COMPLETION_EVENT_POSITION_ASSIGNED_DATE => 'prog_assigment_completion_position_assigned_date',
    COMPLETION_EVENT_POSITION_START_DATE    => 'prog_assigment_completion_position_start_date',
    COMPLETION_EVENT_PROGRAM_COMPLETION     => 'prog_assigment_completion_program_completion',
    COMPLETION_EVENT_COURSE_COMPLETION      => 'prog_assigment_completion_course_completion',
    COMPLETION_EVENT_PROFILE_FIELD_DATE     => 'prog_assigment_completion_profile_field_date',
    COMPLETION_EVENT_ENROLLMENT_DATE        => 'prog_assigment_completion_enrollment_date',
);

/**
 * Class representing the program assignments
 *
 */
class prog_assignments {

    /**
     * The assignment records from the database.
     *
     * Prior to Totara 10 there was a protected assignments property.
     * This property was always populated during construction but not always used.
     * Do not change the scope from private, call $this->get_assignments() instead.
     *
     * @internal Never use this directly always use $this->get_assignments()
     * @var stdClass[]
     */
    private $assignmentrecords = null;

    /**
     * Class prog_assignments constructor.
     *
     * @param int $programid
     */
    public function __construct($programid) {
        $this->programid = $programid;
    }

    /**
     * Ensures that assignments are loaded.
     */
    public function ensure_assignments_init() {
        if ($this->assignmentrecords === null) {
            $this->init_assignments();
        }
    }

    /**
     * Resets the assignments property so that it contains only the assignments
     * that are currently stored in the database. This is necessary after
     * assignments are updated
     */
    private function init_assignments() {
        global $DB;
        $this->assignmentrecords = $DB->get_records('prog_assignment', array('programid' => $this->programid));;
    }

    /**
     * Returns the assignments for this program.
     *
     * @return stdClass[]
     */
    public function get_assignments() {
        $this->ensure_assignments_init();
        return $this->assignmentrecords;
    }

    /**
     * Resets the program assignment records to ensure they are accurate.
     */
    public function reset() {
        $this->assignmentrecords = null;
    }

    /**
     * @param int $assignmenttype One of ASSIGNTYPE_*
     * @return organisations_category|positions_category|cohorts_category|managers_category|individuals_category
     * @throws Exception
     */
    public static function factory($assignmenttype) {
        global $ASSIGNMENT_CATEGORY_CLASSNAMES;

        if (!array_key_exists($assignmenttype, $ASSIGNMENT_CATEGORY_CLASSNAMES)) {
            throw new coding_exception('Assignment category type not found');
        }

        if (class_exists($ASSIGNMENT_CATEGORY_CLASSNAMES[$assignmenttype])) {
            $classname = $ASSIGNMENT_CATEGORY_CLASSNAMES[$assignmenttype];
            return new $classname();
        } else {
            throw new coding_exception('Assignment category class not found');
        }
    }

    /**
     * Deletes all the assignments and user assignments for this program
     *
     * @return bool true|Exception
     */
    public function delete() {
        global $DB;
        $transaction = $DB->start_delegated_transaction();

        // delete all user assignments
        $DB->delete_records('prog_user_assignment', array('programid' => $this->programid));
        // also delete future user assignments
        $DB->delete_records('prog_future_user_assignment', array('programid' => $this->programid));
        // delete all configured assignments
        $DB->delete_records('prog_assignment', array('programid' => $this->programid));
        // delete all exceptions
        $DB->delete_records('prog_exception', array('programid' => $this->programid));

        $transaction->allow_commit();

        return true;
    }

    /**
     * Returns the number of assignments found for the current program
     * who dont have exceptions
     *
     * @return integer The number of user assignments
     */
    public function count_active_user_assignments() {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/totara/program/program.class.php');

        list($exception_sql, $params) = $DB->get_in_or_equal(array(PROGRAM_EXCEPTION_NONE, PROGRAM_EXCEPTION_RESOLVED));
        $params[] = $this->programid;

        $count = $DB->count_records_sql("SELECT COUNT(DISTINCT userid) FROM {prog_user_assignment} WHERE exceptionstatus {$exception_sql} AND programid = ?", $params);
        return $count;
    }

    /**
     * Returns the total user assignments for the current program
     *
     * @return integer The number of users assigned to the current program
     */
    public function count_total_user_assignments() {
        global $DB;

        // also include future assignments in total
        $sql = "SELECT COUNT(DISTINCT userid) FROM (SELECT userid FROM {prog_user_assignment} WHERE programid = ?
            UNION SELECT userid FROM {prog_future_user_assignment} WHERE programid = ?) q";
        $count = $DB->count_records_sql($sql, array($this->programid, $this->programid));

        return $count;
    }

    /**
     * Returns the number of users found for the current program
     * who have exceptions
     *
     * @return integer The number of users
     */
    public function count_user_assignment_exceptions() {
        global $DB;

        $sql = "SELECT COUNT(DISTINCT ex.userid)
                FROM {prog_exception} ex
                INNER JOIN {user} us ON us.id = ex.userid
                WHERE ex.programid = ? AND us.deleted = ?";
        return $DB->count_records_sql($sql, array($this->programid, 0));
    }

    /**
     * Returns an HTML string suitable for displaying as the label for the
     * assignments in the program overview form
     *
     * @return string
     */
    public function display_form_label() {
        $out = '';
        $out .= get_string('instructions:assignments1', 'totara_program');
        return $out;
    }

    /**
     * Returns an HTML string suitable for displaying as the element body
     * for the assignments in the program overview form
     *
     * @return string
     */
    public function display_form_element() {
        global $OUTPUT, $ASSIGNMENT_CATEGORY_CLASSNAMES;

        $emptyarray = array(
            'typecount' => 0,
            'users'     => 0
        );

        $assignmentdata = array(
            ASSIGNTYPE_ORGANISATION => $emptyarray,
            ASSIGNTYPE_POSITION => $emptyarray,
            ASSIGNTYPE_COHORT => $emptyarray,
            ASSIGNTYPE_MANAGERJA => $emptyarray,
            ASSIGNTYPE_INDIVIDUAL => $emptyarray,
        );

        $out = '';

        $assignmentrecords = $this->get_assignments();
        if (count($assignmentrecords)) {

            $usertotal = 0;

            foreach ($assignmentrecords as $assignment) {
                $assignmentob = prog_assignments::factory($assignment->assignmenttype);

                $assignmentdata[$assignment->assignmenttype]['typecount']++;

                $users = $assignmentob->get_affected_users_by_assignment($assignment);
                $usercount = count($users);
                if ($users) {
                    $assignmentdata[$assignment->assignmenttype]['users'] += $usercount;
                }
                $usertotal += $usercount;
            }

            $table = new html_table();
            $table->head = array(
                get_string('overview', 'totara_program'),
                get_string('numlearners', 'totara_program')
            );
            $table->data = array();

            $categoryrow = 0;
            foreach ($assignmentdata as $categorytype => $data) {
                $categoryclassname = $ASSIGNMENT_CATEGORY_CLASSNAMES[$categorytype];

                $styleclass = ($categoryrow % 2 == 0) ? 'even' : 'odd';

                $row = array();
                $row[] = $data['typecount'].' '.get_string($categoryclassname, 'totara_program');
                $row[] = $data['users'];

                $table->data[] = $row;
                $table->rowclass[] = $styleclass;

                $categoryrow++;
            }

            $out .= $OUTPUT->render($table);

        } else {
            $out .= get_string('noprogramassignments', 'totara_program');
        }

        return $out;
    }

    /**
     * Returns the script to be run when a specific completion event is chosen
     *
     * @global array $COMPLETION_EVENTS_CLASSNAMES
     * @param string $name
     * @return string
     */
    public static function get_completion_events_script($name="eventtype", $programid = null) {
        global $COMPLETION_EVENTS_CLASSNAMES;

        $out = '';

        $out .= "
            function handle_completion_selection() {
                var eventselected = $('#eventtype option:selected').val();
                eventid = eventselected;
        ";

        // Get the script that should be run if we select a specific event
        foreach ($COMPLETION_EVENTS_CLASSNAMES as $class) {
            $event = new $class($programid);
            $out .= "if (eventid == ". $event->get_id() .") { " . $event->get_script() . " }";
        }

        $out .= "
            };
        ";

        return $out;
    }

    public static function get_confirmation_template() {
        global $ASSIGNMENT_CATEGORY_CLASSNAMES, $OUTPUT;

        $table = new html_table();
        $table->head = array('', get_string('added', 'totara_program'), get_string('removed', 'totara_program'));
        $table->data = array();
        foreach ($ASSIGNMENT_CATEGORY_CLASSNAMES as $classname) {
            $category = new $classname();
            $spanadded = html_writer::tag('span', '0', array('class' => 'added_'.$category->id));
            $spanremoved = html_writer::tag('span', '0', array('class' => 'removed_'.$category->id));
            $table->data[] = array($category->name, $spanadded, $spanremoved);
        }

        $tableHTML = $OUTPUT->render($table);
        // Strip new lines as they screw up the JS
        $order   = array("\r\n", "\n", "\r");
        $table = str_replace($order, '', $tableHTML);

        $data = array();
        $data['html'] = html_writer::tag('div', get_string('youhavemadefollowingchanges', 'totara_program') . html_writer::empty_tag('br') . html_writer::empty_tag('br') . $tableHTML . html_writer::empty_tag('br') . get_string('tosaveassignments','totara_program'));

        return json_encode($data);
    }
}

/**
 * Abstract class for a category which appears on the program assignments screen.
 */
abstract class prog_assignment_category {
    public $id;
    public $name = '';
    public $table = '';
    protected $buttonname = '';
    protected $headers = array(); // array of headers as strings?
    protected $data = array(); // array of arrays of strings (html)

    /**
     * Prints out the actual html for the category, by looking at the headers
     * and data which should have been set by sub class
     *
     * @param boolean $canadd If the group can have data added to it or not.
     * @return string html
     */
    function display($canadd = true) {
        global $PAGE;
        $renderer = $PAGE->get_renderer('totara_program');
        return $renderer->assignment_category_display($this, $this->headers, $this->buttonname, $this->data, $canadd);
    }

    /**
     * Checks whether this category has any items by looking
     * @return int
     */
    function has_items() {
        return count($this->data);
    }

    /**
     * Builds the table that appears for this category by filling $this->headers
     * and $this->data
     *
     * @param int|program $programidorinstance - id or instance of the program.
     *    Instance of program accepted since 10 (prior to this, only int was accepted).
     */
    abstract function build_table($programidorinstance);

    /**
     * Builds a single row by looking at the passed in item
     *
     * @param object $item
     * @param bool $canupdate - true if user will be able to update data for this table.
     *   Since Totara 10.
     */
    abstract function build_row($item, $canupdate = true);

    /**
     * Returns any javascript that should be loaded to be used by the category
     *
     * @access  public
     * @param   int     $programid
     */
    abstract function get_js($programid);

    /**
     * Gets the number of affected users
     */
    abstract function user_affected_count($item);

    /**
     * Gets the affected users for the given item record
     *
     * @param object $item An object containing data about the assignment
     * @param int $userid (optional) Only look at this user
     */
    abstract function get_affected_users($item, $userid=0);

    /**
     * Retrieves an array of all the users affected by an assignment based on the
     * assignment record
     *
     * @param object $assignment The db record from 'prog_assignment' for this assignment
     * @param int $userid (optional) only look at this user
     */
    abstract function get_affected_users_by_assignment($assignment, $userid=0);

    /**
     * Updates the assignments by looking at the post data
     *
     * @param object $data  The data we will be updating assignments with
     * @param bool $delete  A flag to stop deletion/rebuild from external pages
     */
    function update_assignments($data, $delete = true) {
        global $DB;

        // Store list of seen ids
        $seenids = array();

        // Clear the completion caches in all cases
        if (isset($data->id)) {
            totara_program\progress\program_progress_cache::mark_program_cache_stale($data->id);
        }

        // If theres inputs for this assignment category (this)
        if (isset($data->item[$this->id])) {

            // Get the list of item ids
            $itemids = array_keys($data->item[$this->id]);
            $seenids = $itemids;

            $insertssql = array();
            $insertsparams = array();
            // Get a list of assignments
            $sql = "SELECT p.assignmenttypeid as hashkey, p.* FROM {prog_assignment} p WHERE programid = ? AND assignmenttype = ?";
            $assignment_hashmap = $DB->get_records_sql($sql, array($data->id, $this->id));

            foreach ($itemids as $itemid) {
                $object = isset($assignment_hashmap[$itemid]) ? $assignment_hashmap[$itemid] : false;
                if ($object !== false) {
                    $original_object = clone $object;
                }

                if (!$object) {
                    $object = new stdClass(); //same for all cats
                    $object->programid = $data->id; //same for all cats
                    $object->assignmenttype = $this->id;
                    $object->assignmenttypeid = $itemid;
                }

                // Let the inheriting object deal with the include children field as it's specific to them
                $object->includechildren = $this->get_includechildren($data, $object);

                // Get the completion time.
                $object->completiontime = !empty($data->completiontime[$this->id][$itemid]) ?
                    $data->completiontime[$this->id][$itemid] : COMPLETION_TIME_NOT_SET;

                // Get the completion event.
                $object->completionevent = isset($data->completionevent[$this->id][$itemid]) ?
                    $data->completionevent[$this->id][$itemid] : COMPLETION_EVENT_NONE;

                // Get the completion instance.
                $object->completioninstance = !empty($data->completioninstance[$this->id][$itemid]) ?
                    $data->completioninstance[$this->id][$itemid] : 0;

                if ($object->completiontime != COMPLETION_TIME_NOT_SET) {
                    if ($object->completionevent == COMPLETION_EVENT_NONE) {
                        // Convert fixed dates.
                        $hour = isset($data->completiontimehour[$this->id][$itemid]) ? sprintf("%02d", $data->completiontimehour[$this->id][$itemid]) : '00';
                        $minute = isset($data->completiontimeminute[$this->id][$itemid]) ? sprintf("%02d", $data->completiontimeminute[$this->id][$itemid]) : '00';
                        $object->completiontime = totara_date_parse_from_format(get_string('datepickerlongyearparseformat', 'totara_core').' H:i', $object->completiontime.' '.$hour.':'.$minute);
                    } else {
                        // Convert relative dates.
                        $parts = explode(' ', $object->completiontime);
                        if (!isset($parts[0]) || !isset($parts[1])) {
                            continue;
                        }
                        $num = $parts[0];
                        $period = $parts[1];
                        $object->completiontime = program_utilities::duration_implode($num, $period);
                    }
                }

                if (isset($object->id)) {
                    // Check if we actually need an update..
                    if ($original_object->includechildren != $object->includechildren ||
                        $original_object->completiontime != $object->completiontime ||
                        $original_object->completionevent != $object->completionevent ||
                        $original_object->completioninstance != $object->completioninstance) {

                        if (!$DB->update_record('prog_assignment', $object)) {
                            print_error('error:updatingprogramassignment', 'totara_program');
                        }
                    }
                } else {
                    // Create new assignment
                    $insertssql[] = "(?, ?, ?, ?, ?, ?, ?)";
                    $insertsparams[] = array($object->programid, $object->assignmenttype, $object->assignmenttypeid, $object->includechildren, $object->completiontime, $object->completionevent, $object->completioninstance);
                    $this->_add_assignment_hook($object);
                }
            }

            // Execute inserts
            if (count($insertssql) > 0) {
                $sql = "INSERT INTO {prog_assignment} (programid, assignmenttype, assignmenttypeid, includechildren, completiontime, completionevent, completioninstance) VALUES " . implode(', ', $insertssql);
                $params = array();
                foreach ($insertsparams as $p) {
                    $params = array_merge($params, $p);
                }
                $DB->execute($sql, $params);
            }
        }

        if ($delete) {
            // Delete any records which exist in the prog_assignment table but that
            // weren't submitted just now. Also delete any existing exceptions that
            // related to the assignment being deleted
            $where = "programid = ? AND assignmenttype = ?";
            $params = array($data->id, $this->id);
            if (count($seenids) > 0) {
                list($idssql, $idsparams) = $DB->get_in_or_equal($seenids, SQL_PARAMS_QM, 'param', false);
                $where .= " AND assignmenttypeid {$idssql}";
                $params = array_merge($params, $idsparams);
            }
            $assignments_to_delete = $DB->get_records_select('prog_assignment', $where, $params);
            foreach ($assignments_to_delete as $assignment_to_delete) {
                // delete any exceptions related to this assignment
                prog_exceptions_manager::delete_exceptions_by_assignment($assignment_to_delete->id);

                // delete any future user assignments related to this assignment
                $DB->delete_records('prog_future_user_assignment', array('assignmentid' => $assignment_to_delete->id, 'programid' => $data->id));
            }
            $DB->delete_records_select('prog_assignment', $where, $params);
        }
    }

    /**
     * Remove user assignments from programs where users not longer belong to the category assignment.
     *
     * @param int $programid Program ID where users are assigned
     * @param int $assignmenttypeid
     * @param array $userids Array of user IDs that we want to remove
     * @return bool $success True if the delete statement was successfully executed.
     */
    public function remove_outdated_assignments($programid, $assignmenttypeid, $userids) {
        global $DB;

        // Do nothing if it's not a group assignment or the id of the assignment type is not given or no users are passed.
        if ($this->id == ASSIGNTYPE_INDIVIDUAL ||
            empty($programid) ||
            empty($assignmenttypeid) ||
            empty($userids)) {
            return false;
        }

        $result = true;

        // Divide the users into batches to prevent sql problems.
        $batches = array_chunk($userids, $DB->get_max_in_params());
        unset($userids);

        // Process each batch of user ids.
        foreach ($batches as $userids) {
            list($sql, $params) = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);
            $params['programid'] = $programid;
            $params['assigntype'] = $this->id;
            $params['assigntypeid'] = $assignmenttypeid;

            $sql = "DELETE FROM {prog_user_assignment}
                     WHERE userid {$sql}
                       AND programid = :programid
                       AND EXISTS (SELECT 1
                         FROM {prog_assignment} pa
                         WHERE pa.assignmenttype = :assigntype
                           AND pa.assignmenttypeid = :assigntypeid
                           AND pa.id = {prog_user_assignment}.assignmentid)";
            $result &= $DB->execute($sql, $params);
        }

        // Clear the program completion caches for this program
        totara_program\progress\program_progress_cache::mark_program_cache_stale($programid);

        return $result;
    }

    /**
     * Called when an assignment of this category is going to be added
     * @param $object
     */
    protected function _add_assignment_hook($object) {
        return true;
    }

    /**
     * Called when an assignment of this list is going to be deleted
     * @param $object
     */
    protected function _delete_assignment_hook($object) {
        return true;
    }

    /**
     * Gets the include children part from the post data
     * @param <type> $data
     * @param <type> $object
     */
    abstract function get_includechildren($data, $object);

    /**
     * Outputs html for a given set of completion criteria.
     *
     * Will be a link if updating the criteria is allowed.
     * Will be fixed text if updating is not allowed.
     *
     * Hidden input fields will be included for updating of data.
     *
     * @param stdClass $item containing any existing completion criteria.
     * @param null|int $programid
     * @param bool $canupdate set to true if the user can update the due date criteria here.
     *    Since Totara 10.
     * @return string of html containing due date criteria, will be as a link if update is allowed.
     */
    function get_completion($item, $programid = null, $canupdate = true) {
        global $OUTPUT;

        if ($canupdate) {
            $completion_string = get_string('setduedate', 'totara_program');
        } else {
            $completion_string = get_string('noduedate', 'totara_program');
        }

        $hour = 0;
        $minute = 0;

        $show_deletecompletionlink = false;

        if (empty($item->completiontime)) {
            $item->completiontime = COMPLETION_TIME_NOT_SET;
        }

        if (!isset($item->completionevent)) {
            $item->completionevent = 0;
        }

        if (!isset($item->completioninstance)) {
            $item->completioninstance = 0;
        }

        if ($item->completionevent == COMPLETION_EVENT_NONE) {
            // Completiontime must be a timestamp.
            if ($item->completiontime != COMPLETION_TIME_NOT_SET) {
                $hour = (int)userdate($item->completiontime, '%H', 99, false);
                $minute = (int)userdate($item->completiontime, '%M', 99, false);
                // Print a date.
                $item->completiontime = trim(userdate($item->completiontime,
                    get_string('datepickerlongyearphpuserdate', 'totara_core'), 99, false));
                $completion_string = self::build_completion_string($item->completiontime, $item->completionevent, $item->completioninstance, $hour, $minute);
                $show_deletecompletionlink = true;
            }
        } else {
            $parts = program_utilities::duration_explode($item->completiontime);
            $item->completiontime = $parts->num . ' ' . $parts->period;
            $completion_string = self::build_completion_string(
                $item->completiontime, $item->completionevent, $item->completioninstance);
            $show_deletecompletionlink = true;
        }

        if (!$canupdate) {
            $show_deletecompletionlink = false;
        }

        $html = html_writer::start_tag('div', array('class' => "completionlink_{$item->id}"));
        if ($item->completiontime != COMPLETION_TIME_NOT_SET && !empty($item->completiontime)) {
            $html .= html_writer::empty_tag('input', array('type' => 'hidden',
                'name' => 'completiontime['.$this->id.']['.$item->id.']', 'value' => $item->completiontime));
            $html .= html_writer::empty_tag('input', array('type' => 'hidden',
                'name' => 'completiontimehour['.$this->id.']['.$item->id.']', 'value' => $hour));
            $html .= html_writer::empty_tag('input', array('type' => 'hidden',
                'name' => 'completiontimeminute['.$this->id.']['.$item->id.']', 'value' => $minute));
        }
        if ($item->completionevent != COMPLETION_EVENT_NONE) {
            $html .= html_writer::empty_tag('input', array('type' => 'hidden',
                'name' => 'completionevent['.$this->id.']['.$item->id.']', 'value' => $item->completionevent));
        }
        if (!empty($item->completioninstance)) {
            $html .= html_writer::empty_tag('input', array('type' => 'hidden',
                'name' => 'completioninstance['.$this->id.']['.$item->id.']', 'value' => $item->completioninstance));
        }

        if ($canupdate) {
            $html .= html_writer::link('#', $completion_string, array('class' => 'completionlink'));
        } else {
            $html .= html_writer::span($completion_string);
        }

        $html .= html_writer::empty_tag('input',
            array('type' => 'hidden', 'class' => 'completionprogramid', 'value' => $programid));

        if ($show_deletecompletionlink) {
            $html .= $OUTPUT->action_icon('#', new pix_icon('t/delete', get_string('removeduedate', 'totara_program')), null,
                array('class' => 'deletecompletiondatelink'));
        }

        $html .= html_writer::end_tag('div');
        return $html;
    }

    public function build_first_table_cell($name, $id, $itemid, $canupdate = true) {
        global $OUTPUT;
        $output = html_writer::start_tag('div', array('class' => 'totara-item-group'));
        $output .= format_string($name);

        if ($canupdate) {
            $output .= $OUTPUT->action_icon('#', new pix_icon('t/delete', get_string('delete')), null,
                array('class' => 'deletelink totara-item-group-icon'));
        }

        $output .= html_writer::end_tag('div');
        $output .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'item['.$id.']['.$itemid.']', 'value' => '1'));
        return $output;
    }

    public static function build_completion_string($completiontime, $completionevent, $completioninstance,
                                                   $completiontimehour = 0, $completiontimeminute = 0) {
        global $COMPLETION_EVENTS_CLASSNAMES, $TIMEALLOWANCESTRINGS;
        if (isset($COMPLETION_EVENTS_CLASSNAMES[$completionevent])) {
            $eventobject = new $COMPLETION_EVENTS_CLASSNAMES[$completionevent];

            // $completiontime comes in the form '1 2' where 1 is the num and 2 is the period

            $parts = explode(' ',$completiontime);

            if (!isset($parts[0]) || !isset($parts[1])) {
                return '';
            }

            $a = new stdClass();
            $a->num = $parts[0];
            if (isset($TIMEALLOWANCESTRINGS[$parts[1]])) {
                $a->period = get_string($TIMEALLOWANCESTRINGS[$parts[1]], 'totara_program');
            } else {
                return '';
            }
            $a->event = $eventobject->get_completion_string();
            $a->instance = $eventobject->get_item_name($completioninstance);

            if (!empty($a->instance)) {
                $a->instance = "'$a->instance'";
            }

            return get_string('completewithinevent', 'totara_program', $a);
        }
        else {
            $datepattern = get_string('datepickerlongyearregexphp', 'totara_core');
            if (preg_match($datepattern, $completiontime, $matches) == 0) {
                return '';
            } else {
                $completiontimehour = sprintf("%02d", $completiontimehour);
                $completiontimeminute = sprintf("%02d", $completiontimeminute);
                // To ensure multi-language compatibility, we must work out the timestamp and then convert that
                // to a string in the user's language.
                $timestamp = totara_date_parse_from_format(get_string('datepickerlongyearparseformat', 'totara_core').' H:i',
                    $completiontime.' '.$completiontimehour.':'.$completiontimeminute);
                $completiontimestring = userdate($timestamp, get_string('strfdateattime', 'langconfig'), 99);

                return get_string('completebytime', 'totara_program', $completiontimestring);
            }
        }
    }

    static function get_categories() {
        $tempcategories = array(
            new organisations_category(),
            new positions_category(),
            new cohorts_category(),
            new managers_category(),
            new individuals_category(),
        );
        $categories = array();
        foreach ($tempcategories as $category) {
            $categories[$category->id] = $category;
        }
        return $categories;
    }
}

class organisations_category extends prog_assignment_category {


    function __construct() {
        $this->id = ASSIGNTYPE_ORGANISATION;
        $this->name = get_string('organisations', 'totara_program');
        $this->buttonname = get_string('addorganisationstoprogram', 'totara_program');
    }

    /**
     * Builds table for displaying within assignment category.
     *
     * @param int|program $programidorinstance - id or instance of the program.
     *   Instance of program accepted since Totara 10 (prior to this, only int was accepted).
     * @throws coding_exception
     */
    function build_table($programidorinstance) {
        global $DB, $OUTPUT;

        if (is_numeric($programidorinstance)) {
            $program = new program($programidorinstance);
        } else if (get_class($programidorinstance) === 'program') {
            $program = $programidorinstance;
        } else {
            throw new coding_exception('programidorinstance must be a program id (integer) or instance of program class');
        }

        $this->headers = array(
            get_string('organisationname', 'totara_program'),
            get_string('allbelow', 'totara_program'),
            get_string('assignmentduedate', 'totara_program') .
            $OUTPUT->help_icon('assignmentduedate', 'totara_program', null));

        if (!$program->has_expired()) {
            $this->headers[] = get_string('actualduedate', 'totara_program') .
                $OUTPUT->help_icon('groupactualduedate', 'totara_program', null);
            $this->headers[] = get_string('numlearners', 'totara_program');
        }

        // Go to the database and gets the assignments
        $items = $DB->get_records_sql(
            "SELECT org.id, org.fullname, org.path,
                    prog_assignment.programid, prog_assignment.id AS assignmentid,
                    prog_assignment.includechildren, prog_assignment.completiontime,
                    prog_assignment.completionevent, prog_assignment.completioninstance
        FROM {prog_assignment} prog_assignment
        INNER JOIN {org} org on org.id = prog_assignment.assignmenttypeid
        WHERE prog_assignment.programid = ?
        AND prog_assignment.assignmenttype = ?", array($program->id, $this->id));

        // Convert these into html
        if (!empty($items)) {
            foreach ($items as $item) {
                $this->data[] = $this->build_row($item, !$program->has_expired());
            }
        }
    }

    function get_item($itemid) {
        global $DB;
        return $DB->get_record('org', array('id' => $itemid));
    }

    /**
     * Create row to be added to this assignment category's table.
     *
     * @param object $item - data to be added to the row
     * @param bool $canupdate - true if user will be able to update data for this table.
     *   Since Totara 10.
     * @return array
     */
    function build_row($item, $canupdate = true) {

        if (is_int($item)) {
            $item = $this->get_item($item);
        }

        $checked = (isset($item->includechildren) && $item->includechildren == 1) ? true : false;

        if (isset($item->programid)) {
            $programid = $item->programid;
        } else  {
            $programid = null;
        }

        $row = array();
        $row[] = $this->build_first_table_cell($item->fullname, $this->id, $item->id, $canupdate);

        if ($canupdate) {
            $row[] = html_writer::checkbox('includechildren[' . $this->id . '][' . $item->id . ']', '', $checked);
        } else {
            $row[] = html_writer::checkbox('includechildren[' . $this->id . '][' . $item->id . ']', '', $checked, '', array('disabled' => 'disabled'));
        }

        $row[] = $this->get_completion($item, $programid, $canupdate);

        if ($canupdate) {
            if (isset($item->programid)) {
                $viewsql = new moodle_url('/totara/program/assignment/duedates_report.php',
                    array('programid' => $item->programid, 'assignmentid' => $item->assignmentid));
                $row[] = html_writer::link($viewsql, get_string('viewdates', 'totara_program'),
                    array('class' => 'assignment-duedates'));
            } else {
                $row[] = get_string('notyetset', 'totara_program');
            }
            $row[] = $this->user_affected_count($item);
        }

        return $row;
    }

    /**
     * Returns a count of all the users who are assigned to an organisation
     *
     * @global object $CFG
     * @param object $item The organisation record
     * @return int
     */
    function user_affected_count($item) {
        return $this->get_affected_users($item, $userid=0, true);
    }

    /**
     * Returns an array of records containing all the users who are assigned
     * to an organisation
     *
     * @global object $CFG
     * @param object $item The assignment record
     * @param boolean $count If true return the record count instead of the records
     * @return integer|array Record count or array of records
     */
    function get_affected_users($item, $userid=0, $count=false) {
        global $DB;

        $params = array();
        $where = "ja.organisationid = ?";
        $params[] = $item->id;
        if (isset($item->includechildren) && $item->includechildren == 1 && isset($item->path)) {
            $children = $DB->get_fieldset_select('org', 'id', $DB->sql_like('path', '?'), array($item->path . '/%'));
            $children[] = $item->id;
            //replace the existing $params
            list($usql, $params) = $DB->get_in_or_equal($children);
            $where = "ja.organisationid {$usql}";
        }
        if ($userid) {
            $where .= " AND u.id=$userid";
        }

        $select = $count ? 'COUNT(DISTINCT u.id)' : 'DISTINCT u.id';

        $sql = "SELECT $select
                FROM {job_assignment} AS ja
                INNER JOIN {user} AS u ON ja.userid=u.id
                WHERE $where
                AND u.deleted = 0";
        if ($count) {
            return $DB->count_records_sql($sql, $params);
        }
        else {
            return $DB->get_records_sql($sql, $params);
        }
    }

    function get_affected_users_by_assignment($assignment, $userid = 0) {
        global $DB;

        // Query to retrieves the data required to determine the number of users
        //affected by an assignment
        $sql = "SELECT org.id,
                        org.fullname,
                        org.path,
                        prog_assignment.includechildren,
                        prog_assignment.completiontime,
                        prog_assignment.completionevent,
                        prog_assignment.completioninstance
                FROM {prog_assignment} prog_assignment
                INNER JOIN {org} org ON org.id = prog_assignment.assignmenttypeid
                WHERE prog_assignment.id = ?";

        if ($item = $DB->get_record_sql($sql, array($assignment->id))) {
            return $this->get_affected_users($item, $userid);
        } else {
            return array();
        }

    }

    /**
     * @param stdClass $data
     * @param stdClass $object
     * @return int
     */
    function get_includechildren($data, $object) {
        if (!isset($data->includechildren[$this->id][$object->assignmenttypeid])) {
            return 0;
        } else {
            return 1;
        }
    }

    function get_js($programid) {
        $title = addslashes_js(get_string('addorganisationstoprogram', 'totara_program'));
        $url = 'find_hierarchy.php?type=organisation&programid='.$programid;
        return "M.totara_programassignment.add_category({$this->id}, 'organisations', '{$url}', '{$title}');";
    }
}

class positions_category extends prog_assignment_category {

    function __construct() {
        $this->id = ASSIGNTYPE_POSITION;
        $this->name = get_string('positions', 'totara_program');
        $this->buttonname = get_string('addpositiontoprogram', 'totara_program');
    }

    /**
     * Builds table for displaying within assignment category.
     *
     * @param int|program $programidorinstance - id or instance of the program.
     *   Instance of program accepted since Totara 10 (prior to this, only int was accepted).
     * @throws coding_exception
     */
    function build_table($programidorinstance) {
        global $DB, $OUTPUT;

        if (is_numeric($programidorinstance)) {
            $program = new program($programidorinstance);
        } else if (get_class($programidorinstance) === 'program') {
            $program = $programidorinstance;
        } else {
            throw new coding_exception('programidorinstance must be a program id (integer) or instance of program class');
        }

        $this->headers = array(
            get_string('positionsname', 'totara_program'),
            get_string('allbelow', 'totara_program'),
            get_string('assignmentduedate', 'totara_program') .
                $OUTPUT->help_icon('assignmentduedate', 'totara_program', null));

        if (!$program->has_expired()) {
            $this->headers[] = get_string('actualduedate', 'totara_program') .
                $OUTPUT->help_icon('groupactualduedate', 'totara_program', null);
            $this->headers[] = get_string('numlearners', 'totara_program');
        }

        // Go to the database and gets the assignments
        $items = $DB->get_records_sql(
            "SELECT pos.id, pos.fullname, pos.path,
                    prog_assignment.programid, prog_assignment.id AS assignmentid,
                    prog_assignment.includechildren, prog_assignment.completiontime,
                    prog_assignment.completionevent, prog_assignment.completioninstance
               FROM {prog_assignment} prog_assignment
         INNER JOIN {pos} pos on pos.id = prog_assignment.assignmenttypeid
              WHERE prog_assignment.programid = ?
                AND prog_assignment.assignmenttype = ?", array($program->id, $this->id));

        // Convert these into html
        foreach ($items as $item) {
            $this->data[] = $this->build_row($item, !$program->has_expired());
        }
    }

    function get_item($itemid) {
        global $DB;
        return $DB->get_record('pos', array('id' => $itemid));
    }

    /**
     * Create row to be added to this assignment category's table.
     *
     * @param object $item - data to be added to the row
     * @param bool $canupdate - true if user will be able to update data for this table.
     *   Since Totara 10.
     * @return array
     */
    function build_row($item, $canupdate = true) {
        if (is_int($item)) {
            $item = $this->get_item($item);
        }

        $checked = (isset($item->includechildren) && $item->includechildren == 1) ? true : false;

        if (isset($item->programid)) {
            $programid = $item->programid;
        } else  {
            $programid = null;
        }

        $row = array();
        $row[] = $this->build_first_table_cell($item->fullname, $this->id, $item->id, $canupdate);

        if ($canupdate) {
            $row[] = html_writer::checkbox('includechildren[' . $this->id . '][' . $item->id . ']', '', $checked);
        } else {
            $row[] = html_writer::checkbox('includechildren[' . $this->id . '][' . $item->id . ']', '', $checked, '', array('disabled' => 'disabled'));
        }

        $row[] = $this->get_completion($item, $programid, $canupdate);

        if ($canupdate) {
            if (isset($item->programid)) {
                $viewsql = new moodle_url('/totara/program/assignment/duedates_report.php',
                    array('programid' => $item->programid, 'assignmentid' => $item->assignmentid));
                $row[] = html_writer::link($viewsql, get_string('viewdates', 'totara_program'),
                    array('class' => 'assignment-duedates'));
            } else {
                $row[] = get_string('notyetset', 'totara_program');
            }
            $row[] = $this->user_affected_count($item);
        }

        return $row;
    }

    /**
     * Returns a count of all the users who are assigned to a position
     *
     * @global object $CFG
     * @param object $item The organisation record
     * @return int
     */
    function user_affected_count($item) {
        return $this->get_affected_users($item, 0, true);
    }

    /**
     * Returns an array of records containing all the users who are assigned
     * to a position
     *
     * @param object $item The assignment record
     * @param int $userid
     * @param boolean $count If true return the record count instead of the records
     * @return integer|array Record count or array of records
     */
    function get_affected_users($item, $userid = 0, $count=false) {
        global $DB;

        $where = "ja.positionid = ?";
        $params = array($item->id);
        if (isset($item->includechildren) && $item->includechildren == 1 && isset($item->path)) {
            $children = $DB->get_fieldset_select('pos', 'id', $DB->sql_like('path', '?'), array($item->path . '/%'));
            $children[] = $item->id;
            // Replace the existing $params.
            list($usql, $params) = $DB->get_in_or_equal($children);
            $where = "ja.positionid $usql";
        }

        $select = $count ? 'COUNT(DISTINCT u.id)' : 'DISTINCT u.id';

        $sql = "SELECT $select
                FROM {job_assignment} ja
                INNER JOIN {user} u ON ja.userid = u.id
                WHERE $where
                AND u.deleted = 0";
        if ($userid) {
            $sql .= " AND u.id = ?";
            $params[] = $userid;
        }
        if ($count) {
            return $DB->count_records_sql($sql, $params);
        }
        else {
            return $DB->get_records_sql($sql, $params);
        }
    }

    function get_affected_users_by_assignment($assignment, $userid = 0) {
        global $DB;

        // Query to retrieves the data required to determine the number of users
        // affected by an assignment.
        $sql = "SELECT pos.id,
                        pos.fullname,
                        pos.path,
                        prog_assignment.includechildren,
                        prog_assignment.completiontime,
                        prog_assignment.completionevent,
                        prog_assignment.completioninstance
                FROM {prog_assignment} prog_assignment
                INNER JOIN {pos} pos on pos.id = prog_assignment.assignmenttypeid
                WHERE prog_assignment.id = ?";

        if ($item = $DB->get_record_sql($sql, array($assignment->id))) {
            return $this->get_affected_users($item, $userid);
        } else {
            return array();
        }

    }

    /**
     * @param stdClass $data
     * @param stdClass $object
     * @return int
     */
    function get_includechildren($data, $object) {
        if (!isset($data->includechildren[$this->id][$object->assignmenttypeid])) {
            return 0;
        } else {
            return 1;
        }
    }

    function get_js($programid) {
        $title = addslashes_js(get_string('addpositiontoprogram', 'totara_program'));
        $url = 'find_hierarchy.php?type=position&programid='.$programid;
        return "M.totara_programassignment.add_category({$this->id}, 'positions', '{$url}', '{$title}');";
    }
}

class cohorts_category extends prog_assignment_category {

    function __construct() {
        $this->id = ASSIGNTYPE_COHORT;
        $this->name = get_string('cohorts', 'totara_program');
        $this->buttonname = get_string('addcohortstoprogram', 'totara_program');
    }

    /**
     * Builds table for displaying within assignment category.
     *
     * @param int|program $programidorinstance - id or instance of the program.
     *   Instance of program accepted since Totara 10 (prior to this, only int was accepted).
     * @throws coding_exception
     */
    function build_table($programidorinstance) {
        global $DB, $OUTPUT;

        if (is_numeric($programidorinstance)) {
            $program = new program($programidorinstance);
        } else if (get_class($programidorinstance) === 'program') {
            $program = $programidorinstance;
        } else {
            throw new coding_exception('programidorinstance must be a program id (integer) or instance of program class');
        }

        $this->headers = array(
            get_string('cohortname', 'totara_program'),
            get_string('type', 'totara_program'),
            get_string('assignmentduedate', 'totara_program') .
                $OUTPUT->help_icon('assignmentduedate', 'totara_program', null)
        );

        if (!$program->has_expired()) {
            $this->headers[] = get_string('actualduedate', 'totara_program') .
                $OUTPUT->help_icon('groupactualduedate', 'totara_program', null);
            $this->headers[] = get_string('numlearners', 'totara_program');
        }

        // Go to the database and gets the assignments.
        $items = $DB->get_records_sql(
            "SELECT cohort.id, cohort.name as fullname, cohort.cohorttype,
                    prog_assignment.programid, prog_assignment.id AS assignmentid,
                    prog_assignment.completiontime, prog_assignment.completionevent, prog_assignment.completioninstance
            FROM {prog_assignment} prog_assignment
            INNER JOIN {cohort} cohort ON cohort.id = prog_assignment.assignmenttypeid
            WHERE prog_assignment.programid = ?
            AND prog_assignment.assignmenttype = ?", array($program->id, $this->id));

        // Convert these into html.
        if (!empty($items)) {
            foreach ($items as $item) {
                $this->data[] = $this->build_row($item, !$program->has_expired());
            }
        }
    }

    function get_item($itemid) {
        global $DB;
        return $DB->get_record('cohort', array('id' => $itemid), 'id, name as fullname, cohorttype');
    }

    /**
     * Create row to be added to this assignment category's table.
     *
     * @param object $item - data to be added to the row
     * @param bool $canupdate - true if user will be able to update data for this table.
     *   Since Totara 10.
     * @return array
     */
    function build_row($item, $canupdate = true) {
        global $CFG;

        require_once($CFG->dirroot.'/cohort/lib.php');

        if (is_int($item)) {
            $item = $this->get_item($item);
        }

        if (isset($item->programid)) {
            $programid = $item->programid;
        } else {
            $programid = null;
        }

        $cohorttypes = cohort::getCohortTypes();
        $cohortstring = $cohorttypes[$item->cohorttype];

        $row = array();
        $row[] = $this->build_first_table_cell($item->fullname, $this->id, $item->id, $canupdate);
        $row[] = $cohortstring;
        $row[] = $this->get_completion($item, $programid, $canupdate);

        if ($canupdate) {
            if (isset($item->programid)) {
                $viewsql = new moodle_url('/totara/program/assignment/duedates_report.php',
                    array('programid' => $item->programid, 'assignmentid' => $item->assignmentid));
                $row[] = html_writer::link($viewsql, get_string('viewdates', 'totara_program'),
                    array('class' => 'assignment-duedates'));
            } else {
                $row[] = get_string('notyetset', 'totara_program');
            }
            $row[] = $this->user_affected_count($item);
        }

        return $row;
    }

    function user_affected_count($item) {
        return $this->get_affected_users($item, 0, true);
    }

    function get_affected_users($item, $userid = 0, $count = false) {
        global $DB;
        $select = $count ? 'COUNT(u.id)' : 'u.id';
        $sql = "SELECT $select
                  FROM {cohort_members} AS cm
            INNER JOIN {user} AS u ON cm.userid=u.id
                 WHERE cm.cohortid = ?
                   AND u.deleted = 0";
        $params = array($item->id);
        if ($userid) {
            $sql .= " AND u.id = ?";
            $params[] = $userid;
        }
        if ($count) {
            return $DB->count_records_sql($sql, $params);
        }
        else {
            return $DB->get_records_sql($sql, $params);
        }
    }

    function get_affected_users_by_assignment($assignment, $userid = 0) {
        $item = new stdClass();
        $item->id = $assignment->assignmenttypeid;
        return $this->get_affected_users($item, $userid);
    }

    /**
     * Unused by the cohorts category, so just return zero
     */
    function get_includechildren($data, $object) {
        return 0;
    }

    function get_js($programid) {
        $title = addslashes_js(get_string('addcohortstoprogram', 'totara_program'));
        $url = 'find_cohort.php?programid='. $programid . '&sesskey=' . sesskey();
        return "M.totara_programassignment.add_category({$this->id}, 'cohorts', '{$url}', '{$title}');";
    }
    protected function _add_assignment_hook($object) {
        return true;
    }

    protected function _delete_assignment_hook($object) {
        return true;
    }
}

class managers_category extends prog_assignment_category {

    function __construct() {
        $this->id = ASSIGNTYPE_MANAGERJA;
        $this->name = get_string('managementhierarchy', 'totara_program');
        $this->buttonname = get_string('addmanagerstoprogram', 'totara_program');
    }

    /**
     * Builds table for displaying within assignment category.
     *
     * @param int|program $programidorinstance - id or instance of the program.
     *   Instance of program accepted since Totara 10 (prior to this, only int was accepted).
     * @throws coding_exception
     */
    function build_table($programidorinstance) {
        global $DB, $OUTPUT, $CFG;
        require_once($CFG->dirroot . '/totara/job/lib.php');

        if (is_numeric($programidorinstance)) {
            $program = new program($programidorinstance);
        } else if (get_class($programidorinstance) === 'program') {
            $program = $programidorinstance;
        } else {
            throw new coding_exception('programidorinstance must be a program id (integer) or instance of program class');
        }

        $this->headers = array(
            get_string('managername', 'totara_program'),
            get_string('for', 'totara_program'),
            get_string('assignmentduedate', 'totara_program') .
                $OUTPUT->help_icon('assignmentduedate', 'totara_program', null));

        if (!$program->has_expired()) {
            $this->headers[] = get_string('actualduedate', 'totara_program') .
                $OUTPUT->help_icon('groupactualduedate', 'totara_program', null);
            $this->headers[] = get_string('numlearners', 'totara_program');
        }

        // Go to the database and gets the assignments.
        $usernamefields = get_all_user_name_fields(true, 'u');
        $items = $DB->get_records_sql("
            SELECT ja.id, " . $usernamefields . ", ja.managerjapath AS path, ja.fullname AS jobname, ja.idnumber, u.id AS userid,
                   prog_assignment.programid, prog_assignment.id AS assignmentid,
                   prog_assignment.includechildren, prog_assignment.completiontime,
                   prog_assignment.completionevent, prog_assignment.completioninstance
              FROM {prog_assignment} prog_assignment
        INNER JOIN {job_assignment} ja ON ja.id = prog_assignment.assignmenttypeid
         LEFT JOIN {user} u ON u.id = ja.userid
             WHERE prog_assignment.programid = ?
               AND prog_assignment.assignmenttype = ?
        ", array($program->id, $this->id));

        // Convert these into html.
        if (!empty($items)) {
            foreach ($items as $item) {
                $job = clone($item);
                $job->fullname = $item->jobname;
                $item->fullname = totara_job_display_user_job($item, $job, false, false);
                //sometimes a manager may not have a job_assignment record e.g. top manager in the tree
                //so we need to set a default path
                if (empty($item->path)) {
                    $item->path = '/' . $item->id;
                }
                $this->data[] = $this->build_row($item, !$program->has_expired());
            }
        }
    }

    function get_item($itemid) {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/totara/job/lib.php');

        $usernamefields = get_all_user_name_fields(true, 'u');
        $sql = "SELECT ja.id, ja.idnumber, ja.fullname as jobname, " . $usernamefields . ", ja.managerjapath AS path
                  FROM {user} AS u
             LEFT JOIN {job_assignment} ja ON u.id = ja.userid
                 WHERE ja.id = ?";
        // Sometimes a manager may not have a job_assignment record e.g. top manager in the tree
        // so we need to set a default path.
        $item = $DB->get_record_sql($sql, array($itemid));
        $job = clone($item);
        $job->fullname = $item->jobname;
        $item->fullname = totara_job_display_user_job($item, $job, false, false);
        if (empty($item->path)) {
            $item->path = "/{$itemid}";
        }
        return $item;
    }

    /**
     * Create row to be added to this assignment category's table.
     *
     * @param object $item - data to be added to the row
     * @param bool $canupdate - true if user will be able to update data for this table.
     *   Since Totara 10.
     * @return array
     */
    function build_row($item, $canupdate = true) {
        if (is_int($item)) {
            $item = $this->get_item($item);
        }

        if (isset($item->programid)) {
            $programid = $item->programid;
        } else  {
            $programid = null;
        }

        $selectedid = (isset($item->includechildren) && $item->includechildren == 1) ? 1 : 0;
        $options = array(
            0 => get_string('directteam', 'totara_program'),
            1 => get_string('allbelowlower', 'totara_program'));

        $row = array();
        $row[] = $this->build_first_table_cell($item->fullname, $this->id, $item->id, $canupdate);

        if ($canupdate) {
            $row[] = html_writer::select($options, 'includechildren[' . $this->id . '][' . $item->id . ']', $selectedid);
        } else {
            $row[] = html_writer::span($options[$selectedid]);
        }

        $row[] = $this->get_completion($item, $programid, $canupdate);

        if ($canupdate) {
            if (isset($item->programid)) {
                $viewsql = new moodle_url('/totara/program/assignment/duedates_report.php',
                    array('programid' => $item->programid, 'assignmentid' => $item->assignmentid));
                $row[] = html_writer::link($viewsql, get_string('viewdates', 'totara_program'),
                    array('class' => 'assignment-duedates'));
            } else {
                $row[] = get_string('notyetset', 'totara_program');
            }
            $row[] = $this->user_affected_count($item);
        }

        return $row;
    }

    function user_affected_count($item) {
        return $this->get_affected_users($item, 0, true);
    }

    function get_affected_users($item, $userid = 0, $count=false) {
        global $DB;

        if (isset($item->includechildren) && $item->includechildren == 1 && isset($item->path)) {
            // For a manager's entire team.
            $where = $DB->sql_like('ja.managerjapath', '?');
            $path = $DB->sql_like_escape($item->path);
            $params = array($path . '/%');
        } else {
            // For a manager's direct team.
            $where = "ja.managerjaid = ?";
            $params = array($item->id);
        }

        $select = $count ? 'COUNT(DISTINCT ja.userid) AS id' : 'DISTINCT ja.userid AS id';

        $sql = "SELECT $select
                FROM {job_assignment} ja
                INNER JOIN {user} u ON (ja.userid = u.id AND u.deleted = 0)
                WHERE {$where}";
        if ($userid) {
            $sql .= " AND u.id = ?";
            $params[] = $userid;
        }

        if ($count) {
            return $DB->count_records_sql($sql, $params);
        } else {
            return $DB->get_records_sql($sql, $params);
        }
    }

    function get_affected_users_by_assignment($assignment, $userid = 0) {
        global $DB;

        // Query to retrieves the data required to determine the number of users
        // affected by an assignment.
        $sql = "SELECT DISTINCT ja.id,
                       ja.managerjapath AS path,
                       prog_assignment.includechildren
                  FROM {prog_assignment} prog_assignment
             LEFT JOIN {job_assignment} ja ON prog_assignment.assignmenttypeid = ja.id
                 WHERE prog_assignment.id = ?";

        if ($item = $DB->get_record_sql($sql, array($assignment->id))) {
            // Sometimes a manager may not have a job_assignment record e.g. top manager in the tree.
            // So we need to set a default path.
            if (empty($item->path)) {
                $item->path = "/{$item->id}";
            }
            return $this->get_affected_users($item, $userid);
        } else {
            return array();
        }

    }

    function get_includechildren($data, $object) {
        if (empty($data->includechildren[$this->id][$object->assignmenttypeid])) {
            return 0;
        } else {
            return 1;
        }
    }

    function get_js($programid) {
        $title = addslashes_js(get_string('addmanagerstoprogram', 'totara_program'));
        $url = 'find_manager_hierarchy.php?programid='.$programid;
        return "M.totara_programassignment.add_category({$this->id}, 'managers', '{$url}', '{$title}');";
    }
}

class individuals_category extends prog_assignment_category {

    function __construct() {
        $this->id = ASSIGNTYPE_INDIVIDUAL;
        $this->name = get_string('individuals', 'totara_program');
        $this->buttonname = get_string('addindividualstoprogram', 'totara_program');
    }

    /**
     * Builds table for displaying within assignment category.
     *
     * @param int|program $programidorinstance - id or instance of the program.
     *   Instance of program accepted since Totara 10 (prior to this, only int was accepted).
     * @throws coding_exception
     */
    function build_table($programidorinstance) {
        global $DB, $OUTPUT;

        if (is_numeric($programidorinstance)) {
            $program = new program($programidorinstance);
        } else if (get_class($programidorinstance) === 'program') {
            $program = $programidorinstance;
        } else {
            throw new coding_exception('programidorinstance must be a program id (integer) or instance of program class');
        }

        $this->headers = array(
            get_string('individualname', 'totara_program'),
            get_string('userid', 'totara_program'),
            get_string('assignmentduedate', 'totara_program') .
                $OUTPUT->help_icon('assignmentduedate', 'totara_program', null),
            get_string('actualduedate', 'totara_program') .
                $OUTPUT->help_icon('individualactualduedate', 'totara_program', null),
        );

        // Go to the database and gets the assignments.

        $usernamefields = get_all_user_name_fields(true, 'individual');
        $items = $DB->get_records_sql(
            "SELECT individual.id, " . $usernamefields . ", prog.id AS progid, prog.certifid, pc.timedue, pc.status AS progstatus,
                    cc.certifpath, cc.renewalstatus,
                    prog_assignment.completiontime, prog_assignment.completionevent, prog_assignment.completioninstance
               FROM {prog_assignment} prog_assignment
               JOIN {user} individual
                 ON individual.id = prog_assignment.assignmenttypeid
               JOIN {prog} prog
                 ON prog.id = prog_assignment.programid
          LEFT JOIN {prog_completion} pc
                 ON pc.programid = prog_assignment.programid AND pc.userid = individual.id AND pc.coursesetid = 0
          LEFT JOIN {certif_completion} cc
                 ON cc.certifid = prog.certifid AND cc.userid = pc.userid
              WHERE prog_assignment.programid = ?
                AND prog_assignment.assignmenttype = ?", array($program->id, $this->id));

        // Convert these into html.
        if (!empty($items)) {
            foreach ($items as $item) {
                $item->fullname = fullname($item);
                $this->data[] = $this->build_row($item, !$program->has_expired());
            }
        }
    }

    function get_item($itemid) {
        global $DB;

        $usernamefields = get_all_user_name_fields(true);
        $item = $DB->get_record_select('user',"id = ?", array($itemid), 'id, ' . $usernamefields);
        $item->fullname = fullname($item);

        return $item;
    }

    /**
     * Create row to be added to this assignment category's table.
     *
     * @param object $item - data to be added to the row
     * @param bool $canupdate - true if user will be able to update data for this table.
     *   Since Totara 10.
     * @return array
     */
    function build_row($item, $canupdate = true) {
        if (is_int($item)) {
            $item = $this->get_item($item);
        }

        $row = array();
        $row[] = $this->build_first_table_cell($item->fullname, $this->id, $item->id, $canupdate);
        $row[] = $item->id;
        if (isset($item->progid)) {
            $isprog = empty($item->certifid);
            if ($isprog && $item->progstatus == STATUS_PROGRAM_COMPLETE) {
                // Program which is complete.
                $row[] = get_string('timeduefixedprog', 'totara_program');
                if (empty($item->timedue) || $item->timedue == COMPLETION_TIME_NOT_SET) {
                    $row[] = get_string('noduedate', 'totara_program');
                } else {
                    $row[] = trim(userdate($item->timedue, get_string('strfdateattime', 'langconfig'), 99, false));
                }
            } else if (!$isprog && ($item->certifpath == CERTIFPATH_RECERT || $item->renewalstatus == CERTIFRENEWALSTATUS_EXPIRED)) {
                // Certification which is complete.
                $row[] = get_string('timeduefixedcert', 'totara_program');
                if (empty($item->timedue) || $item->timedue == COMPLETION_TIME_NOT_SET) {
                    $row[] = get_string('noduedate', 'totara_program');
                } else {
                    $row[] = trim(userdate($item->timedue, get_string('strfdateattime', 'langconfig'), 99, false));
                }
            } else if (empty($item->timedue) || $item->timedue == COMPLETION_TIME_NOT_SET) {
                // No date set.
                $row[] = $this->get_completion($item, $item->progid, $canupdate);
                if ($item->completionevent == COMPLETION_EVENT_NONE) {
                    $row[] = get_string('noduedate', 'totara_program');
                } else {
                    $row[] = get_string('notyetknown', 'totara_program');
                }
            } else {
                // Date set.
                $item->completiontime = COMPLETION_TIME_NOT_SET;
                $item->completionevent = COMPLETION_EVENT_NONE;
                $row[] = $this->get_completion($item, $item->progid, $canupdate);
                $row[] = trim(userdate($item->timedue,
                    get_string('strfdateattime', 'langconfig'), 99, false));
            }
        } else {
            // New individual assignment.
            $item->completiontime = COMPLETION_TIME_NOT_SET;
            $item->completionevent = COMPLETION_EVENT_NONE;
            $row[] = $this->get_completion($item, null, $canupdate);
            $row[] = get_string('notyetset', 'totara_program');
        }

        return $row;
    }

    function user_affected_count($item) {
        return 1;
    }

    function get_affected_users($item, $userid = 0) {
        $user = (object)array('id'=>$item->assignmenttypeid);
        return array($user);
    }

    function get_affected_users_by_assignment($assignment, $userid = 0) {
        return $this->get_affected_users($assignment, $userid);
    }

    function get_includechildren($data, $object) {
        return 0;
    }

    function get_js($programid) {
        $title = addslashes_js(get_string('addindividualstoprogram', 'totara_program'));
        $url = 'find_individual.php?programid='.$programid;
        return "M.totara_programassignment.add_category({$this->id}, 'individuals', '{$url}', '{$title}');";
    }
}

abstract class prog_assignment_completion_type {
    protected $programid;

    public function __construct($programid = null) {
        if (isset($programid)) {
            $this->programid = $programid;
        }
    }

    abstract public function get_id();
    abstract public function get_name();
    abstract public function get_script();
    abstract public function get_item_name($instanceid);
    abstract public function get_completion_string();
    abstract public function get_timestamp($userid, $assignobject);
}

class prog_assigment_completion_first_login extends prog_assignment_completion_type {
    private $timestamps;

    public function get_id() {
        return COMPLETION_EVENT_FIRST_LOGIN;
    }
    public function get_name() {
        return get_string('firstlogin', 'totara_program');
    }
    public function get_script() {
        return "
            totaraDialogs['completionevent'].clear();
        ";
    }
    public function get_item_name($instanceid) {
        return '';
    }
    public function get_completion_string() {
        return get_string('firstlogin', 'totara_program');
    }
    public function get_timestamp($userid, $assignobject) {
        global $DB;
        $rec = $DB->get_record('user', array('id' => $userid), 'id, firstaccess, lastaccess');
        $firstaccess = empty($rec->firstaccess) ? $rec->lastaccess : $rec->firstaccess;

        return $firstaccess;
    }
}

class prog_assigment_completion_position_assigned_date extends prog_assignment_completion_type {
    private $names, $timestamps;
    public function get_id() {
        return COMPLETION_EVENT_POSITION_ASSIGNED_DATE;
    }
    public function get_name() {
        return get_string('positionassigneddate', 'totara_program');
    }
    public function get_script() {
        global $CFG;

        if (empty($this->programid)) {
            throw new coding_exception('Program id must be defined for js that will call the completion ajax scripts.');
        }

        return "
            totaraDialogs['completionevent'].default_url = '$CFG->wwwroot/totara/program/assignment/completion/find_position.php?programid="
            . $this->programid . "';
            totaraDialogs['completionevent'].open();

            $('#instancetitle').unbind('click').click(function() {
                handle_completion_selection();
                return false;
            });
        ";
    }
    public function get_item_name($instanceid) {
        global $DB;

        // Lazy load names when required.
        if (!isset($this->names)) {
            $this->names = $DB->get_records_select('pos', '', null, '', 'id, fullname');
        }

        if (isset($this->names[$instanceid]->fullname)) {
            return $this->names[$instanceid]->fullname;
        } else {
            return get_string('itemdeleted', 'totara_program');
        }
    }
    public function get_completion_string() {
        return get_string('assigntoposition', 'totara_program');
    }
    public function get_timestamp($userid, $assignobject) {
        global $DB;

        // First time calling this function.
        if (!isset($this->timestamps)) {
            $this->timestamps = array();
        }

        // First time calling this function for this positionid.
        $positionid = $assignobject->completioninstance;
        if (!isset($this->timestamps[$positionid])) {
            $sql = "SELECT ja.userid, max(ja.positionassignmentdate) AS timestamp
                      FROM {job_assignment} ja
                     WHERE ja.positionid = :positionid
                  GROUP BY ja.userid";
            $params = array('positionid' => $positionid);
            $this->timestamps[$positionid] = $DB->get_records_sql($sql, $params);
        }

        if (isset($this->timestamps[$positionid][$userid])) {
            return $this->timestamps[$positionid][$userid]->timestamp;
        }

        return false;
    }
}

class prog_assigment_completion_position_start_date extends prog_assignment_completion_type {
    private $names, $timestamps;
    public function get_id() {
        return COMPLETION_EVENT_POSITION_START_DATE;
    }
    public function get_name() {
        return get_string('jobassignmentstartdate', 'totara_program');
    }
    public function get_script() {
        global $CFG;

        if (empty($this->programid)) {
            throw new coding_exception('Program id must be defined for js that will call the completion ajax scripts.');
        }

        return "
            totaraDialogs['completionevent'].default_url = '$CFG->wwwroot/totara/program/assignment/completion/find_position.php?programid="
            . $this->programid . "';
            totaraDialogs['completionevent'].open();

            $('#instancetitle').unbind('click').click(function() {
                handle_completion_selection();
                return false;
            });
        ";
    }
    public function get_item_name($instanceid) {
        global $DB;

        // Lazy load names when required.
        if (!isset($this->names)) {
            $this->names = $DB->get_records_select('pos', '', null, '', 'id, fullname');
        }

        if (isset($this->names[$instanceid]->fullname)) {
            return $this->names[$instanceid]->fullname;
        } else {
            return get_string('itemdeleted', 'totara_program');
        }
    }
    public function get_completion_string() {
        return get_string('startinposition', 'totara_program');
    }
    public function get_timestamp($userid, $assignobject) {
        global $DB;

        // First time calling this function.
        if (!isset($this->timestamps)) {
            $this->timestamps = array();
        }

        // First time calling this function for this positionid.
        $positionid = $assignobject->completioninstance;
        if (!isset($this->timestamps[$positionid])) {
            $sql = "SELECT ja.userid, MAX(ja.startdate) AS timestamp
                      FROM {job_assignment} ja
                     WHERE ja.positionid = :positionid
                  GROUP BY ja.userid";
            $params = array('positionid' => $positionid);
            $this->timestamps[$positionid] = $DB->get_records_sql($sql, $params);
        }

        if (isset($this->timestamps[$positionid][$userid])) {
            return $this->timestamps[$positionid][$userid]->timestamp;
        }

        return false;
    }
}

class prog_assigment_completion_program_completion extends prog_assignment_completion_type {
    private $names, $timestamps;
    public function get_id() {
        return COMPLETION_EVENT_PROGRAM_COMPLETION;
    }
    public function get_name() {
        return get_string('programcompletion', 'totara_program');
    }
    public function get_script() {
        global $CFG;

        if (empty($this->programid)) {
            throw new coding_exception('Program id must be defined for js that will call the completion ajax scripts.');
        }

        return "
            totaraDialogs['completionevent'].default_url = '$CFG->wwwroot/totara/program/assignment/completion/find_program.php?programid="
            . $this->programid . "';
            totaraDialogs['completionevent'].open();

            $('#instancetitle').unbind('click').click(function() {
                handle_completion_selection();
                return false;
            });

            $('.folder').removeClass('clickable').addClass('unclickable');
        ";
    }
    public function get_item_name($instanceid) {
        global $DB;

        // Lazy load names when required.
        if (!isset($this->names)) {
            $this->names = $DB->get_records_select('prog', '', null, '', 'id, fullname');
        }

        if (isset($this->names[$instanceid]->fullname)) {
            return $this->names[$instanceid]->fullname;
        } else {
            return get_string('itemdeleted', 'totara_program');
        }
    }
    public function get_completion_string() {
        return get_string('completionofprogram', 'totara_program');
    }
    public function get_timestamp($userid, $assignobject) {
        global $DB;

        // First time calling this function.
        if (!isset($this->timestamps)) {
            $this->timestamps = array();
        }

        // First time calling this function for this programid.
        $programid = $assignobject->completioninstance;
        if (!isset($this->timestamps[$programid])) {
            $params = array('coursesetid' => 0, 'programid' => $programid);
            $this->timestamps[$programid] = $DB->get_records('prog_completion', $params, '', 'userid, timecompleted');
        }

        if (isset($this->timestamps[$programid][$userid])) {
            return $this->timestamps[$programid][$userid]->timecompleted;
        }

        return false;
    }
}

class prog_assigment_completion_course_completion extends prog_assignment_completion_type {
    private $names, $timestamps;
    public function get_id() {
        return COMPLETION_EVENT_COURSE_COMPLETION;
    }
    public function get_name() {
        return get_string('coursecompletion', 'totara_program');
    }
    public function get_script() {
        global $CFG;

        if (empty($this->programid)) {
            throw new coding_exception('Program id must be defined for js that will call the completion ajax scripts.');
        }

        return "
            totaraDialogs['completionevent'].default_url = '$CFG->wwwroot/totara/program/assignment/completion/find_course.php?programid="
            . $this->programid . "';
            totaraDialogs['completionevent'].open();

            $('#instancetitle').unbind('click').click(function() {
                handle_completion_selection();
                return false;
            });

            $('.folder').removeClass('clickable').addClass('unclickable');
        ";
    }
    public function get_item_name($instanceid) {
        global $DB;

        // Lazy load names when required.
        if (!isset($this->names)) {
            $this->names = $DB->get_records_select('course', '', null, '', 'id, fullname');
        }

        if (isset($this->names[$instanceid]->fullname)) {
            return $this->names[$instanceid]->fullname;
        } else {
            return get_string('itemdeleted', 'totara_program');
        }
    }
    public function get_completion_string() {
        return get_string('completionofcourse', 'totara_program');
    }
    public function get_timestamp($userid, $assignobject) {
        global $DB;

        // First time calling this function.
        if (!isset($this->timestamps)) {
            $this->timestamps = array();
        }

        // First time calling this function for this courseid.
        $courseid = $assignobject->completioninstance;
        if (!isset($this->timestamps[$courseid])) {
            $params = array('course' => $courseid);
            $this->timestamps[$courseid] = $DB->get_records('course_completions', $params, '', 'userid, timecompleted');
        }

        if (isset($this->timestamps[$courseid][$userid])) {
            return $this->timestamps[$courseid][$userid]->timecompleted;
        }

        return false;
    }
}

class prog_assigment_completion_profile_field_date extends prog_assignment_completion_type {
    private $names, $timestamps;
    public function get_id() {
        return COMPLETION_EVENT_PROFILE_FIELD_DATE;
    }
    public function get_name() {
        return get_string('profilefielddate', 'totara_program');
    }
    public function get_script() {
        global $CFG;

        if (empty($this->programid)) {
            throw new coding_exception('Program id must be defined for js that will call the completion ajax scripts.');
        }

        return "
            totaraDialogs['completionevent'].default_url = '$CFG->wwwroot/totara/program/assignment/completion/find_profile_field.php?programid="
            . $this->programid . "';
            totaraDialogs['completionevent'].open();

            $('#instancetitle').unbind('click').click(function() {
                handle_completion_selection();
                return false;
            });
        ";
    }
    public function get_item_name($instanceid) {
        global $DB;

        // Lazy load names when required.
        if (!isset($this->names)) {
            $this->names = $DB->get_records_select('user_info_field', '', null, '', 'id, name');
        }

        if (isset($this->names[$instanceid]->name)) {
            return $this->names[$instanceid]->name;
        } else {
            return get_string('itemdeleted', 'totara_program');
        }
    }
    public function get_completion_string() {
        return get_string('dateinprofilefield', 'totara_program');
    }
    public function get_timestamp($userid, $assignobject) {
        global $DB;

        // First time calling this function.
        if (!isset($this->timestamps)) {
            $this->timestamps = array();
        }

        // First time calling this function for this fieldid. We can't narrow this down to only the users in this assignment
        // because it's possible that those records haven't yet been created. But doing it this way means that we can reuse
        // the same custom field if it is used in more than one assignment.
        $fieldid = $assignobject->completioninstance;
        if (!isset($this->timestamps[$fieldid])) {
            $params = array('fieldid' => $fieldid);
            $this->timestamps[$fieldid] = $DB->get_records('user_info_data', $params, '', 'userid, data');
        }

        if (!isset($this->timestamps[$fieldid][$userid])) {
            return false;
        }

        $date = $this->timestamps[$fieldid][$userid]->data;
        $date = trim($date);

        if (empty($date)) {
            return false;
        }

        // Check if the profile field contains a date in UNIX timestamp form..
        $timestamppattern = '/^[0-9]+$/';
        if (preg_match($timestamppattern, $date, $matches) > 0) {
            return $date;
        }

        // Check if the profile field contains a date in the specified format.
        $result = totara_date_parse_from_format(get_string('customfieldtextdateformat', 'totara_customfield'), $date);
        if ($result > 0) {
            return $result;
        }

        // Last ditch attempt, try using strtotime to convert the string into a timestamp..
        $result = strtotime($date);
        if ($result != false) {
            return $result;
        }

        // Else we couldn't match a date, so return false.
        return false;
    }
}

class prog_assigment_completion_enrollment_date extends prog_assignment_completion_type {
    private $timestamps;

    public function get_id() {
        return COMPLETION_EVENT_ENROLLMENT_DATE;
    }
    public function get_name() {
        return get_string('programenrollmentdate', 'totara_program');
    }
    public function get_script() {
        return "
            totaraDialogs['completionevent'].clear();
        ";
    }
    public function get_item_name($instanceid) {
        return '';
    }
    public function get_completion_string() {
        return get_string('programenrollmentdate', 'totara_program');
    }
    public function get_timestamp($userid, $assignobject) {
        global $DB;

        // First time calling this function.
        if (!isset($this->timestamps)) {
            $this->timestamps = array();
        }

        // First time calling this function for this assignmentid.
        $assignmentid = $assignobject->id;
        if (!isset($this->timestamps[$assignmentid])) {
            $params = array('assignmentid' => $assignmentid);
            $this->timestamps[$assignmentid] = $DB->get_records('prog_user_assignment', $params, '', 'userid, timeassigned');
        }

        // Get the specific user assignment record.
        if (isset($this->timestamps[$assignmentid][$userid])) {
            return $this->timestamps[$assignmentid][$userid]->timeassigned;
        }

        // The only reason we would be trying to get this timestamp and it doesn't exist is if the record
        // is just about to be created, so just return the current time.
        return time();
    }
}
