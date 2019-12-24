<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2017 onwards Totara Learning Solutions LTD
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
 * @package user
 * @author Rob Tyler <rob.tyler@totaralearning.com>
 */

// NOTE: no MOODLE_INTERNAL test here, this file may be required by behat before including /config.php.

/**
 * Behat steps to work with Totara custom fields
 */
class behat_user extends behat_base {

    /**
     * Checks the form validation message for a particular custom field using the user profile field short name.
     *
     * @Given /^I should see the form validation error "([^"]*)" for the "([^"]*)" user profile field$/
     *
     * @param string $error The form error message to check for.
     * @param string $field The short name of the user profile field to match the error agains.
     */
    public function i_should_see_the_form_validation_error_for_the_user_profile_field($error, $field) {
        $field_literal = behat_context_helper::escape('fitem_id_profile_field_' . $field);
        $this->execute('behat_general::assert_element_contains_text', array($error, '//div[contains(@id,' . $field_literal . ')]', 'xpath_element'));
    }

    /**
     * Checks there is no form validation message for a particular custom field using the user profile field short name.
     *
     * @Given /^I should not see the form validation error "([^"]*)" for the "([^"]*)" user profile field$/
     *
     * @param string $error The form error message to check for.
     * @param string $field The short name of the user profile field to match the error agains.
     */
    public function i_should_not_see_the_form_validation_error_for_the_user_profile_field($error, $field) {
        $field_literal = behat_context_helper::escape('fitem_id_profile_field_' . $field);
        $this->execute('behat_general::assert_element_not_contains_text', array($error, '//div[contains(@id,' . $field_literal . ')]', 'xpath_element'));
    }

}