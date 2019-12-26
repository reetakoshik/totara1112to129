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

global $CFG;
require_once($CFG->libdir.'/adminlib.php');

/**
 * @group core_event totara_catalog
 */
class event_admin_settings_changed_test extends advanced_testcase {

    public function setUp() {
        $this->setAdminUser();
        $this->resetAfterTest();
    }

    /**
     * Tests the event details.
     */
    public function test_event() {
        $sink = $this->redirectEvents();
        admin_write_settings(['s__usetags' => 1]);

        $events = $sink->get_events();
        $event = reset($events);
        $eventdata = $event->get_data();

        $this->assertInstanceOf('core\\event\\admin_settings_changed', $event);
        $this->assertArrayHasKey('olddata', $eventdata['other']);
    }
}
