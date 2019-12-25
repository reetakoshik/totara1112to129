<?php
/*
 * This file is part of Totara LMS
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
 * @author Jo Jones <jo.jones@kineo.com>
 * @package totara_cohort
 */

namespace totara_cohort;

defined('MOODLE_INTERNAL') || die();

use stdClass;
use cohort;

/**
 * This class traverses cohorts dependencies of other cohorts and sorts them in order from inner to
 * outer level.
 */
final class cohort_dependency_helper {
    /**
     * @var array
     */
    private static $cohortdata;

    /**
     * Order cohorts by their dependencies (less dependencies first)
     * @param stdClass[] $cohorts
     * @return stdClass[]
     */
    public static function order_cohorts(array $cohorts): array {
        self::$cohortdata = [];

        foreach ($cohorts as $cohort) {
            $processed = [];
            self::get_nested_cohorts($cohort->id, 1, $processed);
        }

        $colchildren  = array_column(self::$cohortdata, 'children');
        $collevel = array_column(self::$cohortdata, 'level');
        array_multisort($collevel, SORT_DESC, $colchildren, SORT_ASC, self::$cohortdata);

        if (debugging() && !PHPUNIT_TEST) {
            mtrace("Cohort Priority Ordering:");
        }

        $sortedcohorts = [];
        foreach (self::$cohortdata as $cohort) {
            $sortedcohorts[] = $cohorts[$cohort['id']];

            if (debugging() && !PHPUNIT_TEST) {
                mtrace("Cohort {$cohort['id']}, Level {$cohort['level']}, Children {$cohort['children']}");
            }
        }

        return $sortedcohorts;
    }

    /**
     * Recursively fetch cohorts that current cohort depends on
     * @param int   $cohortid
     * @param int   $level
     * @param array $processed
     * @return stdClass[]
     */
    protected static function get_nested_cohorts(int $cohortid, int $level, array $processed): array {
        global $DB;

        $processed[] = $cohortid;
        $statusactive = COHORT_COL_STATUS_ACTIVE;
        $cohorttype = cohort::TYPE_DYNAMIC;

        if (!isset(self::$cohortdata[$cohortid])) {
            $sql = "
                SELECT DISTINCT child.id
                FROM {cohort} c
                INNER JOIN {cohort_rule_collections} crc 
                  ON c.id = crc.cohortid AND crc.status = {$statusactive}
                INNER JOIN {cohort_rulesets} crs 
                  ON crc.id = crs.rulecollectionid
                INNER JOIN {cohort_rules} cr 
                  ON crs.id = cr.rulesetid AND cr.ruletype = 'cohort' AND cr.name = 'cohortmember'
                INNER JOIN {cohort_rule_params} crp 
                  ON cr.id = crp.ruleid AND crp.name = 'cohortids'
                INNER JOIN {cohort} child
                  ON child.id = " . $DB->sql_cast_char2int('crp.value') . "
                WHERE c.id = :cohortid
                  AND child.cohorttype = {$cohorttype}
            ";

            $cohorts = $DB->get_records_sql($sql, [ 'cohortid' => $cohortid ]);
            self::$cohortdata[$cohortid] = [
                'id' => $cohortid,
                'cohorts' => $cohorts,
                'children' => count($cohorts),
                'level' => $level
            ];
        } else {
            $cohorts = self::$cohortdata[$cohortid]['cohorts'];
        }

        if ($level > self::$cohortdata[$cohortid]['level']) {
            self::$cohortdata[$cohortid]['level'] = $level;
        }

        if ($cohorts) {
            foreach ($cohorts as $cohort) {
                if (!in_array($cohort->id, $processed)) {
                    self::get_nested_cohorts($cohort->id, $level + 1, $processed);
                }
            }
        }

        return $cohorts;
    }
}