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

class tree extends merge_select {

    /** @var callable */
    protected $optionsloader = [];

    /** @var array */
    private $options = null; // Null indicates that the options have not yet been loaded.

    /** @var string */
    private $allname = null; // Null indicates that an 'all' option has not been set.

    /** @var string */
    private $allkey;

    /**
     * Select_tree constructor.
     *
     * The optionsloader, when called, must return an array of options which, where each option is a stdClass with properties:
     * - string 'key'
     * - string 'name'
     * - array 'children' (optional) containing children options
     * - bool 'default' (optional) true for one option
     *
     * If no option is set as the default then the first option is used. If more than one are set then an exception
     * will be thrown.
     *
     * The keys and names returned by $optionsloader MUST be clean! If they come from user input then they must have
     * been passed through format_string or something similar, so that they are safe to display in the browser.
     *
     * @param string $key
     * @param string $title
     * @param callable|null $optionsloader as described above
     */
    public function __construct(string $key, string $title = '', callable $optionsloader) {
        parent::__construct($key, $title);

        $this->optionsloader = $optionsloader;
    }

    /**
     * Add an 'All' option at the start of the list of options.
     *
     * @param string|null $name
     * @param string $key
     */
    public function add_all_option(string $name = null, string $key = '') {
        $this->allname = empty($name) ? new \lang_string('all') : $name;
        $this->allkey = $key;
    }

    /**
     * Load all options from the optionsloader.
     *
     * Fails if duplicate keys are discovered. Fails if more than one default is discovered.
     *
     * If no default is specified then the first option will become the default.
     *
     * Results can safely be displayed in the browser.
     *
     * @return array
     */
    public function get_options(): array {
        if (is_null($this->options)) {
            $optionkeys = [];
            $defaultoption = null;

            $optionsloader = $this->optionsloader;
            $options = $optionsloader();

            $optionstocheck = $options;
            while (!empty($optionstocheck)) {
                $option = array_shift($optionstocheck);

                if (in_array($option->key, $optionkeys, true)) {
                    throw new \coding_exception('Tried to create two select tree options with the same key');
                }

                if (!empty($option->children)) {
                    $optionstocheck = array_merge($optionstocheck, $option->children);
                }

                if (!empty($option->default) && $option->default) {
                    if (!empty($defaultoption)) {
                        throw new \coding_exception('Tried to create two select tree options which are both defaults');
                    }

                    $defaultoption = $option;
                }

                $optionkeys[] = $option->key;
            }

            if (!is_null($this->allname)) {
                if (in_array($this->allkey, $optionkeys, true)) {
                    throw new \coding_exception("Tried to add an 'all' option with a key already in use");
                }

                $alloption = new \stdClass();
                $alloption->key = $this->allkey;
                $alloption->name = $this->allname;
                $options = array_merge([$alloption], $options);
            }

            if (empty($defaultoption)) {
                $firstoption = reset($options);
                $firstoption->default = true;
            }

            $this->options = $options;
        }

        return $this->options;
    }

    public function set_current_data(array $paramdata) {
        parent::set_current_data($paramdata);

        // Mark the filter as disabled if the 'All' option has been selected.
        if (!is_null($this->allname) && $this->currentdata == $this->allkey) {
            $this->currentdata = null;
        }
    }

    public function can_merge(merge_select $otherselector) {
        if (!parent::can_merge($otherselector)) {
            return false;
        }

        /** @var tree $otherselector */
        // Both tree lists must have the same options loader.
        if ($this->optionsloader !== $otherselector->optionsloader) {
            return false;
        }

        return true;
    }

    public function get_template() {
        return select_tree::create(
            $this->key,
            $this->title,
            $this->titlehidden,
            $this->get_options(),
            $this->get_data(),
            $this->is_flat()
        );
    }

    /**
     * Determine if the options are only one level deep, in which case the tree can be displayed flat.
     *
     * @return bool
     */
    private function is_flat() {
        foreach ($this->get_options() as $option) {
            if (!empty($option->children)) {
                return false;
            }
        }

        return true;
    }
}