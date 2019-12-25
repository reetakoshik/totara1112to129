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
 * @author Nathan Lewis <nathan.lewis@totaralearning.com>
 * @package totara_completioneditor
 */

namespace totara_completioneditor\form;

use completion_criteria;
use core_completion\helper;
use totara_completioneditor\course_editor;
use totara_form\form;
use totara_form\form_controller;

global $CFG;

require_once($CFG->dirroot . '/completion/criteria/completion_criteria.php');
require_once($CFG->dirroot . '/completion/completion_completion.php');

/**
 * Controller for course_completion
 *
 * @package   totara_completioneditor
 * @copyright 2016 Totara Learning Solutions Ltd {@link http://www.totaralms.com/}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Nathan Lewis <nathan.lewis@totaralearning.com>
 */
class course_completion_controller extends form_controller {

    /** @var course_completion $form */
    protected $form;

    /**
     * This method is responsible for:
     *  - access control
     *  - getting of current data
     *  - getting of parameters
     *
     * and returning of the form instance.
     *
     * @param string $idsuffix string extra for identifier to allow repeated forms on one page
     * @return form
     */
    public function get_ajax_form_instance($idsuffix) {
        $section = optional_param('section', '', PARAM_ALPHA);
        $courseid = required_param('courseid', PARAM_INT);
        $userid = required_param('userid', PARAM_INT);
        $chid = optional_param('chid', 0, PARAM_INT);
        $criteriaid = optional_param('criteriaid', 0, PARAM_INT);
        $cmid = optional_param('cmid', 0, PARAM_INT);

        // Access control first.
        require_login($courseid);
        $coursecontext = \context_course::instance($courseid);
        require_capability('totara/completioneditor:editcoursecompletion', $coursecontext);

        require_sesskey();

        // Get the current data.
        list($currentdata, $params) = self::get_current_data_and_params($section, $courseid, $userid, $chid, $criteriaid, $cmid);

        $currentdata['form_select'] = 'totara_completioneditor\form\course_completion';

        // Create the form instance.
        $this->form = new course_completion($currentdata, $params, $idsuffix);

        return $this->form;
    }

    /**
     * Prepares all data for the form.
     *
     * @param string $section Which section/tab/page/functionality of the course completion editor that the user is viewing:
     *                        'overview', 'current', 'criteria', 'history', 'editcriteria', 'editmodule', 'edithistory'
     * @param int $courseid The course who's completion data we are viewing or editing
     * @param int $userid The user who's completion data we are viewing or editing
     * @param int $chid The course_completion_history->id to edit, when section is 'edithistory'
     * @param int $criteriaid The course_completion_criteria->id to edit, when section is 'editcriteria'
     * @param int $cmid The course_modules->id to edit, when section is 'editmodule' or 'editcriteria' for activities
     * @return array(array $currentdata, array $params)
     */
    public static function get_current_data_and_params($section, $courseid, $userid, $chid = 0, $criteriaid = 0, $cmid = 0) {
        global $COMPLETION_STATUS, $DB, $PAGE;

        // Set up the basic currentdata and params. Can be modified/added to later.
        $currentdata = array(
            'section' => $section,
            'courseid' => $courseid,
            'userid' => $userid,
            'chid' => $chid,
            'criteriaid' => $criteriaid,
            'cmid' => $cmid,
        );
        $params = array(
            'section' => $section,
        );

        /////////////////////////////////////////////////////////////////
        /// Current completion record.
        $coursecompletion = helper::load_course_completion($courseid, $userid, false);
        if (empty($coursecompletion)) {
            // Let the user know that there is no record, so it can't be edited.
            $params['hascoursecompletion'] = false;

        } else {
            $params['hascoursecompletion'] = true;
        }

        if ($section == 'overview' || $section == 'current') {
            if ($params['hascoursecompletion']) {
                if (array_key_exists($coursecompletion->status, $COMPLETION_STATUS)) {
                    $currentdata['status'] = $coursecompletion->status;
                } else {
                    $currentdata['status'] = course_completion::COMPLETIONSTATUSINVALID;
                }
                $currentdata['timeenrolled'] = !empty($coursecompletion->timeenrolled) ? $coursecompletion->timeenrolled : null;
                $currentdata['timestarted'] = !empty($coursecompletion->timestarted) ? $coursecompletion->timestarted : null;
                $currentdata['timecompleted'] = !empty($coursecompletion->timecompleted) ? $coursecompletion->timecompleted : null;
                $currentdata['rpl'] = $coursecompletion->rpl;
                $currentdata['rplgrade'] = $coursecompletion->rplgrade;

                $errors = helper::get_course_completion_errors($coursecompletion);
                if (!empty($errors)) {
                    $problemkey = helper::convert_errors_to_problemkey($errors);
                    $params['solution'] = course_editor::get_error_solution($problemkey, $courseid, $userid, true);
                }
            }
        }

        /////////////////////////////////////////////////////////////////
        /// History completion record.
        if ($section == 'edithistory') {
            if (!empty($chid)) {
                $historycompletion = $DB->get_record('course_completion_history',
                    array('id' => $chid, 'courseid' => $courseid, 'userid' => $userid), '*', MUST_EXIST);

                $currentdata['timecompleted'] = !empty($historycompletion->timecompleted) ? $historycompletion->timecompleted : null;
                $currentdata['grade'] = $historycompletion->grade;

                $params['hashistorycompletion'] = true;
            } else {
                $currentdata['timecompleted'] = null;
                $currentdata['grade'] = '';

                $params['hashistorycompletion'] = false;
            }
        }

        /////////////////////////////////////////////////////////////////
        /// Completion criteria record.
        if ($section == 'editcriteria') {
            // First get all the available data and check for problems.
            $ccc = $DB->get_record('course_completion_criteria',
                array('id' => $criteriaid, 'course' => $courseid), '*', MUST_EXIST);

            $cccc = $DB->get_record('course_completion_crit_compl',
                array('criteriaid' => $criteriaid, 'userid' => $userid), '*', IGNORE_MISSING);

            $cmc = null;
            $cmcerrors = array();
            if ($ccc->criteriatype == COMPLETION_CRITERIA_TYPE_ACTIVITY) {
                $cmid = $ccc->moduleinstance;
                $currentdata['cmid'] = $cmid;
                $cmc = $DB->get_record('course_modules_completion',
                    array('coursemoduleid' => $cmid, 'userid' => $userid));
                if (!empty($cmc)) {
                    $cmcerrors = helper::get_module_completion_errors($cmc);
                }
            }

            $ccccerrors = !empty($cccc) ? helper::get_criteria_completion_errors($cccc) : array();

            // Set up criteria stuff.
            $currentdata['cctimecompleted'] = !empty($cccc->timecompleted) ? $cccc->timecompleted : null;
            $currentdata['rpl'] = !empty($cccc->rpl) ? $cccc->rpl : null;
            if (!empty($ccccerrors)) {
                $currentdata['criteriastatus'] = course_completion::CRITERIASTATUSINVALID;
            } else {
                $currentdata['criteriastatus'] = empty($currentdata['cctimecompleted']) ?
                    course_completion::CRITERIASTATUSINCOMPLETE :
                    course_completion::CRITERIASTATUSCOMPLETE;
            }

            // Check if this completion criteria is for a module. In this case, we'll be editing two records.
            if ($ccc->criteriatype == COMPLETION_CRITERIA_TYPE_ACTIVITY) {
                $currentdata['cmctimecompleted'] = null;
                if (!empty($cmc)) {
                    $currentdata['viewed'] = $cmc->viewed;
                    if (!empty($cmcerrors)) {
                        $currentdata['completionstate'] = course_completion::COMPLETIONSTATUSINVALID;
                    } else {
                        $currentdata['completionstate'] = $cmc->completionstate;
                    }

                    if (helper::module_uses_timecompleted($cmid) || !empty($cmc->timecompleted)) {
                        // We use timecompleted if it is not empty, even if the module is not supposed to use it.
                        $timecompleted = $cmc->timecompleted;
                    } else {
                        $timecompleted = $cmc->timemodified;
                    }
                } else {
                    $currentdata['viewed'] = false;
                    $currentdata['completionstate'] = COMPLETION_INCOMPLETE;
                }
                $currentdata['cmctimecompleted'] = !empty($timecompleted) ? $timecompleted : null;

                $currentdata['cmid'] = $cmid;
                $params['ismodule'] = true;
                $params['hasmodulepassfail'] = plugin_supports('mod', $ccc->module, FEATURE_GRADE_HAS_GRADE, false);

                if (!empty($ccccerrors) || !empty($cmcerrors)) {
                    $currentdata['editingmode'] = course_completion::EDITINGMODESEPARATE;
                } else if ($currentdata['completionstate'] == COMPLETION_INCOMPLETE && $currentdata['criteriastatus'] == course_completion::CRITERIASTATUSINCOMPLETE) {
                    $currentdata['editingmode'] = course_completion::EDITINGMODEUSEMODULE;
                } else if ($currentdata['cmctimecompleted'] == $currentdata['cctimecompleted'] && $currentdata['completionstate'] != COMPLETION_INCOMPLETE) {
                    $currentdata['editingmode'] = course_completion::EDITINGMODEUSEMODULE;
                } else {
                    $currentdata['editingmode'] = course_completion::EDITINGMODESEPARATE;
                }
            } else {
                $params['ismodule'] = false;
            }

            // Title for the form section.
            $completioncriteria = completion_criteria::factory((array)$ccc);
            if ($ccc->criteriatype == COMPLETION_CRITERIA_TYPE_ACTIVITY) {
                $params['sectiontitle'] = $completioncriteria->get_title_detailed();

            } else if ($ccc->criteriatype == COMPLETION_CRITERIA_TYPE_COURSE) {
                $coursename = $completioncriteria->get_title_detailed();
                $params['sectiontitle'] = get_string('completionofcoursex', 'totara_completioneditor', $coursename);

            } else {
                $completion = \completion_completion::fetch(array('course' => $courseid, 'userid' => $userid));
                if (empty($completion)) {
                    $completion = new \stdClass();
                    $completion->userid = $userid;
                }
                $details = $completioncriteria->get_details($completion);
                if ($ccc->criteriatype == COMPLETION_CRITERIA_TYPE_ROLE) {
                    $params['sectiontitle'] = $details['requirement'];
                } else {
                    $params['sectiontitle'] = $details['type'];
                }
            }
        }

        /////////////////////////////////////////////////////////////////
        /// Module completion record.
        if ($section == 'editmodule') {
            $cmc = $DB->get_record('course_modules_completion',
                array('coursemoduleid' => $cmid, 'userid' => $userid));

            if (!empty($cmc)) {
                $cmcerrors = helper::get_module_completion_errors($cmc);

                $currentdata['viewed'] = $cmc->viewed;
                if (!empty($cmcerrors)) {
                    $currentdata['completionstate'] = course_completion::COMPLETIONSTATUSINVALID;
                } else {
                    $currentdata['completionstate'] = $cmc->completionstate;
                }

                if (helper::module_uses_timecompleted($cmid) || !empty($cmc->timecompleted)) {
                    // We use timecompleted if it is not empty, even if the module is not supposed to use it.
                    $timecompleted = $cmc->timecompleted;
                } else {
                    $timecompleted = $cmc->timemodified;
                }
            } else {
                $currentdata['viewed'] = false;
                $currentdata['completionstate'] = COMPLETION_INCOMPLETE;
            }
            $currentdata['cmctimecompleted'] = !empty($timecompleted) ? $timecompleted : null;

            // Title for the form section.
            $cms = get_fast_modinfo($courseid);
            $cminfo = $cms->get_cm($cmid);
            $params['ismodule'] = true;
            $params['hasmodulepassfail'] = plugin_supports('mod', $cminfo->modname, FEATURE_GRADE_HAS_GRADE, false);
            $params['sectiontitle'] = $cminfo->name;
        }

        /* @var \totara_completioneditor\output\course_renderer $output */
        $output = $PAGE->get_renderer('totara_completioneditor', 'course');

        /////////////////////////////////////////////////////////////////
        /// Programs and certifications.
        if ($section == 'overview') {
            list($progs, $certs) = course_editor::get_all_progs_and_certs($courseid, $userid);
            $params['progsandcertstable'] = $output->related_progs_and_certs($progs, $certs);
        }

        /////////////////////////////////////////////////////////////////
        /// Critera.
        if ($section == 'overview' || $section == 'criteria') {
            // Course completion criteria.
            list($coursecompletioncriteria, $overall) = course_editor::get_all_criteria($courseid, $userid);
            $params['criteriatable'] = $output->criteria($coursecompletioncriteria, $overall);

            // Orphaned course completion criteria.
            $orphanedcritcompls = course_editor::get_orphaned_criteria($courseid, $userid);
            $params['orphanedcritcompltable'] = $output->orphaned_criteria($orphanedcritcompls);

            // Module completion.
            $modules = course_editor::get_all_modules($courseid, $userid);
            $params['modulestable'] = $output->modules($modules);
        }

        /////////////////////////////////////////////////////////////////
        /// History.
        if ($section == 'overview' || $section == 'history') {
            $history = course_editor::get_all_history($courseid, $userid);
            $params['historytable'] = $output->history($history);
        }

        /////////////////////////////////////////////////////////////////
        /// Transactions.
        if ($section == 'overview' || $section == 'transactions') {
            $sql = "SELECT ccl.id, ccl.timemodified, ccl.changeuserid, ccl.description, " . get_all_user_name_fields(true, 'usr') . "
                      FROM {course_completion_log} ccl
                      LEFT JOIN {user} usr ON usr.id = ccl.changeuserid
                     WHERE (ccl.userid = :userid OR ccl.userid IS NULL) AND ccl.courseid = :courseid
                     ORDER BY ccl.id DESC";
            $transactions = $DB->get_records_sql($sql, array('userid' => $userid, 'courseid' => $courseid));
            $params['transactionstable'] = $output->transactions($transactions);
        }

        return array($currentdata, $params);
    }

    /**
     * Process the submitted form.
     *
     * @return array processed data
     */
    public function process_ajax_data() {
        parent::process_ajax_data();
        return array();
    }
}
