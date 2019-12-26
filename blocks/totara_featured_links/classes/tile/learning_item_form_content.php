<?php
/**
 * This file is part of Totara LMS
 *
 * Copyright (C) 2017 onwards Totara Learning Solutions LTD
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
 * @author Andrew McGhie <andrew.mcghie@totaralearning.com>
 * @package block_totara_featured_links
 */

namespace block_totara_featured_links\tile;

use block_totara_featured_links\form\element\colorpicker;
use block_totara_featured_links\form\validator\is_color;
use totara_form\element_validator;
use totara_form\form\element\checkbox;
use totara_form\form\element\hidden;
use totara_form\form\element\radios;
use totara_form\form\element\select;
use totara_form\form\element\static_html;
use totara_form\group;

/**
 * Class learning_item_content
 * Defines the content form for editing for a learning item
 * such as a course, program or certification.
 *
 * @package block_totara_featured_links\tile
 */
abstract class learning_item_form_content extends base_form_content {

    /**
     * Gets the type of the learning item.
     * Used to create ids and names of elements and retrieve strings.
     *
     * @return string
     */
    protected abstract function get_learning_item_type(): string;

    /**
     * Gets a validator that validates that the selected learning item exists and is valid
     *
     * @return element_validator
     */
    protected abstract function get_validator(): element_validator;

    /**
     * Adds the {@link \totara_form\item}'s to the form
     * Adds the learning item picker and the heading location and background color items
     *
     * @param \totara_form\group $group
     * @return void
     */
    public function specific_definition(group $group) {
        $this->add_learning_item_picker($group);

        $group->add(new checkbox('progressbar', get_string('show_progress_bar', 'block_totara_featured_links')));

        $group->add(new radios('heading_location', get_string('heading_location', 'block_totara_featured_links'), [
            base::HEADING_TOP => get_string('top_heading', 'block_totara_featured_links'),
            base::HEADING_BOTTOM => get_string('bottom_heading', 'block_totara_featured_links')
        ]));

        $background = $group->add(
            new colorpicker(
                'background_color',
                get_string('tile_background_color', 'block_totara_featured_links'),
                PARAM_TEXT
            )
        );

        $background->add_validator(new is_color());
    }

    /**
     * Gets the element that will allow the user to pick the learning item.
     * eg a dialog for courses or programs ect.
     *
     * @param group $group
     * @return void
     */
    protected function add_learning_item_picker(group $group) {
        $learning_item_data = $this->model->get_current_data($this->get_learning_item_type() . '_name');
        if (empty($learning_item_data[$this->get_learning_item_type() . '_name'])) {
            $learning_item_id_data = $this->model->get_current_data($this->get_learning_item_type() . '_name_id');
            if (empty($learning_item_id_data[$this->get_learning_item_type() . '_name_id'])) {
                $learning_item_name = get_string($this->get_learning_item_type() . '_not_selected', 'block_totara_featured_links');
            } else {
                $learning_item_name = get_string($this->get_learning_item_type() . '_has_been_deleted', 'block_totara_featured_links');
            }
        } else {
            $learning_item_name = $learning_item_data[$this->get_learning_item_type() . '_name'];
        }
        $learning_item_name = $group->add(
            new static_html(
                $this->get_learning_item_type() . '_name',
                get_string($this->get_learning_item_type() . '_name_label', 'block_totara_featured_links'),
                '<span id="' . $this->get_learning_item_type() . '-name">'.$learning_item_name.'</span>'
            )
        );
        /** @var static_html $learning_item_name */
        $learning_item_name->set_allow_xss(true);
        $learning_item_name->add_validator($this->get_validator());
        $learning_item_hidden = $group->add(new hidden($this->get_learning_item_type() . '_name_id', PARAM_INT));
        $learning_item_hidden->set_frozen(false);

        /** @var static_html $select_learning_item_button */
        $select_learning_item_button = $group->add(
            new static_html(
                'select_' . $this->get_learning_item_type() . '_button',
                '&nbsp;',
                '<input
                    type="button"
                    value="' . get_string($this->get_learning_item_type() . '_select', 'block_totara_featured_links') . '"
                    id="show-' . $this->get_learning_item_type() . '-dialog">'
            )
        );
        $select_learning_item_button->set_allow_xss(true);
    }

    /**
     * Includes the dependencies for the spectrum color picker.
     *
     * {@inheritdoc}
     */
    public function requirements() {
        parent::requirements();
        global $PAGE;
        $PAGE->requires->css(new \moodle_url('/blocks/totara_featured_links/spectrum/spectrum.css'));
        $PAGE->requires->strings_for_js(
            ['less', 'clear_color', $this->get_learning_item_type() . '_select'],
            'block_totara_featured_links'
        );
        $PAGE->requires->strings_for_js(['cancel', 'choose', 'more'], 'moodle');
        $PAGE->requires->js_call_amd('block_totara_featured_links/spectrum', 'spectrum');
        $PAGE->add_body_class('contains-spectrum-colorpicker');

        $markup = dialog_display_currently_selected(
            get_string('currently_selected', 'block_totara_featured_links'),
            $this->get_learning_item_type()
        );

        $PAGE->requires->js_call_amd(
            'block_totara_featured_links/learning_item_dialog',
            'init',
            [$markup, $this->get_learning_item_type()]
        );
    }
}