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
 * @author Nathan Lewis <nathan.lewis@totaralearning.com>
 * @package core_completion
 */

namespace core_completion;

defined('MOODLE_INTERNAL') || die();

/**
 * Class helper.
 *
 * @package core_completion
 */
final class helper {
    /**
     * Create or update course_completions record.
     *
     * Checks are performed to ensure that the data is valid before it can be written to the db. You cannot move a
     * current completion record from one user or course to another.
     *
     * @param \stdClass $coursecompletion A course_completions record to be saved, including 'id' if this is an update.
     *                                    Must contain at least 'userid' and 'course', even when updating using 'id'.
     * @param string $message If provided, will be added to the course completion log message.
     * @return int|bool the record id that was created or updated, or false if it failed due to validation problems
     */
    public static function write_course_completion($coursecompletion, $message = '') {
        global $DB;

        // Decide if this is an insert or update.
        $isinsert = empty($coursecompletion->id);

        // Ensure the record matches the database records.
        if ($isinsert) {
            // Checks that the course_completions doesn't already exist.
            $params = array(
                'course' => $coursecompletion->course,
                'userid' => $coursecompletion->userid
            );
            if ($DB->record_exists('course_completions', $params)) {
                throw new \coding_exception("Course_completions already exists");
            }

            if (empty($message)) {
                $message = "Current completion created";
            }
        } else {
            // Checks that the course_completions exists, is for the correct user and course.
            $sql = "SELECT cc.id
                      FROM {course_completions} cc
                     WHERE cc.id = :ccid
                       AND cc.course = :courseid
                       AND cc.userid = :userid";
            $params = array(
                'ccid' => $coursecompletion->id,
                'courseid' => $coursecompletion->course,
                'userid' => $coursecompletion->userid
            );
            if (!$DB->record_exists_sql($sql, $params)) {
                throw new \coding_exception("Either course_completions doesn't exist or belongs to a different user or course");
            }

            if (empty($message)) {
                $message = "Current completion updated";
            }
        }

        // Before applying the changes, verify that the new record is in a valid state.
        $errors = self::get_course_completion_errors($coursecompletion);

        if (empty($errors)) {
            $transaction = $DB->start_delegated_transaction();

            if ($isinsert) {
                $coursecompletion->id = $DB->insert_record('course_completions', $coursecompletion);
            } else {
                $DB->update_record('course_completions', $coursecompletion);
            }

            self::log_course_completion($coursecompletion->course, $coursecompletion->userid, $message);

            $transaction->allow_commit();

            // Update the cached record.
            $cache = \cache::make('core', 'coursecompletion');
            $key = $coursecompletion->userid . '_' . $coursecompletion->course;
            $cache->delete($key);

            // Mark progress caches stale
            self::mark_progress_caches_stale($coursecompletion->course, $coursecompletion->userid);

            return $coursecompletion->id;
        } else {
            // Some error was detected, and it wasn't specified in $ignoreproblemkey.
            self::save_completion_log($coursecompletion->course, $coursecompletion->userid,
                'An error occurred. Message of caller was:<br/>' . $message);
            return false;
        }
    }

    /**
     * Create or update course_completion_history record.
     *
     * Checks are performed to ensure that the data is valid before it can be written to the db. You cannot move a
     * history record from one user or course to another.
     *
     * @param \stdClass $historycompletion A course_completion_history record to be saved, including 'id' if this is an update.
     *                                     Must contain at least 'userid' and 'courseid', even when updating using 'id'.
     * @param string $message If provided, will be added to the course completion log message.
     * @return int the record id
     */
    public static function write_course_completion_history($historycompletion, $message = '') {
        global $DB;

        // Decide if this is an insert or update.
        $isinsert = empty($historycompletion->id);

        // Ensure the record matches the database records.
        if ($isinsert) {
            if (empty($message)) {
                $message = "History completion created";
            }

        } else {
            // Checks that the course_completion_history exists, is for the correct user and course.
            $params = array(
                'id' => $historycompletion->id,
                'courseid' => $historycompletion->courseid,
                'userid' => $historycompletion->userid
            );
            if (!$DB->record_exists('course_completion_history', $params)) {
                throw new \coding_exception("Either course_completion_history doesn't exist or belongs to a different user or course");
            }

            if (empty($message)) {
                $message = "History completion updated";
            }
        }

        $transaction = $DB->start_delegated_transaction();

        if ($isinsert) {
            $historycompletion->id = $DB->insert_record('course_completion_history', $historycompletion);
        } else {
            $DB->update_record('course_completion_history', $historycompletion);
            $historycompletion->id;
        }

        self::log_course_completion_history($historycompletion->id, $message);

        $transaction->allow_commit();

        return $historycompletion->id;
    }

    /**
     * Create or update course_completion_crit_compl record.
     *
     * Checks are performed to ensure that the data is valid before it can be written to the db. You cannot move a
     * crit_compl record from one user, course or criteria to another.
     *
     * @param \stdClass $critcompl A course_completion_crit_compl record to be saved, including 'id' if this is an update.
     *                             Must contain at least 'userid', 'course' and 'criteriaid', even when updating using 'id'.
     * @param string $message If provided, will be added to the course completion log message.
     * @param boolean $reaggregation Set true to schedule reaggregation
     * @return int the record id
     */
    public static function write_criteria_completion($critcompl, $message = '', $reaggregation = false) {
        global $DB;

        // Decide if this is an insert or update.
        $isinsert = empty($critcompl->id);

        // Ensure the record matches the database records.
        if ($isinsert) {
            // Checks that the course_completion_crit_compl doesn't already exist.
            $params = array(
                'course' => $critcompl->course,
                'userid' => $critcompl->userid,
                'criteriaid' => $critcompl->criteriaid
            );
            if ($DB->record_exists('course_completion_crit_compl',$params)) {
                throw new \coding_exception("Course_completion_crit_compl already exists");
            }

            if (empty($message)) {
                $message = "Criteria completion created";
            }

        } else {
            // Checks that the course_completion_crit_compl exists, is for the correct user and course.
            $params = array(
                'id' => $critcompl->id,
                'course' => $critcompl->course,
                'userid' => $critcompl->userid,
                'criteriaid' => $critcompl->criteriaid
            );
            if (!$DB->record_exists('course_completion_crit_compl', $params)) {
                throw new \coding_exception("Either course_completion_crit_compl doesn't exist or belongs to a different user, course or criteria");
            }

            if (empty($message)) {
                $message = "Criteria completion updated";
            }
        }

        $transaction = $DB->start_delegated_transaction();

        if ($isinsert) {
            $critcompl->id = $DB->insert_record('course_completion_crit_compl', $critcompl);
        } else {
            $DB->update_record('course_completion_crit_compl', $critcompl);
        }

        self::log_criteria_completion($critcompl->id, $message);

        if ($reaggregation) {
            self::schedule_reaggregation_of_completion($critcompl->course, $critcompl->userid);
        }

        $transaction->allow_commit();

        // Mark progress caches stale
        self::mark_progress_caches_stale($critcompl->course, $critcompl->userid);

        return $critcompl->id;
    }

    /**
     * Create or update course_modules_completion record.
     *
     * Checks are performed to ensure that the data is valid before it can be written to the db. You cannot move a
     * modules_completion record from one user or module to another.
     *
     * @param \stdClass $modulecompletion A course_modules_completion record to be saved, including 'id' if this is an update.
     *                                    Must contain at least 'userid', 'coursemoduleid', 'completionstate' and
     *                                    'timemodified', even when updating using 'id'.
     * @param string $message If provided, will be added to the course completion log message.
     * @return int the record id
     */
    public static function write_module_completion($modulecompletion, $message = '') {
        global $DB;

        // Decide if this is an insert or update.
        $isinsert = empty($modulecompletion->id);

        // Ensure the record matches the database records.
        if ($isinsert) {
            // Checks that the course_completions doesn't already exist.
            $params = array(
                'coursemoduleid' => $modulecompletion->coursemoduleid,
                'userid' => $modulecompletion->userid
            );
            if ($DB->record_exists('course_modules_completion', $params)) {
                throw new \coding_exception("Course_modules_completion already exists");
            }

            if (empty($message)) {
                $message = "Module completion created";
            }

        } else {
            // Checks that the course_modules_completion exists, is for the correct user and course module.
            $params = array(
                'id' => $modulecompletion->id,
                'coursemoduleid' => $modulecompletion->coursemoduleid,
                'userid' => $modulecompletion->userid
            );
            if (!$DB->record_exists('course_modules_completion', $params)) {
                throw new \coding_exception("Either course_modules_completion doesn't exist or belongs to a different user or module");
            }

            if (empty($message)) {
                $message = "Module completion updated";
            }
        }

        $transaction = $DB->start_delegated_transaction();

        if ($isinsert) {
            $modulecompletion->id = $DB->insert_record('course_modules_completion', $modulecompletion);
        } else {
            $DB->update_record('course_modules_completion', $modulecompletion);
        }

        self::log_course_module_completion($modulecompletion->id, $message);

        $transaction->allow_commit();

        // Mark progress caches stale
        \totara_program\progress\program_progress_cache::mark_user_cache_stale($modulecompletion->userid);

        return $modulecompletion->id;
    }

    /**
     * Create a record suitable for saving to the course completion log.
     *
     * @param int $courseid ID of the course.
     * @param int $userid ID of the user who's record is being affected, or null if it affects the whole course.
     * @param string $description  Describing what happened, including details. Can include simple html formatting.
     * @param int|null $changeuserid ID of the user who triggered the event, or 0 to indicate cron or no user, assumes $USER->id if null.
     * @return \stdClass
     */
    public static function make_log_record($courseid, $userid, $description, $changeuserid = null) {
        global $USER;

        if (is_null($changeuserid)) {
            $changeuserid = $USER->id;
        }

        $record = new \stdClass();
        $record->courseid = $courseid;
        $record->userid = $userid;
        $record->changeuserid = $changeuserid;
        $record->description = $description;
        $record->timemodified = time();

        return $record;
    }

    /**
     * Write a record to the course completion log.
     *
     * @param int $courseid ID of the course.
     * @param int $userid ID of the user who's record is being affected, or null if it affects the whole course.
     * @param string $description  Describing what happened, including details. Can include simple html formatting.
     * @param int|null $changeuserid ID of the user who triggered the event, or 0 to indicate cron or no user, assumes $USER->id if null.
     */
    public static function save_completion_log($courseid, $userid, $description, $changeuserid = null) {
        global $DB;

        $record = self::make_log_record($courseid, $userid, $description, $changeuserid);

        $DB->insert_record('course_completion_log', $record);
    }

    /**
     * Write a log message (in the course completion log) when a course completion has been added or edited.
     *
     * @param int $courseid
     * @param int $userid
     * @param string $message If provided, will be added at the start of the log message (instead of "Current completion record logged")
     * @param int|null $changeuserid ID of the user who triggered the event, or 0 to indicate cron or no user, assumes $USER->id if null.
     */
    public static function log_course_completion($courseid, $userid, $message = '', $changeuserid = null) {
        global $DB;

        $coursecompletion = $DB->get_record('course_completions',
            array('course' => $courseid, 'userid' => $userid), '*', MUST_EXIST);

        $description = self::get_course_completion_log_description($coursecompletion, $message);

        self::save_completion_log(
            $courseid,
            $userid,
            $description,
            $changeuserid
        );
    }

    /**
     * Calculate the description string for a course completion log message.
     *
     * @param \stdClass $coursecompletion
     * @param string $message If provided, will be added at the start of the log message (instead of "Current completion record logged")
     * @return string
     */
    public static function get_course_completion_log_description($coursecompletion, $message = '') {

        switch ($coursecompletion->status) {
            case COMPLETION_STATUS_NOTYETSTARTED:
                $status = 'Not yet started';
                break;
            case COMPLETION_STATUS_INPROGRESS:
                $status = 'In progress';
                break;
            case COMPLETION_STATUS_COMPLETE:
                $status = 'Complete';
                break;
            case COMPLETION_STATUS_COMPLETEVIARPL:
                $status = 'Complete via rpl';
                break;
            default:
                $status = 'Unknown status';
        }
        $status .= " ({$coursecompletion->status})";

        if (is_null($coursecompletion->rpl)) {
            $rpl = "Empty (null)";
        } else if ($coursecompletion->rpl === '') {
            $rpl = "Empty ('')";
        } else {
            $rpl = $coursecompletion->rpl;
        }
        if (is_numeric($coursecompletion->rplgrade)) {
            $rplgrade = (float)$coursecompletion->rplgrade;
        } else {
            $rplgrade = "Empty (non-numeric)";
        }

        if (empty($message)) {
            $message = 'Current completion record logged';
        }

        $description = $message . '<br/>' .
            '<ul><li>Status: ' . $status . '</li>' .
            '<li>Time enrolled: ' . self::format_log_date($coursecompletion->timeenrolled) . '</li>' .
            '<li>Time started: ' . self::format_log_date($coursecompletion->timestarted) . '</li>' .
            '<li>Time completed: ' . self::format_log_date($coursecompletion->timecompleted) . '</li>' .
            '<li>RPL: ' . $rpl . '</li>' .
            '<li>RPL grade: ' . $rplgrade . '</li>' .
            '<li>Reaggregate: ' . self::format_log_date($coursecompletion->reaggregate) . '</li></ul>';

        return $description;
    }

    /**
     * Write a log message (in the course completion log) when a course completion history has been added or edited.
     *
     * @param int $chid
     * @param string $message If provided, will be added at the start of the log message (instead of "Course completion history logged")
     * @param int|null $changeuserid ID of the user who triggered the event, or 0 to indicate cron or no user, assumes $USER->id if null.
     */
    public static function log_course_completion_history($chid, $message = '', $changeuserid = null) {
        $historycompletion = self::load_course_completion_history($chid);

        $description = self::get_course_completion_history_log_description($historycompletion, $message);

        self::save_completion_log(
            $historycompletion->courseid,
            $historycompletion->userid,
            $description,
            $changeuserid
        );
    }

    /**
     * Write a log message (in the course completion log) when a reaggregation flag has been set.
     *
     * @param int $courseid
     * @param int $userid
     * @param string $message
     * @param int|null $changeuserid
     */
    public static function log_course_reaggregation($courseid, $userid, $message = '', $changeuserid = null) {
        global $DB;
        if (empty($message)) {
            $message = 'Course completion reaggregation logged';
        }
        $reaggregate = $DB->get_field('course_completions', 'reaggregate', array('course' => $courseid, 'userid' => $userid));
        if (!empty($reaggregate)) {
            $description = $message . '<br/>' .
                '<ul><li>Reaggregate: ' . self::format_log_date($reaggregate) . '</li></ul>';
            self::save_completion_log($courseid, $userid, $description, $changeuserid);
        }
    }

    /**
     * Calculate the description string for a course completion history log message.
     *
     * @param \stdClass $historycompletion
     * @param string $message If provided, will be added at the start of the log message (instead of "Course completion history logged")
     * @return string
     */
    public static function get_course_completion_history_log_description($historycompletion, $message = '') {
        if (is_numeric($historycompletion->grade)) {
            $grade = (float)$historycompletion->grade;
        } else {
            $grade = "Empty (non-numeric)";
        }

        if (empty($message)) {
            $message = 'Course completion history logged';
        }

        if (!empty($historycompletion->id)) {
            $cchid = '<li>CCHID: ' . $historycompletion->id . '</li>';
        } else {
            $cchid = '';
        }

        $description = $message . '<br/><ul>' .
            $cchid .
            '<li>Time completed: ' . self::format_log_date($historycompletion->timecompleted) . '</li>' .
            '<li>Grade: ' . $grade . '</li></ul>';

        return $description;
    }

    /**
     * Write a log message (in the course completion log) when a course completion crit compl has been added or edited.
     *
     * @param int $ccccid
     * @param string $message If provided, will be added at the start of the log message (instead of "Course completion criteria logged")
     * @param int|null $changeuserid ID of the user who triggered the event, or 0 to indicate cron or no user, assumes $USER->id if null.
     */
    public static function log_criteria_completion($ccccid, $message = '', $changeuserid = null) {
        global $DB;

        $critcompl = $DB->get_record('course_completion_crit_compl',
            array('id' => $ccccid), '*', MUST_EXIST);

        if (is_null($critcompl->gradefinal)) {
            $gradefinal = "Empty (null)";
        } else if ($critcompl->gradefinal === '') {
            $gradefinal = "Empty ('')";
        } else {
            $gradefinal = $critcompl->gradefinal;
        }

        if (is_null($critcompl->unenroled)) {
            $unenroled = "Empty (null)";
        } else if ($critcompl->unenroled === '') {
            $unenroled = "Empty ('')";
        } else {
            $unenroled = $critcompl->unenroled;
        }

        if (is_null($critcompl->rpl)) {
            $rpl = "Empty (null)";
        } else if ($critcompl->rpl === '') {
            $rpl = "Empty ('')";
        } else {
            $rpl = $critcompl->rpl;
        }

        if (empty($message)) {
            $message = 'Crit compl logged';
        }

        $description = $message . '<br/>' .
            '<ul><li>CCCCID: ' . $critcompl->id . '</li>' .
            '<li>Criteria ID: ' . $critcompl->criteriaid . '</li>' .
            '<li>Grade final: ' . $gradefinal . '</li>' .
            '<li>Unenroled: ' . $unenroled . '</li>' .
            '<li>RPL: ' . $rpl . '</li>' .
            '<li>Time completed: ' . self::format_log_date($critcompl->timecompleted) . '</li></ul>';

        self::save_completion_log(
            $critcompl->course,
            $critcompl->userid,
            $description,
            $changeuserid
        );
    }

    /**
     * Write a log message (in the course completion log) when a course modules completion has been added or edited.
     *
     * @param int $cmcid
     * @param string $message If provided, will be added at the start of the log message (instead of "Course modules completion logged")
     * @param int|null $changeuserid ID of the user who triggered the event, or 0 to indicate cron or no user, assumes $USER->id if null.
     */
    public static function log_course_module_completion($cmcid, $message = '', $changeuserid = null) {
        global $DB;

        $modulescompletion = $DB->get_record('course_modules_completion',
            array('id' => $cmcid), '*', MUST_EXIST);

        if (empty($message)) {
            $message = 'Module completion logged';
        }

        switch ($modulescompletion->completionstate) {
            case COMPLETION_INCOMPLETE:
                $completionstate = "Not complete";
                break;
            case COMPLETION_COMPLETE:
                $completionstate = "Complete";
                break;
            case COMPLETION_COMPLETE_PASS:
                $completionstate = "Complete with passing grade";
                break;
            case COMPLETION_COMPLETE_FAIL:
                $completionstate = "Complete with failing grade";
                break;
            default:
                $completionstate = "Unknown completion state";
        }
        $completionstate .= " ($modulescompletion->completionstate)";

        $viewed = $modulescompletion->viewed ? "Yes" : "No";
        $viewed .= " ($modulescompletion->viewed)";

        $description = $message . '<br/>' .
            '<ul><li>CMCID: ' . $modulescompletion->id . '</li>' .
            '<li>Completion state: ' . $completionstate . '</li>' .
            '<li>Viewed: ' . $viewed . '</li>' .
            '<li>Time modified: ' . self::format_log_date($modulescompletion->timemodified) . '</li>' .
            '<li>Time completed: ' . self::format_log_date($modulescompletion->timecompleted) . '</li>' .
            '<li>Reaggregate: ' . self::format_log_date($modulescompletion->reaggregate) . '</li></ul>';

        $courseid = $DB->get_field('course_modules', 'course',
            array('id' => $modulescompletion->coursemoduleid), MUST_EXIST);

        self::save_completion_log(
            $courseid,
            $modulescompletion->userid,
            $description,
            $changeuserid
        );
    }

    /**
     * Load a course_completions record out of the db.
     *
     * Use this function because using the API is the right way to do it!
     *
     * @param int $courseid
     * @param int $userid
     * @param bool $mustexist If records are missing, default true causes an error, false returns false
     * @return mixed
     */
    public static function load_course_completion($courseid, $userid, $mustexist = true) {
        global $DB;

        $coursecompletion = $DB->get_record('course_completions', array('course' => $courseid, 'userid' => $userid));

        if (empty($coursecompletion)) {
            if ($mustexist) {
                throw new \coding_exception("Tried to load a course_completions but it does not exist");
            } else {
                return false;
            }
        }

        return $coursecompletion;
    }

    /**
     * Load a course_completion_history record out of the db.
     *
     * @param int $cchid
     * @param bool $mustexist If records are missing, default true causes an error, false returns false
     * @return mixed
     */
    public static function load_course_completion_history($cchid, $mustexist = true) {
        global $DB;

        $historycompletion = $DB->get_record('course_completion_history', array('id' => $cchid));

        if (empty($historycompletion)) {
            if ($mustexist) {
                throw new \coding_exception("Tried to load a course_completion_history but it does not exist");
            } else {
                return false;
            }
        }

        return $historycompletion;
    }

    /**
     * Delete a course completion record, logging it in the course completion log.
     *
     * @param int $courseid
     * @param int $userid
     * @param string $message If provided, will override the default completion log message.
     */
    public static function delete_course_completion($courseid, $userid, $message = '') {
        global $DB;

        if (empty($message)) {
            $message = 'Current completion deleted';
        }

        $transaction = $DB->start_delegated_transaction();

        $DB->delete_records('course_completions', array('course' => $courseid, 'userid' => $userid));

        // Record the change in the course completion log.
        self::save_completion_log(
            $courseid,
            $userid,
            $message
        );

        $transaction->allow_commit();
    }

    /**
     * Delete a course completion history record, logging it in the course completion log.
     *
     * @param int $chid
     * @param string $message If provided, will override the default completion log message.
     */
    public static function delete_course_completion_history($chid, $message = '') {
        global $DB;

        $sql = "SELECT cch.userid, cch.courseid
                  FROM {course_completion_history} cch
                 WHERE cch.id = :chid";
        $info = $DB->get_record_sql($sql, array('chid' => $chid), MUST_EXIST);

        if (empty($message)) {
            $message = 'History deleted';
        }

        $description = $message . '<br/>' .
            '<ul><li>CCHID: ' . $chid . '</li></ul>';

        $transaction = $DB->start_delegated_transaction();

        $DB->delete_records('course_completion_history', array('id' => $chid));

        // Record the change in the course completion log.
        self::save_completion_log(
            $info->courseid,
            $info->userid,
            $description
        );

        $transaction->allow_commit();
    }

    /**
     * Delete a course_completion_crit_compl record, logging it in the course completion log.
     *
     * @param int $ccid
     * @param string $message If provided, will override the default completion log message.
     */
    public static function delete_criteria_completion($ccid, $message = '') {
        global $DB;

        $info = $DB->get_record('course_completion_crit_compl',
            array('id' => $ccid), '*', MUST_EXIST);

        if (empty($message)) {
            $message = 'Crit compl deleted';
        }

        $description = $message . '<br/>' .
            '<ul><li>CCCCID: ' . $ccid . '</li></ul>';

        $transaction = $DB->start_delegated_transaction();

        $DB->delete_records('course_completion_crit_compl', array('id' => $ccid));

        // Record the change in the course completion log.
        self::save_completion_log(
            $info->course,
            $info->userid,
            $description
        );

        $transaction->allow_commit();
    }

    /**
     * Delete a course_modules_completion record, logging it in the course completion log.
     *
     * @param int $cmcid
     * @param string $message If provided, will override the default completion log message.
     */
    public static function delete_module_completion($cmcid, $message = '') {
        global $DB;

        $sql = "SELECT cmc.userid, cm.course
                  FROM {course_modules_completion} cmc
                  JOIN {course_modules} cm ON cm.id = cmc.coursemoduleid
                 WHERE cmc.id = :cmcid";
        $info = $DB->get_record_sql($sql, array('cmcid' => $cmcid), MUST_EXIST);

        if (empty($message)) {
            $message = 'Module completion deleted';
        }

        $description = $message . '<br/>' .
            '<ul><li>CMCID: ' . $cmcid . '</li></ul>';

        $transaction = $DB->start_delegated_transaction();

        $DB->delete_records('course_modules_completion', array('id' => $cmcid));

        // Record the change in the course completion log.
        self::save_completion_log(
            $info->course,
            $info->userid,
            $description
        );

        $transaction->allow_commit();
    }

    /**
     * Checks the state of a user's course completion record.
     *
     * When an inconsistent state is detected, this function assumes that the status is correct, and reports
     * problems with other fields relative to this. It is possible that the problem (or solution to the
     * problem) is that the status is incorrect, and the other fields are correct, but it's not possible to
     * distinguish between the two scenarios.
     *
     * @param \stdClass $coursecompletion as stored in the course_completions table (not all fields are required)
     * @return array describes any problems (error key => form field)
     */
    public static function get_course_completion_errors(\stdClass $coursecompletion) {
        $errors = array();

        switch ($coursecompletion->status) {
            case COMPLETION_STATUS_NOTYETSTARTED:
                if (!empty($coursecompletion->timecompleted)) {
                    $errors['error:coursestatusnotyetstarted-timecompletednotempty'] = 'timecompleted';
                }
                if (!empty($coursecompletion->rpl)) {
                    $errors['error:coursestatusnotyetstarted-rplnotempty'] = 'rpl';
                }
                if (!empty($coursecompletion->rplgrade)) {
                    $errors['error:coursestatusnotyetstarted-rplgradenotempty'] = 'rplgrade';
                }
                break;
            case COMPLETION_STATUS_INPROGRESS:
                if (!empty($coursecompletion->timecompleted)) {
                    $errors['error:coursestatusinprogress-timecompletednotempty'] = 'timecompleted';
                }
                if (!empty($coursecompletion->rpl)) {
                    $errors['error:coursestatusinprogress-rplnotempty'] = 'rpl';
                }
                if (!empty($coursecompletion->rplgrade)) {
                    $errors['error:coursestatusinprogress-rplgradenotempty'] = 'rplgrade';
                }
                break;
            case COMPLETION_STATUS_COMPLETE:
                if (empty($coursecompletion->timecompleted)) {
                    $errors['error:coursestatuscomplete-timecompletedempty'] = 'timecompleted';
                }
                if (!empty($coursecompletion->rpl)) {
                    $errors['error:coursestatuscomplete-rplnotempty'] = 'rpl';
                }
                if (!empty($coursecompletion->rplgrade)) {
                    $errors['error:coursestatuscomplete-rplgradenotempty'] = 'rplgrade';
                }
                break;
            case COMPLETION_STATUS_COMPLETEVIARPL:
                if (empty($coursecompletion->timecompleted)) {
                    $errors['error:coursestatusrplcomplete-timecompletedempty'] = 'timecompleted';
                }
                if (is_null($coursecompletion->rpl) || $coursecompletion->rpl == '') {
                    $errors['error:coursestatusrplcomplete-rplempty'] = 'rpl';
                }
                break;
            default:
                $errors['error:stateinvalid'] = 'status';
                break;
        }

        return $errors;
    }

    /**
     * Checks the state of a user's module completion record.
     *
     * When an inconsistent state is detected, this function assumes that the status is correct, and reports
     * problems with other fields relative to this. It is possible that the problem (or solution to the
     * problem) is that the status is incorrect, and the other fields are correct, but it's not possible to
     * distinguish between the two scenarios.
     *
     * @param \stdClass $cmc as stored in the course_moduels_completion table (not all fields are required)
     * @return array describes any problems (error key => form field)
     */
    public static function get_module_completion_errors($cmc) {
        $errors = array();

        $usestimecompleted = self::module_uses_timecompleted($cmc->coursemoduleid);

        switch ($cmc->completionstate) {
            case COMPLETION_INCOMPLETE:
                // Test is not restricted to only those modules which are supposed to use timecompleted.
                if (!empty($cmc->timecompleted)) {
                    $errors['error:modulestatusincomplete-timecompletednotempty'] = 'cmctimecompleted';
                }
                break;
            case COMPLETION_COMPLETE:
                if ($usestimecompleted) {
                    if (empty($cmc->timecompleted)) {
                        $errors['error:modulestatuscomplete-timecompletedempty'] = 'cmctimecompleted';
                    }
                } else {
                    if (empty($cmc->timemodified) && empty($cmc->timecompleted)) {
                        $errors['error:modulestatuscomplete-timecompletedempty'] = 'cmctimecompleted';
                    }
                }
                break;
            case COMPLETION_COMPLETE_PASS:
                if ($usestimecompleted) {
                    if (empty($cmc->timecompleted)) {
                        $errors['error:modulestatuscompletepass-timecompletedempty'] = 'cmctimecompleted';
                    }
                } else {
                    if (empty($cmc->timemodified) && empty($cmc->timecompleted)) {
                        $errors['error:modulestatuscompletepass-timecompletedempty'] = 'cmctimecompleted';
                    }
                }
                break;
            case COMPLETION_COMPLETE_FAIL:
                if ($usestimecompleted) {
                    if (empty($cmc->timecompleted)) {
                        $errors['error:modulestatuscompletefail-timecompletedempty'] = 'cmctimecompleted';
                    }
                } else {
                    if (empty($cmc->timemodified) && empty($cmc->timecompleted)) {
                        $errors['error:modulestatuscompletefail-timecompletedempty'] = 'cmctimecompleted';
                    }
                }
                break;
            default:
                $errors['error:stateinvalid'] = 'completionstate';
                break;
        }

        return $errors;
    }

    /**
     * Checks the state of a user's criteria completion record.
     *
     * @param \stdClass $cccc as stored in the course_completion_crit_compl table (not all fields are required)
     * @return array describes any problems (error key => form field)
     */
    public static function get_criteria_completion_errors($cccc) {
        global $DB;

        $errors = array();

        $ismodulecriteria = $DB->record_exists('course_completion_criteria',
            array('id' => $cccc->criteriaid, 'criteriatype' => COMPLETION_CRITERIA_TYPE_ACTIVITY));

        $isrpl = isset($cccc->rpl) && !is_null($cccc->rpl) && $cccc->rpl != "";

        if ($isrpl && !$ismodulecriteria) {
            $errors['error:criterianotmodule-rplnotempty'] = 'rpl';
        }

        if ($isrpl && empty($cccc->timecompleted)) {
            $errors['error:criteriaincomplete-rplnotempty'] = 'rpl';
        }

        return $errors;
    }

    /**
     * Convert the errors returned by get_course_completion_errors into errors that can be used for form validation.
     *
     * @param array $errors as returned by get_course_completion_errors
     * @return array of form validation errors
     */
    public static function convert_errors_for_form($errors) {
        $formerrors = array();
        foreach ($errors as $stringkey => $formkey) {
            if (isset($formerrors[$formkey])) {
                $formerrors[$formkey] .= '<br/>' . get_string($stringkey, 'completion');
            } else {
                $formerrors[$formkey] = get_string($stringkey, 'completion');
            }
        }
        return $formerrors;
    }

    /**
     * Given a set of errors, calculate a unique problem key (just sort and concatenate errors).
     *
     * @param array $errors as returned by get_course_completion_errors
     * @return string
     */
    public static function convert_errors_to_problemkey($errors) {
        if (empty($errors)) {
            return '';
        }

        $errorkeys = array_keys($errors);
        sort($errorkeys);
        return implode('|', $errorkeys);
    }

    /**
     * Formats a date for a completion log.
     *
     * @param int $date
     * @return string
     */
    public static function format_log_date($date) {
        if ($date > 0) {
            return userdate($date, '%d %B %Y, %H:%M', 0) . ' (' . $date . ')';
        } else if (is_null($date)) {
            return "Not set (null)";
        } else {
            return "Not set ({$date})";
        }
    }

    /**
     * Returns true if the course module id relates to a module which uses timecompleted as the time that a
     * user has completed the module, rather than timemodified.
     *
     * @param int $cmid
     * @return bool
     */
    public static function module_uses_timecompleted($cmid) {
        global $DB;

        $sql = "SELECT m.name
                  FROM {course_modules} cm
                  JOIN {modules} m ON m.id = cm.module
                 WHERE cm.id = :cmid";
        $modulename = $DB->get_field_sql($sql, array('cmid' => $cmid), MUST_EXIST);

        $usetimecompleted = plugin_supports('mod', $modulename, FEATURE_COMPLETION_TIME_IN_TIMECOMPLETED);
        return !empty($usetimecompleted);
    }

    /**
     * Mark progressinfo caches stale to ensure completion data is re-read from the database on next view
     *
     * @param int @courseid
     * @param int @userid
     */
    public static function mark_progress_caches_stale($courseid, $userid) {
        $course = (object) array('id' => $courseid);
        $info = new \completion_info($course);
        $info->mark_progressinfo_stale($userid);
        \totara_program\progress\program_progress_cache::mark_user_cache_stale($userid);
    }

    /**
     * Schedule the reaggregation of the course completion of a given user.
     * The function will fail if it is already scheduled.
     *
     * @param int $courseid ID of the course.
     * @param int $userid ID of the user.
     * @param int $time The timestamp to schedule, 0 to schedule as soon as possible
     * @return bool true if succeeds
     */
    public static function schedule_reaggregation_of_completion($courseid, $userid, $time = 0) {
        global $DB;
        if ($time <= 0) {
            $time = time();
        }
        $success = $DB->set_field('course_completions', 'reaggregate', $time, array('course' => $courseid, 'userid' => $userid, 'reaggregate' => 0));
        if ($success) {
            self::log_course_reaggregation($courseid, $userid, 'Completion reaggregation scheduled');
        }
        return $success;
    }
}
