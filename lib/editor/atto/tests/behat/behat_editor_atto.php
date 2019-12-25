<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Atto custom steps definitions.
 *
 * @package    editor_atto
 * @category   test
 * @copyright  2014 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// NOTE: no MOODLE_INTERNAL test here, this file may be required by behat before including /config.php.

use Behat\Mink\Exception\ExpectationException as ExpectationException;

/**
 * Steps definitions to deal with the atto text editor
 *
 * @package    editor_atto
 * @category   test
 * @copyright  2014 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_editor_atto extends behat_base {

    /**
     * Select the text in an Atto field.
     *
     * @Given /^I select the text in the "([^"]*)" Atto editor$/
     * @throws ElementNotFoundException Thrown by behat_base::find
     * @param string $field
     * @return void
     */
    public function select_the_text_in_the_atto_editor($fieldlocator) {
        \behat_hooks::set_step_readonly(false);

        if (!$this->running_javascript()) {
            throw new coding_exception('Selecting text requires javascript.');
        }
        // We delegate to behat_form_field class, it will
        // guess the type properly.
        $field = behat_field_manager::get_form_field_from_label($fieldlocator, $this);

        if (!method_exists($field, 'select_text')) {
            throw new coding_exception('Field does not support the select_text function.');
        }
        $field->select_text();
    }

    /**
     * Totara hack!
     *
     * Checks, that page contains specified text. It also checks if the text is visible when running Javascript tests.
     *
     * @Then /^I should see "(?P<text_string>(?:[^"]|\\")*)" list in the "([^"]*)" Atto editor$/
     * @throws Behat\Mink\Exception\ExpectationException
     * @param string $text
     * @return array
     */
    public function assert_page_contains_list_from_atto($text, $fieldlocator) {
        \behat_hooks::set_step_readonly(true);
        global $CFG;

        if (!$this->running_javascript()) {
            throw new coding_exception('Selecting text requires javascript.');
        }
        // We delegate to behat_form_field class, it will
        // guess the type properly.
        $field = behat_field_manager::get_form_field_from_label($fieldlocator, $this);
        if (!method_exists($field, 'get_value')) {
            throw new coding_exception('Field does not support the get_value function.');
        }
        $fieldText = $field->get_value();

        // Some chrome browsers add a <span> tag around the text.
        // For now NOT expecting the closing tags in the test text
        $elements = preg_split('/(<[ou]l><li>)/', $text, null, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
        $searchre = '/';
        if (count($elements) > 1) {
            for ($i = 1; $i < count($elements); $i += 2) {
                $searchre .= $elements[$i - 1] . '.*' . $elements[$i];
            }
        }
        else {
            $searchre .= $elements[0];
        }
        $searchre .= '/';

        if (!preg_match($searchre, $fieldText)) {
            throw new ExpectationException('"' . $text . '" text was not found in the Atto editor', $this->getSession());
        }
    }
}

