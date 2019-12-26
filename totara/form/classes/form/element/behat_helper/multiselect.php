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
 * A multiselect element helper.
 *
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 * @copyright 2016 Totara Learning Solutions Ltd {@link http://www.totaralms.com/}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package totara_form
 */
class multiselect extends element {

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
     * Work around the silly Moodle hacks in Selenium driver.
     *
     * @param \Behat\Mink\Element\NodeElement $select
     * @param array $values
     */
    private function set_select_values(\Behat\Mink\Element\NodeElement $select, array $values) {
        if (!$this->context->running_javascript()) {
            $select->setValue($values);
        }
        // There is a wrong string cast to string in setValue(), so use the private API instead to unselect all
        // and select individual options.
        if (!$values) {
            $deselectalloptions = new \ReflectionMethod('Selenium2Driver', 'deselectAllOptions');
            $deselectalloptions->setAccessible(true);
            $deselectalloptions->invoke($this->context->getSession()->getDriver(), $select);
            return;
        }
        $first = true;
        $xpath = $select->getXpath();
        foreach ($values as $value) {
            $this->context->getSession()->getDriver()->selectOption($xpath, $value, !$first);
            $first = false;
        }
    }

    /**
     * Selects the given value in the select element.
     *
     * @param string $value The value to select - comma separated list
     * @throws \Behat\Mink\Exception\ExpectationException
     */
    public function set_value($value) {
        $select = $this->get_select_input();
        $setvalues = self::split_values($value);

        if (!$setvalues) {
            $this->set_select_values($select, array());
            return;
        }

        /** @var \Behat\Mink\Element\NodeElement[] $options */
        $options = $select->findAll('xpath', "//option");

        $allvalues = array();
        $alllabels = array();
        foreach ($options as $option) {
            $allvalues[] = (string)$option->getAttribute('value');
            $alllabels[] = $option->getText();
        }
        $missing = array_diff($setvalues, $allvalues);
        if (!$missing) {
            if ($allvalues !== $alllabels and array_intersect($setvalues, $alllabels)) {
                throw new ExpectationException("Totara form {$this->mytype} element '{$this->locator}' selection options are both in values and labels: {$value}", $this->context->getSession());
            }
            $this->set_select_values($select, $setvalues);
            return;
        }

        $missing = array_diff($setvalues, $alllabels);
        if (!$missing) {
            if ($allvalues !== $alllabels and array_intersect($setvalues, $allvalues)) {
                throw new ExpectationException("Totara form {$this->mytype} element '{$this->locator}' selection options are both in values and labels: {$value}", $this->context->getSession());
            }
            $values = array();
            foreach ($options as $option) {
                if (in_array($option->getText(), $setvalues, true)) {
                    $values[] = $option->getValue();
                }
            }
            $this->set_select_values($select, $values);
            return;
        }

        throw new ExpectationException("Totara form {$this->mytype} element '{$this->locator}' does not contain all requested options: {$value}", $this->context->getSession());
    }

    /**
     * Asserts the field has expected value.
     *
     * @param string $expectedvalue
     * @return void
     */
    public function assert_value($expectedvalue) {
        $expectedvalues = $this->split_values($expectedvalue);
        $select = $this->get_select_input();

        if (!$this->context->running_javascript() and $this->is_frozen()) {
            // This is tricky, because Goutte does not return the disabled element value, so use the initial selected attribute in xpath instead.
            // Note that xpath does not have access to the actual current value, but it is really no problem for frozen elements.
            $values = array();
            /** @var \Behat\Mink\Element\NodeElement[] $options */
            $options = $select->findAll('xpath', "//option[@selected='selected']");
            if ($options) {
                foreach ($options as $option) {
                    $values[] = $option->getAttribute('value');
                }
            }
        } else {
            $values = $select->getValue();
        }

        sort($values);
        sort($expectedvalues);

        if ($expectedvalues == $values) {
            return;
        }

        $labels = array();
        /** @var \Behat\Mink\Element\NodeElement[] $options */
        $options = $select->findAll('xpath', "//option");
        foreach ($options as $option) {
            if (in_array($option->getValue(), $values, true)) {
                $labels[] = $option->getText();
            }
        }
        sort($labels);
        if ($expectedvalues == $labels) {
            return;
        }

        throw new ExpectationException("Totara form {$this->mytype} element '{$this->locator}' does not match expected value: {$expectedvalue}", $this->context->getSession());
    }

}
