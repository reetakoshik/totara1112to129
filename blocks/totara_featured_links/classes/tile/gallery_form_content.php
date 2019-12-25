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

use block_totara_featured_links\form\validator\valid_interval;
use totara_form\form\clientaction\hidden_if;
use totara_form\form\element\checkbox;
use totara_form\form\element\checkboxes;
use totara_form\form\element\filemanager;
use totara_form\form\element\number;
use totara_form\form\element\radios;
use totara_form\form\element\select;
use totara_form\form\element\text;
use totara_form\group;

/**
 * Class gallery_form_content
 * Defines the content form for the multi tile
 * @package block_totara_featured_links
 */
class gallery_form_content extends base_form_content {

    /**
     * The tile specific content options
     * @param group $group
     * @return void
     */
    public function specific_definition(group $group) {

        $group->add(
            new radios(
                'transition',
                get_string('transition', 'block_totara_featured_links'),
                [
                    gallery_tile::TRANSITION_SLIDE => get_string('slide', 'block_totara_featured_links'),
                    gallery_tile::TRANSITION_FADE => get_string('fade', 'block_totara_featured_links')
                ]
            )
        );
        $group->add(
            new radios(
                'order',
                get_string('order', 'block_totara_featured_links'),
                [
                    gallery_tile::ORDER_RANDOM => get_string('random', 'block_totara_featured_links'),
                    gallery_tile::ORDER_SEQUENTIAL => get_string('sequential', 'block_totara_featured_links')
                ]
            )
        );
        $group->add(
            new checkboxes(
                'controls',
                get_string('controls', 'block_totara_featured_links'),
                [
                    gallery_tile::CONTROLS_ARROWS => get_string('arrows', 'block_totara_featured_links'),
                    gallery_tile::CONTROLS_POSITION => get_string('position_indicator', 'block_totara_featured_links')
                ]
            )
        );
        $group->add(
            new checkbox(
                'repeat',
                get_string('repeat', 'block_totara_featured_links')
            )
        );
        $autoplay = $group->add(
            new checkbox(
                'autoplay',
                get_string('autoplay', 'block_totara_featured_links')
            )
        );

        $interval = $group->add(new text('interval', get_string('interval', 'block_totara_featured_links'), PARAM_TEXT));
        $interval->add_validator(new valid_interval());
        $interval->add_help_button('interval', 'block_totara_featured_links');
        $this->model->add_clientaction(new hidden_if($interval))->is_equal($autoplay, '0');

        $pauseonhover = $group->add(
            new checkbox(
                'pauseonhover',
                get_string('pauseonhover', 'block_totara_featured_links')
            )
        );
        $this->model->add_clientaction(new hidden_if($pauseonhover))->is_equal($autoplay, '0');
        return;
    }

    /**
     * The form requires the javascript and css for spectrum as well as passing in the strings
     */
    public function requirements() {
        parent::requirements();
        global $PAGE;
        $PAGE->requires->css(new \moodle_url('/blocks/totara_featured_links/spectrum/spectrum.css'));
        $PAGE->requires->strings_for_js(['less', 'clear_color'], 'block_totara_featured_links');
        $PAGE->requires->strings_for_js(['cancel', 'choose', 'more'], 'moodle');
        $PAGE->requires->js_call_amd('block_totara_featured_links/spectrum', 'spectrum');
        $PAGE->add_body_class('contains-spectrum-colorpicker');
    }

    /**
     * Gets that url that the form will redirect to when it gets saved
     *
     * @param base $tile_instance the instance of the tile that the form is for
     * @return string
     */
    public function get_next_url(base $tile_instance): string {
        if ($this->get_parameters()['tileid'] == 0) {
            $manage_subtile_url = new \moodle_url(
                '/blocks/totara_featured_links/sub_tile_manage.php',
                ['tileid' => $tile_instance->id, 'return_url' => parent::get_next_url($tile_instance)]
            );
            return $manage_subtile_url->out_as_local_url();
        }
        return parent::get_next_url($tile_instance);
    }

    /**
     * Adds the action buttons to the form
     * This adds the save button if editing and
     * Save and edit if creating a gallery tile for the first time
     */
    protected function add_action_buttons(): void {
        if (empty($this->get_parameters()['tileid'])) {
            $this->model->add_action_buttons(true, get_string('save_edit', 'block_totara_featured_links'));
        } else {
            $this->model->add_action_buttons();
        }
    }
}