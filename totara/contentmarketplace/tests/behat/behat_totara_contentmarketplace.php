<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
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
 * @author Michael Dunstan <michael.dunstan@androgogic.com>
 * @package totara_contentmarketplace
 */


require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');


class behat_totara_contentmarketplace extends behat_base {

    /**
     * Navigates directly to the Totara content markerplace test filters.
     *
     * This page is only used for acceptance testing and does not appear in the navigation.
     * For that reason we must navigate directly to it.
     *
     * @Given /^I navigate to the content marketplace test filters$/
     */
    public function i_navigate_to_the_content_marketplace_test_filters() {
        \behat_hooks::set_step_readonly(false);
        $url = new moodle_url('/totara/contentmarketplace/tests/fixtures/test_filters.php');
        $this->getSession()->visit($url->out(false));
        $this->wait_for_pending_js();
    }

}
