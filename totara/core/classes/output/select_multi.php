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

class select_multi extends select {

    /**
     * Creates a multi-select template.
     *
     * @param string $key
     * @param string $title
     * @param bool $titlehidden true if the title should be hidden
     * @param array $rawoptions an array of $key => $name pairs, where $name is displayed in the multi-select and $key is returned
     * @param array $activekeys array of options that should be currently active
     * @return select_multi
     */
    public static function create(
        string $key,
        string $title,
        bool $titlehidden,
        array $rawoptions,
        array $activekeys = []
    ) : select_multi {
        $data = parent::get_base_template_data($key, $title, $titlehidden);
        global $CFG;
        $data->options = [];
        $data->wwwroot = $CFG->wwwroot;
        $i=1;
        foreach ($rawoptions as $optionkey => $name) {
            $option = new \stdClass();

            $option->active = in_array($optionkey, $activekeys);
            $option->key = $optionkey;
            $option->name = $name;
            $option->ltypeno=$i;
            $data->options[] = $option;
             $i++;
        }

        return new static((array)$data);
    }
}