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
 * @author Yuliya Bozhko <yuliya.bozhko@totaralearning.com>
 * @package totara_customfield
 */

namespace totara_customfield\rb\source;

defined('MOODLE_INTERNAL') || die();

trait report_trait {

    use multiselect_report_trait,
        text_report_trait,
        textarea_report_trait,
        location_report_trait,
        menu_report_trait,
        datetime_report_trait,
        date_report_trait,
        checkbox_report_trait,
        file_report_trait,
        url_report_trait;

    /**
     * Function to add all customfield-related information to the base source.
     *
     * @throws \ReportBuilderException
     * @throws \coding_exception
     */
    protected function add_totara_customfield_base() {
        // Create array to store the join functions and join table.
        $joindata = [];
        $base = $this->base;
        // If any of the join tables are customfield-related, ensure the custom fields are added.
        foreach ($this->joinlist as $join) {
            // Tables can be joined multiple times so we set elements of an associative array as join => extradata.
            $table = $join->table;
            switch ($table) {
                case '{user}':
                    if ($join->name !== 'auser') {
                        break;
                    }
                    // This is a fallback only for sources that does not add user fields properly!
                    $joindata['custom_user'] = ['jointable' => 'auser', 'cf_prefix' => 'user', 'joinfield' => 'userid'];
                    break;
                case '{course}':
                    $joindata['custom_course'] = ['jointable' => 'course', 'cf_prefix' => 'course', 'joinfield' => 'courseid'];
                    break;
                case '{prog}':
                    $joindata['custom_prog'] = ['jointable' => $join->name, 'cf_prefix' => 'prog', 'joinfield' => 'programid'];
                    break;
                case '{comp}':
                    $joindata['custom_competency'] = ['jointable' => 'competency', 'cf_prefix' => 'comp_type', 'joinfield' => 'competencyid'];
                    break;
                case '{goal}':
                    $joindata['custom_goal'] = ['jointable' => 'goal', 'cf_prefix' => 'goal_type', 'joinfield' => 'goalid'];
                    break;
                case '{goal_personal}':
                    $joindata['custom_personal_goal'] = ['jointable' => 'goal_personal', 'cf_prefix' => 'goal_user', 'joinfield' => 'goal_userid'];
                    break;
                case '{dp_plan_evidence}':
                    $joindata['custom_evidence'] = ['jointable' => 'dp_plan_evidence', 'cf_prefix' => 'dp_plan_evidence', 'joinfield' => 'evidenceid'];
                    break;
            }
        }

        // Now ensure custom fields are added if there are no joins but the base table is customfield-related
        switch ($base) {
            case '{user}':
                // This is a fallback only for sources that does not add user fields properly!
                $joindata['custom_user'] = ['jointable' => 'base', 'cf_prefix' => 'user', 'joinfield' => 'userid'];
                break;
            case '{course}':
                $joindata['custom_course'] = ['jointable' => 'base', 'cf_prefix' => 'course', 'joinfield' => 'courseid'];
                break;
            case '{prog}':
                $joindata['custom_prog'] = ['jointable' => 'base', 'cf_prefix' => 'prog', 'joinfield' => 'programid'];
                break;
            case '{org}':
                $joindata['custom_organisation'] = ['jointable' => 'base', 'cf_prefix' => 'org_type', 'joinfield' => 'organisationid'];
                break;
            case '{pos}':
                $joindata['custom_position'] = ['jointable' => 'position', 'cf_prefix' => 'pos_type', 'joinfield' => 'positionid'];
                break;
            case '{comp}':
                $joindata['custom_competency'] = ['jointable' => 'competency', 'cf_prefix' => 'comp_type', 'joinfield' => 'competencyid'];
                break;
            case '{goal}':
                $joindata['custom_goal'] = ['jointable' => 'base', 'cf_prefix' => 'goal_type', 'joinfield' => 'goalid'];
                break;
            case '{goal_personal}':
                $joindata['custom_personal_goal'] = ['jointable' => 'base', 'cf_prefix' => 'goal_user', 'joinfield' => 'goal_userid'];
                break;
            case '{dp_plan_evidence}':
                $joindata['custom_evidence'] = ['jointable' => 'base', 'cf_prefix' => 'dp_plan_evidence', 'joinfield' => 'evidenceid'];
                break;
        }

        foreach ($joindata as $extrajoindata) {
            $this->add_totara_customfield_component(
                $extrajoindata['cf_prefix'], $extrajoindata['jointable'], $extrajoindata['joinfield'],
                $this->joinlist, $this->columnoptions, $this->filteroptions
            );
        }
    }

    /**
     * Generic function for adding component custom fields to the reports
     * Intentionally optimized into one function to reduce number of db queries
     *
     * @param string $cf_prefix     prefix for custom field table e.g. everything before '_info_field' or
     *                              '_info_data'
     * @param string $join          join table in joinlist used as a link to main query
     * @param string $joinfield     joinfield in data table used to link with main table
     * @param array  $joinlist      array of joins passed by reference
     * @param array  $columnoptions array of columnoptions, passed by reference
     * @param array  $filteroptions array of filters, passed by reference
     * @param string $suffix        instead of custom_field_{$id}, column name will be custom_field_{$id}{$suffix}.
     *                              Use short prefixes to avoid hiting column size limitations
     * @param bool   $nofilter      do not create filter for custom fields. It is useful when customfields are
     *                              dynamically added by column generator
     *
     * @return bool
     */
    protected function add_totara_customfield_component($cf_prefix, $join, $joinfield, array &$joinlist,
                                                        array &$columnoptions, array &$filteroptions, $suffix = '', $nofilter = false) {

        if (strlen($suffix)) {
            if (!preg_match('/^[a-zA-Z]{1,5}$/', $suffix)) {
                throw new \coding_exception('Suffix for add_custom_fields_for must be letters only up to 5 chars.');
            }
        }

        $seek = false;
        foreach ($joinlist as $object) {
            $seek = ($object->name == $join);
            if ($seek) {
                break;
            }
        }

        if ($join == 'base') {
            $seek = 'base';
        }

        if (!$seek) {
            $a = new \stdClass();
            $a->join = $join;
            $a->source = get_class($this);
            throw new \ReportBuilderException(get_string('error:missingdependencytable', 'totara_reportbuilder', $a));
        }

        if ($cf_prefix === 'user') {
            return $this->add_core_user_customfield($joinlist, $columnoptions, $filteroptions, $join, 'user', false, $nofilter);
        }

        // Check if there are any visible custom fields of this type.
        $items = \totara_customfield\report_builder_field_loader::get_visible_fields($cf_prefix);

        foreach ($items as $record) {
            // Add extra information to the record.
            $record->join = $join;
            $record->joinfield = $joinfield;
            $record->prefix = $cf_prefix;
            $record->suffix = $suffix;

            // Custom field methods adding to the joins, columns, and filters list.
            $join_function = "add_totara_customfield_{$record->datatype}_tables";
            $column_function = "add_totara_customfield_{$record->datatype}_columns";
            $filter_function = "add_totara_customfield_{$record->datatype}_filters";

            // Add each custom field.
            $this->$join_function($record, $joinlist);
            $this->$column_function($record, $columnoptions);
            if (!$nofilter) {
                $this->$filter_function($record, $filteroptions);
            }
        }

        return true;
    }
}
