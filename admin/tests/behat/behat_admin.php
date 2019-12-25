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
 * Steps definitions related with administration.
 *
 * @package   core_admin
 * @category  test
 * @copyright 2013 David Monllaó
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// NOTE: no MOODLE_INTERNAL test here, this file may be required by behat before including /config.php.

require_once(__DIR__ . '/../../../lib/behat/behat_base.php');
require_once(__DIR__ . '/../../../lib/behat/behat_field_manager.php');

use Behat\Gherkin\Node\TableNode as TableNode,
    Behat\Mink\Exception\ElementNotFoundException as ElementNotFoundException;

/**
 * Site administration level steps definitions.
 *
 * @package    core_admin
 * @category   test
 * @copyright  2013 David Monllaó
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_admin extends behat_base {

    /**
     * Sets the specified site settings. A table with | Setting label | value | is expected.
     *
     * @Given /^I set the following administration settings values:$/
     * @param TableNode $table
     */
    public function i_set_the_following_administration_settings_values(TableNode $table) {
        \behat_hooks::set_step_readonly(false);

        if (!$data = $table->getRowsHash()) {
            return;
        }

        foreach ($data as $label => $value) {

            // We expect admin block to be visible, otherwise go to homepage.
            if (!$this->getSession()->getPage()->find('css', '.block_settings')) {
                $this->getSession()->visit($this->locate_path('/index.php?redirect=0'));
                $this->wait_for_pending_js();
            }

            // Search by label.
            $searchbox = $this->find_field(get_string('searchinsettings', 'admin'));
            $searchbox->setValue($label);
            // Totara: Roots themes are using button elements with different label.
            $fieldxpath = "//form[@class='adminsearchform']//input[@type='submit'] | //form[@class='adminsearchform']//button[@type='submit']";
            $submitsearch = $this->find('xpath', $fieldxpath);
            $submitsearch->click();
            $this->wait_for_pending_js();

            // Admin settings does not use the same DOM structure than other moodle forms
            // but we also need to use lib/behat/form_field/* to deal with the different moodle form elements.
            $exception = new ElementNotFoundException($this->getSession(), '"' . $label . '" administration setting ');

            // The argument should be converted to an xpath literal.
            $label = behat_context_helper::escape($label);

            // Single element settings.
            try {
                $fieldxpath = "//*[self::input | self::textarea | self::select][not(./@type = 'submit' or ./@type = 'image' or ./@type = 'hidden')]" .
                    "[@id=//label[contains(normalize-space(.), $label)]/@for or " .
                    "@id=//span[contains(normalize-space(.), $label)]/preceding-sibling::label[1]/@for]";
                $fieldnode = $this->find('xpath', $fieldxpath, $exception);

                $formfieldtypenode = $this->find('xpath', $fieldxpath . "/ancestor::div[@class='form-setting']" .
                    "/child::div[contains(concat(' ', @class, ' '),  ' form-')]/child::*/parent::div");

            } catch (ElementNotFoundException $e) {

                // Multi element settings, interacting only the first one.
                $fieldxpath = "//*[label[normalize-space(.)= $label]|span[normalize-space(.)= $label]]/" .
                    "ancestor::div[contains(concat(' ', normalize-space(@class), ' '), ' form-item ')]" .
                    "/descendant::div[@class='form-group']/descendant::*[self::input | self::textarea | self::select]" .
                    "[not(./@type = 'submit' or ./@type = 'image' or ./@type = 'hidden')]";
                $fieldnode = $this->find('xpath', $fieldxpath);

                // It is the same one that contains the type.
                $formfieldtypenode = $fieldnode;
            }

            // Getting the class which contains the field type.
            $classes = explode(' ', $formfieldtypenode->getAttribute('class'));
            foreach ($classes as $class) {
                if (substr($class, 0, 5) == 'form-') {
                    $type = substr($class, 5);
                }
            }

            // Instantiating the appropiate field type.
            $field = behat_field_manager::get_field_instance($type, $fieldnode, $this->getSession());
            $field->set_value($value);

            $this->find_button(get_string('savechanges'))->press();
            $this->wait_for_pending_js();
        }
    }

    /**
     * Sets the specified site settings. A table with | config | value | (optional)plugin | is expected.
     *
     * @Given /^the following config values are set as admin:$/
     * @param TableNode $table
     */
    public function the_following_config_values_are_set_as_admin(TableNode $table) {
        \behat_hooks::set_step_readonly(false);

        if (!$data = $table->getRowsHash()) {
            return;
        }

        foreach ($data as $config => $value) {
            // Default plugin value is null.
            $plugin = null;

            if (is_array($value)) {
                $plugin = $value[1];
                $value = $value[0];
            }
            set_config($config, $value, $plugin);
        }
    }

    /**
     * Make sure there are no new admin settings waiting to be confirmed.
     *
     * NOTE: this is a workaround for suddenly popping up settings such as when adding custom fields,
     *       this is not intended for real upgrades.
     *
     * @Given /^I confirm new default admin settings$/
     */
    public function i_confirm_new_default_admin_settings() {
        \behat_hooks::set_step_readonly(false);

        $this->getSession()->visit($this->locate_path('admin/upgradesettings.php'));
        $this->wait_for_pending_js();

        $url = $this->getSession()->getCurrentUrl();
        if (strpos($url, 'upgradesettings.php') !== false) {
            $this->execute('behat_forms::press_button', 'Save changes');
            $this->wait_for_pending_js();
        }

        $this->getSession()->visit($this->locate_path('index.php'));
        $this->wait_for_pending_js();
    }

}
