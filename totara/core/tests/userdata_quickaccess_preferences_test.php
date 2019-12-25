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
 * @author Alastair Munro <alastair.munro@totaralearning.com>
 * @package totara_core
 */

use totara_core\userdata\quickaccess_preferences;
use totara_userdata\userdata\export;
use totara_userdata\userdata\item;
use totara_userdata\userdata\target_user;

defined('MOODLE_INTERNAL') || die();

/**
 * Test purging, exporting and counting of users preferences
 *
 * @group totara_userdata
 */
class totara_core_userdata_quickaccess_preferences_testcase extends advanced_testcase {

    /**
     * test compatible context levels
     */
    public function test_compatible_context_levels() {
        $expectedcontextlevels = [CONTEXT_SYSTEM];
        $this->assertEquals($expectedcontextlevels, quickaccess_preferences::get_compatible_context_levels());
    }

    /**
     * Test function is_purgeable
     */
    public function test_is_purgeable() {
        $this->assertTrue(quickaccess_preferences::is_purgeable(target_user::STATUS_ACTIVE));
        $this->assertTrue(quickaccess_preferences::is_purgeable(target_user::STATUS_SUSPENDED));
        $this->assertTrue(quickaccess_preferences::is_purgeable(target_user::STATUS_DELETED));
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

        $menu_items_key = 'core_admin-quickaccessmenu-items';
        $menu_groups_key = 'core_admin-quickaccessmenu-groups';

        $control_data1 = array('userid' => $controluser->id, 'name' => $menu_items_key, 'value' => '[dummy-items-control]');
        $control_data2 = array('userid' => $controluser->id, 'name' => $menu_groups_key, 'value'=> '[dummy-groups-control]');
        $DB->insert_record('quickaccess_preferences', (object)$control_data1);
        $DB->insert_record('quickaccess_preferences', (object)$control_data2);

        $active_data1 = array('userid' => $activeuser->id, 'name' => $menu_items_key, 'value' => '[dummy-items-active]');
        $DB->insert_record('quickaccess_preferences', (object)$active_data1);

        $suspended_data1 = array('userid' => $suspendeduser->id, 'name' => $menu_items_key, 'value' => '[dummy-items-suspended]');
        $suspended_data2 = array('userid' => $suspendeduser->id, 'name' => $menu_groups_key, 'value'=> '[dummy-groups-suspended]');
        $DB->insert_record('quickaccess_preferences', (object)$suspended_data1);
        $DB->insert_record('quickaccess_preferences', (object)$suspended_data2);

        $deleted_data1 = array('userid' => $deleteduser->id, 'name' => $menu_items_key, 'value' => '[dummy-items-deleted]');
        $deleted_data2 = array('userid' => $deleteduser->id, 'name' => $menu_groups_key, 'value' => '[dummy-groups-deleted]');
        $DB->insert_record('quickaccess_preferences', (object)$deleted_data1);
        $DB->insert_record('quickaccess_preferences', (object)$deleted_data2);

        $controluser = new target_user($controluser);
        $activeuser = new target_user($activeuser);
        $suspendeduser = new target_user($suspendeduser);
        $deleteduser = new target_user($deleteduser);

        // PURGE activeuser
        $result = quickaccess_preferences::execute_purge($activeuser, context_system::instance());
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $result);

        $activeuserrecords = $DB->get_records('quickaccess_preferences', array('userid' => $activeuser->id));
        $this->assertCount(0, $activeuserrecords);

        // PURGE suspendeduser
        $result = quickaccess_preferences::execute_purge($suspendeduser, context_system::instance());
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $result);

        $suspendeduserrecords = $DB->get_records('quickaccess_preferences', array('userid' => $suspendeduser->id));
        $this->assertCount(0, $suspendeduserrecords);


        // PURGE deleteduser
        $result = quickaccess_preferences::execute_purge($deleteduser, context_system::instance());
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $result);

        // We don't purge data for deleted users
        $deleteduserrecords = $DB->get_records('quickaccess_preferences', array('userid' => $deleteduser->id));
        $this->assertCount(2, $deleteduserrecords);

        // CHECK controluser
        $controluserrecords = $DB->get_records('quickaccess_preferences', array('userid' => $controluser->id), '', 'name, value');
        $this->assertCount(2, $controluserrecords);
        $this->assertEquals('[dummy-items-control]', $controluserrecords[$menu_items_key]->value);
        $this->assertEquals('[dummy-groups-control]', $controluserrecords[$menu_groups_key]->value);
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

        // We don't need valid data here.
        $menu_items_key = 'core_admin-quickaccessmenu-items';
        $menu_groups_key = 'core_admin-quickaccessmenu-groups';

        $control_data1 = array('userid' => $controluser->id, 'name' => $menu_items_key, 'value' => '[dummy-items]');
        $control_data2 = array('userid' => $controluser->id, 'name' => $menu_groups_key, 'value'=> '[dummy-groups]');
        $DB->insert_record('quickaccess_preferences', (object)$control_data1);
        $DB->insert_record('quickaccess_preferences', (object)$control_data2);

        $suspended_data1 = array('userid' => $suspendeduser->id, 'name' => $menu_items_key, 'value' => '[dummy-items]');
        $suspended_data2 = array('userid' => $suspendeduser->id, 'name' => $menu_groups_key, 'value'=> '[dummy-groups]');
        $DB->insert_record('quickaccess_preferences', (object)$suspended_data1);
        $DB->insert_record('quickaccess_preferences', (object)$suspended_data2);

        $deleted_data1 = array('userid' => $deleteduser->id, 'name' => $menu_items_key, 'value' => '[dummy-items]');
        $DB->insert_record('quickaccess_preferences', (object)$deleted_data1);

        $controluser = new target_user($controluser);
        $activeuser = new target_user($activeuser);
        $suspendeduser = new target_user($suspendeduser);
        $deleteduser = new target_user($deleteduser);

        // Do the count.
        $result = quickaccess_preferences::execute_count(new target_user($controluser), context_system::instance());
        $this->assertEquals(2, $result);

        $result = quickaccess_preferences::execute_count(new target_user($activeuser), context_system::instance());
        $this->assertEquals(0, $result);

        $result = quickaccess_preferences::execute_count(new target_user($suspendeduser), context_system::instance());
        $this->assertEquals(2, $result);

        $result = quickaccess_preferences::execute_count(new target_user($deleteduser), context_system::instance());
        $this->assertEquals(1, $result);
    }

    /**
     * Test function is_exportable
     */
    public function test_is_exportable() {
        $this->assertFalse(quickaccess_preferences::is_exportable());
    }
}
