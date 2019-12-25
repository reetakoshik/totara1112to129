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

use core\output\template;
use totara_catalog\optional_param;

defined('MOODLE_INTERNAL') || die();

abstract class merge_select {

    /** @var string */
    protected $key;

    /** @var string */
    protected $title;

    /** @var bool */
    protected $titlehidden = false;

    /** @var mixed */
    protected $currentdata;

    /**
     * Base merge_select constructor.
     *
     * $title MUST be a sanitised value, e.g. by using format_string.
     *
     * @param string $key
     * @param string $title
     */
    public function __construct(string $key, string $title = '') {
        $this->key = $key;
        $this->title = $title;
    }

    /**
     * @param string $title
     */
    public function set_title(string $title) {
        $this->title = $title;
    }

    /**
     * @return string
     */
    public function get_title() {
        return $this->title;
    }

    /**
     * Override this function to return the params that may be returned when the selector is submitted.
     *
     * @return optional_param[]
     */
    public function get_optional_params(): array {
        return [new optional_param($this->key, null, PARAM_RAW)];
    }

    /**
     * @param bool $titlehidden
     */
    public function set_title_hidden(bool $titlehidden = true) {
        $this->titlehidden = $titlehidden;
    }

    /**
     * Save submitted data in the object.
     *
     * Override this function if other data processing needs to occur. Subclasses should store all data required
     * to represent the current state in currentdata - use an array or stdClass if more than one value is needed.
     *
     * @param array $paramdata must contain key/values with with keys prefixed with $this->key
     */
    public function set_current_data(array $paramdata) {
        $this->currentdata = $paramdata[$this->key] ?? null;
    }

    /**
     * Returns the data for this selector, in a generic, non-selector/url format.
     *
     * If all data set through set_current_data is stored in currentdata then this is all you need.
     *
     * @return mixed
     */
    final public function get_data() {
        return $this->currentdata;
    }

    /**
     * Works out whether two selectors can be merged together.
     *
     * Override and call parent::can_merge if your selector subclass has criteria that needs to be accounted for.
     *
     * @param merge_select $otherselector
     * @return bool true if the selectors can be merged
     */
    public function can_merge(merge_select $otherselector) {
        if (get_class($this) !== get_class($otherselector)) {
            return false;
        }

        if ($this->key != $otherselector->key) {
            return false;
        }

        if ($this->title != $otherselector->title) {
            return false;
        }

        if ($this->titlehidden != $otherselector->titlehidden) {
            return false;
        }

        if ($this->currentdata != $otherselector->currentdata) {
            return false;
        }

        return true;
    }

    /**
     * Merges the given selector into the current selector.
     *
     * Override and call parent if your selector subclass has data which needs to be manipulated during merge.
     *
     * @param merge_select $otherselector
     */
    public function merge(merge_select $otherselector) {
        if (!$this->can_merge($otherselector)) {
            throw new \coding_exception('Tried to merge two selectors that are not identical');
        }
    }

    /**
     * @return template
     */
    abstract public function get_template();
}