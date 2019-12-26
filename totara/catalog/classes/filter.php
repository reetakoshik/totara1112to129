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
use totara_catalog\merge_select\merge_select;

/**
 * A simple container used to join a datasearch filter and a selector.
 */
final class filter {

    const REGION_PANEL = 1;
    const REGION_BROWSE = 2;
    const REGION_FTS = 3;

    /** @var string */
    public $key;

    /** @var int */
    public $region;

    /** @var datafilter */
    public $datafilter;

    /** @var merge_select */
    public $selector;

    /** @var string */
    public $category;

    /**
     * @param string $key something to identify this filter - if the same as other filters then merging will be attempted
     * @param int $region the region on the screen (or FTS index) where the filter should appear
     * @param datafilter $datafilter an object used to produce sql used to filter database records
     * @param merge_select $selector an object containing a front end selector element which can be merged with others
     * @param string $category optional, used for sectioning of select lists in admin config form
     */
    public function __construct(string $key, int $region, datafilter $datafilter, merge_select $selector, string $category = null) {
        $this->key = $key;
        $this->region = $region;
        $this->datafilter = $datafilter;
        $this->selector = $selector;
        $this->category = $category ?? new \lang_string('default_option_group', 'totara_catalog');
    }

    /**
     * Determine if this filter can be merged with another.
     *
     * @param filter $otherfilter
     * @return bool
     */
    public function can_merge(filter $otherfilter): bool {
        if ($this->region != $otherfilter->region) {
            return false;
        }

        if (!$this->datafilter->can_merge($otherfilter->datafilter)) {
            return false;
        }

        if (!$this->selector->can_merge($otherfilter->selector)) {
            return false;
        }

        return true;
    }

    /**
     * Merge another filter into this one.
     *
     * @param filter $otherfilter
     */
    public function merge(filter $otherfilter): void {
        $this->datafilter->merge($otherfilter->datafilter);
        $this->selector->merge($otherfilter->selector);
    }
}
