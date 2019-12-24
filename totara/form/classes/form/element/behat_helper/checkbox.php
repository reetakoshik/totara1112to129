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
 * A checkbox element helper.
 *
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 * @copyright 2016 Totara Learning Solutions Ltd {@link http://www.totaralms.com/}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package totara_form
 */
class checkbox extends element {

    /**
     * Returns the checkbox input.
     *
     * @return \Behat\Mink\Element\NodeElement
     */
    protected function get_checkbox_input() {
        $id = $this->node->getAttribute('data-element-id');
        $idliteral = \behat_context_helper::escape($id);
        $checkboxes = $this->node->findAll('xpath', "//input[@type='checkbox' and @id=$idliteral]");
        if (!is_array($checkboxes) || empty($checkboxes)) {
            throw new ExpectationException("Could not find expected {$this->mytype} input: {$this->locator}", $this->context->getSession());
        }
        if (count($checkboxes) > 1) {
            throw new ExpectationException("Found multiple {$this->mytype} inputs where only one was expected: {$this->locator}", $this->context->getSession());
        }
        return reset($checkboxes);
    }

    /**
     * Checks or unchecks the checkbox based on the given value 1 (checked) or 0 (unchecked).
     *
     * NOTE: this method does not accept the actual element checked and unchecked values
     *
     * @param string $value '1' means check, '0' means uncheck the checkbox.
     */
    public function set_value($value) {
        $value = trim($value);
        $checkbox = $this->get_checkbox_input();
        if (!$this->context->running_javascript()) {
            if (!empty($value) && !$checkbox->isChecked()) {
                $checkboxvalue = $checkbox->getAttribute('value');
                if ($checkboxvalue !== '1') {
                    throw new \coding_exception('Goutte driver supports checkboxes with value "1" only, you need to use @javascript tag to force use of Selenium: ' . $this->locator);
                }
                $checkbox->check();
            } else if (empty($value) && $checkbox->isChecked()) {
                $checkbox->uncheck();
            } else {
                // Nothing to do!
            }
            return;
        }
        if ((!empty($value) && !$checkbox->isChecked()) || (empty($value) && $checkbox->isChecked())) {
            // OK click the checkbox, the value does match the current state.
            $checkbox->click();
        } else {
            // Nothing to do!
        }
    }

    /**
     * Asserts the field has expected value.
     *
     * @param string $expectedvalue
     * @return void
     */
    public function assert_value($expectedvalue) {
        $expectedvalue = trim($expectedvalue);
        $checkbox = $this->get_checkbox_input();

        if (!$this->context->running_javascript() and $this->is_frozen()) {
            // This is tricky, because Goutte does not return the disabled element value, so use the initial checked attribute in xpath instead.
            // Note that xpath does not have access to the actual current value, but it is really no problem for frozen elements.
            $checked = !empty($checkbox->getAttribute('checked'));
        } else {
            $checked = $checkbox->isChecked();
        }

        if (empty($expectedvalue)) {
            if ($checked) {
                throw new ExpectationException("Totara form {$this->mytype} element '{$this->locator}' is expected to be unchecked", $this->context->getSession());
            }
        } else {
            if (!$checked) {
                throw new ExpectationException("Totara form {$this->mytype} element '{$this->locator}' is expected to be checked", $this->context->getSession());
            }
        }
    }

}