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

trait location_report_trait {

    /**
     * Adds location custom field table to the $joinlist array
     *
     * @param \stdClass $cf_info Object containing information about this custom field
     * @param array     $joinlist
     */
    protected function add_totara_customfield_location_tables(\stdClass $cf_info, array &$joinlist) {
        $joinname = "{$cf_info->prefix}_{$cf_info->id}{$cf_info->suffix}";
        $joinlist[] = new \rb_join(
            $joinname,
            'LEFT',
            "{{$cf_info->prefix}_info_data}",
            "{$joinname}.{$cf_info->joinfield} = {$cf_info->join}.id AND {$joinname}.fieldid = {$cf_info->id}",
            REPORT_BUILDER_RELATION_ONE_TO_ONE,
            $cf_info->join
        );
    }

    /**
     * Adds location custom field to the $columnoptions array
     *
     * @param \stdClass $cf_info Object containing information about this custom field
     * @param array     $columnoptions
     */
    protected function add_totara_customfield_location_columns(\stdClass $cf_info, array &$columnoptions) {
        $name = isset($cf_info->fullname) ? $cf_info->fullname : $cf_info->name;
        $joinname = "{$cf_info->prefix}_{$cf_info->id}{$cf_info->suffix}";

        $columnoptions[] = new \rb_column_option(
            $cf_info->prefix,
            "custom_field_{$cf_info->id}{$cf_info->suffix}",
            $name,
            "{$joinname}.data",
            [
                'joins'        => $joinname,
                'displayfunc'  => 'customfield_location',
                'outputformat' => 'text',
            ]
        );
    }

    /**
     * Adds location custom field to the $filteroptions array
     *
     * @param \stdClass $cf_info Object containing information about this custom field
     * @param array     $filteroptions
     */
    protected function add_totara_customfield_location_filters(\stdClass $cf_info, array &$filteroptions) {
        $name = isset($cf_info->fullname) ? $cf_info->fullname : $cf_info->name;
        $filteroptions[] = new \rb_filter_option(
            $cf_info->prefix,
            "custom_field_{$cf_info->id}{$cf_info->suffix}",
            $name,
            'text',
            []
        );
    }
}
