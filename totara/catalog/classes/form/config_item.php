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

use totara_catalog\form\element\multiple_select;
use totara_catalog\form\element\titled_checkbox;
use totara_catalog\form\element\additional_field_select;
use totara_catalog\form\group\row_collection;
use totara_form\form\element\select;
use totara_form\form\group\section;
use totara_form\form\element\static_html;

defined('MOODLE_INTERNAL') || die();

/**
 * Totara form class for catalog configuration: Tab "Item".
 *
 * @package totara_catalog
 */
class config_item extends base_config_form {

    public static function get_form_controller() {
        return new config_item_controller();
    }

    protected function definition() {
        $params = $this->get_parameters();

        $placeholders = $params['placeholders'];
        $optgroups = $params['placeholder_optgroups'];
        $active_provider_names = $params['active_provider_names'];

        $form_helper = $this->form_helper();
        $config = $this->config();

        // Title
        /** @var section $section */
        $this->model->add(
            new static_html(
                'item_title_subheading',
                '',
                get_string('item_subheading', 'totara_catalog')
            )
        );
        $section = $this->model->add(new section('item_title', get_string('title', 'totara_catalog')));
        $section->set_collapsible(false);
        foreach ($active_provider_names as $key => $label) {
            $select = new select($form_helper->build_element_key('item_title', $key), $label, $placeholders[$key]['title']);
            $section->add($select);
            $select->set_optgroups($optgroups[$key]['title']);
        }

        // Hero data
        $hero_data_type = $config->get_value('hero_data_type');
        if ($hero_data_type != 'none') {
            $section = $this->model->add(new section('hero_data', get_string('hero_data', 'totara_catalog')));
            $section->set_collapsible(false);
            foreach ($active_provider_names as $key => $label) {
                if (!empty($placeholders[$key][$hero_data_type])) {
                    $select = new select(
                        $form_helper->build_element_key('hero_data_' . $hero_data_type, $key),
                        $label, $this->add_empty_option($placeholders[$key][$hero_data_type])
                    );
                    $section->add($select);
                    $select->set_optgroups($optgroups[$key][$hero_data_type]);
                }
            }
        }

        // Description
        if ($config->get_value('item_description_enabled') === '1') {
            $section = $this->model->add(new section('item_description', get_string('description', 'totara_catalog')));
            $section->set_collapsible(false);
            foreach ($active_provider_names as $key => $label) {
                if (!empty($placeholders[$key]['text'])) {
                    $select = new select(
                        $form_helper->build_element_key('item_description', $key),
                        $label,
                        $this->add_empty_option($placeholders[$key]['text'])
                    );
                    $section->add($select);
                    $select->set_optgroups($optgroups[$key]['text']);
                }
            }
        }

        // Additional text field(s)
        $item_additional_text_count = $config->get_value('item_additional_text_count');
        if ($item_additional_text_count > 0) {
            $section = $this->model->add(
                new section(
                    'item_additional_text',
                    get_string('additional_text_fields', 'totara_catalog')
                )
            );
            $section->set_collapsible(false);
            $section->add(
                new static_html(
                    'item_additional_text_subheading',
                    '',
                    get_string('additional_text_fields_subheading', 'totara_catalog')
                )
            );
            foreach ($active_provider_names as $key => $label) {
                if (!empty($placeholders[$key]['text'])) {
                    for ($i = 0; $i < $item_additional_text_count; $i++) {
                        $label = ($i > 0) ? '' : $label;
                        $row_group = $section->add(new row_collection('additional_text_row_' . $key . '_' . $i));
                        $select = new additional_field_select(
                            $form_helper->build_element_key('item_additional_text', $key, $i),
                            $label,
                            $this->add_empty_option($placeholders[$key]['text'])
                        );
                        $row_group->add($select);
                        $select->set_optgroups($optgroups[$key]['text']);
                        $row_group->add(
                            new titled_checkbox(
                                $form_helper->build_element_key('item_additional_text_label', $key, $i),
                                '',
                                'include_label'
                            )
                        );
                    }
                }
            }
        }

        // Icon sources
        $item_additional_icons_enabled = $config->get_value('item_additional_icons_enabled');
        if ($item_additional_icons_enabled) {
            $section = $this->model->add(new section('item_additional_icons', get_string('icon_sources', 'totara_catalog')));
            $section->set_collapsible(false);
            foreach ($active_provider_names as $key => $label) {
                if (!empty($placeholders[$key]['icons'])) {
                    $element_key = $form_helper->build_element_key('item_additional_icons', $key);
                    $selected = $params[$element_key] ?? [];
                    $selected = $this->remove_invalid_placeholders($selected, $placeholders[$key]['icons']);
                    $multiple_select = new multiple_select($element_key, $label);
                    $multiple_select->set_attribute('icons', $placeholders[$key]['icons']);
                    $multiple_select->set_attribute('selected', $selected);
                    $multiple_select->set_optgroups($optgroups[$key]['icons']);
                    $section->add($multiple_select);
                }
            }
        }

        $this->add_action_buttons();
    }
}
