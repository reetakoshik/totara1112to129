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

/**
 * Totara form controller class for catalog configuration: Tab "Item".
 *
 * @package totara_catalog
 */
class config_item_controller extends base_config_form_controller {

    public function get_form_key(): string {
        return 'item';
    }

    public function get_current_data_and_params(): array {
        list($currentdata, $params) = parent::get_current_data_and_params();
        $currentdata = $this->remove_invalid_currentdata($currentdata, $params);
        return [ $currentdata, $params ];
    }

    protected function remove_invalid_currentdata(array $currentdata, array $params): array {
        $currentdata = parent::remove_invalid_currentdata($currentdata, $params);
        $active_providers = $params['active_provider_names'];
        $placeholders = $params['placeholders'];

        // Hero data
        $hero_data_type = $currentdata['hero_data_type'];
        if ($hero_data_type != 'none') {
            foreach ($active_providers as $key => $label) {
                if (!empty($placeholders[$key][$hero_data_type])) {
                    $element_key = $this->form_helper->build_element_key('hero_data_' . $hero_data_type, $key);
                    if (!empty($currentdata[$element_key]) &&
                        !array_key_exists($currentdata[$element_key], $placeholders[$key][$hero_data_type])) {
                        $currentdata[$element_key] = base_config_form::EMPTY_OPTION_VALUE;
                    }
                }
            }
        }

        return $currentdata;
    }
}
