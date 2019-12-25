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
 * @package totara_customfield
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 */

// NOTE: no MOODLE_INTERNAL test here, this file may be required by behat before including /config.php.

/**
 * Behat steps to work with Totara custom fields
 *
 * @package totara_customfield
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 */
class behat_totara_customfield extends behat_base {

    /**
     * Checks the user does not see the controls used to move the Totara custom field up or down.
     *
     * @Given /^I should not be able to move the "([^"]*)" Totara custom field (up|down)$/
     *
     * @throws \Behat\Mink\Exception\ExpectationException
     * @throws coding_exception
     * @param string $fullname
     * @param string $direction
     */
    public function i_should_not_be_able_to_move_the_totara_custom_field($fullname, $direction) {
        \behat_hooks::set_step_readonly(true);

        $fn_literal = behat_context_helper::escape($fullname);
        $string = $direction === 'up' ? 'moveup' : 'movedown';
        $move_literal = behat_context_helper::escape(get_string($string));
        $xpath = "//td/span[text()={$fn_literal}]/ancestor::tr/td//a[@title={$move_literal}]";
        try {
            $this->find('xpath', $xpath, new coding_exception(__METHOD__));
        } catch (coding_exception $ex) {
            if (strpos($ex->getMessage(), __METHOD__) === false) {
                // Its not the expected coding_exception.
                throw $ex;
            }
            // Its a success.
            return;
        }
        throw new \Behat\Mink\Exception\ExpectationException("Found the {$move_literal} action for the {$fn_literal} Totara custom field", $this->getSession());
    }

    /**
     * Checks the user sees the controls used to move the Totara custom field up or down.
     *
     * @Given /^I should be able to move the "([^"]*)" Totara custom field (up|down)$/
     *
     * @throws \Behat\Mink\Exception\ExpectationException
     * @param string $fullname
     * @param string $direction
     */
    public function i_should_be_able_to_move_the_totara_custom_field($fullname, $direction) {
        \behat_hooks::set_step_readonly(true);

        $fn_literal = behat_context_helper::escape($fullname);
        $string = $direction === 'up' ? 'moveup' : 'movedown';
        $move_literal = behat_context_helper::escape(get_string($string));
        $xpath = "//td/span[text()={$fn_literal}]/ancestor::tr/td//a[@title={$move_literal}]";
        $this->find('xpath', $xpath, new \Behat\Mink\Exception\ExpectationException("Could not find the {$move_literal} action for the {$fn_literal} Totara custom field", $this->getSession()));
    }

    /**
     * Moves the Totara custom field with the given name up or down.
     *
     * @Given /^I click to move the "([^"]*)" Totara custom field (up|down)$/
     *
     * @throws \Behat\Mink\Exception\ExpectationException
     * @param string $fullname
     * @param string $direction
     */
    public function i_click_to_move_the_totoara_custom_field($fullname, $direction) {
        \behat_hooks::set_step_readonly(false);

        $fn_literal = behat_context_helper::escape($fullname);
        $string = $direction === 'up' ? 'moveup' : 'movedown';
        $move_literal = behat_context_helper::escape(get_string($string));
        $xpath = "//td/span[text()={$fn_literal}]/ancestor::tr/td//a[@title={$move_literal}]";
        $node = $this->find('xpath', $xpath, new \Behat\Mink\Exception\ExpectationException("Could not find the {$move_literal} action for the {$fn_literal} Totara custom field", $this->getSession()));
        $node->click();
    }

    /**
     * Checks the form validation message for a particular custom field using the custom fields short short name.
     *
     * @Given /^I should see the form validation error "([^"]*)" for the "([^"]*)" custom field$/
     *
     * @param string $errormsg
     * @param string $fieldshortname
     */
    public function i_should_see_the_form_validation_error_for_the_custom_field($errormsg, $fieldshortname) {
        \behat_hooks::set_step_readonly(true);

        $fieldshortname_literal = behat_context_helper::escape('customfield_' . $fieldshortname);

        $this->execute('behat_general::assert_element_contains_text', array($errormsg, '//div[contains(@id,' . $fieldshortname_literal . ')]', 'xpath_element'));
    }

    /**
     * Checks a locked custom field has the right content.
     *
     * @Given /^I should see the "([^"]*)" custom field is locked and contains "([^"]*)"$/
     *
     * @param string $fieldshortname
     * @param string $content
     */
    public function i_should_see_the_custom_field_is_locked_and_contains($fieldshortname, $content) {
        \behat_hooks::set_step_readonly(true);

        $fieldshortname_literal = behat_context_helper::escape('customfield_' . $fieldshortname);

        $this->execute('behat_general::assert_element_contains_text', array($content, '//div[contains(@id,' . $fieldshortname_literal . ')]', 'xpath_element'));
    }

    /**
     * Checks a locked custom field is empty.
     *
     * @Given /^I should see the "([^"]*)" custom field is locked and empty$/
     *
     * @param string $fieldshortname
     */
    public function i_should_see_the_custom_field_is_locked_and_empty($fieldshortname) {
        $content = get_string('readonlyemptyfield', 'totara_customfield');
        $this->i_should_see_the_custom_field_is_locked_and_contains($fieldshortname, $content);
    }

}