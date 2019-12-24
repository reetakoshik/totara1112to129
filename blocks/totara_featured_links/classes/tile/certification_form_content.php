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

use block_totara_featured_links\form\validator\is_valid_certification;
use totara_form\element_validator;

/**
 * Class certification_content
 * Defines the content form for a certification tile
 * relies heavily on {@link learning_item_content}
 * @package block_totara_featured_links\tile
 */
class certification_form_content extends learning_item_form_content {

    /**
     * Tels the parent class what learning item this is for.
     *
     * @return string 'certification'
     */
    protected function get_learning_item_type(): string {
        return 'certification';
    }

    /**
     * Tells the parent class what validator to use to validate the certification
     *
     * @return element_validator
     */
    protected function get_validator(): element_validator {
        return new is_valid_certification();
    }
}