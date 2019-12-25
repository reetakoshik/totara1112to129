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
 * @package report_completion
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Tests for RPL completion events.
 *
 * @author Petr Skoda <petr.skoda@totaralms.com>
 * @package report_completion
 */
class report_completion_rpl_testcase extends advanced_testcase {
    public function test_rpl_created() {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $forum = $this->getDataGenerator()->create_module('forum', array('course' => $course));
        $context = context_course::instance($course->id);

        // Course completion.

        $event = \report_completion\event\rpl_created::create_from_rpl($user->id, $course->id, null, 'course');

        $sink = $this->redirectEvents();
        $event->trigger();
        $events = $sink->get_events();
        /** @var \report_completion\event\rpl_created $event */
        $event = reset($events);

        $this->assertInstanceOf('\report_completion\event\rpl_created', $event);
        $this->assertEquals($context, $event->get_context());
        $this->assertSame('c', $event->crud);
        $this->assertSame($event::LEVEL_OTHER, $event->edulevel);
        $url = new moodle_url('/report/completion/index.php', array('course' => $course->id));
        $this->assertEquals($url, $event->get_url());
        $this->assertEventContextNotUsed($event);
        $this->assertSame(array('cmid' => null, 'type' => 'course'), $event->other);

        // Activity completion.

        $event = \report_completion\event\rpl_created::create_from_rpl($user->id, $course->id, $forum->cmid, '666');

        $sink = $this->redirectEvents();
        $event->trigger();
        $events = $sink->get_events();
        /** @var \report_completion\event\rpl_created $event */
        $event = reset($events);

        $this->assertInstanceOf('\report_completion\event\rpl_created', $event);
        $this->assertEquals($context, $event->get_context());
        $this->assertSame('c', $event->crud);
        $this->assertSame($event::LEVEL_OTHER, $event->edulevel);
        $url = new moodle_url('/report/completion/index.php', array('course' => $course->id));
        $this->assertEquals($url, $event->get_url());
        $this->assertEventContextNotUsed($event);
        $this->assertSame(array('cmid' => $forum->cmid, 'type' => '666'), $event->other);
    }

    public function test_rpl_deleted() {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $forum = $this->getDataGenerator()->create_module('forum', array('course' => $course));
        $context = context_course::instance($course->id);

        // Course un-completion.

        $event = \report_completion\event\rpl_deleted::create_from_rpl($user->id, $course->id, null, 'course');

        $sink = $this->redirectEvents();
        $event->trigger();
        $events = $sink->get_events();
        /** @var \report_completion\event\rpl_deleted $event */
        $event = reset($events);

        $this->assertInstanceOf('\report_completion\event\rpl_deleted', $event);
        $this->assertEquals($context, $event->get_context());
        $this->assertSame('d', $event->crud);
        $this->assertSame($event::LEVEL_OTHER, $event->edulevel);
        $url = new moodle_url('/report/completion/index.php', array('course' => $course->id));
        $this->assertEquals($url, $event->get_url());
        $this->assertEventContextNotUsed($event);
        $this->assertSame(array('cmid' => null, 'type' => 'course'), $event->other);

        // Activity un-completion.

        $event = \report_completion\event\rpl_deleted::create_from_rpl($user->id, $course->id, $forum->cmid, '666');

        $sink = $this->redirectEvents();
        $event->trigger();
        $events = $sink->get_events();
        /** @var \report_completion\event\rpl_deleted $event */
        $event = reset($events);

        $this->assertInstanceOf('\report_completion\event\rpl_deleted', $event);
        $this->assertEquals($context, $event->get_context());
        $this->assertSame('d', $event->crud);
        $this->assertSame($event::LEVEL_OTHER, $event->edulevel);
        $url = new moodle_url('/report/completion/index.php', array('course' => $course->id));
        $this->assertEquals($url, $event->get_url());
        $this->assertEventContextNotUsed($event);
        $this->assertSame(array('cmid' => $forum->cmid, 'type' => '666'), $event->other);
    }
}
