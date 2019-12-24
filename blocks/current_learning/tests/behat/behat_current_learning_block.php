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
 * @author Simon Player <simon.player@totaralearning.com>
 * @package block_current_learning
 */

// NOTE: no MOODLE_INTERNAL used, this file may be required by behat before including /config.php.
require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');

class behat_current_learning_block extends behat_base {

    /**
     * Click on the expand item for a program or certification if the current learning block.
     *
     * @Given /^I toggle "([^"]*)" in the current learning block$/
     */
    public function i_toggle_item_in_current_learning_block($program) {
        \behat_hooks::set_step_readonly(false);
        $program_xpath = behat_context_helper::escape($program);
        $xpath = ".//li[div[@class[contains(.,'block_current_learning-row-item')]][.//text()[.=" . $program_xpath . "]]]";
        $row = $this->find(
            'xpath',
            $xpath,
            new \Behat\Mink\Exception\ExpectationException('Could not find item row for "'.$program.'" in the current learning block' . $xpath, $this->getSession())
        );

        $xpath = "//*[@class[contains(.,'expand-collapse-icon-wrap')]]";
        $node = $row->find(
            'xpath',
            $xpath,
            new \Behat\Mink\Exception\ExpectationException('Could not find specific expand icon for "'.$program.'" in the current learning block' . $xpath, $this->getSession())
        );
        $node->click();
    }

    /**
     * Check if a course exists within a program or certification in the current learning block.
     *
     * @Given /^I should see "([^"]*)" in "([^"]*)" within the current learning block$/
     */
    public function i_should_see_course_in_program_within_the_current_learning_block($course, $program) {
        $program_xpath = behat_context_helper::escape($program);
        $xpath = ".//li[div[@class[contains(.,'block_current_learning-row-item')]][.//text()[.=" . $program_xpath . "]]]";
        $this->execute('behat_general::assert_element_contains_text', array($course, $xpath, 'xpath_element'));
    }

    /**
     * Check if a course exists within a program or certification in the current learning block.
     *
     * @Given /^I should not see "([^"]*)" in "([^"]*)" within the current learning block$/
     */
    public function i_should_not_see_course_in_program_within_the_current_learning_block($course, $program) {
        $program_xpath = behat_context_helper::escape($program);
        $xpath = ".//li[div[@class[contains(.,'block_current_learning-row-item')]][.//text()[.=" . $program_xpath . "]]]";
        $this->execute('behat_general::assert_element_not_contains_text', array($course, $xpath, 'xpath_element'));
    }

    /**
     * Check if the icon for a row has the expected hover text.
     *
     * @Given /^the current learning block learning type icon text is "([^"]*)" for the "([^"]*)"$/
     */
    public function the_current_learning_block_icon_text_is($text, $program) {
        $program_xpath = behat_context_helper::escape($program);
        $xpath = ".//li[div[@class[contains(.,'block_current_learning-row-item')]][.//text()[.=" . $program_xpath . "]]]";
        $row = $this->find(
            'xpath',
            $xpath,
            new \Behat\Mink\Exception\ExpectationException('Could not find item row for "'.$program.'" in the current learning block' . $xpath, $this->getSession())
        );

        $xpath = "//*[@data-toggle='tooltip']";
        $node = $row->find(
            'xpath',
            $xpath,
            new \Behat\Mink\Exception\ExpectationException('Could not find icon for "'.$program.'" in the current learning block' . $xpath, $this->getSession())
        );
        $titletext = $node->getAttribute('data-original-title');
        if ($titletext !== $text) {
            throw new \Behat\Mink\Exception\ExpectationException('Hover text for "'.$program.'" in the current learning block "'.$titletext.'" does not match expected "'.$text.'"', $this->getSession());
        }
    }

    /**
     * Check that the item is not togglable.
     *
     * @Given /^I should not be able to toggle "([^"]*)" row within the current learning block$/
     */
    public function i_should_not_be_able_to_toggle_row($program) {
        $program_xpath = behat_context_helper::escape($program);
        $xpath = ".//li[div[@class[contains(.,'block_current_learning-row-item')]][.//text()[.=" . $program_xpath . "]]]";
        $row = $this->find(
            'xpath',
            $xpath,
            new \Behat\Mink\Exception\ExpectationException('Could not find item row for "'.$program.'" in the current learning block' . $xpath, $this->getSession())
        );

        $expandiconxpath = "//*[@class[contains(.,'expand-collapse-icon-wrap')]]";

        try {
            $this->find('xpath', $expandiconxpath, false, $row, self::REDUCED_TIMEOUT);
        } catch (\Behat\Mink\Exception\ElementNotFoundException $e) {
            // Yay not found.
            return;
        }

        throw new \Behat\Mink\Exception\ExpectationException("\"$program\" row in current learning block was togglable", $this->getSession());
    }
}
