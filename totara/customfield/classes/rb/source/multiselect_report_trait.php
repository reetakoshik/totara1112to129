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

trait multiselect_report_trait {

    /**
     * Adds multiselect custom field table to the $joinlist array
     *
     * @param \stdClass $cf_info Object containing information about this custom field
     * @param array     $joinlist
     *
     * @throws \coding_exception
     */
    protected function add_totara_customfield_multiselect_tables(\stdClass $cf_info, array &$joinlist) {
        global $DB;

        $joinname = "{$cf_info->prefix}_{$cf_info->id}{$cf_info->suffix}";
        $jsondata = $DB->sql_cast_2char('cfid.data');
        $data = $DB->sql_group_concat_unique($DB->sql_cast_2char('cfidp.value'), '|');

        $joinlist[] = new \rb_join(
            $joinname,
            'LEFT',
            '(SELECT ' . $data . ' AS data, cfid.' . $cf_info->joinfield . ' AS joinid, ' . $jsondata . ' AS jsondata
                            FROM {' . $cf_info->prefix . '_info_data} cfid
                            LEFT JOIN {' . $cf_info->prefix . '_info_data_param} cfidp ON (cfidp.dataid = cfid.id)
                           WHERE cfid.fieldid = ' . $cf_info->id . '
                           GROUP BY cfid.' . $cf_info->joinfield . ', ' . $jsondata . ')',
            "$joinname.joinid = {$cf_info->join}.id ",
            REPORT_BUILDER_RELATION_ONE_TO_ONE,
            $cf_info->join
        );
    }

    /**
     * Adds multiselect custom field to the $columnoptions array
     *
     * @param \stdClass $cf_info Object containing information about this custom field
     * @param array     $columnoptions
     *
     * @throws \coding_exception
     */
    protected function add_totara_customfield_multiselect_columns(\stdClass $cf_info, array &$columnoptions) {
        $joinname = "{$cf_info->prefix}_{$cf_info->id}{$cf_info->suffix}";
        $value = "custom_field_{$cf_info->id}{$cf_info->suffix}";
        $name = isset($cf_info->fullname) ? $cf_info->fullname : $cf_info->name;

        $columnoptions[] = new \rb_column_option(
            $cf_info->prefix,
            $value . '_icon',
            get_string('multiselectcolumnicon', 'totara_customfield', $name),
            "$joinname.data",
            [
                'joins'          => $joinname,
                'displayfunc'    => 'customfield_multiselect_icon',
                'extrafields'    => [
                    "{$cf_info->prefix}_{$value}_icon_json" => "{$joinname}.jsondata",
                ],
                'defaultheading' => $name,
            ]
        );

        $columnoptions[] = new \rb_column_option(
            $cf_info->prefix,
            $value . '_text',
            get_string('multiselectcolumntext', 'totara_customfield', $name),
            "$joinname.data",
            [
                'joins'          => $joinname,
                'displayfunc'    => 'customfield_multiselect_text',
                'extrafields'    => [
                    "{$cf_info->prefix}_{$value}_text_json" => "{$joinname}.jsondata",
                ],
                'defaultheading' => $name,
            ]
        );
    }

    /**
     * Adds multiselect custom field to the $filteroptions array
     *
     * @param \stdClass $cf_info Object containing information about this custom field
     * @param array     $filteroptions
     *
     * @throws \coding_exception
     */
    protected function add_totara_customfield_multiselect_filters(\stdClass $cf_info, array &$filteroptions) {
        global $CFG;

        require_once($CFG->dirroot . '/totara/customfield/definelib.php');
        require_once($CFG->dirroot . '/totara/customfield/field/multiselect/field.class.php');
        require_once($CFG->dirroot . '/totara/customfield/field/multiselect/define.class.php');

        $cfield = new \customfield_define_multiselect();
        $cfield->define_load_preprocess($cf_info);

        $name = isset($cf_info->fullname) ? $cf_info->fullname : $cf_info->name;

        $filter_options = [
            'concat'     => true,
            'simplemode' => true,
        ];

        $selectchoices = [];
        foreach ($cf_info->multiselectitem as $selectchoice) {
            $selectchoices[md5($selectchoice['option'])] = format_string($selectchoice['option']);
        }
        $filter_options['selectchoices'] = $selectchoices;
        $filter_options['showcounts'] = [
            'joins'      => [
                "LEFT JOIN (SELECT id, {$cf_info->joinfield} FROM {{$cf_info->prefix}_info_data} " .
                "WHERE fieldid = {$cf_info->id}) {$cf_info->prefix}_idt_{$cf_info->id} " .
                "ON base_{$cf_info->prefix}_idt_{$cf_info->id} = {$cf_info->prefix}_idt_{$cf_info->id}.{$cf_info->joinfield}",
                "LEFT JOIN {{$cf_info->prefix}_info_data_param} {$cf_info->prefix}_idpt_{$cf_info->id} " .
                "ON {$cf_info->prefix}_idt_{$cf_info->id}.id = {$cf_info->prefix}_idpt_{$cf_info->id}.dataid",
            ],
            'basefields' => [
                "{$cf_info->join}.id AS base_{$cf_info->prefix}_idt_{$cf_info->id}",
            ],
            'basegroups' => [
                "{$cf_info->join}.id",
            ],
            'dependency' => $cf_info->join,
            'dataalias'  => "{$cf_info->prefix}_idpt_{$cf_info->id}",
            'datafield'  => "value",
        ];

        $filteroptions[] = new \rb_filter_option(
            $cf_info->prefix,
            'custom_field_' . $cf_info->id . $cf_info->suffix . '_text',
            get_string('multiselectcolumntext', 'totara_customfield', $name),
            'multicheck',
            $filter_options
        );

        $iconselectchoices = [];
        foreach ($cf_info->multiselectitem as $selectchoice) {
            $iconselectchoices[md5($selectchoice['option'])] =
                \customfield_multiselect::get_item_string(format_string($selectchoice['option']), $selectchoice['icon'], 'list-icon');
        }
        $filter_options['selectchoices'] = $iconselectchoices;
        $filter_options['showcounts'] = [
            'joins'      => [
                "LEFT JOIN (SELECT id, {$cf_info->joinfield} FROM {{$cf_info->prefix}_info_data} " .
                "WHERE fieldid = {$cf_info->id}) {$cf_info->prefix}_idi_{$cf_info->id} " .
                "ON base_{$cf_info->prefix}_idi_{$cf_info->id} = {$cf_info->prefix}_idi_{$cf_info->id}.{$cf_info->joinfield}",
                "LEFT JOIN {{$cf_info->prefix}_info_data_param} {$cf_info->prefix}_idpi_{$cf_info->id} " .
                "ON {$cf_info->prefix}_idi_{$cf_info->id}.id = {$cf_info->prefix}_idpi_{$cf_info->id}.dataid",
            ],
            'basefields' => [
                "{$cf_info->join}.id AS base_{$cf_info->prefix}_idi_{$cf_info->id}",
            ],
            'basegroups' => [
                "{$cf_info->join}.id",
            ],
            'dependency' => $cf_info->join,
            'dataalias'  => "{$cf_info->prefix}_idpi_{$cf_info->id}",
            'datafield'  => "value",
        ];

        $filteroptions[] = new \rb_filter_option(
            $cf_info->prefix,
            'custom_field_' . $cf_info->id . $cf_info->suffix . '_icon',
            get_string('multiselectcolumnicon', 'totara_customfield', $name),
            'multicheck',
            $filter_options
        );
    }
}
