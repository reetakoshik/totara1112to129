<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * General use steps definitions.
 *
 * @package   core
 * @category  test
 * @copyright 2012 David Monllaó
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// NOTE: no MOODLE_INTERNAL test here, this file may be required by behat before including /config.php.

require_once(__DIR__ . '/../../behat/behat_base.php');

use Behat\Mink\Exception\ExpectationException as ExpectationException,
    Behat\Mink\Exception\ElementNotFoundException as ElementNotFoundException,
    Behat\Mink\Exception\DriverException as DriverException,
    WebDriver\Exception\NoSuchElement as NoSuchElement,
    WebDriver\Exception\StaleElementReference as StaleElementReference,
    Behat\Gherkin\Node\TableNode as TableNode;

/**
 * Cross component steps definitions.
 *
 * Basic web application definitions from MinkExtension and
 * BehatchExtension. Definitions modified according to our needs
 * when necessary and including only the ones we need to avoid
 * overlapping and confusion.
 *
 * @package   core
 * @category  test
 * @copyright 2012 David Monllaó
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_general extends behat_base {

    /**
     * @var string used by {@link switch_to_window()} and
     * {@link switch_to_the_main_window()} to work-around a Chrome browser issue.
     */
    const MAIN_WINDOW_NAME = '__totara_behat_main_window_name';

    /**
     * @var string when we want to check whether or not a new page has loaded,
     * we first write this unique string into the page. Then later, by checking
     * whether it is still there, we can tell if a new page has been loaded.
     */
    const PAGE_LOAD_DETECTION_STRING = 'new_page_not_loaded_since_behat_started_watching';

    /**
     * @var $pageloaddetectionrunning boolean Used to ensure that page load detection was started before a page reload
     * was checked for.
     */
    private $pageloaddetectionrunning = false;

    /**
     * Opens Moodle homepage.
     *
     * @Given /^I am on homepage$/
     */
    public function i_am_on_homepage() {
        \behat_hooks::set_step_readonly(false);
        $this->getSession()->visit($this->locate_path('/'));
        $this->wait_for_pending_js();
    }

    /**
     * Opens Moodle site homepage.
     *
     * @Given /^I am on site homepage$/
     */
    public function i_am_on_site_homepage() {
        \behat_hooks::set_step_readonly(false);
        $this->getSession()->visit($this->locate_path('/?redirect=0'));
        $this->wait_for_pending_js();
    }

    /**
     * Opens course index page.
     *
     * @Given /^I am on course index$/
     */
    public function i_am_on_course_index() {
        \behat_hooks::set_step_readonly(false);
        $this->getSession()->visit($this->locate_path('/course/index.php'));
        $this->wait_for_pending_js();
    }

    /**
     * Reloads the current page.
     *
     * @Given /^I reload the page$/
     */
    public function reload() {
        \behat_hooks::set_step_readonly(false);
        $this->getSession()->reload();
        $this->wait_for_pending_js();
    }

    /**
     * Follows the page redirection. Use this step after any action that shows a message and waits for a redirection
     *
     * @Given /^I wait to be redirected$/
     */
    public function i_wait_to_be_redirected() {
        \behat_hooks::set_step_readonly(false);

        // Xpath and processes based on core_renderer::redirect_message(), core_renderer::$metarefreshtag and
        // moodle_page::$periodicrefreshdelay possible values.
        if (!$metarefresh = $this->getSession()->getPage()->find('xpath', "//head/descendant::meta[@http-equiv='refresh']")) {
            // We don't fail the scenario if no redirection with message is found to avoid race condition false failures.
            return true;
        }

        // Wrapped in try & catch in case the redirection has already been executed.
        try {
            $content = $metarefresh->getAttribute('content');
        } catch (NoSuchElement $e) {
            return true;
        } catch (StaleElementReference $e) {
            return true;
        }

        // Getting the refresh time and the url if present.
        if (strstr($content, 'url') != false) {

            list($waittime, $url) = explode(';', $content);

            // Cleaning the URL value.
            $url = trim(substr($url, strpos($url, 'http')));

        } else {
            // Just wait then.
            $waittime = $content;
        }


        // Wait until the URL change is executed.
        if ($this->running_javascript()) {
            $this->getSession()->wait($waittime * 1000, false);

        } else if (!empty($url)) {
            // We redirect directly as we can not wait for an automatic redirection.
            $this->getSession()->getDriver()->getClient()->request('get', $url);

        } else {
            // Reload the page if no URL was provided.
            $this->getSession()->getDriver()->reload();
        }

        $this->wait_for_pending_js();
    }

    /**
     * Switches to the specified iframe.
     *
     * @Given /^I switch to "(?P<iframe_name_string>(?:[^"]|\\")*)" iframe$/
     * @param string $iframename
     */
    public function switch_to_iframe($iframename) {
        \behat_hooks::set_step_readonly(false);

        $this->wait_for_pending_js();

        // We spin to give time to the iframe to be loaded.
        // Using extended timeout as we don't know about which
        // kind of iframe will be loaded.
        $this->spin(
            function($context, $iframename) {
                $context->getSession()->switchToIFrame($iframename);

                // If no exception we are done.
                return true;
            },
            $iframename,
            self::EXTENDED_TIMEOUT
        );
    }

    /**
     * Switches to the main Moodle frame.
     *
     * @Given /^I switch to the main frame$/
     */
    public function switch_to_the_main_frame() {
        \behat_hooks::set_step_readonly(false);
        $this->getSession()->switchToIFrame();
    }

    /**
     * Switches to the specified window. Useful when interacting with popup windows.
     *
     * @Given /^I switch to "(?P<window_name_string>(?:[^"]|\\")*)" window$/
     * @param string $windowname
     */
    public function switch_to_window($windowname) {
        \behat_hooks::set_step_readonly(false);

        // Totara: do not rely on tags, force browser restart here!
        behat_hooks::$forcerestart = true;

        $this->wait_for_pending_js();

        // Totara: this sleep is mega super important, if we do not do it Chrome may get stuck randomly!
        sleep(1);

        $this->getSession()->switchToWindow($windowname);
    }

    /**
     * Switches to the main Moodle window. Useful when you finish interacting with popup windows.
     *
     * @Given /^I switch to the main window$/
     */
    public function switch_to_the_main_window() {
        \behat_hooks::set_step_readonly(false);

        $this->wait_for_pending_js();
        $this->getSession()->switchToWindow(self::MAIN_WINDOW_NAME);
    }

    /**
     * Accepts the currently displayed alert dialog. This step does not work in all the browsers, consider it experimental.
     * @Given /^I accept the currently displayed dialog$/
     */
    public function accept_currently_displayed_alert_dialog() {
        \behat_hooks::set_step_readonly(false);
        $this->getSession()->getDriver()->getWebDriverSession()->accept_alert();
    }

    /**
     * Dismisses the currently displayed alert dialog. This step does not work in all the browsers, consider it experimental.
     * @Given /^I dismiss the currently displayed dialog$/
     */
    public function dismiss_currently_displayed_alert_dialog() {
        \behat_hooks::set_step_readonly(false);
        $this->getSession()->getDriver()->getWebDriverSession()->dismiss_alert();
    }

    /**
     * Clicks link with specified id|title|alt|text.
     *
     * @When /^I follow "(?P<link_string>(?:[^"]|\\")*)"$/
     * @throws ElementNotFoundException Thrown by behat_base::find
     * @param string $link
     */
    public function click_link($link) {
        \behat_hooks::set_step_readonly(false);

        $linknode = $this->find_link($link);
        $this->ensure_node_is_visible($linknode);
        $linknode->click();
    }

    /**
     * Waits X seconds. Required after an action that requires data from an AJAX request.
     *
     * @Then /^I wait "(?P<seconds_number>\d+)" seconds$/
     * @param int $seconds
     */
    public function i_wait_seconds($seconds) {
        \behat_hooks::set_step_readonly(true);

        if ($this->running_javascript()) {
            $this->getSession()->wait($seconds * 1000, false);
        } else {
            sleep($seconds);
        }
    }

    /**
     * Waits until all pending JavaScript has completed.
     *
     * @Given /^I wait for pending js$/
     */
    public function i_wait_for_pending_js() {
        // No need to wait if not running JS.
        if (!$this->running_javascript()) {
            return;
        }
        $this->wait_for_pending_js();
    }

    /**
     * Waits until the page is completely loaded. This step is auto-executed after every step.
     *
     * @Given /^I wait until the page is ready$/
     */
    public function wait_until_the_page_is_ready() {

        // No need to wait if not running JS.
        if (!$this->running_javascript()) {
            return;
        }

        $this->getSession()->wait(self::TIMEOUT * 1000, self::PAGE_READY_JS);
    }

    /**
     * Waits until the provided element selector exists in the DOM
     *
     * Using the protected method as this method will be usually
     * called by other methods which are not returning a set of
     * steps and performs the actions directly, so it would not
     * be executed if it returns another step.

     * @Given /^I wait until "(?P<element_string>(?:[^"]|\\")*)" "(?P<selector_string>[^"]*)" exists$/
     * @param string $element
     * @param string $selector
     * @return void
     */
    public function wait_until_exists($element, $selectortype) {
        \behat_hooks::set_step_readonly(true);
        $this->ensure_element_exists($element, $selectortype);
    }

    /**
     * Waits until the provided element does not exist in the DOM
     *
     * Using the protected method as this method will be usually
     * called by other methods which are not returning a set of
     * steps and performs the actions directly, so it would not
     * be executed if it returns another step.

     * @Given /^I wait until "(?P<element_string>(?:[^"]|\\")*)" "(?P<selector_string>[^"]*)" does not exist$/
     * @param string $element
     * @param string $selector
     * @return void
     */
    public function wait_until_does_not_exists($element, $selectortype) {
        \behat_hooks::set_step_readonly(true);
        $this->ensure_element_does_not_exist($element, $selectortype);
    }

    /**
     * Generic mouse over action. Mouse over a element of the specified type.
     *
     * @When /^I hover "(?P<element_string>(?:[^"]|\\")*)" "(?P<selector_string>[^"]*)"$/
     * @param string $element Element we look for
     * @param string $selectortype The type of what we look for
     */
    public function i_hover($element, $selectortype) {
        \behat_hooks::set_step_readonly(false);

        // Gets the node based on the requested selector type and locator.
        $node = $this->get_selected_node($selectortype, $element);
        $node->mouseOver();
    }

    /**
     * Generic click action. Click on the element of the specified type.
     *
     * @When /^I click on "(?P<element_string>(?:[^"]|\\")*)" "(?P<selector_string>[^"]*)"$/
     * @param string $element Element we look for
     * @param string $selectortype The type of what we look for
     */
    public function i_click_on($element, $selectortype) {
        \behat_hooks::set_step_readonly(false);

        // Gets the node based on the requested selector type and locator.
        $node = $this->get_selected_node($selectortype, $element);
        $this->ensure_node_is_visible($node);
        $node->click();
    }

    /**
     * Sets the focus and takes away the focus from an element, generating blur JS event.
     *
     * @When /^I take focus off "(?P<element_string>(?:[^"]|\\")*)" "(?P<selector_string>[^"]*)"$/
     * @param string $element Element we look for
     * @param string $selectortype The type of what we look for
     */
    public function i_take_focus_off_field($element, $selectortype) {
        \behat_hooks::set_step_readonly(false);
        if (!$this->running_javascript()) {
            throw new ExpectationException('Can\'t take focus off from "' . $element . '" in non-js mode', $this->getSession());
        }
        // Gets the node based on the requested selector type and locator.
        $node = $this->get_selected_node($selectortype, $element);
        $this->ensure_node_is_visible($node);

        // Ensure element is focused before taking it off.
        $node->focus();
        $node->blur();
    }

    /**
     * Totara: Work around missing support for alert confirmation in some Selenium browsers.
     *
     * @param bool $accept true means accept, false dismiss the confirmation alert
     * @return bool true if workaround applied, false if not
     */
    protected function apply_confirm_workaround($accept) {
        /** @var Moodle\BehatExtension\Driver\MoodleSelenium2Driver $driver */
        $driver = $this->getSession()->getDriver();
        if (get_class($driver) !== 'Moodle\BehatExtension\Driver\MoodleSelenium2Driver') {
            return false;
        }
        if ($driver->getBrowser() !== 'phantomjs') {
            return false;
        }

        $accept = $accept ? 'true' : 'false';

        $this->getSession()->evaluateScript("
            if (typeof window.originalconfirm == 'undefined') {
                window.originalconfirm = window.confirm;
            }
            window.confirm = function(msg) {
                window.confirm = window.originalconfirm;
                return $accept;
            };");

        return true;
    }

    /**
     * Clicks the specified element and confirms the expected dialogue.
     *
     * @When /^I click on "(?P<element_string>(?:[^"]|\\")*)" "(?P<selector_string>[^"]*)" confirming the dialogue$/
     * @throws ElementNotFoundException Thrown by behat_base::find
     * @param string $element Element we look for
     * @param string $selectortype The type of what we look for
     */
    public function i_click_on_confirming_the_dialogue($element, $selectortype) {
        \behat_hooks::set_step_readonly(false);
        $workaroundactive = $this->apply_confirm_workaround(true);
        $this->i_click_on($element, $selectortype);
        if ($workaroundactive) {
            return;
        }
        $this->accept_currently_displayed_alert_dialog();
    }

    /**
     * Clicks the specified element and dismissing the expected dialogue.
     *
     * @When /^I click on "(?P<element_string>(?:[^"]|\\")*)" "(?P<selector_string>[^"]*)" dismissing the dialogue$/
     * @throws ElementNotFoundException Thrown by behat_base::find
     * @param string $element Element we look for
     * @param string $selectortype The type of what we look for
     */
    public function i_click_on_dismissing_the_dialogue($element, $selectortype) {
        \behat_hooks::set_step_readonly(false);
        $workaroundactive = $this->apply_confirm_workaround(false);
        $this->i_click_on($element, $selectortype);
        if ($workaroundactive) {
            return;
        }
        $this->dismiss_currently_displayed_alert_dialog();
    }

    /**
     * Click on the element of the specified type which is located inside the second element.
     *
     * @When /^I click on "(?P<element_string>(?:[^"]|\\")*)" "(?P<selector_string>[^"]*)" in the "(?P<element_container_string>(?:[^"]|\\")*)" "(?P<text_selector_string>[^"]*)"$/
     * @param string $element Element we look for
     * @param string $selectortype The type of what we look for
     * @param string $nodeelement Element we look in
     * @param string $nodeselectortype The type of selector where we look in
     */
    public function i_click_on_in_the($element, $selectortype, $nodeelement, $nodeselectortype) {
        \behat_hooks::set_step_readonly(false);
        $node = $this->get_node_in_container($selectortype, $element, $nodeselectortype, $nodeelement);
        $this->ensure_node_is_visible($node);
        $node->click();
    }

    /**
     * Click on the element of the specified type which is located inside the second element.
     *
     * Totara added step
     *
     * @When /^I click on "(?P<element_string>(?:[^"]|\\")*)" "(?P<selector_string>[^"]*)" in the "(?P<element_container_string>(?:[^"]|\\")*)" "(?P<text_selector_string>[^"]*)" confirming the dialogue$/
     * @param string $element Element we look for
     * @param string $selectortype The type of what we look for
     * @param string $nodeelement Element we look in
     * @param string $nodeselectortype The type of selector where we look in
     */
    public function i_click_on_in_the_confirming_the_dialogue($element, $selectortype, $nodeelement, $nodeselectortype) {
        \behat_hooks::set_step_readonly(false);
        $workaroundactive = $this->apply_confirm_workaround(true);
        $node = $this->i_click_on_in_the($element, $selectortype, $nodeelement, $nodeselectortype);
        if ($workaroundactive) {
            return;
        }
        $this->accept_currently_displayed_alert_dialog();
    }

    /**
     * Click on the element of the specified type which is located inside the second element.
     *
     * Totara added step
     *
     * @When /^I click on "(?P<element_string>(?:[^"]|\\")*)" "(?P<selector_string>[^"]*)" in the "(?P<element_container_string>(?:[^"]|\\")*)" "(?P<text_selector_string>[^"]*)" dismissing the dialogue$/
     * @param string $element Element we look for
     * @param string $selectortype The type of what we look for
     * @param string $nodeelement Element we look in
     * @param string $nodeselectortype The type of selector where we look in
     */
    public function i_click_on_in_the_dismissing_the_dialogue($element, $selectortype, $nodeelement, $nodeselectortype) {
        \behat_hooks::set_step_readonly(false);
        $workaroundactive = $this->apply_confirm_workaround(false);
        $node = $this->i_click_on_in_the($element, $selectortype, $nodeelement, $nodeselectortype);
        if ($workaroundactive) {
            return;
        }
        $this->dismiss_currently_displayed_alert_dialog();
    }

    /**
     * Drags and drops the specified element to the specified container. This step does not work in all the browsers, consider it experimental.
     *
     * The steps definitions calling this step as part of them should
     * manage the wait times by themselves as the times and when the
     * waits should be done depends on what is being dragged & dropper.
     *
     * @Given /^I drag "(?P<element_string>(?:[^"]|\\")*)" "(?P<selector1_string>(?:[^"]|\\")*)" and I drop it in "(?P<container_element_string>(?:[^"]|\\")*)" "(?P<selector2_string>(?:[^"]|\\")*)"$/
     * @param string $element
     * @param string $selectortype
     * @param string $containerelement
     * @param string $containerselectortype
     */
    public function i_drag_and_i_drop_it_in($element, $selectortype, $containerelement, $containerselectortype) {
        \behat_hooks::set_step_readonly(false);

        list($sourceselector, $sourcelocator) = $this->transform_selector($selectortype, $element);
        $sourcexpath = $this->getSession()->getSelectorsHandler()->selectorToXpath($sourceselector, $sourcelocator);

        list($containerselector, $containerlocator) = $this->transform_selector($containerselectortype, $containerelement);
        $destinationxpath = $this->getSession()->getSelectorsHandler()->selectorToXpath($containerselector, $containerlocator);

        $node = $this->get_selected_node("xpath_element", $sourcexpath);
        if (!$node->isVisible()) {
            throw new ExpectationException('"' . $sourcexpath . '" "xpath_element" is not visible', $this->getSession());
        }
        $node = $this->get_selected_node("xpath_element", $destinationxpath);
        if (!$node->isVisible()) {
            throw new ExpectationException('"' . $destinationxpath . '" "xpath_element" is not visible', $this->getSession());
        }

        $this->getSession()->getDriver()->dragTo($sourcexpath, $destinationxpath);
    }

    /**
     * Checks, that the specified element is visible. Only available in tests using Javascript.
     *
     * @Then /^"(?P<element_string>(?:[^"]|\\")*)" "(?P<selector_string>(?:[^"]|\\")*)" should be visible$/
     * @throws ElementNotFoundException
     * @throws ExpectationException
     * @throws DriverException
     * @param string $element
     * @param string $selectortype
     * @return void
     */
    public function should_be_visible($element, $selectortype) {
        \behat_hooks::set_step_readonly(true);

        if (!$this->running_javascript()) {
            throw new DriverException('Visible checks are disabled in scenarios without Javascript support');
        }

        $node = $this->get_selected_node($selectortype, $element);
        if (!$node->isVisible()) {
            throw new ExpectationException('"' . $element . '" "' . $selectortype . '" is not visible', $this->getSession());
        }
    }

    /**
     * Checks, that the existing element is not visible. Only available in tests using Javascript.
     *
     * As a "not" method, it's performance could not be good, but in this
     * case the performance is good because the element must exist,
     * otherwise there would be a ElementNotFoundException, also here we are
     * not spinning until the element is visible.
     *
     * @Then /^"(?P<element_string>(?:[^"]|\\")*)" "(?P<selector_string>(?:[^"]|\\")*)" should not be visible$/
     * @throws ElementNotFoundException
     * @throws ExpectationException
     * @param string $element
     * @param string $selectortype
     * @return void
     */
    public function should_not_be_visible($element, $selectortype) {
        \behat_hooks::set_step_readonly(true);

        try {
            $this->should_be_visible($element, $selectortype);
        } catch (ExpectationException $e) {
            // All as expected.
            return;
        }
        throw new ExpectationException('"' . $element . '" "' . $selectortype . '" is visible', $this->getSession());
    }

    /**
     * Checks, that the specified element is visible inside the specified container. Only available in tests using Javascript.
     *
     * @Then /^"(?P<element_string>(?:[^"]|\\")*)" "(?P<selector_string>[^"]*)" in the "(?P<element_container_string>(?:[^"]|\\")*)" "(?P<text_selector_string>[^"]*)" should be visible$/
     * @throws ElementNotFoundException
     * @throws DriverException
     * @throws ExpectationException
     * @param string $element Element we look for
     * @param string $selectortype The type of what we look for
     * @param string $nodeelement Element we look in
     * @param string $nodeselectortype The type of selector where we look in
     */
    public function in_the_should_be_visible($element, $selectortype, $nodeelement, $nodeselectortype) {
        \behat_hooks::set_step_readonly(true);

        if (!$this->running_javascript()) {
            throw new DriverException('Visible checks are disabled in scenarios without Javascript support');
        }

        $node = $this->get_node_in_container($selectortype, $element, $nodeselectortype, $nodeelement);
        if (!$node->isVisible()) {
            throw new ExpectationException(
                '"' . $element . '" "' . $selectortype . '" in the "' . $nodeelement . '" "' . $nodeselectortype . '" is not visible',
                $this->getSession()
            );
        }
    }

    /**
     * Checks, that the existing element is not visible inside the existing container. Only available in tests using Javascript.
     *
     * As a "not" method, it's performance could not be good, but in this
     * case the performance is good because the element must exist,
     * otherwise there would be a ElementNotFoundException, also here we are
     * not spinning until the element is visible.
     *
     * @Then /^"(?P<element_string>(?:[^"]|\\")*)" "(?P<selector_string>[^"]*)" in the "(?P<element_container_string>(?:[^"]|\\")*)" "(?P<text_selector_string>[^"]*)" should not be visible$/
     * @throws ElementNotFoundException
     * @throws ExpectationException
     * @param string $element Element we look for
     * @param string $selectortype The type of what we look for
     * @param string $nodeelement Element we look in
     * @param string $nodeselectortype The type of selector where we look in
     */
    public function in_the_should_not_be_visible($element, $selectortype, $nodeelement, $nodeselectortype) {
        \behat_hooks::set_step_readonly(true);

        try {
            $this->in_the_should_be_visible($element, $selectortype, $nodeelement, $nodeselectortype);
        } catch (ExpectationException $e) {
            // All as expected.
            return;
        }
        throw new ExpectationException(
            '"' . $element . '" "' . $selectortype . '" in the "' . $nodeelement . '" "' . $nodeselectortype . '" is visible',
            $this->getSession()
        );
    }

    /**
     * Checks that page contains specified date (as read by strtotime) in the given format (as used by userdate).
     *
     * @Then /^I should see date "(?P<date_string>(?:[^"]|\\")*)" formatted "(?P<format_string>(?:[^"]|\\")*)"$/
     * @throws ExpectationException
     * @param string $datestring
     * @param string $formatstring
     */
    public function i_should_see_date_formatted($datestring, $formatstring) {
        \behat_hooks::set_step_readonly(true);

        // NOTE: forget strtotime(), it seems very buggy when used with time zones.

        $timezone = 'Australia/Perth';
        foreach (\core_date::get_list_of_timezones() as $tz => $ignored) {
            if (is_numeric($tz)) {
                continue;
            }
            if (strpos($datestring, $tz) !== false) {
                $timezone = $tz;
                $datestring = str_replace($tz, '', $datestring);
            }
        }
        $datestring = trim($datestring);

        $date = new DateTime('now', new DateTimeZone($timezone));

        if ($datestring !== 'today') {
            $date->modify($datestring);
        }

        $text = userdate($date->getTimestamp(), $formatstring, $timezone);
        $this->assert_page_contains_text($text);
    }

    /**
     * Checks that an element contains specified date (as read by strtotime) in the given format (as used by userdate).
     *
     * @Then /^I should see date "(?P<date_string>(?:[^"]|\\")*)" formatted "(?P<format_string>(?:[^"]|\\")*)" in the "(?P<element_string>(?:[^"]|\\")*)" "(?P<text_selector_string>[^"]*)"$/
     * @throws ExpectationException
     * @param string $datestring
     * @param string $formatstring
     * @param string $element Element we look in.
     * @param string $selectortype The type of element where we are looking in.
     */
    public function i_should_see_date_formatted_in_the($datestring, $formatstring, $element, $selectortype) {
        \behat_hooks::set_step_readonly(true);

        // NOTE: forget strtotime(), it seems very buggy when used with time zones.

        $timezone = 'Australia/Perth';
        foreach (\core_date::get_list_of_timezones() as $tz => $ignored) {
            if (is_numeric($tz)) {
                continue;
            }
            if (strpos($datestring, $tz) !== false) {
                $timezone = $tz;
                $datestring = str_replace($tz, '', $datestring);
            }
        }
        $datestring = trim($datestring);

        $date = new DateTime('now', new DateTimeZone($timezone));

        if ($datestring !== 'today' and $datestring !== '0 day') {
            $date->modify($datestring);
        }

        $text = userdate($date->getTimestamp(), $formatstring, $timezone);
        $this->assert_element_contains_text($text, $element, $selectortype);
    }

    /**
     * Checks, that page contains specified text. It also checks if the text is visible when running Javascript tests.
     *
     * @Then /^I should see "(?P<text_string>(?:[^"]|\\")*)"$/
     * @throws ExpectationException
     * @param string $text
     */
    public function assert_page_contains_text($text) {
        \behat_hooks::set_step_readonly(true);

        // Looking for all the matching nodes without any other descendant matching the
        // same xpath (we are using contains(., ....).
        $xpathliteral = behat_context_helper::escape($text);
        // 'contains(text(), $xpathliteral)' is required as there could be a hidden child
        // node containing the text being looked for.
        $xpath = "/descendant-or-self::*[contains(., $xpathliteral)]" .
            "[count(descendant::*[contains(., $xpathliteral)]) = 0 or contains(text(), $xpathliteral)]";

        try {
            $nodes = $this->find_all('xpath', $xpath);
        } catch (ElementNotFoundException $e) {
            throw new ExpectationException('"' . $text . '" text was not found in the page', $this->getSession());
        }

        // If we are not running javascript we have enough with the
        // element existing as we can't check if it is visible.
        if (!$this->running_javascript()) {
            return;
        }

        // We spin as we don't have enough checking that the element is there, we
        // should also ensure that the element is visible. Using microsleep as this
        // is a repeated step and global performance is important.
        $this->spin(
            function($context, $args) {

                foreach ($args['nodes'] as $node) {
                    if ($node->isVisible()) {
                        return true;
                    }
                }

                // If non of the nodes is visible we loop again.
                throw new ExpectationException('"' . $args['text'] . '" text was found but was not visible', $context->getSession());
            },
            array('nodes' => $nodes, 'text' => $text),
            false,
            false,
            true
        );

    }

    /**
     * Checks, that page doesn't contain specified text. When running Javascript tests it also considers that texts may be hidden.
     *
     * @Then /^I should not see "(?P<text_string>(?:[^"]|\\")*)"$/
     * @throws ExpectationException
     * @param string $text
     */
    public function assert_page_not_contains_text($text) {
        \behat_hooks::set_step_readonly(true);

        // Looking for all the matching nodes without any other descendant matching the
        // same xpath (we are using contains(., ....).
        $xpathliteral = behat_context_helper::escape($text);
        $xpath = "/descendant-or-self::*[contains(., $xpathliteral)]" .
            "[count(descendant::*[contains(., $xpathliteral)]) = 0]";

        // We should wait a while to ensure that the page is not still loading elements.
        // Waiting less than self::TIMEOUT as we already waited for the DOM to be ready and
        // all JS to be executed.
        try {
            $nodes = $this->find_all('xpath', $xpath, false, false, self::REDUCED_TIMEOUT);
        } catch (ElementNotFoundException $e) {
            // All ok.
            return;
        }

        // If we are not running javascript we have enough with the
        // element existing as we can't check if it is hidden.
        if (!$this->running_javascript()) {
            throw new ExpectationException('"' . $text . '" text was found in the page', $this->getSession());
        }

        // If the element is there we should be sure that it is not visible.
        $this->spin(
            function($context, $args) {

                foreach ($args['nodes'] as $node) {
                    // If element is removed from dom, then just exit.
                    try {
                        // If element is visible then throw exception, so we keep spinning.
                        if ($node->isVisible()) {
                            throw new ExpectationException('"' . $args['text'] . '" text was found in the page',
                                $context->getSession());
                        }
                    } catch (WebDriver\Exception\NoSuchElement $e) {
                        // Do nothing just return, as element is no more on page.
                        return true;
                    }
                }

                // If non of the found nodes is visible we consider that the text is not visible.
                return true;
            },
            array('nodes' => $nodes, 'text' => $text),
            self::REDUCED_TIMEOUT,
            false,
            true
        );
    }

    /**
     * Checks, that the specified element contains the specified text. When running Javascript tests it also considers that texts may be hidden.
     *
     * @Then /^I should see "(?P<text_string>(?:[^"]|\\")*)" in the "(?P<element_string>(?:[^"]|\\")*)" "(?P<text_selector_string>[^"]*)"$/
     * @throws ElementNotFoundException
     * @throws ExpectationException
     * @param string $text
     * @param string $element Element we look in.
     * @param string $selectortype The type of element where we are looking in.
     */
    public function assert_element_contains_text($text, $element, $selectortype) {
        \behat_hooks::set_step_readonly(true);

        // Getting the container where the text should be found.
        $container = $this->get_selected_node($selectortype, $element);

        // Looking for all the matching nodes without any other descendant matching the
        // same xpath (we are using contains(., ....).
        $xpathliteral = behat_context_helper::escape($text);
        $xpath = "/descendant-or-self::*[contains(., $xpathliteral)]" .
            "[count(descendant::*[contains(., $xpathliteral)]) = 0]";

        // Wait until it finds the text inside the container, otherwise custom exception.
        try {
            $nodes = $this->find_all('xpath', $xpath, false, $container);
        } catch (ElementNotFoundException $e) {
            throw new ExpectationException('"' . $text . '" text was not found in the "' . $element . '" element', $this->getSession());
        }

        // If we are not running javascript we have enough with the
        // element existing as we can't check if it is visible.
        if (!$this->running_javascript()) {
            return;
        }

        // We also check the element visibility when running JS tests. Using microsleep as this
        // is a repeated step and global performance is important.
        $this->spin(
            function($context, $args) {

                foreach ($args['nodes'] as $node) {
                    if ($node->isVisible()) {
                        return true;
                    }
                }

                throw new ExpectationException('"' . $args['text'] . '" text was found in the "' . $args['element'] . '" element but was not visible', $context->getSession());
            },
            array('nodes' => $nodes, 'text' => $text, 'element' => $element),
            false,
            false,
            true
        );
    }

    /**
     * Checks, that the specified element does not contain the specified text. When running Javascript tests it also considers that texts may be hidden.
     *
     * @Then /^I should not see "(?P<text_string>(?:[^"]|\\")*)" in the "(?P<element_string>(?:[^"]|\\")*)" "(?P<text_selector_string>[^"]*)"$/
     * @throws ElementNotFoundException
     * @throws ExpectationException
     * @param string $text
     * @param string $element Element we look in.
     * @param string $selectortype The type of element where we are looking in.
     */
    public function assert_element_not_contains_text($text, $element, $selectortype) {
        \behat_hooks::set_step_readonly(true);

        // Getting the container where the text should be found.
        $container = $this->get_selected_node($selectortype, $element);

        // Looking for all the matching nodes without any other descendant matching the
        // same xpath (we are using contains(., ....).
        $xpathliteral = behat_context_helper::escape($text);
        $xpath = "/descendant-or-self::*[contains(., $xpathliteral)]" .
            "[count(descendant::*[contains(., $xpathliteral)]) = 0]";

        // We should wait a while to ensure that the page is not still loading elements.
        // Giving preference to the reliability of the results rather than to the performance.
        try {
            $nodes = $this->find_all('xpath', $xpath, false, $container, self::REDUCED_TIMEOUT);
        } catch (ElementNotFoundException $e) {
            // All ok.
            return;
        }

        // If we are not running javascript we have enough with the
        // element not being found as we can't check if it is visible.
        if (!$this->running_javascript()) {
            throw new ExpectationException('"' . $text . '" text was found in the "' . $element . '" element', $this->getSession());
        }

        // We need to ensure all the found nodes are hidden.
        $this->spin(
            function($context, $args) {

                foreach ($args['nodes'] as $node) {
                    if ($node->isVisible()) {
                        throw new ExpectationException('"' . $args['text'] . '" text was found in the "' . $args['element'] . '" element and was visible', $context->getSession());
                    }
                }

                // If all the found nodes are hidden we are happy.
                return true;
            },
            array('nodes' => $nodes, 'text' => $text, 'element' => $element),
            self::REDUCED_TIMEOUT,
            false,
            true
        );
    }

    /**
     * Checks, that the first specified element appears before the second one.
     *
     * @Given /^"(?P<preceding_element_string>(?:[^"]|\\")*)" "(?P<selector1_string>(?:[^"]|\\")*)" should appear before "(?P<following_element_string>(?:[^"]|\\")*)" "(?P<selector2_string>(?:[^"]|\\")*)"$/
     * @throws ExpectationException
     * @param string $preelement The locator of the preceding element
     * @param string $preselectortype The locator of the preceding element
     * @param string $postelement The locator of the latest element
     * @param string $postselectortype The selector type of the latest element
     */
    public function should_appear_before($preelement, $preselectortype, $postelement, $postselectortype) {
        \behat_hooks::set_step_readonly(true);

        // We allow postselectortype as a non-text based selector.
        list($preselector, $prelocator) = $this->transform_selector($preselectortype, $preelement);
        list($postselector, $postlocator) = $this->transform_selector($postselectortype, $postelement);

        $prexpath = $this->find($preselector, $prelocator)->getXpath();
        $postxpath = $this->find($postselector, $postlocator)->getXpath();

        // Using following xpath axe to find it.
        $msg = '"'.$preelement.'" "'.$preselectortype.'" does not appear before "'.$postelement.'" "'.$postselectortype.'"';
        $xpath = $prexpath.'/following::*[contains(., '.$postxpath.')]';
        if (!$this->getSession()->getDriver()->find($xpath)) {
            throw new ExpectationException($msg, $this->getSession());
        }
    }

    /**
     * Checks, that the first specified element appears after the second one.
     *
     * @Given /^"(?P<following_element_string>(?:[^"]|\\")*)" "(?P<selector1_string>(?:[^"]|\\")*)" should appear after "(?P<preceding_element_string>(?:[^"]|\\")*)" "(?P<selector2_string>(?:[^"]|\\")*)"$/
     * @throws ExpectationException
     * @param string $postelement The locator of the latest element
     * @param string $postselectortype The selector type of the latest element
     * @param string $preelement The locator of the preceding element
     * @param string $preselectortype The locator of the preceding element
     */
    public function should_appear_after($postelement, $postselectortype, $preelement, $preselectortype) {
        \behat_hooks::set_step_readonly(true);

        // We allow postselectortype as a non-text based selector.
        list($postselector, $postlocator) = $this->transform_selector($postselectortype, $postelement);
        list($preselector, $prelocator) = $this->transform_selector($preselectortype, $preelement);

        $postxpath = $this->find($postselector, $postlocator)->getXpath();
        $prexpath = $this->find($preselector, $prelocator)->getXpath();

        // Using preceding xpath axe to find it.
        $msg = '"'.$postelement.'" "'.$postselectortype.'" does not appear after "'.$preelement.'" "'.$preselectortype.'"';
        $xpath = $postxpath.'/preceding::*[contains(., '.$prexpath.')]';
        if (!$this->getSession()->getDriver()->find($xpath)) {
            throw new ExpectationException($msg, $this->getSession());
        }
    }

    /**
     * Checks, that element of specified type is disabled.
     *
     * @Then /^the "(?P<element_string>(?:[^"]|\\")*)" "(?P<selector_string>[^"]*)" should be disabled$/
     * @throws ExpectationException Thrown by behat_base::find
     * @param string $element Element we look in
     * @param string $selectortype The type of element where we are looking in.
     */
    public function the_element_should_be_disabled($element, $selectortype) {
        \behat_hooks::set_step_readonly(true);

        // Transforming from steps definitions selector/locator format to Mink format and getting the NodeElement.
        $node = $this->get_selected_node($selectortype, $element);

        if (!$node->hasAttribute('disabled')) {
            throw new ExpectationException('The element "' . $element . '" is not disabled', $this->getSession());
        }
    }

    /**
     * Checks, that element of specified type is enabled.
     *
     * @Then /^the "(?P<element_string>(?:[^"]|\\")*)" "(?P<selector_string>[^"]*)" should be enabled$/
     * @throws ExpectationException Thrown by behat_base::find
     * @param string $element Element we look on
     * @param string $selectortype The type of where we look
     */
    public function the_element_should_be_enabled($element, $selectortype) {
        \behat_hooks::set_step_readonly(true);

        // Transforming from steps definitions selector/locator format to mink format and getting the NodeElement.
        $node = $this->get_selected_node($selectortype, $element);

        if ($node->hasAttribute('disabled')) {
            throw new ExpectationException('The element "' . $element . '" is not enabled', $this->getSession());
        }
    }

    /**
     * Checks the provided element and selector type are readonly on the current page.
     *
     * @Then /^the "(?P<element_string>(?:[^"]|\\")*)" "(?P<selector_string>[^"]*)" should be readonly$/
     * @throws ExpectationException Thrown by behat_base::find
     * @param string $element Element we look in
     * @param string $selectortype The type of element where we are looking in.
     */
    public function the_element_should_be_readonly($element, $selectortype) {
        \behat_hooks::set_step_readonly(true);
        // Transforming from steps definitions selector/locator format to Mink format and getting the NodeElement.
        $node = $this->get_selected_node($selectortype, $element);

        if (!$node->hasAttribute('readonly')) {
            throw new ExpectationException('The element "' . $element . '" is not readonly', $this->getSession());
        }
    }

    /**
     * Checks the provided element and selector type are not readonly on the current page.
     *
     * @Then /^the "(?P<element_string>(?:[^"]|\\")*)" "(?P<selector_string>[^"]*)" should not be readonly$/
     * @throws ExpectationException Thrown by behat_base::find
     * @param string $element Element we look in
     * @param string $selectortype The type of element where we are looking in.
     */
    public function the_element_should_not_be_readonly($element, $selectortype) {
        \behat_hooks::set_step_readonly(true);
        // Transforming from steps definitions selector/locator format to Mink format and getting the NodeElement.
        $node = $this->get_selected_node($selectortype, $element);

        if ($node->hasAttribute('readonly')) {
            throw new ExpectationException('The element "' . $element . '" is readonly', $this->getSession());
        }
    }

    /**
     * Checks the provided element and selector type exists in the current page.
     *
     * This step is for advanced users, use it if you don't find anything else suitable for what you need.
     *
     * @Then /^"(?P<element_string>(?:[^"]|\\")*)" "(?P<selector_string>[^"]*)" should exist$/
     * @throws ElementNotFoundException Thrown by behat_base::find
     * @param string $element The locator of the specified selector
     * @param string $selectortype The selector type
     */
    public function should_exist($element, $selectortype) {
        \behat_hooks::set_step_readonly(true);

        // Getting Mink selector and locator.
        list($selector, $locator) = $this->transform_selector($selectortype, $element);

        // Will throw an ElementNotFoundException if it does not exist.
        $this->find($selector, $locator);
    }

    /**
     * Checks that the provided element and selector type not exists in the current page.
     *
     * This step is for advanced users, use it if you don't find anything else suitable for what you need.
     *
     * @Then /^"(?P<element_string>(?:[^"]|\\")*)" "(?P<selector_string>[^"]*)" should not exist$/
     * @throws ExpectationException
     * @param string $element The locator of the specified selector
     * @param string $selectortype The selector type
     */
    public function should_not_exist($element, $selectortype) {
        \behat_hooks::set_step_readonly(true);

        // Getting Mink selector and locator.
        list($selector, $locator) = $this->transform_selector($selectortype, $element);

        try {

            // Using directly the spin method as we want a reduced timeout but there is no
            // need for a 0.1 seconds interval because in the optimistic case we will timeout.
            $params = array('selector' => $selector, 'locator' => $locator);
            // The exception does not really matter as we will catch it and will never "explode".
            $exception = new ElementNotFoundException($this->getSession(), $selectortype, null, $element);

            // If all goes good it will throw an ElementNotFoundExceptionn that we will catch.
            $this->spin(
                function($context, $args) {
                    return $context->getSession()->getPage()->findAll($args['selector'], $args['locator']);
                },
                $params,
                self::REDUCED_TIMEOUT,
                $exception,
                false
            );
        } catch (ElementNotFoundException $e) {
            // It passes.
            return;
        }

        throw new ExpectationException('The "' . $element . '" "' . $selectortype .
                '" exists in the current page', $this->getSession());
    }

    /**
     * This step triggers cron like a user would do going to admin/cron.php.
     *
     * @Given /^I trigger cron$/
     */
    public function i_trigger_cron() {
        \behat_hooks::set_step_readonly(false);
        $this->getSession()->visit($this->locate_path('/admin/cron.php'));
        // No need to wait for JS, cron is a plain text page.
    }

    /**
     * Runs all ad-hoc tasks in the queue.
     *
     * This is faster and more reliable than running cron (running cron won't
     * work more than once in the same test, for instance). However it is
     * a little less 'realistic'.
     *
     * While the task is running, we suppress mtrace output because it makes
     * the Behat result look ugly.
     *
     * @Given /^I run all adhoc tasks$/
     * @throws DriverException
     */
    public function i_run_all_adhoc_tasks() {
        \behat_hooks::set_step_readonly(false);

        // Do setup for cron task.
        cron_setup_user();

        // Run tasks. Locking is handled by get_next_adhoc_task.
        $now = time();
        ob_start(); // Discard task output as not appropriate for Behat output!
        while (($task = \core\task\manager::get_next_adhoc_task($now)) !== null) {

            try {
                $task->execute();

                // Mark task complete.
                \core\task\manager::adhoc_task_complete($task);
            } catch (Exception $e) {
                // Mark task failed and throw exception.
                \core\task\manager::adhoc_task_failed($task);
                ob_end_clean();
                throw new DriverException('An adhoc task failed', 0, $e);
            }
        }
        ob_end_clean();
    }

    /**
     * Checks that an element and selector type exists in another element and selector type on the current page.
     *
     * This step is for advanced users, use it if you don't find anything else suitable for what you need.
     *
     * @Then /^"(?P<element_string>(?:[^"]|\\")*)" "(?P<selector_string>[^"]*)" should exist in the "(?P<element2_string>(?:[^"]|\\")*)" "(?P<selector2_string>[^"]*)"$/
     * @throws ElementNotFoundException Thrown by behat_base::find
     * @param string $element The locator of the specified selector
     * @param string $selectortype The selector type
     * @param string $containerelement The container selector type
     * @param string $containerselectortype The container locator
     */
    public function should_exist_in_the($element, $selectortype, $containerelement, $containerselectortype) {
        \behat_hooks::set_step_readonly(true);
        // Get the container node.
        $containernode = $this->get_selected_node($containerselectortype, $containerelement);

        list($selector, $locator) = $this->transform_selector($selectortype, $element);

        // Specific exception giving info about where can't we find the element.
        $locatorexceptionmsg = $element . '" in the "' . $containerelement. '" "' . $containerselectortype. '"';
        $exception = new ElementNotFoundException($this->getSession(), $selectortype, null, $locatorexceptionmsg);

        // Looks for the requested node inside the container node.
        $this->find($selector, $locator, $exception, $containernode);
    }

    /**
     * Checks that an element and selector type does not exist in another element and selector type on the current page.
     *
     * This step is for advanced users, use it if you don't find anything else suitable for what you need.
     *
     * @Then /^"(?P<element_string>(?:[^"]|\\")*)" "(?P<selector_string>[^"]*)" should not exist in the "(?P<element2_string>(?:[^"]|\\")*)" "(?P<selector2_string>[^"]*)"$/
     * @throws ExpectationException
     * @param string $element The locator of the specified selector
     * @param string $selectortype The selector type
     * @param string $containerelement The container selector type
     * @param string $containerselectortype The container locator
     */
    public function should_not_exist_in_the($element, $selectortype, $containerelement, $containerselectortype) {
        \behat_hooks::set_step_readonly(true);

        // Get the container node; here we throw an exception
        // if the container node does not exist.
        $containernode = $this->get_selected_node($containerselectortype, $containerelement);

        list($selector, $locator) = $this->transform_selector($selectortype, $element);

        // Will throw an ElementNotFoundException if it does not exist, but, actually
        // it should not exist, so we try & catch it.
        try {
            // Would be better to use a 1 second sleep because the element should not be there,
            // but we would need to duplicate the whole find_all() logic to do it, the benefit of
            // changing to 1 second sleep is not significant.
            $this->find($selector, $locator, false, $containernode, self::REDUCED_TIMEOUT);
        } catch (ElementNotFoundException $e) {
            // It passes.
            return;
        }
        throw new ExpectationException('The "' . $element . '" "' . $selectortype . '" exists in the "' .
                $containerelement . '" "' . $containerselectortype . '"', $this->getSession());
    }

    /**
     * Change browser window size small: 640x480, medium: 1024x768, large: 2560x1600, custom: widthxheight
     *
     * Example: I change window size to "small" or I change window size to "1024x768"
     * or I change viewport size to "800x600". The viewport option is useful to guarantee that the
     * browser window has same viewport size even when you run Behat on multiple operating systems.
     *
     * @throws ExpectationException
     * @Then /^I change (window|viewport) size to "(small|medium|large|\d+x\d+)"$/
     * @param string $windowsize size of the window (small|medium|large|wxh).
     */
    public function i_change_window_size_to($windowviewport, $windowsize) {
        \behat_hooks::set_step_readonly(false);
        $this->resize_window($windowsize, $windowviewport === 'viewport');
    }

    /**
     * Checks whether there is an attribute on the given element that contains the specified text.
     *
     * @Then /^the "(?P<attribute_string>[^"]*)" attribute of "(?P<element_string>(?:[^"]|\\")*)" "(?P<selector_string>[^"]*)" should contain "(?P<text_string>(?:[^"]|\\")*)"$/
     * @throws ExpectationException
     * @param string $attribute Name of attribute
     * @param string $element The locator of the specified selector
     * @param string $selectortype The selector type
     * @param string $text Expected substring
     */
    public function the_attribute_of_should_contain($attribute, $element, $selectortype, $text) {
        \behat_hooks::set_step_readonly(true);
        // Get the container node (exception if it doesn't exist).
        $containernode = $this->get_selected_node($selectortype, $element);
        $value = $containernode->getAttribute($attribute);
        if ($value == null) {
            throw new ExpectationException('The attribute "' . $attribute. '" does not exist',
                    $this->getSession());
        } else if (strpos($value, $text) === false) {
            throw new ExpectationException('The attribute "' . $attribute .
                    '" does not contain "' . $text . '" (actual value: "' . $value . '")',
                    $this->getSession());
        }
    }

    /**
     * Checks that the attribute on the given element does not contain the specified text.
     *
     * @Then /^the "(?P<attribute_string>[^"]*)" attribute of "(?P<element_string>(?:[^"]|\\")*)" "(?P<selector_string>[^"]*)" should not contain "(?P<text_string>(?:[^"]|\\")*)"$/
     * @throws ExpectationException
     * @param string $attribute Name of attribute
     * @param string $element The locator of the specified selector
     * @param string $selectortype The selector type
     * @param string $text Expected substring
     */
    public function the_attribute_of_should_not_contain($attribute, $element, $selectortype, $text) {
        \behat_hooks::set_step_readonly(true);
        // Get the container node (exception if it doesn't exist).
        $containernode = $this->get_selected_node($selectortype, $element);
        $value = $containernode->getAttribute($attribute);
        if ($value == null) {
            throw new ExpectationException('The attribute "' . $attribute. '" does not exist',
                    $this->getSession());
        } else if (strpos($value, $text) !== false) {
            throw new ExpectationException('The attribute "' . $attribute .
                    '" contains "' . $text . '" (value: "' . $value . '")',
                    $this->getSession());
        }
    }

    /**
     * Checks the provided value exists in specific row/column of table.
     *
     * @Then /^"(?P<row_string>[^"]*)" row "(?P<column_string>[^"]*)" column of "(?P<table_string>[^"]*)" table should contain "(?P<value_string>[^"]*)"$/
     * @throws ElementNotFoundException
     * @param string $row row text which will be looked in.
     * @param string $column column text to search (or numeric value for the column position)
     * @param string $table table id/class/caption
     * @param string $value text to check.
     * @param bool $mustequal If true, the table cell must be identical to the data to be matched.
     *                        If false, the table cell needs to contain the data (other data can be present).
     */
    public function row_column_of_table_should_contain($row, $column, $table, $value, $mustequal = true) {
        \behat_hooks::set_step_readonly(true);
        $tablenode = $this->get_selected_node('table', $table);
        $tablexpath = $tablenode->getXpath();

        $rowliteral = behat_context_helper::escape($row);
        $valueliteral = behat_context_helper::escape($value);
        $columnliteral = behat_context_helper::escape($column);

        if (preg_match('/^-?(\d+)-?$/', $column, $columnasnumber)) {
            // Column indicated as a number, just use it as position of the column.
            $columnpositionxpath = "/child::*[position() = {$columnasnumber[1]}]";
        } else {
            // Header can be in thead or tbody (first row), following xpath should work.
            $theadheaderxpath = "thead/tr[1]/th[(normalize-space(.)=" . $columnliteral . " or a[normalize-space(text())=" .
                    $columnliteral . "] or div[normalize-space(text())=" . $columnliteral . "])]";
            $tbodyheaderxpath = "tbody/tr[1]/td[(normalize-space(.)=" . $columnliteral . " or a[normalize-space(text())=" .
                    $columnliteral . "] or div[normalize-space(text())=" . $columnliteral . "])]";

            // Totara: Moodle stuff was not working properly, so let's make this easier by looking up the column number instead.
            $columnheaderxpath = "$tablexpath/$theadheaderxpath | $tablexpath/$tbodyheaderxpath";
            $columnheader = $this->getSession()->getDriver()->find($columnheaderxpath);
            if (empty($columnheader)) {
                $columnexceptionmsg = $column . '" in table "' . $table . '"';
                throw new ElementNotFoundException($this->getSession(), "\n$columnheaderxpath\n\n".'Column', null, $columnexceptionmsg);
            }
            /** @var \Behat\Mink\Element\NodeElement $columnheader */
            $columnheader = reset($columnheader);
            $preceding = $this->getSession()->getDriver()->find($columnheader->getXpath() . '/preceding-sibling::*');
            $position = count($preceding) + 1;
            $columnpositionxpath = "/child::*[position() = $position]";
        }

        // Check if value exists in specific row/column.
        // Get row xpath.
        // GoutteDriver uses DomCrawler\Crawler and it is making XPath relative to the current context, so use descendant.
        if ($mustequal) {
            $rowxpath = $tablexpath . "/tbody/tr[descendant::th[normalize-space(.)=" . $rowliteral .
                        "] | descendant::td[normalize-space(.)=" . $rowliteral . "]]";
        } else {
            $rowxpath = $tablexpath . "/tbody/tr[descendant::th[contains(.," . $rowliteral .
                        ")] | descendant::td[contains(.," . $rowliteral . ")]]";
        }

        $columnvaluexpath = $rowxpath . $columnpositionxpath . "[contains(normalize-space(.)," . $valueliteral . ")]";

        // Looks for the requested node inside the container node.
        $coumnnode = $this->getSession()->getDriver()->find($columnvaluexpath);
        if (empty($coumnnode)) {
            $locatorexceptionmsg = $value . '" in "' . $row . '" row with column "' . $column;
            throw new ElementNotFoundException($this->getSession(), "\n$columnvaluexpath\n\n".'Column value', null, $locatorexceptionmsg);
        }
    }

    /**
     * Checks the provided value should not exist in specific row/column of table.
     *
     * @Then /^"(?P<row_string>[^"]*)" row "(?P<column_string>[^"]*)" column of "(?P<table_string>[^"]*)" table should not contain "(?P<value_string>[^"]*)"$/
     * @throws ElementNotFoundException
     * @param string $row row text which will be looked in.
     * @param string $column column text to search
     * @param string $table table id/class/caption
     * @param string $value text to check.
     */
    public function row_column_of_table_should_not_contain($row, $column, $table, $value) {
        \behat_hooks::set_step_readonly(true);
        try {
            $this->row_column_of_table_should_contain($row, $column, $table, $value);
        } catch (ElementNotFoundException $e) {
            // Table row/column doesn't contain this value. Nothing to do.
            return;
        }
        // Throw exception if found.
        throw new ExpectationException(
            '"' . $column . '" with value "' . $value . '" is present in "' . $row . '"  row for table "' . $table . '"',
            $this->getSession()
        );
    }

    /**
     * This function is very similar to "the following should exist in the &lt;table&gt; table"
     * but does a 'contains' rather than an 'equals' match against the table data. This allows
     * superfluous cell content such as images to be ignored but a match to be achieved.
     *
     * @Then /^the "(?P<table_string>[^"]*)" table should contain the following:$/
     * @param string $table name of table
     * @param TableNode $data table with first row as header and following values
     *        | Header 1 | Header 2 | Header 3 |
     *        | Value 1  | Value 2  | Value 3  |
     */
    public function the_table_should_contain_the_following($table, TableNode $data) {
        $this->following_should_exist_in_the_table($table, $data, false);
    }

    /**
     * Checks that the provided value exist in table.
     * More info in http://docs.moodle.org/dev/Acceptance_testing#Providing_values_to_steps.
     *
     * First row may contain column headers or numeric indexes of the columns
     * (syntax -1- is also considered to be column index). Column indexes are
     * useful in case of multirow headers and/or presence of cells with colspan.
     *
     * @Then /^the following should exist in the "(?P<table_string>[^"]*)" table:$/
     * @throws ExpectationException
     * @param string $table name of table
     * @param TableNode $data table with first row as header and following values
     *        | Header 1 | Header 2 | Header 3 |
     *        | Value 1  | Value 2  | Value 3  |
     * @param bool $mustequal Indicates of the match must equal or contain the data.
     */
    public function following_should_exist_in_the_table($table, TableNode $data, $mustequal = true) {
        \behat_hooks::set_step_readonly(true);
        $datahash = $data->getHash();

        foreach ($datahash as $row) {
            $firstcell = null;
            foreach ($row as $column => $value) {
                if ($firstcell === null) {
                    $firstcell = $value;
                } else {
                    $this->row_column_of_table_should_contain($firstcell, $column, $table, $value, $mustequal);
                }
            }
        }
    }

    /**
     * This function is very similar to "the following should not exist in the &lt;table&gt; table"
     * but does a 'contains' rather than an 'equals' match against the table data. This allows
     * superfluous cell content such as images to be ignored but a match to be achieved.
     *
     * @Then /^the "(?P<table_string>[^"]*)" table should not contain the following:$/
     * @param string $table name of table
     * @param TableNode $data table with first row as header and following values
     *        | Header 1 | Header 2 | Header 3 |
     *        | Value 1  | Value 2  | Value 3  |
     */
    public function the_table_should_not_contain_the_following($table, TableNode $data) {
        $this->following_should_not_exist_in_the_table($table, $data, false);
    }

    /**
     * Checks that the provided value exist in table.
     * More info in http://docs.moodle.org/dev/Acceptance_testing#Providing_values_to_steps.
     *
     * @Then /^the following should not exist in the "(?P<table_string>[^"]*)" table:$/
     * @throws ExpectationException
     * @param string $table name of table
     * @param TableNode $data table with first row as header and following values
     *        | Header 1 | Header 2 | Header 3 |
     *        | Value 1  | Value 2  | Value 3  |
     * @param bool $mustequal Indicates of the match must equal or contain the data.
     */
    public function following_should_not_exist_in_the_table($table, TableNode $data, $mustequal = true) {
        \behat_hooks::set_step_readonly(true);
        $datahash = $data->getHash();

        foreach ($datahash as $value) {
            $row = array_shift($value);
            foreach ($value as $column => $value) {
                try {
                    $this->row_column_of_table_should_contain($row, $column, $table, $value, $mustequal);
                    // Throw exception if found.
                } catch (ElementNotFoundException $e) {
                    // Table row/column doesn't contain this value. Nothing to do.
                    continue;
                }
                throw new ExpectationException('"' . $column . '" with value "' . $value . '" is present in "' .
                    $row . '"  row for table "' . $table . '"', $this->getSession()
                );
            }
        }
    }

    /**
     * Given the text of a link, download the linked file and return the contents.
     *
     * This is a helper method used by {@link following_should_download_bytes()}
     * and {@link following_should_download_between_and_bytes()}
     *
     * @param string $link the text of the link.
     * @return string the content of the downloaded file.
     */
    public function download_file_from_link($link) {
        \behat_hooks::set_step_readonly(false);
        // Find the link.
        $linknode = $this->find_link($link);
        $this->ensure_node_is_visible($linknode);

        // Get the href and check it.
        $url = $linknode->getAttribute('href');
        if (!$url) {
            throw new ExpectationException('Download link does not have href attribute',
                    $this->getSession());
        }
        if (!preg_match('~^https?://~', $url)) {
            throw new ExpectationException('Download link not an absolute URL: ' . $url,
                    $this->getSession());
        }

        // Download the URL and check the size.
        $session = $this->getSession()->getCookie('TotaraSession');
        return download_file_content($url, array('Cookie' => 'BEHAT=1;TotaraSession=' . $session));
    }

    /**
     * Downloads the file from a link on the page and checks the size.
     *
     * Only works if the link has an href attribute. Javascript downloads are
     * not supported. Currently, the href must be an absolute URL.
     *
     * @Then /^following "(?P<link_string>[^"]*)" should download "(?P<expected_bytes>\d+)" bytes$/
     * @throws ExpectationException
     * @param string $link the text of the link.
     * @param number $expectedsize the expected file size in bytes.
     */
    public function following_should_download_bytes($link, $expectedsize) {
        \behat_hooks::set_step_readonly(false);
        $exception = new ExpectationException('Error while downloading data from ' . $link, $this->getSession());

        // It will stop spinning once file is downloaded or time out.
        $result = $this->spin(
            function($context, $args) {
                $link = $args['link'];
                return $this->download_file_from_link($link);
            },
            array('link' => $link),
            self::EXTENDED_TIMEOUT,
            $exception
        );

        // Check download size.
        $actualsize = (int)strlen($result);
        if ($actualsize !== (int)$expectedsize) {
            throw new ExpectationException('Downloaded data was ' . $actualsize .
                    ' bytes, expecting ' . $expectedsize, $this->getSession());
        }
    }

    /**
     * Downloads the file from a link on the page and checks the size is in a given range.
     *
     * Only works if the link has an href attribute. Javascript downloads are
     * not supported. Currently, the href must be an absolute URL.
     *
     * The range includes the endpoints. That is, a 10 byte file in considered to
     * be between "5" and "10" bytes, and between "10" and "20" bytes.
     *
     * @Then /^following "(?P<link_string>[^"]*)" should download between "(?P<min_bytes>\d+)" and "(?P<max_bytes>\d+)" bytes$/
     * @throws ExpectationException
     * @param string $link the text of the link.
     * @param number $minexpectedsize the minimum expected file size in bytes.
     * @param number $maxexpectedsize the maximum expected file size in bytes.
     */
    public function following_should_download_between_and_bytes($link, $minexpectedsize, $maxexpectedsize) {
        \behat_hooks::set_step_readonly(false);
        // If the minimum is greater than the maximum then swap the values.
        if ((int)$minexpectedsize > (int)$maxexpectedsize) {
            list($minexpectedsize, $maxexpectedsize) = array($maxexpectedsize, $minexpectedsize);
        }

        $exception = new ExpectationException('Error while downloading data from ' . $link, $this->getSession());

        // It will stop spinning once file is downloaded or time out.
        $result = $this->spin(
            function($context, $args) {
                $link = $args['link'];

                return $this->download_file_from_link($link);
            },
            array('link' => $link),
            self::EXTENDED_TIMEOUT,
            $exception
        );

        // Check download size.
        $actualsize = (int)strlen($result);
        if ($actualsize < $minexpectedsize || $actualsize > $maxexpectedsize) {
            throw new ExpectationException('Downloaded data was ' . $actualsize .
                    ' bytes, expecting between ' . $minexpectedsize . ' and ' .
                    $maxexpectedsize, $this->getSession());
        }
    }

    /**
     * Prepare to detect whether or not a new page has loaded (or the same page reloaded) some time in the future.
     *
     * @Given /^I start watching to see if a new page loads$/
     */
    public function i_start_watching_to_see_if_a_new_page_loads() {
        \behat_hooks::set_step_readonly(false);
        if (!$this->running_javascript()) {
            throw new DriverException('Page load detection requires JavaScript.');
        }

        $session = $this->getSession();

        if ($this->pageloaddetectionrunning || $session->getPage()->find('xpath', $this->get_page_load_xpath())) {
            // If we find this node at this point we are already watching for a reload and the behat steps
            // are out of order. We will treat this as an error - really it needs to be fixed as it indicates a problem.
            throw new ExpectationException(
                'Page load expectation error: page reloads are already been watched for.', $session);
        }

        $this->pageloaddetectionrunning = true;

        $session->executeScript(
                'var span = document.createElement("span");
                span.setAttribute("data-rel", "' . self::PAGE_LOAD_DETECTION_STRING . '");
                span.setAttribute("style", "display: none;");
                document.body.appendChild(span);');
    }

    /**
     * Verify that a new page has loaded (or the same page has reloaded) since the
     * last "I start watching to see if a new page loads" step.
     *
     * @Given /^a new page should have loaded since I started watching$/
     */
    public function a_new_page_should_have_loaded_since_i_started_watching() {
        \behat_hooks::set_step_readonly(false);
        $session = $this->getSession();

        // Make sure page load tracking was started.
        if (!$this->pageloaddetectionrunning) {
            throw new ExpectationException(
                'Page load expectation error: page load tracking was not started.', $session);
        }

        // As the node is inserted by code above it is either there or not, and we do not need spin and it is safe
        // to use the native API here which is great as exception handling (the alternative is slow).
        if ($session->getPage()->find('xpath', $this->get_page_load_xpath())) {
            // We don't want to find this node, if we do we have an error.
            throw new ExpectationException(
                'Page load expectation error: a new page has not been loaded when it should have been.', $session);
        }

        // Cancel the tracking of pageloaddetectionrunning.
        $this->pageloaddetectionrunning = false;
    }

    /**
     * Verify that a new page has not loaded (or the same page has reloaded) since the
     * last "I start watching to see if a new page loads" step.
     *
     * @Given /^a new page should not have loaded since I started watching$/
     */
    public function a_new_page_should_not_have_loaded_since_i_started_watching() {
        \behat_hooks::set_step_readonly(false);
        $session = $this->getSession();

        // Make sure page load tracking was started.
        if (!$this->pageloaddetectionrunning) {
            throw new ExpectationException(
                'Page load expectation error: page load tracking was not started.', $session);
        }

        // We use our API here as we can use the exception handling provided by it.
        $this->find(
            'xpath',
            $this->get_page_load_xpath(),
            new ExpectationException(
                'Page load expectation error: A new page has been loaded when it should not have been.',
                $this->getSession()
            )
        );
    }

    /**
     * Helper used by {@link a_new_page_should_have_loaded_since_i_started_watching}
     * and {@link a_new_page_should_not_have_loaded_since_i_started_watching}
     * @return string xpath expression.
     */
    protected function get_page_load_xpath() {
        return "//span[@data-rel = '" . self::PAGE_LOAD_DETECTION_STRING . "']";
    }

    /**
     * Wait unit user press Enter/Return key. Useful when debugging a scenario.
     *
     * @Then /^(?:|I )pause(?:| scenario execution)$/
     */
    public function i_pause_scenario_executon() {
        global $CFG;
        \behat_hooks::set_step_readonly(true);

        $posixexists = function_exists('posix_isatty');

        // Make sure this step is only used with interactive terminal (if detected).
        if ($posixexists && !@posix_isatty(STDOUT)) {
            $session = $this->getSession();
            throw new ExpectationException('Break point should only be used with interative terminal.', $session);
        }

        // Windows don't support ANSI code by default, but with ANSICON.
        $isansicon = getenv('ANSICON');
        if (($CFG->ostype === 'WINDOWS') && empty($isansicon)) {
            fwrite(STDOUT, "Paused. Press Enter/Return to continue.");
            fread(STDIN, 1024);
        } else {
            fwrite(STDOUT, "\033[s\n\033[0;93mPaused. Press \033[1;31mEnter/Return\033[0;93m to continue.\033[0m");
            fread(STDIN, 1024);
            fwrite(STDOUT, "\033[2A\033[u\033[2B");
        }
    }

    /**
     * Presses a given button in the browser.
     * NOTE: Phantomjs and goutte driver reloads page while navigating back and forward.
     *
     * @Then /^I press the "(back|forward|reload)" button in the browser$/
     * @param string $button the button to press.
     * @throws ExpectationException
     */
    public function i_press_in_the_browser($button) {
        \behat_hooks::set_step_readonly(false);
        $session = $this->getSession();

        if ($button == 'back') {
            $session->back();
        } else if ($button == 'forward') {
            $session->forward();
        } else if ($button == 'reload') {
            $session->reload();
        } else {
            throw new ExpectationException('Unknown browser button.', $session);
        }
    }

    /**
     * Trigger a keydown event for a key on a specific element.
     *
     * @When /^I press key "(?P<key_string>(?:[^"]|\\")*)" in "(?P<element_string>(?:[^"]|\\")*)" "(?P<selector_string>[^"]*)"$/
     * @param string $key either char-code or character itself,
     *               may optionally be prefixed with ctrl-, alt-, shift- or meta-
     * @param string $element Element we look for
     * @param string $selectortype The type of what we look for
     * @throws DriverException
     * @throws ExpectationException
     */
    public function i_press_key_in_element($key, $element, $selectortype) {
        \behat_hooks::set_step_readonly(false);
        if (!$this->running_javascript()) {
            throw new DriverException('Key down step is not available with Javascript disabled');
        }
        // Gets the node based on the requested selector type and locator.
        $node = $this->get_selected_node($selectortype, $element);
        $modifier = null;
        $validmodifiers = array('ctrl', 'alt', 'shift', 'meta');
        $char = $key;
        if (strpos($key, '-')) {
            list($modifier, $char) = preg_split('/-/', $key, 2);
            $modifier = strtolower($modifier);
            if (!in_array($modifier, $validmodifiers)) {
                throw new ExpectationException(sprintf('Unknown key modifier: %s.', $modifier));
            }
        }
        if (is_numeric($char)) {
            $char = (int)$char;
        }

        $node->keyDown($char, $modifier);
        $node->keyPress($char, $modifier);
        $node->keyUp($char, $modifier);
    }

    /**
     * Press tab key on a specific element.
     *
     * @When /^I press tab key in "(?P<element_string>(?:[^"]|\\")*)" "(?P<selector_string>[^"]*)"$/
     * @param string $element Element we look for
     * @param string $selectortype The type of what we look for
     * @throws DriverException
     * @throws ExpectationException
     */
    public function i_post_tab_key_in_element($element, $selectortype) {
        \behat_hooks::set_step_readonly(false);
        if (!$this->running_javascript()) {
            throw new DriverException('Tab press step is not available with Javascript disabled');
        }
        // Gets the node based on the requested selector type and locator.
        $node = $this->get_selected_node($selectortype, $element);
        $this->getSession()->getDriver()->post_key("\xEE\x80\x84", $node->getXpath());
    }

    /**
     * Checks if database family used is using one of the specified, else skip. (mysql, postgres, mssql, oracle, etc.)
     *
     * @Given /^database family used is one of the following:$/
     * @param TableNode $databasefamilies list of database.
     * @return void.
     * @throws \Moodle\BehatExtension\Exception\SkippedException
     */
    public function database_family_used_is_one_of_the_following(TableNode $databasefamilies) {
        global $DB;

        $dbfamily = $DB->get_dbfamily();

        // Check if used db family is one of the specified ones. If yes then return.
        foreach ($databasefamilies->getRows() as $dbfamilytocheck) {
            if ($dbfamilytocheck[0] == $dbfamily) {
                return;
            }
        }

        throw new \Moodle\BehatExtension\Exception\SkippedException();
    }

    /**
     * @Then /^I should see "([^"]*)" exactly "([^"]*)" times$/
     */
    public function i_should_see_text_x_times($text, $expected) {
        $content = $this->getSession()->getPage()->getText();
        $regexp = '/\b'.$text.'\b/';
        $found = preg_match_all($regexp, $content);
        if ($expected != $found) {
            throw new \Exception('Found '.$found.' occurences of "'.$text.'" when expecting '.$expected);
        }
    }

    /**
     * Checks focus is with the given element.
     *
     * @Then /^the focused element is( not)? "(?P<node_string>(?:[^"]|\\")*)" "(?P<node_selector_string>[^"]*)"$/
     * @param string $not optional step verifier
     * @param string $nodeelement Element identifier
     * @param string $nodeselectortype Element type
     * @throws ErrorException If not using JavaScript
     * @throws ExpectationException
     */
    public function the_focused_element_is($not, $nodeelement, $nodeselectortype) {
        if (!$this->running_javascript()) {
            throw new ErrorException('Checking focus on an element requires JavaScript');
        }
        list($a, $b) = $this->transform_selector($nodeselectortype, $nodeelement);
        $element = $this->find($a, $b);
        $xpath = addslashes_js($element->getXpath());
        $script = 'return (function() { return document.activeElement === document.evaluate("' . $xpath . '",
                document, null, XPathResult.FIRST_ORDERED_NODE_TYPE, null).singleNodeValue; })(); ';
        $targetisfocused = $this->getSession()->evaluateScript($script);
        if ($not == ' not') {
            if ($targetisfocused) {
                throw new ExpectationException("$nodeelement $nodeselectortype is focused", $this->getSession());
            }
        } else {
            if (!$targetisfocused) {
                throw new ExpectationException("$nodeelement $nodeselectortype is not focused", $this->getSession());
            }
        }
    }

    /**
     * Checks focus is with the given element.
     *
     * @Then /^the focused element is( not)? "(?P<n>(?:[^"]|\\")*)" "(?P<ns>[^"]*)" in the "(?P<c>(?:[^"]|\\")*)" "(?P<cs>[^"]*)"$/
     * @param string $not string optional step verifier
     * @param string $element Element identifier
     * @param string $selectortype Element type
     * @param string $nodeelement Element we look in
     * @param string $nodeselectortype The type of selector where we look in
     * @throws ErrorException If not using JavaScript
     * @throws ExpectationException
     */
    public function the_focused_element_is_in_the($not, $element, $selectortype, $nodeelement, $nodeselectortype) {
        if (!$this->running_javascript()) {
            throw new ErrorException('Checking focus on an element requires JavaScript');
        }
        $element = $this->get_node_in_container($selectortype, $element, $nodeselectortype, $nodeelement);
        $xpath = addslashes_js($element->getXpath());
        $script = 'return (function() { return document.activeElement === document.evaluate("' . $xpath . '",
                document, null, XPathResult.FIRST_ORDERED_NODE_TYPE, null).singleNodeValue; })(); ';
        $targetisfocused = $this->getSession()->evaluateScript($script);
        if ($not == ' not') {
            if ($targetisfocused) {
                throw new ExpectationException("$nodeelement $nodeselectortype is focused", $this->getSession());
            }
        } else {
            if (!$targetisfocused) {
                throw new ExpectationException("$nodeelement $nodeselectortype is not focused", $this->getSession());
            }
        }
    }

    /**
     * Manually press tab key.
     *
     * @When /^I press( shift)? tab$/
     * @param string $shift string optional step verifier
     * @throws DriverException
     */
    public function i_manually_press_tab($shift = '') {
        if (!$this->running_javascript()) {
            throw new DriverException($shift . ' Tab press step is not available with Javascript disabled');
        }

        $value = ($shift == ' shift') ? [\WebDriver\Key::SHIFT . \WebDriver\Key::TAB] : [\WebDriver\Key::TAB];
        $this->getSession()->getDriver()->getWebDriverSession()->activeElement()->postValue(['value' => $value]);
    }

    /**
     * Confirms that an image was successfully loaded in a page as (reported by the browser to the javascript engine).
     *
     * Note that SVG images should fail as browsers should not allow a javascript action against them.
     *
     * @Then /^I should see image with alt text "([^"]*)"$/
     */
    public function i_should_see_image_with_alt_text($text) {
        // Javascript is a requirement.
        if (!$this->running_javascript()) {
            throw new DriverException('Ability to confirm image presence is not available with Javascript disabled');
        }

        // Wait until browser thinks image load is complete.
        $escaped_text = str_replace("'", "\'", $text);
        try {
            while (!$this->getSession()->getDriver()->evaluateScript("return document.querySelector('img[alt=\"$escaped_text\"]').complete")) {
                sleep(1);
            }
        }
        catch (exception $e) {
            throw new ExpectationException('Image with alt text "' . $text . '" was not defined for the page', $this->getSession());
        }

        // Check that browser reports image has loaded successfully.
        $loaded = $this->getSession()->getDriver()->evaluateScript("return document.querySelector('img[alt=\"$escaped_text\"]').naturalWidth > 0");
        if ($loaded == false) {
            throw new ExpectationException('Image with alt text "' . $text . '" was not displayed on the page', $this->getSession());
        }
    }
}
