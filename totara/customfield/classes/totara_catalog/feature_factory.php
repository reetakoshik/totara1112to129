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
use totara_catalog\datasearch\all;
use totara_catalog\feature;
use totara_catalog\local\config;
use totara_catalog\provider;

global $CFG;

require_once($CFG->dirroot . '/totara/customfield/fieldlib.php');

class feature_factory {

    /**
     * Create features for custom fields that belong to the plugin (identified by the $tableprefix).
     *
     * @param string $tableprefix the prefix to use in front of '_info_data' and '_info_field'
     * @param string $prefix the prefix to use in front of 'id' as the column containing the item id
     * @param provider $provider
     * @return feature[]
     */
    public static function get_features(string $tableprefix, string $prefix, provider $provider) {
        $customfields = customfield_get_fields_definition($tableprefix, ['hidden' => 0]);

        $features = [];

        foreach ($customfields as $customfield) {
            switch ($customfield->datatype) {
                case 'menu':
                case 'multiselect':
                case 'checkbox':
                    $features[] = self::get_feature($customfield, $tableprefix, $prefix, $provider);
                    break;
                case 'datetime':
                case 'text':
                case 'url':
                case 'file':
                case 'location':
                case 'textarea':
                default:
                    // Skip this custom field type.
                    continue 2;
            }
        }

        return $features;
    }

    /**
     * Gets a feature of the specified type
     *
     * @param \stdClass $customfield a custom field definition
     * @param string $tableprefix the prefix to use in front of '_info_data' and '_info_field'
     * @param string $prefix the prefix to use in front of 'id' as the column containing the item id
     * @param provider $provider
     * @return feature
     */
    private static function get_feature(\stdClass $customfield, string $tableprefix, string $prefix, provider $provider): feature {
        global $DB;

        $datatype = $customfield->datatype;

        if (!in_array($datatype, ['menu', 'multiselect', 'checkbox'])) {
            throw new \coding_exception("Unhandled datatype specified: " . $datatype);
        }

        $filterkey = 'cff_' . $datatype;

        $alias = $filterkey . '_' . catalog_retrieval::get_safe_table_alias($customfield->shortname . '_' . $customfield->fullname);

        $datafilter = new all(
            $alias,
            'catalog',
            ['objecttype', 'objectid'],
            'LEFT JOIN'
        );

        $fieldidparamkey = $DB->get_unique_param($filterkey);
        $dataparamkey = $DB->get_unique_param($filterkey);

        $objecttypealias = $filterkey . '_' . catalog_retrieval::get_safe_table_alias(
            $provider->get_object_type() . '_' . $customfield->shortname . '_' . $customfield->fullname
        );

        // Rewrite the default value for multi-select fields, which store their defaults in param1.
        if ($datatype == 'multiselect') {
            $customfield->defaultdata = self::get_multiselect_default($customfield->param1);
        }

        if ($datatype == 'multiselect') {
            $value = '"option":"' . config::instance()->get_value('featured_learning_value') . '"';
            $dataparamvalue = '%' . $value . '%';

            if (strpos($customfield->defaultdata, $value) === false) {
                $includedefault = false;
                $compare = $DB->sql_like("cfid.data", ":{$dataparamkey}");
            } else {
                $includedefault = true;
                $compare = "({$DB->sql_like("cfid.data", ":{$dataparamkey}")} OR cfid.id IS NULL)";
            }
        } else { // Menu and checkbox.
            $dataparamvalue = config::instance()->get_value('featured_learning_value');

            if ($customfield->defaultdata != $dataparamvalue) {
                $includedefault = false;
                $compare = "cfid.data = :{$dataparamkey}";
            } else {
                $includedefault = true;
                $compare = "(cfid.data = :{$dataparamkey} OR cfid.id IS NULL)";
            }
        }

        if ($includedefault) {
            $table = "(SELECT cfs.{$provider->get_objectid_field()} AS {$prefix}id, 1 AS featured
                         FROM {$provider->get_object_table()} cfs
                    LEFT JOIN {{$tableprefix}_info_data} cfid
                           ON cfs.{$provider->get_objectid_field()} = cfid.{$prefix}id
                          AND cfid.fieldid = :{$fieldidparamkey}
                        WHERE {$compare})";
        } else {
            $table = "(SELECT cfid.*, 1 AS featured
                         FROM {{$tableprefix}_info_data} cfid
                        WHERE {$compare} AND cfid.fieldid = :{$fieldidparamkey})";
        }

        $datafilter->add_source(
            'notused',
            $table,
            $objecttypealias,
            [
                'objecttype' => "'{$provider->get_object_type()}'",
                'objectid' => "{$objecttypealias}.{$prefix}id",
            ],
            "",
            [
                $fieldidparamkey => $customfield->id,
                $dataparamkey => $dataparamvalue,
            ],
            [
                'featured' => '1',
            ]
        );

        $feature = new feature(
            $alias,
            format_string($customfield->fullname, true, ['context' => \context_system::instance()]),
            $datafilter,
            new \lang_string('customfields', 'totara_customfield')
        );
        $optionsloaderfunction = 'get_' . $datatype . '_options_loader';
        $feature->add_options_loader(self::$optionsloaderfunction($customfield, $tableprefix, $prefix));

        return $feature;
    }

    /**
     * Gets a list of menu-of-choice options
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

            // This is a bit hacky - we should be passing a real item.
            $item = new \stdClass();
            $item->id = 0;
            $field = new \customfield_menu($customfield->id, $item, $prefix, $tableprefix);

            $options = [];
            foreach ($field->options as $option) {
                $safeoption = format_string($option, true, ['context' => \context_system::instance()]);
                $options[$safeoption] = $safeoption;
            }

            return $options;
        };
    }

    /**
     * Gets a list of multi-select options
     *
     * @param \stdClass $customfield a custom field definition
     * @param string $tableprefix the prefix to use in front of '_info_data' and '_info_field'
     * @param string $prefix the prefix to use in front of 'id' as the column containing the item id
     * @return callable
     */
    private static function get_multiselect_options_loader(\stdClass $customfield, string $tableprefix, string $prefix) {
        return function () use ($customfield, $tableprefix, $prefix) {
            global $CFG;

            require_once($CFG->dirroot . '/totara/customfield/field/multiselect/field.class.php');

            // This is a bit hacky - we should be passing a real item.
            $item = new \stdClass();
            $item->id = 0;
            $field = new \customfield_multiselect($customfield->id, $item, $prefix, $tableprefix);

            $options = [];
            foreach ($field->options as $option) {
                $safeoption = format_string($option['option'], true, ['context' => \context_system::instance()]);
                $options[$safeoption] = $safeoption;
            }

            return $options;
        };
    }

    /**
     * Gets a list of checkbox options
     *
     * @return callable
     */
    private static function get_checkbox_options_loader() {
        return function () {
            return [
                1 => 'Yes',
                0 => 'No',
            ];
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
