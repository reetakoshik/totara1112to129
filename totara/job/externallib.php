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
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 * @package totara_job
 */

defined('MOODLE_INTERNAL') || die;

global $CFG;
require_once("$CFG->libdir/externallib.php");
require_once("$CFG->dirroot/totara/job/lib.php");

/**
 * Totara Job external api.
 *
 * This class presents an external API for interacting with the Jobs API.
 * It can be consumed via any external service, web services, AJAX included.
 *
 * Please note that all functions must have a matching entry in totara/job/db/services.php
 * Internally all functions must perform full access and control checks.
 */
class totara_job_external extends external_api {

    /**
     * Resort all of a user's job assignments.
     *
     * Please note that all job assignments the user has must be referenced here.
     * If the list is incomplete you will get an exception.
     * Each job assignment can only be referenced once, and a the sortorder must be unique.
     * Failing the above will lead to an exception being thrown.
     *
     * @param int $userid The id of the user whose job assignments we are resorting.
     * @param array[] $newsortorders An array of all jobs and their new sort orders. In the following format:
     *     [ ['jobassignid' => X, 'sortorder' => 1], ['jobassignid' => Y, 'sortorder' => 3], ['jobassignid' => Z, 'sortorder' => 2] ]
     * @return array
     * @throws moodle_exception
     */
    public static function resort_job_assignments($userid, array $newsortorders) {

        // Do basic automatic PARAM checks on incoming data, using params description.
        // If any problems are found then exceptions are thrown with helpful error messages.
        $params = self::validate_parameters(self::resort_job_assignments_parameters(), ['userid' => $userid, 'sort' => $newsortorders]);

        // Replace our given arguments now that they have been validated.
        $userid = $params['userid'];
        $newsortorders = $params['sort'];

        // Ensure the current user is allowed to run this function.
        $context = context_system::instance();
        self::validate_context($context);

        // Load the user, we need to verify the user is a valid user.
        $targetuser = core_user::get_user($userid, '*', MUST_EXIST);

        // They have to be able to view the job assignments.
        if (!totara_job_can_view_job_assignments($targetuser)) {
            // Generic error - we don't want to give too much away. Unless debugging is on.
            throw new moodle_exception('error', 'error', '', null, 'No permission to view job assignments.');
        }
        // They have to be able to edit the job assignments.
        if (!totara_job_can_edit_job_assignments($targetuser->id)) {
            // Generic error - we don't want to give too much away. Unless debugging is on.
            throw new moodle_exception('error', 'error', '', null, 'No permission to edit job assignments.');
        }

        $map = [];
        $jobassignids = [];
        foreach ($newsortorders as $instance) {
            $sortorder = (string)$instance['sortorder'];
            $jobassignid = (string)$instance['jobassignid'];
            // Verify that the sort order is unique.
            if (isset($map[$sortorder])) {
                // Generic error - we don't want to give too much away. Unless debugging is on.
                throw new moodle_exception('error', 'error', '', null, 'Duplicate sort order in submit jobs.');
            }
            // Verify that the jobassignid is unique.
            if (in_array($jobassignid, $jobassignids)) {
                // Generic error - we don't want to give too much away. Unless debugging is on.
                throw new moodle_exception('error', 'error', '', null, 'Duplicate job in submit jobs.');
            }
            $jobassignids[$jobassignid] = null;
            $map[$sortorder] = $jobassignid;
        }

        ksort($map);

        // Now update the sort orders here. Internally this function will verify that the map contains
        // only job ids' belonging to the given user, and that all job ids are referenced.
        $jobassignments = \totara_job\job_assignment::resort_all($targetuser->id, array_values($map));

        // Generate a result in the format described by \totara_job_external::resort_job_assignments_returns.
        $result = [];
        foreach ($jobassignments as $jobassignment) {
            $result[] = ['jobassignid' => $jobassignment->id, 'sortorder' => $jobassignment->sortorder];
        }
        return $result;
    }

    /**
     * Returns an object that describes the parameters resort_job_assignments requires.
     *
     * @return external_function_parameters
     */
    public static function resort_job_assignments_parameters() {
        return new external_function_parameters(
            array(
                'userid' => new external_value(PARAM_INT, 'User ID'),
                'sort' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'jobassignid' => new external_value(PARAM_INT, 'Job Assignment ID'),
                            'sortorder' => new external_value(PARAM_INT, 'Unique sortorder'),
                        )
                    )
                )
            )
        );
    }

    /**
     * Returns an object that describes the structure of the return from resort_job_assignments.
     *
     * @return external_multiple_structure
     */
    public static function resort_job_assignments_returns() {
        return new external_multiple_structure(
            new external_single_structure(
                array(
                    'jobassignid' => new external_value(PARAM_INT, 'Job Assignment ID'),
                    'sortorder' => new external_value(PARAM_USERNAME, 'Unique sortorder'),
                )
            )
        );
    }

    /**
     * Deletes a given job assignment from the given user.
     *
     * @param int $userid
     * @param int $jobassignmentid
     * @throws moodle_exception
     */
    public static function delete_job_assignment($userid, $jobassignmentid) {

        // Do basic automatic PARAM checks on incoming data, using params description.
        // If any problems are found then exceptions are thrown with helpful error messages.
        $params = self::validate_parameters(self::delete_job_assignment_parameters(), ['userid' => $userid, 'jobassignmentid' => $jobassignmentid]);

        // Replace our given arguments now that they have been validated.
        $userid = $params['userid'];
        $jobassignmentid = $params['jobassignmentid'];

        // Ensure the current user is allowed to run this function.
        $context = context_system::instance();
        self::validate_context($context);

        // Load the user, we need to verify the user is a valid user.
        $targetuser = core_user::get_user($userid, '*', MUST_EXIST);

        // They have to be able to view the job assignments.
        if (!totara_job_can_view_job_assignments($targetuser)) {
            // Generic error - we don't want to give too much away. Unless debugging is on.
            throw new moodle_exception('error', 'error', '', null, 'No permission to view job assignments.');
        }
        // They have to be able to edit the job assignments.
        if (!totara_job_can_edit_job_assignments($targetuser->id)) {
            // Generic error - we don't want to give too much away. Unless debugging is on.
            throw new moodle_exception('error', 'error', '', null, 'No permission to edit job assignments.');
        }

        $jobassignment = \totara_job\job_assignment::get_with_id($jobassignmentid);
        if ($jobassignment->userid != $targetuser->id) {
            // Generic error - we don't want to give too much away. Unless debugging is on.
            throw new moodle_exception('error', 'error', '', null, 'Given Job Assignment does not belong to the given user.');
        }
        \totara_job\job_assignment::delete($jobassignment);

        // Generate a result in the format described by \totara_job_external::resort_job_assignments_returns.
        $result = [];
        foreach (\totara_job\job_assignment::get_all($targetuser->id) as $jobassignment) {
            $result[] = ['jobassignid' => $jobassignment->id, 'sortorder' => $jobassignment->sortorder];
        }
        return $result;
    }

    /**
     * Returns an object that describes the parameters delete_job_assignment requires.
     *
     * @return external_function_parameters
     */
    public static function delete_job_assignment_parameters() {
        return new external_function_parameters(
            array(
                'userid' => new external_value(PARAM_INT, 'User ID'),
                'jobassignmentid' => new external_value(PARAM_INT, 'Job Assignment ID'),
            )
        );
    }

    /**
     * Returns an object that describes the structure of the return from delete_job_assignment.
     *
     * @return external_multiple_structure
     */
    public static function delete_job_assignment_returns() {
        return new external_multiple_structure(
            new external_single_structure(
                array(
                    'jobassignid' => new external_value(PARAM_INT, 'Job Assignment ID'),
                    'sortorder' => new external_value(PARAM_USERNAME, 'Unique sortorder'),
                )
            )
        );
    }

}