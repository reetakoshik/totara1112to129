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

namespace block_totara_featured_links\form\element;

defined('MOODLE_INTERNAL') || die();

use totara_form\form\element\text;

/**
 * Class colorpicker
 * This is a color picker element
 * If it needs to work on browsers that do not support input type=color then
 * You will need to include spectrum. This does it by default.
 * @package block_totara_featured_links\form\element
 */
class colorpicker extends text {
    /**
     * Get Mustache template data.
     *
     * @param \renderer_base $output
     * @return array
     */
    public function export_for_template(\renderer_base $output) {
        $result = parent::export_for_template($output);
        $result['form_item_template'] = 'block_totara_featured_links/element_color';
        $result['amdmodule'] = 'totara_form/form_element_textarea';
        if ($result['value'] == '') {
            $result['value'] = '#FFFFFF';
        }
        return $result;
    }
}