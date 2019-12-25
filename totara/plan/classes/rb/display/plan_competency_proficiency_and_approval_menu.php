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
 * Displays the competency's proficiency/approval status, and if the current user would have permission
 * to change the competency's status via the competency page of the learning plan, it gives them
 * a drop-down menu to change the status, which saves changes via Javascript
 *
 * @author Simon Player <simon.player@totaralearning.com>
 * @package totara_plan
 */
class plan_competency_proficiency_and_approval_menu extends base {

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
        global $CFG, $DB;

        // Needed for approval constants.
        require_once($CFG->dirroot . '/totara/plan/lib.php');

        $extrafields = self::get_extrafields_row($row, $column);
        $isexport = ($format !== 'html');

        if ($isexport) {
          return $value;
        }

        $content = array();
        $approved = isset($extrafields->approved) ? $extrafields->approved : null;
        $compframeworkid = isset($extrafields->compframeworkid) ? $extrafields->compframeworkid : null;
        $planid = isset($extrafields->planid) ? $extrafields->planid : null;
        $compevscalevalueid = isset($extrafields->compevscalevalueid) ? $extrafields->compevscalevalueid : null;
        $plancompid = isset($extrafields->plancompid) ? $extrafields->plancompid : null;
        $competencyid = isset($extrafields->competencyid) ? $extrafields->competencyid : null;

        if (!$planid) {
            return $value;
        } else {
            if (array_key_exists($planid, $report->src->dp_plans)) {
                $plan = $report->src->dp_plans[$planid];
            } else {
                $plan = new \development_plan($planid);
                $report->src->dp_plans[$planid] = $plan;
            }

            $competencycomponent = $plan->get_component('competency');
            if ($competencycomponent->can_update_competency_evidence($extrafields)) {

                // Get the info we need about the framework
                if (array_key_exists( $compframeworkid, $report->src->compscales)) {
                    $compscale = $report->src->compscales[$compframeworkid];
                } else {
                    $sql = "SELECT
                                cs.defaultid as defaultid, cs.id as scaleid
                            FROM {comp} c
                            JOIN {comp_scale_assignments} csa
                                ON c.frameworkid = csa.frameworkid
                            JOIN {comp_scale} cs
                                ON csa.scaleid = cs.id
                            WHERE c.id= ?";
                    $scaledetails = $DB->get_record_sql($sql, array($competencyid));
                    $formatscale = $DB->get_records_menu('comp_scale_values', array('scaleid' => $scaledetails->scaleid), 'sortorder');

                    $compscale = array();
                    foreach ($formatscale as $key => $scale) {
                        $compscale[$key] = format_string($scale);
                    }
                    $report->src->compscales[$compframeworkid] = $compscale;
                }

                $label = \html_writer::label(get_string('statusof', 'totara_plan', $extrafields->compfullname), 'menucompetencyevidencestatus' . $plancompid, '', array('class' => 'sr-only'));
                $action = "var response; ".
                    "response = \$.get(".
                    "'{$CFG->wwwroot}/totara/plan/components/competency/update-competency-setting.php".
                    "?sesskey=" . sesskey() .
                    "&competencyid={$competencyid}" .
                    "&planid={$planid}".
                    "&prof=' + $(this).val()".
                    "); ";
                $attributes = array('onchange' => $action);
                $content[] = $label . \html_writer::select($compscale,
                        'competencyevidencestatus'.$plancompid,
                        $compevscalevalueid,
                        array(($compevscalevalueid ? '' : 0) => ($compevscalevalueid ? '' : get_string('notset', 'totara_hierarchy'))),
                        $attributes);
            } else if ($value) {
                $content[] = $value;
            }
        }

        // Highlight if the item has not yet been approved.
        if ($approved != DP_APPROVAL_APPROVED) {
            $itemstatus = \totara_plan\rb\display\plan_item_status::display($approved, $format, $extrafields, $column, $report);
            if ($itemstatus) {
                $content[] = $itemstatus;
            }
        }
        return implode(\html_writer::empty_tag('br'), $content);

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
