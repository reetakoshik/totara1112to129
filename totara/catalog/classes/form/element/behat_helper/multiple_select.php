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
 * @author Matthias Bonk <matthias.bonk@totaralearning.com>
 * @package totara_catalog
 */

namespace totara_catalog\form\element\behat_helper;

defined('MOODLE_INTERNAL') || die();

use Behat\Mink\Exception\ExpectationException;
use totara_form\form\element\behat_helper\select;


/**
 * A multiple_select element helper.
 *
 * @author Matthias Bonk <matthias.bonk@totaralearning.com>
 * @copyright 2018 Totara Learning Solutions Ltd {@link http://www.totaralms.com/}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package totara_form
 */
class multiple_select extends select {

    /**
     * Returns the select input.
     *
     * @return \Behat\Mink\Element\NodeElement
     */
    protected function get_select_input() {
        $id = $this->node->getAttribute('data-element-id');
        $idliteral = \behat_context_helper::escape($id . '_addicon');
        $selects = $this->node->findAll('xpath', "//select[@id={$idliteral}]");
        if (empty($selects) || !is_array($selects)) {
            throw new ExpectationException(
                "Could not find expected {$this->mytype} input: {$this->locator}",
                $this->context->getSession()
            );
        }
        if (count($selects) > 1) {
            throw new ExpectationException(
                "Found multiple {$this->mytype} inputs where only one was expected: {$this->locator}",
                $this->context->getSession()
            );
        }
        return reset($selects);
    }

    /**
     * Asserts the field has expected value.
     *
     * @param string $expectedvalue
     * @return void
     */
    public function assert_value($expectedvalue) {
        $selected = $this->node->findAll('xpath', "//ol//li");

        if (empty($expectedvalue)) {
            if (!empty($selected)) {
                throw new ExpectationException(
                    "Found selected items for {$this->mytype} {$this->locator} when none were expected.",
                    $this->context->getSession()
                );
            } else {
                return;
            }
        }

        $expectedvalues = explode(',', $expectedvalue);
        $countselected = count($selected);
        $countexpected = count($expectedvalues);
        if ($countselected != $countexpected) {
            throw new ExpectationException(
                "Totara form {$this->mytype} element '{$this->locator}':
                Expected amount of values: {$countexpected} Actual: {$countselected}",
                $this->context->getSession()
            );
        }

        foreach ($expectedvalues as $expected) {
            $expectedliteral = \behat_context_helper::escape($expected);
            $selected = $this->node->findAll('xpath', "//ol//li//span[text()={$expectedliteral}]");
            if (empty($selected) || !is_array($selected)) {
                throw new ExpectationException(
                    "Could not find expected {$this->mytype} selected list item {$expectedliteral} for: {$this->locator}",
                    $this->context->getSession()
                );
            }
            if (count($selected) > 1) {
                throw new ExpectationException(
                    "Found multiple {$this->mytype} selected list item {$expectedliteral}
                    when only one was expected for: {$this->locator}",
                    $this->context->getSession()
                );
            }
        }
    }
}
