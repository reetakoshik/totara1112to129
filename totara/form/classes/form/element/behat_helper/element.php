<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2017 onwards Totara Learning Solutions LTD
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
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 * @package totara_form
 */

namespace totara_form\form\element\behat_helper;

use Behat\Mink\Exception\ExpectationException;

/**
 * Abstract basic element helper implementation.
 *
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 * @package totara_form
 */
abstract class element {

    /**
     * The element node, containing the whole element markup.
     * @var \Behat\Mink\Element\NodeElement
     */
    protected $node;

    /**
     * The context that is currently working with this element.
     * @var \behat_totara_form
     */
    protected $context;

    /**
     * The original element locator used in the feature file,
     * intended for error and debugging messages.
     * @var string
     */
    protected $locator;

    /**
     * The short element name of this instance,
     * intended for error and debugging messages.
     * @var string
     */
    protected $mytype;

    /**
     * Constructs a checkbox behat element helper.
     *
     * @param \Behat\Mink\Element\NodeElement $node
     * @param \behat_totara_form $context
     * @param string $locator
     */
    public function __construct(\Behat\Mink\Element\NodeElement $node, \behat_totara_form $context, $locator) {
        $this->node = $node;
        $this->context = $context;
        $this->locator = $locator;

        $classname = get_class($this);
        $parts = explode('\\', $classname);
        $this->mytype = end($parts);
    }

    /**
     * Splits a string into multiple values. A comma is used to separate and can be escaped with a backslash.
     *
     * @param string $value
     * @return string[]
     */
    public static function split_values($value) {
        $value = trim($value);
        if ($value === '') {
            return array();
        }
        $values = preg_split('#(?<!\\\)\,#', $value);
        foreach ($values as $k => $v) {
            $v = str_replace('\,', ',', $v);
            $v = trim($v);
            $values[$k] = $v;
        }

        return $values;
    }

    /**
     * Is this element frozen?
     * @return bool
     */
    protected function is_frozen() {
        $value = $this->node->getAttribute('data-element-frozen');
        if (!$value or $value === 'false') { // The 'false' is a hack around bug described in TL-14742.
            return false;
        }
        return true;
    }

    /**
     * Assets that element is frozen.
     */
    public function assert_frozen() {
        if (!$this->is_frozen()) {
            throw new ExpectationException("Form element {$this->mytype} is expected to be frozen: {$this->locator}", $this->context->getSession());
        }
    }

    /**
     * Assets that element is not frozen.
     */
    public function assert_not_frozen() {
        if ($this->is_frozen()) {
            throw new ExpectationException("Form element {$this->mytype} is not expected to be frozen: {$this->locator}", $this->context->getSession());
        }
    }

    /**
     * Sets the value of the element.
     *
     * @param string $value
     * @return void
     */
    abstract public function set_value($value);

    /**
     * Asserts the field has expected value.
     *
     * @param string $expectedvalue
     * @return void
     */
    abstract public function assert_value($expectedvalue);

}
