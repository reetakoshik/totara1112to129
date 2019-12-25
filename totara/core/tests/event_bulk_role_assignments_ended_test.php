<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2014 onwards Totara Learning Solutions LTD
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
 * @author Petr Skoda <petr.skoda@totaralms.com>
 * @package totara_core
 */

defined('MOODLE_INTERNAL') || die();

class totara_core_event_bulk_role_assignments_ended_testcase extends advanced_testcase {
    public function test_event() {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $context = context_course::instance($course->id);

        \totara_core\event\bulk_role_assignments_started::create_from_context($context)->trigger();

        $event = \totara_core\event\bulk_role_assignments_ended::create_from_context($context);
        $event->trigger();

        $this->assertNull($event->objecttable);
        $this->assertNull($event->objectid);
        $this->assertSame('u', $event->crud);
        $this->assertSame($event::LEVEL_OTHER, $event->edulevel);
        $this->assertSame(CONTEXT_COURSE, $event->contextlevel);
        $this->assertSame($course->id, $event->contextinstanceid);
        $this->assertSame(null, $event->other);

        $this->assertEventContextNotUsed($event);
    }
}
