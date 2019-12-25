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

class totara_customfield_event_custom_field_created_testcase extends advanced_testcase {

    public function setUp() {
        $this->setAdminUser();
        $this->resetAfterTest();
    }

    /**
     * Tests the customfield created events
     */
    public function test_custom_field_create_event() {

        $sink = $this->redirectEvents();

        // Create course customfields.
        $cfgenerator = $this->getDataGenerator()->get_plugin_generator('totara_customfield');
        $textids = $cfgenerator->create_text('course', array('text1'));

        $events = $sink->get_events();
        $event = reset($events);
        $eventdata = $event->get_data();

        $this->assertInstanceOf('\\totara_customfield\\event\\customfield_created', $event);
        $this->assertArrayHasKey('data', $eventdata['other']);
        $this->assertArrayHasKey('type', $eventdata['other']);
        $this->assertSame('course', $eventdata['other']['type']);
    }
}
