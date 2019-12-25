<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2017 onwards Totara Learning Solutions LTD
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
 * @author Alastair Munro <alastair.munro@totaralms.com>
 * @package totara_customfield
 */

defined('MOODLE_INTERNAL') || die();

class customfield_events_testcase extends advanced_testcase {

    protected function tearDown() {

        parent::tearDown();
    }

    public function setUp() {
        global $DB;
        $this->resetAfterTest();

    }

    public function test_customfield_updated_event() {
        $eventdata = new stdClass();
        $eventdata->objectid = 1;
        $eventdata->oldshortname = 'oldshort';
        $eventdata->shortname = 'newshortname';

        $event = \totara_customfield\event\profilefield_updated::create_from_field($eventdata);
        $event->trigger();

        $this->assertSame($eventdata->oldshortname, $event->get_info()->oldshortname);
        $this->assertSame($eventdata->shortname, $event->get_info()->shortname);
        $this->assertSame('u', $event->crud);
        $this->assertEventContextNotUsed($event);
    }
}
