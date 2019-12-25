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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package mod_facetoface
 */

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once("{$CFG->dirroot}/mod/facetoface/lib.php");
require_once("{$CFG->dirroot}/lib/modinfolib.php");


/**
 * Test suite of checking the compabilities of the configs within facetoface
 * @see facetoface_cm_info_view
 */
class mod_facetoface_cm_info_view_testcase extends advanced_testcase {
    /**
     * Create facetoface
     * Create events
     *
     * The array $configs supporting keys:
     * - display: int
     * - multiplesessions: boolean
     *
     * @param array     $configs
     * @param stdClass  $course
     * @return stdClass
     */
    private function create_facetoface_with_events(stdClass $course, array $configs, $numberofevents): stdClass {
        /** @var mod_facetoface_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator("mod_facetoface");

        $data = array(
            'display' => isset($configs['display']) ? $configs['display'] : 6,
            'multiplesessions' => isset($configs['multiplesessions']) && $configs['multiplesessions'] ? 1 : 0,
            'course' => $course->id,
        );

        $facetoface = $generator->create_instance((object)$data, ['section' => 0]);
        $time = time() + (DAYSECS * 2);
        for ($i = 0; $i < $numberofevents; $i++) {
            $record = [
                'facetoface' => $facetoface->id,
                'sessiondates' => [
                    (object)[
                        'timestart' => $time,
                        'timefinish' => $time + 3600,
                        'sessiontimezone' => 'Pacific/Auckland',
                        'roomid' => 0,
                        'assetids' => []
                    ],
                ]
            ];
            $generator->add_session((object)$record);
        }

        return $facetoface;
    }

    /**
     * Creating a sign up for user with the event. Before signing up the user to the event, method must checking whether
     * the session had started or not. If it had been started, then exception would be thrown
     *
     * @param stdClass $user
     * @param stdClass $event
     * @param stdClass $facetoface
     * @param stdClass $course
     *
     * @throws Exception
     */
    private function create_signup(stdClass $user, stdClass $event, stdClass $facetoface, stdClass $course): void {
        /** @var mod_facetoface_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator("mod_facetoface");
        $refClass = new ReflectionClass($generator);

        // Setting up the attribute $mapsessionf2f through reflection
        // as there is no public interface to update it
        $mapsessionf2f = $refClass->getProperty("mapsessionf2f");
        $mapsessionf2f->setAccessible(true);
        $mapsessionf2f->setValue($generator, array($event->id => $facetoface));

        // Setting up the attribute $mapsessioncourse through reflection
        // as there no public interface to update it
        $mapsessioncourse = $refClass->getProperty("mapsessioncourse");
        $mapsessioncourse->setAccessible(true);
        $mapsessioncourse->setValue($generator, array($event->id => $course));

        if (facetoface_has_session_started($event, time())) {
            throw new Exception("The session had started, therefore, cannot sign up the user");
        }
        $generator->create_signup($user, $event);
    }

    /**
     * The assertion method for test suites of checking the number of row rendered.
     * The idea is simple, if the numberofeventexpected is zero, hence, the method is not expecting the tag
     * <table> within the rendered content, otherwise get the contents between tag <tbody> </tbody> and start
     * counting the number of tag <tr> to be equal with the one expected
     *
     * @param cm_info $cm_info
     * @param $numberofeventexpected
     */
    private function perform_assertion(cm_info $cm_info, $numberofeventexpected): void {
        $content = $cm_info->content;

        if ($numberofeventexpected == 0) {
            $this->assertFalse(stripos($content, "<table>"));
        } else {
            $start = stripos($content, "<tbody>");
            $stop = stripos($content, "</tbody>");
            if (!$start || !$stop) {
                $this->fail("There is no tabl  body for the test of {$numberofeventexpected} row expected");
            }

            $length = strlen($content) - ($stop+8); // where 8 is the length of </tbody>
            $body = substr($content, $start, -$length);
            preg_match_all("/(<\/tr>)/", $body,$matches);

            if (empty($matches) || !isset($matches[0])) {
                $this->fail("No matches found for the number of row expected: {$numberofeventexpected}");
            }
            $this->assertCount($numberofeventexpected, $matches[0]);
        }
    }

    /**
     * Test suite of checking whether the method facetoface_cm_info_view is giving the right rendering when the config
     * of display number of seminar is set to 2. and there are 5 seminars's event within this test. Therefore, the test
     * is expecting the method facetoface_cm_info_view renders 2 rows of seminar's events
     *
     * @see facetoface_cm_info_view
     * @return void
     */
    public function test_facetoface_display_with_number_of_display_seminar_event_is_two(): void {
        global $USER, $CFG, $PAGE;
        $PAGE->set_url("{$CFG->wwwroot}/course");

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course([], ['createsections' => true]);
        $facetoface = $this->create_facetoface_with_events($course, ['display' => 2], 5);

        $modinfo = get_fast_modinfo($course, $USER->id);
        $cminfo = current($modinfo->get_cms());

        if (is_null($cminfo)) {
            $this->fail("Unable to create a seminar for the course");
        }

        facetoface_cm_info_view($cminfo);
        $this->perform_assertion($cminfo, 2);
    }

    /**
     * Test suite of checking whether the method facetoface_cm_info_view is rendering the seminar events that the config
     * displaying number of event is compatible with the config multiple sessions enabled. Since the config display is
     * being set to 3 and the multiplesession is set to true. Thefore the expected behaviour is that there should only
     * 3 rows got rendered for seminar's events
     *
     * @see facetoface_cm_info_view
     * @return void
     */
    public function test_facetoface_display_with_multiple_sessions_and_number_of_displaying_seminar_event_is_three(): void {
        global $USER, $CFG, $PAGE;
        $PAGE->set_url("{$CFG->wwwroot}/hello_world");

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course([], ['createsections' => true]);
        $facetoface = $this->create_facetoface_with_events($course, [
            'display' => 3,
            'multiplesessions' => true
        ], 6);

        $modinfo = get_fast_modinfo($course, $USER->id);
        $cminfo = current($modinfo->get_cms());
        if (is_null($cminfo)) {
            $this->fail("Unable to create a seminar for course");
        }

        facetoface_cm_info_view($cminfo);
        $this->perform_assertion($cminfo, 3);
    }

	/**
	 * Scenario: When the seminar setting is set to display with number of seminar's events (one) and the settings
     * multiplesession was set to enabled , then user got signed up to one of the event. Therefore, the render would
     * return two rows for the user
	 *
	 * @see facetoface_cm_info_view
	 * @return void
	 */
	public function test_facetoface_display_with_number_of_display_seminar_event_is_one_and_one_signup(): void {
        global $USER, $CFG, $PAGE, $DB;

        // Unset the smtphosts so that email would not be sent
        $CFG->smtphosts = null;
        $PAGE->set_url("{$CFG->wwwroot}/hello_world");

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course([], ['creatsections' => true]);
        $facetoface = $this->create_facetoface_with_events($course, [
            'display' => 1,
            'multiplesessions' => true
        ], 5);

        $events = $DB->get_records("facetoface_sessions", ['facetoface' => $facetoface->id], "", "id", 0, 1);

        $event = current($events);
        if (is_null($event)) {
            $this->fail("No event found for performing the test");
        }

        $event->sessiondates = facetoface_get_session_dates($event->id);
        $this->create_signup($USER, $event, $facetoface, $course);
        $modinfo = get_fast_modinfo($course, $USER->id);

        /** @var cm_info $cminfo */
        $cminfo = current($modinfo->get_cms());
        if(is_null($cminfo)) {
                $this->fail("Unable to create seminar for course");
        }

        facetoface_cm_info_view($cminfo);
        $this->perform_assertion($cminfo, 2);
	}
}
