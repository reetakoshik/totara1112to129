<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2016 onwards Totara Learning Solutions LTD
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
 * @author Sam Hemelryk <sam.hemelryk@totaralms.com>
 * @package totara_job
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright 2016 Totara Learning Solutions Ltd {@link http://www.totaralms.com/}
 */

require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');

use Behat\Mink\Exception\ExpectationException;
use Behat\Mink\Exception\DriverException;

/**
 * Totara Job behat definitions.
 *
 * @package totara_job
 * @copyright 2016 Totara Learning Solutions Ltd {@link http://www.totaralms.com/}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 */
class behat_totara_job extends behat_base {

    /**
     * Checks the number of job assignments the user has.
     *
     * @Given /^there should be "(\d+)" totara job assignments$/
     *
     * @throws ExpectationException
     * @param int $count
     */
    public function there_should_be_x_totara_job_assignments($count) {
        \behat_hooks::set_step_readonly(true);

        // Note we don't use $this->find here - we should never ever need spin here.
        // If content becomes dynamic THEN we may need to convert this to spin, but lets try avoid it first.
        $elementxpath = '//*[contains(@class, \'totara-job-management-listing\')]/*/li';
        $nodes = $this->getSession()->getDriver()->find($elementxpath);

        if (count($nodes) !== (int)$count) {
            throw new ExpectationException($count.' job assignments were expected, but '.count($nodes).' were found', $this->getSession());
        }
    }

    /**
     * Checks the the job assignment at the given position matches the given full name.
     *
     * @Given /^job assignment at position "(\d+)" should be "([^"]+)"$/
     *
     * @throws ExpectationException
     * @param int $position
     * @param string $fullname
     */
    public function job_assignment_at_position_X_should_be($position, $fullname) {
        \behat_hooks::set_step_readonly(true);

        // Note we don't use $this->find here - we should never ever need spin here.
        // If content becomes dynamic THEN we may need to convert this to spin, but lets try avoid it first.
        $elementxpath = '(//*[contains(@class, \'totara-job-management-listing\')]/*/li)['.(string)(int)$position.']';
        $nodes = $this->getSession()->getDriver()->find($elementxpath);

        if (count($nodes) === 0) {
            throw new ExpectationException('Job Assignment at position '.$position.' could not be found.', $this->getSession());
        }
        /** @var \Behat\Mink\Element\NodeElement $li */
        $li = reset($nodes);
        $link = $li->find('xpath', '/a[text()='.behat_context_helper::escape($fullname).']');
        if (empty($link)) {
            throw new ExpectationException('Job Assignment at position '.$position.' is not the expected "'.$fullname.'".', $this->getSession());
        }
    }

    /**
     * Moves a job assignment up or down given its fullname
     *
     * @Given /^I move job assignment "([^"]+)" (up|down)$/
     *
     * @throws ExpectationException
     * @throws DriverException
     * @param string $fullname
     * @param string $direction Either 'up' or 'down'
     */
    public function i_move_job_assignment($fullname, $direction) {
        \behat_hooks::set_step_readonly(false);

        if (!$this->running_javascript()) {
            throw new DriverException('Moving job assignments requires JavaScript');
        }

        // Normalise direction.
        if ($direction !== 'up') {
            $direction = 'down';
        }

        // Note we don't use $this->find here - we should never ever need spin here.
        // If content becomes dynamic THEN we may need to convert this to spin, but lets try avoid it first.
        $fnliteral = behat_context_helper::escape($fullname);
        $elementxpath = '//*[contains(@class, \'totara-job-management-listing\')]/*/li//a[text()='.$fnliteral.']/parent::li//a[@title="Move '.$direction.'"]';
        $nodes = $this->getSession()->getDriver()->find($elementxpath);

        if (count($nodes) === 0) {
            throw new ExpectationException('Job Assignment "'.$fullname.'"" could not be moved '.$direction, $this->getSession());
        }

        /** @var \Behat\Mink\Element\NodeElement $link */
        $link = reset($nodes);

        if (!$link->isVisible()) {
            throw new ExpectationException('Job Assignment "'.$fullname.'"" could not be moved '.$direction.' because it is not visible', $this->getSession());
        }

        $link->click();

        // Wait for any JavaScript to complete.
        $this->wait_for_pending_js();
    }

    /**
     * Deletes a job assignment given its full name.
     *
     * @Given /^I click the delete icon for the "([^"]+)" job assignment$/
     *
     * @throws ExpectationException
     * @throws DriverException
     * @param string $fullname
     */
    public function i_click_the_delete_icon_for_the_job_assignment($fullname) {
        \behat_hooks::set_step_readonly(false);

        if (!$this->running_javascript()) {
            throw new \Behat\Mink\Exception\DriverException('Deleting job assignments requires JavaScript');
        }

        // Note we don't use $this->find here - we should never ever need spin here.
        // If content becomes dynamic THEN we may need to convert this to spin, but lets try avoid it first.
        $fnliteral = behat_context_helper::escape($fullname);
        $elementxpath = '//*[contains(@class, \'totara-job-management-listing\')]/*/li//a[text()='.$fnliteral.']/parent::li//a[@title="Delete"]';
        $nodes = $this->getSession()->getDriver()->find($elementxpath);

        if (count($nodes) === 0) {
            throw new ExpectationException('Job Assignment "'.$fullname.'"" could not be deleted', $this->getSession());
        }

        /** @var \Behat\Mink\Element\NodeElement $link */
        $link = reset($nodes);

        if (!$link->isVisible()) {
            throw new ExpectationException('Job Assignment "'.$fullname.'"" could not be deleted because it is not visible', $this->getSession());
        }

        $link->click();

        // Wait for any JavaScript to complete.
        $this->wait_for_pending_js();
    }

    /**
     * Check a user can/cannot sort a job assignment.
     *
     * @Given /^I (should|should not) be able to delete the "([^"]+)" totara job assignment$/
     *
     * @throws ExpectationException
     * @throws DriverException
     * @param string $not
     */
    public function i_should_be_able_to_delete_the_totara_job_assignment($not, $fullname) {
        \behat_hooks::set_step_readonly(true);

        $not = ($not === 'should not');

        // Note we don't use $this->find here - we should never ever need spin here.
        // If content becomes dynamic THEN we may need to convert this to spin, but lets try avoid it first.
        $fnliteral = behat_context_helper::escape($fullname);
        $elementxpath = '//*[contains(@class, \'totara-job-management-listing\')]/*/li//a[text()='.$fnliteral.']/parent::li//a[@title="Delete"]';
        $nodes = $this->getSession()->getDriver()->find($elementxpath);

        if ($not) {
            if (count($nodes) > 0) {
                throw new ExpectationException('Job Assignment "'.$fullname.'"" can be deleted.', $this->getSession());
            }
        } else {
            if (count($nodes) === 0) {
                throw new ExpectationException('Job Assignment "'.$fullname.'"" can not be deleted.', $this->getSession());
            }
        }

    }

    /**
     * Check a user can/cannot sort a job assignment.
     *
     * @Given /^I (should|should not) be able to sort the "([^"]+)" totara job assignment$/
     *
     * @throws ExpectationException
     * @throws DriverException
     * @param string $not
     */
    public function i_should_be_able_to_sort_the_totara_job_assignment($not, $fullname) {
        \behat_hooks::set_step_readonly(true);

        $not = ($not === 'should not');

        // Note we don't use $this->find here - we should never ever need spin here.
        // If content becomes dynamic THEN we may need to convert this to spin, but lets try avoid it first.
        $fnliteral = behat_context_helper::escape($fullname);
        $elementxpath = '//*[contains(@class, \'totara-job-management-listing\')]/*/li//a[text()='.$fnliteral.']/parent::li//a[@data-function="move"]';
        $nodes = $this->getSession()->getDriver()->find($elementxpath);

        if ($not) {
            if (count($nodes) > 0) {
                throw new ExpectationException('Job Assignment "'.$fullname.'"" can be sorted.', $this->getSession());
            }
        } else {
            if (count($nodes) === 0) {
                throw new ExpectationException('Job Assignment "'.$fullname.'"" can not be sorted.', $this->getSession());
            }
        }

    }

}