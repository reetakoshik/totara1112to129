<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2015 onwards Totara Learning Solutions LTD
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
 * @category tests
 */

defined('MOODLE_INTERNAL') || die();

class totara_core_event_reminder_updated_testcase extends advanced_testcase {
    public function test_event() {
        global $USER, $CFG;
        require_once("$CFG->dirroot/lib/reminderlib.php");

        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();

        $reminder = new reminder();
        $reminder->courseid = $course->id;
        $reminder->timemodified = $reminder->timecreated = time() - 10;
        $reminder->modifierid = $USER->id;
        $reminder->deleted = '0';
        $reminder->title = 'some title';
        $reminder->type = 'completion';
        $reminder->config = serialize(array());
        $reminderid = $reminder->insert();
        $this->assertGreaterThan(0, $reminderid);

        $reminder->timemodified = time();
        $reminder->title = 'other title';
        $reminder->update();

        $event = \totara_core\event\reminder_updated::create_from_reminder($reminder);
        $event->trigger();

        $this->assertSame('reminder', $event->objecttable);
        $this->assertSame($reminder->id, $event->objectid);
        $this->assertSame('u', $event->crud);
        $this->assertSame($event::LEVEL_OTHER, $event->edulevel);
        $this->assertSame(CONTEXT_COURSE, $event->contextlevel);
        $this->assertSame($course->id, $event->contextinstanceid);
        $this->assertSame(array('type' => $reminder->type), $event->other);
        $this->assertEquals(new moodle_url('/course/reminders.php', array('courseid' => $course->id, 'id' => $reminder->id)), $event->get_url());

        $this->assertEventContextNotUsed($event);
        $this->assertEventLegacyLogData(array($reminder->courseid, 'course', 'reminder updated',
            'reminders.php?courseid='.$reminder->courseid.'&id='.$reminder->id, $reminder->title), $event);
    }
}
