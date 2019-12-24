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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package core_user
 */

use core_user\userdata\preferences;
use totara_userdata\userdata\export;
use totara_userdata\userdata\item;
use totara_userdata\userdata\target_user;

defined('MOODLE_INTERNAL') || die();

/**
 * Test purging, exporting and counting of users preferences
 *
 * @group totara_userdata
 */
class core_user_userdata_preferences_testcase extends advanced_testcase {

    /**
     * test compatible context levels
     */
    public function test_compatible_context_levels() {
        $expectedcontextlevels = [CONTEXT_SYSTEM];
        $this->assertEquals($expectedcontextlevels, preferences::get_compatible_context_levels());
    }

    /**
     * test if data is correctly purged
     */
    public function test_purge() {
        global $DB;

        $this->resetAfterTest(true);

        $controluser = $this->getDataGenerator()->create_user();
        $activeuser = $this->getDataGenerator()->create_user();
        $suspendeduser = $this->getDataGenerator()->create_user(['suspended' => 1]);
        $deleteduser = $this->getDataGenerator()->create_user(['deleted' => 1]);

        $controluser->lang = 'de';
        $controluser->calendartype = 'testcalendar';
        $controluser->theme = 'testtheme';
        $controluser->timezone = 'Europe/Berlin';
        $DB->update_record('user', $controluser);

        $activeuser->lang = 'es';
        $activeuser->calendartype = 'testcalendar';
        $activeuser->theme = 'testtheme';
        $activeuser->timezone = 'Europe/Madrid';
        $DB->update_record('user', $activeuser);

        $suspendeduser->lang = 'fr';
        $suspendeduser->theme = 'testtheme';
        $suspendeduser->timezone = 'Europe/Paris';
        $DB->update_record('user', $suspendeduser);

        $deleteduser->lang = 'fr';
        $deleteduser->timezone = 'Europe/Paris';
        $DB->update_record('user', $deleteduser);

        $controluser = new target_user($controluser);
        $activeuser = new target_user($activeuser);
        $suspendeduser = new target_user($suspendeduser);
        $deleteduser = new target_user($deleteduser);

        $this->assertTrue(preferences::is_purgeable($activeuser->status));
        $this->assertTrue(preferences::is_purgeable($suspendeduser->status));
        $this->assertTrue(preferences::is_purgeable($deleteduser->status));

        // We want to catch the events fired.
        $sink = $this->redirectEvents();

        // To test timemodified we need to wait a second.
        sleep(1);

        /****************************
         * PURGE activeuser
         ***************************/
        $result = preferences::execute_purge($activeuser, context_system::instance());
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $result);

        $activeuserreloaded = $DB->get_record('user', ['id' => $activeuser->id]);
        $this->assertEquals('', $activeuserreloaded->lang);
        $this->assertEquals('gregorian', $activeuserreloaded->calendartype);
        $this->assertEquals('', $activeuserreloaded->theme);
        $this->assertEquals('99', $activeuserreloaded->timezone);

        $this->assertGreaterThan($activeuser->timemodified, $activeuserreloaded->timemodified);

        $events = $sink->get_events();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(\core\event\user_updated::class, reset($events));
        $sink->clear();

        /****************************
         * PURGE suspendeduser
         ***************************/
        $result = preferences::execute_purge($suspendeduser, context_system::instance());
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $result);

        $suspendeduserreloaded = $DB->get_record('user', ['id' => $suspendeduser->id]);
        $this->assertEquals('', $suspendeduserreloaded->lang);
        $this->assertEquals($suspendeduser->calendartype, $suspendeduserreloaded->calendartype);
        $this->assertEquals('', $suspendeduserreloaded->theme);
        $this->assertEquals('99', $suspendeduserreloaded->timezone);

        $this->assertGreaterThan($suspendeduser->timemodified, $suspendeduserreloaded->timemodified);

        $events = $sink->get_events();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(\core\event\user_updated::class, reset($events));
        $sink->clear();

        /****************************
         * PURGE deleteduser
         ***************************/
        $result = preferences::execute_purge($deleteduser, context_system::instance());
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $result);

        $deleteduserreloaded = $DB->get_record('user', ['id' => $deleteduser->id]);
        $this->assertEquals('', $deleteduserreloaded->lang);
        $this->assertEquals($deleteduser->calendartype, $deleteduserreloaded->calendartype);
        $this->assertEquals($deleteduser->theme, $deleteduserreloaded->theme);
        $this->assertEquals('99', $deleteduserreloaded->timezone);

        $this->assertEquals($deleteduser->timemodified, $deleteduserreloaded->timemodified);

        $events = $sink->get_events();
        $this->assertCount(0, $events);

        /****************************
         * CHECK controluser
         ***************************/
        $controluserreloaded = $DB->get_record('user', ['id' => $controluser->id]);
        $this->assertEquals($controluser->lang, $controluserreloaded->lang);
        $this->assertEquals($controluser->calendartype, $controluserreloaded->calendartype);
        $this->assertEquals($controluser->theme, $controluserreloaded->theme);
        $this->assertEquals($controluser->timezone, $controluserreloaded->timezone);
        $this->assertEquals($controluser->timemodified, $controluserreloaded->timemodified);
    }

    /**
     * test if data is correctly counted
     */
    public function test_count() {
        global $DB;

        $this->resetAfterTest(true);

        $controluser = $this->getDataGenerator()->create_user();
        $activeuser = $this->getDataGenerator()->create_user();
        $suspendeduser = $this->getDataGenerator()->create_user(['suspended' => 1]);
        $deleteduser = $this->getDataGenerator()->create_user(['deleted' => 1]);

        $controluser->lang = 'de';
        $controluser->calendartype = 'testcalendar';
        $controluser->theme = 'testtheme';
        $controluser->timezone = 'Europe/Berlin';
        $DB->update_record('user', $controluser);

        $activeuser->lang = 'es';
        $activeuser->calendartype = 'testcalendar';
        $activeuser->theme = 'testtheme';
        $DB->update_record('user', $activeuser);

        $suspendeduser->lang = 'fr';
        $suspendeduser->calendartype = 'testcalendar';
        $DB->update_record('user', $suspendeduser);

        $deleteduser->lang = 'fr';
        $DB->update_record('user', $deleteduser);

        $controluser = new target_user($controluser);
        $activeuser = new target_user($activeuser);
        $suspendeduser = new target_user($suspendeduser);
        $deleteduser = new target_user($deleteduser);

        // Do the count.
        $result = preferences::execute_count(new target_user($controluser), context_system::instance());
        $this->assertEquals(4, $result);

        $result = preferences::execute_count(new target_user($activeuser), context_system::instance());
        $this->assertEquals(3, $result);

        $result = preferences::execute_count(new target_user($suspendeduser), context_system::instance());
        $this->assertEquals(2, $result);

        $result = preferences::execute_count(new target_user($deleteduser), context_system::instance());
        $this->assertEquals(1, $result);
    }


    /**
     * test if data is correctly counted
     */
    public function test_export() {
        global $DB;

        $this->resetAfterTest(true);

        $activeuser = $this->getDataGenerator()->create_user();
        $deleteduser = $this->getDataGenerator()->create_user(['deleted' => 1]);

        $activeuser->lang = 'de';
        $activeuser->calendartype = 'testcalendar';
        $activeuser->theme = 'testtheme';
        $activeuser->timezone = 'Europe/Berlin';
        $DB->update_record('user', $activeuser);

        $deleteduser->lang = 'es';
        $deleteduser->calendartype = 'testcalendar';
        $DB->update_record('user', $deleteduser);

        /****************************
         * EXPORT activeuser
         ***************************/

        $result = preferences::execute_export(new target_user($activeuser), context_system::instance());
        $this->assertInstanceOf(export::class, $result);
        $this->assertEquals(
            [
                'lang' => $activeuser->lang,
                'calendartype' => $activeuser->calendartype,
                'theme' => $activeuser->theme,
                'timezone' => $activeuser->timezone
            ],
            $result->data
        );

        /****************************
         * EXPORT deleteduser
         ***************************/

        $result = preferences::execute_export(new target_user($deleteduser), context_system::instance());
        $this->assertInstanceOf(export::class, $result);
        $this->assertEquals(
            [
                'lang' => $deleteduser->lang,
                'calendartype' => $deleteduser->calendartype,
                'theme' => '',
                'timezone' => ''
            ],
            $result->data
        );
    }

}