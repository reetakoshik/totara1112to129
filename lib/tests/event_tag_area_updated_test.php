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
 * @package core_event
 */

defined('MOODLE_INTERNAL') || die();

use core_tag\output\tagareaenabled;
/**
 * @group core_event totara_catalog
 */
class event_tag_area_updated_test extends advanced_testcase {

    public function setUp() {
        $this->setAdminUser();
        $this->resetAfterTest();
    }


    public function test_event_when_tag_area_disabled() {
        global $DB;
        // Check event when tag area disabled
        $sink = $this->redirectEvents();
        $record = $DB->get_record('tag_area', ['itemtype' => 'course']);
        tagareaenabled::update($record->id, 0);
        $events = $sink->get_events();
        $event = reset($events);

        $eventdata = $event->get_data();
        $this->assertInstanceOf('core\\event\\tag_area_updated', $event);
        $this->assertSame('course', $eventdata['other']['itemtype']);
        $this->assertSame(0, $eventdata['other']['enabled']);
    }

    public function test_event_when_tag_area_enabled() {
        global $DB;
        // Check event when tag area disabled
        $sink = $this->redirectEvents();
        $record = $DB->get_record('tag_area', ['itemtype' => 'course']);
        tagareaenabled::update($record->id, 1);
        $events = $sink->get_events();
        $event = reset($events);

        $eventdata = $event->get_data();
        $this->assertInstanceOf('core\\event\\tag_area_updated', $event);
        $this->assertSame('course', $eventdata['other']['itemtype']);
        $this->assertSame(1, $eventdata['other']['enabled']);
    }
}
