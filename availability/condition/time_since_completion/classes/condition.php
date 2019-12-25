<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
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
 * @author Simon Player <simon.player@totaralearning.com>
 * @package availability_time_since_completion
 */

namespace availability_time_since_completion;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/completionlib.php');

class condition extends \core_availability\condition {

    /**
     * Time periods.
     */
    const TIME_PERIOD_DAYS    = 3;
    const TIME_PERIOD_WEEKS   = 4;
    const TIME_PERIOD_YEARS   = 5;

    /** @var int ID of module that this depends on */
    protected $cmid;

    /** @var int Expected completion type (one of the COMPLETE_xx constants) */
    protected $expectedcompletion;

    /** @var int the time amount e.g. 10 */
    protected $timeamount;

    /** @var int the time period e.g. days */
    protected $timeperiod;

    /** @var array Array of modules used in these conditions for course */
    protected static $modsusedincondition = array();

    /** @var  object Completion data */
    protected $completiondata;

    /** @var array String mapping array for teh condition */
    private static $stringmapping = [
        0 => [
            COMPLETION_COMPLETE      => 'requires_complete',
            COMPLETION_COMPLETE_PASS => 'requires_complete_pass',
            COMPLETION_COMPLETE_FAIL => 'requires_complete_fail',
        ],
        1 => [
            COMPLETION_COMPLETE      => 'requires_incomplete',
            COMPLETION_COMPLETE_PASS => 'requires_incomplete',
            COMPLETION_COMPLETE_FAIL => 'requires_incomplete',
        ]
    ];

    /**
     * Constructor.
     *
     * @param \stdClass $structure Data structure from JSON decode
     * @throws \coding_exception If invalid data structure.
     */
    public function __construct(\stdClass $structure) {
        // Get cmid.
        if (isset($structure->cm) && is_number($structure->cm) && $structure->cm >= 0) {
            $this->cmid = (int)$structure->cm;
        } else {
            throw new \coding_exception('Missing or invalid ->cm for time since completion condition');
        }

        // Get expected completion.
        if (isset($structure->expectedcompletion) && in_array($structure->expectedcompletion,
                array(COMPLETION_COMPLETE, COMPLETION_INCOMPLETE,
                        COMPLETION_COMPLETE_PASS, COMPLETION_COMPLETE_FAIL))) {
            $this->expectedcompletion = $structure->expectedcompletion;
        } else {
            throw new \coding_exception('Missing or invalid ->expectedcompletion for time since completion condition');
        }

        // Get time amount.
        if (isset($structure->timeamount) && is_int($structure->timeamount) && $structure->timeamount > 0) {
            $this->timeamount = $structure->timeamount;
        } else {
            throw new \coding_exception('Missing or invalid ->timeamount for time since completion condition');
        }

        // Get time period.
        if (isset($structure->timeperiod) && in_array($structure->timeperiod,
                array(self::TIME_PERIOD_DAYS, self::TIME_PERIOD_WEEKS, self::TIME_PERIOD_YEARS))) {
            $this->timeperiod = $structure->timeperiod;
        } else {
            throw new \coding_exception('Missing or invalid ->timeperiod for time since completion condition');
        }
    }

    /**
     * Save the restriction
     *
     * @return \stdClass Details of the restriction
     */
    public function save() {
        return (object)array(
                'type'                => 'time_since_completion',
                'cm'                  => $this->cmid,
                'expectedcompletion'  => $this->expectedcompletion,
                'timeamount'          => $this->timeamount,
                'timeperiod'          => $this->timeperiod
        );
    }

    /**
     * Get the completion data
     *
     * @param \core_availability\info $info
     * @param $grabthelot
     * @param $userid
     * @return null|\stdClass
     */
    public function get_completiondata(\core_availability\info $info, $grabthelot, $userid) :?\stdClass {
        $modinfo = $info->get_modinfo();
        $completion = new \completion_info($modinfo->get_course());
        if (!array_key_exists($this->cmid, $modinfo->cms)) {
            return null;
        } else {
            $this->completiondata = $completion->get_data((object)array('id' => $this->cmid),
                $grabthelot, $userid, $modinfo);

            return $this->completiondata;
        }
    }

    /**
     * Determines if this condition allow the activity to be available
     *
     * @param bool $not Set true if we are inverting the condition
     * @param \core_availability\info $info Item we're checking
     * @param bool $grabthelot Performance hint: if true, caches information
     *   required for all course-modules, to make the front page and similar
     *   pages work more quickly (works only for current user)
     * @param int $userid User ID to check availability for
     * @return bool True if available
     */
    public function is_available($not, \core_availability\info $info, $grabthelot, $userid) :bool {
        $completiondata = $this->get_completiondata($info, $grabthelot, $userid);

        if (!$completiondata) {
            // No completion data set.
            $allow = false;
        } else if ($this->expectedcompletion != $completiondata->completionstate) {
            // The completion state does not match.
            $allow = false;
        } else {
            $allow = true;

            if (!$this->get_time_completed()) {
                // Not yet complete.
                $allow = false;
            } else {
                // Compare the dates.
                if ($this->time_since_completion_seconds() < $this->time_condition_seconds()) {
                    $allow = false;
                }
            }
        }

        if ($not) {
            $allow = !$allow;
        }

        return $allow;
    }

    /**
     * Get the number of seconds since completion.
     *
     * @return int
     */
    public function time_since_completion_seconds() :int {
        switch ($this->timeperiod) {
            case self::TIME_PERIOD_DAYS:
            case self::TIME_PERIOD_WEEKS:
            case self::TIME_PERIOD_YEARS:
                return usergetmidnight(time()) - usergetmidnight($this->get_time_completed());
        }
    }

    /**
     * Get the number of seconds that the time condition is based on.
     *
     * @return int
     */
    public function time_condition_seconds() :int {
        switch ($this->timeperiod) {
            case self::TIME_PERIOD_DAYS:
                return $this->timeamount * DAYSECS;
            case self::TIME_PERIOD_WEEKS:
                return $this->timeamount * WEEKSECS;
            case self::TIME_PERIOD_YEARS:
                return $this->timeamount * YEARSECS;
        }
    }

    /**
     * Get condition description
     *
     * @param bool $full Display full description or shortened version, not used
     * @param bool $not Should the condition be inverted
     * @param \core_availability\info $info
     *
     * @return string Text describing the conditions of restriction
     */
    public function get_description($full, $not, \core_availability\info $info) :string {
        // Get name for module.
        $modname = $this->get_modname($info);

        // Get the time element of the description.
        $desctime =  $this->get_description_time();

        // Get the completion element of the description.
        $desccompletion = get_string(self::$stringmapping[$not][$this->expectedcompletion], 'availability_time_since_completion', $modname);

        return $desctime . ' ' . $desccompletion;
    }

    /**
     * Get the time element of the description.
     *
     * @throws \coding_exception If unexpected completion state
     * @return string
     */
    public function get_description_time() :string {
        switch ($this->timeperiod) {
            case self::TIME_PERIOD_DAYS:
                $str = $this->timeamount == 1 ? 'timedescription_day' : 'timedescription_days';
                break;
            case self::TIME_PERIOD_WEEKS:
                $str = $this->timeamount == 1 ? 'timedescription_week' : 'timedescription_weeks';
                break;
            case self::TIME_PERIOD_YEARS:
                $str = $this->timeamount == 1 ? 'timedescription_year' : 'timedescription_years';
                break;
            default:
                throw new \coding_exception('Unexpected time period: ' . $this->timeperiod);
        }

        return get_string($str, 'availability_time_since_completion', $this->timeamount);
    }

    /**
     * Get the mod name
     *
     * @param \core_availability\info $info
     * @return string The mod name
     */
    protected function get_modname(\core_availability\info $info) :string {
        $modinfo = $info->get_modinfo();
        if (!array_key_exists($this->cmid, $modinfo->cms)) {
            $modname = get_string('missing', 'availability_time_since_completion');
        } else {
            $modname = '<AVAILABILITY_CMNAME_' . $modinfo->cms[$this->cmid]->id . '/>';
        }
        return $modname;
    }

    /**
     * Get time completed
     *
     * @return int|null
     */
    protected function get_time_completed() :?int {
        if (!empty($this->completiondata->timecompleted)) {
            return $this->completiondata->timecompleted;
        } else if (!empty($this->completiondata->timemodified)) {
            return $this->completiondata->timemodified;
        } else {
            return false;
        }
    }

    /**
     * Return debugging string
     *
     * @throws \coding_exception
     * @return string Debug text
     */
    protected function get_debug_string() :string {
        switch ($this->expectedcompletion) {
            case COMPLETION_COMPLETE :
                $type = 'COMPLETE';
                break;
            case COMPLETION_INCOMPLETE :
                $type = 'INCOMPLETE';
                break;
            case COMPLETION_COMPLETE_PASS:
                $type = 'COMPLETE_PASS';
                break;
            case COMPLETION_COMPLETE_FAIL:
                $type = 'COMPLETE_FAIL';
                break;
            default:
                throw new \coding_exception('Unexpected expected completion');
        }
        return 'cm' . $this->cmid . ' ' . $type;
    }

    /**
     * Called during restore
     *
     * @param string $restoreid Restore identifier
     * @param int $courseid Target course id
     * @param \base_logger $logger Logger for any warnings
     * @param string $name The condition name
     *
     * @return bool
     */
    public function update_after_restore($restoreid, $courseid, \base_logger $logger, $name) :bool {
        global $DB;
        $rec = \restore_dbops::get_backup_ids_record($restoreid, 'course_module', $this->cmid);
        if (!$rec || !$rec->newitemid) {
            // If we are on the same course (e.g. duplicate) then we can just
            // use the existing one.
            if ($DB->record_exists('course_modules',
                    array('id' => $this->cmid, 'course' => $courseid))) {
                return false;
            }
            // Otherwise it's a warning.
            $this->cmid = 0;
            $logger->process('Restored item (' . $name .
                    ') has availability condition on module that was not restored',
                    \backup::LOG_WARNING);
        } else {
            $this->cmid = (int)$rec->newitemid;
        }
        return true;
    }

    /**
     * Used in course/lib.php because we need to disable the completion JS if
     * a completion value affects a conditional activity.
     *
     * @param \stdClass $course Moodle course object
     * @param int $cmid Course-module id
     * @return bool True if this is used in a condition, false otherwise
     */
    public static function completion_value_used($course, $cmid) :bool {
        // Have we already worked out a list of required completion values
        // for this course? If so just use that.
        if (!array_key_exists($course->id, self::$modsusedincondition)) {

            // We don't have data for this course, build it.
            $modinfo = get_fast_modinfo($course);
            self::$modsusedincondition[$course->id] = array();

            // Activities.
            foreach ($modinfo->cms as $othercm) {
                if (is_null($othercm->availability)) {
                    continue;
                }
                $ci = new \core_availability\info_module($othercm);
                $tree = $ci->get_availability_tree();
                foreach ($tree->get_all_children('availability_time_since_completion\condition') as $cond) {
                    self::$modsusedincondition[$course->id][$cond->cmid] = true;
                }
            }

            // Sections.
            foreach ($modinfo->get_section_info_all() as $section) {
                if (is_null($section->availability)) {
                    continue;
                }
                $ci = new \core_availability\info_section($section);
                $tree = $ci->get_availability_tree();
                foreach ($tree->get_all_children('availability_time_since_completion\condition') as $cond) {
                    self::$modsusedincondition[$course->id][$cond->cmid] = true;
                }
            }
        }
        return array_key_exists($cmid, self::$modsusedincondition[$course->id]);
    }

    /**
     * Wipes the static cache of modules used in a condition (for unit testing).
     */
    public static function wipe_static_cache() {
        self::$modsusedincondition = array();
    }

    /**
     * Update dependency id required
     *
     * @param string $table Table name
     * @param int $oldid Previous ID
     * @param int $newid New ID
     * @return bool True if changed, otherwise false
     */
    public function update_dependency_id($table, $oldid, $newid) :bool {
        if ($table === 'course_modules' && (int)$this->cmid === (int)$oldid) {
            $this->cmid = $newid;
            return true;
        } else {
            return false;
        }
    }
}
