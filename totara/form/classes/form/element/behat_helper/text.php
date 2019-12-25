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
 * A text element helper.
 *
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 * @copyright 2016 Totara Learning Solutions Ltd {@link http://www.totaralms.com/}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package totara_form
 */
class text extends element {

    /**
     * Returns the input element.
     *
     * @return \Behat\Mink\Element\NodeElement
     */
    protected function get_text_input() {
        $id = $this->node->getAttribute('data-element-id');
        $idliteral = \behat_context_helper::escape($id);
        $texts = $this->node->findAll('xpath', "//input[@id={$idliteral}]");
        if (empty($texts) || !is_array($texts)) {
            throw new ExpectationException("Could not find expected {$this->mytype} input: {$this->locator}", $this->context->getSession());
        }
        if (count($texts) > 1) {
            throw new ExpectationException("Found multiple {$this->mytype} inputs where only one was expected: {$this->locator}", $this->context->getSession());
        }
        return reset($texts);
    }

    /**
     * Returns the value of the input.
     *
     * @return string
     */
    protected function get_value() {
        return (string)$this->get_text_input()->getValue();
    }

    /**
     * Sets the value of the text input
     *
     * @param string $value
     */
    public function set_value($value) {
        $text = $this->get_text_input();
        // Do not call any normalise_value_pre_set here,
        // because it would end up in double normalisation in some cases,
        // instead override this method to normalise the values if necessary.
        $text->setValue($value);
    }

    /**
     * Asserts the field has expected value.
     *
     * @param string $expectedvalue
     * @return void
     */
    public function assert_value($expectedvalue) {
        if ($expectedvalue === $this->get_value()) {
            return;
        }
        throw new ExpectationException("Totara form {$this->mytype} element '{$this->locator}' does not match expected value: {$expectedvalue}", $this->context->getSession());
    }

}