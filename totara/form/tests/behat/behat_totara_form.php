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
 * @author Sam Hemelryk <sam.hemelryk@totaralms.com>
 * @package totara_form
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright 2016 Totara Learning Solutions Ltd {@link http://www.totaralms.com/}
 */

require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');

use Behat\Gherkin\Node\TableNode as TableNode,
    Behat\Mink\Exception\ExpectationException;

/**
 * Totara form behat definitions.
 *
 * @package totara_form
 * @copyright 2016 Totara Learning Solutions Ltd {@link http://www.totaralms.com/}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 */
class behat_totara_form extends behat_base {

    /**
     * Navigates directly to the Totara test form.
     *
     * This page is only used for acceptance testing and does not appear in the navigation.
     * For that reason we must navigate directly to it.
     *
     * @Given /^I navigate to the Totara test form$/
     */
    public function i_navigate_to_the_totara_test_form() {
        \behat_hooks::set_step_readonly(false);
        $url = new moodle_url('/totara/form/tests/fixtures/test_acceptance.php');
        $this->getSession()->visit($url->out(false));
        $this->wait_for_pending_js();
    }

    /**
     * Fills a Totara form with field/value data.
     *
     * This can only be used for Totara forms.
     *
     * @Given /^I set the following Totara form fields to these values:$/
     * @param TableNode $data
     */
    public function i_set_the_following_totara_form_fields_to_these_values(TableNode $data) {
        \behat_hooks::set_step_readonly(false);

        if ($this->running_javascript()) {
            // If there are multiple sections we need to click the expand all link.
            $this->wait_for_pending_js();
            $nodes = $this->getSession()->getPage()->findAll('xpath', "//form[@data-totara-form]//a[contains(text(), 'Expand all')]");
            if (is_array($nodes)) {
                foreach ($nodes as $node) {
                    if ($node->isVisible()) {
                        $node->click();
                    }
                }
            }
        }

        $datahash = $data->getRowsHash();

        // The action depends on the field type.
        foreach ($datahash as $locator => $value) {
            $this->i_set_totara_form_field_value($locator, trim($value));
        }
    }

    /**
     * Sets the value of a Totara form field.
     *
     * This can only be used for Totara form fields.
     *
     * @Given /^I set the "(?P<locator>(?:[^"]|\\")*)" Totara form field to "(?P<value>(?:[^"]|\\")*)"$/
     */
    public function i_set_totara_form_field_value($locator, $value, $lookfordataname = false) {
        \behat_hooks::set_step_readonly(false);
        $field = $this->get_field_element_given_locator($locator, $lookfordataname);
        $field->assert_not_frozen();
        $field->set_value((string)$value);
    }

    /**
     * Sets the Totara form field to wwwroot plus the given path.
     *
     * @Given /^I set the "(?P<locator>(?:[^"]|\\")*)" Totara form field to local url "(?P<value>(?:[^"]|\\")*)"$/
     */
    public function i_set_totara_form_field_to_local_url($locator, $value, $lookfordataname = false) {
        global $CFG;
        \behat_hooks::set_step_readonly(false);
        $field = $this->get_field_element_given_locator($locator, $lookfordataname);
        $field->assert_not_frozen();
        $field->set_value($CFG->wwwroot . '/' . ltrim($value, '/'));
    }

    /**
     * Sets the value of a Totara form multiple_select field.
     *
     * This needs a separate step because it's not a regular form element with a "name"-attribute,
     * so we have to call the locator with the "look for data-name" flag.
     *
     * @Given /^I set the "(?P<locator>(?:[^"]|\\")*)" multiple select Totara form field to "(?P<value>(?:[^"]|\\")*)"$/
     */
    public function i_set_multiple_select_field_value($locator, $value) {
        $this->i_set_totara_form_field_value($locator, $value, true);
    }

    /**
     * Asserts a Totara form fields have expected values.
     *
     * This can only be used for Totara forms.
     *
     * @Given /^I should see the following Totara form fields having these values:$/
     * @param TableNode $data
     */
    public function i_should_see_the_following_totara_form_fields_having_these_values(TableNode $data) {
        \behat_hooks::set_step_readonly(true);

        if ($this->running_javascript()) {
            // If there are multiple sections we need to click the expand all link.
            $this->wait_for_pending_js();
            $nodes = $this->getSession()->getPage()->findAll('xpath', "//form[@data-totara-form]//a[contains(text(), 'Expand all')]");
            if (is_array($nodes)) {
                foreach ($nodes as $node) {
                    if ($node->isVisible()) {
                        $node->click();
                    }
                }
            }
        }

        $datahash = $data->getRowsHash();

        // The action depends on the field type.
        foreach ($datahash as $locator => $value) {
            $this->i_should_see_totara_form_field_value($locator, trim($value));
        }
    }

    /**
     * Asserts a Totara form field has expected value.
     *
     * This can only be used for Totara form fields.
     *
     * @Given /^I should see the "(?P<locator>(?:[^"]|\\")*)" Totara form field has value "(?P<value>(?:[^"]|\\")*)"$/
     */
    public function i_should_see_totara_form_field_value($locator, $value) {
        \behat_hooks::set_step_readonly(true);
        $field = $this->get_field_element_given_locator($locator);
        $field->assert_value((string)$value);
    }

    /**
     * Asserts a Totara form fields are frozen.
     *
     * This can only be used for Totara forms.
     *
     * @Given /^I should see the following Totara form fields frozen:$/
     * @param TableNode $data
     */
    public function i_should_see_the_following_totara_form_fields_frozen(TableNode $data) {
        \behat_hooks::set_step_readonly(true);

        if ($this->running_javascript()) {
            // If there are multiple sections we need to click the expand all link.
            $this->wait_for_pending_js();
            $nodes = $this->getSession()->getPage()->findAll('xpath', "//form[@data-totara-form]//a[contains(text(), 'Expand all')]");
            if (is_array($nodes)) {
                foreach ($nodes as $node) {
                    if ($node->isVisible()) {
                        $node->click();
                    }
                }
            }
        }

        $datahash = $data->getRowsHash();

        // The action depends on the field type.
        foreach ($datahash as $locator => $nothing) {
            $this->i_should_see_frozen_totara_form_field($locator, true);
        }
    }

    /**
     * Asserts a Totara form field is frozen.
     *
     * This can only be used for Totara form fields.
     *
     * @Given /^I should see the "(?P<locator>(?:[^"]|\\")*)" Totara form field (is|is not) frozen$/
     */
    public function i_should_see_frozen_totara_form_field($locator, $is_frozen) {
        \behat_hooks::set_step_readonly(true);
        $should_be_frozen = ($is_frozen === 'is' || $is_frozen === true);
        $field = $this->get_field_element_given_locator($locator, $should_be_frozen);

        if ($should_be_frozen) {
            $field->assert_frozen();
        } else {
            $field->assert_not_frozen();
        }
    }

    /**
     * Returns a behat_helper instance for the given node
     * and verify the element is visible.
     *
     * @param string $locator
     * @param bool $lookfordataname  Look for 'data-name' attribute instead of 'name'. Needed for frozen or some special elements.
     * @return totara_form\form\element\behat_helper\element
     * @throws ExpectationException if element cannot be found or is not visible
     */
    protected function get_field_element_given_locator($locator, $lookfordataname = false) {
        // Locator could be a label, an input name, or a field.
        if ($this->running_javascript()) {
            $this->wait_for_pending_js();
        }
        $locator = trim($locator);
        $locatorliteral = behat_context_helper::escape($locator);

        // For frozen and some special elements,
        // look for the correct name attribute, so we can find them even if they don't have labels.
        // (Frozen elements have attribute "data-name" instead of "name". Also "multiple_select" element from totara_catalog.)
        $nameattribute = $lookfordataname ? 'data-name' : 'name';
        $xpath = "//form[@data-totara-form]//*[label[text()={$locatorliteral}] ".
                 "or *[@{$nameattribute}={$locatorliteral}] ".
                 "or *[@id={$locatorliteral}] ".
                 "or *[@data-element-label and text()={$locatorliteral}]]//ancestor::*[@data-element-type][1]";

        $nodes = $this->getSession()->getPage()->findAll('xpath', $xpath);
        if (empty($nodes)) {
            throw new ExpectationException(
                'Unable to find a Totara form element with label, name or id = ' . $locatorliteral,
                $this->getSession()
            );
        }
        if (count($nodes) > 1) {
            throw new ExpectationException(
                'Found more than one Totara form element with matching label, name or id = ' . $locatorliteral,
                $this->getSession()
            );
        }
        $node = reset($nodes);

        $type = $node->getAttribute('data-element-type');
        if (!preg_match('#^([^\\\\]+)\\\\form\\\\element\\\\([^\\\\]+)$#', $type, $matches)) {
            throw new ExpectationException('Unrecognised element type ' . $type, $this->getSession());
        }
        $component = $matches[1];
        $elementtype = $matches[2];
        $behatelement = $component.'\\form\\element\\behat_helper\\'.$elementtype;
        if (!class_exists($behatelement)) {
            throw new ExpectationException(
                "No Totara form behat element equivalent for '{$type}': " . $locatorliteral,
                $this->getSession()
            );
        }
        if ($this->running_javascript() and !$node->isVisible()) {
            throw new ExpectationException('Totara form element is not visible: ' . $locatorliteral, $this->getSession());
        }

        return new $behatelement($node, $this, $locator);
    }

    /**
     * Asserts a Totara form item is or is not displayed.
     *
     * @Then /^I (should|should not) see Totara form (label|section) "([^"]+)"$/
     */
    public function i_should_see_totara_form_item($should_be_displayed, $form_item , $text) {
        \behat_hooks::set_step_readonly(true);
        $text = behat_context_helper::escape($text);
        $should_be_displayed = ($should_be_displayed === 'should');
        // Reduce timeout when it's not expected to be displayed to avoid wasting time.
        $timeout = $should_be_displayed ? false : 2;

        $xpath = "//form[@data-totara-form]";
        switch ($form_item) {
            case "label":
                $xpath .= "//*[label[text()={$text} and contains(concat(' ', normalize-space(@class), ' '), ' legend ')]]";
                break;
            case "section":
                $concat = "concat(' ', normalize-space(@class), ' ')";
                $xpath .= "//*[legend[text()={$text} and contains({$concat}, ' tf_section_legend ')]]";
                break;
            default:
                throw new Exception("Unknown form_item {$form_item}.");
        }

        try {
            $node = $this->find(
                'xpath',
                $xpath,
                new ExpectationException("Totara form {$form_item} '{$text}' could not be found", $this->getSession()),
                false,
                $timeout
            );
            if (!$should_be_displayed) {
                throw new ExpectationException(
                    "Totara form {$form_item} '{$text}' has been found when it shouldn't have been.",  $this->getSession()
                );
            }

            if ($this->running_javascript()) {
                if (!$node->isVisible()) {
                    throw new ExpectationException(
                        "Totara form {$form_item} '{$text}' has been found but is not visible.",  $this->getSession()
                    );
                }
            }

            return true;

        } catch (ExpectationException $e) {
            if (!$should_be_displayed) {
                return true;
            } else {
                throw $e;
            }
        }
    }

    /**
     * Exposes the running_javascript method.
     *
     * @return bool
     */
    public function running_javascript() {
        return parent::running_javascript();
    }

}
