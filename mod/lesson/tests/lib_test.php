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
 * Unit tests for mod/lesson/lib.php.
 *
 * @package    mod_lesson
 * @category   test
 * @copyright  2017 Jun Pataleta
 * @license    http://www.gnu.org/copyleft/gpl.html GNU Public License
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/lesson/lib.php');

/**
 * Unit tests for mod/lesson/lib.php.
 *
 * @copyright  2017 Jun Pataleta
 * @license    http://www.gnu.org/copyleft/gpl.html GNU Public License
 */
class mod_lesson_lib_testcase extends advanced_testcase {

    /**
     * Test check_updates_since callback.
     */
    public function test_check_updates_since() {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();
        $course = new stdClass();
        $course->groupmode = SEPARATEGROUPS;
        $course->groupmodeforce = true;
        $course = $this->getDataGenerator()->create_course($course);

        // Create user.
        $studentg1 = self::getDataGenerator()->create_user();
        $teacherg1 = self::getDataGenerator()->create_user();
        $studentg2 = self::getDataGenerator()->create_user();

        // User enrolment.
        $studentrole = $DB->get_record('role', array('shortname' => 'student'));
        $teacherrole = $DB->get_record('role', array('shortname' => 'editingteacher'));
        $this->getDataGenerator()->enrol_user($studentg1->id, $course->id, $studentrole->id, 'manual');
        $this->getDataGenerator()->enrol_user($teacherg1->id, $course->id, $teacherrole->id, 'manual');
        $this->getDataGenerator()->enrol_user($studentg2->id, $course->id, $studentrole->id, 'manual');

        $group1 = $this->getDataGenerator()->create_group(array('courseid' => $course->id));
        $group2 = $this->getDataGenerator()->create_group(array('courseid' => $course->id));
        groups_add_member($group1, $studentg1);
        groups_add_member($group2, $studentg2);

        $this->setCurrentTimeStart();
        $record = array(
            'course' => $course->id,
            'custom' => 0,
            'feedback' => 1,
        );
        $lessonmodule = $this->getDataGenerator()->create_module('lesson', $record);
        // Convert to a lesson object.
        $lesson = new lesson($lessonmodule);
        $cm = $lesson->cm;
        $cm = cm_info::create($cm);

        // Check that upon creation, the updates are only about the new configuration created.
        $onehourago = time() - HOURSECS;
        $updates = lesson_check_updates_since($cm, $onehourago);
        foreach ($updates as $el => $val) {
            if ($el == 'configuration') {
                $this->assertTrue($val->updated);
                $this->assertTimeCurrent($val->timeupdated);
            } else {
                $this->assertFalse($val->updated);
            }
        }

        // Set up a generator to create content.
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_lesson');
        $tfrecord = $generator->create_question_truefalse($lesson);

        // Check now for pages and answers.
        $updates = lesson_check_updates_since($cm, $onehourago);
        $this->assertTrue($updates->pages->updated);
        $this->assertCount(1, $updates->pages->itemids);

        $this->assertTrue($updates->answers->updated);
        $this->assertCount(2, $updates->answers->itemids);

        // Now, do something in the lesson with the two users.
        $this->setUser($studentg1);
        mod_lesson_external::launch_attempt($lesson->id);
        $data = array(
            array(
                'name' => 'answerid',
                'value' => $DB->get_field('lesson_answers', 'id', array('pageid' => $tfrecord->id, 'jumpto' => -1)),
            ),
            array(
                'name' => '_qf__lesson_display_answer_form_truefalse',
                'value' => 1,
            )
        );
        mod_lesson_external::process_page($lesson->id, $tfrecord->id, $data);
        mod_lesson_external::finish_attempt($lesson->id);

        $this->setUser($studentg2);
        mod_lesson_external::launch_attempt($lesson->id);
        $data = array(
            array(
                'name' => 'answerid',
                'value' => $DB->get_field('lesson_answers', 'id', array('pageid' => $tfrecord->id, 'jumpto' => -1)),
            ),
            array(
                'name' => '_qf__lesson_display_answer_form_truefalse',
                'value' => 1,
            )
        );
        mod_lesson_external::process_page($lesson->id, $tfrecord->id, $data);
        mod_lesson_external::finish_attempt($lesson->id);

        $this->setUser($studentg1);
        $updates = lesson_check_updates_since($cm, $onehourago);

        // Check question attempts, timers and new grades.
        $this->assertTrue($updates->questionattempts->updated);
        $this->assertCount(1, $updates->questionattempts->itemids);

        $this->assertTrue($updates->grades->updated);
        $this->assertCount(1, $updates->grades->itemids);

        $this->assertTrue($updates->timers->updated);
        $this->assertCount(1, $updates->timers->itemids);

        // Now, as teacher, check that I can see the two users (even in separate groups).
        $this->setUser($teacherg1);
        $updates = lesson_check_updates_since($cm, $onehourago);
        $this->assertTrue($updates->userquestionattempts->updated);
        $this->assertCount(2, $updates->userquestionattempts->itemids);

        $this->assertTrue($updates->usergrades->updated);
        $this->assertCount(2, $updates->usergrades->itemids);

        $this->assertTrue($updates->usertimers->updated);
        $this->assertCount(2, $updates->usertimers->itemids);

        // Now, teacher can't access all groups.
        groups_add_member($group1, $teacherg1);
        assign_capability('moodle/site:accessallgroups', CAP_PROHIBIT, $teacherrole->id, context_module::instance($cm->id));
        accesslib_clear_all_caches_for_unit_testing();
        $updates = lesson_check_updates_since($cm, $onehourago);
        // I will see only the studentg1 updates.
        $this->assertTrue($updates->userquestionattempts->updated);
        $this->assertCount(1, $updates->userquestionattempts->itemids);

        $this->assertTrue($updates->usergrades->updated);
        $this->assertCount(1, $updates->usergrades->itemids);

        $this->assertTrue($updates->usertimers->updated);
        $this->assertCount(1, $updates->usertimers->itemids);
    }
}
