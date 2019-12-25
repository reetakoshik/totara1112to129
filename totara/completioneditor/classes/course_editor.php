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
 * @package totara_completioneditor
 */

namespace totara_completioneditor;

use core_completion\helper;
use totara_completioneditor\form\course_completion;

defined('MOODLE_INTERNAL') || die();

/**
 * Class course_editor.
 *
 * @package totara_completioneditor
 */
final class course_editor {

    /**
     * Given a problem key returned by convert_errors_to_problemkey, return any known explanation or solutions, in html format.
     *
     * @param string $problemkey as returned by \core_completion\helper::convert_errors_to_problemkey
     * @param int $courseid if provided (non-0), url should only fix problems for this course
     * @param int $userid if provided (non-0), url should only fix problems for this user
     * @param bool $returntoeditor true if you want to return to the editor for this user/course, default false for checker
     * @return string html formatted, possibly including url links to activate known fixes
     */
    public static function get_error_solution($problemkey, $courseid = 0, $userid = 0, $returntoeditor = false) {
        if (empty($problemkey)) {
            return '';
        }

        $params = array(
            'courseid' => $courseid,
            'userid' => $userid,
            'returntoeditor' => $returntoeditor,
            'sesskey' => sesskey()
        );
        $baseurl = new \moodle_url('/totara/completioneditor/check_course_completion.php', $params);

        switch ($problemkey) {
            // See certs for examples of automated fixes. Remove this comment when a fix is implemented.
            default:
                $html = get_string('error:info_unknowncombination', 'totara_completioneditor');
                break;
        }

        return $html;
    }

    /**
     * Applies the specified fix to course completion record.
     *
     * @param string $fixkey the key for the specific fix to be applied (see switch in code)
     * @param int $courseid if provided (non-0), only fix problems for this course
     * @param int $userid if provided (non-0), only fix problems for this user
     */
    public static function apply_fix($fixkey, $courseid = 0, $userid = 0) {
        global $DB;

        // Get all completion records, applying the specified filters.
        $sql = "SELECT cc.*
              FROM {course_completions} cc
             WHERE 1=1";
        $params = array();
        if ($courseid) {
            $sql .= " AND cc.course = :courseid";
            $params['courseid'] = $courseid;
        }
        if ($userid) {
            $sql .= " AND cc.userid = :userid";
            $params['userid'] = $userid;
        }

        $rs = $DB->get_recordset_sql($sql, $params);

        foreach ($rs as $coursecompletion) {
            // Check for errors.
            $errors = helper::get_course_completion_errors($coursecompletion);

            // Nothing wrong, so skip this record.
            if (empty($errors)) {
                continue;
            }

            $problemkey = helper::convert_errors_to_problemkey($errors);
            $result = "";
            $ignoreproblem = "";

            // Only fix if this is an exact match for the specified problem.
            switch ($fixkey) {
                // See certif_fix_completions for an example. Remove this comment when first fix is implemented.
                // When adding the first fix here, you must also implement (copy from certs) the following tests:
                // * test_course_completion_fix_only_selected
                // * test_course_completion_fix_only_specified_state
                // * test_course_completion_fix_only_if_isolated_problem
                // * test_course_completion_fix_known_unfixed_problems
                // Plus one test for each fix function.
                default:
                    break;
            }

            // Nothing happened, so no need to update or log.
            if (empty($result)) {
                continue;
            }

            helper::write_course_completion($coursecompletion, $result);
        }

        $rs->close();
    }

    /**
     * Gets all of the programs and certifications that the user is involved in and that relate to the given course.
     *
     * @param int $courseid
     * @param int $userid
     * @return array(array(stdClass) $progs, array(stdClass) $certs)
     *         each $prog contains 'name', 'status', 'timecompleted', etc.
     *         each $cert contains 'name', 'status', 'certifpath', 'renewalstatus', 'timecompleted', etc.
     */
    public static function get_all_progs_and_certs($courseid, $userid) {
        global $CFG, $DB;

        $progseditenabled = $CFG->enableprogramcompletioneditor && !totara_feature_disabled('programs');
        $certseditenabled = $CFG->enableprogramcompletioneditor && !totara_feature_disabled('certifications');

        // Programs first.
        $progs = array();

        $sql = "SELECT DISTINCT prog.id AS programid, prog.fullname, pc.status, pc.timecompleted
              FROM {prog} prog
              JOIN (SELECT pcs.programid
                      FROM {prog_courseset_course} pcsc
                      JOIN {prog_courseset} pcs
                        ON pcs.id = pcsc.coursesetid
                     WHERE pcsc.courseid = :courseid1
                     UNION
                    SELECT pcs.programid
                      FROM {comp_criteria} cc
                      JOIN {prog_courseset} pcs
                        ON pcs.competencyid = cc.competencyid
                       AND cc.itemtype = 'coursecompletion'
                     WHERE cc.iteminstance = :courseid2
                   ) cp
                ON prog.id = cp.programid
         LEFT JOIN {prog_completion} pc
                ON pc.programid = prog.id
               AND pc.coursesetid = 0
               AND pc.userid = :userid1
             WHERE prog.certifid IS NULL
               AND EXISTS (SELECT pc2.id
                             FROM {prog_completion} pc2
                            WHERE pc2.programid = prog.id
                              AND pc2.userid = :userid2
                              AND pc2.coursesetid = 0
                            UNION
                           SELECT pch.id
                             FROM {prog_completion_history} pch
                            WHERE pch.programid = prog.id
                              AND pch.userid = :userid3
                              AND pch.coursesetid = 0)";
        $params = array(
            'courseid1' => $courseid,
            'courseid2' => $courseid,
            'userid1' => $userid,
            'userid2' => $userid,
            'userid3' => $userid,
        );
        $records = $DB->get_records_sql($sql, $params);

        $progurl = new \moodle_url('/totara/program/edit_completion.php');

        foreach ($records as $record) {
            // Provide all the info that someone might want to use to display the record.
            $row = new \stdClass();
            $row->type = 'program';
            $row->programid = $record->programid;
            $row->name = $record->fullname;
            $row->status = $record->status;
            $row->timecompleted = $record->timecompleted;
            $editurl = clone($progurl);
            $editurl->param('id', $record->programid);
            $editurl->param('userid', $userid);

            $program = new \program($record->programid);
            $programcontext = $program->get_context();
            if ($progseditenabled && has_capability('totara/program:editcompletion', $programcontext)) {
                $row->editurl = $editurl;
            } else {
                $row->editurl = false;
            }

            $progs[] = $row;
        }

        // Then certs.
        $certs = array();

        $sql = "SELECT DISTINCT prog.id AS programid, prog.fullname, cc.certifpath, cc.status, cc.renewalstatus, cc.timecompleted
              FROM {prog} prog
              JOIN {prog_courseset} pcs
                ON prog.id = pcs.programid
              JOIN {prog_courseset_course} pcsc
                ON pcs.id = pcsc.coursesetid
               AND pcsc.courseid = :courseid
         LEFT JOIN {certif_completion} cc
                ON cc.certifid = prog.certifid
               AND cc.userid = :userid1
             WHERE EXISTS (SELECT cc2.id
                             FROM {certif_completion} cc2
                            WHERE cc2.certifid = prog.certifid
                              AND cc2.userid = :userid2
                            UNION
                           SELECT cch.id
                             FROM {certif_completion_history} cch
                            WHERE cch.certifid = prog.certifid
                              AND cch.userid = :userid3)";
        $params = array('courseid' => $courseid, 'userid1' => $userid, 'userid2' => $userid, 'userid3' => $userid);
        $records = $DB->get_records_sql($sql, $params);

        $certurl = new \moodle_url('/totara/certification/edit_completion.php');

        foreach ($records as $record) {
            // Provide all the info that someone might want to use to display the record.
            $row = new \stdClass();
            $row->type = 'certification';
            $row->programid = $record->programid;
            $row->name = $record->fullname;
            $row->certifpath = $record->certifpath;
            $row->status = $record->status;
            $row->renewalstatus = $record->renewalstatus;
            $row->timecompleted = $record->timecompleted;
            $editurl = clone($certurl);
            $editurl->param('id', $record->programid);
            $editurl->param('userid', $userid);

            $program = new \program($record->programid);
            $programcontext = $program->get_context();
            if ($certseditenabled && has_capability('totara/program:editcompletion', $programcontext)) {
                $row->editurl = $editurl;
            } else {
                $row->editurl = false;
            }

            $certs[] = $row;
        }

        return array($progs, $certs);
    }

    /**
     * Gets all of the completion criteria data for the user in the course.
     *
     * @param int $courseid
     * @param int $userid
     * @return array(array(stdClass) $criteria, int $overall)
     *         each $criteria contains 'type', 'title', 'status', 'complete', 'timecompleted', 'details', 'editurl', etc.
     *         $overall is one of COMPLETION_AGGREGATION_ALL or COMPLETION_AGGREGATION_ANY
     */
    public static function get_all_criteria($courseid, $userid) {
        global $DB, $PAGE;

        $rows = array();

        // Completion criteria.
        $course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
        $info = new \completion_info($course);
        $completions = $info->get_completions($userid);

        // Get overall aggregation method.
        $overall = $info->get_aggregation_method();

        // Organise module completions according to the course display order.
        // Obtain the display order of modules.
        $sections = $DB->get_records('course_sections', array('course' => $courseid), 'section ASC', 'id, sequence');
        $moduleorder = array();
        foreach ($sections as $section) {
            if (!empty($section->sequence)) {
                $moduleorder = array_merge(array_values($moduleorder), array_values(explode(',', $section->sequence)));
            }
        }

        $orderedcompletions = array();
        $modulecriteria = array();
        $modulecompletions = array();
        $nonmodulecompletions = array();
        foreach($completions as $completion) {
            /** @var \completion_criteria_completion $completion */
            $criteria = $completion->get_criteria();
            if ($criteria->criteriatype == COMPLETION_CRITERIA_TYPE_ACTIVITY) {
                if (!empty($criteria->moduleinstance)) {
                    $modulecriteria[$criteria->moduleinstance] = $completion;
                }
            } else {
                $nonmodulecompletions[] = $completion;
            }
        }

        // Compare to the course module order to put the modules in the same order as on the course view.
        foreach($moduleorder as $module) {
            // Some modules may not have completion criteria and can be ignored.
            if (isset($modulecriteria[$module])) {
                $modulecompletions[] = $modulecriteria[$module];
            }
        }

        // Put the module completions at the top.
        foreach ($modulecompletions as $completion) {
            $orderedcompletions[] = $completion;
        }
        foreach ($nonmodulecompletions as $completion) {
            $orderedcompletions[] = $completion;
        }

        $url = clone($PAGE->url);
        $url->param('section', 'editcriteria');

        // Loop through course criteria and create the final rows.
        foreach ($orderedcompletions as $completion) {
            /** @var \completion_criteria $criteria */
            $criteria = $completion->get_criteria();
            $details = $criteria->get_details($completion);

            // Provide all the info that someone might want to use to display the record.
            $row = new \stdClass();
            $row->ccid = $completion->id;
            $row->details = $details;
            $row->title = $criteria->get_title();
            $row->criteriatype = $criteria->criteriatype;
            $row->aggregation = "";
            $row->rpl = $completion->rpl;
            $row->timecompleted = $completion->timecompleted;
            $editurl = clone($url);
            $editurl->param('criteriaid', $completion->criteriaid);
            $row->editurl = $editurl;
            $row->hasproblem = !empty(helper::get_criteria_completion_errors($completion));

            if ($criteria->criteriatype == COMPLETION_CRITERIA_TYPE_ACTIVITY) {
                $row->cmcid = $DB->get_field('course_modules_completion', 'id',
                    array('coursemoduleid' => $criteria->moduleinstance, 'userid' => $userid));
            } else {
                $row->cmcid = '';
            }

            // Calculate aggregation method and pre-process some stuff.
            switch ($criteria->criteriatype) {
                case COMPLETION_CRITERIA_TYPE_ACTIVITY:
                case COMPLETION_CRITERIA_TYPE_COURSE:
                case COMPLETION_CRITERIA_TYPE_ROLE:
                    $agg = $info->get_aggregation_method($criteria->criteriatype);
                    if ($agg == COMPLETION_AGGREGATION_ALL) {
                        $row->aggregation = get_string('allrequired', 'totara_completioneditor');
                    } else {
                        $row->aggregation = get_string('anyrequired', 'totara_completioneditor');
                    }

                    if ($criteria->criteriatype == COMPLETION_CRITERIA_TYPE_ROLE) {
                        $row->criteriadetails = $criteria->get_title();
                    } else {
                        $row->criteriadetails = $criteria->get_title_detailed();
                    }
                    break;
                default:
                    $row->criteriadetails = "";
                    break;
            }

            $rows[] = $row;
        }

        return array($rows, $overall);
    }

    /**
     * Gets all of the orphaned completion criteria records for the user in the course.
     *
     * @param int $courseid
     * @param int $userid
     * @return array(stdClass) containing 'name' (always "Unknown"), 'rpl', 'timecompleted', 'delteurl', etc.
     */
    public static function get_orphaned_criteria($courseid, $userid) {
        global $DB;

        $sql = "SELECT cccc.*
              FROM {course_completion_crit_compl} cccc
         LEFT JOIN {course_completion_criteria} ccc
                ON ccc.id = cccc.criteriaid
             WHERE cccc.course = :courseid
               AND cccc.userid = :userid
               AND ccc.id IS NULL";
        $params = array('courseid' => $courseid, 'userid' => $userid);
        $records = $DB->get_records_sql($sql, $params);

        $orphans = array();

        foreach ($records as $record) {
            $name = 'name???';

            $orphan = new \stdClass();
            $orphan->ccid = $record->id;
            $orphan->criteriaid = $record->criteriaid;
            $orphan->name = get_string('unknown', 'totara_completioneditor');
            $orphan->rpl = $record->rpl;
            $orphan->timecompleted = $record->timecompleted;
            $orphan->deleteurl = new \moodle_url('/totara/completioneditor/edit_course_completion.php',
                array(
                    'courseid' => $courseid,
                    'userid' => $userid,
                    'section' => 'criteria',
                    'deleteorphanedcritcomplid' => $record->id,
                    'sesskey' => sesskey()
                ));

            $orphans[] = $orphan;
        }

        return $orphans;
    }

    /**
     * Gets all of the modules and associated completion data for the user in the course.
     *
     * @param int $courseid
     * @param int $userid
     * @return array(stdClass) containing 'name' (of module), 'status', 'timecompleted', 'editurl', etc.
     */
    public static function get_all_modules($courseid, $userid) {
        global $DB;

        $sql = "SELECT cm.id AS coursemoduleid, md.name AS modname, ccc.id AS criteriaid, cccc.id AS critcomplid,
                   cm.instance, cmc.id AS cmcid, cmc.completionstate, cmc.timemodified, cmc.timecompleted
              FROM {course_modules} cm
              JOIN {modules} md
                ON cm.module = md.id
         LEFT JOIN {course_modules_completion} cmc
                ON cmc.coursemoduleid = cm.id
               AND cmc.userid = :userid1
         LEFT JOIN {course_completion_criteria} ccc
                ON ccc.criteriatype = 4
               AND ccc.moduleinstance = cm.id
         LEFT JOIN {course_completion_crit_compl} cccc
                ON cccc.criteriaid = ccc.id
               AND cccc.userid = :userid2
             WHERE cm.course = :courseid
               AND cm.instance != 0";
        $params = array('courseid' => $courseid, 'userid1' => $userid, 'userid2' => $userid);
        $records = $DB->get_records_sql($sql, $params);

        $modules = array();

        foreach ($records as $record) {
            $name = $DB->get_field($record->modname, 'name',
                array('id' => $record->instance), MUST_EXIST);

            // Provide all the info that someone might want to use to display the record.
            $module = new \stdClass();
            $module->cmcid = $record->cmcid;
            $module->criteriaid = $record->criteriaid;
            $module->ccid = $record->critcomplid;
            $module->name = $name;
            $module->status = $record->completionstate;
            if ($record->completionstate == COMPLETION_INCOMPLETE) {
                $module->timecompleted = null;
            } else if (helper::module_uses_timecompleted($record->coursemoduleid)){
                $module->timecompleted = $record->timecompleted;
            } else {
                $module->timecompleted = $record->timemodified;
            }
            if (!empty($record->criteriaid)) {
                $module->editurl = new \moodle_url('/totara/completioneditor/edit_course_completion.php',
                    array('courseid' => $courseid, 'userid' => $userid, 'section' => 'editcriteria', 'criteriaid' => $record->criteriaid));
            } else {
                $module->editurl = new \moodle_url('/totara/completioneditor/edit_course_completion.php',
                    array('courseid' => $courseid, 'userid' => $userid, 'section' => 'editmodule', 'cmid' => $record->coursemoduleid));
            }
            $module->hasproblem = !empty(helper::get_module_completion_errors($record));

            $modules[] = $module;
        }

        return $modules;
    }

    /**
     * Gets all history data for the user in the course.
     *
     * @param int $courseid
     * @param int $userid
     * @return array(stdClass) containing 'chid', 'timecompleted', 'grade', 'editurl' and 'deleteurl'.
     */
    public static function get_all_history($courseid, $userid) {
        global $DB, $PAGE;

        $records = $DB->get_records('course_completion_history', array('userid' => $userid, 'courseid' => $courseid),
            'timecompleted DESC', 'id AS chid, timecompleted, grade');

        $history = array();

        $editurl = clone($PAGE->url);
        $editurl->param('section', 'edithistory');
        $deleteurl = clone($PAGE->url);
        $deleteurl->param('deletehistory', 1);
        $deleteurl->param('sesskey', sesskey());

        foreach ($records as $record) {
            $editurl->param('chid', $record->chid);
            $deleteurl->param('chid', $record->chid);
            $record->editurl = clone($editurl);
            $record->deleteurl = clone($deleteurl);
            $history[] = $record;
        }

        return $history;
    }

    /**
     * Processes completion data submitted through the editor - transforms it to look like a course completion record,
     * suitable for use in $DB->update_record().
     *
     * Note that the course_completions record must already exist in the database (matching the user and course id
     * supplied), and the record ids will be included in the returned data. Creating new completion records should be
     * achieved automatically by assigning a user to a course, not manually in a form!
     *
     * @param \stdClass $data contains the data submitted by the form
     * @return \stdClass compatible with the course_completions table
     */
    public static function get_current_completion_from_data($data) {
        global $DB;

        // Get existing record id (double-checks that everything is valid).
        $sql = "SELECT cc.id
              FROM {course_completions} cc
             WHERE cc.course = :courseid AND cc.userid = :userid";
        $params = array('courseid' => $data->courseid, 'userid' => $data->userid);
        $existingrecords = $DB->get_record_sql($sql, $params, MUST_EXIST);

        $now = time();

        $coursecompletion = new \stdClass();
        $coursecompletion->id = $existingrecords->id;
        $coursecompletion->course = $data->courseid;
        $coursecompletion->userid = $data->userid;
        $coursecompletion->status = $data->status;
        $coursecompletion->timeenrolled = !empty($data->timeenrolled) ? $data->timeenrolled : 0;
        $coursecompletion->timestarted = !empty($data->timestarted) ? $data->timestarted : 0;
        $coursecompletion->timecompleted = !empty($data->timecompleted) ? $data->timecompleted : null;
        $coursecompletion->rpl = (isset($data->rpl) && !is_null($data->rpl) && $data->rpl != '') ? $data->rpl : null;
        $coursecompletion->rplgrade = (isset($data->rplgrade) && is_numeric($data->rplgrade)) ? $data->rplgrade : null;
        $coursecompletion->timemodified = $now;

        return $coursecompletion;
    }

    /**
     * Processes completion data submitted through the editor, returns records course_modules_completion and
     * course_completion_crit_compl, suitable for use in  $DB->insert_record() or $DB->update_record().
     *
     * @param \stdClass $data contains the data submitted by the form
     * @return array(\stdClass $cmc, \stdClass $cccc) compatible with course_modules_completion and course_completion_crit_compl
     */
    public static function get_module_and_criteria_from_data($data) {
        global $DB;

        $cmc = null;
        $cccc = null;

        if (!empty($data->cmid)) {
            $cmc = $DB->get_record('course_modules_completion',
                array('coursemoduleid' => $data->cmid, 'userid' => $data->userid),
                'id, timecompleted');

            if (empty($cmc)) {
                $cmc = new \stdClass();
            }

            $cmc->coursemoduleid = $data->cmid;
            $cmc->userid = $data->userid;
            $cmc->completionstate = $data->completionstate;
            $cmc->viewed = $data->viewed;

            $now = time();

            if (helper::module_uses_timecompleted($data->cmid) || !empty($cmc->timecompleted)) {
                // We write to timecompleted if it is not currently empty, even if the module is not supposed to use it.
                $cmc->timemodified = $now;
                $cmc->timecompleted = !empty($data->cmctimecompleted) ? $data->cmctimecompleted : null;
            } else {
                $cmc->timemodified = !empty($data->cmctimecompleted) ? $data->cmctimecompleted : $now;
                $cmc->timecompleted = null;
            }

            $ismodule = true;
        } else {
            $ismodule = false;
        }

        if (!empty($data->criteriaid)) {
            $cccc = $DB->get_record('course_completion_crit_compl',
                array('userid' => $data->userid, 'course' => $data->courseid, 'criteriaid' => $data->criteriaid),
                'id');

            if (empty($cccc)) {
                $cccc = new \stdClass();
            }

            $cccc->userid = $data->userid;
            $cccc->course = $data->courseid;
            $cccc->criteriaid = $data->criteriaid;
            $cccc->rpl = !empty($data->rpl) ? $data->rpl : null;

            if ($ismodule && $data->editingmode == course_completion::EDITINGMODEUSEMODULE) {
                // Use the module's status and timecompleted.
                $cccc->timecompleted = !empty($data->cmctimecompleted) ? $data->cmctimecompleted : null;
            } else {
                // Ignore the module status and time completed.
                $cccc->timecompleted = !empty($data->cctimecompleted) ? $data->cctimecompleted : null;
            }
        }

        return array($cmc, $cccc);
    }
}
