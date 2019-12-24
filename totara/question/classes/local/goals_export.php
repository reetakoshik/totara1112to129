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
 * @author Nathan Lewis <nathan.lewis@totaralearning.com>
 * @package totara_question
 */

namespace totara_question\local;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/totara/hierarchy/prefix/goal/lib.php');

/**
 * Class export_helper.
 *
 * @package totara_question
 */
class goals_export extends review_export {

    /**
     * Gets a list of ids and names of the items that have been selected for this question.
     *
     * @param int $questfieldid
     * @return array
     */
    public function get_items(int $questfieldid, int $answerfieldid) : array {
        global $DB;

        $prefix = static::$prefix;
        $answerfield = static::$ansfield;

        $goalscopepersonal = \goal::SCOPE_PERSONAL;
        $goalkeypersonal = $DB->sql_concat('rd.itemid', "'_{$goalscopepersonal}'");

        $goalscopecompany = \goal::SCOPE_COMPANY;
        $goalkeycompany = $DB->sql_concat('rd.itemid', "'_{$goalscopecompany}'");

        $sql =
            "SELECT {$goalkeypersonal} AS id, gp.name
               FROM {{$prefix}_review_data} rd
               JOIN {goal_personal} gp ON rd.itemid = gp.id
              WHERE rd.{$prefix}questfieldid = :questfieldid1
                AND rd.{$answerfield} = :answerfieldid1
                AND rd.scope = :scopepersonal
              UNION
             SELECT {$goalkeycompany} AS id, goal.fullname AS name
               FROM {{$prefix}_review_data} rd
               JOIN {goal_record} gr ON rd.itemid = gr.id
               JOIN {goal} goal ON gr.goalid = goal.id
              WHERE rd.{$prefix}questfieldid = :questfieldid2
                AND rd.{$answerfield} = :answerfieldid2
                AND rd.scope = :scopecompany";

        $params = [
            'questfieldid1' => $questfieldid,
            'questfieldid2' => $questfieldid,
            'scopepersonal' => \goal::SCOPE_PERSONAL,
            'scopecompany' => \goal::SCOPE_COMPANY,
            'answerfieldid1' => $answerfieldid,
            'answerfieldid2' => $answerfieldid,
        ];

        return $DB->get_records_sql($sql, $params);
    }
}
