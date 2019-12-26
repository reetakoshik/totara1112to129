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
 * @author Brian Barnes <brian.barnes@totaralearning.com>
 * @package totara_catalog
 */

trait optgroup_trait {

    /** @var array $optgroups optional grouping of options via optgroups */
    private $optgroups = [];

    /**
     * Specify optional grouping of options.
     * This corresponds to the optgroups handling in the select element.
     *
     * The format of array elements is ['First group' => array('value1', 'value2')), 'Second group' => array('value3', 'value4'))]
     *
     * Please note that one value can be in multiple groups,
     * the order of original options is maintained
     * and values not present in options are ignored.
     *
     * @param array $optgroups
     */
    public function set_optgroups(array $optgroups) {
        $this->optgroups = [];
        // Normalise the values to be always strings.
        foreach ($optgroups as $name => $values) {
            $this->optgroups[(string)$name] = array_map('strval', $values);
        }
    }

    /**
     * @param array $options
     * @param string $value_key
     * @param string $text_key
     * @return array
     */
    private function get_grouped_options(array $options, string $value_key, string $text_key): array {
        $result = [];
        $processed_groups = [];
        foreach ($options as $value => $name) {
            $value = (string)$value;
            $found_in_group = false;
            foreach ($this->optgroups as $group_name => $group_values) {
                if (!in_array($value, $group_values, true)) {
                    continue;
                }
                $found_in_group = true;
                if (isset($processed_groups[$group_name])) {
                    // Already processed
                    continue;
                }
                $grouped_options = [];
                foreach ($group_values as $group_value) {
                    if (isset($options[$group_value])) {
                        $grouped_options[] = [$value_key => $group_value, $text_key => clean_text($options[$group_value])];
                    }
                }
                $processed_groups[$group_name] = true;
                $result[] = ['group' => true, 'label' => $group_name, 'options' => $grouped_options];
            }
            if ($found_in_group) {
                continue;
            }

            $result[] = [
                $value_key => $value,
                $text_key => clean_text($name),
            ];
        }
        return $result;
    }
}
