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

use \totara_core\user_learning\designation_primary;
use \totara_core\user_learning\item_base;
use \totara_core\user_learning\item_has_progress;
use \totara_core\user_learning\item_has_dueinfo;
use \totara_program\user_learning\program_common;

global $CFG;
require_once($CFG->dirroot . '/totara/program/lib.php');

class item extends item_base implements item_has_progress, item_has_dueinfo {

    use designation_primary;
    use program_common;

    /**
     * @var \program
     */
    protected $certification;
    /**
     * @var courseset[]
     */
    protected $coursesets;

    /**
     * The users current path, one of CERTIFPATH_*.
     * @var int|null
     */
    protected $certification_path = null;

    /**
     * True if this item can be completed. Null until loaded only.
     * @var bool|null
     */
    protected $progress_canbecompleted = null;

    /**
     * The users progress as a percentage (0 - 100).
     * @var int
     */
    protected $progress_percentage;

    /**
     * A summary of the users progress.
     * @var string
     */
    protected $progress_summary;

    /**
     * Gets all program learning items for the given user.
     *
     * @param \stdClass|int $userorid A user object or user ID
     *
     * @return array An arrray of learning object of type item
     */
    public static function all($userorid) {
        // Check programs are enabled.
        if (totara_feature_disabled('certifications')) {
            return [];
        }
        $items = [];
        $user = self::resolve_user($userorid);
        $certifications = \prog_get_all_programs($user->id, '', '', '', false, false, false, true, true);
        foreach ($certifications as $certification) {
            $cert = new self($user, $certification);
            $items = array_merge($items, [$cert], $cert->get_courseset_courses());
        }
        return $items;
    }

    /**
     * Gets a single course learning item for a give user.
     *
     * @param \stdClass|int $userorid A user object of ID
     * @param \stdClass|int $itemorid A course object or ID
     *
     * @return item_base|false A learning item object for the program
     */
    public static function one($userorid, $itemorid) {
        // Check certifications are enabled.
        if (totara_feature_disabled('certifications')) {
            return false;
        }

        $user = self::resolve_user($userorid);

        if (is_object($itemorid)) {
            $itemid = $itemorid->id;
        } else {
            $itemid = $itemorid;
        }

        $program = new \program($itemid);

        // If this is not a cert then return false.
        if (!isset($program->certifid)) {
            return false;
        }

        if ($program->get_completion_data($userorid)) {
            $data = new \stdClass();
            $data->id = $program->id;
            $data->fullname = $program->fullname;
            $data->shortname = $program->shortname;
            $data->summary = $program->summary;

            $item = new self($user, $data);
            $item->get_courseset_courses();

            return $item;
        } else {
            return false;
        }
    }

    /**
     * Get the context for the course item
     *
     * @return integer The program context level.
     */
    public static function get_context_level() {
        return CONTEXT_PROGRAM;
    }

    /**
     * Maps data from the program properties to the item object
     *
     * @param \stdClass $data A course object
     */
    protected function map_learning_item_record_data(\stdClass $data) {
        $this->id = $data->id;
        $this->fullname = $data->fullname;
        $this->shortname = $data->shortname;
        $this->description = $data->summary;
        $this->description_format = FORMAT_HTML; // Certifications do not store a format we can use here.

        $course = $this->is_single_course();
        if ($course) {
            // Do audience visibility checks.
            $coursecontext = \context_course::instance($course->id);
            $canview = is_enrolled($coursecontext, $this->user->id) || totara_course_is_viewable($course->id, $this->user->id);
            if ($canview) {
                $this->url_view = new \moodle_url('/course/view.php', array('id' => $course->id));
            } else if (!empty($CFG->audiencevisibility) && $course->audiencevisible != COHORT_VISIBLE_NOUSERS) {
                $params = array('id' => $this->program->id, 'cid' => $course->id, 'userid' => $this->user->id, 'sesskey' => $USER->sesskey);
                $this->url_view = new \moodle_url('/totara/program/required.php', $params);
            } else {
                // This is a single course program but something isn't right... so show the normal program link.
                $this->url_view = new \moodle_url('/totara/program/view.php', array('id' => $this->id));
            }
        } else {
            $this->url_view = new \moodle_url('/totara/program/view.php', array('id' => $this->id));
        }
    }

    /**
     * Ensure the program record has been loaded and
     * if not load it.
     */
    protected function ensure_certification_loaded() {
        if ($this->certification === null) {
            $this->certification = new \program($this->id);
        }
        if ($this->certification_path === null) {
            $this->certification_path = \get_certification_path_user($this->certification->certifid, $this->user->id);
        }
    }

    /**
     * Ensure coursesets for the program have been loaded and if not then load them.
     */
    protected function ensure_course_sets_loaded() {
        if ($this->coursesets !== null) {
            return;
        }
        $this->ensure_certification_loaded();
        $this->coursesets = [];
        foreach ($this->certification->content->get_course_sets_path($this->certification_path) as $set) {
            /** @var \course_set $set */
            $courseset = courseset::from_course_set($this, $set, $this->user);
            $this->coursesets[] = $courseset;
        }
    }

    /**
     * If completion is enable for the site and course then load the completion and progress info
     *
     * progress_canbecompleted is set the first time this is run
     * so if it is not null then we already have the data we need.
     */
    public function ensure_completion_loaded() {
        global $OUTPUT;

        if ($this->progress_canbecompleted === null) {

            if (!\completion_info::is_enabled_for_site()) {
                // Completion is disabled at the site level.
                $this->progress_canbecompleted = false;
                return;
            }

            // The user can complete this program.
            $certificationprogress = round($this->certification->get_progress($this->user->id));

            $this->progress_canbecompleted = true;
            $this->progress_percentage = $certificationprogress;

            $pbar = new \static_progress_bar('', '0');
            $pbar->set_progress((int)$this->progress_percentage);
            $this->progress_pbar = $pbar->export_for_template($OUTPUT);
        }
    }

    /**
     * Export progress information to display in template
     *
     * @return \stdClass Object containing progress info
     */
    public function export_progress_for_template() {
        $this->ensure_completion_loaded();

        $record = new \stdClass;
        $record->summary = (string)$this->progress_summary;
        $record->pbar = $this->progress_pbar;
        return $record;
    }

    /**
     * Export due date data to display in template
     *
     * @return \stdClass|false Object containing due date info
     */
    public function export_dueinfo_for_template() {
        $this->ensure_duedate_loaded();

        if ($this->duedate < 0) {
            return false;
        }

        $now = time();

        $dueinfo = new \stdClass();

        // Date for tooltip.
        $duedateformat = get_string('strftimedatetimeon', 'langconfig');
        $duedateformattedtooltip = userdate($this->duedate, $duedateformat);

        $duedateformatted = userdate($this->duedate, get_string('strftimedateshorttotara', 'langconfig'));
        if ($now > $this->duedate) {
            // Overdue.
            $dueinfo->duetext = get_string('userlearningoverduesincex', 'totara_core', $duedateformatted); // Add this format to language pack?
            $dueinfo->tooltip = get_string('userlearningoverduesincextooltip', 'totara_core', $duedateformattedtooltip);
        } else {
            // Due.
            $dueinfo->duetext = get_string('userlearningdueonx', 'totara_core', $duedateformatted);
            $dueinfo->tooltip = get_string('certificationduex', 'totara_certification', $duedateformattedtooltip);
        }

        return $dueinfo;
    }

    /**
     * Returns true if this item has a due date.
     *
     * @return bool
     */
    public function item_has_duedate() {
        return true;
    }

    /**
     * Exports certification item data for the template
     *
     * @return \stdClass Object containing data about this item
     */
    public function export_for_template() {
        $this->ensure_course_sets_loaded();
        $record = parent::export_for_template();

        $coursesetinfo = $this->process_coursesets($this->coursesets);

        // Set string for coursesets header for completed and optional sets.
        $record->coursesets_header_text = $this->get_coursesets_header_text($coursesetinfo->completecount, $coursesetinfo->optionalcount);

        // Set string for unavailable sets.
        if ($coursesetinfo->unavailablecount == 1) {
            $record->unavailable_coursesets = get_string('unavailablecoursesets', 'block_current_learning', $coursesetinfo->unavailablecount);
        } else if ($coursesetinfo->unavailablecount > 1) {
            $record->unavailable_coursesets = get_string('unavailablecoursesetsplural', 'block_current_learning', $coursesetinfo->unavailablecount);
        }

        /** @var courseset $set */
        foreach ($coursesetinfo->sets as $set) {
            $record->coursesets[] = $set->export_for_template();
        }

        // Remove the next operator for the last courseset.
        if (isset($record->coursesets)) {
            unset(end($record->coursesets)->nextseticon);
            unset(end($record->coursesets)->nextsetoperator);
        }

        return $record;
    }

    /**
     * Find out if this is a single course certification.
     *
     * @return false|course If is a single course certification return the course
     */
    public function is_single_course() {
        $this->ensure_certification_loaded();

        return $this->certification->is_single_course($this->user->id);
    }

    /**
     * Returns the component that owns this user learning instance.
     *
     * @return string
     */
    public function get_component() {
        return 'totara_certification';
    }

    /**
     * Returns the type of this user learning instance.
     *
     * @return string
     */
    public function get_type() {
        return 'certification';
    }
}
