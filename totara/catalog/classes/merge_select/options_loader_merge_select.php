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

abstract class options_loader_merge_select extends merge_select {

    /** @var callable[] */
    protected $optionsloaders = [];

    /** @var array */
    protected $options = null; // Null indicates that the options have not yet been loaded.

    /**
     * Add an options loader, which can be called to produce a list of options
     *
     * Note that all keys and values returned MUST be clean! If they come from user input then they must have
     * been passed through format_string or something similar, so that they are safe to display in the browser.
     *
     * @param callable $optionsloader
     */
    public function add_options_loader(callable $optionsloader): void {
        $this->options = null;
        $this->optionsloaders[] = $optionsloader;
    }

    /**
     * Get the list of options, by calling all of the registered options loaders and merging the results
     *
     * @return array
     */
    abstract public function get_options(): array;
}