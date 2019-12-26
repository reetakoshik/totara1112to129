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
 * @package totara_customfield
 * @category totara_catalog
 */

namespace totara_customfield\totara_catalog;

defined('MOODLE_INTERNAL') || die();

use totara_catalog\catalog_retrieval;
use totara_catalog\datasearch\equal;
use totara_catalog\datasearch\in_or_equal;
use totara_catalog\datasearch\like;
use totara_catalog\datasearch\like_or;
use totara_catalog\filter;
use totara_catalog\merge_select\multi;
use totara_catalog\merge_select\single;
use totara_catalog\provider;

global $CFG;

require_once($CFG->dirroot . '/totara/customfield/fieldlib.php');

class filter_factory {

    /**
     * Create filters for custom fields that belong to the plugin (identified by the $tableprefix).
     *
     * @param string $tableprefix The table prefix of the customfield
     * @param string $prefix The prefix of the custom field
     * @param provider $provider
     * @return filter[]
     */
    public static function get_filters(string $tableprefix, string $prefix, provider $provider) {
        $customfields = customfield_get_fields_definition($tableprefix, ['hidden' => 0]);

        $filters = [];

        foreach ($customfields as $customfield) {
            switch ($customfield->datatype) {
                case 'menu':
                case 'multiselect':
                case 'checkbox':
                    $filters[] = self::get_filter(filter::REGION_PANEL, $customfield, $tableprefix, $prefix, $provider);
                    $filters[] = self::get_filter(filter::REGION_BROWSE, $customfield, $tableprefix, $prefix, $provider);
                    break;
                case 'datetime':
                case 'text':
                case 'url':
                case 'file':
                case 'location':
                case 'textarea':
                default:
                    // Skip this custom field type.
                    break;
            }
        }

        return $filters;
    }

    /**
     * Gets a filter of the specified type
     *
     * @param int $filterregion where on the screen the filter will be used, filter::REGION_PANEL or filter::REGION_BROWSE
     * @param \stdClass $customfield a custom field definition
     * @param string $tableprefix the prefix to use in front of '_info_data' and '_info_field'
     * @param string $prefix the prefix to use in front of 'id' as the column containing the item id
     * @param provider $provider
     * @return filter
     */
    private static function get_filter(
        int $filterregion,
        \stdClass $customfield,
        string $tableprefix,
        string $prefix,
        provider $provider
    ): filter {
        global $DB;

        $datatype = $customfield->datatype;

        if (!in_array($datatype, ['menu', 'multiselect', 'checkbox'])) {
            throw new \coding_exception("Unhandled datatype specified: " . $datatype);
        }

        if ($filterregion == filter::REGION_PANEL) {
            $filterkey = 'cfp_';
        } else if ($filterregion == filter::REGION_BROWSE) {
            $filterkey = 'cfb_';
        } else {
            throw new \coding_exception("Unexpected filter region specified: " . $filterregion);
        }
        $filterkey .= $datatype;

        $alias = $filterkey . '_' . catalog_retrieval::get_safe_table_alias($customfield->shortname . '_' . $customfield->fullname);

        if ($filterregion == filter::REGION_PANEL) {
            if ($datatype == 'menu' || $datatype == 'checkbox') {
                $datafilter = new in_or_equal(
                    $alias,
                    'catalog',
                    ['objecttype', 'objectid']
                );
            } else {
                $datafilter = new like_or(
                    $alias,
                    'catalog',
                    ['objecttype', 'objectid']
                );
            }
        } else {
            if ($datatype == 'menu' || $datatype == 'checkbox') {
                $datafilter = new equal(
                    $alias,
                    'catalog',
                    ['objecttype', 'objectid']
                );
            } else {
                $datafilter = new like(
                    $alias,
                    'catalog',
                    ['objecttype', 'objectid']
                );
            }
        }

        $objecttypealias = $filterkey . '_' . catalog_retrieval::get_safe_table_alias(
            $provider->get_object_type() . '_' . $customfield->shortname . '_' . $customfield->fullname
        );
        $fieldidparamkey = $DB->get_unique_param($filterkey);
        $additionalparams = [$fieldidparamkey => $customfield->id];

        // Rewrite the default value for multi-select fields, which store their defaults in param1.
        if ($datatype == 'multiselect') {
            $customfield->defaultdata = self::get_multiselect_default($customfield->param1);
        }

        if (is_null($customfield->defaultdata) || $customfield->defaultdata === "") {
            $table = "{{$tableprefix}_info_data}";
            $additionalcriteria = "{$objecttypealias}.fieldid = :{$fieldidparamkey}";
        } else {
            $defaultparamkey = $DB->get_unique_param('cfp_check_def');
            $table = "(SELECT cfs.{$provider->get_objectid_field()} AS {$prefix}id, COALESCE(cfid.data, :{$defaultparamkey}) AS data
                         FROM {$provider->get_object_table()} cfs
                    LEFT JOIN {{$tableprefix}_info_data} cfid
                           ON cfs.{$provider->get_objectid_field()} = cfid.{$prefix}id
                          AND cfid.fieldid = :{$fieldidparamkey})";
            $additionalcriteria = "";
            $additionalparams[$defaultparamkey] = $customfield->defaultdata;
        }

        $datafilter->add_source(
            "{$objecttypealias}.data",
            $table,
            $objecttypealias,
            [
                'objecttype' => "'{$provider->get_object_type()}'",
                'objectid' => "{$objecttypealias}.{$prefix}id",
            ],
            $additionalcriteria,
            $additionalparams
        );
        if ($datatype == 'multiselect') {
            $datafilter->set_prefix_and_suffix('%"option":"', '"%');
        }

        if ($filterregion == filter::REGION_PANEL) {
            $selector = new multi(
                $alias,
                format_string($customfield->fullname, true, ['context' => \context_system::instance()])
            );
        } else {
            $selector = new single(
                $alias,
                format_string($customfield->fullname, true, ['context' => \context_system::instance()]),
                ''
            );
            $selector->add_all_option();
        }
        $optionsloaderfunction = 'get_' . $datatype . '_options_loader';
        $selector->add_options_loader(self::$optionsloaderfunction($customfield, $tableprefix, $prefix));

        return new filter(
            $alias,
            $filterregion,
            $datafilter,
            $selector,
            new \lang_string('customfields', 'totara_customfield')
        );
    }

    /**
     * Gets a function which returns a list of menu-of-choice options
     *
     * @param \stdClass $customfield a custom field definition
     * @param string $tableprefix the prefix to use in front of '_info_data' and '_info_field'
     * @param string $prefix the prefix to use in front of 'id' as the column containing the item id
     * @return callable
     */
    private static function get_menu_options_loader(\stdClass $customfield, string $tableprefix, string $prefix) {
        return function () use ($customfield, $tableprefix, $prefix) {
            global $CFG;
            require_once($CFG->dirroot . '/totara/customfield/field/menu/field.class.php');

            $systemcontent = \context_system::instance();

            // This is a bit hacky - we should be passing a real item.
            $item = new \stdClass();
            $item->id = 0;
            $field = new \customfield_menu($customfield->id, $item, $prefix, $tableprefix);

            $options = [];
            foreach ($field->options as $option) {
                $safeoption = format_string($option, true, ['context' => $systemcontent]);
                $options[$option] = $safeoption;
            }

            return $options;
        };
    }

    /**
     * Gets a function which returns a list of multi-select options
     *
     * @param \stdClass $customfield a custom field definition
     * @param string $tableprefix the prefix to use in front of '_info_data' and '_info_field'
     * @param string $prefix the prefix to use in front of 'id' as the column containing the item id
     * @return callable
     */
    private static function get_multiselect_options_loader(\stdClass $customfield, string $tableprefix, string $prefix) {
        return function () use ($tableprefix, $prefix, $customfield) {
            global $CFG;

            require_once($CFG->dirroot . '/totara/customfield/field/multiselect/field.class.php');

            // This is a bit hacky - we should be passing a real item.
            $item = new \stdClass();
            $item->id = 0;
            $field = new \customfield_multiselect($customfield->id, $item, $prefix, $tableprefix);

            $options = [];
            foreach ($field->options as $option) {
                $safeoption = format_string($option['option'], true, ['context' => \context_system::instance()]);
                $options[$option['option']] = $safeoption;
            }

            return $options;
        };
    }

    /**
     * Gets a function which returns a list of checkbox options
     *
     * @return callable
     */
    private static function get_checkbox_options_loader() {
        return function () {
            return [1 => 'Yes', 0 => 'No'];
        };
    }

    /**
     * Get the default value for a multi-select custom field
     *
     * @param string $param1 the param1 field from the <tableprefix>_info_field table
     * @return string
     */
    private static function get_multiselect_default(string $param1): string {
        $options = json_decode($param1);

        $defaults = [];

        foreach ($options as $option) {
            if ($option->default) {
                $defaults[] = '"option":"' . $option->option . '"';
            }
        }

        return implode(",", $defaults);
    }
}
