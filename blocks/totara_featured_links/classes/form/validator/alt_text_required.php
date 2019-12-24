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

namespace block_totara_featured_links\form\validator;

use totara_form\element_validator;

/**
 * Class alt_text_required
 * A validator that checks if the default_content_form should have an alt text
 * @package block_totara_featured_links
 */
class alt_text_required extends element_validator {
    protected $img_input = '';

    public function __construct ($message = null, $img_input = '') {
        parent::__construct($message);
        if ($img_input == '') {
            throw new \coding_exception("Please pass the name of the image upload input to the validator");
        }
        $this->img_input = $img_input;
    }

    /**
     * Will return an error if there is not heading, textbody, alt_text but there is an image.
     */
    public function validate () {
        $data = $this->element->get_model()->get_raw_post_data();
        $title = $data['heading'];
        $description = $data['textbody'];
        $alt_text = $this->element->get_data()['alt_text'];
        if (isset($this->element->get_model()->get_files()[$this->img_input][0])
            && (!isset($title) || $title == '')
            && (!isset($description) || $description == '')
            && (!isset($alt_text) || $alt_text == '')) {
            $this->element->add_error(get_string('requires_alt_text', 'block_totara_featured_links'));
        }
    }
}