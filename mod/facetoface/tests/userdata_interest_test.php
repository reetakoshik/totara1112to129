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
 * @author Matthias Bonk <matthias.bonk@totaralearning.com>
 * @package mod_facetoface
 */

namespace mod_facetoface\userdata;

use advanced_testcase;
use context_course;
use context_coursecat;
use context_module;
use context_system;
use mod_facetoface\seminar;
use totara_userdata\userdata\item;
use totara_userdata\userdata\target_user;

defined('MOODLE_INTERNAL') || die();

/**
 * Test interest item.
 *
 * @group totara_userdata
 */
class mod_facetoface_userdata_interest_testcase extends advanced_testcase {

    protected $user1;
    protected $user2;
    protected $course1;
    protected $course2;
    protected $facetoface1;
    protected $facetoface2;
    protected $facetoface3;

    /**
     * Set up tests.
     */
    public function setUp() {
        parent::setUp();

        $this->resetAfterTest();

        $this->user1 = $this->getDataGenerator()->create_user();
        $this->user2 = $this->getDataGenerator()->create_user();
        $coursecat1 = $this->getDataGenerator()->create_category();
        $coursecat2 = $this->getDataGenerator()->create_category();
        $this->course1 = $this->getDataGenerator()->create_course(['category' => $coursecat1->id]);
        $this->course2 = $this->getDataGenerator()->create_course(['category' => $coursecat2->id]);
        $this->facetoface1 = $this->getDataGenerator()->create_module('facetoface', array('course' => $this->course1->id));
        $this->facetoface2 = $this->getDataGenerator()->create_module('facetoface', array('course' => $this->course2->id));
        $this->facetoface3 = $this->getDataGenerator()->create_module('facetoface', array('course' => $this->course2->id));

        \mod_facetoface\interest::from_seminar(new seminar($this->facetoface1->id), $this->user1->id)->set_reason('reason user 1 for f2f 1')->declare();
        \mod_facetoface\interest::from_seminar(new seminar($this->facetoface2->id), $this->user1->id)->set_reason('reason user 1 for f2f 2')->declare();
        \mod_facetoface\interest::from_seminar(new seminar($this->facetoface1->id), $this->user2->id)->set_reason('reason user 2 for f2f 1')->declare();
        \mod_facetoface\interest::from_seminar(new seminar($this->facetoface2->id), $this->user2->id)->set_reason('reason user 2 for f2f 2')->declare();
        \mod_facetoface\interest::from_seminar(new seminar($this->facetoface3->id), $this->user2->id)->set_reason('reason user 2 for f2f 3')->declare();
    }

    /**
     * Unset properties to avoid PHPUnit memory problems.
     */
    protected function tearDown() {
        $this->user1 = $this->user2 = null;
        $this->course1 = $this->course2 = null;
        $this->facetoface1 = $this->facetoface2 = $this->facetoface3 = null;

        parent::tearDown();
    }

    /**
     * Test count.
     */
    public function test_count() {
        $targetuser1 = new target_user($this->user1);
        $targetuser2 = new target_user($this->user2);

        // System context
        $this->assertEquals(2, interest::execute_count($targetuser1, context_system::instance()));
        $this->assertEquals(3, interest::execute_count($targetuser2, context_system::instance()));

        // Module context
        $coursemodule = get_coursemodule_from_instance('facetoface', $this->facetoface1->id);
        $modulecontext = context_module::instance($coursemodule->id);
        $this->assertEquals(1, interest::execute_count($targetuser1, $modulecontext));
        $this->assertEquals(1, interest::execute_count($targetuser2, $modulecontext));

        $coursemodule = get_coursemodule_from_instance('facetoface', $this->facetoface2->id);
        $modulecontext = context_module::instance($coursemodule->id);
        $this->assertEquals(1, interest::execute_count($targetuser1, $modulecontext));
        $this->assertEquals(1, interest::execute_count($targetuser2, $modulecontext));

        $coursemodule = get_coursemodule_from_instance('facetoface', $this->facetoface3->id);
        $modulecontext = context_module::instance($coursemodule->id);
        $this->assertEquals(0, interest::execute_count($targetuser1, $modulecontext));
        $this->assertEquals(1, interest::execute_count($targetuser2, $modulecontext));

        // Course context
        $coursemodule = get_coursemodule_from_instance('facetoface', $this->facetoface1->id);
        $coursecontext = context_course::instance($coursemodule->course);
        $this->assertEquals(1, interest::execute_count($targetuser1, $coursecontext));
        $this->assertEquals(1, interest::execute_count($targetuser2, $coursecontext));

        $coursemodule = get_coursemodule_from_instance('facetoface', $this->facetoface2->id);
        $coursecontext = context_course::instance($coursemodule->course);
        $this->assertEquals(1, interest::execute_count($targetuser1, $coursecontext));
        $this->assertEquals(2, interest::execute_count($targetuser2, $coursecontext));

        // Course category context
        $coursemodule = get_coursemodule_from_instance('facetoface', $this->facetoface1->id);
        $course = get_course($coursemodule->course);
        $coursecatcontext = context_coursecat::instance($course->category);
        $this->assertEquals(1, interest::execute_count($targetuser1, $coursecatcontext));
        $this->assertEquals(1, interest::execute_count($targetuser2, $coursecatcontext));

        $coursemodule = get_coursemodule_from_instance('facetoface', $this->facetoface2->id);
        $course = get_course($coursemodule->course);
        $coursecatcontext = context_coursecat::instance($course->category);
        $this->assertEquals(1, interest::execute_count($targetuser1, $coursecatcontext));
        $this->assertEquals(2, interest::execute_count($targetuser2, $coursecatcontext));
    }

    /**
     * Test export.
     */
    public function test_export() {
        $targetuser1 = new target_user($this->user1);
        $targetuser2 = new target_user($this->user2);

        // System context.
        $export = interest::execute_export($targetuser1, context_system::instance());
        $data = $export->data;
        $this->assertCount(2, $data);

        // Sequence of records is predictable because of sorting by id.
        $record = array_shift($data);
        $this->assertEquals($this->facetoface1->id, $record->facetoface);
        $this->assertEquals($targetuser1->id, $record->userid);
        $this->assertNotEmpty($record->timedeclared);
        $this->assertEquals('reason user 1 for f2f 1', $record->reason);

        $record = array_shift($data);
        $this->assertEquals($this->facetoface2->id, $record->facetoface);
        $this->assertEquals($targetuser1->id, $record->userid);
        $this->assertNotEmpty($record->timedeclared);
        $this->assertEquals('reason user 1 for f2f 2', $record->reason);

        // Module context
        $coursemodule = get_coursemodule_from_instance('facetoface', $this->facetoface1->id);
        $modulecontext = context_module::instance($coursemodule->id);
        $export = interest::execute_export($targetuser1, $modulecontext);
        $data = $export->data;
        $this->assertCount(1, $data);
        $record = array_shift($data);
        $this->assertEquals($this->facetoface1->id, $record->facetoface);

        // Course context
        $coursemodule = get_coursemodule_from_instance('facetoface', $this->facetoface2->id);
        $coursecontext = context_course::instance($coursemodule->course);
        $export = interest::execute_export($targetuser2, $coursecontext);
        $data = $export->data;
        $this->assertCount(2, $data);
        $record = array_shift($data);
        $this->assertEquals($this->facetoface2->id, $record->facetoface);
        $record = array_shift($data);
        $this->assertEquals($this->facetoface3->id, $record->facetoface);

        // Course category context
        $coursemodule = get_coursemodule_from_instance('facetoface', $this->facetoface2->id);
        $course = get_course($coursemodule->course);
        $coursecatcontext = context_coursecat::instance($course->category);
        $export = interest::execute_export($targetuser2, $coursecatcontext);
        $data = $export->data;
        $this->assertCount(2, $data);
        $record = array_shift($data);
        $this->assertEquals($this->facetoface2->id, $record->facetoface);
        $record = array_shift($data);
        $this->assertEquals($this->facetoface3->id, $record->facetoface);
    }

    /**
     * Test purge in system context.
     */
    public function test_purge_system_context() {
        global $DB;

        $this->assertCount(2, $DB->get_records('facetoface_interest', ['userid' => $this->user1->id]));
        $this->assertCount(3, $DB->get_records('facetoface_interest', ['userid' => $this->user2->id]));

        $targetuser1 = new target_user($this->user1);
        $status = interest::execute_purge($targetuser1, context_system::instance());

        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $status);
        $this->assertCount(0, $DB->get_records('facetoface_interest', ['userid' => $this->user1->id]));
        $this->assertCount(3, $DB->get_records('facetoface_interest', ['userid' => $this->user2->id]));

        $targetuser2 = new target_user($this->user2);
        $status = interest::execute_purge($targetuser2, context_system::instance());

        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $status);
        $this->assertCount(0, $DB->get_records('facetoface_interest', ['userid' => $this->user1->id]));
        $this->assertCount(0, $DB->get_records('facetoface_interest', ['userid' => $this->user2->id]));
    }

    /**
     * Data provider for module context tests.
     *
     * @return array
     */
    public function purge_module_context_data_provider() {
        return [
            [ 'user1', 'facetoface1', [0, 1], [1, 1, 1] ],
            [ 'user1', 'facetoface2', [1, 0], [1, 1, 1] ],
            [ 'user2', 'facetoface1', [1, 1], [0, 1, 1] ],
            [ 'user2', 'facetoface2', [1, 1], [1, 0, 1] ],
            [ 'user2', 'facetoface3', [1, 1], [1, 1, 0] ],
        ];
    }

    /**
     * Test purge in module context.
     *
     * @param string $purgeuser
     * @param string $facetoface
     * @param array $expecteduser1
     * @param array $expecteduser2
     * @dataProvider purge_module_context_data_provider
     */
    public function test_purge_module_context(string $purgeuser, string $facetoface, array $expecteduser1, array $expecteduser2) {
        $user = $this->{$purgeuser};
        $targetuser = new target_user($user);

        $facetoface = $this->{$facetoface};
        $coursemodule = get_coursemodule_from_instance('facetoface', $facetoface->id);
        $modulecontext = context_module::instance($coursemodule->id);

        $status = interest::execute_purge($targetuser, $modulecontext);
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $status);
        $this->assert_interest_counts($expecteduser1, $expecteduser2);
    }

    /**
     * Data provider for course and course category context tests.
     *
     * @return array
     */
    public function purge_course_context_data_provider() {
        return [
            [ 'user1', 'course1', [0, 1], [1, 1, 1] ],
            [ 'user1', 'course2', [1, 0], [1, 1, 1] ],
            [ 'user2', 'course1', [1, 1], [0, 1, 1] ],
            [ 'user2', 'course2', [1, 1], [1, 0, 0] ],
        ];
    }

    /**
     * Test purge in course context.
     *
     * @param string $purgeuser
     * @param string $courseforcontext
     * @param array $expecteduser1
     * @param array $expecteduser2
     * @dataProvider purge_course_context_data_provider
     */
    public function test_purge_course_context(string $purgeuser, string $courseforcontext, array $expecteduser1, array $expecteduser2) {
        $user = $this->{$purgeuser};
        $targetuser = new target_user($user);

        $course = $this->{$courseforcontext};
        $coursecontext = context_course::instance($course->id);

        $status = interest::execute_purge($targetuser, $coursecontext);
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $status);
        $this->assert_interest_counts($expecteduser1, $expecteduser2);
    }

    /**
     * Test purge in course category context.
     * Uses the same data provider as course context test because we expect the same results.
     *
     * @param string $purgeuser
     * @param string $courseforcontext
     * @param array $expecteduser1
     * @param array $expecteduser2
     * @dataProvider purge_course_context_data_provider
     */
    public function test_purge_category_context(string $purgeuser, string $courseforcontext, array $expecteduser1, array $expecteduser2) {
        $user = $this->{$purgeuser};
        $targetuser = new target_user($user);

        $course = $this->{$courseforcontext};
        $coursecatcontext = context_coursecat::instance($course->category);

        $status = interest::execute_purge($targetuser, $coursecatcontext);
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $status);
        $this->assert_interest_counts($expecteduser1, $expecteduser2);
    }

    /**
     * Assert counts of interest records according to given arrays.
     *
     * @param array $expecteduser1
     * @param array $expecteduser2
     */
    private function assert_interest_counts(array $expecteduser1, array $expecteduser2) {
        global $DB;

        $this->assertCount($expecteduser1[0], $DB->get_records('facetoface_interest', ['userid' => $this->user1->id, 'facetoface' => $this->facetoface1->id]));
        $this->assertCount($expecteduser1[1], $DB->get_records('facetoface_interest', ['userid' => $this->user1->id, 'facetoface' => $this->facetoface2->id]));
        $this->assertCount($expecteduser2[0], $DB->get_records('facetoface_interest', ['userid' => $this->user2->id, 'facetoface' => $this->facetoface1->id]));
        $this->assertCount($expecteduser2[1], $DB->get_records('facetoface_interest', ['userid' => $this->user2->id, 'facetoface' => $this->facetoface2->id]));
        $this->assertCount($expecteduser2[2], $DB->get_records('facetoface_interest', ['userid' => $this->user2->id, 'facetoface' => $this->facetoface3->id]));
    }
}
