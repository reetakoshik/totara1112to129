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
 * @author Aleksandr Baishev <aleksandr.baishev@totaralearning.com>
 * @package core_grades
 */

namespace core_grades\userdata\helpers;


use totara_userdata\userdata\item;
use totara_userdata\userdata\target_user;

/**
 * User-data grades item helper class.
 * Counts, exports and purges grades and grades history for the user.
 *
 * Note: It doesn't include advanced grading comments here, as it is used only in grading assignments => it handled by
 *  "singleassignments" user-data item.
 *
 * @package core_grades\userdata
 */
class grades {

    /**
     * Moodle database object reference
     *
     * @var \moodle_database
     */
    protected $db;

    /**
     * Target user
     *
     * @var \totara_userdata\userdata\target_user
     */
    protected $user;

    /**
     * Given context
     *
     * @var \context
     */
    protected $context;

    /**
     * Array of grade_item ids related to the current export/purge/count
     *
     * @var array
     */
    protected $itemids = [];

    /**
     * Initializes database object, context and target user fields.
     * @param \context $context
     * @param \totara_userdata\userdata\target_user $user
     * @param \moodle_database|null $db
     */
    public function __construct(\context $context, target_user $user, \moodle_database $db = null) {
        if (is_null($db)) {
            global $DB;
            $db = $DB;
        }

        $this->db = $db;
        $this->context = $context;
        $this->user = $user;

        // Cache item ids.
        $this->itemids = $this->get_contextual_grade_item_ids();
    }

    /**
     * Get grade item ids, not using standard join method as grades are special and it won't work.
     *
     * @return array
     */
    protected function get_contextual_grade_item_ids(): array {
        $conditions = [];

        switch ($this->context->contextlevel) {
            case CONTEXT_MODULE:
                // So, we need to see which module is it.
                // Then filter by those things and then instance id.
                $module = $this->get_module_by_module_context();

                $conditions = [
                    'itemtype' => 'mod',
                    'itemmodule' => $module->module_name,
                    'iteminstance' => $module->instance,
                ];
                break;

            case CONTEXT_COURSE:
                $conditions['courseid'] = $this->context->instanceid;
                break;
            case CONTEXT_COURSECAT:
                $conditions['courseid'] = $this->get_course_ids_by_coursecat_context();
                break;

            case CONTEXT_SYSTEM:
            default:
                // Do nothing. No restrictions should be applied.
        }

        // Applying the conditions
        foreach ($conditions as $attribute => &$condition) {
            if (is_array($condition)) {
                if (!empty($condition)) {
                    // These are array of ids (integers, db safe)
                    // Not using get in or equal as it would insert those as params if less than some number.
                    $ids = implode(', ', $condition);
                } else {
                    $ids = '-1'; // Empty array given, so this should return an empty set.
                }
                $condition = "{$attribute} IN ({$ids})";
            } elseif (is_numeric($condition)) {
                $condition = "{$attribute} = {$condition}";
            } else {
                $condition = "{$attribute} = '{$condition}'";
            }
        }

        // Building condition
        $where = implode(' AND ', $conditions);

        if (trim($where) != '') {
            $where = "WHERE {$where}";
        };

        return array_keys($this->db->get_records_sql("SELECT id FROM {grade_items} {$where}"));
    }

    /**
     * Get IDs for the courses within the course category by context
     *
     * @param \context|null $context Context if not supplied the context stored on the class is used.
     * @return array
     */
    protected function get_course_ids_by_coursecat_context(\context $context = null): array {
        if (is_null($context)) {
            $context = $this->context;
        }
        if ($context->contextlevel !== CONTEXT_COURSECAT) {
            throw new \coding_exception('Oops, we have expected COURSE CATEGORY context, something else given. LEVEL: ' . $context->contextlevel);
        }

        return $this->db->get_fieldset_sql("
                 SELECT instanceid as instance_id
                 FROM {context} ctx
                 WHERE ctx.contextlevel = " . CONTEXT_COURSE . "
                   AND ctx.path LIKE '{$context->path}/%'");
    }


    /**
     * Get grades from the database
     *
     * @param array|null $itemids Array of item IDs or null to get stored on the class array of ids
     * @param bool $historical A flag whether to return historical or actual grades
     * @return array
     */
    protected function get_grades(array $itemids = null, bool $historical = false): array {
        if (is_null($itemids)) {
            $itemids = $this->itemids;
        }

        $table = 'grade_grades' . ($historical ? '_history' : '');
        $itemids = $this->comma_separate_item_ids($itemids);
        $user = intval($this->user->id);

        return $this->db->get_records_sql(
            "SELECT grades_.*, grade_items.itemname as name, course_.fullname as course_name
                  FROM {{$table}} grades_
                  JOIN {grade_items} grade_items ON grade_items.id = grades_.itemid
                  JOIN {course} course_ ON course_.id = grade_items.courseid
                  WHERE grades_.itemid IN ({$itemids}) AND grades_.userid = {$user}
                  ORDER BY grades_.timemodified");
    }

    /**
     * Get historical grades from the database
     *
     * @param array|null $itemids Array of item IDs or null to get stored on the class array of ids
     * @return array
     */
    protected function get_historical_grades(array $itemids = null): array {
        return $this->get_grades($itemids, true);
    }

    /**
     * Filter historical grade only for the specified item id.
     *
     * @param int $id Item id
     * @param array|null $grades Array of grades fetched from the database
     * @return array
     */
    protected function get_historical_grades_for_item($id, array $grades = null): array {
        if (is_null($grades)) {
            $grades = $this->get_historical_grades();
        }

        return array_filter($grades, function ($row) use ($id) {
            return $row->itemid == $id;
        });
    }

    /**
     * Get module object by the context.
     *
     * @param \context|null $context Context object or null to get one from the class
     * @return null|\stdClass
     */
    protected function get_module_by_module_context(\context $context = null): ?\stdClass {
        if (is_null($context)) {
            $context = $this->context;
        }
        if ($context->contextlevel !== CONTEXT_MODULE) {
            throw new \coding_exception('Oops, we have expected COURSE MODULE context, something else given. LEVEL: ' . $context->contextlevel);
        }

        $instance = intval($context->instanceid);

        return ($module = $this->db->get_record_sql(
            "SELECT course_modules.*, modules_.name as module_name
                  FROM {course_modules} as course_modules
                  JOIN {modules} modules_ ON modules_.id = course_modules.module
                  WHERE course_modules.id = {$instance}")) ? $module : null; // Reads as 'or null' if db call evaluates to false.
    }

    /**
     * Remap grade objects from the database for the export
     *
     * @param \stdClass $row Grade object (one row from the db)
     * @param array|null $historical Historical values grabbed from the database
     * @return array
     */
    protected function process_grade_export(\stdClass $row, array $historical = null) {
        $output = [
            'id' => intval($row->id),
            'course' => $row->course_name,
            'activity' => $row->name,
            'user_id' => intval($row->userid),
            'raw_grade' => floatval($row->rawgrade),
            'raw_grade_min' => floatval($row->rawgrademin),
            'raw_grade_max' => floatval($row->rawgrademax),
            'final_grade' => floatval($row->finalgrade),
            'feedback' => $row->feedback,
            'information' => $row->information,
            'created_at' => empty($row->timecreated) ? null : intval($row->timecreated),
            'modified_at' => empty($row->timemodified) ? null : intval($row->timemodified),
        ];

        if (!is_null($historical)) {
            $output['history'] = array_map(function($row) {
                return $this->process_grade_export($row, null);
            }, $this->get_historical_grades_for_item($row->itemid, $historical));
        }

        return $output;
    }

    /**
     * Perform data purge for this item
     *
     * @return int
     */
    public function purge(): int {
        // If these are empty, there is no data to purge.
        if (!empty($this->itemids)) {
            $items = $this->comma_separate_item_ids($this->itemids);

            $user = intval($this->user->id);
            $sql = "itemid IN ({$items}) AND userid = {$user}";

            $tables = [
                'grade_grades',
                'grade_grades_history'
            ];

            foreach ($tables as $table) {
                // No point of returning value from delete_records_select, it always returns true.
                $this->db->delete_records_select($table, $sql);
            }
        }

        return item::RESULT_STATUS_SUCCESS;
    }

    /**
     * Perform user data export
     *
     * @return array
     */
    public function export() {
        $historical = $this->get_historical_grades();
        return array_map(function($row) use ($historical) {
            return $this->process_grade_export($row, $historical);
        }, $this->get_grades());
    }

    /**
     * Perform data count
     *
     * @return int
     */
    public function count(): int {
        $ids = $this->comma_separate_item_ids($this->itemids);
        $userid = intval($this->user->id);

        return $this->db->count_records_select('grade_grades', "itemid IN ({$ids}) and userid = {$userid}");
    }

    /**
     * Return sub-query friendly list of comma-separated ids generated from an array
     *
     * @param array $itemids
     * @return array|string
     */
    protected function comma_separate_item_ids(array $itemids): string {
        return !empty($itemids) ? implode(', ', array_map('intval', $itemids)) : '-1';
    }
}