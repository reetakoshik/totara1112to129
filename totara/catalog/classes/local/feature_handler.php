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
 * @package totara_catalog
 */

namespace totara_catalog\local;

use totara_catalog\feature;
use totara_catalog\provider_handler;

defined('MOODLE_INTERNAL') || die();

/**
 * Feature handler.
 */
class feature_handler {

    private static $instance;

    /** @var feature[] */
    private $allfeatures = null;

    /** @var feature */
    private $currentfeature = null;

    /**
     * Return a singleton instance.
     *
     * @return feature_handler
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
     * This function should be used after data relating to features has changed, including during testing.
     */
    public function reset_cache() {
        $this->allfeatures = null;
        $this->currentfeature = null;
    }

    /**
     * Gets all the feature options from all the (active) providers, plus those built into the catalog.
     *
     * They will be combined where appropriate.
     *
     * @return feature[]
     */
    public function get_all_features() {
        if (is_null($this->allfeatures)) {
            $this->allfeatures = [];

            foreach (provider_handler::instance()->get_active_providers() as $provider) {
                $providerfeatures = $provider->get_features();
                foreach ($providerfeatures as $providerfeature) {
                    $this->register_feature($providerfeature);
                }
            }

            $this->register_feature(category_feature::create());
        }

        return $this->allfeatures;
    }

    /**
     * Register a feature in the features array.
     *
     * Features will be merged if they have the same key. Checks will be performed to ensure that
     * the datafilters match, and will throw an exception if they don't. When merging datafilters,
     * their "merge" functions will be used.
     *
     * @param feature $feature
     */
    private function register_feature(feature $feature) {
        if (isset($this->allfeatures[$feature->key])) {
            $existingfeature = $this->allfeatures[$feature->key];

            if (!$existingfeature->can_merge($feature)) {
                throw new \coding_exception('Tried to define two catalog features with the same key but which cannot be merged');
            }

            $existingfeature->merge($feature);
        } else {
            $this->allfeatures[$feature->key] = $feature;
        }
    }

    /**
     * Get the feature which is currently set.
     *
     * @return feature
     */
    public function get_current_feature() {
        if (is_null($this->currentfeature)) {
            if (!config::instance()->get_value('featured_learning_enabled')) {
                return null;
            }

            $allfeatures = $this->get_all_features();

            if (!array_key_exists(config::instance()->get_value('featured_learning_source'), $allfeatures)) {
                return null;
            }

            $this->currentfeature = $allfeatures[config::instance()->get_value('featured_learning_source')];
        }

        return $this->currentfeature;
    }
}
