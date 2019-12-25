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
 * A checkboxes element helper.
 *
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 * @copyright 2016 Totara Learning Solutions Ltd {@link http://www.totaralms.com/}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package totara_form
 */
class checkboxes extends element {

    /**
     * Returns the checkboxes input.
     *
     * @return \Behat\Mink\Element\NodeElement[]
     */
    protected function get_checkboxes_input() {
        $checkboxes = $this->node->findAll('xpath', "//input[@type='checkbox']");
        if (empty($checkboxes)) {
            throw new ExpectationException("Could not find expected {$this->mytype} inputs: {$this->locator}", $this->context->getSession());
        }
        return $checkboxes;
    }

    /**
     * Checks or unchecks the checkboxes based on the given value.
     *
     * NOTE: use either real values or labels, but not a mixture.
     *
     * @param string $value A comma separate list of checkbox values and/or labels.
     */
    public function set_value($value) {
        if (!$this->context->running_javascript()) {
            throw new \coding_exception('Goutte driver supports checkboxes with value 1 only, you need to use @javascript tag to force use of Selenium when testing checkboxes element');
        }
        $checkboxes = $this->get_checkboxes_input();
        $setvalues = self::split_values($value);

        $allvalues = array();
        $alllabels = array();
        foreach ($checkboxes as $checkbox) {
            $allvalues[] = (string)$checkbox->getAttribute('value');
            $idliteral = \behat_context_helper::escape($checkbox->getAttribute('id'));
            /** @var \Behat\Mink\Element\NodeElement $label */
            $label = $this->node->find('xpath', "//label[@for=$idliteral]");
            $alllabels[] = $label->getText();
        }

        $missing = array_diff($setvalues, $allvalues);
        if (!$missing) {
            if ($allvalues !== $alllabels and array_intersect($setvalues, $alllabels)) {
                throw new ExpectationException("Totara form {$this->mytype} element '{$this->locator}' selection options are both in values and labels: {$value}", $this->context->getSession());
            }
            foreach ($checkboxes as $checkbox) {
                $thisvalue = (string)$checkbox->getAttribute('value');
                if (in_array($thisvalue, $setvalues, true)) {
                    if (!$checkbox->isChecked()) {
                        $checkbox->click();
                    }
                } else {
                    if ($checkbox->isChecked()) {
                        $checkbox->click();
                    }
                }
            }
            return;
        }

        $missing = array_diff($setvalues, $alllabels);
        if (!$missing) {
            if ($allvalues !== $alllabels and array_intersect($setvalues, $allvalues)) {
                throw new ExpectationException("Totara form {$this->mytype} element '{$this->locator}' selection options are both in values and labels: {$value}", $this->context->getSession());
            }
            foreach ($checkboxes as $checkbox) {
                $idliteral = \behat_context_helper::escape($checkbox->getAttribute('id'));
                /** @var \Behat\Mink\Element\NodeElement $label */
                $label = $this->node->find('xpath', "//label[@for=$idliteral]");
                $label = $label->getText();
                if (in_array($label, $setvalues, true)) {
                    if (!$checkbox->isChecked()) {
                        $checkbox->click();
                    }
                } else {
                    if ($checkbox->isChecked()) {
                        $checkbox->click();
                    }
                }
            }
            return;
        }

        // Developer included some bogus options, fail!
        throw new ExpectationException("Totara form {$this->mytype} element '{$this->locator}' does not contain all given options", $this->context->getSession());
    }

    /**
     * Asserts the field has expected value.
     *
     * @param string $expectedvalue
     * @return void
     */
    public function assert_value($expectedvalue) {
        $checkboxes = $this->get_checkboxes_input();

        $expectedvalues = self::split_values($expectedvalue);
        sort($expectedvalues);

        $values = array();
        foreach ($checkboxes as $checkbox) {
            if ($checkbox->isChecked()) {
                $values[] = $checkbox->getAttribute('value');
            }
        }
        sort($values);

        if ($expectedvalues == $values) {
            return;
        }

        // We do not have value match, let's try a label match.
        $labels = array();
        foreach ($checkboxes as $checkbox) {
            if (!$checkbox->isChecked()) {
                continue;
            }
            $idliteral = \behat_context_helper::escape($checkbox->getAttribute('id'));
            /** @var \Behat\Mink\Element\NodeElement $label */
            $label = $this->node->find('xpath', "//label[@for=$idliteral]");
            $labels[] = $label->getText();
        }
        sort($labels);

        if ($expectedvalues == $labels) {
            return;
        }

        throw new ExpectationException("Totara form {$this->mytype} element '{$this->locator}' does not match expected options: {$expectedvalue}", $this->context->getSession());
    }

}