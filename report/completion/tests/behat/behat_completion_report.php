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
 * @author Simon Player <simon.player@totaralms.com>
 * @package report_completion
 * @copyright 2015 Totara Learning Solutions LTD
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');

use Behat\Mink\Exception\DriverException as DriverException;

class behat_completion_report extends behat_base {

    /**
     * Complete course via RPL
     *
     * @Given /^I complete the course via rpl for "([^"]*)" with text "([^"]*)"$/
     * @param string $users_name
     * @param string $rpltext
     */
    public function i_complete_course_via_rpl($users_name, $rpltext) {
        \behat_hooks::set_step_readonly(false);

        if (!$this->running_javascript()) {
            throw new DriverException('Complete course via RPL step is not available with Javascript disabled');
        }

        $xpath = "//tr[contains(descendant::*, '" . $users_name . "')]//td[@class='completion-progresscell rpl-course']";
        $td = $this->find('xpath', $xpath);

        $xpath = "//a";
        $tick = $td->find('xpath', $xpath);
        $tick->click();
        $this->wait_for_pending_js();

        $input = $td->find('css', '.rplinput');
        if (!method_exists($input, 'setValue')) {
            throw new \Behat\Mink\Exception\ExpectationException('Cannot set RPL record for ' . $users_name, $this->getSession());
        }
        $input->setValue($rpltext);
        $tick->click();
    }

    /**
     * Delete course RPL
     *
     * @Given /^I delete the course rpl for "([^"]*)"$/
     * @param string $users_name
     */
    public function i_delete_course_rpl($users_name) {
        \behat_hooks::set_step_readonly(false);

        if (!$this->running_javascript()) {
            throw new DriverException('Delete course RPL step is not available with Javascript disabled');
        }

        $xpath = "//tr[contains(descendant::*, '" . $users_name . "')]";
        $tr = $this->find('xpath', $xpath);

        $xpath = "//td[@class='completion-progresscell rpl-course']";
        $td = $tr->find('xpath', $xpath);

        $xpath = "//a";
        $tick = $td->find('xpath', $xpath);
        $tick->click();
        $this->wait_for_pending_js();

        $xpath = "//a[@title='Delete this RPL']";
        $delete = $td->find('xpath', $xpath);
        $delete->click();
    }

}
