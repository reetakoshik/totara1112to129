<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2016 onwards Totara Learning Solutions LTD
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
 * @author Simon Player <simon.player@totaralearning.com>
 * @package totara_certification
 */

namespace totara_certification\user_learning;

use totara_core\user_learning\designation;
use \totara_core\user_learning\designation_asset;

class courseset implements designation {

    use designation_asset;

    /**
     * The course set id.
     * @var int
     */
    public $id;

    /**
     * The course set name.
     * @var string
     */
    public $name;

    /**
     * The operator between this set and the next.
     * @var int
     */
    public $nextsetoperator;

    /**
     * What in this course set needs to be completed.
     * One of COMPLETIONTYPE_*
     * @var int
     */
    public $completiontype;

    /**
     * The type of the content.
     * One of CONTENTTYPE_*
     * @var int
     */
    public $contenttype;

    /**
     * The total number of points that must be acquired in order to complete this course set.
     * @var int
     */
    public $totalpointstocomplete;

    /**
     * The number of points the user has earned so far by completing courses in this course set.
     * @var int
     */
    public $totalpointsearned;

    /**
     * True if the user has completed this course set, false otherwise.
     * @var bool
     */
    public $complete;

    /**
     * The id of the course customfield that houses the points each course is worth.
     * @var int
     */
    public $coursesumfield;

    /**
     * The certification path the user is currently on, one of CERTIFPATH_*
     * @var int
     */
    public $certifpath;

    /**
     * The total number of courses the user has completed.
     * @var int
     */
    public $totalcompletedcourses = 0;

    /**
     * The minimum number of courses the user must complete.
     * Only used for multi course sets.
     * @var int
     */
    public $mincourses = 0;

    /**
     * @var item
     */
    public $program;

    /**
     * @var \stdClass
     */
    private $user;

    /**
     * @var \totara_certification\user_learning\course[]
     */
    private $courses;

    /**
     * @var \course_set
     */
    private $set;

    /**
     * Gets the details of the course set requested.
     *
     * @param item $program
     * @param \course_set $set
     * @param object $user
     *
     * @return courseset Details of the courseset
     */
    public static function from_course_set(item $program, \course_set $set, $user) {

        $courseset = new self;
        $courseset->program = $program;
        $courseset->user = $user;
        $courseset->id = $set->id;
        $courseset->name = $set->label;
        $courseset->nextsetoperator = $set->nextsetoperator;
        $courseset->completiontype = $set->completiontype;
        $courseset->contenttype = $set->contenttype;
        $courseset->certifpath = $set->certifpath;
        $courseset->totalcompletedcourses = 0;

        if ($set instanceof \multi_course_set) {
            $courseset->coursesumfield = $set->coursesumfield;
            $courseset->totalpointsearned = 0;
            $courseset->totalpointstocomplete = (int)$set->coursesumfieldtotal;
            $courseset->mincourses = (int)$set->mincourses;
        }

        $courseset->complete = $set->is_courseset_complete($user->id);

        $courseset->set = $set;

        return $courseset;
    }

    /**
     * Get the courses for this courseset.
     *
     * @return \totara_certification\user_learning\course[] Array of courses in this set.
     */
    public function get_courses() {
        $this->ensure_courses_loaded();
        return $this->courses;
    }

    /**
     * Removes completed courses from this course set.
     */
    public function remove_completed_courses() {
        $this->ensure_courses_loaded();
        foreach ($this->courses as $key => $course) {
            if ($course->is_complete() === true) {
                // Course complete, lets remove it.
                unset($this->courses[$key]);
            }
        }
    }

    /**
     * Check if the courseset is complete.
     *
     * @return bool True if this set is complete
     */
    public function is_set_complete() {
        $complete = $this->set->check_courseset_complete($this->user->id);

        return $complete;
    }

    /**
     * Check if the courseset is optional.
     *
     * @return bool True if this courseset is optional.
     */
    public function is_set_optional() {
        $optional = $this->set->is_considered_optional();

        return $optional;
    }

    /**
     * Remove a course from the courses array
     *
     * @param int $id of the course to remove
     */
    public function remove_course($id) {
        foreach ($this->courses as $key => $course) {
            if ($course->id == $id) {
                unset($this->courses[$key]);
            }
        }
    }

    /**
     * Check that courses have been loaded for the set and
     * if not, then load them.
     */
    protected function ensure_courses_loaded() {
        if ($this->courses !== null) {
            return;
        }
        $this->courses = [];

        if ($this->set instanceof \multi_course_set) {
            foreach ($this->set->courses as $course) {
                /** @var course $course */
                $course = course::one($this->user, $course);
                if (!empty($this->coursesumfield)) {
                    if ($course->is_complete()) {
                        // If the course is complete then add the point to the total.
                        $this->totalpointsearned += $course->get_points($this);
                    }
                }
                $this->totalcompletedcourses += $course->is_complete();
                $course->set_owner($this->program);
                $this->courses[] = $course;
            }
        } else if ($this->set instanceof \recurring_course_set) {
            $this->courses[] = course::one($this->user, $this->set->course);
        }
    }

    /**
     * Exports certification item data for the template
     *
     * @return \stdClass Object containing data about this item
     */
    public function export_for_template() {

        $setdata = new \stdClass();
        $setdata->name = format_string($this->name);

        // Course score field.
        if (!empty($this->coursesumfield)) {
            $scorefield = customfield_get_field_instance($this, $this->coursesumfield, 'course', 'course');
            $setdata->scorefieldname = format_string($scorefield->field->fullname);
        }

        // Completion text.
        switch ($this->completiontype) {
            case COMPLETIONTYPE_ALL;
                $setdata->completion_text = get_string('completeallcoursestoprogress', 'totara_program');
                break;
            case COMPLETIONTYPE_ANY;
                $setdata->completion_text = get_string('completeanycoursetoprogress', 'totara_program');
                break;
            case COMPLETIONTYPE_SOME;
                if ($this->mincourses !== 0 && $this->totalpointstocomplete === 0) {
                    // Complete a number of courses to progress.
                    $coursesrequired = $this->mincourses - $this->totalcompletedcourses;
                    $langstring = $this->mincourses == 1 ? 'completexcoursestoprogress' : 'completexcoursestoprogressplural';
                    $setdata->completion_text = get_string($langstring, 'totara_program', $coursesrequired);
                } elseif ($this->mincourses === 0 && $this->totalpointstocomplete !== 0) {
                    // Obtain more points to progress.
                    $setcompletiontext = new \stdClass();
                    $setcompletiontext->scorefieldname = $setdata->scorefieldname;
                    $setcompletiontext->pointsrequired = $this->totalpointstocomplete - $this->totalpointsearned;
                    $setdata->completion_text = get_string('obtainxpointstoprogress', 'totara_program', $setcompletiontext);
                } elseif ($this->mincourses !== 0 && $this->totalpointstocomplete !== 0) {
                    // Complete a number of courses and obtain more points to progress.
                    $setcompletiontext = new \stdClass();
                    $setcompletiontext->scorefieldname = $setdata->scorefieldname;
                    $setcompletiontext->pointsrequired = $this->totalpointstocomplete - $this->totalpointsearned;
                    $setcompletiontext->coursesrequired = $this->mincourses - $this->totalcompletedcourses;
                    $langstring = $this->mincourses == 1 ? 'obtainxpointsandcompletexcoursestoprogress' : 'obtainxpointsandcompletexcoursestoprogressplural';
                    $setdata->completion_text = get_string($langstring, 'totara_program', $setcompletiontext);
                }
                break;
            default:
                $setdata->completion_text = '';

        }

        // Next step operator.
        switch ($this->nextsetoperator) {
            case NEXTSETOPERATOR_OR:
                $setoperator = get_string('or', 'totara_program');
                break;
            case NEXTSETOPERATOR_AND:
                $setoperator = get_string('and', 'totara_program');
                $setdata->nextseticon = 'plus';
                break;
            default:
                $setoperator = '';
                break;
        }
        $setdata->nextsetoperator = $setoperator;

        $setdata->courses = array();

        $courses = $this->get_courses();

        foreach ($courses as $course) {
            $courseinfo = $course->export_for_template();
            if ($points = $course->get_points($this)) {
                $courseinfo->points = $points . ' ' . $setdata->scorefieldname;
            }
            $setdata->courses[] = $courseinfo;
        }

        return $setdata;
    }

    /**
     * Returns true if this asset class represents a primary learning item... it does not.
     *
     * @return bool
     */
    public static function is_a_primary_user_learning_class() {
        return false;
    }

    /**
     * Returns true if this asset is a primary learning item... its not.
     *
     * @return bool
     */
    public function is_primary_user_learning_item() {
        return false;
    }

    /**
     * Returns the set.
     *
     * @return object An instance of a program set
     */
    public function get_set() {
        return $this->set;
    }
}
