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
 * @author Alastair Munro <alastair.munro@totaralearning.com>
 * @package totara_plan
 */

namespace totara_plan\user_learning;

use \totara_core\user_learning\item_base;
use \totara_core\user_learning\item_has_progress;
use \totara_core\user_learning\designation_primary;

global $CFG;
require_once($CFG->dirroot . '/totara/program/lib.php');

class item extends item_base implements item_has_progress {

    use designation_primary;

    /**
     * The learning plan instance.
     * @var \development_plan
     */
    protected $plan;

    /**
     * An array of courses within this learning plan.
     * @var \totara_plan\user_learning\course[]
     */
    protected $courses;
    protected $competencies;
    protected $programs;

    protected $progress_canbecompleted = null;
    protected $progress_percentage;
    protected $progress_summary;

    /**
     * Gets all learning plan learning items for the given user.
     *
     * @param \stdClass|int $userorid A user object or user ID
     *
     * @return array An arrray of learning object of type item
     */
    public static function all($userorid) {
        // Check learningplans are enabled.
        if (totara_feature_disabled('learningplans')) {
            return [];
        }
        $items = [];
        $user = self::resolve_user($userorid);
        $plans = \dp_get_plans($user->id);
        foreach ($plans as $plan) {
            $item = new self($user, $plan);

            $item->get_courses();
            $item->get_programs();

            $items[] = $item;
        }

        return $items;
    }

    /**
     * Gets a single course learning item for a give user.
     *
     * @param \stdClass|int $userorid A user object of ID
     * @param \stdClass|int $itemid A plan ID
     *
     * @return item_base|false A learning item object for the program
     */
    public static function one($userorid, $itemid) {
        // Check programs are enabled.
        if (totara_feature_disabled('learningplans')) {
            return false;
        }
        $user = self::resolve_user($userorid);
        $plan = new \development_plan($itemid);

        // Check that the plan belongs to this user.
        if ($plan->userid != $user->id) {
            return false;
        }

        $data = new \stdClass();
        $data->id = $plan->id;
        $data->name = $plan->name;
        $data->description = $plan->description;

        $item = new self($user, $data);

        return $item;
    }

    /**
     * Get the context for the course item
     *
     * @return int The program context level.
     */
    public static function get_context_level() {
        return CONTEXT_SYSTEM;
    }

    /**
     * Maps data from the program properties to the item object
     *
     * @param \stdClass $data A course object
     */
    protected function map_learning_item_record_data(\stdClass $data) {
        $this->id = $data->id;
        $this->fullname = $data->name;
        $this->shortname = $data->name;
        $this->description = $data->description;
        $this->description_format = FORMAT_HTML; // Plan doesn't store a format so we use HTML.
        $this->url_view = new \moodle_url('/totara/plan/view.php', array('id' => $this->id));
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
     * If completion is enable for the site and course then
     * load the completion and progress info
     *
     * progress_canbecompleted is set the first time this is run
     * so if it is not null then we already have the data we need.
     */
    protected function ensure_completion_loaded() {

        if ($this->progress_canbecompleted === null) {

            if (!\completion_info::is_enabled_for_site()) {
                // Completion is disabled at the site level.
                $this->progress_canbecompleted = false;
                return;
            }

            // The user can complete this course.
            // TODO: We need a way to report progress for plans
            $this->progress_canbecompleted = true;
            $this->progress_percentage = 0;
            $this->progress_summary = '';
        }
    }

    /**
     * Get the courses assigned to the plan.
     *
     * @return \totara_plan\user_learning\course[] An array of course learning items
     */
    public function get_courses() {
        $this->ensure_plan_content_loaded();
        return $this->courses;
    }

    /**
     * Get the programs assigned to the plan.
     *
     * @return \totara_plan\user_learning\program[] An array of program learning items
     */
    public function get_programs() {
        $this->ensure_plan_content_loaded();
        return $this->programs;
    }

    /**
     * Ensure the plan record has been loaded and
     * if not then load it.
     *
     * @param bool $reset Reloads the plan if set to true.
     */
    public function ensure_plan_loaded($reset = false) {
        if ($this->plan === null || $reset) {
            $this->plan = new \development_plan($this->id);
        }
    }

    /**
     * Make sure that the plan content is loaded.
     *
     */
    public function ensure_plan_content_loaded() {
        global $DB;
        $this->ensure_plan_loaded();

        // Get the plan content.
        $content = $this->plan->get_assigned_items(DP_APPROVAL_APPROVED);

        // Courses.
        if ($this->courses === null) {
            // We only do this once.
            $this->courses = array();

            if (!empty($content['course'])) {
                $courseids = array();
                foreach ($content['course'] as $plancourse) {
                    $courseids[$plancourse->courseid] = $plancourse;
                }
                $courses = $DB->get_records_list('course', 'id', array_keys($courseids));
                foreach ($courses as $id => $course) {
                    $plancourse = $courseids[$id];
                    $item = course::one($this->user, $course);
                    $item->set_owner($this);
                    // Set the course due date by using the plan course due date.
                    $item->duedate = $plancourse->duedate;
                    $this->courses[] = $item;
                }
            }
        }

        // Programs.
        if ($this->programs === null) {
            // We only do this once.
            $this->programs = array();
            if (!empty($content['program'])) {
                foreach ($content['program'] as $planprogram) {
                    $item = program::one($this->user, $planprogram->programid);
                    if ($item) {
                        $this->programs[] = $item;
                    }
                }
            }
        }
    }


    /**
     * Checks completion is loaded and returns the percentage complete
     *
     * @return int The percentage complete
     */
    public function get_progress_percentage() {
        $this->ensure_completion_loaded();
        return $this->progress_percentage;
    }

    /**
     * Export progress information to display in template
     *
     * @return \stdClass Object containing progress info
     */
    public function export_progress_for_template() {
        $this->ensure_completion_loaded();

        $record = new \stdClass;
        $record->summary = $this->progress_summary;
        $record->percentage = $this->progress_percentage;
        return $record;
    }

    /**
     * Exports the data of this block as a context data object suitable for use with a template.
     */
    public function export_for_template() {

        $record = parent::export_for_template();

        return $record;
    }

    /**
     * Returns the component that owns this user learning instance.
     *
     * @return string
     */
    public function get_component() {
        return 'totara_plan';
    }

    /**
     * Returns the type of this user learning instance.
     *
     * @return string
     */
    public function get_type() {
        return 'plan';
    }
}

