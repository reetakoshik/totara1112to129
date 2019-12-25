<?php
/**
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Andrew McGhie <andrew.mcghie@totaralearning.com>
 * @package core_notes
 */

use core_notes\userdata\notes_purge;
use totara_userdata\userdata\target_user;

/**
 * Tests the purging of the notes.
 *
 * @group totara_userdata
 */
class core_notes_userdata_notes_purge_testcase extends advanced_testcase {

    /**
     * Gets the data for all the tests.
     */
    private function get_data() {
        $data = new class() {
            /** @var target_user */
            public $activeuser, $deleteduser;
            /** @var array */
            public $courses, $notes;
        };
        $activeuser = $this->getDataGenerator()->create_user();
        $deleteduser = $this->getDataGenerator()->create_user(['deleted' => 1]);
        $data->activeuser = new target_user($activeuser);
        $data->deleteduser = new target_user($deleteduser);

        $data->courses[] = $this->getDataGenerator()->create_course();
        $data->courses[] = $this->getDataGenerator()->create_course();

        /** @var core_notes_generator $notegenerator */
        $notegenerator = $this->getDataGenerator()->get_plugin_generator('core_notes');

        $data->notes[] = $notegenerator->create_instance(['userid' => $activeuser->id, 'courseid' => $data->courses[0]->id]);
        $data->notes[] = $notegenerator->create_instance(['userid' => $activeuser->id, 'courseid' => $data->courses[1]->id]);
        $data->notes[] = $notegenerator->create_instance(['userid' => $deleteduser->id, 'courseid' => $data->courses[0]->id]);
        $data->notes[] = $notegenerator->create_instance(['userid' => $deleteduser->id, 'courseid' => $data->courses[1]->id]);
        $data->notes[] = $notegenerator->create_instance([
            'userid' => $activeuser->id,
            'courseid' => $data->courses[0]->id,
            'publishstate' => NOTES_STATE_PUBLIC
        ]);
        $data->notes[] = $notegenerator->create_instance([
            'userid' => $activeuser->id,
            'courseid' => $data->courses[1]->id,
            'publishstate' => NOTES_STATE_PUBLIC
        ]);
        $this->setUser($activeuser);
        $data->notes[] = $notegenerator->create_instance([
            'userid' => $activeuser->id,
            'courseid' => $data->courses[0]->id,
            'publishstate' => NOTES_STATE_DRAFT
        ]);
        $data->notes[] = $notegenerator->create_instance([
            'userid' => $activeuser->id,
            'courseid' => $data->courses[1]->id,
            'publishstate' => NOTES_STATE_DRAFT
        ]);
        $this->setAdminUser();

        return $data;
    }

    /**
     * Tests that when purging the notes for a user are all deleted on purge.
     */
    public function test_purge_removes_notes_on_a_user() {
        global $DB;
        $this->resetAfterTest();
        $data = $this->get_data();
        $systemcontext = context_system::instance();

        $result = notes_purge::execute_purge($data->activeuser, $systemcontext);
        $this->assertEquals(notes_purge::RESULT_STATUS_SUCCESS, $result);

        $count = $DB->count_records('post', ['userid' => $data->activeuser->id]);
        $this->assertEquals(0, $count);
    }

    /**
     * Make sure purging doesnt remove other notes.
     */
    public function test_purge_doesnt_remove_other_notes() {
        $this->resetAfterTest();
        $data = $this->get_data();
        $systemcontext = context_system::instance();

        // Makes sure it doesnt remove visible notes.
        $course0context = context_course::instance($data->courses[0]->id);
        $countdeletedueser = notes_purge::execute_count($data->deleteduser, $systemcontext);

        $result = notes_purge::execute_purge($data->activeuser, $systemcontext);
        $this->assertEquals(notes_purge::RESULT_STATUS_SUCCESS, $result);

        // Other user remains unchanged.
        $newcount = notes_purge::execute_count($data->deleteduser, $systemcontext);
        $this->assertEquals($countdeletedueser, $newcount);

        $activeusernewcount = notes_purge::execute_count($data->activeuser, $course0context);
        $this->assertEquals(0, $activeusernewcount);
    }

    /**
     * Test that when deleting the notes in the course context.
     * that only the notes in that course.
     */
    public function test_purge_course_context_only_removes_notes_in_course() {
        $this->resetAfterTest();
        $data = $this->get_data();
        $coursecontext = context_course::instance($data->courses[0]->id);
        $systemcontext = context_system::instance();

        $activeusernotcourse0 = array_filter($data->notes, function($note) use ($data) {
            return $note->userid == $data->activeuser->id
                && ($note->publishstate == NOTES_STATE_SITE || $note->courseid != $data->courses[0]->id);
        });

        $otheruserbeforecount = notes_purge::execute_count($data->deleteduser, $coursecontext);

        $result = notes_purge::execute_purge($data->activeuser, $coursecontext);
        $this->assertEquals(notes_purge::RESULT_STATUS_SUCCESS, $result);

        $activeusercoursecount = notes_purge::execute_count($data->activeuser, $coursecontext);
        $this->assertEquals(0, $activeusercoursecount);

        $activeusersystemcount = notes_purge::execute_count($data->activeuser, $systemcontext);
        $this->assertEquals(count($activeusernotcourse0), $activeusersystemcount);

        $deletedusercount = notes_purge::execute_count($data->deleteduser, $coursecontext);
        $this->assertEquals($otheruserbeforecount, $deletedusercount);
    }

    /**
     * Make sure count returns 0 after a purge.
     */
    public function test_count_zero_after_purge() {
        $this->resetAfterTest();
        $data = $this->get_data();
        $systemcontext = context_system::instance();

        $currentcount = notes_purge::execute_count($data->activeuser, $systemcontext);
        $this->assertGreaterThan(0, $currentcount);

        $result = notes_purge::execute_purge($data->activeuser, $systemcontext);
        $this->assertEquals(notes_purge::RESULT_STATUS_SUCCESS, $result);

        $currentcount = notes_purge::execute_count($data->activeuser, $systemcontext);
        $this->assertEquals(0, $currentcount);

        $currentcount = notes_purge::execute_count($data->deleteduser, $systemcontext);
        $this->assertGreaterThan(0, $currentcount);

        $result = notes_purge::execute_purge($data->deleteduser, $systemcontext);
        $this->assertEquals(notes_purge::RESULT_STATUS_SUCCESS, $result);

        $currentcount = notes_purge::execute_count($data->deleteduser, $systemcontext);
        $this->assertEquals(0, $currentcount);
    }

    /**
     * Makes sure that count counts the notes for only one user,
     * regardless of course and published state.
     */
    public function test_count_is_correct() {
        global $DB;
        $this->resetAfterTest();
        $data = $this->get_data();
        $systemcontext = \context_system::instance();
        /** @var core_notes_generator $notegenerator */
        $notegenerator = $this->getDataGenerator()->get_plugin_generator('core_notes');
        $course = $this->getDataGenerator()->create_course();

        $startingcount = notes_purge::execute_count($data->activeuser, $systemcontext);

        // Adding a note to a different course increased the count by 1.
        $notegenerator->create_instance([
            'userid' => $data->activeuser->id,
            'courseid' => $course->id
        ]);
        $currentcount = notes_purge::execute_count($data->activeuser, $systemcontext);
        $this->assertEquals($startingcount + 1, $currentcount);

        // Adding a note to a different users does not change the count.
        $notegenerator->create_instance(['userid' => $data->deleteduser->id, 'courseid' => $course->id]);
        $currentcount = notes_purge::execute_count($data->activeuser, $systemcontext);
        $this->assertEquals($startingcount + 1, $currentcount);

        // Notes in different states are counted.
        $user = $DB->get_record('user', ['id' => $data->activeuser->id]);
        $this->setUser($user);
        $notegenerator->create_instance([
            'userid' => $data->activeuser->id,
            'courseid' => $course->id,
            'publishstate' => NOTES_STATE_DRAFT
        ]);
        $this->setAdminUser();
        $currentcount = notes_purge::execute_count($data->activeuser, $systemcontext);
        $this->assertEquals($startingcount + 2, $currentcount);
        $notegenerator->create_instance([
            'userid' => $data->activeuser->id,
            'courseid' => $course->id,
            'publishstate' => NOTES_STATE_PUBLIC
        ]);
        $currentcount = notes_purge::execute_count($data->activeuser, $systemcontext);
        $this->assertEquals($startingcount + 3, $currentcount);
    }

    /**
     * Tests that the count limits course context to only notes in the course.
     */
    public function test_count_course_context_only_includes_notes_in_course() {
        $this->resetAfterTest();
        $data = $this->get_data();
        $coursecontext = context_course::instance($data->courses[0]->id);

        $userid = $data->activeuser->id;
        $courseid = $data->courses[0]->id;

        $course0notes = array_filter($data->notes, function($note) use ($userid, $courseid) {
            return $note->userid == $userid &&
                $note->courseid == $courseid &&
                $note->publishstate != NOTES_STATE_SITE;
        });

        $currentcount = notes_purge::execute_count($data->activeuser, $coursecontext);
        $this->assertEquals(count($course0notes), $currentcount);

        $notegenerator = $this->getDataGenerator()->get_plugin_generator('core_notes');
        $notegenerator->create_instance([
            'userid' => $data->activeuser->id,
            'courseid' => $courseid,
            'publishstate' => NOTES_STATE_PUBLIC
        ]);

        $currentcount = notes_purge::execute_count($data->activeuser, $coursecontext);
        $this->assertEquals(count($course0notes) + 1, $currentcount);

        $notegenerator->create_instance([
            'userid' => $data->activeuser->id,
            'courseid' => $courseid,
            'publishstate' => NOTES_STATE_SITE
        ]);

        // Site notes are not included.
        $currentcount = notes_purge::execute_count($data->activeuser, $coursecontext);
        $this->assertEquals(count($course0notes) + 1, $currentcount);
    }

    /**
     * Tests that count still count the notes when the user has being deleted.
     * Assumes that the notes are not deleted when the user is deleted.
     */
    public function test_count_works_on_deleted_users() {
        $this->resetAfterTest();
        $data = $this->get_data();
        $systemcontext = context_system::instance();

        $deletedusernotes = array_filter($data->notes, function($note) use ($data) {
            return $note->userid == $data->deleteduser->id;
        });

        $result = notes_purge::execute_count($data->deleteduser, $systemcontext);
        $this->assertEquals(count($deletedusernotes), $result);
    }

    /**
     * Set up the data for testing categories
     */
    private function get_category_data() {
        $data = new class() {
            /** @var stdclass */
            public $user;
            /** @var stdclass */
            public $category, $subcategory;
            /** @var target_user */
            public $usertarget;
            /** @var array */
            public $notes;
        };
        $data->user = $this->getDataGenerator()->create_user();
        $data->category = $this->getDataGenerator()->create_category();
        $data->subcategory = $this->getDataGenerator()->create_category(['parent' => $data->category->id]);
        $data->course = $this->getDataGenerator()->create_course(['category' => $data->category->id]);
        $data->subcategorycourse = $this->getDataGenerator()->create_course(['category' => $data->subcategory->id]);

        $this->getDataGenerator()->enrol_user($data->user->id, $data->course->id);
        $this->getDataGenerator()->enrol_user($data->user->id, $data->subcategorycourse->id);

        $categorycontext = context_coursecat::instance($data->category->id);
        list($allowedroles) = get_roles_with_cap_in_context(
            $categorycontext ,
            'moodle/notes:view'
        );
        foreach ($allowedroles as $roleid => $allowed) {
            if ($allowed) {
                role_assign($roleid, $data->user->id, $categorycontext );
            }
        }

        $data->usertarget = new target_user($data->user);

        /** @var core_notes_generator $notegenerator */
        $notegenerator = $this->getDataGenerator()->get_plugin_generator('core_notes');

        $data->notes[] = $notegenerator->create_instance([
            'userid' => $data->user->id,
            'courseid' => $data->course->id,
            'publishstate' => NOTES_STATE_PUBLIC
        ]);
        $data->notes[] = $notegenerator->create_instance([
            'userid' => $data->user->id,
            'courseid' => $data->subcategorycourse->id,
            'publishstate' => NOTES_STATE_PUBLIC
        ]);
        $data->notes[] = $notegenerator->create_instance([
            'userid' => $data->user->id,
            'courseid' => $data->course->id,
            'publishstate' => NOTES_STATE_SITE
        ]);

        return $data;
    }

    /**
     * Tests that the count only includes notes in the category when using category context.
     */
    public function test_count_works_on_category_context() {
        global $CFG;
        require_once($CFG->dirroot . '/notes/lib.php');

        $this->resetAfterTest();
        $data = $this->get_category_data();
        $categorycontext = context_coursecat::instance($data->category->id);
        $subcategorycontext = context_coursecat::instance($data->subcategory->id);

        $publicnotes = array_filter($data->notes, function($note) {
            return $note->publishstate == NOTES_STATE_PUBLIC;
        });

        $topcategorycount = notes_purge::execute_count($data->usertarget, $categorycontext);
        $this->assertEquals(count($publicnotes), $topcategorycount);

        $subcategorynotes = array_filter($publicnotes, function($note) use ($data) {
            return $note->courseid == $data->subcategorycourse->id;
        });

        $subcategorycount = notes_purge::execute_count($data->usertarget, $subcategorycontext);
        $this->assertEquals(count($subcategorynotes), $subcategorycount);
    }

    /**
     * Tests that the export only contains notes in the category when using category context.
     */
    public function test_purge_works_on_category_context() {
        global $CFG;
        require_once($CFG->dirroot . '/notes/lib.php');

        $this->resetAfterTest();
        $data = $this->get_category_data();
        $categorycontext = context_coursecat::instance($data->category->id);
        $subcategorycontext = context_coursecat::instance($data->subcategory->id);

        $subcategorynotes = array_filter($data->notes, function($note) use ($data) {
            return $note->publishstate == NOTES_STATE_PUBLIC
                && $note->courseid == $data->subcategorycourse->id;
        });
        $sitenotes = array_filter($data->notes, function($note) {
            return $note->publishstate == NOTES_STATE_SITE;
        });

        $countbefore = notes_purge::execute_count($data->usertarget, $categorycontext);

        $result = notes_purge::execute_purge($data->usertarget, $subcategorycontext);
        $this->assertEquals(notes_purge::RESULT_STATUS_SUCCESS, $result);

        // Only the subcategory notes should have being removed.
        $subcategorycount = notes_purge::execute_count($data->usertarget, $subcategorycontext);
        $this->assertEquals(0, $subcategorycount);

        $topcategorycount = notes_purge::execute_count($data->usertarget, $categorycontext);
        $this->assertEquals($countbefore - count($subcategorynotes), $topcategorycount);

        $result = notes_purge::execute_purge($data->usertarget, $categorycontext);
        $this->assertEquals(notes_purge::RESULT_STATUS_SUCCESS, $result);

        // All category events should have being removed.
        $topcategorycount = notes_purge::execute_count($data->usertarget, $categorycontext);
        $this->assertEquals(0, $topcategorycount);

        // The site notes should still remain.
        $systemcount = notes_purge::execute_count($data->usertarget, context_system::instance());
        $this->assertEquals(count($sitenotes), $systemcount);
    }
}