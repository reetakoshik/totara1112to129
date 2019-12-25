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
 * Course completion status for a particular user/course
 *
 * @package core_completion
 * @category completion
 * @copyright 2009 Catalyst IT Ltd
 * @author Aaron Barnes <aaronb@catalyst.net.nz>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Course completion status constants
 *
 * For translating database recorded integers to strings and back
 */
define('COMPLETION_STATUS_NOTYETSTARTED',   10);
define('COMPLETION_STATUS_INPROGRESS',      25);
define('COMPLETION_STATUS_COMPLETE',        50);
define('COMPLETION_STATUS_COMPLETEVIARPL',  75);

global $COMPLETION_STATUS;
$COMPLETION_STATUS = array(
    COMPLETION_STATUS_NOTYETSTARTED => 'notyetstarted',
    COMPLETION_STATUS_INPROGRESS => 'inprogress',
    COMPLETION_STATUS_COMPLETE => 'complete',
    COMPLETION_STATUS_COMPLETEVIARPL => 'completeviarpl',
);


defined('MOODLE_INTERNAL') || die();
require_once("{$CFG->dirroot}/completion/data_object.php");
require_once("{$CFG->libdir}/completionlib.php");
require_once("{$CFG->dirroot}/blocks/totara_stats/locallib.php");
require_once("{$CFG->dirroot}/totara/plan/lib.php");

/**
 * Course completion status for a particular user/course
 *
 * @package core_completion
 * @category completion
 * @copyright 2009 Catalyst IT Ltd
 * @author Aaron Barnes <aaronb@catalyst.net.nz>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class completion_completion extends data_object {

    /* @var string $table Database table name that stores completion information */
    public $table = 'course_completions';

    /* @var array $required_fields Array of required table fields, must start with 'id'. */
    public $required_fields = array('id', 'userid', 'course', 'organisationid', 'positionid',
        'timeenrolled', 'timestarted', 'timecompleted', 'reaggregate', 'status', 'rpl', 'rplgrade', 'renewalstatus', 'invalidatecache');

    /* @var array $optional_fields Array of optional table fields */
    public $optional_fields = array('name' => '');


    /* @var int $userid User ID */
    public $userid;

    /* @var int $course Course ID */
    public $course;

    /* @var int $organisationid Origanisation ID user had when completed */
    public $organisationid;

    /* @var int $positionid Position ID user had when completed */
    public $positionid;


    /* @var int Time of course enrolment {@link completion_completion::mark_enrolled()} */
    public $timeenrolled;

    /* @var int Time the user started their course completion {@link completion_completion::mark_inprogress()} */
    public $timestarted;

    /* @var int Timestamp of course completion {@link completion_completion::mark_complete()} */
    public $timecompleted;

    /* @var int Flag to trigger cron aggregation (timestamp) */
    public $reaggregate;

    /* @var str Course name (optional) */
    public $name;

    /* @var int Completion status constant */
    public $status;

    /* @var string Record of prior learning, leave blank if none */
    public $rpl;

    /* @var string Grade for record of prior learning, leave blank if none */
    public $rplgrade;

    /**
     * A progressinfo object once initialised. Please call $this->get_progressinfo();
     * @since Totara 10
     * @var \totara_core\progressinfo\progressinfo | null
     */
    private $progressinfo = null;

    /**
     * The percentagecomplete for this user in this course.
     * @since Totara 10
     * @var null|float
     */
    private $percentagecomplete = null;

    /**
     * Finds and returns a data_object instance based on params.
     *
     * @param array $params associative arrays varname = >value
     * @return completion_completion|bool instance of self or false if none found.
     */
    public static function fetch($params) {
        $cache = cache::make('core', 'coursecompletion');

        // Totara: use the cache only if there are no extra parameters that would be restricting the result.
        if (count($params) === 2 and !empty($params['userid'] and !empty($params['course']))) {
            $key = $params['userid'] . '_' . $params['course'];
            $hit = $cache->get($key);
            if ($hit !== false) {
                if ($hit instanceof \completion_completion) {
                    return $hit;
                }
                debugging('Invalid data detected in coursecompletion cache', DEBUG_DEVELOPER);
            }
        }

        /** @var completion_completion|bool $tocache */
        $tocache = self::fetch_helper('course_completions', __CLASS__, $params);

        if ($tocache !== false) {
            $key = $tocache->userid . '_' . $tocache->course;
            $cache->set($key, $tocache);
        }

        return $tocache;
    }

    /**
     * Add support for serialising of this object in 'coursecompletion' MUC caches.
     * @return array
     */
    public function __sleep() {
        $properties = get_object_vars($this);
        // Remove caches, those will be reloaded separately.
        unset($properties['progressinfo']);
        unset($properties['percentagecomplete']);
        // Remove constant properties.
        unset($properties['table']);
        unset($properties['required_fields']);
        unset($properties['optional_fields']);
        unset($properties['text_fields']);
        unset($properties['unique_fields']);
        return array_keys($properties);
    }

    /**
     * Return user's status
     *
     * Uses the following properties to calculate:
     *  - $timeenrolled
     *  - $timestarted
     *  - $timecompleted
     *  - $rpl
     *
     * @static static
     *
     * @param   object  $completion  Object with at least the described columns
     * @return  str     Completion status lang string key
     */
    public static function get_status($completion) {
        // Check if a completion record was supplied
        if (!is_object($completion)) {
            throw new coding_exception('Incorrect data supplied to calculate Completion status');
        }

        // Check we have the required data, if not the user is probably not
        // participating in the course
        if (empty($completion->timeenrolled) &&
            empty($completion->timestarted) &&
            empty($completion->timecompleted))
        {
            return '';
        }

        // Check if complete
        if ($completion->timecompleted) {

            // Check for RPL
            if (isset($completion->rpl) && strlen($completion->rpl)) {
                return 'completeviarpl';
            }
            else {
                return 'complete';
            }
        }

        // Check if in progress
        elseif ($completion->timestarted) {
            return 'inprogress';
        }

        // Otherwise not yet started
        elseif ($completion->timeenrolled) {
            return 'notyetstarted';
        }

        // Otherwise they are not participating in this course
        else {
            return '';
        }
    }

    /**
     * Returns the progressinfo cache.
     * @since Totara 10
     * @return cache_application
     */
    private function get_progressinfo_cache() {
        return cache::make('totara_core', 'completion_progressinfo');
    }

    /**
     * Returns the string to use for progressinfo cache keys.
     * @since Totara 10
     * @return string
     */
    private function get_progressinfo_cache_key() {
        return "{$this->course}_{$this->userid}";
    }

    /**
     * Return progress information structure
     * @since Totara 10
     * @throws coding_exception If the aggregate function does not generate a progressinfo object.
     * @return \totara_core\progressinfo\progressinfo
     */
    public function get_progressinfo() {
        if ($this->progressinfo) {
            if ($this->progressinfo instanceof \totara_core\progressinfo\progressinfo) {
                return $this->progressinfo;
            }
            $this->progressinfo = null;
            debugging('Invalid progressinfo detected', DEBUG_DEVELOPER);
        }

        $cache = $this->get_progressinfo_cache();
        $key = $this->get_progressinfo_cache_key();

        $data = $cache->get($key);
        if ($data !== false) {
            if ($data instanceof \totara_core\progressinfo\progressinfo) {
                $this->progressinfo = $data;
                return $this->progressinfo;
            }
            debugging('Invalid data detected in progressinfo cache', DEBUG_DEVELOPER);
        }

        $this->aggregate();

        if (!($this->progressinfo instanceof \totara_core\progressinfo\progressinfo)) {
            // NOTE: this should not be necessary, but it is here to detect bugs in aggregate() function.
            $this->progressinfo = null;
            throw new coding_exception('Progressinfo object not correctly generated by the aggregate method.');
        }

        // NOTE: aggregate() above puts the new data into cache, no need to repeat it here.

        return $this->progressinfo;
    }

    /**
     * Marks the progressinfo cache stale for this entry
     * @since Totara 10
     */
    public function mark_progressinfo_stale() {
        $this->progressinfo = null;
        $this->percentagecomplete = null;
        $cache = $this->get_progressinfo_cache();
        $key = $this->get_progressinfo_cache_key();
        $cache->delete($key);
    }

    /**
     * Return percentage completed of the course
     * @since Totara 10
     * @return float
     */
    public function get_percentagecomplete() {
        if ($this->percentagecomplete === null) {
            $this->percentagecomplete = $this->get_progressinfo()->get_percentagecomplete();
        }
        return $this->percentagecomplete;
    }

    /**
     * Return status of this completion
     *
     * @return bool
     */
    public function is_complete() {
        return (bool) $this->timecompleted;
    }

    /**
     * Mark this user as started (or enrolled) in this course
     *
     * If the user is already marked as started, no change will occur
     *
     * @param integer $timeenrolled Time enrolled (optional)
     */
    public function mark_enrolled($timeenrolled = null) {
        global $DB;

        if ($this->timeenrolled === null) {

            if ($timeenrolled === null) {
                $timeenrolled = time();
            }

            $this->timeenrolled = $timeenrolled;
        }

        if (!$this->aggregate()) {
            return false;
        }

        $data = array();
        $data['userid'] = $this->userid;
        $data['eventtype'] = STATS_EVENT_COURSE_STARTED;
        $data['data2'] = $this->course;
        if (!$DB->record_exists('block_totara_stats', $data)) {
            totara_stats_add_event(time(), $this->userid, STATS_EVENT_COURSE_STARTED, '', $this->course);
        }
    }

    /**
     * Mark this user as inprogress in this course
     *
     * If the user is already marked as inprogress, the time will not be changed
     *
     * @param integer $timestarted Time started (optional)
     */
    public function mark_inprogress($timestarted = null) {
        global $DB;

        $timenow = time();
        $markinprogress = true;

        if (!$this->timestarted) {
            $markinprogress = false;
            if (!$timestarted) {
                $timestarted = $timenow;
            }
            $this->timestarted = $timestarted;
        }

        $wasenrolled = $this->timeenrolled;

        if (!$this->aggregate()) {
            return false;
        }

        if (!$markinprogress) {
            if (!$wasenrolled) {
                $data = array();
                $data['userid'] = $this->userid;
                $data['eventtype'] = STATS_EVENT_COURSE_STARTED;
                $data['data2'] = $this->course;
                if (!$DB->record_exists('block_totara_stats', $data)) {
                    totara_stats_add_event($timenow, $this->userid, STATS_EVENT_COURSE_STARTED, '', $this->course);
                }
            }

            // Trigger event to indicate that a user has been marked as in progress in a course.
            $context = context_course::instance($this->course);
            $data = array(
                'relateduserid' => $this->userid,
                'objectid' => $this->course,
                'context' => $context,
            );
            \core\event\course_in_progress::create($data)->trigger();
        }
    }

    /**
     * Mark this user complete in this course
     *
     * This generally happens when the required completion criteria
     * in the course are complete.
     *
     * @param integer $timecomplete Time completed (optional)
     * @return bool success
     */
    public function mark_complete($timecomplete = null) {
        global $USER, $CFG, $DB;

        // Never change a completion time.
        if ($this->timecompleted) {
            return;
        }

        // Use current time if nothing supplied.
        if (!$timecomplete) {
            $timecomplete = time();
        }

        // Set time complete.
        $this->timecompleted = $timecomplete;

        // Get user's positionid and organisationid if not already set
        if ($this->positionid === null) {
            $jobassignment = \totara_job\job_assignment::get_first($this->userid, false);
            if ($jobassignment) {
                $this->positionid = $jobassignment->positionid;
                $this->organisationid = $jobassignment->organisationid;
            } else {
                $this->positionid = 0;
                $this->organisationid = 0;
            }
        }

        // Save record.
        $result = $this->_save(true);
        if ($result) {
            $data = $this->get_record_data();
            \core\event\course_completed::create_from_completion($data)->trigger();

            $data = array();
            $data['userid'] = $this->userid;
            $data['eventtype'] = STATS_EVENT_COURSE_COMPLETE;
            $data['data2'] = $this->course;
            if (!$DB->record_exists('block_totara_stats', $data)) {
                totara_stats_add_event(time(), $this->userid, STATS_EVENT_COURSE_COMPLETE, '', $this->course);
            }

            //Auto plan completion hook
            dp_plan_item_updated($this->userid, 'course', $this->course);

            // Program completion hook.
            prog_update_completion($this->userid, null, $this->course);
        }

        return $result;
    }

    /**
     * Save course completion status
     *
     * This method creates a course_completions record if none exists
     * and also calculates the timeenrolled date if the record is being
     * created
     *
     * @param bool $purgeprogressinfo
     * @access  private
     * @return  bool
     */
    private function _save(bool $purgeprogressinfo = true) {
        global $DB;

        // Make sure timeenrolled is not null
        if (!$this->timeenrolled) {
            $this->timeenrolled = 0;
        }

        // Update status column
        $status = completion_completion::get_status($this);
        if ($status) {
            $status = constant('COMPLETION_STATUS_'.strtoupper($status));
        } else {
            $status = COMPLETION_STATUS_NOTYETSTARTED;
        }

        $this->status = $status;

        $result = false;
        // Save record
        if ($this->id) {
            $transaction = $DB->start_delegated_transaction();
            $result = $this->update();
            if ($result) {
                // Totara: we need to check if the record still exists, something might have deleted it in the meantime, we do not want to store wrong data in caches.
                if (!$DB->record_exists($this->table, ['id' => $this->id])) {
                    $result = false;
                    \core_completion\helper::log_course_completion($this->course, $this->userid, "Deleted completion update failed in completion_completion->_save");
                } else {
                    \core_completion\helper::log_course_completion($this->course, $this->userid, "Updated current completion in completion_completion->_save");
                }
            } else {
                \core_completion\helper::log_course_completion($this->course, $this->userid, "Current completion update failed in completion_completion->_save");
            }
            $transaction->allow_commit();
        } else {
            // Create new
            if (!$this->timeenrolled) {
                global $DB;

                // Get earliest current enrolment start date
                // This means timeend > now() and timestart < now()
                $sql = "
                    SELECT
                        ue.timestart
                    FROM
                        {user_enrolments} ue
                    JOIN
                        {enrol} e
                    ON (e.id = ue.enrolid AND e.courseid = :courseid)
                    WHERE
                        ue.userid = :userid
                    AND ue.status = :active
                    AND e.status = :enabled
                    AND (
                        ue.timeend = 0
                     OR ue.timeend > :now
                    )
                    AND ue.timestart < :now2
                    ORDER BY
                        ue.timestart ASC
                ";
                $params = array(
                    'enabled'   => ENROL_INSTANCE_ENABLED,
                    'active'    => ENROL_USER_ACTIVE,
                    'userid'    => $this->userid,
                    'courseid'  => $this->course,
                    'now'       => time(),
                    'now2'      => time()
                );

                if ($enrolments = $DB->get_record_sql($sql, $params, IGNORE_MULTIPLE)) {
                    $this->timeenrolled = $enrolments->timestart;
                }

                // If no timeenrolled could be found, use current time
                if (!$this->timeenrolled) {
                    $this->timeenrolled = time();
                }
            }

            // We should always be reaggregating when new course_completions
            // records are created as they might have already completed some
            // criteria before enrolling
            // This will also result in the progress fields being created and calculated.
            if (!$this->reaggregate) {
                $this->reaggregate = time();
            }

            // Make sure timestarted is not null
            if (!$this->timestarted) {
                $this->timestarted = 0;
            }

            $transaction = $DB->start_delegated_transaction();
            $result = $this->insert();
            if ($result) {
                \core_completion\helper::log_course_completion($this->course, $this->userid, "Created current completion in completion_completion->_save");
            } else {
                \core_completion\helper::log_course_completion($this->course, $this->userid, "Current completion creation failed in completion_completion->_save");
            }
            $transaction->allow_commit();
        }

        // Purge progress info from cache, but keep $this->progressinfo because it most likely already has the new info.
        if ($purgeprogressinfo) {
            $this->mark_progressinfo_stale();
        }

        // Update cache.
        $cache = cache::make('core', 'coursecompletion');
        if ($result) {
            $cache->set($this->userid . '_' . $this->course, $this);
        } else {
            $cache->delete($this->userid . '_' . $this->course);
        }

        return $result;
    }

    /**
     * Sets progress info for completed user.
     */
    private function set_completed_progressinfo() {
        // If completed add customdata with detail to be used when showing summary information
        $customdata = null;
        $format = get_string('strfdateshortmonth', 'langconfig');
        $a = array('timecompleted' => userdate($this->timecompleted, $format));

        if (!empty($this->rpl) && $this->status == COMPLETION_STATUS_COMPLETEVIARPL) {
            $a['rpl'] = $this->rpl;
            $customdata = array('completion' => get_string('completedviarpl-on', 'completion', $a));
        } else {
            $customdata = array('completion' => get_string('completed-on', 'completion', $a));
        }
        // Create a complete progressinfo, but don't worry about generating the full structure.
        // We don't need it at this point.
        $this->progressinfo = \totara_core\progressinfo\progressinfo::from_data(
            \totara_core\progressinfo\progressinfo::AGGREGATE_ALL,
            1,
            1,
            $customdata
        );
        // Ensure percentagecomplete is 100 if marked as completed
        $this->percentagecomplete = 100;
    }

    /**
     * Aggregate completion
     *
     * @return bool
     */
    public function aggregate() {
        global $DB;
        static $courses = array();

        // Don't use the cache when running tests.
        if (PHPUNIT_TEST) {
            $courses = array();
        }

        // Check if already complete.
        if ($this->timecompleted) {
            $result = $this->_save(true);
            $this->set_completed_progressinfo();
            // Cache the result only after successful save.
            $cache = $this->get_progressinfo_cache();
            $cachekey = $this->get_progressinfo_cache_key();
            if ($result) {
                $cache->set($cachekey, $this->progressinfo);
            } else {
                $cache->delete($cachekey);
            }
            return $result;
        }

        // Cached course completion enabled and aggregation method.
        if (!isset($courses[$this->course])) {
            $c = new stdClass();
            $c->id = $this->course;
            $info = new completion_info($c);
            $courses[$this->course] = new stdClass();
            $courses[$this->course]->enabled = $info->is_enabled();
            $courses[$this->course]->agg = $info->get_aggregation_method();

            // We do not want to re-read the completion criteria structure more than necessary
            // Therefore keeping the structure in the cache and filling it for each user
            // when needed (filled structures are stored in the instance's progressinfo attribute)
            $courses[$this->course]->progressinfobase = $info->get_progressinfo()->prepare_to_cache();
        }

        // Use fresh progress info.
        $this->progressinfo = \totara_core\progressinfo\progressinfo::wake_from_cache($courses[$this->course]->progressinfobase);
        $this->percentagecomplete = null;

        // No need to do this if completion is disabled.
        if (!$courses[$this->course]->enabled) {
            return false;
        }

        // Get user's completions.
        $sql = "
            SELECT
                cr.id AS criteriaid,
                cr.course,
                co.userid,
                cr.criteriatype,
                cr.moduleinstance,
                cr.courseinstance,
                cr.enrolperiod,
                cr.timeend,
                cr.gradepass,
                cr.role,
                co.id AS completionid,
                co.gradefinal,
                co.rpl,
                co.unenroled,
                co.timecompleted,
                a.method AS agg_method
            FROM
                {course_completion_criteria} cr
            LEFT JOIN
                {course_completion_crit_compl} co
             ON co.criteriaid = cr.id
            AND co.userid = :userid
            LEFT JOIN
                {course_completion_aggr_methd} a
             ON a.criteriatype = cr.criteriatype
            AND a.course = cr.course
            WHERE
                cr.course = :course
        ";

        $params = array(
            'userid' => $this->userid,
            'course' => $this->course
        );

        $completions = $DB->get_records_sql($sql, $params);

        // If no criteria, no need to aggregate.
        if (empty($completions)) {
            $result = $this->_save(false);
            // Cache the result only after successful save.
            $cache = $this->get_progressinfo_cache();
            $cachekey = $this->get_progressinfo_cache_key();
            if ($result) {
                $cache->set($cachekey, $this->progressinfo);
            } else {
                $cache->delete($cachekey);
            }
            return $result;
        }

        // Get aggregation methods.
        $agg_overall = $courses[$this->course]->agg;

        $overall_status = null;
        $activity_status = null;
        $prerequisite_status = null;
        $role_status = null;

        // Get latest timecompleted.
        $timecompleted = null;

        // Check each of the criteria.
        foreach ($completions as $completion) {
            $timecompleted = max($timecompleted, $completion->timecompleted);
            $iscomplete = (bool) $completion->timecompleted;

            // Handle aggregation special cases.
            switch ($completion->criteriatype) {
                case COMPLETION_CRITERIA_TYPE_ACTIVITY:
                    completion_status_aggregate($completion->agg_method, $iscomplete, $activity_status);
                    break;

                case COMPLETION_CRITERIA_TYPE_COURSE:
                    completion_status_aggregate($completion->agg_method, $iscomplete, $prerequisite_status);
                    break;

                case COMPLETION_CRITERIA_TYPE_ROLE:
                    completion_status_aggregate($completion->agg_method, $iscomplete, $role_status);
                    break;

                default:
                    completion_status_aggregate($agg_overall, $iscomplete, $overall_status);
            }
        }

        // Include role criteria aggregation in overall aggregation.
        if ($role_status !== null) {
            completion_status_aggregate($agg_overall, $role_status, $overall_status);
        }

        // Include activity criteria aggregation in overall aggregation.
        if ($activity_status !== null) {
            completion_status_aggregate($agg_overall, $activity_status, $overall_status);
        }

        // Include prerequisite criteria aggregation in overall aggregation.
        if ($prerequisite_status !== null) {
            completion_status_aggregate($agg_overall, $prerequisite_status, $overall_status);
        }

        // Aggregate the course progress
        $this->aggregate_progress($completions);

        // If overall aggregation status is true, mark course complete for user.
        if ($overall_status) {
            $result = $this->mark_complete($timecompleted);
            $this->set_completed_progressinfo();
        } else {
            $result = $this->_save(false);
        }

        // Cache the result only after successful save.
        $cache = $this->get_progressinfo_cache();
        $cachekey = $this->get_progressinfo_cache_key();
        if ($result) {
            $cache->set($cachekey, $this->progressinfo);
        } else {
            $cache->delete($cachekey);
        }

        return $result;
    }


    /**
     * Aggregate the user's progress on the course
     *
     * We aggregates 2 attributes - score and weight.
     * The final percentagecomplete can be obtained through (score / weight) on top level
     *
     * At the moment all lowest level criteria have a score of either 0 or 1
     * depending on completion. (In future progress we may take more factors into
     * consideration (e.g. for set of courses to be completed take the actual course
     * progress into consideration, etc.))
     *
     * @param array $completions Information on the activities that the user completed
     * @since Totara 10
     */
    private function aggregate_progress($completions) {

        // The progressinfo object for a course contains a hierarchy representing all the completion criteria
        // that must be met.
        // The root node contains the aggregated progress towards completion of this course for this user.
        // The following level contains nodes for each type of criteria that must be met.
        // Types that may have multiple criteria (e.g. activities, courses, roles) will have a child node
        // for each criteria of this type to be met.
        // The leaf nodes contain information on the user's progress towards completing the
        // actual criteria.
        // Higher level nodes contains the aggragated progress of all its children (e.g. the 'activity type' node
        // will contain the aggregation of all activities to be completed, etc.).
        // The root node contains the aggregated overall progress.
        // (see the PHPunit tests for some examples)

        $multi_activity_criteria = completion_info::get_multi_activity_criteria();
        foreach ($completions as $completion) {

            $cc = completion_criteria::factory(array('criteriatype' => $completion->criteriatype));

            if (!empty($completion->completionid)) {
                // Entry exists in course_completion_crit_compl for this course and user
                $params = array(
                    'id' => $completion->completionid,
                    'course' => $completion->course,
                    'userid' => $completion->userid,
                    'criteriaid' => $completion->criteriaid,
                    'gradefinal' => $completion->gradefinal,
                    'rpl' => $completion->rpl,
                    'unenroled' => $completion->unenroled,
                    'timecompleted' => $completion->timecompleted
                );

                // Not fetching row from the database - use values from $completions
                $crc = new completion_criteria_completion($params, false);
                $progress = $cc->get_progress($crc);
            } else {
                $progress = 0;
            }

            $key = $completion->criteriatype;
            if ($this->progressinfo->criteria_exist($key)) {
                $curnode = $this->progressinfo->get_criteria($key);
            } else {
                // Should have been initialized for the completion_info - but just in case.
                $curnode = $this->progressinfo->add_criteria($key, $completion->agg_method, $cc->get_weight());
            }

            if ($curnode && array_key_exists($completion->criteriatype, $multi_activity_criteria)) {
                // Must set score on lowest level

                // Again, this should have been initialized for the completion_info, but just making sure
                $key = $completion->{$multi_activity_criteria[$completion->criteriatype]};
                if ($curnode->criteria_exist($key)) {
                    $curnode = $curnode->get_criteria($key);
                } else {
                    $curnode = $curnode->add_criteria($key, $completion->agg_method, $cc->get_weight());
                }
            }

            if ($curnode) {
                $curnode->set_score($progress);
            }
        }

        // Now do the actual aggregation
        $this->progressinfo->aggregate_score_weight();
        $this->percentagecomplete = $this->progressinfo->get_percentagecomplete();
    }

    /**
     * Export summarized completion criteria information to display
     * in the progressbar popover
     *
     * @return array Completion summary
     */
    public function export_completion_criteria_for_template() {

        $progressinfo = $this->get_progressinfo();
        $criteria = !empty($progressinfo) ? $progressinfo->get_all_criteria() : array();

        $data = array(
            'hascoursecriteria' => false
        );

        if (empty($progressinfo)) {
            return $data;
        }

        $percent = $progressinfo->get_percentagecomplete();
        if (empty($criteria)) {
            $customdata = $progressinfo->get_customdata();
            if (!empty($customdata['completion'])) {
                $data['summary'] = $customdata['completion'];
            } else if ($percent == 100) {
                $data['summary'] = get_string('completed', 'completion');
            }
            return $data;
        }

        $data = array(
            'hascoursecriteria' => true,
            'criteria' => array(),
            'progress' => (int)$percent
        );

        $aggregate = $progressinfo->get_agg_method();
        if ($aggregate == COMPLETION_AGGREGATION_ALL) {
            $a = get_string('aggregateall', 'completion');
        } else {
            $a = get_string('aggregateany', 'completion');
        }
        $data['aggregation'] = get_string('tooltipcourseaggregate', 'completion', $a);

        foreach ($criteria as $key => $info) {
            switch ($key) {
                case COMPLETION_CRITERIA_TYPE_SELF:
                    $data['criteria'][] = get_string('tooltipcompletionself', 'completion');
                    break;

                case COMPLETION_CRITERIA_TYPE_DATE:
                    $customdata = $info->get_customdata();
                    $data['criteria'][] = get_string('tooltipcompletiondate', 'completion',
                        isset($customdata['date']) ? $customdata['date'] : '');
                    break;

                case COMPLETION_CRITERIA_TYPE_ACTIVITY:
                    $nactivity = $info->count_criteria();
                    if ($nactivity == 1 || $info->get_agg_method() == COMPLETION_AGGREGATION_ANY) {
                        $data['criteria'][] = get_string('tooltipcompletionactivityone', 'completion');
                    } else {
                        $data['criteria'][] = get_string('tooltipcompletionactivitymany', 'completion', $nactivity);
                    }
                    break;

                case COMPLETION_CRITERIA_TYPE_DURATION:
                    $customdata = $info->get_customdata();
                    $data['criteria'][] = get_string('tooltipcompletionduration', 'completion',
                        isset($customdata['duration']) ? $customdata['duration'] : '');
                    break;

                case COMPLETION_CRITERIA_TYPE_GRADE:
                    $customdata = $info->get_customdata();
                    if (!isset($customdata['grade']) || $customdata['grade'] == 0) {
                        $data['criteria'][] = get_string('tooltipcompletiongrade0', 'completion');
                    } else {
                        $data['criteria'][] = get_string('tooltipcompletiongrade', 'completion', $customdata['grade']);
                    }
                    break;

                case COMPLETION_CRITERIA_TYPE_ROLE:
                    $nrole = $info->count_criteria();
                    $customdata = $info->get_customdata();

                    if ($nrole == 1) {
                        $data['criteria'][] = get_string('tooltipcompletionroleone', 'completion',
                            isset($customdata['roles']) ? $customdata['roles'] : '');
                    } else {
                        $a = new \stdClass();
                        if ($info->get_agg_method() == COMPLETION_AGGREGATION_ALL) {
                            $a->aggregation = get_string('aggregateall', 'completion');
                        } else {
                            $a->aggregation = get_string('aggregateany', 'completion');
                        }

                        $a->roles = isset($customdata['roles']) ? $customdata['roles'] : '';
                        $data['criteria'][] = get_string('tooltipcompletionroleany', 'completion', $a);
                    }
                    break;

                case COMPLETION_CRITERIA_TYPE_COURSE:
                    $ncourse = $info->count_criteria();
                    if ($ncourse == 1 || $info->get_agg_method() == COMPLETION_AGGREGATION_ANY) {
                        $data['criteria'][] = get_string('tooltipcompletioncourseone', 'completion');
                    } else {
                        $data['criteria'][] = get_string('tooltipcompletioncoursemany', 'completion', $ncourse);
                    }
                    break;
            }
        }

        return $data;
    }

    /**
     * @param bool $deleted
     * @return void
     */
    public function notify_changed($deleted) {
        if ($deleted) {
            if (empty($this->userid) || empty($this->course)) {
                // If the userid or course is not being set then there is no point to proceed here
                return;
            }

            $key = $this->userid . "_" . $this->course;
            $cache = cache::make("core", "coursecompletion");
            $cache->delete($key);
        }
    }
}


/**
 * Aggregate criteria status's as per configured aggregation method
 *
 * @param int $method COMPLETION_AGGREGATION_* constant
 * @param bool $data Criteria completion status
 * @param bool|null $state Aggregation state
 */
function completion_status_aggregate($method, $data, &$state) {
    if ($method == COMPLETION_AGGREGATION_ALL) {
        if ($data && $state !== false) {
            $state = true;
        } else {
            $state = false;
        }
    } else if ($method == COMPLETION_AGGREGATION_ANY) {
        if ($data) {
            $state = true;
        } else if (!$data && $state === null) {
            $state = false;
        }
    }
}

/**
 * Triggered by changing course completion criteria, changing course settings and running cron.
 *
 * This function bulk creates course completion records.
 *
 * @param   integer     $courseid       Course ID default 0 indicates update all courses
 */
function completion_start_user_bulk($courseid = 0) {
    global $CFG, $DB, $USER;

    if (empty($CFG->enablecompletion)) {
        // Never create completion records if site completion is disabled.
        return;
    }

    if ($courseid) {
        $coursesql = "AND c.id = :courseid";
    } else {
        $coursesql = "";
    }

    $now = time();
    $nowstring = \core_completion\helper::format_log_date($now);

    /*
     * A quick explaination of this horrible looking query
     *
     * It's purpose is to locate all the active participants
     * of a course with course completion enabled, but without
     * a course_completions record.
     *
     * We want to record the user's enrolment start time for the
     * course. This gets tricky because there can be multiple
     * enrolment plugins active in a course, hence the fun
     * case statement.
     */
    $insertsql = "
        INSERT INTO
            {course_completions}
            (course, userid, timeenrolled, timestarted, reaggregate, status)
        SELECT
            c.id AS course,
            ue.userid AS userid,
            CASE
                WHEN MIN(ue.timestart) <> 0
                THEN MIN(ue.timestart)
                ELSE MIN(ue.timecreated)
            END,
            0,
            :reaggregate,
            :completionstatus
    ";
    $logdescriptiontimestart = $DB->sql_concat(
        "'Created current completion in completion_start_user_bulk<br><ul>'",
        "'<li>Status: Not yet started (" . COMPLETION_STATUS_NOTYETSTARTED . ")</li>'",
        "'<li>Time enrolled (from user enrolment timestart): '",
        $DB->sql_cast_2char("MIN(ue.timestart)"),
        "'</li>'",
        "'<li>Time started: Not set (0)</li>'",
        "'<li>Time completed: Not set (null)</li>'",
        "'<li>RPL: Empty(null)</li>'",
        "'<li>RPL grade: Empty (non-numeric)</li>'",
        "'<li>Reaggreagte: {$nowstring}</li>'",
        "'</ul>'"
    );
    $logdescriptiontimecreated = $DB->sql_concat(
        "'Created current completion in completion_start_user_bulk<br><ul>'",
        "'<li>Status: Not yet started (" . COMPLETION_STATUS_NOTYETSTARTED . ")</li>'",
        "'<li>Time enrolled (from user enrolment timecreated): '",
        $DB->sql_cast_2char("MIN(ue.timecreated)"),
        "'</li>'",
        "'<li>Time started: Not set (0)</li>'",
        "'<li>Time completed: Not set (null)</li>'",
        "'<li>RPL: Empty(null)</li>'",
        "'<li>RPL grade: Empty (non-numeric)</li>'",
        "'<li>Reaggreagte: {$nowstring}</li>'",
        "'</ul>'"
    );
    $logsql = "
        INSERT INTO
            {course_completion_log}
            (courseid, userid, changeuserid, description, timemodified)
        SELECT
            c.id AS courseid,
            ue.userid AS userid,
            :changeuserid,
            CASE
                WHEN MIN(ue.timestart) <> 0
                THEN {$logdescriptiontimestart}
                ELSE {$logdescriptiontimecreated}
            END,
            :reaggregate
    ";
    $basesql = "
        FROM
            {user_enrolments} ue
        INNER JOIN
            {enrol} e
         ON e.id = ue.enrolid
        INNER JOIN
            {course} c
         ON c.id = e.courseid
        LEFT JOIN
            {course_completions} crc
         ON crc.course = c.id
        AND crc.userid = ue.userid
        WHERE
            c.enablecompletion = 1
        AND crc.id IS NULL
        {$coursesql}
        AND ue.status = :userenrolstatus
        AND e.status = :instanceenrolstatus
        AND (ue.timeend > :timeendafter OR ue.timeend = 0)
        GROUP BY
            c.id,
            ue.userid
    ";

    $params = array(
        'changeuserid' => !empty($USER->id) ? $USER->id : 0,
        'reaggregate' => $now,
        'completionstatus' => COMPLETION_STATUS_NOTYETSTARTED,
        'userenrolstatus' => ENROL_USER_ACTIVE,
        'instanceenrolstatus' => ENROL_INSTANCE_ENABLED,
        'timeendafter' => $now // Excludes user enrolments that have ended already.
    );
    if ($courseid) {
        $params['courseid'] = $courseid;
    }

    $transaction = $DB->start_delegated_transaction();
    $DB->execute($logsql . $basesql, $params);
    $DB->execute($insertsql . $basesql, $params);
    $transaction->allow_commit();
}

