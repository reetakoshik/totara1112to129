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

defined('MOODLE_INTERNAL') || die();

use block_totara_featured_links\form\element\iconpicker;
use block_totara_featured_links\form\element\colorpicker;
use block_totara_featured_links\form\validator\alt_text_required;
use block_totara_featured_links\form\validator\is_color;
use totara_form\form\element\checkbox;
use totara_form\form\element\filemanager;
use totara_form\form\element\radios;
use totara_form\form\element\text;
use totara_form\form\element\textarea;
use totara_form\form\element\url;
use totara_form\form\group\section;
use totara_form\form\validator\element_filemanager;
use totara_form\group;

/**
 * Class default_form_content
 * This is the default content form.
 * This can be used as an example for other tile types
 * @package block_totara_featured_links
 */
class default_form_content extends base_form_content{

    /**
     * The tile specific content options
     * @param group $group
     * @return void
     */
    public function specific_definition(group $group) {
        $url = $group->add(new url('url', get_string('url_title', 'block_totara_featured_links')));
        // Help button is not necessary, this is a regular URL element now.
        $url->set_attributes(['required' => true, 'size' => 60]);

        $group->add(new checkbox('target', get_string('link_target_label', 'block_totara_featured_links'), '_blank', '_self'));

        /** @var section $textgroup */
        $textgroup = $this->model->add(new section('textgroup', get_string('text', 'block_totara_featured_links')));
        $textgroup->set_expanded(true);
        $textgroup->set_collapsible(true);

        $textgroup->add(new text('heading', get_string('tile_title', 'block_totara_featured_links'), PARAM_TEXT));

        $textgroup->add(new textarea('textbody', get_string('tile_description', 'block_totara_featured_links'), PARAM_TEXT));

        $textgroup->add(new radios(
            'heading_location',
            get_string('heading_location', 'block_totara_featured_links'),
            [
                base::HEADING_TOP => get_string('top_heading', 'block_totara_featured_links'),
                base::HEADING_BOTTOM => get_string('bottom_heading', 'block_totara_featured_links')
            ]
        ));

        /** @var section $backgroundgroup */
        $backgroundgroup = $this->model->add(new section('backgroundgroup', get_string('background', 'block_totara_featured_links')));
        $backgroundgroup->set_expanded(true);
        $backgroundgroup->set_collapsible(true);

        $file = $backgroundgroup->add(
            new filemanager(
                'background_img',
                get_string('tile_background', 'block_totara_featured_links'),
                [
                    'subdirs' => 0,
                    'maxbytes' => 0,
                    'maxfiles' => 1,
                    'accept' => ['web_image'],
                    'context' => \context_block::instance($this->get_parameters()['blockinstanceid'])
                ]
            )
        );
        $file->add_validator(new element_filemanager());
        $file->add_help_button('tile_background', 'block_totara_featured_links');

        $group->add(new radios(
            'background_appearance',
            get_string('backgroundappearance', 'block_totara_featured_links'),
            [
                'cover' => get_string('backgroundcover', 'block_totara_featured_links'),
                'contain' => get_string('backgroundcontain', 'block_totara_featured_links')
            ]
        ));

        $iconpicker = $group->add(
            new iconpicker('icon', get_string('icon', 'block_totara_featured_links'))
        );
        $iconpicker->add_help_button('icon', 'block_totara_featured_links');

        $iconsizeoptions = [
            'large' => get_string('icon_size_large', 'block_totara_featured_links'),
            'medium' => get_string('icon_size_medium', 'block_totara_featured_links'),
            'small' => get_string('icon_size_small', 'block_totara_featured_links')
        ];
        $group->add(
            new radios('icon_size',
                get_string('icon_size', 'block_totara_featured_links'),
                $iconsizeoptions
            )
        );

        $alt_text = $backgroundgroup->add(new text('alt_text', get_string('tile_alt_text', 'block_totara_featured_links'), PARAM_TEXT));
        $alt_text->add_validator(new alt_text_required(null, 'background_img'));
        $alt_text->add_help_button('tile_alt_text', 'block_totara_featured_links');

        $backgroundcolor = $backgroundgroup->add(
            new colorpicker(
                'background_color',
                get_string('tile_background_color', 'block_totara_featured_links'),
                PARAM_TEXT
            )
        );
        $backgroundcolor->add_validator(new is_color());
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
        $PAGE->requires->strings_for_js(['cancel', 'ok', 'choose', 'more'], 'moodle');
        $PAGE->requires->strings_for_js(['icon_choose'], 'block_totara_featured_links');
        $PAGE->requires->js_call_amd('block_totara_featured_links/spectrum', 'spectrum');
        $PAGE->add_body_class('contains-spectrum-colorpicker');
    }
}