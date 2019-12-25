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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package totara_cohort
 */

namespace totara_cohort;

defined('MOODLE_INTERNAL') || die();

use cohort_rule_option;
use cohort_rule_sqlhandler;
use coding_exception;

/**
 * This is the class where it checking all the valid record whether to detect it is exist or not. If it
 * is not exist, then the broken rules should be emailed out to the admin user to have a notice about
 *
 * Class cohort_broken_rules_check
 * @package totara_cohort
 */
class cohort_broken_rules_check {
    /**
     * A map of the rule type name with the record table that it is needed to be be checked against
     * Array<name, tablename>
     * @var array
     */
    private $definition;

    /**
     * cohort_broken_rules_check constructor.
     */
    public final function __construct() {
        $this->definition = array(
            'coursecompletionlist'              => 'course',
            'coursecompletiondate'              => 'course',
            'coursecompletionduration'          => 'course',
            'programcompletionlist'             => 'prog',
            'programcompletiondate'             => 'prog',
            'programcompletiondurationassigned' => 'prog',
            'programcompletiondurationstarted'  => 'prog',
            'positions'                         => 'pos',
            'organisations'                     => 'org'
        );
    }

    /**
     * Getting the list of ids from cohort_rule_sqlhandler. Since each child of class cohort_rule_sqlhandler has
     * a different definition of reference record's id list, therefore, it is needed to go thru the possible list
     * to retrieve the list
     *
     * @return int[]
     * @see cohort_rule_sqlhandler::$params
     */
    private function get_list_of_ids(cohort_rule_sqlhandler $sqlhandler) {
        $listofids = array();
        if (!empty($sqlhandler->listofids)) {
            $listofids = $sqlhandler->listofids;
        } else if (!empty($sqlhandler->lov)) {
            $listofids = $sqlhandler->lov;
        } else if (!empty($sqlhandler->listofvalues)) {
            $listofids = $sqlhandler->listofvalues;
        }
        return $listofids;
    }

    /**
     * A method to check whether this class is able to check for the rule passed by reference or not. Since there is
     * a limitation of checking base on the rule group instance
     * @param cohort_rule_option $rule
     * @return bool
     */
    public function has_checker(cohort_rule_option $rule): bool {
        return isset($this->definition[$rule->name]);
    }

    /**
     * This is the method where you checking whether the rule containing a valid method or not
     * @return bool
     */
    public function is_invalid(cohort_rule_option $rule, $ruleinstanceid): bool {
        global $DB;
        if (!$this->has_checker($rule)) {
            throw new coding_exception("There is no checker for the rule: {$rule->name}");
        }

        $tablename = $this->definition[$rule->name];
        $sqlhandler = $rule->sqlhandler;
        $sqlhandler->fetch($ruleinstanceid);
        $listofids = $this->get_list_of_ids($sqlhandler);

        list($partsql, $params) = $DB->get_in_or_equal($listofids, SQL_QUERY_SELECT);
        $sql = "SELECT COUNT(record_table.id) AS total FROM {{$tablename}} as record_table 
                WHERE record_table.id {$partsql}";

        $rs = $DB->get_record_sql($sql, $params);
        $total = (int) $rs->total;
        // Checking if the total records within the db match with the rule params or not
        return $total != count($listofids);
    }
}