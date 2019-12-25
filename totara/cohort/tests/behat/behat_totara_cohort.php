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
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 * @package totara_cohort
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__.'/../../../../lib/behat/behat_base.php');

class behat_totara_cohort extends behat_base {

    /**
     * Deletes an associated enrolled learning item from the Enrolled learning tab of a cohort.
     *
     * @Given /^I click to delete the cohort enrolled learning association on "([^"]*)"/
     *
     * @param string $name
     */
    public function create_appraisal_questions_on_page($name) {
        \behat_hooks::set_step_readonly(false);

        $name_literal = $this->getSession()->getSelectorsHandler()->xpathLiteral($name);
        $xpath = '//table[@id="cohort_associations_enrolled"]//a[text()='.$name_literal.']/ancestor::tr//a[contains(@class, "learning-delete")]';
        $exception = new \Behat\Mink\Exception\ExpectationException('The delete icon for the enrolled learning association with '.$name_literal.' could not be found.', $this->getSession());
        $node = $this->find('xpath', $xpath, $exception);
        $node->click();
        // There is a bloody browser confirmation here.
        $this->getSession()->getDriver()->getWebDriverSession()->accept_alert();
    }
}
