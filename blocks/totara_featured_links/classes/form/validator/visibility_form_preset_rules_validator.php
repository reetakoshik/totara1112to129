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

defined('MOODLE_INTERNAL') || die();

use totara_form\element_validator;

/**
 * Class visibility_form_preset_rules_validator
 * Makes sure that if preset rules is selected then at least one rule is chosen
 * @package block_totara_featured_links
 */
class visibility_form_preset_rules_validator extends element_validator {

    /**
     * does the validation to make sure a preset rule is chosen
     */
    public function validate() {
        $preset_showing = $this->element->get_model()->get_data()['preset_showing'] == '1' && $this->element->get_model()->get_data()['visibility'] == '2';
        $checkboxes = $this->element->get_data()['presets_checkboxes'];
        if (empty($checkboxes) && $preset_showing) {
            $this->element->add_error(get_string('error_no_rule', 'block_totara_featured_links'));
        }
    }

}