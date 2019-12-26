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

defined('MOODLE_INTERNAL') || die();

use totara_catalog\local\config;

/**
 * Totara form controller class for catalog configuration: Tab "Contents".
 *
 * @package totara_catalog
 */
class config_contents_controller extends base_config_form_controller {

    public function get_form_key(): string {
        return 'contents';
    }

    public function process_data(): array {
        $previous_providers = config::instance()->get_learning_types_in_catalog();

        $result = parent::process_data();

        if (empty($result['data']['learning_types_in_catalog'])) {
            // Add a warning if all providers were deactivated.
            $result['warning_msg'] = get_string('warning_empty_catalog', 'totara_catalog');
        } else if (count($previous_providers) < count($result['data']['learning_types_in_catalog'])) {
            // When a provider was activated, mention that it may take time to process before changes appear in catalog.
            $result['success_msg'] = get_string('changes_saved_delayed_processing', 'totara_catalog');
        }

        return $result;
    }

    public function get_current_data_and_params(): array {
        list($currentdata, $params) = parent::get_current_data_and_params();
        $currentdata = $this->remove_invalid_currentdata($currentdata, $params);
        return [ $currentdata, $params ];
    }

    protected function remove_invalid_currentdata(array $currentdata, array $params): array {
        $currentdata = parent::remove_invalid_currentdata($currentdata, $params);

        // Configured providers may have become disabled elsewhere. Remove invalid ones.
        $all_provider_names = $params['all_provider_names'];
        $currentdata['learning_types_in_catalog'] = array_filter(
            $currentdata['learning_types_in_catalog'],
            function ($provider_key) use ($all_provider_names) {
                return isset($all_provider_names[$provider_key]);
            }
        );

        // Re-index, so array starts with 0 index.
        $currentdata['learning_types_in_catalog'] = array_values($currentdata['learning_types_in_catalog']);

        return $currentdata;
    }
}
