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
use block_totara_featured_links\form\validator\alt_text_required;
use block_totara_featured_links\form\validator\is_color;
use block_totara_featured_links\form\validator\valid_interval;
use totara_form\form\element\checkbox;
use totara_form\form\element\filemanager;
use totara_form\form\element\radios;
use totara_form\form\element\text;
use totara_form\form\element\textarea;
use totara_form\form\validator\element_filemanager;
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
     * @return null
     */
    public function specific_definition(group $group) {
        $url = $group->add(new text('url', get_string('url_title', 'block_totara_featured_links'), PARAM_URL), 2);
        $url->add_help_button('url_title', 'block_totara_featured_links');
        $url->set_attribute('required', true);

        $group->add(new checkbox('target', get_string('link_target_label', 'block_totara_featured_links'), '_blank', '_self'), 3);

        $group->add(new text('heading', get_string('tile_title', 'block_totara_featured_links'), PARAM_TEXT), 4);

        $group->add(new textarea('textbody', get_string('tile_description', 'block_totara_featured_links'), PARAM_TEXT), 5);

        $group->add(new radios('heading_location', get_string('heading_location', 'block_totara_featured_links'), [
            base::HEADING_TOP => get_string('top_heading', 'block_totara_featured_links'),
            base::HEADING_BOTTOM => get_string('bottom_heading', 'block_totara_featured_links')
        ]), 6);

        $file = $group->add(
            new filemanager(
                'background_imgs',
                get_string('tile_gallery_background', 'block_totara_featured_links'),
                ['subdirs' => 0,
                    'maxbytes' => 0,
                    'accept' => ['web_image'],
                    'context' => \context_block::instance($this->get_parameters()['blockinstanceid'])]
            ),
            7
        );
        $file->add_validator(new element_filemanager());
        $file->add_help_button('tile_gallery_background', 'block_totara_featured_links');

        $interval = $group->add(new text('interval', get_string('interval', 'block_totara_featured_links'), PARAM_TEXT), 8);
        $interval->add_validator(new valid_interval());
        $interval->add_help_button('interval', 'block_totara_featured_links');

        $alt_text = $group->add(new text('alt_text', get_string('tile_alt_text', 'block_totara_featured_links'), PARAM_TEXT), 9);
        $alt_text->add_validator(new alt_text_required(null, 'background_imgs'));
        $alt_text->add_help_button('tile_alt_text', 'block_totara_featured_links');

        $background = $group->add(
            new colorpicker(
                'background_color',
                get_string('tile_background_color', 'block_totara_featured_links'),
                PARAM_TEXT
            ),
            10
        );
        $background->add_validator(new is_color());
        return;
    }

    /**
     * The form requires the javascript and css for spectrum as well as passing in the strings
     */
    public function requirements () {
        parent::requirements();
        global $PAGE;
        $PAGE->requires->css(new \moodle_url('/blocks/totara_featured_links/spectrum/spectrum.css'));
        $PAGE->requires->strings_for_js(['less', 'clear_color'], 'block_totara_featured_links');
        $PAGE->requires->strings_for_js(['cancel', 'choose', 'more'], 'moodle');
        $PAGE->requires->js_call_amd('block_totara_featured_links/spectrum', 'spectrum');
        $PAGE->add_body_class('contains-spectrum-colorpicker');
    }
}