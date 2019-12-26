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
 * @package totara_catalog
 */

namespace totara_catalog\merge_select;

use totara_catalog\optional_param;
use totara_core\output\select_multi;

defined('MOODLE_INTERNAL') || die();

class multi extends options_loader_merge_select {

    /**
     * Load all options from the optionsloaders.
     *
     * Removes options with duplicate keys. When the keys match, there is no check that the values match, so
     * it could end up with weird results if they don't.
     *
     * Results can safely be displayed in the browser.
     *
     * @return array
     */
    public function get_options(): array {
        if (is_null($this->options)) {
            $options = [];

            foreach ($this->optionsloaders as $optionsloader) {
                $loadedoptions = $optionsloader();
                foreach ($loadedoptions as $key => $name) {
                    $options[$key] = $name;
                }
            }

            $this->options = $options;

            asort($this->options);
        }

        return $this->options;
    }

    /**
     * Multiselect just returns one param per option, and nothing else.
     *
     * @return array
     */
    public function get_optional_params(): array {
        return [new optional_param($this->key, null, PARAM_RAW, true)];
    }

    public function merge(merge_select $otherselector) {
        parent::merge($otherselector);

        /** @var multi $otherselector */
        foreach ($otherselector->optionsloaders as $optionsloader) {
            $this->add_options_loader($optionsloader);
        }
    }

    public function set_current_data(array $paramdata) {
        parent::set_current_data($paramdata);

        if (empty($this->currentdata)) {
            $this->currentdata = [];
        }

        foreach ($this->currentdata as $key => $value) {
            $this->currentdata[$key] = rawurldecode($value);
        }
    }

    public function get_template() {
        $activekeys = [];
        foreach ($this->currentdata as $key) {
            $activekeys[] = rawurlencode($key);
        }

        $options = [];
        foreach ($this->get_options() as $key => $name) {
            $options[rawurlencode($key)] = $name;
        }

        return select_multi::create(
            $this->key,
            $this->title,
            $this->titlehidden,
            $options,
            $activekeys
        );
    }
}