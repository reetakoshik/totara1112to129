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
 * @author Alastair Munro <alastair.munro@totaralearning.com>
 * @package totara_plan
 */

namespace totara_plan\userdata;

use totara_userdata\userdata\item;
use totara_userdata\userdata\target_user;
use totara_userdata\userdata\export;

require_once($CFG->dirroot . '/totara/plan/lib.php');

defined('MOODLE_INTERNAL') || die();

/**
 * User data item for learning plans
 */
class plan extends item {

    /**
     * Can user data for this data item be exported from the system?
     *
     * @return bool
     */
    public static function is_exportable() {
        return true;
    }

    /**
     * Export user data for this item
     *
     * @param target_user $user
     * @param \context $context
     *
     * @return export
     */
    public static function export(target_user $user, \context $context) {
        global $DB, $TEXTAREA_OPTIONS;

        $export = new export();

        $statuses = [DP_PLAN_STATUS_UNAPPROVED, DP_PLAN_STATUS_PENDING,
                 DP_PLAN_STATUS_APPROVED, DP_PLAN_STATUS_COMPLETE];

        $plans = \dp_get_plans($user->id, $statuses);

        $priorityvaluecache = array();

        $fs = get_file_storage();

        foreach ($plans as $planrecord) {
            $plan = new \development_plan($planrecord->id);

            $planexport = new \stdClass();
            $planexport->name = $plan->name;
            $planexport->description = $plan->description;

            $files = $fs->get_area_files($TEXTAREA_OPTIONS['context']->id, 'totara_plan', 'dp_plan', $plan->id, 'timemodified', false);
            foreach ($files as $file) {
                $planexport->files[] = $export->add_file($file);
            }

            $planexport->startdate = $plan->startdate;
            $planexport->enddate = $plan->enddate;
            $planexport->status = $plan->status;
            $planexport->timecompleted = $plan->timecompleted;

            $planexport->comments = self::export_comments('totara_plan', 'plan_overview', $plan->id, $context);

            $plancomponents = $plan->get_components();

            foreach ($plancomponents as $componentname => $component) {
                $componentdata = new \stdClass();
                $componentdata->component = $componentname;
                $componentdata->items = array();

                $assigneditems = $component->get_assigned_items(null, 'id');

                foreach ($assigneditems as $assigneditem) {
                    $itemexport = new \stdClass();
                    $itemexport->id = $assigneditem->id;
                    $itemexport->name = $assigneditem->name;

                    $commentarea = 'plan_' . $componentname . '_item';
                    $itemcomments = self::export_comments('totara_plan', $commentarea, $assigneditem->id, $context);
                    if (!empty($itemcomments)) {
                        // Only add comments if there are some
                        $itemexport->comments = $itemcomments;
                    }

                    // Add linked items
                    $linked = self::export_linked_items($componentname, $assigneditem->id);
                    $itemexport->linkeditems = $linked;

                    // Add linked evidence
                    $linkedevidence_params = [
                        'component' => $componentname,
                        'itemid' => $assigneditem->id
                    ];
                    $linkedevidence  = $DB->get_records('dp_plan_evidence_relation', $linkedevidence_params);
                    $itemexport->linkedevidence = array_values($linkedevidence);

                    // Add component specific stuff to the export data.
                    switch ($componentname) {
                        case 'course':
                            // Course completion
                            $course_completion_data = $DB->get_record('course_completions', ['userid' => $user->id, 'course' => $assigneditem->id]);
                            if ($course_completion_data) {
                                // Completion status and RPL
                                $itemexport->completionstatus = $course_completion_data->status;
                                $itemexport->rpl = $course_completion_data->rpl;
                            }
                            $itemexport->duedate = $assigneditem->duedate;
                            break;
                        case 'competency':
                            $itemexport->proficiencyid = $assigneditem->profscalevalueid;
                            $itemexport->status = $assigneditem->status;
                            $itemexport->sort = $assigneditem->profsort;

                            // Lookup priority
                            if (!isset($priorityvaluecache[$assigneditem->priority])) {
                                $priorityvaluecache[$assigneditem->priority] = $DB->get_field('dp_priority_scale_value', 'name', ['id' => $assigneditem->priority]);
                            }
                            $itemexport->priority = $priorityvaluecache[$assigneditem->priority] ? $priorityvaluecache[$assigneditem->priority] : null;

                            break;
                        case 'objective':
                            $itemexport->description = $assigneditem->description;

                            $files = $fs->get_area_files($TEXTAREA_OPTIONS['context']->id, 'totara_plan', 'dp_plan_objective', $assigneditem->id, 'timemodified', false);
                            foreach ($files as $file) {
                                $itemexport->files[] = $export->add_file($file);
                            }

                            $itemexport->progress = $assigneditem->progress;
                            $itemexport->achieved = $assigneditem->achieved;

                            // Lookup priority
                            if (!isset($priorityvaluecache[$assigneditem->priority])) {
                                $priorityvaluecache[$assigneditem->priority] = $DB->get_field('dp_priority_scale_value', 'name', ['id' => $assigneditem->priority]);
                            }
                            $itemexport->priority = $priorityvaluecache[$assigneditem->priority] ? $priorityvaluecache[$assigneditem->priority] : null;

                            break;
                        case 'program':
                            $itemexport->duedate = $assigneditem->duedate;
                            break;
                        default:
                            break;
                    }

                    $componentdata->items[] = $itemexport;
                }
                $planexport->componentinfo[$componentdata->component] = $componentdata;
            }
            $export->data[] = $planexport;
        }

        unset($priorityvaluecache);

        return $export;
    }

    /**
     * Can user data for this data item be purged?
     *
     * @param int $userstatus target_user::STATUS_ACTIVE, target_user::STATUS_DELETED, target_user::STATUS_SUSPENDED
     * @return bool
     */
    public static function is_purgeable(int $userstatus) {
        return true;
    }

    /**
     * Purge plan records for specified user.
     *
     * @param target_user $user
     * @param \context $context
     *
     * @return self::RESULT_STATUS_SUCCESS if successful
     */
    public static function purge(target_user $user, \context $context) {
        global $CFG, $DB;

        require_once($CFG->dirroot . '/comment/lib.php');

        $planids = $DB->get_fieldset_select('dp_plan', 'id', 'userid = :userid', ['userid' => $user->id]);
        $contextsystem = \context_system::instance();

        $fs = get_file_storage();

        foreach ($planids as $planid) {
            $plan = new \development_plan($planid);

            // Remove plan overview comments.
            \comment::delete_comments(['contextid' => $contextsystem->id, 'commentarea' => 'plan_overview', 'itemid' => $plan->id]);

            $fs->delete_area_files($contextsystem->id, 'totara_plan', 'dp_plan', $plan->id);

            $plancomponents = $plan->get_components();

            foreach ($plancomponents as $componentname => $component) {

                $assigneditems = $component->get_assigned_items(null);
                foreach ($assigneditems as $item) {
                    if ($componentname == 'objective') {
                        // Delete any files that could be embedded in the objective description.
                        $fs->delete_area_files($contextsystem->id, 'totara_plan', 'dp_plan_objective', $item->id);

                    }

                    \comment::delete_comments(['contextid' => $contextsystem->id, 'commentarea' => 'plan_'.$componentname.'_item', 'itemid' => $item->id]);
                }
            }

            $plan->delete();
        }

        return item::RESULT_STATUS_SUCCESS;
    }

    /**
     * Is this data item countable?
     *
     * @return bool
     */
    public static function is_countable() {
        return true;
    }

    /**
     * Count user data for this item.
     *
     * @param target_user $user
     * @param \context $context restriction for counting i.e., system context for everything and course context for course data
     * @return int  integer is the count >= 0, negative number is error result self::RESULT_STATUS_ERROR or self::RESULT_STATUS_SKIPPED
     */
    public static function count(target_user $user, \context $context) {
        global $DB;

        return $DB->count_records('dp_plan', ['userid' => $user->id]);
    }

    /**
     * Export comments
     *
     * @param string $component
     * @param String $commentarea
     * @param int $itemid
     * @param \context $content
     *
     * @return array An array of comments to export
     */
    private static function export_comments($component, $commentarea, $itemid, \context $context) {
        global $DB;

        $params = [
            'contextid' => $context->id,
            'component' => $component,
            'commentarea' => $commentarea,
            'itemid' => $itemid
        ];

        $usernamefields = get_all_user_name_fields(true, 'u');

        $sql = "SELECT c.*, {$usernamefields} FROM {comments} c JOIN {user} u on c.userid = u.id WHERE
            c.contextid = :contextid AND
            c.component = :component AND
            c.commentarea = :commentarea AND
            c.itemid = :itemid
            ORDER BY c.timecreated ASC";

        $comment_records = $DB->get_records_sql($sql, $params);

        $comments = [];
        $user_cache = [];

        foreach ($comment_records as $record) {
            $newcomment = new \stdClass();
            $newcomment->content = $record->content;

            // Get name of commenter.
            if (!isset($user_cache[$record->userid])) {
                $fullname = fullname($record);

                $newcomment->commenter = $fullname;

                // Add to cache
                $user_cache[$record->userid] = $fullname;
            } else {
                $newcomment->commenter = $user_cache[$record->userid];
            }

            $comments[] = $newcomment;
        }

        return $comments;
    }

    /**
     * Export items linked to specified item
     *
     * @param String $component
     * @param int $itemid
     *
     * @return array Array of linked items to export
     */
    private static function export_linked_items($component, $itemid) {
        global $DB;

        $sql = "SELECT * FROM {dp_plan_component_relation}
                 WHERE (component1 = :component1 AND itemid1 = :itemid1)
                    OR (component2 = :component2 AND itemid2 = :itemid2)";

        $params = [
            'component1' => $component,
            'itemid1' => $itemid,
            'component2' => $component,
            'itemid2' => $itemid
        ];

        $linked_items = $DB->get_records_sql($sql, $params);

        $linked_data = [];

        foreach ($linked_items as $item) {
            $data = new \stdClass();
            $data->component1 = $item->component1;
            $data->itemid1 = $item->itemid1;
            $data->component2 = $item->component2;
            $data->itemid2 = $item->itemid2;
            $data->mandatory = $item->mandatory;

            $linked_data[] = $data;
        }

        return $linked_data;
    }
}
