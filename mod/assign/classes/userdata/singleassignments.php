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
 * @author Murali Nair <murali.nair@totaralearning.com>
 * @package mod_assign
 */

namespace mod_assign\userdata;


use totara_userdata\userdata\export;
use totara_userdata\userdata\item;
use totara_userdata\userdata\target_user;

defined('MOODLE_INTERNAL') || die();

// This is needed to suppress warnings given by the use of undefined submission constants.
/** @var $CFG \stdClass */
require_once($CFG->dirroot .'/mod/assign/submissionplugin.php');
require_once($CFG->dirroot .'/mod/assign/feedbackplugin.php');
require_once($CFG->dirroot .'/mod/assign/submission/onlinetext/locallib.php');
require_once($CFG->dirroot .'/mod/assign/submission/file/locallib.php');
require_once($CFG->dirroot .'/mod/assign/feedback/file/locallib.php');

/**
 * Handler for individual assignments.
 *
 * It is ok to completely remove individual assignments. On the other hand, team
 * submissions must be retained because purging records affects other learners'
 * grades.
 *
 * The public mod assign APIs are not used for purging for 2 reasons:
 * - The APIs have "delete" functions like reset_userdata and delete_instance.
 *   However these remove data for ALL users for a  specific assignment.
 * - Other APIs deal with a single entity at a time eg delete_user_submission.
 *   Which makes the usual practice of querying for a bunch of assignments, then
 *   processing each user for each submission/feedback in each assignment very
 *   inefficient. Better to do mass deletions, all wrapped in a DB transaction.
 */
class singleassignments extends item {

    /**
     * Returns sort order.
     *
     * @return int
     */
    public static function get_sortorder() {
        return 100;
    }

    /**
     * {@inheritDoc}
     */
    public static function get_compatible_context_levels() {
        return [CONTEXT_SYSTEM, CONTEXT_COURSECAT, CONTEXT_COURSE, CONTEXT_MODULE];
    }

    /**
     * {@inheritDoc}
     */
    public static function is_purgeable(int $userstatus) {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    protected static function purge(target_user $user, \context $context) {
        $userid = $user->get_user_record()->id;

        $advgradingids = static::get_advanced_grading_instance_ids($user, $context);

        // At this time, the order of deletions is immaterial since the
        // database does not enforce referential integrity. But logically,
        // it should follow the sequence specified below.
        self::purge_user_files($context, $userid);
        self::purge_user_submission_comments($context, $userid);
        self::purge_user_submissions($context, $userid);
        self::purge_user_feedback($context, $userid);
        self::purge_user_overrides($context, $userid);
        self::purge_user_assignments($context, $userid);
        self::purge_user_gradebook($context, $userid);

        // No ids here - no data to purge.
        if (!empty($advgradingids)) {
            self::purge_advanced_grading_fillings('gradingform_guide_fillings', $advgradingids);
            self::purge_advanced_grading_fillings('gradingform_rubric_fillings', $advgradingids);
            self::purge_advanced_grading_instances($advgradingids);
        }

        return self::RESULT_STATUS_SUCCESS;
    }


    /**
     * Deletes uploaded files associated with the purged user.
     *
     * NB: this not only deletes submission files uploaded by the user but also
     * feedback files uploaded by the teacher. The latter is necessary because
     * everything about a user is removed for individual assignments; retaining
     * a teacher's uploads will result in dangling references.
     *
     * @param \context $context context.
     * @param int $userid user to be deleted.
     */
    private static function purge_user_files(\context $context, $userid) {
        global $DB;

        $module = 'assign';
        $params = ['userid' => $userid];

        $target = "submission";
        $contextjoin = self::get_activities_join($context, $module, "$target.assignment", $module);
        $filter = "
            SELECT $target.id, $module.id as moduleid, $module.course, cm.id as cmid
              FROM {assign_submission} $target
              $contextjoin
             WHERE $target.userid = :userid
               AND $module.teamsubmission = 0
        ";

        $fs = get_file_storage();
        foreach ($DB->get_records_sql($filter, $params) as $submission) {
            $modulecontext = \context_module::instance($submission->cmid);

            $files = [
                ['assignsubmission_onlinetext', ASSIGNSUBMISSION_ONLINETEXT_FILEAREA],
                ['assignsubmission_file', ASSIGNSUBMISSION_FILE_FILEAREA],
                ['assignfeedback_file', ASSIGNFEEDBACK_FILE_FILEAREA]
            ];
            foreach ($files as [$component, $area]) {
                $fs->delete_area_files($modulecontext->id, $component, $area, $submission->id);
            }
        }
    }


    /**
     * Deletes user submission comments.
     *
     * @param \context $context context.
     * @param int $userid user to be deleted.
     */
    private static function purge_user_submission_comments(\context $context, $userid) {
        global $DB;

        // Note the values in params[]: it is an *associative array* but the DML
        // module chokes when it refers to the same placeholder but in multiple
        // points in the SQL statement.
        $module = 'assign';
        $params = ['userid' => $userid, 'userid1' => $userid];

        $target = "submission";
        $contextjoin = self::get_activities_join($context, $module, "$target.assignment", $module);
        $filter = "
              userid = :userid
              AND component = 'assignsubmission_comments'
              AND commentarea = 'submission_comments'
              AND itemid IN (
                  SELECT $target.id
                    FROM {assign_submission} $target
                    $contextjoin
                   WHERE $target.userid = :userid1
                      AND $module.teamsubmission = 0
              )
        ";

        $DB->delete_records_select('comments', $filter, $params);
    }


    /**
     * Deletes user submissions.
     *
     * @param \context $context context.
     * @param int $userid user to be deleted.
     */
    private static function purge_user_submissions(\context $context, $userid) {
        global $DB;

        $module = 'assign';
        $params = ['userid' => $userid];

        $target = 'submission';
        $contextjoin = self::get_activities_join($context, $module, "$target.assignment", $module);
        $filter = "
            submission IN (
                SELECT $target.id
                  FROM {assign_submission} $target
                  $contextjoin
                 WHERE $target.userid = :userid
                   AND $module.teamsubmission = 0
            )
        ";

        $tables = [
            'assignsubmission_onlinetext',
            'assignsubmission_file'
        ];
        foreach ($tables as $table) {
            $DB->delete_records_select($table, $filter, $params);
        }
    }


    /**
     * Deletes user feedback.
     *
     * @param \context $context context.
     * @param int $userid user to be deleted.
     */
    private static function purge_user_feedback(\context $context, $userid) {
        global $DB;

        $module = 'assign';
        $params = ['userid' => $userid];

        $target = 'grade';
        $contextjoin = self::get_activities_join($context, $module, "$target.assignment", $module);
        $filter = "
             IN (
                SELECT $target.id
                  FROM {assign_grades} $target
                  $contextjoin
                 WHERE $target.userid = :userid
                   AND $module.teamsubmission = 0
            )
        ";

        $tables = [
            ['assignfeedback_file', 'grade'],
            ['assignfeedback_editpdf_annot', 'gradeid'],
            ['assignfeedback_editpdf_cmnt', 'gradeid'],
            ['assignfeedback_comments', 'grade']
        ];
        foreach ($tables as [$table, $column]) {
            $DB->delete_records_select($table, "$column $filter", $params);
        }


        // Note assignfeedback_editpdf_queue links by assign_submission whereas
        // all the other feedback tables link by assign_grades!
        $target = 'submission';
        $contextjoin = self::get_activities_join($context, $module, "$target.assignment", $module);
        $filter = "
            submissionid IN (
              SELECT $target.id
                FROM {assign_submission} $target
                $contextjoin
               WHERE $target.userid = :userid
                 AND $module.teamsubmission = 0
            )
        ";

        $DB->delete_records_select('assignfeedback_editpdf_queue', $filter, $params);
    }


    /**
     * Deletes user assignment details.
     *
     * @param \context $context context.
     * @param int $userid user to be deleted.
     */
    private static function purge_user_assignments(\context $context, $userid) {
        global $DB;

        $module = 'assign';
        $params = ['userid' => $userid];

        $target = 'target';
        $contextjoin = self::get_activities_join($context, $module, "$target.assignment", $module);

        $tables = [
            'assign_user_flags',
            'assign_user_mapping',
            'assign_grades',
            'assign_submission'
        ];
        foreach ($tables as $table) {
            // Unfortunately in MySQL, it is not possible to delete records if
            // a WHERE clause refers to the primary table in a subquery. Hence
            // the inefficient two step retrieval and deletion here.
            $filter = "
                userid = :userid
                   AND id IN (
                     SELECT $target.id
                       FROM {{$table}} $target
                       $contextjoin
                      WHERE $module.teamsubmission = 0
                   )
            ";
            $records = $DB->get_records_select_menu($table, $filter, $params, "", "id, userid");
            $DB->delete_records_list($table, 'id', array_keys($records));
        }
    }


    /**
     * Deletes user assignment overrides.
     *
     * @param \context $context context.
     * @param int $userid user to be deleted.
     */
    private static function purge_user_overrides(\context $context, $userid) {
        global $DB;

        $module = 'assign';
        $params = ['userid' => $userid];

        $target = 'target';
        $contextjoin = self::get_activities_join($context, $module, "$target.assignid", $module);
        $filter = "
            userid = :userid
               AND id IN (
                 SELECT $target.id
                   FROM {assign_overrides} $target
                   $contextjoin
                  WHERE $module.teamsubmission = 0
               )
        ";

        // Need to send out events for user override deletions. Also, in MySQL,
        // it is not possible to delete records if a WHERE clause refers to the
        // primary table in a subquery. Hence the inefficient retrieval and then
        // one by one deletion here.
        $overrides = $DB->get_records_select('assign_overrides', $filter, $params);

        foreach ($overrides as $override) {
            $id = $override->id;
            $DB->delete_records('assign_overrides', ['id' => $id]);

            $params = [
                'objectid' => $id,
                'context' => $context,
                'relateduserid' => $override->userid,
                'other' => ['assignid' => $override->assignid]
            ];

            \mod_assign\event\user_override_deleted::create($params)->trigger();
        }
    }


    /**
     * Deletes grades from the gradebook.
     *
     * @param \context $context context.
     * @param int $userid user to be deleted.
     */
    private static function purge_user_gradebook(\context $context, $userid) {
        global $DB;

        $module = 'assign';
        $params = ['userid' => $userid];

        $target = 'grade_items';
        $contextjoin = self::get_activities_join($context, $module, "$target.iteminstance", $module);
        $filter = "
            userid = :userid
               AND itemid IN (
                SELECT $target.id
                  FROM {grade_items} $target
                  $contextjoin
                 WHERE $target.itemtype = 'mod'
                   AND $target.itemmodule = 'assign'
                   AND $module.teamsubmission = 0
             )
        ";

        $tables = [
            'grade_grades_history',
            'grade_grades'
        ];
        foreach ($tables as $table) {
            $DB->delete_records_select($table, $filter, $params);
        }
    }


    /**
     * {@inheritDoc}
     */
    public static function is_exportable() {
        return true;
    }


    /**
     * {@inheritDoc}
     */
    protected static function export(target_user $user, \context $context) {
        global $DB;

        $userid = $user->get_user_record()->id;
        $params = ['userid' => $userid];

        $module = 'assign';
        $target = 'submission';
        $contextjoin = self::get_activities_join($context, $module, "$target.assignment", $module);
        $sql = "
            SELECT $module.id as moduleid, $module.name, $module.course, $target.id as submissionid, $target.timemodified, cm.id as cmid
              FROM {assign_submission} $target
              $contextjoin
             WHERE $target.userid = :userid
               AND $module.teamsubmission = 0
        ";

        $export = new export();

        $advgradeinstanceids = static::get_advanced_grading_instance_ids($user, $context);
        $advgraderubricfillings = static::get_advanced_grading_fillings('gradingform_rubric_fillings', $advgradeinstanceids);
        $advgradeguidefillings = static::get_advanced_grading_fillings('gradingform_guide_fillings', $advgradeinstanceids);

        foreach ($DB->get_recordset_sql($sql, $params) as $assignment) {
            $params = ['userid' => $userid, 'assignmentid' => $assignment->moduleid];

            $gradesql = "
                SELECT id, grade, attemptnumber
                  FROM {assign_grades} grade
                 WHERE userid = :userid
                   AND assignment = :assignmentid
            ";
            $attempts = [];
            foreach ($DB->get_recordset_sql($gradesql, $params) as $grade) {
                $attempts[$grade->attemptnumber] = [
                    "grade" => $grade->grade,
                ];

                if (!empty($guidefillings = static::get_guide_fillings_for_grading_instance($advgradeguidefillings, $grade->id))) {
                    $attempts[$grade->attemptnumber]['advanced_guide_fillings'] = $guidefillings;
                }

                if (!empty($rubricfillings = static::get_rubric_fillings_for_grading_instance($advgraderubricfillings, $grade->id))) {
                    $attempts[$grade->attemptnumber]['advanced_rubric_fillings'] = $rubricfillings;
                }
            }

            $commentssql = "
                SELECT content
                  FROM {comments}
                 WHERE userid = :userid
                   AND component = 'assignsubmission_comments'
                   AND commentarea = 'submission_comments'
                   AND itemid = :submissionid
            ";
            $params = ['userid' => $userid, 'submissionid' => $assignment->submissionid];

            $comments = [];
            foreach ($DB->get_recordset_sql($commentssql, $params) as $comment) {
                $comments[] = ['comment' => $comment->content];
            }

            $onlinetextssql = "
                SELECT onlinetext
                  FROM {assignsubmission_onlinetext}
                 WHERE assignment = :assignmentid
                   AND submission = :submissionid
            ";
            $params = ['assignmentid' => $assignment->moduleid, 'submissionid' => $assignment->submissionid];

            $onlinetext = [];
            foreach ($DB->get_recordset_sql($onlinetextssql, $params) as $text) {
                $onlinetext[] = ['submission text' => $text->onlinetext];
            }

            $data = [
                'assignment' => $assignment->name,
                'submission time' => $assignment->timemodified,
                'submission text' => $onlinetext,
                'attempts' => $attempts,
                'comments' => $comments,
                'files' => []
            ];

            $modulecontext = \context_module::instance($assignment->cmid);
            $fileareas = [
                ['assignsubmission_onlinetext', ASSIGNSUBMISSION_ONLINETEXT_FILEAREA],
                ['assignsubmission_file', ASSIGNSUBMISSION_FILE_FILEAREA]
            ];
            foreach ($fileareas as [$component, $area]) {
                $stored = \get_file_storage()->get_area_files($modulecontext->id, $component, $area, $assignment->submissionid, "itemid, filename", false);

                foreach ($stored as $file) {
                    $data['files'][] = $export->add_file($file);
                }
            }

            $export->data[] = $data;
        }

        return $export;
    }


    /**
     * {@inheritDoc}
     */
    public static function is_countable() {
        return true;
    }


    /**
     * {@inheritDoc}
     */
    protected static function count(target_user $user, \context $context) {
        global $DB;

        $userid = $user->get_user_record()->id;
        $params = ['userid' => $userid];

        $target = 'submission';
        $module = 'assign';
        $contextjoin = self::get_activities_join($context, $module, "$target.assignment", $module);

        $filtersubmissionssql = "
            SELECT COUNT($target.id)
              FROM {assign_submission} $target
              $contextjoin
             WHERE $target.userid = :userid
               AND $module.teamsubmission = 0
        ";

        return $DB->count_records_sql($filtersubmissionssql, $params);
    }


    /**
     * Get the comma-separated string of IDs for grading instances for the given user & context.
     *
     * @param target_user $user User
     * @param \context $context Context
     * @return string Comma-separated list of advanced grading instances for the given user or -1 if there isn't any.
     */
    protected static function get_advanced_grading_instance_ids(target_user $user, \context $context): string {
        global $DB;

        $user = intval($user->id);
        $joins = item::get_activities_join($context, 'assign', 'ass_gr.assignment', 'assign');
        $aggsql = $DB->sql_group_concat("grid", ', ');

        return ($agg = $DB->get_field_sql(
            "SELECT ({$aggsql}) 
                  FROM 
                    (SELECT id as grid
                     FROM {grading_instances} gr_inst
                     WHERE itemid IN
                      (SELECT ass_gr.id
                       FROM {assign_grades} ass_gr $joins
                       WHERE ass_gr.userid = {$user}
                      )
                    ) g")) ? $agg : '-1';
    }


    /**
     * Get the advanced grading fillings for the specified table
     *
     * @param string $table Table name, currently: gradingform_guide_fillings or gradingform_rubric_fillings
     * @param string $ids List of comma-separated instance IDs returned by static::get_advanced_grading_instance_ids()
     * @return array of records.
     */
    public static function get_advanced_grading_fillings($table, $ids): array {
        global $DB;

        switch ($table) {
            case 'gradingform_guide_fillings':
                $select = implode(', ', [
                        'src.*',
                        'criteria.shortname as criterion_name',
                        'criteria.description',
                        'criteria.descriptionmarkers as markers',
                        'criteria.maxscore as max_score',
                ]);
                $join = "JOIN {gradingform_guide_criteria} as criteria ON src.criterionid = criteria.id";
                break;

            case 'gradingform_rubric_fillings':
                $select = implode(', ', [
                    'src.*',
                    'criteria.description',
                    'levels_.score as score',
                    '(SELECT MAX(score) FROM {gradingform_rubric_levels} l WHERE l.criterionid = src.criterionid) as max_score',
                ]);
                $join = "JOIN {gradingform_rubric_criteria} as criteria ON src.criterionid = criteria.id " .
                        "JOIN {gradingform_rubric_levels} as levels_ ON src.levelid = levels_.id";
                break;

            default:
                $select = '*';
                $join = '';
                break;
        }

        return $DB->get_records_sql("
                  SELECT {$select}, instances.itemid as item_id
                  FROM {{$table}} src
                    JOIN {grading_instances} instances ON instances.id = src.instanceid
                    {$join}
                  WHERE src.instanceid IN ($ids)");
    }


    /**
     * Takes care of the records in {grading_instances} table
     *
     * @param string $ids List of comma-separated instance IDs returned by static::get_advanced_grading_instance_ids()
     */
    protected static function purge_advanced_grading_instances($ids): void {
        global $DB;

        $DB->delete_records_select('grading_instances', "id IN ($ids)");
    }


    /**
     * Takes care of the records in the given fillings table
     *
     * @param string $table Name of the table to be taken care of
     * @param string $ids List of comma-separated instance IDs returned by static::get_advanced_grading_instance_ids()
     */
    protected static function purge_advanced_grading_fillings($table, $ids): void {
        global $DB;

        $DB->delete_records_select($table, "instanceid IN ($ids)");
    }

    /**
     * Filters and remaps rubric fillings for a given assign_grade id (Assignment submission grading attempt)
     *
     * @param array $fillings fillings returned by static::get_advanced_grading_fillings('gradingform_rubric_fillings', ...)
     * @param int $id assign_grade id
     * @return array
     */
    protected static function get_rubric_fillings_for_grading_instance($fillings, $id) {
        return array_map(function ($row) {
            return [
                'id' => $row->id,
                'level_id' => $row->levelid,
                'remark' => $row->remark,
                'criterion' => $row->description,
                'score' => $row->score,
                'max_score' => $row->max_score,
            ];
        }, array_filter($fillings, function($row) use ($id) {
            return $row->item_id == $id;
        }));
    }

    /**
     * Filters and remaps guide fillings for a given assign_grade id (Assignment submission grading attempt)
     *
     * @param array $fillings fillings returned by static::get_advanced_grading_fillings('gradingform_guide_fillings', ...)
     * @param int $id assign_grade id
     * @return array
     */
    protected static function get_guide_fillings_for_grading_instance($fillings, $id) {
        return array_map(function ($row) {
            return [
                'id' => $row->id,
                'remark' => $row->remark,
                'score' => $row->score,
                'criterion' => $row->criterion_name,
                'criterion_description' => $row->description,
                'markers' => $row->markers,
                'max_score' => $row->max_score,
            ];
        }, array_filter($fillings, function($row) use ($id) {
            return $row->item_id == $id;
        }));
    }
}
