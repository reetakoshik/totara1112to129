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
 * @author Valerii Kuznetsov <valerii.kuznetsov@totaralms.com>
 * @package tool_totara_sync
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/admin/tool/totara_sync/lib.php');

/**
 * @group tool_totara_sync
 */
class tool_totara_sync_events_testcase extends advanced_testcase {
    /**
     * Test set up.
     */
    public function setUp() {
        $this->resetAfterTest(true);
    }

    /**
     * Test sync completed event.
     */
    public function test_sync_completed () {
        $event = \tool_totara_sync\event\sync_completed::create();
        $event->trigger();

        $this->assertSame('u', $event->crud);
        $this->assertSame($event::LEVEL_OTHER, $event->edulevel);
        $this->assertSame(null, $event->other);

        $this->assertEventContextNotUsed($event);
    }
}
