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

use totara_catalog\filter;
use totara_catalog\local\filter_handler;

defined('MOODLE_INTERNAL') || die();

/**
 * Totara form controller class for catalog configuration: Tab "Filters".
 *
 * @package totara_catalog
 */
class config_filters_controller extends base_config_form_controller {

    public function get_form_key(): string {
        return 'filters';
    }

    public function get_submission_data(): array {
        $data = parent::get_submission_data();

        if ($data) {
            // Make sure we have an empty array when the form element's submission is empty.
            if (empty($data['filters']) || !is_array($data['filters'])) {
                $data['filters'] = [];
            }
        }

        return $data;
    }

    public function get_current_data_and_params(): array {
        list($currentdata, $params) = parent::get_current_data_and_params();

        $panel_filter_data = [];
        $panel_filter_keys = [];
        foreach (filter_handler::instance()->get_region_filters(filter::REGION_PANEL) as $filter) {
            $panel_filter_data[(string)$filter->category][$filter->key] = $filter->selector->get_title();
            $panel_filter_keys[] = $filter->key;
        }

        if ($params['is_submitted_or_reloaded']) {
            $currentdata['filters'] = optional_param_array('filters', [], PARAM_RAW);
        }

        // Configured filters may be invalid depending on enabled providers. Remove invalid ones.
        $currentdata['filters'] = array_filter(
            $currentdata['filters'],
            function ($filter_key) use ($panel_filter_keys) {
                return in_array($filter_key, $panel_filter_keys);
            },
            ARRAY_FILTER_USE_KEY
        );

        $params['selected_panel_filters'] = $currentdata['filters'] ?? [];

        list($params['panel_filters'], $params['panel_filter_optgroups']) = $this->form_helper->build_optgroups($panel_filter_data);

        $currentdata = $this->remove_invalid_currentdata($currentdata, $params);
        return [ $currentdata, $params ];
    }
}
