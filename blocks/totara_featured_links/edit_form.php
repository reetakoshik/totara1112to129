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

defined('MOODLE_INTERNAL') || die();

/**
 * Class block_totara_featured_links_edit_form
 * This is the edit form for the block
 */
class block_totara_featured_links_edit_form extends block_edit_form{

    /**
     * defines the form for the custom block options
     * @param MoodleQuickForm $mform
     */
    protected function specific_definition($mform) {

        $mform->addElement('header', 'config_header', get_string('customblocksettings', 'block'));

        $mform->addElement(
            'select',
            'config_size',
            get_string('tile_size', 'block_totara_featured_links'),
            ['large' => get_string('size_large', 'block_totara_featured_links'),
                'medium' => get_string('size_medium', 'block_totara_featured_links'),
                'small' => get_string('size_small', 'block_totara_featured_links')
            ]
        );

        $shapeoptions = [];
        $shapeoptions[] = $mform->createElement('radio',
            'config_shape',
            '',
            get_string('shape_square', 'block_totara_featured_links'),
            'square');
        $shapeoptions[] = $mform->createElement('radio',
            'config_shape',
            '',
            get_string('shape_portrait', 'block_totara_featured_links'),
            'portrait');
        $shapeoptions[] = $mform->createElement('radio',
            'config_shape',
            '',
            get_string('shape_landscape', 'block_totara_featured_links'),
            'landscape');
        $shapeoptions[] = $mform->createElement('radio',
            'config_shape',
            '',
            get_string('shape_fullwidth', 'block_totara_featured_links'),
            'fullwidth');

        $mform->addGroup($shapeoptions, 'config_shape', get_string('tile_shape', 'block_totara_featured_links'), ['<br>'], false);

        $mform->addElement('text', 'config_manual_id', get_string('manual_id', 'block_totara_featured_links'));

        $mform->setType('config_size', PARAM_ALPHA);
        $mform->setType('config_manual_id', PARAM_ALPHANUMEXT);
        $mform->addHelpButton('config_manual_id', 'manual_id', 'block_totara_featured_links');
    }
}
