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

use core_user\userdata\customfields_notvisible;
use totara_userdata\userdata\export;
use totara_userdata\userdata\item;
use totara_userdata\userdata\target_user;

defined('MOODLE_INTERNAL') || die();

/**
 * Test exporting and counting of non-visible custom fields
 *
 * @group totara_userdata
 */
class core_user_userdata_customfields_notvisible_testcase extends advanced_testcase {

    /**
     * require necessary file
     */
    public static function setUpBeforeClass() {
        parent::setUpBeforeClass();

        global $CFG;
        require_once($CFG->dirroot . '/user/profile/lib.php');
    }

    /**
     * test compatible context levels
     */
    public function test_compatible_context_levels() {
        $expectedcontextlevels = [CONTEXT_SYSTEM];
        $this->assertEquals($expectedcontextlevels, customfields_notvisible::get_compatible_context_levels());
    }

    /**
     * test if data is purgeable
     */
    public function test_ispurgeable() {
        $this->assertTrue(customfields_notvisible::is_purgeable(target_user::STATUS_ACTIVE));
        $this->assertTrue(customfields_notvisible::is_purgeable(target_user::STATUS_DELETED));
        $this->assertTrue(customfields_notvisible::is_purgeable(target_user::STATUS_SUSPENDED));
    }

    /**
     * test if data is exportable
     */
    public function test_isexportable() {
        $this->assertTrue(customfields_notvisible::is_exportable());
    }

    /**
     * test if data is correctly purged
     */
    public function test_purge() {
        global $DB;

        $this->resetAfterTest(true);

        /** @var totara_core_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_core');

        $field1 = $generator->create_custom_profile_field(['datatype' => 'text', 'visible' => PROFILE_VISIBLE_NONE]);
        $field2 = $generator->create_custom_profile_field(['datatype' => 'text']);
        $field3 = $generator->create_custom_profile_field(['datatype' => 'text']);
        $field4 = $generator->create_custom_profile_field(['datatype' => 'text', 'visible' => PROFILE_VISIBLE_NONE]);
        $field5 = $generator->create_custom_profile_field(['datatype' => 'text', 'visible' => PROFILE_VISIBLE_PRIVATE]);

        $controluser = new target_user($this->getDataGenerator()->create_user());
        $activeuser = new target_user($this->getDataGenerator()->create_user());
        $suspendeduser = new target_user($this->getDataGenerator()->create_user(['suspended' => 1]));
        $deleteduser = new target_user($this->getDataGenerator()->create_user(['deleted' => 1]));

        $this->set_profile_field_value($activeuser, $field1, 'active hidden1');
        $this->set_profile_field_value($activeuser, $field2, 'active visible1');
        $this->set_profile_field_value($activeuser, $field3, 'active visible2');
        $this->set_profile_field_value($activeuser, $field4, 'active hidden2');
        $this->set_profile_field_value($activeuser, $field5, 'active visible3');

        $this->set_profile_field_value($suspendeduser, $field1, 'suspended hidden1');
        $this->set_profile_field_value($suspendeduser, $field2, 'suspended visible1');
        $this->set_profile_field_value($suspendeduser, $field3, 'suspended visible2');
        $this->set_profile_field_value($suspendeduser, $field4, 'suspended hidden2');
        $this->set_profile_field_value($suspendeduser, $field5, 'suspended visible3');

        $this->set_profile_field_value($deleteduser, $field1, 'deleted hidden1');
        $this->set_profile_field_value($deleteduser, $field2, 'deleted visible1');
        $this->set_profile_field_value($deleteduser, $field3, 'deleted visible1');
        $this->set_profile_field_value($deleteduser, $field4, 'deleted hidden2');
        $this->set_profile_field_value($deleteduser, $field5, 'deleted visible3');

        $this->set_profile_field_value($controluser, $field1, 'control hidden1');
        $this->set_profile_field_value($controluser, $field2, 'control visible1');
        $this->set_profile_field_value($controluser, $field3, 'control visible1');
        $this->set_profile_field_value($controluser, $field4, 'control hidden2');
        $this->set_profile_field_value($controluser, $field5, 'control visible3');

        $this->assertTrue(customfields_notvisible::is_purgeable($activeuser->status));
        $this->assertTrue(customfields_notvisible::is_purgeable($suspendeduser->status));
        $this->assertTrue(customfields_notvisible::is_purgeable($deleteduser->status));

        // We want to catch the events fired.
        $sink = $this->redirectEvents();

        /****************************
         * PURGE activeuser
         ***************************/
        $result = customfields_notvisible::execute_purge($activeuser, context_system::instance());
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $result);
        // Non-visible items are gone.
        $this->assertEmpty($DB->get_record('user_info_data', ['userid' => $activeuser->id, 'fieldid' => $field1->id]));
        $this->assertEmpty($DB->get_record('user_info_data', ['userid' => $activeuser->id, 'fieldid' => $field4->id]));
        // Visible items are still there.
        $this->assertNotEmpty($DB->get_record('user_info_data', ['userid' => $activeuser->id, 'fieldid' => $field2->id]));
        $this->assertNotEmpty($DB->get_record('user_info_data', ['userid' => $activeuser->id, 'fieldid' => $field3->id]));
        $this->assertNotEmpty($DB->get_record('user_info_data', ['userid' => $activeuser->id, 'fieldid' => $field5->id]));

        $events = $sink->get_events();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(\core\event\user_updated::class, reset($events));
        $sink->clear();

        /****************************
         * PURGE suspendeduser
         ***************************/
        $result = customfields_notvisible::execute_purge($suspendeduser, context_system::instance());
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $result);
        // Non-visible items are gone.
        $this->assertEmpty($DB->get_record('user_info_data', ['userid' => $suspendeduser->id, 'fieldid' => $field1->id]));
        $this->assertEmpty($DB->get_record('user_info_data', ['userid' => $suspendeduser->id, 'fieldid' => $field4->id]));
        // Visible items are still there.
        $this->assertNotEmpty($DB->get_record('user_info_data', ['userid' => $suspendeduser->id, 'fieldid' => $field2->id]));
        $this->assertNotEmpty($DB->get_record('user_info_data', ['userid' => $suspendeduser->id, 'fieldid' => $field3->id]));
        $this->assertNotEmpty($DB->get_record('user_info_data', ['userid' => $suspendeduser->id, 'fieldid' => $field5->id]));

        $events = $sink->get_events();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(\core\event\user_updated::class, reset($events));
        $sink->clear();

        /****************************
         * PURGE deleteduser
         ***************************/
        $result = customfields_notvisible::execute_purge($deleteduser, context_system::instance());
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $result);
        // Non-visible items are gone.
        $this->assertEmpty($DB->get_record('user_info_data', ['userid' => $deleteduser->id, 'fieldid' => $field1->id]));
        $this->assertEmpty($DB->get_record('user_info_data', ['userid' => $deleteduser->id, 'fieldid' => $field4->id]));
        // Visible items are still there.
        $this->assertNotEmpty($DB->get_record('user_info_data', ['userid' => $deleteduser->id, 'fieldid' => $field2->id]));
        $this->assertNotEmpty($DB->get_record('user_info_data', ['userid' => $deleteduser->id, 'fieldid' => $field3->id]));
        $this->assertNotEmpty($DB->get_record('user_info_data', ['userid' => $deleteduser->id, 'fieldid' => $field5->id]));

        $events = $sink->get_events();
        $this->assertCount(0, $events);

        // Control users entries are untouched.
        $this->assertCount(5, $DB->get_records('user_info_data', ['userid' => $controluser->id]));
    }

    /**
     * test if data is correctly counted
     */
    public function test_count() {
        $this->resetAfterTest(true);

        /** @var totara_core_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_core');

        $field1 = $generator->create_custom_profile_field(['datatype' => 'text', 'visible' => PROFILE_VISIBLE_NONE]);
        $field2 = $generator->create_custom_profile_field(['datatype' => 'text']);
        $field3 = $generator->create_custom_profile_field(['datatype' => 'text']);
        $field4 = $generator->create_custom_profile_field(['datatype' => 'text', 'visible' => PROFILE_VISIBLE_NONE]);
        $field5 = $generator->create_custom_profile_field(['datatype' => 'text', 'visible' => PROFILE_VISIBLE_PRIVATE]);

        $activeuser = new target_user($this->getDataGenerator()->create_user());
        $suspendeduser = new target_user($this->getDataGenerator()->create_user(['suspended' => 1]));
        $deleteduser = new target_user($this->getDataGenerator()->create_user(['deleted' => 1]));
        $user = new target_user($this->getDataGenerator()->create_user());

        $this->set_profile_field_value($activeuser, $field1, 'hidden');
        $this->set_profile_field_value($activeuser, $field2, 'abc');
        $this->set_profile_field_value($activeuser, $field3, 'cde');
        $this->set_profile_field_value($activeuser, $field4, 'hidden');
        $this->set_profile_field_value($activeuser, $field5, 'private');

        $this->set_profile_field_value($suspendeduser, $field1, 'hidden1');
        $this->set_profile_field_value($suspendeduser, $field2, 'visible1');
        $this->set_profile_field_value($suspendeduser, $field3, 'visible2');
        $this->set_profile_field_value($suspendeduser, $field4, 'hidden2');
        $this->set_profile_field_value($suspendeduser, $field5, 'visible3');

        $this->set_profile_field_value($deleteduser, $field1, 'hidden1');
        $this->set_profile_field_value($deleteduser, $field2, 'visible1');
        $this->set_profile_field_value($deleteduser, $field5, 'visible2');

        // Do the count.
        $result = customfields_notvisible::execute_count(new target_user($activeuser), context_system::instance());
        $this->assertEquals(2, $result);

        $result = customfields_notvisible::execute_count(new target_user($suspendeduser), context_system::instance());
        $this->assertEquals(2, $result);

        $result = customfields_notvisible::execute_count(new target_user($deleteduser), context_system::instance());
        $this->assertEquals(1, $result);

        $result = customfields_notvisible::execute_count(new target_user($user), context_system::instance());
        $this->assertEquals(0, $result);
    }


    /**
     * test if data is correctly counted
     */
    public function test_export() {
        $this->resetAfterTest(true);

        /** @var totara_core_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_core');

        $field1 = $generator->create_custom_profile_field(['datatype' => 'text', 'visible' => PROFILE_VISIBLE_NONE]);
        $field2 = $generator->create_custom_profile_field(['datatype' => 'text']);
        $field3 = $generator->create_custom_profile_field(['datatype' => 'text']);
        $field4 = $generator->create_custom_profile_field(['datatype' => 'text', 'visible' => PROFILE_VISIBLE_NONE]);
        $field5 = $generator->create_custom_profile_field(['datatype' => 'text', 'visible' => PROFILE_VISIBLE_PRIVATE]);

        $activeuser = new target_user($this->getDataGenerator()->create_user());
        $suspendeduser = new target_user($this->getDataGenerator()->create_user(['suspended' => 1]));
        $deleteduser = new target_user($this->getDataGenerator()->create_user(['deleted' => 1]));
        $user = new target_user($this->getDataGenerator()->create_user());

        $this->set_profile_field_value($activeuser, $field1, 'active hidden1');
        $this->set_profile_field_value($activeuser, $field2, 'active visible1');
        $this->set_profile_field_value($activeuser, $field3, 'active visible2');
        $this->set_profile_field_value($activeuser, $field4, 'active hidden2');
        $this->set_profile_field_value($activeuser, $field5, 'active visible3');

        $this->set_profile_field_value($suspendeduser, $field1, 'suspended hidden1');
        $this->set_profile_field_value($suspendeduser, $field2, 'suspended visible1');
        $this->set_profile_field_value($suspendeduser, $field3, 'suspended visible2');
        $this->set_profile_field_value($suspendeduser, $field4, 'suspended hidden2');
        $this->set_profile_field_value($suspendeduser, $field5, 'suspended visible3');

        $this->set_profile_field_value($deleteduser, $field1, 'deleted hidden1');
        $this->set_profile_field_value($deleteduser, $field2, 'deleted visible1');
        $this->set_profile_field_value($deleteduser, $field5, 'deleted visible2');

        /****************************
         * EXPORT activeuser
         ***************************/

        $result = customfields_notvisible::execute_export(new target_user($activeuser), context_system::instance());
        $this->assertInstanceOf(export::class, $result);
        $this->assertCount(2, $result->data);

        $expectedfield1 = ['shortname' => $field1->shortname, 'data' => 'active hidden1'];
        $expectedfield2 = ['shortname' => $field4->shortname, 'data' => 'active hidden2'];

        $this->assertContains($expectedfield1, $result->data);
        $this->assertContains($expectedfield2, $result->data);

        /****************************
         * EXPORT suspendeduser
         ***************************/

        $result = customfields_notvisible::execute_export(new target_user($suspendeduser), context_system::instance());
        $this->assertInstanceOf(export::class, $result);
        $this->assertCount(2, $result->data);

        $expectedfield1 = ['shortname' => $field1->shortname, 'data' => 'suspended hidden1'];
        $expectedfield2 = ['shortname' => $field4->shortname, 'data' => 'suspended hidden2'];

        $this->assertContains($expectedfield1, $result->data);
        $this->assertContains($expectedfield2, $result->data);

        /****************************
         * EXPORT deleteduser
         ***************************/

        $result = customfields_notvisible::execute_export(new target_user($deleteduser), context_system::instance());
        $this->assertInstanceOf(export::class, $result);
        $this->assertCount(1, $result->data);

        $expectedfield = ['shortname' => $field1->shortname, 'data' => 'deleted hidden1'];

        $this->assertContains($expectedfield, $result->data);

        /****************************
         * EXPORT user
         ***************************/

        $result = customfields_notvisible::execute_export(new target_user($user), context_system::instance());
        $this->assertInstanceOf(export::class, $result);
        $this->assertCount(0, $result->data);
    }

    /**
     * @param stdClass $user
     * @param stdClass $field
     * @param string $data
     * @param int $dataformat
     *
     * @return int
     */
    private function set_profile_field_value(stdClass $user, stdClass $field, string $data, int $dataformat = 0): int {
        global $DB;

        $record = new stdClass();
        $record->fieldid = $field->id;
        $record->userid = $user->id;
        $record->data = $data;
        $record->dataformat = $dataformat;

        return $DB->insert_record('user_info_data', $record);
    }

}