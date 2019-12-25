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
 * Unit tests for (some of) mod/feedback/lib.php.
 *
 * @package    mod_feedback
 * @copyright  2016 Stephen Bourget
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot . '/mod/feedback/lib.php');

/**
 * Unit tests for (some of) mod/feedback/lib.php.
 *
 * @copyright  2016 Stephen Bourget
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_feedback_lib_testcase extends advanced_testcase {

    public function test_feedback_get_completion_progress() {
        global $DB;
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $feedback = $this->getDataGenerator()->create_module('feedback', ['course' => $course->id, 'completionsubmit' => '1']);
        list($course, $cm) = get_course_and_cm_from_cmid($feedback->cmid, 'feedback');
        // This is tragic, but the creation of items is hardwired through mforms, and this style is copied from
        // the existing event tests found in mod/feedback/tests/events_test.php
        $item = new stdClass();
        $item->feedback = $feedback->id;
        $item->name = 'test';
        $item->typ = 'numeric';
        $item->presentation = '0|0';
        $item->hasvalue = '1';
        $itemid = $DB->insert_record('feedback_item', $item);
        $this->getDataGenerator()->enrol_user($user->id, $course->id);

        $this->setUser($user);

        $feedbackcompletion = new mod_feedback_completion($feedback, $cm, $course->id);
        $items = $feedbackcompletion->get_items();
        $this->assertTrue($feedbackcompletion->is_open());
        $this->assertFalse($feedbackcompletion->is_already_submitted());
        $this->assertTrue($feedbackcompletion->can_complete());
        $this->assertFalse($feedbackcompletion->is_empty());
        $this->assertCount(1, $items);

        $this->assertSame(array(), feedback_get_completion_progress($cm, $user->id));

        $data = new stdClass;
        $data->{'numeric_'.$itemid} = '1';
        $feedbackcompletion->save_response_tmp($data);

        // Its just temp presently.
        $this->assertFalse($feedbackcompletion->is_already_submitted());
        $this->assertSame(array(), feedback_get_completion_progress($cm, $user->id));

        // And now commit the save.
        $feedbackcompletion->save_response();
        $this->assertTrue($feedbackcompletion->is_already_submitted());
        $this->assertTrue($feedbackcompletion->can_complete());
        $this->assertSame(array('Submitted'), feedback_get_completion_progress($cm, $user->id));
    }

    /**
     * Tests for mod_feedback_refresh_events.
     */
    public function test_feedback_refresh_events() {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        $timeopen = time();
        $timeclose = time() + 86400;

        $course = $this->getDataGenerator()->create_course();
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_feedback');
        $params['course'] = $course->id;
        $params['timeopen'] = $timeopen;
        $params['timeclose'] = $timeclose;
        $feedback = $generator->create_instance($params);
        $cm = get_coursemodule_from_instance('feedback', $feedback->id);
        $context = context_module::instance($cm->id);

        // Normal case, with existing course.
        $this->assertTrue(feedback_refresh_events($course->id));
        $eventparams = array('modulename' => 'feedback', 'instance' => $feedback->id, 'eventtype' => 'open');
        $openevent = $DB->get_record('event', $eventparams, '*', MUST_EXIST);
        $this->assertEquals($openevent->timestart, $timeopen);

        $eventparams = array('modulename' => 'feedback', 'instance' => $feedback->id, 'eventtype' => 'close');
        $closeevent = $DB->get_record('event', $eventparams, '*', MUST_EXIST);
        $this->assertEquals($closeevent->timestart, $timeclose);
        // In case the course ID is passed as a numeric string.
        $this->assertTrue(feedback_refresh_events('' . $course->id));
        // Course ID not provided.
        $this->assertTrue(feedback_refresh_events());
        $eventparams = array('modulename' => 'feedback');
        $events = $DB->get_records('event', $eventparams);
        foreach ($events as $event) {
            if ($event->modulename === 'feedback' && $event->instance === $feedback->id && $event->eventtype === 'open') {
                $this->assertEquals($event->timestart, $timeopen);
            }
            if ($event->modulename === 'feedback' && $event->instance === $feedback->id && $event->eventtype === 'close') {
                $this->assertEquals($event->timestart, $timeclose);
            }
        }
    }
}
