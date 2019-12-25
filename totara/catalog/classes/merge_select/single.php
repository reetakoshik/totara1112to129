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

use totara_core\output\select_tree;

defined('MOODLE_INTERNAL') || die();

class single extends options_loader_merge_select {

    /** @var null */
    private $defaultkey;

    /** @var string */
    private $allname = null; // Null indicates that an 'all' option has not been set.

    /** @var string */
    private $allkey;

    /**
     * Create a single-select merge_select object. Multiple options loaders can be specified and will be
     * merged (like the multi-select merge_select class).
     *
     * If no default is specified then the first option will become the default.
     *
     * @param string $key
     * @param string $title
     * @param string $defaultkey
     */
    public function __construct(string $key, string $title = '', string $defaultkey = null) {
        parent::__construct($key, $title);

        $this->defaultkey = $defaultkey;
    }

    /**
     * Add an 'All' option to the start of the options list.
     *
     * @param string|null $name
     * @param string $key
     */
    public function add_all_option(string $name = null, string $key = '') {
        $this->allname = empty($name) ? new \lang_string('all') : $name;
        $this->allkey = $key;
    }

    /**
     * Load all options from the optionsloaders. Removes options with duplicate keys.
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
                    $option = new \stdClass();
                    $option->key = $key;
                    $option->name = $name;
                    $options[$key] = $option;
                }
            }

            uasort(
                $options,
                function ($a, $b) {
                    return strcmp($a->name, $b->name);
                }
            );

            if (!is_null($this->allname)) {
                if (!empty($options[$this->allkey])) {
                    throw new \coding_exception("Tried to add an 'all' option with a key already in use");
                }

                $alloption = new \stdClass();
                $alloption->key = $this->allkey;
                $alloption->name = $this->allname;
                $options = [$this->allkey => $alloption] + $options;
            }

            if (!is_null($this->defaultkey)) {
                if (!array_key_exists($this->defaultkey, $options)) {
                    throw new \coding_exception("Default key not found in options list: " . $this->key);
                }

                $options[$this->defaultkey]->default = true;
            } else {
                $firstoption = reset($options);
                $firstoption->default = true;
            }

            $this->options = $options;
        }

        return $this->options;
    }

    public function set_current_data(array $paramdata) {
        parent::set_current_data($paramdata);

        if (!is_null($this->currentdata)) {
            $this->currentdata = rawurldecode($this->currentdata);
        }

        // Mark the filter as disabled if the 'All' option has been selected.
        if (!is_null($this->allname) && $this->currentdata == $this->allkey) {
            $this->currentdata = null;
        }
    }

    public function merge(merge_select $otherselector) {
        parent::merge($otherselector);

        /** @var single $otherselector */
        foreach ($otherselector->optionsloaders as $optionsloader) {
            $this->add_options_loader($optionsloader);
        }
    }

    public function get_template() {
        $options = [];
        foreach ($this->get_options() as $key => $option) {
            $option->key = rawurlencode($option->key);
            $options[rawurlencode($key)] = $option;
        }

        return select_tree::create(
            $this->key,
            $this->title,
            $this->titlehidden,
            $options,
            rawurlencode($this->get_data()),
            true
        );
    }
}