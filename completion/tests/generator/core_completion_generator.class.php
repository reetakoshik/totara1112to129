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
 * @author Maria Torres <maria.torres@totaralms.com>
 * @package core_completion
 * @subpackage test
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Data generator.
 *
 * @package    core_completion
 * @category   test
 */
class core_completion_generator extends component_generator_base {
    /**
     * Set activity completion for the course.
     *
     * @param int $courseid The course id
     * @param array $activities Array of activity objects that will be set for the course completion
     * @param int $activityaggregation - COMPLETION_AGGREGATION_ALL or COMPLETION_AGGREGATION_ANY.
     *            values defined in lib/completionlib.php
     */
    public function set_activity_completion($courseid, $activities, $activityaggregation = COMPLETION_AGGREGATION_ALL) {
        global $CFG;
        require_once($CFG->dirroot.'/completion/criteria/completion_criteria_activity.php');
        require_once($CFG->dirroot.'/completion/criteria/completion_criteria.php');

        $criteriaactivity = array();
        foreach ($activities as $activity) {
            $criteriaactivity[$activity->cmid] = 1;
        }

        if (!empty($criteriaactivity)) {
            $data = new stdClass();
            $data->id = $courseid;
            $data->activity_aggregation = $activityaggregation;
            $data->criteria_activity_value = $criteriaactivity;

            // Set completion criteria activity.
            $criterion = new completion_criteria_activity();
            $criterion->update_config($data);

            // Handle activity aggregation.
            $this->set_aggregation_method($courseid, COMPLETION_CRITERIA_TYPE_ACTIVITY, $activityaggregation);
        }
    }

    /**
     * Sets one or more courses as criteria for completion of another course.
     *
     * @param stdClass $course - the course that we are setting completion criteria for.
     * @param int[] $criteriacourseids - array of course ids to be completion criteria.
     * @param int $aggregationmethod - COMPLETION_AGGREGATION_ALL or COMPLETION_AGGREGATION_ANY.
     * @return void.
     */
    public function set_course_criteria_course_completion($course, $criteriacourseids, $aggregationmethod = COMPLETION_AGGREGATION_ALL) {
        global $CFG;
        require_once($CFG->dirroot.'/completion/criteria/completion_criteria_course.php');
        require_once($CFG->dirroot.'/completion/criteria/completion_criteria.php');

        if (!empty($criteriacourseids)) {
            $data = new stdClass();
            $data->id = $course->id;
            $data->criteria_course_value = $criteriacourseids;

            // Set completion criteria course.
            $criterion = new completion_criteria_course();
            $criterion->update_config($data);

            // Handle course aggregation.
            $this->set_aggregation_method($course->id, COMPLETION_CRITERIA_TYPE_COURSE, $aggregationmethod);
        }
    }

    /**
     * Sets one or more roles as criteria for completion of a course.
     *
     * @param stdClass $course - the course that we are setting completion criteria for.
     * @param int[] $criteriaroleids - array of role ids that must complete the course.
     * @param int $aggregationmethod - COMPLETION_AGGREGATION_ALL or COMPLETION_AGGREGATION_ANY.
     * @return void.
     */
    public function set_course_criteria_role_completion($course, $criteriaroleids, $aggregationmethod = COMPLETION_AGGREGATION_ALL) {
        global $CFG;
        require_once($CFG->dirroot.'/completion/criteria/completion_criteria_role.php');

        if (!empty($criteriaroleids)) {
            $data = new stdClass();
            $data->id = $course->id;
            $data->criteria_role_value = array();
            foreach ($criteriaroleids as $role) {
                $data->criteria_role_value[$role] = true;
            }

            // Set completion criteria course.
            $criterion = new completion_criteria_role();
            $criterion->update_config($data);

            // Handle course aggregation.
            $this->set_aggregation_method($course->id, COMPLETION_CRITERIA_TYPE_ROLE, $aggregationmethod);
        }
    }

    /**
     * Set completion criteria for a course.
     *
     * @param stdClass $course - the course that we are setting completion criteria for.
     * @param array    $criteria - array of criteira to set. The criteriatype should be the array key.
     *                          For multi criteria types (activity, course, role) the array value should be an array
     *                          containing keys elements and aggregationmethod
     * @return void.
     */
    public function set_completion_criteria($course, $criteria) {
        global $CFG;
        require_once($CFG->dirroot.'/completion/criteria/completion_criteria.php');

        if (empty($criteria)) {
            return;
        }

        foreach ($criteria as $criteriatype => $value) {
            switch ($criteriatype) {
                case COMPLETION_CRITERIA_TYPE_SELF:
                case COMPLETION_CRITERIA_TYPE_DATE:
                case COMPLETION_CRITERIA_TYPE_DURATION:
                case COMPLETION_CRITERIA_TYPE_GRADE:
                    /** @var completion_criteria_self $cc */
                    $cc = completion_criteria::factory(array('criteriatype' => $criteriatype));
                    $name = str_replace('completion_', '', get_class($cc));
                    $formval = "{$name}_value";

                    $data = new stdClass();
                    $data->id = $course->id;
                    $data->$formval = $value;

                    $cc->update_config($data);
                    break;

                case COMPLETION_CRITERIA_TYPE_ACTIVITY:
                    if (is_array($value) && !empty($value['elements'])) {
                        $this->set_activity_completion($course->id, $value['elements'],
                            isset($value['aggregationmethod']) ? $value['aggregationmethod'] : null);
                    }
                    break;

                case COMPLETION_CRITERIA_TYPE_ROLE:
                    if (is_array($value) && !empty($value['elements'])) {
                        $this->set_course_criteria_role_completion($course, $value['elements'],
                            isset($value['aggregationmethod']) ? $value['aggregationmethod'] : null);
                    }
                    break;

                case COMPLETION_CRITERIA_TYPE_COURSE:
                    if (is_array($value) && !empty($value['elements'])) {
                        $this->set_course_criteria_course_completion($course, $value['elements'],
                            isset($value['aggregationmethod']) ? $value['aggregationmethod'] : null);
                    }
                    break;
            }
        }
    }

    /**
     * Set the aggregation method for the course and optional criteriatype
     *
     * @param int $courseid Course for which aggregation method is set
     * @param int $criteriatype Criteria type for which aggregation method is set. If null, sets overall aggregation method
     * @param int $aggregationmethod - COMPLETION_AGGREGATION_ALL or COMPLETION_AGGREGATION_ANY.
     * @return void.
     */
    public function set_aggregation_method($courseid, $criteriatype = null, $aggregationmethod = COMPLETION_AGGREGATION_ALL) {
        $aggdata = array('course' => $courseid);
        $aggdata['criteriatype'] = $criteriatype;

        $aggregation = new completion_aggregation($aggdata);
        $aggregation->setMethod($aggregationmethod);
        $aggregation->save();
    }


    /**
     * Enable completion tracking for this course.
     *
     * @param object $course
     */
    public function enable_completion_tracking($course) {
        global $CFG;
        require_once($CFG->dirroot . '/course/lib.php');

        // Update course completion settings.
        $course->enablecompletion = COMPLETION_ENABLED;
        $course->completionstartonenrol = 1;
        $course->completionprogressonview = 1;
        update_course($course);

        // Invalidate the completion cache
        $info = new completion_info($course);
        $info->invalidatecache();
    }

    /**
     * Disable completion tracking for this course.
     *
     * @param object $course
     */
    public function disable_completion_tracking($course) {
        global $CFG;
        require_once($CFG->dirroot . '/course/lib.php');

        // Update course completion settings.
        $course->enablecompletion = COMPLETION_DISABLED;
        update_course($course);

        // Invalidate the completion cache
        $info = new completion_info($course);
        $info->invalidatecache();

    }

    /**
     * Complete a course as a user at a given time.
     *
     * @param stdClass $course - the course to complete.
     * @param stdClass $user - the user completing the course.
     * @param int|null $time - timestamp for completion time. If null, will use current time.
     */
    public function complete_course($course, $user, $time = null) {
        if (!isset($time)) {
            $time = time();
        }
        $coursecompletion = new completion_completion(array(
            'course' => $course->id,
            'userid' => $user->id
        ));
        $coursecompletion->mark_complete($time);
    }


    /**
     * Complete an activity as a user at a given time.
     *
     * @param int $courseid - the course to complete.
     * @param int $userid - the user completing the course.
     * @param int @activityid - the activity to complete.
     * @param int|null $time - timestamp for completion time. If null, will use current time.
     */
    public function complete_activity($courseid, $userid, $activityid, $time = null) {
        if (!isset($time)) {
            $time = time();
        }

        $completion_criteria_data = new completion_criteria_activity(array(
            'course' => $courseid,
            'criteriatype' => COMPLETION_CRITERIA_TYPE_ACTIVITY,
            'moduleinstance' => $activityid
        ));

        $datacompletion = new completion_criteria_completion(array(
            'course' => $courseid,
            'userid' => $userid,
            'criteriaid' => $completion_criteria_data->id
        ));

        $datacompletion->mark_complete($time);
    }


    /**
     * Toggle course complete by role
     *
     * @param stdClass $course - the course to complete.
     * @param int $userid      - the user for which the course is marked complete
     * @param int $roleid      - role marking the course completed for the user
     */
    public function complete_by_role($course, $userid, $roleid) {
        $criteria = completion_criteria::factory(array(
            'course' => $course->id,
            'criteriatype'=>COMPLETION_CRITERIA_TYPE_ROLE,
            'role' => $roleid), true);
        $completion = new completion_info($course);
        $criteria_completions = $completion->get_completions($userid, COMPLETION_CRITERIA_TYPE_ROLE);

        foreach ($criteria_completions as $criteria_completion) {
            if ($criteria_completion->criteriaid == $criteria->id) {
                $criteria->complete($criteria_completion);
                break;
            }
        }
    }

    /**
     * Toggle course complete by self
     *
     * @param stdClass $course - the course to complete.
     * @param int $userid      - the user for which the course is marked complete
     */
    public function complete_by_self($course, $userid) {
        $criteria = completion_criteria::factory(array(
            'course' => $course->id,
            'criteriatype'=>COMPLETION_CRITERIA_TYPE_SELF
        ), true);
        $completion = new completion_info($course);
        $criteria_completions = $completion->get_completions($userid, COMPLETION_CRITERIA_TYPE_SELF);

        foreach ($criteria_completions as $criteria_completion) {
            if ($criteria_completion->criteriaid == $criteria->id) {
                $criteria->complete($criteria_completion);
                break;
            }
        }
    }
}
