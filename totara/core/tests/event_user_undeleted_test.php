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

class totara_core_event_user_undeleted_testcase extends advanced_testcase {
    public function test_event() {
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();

        $event = \totara_core\event\user_undeleted::create_from_user($user);
        $event->trigger();

        $this->assertSame('user', $event->objecttable);
        $this->assertSame($user->id, $event->objectid);
        $this->assertSame('u', $event->crud);
        $this->assertSame($event::LEVEL_OTHER, $event->edulevel);
        $this->assertSame(CONTEXT_USER, $event->contextlevel);
        $this->assertSame($user->id, $event->contextinstanceid);
        $this->assertSame($user->username, $event->other['username']);

        $this->assertEventContextNotUsed($event);
        $this->assertEventLegacyData($user, $event);
        $this->assertEventLegacyLogData(array(SITEID, 'user', 'undelete', "view.php?id=".$user->id, $user->firstname.' '.$user->lastname), $event);

        $data = array(
            'objectid' => $user->id,
            'context' => \context_user::instance($user->id),
            'other' => array(
            )
        );
        try {
            $event = \totara_core\event\user_undeleted::create($data);
            $this->fail('coding_exception expected when username missing');
        } catch (moodle_exception $ex) {
            $this->assertInstanceOf('coding_exception', $ex);
            $this->assertEquals('Coding error detected, it must be fixed by a programmer: username must be set in $other.', $ex->getMessage());
        }
    }

    public function test_undelete_user() {
        global $DB;

        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        $original = $DB->get_record('user', array('id' => $user->id));

        $oldcontext = context_user::instance($user->id);

        set_config('authdeleteusers', 'partial'); // The old totara delete that can be partially reverted.
        delete_user($user);

        $this->assertFalse(context_user::instance($user->id, IGNORE_MISSING));

        $deleted = $DB->get_record('user', array('id' => $user->id));

        $this->assertEquals(1, $deleted->deleted);
        $this->assertSame($original->username, $deleted->username);
        $this->assertSame($original->email, $deleted->email);
        $this->assertSame($original->idnumber, $deleted->idnumber);

        $sink = $this->redirectEvents();
        $result = undelete_user($deleted);
        $this->assertTrue($result);
        $events = $sink->get_events();
        $sink->clear();

        $this->assertEquals(0, $deleted->deleted);
        $original->timemodified = $deleted->timemodified = 1; // Get rid of expected diffs.
        $this->assertEquals($original, $deleted);

        $this->assertCount(1, $events);
        $event = $events[0];
        $this->assertInstanceOf('\totara_core\event\user_undeleted', $event);
        $this->assertSame($user->id, $event->objectid);

        $this->assertEquals($oldcontext, context_user::instance($user->id));

        $user = $this->getDataGenerator()->create_user();
        delete_user($user);
        $sink->clear();

        // Not deleted user - fail.
        $result = undelete_user($user);
        $this->assertFalse($result);
        $events = $sink->get_events();
        $this->assertCount(0, $events);

        // Moodle deleted user - fail.
        $DB->set_field('user', 'email', md5($user->email), array('id' => $user->id));
        $user = $DB->get_record('user', array('id' => $user->id));
        $result = undelete_user($user);
        $this->assertFalse($result);
        $events = $sink->get_events();
        $this->assertCount(0, $events);

        // User without email cannot be undeleted.
        $DB->set_field('user', 'email', '', array('id' => $user->id));
        $user = $DB->get_record('user', array('id' => $user->id));
        $result = undelete_user($user);
        $this->assertFalse($result);
        $events = $sink->get_events();
        $this->assertCount(0, $events);

        // User with invalid email cannot be undeleted.
        $DB->set_field('user', 'email', 'xxx@', array('id' => $user->id));
        $user = $DB->get_record('user', array('id' => $user->id));
        $result = undelete_user($user);
        $this->assertFalse($result);
        $events = $sink->get_events();
        $this->assertCount(0, $events);

        $sink->close();
    }
}
