<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @author Oleg Demeshev <oleg.demeshev@totaralearning.com>
 * @package tool_task
 */

defined('MOODLE_INTERNAL') || die();

class tool_task_events_testcase extends advanced_testcase {

    public function setUp() {
        $this->resetAfterTest();
        $this->setAdminUser();
    }

    public function test_scheduled_task_updated() {

        $task = \core\task\manager::get_scheduled_task('assignfeedback_editpdf\task\convert_submissions');

        $event = \tool_task\event\scheduled_task_updated::create_from_schedule($task);
        $event->trigger();

        $this->assertSame('u', $event->crud);
        $this->assertSame($event->edulevel, $event::LEVEL_OTHER);
        $this->assertEventContextNotUsed($event);
    }
}