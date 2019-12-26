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
 * A radios element helper.
 *
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 * @copyright 2016 Totara Learning Solutions Ltd {@link http://www.totaralms.com/}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package totara_form
 */
class radios extends element {

    /**
     * Returns the radios input.
     *
     * @return \Behat\Mink\Element\NodeElement[]
     * @throws \Behat\Mink\Exception\ExpectationException
     */
    protected function get_radios_inputs() {
        $id = $this->node->getAttribute('data-element-id');
        $idliteral = \behat_context_helper::escape($id);
        $radios = $this->node->findAll('xpath', "//*[@id={$idliteral}]//input[@type='radio']");
        if ($radios === null) {
            throw new ExpectationException("Could not find expected {$this->mytype} input: {$this->locator}", $this->context->getSession());
        }
        return $radios;
    }

    /**
     * Returns the value of the radios if it is checked, or the unchecked value otherwise.
     *
     * @return string|null
     * @throws \Behat\Mink\Exception\ExpectationException
     */
    public function get_value() {
        if (!$this->context->running_javascript() and $this->is_frozen()) {
            $id = $this->node->getAttribute('data-element-id');
            $idliteral = \behat_context_helper::escape($id);
            $radios = $this->node->findAll('xpath', "//*[@id={$idliteral}]//input[@type='radio' and @checked='checked']");
            if ($radios) {
                $radio = reset($radios);
                return (string)$radio->getAttribute('value');
            }
            return null;
        }
        $radios = $this->get_radios_inputs();
        foreach ($radios as $radio) {
            if ($radio->isChecked()) {
                return (string)$radio->getAttribute('value');
            }
        }
        // No radios were checked.
        return null;
    }

    /**
     * Checks or unchecks the radios based on the given value.
     *
     * NOTE: you cannot uncheck all radios!
     *
     * @param string $value the value or label of the radio in group that should be selected
     * @throws \Behat\Mink\Exception\ExpectationException
     */
    public function set_value($value) {
        $value = (string)$value; // Prevent nulls!
        $radios = $this->get_radios_inputs();

        $found = null;
        foreach ($radios as $radio) {
            $thisvalue = (string)$radio->getAttribute('value');
            if ($thisvalue === $value) {
                if ($found) {
                    throw new ExpectationException("Totara form {$this->mytype} element '{$this->locator}' contains more than one radio with matching name or value: {$value}", $this->context->getSession());
                }
                $found = $radio;
            }
            $idliteral = \behat_context_helper::escape($radio->getAttribute('id'));
            /** @var \Behat\Mink\Element\NodeElement $label */
            $label = $this->node->find('xpath', "//label[@for=$idliteral]");
            if ($value === $label->getText()) {
                if ($found) {
                    throw new ExpectationException("Totara form {$this->mytype} element '{$this->locator}' contains more than one radio with matching name or value: {$value}", $this->context->getSession());
                }
                $found = $radio;
            }
        }

        if (!$found) {
            throw new ExpectationException("Totara form {$this->mytype} element '{$this->locator}' does not contain requested option: {$value}", $this->context->getSession());
        }

        if ($this->context->running_javascript()) {
            if (!$found->isChecked()) {
                $found->click();
            }
        } else {
            $found->setValue($found->getAttribute('value'));
        }
    }

    /**
     * Asserts the field has expected value.
     *
     * NOTE: use '$@NULL@$' string for nothing selected
     *
     * @param string $expectedvalue
     * @return void
     */
    public function assert_value($expectedvalue) {
        if ($expectedvalue === '$@NULL@$') {
            $expectedvalue = null; // Means nothing selected yet.
        } else {
            $expectedvalue = (string)$expectedvalue;
        }

        if ($expectedvalue === $this->get_value()) {
            return;
        }

        if ($expectedvalue === null) {
            throw new ExpectationException("Totara form {$this->mytype} element '{$this->locator}' has selected option", $this->context->getSession());
        }

        $checked_radio = null;
        $radios = $this->get_radios_inputs();
        foreach ($radios as $radio) {
            if ($this->context->running_javascript()) {
                if ($radio->isChecked()) {
                    $checked_radio = $radio;
                    break;
                }
            } else {
                if ($radio->getAttribute('checked') === 'checked') {
                    $checked_radio = $radio;
                    break;
                }
            }
        }

        if ($checked_radio) {
            $idliteral = \behat_context_helper::escape($checked_radio->getAttribute('id'));
            /** @var \Behat\Mink\Element\NodeElement $label */
            $label = $this->node->find('xpath', "//label[@for=$idliteral]");
            if ($expectedvalue === (string)$label->getText()) {
                return;
            }
        }

        throw new ExpectationException("Totara form {$this->mytype} element '{$this->locator}' does not match expected value: {$expectedvalue}", $this->context->getSession());
    }

}