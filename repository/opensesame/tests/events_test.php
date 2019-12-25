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
 * @package repository_opensesame
 */

defined('MOODLE_INTERNAL') || die();

class repository_opensesame_events_testcase extends advanced_testcase {
    public function test_events() {
        global $DB;

        $this->resetAfterTest();

        $pkg = array('zipfilename' => 'abc.zip', 'title' => 'abc',
            'expirationdate' => '',  'mobilecompatibility' => '', 'externalid' => 'xyz', 'description' => 'efg', 'duration' => '',
            'timecreated' => 1416859984, 'timemodified' => 1416859984, 'visible' => 1);
        $id = $DB->insert_record('repository_opensesame_pkgs', $pkg);
        $pkg = $DB->get_record('repository_opensesame_pkgs', array('id' => $id));

        $event = \repository_opensesame\event\catalogue_accessed::create();
        $event->trigger();
        $this->assertEventContextNotUsed($event);
        $this->assertNotEmpty($event->get_name());
        $this->assertNotEmpty($event->get_url());
        $this->assertSame($event::LEVEL_OTHER, $event->edulevel);
        $this->assertSame('r', $event->crud);
        $this->assertSame(CONTEXT_SYSTEM, $event->contextlevel);
        $this->assertNull($event->objecttable);

        $event = \repository_opensesame\event\package_fetched::create_from_package($pkg);
        $event->trigger();
        $this->assertEventContextNotUsed($event);
        $this->assertNotEmpty($event->get_name());
        $this->assertNotEmpty($event->get_url());
        $this->assertSame($event::LEVEL_OTHER, $event->edulevel);
        $this->assertSame('c', $event->crud);
        $this->assertSame(CONTEXT_SYSTEM, $event->contextlevel);
        $this->assertSame('repository_opensesame_pkgs', $event->objecttable);
        $this->assertSame($pkg->externalid, $event->other['externalid']);

        $pkg->visible = 0;
        $event = \repository_opensesame\event\package_hid::create_from_package($pkg);
        $event->trigger();
        $this->assertEventContextNotUsed($event);
        $this->assertNotEmpty($event->get_name());
        $this->assertNotEmpty($event->get_url());
        $this->assertSame($event::LEVEL_OTHER, $event->edulevel);
        $this->assertSame('u', $event->crud);
        $this->assertSame(CONTEXT_SYSTEM, $event->contextlevel);
        $this->assertSame('repository_opensesame_pkgs', $event->objecttable);
        $this->assertSame($pkg->externalid, $event->other['externalid']);

        $pkg->visible = 1;
        $event = \repository_opensesame\event\package_unhid::create_from_package($pkg);
        $event->trigger();
        $this->assertEventContextNotUsed($event);
        $this->assertNotEmpty($event->get_name());
        $this->assertNotEmpty($event->get_url());
        $this->assertSame($event::LEVEL_OTHER, $event->edulevel);
        $this->assertSame('u', $event->crud);
        $this->assertSame(CONTEXT_SYSTEM, $event->contextlevel);
        $this->assertSame('repository_opensesame_pkgs', $event->objecttable);
        $this->assertSame($pkg->externalid, $event->other['externalid']);

        $event = \repository_opensesame\event\tenant_registered::create_from_tenantid('xxxx');
        $event->trigger();
        $this->assertEventContextNotUsed($event);
        $this->assertNotEmpty($event->get_name());
        $this->assertNotEmpty($event->get_url());
        $this->assertSame($event::LEVEL_OTHER, $event->edulevel);
        $this->assertSame('c', $event->crud);
        $this->assertSame(CONTEXT_SYSTEM, $event->contextlevel);
        $this->assertNull($event->objecttable);
        $this->assertSame('xxxx', $event->other['tenantid']);

        $event = \repository_opensesame\event\tenant_unregistered::create_from_tenantid('xxxx');
        $event->trigger();
        $this->assertEventContextNotUsed($event);
        $this->assertNotEmpty($event->get_name());
        $this->assertNotEmpty($event->get_url());
        $this->assertSame($event::LEVEL_OTHER, $event->edulevel);
        $this->assertSame('d', $event->crud);
        $this->assertSame(CONTEXT_SYSTEM, $event->contextlevel);
        $this->assertNull($event->objecttable);
        $this->assertSame('xxxx', $event->other['tenantid']);
    }
}
