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
 * A editor element helper.
 *
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 * @copyright 2016 Totara Learning Solutions Ltd {@link http://www.totaralms.com/}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package totara_form
 */
class editor extends textarea {
    /**
     * Returns the editor input.
     *
     * @return \Behat\Mink\Element\NodeElement
     * @throws \Behat\Mink\Exception\ExpectationException
     */
    protected function get_editor_input() {
        $id = $this->node->getAttribute('data-element-id');
        $idliteral = \behat_context_helper::escape($id);
        $editors = $this->node->findAll('xpath', "//textarea[@id={$idliteral}]");
        if (!is_array($editors) || empty($editors)) {
            throw new ExpectationException('Could not find expected editor', $this->context->getSession());
        }
        if (count($editors) > 1) {
            throw new ExpectationException('Found multiple editors where only one was expected', $this->context->getSession());
        }
        return reset($editors);
    }

    /**
     * Returns the value of the editor.
     *
     * @return string
     * @throws \Behat\Mink\Exception\ExpectationException
     */
    public function get_value() {
        $editor = $this->get_editor_input();
        if (!$this->context->running_javascript() and $this->is_frozen()) {
            // This is tricky, because Goutte does not return the disabled element value.
            return (string)$editor->getText();
        }
        return (string)$editor->getValue();
    }

    /**
     * Sets the value of this editor.
     *
     * @param string $value True if the editor should be checked, false otherwise.
     * @throws \Behat\Mink\Exception\ExpectationException
     */
    public function set_value($value) {
        $editorid = $this->node->getAttribute('data-element-id');
        $editor = $this->get_editor_input();
        if (!$this->context->running_javascript()) {
            $editor->setValue($value);
            return;
        }
        // We don't check visibility here because the editor that is in use will determine what is visible.
        // Really this next bit is just bit of a hack, there should be an API to set the value of an editor.
        $js  = 'if (window.totara_form_editors.'.$editorid.') {';
        $js .=     'window.totara_form_editors.'.$editorid.'.setValue('.json_encode($value).');';
        $js .= '}';
        $this->context->getSession()->executeScript($js);
    }

    /**
     * Asserts the field has expected value.
     *
     * @param string $expectedvalue
     * @return void
     */
    public function assert_value($expectedvalue) {
        $value = $this->get_value();
        if ($expectedvalue === $value) {
            return;
        }

        throw new ExpectationException("Totara form {$this->mytype} element '{$this->locator}' does not match expected value: {$expectedvalue}", $this->context->getSession());
    }

}