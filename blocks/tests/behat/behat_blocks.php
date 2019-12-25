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
 * Steps definitions related with blocks.
 *
 * @package   core_block
 * @category  test
 * @copyright 2012 David Monllaó
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// NOTE: no MOODLE_INTERNAL test here, this file may be required by behat before including /config.php.
use Behat\Mink\Exception\DriverException;
use Behat\Mink\Exception\ElementNotFoundException as ElementNotFoundException;

require_once(__DIR__ . '/../../../lib/behat/behat_base.php');

/**
 * Blocks management steps definitions.
 *
 * @package    core_block
 * @category   test
 * @copyright  2012 David Monllaó
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_blocks extends behat_base {

    /**
     * Adds the selected block to the left column region. Editing mode must be previously enabled.
     *
     * @Given /^I add the "(?P<block_name_string>(?:[^"]|\\")*)" block$/
     * @param string $blockname
     */
    public function i_add_the_block($blockname) {
        $this->i_add_the_block_to_region($blockname, 'side-pre');
    }

    /**
     * Adds the selected block to a given region. Editing mode must be previously enabled.
     *
     * @Given /^I add the "(?P<block_name_string>(?:[^"]|\\")*)" block to the "(?P<region_string>(?:[^"]|\\")*)" region$/
     * @param string $blockname
     */
    public function i_add_the_block_to_region($blockname, $regionname) {
        if (!$this->running_javascript()) {
            throw new DriverException('Adding blocks requires JavaScript.');
        }

        $this->execute(
            "behat_general::i_click_on_in_the",
            array('.addBlock--trigger', 'css_element', '#block-region-' . $regionname, 'css_element')
        );
        $this->execute(
            "behat_general::i_click_on",
            array(".addBlock .popover .addBlockPopover--results_list_item[data-addblockpopover-blocktitle='" . $this->escape($blockname) . "']", "css_element")
        );
    }

    /**
     * Adds the selected block if it is not already present. Editing mode must be previously enabled.
     *
     * @Given /^I add the "(?P<block_name_string>(?:[^"]|\\")*)" block if not present$/
     * @param string $blockname
     */
    public function i_add_the_block_if_not_present($blockname) {
        try {
            $this->get_text_selector_node('block', $blockname);
        } catch (ElementNotFoundException $e) {
            $this->execute('behat_blocks::i_add_the_block', [$blockname]);
        }
    }

    /**
     * Docks a block. Editing mode should be previously enabled.
     *
     * @Given /^I dock "(?P<block_name_string>(?:[^"]|\\")*)" block$/
     * @param string $blockname
     */
    public function i_dock_block($blockname) {

        // Looking for both title and alt.
        $xpath = "//a[contains(.,'" . get_string('addtodock', 'block') . "')]";
        $this->execute('behat_general::i_click_on_in_the',
            array($xpath, "xpath_element", $this->escape($blockname), "block")
        );
    }

    /**
     * Opens a block's actions menu if it is not already opened.
     *
     * @Given /^I open the "(?P<block_name_string>(?:[^"]|\\")*)" blocks action menu$/
     * @throws DriverException The step is not available when Javascript is disabled
     * @param string $blockname
     */
    public function i_open_the_blocks_action_menu($blockname) {

        if (!$this->running_javascript()) {
            // Action menu does not need to be open if Javascript is off.
            return;
        }

        // If it is already opened we do nothing.
        $blocknode = $this->get_text_selector_node('block', $blockname);
        if ($blocknode->hasClass('action-menu-shown')) {
            return;
        }

        $this->execute('behat_general::i_click_on_in_the',
            array("a[role='menuitem']", "css_element", $this->escape($blockname), "block")
        );
    }

    /**
     * Clicks on Configure block for specified block. Page must be in editing mode.
     *
     * Argument block_name may be either the name of the block or CSS class of the block.
     *
     * @Given /^I configure the "(?P<block_name_string>(?:[^"]|\\")*)" block$/
     * @param string $blockname
     */
    public function i_configure_the_block($blockname) {
        // Note that since $blockname may be either block name or CSS class, we can not use the exact label of "Configure" link.

        $this->execute("behat_blocks::i_open_the_blocks_action_menu", $this->escape($blockname));

        $this->execute('behat_general::i_click_on_in_the',
            array("Configure", "link", $this->escape($blockname), "block")
        );
    }

    /**
     * Confirms that I can see a block with the given title.
     *
     * @Given /^I should see the "([^"]*)" block$/
     * @param string $title
     */
    public function i_should_see_the_block($title) {
        $this->execute('behat_general::should_exist', [ $this->get_block_xpath($title), 'xpath_element' ]);
    }

    /**
     * Confirms that I cannot see a block with the given title.
     *
     * @Given /^I should not see the "([^"]*)" block$/
     * @param string $title
     */
    public function i_should_not_see_the_block($title) {
        $this->execute('behat_general::should_not_exist', [ $this->get_block_xpath($title), 'xpath_element' ]);
    }

    /**
     * Confirms that I can see a block with the given title in the given region.
     *
     * @Given /^I should see the "([^"]*)" block in the "([^"]*)" region$/
     * @param string $title
     */
    public function i_should_see_the_block_in_region($title, $region) {
        $xpath = '//aside[@id="block-region-' . $region . '"]' . $this->get_block_xpath($title);
        $this->execute('behat_general::should_exist', [ $xpath, 'xpath_element' ]);
    }

    /**
     * Get block xpath commonly needed by methods.
     *
     * @param string $title
     * @return string
     */
    private function get_block_xpath(string $title): string {
        $xpathliteral = behat_context_helper::escape($title);
        return '//div[contains(concat(\' \', normalize-space(@class), \' \'), \' block \')]//h2[text()[contains(.,' . $xpathliteral . ')]]';
    }

    /**
     * Ensures that block can be added to the page but does not actually add it.
     *
     * @Then /^the add block selector should contain "(?P<block_name_string>(?:[^"]|\\")*)" block$/
     * @param string $blockname
     */
    public function the_add_block_selector_should_contain_block($blockname) {
        $this->execute(
            'behat_general::should_exist',
            [ ".addBlockPopover--results_list_item[data-addblockpopover-blocktitle='" . $blockname . "']", "css_element" ]
        );
    }

    /**
     * Ensures that block can not be added to the page.
     *
     * @Then /^the add block selector should not contain "(?P<block_name_string>(?:[^"]|\\")*)" block$/
     * @param string $blockname
     */
    public function the_add_block_selector_should_not_contain_block($blockname) {
        $this->execute(
            'behat_general::should_not_exist',
            [ ".addBlockPopover--results_list_item[data-addblockpopover-blocktitle='" . $blockname . "']", "css_element" ]
        );
    }
}
