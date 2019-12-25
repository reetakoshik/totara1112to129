<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2016 onwards Totara Learning Solutions LTD
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
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 * @package totara_form
 */

namespace totara_form\form\clientaction;

use totara_form\model,
    totara_form\clientaction,
    totara_form\item;

/**
 * Hidden if client action.
 *
 * The hidden if client action hides the target element when the set conditions are met.
 * There could be one or more given conditions.
 *
 * @package totara_form
 * @copyright 2016 Totara Learning Solutions Ltd {@link http://www.totaralms.com/}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 */
class hidden_if implements clientaction {

    /**
     * @var item
     */
    protected $target;

    /**
     * An array of objects describing the different comparisons.
     *
     * @var \stdClass[]
     */
    protected $comparisons = [];

    /**
     * hidden_if constructor.
     *
     * @param item $target
     */
    public function __construct(item $target) {
        $this->target = $target;
    }

    /**
     * Returns the configuration object that needs to be passed to JS.
     *
     * @param \renderer_base $output
     * @return \stdClass
     */
    public function get_js_config_obj(\renderer_base $output) {
        $data = new \stdClass;
        $data->target = $this->target->get_id();
        $data->comparisons = $this->comparisons;
        return $data;
    }

    /**
     * Adds a comparison to this hidden if client action.
     *
     * It is strongly recommended to not call this directly but to instead call the
     * dedicated comparison methods on this object.
     *
     * @param item $element
     * @param string $operator One of modle::OP_*
     * @param array $options An array of options to be passed to the JS client.
     * @return $this Chainable.
     */
    public function compare(item $element, $operator, $options = array()) {
        if (!is_array($options)) {
            $options = [$options];
        }
        $comparison = new \stdClass;
        $comparison->element = $element->get_id();
        $comparison->operator = $operator;
        $comparison->options = $options;
        $this->comparisons[] = $comparison;

        return $this;
    }

    /**
     * Adds an is equal comparison.
     *
     * When the reference item {@see $elment} is equal to the expected value the target item is hidden.
     * It is made visible again when the value of the reference element no longer matches the expected value.
     *
     * @param item $element The reference element.
     * @param string $expected
     * @return hidden_if
     */
    public function is_equal(item $element, $expected) {
        return $this->compare($element, model::OP_EQUALS, [(string)$expected]);
    }

    /**
     * Adds an empty comparison.
     *
     * When the reference element {@see $element} is empty the target item is hidden.
     * It is made visible again when the reference element is no longer empty.
     * If the value given is 0 or an empty string this is considered empty.
     *
     * @param item $element The reference element.
     * @return hidden_if
     */
    public function is_empty(item $element) {
        return $this->compare($element, model::OP_EMPTY);
    }

    /**
     * Adds an is filled comparison.
     *
     * When the reference element {@see $element} has a value (including 0) the target item is hidden.
     * It is made visible again when the reference element is emptied.
     *
     * @param item $element
     * @return hidden_if
     */
    public function is_filled(item $element) {
        return $this->compare($element, model::OP_FILLED);
    }

    /**
     * Adds a not equals comparison.
     *
     * When the reference item {@see $elment} is not equal to the expected value the target item is hidden.
     * It is made visible again when the value of the reference element matches the expected value.
     *
     * @param item $element
     * @param string $expected
     * @return hidden_if
     */
    public function not_equals(item $element, $expected) {
        return $this->compare($element, model::OP_NOT_EQUALS, [(string)$expected]);
    }

    /**
     * Adds an not empty comparison.
     *
     * When the reference element {@see $element} is not empty the target item is hidden.
     * It is made visible again when the reference element is empty.
     * If the value given is 0 or an empty string this is considered empty.
     *
     * @param item $element The reference element.
     * @return hidden_if
     */
    public function not_empty(item $element) {
        return $this->compare($element, model::OP_NOT_EMPTY);
    }

    /**
     * Adds an is filled comparison.
     *
     * When the reference element {@see $element} has no value (including 0) the target item is hidden.
     * It is made visible again when the reference element has a value.
     *
     * @param item $element
     * @return hidden_if
     */
    public function not_filled(item $element) {
        return $this->compare($element, model::OP_NOT_FILLED);
    }

}
