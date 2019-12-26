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

use external_function_parameters;
use external_single_structure;
use external_multiple_structure;
use external_value;

global $CFG;
require_once("$CFG->libdir/externallib.php");

final class external extends \external_api {

    /**
     * Check permissions needed to view program assignments
     *
     * Note: this only checks the capability not if the program is available
     * the can_update function in \totara_program\assignment\helper includes
     * the availability check.
     *
     * @param int $programid
     */
    private static function ensure_user_can_manage_program_assignments(int $programid) {
        // Check user permissions
        $context = \context_program::instance($programid);
        require_capability('totara/program:configureassignments', $context);
    }

    private static function single_assignment() {
        return new external_single_structure([
            'id' => new external_value(PARAM_INT, 'something'),
            'name' => new external_value(PARAM_RAW, 'Name of assignment'),
            'type' => new external_value(PARAM_RAW, 'Type of assignment'),
            'type_id' => new external_value(PARAM_INT, 'Type of assignment'),
            'checkbox' => new external_value(PARAM_RAW, 'Type of assignment'),
            'dropdown' => new external_value(PARAM_RAW, 'Type of assignment'),
            'includechildren' => new external_value(PARAM_INT, 'Are we including children items'),
            'duedate' => new external_value(PARAM_RAW, 'Due date'),
            'duedateupdatable' => new external_value(PARAM_RAW, 'Whether duedate is updateable'),
            'actualduedate' => new external_value(PARAM_RAW, 'Actual Due date'),
            'learnercount' => new external_value(PARAM_INT, 'Number of learners within this assignment'),
        ]);
    }

    private static function update_state() {
        return new external_single_structure([
            'status_string' => new external_value(PARAM_RAW, 'Message for assignments interface'),
            'state' => new external_value(PARAM_RAW, 'The state of the exception message'),
            'exception_count' => new external_value(PARAM_INT, 'The number assignment exceptions in this Program'),
        ]);
    }

    /**
     * Add program assignments for given type and ids
     *
     * @param int $programid
     * @param int $typeid
     * @param array $items Array of items to create assignments for
     *
     * @return array Array of assignments with data needed for rendering new rows
     */
    public static function add_assignments(int $programid, int $typeid, array $items) {
        global $SESSION, $CFG;
        require_once($CFG->dirroot . '/totara/program/program.class.php');

        // Check manage program capability
        if (!helper::can_update($programid)) {
            throw new \moodle_exception('error:cannotupdateassignment', 'totara_program');
        }

        $context = \context_program::instance($programid);
        self::validate_context($context);

        $types = helper::get_types();
        $class = $types[$typeid];
        $classpath = '\\totara_program\\assignment\\' . $class;

        $newassignments = [];

        if (!isset($SESSION->recentprogramassignments)) {
            $SESSION->recentprogramassignments = [];
        }

        foreach ($items as $id) {
           $assignment = $classpath::create_from_instance_id($programid, $typeid, $id);
           $assignment->save();

           $newassignments[] = $assignment;

           // Used for 'recently added' filter
           $SESSION->recentprogramassignments[] = $assignment->get_id();
        }

        $renderable_items = \totara_program\output\assignment_table::create_from_assignments($newassignments);
        $templatedata = $renderable_items->get_template_data();

        // Get notification message
        $program = new \program($programid);
        $data = $program->get_current_status();
        $exceptioncount = (int)$program->get_exception_count();

        $return = [];
        $return['status'] = [
            'status_string' => helper::build_status_string($data),
            'state' => $data->notification_state,
            'exception_count' => $exceptioncount,
        ];
        $return['items'] = $templatedata['items'];

        return $return;
    }

    public static function add_assignments_parameters() {
        return new external_function_parameters([
            'programid' => new external_value(PARAM_INT, 'Program ID'),
            'typeid' => new external_value(PARAM_INT, 'Type Id being added'),
            'items' => new external_multiple_structure(
                new external_value(PARAM_INT, 'ID of Assignment item being added'),
                'List of assignment instance ids'
            ),
        ]);
    }

    public static function add_assignments_returns() {
        return new external_single_structure([
            'status' => self::update_state(),
            'items' => new external_multiple_structure(self::single_assignment(), 'List of assignments', VALUE_OPTIONAL),
        ]);
    }

    /**
     * Return a filtered list of assignments
     */
    public static function filter_assignments(array $categories, bool $recent, string $term, int $programid) {
        global $DB, $SESSION;

        self::ensure_user_can_manage_program_assignments($programid);

        $context = \context_program::instance($programid);
        self::validate_context($context);

        // Get all assignments that match category and programid
        if (!empty($categories)) {
            list($catin, $catparams) = $DB->get_in_or_equal($categories, SQL_PARAMS_NAMED);
            $catsql = "assignmenttype $catin";
        } else {
            $catsql = '1=1';
            $catparams = [];
        }

        // Add recently added prog assignment into the filter sql if any
        // have been set
        if ($recent === true && isset($SESSION->recentprogramassignments)) {
            list($recentsql, $recentparams) =  $DB->get_in_or_equal($SESSION->recentprogramassignments, SQL_PARAMS_NAMED);
            $recentsql = 'id ' . $recentsql;
        } else if ($recent === true){
            $recentsql = '1=0';
            $recentparams = [];
        } else {
            $recentsql = '1=1';
            $recentparams = [];
        }

        $sql = "SELECT * FROM {prog_assignment}
                WHERE programid = :programid
                AND $catsql AND $recentsql";
        $params = array_merge(['programid' => $programid], $catparams, $recentparams);

        $records = $DB->get_records_sql($sql, $params);

        $types = helper::get_types();

        $assignments = [];

        foreach ($records as $record) {
            $class = $types[$record->assignmenttype];
            $classpath = '\\totara_program\\assignment\\' . $class;
            $assignments[] = $classpath::create_from_id($record->id);
        }

        // If the term isn't empty the filter the results
        if (!empty($term)) {
            $assignments = self::search_assignments($assignments, $term);
        }

        // If we have more than the max results then return early
        // and show too many results template.
        if (count($assignments) > helper::MAX_RESULTS) {
            $return = [];
            $return['toomany'] = true;
            $return['count'] = 0;
            return $return;
        }

        // Sort assignment
        \core_collator::asort_objects_by_method($assignments, 'get_name', \core_collator::SORT_NATURAL);

        $assignmentobjects = \totara_program\output\assignment_table::create_from_assignments($assignments);
        $return = $assignmentobjects->get_template_data();
        $return['toomany'] = false;
        $return['count'] = count($assignments);

        return $return;
    }

    public static function filter_assignments_parameters() {
        return new external_function_parameters([
            'categories' => new external_multiple_structure(
                new external_value(PARAM_INT, 'Category Type id'),
                'List of type ids'
            ),
            'recent' => new external_value(PARAM_BOOL, 'Recently Added'),
            'term' => new external_value(PARAM_RAW, 'Search query'),
            'program_id' => new external_value(PARAM_INT, 'Program ID'),
        ]);
    }

    public static function filter_assignments_returns() {
        return new external_single_structure([
            'items' => new external_multiple_structure(self::single_assignment(), 'List of assignments', VALUE_OPTIONAL),
            'count' => new external_value(PARAM_INT, 'The number of results'),
            'toomany' => new external_value(PARAM_BOOL, 'Are there too many results')
        ]);
    }

    /**
     * Remove a program assignment given the ID
     *
     * @param int $assignmentid
     */
    public static function remove_assignment(int $assignmentid) {
        global $CFG;
        require_once($CFG->dirroot . '/totara/program/program.class.php');

        // Create instance of the assignment
        $assignment = base::create_from_id($assignmentid);

        if (!helper::can_update($assignment->get_programid())) {
            throw new \moodle_exception('error:cannotupdateassignment', 'totara_program');
        }

        // And remove it
        $assignment->remove();
        $program = new \program($assignment->get_programid());

        // Get notification message
        $data = $program->get_current_status();
        $exceptioncount = (int)$program->get_exception_count();

        $return = [];
        $return['status'] = [
            'status_string' => helper::build_status_string($data),
            'state' => $data->notification_state,
            'exception_count' => $exceptioncount
        ];
        $return['success'] = true;

        return $return;
    }

    public static function remove_assignment_parameters() {
        return new external_function_parameters([
            'assignment_id' => new external_value(PARAM_INT, 'Assignment ID'),
        ]);
    }

    public static function remove_assignment_returns() {
        return new external_single_structure([
            'status' => self::update_state(),
            'success' => new external_value(PARAM_BOOL, 'Success or failure')
        ]);
    }

    /**
     * Set fixed due date
     *
     * @param int $assignmentid
     * @param string $date
     * @param int $hour
     * @param int $minute
     */
    public static function set_fixed_due_date(int $assignmentid, string $date, int $hour, int $minute) {
        global $CFG;
        require_once($CFG->dirroot . '/totara/program/program.class.php');

        $assignment = base::create_from_id($assignmentid);

        if (!$assignment->can_update_date()) {
            throw new \moodle_exception('error:cannotupdateduedate', 'totara_program');
        }

        // Check user permissions
        if (!helper::can_update($assignment->get_programid())) {
            throw new \moodle_exception('error:cannotupdateassignment', 'totara_program');
        }

        // Convert date, hour and minute into timestamp
        $completiontimehour = sprintf("%02d", $hour);
        $completiontimeminute = sprintf("%02d", $minute);

        $duedate = totara_date_parse_from_format(get_string('datepickerlongyearparseformat', 'totara_core').' H:i',
            $date.' '.$completiontimehour.':'.$completiontimeminute);
        $assignment->set_duedate($duedate);

        $assignment->save();

        $return = [];
        $return['duedate'] = $assignment->get_duedate()->string;
        $return['duedateupdatable'] = $assignment->get_duedate()->changeable;
        $return['actualduedate'] = $assignment->get_actual_duedate();

        // Get notification message
        $program = new \program($assignment->get_programid());
        $data = $program->get_current_status();
        $exceptioncount = (int)$program->get_exception_count();
        $return['status'] = [
            'status_string' => helper::build_status_string($data),
            'state' => $data->notification_state,
            'exception_count' => $exceptioncount
        ];

        return $return;
    }

    public static function set_fixed_due_date_parameters() {
        return new external_function_parameters([
            'assignment_id' => new external_value(PARAM_INT, 'Assignment ID'),
            'duedate' => new external_value(PARAM_RAW, 'Date to set'),
            'hour' => new external_value(PARAM_INT, 'Hour due'),
            'minute' => new external_value(PARAM_INT, 'Minute due'),
        ]);
    }

    public static function set_fixed_due_date_returns() {
        return self::due_date_context();
    }

    /**
     * @param int $assignmentid
     * @param int $num Multiple of time period
     * @param int $period Time period (eg. Days, Weeks, etc.)
     * @param int $event
     * @param int $eventinstanceid
     *
     */
    public static function set_relative_due_date(int $assignmentid, int $num, int $period, $event, int $eventinstanceid = 0) {
        global $CFG;
        require_once($CFG->dirroot . '/totara/program/program.class.php');

        $assignment = base::create_from_id($assignmentid);

        if (!$assignment->can_update_date()) {
            throw new \moodle_exception('error:cannotupdateduedate', 'totara_program');
        }

        if (!helper::can_update($assignment->get_programid())) {
            throw new \moodle_exception('error:cannotupdateassignment', 'totara_program');
        }

        $duration = \program_utilities::duration_implode($num, $period);

        // For relative due dates the duration is set in the completiontime field
        $assignment->set_duedate($duration, $event, $eventinstanceid);
        $duedate = $assignment->get_duedate();

        $return = [];
        $return['duedate'] = $duedate->string;
        $return['duedateupdatable'] = $duedate->changeable;
        $return['actualduedate'] = $assignment->get_actual_duedate();

        // Get notification message
        $program = new \program($assignment->get_programid());
        $data = $program->get_current_status();
        $exceptioncount = (int)$program->get_exception_count();
        $return['status'] = [
            'status_string' => helper::build_status_string($data),
            'state' => $data->notification_state,
            'exception_count' => $exceptioncount
        ];

        return $return;
    }

    public static function set_relative_due_date_parameters() {
        return new external_function_parameters([
            'assignment_id' => new external_value(PARAM_INT, 'Assignment ID'),
            'num' => new external_value(PARAM_INT, 'Multiplier of time period'),
            'period' => new external_value(PARAM_INT, 'Period of time'),
            'event' => new external_value(PARAM_INT, 'Completion event'),
            'eventinstanceid' => new external_value(PARAM_INT, 'ID of item associated with event'),
        ]);
    }

    public static function set_relative_due_date_returns() {
        return self::due_date_context();
    }

    public static function remove_due_date(int $assignmentid) {

        $assignment = base::create_from_id($assignmentid);

        if (!helper::can_update($assignment->get_programid())) {
            throw new \moodle_exception('error:cannotupdateassignment', 'totara_program');
        }

        $assignment->set_duedate(-1);

        // Get notification message
        $program = new \program($assignment->get_programid());
        $data = $program->get_current_status();
        $exceptioncount = (int)$program->get_exception_count();
        $duedate = $assignment->get_duedate();

        return [
            'status' => [
                'status_string' => helper::build_status_string($data),
                'state' => $data->notification_state,
                'exception_count' => $exceptioncount
            ],
            'duedate' => $duedate->string,
            'duedateupdatable' => $duedate->changeable,
            'actualduedate' => $assignment->get_actual_duedate()
        ];
    }

    public static function remove_due_date_parameters() {
        return new external_function_parameters([
            'assignment_id' => new external_value(PARAM_INT, 'Assignment ID')
        ]);
    }

    public static function remove_due_date_returns() {
        return self::due_date_context();
    }

    private static function due_date_context() {
        return new external_single_structure([
            'status' => self::update_state(),
            'duedateupdatable' => new external_value(PARAM_RAW, 'Whether duedate is updateable'),
            'duedate' => new external_value(PARAM_RAW, 'Date string'),
            'actualduedate' => new external_value(PARAM_RAW, 'Date string'),
        ]);
    }

    public static function set_includechildren(int $assignmentid, int $value) {

        $assignment = base::create_from_id($assignmentid);

        $assignment->set_includechildren($value);

        $assigneecount = $assignment->get_user_count();

        // Get notification message
        $program = new \program($assignment->get_programid());
        $data = $program->get_current_status();
        $exceptioncount = (int)$program->get_exception_count();

        return [
            'status' => [
                'status_string' => helper::build_status_string($data),
                'state' => $data->notification_state,
                'exception_count' => $exceptioncount
            ],
            'numusers' => $assigneecount
        ];
    }

    public static function set_includechildren_parameters() {
        return new external_function_parameters([
            'assignmentid' => new external_value(PARAM_INT, 'Assignment ID'),
            'value' => new external_value(PARAM_INT, 'Value to set')
        ]);
    }

    public static function set_includechildren_returns() {
        return new external_single_structure([
            'status' => self::update_state(),
            'numusers' => new external_value(PARAM_INT, 'Number of assigned users')
        ]);
    }

    /**
     * Search assignment for the term
     *
     * @param array $assignments
     * @param string $term Search term
     *
     * @return array An array of assignment that match the search
     */
    public static function search_assignments(array $assignments, string $term) {
        $results = [];
        $count = 0;
        foreach ($assignments as $assignment) {
            if (\core_text::strpos(\core_text::strtolower($assignment->get_name()), \core_text::strtolower($term)) !== false) {
                $results[] = $assignment;
                $count++;
            }

            if ($count > helper::MAX_RESULTS) {
                break;
            }
        }
        return $results;
    }
}
