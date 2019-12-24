<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2017 onwards Totara Learning Solutions LTD
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
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
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 *
 * @package auth_approved
 */

require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');

class behat_auth_approved extends behat_base {
    /**
     * @When /^I confirm self-registration request from email "([^"]*)"$/
     */
    public function confirm_request($email) {
        \behat_hooks::set_step_readonly(false);
        global $DB;
        $request = $DB->get_record('auth_approved_request', array('email' => $email));
        $this->getSession()->visit($this->locate_path('auth/approved/confirm.php?token=' . $request->confirmtoken));
        $this->wait_for_pending_js();
    }

    /**
     * @When /^I use magic for auth approved to set last password change to "([^"]*)" for user "([^"]*)"$/
     */
    public function set_last_pasword_change($interval, $username) {
        \behat_hooks::set_step_readonly(true); // Backend action.

        $user = core_user::get_user_by_username($username, 'id', null, MUST_EXIST);

        $date = new DateTime('@' . time());
        $interval = new DateInterval($interval);
        $date->sub($interval);

        set_user_preference("auth_approved_passwordupdatetime", $date->getTimestamp(), $user->id);
    }
}
