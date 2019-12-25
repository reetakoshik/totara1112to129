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

use block_totara_featured_links\tile\base;
use totara_form\element_validator;

/**
 * Class visibility_form_custom_validator
 * Makes sure that if custom rules is selected then at least one rule is defined
 * @package block_totara_featured_links
 */
class visibility_form_custom_validator extends element_validator {

    /**
     * Does the validation to make sure that a custom rule is chosen
     */
    public function validate() {
        $data = $this->element->get_model()->get_data();
        $visibility_type = $data['visibility'];
        $preset_showing = empty($data['preset_showing']) ? 0 : $data['preset_showing'];
        $audience_showing = empty($data['audience_showing']) ? 0 : $data['audience_showing'];
        $tile_rules_showing = empty($data['tile_rules_showing']) ? 0 : $data['tile_rules_showing'];
        if ($visibility_type == base::VISIBILITY_CUSTOM && $preset_showing == '0' && $audience_showing == '0' && $tile_rules_showing == '0') {
            $this->element->add_error(get_string('error_no_rule', 'block_totara_featured_links'));
        }
    }
}