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
use totara_catalog\form\element\titled_checkbox;
use totara_form\form\clientaction\onchange_reload;
use totara_form\form\element\radios;
use totara_form\form\element\select;
use totara_form\form\group\section;

defined('MOODLE_INTERNAL') || die();

/**
 * Totara form class for catalog configuration: Tab "General".
 *
 * @package totara_catalog
 */
class config_general extends base_config_form {

    public static function get_form_controller() {
        return new config_general_controller();
    }

    protected function definition() {
        $current_data = $this->model->get_current_data(null);
        $params = $this->get_parameters();

        /** @var section $section */
        $section = $this->model->add(new section('general', get_string('general')));
        $section->set_collapsible(false);

        // View options
        $section->add(
            new radios(
                'view_options',
                get_string('view_options', 'totara_catalog'),
                [
                    'tile_and_list' => get_string('tile_and_list', 'totara_catalog'),
                    'tile_only' => get_string('tile_only', 'totara_catalog'),
                    'list_only' => get_string('list_only', 'totara_catalog'),
                ]
            )
        );

        // Items per 'load more'
        $load_more_select = new select(
            'items_per_load',
            get_string('items_per_load', 'totara_catalog'),
            [
                '20' => '20',
                '40' => '40',
                '60' => '60',
            ]
        );
        $section->add($load_more_select);
        $load_more_select->add_help_button('items_per_load', 'totara_catalog');

        // Browse menu
        $browse_by_radios = new radios(
            'browse_by',
            get_string('browse_by', 'totara_catalog'),
            [
                'category' => get_string('category', 'totara_catalog'),
                'none' => get_string('none'),
                'custom' => get_string('custom', 'totara_catalog'),
            ]
        );
        $section->add($browse_by_radios);
        $onchange_reload = new onchange_reload($browse_by_radios);
        $this->model->add_clientaction($onchange_reload);
        $browse_by_radios->add_help_button('browse_by', 'totara_catalog');

        $browse_by_custom_select = new select(
            'browse_by_custom',
            '',
            $this->add_empty_option($params['browse_filters'], 'empty_select_option_hint')
        );
        $section->add($browse_by_custom_select);
        $browse_by_custom_select->set_optgroups($params['browse_filter_optgroups']);
        if ($current_data['browse_by'] != 'custom') {
            $browse_by_custom_select->set_frozen(true);
            $onchange_reload->add_ignored_value('category');
            $onchange_reload->add_ignored_value('none');
        }

        // Featured learning
        $featured_learning_checkbox = new titled_checkbox(
            'featured_learning_enabled',
            get_string('featured_learning', 'totara_catalog')
        );
        $section->add($featured_learning_checkbox);
        $featured_learning_checkbox->add_help_button('featured_learning', 'totara_catalog');
        $this->model->add_clientaction(new onchange_reload($featured_learning_checkbox));

        $featured_learning_source_select = new select(
            'featured_learning_source',
            '',
            $this->add_empty_option($params['feature_names'], 'empty_select_source_option')
        );
        $section->add($featured_learning_source_select);
        $featured_learning_source_select->set_optgroups($params['feature_name_optgroups']);
        $this->model->add_clientaction(new onchange_reload($featured_learning_source_select));

        if ($current_data['featured_learning_source'] === self::EMPTY_OPTION_VALUE) {
            $featured_learning_value_options = $this->add_empty_option([], 'empty_select_value_option');
        } else {
            /** @var feature $feature */
            $feature = $params['features'][$current_data['featured_learning_source']];
            $featured_learning_value_options = $feature->get_options();
        }
        $featured_learning_value_select = new select(
            'featured_learning_value',
            '',
            $this->add_empty_option($featured_learning_value_options, 'empty_select_value_option')
        );
        $section->add($featured_learning_value_select);
        if ($current_data['featured_learning_enabled'] != '1') {
            $featured_learning_value_select->set_frozen(true);
        }

        if ($current_data['featured_learning_enabled'] != '1') {
            $featured_learning_source_select->set_frozen(true);
        }

        $this->add_action_buttons();
    }

    public function validation(array $data, array $files) {
        $errors = parent::validation($data, $files);
        if (!$this->is_featured_learning_selection_valid($data)) {
            $errors['featured_learning_source'] = get_string('error_featured_learning_inconsistent', 'totara_catalog');
        }
        if (!$this->is_browse_by_custom_selection_valid($data)) {
            $errors['browse_by_custom'] = get_string('error_browse_by_custom_invalid', 'totara_catalog');
        }
        return $errors;
    }

    /**
     * Check if 'Featured learning' selection is valid.
     *
     * @param array $formdata
     * @return bool
     */
    private function is_featured_learning_selection_valid(array $formdata): bool {
        if (!$formdata['featured_learning_enabled']) {
            return true;
        }
        return ($formdata['featured_learning_source'] !== self::EMPTY_OPTION_VALUE &&
            $formdata['featured_learning_value'] !== self::EMPTY_OPTION_VALUE);
    }

    /**
     * Check if 'Browse by custom' selection is valid.
     *
     * @param array $formdata
     * @return bool
     */
    private function is_browse_by_custom_selection_valid(array $formdata): bool {
        if ($formdata['browse_by'] != 'custom') {
            return true;
        }
        return ($formdata['browse_by_custom'] !== self::EMPTY_OPTION_VALUE);
    }
}
