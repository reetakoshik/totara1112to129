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

/**
 * Class export_helper.
 *
 * @package totara_question
 */
class compfromplan_export extends review_export {

    /**
     * Gets a list of ids and names of the items that have been selected for this question.
     *
     * @param int $questfieldid
     * @param int $answerfieldid
     * @return array
     */
    public function get_items(int $questfieldid, int $answerfieldid) : array {
        global $DB;

        $prefix = static::$prefix;
        $answerfield = static::$ansfield;

        $key = $DB->sql_concat('rd.itemid', "'_0'");
        $sql =
            "SELECT DISTINCT {$key} AS id, comp.fullname AS name
               FROM {{$prefix}_review_data} rd
               JOIN {dp_plan_competency_assign} pca ON rd.itemid = pca.id
               JOIN {comp} comp ON pca.competencyid = comp.id
              WHERE rd.{$prefix}questfieldid = :questfieldid
                AND rd.{$answerfield} = :answerfieldid";

        return $DB->get_records_sql($sql, ['questfieldid' => $questfieldid, 'answerfieldid' => $answerfieldid]);
    }
}
