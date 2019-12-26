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
 * SCORM module library functions tests
 *
 * @package    mod_scorm
 * @category   test
 * @copyright  2015 Juan Leyva <juan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 3.0
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/webservice/tests/helpers.php');
require_once($CFG->dirroot . '/mod/scorm/lib.php');

/**
 * SCORM module library functions tests
 *
 * @package    mod_scorm
 * @category   test
 * @copyright  2015 Juan Leyva <juan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 3.0
 */
class mod_scorm_lib_testcase extends externallib_advanced_testcase {

    /**
     * Set up for every test
     */
    public function setUp() {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        // Setup test data.
        $this->course = $this->getDataGenerator()->create_course(array('enablecompletion' => true));
        $this->scorm = $this->getDataGenerator()->create_module('scorm', array('course' => $this->course->id));
        $this->context = context_module::instance($this->scorm->cmid);
        $this->cm = get_coursemodule_from_instance('scorm', $this->scorm->id);

        // Create users.
        $this->student = self::getDataGenerator()->create_user();
        $this->teacher = self::getDataGenerator()->create_user();

        // Users enrolments.
        $this->studentrole = $DB->get_record('role', array('shortname' => 'student'));
        $this->teacherrole = $DB->get_record('role', array('shortname' => 'editingteacher'));
        $this->getDataGenerator()->enrol_user($this->student->id, $this->course->id, $this->studentrole->id, 'manual');
        $this->getDataGenerator()->enrol_user($this->teacher->id, $this->course->id, $this->teacherrole->id, 'manual');
    }

    /** Test scorm_check_mode
     *
     * @return void
     */
    public function test_scorm_check_mode() {
        global $CFG;

        $newattempt = 'on';
        $attempt = 1;
        $mode = 'normal';
        scorm_check_mode($this->scorm, $newattempt, $attempt, $this->student->id, $mode);
        $this->assertEquals('off', $newattempt);

        $scoes = scorm_get_scoes($this->scorm->id);
        $sco = array_pop($scoes);
        scorm_insert_track($this->student->id, $this->scorm->id, $sco->id, 1, 'cmi.core.lesson_status', 'completed');
        $newattempt = 'on';
        scorm_check_mode($this->scorm, $newattempt, $attempt, $this->student->id, $mode);
        $this->assertEquals('on', $newattempt);

        // Now do the same with a SCORM 2004 package.
        $record = new stdClass();
        $record->course = $this->course->id;
        $record->packagefilepath = $CFG->dirroot.'/mod/scorm/tests/packages/RuntimeBasicCalls_SCORM20043rdEdition.zip';
        $scorm13 = $this->getDataGenerator()->create_module('scorm', $record);
        $newattempt = 'on';
        $attempt = 1;
        $mode = 'normal';
        scorm_check_mode($scorm13, $newattempt, $attempt, $this->student->id, $mode);
        $this->assertEquals('off', $newattempt);

        $scoes = scorm_get_scoes($scorm13->id);
        $sco = array_pop($scoes);
        scorm_insert_track($this->student->id, $scorm13->id, $sco->id, 1, 'cmi.completion_status', 'completed');

        $newattempt = 'on';
        $attempt = 1;
        $mode = 'normal';
        scorm_check_mode($scorm13, $newattempt, $attempt, $this->student->id, $mode);
        $this->assertEquals('on', $newattempt);
    }

    /**
     * Test scorm_view
     * @return void
     */
    public function test_scorm_view() {
        global $CFG;

        // Trigger and capture the event.
        $sink = $this->redirectEvents();

        scorm_view($this->scorm, $this->course, $this->cm, $this->context);

        $events = $sink->get_events();
        $this->assertCount(1, $events);
        $event = array_shift($events);

        // Checking that the event contains the expected values.
        $this->assertInstanceOf('\mod_scorm\event\course_module_viewed', $event);
        $this->assertEquals($this->context, $event->get_context());
        $url = new \moodle_url('/mod/scorm/view.php', array('id' => $this->cm->id));
        $this->assertEquals($url, $event->get_url());
        $this->assertEventContextNotUsed($event);
        $this->assertNotEmpty($event->get_name());
    }

    /**
     * Test scorm_get_availability_status and scorm_require_available
     * @return void
     */
    public function test_scorm_check_and_require_available() {
        global $DB;

        // Set to the student user.
        self::setUser($this->student);

        // Usual case.
        list($status, $warnings) = scorm_get_availability_status($this->scorm, false);
        $this->assertEquals(true, $status);
        $this->assertCount(0, $warnings);

        // SCORM not open.
        $this->scorm->timeopen = time() + DAYSECS;
        list($status, $warnings) = scorm_get_availability_status($this->scorm, false);
        $this->assertEquals(false, $status);
        $this->assertCount(1, $warnings);

        // SCORM closed.
        $this->scorm->timeopen = 0;
        $this->scorm->timeclose = time() - DAYSECS;
        list($status, $warnings) = scorm_get_availability_status($this->scorm, false);
        $this->assertEquals(false, $status);
        $this->assertCount(1, $warnings);

        // SCORM not open and closed.
        $this->scorm->timeopen = time() + DAYSECS;
        list($status, $warnings) = scorm_get_availability_status($this->scorm, false);
        $this->assertEquals(false, $status);
        $this->assertCount(2, $warnings);

        // Now additional checkings with different parameters values.
        list($status, $warnings) = scorm_get_availability_status($this->scorm, true, $this->context);
        $this->assertEquals(false, $status);
        $this->assertCount(2, $warnings);

        // SCORM not open.
        $this->scorm->timeopen = time() + DAYSECS;
        $this->scorm->timeclose = 0;
        list($status, $warnings) = scorm_get_availability_status($this->scorm, true, $this->context);
        $this->assertEquals(false, $status);
        $this->assertCount(1, $warnings);

        // SCORM closed.
        $this->scorm->timeopen = 0;
        $this->scorm->timeclose = time() - DAYSECS;
        list($status, $warnings) = scorm_get_availability_status($this->scorm, true, $this->context);
        $this->assertEquals(false, $status);
        $this->assertCount(1, $warnings);

        // SCORM not open and closed.
        $this->scorm->timeopen = time() + DAYSECS;
        list($status, $warnings) = scorm_get_availability_status($this->scorm, true, $this->context);
        $this->assertEquals(false, $status);
        $this->assertCount(2, $warnings);

        // As teacher now.
        self::setUser($this->teacher);

        // SCORM not open and closed.
        $this->scorm->timeopen = time() + DAYSECS;
        list($status, $warnings) = scorm_get_availability_status($this->scorm, false);
        $this->assertEquals(false, $status);
        $this->assertCount(2, $warnings);

        // Now, we use the special capability.
        // SCORM not open and closed.
        $this->scorm->timeopen = time() + DAYSECS;
        list($status, $warnings) = scorm_get_availability_status($this->scorm, true, $this->context);
        $this->assertEquals(true, $status);
        $this->assertCount(0, $warnings);

        // Check exceptions does not broke anything.
        scorm_require_available($this->scorm, true, $this->context);
        // Now, expect exceptions.
        $this->expectException('moodle_exception');
        $this->expectExceptionMessage(get_string("notopenyet", "scorm", userdate($this->scorm->timeopen)));

        // Now as student other condition.
        self::setUser($this->student);
        $this->scorm->timeopen = 0;
        $this->scorm->timeclose = time() - DAYSECS;

        $this->expectException('moodle_exception');
        $this->expectExceptionMessage(get_string("expired", "scorm", userdate($this->scorm->timeclose)));
        scorm_require_available($this->scorm, false);
    }

    /**
     * Test scorm_get_last_completed_attempt
     *
     * @return void
     */
    public function test_scorm_get_last_completed_attempt() {
        $this->assertEquals(1, scorm_get_last_completed_attempt($this->scorm->id, $this->student->id));
    }

    /**
     * Test scorm print overview.
     */
    public function test_scorm_print_overview() {
        global $CFG;

        $this->resetAfterTest();

        $CFG->enablecompletion = true;

        // Delete the existing scorm.
        $this->assertNull(course_delete_module($this->cm->id));

        // Create and delete a label.
        $label = $this->getDataGenerator()->create_module('label', array('course' => $this->course->id));
        $this->assertNull(course_delete_module($label->cmid));

        // Create a new scorm activity.
        $this->getDataGenerator()->create_module('scorm', array(
            'course' => $this->course->id,
            'completion' => COMPLETION_TRACKING_MANUAL
        ));

        scorm_print_overview(array($this->course->id => $this->course), $details);
        $this->assertIsArray($details);
        $this->assertCount(1, $details);
        $scormdetails = reset($details);
        $this->assertIsArray($scormdetails);
        $this->assertCount(1, $scormdetails);
        $this->assertArrayHasKey('scorm', $scormdetails);
        $this->assertContains('SCORM package:', $scormdetails['scorm']);
    }

    /**
     * Test of the scorm lib functions:
     *   - scorm_get_completion_progress
     *   - scorm_get_completion_state
     */
    public function test_scorm_get_completion_progress_and_state() {
        global $CFG;
        require_once($CFG->libdir . '/gradelib.php');

        /** @var core_grades_generator $grade_generator */
        $grade_generator = self::getDataGenerator()->get_plugin_generator('core_grades');

        // Create an additional student.
        $student2 = self::getDataGenerator()->create_user();
        self::getDataGenerator()->enrol_user($student2->id, $this->course->id, 'student');

        $completion = new completion_info($this->course);

        // Test case 1: view requirement
        $scorm1 = self::getDataGenerator()->create_module(
            'scorm',
            [
                'course'         => $this->course->id,
                'completion'     => COMPLETION_TRACKING_AUTOMATIC,
                'completionview' => COMPLETION_VIEW_REQUIRED,
            ]
        );
        $cm1 = get_coursemodule_from_id('scorm', $scorm1->cmid, $this->course->id);
        scorm_update_grades($scorm1, $this->student->id);

        // SCORM doesn't handle 'view' requirement, so state returns its type and completion should be empty.
        self::assertTrue(scorm_get_completion_state($this->course, $cm1, $this->student->id, true));
        self::assertEqualsCanonicalizing([], scorm_get_completion_progress($cm1, $this->student->id));

        $current = $completion->get_data($cm1, false, $this->student->id);
        self::assertEquals(COMPLETION_INCOMPLETE, $completion->internal_get_state($cm1, $this->student->id, $current));

        $completion->set_module_viewed($cm1, $this->student->id);
        $current = $completion->get_data($cm1, false, $this->student->id); // Reload completion data after viewing.
        self::assertEquals(COMPLETION_COMPLETE, $completion->internal_get_state($cm1, $this->student->id, $current));

        // SCORM doesn't handle 'view' requirement, so state still returns its type and completion should be empty.
        self::assertTrue(scorm_get_completion_state($this->course, $cm1, $this->student->id, true));
        self::assertEqualsCanonicalizing([], scorm_get_completion_progress($cm1, $this->student->id));

        // Test case 2: view + complete requirement
        $scorm2 = self::getDataGenerator()->create_module(
            'scorm',
            [
                'course'                   => $this->course->id,
                'completion'               => COMPLETION_TRACKING_AUTOMATIC,
                'completionview'           => COMPLETION_VIEW_REQUIRED,
                'completionstatusrequired' => 4 // status 'completed'
            ]
        );
        $cm2 = get_coursemodule_from_id('scorm', $scorm2->cmid, $this->course->id);
        scorm_update_grades($scorm2, $this->student->id);

        $completion->set_module_viewed($cm2, $this->student->id);

        // Viewed, but not completed, so false here.
        self::assertFalse(scorm_get_completion_state($this->course, $cm2, $this->student->id, true));
        self::assertEqualsCanonicalizing([], scorm_get_completion_progress($cm2, $this->student->id));

        $scoes = scorm_get_scoes($scorm2->id);
        $sco = array_pop($scoes);
        scorm_insert_track($this->student->id, $scorm2->id, $sco->id, 1, 'cmi.core.lesson_status', 'completed');
        self::assertEqualsCanonicalizing(['Completed'], scorm_get_completion_progress($cm2, $this->student->id));

        // Test case 3: view + grade requirement
        $scorm3 = self::getDataGenerator()->create_module(
            'scorm',
            [
                'course'                  => $this->course->id,
                'completion'              => COMPLETION_TRACKING_AUTOMATIC,
                'completionview'          => COMPLETION_VIEW_REQUIRED,
                'grademethod'             => GRADEHIGHEST,
                'completionscorerequired' => 65,
            ]
        );
        $cm3 = get_coursemodule_from_id('scorm', $scorm3->cmid, $this->course->id);
        scorm_update_grades($scorm3, $this->student->id);
        scorm_update_grades($scorm3, $student2->id);

        $completion->set_module_viewed($cm3, $this->student->id);
        $completion->set_module_viewed($cm3, $student2->id);

        // Viewed, but no grade, so false here.
        self::assertFalse(scorm_get_completion_state($this->course, $cm3, $this->student->id, true));
        self::assertFalse(scorm_get_completion_state($this->course, $cm3, $student2->id, true));

        self::assertEqualsCanonicalizing(['Scored 0'], scorm_get_completion_progress($cm3, $this->student->id));
        self::assertEqualsCanonicalizing(['Scored 0'], scorm_get_completion_progress($cm3, $student2->id));

        // 3.1: view + grade requirement (without tracking data)
        // Adding a grade manually for one user.
        $gradeparams = ['courseid' => $this->course->id, 'itemtype' => 'mod', 'itemmodule' => 'scorm', 'iteminstance' => $cm3->instance];
        $item = grade_item::fetch($gradeparams);
        $grade_generator->new_grade_for_item($item->id, 70, $this->student);
        self::assertEqualsCanonicalizing(['Scored 70'], scorm_get_completion_progress($cm3, $this->student->id));

        // 3.2: view + grade requirement (with tracking data)
        $scoes = scorm_get_scoes($scorm3->id);
        $sco = array_pop($scoes);
        scorm_insert_track($student2->id, $scorm3->id, $sco->id, 1, 'cmi.core.score.raw', 55);
        self::assertEqualsCanonicalizing(['Scored 55'], scorm_get_completion_progress($cm3, $student2->id));

        self::assertTrue(scorm_get_completion_state($this->course, $cm3, $this->student->id, true));
        self::assertFalse(scorm_get_completion_state($this->course, $cm3, $student2->id, true));

        // Test case 4: grade + passed requirement
        $scorm4 = self::getDataGenerator()->create_module(
            'scorm', [
                'course' => $this->course->id,
                'completion'               => COMPLETION_TRACKING_AUTOMATIC,
                'completionscorerequired'  => 60,
                'grademethod'              => GRADEHIGHEST,
                'completionstatusrequired' => 2 // status 'passed'
            ]
        );

        $cm4 = get_coursemodule_from_id('scorm', $scorm4->cmid, $this->course->id);
        scorm_update_grades($scorm4, $this->student->id);
        scorm_update_grades($scorm4, $student2->id);

        self::assertFalse(scorm_get_completion_state($this->course, $cm4, $this->student->id, true));
        self::assertFalse(scorm_get_completion_state($this->course, $cm4, $student2->id, true));

        self::assertEqualsCanonicalizing(['Scored 0'], scorm_get_completion_progress($cm4, $this->student->id));
        self::assertEqualsCanonicalizing(['Scored 0'], scorm_get_completion_progress($cm4, $student2->id));

        $gradeparams = ['courseid' => $this->course->id, 'itemtype' => 'mod', 'itemmodule' => 'scorm', 'iteminstance' => $cm4->instance];
        $item = grade_item::fetch($gradeparams);

        // 4.1: grade + passed requirement (without tracking data)
        // Add a grade for the first user.
        $grade_generator->new_grade_for_item($item->id, 85, $this->student);

        self::assertFalse(scorm_get_completion_state($this->course, $cm4, $this->student->id, true));
        self::assertEqualsCanonicalizing(['Scored 85'], scorm_get_completion_progress($cm4, $this->student->id));

        // 4.2: grade + passed requirement (with tracking data)
        // Add a grade and a 'passed' track for the second user.
        $scoes = scorm_get_scoes($scorm4->id);
        $sco = array_pop($scoes);
        scorm_insert_track($student2->id, $scorm4->id, $sco->id, 1, 'cmi.core.lesson_status', 'passed');

        $grade_generator->new_grade_for_item($item->id, 70, $student2);

        self::assertTrue(scorm_get_completion_state($this->course, $cm4, $student2->id, true));
        self::assertEqualsCanonicalizing(['Scored 70', 'Passed'], scorm_get_completion_progress($cm4, $student2->id));
    }
}
