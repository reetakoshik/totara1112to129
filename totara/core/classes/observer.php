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
 * @author Jonathan Newman <jonathan.newman@catalyst.net.nz>
 * @package totara_core
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Totara core event handler.
 */
class totara_core_observer {
    /** @var array $bulkinprogress */
    static public $bulkenrolling = array();

    /**
     * Start of bulk processing.
     * @param \totara_core\event\bulk_enrolments_started $event
     */
    public static function bulk_enrolments_started(\totara_core\event\bulk_enrolments_started $event) {
        self::$bulkenrolling[$event->courseid] = $event->courseid;
    }

    /**
     * End of bulk processing.
     * @param \totara_core\event\bulk_enrolments_ended $event
     */
    public static function bulk_enrolments_ended(\totara_core\event\bulk_enrolments_ended $event) {
        global $CFG;
        require_once($CFG->dirroot . '/completion/completion_completion.php');

        unset(self::$bulkenrolling[$event->courseid]);
        completion_start_user_bulk($event->courseid);
    }

    /**
     * Handler function called when a user_confirmed event is triggered
     *
     * The guts of this function were copied from enrol/cohort/locallib.php enrol_cohort_handler::member_added()
     *
     * @param \core\event\user_confirmed $event
     * @return bool Success status
     */
    public static function user_confirmed(\core\event\user_confirmed $event) {
        global $DB;

        $sql = " SELECT e.*, r.id as roleexists
                   FROM {enrol} e
              LEFT JOIN {role} r
                     ON r.id = e.roleid
             INNER JOIN {cohort_members} cm
                     ON e.customint1 = cm.cohortid
                  WHERE e.enrol = 'cohort'
                    AND cm.userid = :uid";
        $params = array('uid' => $event->relateduserid);
        $instances = $DB->get_records_sql($sql, $params);

        $plugin = enrol_get_plugin('cohort');
        foreach ($instances as $instance) {
            if ($instance->status != ENROL_INSTANCE_ENABLED ) {
                // No roles for disabled instances.
                $instance->roleid = 0;
            } else if ($instance->roleid and !$instance->roleexists) {
                // Invalid role - let's just enrol, they will have to create new sync and delete this one.
                $instance->roleid = 0;
            }
            unset($instance->roleexists);
            // No problem if already enrolled.
            $plugin->enrol_user($instance, $event->relateduserid, $instance->roleid, 0, 0, ENROL_USER_ACTIVE);

            // Sync groups.
            if ($instance->customint2) {
                if (!groups_is_member($instance->customint2, $event->relateduserid)) {
                    if ($group = $DB->get_record('groups', array('id'=>$instance->customint2, 'courseid'=>$instance->courseid))) {
                        groups_add_member($group->id, $event->relateduserid, 'enrol_cohort', $instance->id);
                    }
                }
            }
        }

        return true;
    }

    /**
     * Triggered by the user_enrolled event, this function is run when a user is enrolled in the course
     * and creates a completion_completion record for the user if completion is enabled for this course
     *
     * @param  \core\event\user_enrolment_created $event
     * @return boolean always true
     */
    public static function user_enrolment(\core\event\user_enrolment_created $event) {
        global $CFG, $DB;

        if (isset(self::$bulkenrolling[$event->courseid])) {
            // Enrolment marked when bulk finished.
            return true;
        }

        require_once($CFG->dirroot . '/completion/completion_completion.php');

        $ue = $event->get_record_snapshot('user_enrolments', $event->objectid);

        $courseid = $event->courseid;
        $userid = $ue->userid;
        $timestart = $ue->timestart;

        // Load course.
        if (!$course = $DB->get_record('course', array('id' => $courseid))) {
            debugging('Could not load course id '.$courseid);
            return true;
        }

        // Create completion object.
        $cinfo = new completion_info($course);

        // Check completion is enabled for this site and course.
        if (!$cinfo->is_enabled()) {
            return true;
        }

        // Create completion record.
        $data = array(
            'userid'    => $userid,
            'course'    => $course->id
        );
        $completion = new completion_completion($data);

        // If no start on enrol, don't mark as started.
        if (empty($course->completionstartonenrol)) {
            $completion->mark_enrolled();
        } else {
            $completion->mark_enrolled($timestart);
        }

        // Review criteria.
        completion_handle_criteria_recalc($course->id, $userid);

        return true;
    }

    /**
     * Triggered by the module_completion event, this function
     * checks if the criteria exists, if it is applicable to the user
     * and then reviews the user's state in it.
     *
     * @param   object      $event
     * @return  boolean
     */
    public static function criteria_course_calc(\totara_core\event\module_completion $event) {
        global $CFG, $DB;
        include_once($CFG->dirroot . '/completion/completion_completion.php');

        $eventdata = $event->get_data();
        // Check if applicable course criteria exists.
        $criteria = completion_criteria::factory($eventdata['other']);
        $params = array_intersect_key($eventdata['other'], array_flip($criteria->required_fields));

        $criteria = $DB->get_records('course_completion_criteria', $params);
        if (!$criteria) {
            return true;
        }

        // Loop through, and see if the criteria apply to this user.
        foreach ($criteria as $criterion) {

            $course = new stdClass();
            $course->id = $criterion->course;
            $cinfo = new completion_info($course);

            if (!$cinfo->is_tracked_user($eventdata['other']['userid'])) {
                continue;
            }

            // Load criterion.
            $criterion = completion_criteria::factory((array) $criterion);

            // Load completion record.
            $data = array(
                'criteriaid'    => $criterion->id,
                'userid'        => $eventdata['other']['userid'],
                'course'        => $criterion->course
            );
            $completion = new completion_criteria_completion($data);

            // Review and mark complete if necessary.
            $criterion->review($completion);
        }

        return true;
    }

    /**
     * Triggered when 'course_completed' event is triggered.
     *
     * When a course is completed, check to see if the course is the criteria for completion of another course.
     *
     * @param \core\event\course_completed $event
     * @return bool
     */
    public static function course_criteria_review(\core\event\course_completed $event) {
        global $DB;

        // Check if applicable course criteria exists.
        $data = $event->get_data();
        $eventdata = new stdClass();
        $eventdata->criteriatype = COMPLETION_CRITERIA_TYPE_COURSE;
        $eventdata->courseinstance = $data['courseid'];
        // The id for the user that completed the course is in 'relateduserid'.
        // The value for 'userid' may only be the person who created the completion (e.g. an admin marking complete).
        $eventdata->userid = $data['relateduserid'];
        $eventdata->timecompleted = time();

        $criteria = completion_criteria::factory((array)$eventdata);
        $params = array_intersect_key((array)$eventdata, array_flip($criteria->required_fields));

        $criteria = $DB->get_records('course_completion_criteria', $params);
        if (!$criteria) {
            return true;
        }

        // Loop through, and see if the criteria apply to this user.
        foreach ($criteria as $criterion) {
            $course = new stdClass();
            $course->id = $criterion->course;
            $cinfo = new completion_info($course);

            if (!$cinfo->is_tracked_user($eventdata->userid)) {
                continue;
            }

            // Load criterion.
            $criterion = completion_criteria::factory((array) $criterion);

            // Load completion record.
            $data = array(
                'criteriaid'    => $criterion->id,
                'userid'        => $eventdata->userid,
                'course'        => $criterion->course
            );
            // Add the timecompleted for aggregated course completion.
            if (isset($eventdata->timecompleted)) {
                $data['timecompleted'] = $eventdata->timecompleted;
            }

            // We need to use the DATA_OBJECT_FETCH_BY_KEY to ensure it finds a record
            // with matching values for the unique columns (criteriaid, userid, course)
            // regardless of whether the timecompleted matches or not.
            $completion = new completion_criteria_completion($data, DATA_OBJECT_FETCH_BY_KEY);

            // Review and mark complete if necessary.
            $criterion->review($completion);
        }

        return true;
    }

    /**
     * Reset Totara menu caches for all users.
     *
     * \core\event\base
     */
    public static function totara_menu_reset_all_caches(\core\event\base $event) {
        totara_menu_reset_all_caches();
    }

    /**
     * Reset Totara menu caches for current user session only.
     *
     * \core\event\base
     */
    public static function totara_menu_reset_session_cache(\core\event\base $event) {
        totara_menu_reset_session_cache();
    }

    /**
     * Event that is triggered when a user is deleted.
     *
     * Removes an user from reminders they are associated with, tables to clear are
     * reminder_sent
     *
     * @param \core\event\user_deleted $event
     *
     */
    public static function user_deleted(\core\event\user_deleted $event) {
        global $DB;

        $userid = $event->objectid;

        $transaction = $DB->start_delegated_transaction();

        // Remove the user from reminders.
        $DB->delete_records('reminder_sent', array('userid' => $userid));

        $transaction->allow_commit();
    }
}
