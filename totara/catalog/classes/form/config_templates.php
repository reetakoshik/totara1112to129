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

use totara_catalog\form\element\titled_checkbox;
use totara_form\form\element\radios;
use totara_form\form\element\select;
use totara_form\form\element\static_html;
use totara_form\form\group\section;

defined('MOODLE_INTERNAL') || die();

/**
 * Totara form class for catalog configuration: Tab "Templates".
 *
 * @package totara_catalog
 */
class config_templates extends base_config_form {

    public static function get_form_controller() {
        return new config_templates_controller();
    }

    protected function definition() {
        // Section: Item content placeholders
        /** @var section $section */
        $section = $this->model->add(new section('item_content', get_string('item_content_placeholders', 'totara_catalog')));
        $section->set_collapsible(false);

        $section->add(
            new static_html(
                'item_content_placeholders_subheading',
                '',
                get_string('item_content_placeholders_subheading', 'totara_catalog')
            )
        );

        // "Title enabled" static element.
        $section->add(
            new static_html(
                'item_title_enabled',
                get_string('title', 'totara_catalog'),
                get_string('enabled', 'totara_catalog')
            )
        );

        // Image
        $section->add(new titled_checkbox('image_enabled', get_string('image', 'totara_catalog')));

        // Hero data type
        $hero_data_type_radios = new radios(
            'hero_data_type',
            get_string('hero_data_type', 'totara_catalog'),
            [
                'none' => get_string('none'),
                'text' => get_string('text', 'totara_catalog'),
                'icon' => get_string('icon', 'totara_catalog'),
            ]
        );
        $hero_data_type_radios->add_help_button('hero_data_type', 'totara_catalog');
        $section->add($hero_data_type_radios);

        // Description
        $description_checkbox = new titled_checkbox('item_description_enabled', get_string('description', 'totara_catalog'));
        $description_checkbox->add_help_button('description', 'totara_catalog');
        $section->add($description_checkbox);

        // Additional text placeholders
        $additional_text_placeholders_select = new select(
            'item_additional_text_count',
            get_string('additional_text_placeholders', 'totara_catalog'),
            range(0, 5)
        );
        $section->add($additional_text_placeholders_select);
        $additional_text_placeholders_select->add_help_button('additional_text_placeholders', 'totara_catalog');

        // Additional icon placeholders
        $icon_placeholders_checkbox = new titled_checkbox(
            'item_additional_icons_enabled',
            get_string('icon_placeholders', 'totara_catalog')
        );
        $section->add($icon_placeholders_checkbox);
        $icon_placeholders_checkbox->add_help_button('icon_placeholders', 'totara_catalog');

        // Progress bar
        $section->add(new titled_checkbox('progress_bar_enabled', get_string('progress_bar', 'totara_catalog')));

        // Section: Detail content placeholders
        $section = $this->model->add(new section('details_content', get_string('details_content_placeholders', 'totara_catalog')));
        $section->set_collapsible(false);
        $section->add(
            new static_html(
                'details_content_placeholders_subheading',
                '',
                get_string('details_content_placeholders_subheading', 'totara_catalog')
            )
        );

        // Title
        $section->add(new titled_checkbox('details_title_enabled', get_string('details_title', 'totara_catalog')));

        // Rich text content
        $rich_text_content_checkbox = new titled_checkbox(
            'rich_text_content_enabled',
            get_string('rich_text_content', 'totara_catalog')
        );
        $rich_text_content_checkbox->add_help_button('rich_text_content', 'totara_catalog');
        $section->add($rich_text_content_checkbox);

        // Description
        $description_checkbox = new titled_checkbox('details_description_enabled', get_string('description', 'totara_catalog'));
        $description_checkbox->add_help_button('description_detail', 'totara_catalog');
        $section->add($description_checkbox);

        // Additional text placeholders
        $additional_text_placeholders_select = new select(
            'details_additional_text_count',
            get_string('additional_text_placeholders', 'totara_catalog'),
            range(0, 5)
        );
        $section->add($additional_text_placeholders_select);
        $additional_text_placeholders_select->add_help_button('additional_text_placeholders_detail', 'totara_catalog');

        // Additional icon placeholders
        $icon_placeholders_checkbox = new titled_checkbox(
            'details_additional_icons_enabled',
            get_string('icon_placeholders', 'totara_catalog')
        );
        $section->add($icon_placeholders_checkbox);
        $icon_placeholders_checkbox->add_help_button('icon_placeholders_detail', 'totara_catalog');

        $this->add_action_buttons();
    }
}
