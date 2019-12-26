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

use totara_catalog\dataformatter\formatter;
use totara_catalog\dataholder;
use totara_catalog\provider;
use totara_catalog\provider_handler;

defined('MOODLE_INTERNAL') || die();

/**
 * Singleton for reading and writing catalog config data.
 *
 * @package totara_catalog
 */
class config {

    private static $instance;

    private $config_cache = null;

    private $provider_config_cache = [];

    private $provider_defaults_cache = null;

    private $learningtypesincatalog = null;

    private $static_defaults = [
        'browse_by' => 'category',
        'browse_by_custom' => '',
        'details_additional_text_count' => '2',
        'details_additional_icons_enabled' => '0',
        'details_description_enabled' => '0',
        'details_title_enabled' => '1',
        'featured_learning_enabled' => '0',
        'featured_learning_source' => '',
        'featured_learning_value' => '',
        'hero_data_type' => 'none',
        'image_enabled' => '1',
        'item_description_enabled' => '0',
        'item_additional_text_count' => '2',
        'item_additional_icons_enabled' => '0',
        'items_per_load' => '20',
        'progress_bar_enabled' => '0',
        'rich_text_content_enabled' => '1',
        'view_options' => 'tile_and_list',
    ];

    /**
     * Return a singleton instance.
     *
     * @return config
     */
    public static function instance() {
        if (!isset(self::$instance)) {
            self::$instance = new static();
        }
        return self::$instance;
    }

    private function __construct() {
    }

    /**
     * Reset the singleton's internal cache, so that the values will be loaded again next time they are accessed.
     *
     * This function should be used after data relating to configuration has changed, including during testing.
     */
    public function reset_cache() {
        $this->config_cache = null;
        $this->provider_config_cache = [];
        $this->provider_defaults_cache = null;
        $this->learningtypesincatalog = null;
    }

    /**
     * Get config from DB and unserialize values.
     *
     * This will always return a complete catalog configuration array.
     * If settings are not found in DB, they will be filled with default values.
     *
     * Application logic should not assume that everything is consistent and should handle dependencies between
     * config settings. E.g. it's possible that an admin configures placeholders for 'details_description' text fields
     * but then disables 'details_description', which still leaves the placeholder configuration in place (but meaningless
     * for the time being).
     *
     * @return array
     */
    public function get(): array {
        if (is_null($this->config_cache)) {
            $config_db = (array)get_config('totara_catalog');
            $defaults = $this->get_defaults();

            // Filter everything out that happens to be in plugin config but that is not for our purpose (e.g. 'version').
            $config_db = array_filter(
                $config_db,
                function ($k) use ($defaults) {
                    return isset($defaults[$k]);
                },
                ARRAY_FILTER_USE_KEY
            );

            $config_db = $this->unserialize_values($config_db);
            $this->config_cache = array_merge($defaults, $config_db);
        }

        return $this->config_cache;
    }

    /**
     * @param string $key
     * @return mixed|null
     */
    public function get_value(string $key) {
        $config = $this->get();
        return $config[$key] ?? null;
    }

    /**
     * @return array
     */
    public function get_defaults(): array {
        return array_merge_recursive($this->get_static_defaults(), $this->get_provider_defaults());
    }

    /**
     * @return array
     */
    public function get_static_defaults(): array {
        // Filter defaults are only static for the current language.
        $filters = [
            'catalog_learning_type_panel' => get_string('learning_type', 'totara_catalog')
        ];

        return array_merge(
            $this->static_defaults,
            ['filters' => $filters]
        );
    }

    /**
     * Create default configuration for all active providers.
     *
     * @return array
     */
    public function get_provider_defaults(): array {
        if (is_null($this->provider_defaults_cache)) {
            $this->provider_defaults_cache = [];

            $defaults = [
                'item_additional_text' => [
                    'course' => ['catalog_learning_type', 'course_category'],
                    'certification' => ['catalog_learning_type', 'course_category'],
                    'program' => ['catalog_learning_type', 'course_category'],
                ],
            ];

            foreach (provider_handler::instance()->get_active_providers() as $provider) {
                $objecttype = $provider->get_object_type();
                foreach (['item', 'details'] as $type) {
                    $this->provider_defaults_cache[$type . '_title'][$objecttype] = $this->get_default_title_placeholder($provider);

                    // Additional icons and texts empty per default.
                    foreach (['icons', 'text', 'text_label'] as $setting) {
                        $config_key = $type . '_additional_' . $setting;
                        $this->provider_defaults_cache[$config_key][$objecttype] = $defaults[$config_key][$objecttype] ?? [];
                    }

                    // Descriptions empty per default
                    $this->provider_defaults_cache[$type . '_description'][$objecttype] = '';
                }

                // Hero data empty per default.
                $this->provider_defaults_cache['hero_data_icon'][$objecttype] = '';
                $this->provider_defaults_cache['hero_data_text'][$objecttype] = '';

                // Rich text empty per default
                $this->provider_defaults_cache['rich_text'][$objecttype] = '';
            }
        }

        return $this->provider_defaults_cache;
    }

    /**
     * Item and details titles must not be empty. Take 'fullname' if it exists, otherwise
     * take the first title placeholder from the list.
     *
     * @param provider $provider
     * @return string
     */
    private function get_default_title_placeholder(provider $provider): string {
        $placeholders = $provider->get_dataholders(formatter::TYPE_PLACEHOLDER_TITLE);
        if (isset($placeholders['fullname'])) {
            $placeholder = $placeholders['fullname'];
        } else {
            $placeholder = reset($placeholders);
        }
        return $placeholder instanceof dataholder ? $placeholder->key : '';
    }

    /**
     * Gets only the provider-specific configuration.
     *
     * @param string $objecttype
     * @return array
     * @throws \dml_exception
     */
    public function get_provider_config(string $objecttype): array {
        if (!isset($this->provider_config_cache[$objecttype])) {
            $all = $this->get();
            $result = [];

            foreach (['item', 'details'] as $type) {
                foreach (['icons', 'text', 'text_label'] as $setting) {
                    $config_key = $type . '_additional_' . $setting;
                    $result[$config_key] = $all[$config_key][$objecttype] ?? [];
                }
                $result[$type . '_title'] = $all[$type . '_title'][$objecttype] ?? '';
                $result[$type . '_description'] = $all[$type . '_description'][$objecttype] ?? '';
            }
            $result['hero_data_text'] = $all['hero_data_text'][$objecttype] ?? '';
            $result['hero_data_icon'] = $all['hero_data_icon'][$objecttype] ?? '';
            $result['rich_text'] = $all['rich_text'][$objecttype] ?? '';

            $this->provider_config_cache[$objecttype] = $result;
        }

        return $this->provider_config_cache[$objecttype];
    }

    /**
     * Gets only one provider-specific config value.
     *
     * @param string $provider_key
     * @param string $config_key
     * @return mixed|null
     * @throws \dml_exception
     */
    public function get_provider_config_value(string $provider_key, string $config_key) {
        $provider_config = $this->get_provider_config($provider_key);
        return $provider_config[$config_key] ?? null;
    }

    /**
     * Add or edit configuration.
     *
     * @param array $data data from one of the totara_catalog\form\* forms
     */
    public function update(array $data) {
        if (isset($data['learning_types_in_catalog']) && is_array($data['learning_types_in_catalog'])) {
            $oldlearningtypesincatalog = $this->get_learning_types_in_catalog();
            $newlearningtypesincatalog = $data['learning_types_in_catalog'];
        }

        $data = $this->serialize_values($data);
        foreach ($data as $key => $value) {
            set_config($key, $value, 'totara_catalog');
        }

        $this->reset_cache();

        if (isset($oldlearningtypesincatalog) && isset($newlearningtypesincatalog)) {
            // Trigger events for enabled or disabled providers.
            $this->process_provider_status_changes($oldlearningtypesincatalog, $newlearningtypesincatalog);
        }
    }

    /**
     * @param array $old
     * @param array $new
     */
    private function process_provider_status_changes(array $old, array $new) {
        /** @var provider $providername */
        foreach (provider_handler::instance()->get_all_provider_classes() as $providername) {
            $object_type = $providername::get_object_type();
            if (in_array($object_type, $old) && !in_array($object_type, $new)) {
                $providername::change_status(provider::PROVIDER_STATUS_INACTIVE);
            } else if (!in_array($object_type, $old) && in_array($object_type, $new)) {
                /** @var provider $provider */
                $providername::change_status(provider::PROVIDER_STATUS_ACTIVE);
            }
        }
    }

    /**
     * @param array $config
     * @return array
     */
    private function serialize_values(array $config): array {
        foreach ($config as $key => &$value) {
            $value = json_encode($value);
        }
        return $config;
    }

    /**
     * @param array $config
     * @return array
     */
    private function unserialize_values(array $config): array {
        foreach ($config as $key => &$value) {
            $value = json_decode($value, true);
        }
        return $config;
    }

    /**
     * Get an array of the active objecttypes.
     *
     * @return string[]
     */
    public function get_learning_types_in_catalog() {
        if (!is_array($this->learningtypesincatalog)) {
            $this->learningtypesincatalog = json_decode(get_config('totara_catalog', 'learning_types_in_catalog'));

            if (!is_array($this->learningtypesincatalog)) {
                $this->learningtypesincatalog = [];

                /** @var provider $providerclass */
                foreach (provider_handler::instance()->get_all_provider_classes() as $providerclass) {
                    $this->learningtypesincatalog[] = $providerclass::get_object_type();
                }
            }
        }

        return $this->learningtypesincatalog;
    }

    /**
     * Determines if a provider is active. If the config is not set then it defaults to all providers being enabled.
     *
     * @param string $objecttype
     * @return bool
     */
    public function is_provider_active(string $objecttype): bool {
        return in_array($objecttype, $this->get_learning_types_in_catalog());
    }
}
