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

namespace totara_catalog;

defined('MOODLE_INTERNAL') || die();

use totara_catalog\datasearch\filter as datafilter;

/**
 * A simple container to specify featured learning as a datasearch filter and some the related options.
 *
 * @package totara_catalog
 */
final class feature {

    /** @var string */
    public $key;

    /** @var string */
    public $title;

    /** @var filter */
    public $datafilter;

    /** @var string */
    public $category;

    /** @var string[] */
    private $options;

    /** @var callable[] */
    protected $optionsloaders = [];

    /**
     * Create a feature container.
     *
     * @param string $key
     * @param string $title
     * @param datafilter $datafilter
     * @param string $category optional, used for sectioning of select lists in admin config form
     */
    public function __construct(string $key, string $title, datafilter $datafilter, string $category = null) {
        $this->key = $key;
        $this->title = $title;
        $this->datafilter = $datafilter;
        $this->category = $category ?? new \lang_string('default_option_group', 'totara_catalog');
    }

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
     * Gets the list of options.
     *
     * @return string[]
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
     * Determine if this feature can be merged with another.
     *
     * @param feature $otherfeature
     * @return bool
     */
    public function can_merge(feature $otherfeature): bool {
        if (!$this->datafilter->can_merge($otherfeature->datafilter)) {
            return false;
        }

        return true;
    }

    /**
     * Merge another feature into this one.
     *
     * @param feature $otherfeature
     */
    public function merge(feature $otherfeature): void {
        $this->datafilter->merge($otherfeature->datafilter);

        foreach ($otherfeature->optionsloaders as $optionsloader) {
            $this->add_options_loader($optionsloader);
        }
    }
}
