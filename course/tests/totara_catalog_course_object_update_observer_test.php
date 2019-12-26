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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Samantha Jayasinghe <samantha.jayasinghe@totaralearning.com>
 * @package core_course
 * @category totara_catalog
 */

namespace core_course\totara_catalog\course;

use core\event\course_deleted;
use core_course\totara_catalog\course as course_provider;
use core\event\course_created;
use \totara_catalog\task\provider_active_task;

/**
 * @group totara_catalog
 */
class core_course_totara_catalog_course_object_update_observer_testcase extends \advanced_testcase {

    /**
     * @var \stdClass
     */
    private $course = null;

    private $provider_active_task = null;

    protected function setUp() {
        parent::setup();
        $this->setAdminUser();
        $this->resetAfterTest();
        $this->course = $this->getDataGenerator()->create_course();
        $this->provider_active_task = new provider_active_task();
    }

    protected function tearDown() {
        $this->course = null;
        $this->provider_active_task = null;
        parent::tearDown();
    }

    public function test_get_observer_events() {
        foreach ($this->get_update_observers() as $observer) {
            $applicable_events = $observer->get_observer_events();
            $this->assertNotEmpty($applicable_events);
        }
    }

    public function test_update_object() {
        global $DB;

        // Delete the course from the catalog if it is there already.
        $DB->delete_records('catalog', ['objectid' => $this->course->id, 'objecttype' => 'course']);

        // Create course created event (without actually creating the course - it was created earlier).
        $event = course_created::create(
            [
                'objectid' => $this->course->id,
                'context'  => \context_course::instance($this->course->id),
                'other'    => [
                    'shortname' => $this->course->shortname,
                    'fullname'  => $this->course->fullname,
                ],
            ]
        );

        // Get the observer.
        $observer = new course_provider\observer\course('course', $event);

        // Process the observer. This should cause init_change_objects to be called, which in turn marks the course
        // for creation in the catalog, then the creations are processed.
        $observer->process();

        // Check that the record has been created in the catalog.
        $this->assertEquals(
            1,
            $DB->count_records('catalog', ['objectid' => $this->course->id, 'objecttype' => 'course'])
        );
    }

    public function test_delete_object() {
        global $DB;

        // Check that the course is in the catalog.
        $this->assertEquals(
            1,
            $DB->count_records('catalog', ['objectid' => $this->course->id, 'objecttype' => 'course'])
        );

        // Create course deleted event (without actually deleting the course).
        $event = course_deleted::create(
            [
                'objectid' => $this->course->id,
                'context'  => \context_course::instance($this->course->id),
                'other'    => [
                    'shortname' => $this->course->shortname,
                    'fullname'  => $this->course->fullname,
                ],
            ]
        );

        // Get the observer.
        $observer = new course_provider\observer\course_delete('course', $event);

        // Process the observer. This should cause init_change_objects to be called, which in turn marks the course
        // for deletion from the catalog, then the deletions are processed.
        $observer->process();

        // Check that the record has been deleted from the catalog.
        $this->assertEquals(
            0,
            $DB->count_records('catalog', ['objectid' => $this->course->id, 'objecttype' => 'course'])
        );
    }

    public function test_process() {
        global $DB;
        $this->get_update_observers()['course']->process();

        $count = $DB->count_records('catalog', ['objecttype' => course_provider::get_object_type()]);
        $this->assertSame(1, $count);
    }

    private function get_update_observers() {
        $observers = [];

        // Get observer classes.
        $classes = \core_component::get_namespace_classes(
            'totara_catalog\course\observer',
            'totara_catalog\observer\object_update_observer'
        );

        // Create course created event.
        $event = course_created::create(
            [
                'objectid' => $this->course->id,
                'context'  => \context_course::instance($this->course->id),
                'other'    => [
                    'shortname' => $this->course->shortname,
                    'fullname'  => $this->course->fullname,
                ],
            ]
        );

        foreach ($classes as $class) {
            $observer = new $class(
                course_provider::get_object_type(),
                $event
            );
            $shortclassname = str_replace('core_course\\totara_catalog\\course\\observer\\', '', get_class($observer));
            $observers[$shortclassname] = $observer;
        }

        return $observers;
    }

    /**
     * Ensure that a section update on SITEID course does not trigger catalog update event.
     *
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_update_course_section() {
        global $DB;

        // Check count after activation of course provider.
        $this->provider_active_task->set_custom_data(['objecttype' => 'course_section']);
        $this->provider_active_task->execute();

        // With no other courses present we should have empty catalog table.
        $catalog_recs = $DB->count_records('catalog', ['objectid' => SITEID, 'objecttype' => 'course']);
        $this->assertEquals(0, $catalog_recs);

        // Create SITEID course_section and then update it.
        $course = $DB->get_record('course', array('id' => SITEID), '*', MUST_EXIST);
        $section = course_create_section($course, 0);
        $data = [
            'id' => $section->id,
            'summary' => '<p>Hello world</p>',
        ];
        course_update_section($course, $section, $data);

        // Re-check count after refresh of course provider.
        $this->provider_active_task->execute();
        $catalog_recs = $DB->count_records('catalog', ['objectid' => SITEID, 'objecttype' => 'course']);
        $this->assertEquals(0, $catalog_recs);
    }
}
