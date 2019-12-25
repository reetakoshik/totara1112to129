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

use core_notes\userdata\notes_export_visible;
use totara_userdata\userdata\target_user;

/**
 * Tests that the core_notes dataitem purges and counts correctly
 *
 * @group totara_userdata
 */
class core_notes_userdata_notes_export_visible_testcase extends advanced_testcase {

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

        // The user doesnt initially have the capabilty to see the notes.
        $currentcount = notes_export_visible::execute_count($data->activeuser, $systemcontext);
        $this->assertEquals(0, $currentcount);

        // Give the user the capability to see notes.
        list($allowedroles) = get_roles_with_cap_in_context($systemcontext, 'moodle/notes:view');
        foreach ($allowedroles as $roleid => $allowed) {
            if ($allowed) {
                role_assign($roleid, $data->activeuser->id, $systemcontext);
            }
        }

        $startingcount = notes_export_visible::execute_count($data->activeuser, $systemcontext);

        // Adding a note to a different course increased the count by 1.
        $notegenerator->create_instance(['userid' => $data->activeuser->id, 'courseid' => $course->id]);
        $currentcount = notes_export_visible::execute_count($data->activeuser, $systemcontext);
        $this->assertEquals($startingcount + 1, $currentcount);

        // Adding a note to a different users does not change the count.
        $notegenerator->create_instance(['userid' => $data->deleteduser->id, 'courseid' => $course->id]);
        $currentcount = notes_export_visible::execute_count($data->activeuser, $systemcontext);
        $this->assertEquals($startingcount + 1, $currentcount);

        // Personal notes belonging to the user are counted.
        $activeuser = $DB->get_record('user', ['id' => $data->activeuser->id]);
        $this->setUser($activeuser);
        $notegenerator->create_instance([
            'userid' => $data->activeuser->id,
            'courseid' => $course->id,
            'publishstate' => NOTES_STATE_DRAFT
        ]);
        $this->setAdminUser();
        $currentcount = notes_export_visible::execute_count($data->activeuser, $systemcontext);
        $this->assertEquals($startingcount + 2, $currentcount);

        // Personal notes not belonging to the user are not counted.
        $deleteduser = $DB->get_record('user', ['id' => $data->deleteduser->id]);
        $this->setUser($deleteduser );
        $notegenerator->create_instance([
            'userid' => $data->activeuser->id,
            'courseid' => $course->id,
            'publishstate' => NOTES_STATE_DRAFT
        ]);
        $this->setAdminUser();
        $currentcount = notes_export_visible::execute_count($data->activeuser, $systemcontext);
        $this->assertEquals($startingcount + 2, $currentcount);

        // Site notes are counted.
        $notegenerator->create_instance([
            'userid' => $data->activeuser->id,
            'courseid' => $course->id,
            'publishstate' => NOTES_STATE_PUBLIC
        ]);
        $currentcount = notes_export_visible::execute_count($data->activeuser, $systemcontext);
        $this->assertEquals($startingcount + 3, $currentcount);
    }

    /**
     * Tests that the count limits course context to only notes in the course.
     */
    public function test_count_course_context_only_includes_notes_in_course() {
        $this->resetAfterTest(true);
        $data = $this->get_data();
        $coursecontext = context_course::instance($data->courses[0]->id);

        $userid = $data->activeuser->id;
        $courseid = $data->courses[0]->id;

        list($allowedroles) = get_roles_with_cap_in_context($coursecontext, 'moodle/notes:view');
        foreach ($allowedroles as $roleid => $allowed) {
            if ($allowed) {
                role_assign($roleid, $data->activeuser->id, $coursecontext);
            }
        }

        $coursenotes = array_filter($data->notes, function($note) use ($userid, $courseid) {
            return $note->userid == $userid &&
                $note->courseid == $courseid &&
                $note->publishstate != NOTES_STATE_SITE;
        });

        $currentcount = notes_export_visible::execute_count($data->activeuser, $coursecontext);
        $this->assertEquals(count($coursenotes), $currentcount);

        $notegenerator = $this->getDataGenerator()->get_plugin_generator('core_notes');
        $notegenerator->create_instance([
            'userid' => $data->activeuser->id,
            'courseid' => $courseid,
            'publishstate' => NOTES_STATE_PUBLIC
        ]);

        $currentcount = notes_export_visible::execute_count($data->activeuser, $coursecontext);
        $this->assertEquals(count($coursenotes) + 1, $currentcount);
    }

    /**
     * Tests that count still count the notes when the user has being deleted.
     */
    public function test_count_works_on_deleted_users() {
        global $DB;
        $this->resetAfterTest(true);
        $data = $this->get_data();
        $systemcontext = context_system::instance();
        list($allowedroles) = get_roles_with_cap_in_context($systemcontext, 'moodle/notes:view');
        foreach ($allowedroles as $roleid => $allowed) {
            if ($allowed) {
                role_assign($roleid, $data->activeuser->id, $systemcontext);
            }
        }

        $currentcount = notes_export_visible::execute_count($data->activeuser, $systemcontext);
        $this->assertGreaterThan(0, $currentcount);

        $user = $DB->get_record('user', ['id' => $data->activeuser->id]);
        delete_user($user);
        $user = $DB->get_record('user', ['id' => $user->id]);
        $reloadeduser = new target_user($user);

        // Since capabilities have being deleted all notes are hidden.
        $currentcount = notes_export_visible::execute_count($reloadeduser, $systemcontext);
        $this->assertEquals(0, $currentcount);
    }

    /**
     * Checks that count returns the expected value.
     */
    public function test_export_contains_correct_values() {
        $this->resetAfterTest();
        $data = $this->get_data();
        $systemcontext = context_system::instance();
        list($roles) = get_roles_with_cap_in_context($systemcontext, 'moodle/notes:view');
        $allowedroles = array_filter($roles, function ($roleallowed) {
            return $roleallowed;
        });
        foreach ($allowedroles as $roleid => $allowed) {
            role_assign($roleid, $data->activeuser->id, $systemcontext);
        }

        $export = notes_export_visible::execute_export($data->activeuser, $systemcontext);
        sort($export->data);
        $notes = array_values(note_list($systemcontext->instanceid, $data->activeuser->id));
        sort($notes);
        $this->assertEquals($notes, $export->data);
    }

    /**
     * Tests that export does not include notes that are hidden to the user.
     */
    public function test_export_only_includes_visible() {
        $this->resetAfterTest();
        $data = $this->get_data();
        $systemcontext = context_system::instance();

        $export = notes_export_visible::execute_export($data->activeuser, $systemcontext);

        // Nothing to export when the user does not have the notes:view capability.
        $this->assertEmpty($export->data);

        list($allowedroles) = get_roles_with_cap_in_context($systemcontext, 'moodle/notes:view');
        foreach ($allowedroles as $roleid => $allowed) {
            if ($allowed) {
                role_assign($roleid, $data->activeuser->id, $systemcontext);
            }
        }

        // Now that they have the capability at system level they should be able to export all their notes.
        $export = notes_export_visible::execute_export($data->activeuser, $systemcontext);
        sort($export->data);
        $notes = array_values(note_list($systemcontext->instanceid, $data->activeuser->id));
        sort($notes);
        $this->assertEquals($notes, $export->data);
    }

    /**
     * Makes sure calling export doesnt create an error.
     */
    public function test_export_works_on_deleted_users() {
        $this->resetAfterTest();
        $data = $this->get_data();
        $systemcontext = context_system::instance();

        // Since the user lost their capabilities there are no visible notes.
        $export = notes_export_visible::execute_export($data->deleteduser, $systemcontext);
        $this->assertEmpty($export->data);
    }

    /**
     * Set up the data for testing categories.
     */
    private function get_category_data() {
        $data = new class() {
            /** @var stdClass */
            public $user;
            /** @var stdClass */
            public $category, $subcategory;
            /** @var stdClass */
            public $course, $subcategorycourse;
            /** @var target_user */
            public $usertarget;
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

        $topcategorycount = notes_export_visible::execute_count($data->usertarget, $categorycontext);
        $this->assertEquals(count($publicnotes), $topcategorycount);

        $subcategorynotes = array_filter($publicnotes, function($note) use ($data) {
            return $note->courseid == $data->subcategorycourse->id;
        });

        $subcategorycount = notes_export_visible::execute_count($data->usertarget, $subcategorycontext);
        $this->assertEquals(count($subcategorynotes), $subcategorycount);
    }

    /**
     * Tests that the export only contains notes in the category when using category context.
     */
    public function test_export_works_on_category_context() {
        global $CFG;
        require_once($CFG->dirroot . '/notes/lib.php');

        $this->resetAfterTest();
        $data = $this->get_category_data();
        $categorycontext = context_coursecat::instance($data->category->id);
        $subcategorycontext = context_coursecat::instance($data->subcategory->id);

        $publicnotes = array_filter($data->notes, function($note) {
            return $note->publishstate == NOTES_STATE_PUBLIC;
        });

        $export = notes_export_visible::execute_export($data->usertarget, $categorycontext);
        $this->assertEquals($publicnotes, $export->data);

        $subcategorynotes = array_filter($publicnotes, function($note) use ($data) {
            return $note->courseid == $data->subcategorycourse->id;
        });

        $export = notes_export_visible::execute_export($data->usertarget, $subcategorycontext);
        $this->assertEquals(array_values($subcategorynotes), $export->data);
    }
}