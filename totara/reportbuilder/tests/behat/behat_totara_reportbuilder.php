<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2015 onwards Totara Learning Solutions LTD
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
 * @package totara_reportbuilder
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');

use \Behat\Mink\Exception\ExpectationException;

class behat_totara_reportbuilder extends behat_base {

    /**
     * Adds the given column to the report.
     *
     * This definition requires the user to already be editing a report and to be on the Columns tab.
     *
     * @Given /^I add the "([^"]*)" column to the report$/
     */
    public function i_add_the_column_to_the_report($columnname) {
        \behat_hooks::set_step_readonly(false);
        $this->execute("behat_forms::i_set_the_field_to", array("newcolumns", $this->escape($columnname)));
        $this->execute("behat_forms::press_button", "Save changes");
        $this->execute("behat_general::assert_page_contains_text", "Columns updated");
        $this->execute("behat_general::assert_page_contains_text", $this->escape($columnname));

    }

    /**
     * Deletes the given column from the report.
     *
     * This definition requires the user to already be editing a report and to be on the Columns tab.
     *
     * @Given /^I delete the "([^"]*)" column from the report$/
     */
    public function i_delete_the_column_from_the_report($columnname) {
        \behat_hooks::set_step_readonly(false);
        $columnname_xpath = behat_context_helper::escape($columnname);
        $delstring = behat_context_helper::escape(get_string('delete'));
        $xpath = '//option[contains(., '.$columnname_xpath.') and @selected]/ancestor::tr//a[@title='.$delstring.']';
        $node = $this->find(
            'xpath',
            $xpath,
            new ExpectationException('The given column could not be deleted from within the report builder report. '.$xpath, $this->getSession())
        );
        $node->click();
        $this->getSession()->getDriver()->getWebDriverSession()->accept_alert();
    }


    /**
     * Changes a report builder column from one to another.
     *
     * @When /^I change the "([^"]*)" column to "([^"]*)" in the report$/
     */
    public function i_change_the_column_to_in_the_report($original_column, $new_column) {
        \behat_hooks::set_step_readonly(false);
        $column_xpath = behat_context_helper::escape($original_column);
        $xpath = '//select[@class="column_selector"]//option[contains(.,' . $column_xpath . ') and @selected]/ancestor::select';
        $node = $this->find(
            'xpath',
            $xpath,
            new ExpectationException('The column ' . $original_column .  ' could not be found within the report builder report. ' . $xpath, $this->getSession())
        );
        $node->selectOption($new_column);
    }


    /**
     * Sets the aggregation for the given column in the report.
     *
     * This definition requires the user to already be editing a report and to be on the Columns tab.
     *
     * @Given /^I set aggregation for the "([^"]*)" column to "([^"]*)" in the report$/
     */
    public function i_set_aggregation_for_the_column_to_in_the_report($columnname, $aggregation) {
        \behat_hooks::set_step_readonly(false);
        $columnname_xpath = behat_context_helper::escape($columnname);
        $aggregation_xpath = behat_context_helper::escape($aggregation);
        $xpath = '//option[contains(., '.$columnname_xpath.') and @selected]/ancestor::tr//select//option[contains(., '.$aggregation_xpath.')]//ancestor::select';
        $select = $this->find(
            'xpath',
            $xpath,
            new ExpectationException('Aggreation could not be set for the given column within the report builder report. ', $this->getSession())
        );
        $select->selectOption($aggregation);
    }

    /**
     * Navigates to a given report that the user has created.
     *
     * @Given /^I navigate to my "([^"]*)" report$/
     */
    public function i_navigate_to_my_report($reportname) {
        \behat_hooks::set_step_readonly(false);
        $this->execute('behat_totara_core::i_click_on_in_the_totara_menu', 'Reports');
        $this->execute("behat_general::i_click_on_in_the", array($this->escape($reportname), 'link', ".reportmanager", "css_element"));
        $this->execute("behat_general::assert_element_contains_text", array($this->escape($reportname), "#region-main h2", "css_element"));

    }

    /**
     * Confirms the the given value exists in the report for the given row+column.
     *
     * @Then /^I should see "([^"]*)" in the "([^"]*)" report column for "([^"]*)"$/
     */
    public function i_should_see_in_the_report_column_for($value, $column, $rowcontent) {
        \behat_hooks::set_step_readonly(true);
        $rowsearch = behat_context_helper::escape($rowcontent);
        $valuesearch = behat_context_helper::escape($value);
        // Find the table.
        $xpath  = "//table[contains(concat(' ', normalize-space(@class), ' '), ' reportbuilder-table ')]";
        // Find the row
        $xpath .= "//td/*[contains(text(),{$rowsearch})]//ancestor::tr";
        // Find the column
        $xpath .= "/td[contains(concat(' ', normalize-space(@class), ' '), ' {$column} ')]";
        // Find the row
        $xpath .= "/self::*[child::text()[contains(.,{$valuesearch})] or *[child::text()[contains(.,{$valuesearch})]]]";

        $this->find(
            'xpath',
            $xpath,
            new ExpectationException('The given value could not be found within the report builder report', $this->getSession())
        );
        return true;
    }

    /**
     * Confirms the the given value does not exist in the report for the given row+column.
     *
     * @Then /^I should not see "([^"]*)" in the "([^"]*)" report column for "([^"]*)"$/
     */
    public function i_should_not_see_in_the_report_column_for($value, $column, $rowcontent) {
        \behat_hooks::set_step_readonly(true);
        try {
            $this->i_should_see_in_the_report_column_for($value, $column, $rowcontent);
        } catch (ExpectationException $ex) {
            return true;
        }
        throw new ExpectationException('The given value was found within the report builder report', $this->getSession());
    }
}