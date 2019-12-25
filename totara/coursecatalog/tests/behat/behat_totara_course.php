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
 * @author Maria Torres <maria.torres@totaralearning.com>
 * @package coursecatalog
 */

require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');

use Behat\Mink\Exception\ExpectationException as ExpectationException;

/**
 * The Totara core definitions class.
 *
 * This class contains the definitions for course Totara functionality.
 *
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright Copyright (C) 2010-2013 Totara Learning Solutions LTD
 */
class behat_totara_course extends behat_base {

    /**
     * Returns the id of the category with the given idnumber.
     *
     * Please note that this function requires the category to exist. If it does not exist an ExpectationException is thrown.
     *
     * @param string $idnumber
     * @return string
     * @throws ExpectationException
     */
    protected function get_category_id($idnumber) {
        global $DB;
        try {
            return $DB->get_field('course_categories', 'id', array('idnumber' => $idnumber), MUST_EXIST);
        } catch (dml_missing_record_exception $ex) {
            throw new ExpectationException(sprintf("There is no category in the database with the idnumber '%s'", $idnumber), $this->getSession());
        }
    }

    /**
     * Returns the category node from within the listing on the management page.
     *
     * @param string $idnumber
     * @return \Behat\Mink\Element\NodeElement
     */
    protected function get_course_category_listing_node_by_idnumber($idnumber) {
        $id = $this->get_category_id($idnumber);
        $selector = sprintf('.category[data-categoryid="%d"] > div[class="info"]', $id);
        return $this->find('css', $selector);
    }

    /**
     * Click to expand a category revealing its sub categories within the course catalog.
     *
     * @Given /^I click to expand category "(?P<idnumber_string>(?:[^"]|\\")*)" in the course catalog$/
     * @param string $idnumber
     */
    public function i_click_to_expand_category_in_the_course_catalog($idnumber) {
        $categorynode = $this->get_course_category_listing_node_by_idnumber($idnumber);
        $exception = new ExpectationException('Category "' . $idnumber . '" does not contain aria-expanded to toggle.', $this->getSession());
        $categorynode->click();
    }
}
