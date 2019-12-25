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

namespace totara_catalog\form;

use totara_catalog\feature;
use totara_catalog\filter;
use totara_catalog\local\feature_handler;
use totara_catalog\local\filter_handler;
use totara_catalog\local\category_filters;

defined('MOODLE_INTERNAL') || die();

/**
 * Totara form controller class for catalog configuration: Tab "General".
 *
 * @package totara_catalog
 */
class config_general_controller extends base_config_form_controller {

    public function get_form_key(): string {
        return 'general';
    }

    public function get_current_data_and_params(): array {
        list($currentdata, $params) = parent::get_current_data_and_params();

        $browse_by = optional_param('browse_by', null, PARAM_ALPHANUMEXT);
        $featured_learning_enabled = optional_param('featured_learning_enabled', null, PARAM_ALPHANUM);
        $featured_learning_source = optional_param('featured_learning_source', null, PARAM_ALPHANUMEXT);
        $featured_learning_value = optional_param('featured_learning_value', null, PARAM_ALPHANUMEXT);
        $learning_types_in_catalog = optional_param('learning_types_in_catalog', null, PARAM_ALPHANUMEXT);

        if (!is_null($browse_by)) {
            $currentdata['browse_by'] = $browse_by;
        }
        if (!is_null($featured_learning_enabled)) {
            $currentdata['featured_learning_enabled'] = $featured_learning_enabled;
        }
        if (!is_null($featured_learning_source)) {
            $currentdata['featured_learning_source'] = $featured_learning_source;
        }
        if (!is_null($featured_learning_value)) {
            $currentdata['featured_learning_value'] = $featured_learning_value;
        }
        if (!is_null($learning_types_in_catalog)) {
            $currentdata['learning_types_in_catalog'] = $learning_types_in_catalog;
        }

        $browse_filter_data = [];
        foreach (filter_handler::instance()->get_region_filters(filter::REGION_BROWSE) as $filter) {
            // Skip category filter because it's already a hardcoded separate option.
            if ($filter->key == category_filters::FILTER_KEY_CATEGORY_BROWSE) {
                continue;
            }
            $browse_filter_data[(string)$filter->category][$filter->key] = $filter->selector->get_title();
        }

        $features = [];
        $featurenames = [];
        foreach (feature_handler::instance()->get_all_features() as $feature) {
            $featurenames[(string)$feature->category][$feature->key] = $feature->title;
            $features[$feature->key] = $feature;
        }

        $params['features'] = $features;

        list($params['browse_filters'], $params['browse_filter_optgroups']) =
            $this->form_helper->build_optgroups($browse_filter_data);
        list($params['feature_names'], $params['feature_name_optgroups']) =
            $this->form_helper->build_optgroups($featurenames);

        $currentdata = $this->remove_invalid_currentdata($currentdata, $params);
        return [ $currentdata, $params ];
    }

    protected function remove_invalid_currentdata(array $currentdata, array $params): array {
        $currentdata = parent::remove_invalid_currentdata($currentdata, $params);

        if (empty($currentdata['browse_by_custom']) || !isset($params['browse_filters'][$currentdata['browse_by_custom']])) {
            $currentdata['browse_by_custom'] = base_config_form::EMPTY_OPTION_VALUE;
        }
        if (empty($currentdata['featured_learning_source']) ||
            !isset($params['features'][$currentdata['featured_learning_source']])) {
            $currentdata['featured_learning_source'] = base_config_form::EMPTY_OPTION_VALUE;
            $currentdata['featured_learning_value'] = base_config_form::EMPTY_OPTION_VALUE;
        } else {
            if (empty($currentdata['featured_learning_value'])) {
                $currentdata['featured_learning_value'] = base_config_form::EMPTY_OPTION_VALUE;
            } else {
                $feature = $params['features'][$currentdata['featured_learning_source']];
                if (!($feature instanceof feature) ||
                    !array_key_exists($currentdata['featured_learning_value'], $feature->get_options())) {
                    $currentdata['featured_learning_value'] = base_config_form::EMPTY_OPTION_VALUE;
                }
            }
        }
        return $currentdata;
    }
}
