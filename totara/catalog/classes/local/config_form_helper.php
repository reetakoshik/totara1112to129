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
 * @author Matthias Bonk <matthias.bonk@totaralearning.com>
 * @package totara_catalog
 */

namespace totara_catalog\local;

use totara_catalog\provider;

defined('MOODLE_INTERNAL') || die();

/**
 * Helper class for dealing with the catalog configuration admin form.
 *
 * This provides necessary data transformations, validations and convenience methods.
 */
class config_form_helper {

    const ELEMENT_KEY_SEPARATOR = '__';

    public $config;

    private $form_keys = [
        'contents',
        'general',
        'templates',
        'item',
        'details',
        'filters',
    ];

    private $fields = [
        'view_options',
        'items_per_load',
        'browse_by',
        'browse_by_custom',
        'featured_learning_enabled',
        'featured_learning_source',
        'featured_learning_value',

        'image_enabled',
        'hero_data_type',
        'item_description_enabled',
        'details_description_enabled',
        'item_additional_text_count',
        'item_additional_icons_enabled',
        'progress_bar_enabled',
        'details_title_enabled',
        'rich_text_content_enabled',
        'details_additional_text_count',
        'details_additional_icons_enabled',

        'learning_types_in_catalog',
        'filters',
    ];

    // These form fields are dynamically named with the provider key as a suffix.
    private $provider_fields = [
        'item_title',
        'details_title',
        'hero_data_text',
        'hero_data_icon',
        'rich_text',
        'item_description',
        'details_description',
        'item_additional_icons',
        'details_additional_icons',
    ];

    // These have provider key plus numeric indexes at the end, so they can represent a list of variable length.
    private $provider_list_fields = [
        'item_additional_text',
        'details_additional_text',
        'item_additional_text_label',
        'details_additional_text_label',
    ];

    public static function create() {
        return new static();
    }

    /**
     * config_form_helper constructor.
     * @param null|config $config
     */
    public function __construct(config $config = null) {
        $this->config = $config ?? config::instance();
    }

    /**
     * Transform config data for use in totara form definition.
     *
     * @return array
     * @throws \dml_exception
     */
    public function get_config_for_form(): array {
        $result = [];
        foreach ($this->config->get() as $k => $v) {
            $provider_field = false;
            if (in_array($k, $this->provider_fields) && is_array($v)) {
                $provider_field = true;
                foreach ($v as $provider_name => $config_value) {
                    $result[$this->build_element_key($k, $provider_name)] = $config_value;
                }
            }
            if (in_array($k, $this->provider_list_fields) && is_array($v)) {
                $provider_field = true;
                foreach ($v as $provider_name => $value_array) {
                    if (is_array($value_array)) {
                        $i = 0;
                        foreach ($value_array as $config_value) {
                            $result[$this->build_element_key($k, $provider_name, $i)] = $config_value;
                            $i++;
                        }
                    }
                }
            }
            if (!$provider_field) {
                $result[$k] = $v;
            }
        }
        $result['learning_types_in_catalog'] = $this->config->get_learning_types_in_catalog();
        return $result;
    }

    /**
     * Takes the form data, transforms it to arrays and writes it to DB.
     *
     * @param array $form_data
     */
    public function update_from_form_data(array $form_data) {
        $result = [];
        foreach ($form_data as $k => $v) {
            if (!$this->is_valid_element_key($k)) {
                // Ignore rubbish.
                continue;
            }

            $provider_field = false;
            foreach ($this->provider_fields as $prefix) {
                if (preg_match($this->get_provider_field_regex($prefix), $k, $matches)) {
                    $result[$prefix][$matches[1]] = $v;
                    $provider_field = true;
                    break;
                }
            }
            foreach ($this->provider_list_fields as $prefix) {
                if (preg_match($this->get_provider_dynamic_field_regex($prefix), $k, $matches)) {
                    $result[$prefix][$matches[1]][$matches[2]] = $v;
                    $provider_field = true;
                }
            }
            if (!$provider_field) {
                $result[$k] = $v;
            }
        }
        $this->config->update($result);
    }

    /**
     * Build a form key (element name) for the dynamic elements.
     *
     * @param string $prefix
     * @param $part_1
     * @param null $part_2
     * @return string
     */
    public function build_element_key(string $prefix, $part_1, $part_2 = null): string {
        $config_key = $prefix . self::ELEMENT_KEY_SEPARATOR . $part_1;
        $config_key .= is_null($part_2) ? '' : self::ELEMENT_KEY_SEPARATOR . $part_2;
        return $config_key;
    }

    /**
     * @param string $prefix
     * @return string
     */
    private function get_provider_field_regex(string $prefix): string {
        return '/^' . $prefix . self::ELEMENT_KEY_SEPARATOR . '(' . provider::OBJECT_TYPE_REGEX . ')$/';
    }

    /**
     * @param string $prefix
     * @return string
     */
    private function get_provider_dynamic_field_regex(string $prefix): string {
        return '/^' . $prefix . self::ELEMENT_KEY_SEPARATOR .
            '(' . provider::OBJECT_TYPE_REGEX . ')' .
            self::ELEMENT_KEY_SEPARATOR . '([0-9]+)$/';
    }

    /**
     * @param string $key
     * @return bool
     */
    private function is_valid_element_key(string $key): bool {
        if (in_array($key, $this->fields)) {
            return true;
        }
        foreach ($this->provider_fields as $prefix) {
            if (preg_match($this->get_provider_field_regex($prefix), $key)) {
                return true;
            }
        }
        foreach ($this->provider_list_fields as $prefix) {
            if (preg_match($this->get_provider_dynamic_field_regex($prefix), $key)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return array
     */
    public function get_form_keys() {
        return $this->form_keys;
    }

    /**
     * Split placeholder data in options and option groups as required by totara form.
     *
     * @param array $placeholders
     * @return array
     */
    public function build_optgroups_for_placeholders(array $placeholders): array {
        $form_options = [];
        $form_optgroups = [];
        foreach ($placeholders as $object_type => $provider_optgroups) {
            foreach ($provider_optgroups as $placeholder_type => $placeholders_by_optgroups) {
                list($placeholder_options, $placeholder_optgroups) = $this->build_optgroups($placeholders_by_optgroups);
                $form_options[$object_type][$placeholder_type] = $placeholder_options;
                $form_optgroups[$object_type][$placeholder_type] = $placeholder_optgroups;
            }
        }
        return [$form_options, $form_optgroups];
    }

    /**
     * Takes an array of filters or placeholders as generated by form controllers and splits
     * it up into two sorted arrays of options and option groups that can be passed to totara form.
     *
     * @param array $optgroups
     * @return array
     */
    public function build_optgroups(array $optgroups): array {
        uksort($optgroups, [self::class, 'custom_sort_optgroups']);
        $form_options = [];
        $form_optgroups = [];
        foreach ($optgroups as $optgroup_name => $optgroup) {
            asort($optgroup);
            $form_optgroups[$optgroup_name] = array_keys($optgroup);
            $form_options = array_merge($form_options, $optgroup);
        }

        // We don't want a single optgroup, so throw groups away if we don't have more than one.
        $form_optgroups = (count($form_optgroups) > 1) ? $form_optgroups : [];

        return [$form_options, $form_optgroups];
    }

    /**
     * Sort function for optgroup names.
     * Sort alphabetically but always put the "None" group at the top.
     *
     * @param $a
     * @param $b
     * @return int
     */
    public static function custom_sort_optgroups(string $a, string $b): int {
        $always_first = get_string('default_option_group', 'totara_catalog');
        if ($a === $always_first) {
            return -1;
        }
        if ($b === $always_first) {
            return 1;
        }
        return strcasecmp($a, $b);
    }

}
