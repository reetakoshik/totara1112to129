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
 * @author David Curry <david.curry@totaralms.com>
 * @package totara_core
 * @category tests
 */

defined('MOODLE_INTERNAL') || die();

class totara_core_event_module_completion_reset_testcase extends advanced_testcase {

    public function test_event() {
        $this->resetAfterTest();

        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $quiz = $generator->create_module('quiz', array('course' => $course->id));

        // Put it all in a dummy $modinfo object.
        $modinfo = new \stdClass();
        $modinfo->course = $course->id;
        $modinfo->coursemodule = $quiz->cmid;
        $modinfo->modulename = 'quiz';
        $modinfo->instance = $quiz->id;

        $event = \totara_core\event\module_completion_reset::create_from_module($modinfo);
        $event->trigger();

        $this->assertSame('course_modules', $event->objecttable);
        $this->assertSame($quiz->cmid, $event->objectid);
        $this->assertSame('u', $event->crud);
        $this->assertSame($event::LEVEL_OTHER, $event->edulevel);
        $this->assertSame(CONTEXT_MODULE, $event->contextlevel);
        $this->assertSame("$quiz->cmid", $event->contextinstanceid);
        $this->assertSame('quiz', $event->other['module']);
        $this->assertSame($quiz->id, $event->other['instance']);
        $this->assertEquals(new moodle_url('/course/modedit.php', array('id' => $quiz->cmid)), $event->get_url());

        $this->assertEventContextNotUsed($event);

        $legacydata = array($course->id, 'quiz', 'Module completion reset', 'course/modedit.php?id=' . $quiz->cmid, "instance:{$quiz->id}", $quiz->cmid);
        $this->assertEventLegacyLogData($legacydata, $event);
    }
}
