<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2010 onwards Totara Learning Solutions LTD
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
 * @author Valerii Kuznetsov <valerii.kuznetsov@totaralms.com>
 * @package webservice
 */
require_once(__DIR__ . '/../../../lib/behat/behat_base.php');

use Behat\Mink\Exception\ElementNotFoundException as ElementNotFoundException;

class behat_webservice extends behat_base {

    /**
     * Toggle the state of an web service protocol.
     *
     * @Given /^I "(Enable|Disable)" the "([^"]*)" web service protocol/
     */
    public function i_the_web_service_protocol($state, $element) {
        \behat_hooks::set_step_readonly(false);

        $xpath = "//table[@id='webserviceprotocols']//descendant::text()[contains(.,'{$element}')]//ancestor::tr//a//span[@title='{$state}']";
        $exception = new ElementNotFoundException($this->getSession(), 'Could not find state switch for the given web service protocol');
        $node = $this->find('xpath', $xpath, $exception);
        if ($node) {
            $node->click();
        }
    }
}
