<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @package totara_core
 */

require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');

/**
 * Class step definitions for Totara main menu.
 *
 */
class behat_totara_admin_main_menu extends behat_base {

    /**
     * I rename admin main menu group to
     *
     * @When /^I rename admin main menu group "([^"]*)" to "([^"]*)"$/
     */
    public function i_rename_admin_main_menu_group($name, $newname) {
        \behat_hooks::set_step_readonly(false);

        $xpath = "//div/child::h3[contains(., '" . $name . "')]/a";
        $textnode = $this->find('xpath', $xpath);
        $textnode->click();

        $xpath = "//div/child::h3[contains(., '" . $name . "')]/input";
        $element = $this->getSession()->getDriver()->getWebDriverSession()->element('xpath', $xpath);
        $existingvaluelength = strlen($element->attribute('value'));
        $value = str_repeat(\WebDriver\Key::BACKSPACE . \WebDriver\Key::DELETE, $existingvaluelength) . $newname;
        $element->postValue(['value' => [$value, "\r\n"]]);
    }
}
