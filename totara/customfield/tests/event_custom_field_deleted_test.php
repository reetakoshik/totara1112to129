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
 * @package totara_customfield
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/totara/customfield/lib.php');

class totara_customfield_event_custom_field_deleted_testcase extends advanced_testcase {

    public function setUp() {
        $this->setAdminUser();
        $this->resetAfterTest();
    }

    /**
     * Tests the event details.
     */
    public function test_event() {
        // Create course customfields.
        $cfgenerator = $this->getDataGenerator()->get_plugin_generator('totara_customfield');
        $textids = $cfgenerator->create_text('course', array('text1'));

        // Create course 1.
        $course = $this->getDataGenerator()->create_course(array('fullname' => 'Course 1'));
        // Add custom fields data to course
        $cfgenerator->set_text($course, $textids['text1'], 'value1', 'course', 'course');

        $prefix = 'course';
        $extra = array('prefix' => $prefix, 'id' => $textids['text1'], 'action' => 'deletefield');
        $customfieldtype = get_customfield_type_instace($prefix, context_system::instance(), $extra);

        // Delete custom field
        $sink = $this->redirectEvents();
        $customfieldtype->delete($textids['text1']);

        $events = $sink->get_events();
        $event = reset($events);
        $eventdata = $event->get_data();

        $this->assertInstanceOf('\\totara_customfield\\event\\customfield_data_deleted', $event);
        $this->assertArrayHasKey('field_data', $eventdata['other']);
        $this->assertSame($course->id, $eventdata['other']['field_data']['courseid']);
        $this->assertSame($textids['text1'], $eventdata['other']['field_data']['fieldid']);
    }
}
