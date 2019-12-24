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

namespace totara_form\form\element\behat_helper;

use Behat\Mink\Exception\ExpectationException;

/**
 * A select element helper.
 *
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 * @copyright 2016 Totara Learning Solutions Ltd {@link http://www.totaralms.com/}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package totara_form
 */
class select extends element {

    /**
     * Returns the select input.
     *
     * @return \Behat\Mink\Element\NodeElement
     */
    protected function get_select_input() {
        $id = $this->node->getAttribute('data-element-id');
        $idliteral = \behat_context_helper::escape($id);
        $selects = $this->node->findAll('xpath', "//select[@id={$idliteral}]");
        if (empty($selects) || !is_array($selects)) {
            throw new ExpectationException("Could not find expected {$this->mytype} input: {$this->locator}", $this->context->getSession());
        }
        if (count($selects) > 1) {
            throw new ExpectationException("Found multiple {$this->mytype} inputs where only one was expected: {$this->locator}", $this->context->getSession());
        }
        return reset($selects);
    }

    /**
     * Selects the given value in the select element.
     *
     * @param string $value The value to select
     * @throws \Behat\Mink\Exception\ExpectationException
     */
    public function set_value($value) {
        $select = $this->get_select_input();
        $valueliteral = \behat_context_helper::escape($value);
        /** @var \Behat\Mink\Element\NodeElement[] $options */
        $options = $select->findAll('xpath', "//option[text()=$valueliteral or @value=$valueliteral]");
        if (!$options) {
            throw new ExpectationException("Totara form {$this->mytype} element '{$this->locator}' does not contain requested option: {$value}", $this->context->getSession());
        }
        if (count($options) > 1) {
            throw new ExpectationException("Totara form {$this->mytype} element '{$this->locator}' contains more than one option with matching name or value: {$value}", $this->context->getSession());
        }
        $option = reset($options);

        $this->context->getSession()->getDriver()->selectOption($select->getXpath(), $option->getValue(), false);
    }

    /**
     * Asserts the field has expected value.
     *
     * @param string $expectedvalue
     * @return void
     */
    public function assert_value($expectedvalue) {
        $select = $this->get_select_input();
        $expectedliteral = \behat_context_helper::escape($expectedvalue);

        if (!$this->context->running_javascript() and $this->is_frozen()) {
            // This is tricky, because Goutte does not return the disabled element value, so use the initial selected attribute in xpath instead.
            // Note that xpath does not have access to the actual current value, but it is really no problem for frozen elements.
            $options = $select->findAll('xpath', "//option[@selected='selected' and text()=$expectedliteral]|//option[@selected='selected' and @value=$expectedliteral]");
            if ($options) {
                return;
            }
            throw new ExpectationException("Totara form {$this->mytype} element '{$this->locator}' does not match expected value: {$expectedvalue}", $this->context->getSession());
        }

        $value = $select->getValue();
        // Does the value match?
        if ($expectedvalue === $value) {
            return;
        }

        // Do we have a lable match on selected option?
        $valueliteral = \behat_context_helper::escape($value);
        $options = $select->findAll('xpath', "//option[text()=$expectedliteral and @value=$valueliteral]");
        if ($options) {
            return;
        }

        throw new ExpectationException("Totara form {$this->mytype} element '{$this->locator}' does not match expected value: {$expectedvalue}", $this->context->getSession());
    }

}