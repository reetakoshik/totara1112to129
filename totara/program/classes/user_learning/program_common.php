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
 * @author Alastair Munro <alastair.munro@totaralearning.com>
 * @package totara_program
 * @category user_learning
 */

namespace totara_program\user_learning;

trait program_common {
    /**
     * Process coursesets so that they display correctly.
     *
     * @param array $coursesets and array of coursesets.
     *
     * @return \stdClass An object containing filtered sets, completed
     *                  set count and unavailable set count.
     */
    public function process_coursesets($coursesets) {

        $groups = \prog_content::group_coursesets($this->certification_path, $coursesets);

        $completed_set_count = 0;
        $optional_set_count = 0;
        $unavailable_set_count = 0;

        $then = false;

        $finalsets = array();

        foreach ($groups as $group) {
            /** @var \totara_program\user_learning\courseset[] $group */

            $group_complete = false;

            if ($then) {
                $unavailable_set_count++;
                continue;
            }

            // Get a group of the program coursesets so we can
            // use the programs API to determine if the group is complete.
            $program_groups = array();
            foreach ($group as $set) {
                $program_groups[] = $set->get_set();
            }

            if (prog_courseset_group_complete($program_groups, $this->user->id, false)) {
                foreach ($group as $set) {
                    if (!$set->is_set_complete()) {
                        $optional_set_count++;
                    }
                }
                $group_complete = true;
            }

            // Unset to save memory.
            unset($program_groups);

            foreach ($group as $set) {
                if ($set->is_set_complete()) {
                    if ($set->is_set_optional()) {
                        $optional_set_count++;
                    } else {
                        $completed_set_count++;
                    }

                } else if (!$group_complete) {
                    $finalsets[] = $set;
                }
            }

            if ($group_complete) {
                continue;
            }

            if (isset($set) && $set->nextsetoperator == NEXTSETOPERATOR_THEN) {
                $then = true;
            }
        }

        $data = new \stdClass();
        $data->sets = $finalsets;
        $data->completecount = $completed_set_count;
        $data->optionalcount = $optional_set_count;
        $data->unavailablecount = $unavailable_set_count;

        return $data;
    }

    /**
     * Get the courses within the coursesets for the program
     *
     * @param $includeunavailable bool Include courses from coursesets that that are completed or unavailable
     *
     * @return array An array of course learning items
     */
    public function get_courseset_courses($includeunavailable = true) {
        $this->ensure_course_sets_loaded();
        $courses = [];

        $coursesets = $this->coursesets;

        if ($includeunavailable === false) {
            $processed_coursesets = $this->process_coursesets($coursesets);
            $coursesets = $processed_coursesets->sets;
        }

        /** @var \totara_certification\user_learning\courseset $courseset */
        foreach ($coursesets as $courseset) {
            $courses = array_merge($courses, $courseset->get_courses());
        }
        return $courses;
    }

    /**
     * Check if a course can be completed.
     *
     * @return bool True if a course can be completed
     */
    public function can_be_completed() {
        $this->ensure_completion_loaded();
        return $this->progress_canbecompleted;
    }

    /**
     * Load duedate if it isn't already
     */
    public function ensure_duedate_loaded() {
        if ($this->duedate === null) {
            /** @var \totara_certification\user_learning\item $this->certification */
            $completiondata = $this->certification->get_completion_data($this->user->id);

            $this->duedate = $completiondata->timedue;
        }
    }

    /**
     * Returns the due date info for this item
     *
     * @return \stdClass Object containing due info (duetext and tooltip).
     */
    public function get_dueinfo() {
        return $this->dueinfo;
    }

    /**
     * Get the program coursesets
     *
     * @return array An array of courseset for this item.
     */
    public function get_coursesets() {
        return $this->coursesets;
    }

    /**
     * Checks completion is loaded and returns the percentage complete
     *
     * @return integer The percentage complete
     */
    public function get_progress_percentage() {
        $this->ensure_completion_loaded();
        return $this->progress_percentage;
    }

    /**
     * Removes completed courses from course sets.
     */
    public function remove_completed_courses() {
        /** @var courseset $set */
        foreach ($this->get_coursesets() as $set) {
            $set->remove_completed_courses();
        }
    }

    /**
     * Create string for completed and/or optional sets.
     *
     * @param $completecount int The count of completed coursesets
     * @param $optionalcount int The count of optional coursesets
     *
     * @return string The courseset header text
     */
    public function get_coursesets_header_text($completecount, $optionalcount) {
        $string = '';

        if ($completecount >= 1 && $optionalcount < 1) {
            // Only completed.
            if ($completecount == 1) {
                $string = get_string('completedcoursesets', 'block_current_learning');
            } else if ($completecount > 1) {
                $string = get_string('completedcoursesetsplural', 'block_current_learning', $completecount);
            }
        } else if ($completecount < 1 && $optionalcount >= 1) {
            // Only optional.
            if ($optionalcount == 1) {
                $string = get_string('optionalcoursesets', 'block_current_learning');
            } else if ($optionalcount > 1) {
                $string = get_string('optionalcoursesetsplural', 'block_current_learning', $optionalcount);
            }
        } else if ($completecount >= 1 && $optionalcount >= 1) {
            if ($completecount == 1 && $optionalcount == 1) {
                // 1 completed and 1 optional set
                $string = get_string('onecompletedandoneoptionalcoursesets', 'block_current_learning');
            } else if ($completecount == 1 && $optionalcount > 1) {
                // 1 completed set and 2 optional sets
                $string = get_string('onecompletedandmultipleoptionalcoursesets', 'block_current_learning', $optionalcount);
            } else if ($completecount > 1 && $optionalcount == 1) {
                // 2 completed sets and 1 optional set
                $string = get_string('multiplecompletedandoneoptionalcoursesets', 'block_current_learning', $completecount);
            } else if ($completecount > 1 && $optionalcount > 1) {
                // 2 complete sets and 2 optional sets
                $string = get_string('multiplecompletedandmultipleoptionalcoursesets', 'block_current_learning',
                    array('completed' => $completecount, 'optional' => $optionalcount));
            }
        }

        return $string;
    }

}
