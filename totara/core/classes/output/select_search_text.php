<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
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
 * @author Nathan Lewis <nathan.lewis@totaralearning.com>
 * @package totara_core
 */

namespace totara_core\output;

defined('MOODLE_INTERNAL') || die();

class select_search_text extends select {

    /**
     * Create a text search template.
     *
     * @param string $key
     * @param string $title
     * @param bool $titlehidden true if the title should be hidden
     * @param string|null $currentvalue the current text to display in the text box
     * @param bool $showplaceholder if true and title is hidden then the title will appear as the placeholder
     * @param string $hintidentifier if provided then a help icon with popup will be shown beside the text input, using string $hintidentifier . '_help'
     * @param string $hintcomponent component for the hint identifier
     * @return select_search_text
     */
    public static function create(
        string $key,
        string $title,
        bool $titlehidden = false,
        string $currentvalue = null,
        bool $showplaceholder = true,
        string $hintidentifier = "",
        string $hintcomponent = ""
    ) : select_search_text {
        global $OUTPUT;

        $data = parent::get_base_template_data($key, $title, $titlehidden);

        $data->current_val = $currentvalue;
        $data->placeholder_show = $showplaceholder;
        $data->has_hint_icon = !empty($hintidentifier) && !empty($hintcomponent);

        if ($data->has_hint_icon) {
            $icon = new \help_icon($hintidentifier, $hintcomponent);
            $data->hint_icon = $icon->export_for_template($OUTPUT);
        }

        return new static((array)$data);
    }
}