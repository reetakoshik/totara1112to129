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
 * @author Simon Player <simon.player@totaralearning.com>
 * @package totara_plan
 */

namespace totara_plan\rb\display;
use totara_reportbuilder\rb\display\base;

/**
 * Display class intended for an icon linked to the "add competency evidence" page for the competency
 *
 * @author Simon Player <simon.player@totaralearning.com>
 * @package totara_plan
 */
class plan_competency_edit_status extends base {

    /**
     * Handles the display
     *
     * @param string $value
     * @param string $format
     * @param \stdClass $row
     * @param \rb_column $column
     * @param \reportbuilder $report
     * @return string
     */
    public static function display($value, $format, \stdClass $row, \rb_column $column, \reportbuilder $report) {
        $extrafields = self::get_extrafields_row($row, $column);

        $planid = isset($extrafields->planid) ? $extrafields->planid : null;
        if ($planid) {

            // Store the plan object so that we don't have to generate one for each row
            // of the report
            if (array_key_exists($planid, $report->src->dp_plans)) {
                $plan = $report->src->dp_plans[$planid];
            } else {
                $plan = new \development_plan($planid);
                $report->src->dp_plans[$planid] = $plan;
            }

            $competencycomponent = $plan->get_component('competency');

            $extrafields->competencyid = $value;

            return $competencycomponent->display_comp_add_evidence_icon($extrafields, qualified_me());
        }
    }

    /**
     * Is this column graphable?
     *
     * @param \rb_column $column
     * @param \rb_column_option $option
     * @param \reportbuilder $report
     * @return bool
     */
    public static function is_graphable(\rb_column $column, \rb_column_option $option, \reportbuilder $report) {
        return false;
    }
}
