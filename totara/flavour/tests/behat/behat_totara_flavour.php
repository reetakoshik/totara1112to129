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
 * @author Petr Skoda <petr.skoda@totaralms.com>
 * @package totara_flavour
 */

// NOTE: no MOODLE_INTERNAL test here, this file may be required by behat before including /config.php.

class behat_totara_flavour extends behat_base {
    /**
     * Fake enforcing of a flavour via config.php.
     *
     * Note that the flavour needs to be activated from the admin overview UI.
     *
     * @Given /^flavour "(?P<prefix_string>(?:[^"]|\\")*)" is forced$/
     * @param string $flavour
     */
    public function flavour_is_forced($flavour) {
        \behat_hooks::set_step_readonly(true); // Backend action.

        set_config('forceflavour', $flavour);
    }

    /**
     * Stop enforcing of a flavour via config.php.
     *
     * Note that the flavour needs to be deactivated from the admin overview UI.
     *
     * @Given /^no flavour is forced$/
     */
    public function no_flavour_is_forced() {
        \behat_hooks::set_step_readonly(true); // Backend action.

        unset_config('forceflavour');
    }

    /**
     * Fake activation of a flavour.
     *
     * NOTE: this is not compatible with forceflavour.
     *
     * @Given /^flavour "(?P<prefix_string>(?:[^"]|\\")*)" is active$/
     * @param string $flavour
     */
    public function flavour_is_active($flavour) {
        \behat_hooks::set_step_readonly(true); // Backend action.

        \totara_flavour\helper::set_active_flavour('flavour_' . $flavour);
    }

    /**
     * Deactivate flavours.
     *
     * NOTE: this is not compatible with forceflavour.
     *
     * @Given /^no flavour is active$/
     */
    public function no_flavour_is_active() {
        \behat_hooks::set_step_readonly(true); // Backend action.

        \totara_flavour\helper::set_active_flavour('');
    }
}
