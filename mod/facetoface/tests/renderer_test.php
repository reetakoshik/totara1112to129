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
 * @author Brendan Cox <brendan.cox@totaralearning.com>
 * @package mod_facetoface
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/lib/phpunit/classes/advanced_testcase.php');

class mod_facetoface_renderer_testcase extends advanced_testcase {

    /** @var testing_data_generator $data_generator */
    private $data_generator;

    /** @var mod_facetoface_generator */
    private $facetoface_generator;

    public function setUp() {
        parent::setUp();
        $this->resetAfterTest(true);

        $this->data_generator = $this->getDataGenerator();
        $this->facetoface_generator = $this->data_generator->get_plugin_generator('mod_facetoface');
    }

    protected function tearDown() {
        $this->data_generator = null;
        $this->facetoface_generator = null;
        parent::tearDown();
    }

    public function data_provider_regdates_tooltip() {
        $data = array(
            array(1466015400, 1466425800, true),
            array(1466015400, 1466425800, false),
            array(null, 1466425800, true),
            array(null, 1466425800, false),
            array(1466015400, null, true),
            array(1466015400, null, false),
            array(null, null, true),
            array(null, null, false),
        );
        return $data;
    }

    /**
     * Tests the private method get_regdates_tooltip_info by creating a reflection class.
     *
     * @dataProvider data_provider_regdates_tooltip
     * @throws coding_exception
     */
    public function test_get_regdates_tooltip_info($registrationtimestart, $registrationtimefinish, $displaytimezones) {
        $this->resetAfterTest(true);
        global $PAGE;

        $renderer = $PAGE->get_renderer('mod_facetoface');

        // Create reflection class in order to test the private method.
        $reflection = new \ReflectionClass(get_class($renderer));
        $method = $reflection->getMethod('get_regdates_tooltip_info');
        $method->setAccessible(true);

        $timezone = core_date::get_user_timezone();

        $session = new stdClass();
        $session->registrationtimestart = $registrationtimestart;
        $session->registrationtimefinish = $registrationtimefinish;

        // Run the method and get the output.
        $actualoutput = $method->invokeArgs($renderer, array($session, $displaytimezones));

        // Create expected output string.
        $startdatestring = userdate($registrationtimestart, get_string('strftimedate', 'langconfig'), $timezone);
        $starttimestring = userdate($registrationtimestart, get_string('strftimetime', 'langconfig'), $timezone);
        $finishdatestring = userdate($registrationtimefinish, get_string('strftimedate', 'langconfig'), $timezone);
        $finishtimestring = userdate($registrationtimefinish, get_string('strftimetime', 'langconfig'), $timezone);

        // If there are no start or finish dates we will get an empty string.
        $expectedoutput = '';
        if (isset($registrationtimestart)) {
            // The Sign-up period opens text is only show if there is a sign-up period start date.
            $expectedoutput = "Sign-up period opens: " . $startdatestring . ", " . $starttimestring;
            if ($displaytimezones) {
                $expectedoutput .= " (time zone: " . $timezone . ")";
            }

            if ($registrationtimefinish) {
                // There is only a new line if both start and finish dates are there.
                $expectedoutput .= "\n";
            }
        }

        if (isset($registrationtimefinish)) {
            $expectedoutput .= "Sign-up period closes: " . $finishdatestring . ", " . $finishtimestring;
            if ($displaytimezones) {
                $expectedoutput .= " (time zone: " . $timezone . ")";
            }
        }

        $this->assertEquals($expectedoutput, $actualoutput);
    }

    /**
     * Tests the private method get_regdates_tooltip_info by testing the output of
     * the public method print_session_list_table.
     *
     * @dataProvider data_provider_regdates_tooltip
     */
    public function test_get_regdates_tooltip_info_via_print_session_list_table($registrationtimestart, $registrationtimefinish, $displaytimezones) {
        $this->resetAfterTest(true);
        global $PAGE;

        /** @var mod_facetoface_renderer $renderer */
        $renderer = $PAGE->get_renderer('mod_facetoface');
        // We need to set the url as this is queried during the run of print_session_list_table.
        $PAGE->set_url('/mod/facetoface/view.php');

        $course = $this->data_generator->create_course();

        $facetofacedata = new stdClass();
        $facetofacedata->course = $course->id;
        $facetoface = $this->facetoface_generator->create_instance($facetofacedata);
        $sessiondata = new stdClass();
        $sessiondata->facetoface = $facetoface->id;
        $sessiondata->registrationtimestart = $registrationtimestart;
        $sessiondata->registrationtimefinish = $registrationtimefinish;

        // We need to ensure the session is in the future.
        $sessiondate = new stdClass();
        $sessiondate->timestart = time() + 2 * DAYSECS;
        $sessiondate->timefinish = time() + 3 * DAYSECS;
        $sessiondate->sessiontimezone = 'Pacific/Auckland';
        $sessiondate->roomid = 0;
        $sessiondate->assetids = array();
        $sessiondata->sessiondates = array($sessiondate);

        $sessionid = $this->facetoface_generator->add_session($sessiondata);
        $session = facetoface_get_session($sessionid);

        // First of all with minimal set to true. Meaning get_regdates_tooltip_info is called.
        $returnedoutput = $renderer->print_session_list_table(array($session), false, false, $displaytimezones, array(), null, true);

        // The Sign-up period open date will always been first in the string, so we can check that it will indeed
        // be part of a a title attribute.
        if (isset($registrationtimestart)) {
            $this->assertContains('title="Sign-up period opens:', $returnedoutput);
        } else {
            $this->assertNotContains('title="Sign-up period opens:', $returnedoutput);
        }

        // Currently, text like in the strings below only appears in the Sign-up period tooltip. If other elements start
        // using the same text, then the below assertions may be less useful.
        if (isset($registrationtimefinish)) {
            $this->assertContains('Sign-up period closes:', $returnedoutput);
        } else {
            $this->assertNotContains('Sign-up period closes:', $returnedoutput);
        }

        // Now with minimal set to false, meaning other fixed strings are used for the tooltip instead of get_regdates_tooltip_info.
        $returnedoutput = $renderer->print_session_list_table(array($session), false, false, $displaytimezones, array(), null, false);

        // We shouldn't get the detailed output that comes from get_regdates_tooltip_info as this information
        // is given in another column.
        $this->assertFalse(strpos($returnedoutput, 'title="Sign-up period opens:'));
        $this->assertFalse(strpos($returnedoutput, 'Sign-up period closes:'));
    }
}
