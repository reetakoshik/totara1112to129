<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * This file contains the course criteria type.
 *
 * @package core_completion
 * @category completion
 * @copyright 2009 Catalyst IT Ltd
 * @author Aaron Barnes <aaronb@catalyst.net.nz>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Course completion critieria - completion on course completion
 *
 * This course completion criteria depends on another course with
 * completion enabled to be marked as complete for this user
 *
 * @package core_completion
 * @category completion
 * @copyright 2009 Catalyst IT Ltd
 * @author Aaron Barnes <aaronb@catalyst.net.nz>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class completion_criteria_course extends completion_criteria {

    /* @var int Criteria type constant */
    public $criteriatype = COMPLETION_CRITERIA_TYPE_COURSE;

    /**
     * Criteria type form value
     * @var string
     */
    const FORM_MAPPING = 'courseinstance';

    /**
     * Finds and returns a data_object instance based on params.
     *
     * @param array $params associative arrays varname=>value
     * @return data_object instance of data_object or false if none found.
     */
    public static function fetch($params) {
        $params['criteriatype'] = COMPLETION_CRITERIA_TYPE_COURSE;
        return self::fetch_helper('course_completion_criteria', __CLASS__, $params);
    }

    /**
     * Add appropriate form elements to the critieria form
     *
     * Not used for this criteria, defined in course/completion_form.php
     *
     * @param moodle_form $mform Moodle forms object
     * @param stdClass $data data used to define default value of the form
     */
    public function config_form_display(&$mform, $data = null) {
        return;
    }

    /**
     * Update the criteria information stored in the database
     *
     * @param array $data Form data
     * @return  boolean
     */
    public function update_config($data) {
        // Get new criteria
        $name = str_replace('completion_', '', get_called_class());
        $formval = "{$name}_value";
        $formreset = "{$name}_none";

        // Fix select to match expected values for parent::update_config
        $cleaned = array();
        if (empty($data->$formreset) && !empty($data->$formval)) {
            foreach ($data->$formval as $v) {
                $cleaned[$v] = true;
            }
        }

        $data->$formval = $cleaned;

        return parent::update_config($data);
    }

    // TOTARA performance improvement - Static cache of courses to speed up loadtimes in review() & get_details().
    private static $courseinfocache = array();
    private static $courseinfocount = 0;

    public static function invalidatecache() {
        self::$courseinfocache = array();
        self::$courseinfocount = 0;
    }

    /**
     * Review this criteria and decide if the user has completed
     *
     * @param completion_criteria_completion $completion The user's completion record
     * @param bool $mark Optionally set false to not save changes to database
     * @return bool
     */
    public function review($completion, $mark = true) {
        global $DB;

        // TOTARA performance improvement - Get cached completion info.
        if (!isset(self::$courseinfocache[$this->courseinstance])) {
            if (self::$courseinfocount == MAXIMUM_CACHE_RECORDS) {
                self::invalidatecache();
            }

            $course = new stdClass();
            $course->id = $this->courseinstance;
            self::$courseinfocache[$this->courseinstance] = new completion_info($course);
            self::$courseinfocount++;
        }
        /** @var completion_info $info */
        $info = self::$courseinfocache[$this->courseinstance];

        // If the course is complete
        if ($info->is_course_complete($completion->userid)) {

            if ($mark) {
                // TOTARA - Use the completion time of the course as completion time for this criteria.
                // This may be done upstream if MDL-53532 is merged to moodle.
                $cc = new completion_completion(array('userid' => $completion->userid, 'course' => $info->course_id));
                $completion->mark_complete($cc->timecompleted);
            }

            return true;
        }

        return false;
    }

    /**
     * Return criteria title for display in reports
     *
     * @return string
     */
    public function get_title() {
        return get_string('dependenciescompleted', 'completion');
    }

    /**
     * Return a more detailed criteria title for display in reports
     *
     * @return string
     */
    public function get_title_detailed() {
        global $DB;

        $prereq = $DB->get_record('course', array('id' => $this->courseinstance));
        $coursecontext = context_course::instance($prereq->id, MUST_EXIST);
        $fullname = format_string($prereq->fullname, true, array('context' => $coursecontext));
        return shorten_text(urldecode($fullname));
    }

    /**
     * Return criteria type title for display in reports
     *
     * @return string
     */
    public function get_type_title() {
        return get_string('dependencies', 'completion');
    }

    /**
     * Return criteria progress details for display in reports
     *
     * @param completion_completion $completion The user's completion record
     * @return array An array with the following keys:
     *     type, criteria, requirement, status
     */
    public function get_details($completion) {
        global $CFG, $DB;

        // TOTARA performance improvement - Get cached completion info.
        if (!isset(self::$courseinfocache[$completion->course])) {
            if (self::$courseinfocount == MAXIMUM_CACHE_RECORDS) {
                self::invalidatecache();
            }

            $course = new stdClass();
            $course->id = $completion->course;
            self::$courseinfocache[$completion->course] = new completion_info($course);
            self::$courseinfocount++;
        }
        $info = self::$courseinfocache[$completion->course];

        $prereq = $DB->get_record('course', array('id' => $this->courseinstance));
        $coursecontext = context_course::instance($prereq->id, MUST_EXIST);
        $fullname = format_string($prereq->fullname, true, array('context' => $coursecontext));

        $prereq_info = new completion_info($prereq);

        $details = array();
        $details['type'] = $this->get_title();
        $details['criteria'] = '<a href="'.$CFG->wwwroot.'/course/view.php?id='.$this->courseinstance.'">'.s($fullname).'</a>';
        $details['requirement'] = get_string('coursecompleted', 'completion');
        $details['status'] = '<a href="'.$CFG->wwwroot.'/blocks/completionstatus/details.php?course='.$this->courseinstance.'&amp;user='.$completion->userid.'">'.get_string('seedetails', 'completion').'</a>';

        return $details;
    }

    /**
     * Mark users complete who have completed the required course.
     */
    public function cron() {
        global $DB;

        // Check to see if this criteria is in use.
        if (!$this->is_in_use()) {
            if (debugging()) {
                mtrace('... skipping as criteria not used');
            }
            return;
        }

        // Get all users who meet this criteria.
        $sql = '
            SELECT DISTINCT
                c.id AS course,
                cr.id AS criteriaid,
                cc.timecompleted,
                ue.userid AS userid
            FROM
                {user_enrolments} ue
            INNER JOIN
                {enrol} e
             ON e.id = ue.enrolid
            INNER JOIN
                {course} c
             ON e.courseid = c.id
            AND c.enablecompletion = 1
            INNER JOIN
                {course_completion_criteria} cr
             ON cr.course = c.id
            AND cr.criteriatype = '.COMPLETION_CRITERIA_TYPE_COURSE.'
            INNER JOIN
                {course_completions} cc
             ON cc.userid = ue.userid
            AND cc.course = cr.courseinstance
            AND cc.timecompleted > 0
            LEFT JOIN
                {course_completion_crit_compl} cccc
             ON cccc.criteriaid = cr.id
            AND cccc.userid = ue.userid
            WHERE
                cccc.id IS NULL
            AND ue.status = :userenrolstatus
            AND e.status = :instanceenrolstatus
            AND (ue.timeend > :timeendafter OR ue.timeend = 0)
        ';
        // Hint: ue, e, c and cr determine the users, courses they are in, and applicable completion criteria,
        //       cccc.id IS NULL checks if the user is already marked complete,
        //       cc.timecompleted > 0 is the condition requried for completion of the criteria.

        // Loop through completions, and mark as complete.
        $params = array(
            'userenrolstatus' => ENROL_USER_ACTIVE,
            'instanceenrolstatus' => ENROL_INSTANCE_ENABLED,
            'timeendafter' => time() // Excludes user enrolments that have ended already.
        );
        $rs = $DB->get_recordset_sql($sql, $params);
        foreach ($rs as $record) {
            $completion = new completion_criteria_completion((array) $record, DATA_OBJECT_FETCH_BY_KEY);
            $completion->mark_complete($record->timecompleted);
        }
        $rs->close();
    }
}
