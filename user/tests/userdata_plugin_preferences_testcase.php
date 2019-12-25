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
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 * @package core_user
 */

use totara_userdata\userdata\item;
use totara_userdata\userdata\target_user;

defined('MOODLE_INTERNAL') || die();

/**
 * Abstract class to help with testing user preferences that extend \core_user\userdata\plugin_preferences
 *
 * @group totara_userdata
 */
abstract class core_user_userdata_plugin_preferences_testcase extends advanced_testcase {

    /**
     * Returns the preference class as a string.
     *
     * This returned string should be a class name, and that class should implement \core_user\userdata\plugin_preferences
     *
     * @return string
     */
    abstract protected function get_preferences_class(): string;

    /**
     * Returns an array of preferences, the key is the preference name, and the value is an array of possible values.
     *
     * @return array[]
     */
    abstract protected function get_preferences(): array;

    public function test_applicable_in_the_system_context_only() {
        /** @var \core_user\userdata\plugin_preferences $class */
        $class = $this->get_preferences_class();
        self::assertTrue(class_exists($class), 'Incorrectly named preference class, check its namespace '.$class);
        $contexts = $class::get_compatible_context_levels();
        self::assertCount(1, $contexts);
        self::assertSame([CONTEXT_SYSTEM], $contexts);
    }

    public function test_is_exportable() {
        $class = $this->get_preferences_class();
        $result = forward_static_call([$class, 'is_exportable']);
        self::assertTrue($result);
    }

    public function test_is_purgeable() {
        $class = $this->get_preferences_class();
        $result = forward_static_call([$class, 'is_purgeable'], target_user::STATUS_ACTIVE);
        self::assertTrue($result);
        $result = forward_static_call([$class, 'is_purgeable'], target_user::STATUS_SUSPENDED);
        self::assertTrue($result);
        $result = forward_static_call([$class, 'is_purgeable'], target_user::STATUS_DELETED);
        self::assertTrue($result);
    }

    public function test_purge_of_active_users() {

        $this->resetAfterTest(true);

        $generator = $this->getDataGenerator();
        $usera = $generator->create_user(['username' => 'a']);
        $userb = $generator->create_user(['username' => 'b']);

        // Create some preferences that won't be included, they are control preferences.
        set_user_preferences(['control_a' => true, 'control_b' => false], $usera);
        set_user_preferences(['control_c' => true, 'control_d' => false], $userb);

        $class = $this->get_preferences_class();
        $preferences = $this->get_preferences();
        $context = \context_system::instance();

        foreach ($preferences as $preference => $values) {

            foreach ($values as $value) {
                set_user_preference($preference, $value, $usera->id);
                set_user_preference($preference, $value, $userb->id);

                self::assertEquals($value, get_user_preferences($preference, null, $usera->id));
                self::assertEquals($value, get_user_preferences($preference, null, $userb->id));

                $result = forward_static_call([$class, 'execute_purge'], new target_user($usera), $context);
                $this::assertSame(item::RESULT_STATUS_SUCCESS, $result);

                // Ensure that the cache gets unset here. It is established on the user object, and as such is outside of the
                // access of the target user used by export.
                unset($usera->preference);
                unset($userb->preference);
                self::assertEquals(null, get_user_preferences($preference, null, $usera));
                self::assertEquals($value, get_user_preferences($preference, null, $userb));
                self::assertEquals(true, get_user_preferences('control_a', null, $usera));
                self::assertEquals(false, get_user_preferences('control_b', null, $usera));
                self::assertEquals(true, get_user_preferences('control_c', null, $userb));
                self::assertEquals(false, get_user_preferences('control_d', null, $userb));
            }
        }
    }

    public function test_export_of_active_users() {

        $generator = $this->getDataGenerator();
        $usera = $generator->create_user(['username' => 'a']);
        $userb = $generator->create_user(['username' => 'b']);

        // Create some preferences that won't be included, they are control preferences.
        set_user_preferences(['control_a' => true, 'control_b' => false], $usera);
        set_user_preferences(['control_c' => true, 'control_d' => false], $userb);

        $class = $this->get_preferences_class();
        $preferences = $this->get_preferences();
        $context = \context_system::instance();

        foreach ($preferences as $preference => $values) {

            foreach ($values as $value) {
                set_user_preference($preference, $value, $usera->id);
                set_user_preference($preference, $value, $userb->id);

                // Ensure that the cache gets unset here. It is established on the user object, and as such is outside of the
                // access of the target user used by export.
                unset($usera->preference);
                unset($userb->preference);
                self::assertEquals($value, get_user_preferences($preference, null, $usera));
                self::assertEquals($value, get_user_preferences($preference, null, $userb));
                self::assertEquals(true, get_user_preferences('control_a', null, $usera));
                self::assertEquals(false, get_user_preferences('control_b', null, $usera));
                self::assertEquals(true, get_user_preferences('control_c', null, $userb));
                self::assertEquals(false, get_user_preferences('control_d', null, $userb));

                if (forward_static_call([$class, 'is_exportable'])) {
                    $export = forward_static_call([$class, 'execute_export'], new target_user($usera), $context);
                    self::assertInstanceOf(\totara_userdata\userdata\export::class, $export);
                    self::assertArrayHasKey($preference, $export->data);
                    self::assertEquals($value, $export->data[$preference]);
                    self::assertArrayNotHasKey('control_a', $export->data);
                    self::assertArrayNotHasKey('control_b', $export->data);
                    self::assertArrayNotHasKey('control_c', $export->data);
                    self::assertArrayNotHasKey('control_d', $export->data);
                }
            }
        }

        $preferencecount = count($preferences);

        $count = forward_static_call([$class, 'execute_count'], new target_user($usera), $context);
        self::assertIsInt($count);
        self::assertSame($preferencecount, $count);

        $count = forward_static_call([$class, 'execute_count'], new target_user($userb), $context);
        self::assertIsInt($count);
        self::assertSame($preferencecount, $count);
    }

    public function test_purge_of_suspended_users() {

        $this->resetAfterTest(true);

        $generator = $this->getDataGenerator();
        $usera = $generator->create_user(['username' => 'a']);
        $userb = $generator->create_user(['username' => 'b']);

        // Create some preferences that won't be included, they are control preferences.
        set_user_preferences(['control_a' => true, 'control_b' => false], $usera);
        set_user_preferences(['control_c' => true, 'control_d' => false], $userb);

        $class = $this->get_preferences_class();
        $preferences = $this->get_preferences();
        $context = \context_system::instance();

        foreach ($preferences as $preference => $values) {

            $value = reset($values);

            set_user_preference($preference, $value, $usera->id);
            set_user_preference($preference, $value, $userb->id);

            self::assertEquals($value, get_user_preferences($preference, null, $usera->id));
            self::assertEquals($value, get_user_preferences($preference, null, $userb->id));
        }

        $usera = $this->suspend_user_for_testing($usera->id);
        $userb = $this->suspend_user_for_testing($userb->id);

        foreach ($preferences as $preference => $values) {
            $value = reset($values);
            self::assertEquals($value, get_user_preferences($preference, null, $usera));
            self::assertEquals($value, get_user_preferences($preference, null, $userb));
        }
        self::assertEquals(true, get_user_preferences('control_a', null, $usera));
        self::assertEquals(false, get_user_preferences('control_b', null, $usera));
        self::assertEquals(true, get_user_preferences('control_c', null, $userb));
        self::assertEquals(false, get_user_preferences('control_d', null, $userb));

        $result = forward_static_call([$class, 'execute_purge'], new target_user($usera), $context);
        $this::assertSame(item::RESULT_STATUS_SUCCESS, $result);

        // Ensure that the cache gets unset here. It is established on the user object, and as such is outside of the
        // access of the target user used by export.
        unset($usera->preference);
        unset($userb->preference);
        foreach ($preferences as $preference => $values) {
            $value = reset($values);
            self::assertEquals(null, get_user_preferences($preference, null, $usera), 'Testing preference ' . $preference . ', it is not the expected value');
            self::assertEquals($value, get_user_preferences($preference, null, $userb), 'Testing preference ' . $preference . ', it is not the expected value');
        }
        self::assertEquals(true, get_user_preferences('control_a', null, $usera));
        self::assertEquals(false, get_user_preferences('control_b', null, $usera));
        self::assertEquals(true, get_user_preferences('control_c', null, $userb));
        self::assertEquals(false, get_user_preferences('control_d', null, $userb));

        $result = forward_static_call([$class, 'execute_purge'], new target_user($userb), $context);
        $this::assertSame(item::RESULT_STATUS_SUCCESS, $result);

        // Ensure that the cache gets unset here. It is established on the user object, and as such is outside of the
        // access of the target user used by export.
        unset($usera->preference);
        unset($userb->preference);
        foreach ($preferences as $preference => $values) {
            self::assertEquals(null, get_user_preferences($preference, null, $usera));
            self::assertEquals(null, get_user_preferences($preference, null, $userb));
        }
        self::assertEquals(true, get_user_preferences('control_a', null, $usera));
        self::assertEquals(false, get_user_preferences('control_b', null, $usera));
        self::assertEquals(true, get_user_preferences('control_c', null, $userb));
        self::assertEquals(false, get_user_preferences('control_d', null, $userb));
    }

    public function test_export_of_suspended_users() {
        $generator = $this->getDataGenerator();
        $usera = $generator->create_user(['username' => 'a']);
        $userb = $generator->create_user(['username' => 'b']);

        // Create some preferences that won't be included, they are control preferences.
        set_user_preferences(['control_a' => true, 'control_b' => false], $usera);
        set_user_preferences(['control_c' => true, 'control_d' => false], $userb);

        $class = $this->get_preferences_class();
        $preferences = $this->get_preferences();
        $context = \context_system::instance();

        foreach ($preferences as $preference => $values) {

            $value = reset($values);

            set_user_preference($preference, $value, $usera->id);
            set_user_preference($preference, $value, $userb->id);

            self::assertEquals($value, get_user_preferences($preference, null, $usera->id));
            self::assertEquals($value, get_user_preferences($preference, null, $userb->id));
        }

        $usera = $this->suspend_user_for_testing($usera->id);
        $userb = $this->suspend_user_for_testing($userb->id);

        // Ensure that the cache gets unset here. It is established on the user object, and as such is outside of the
        // access of the target user used by export.
        unset($usera->preference);
        unset($userb->preference);
        foreach ($preferences as $preference => $values) {
            $value = reset($values);
            self::assertEquals($value, get_user_preferences($preference, null, $usera));
            self::assertEquals($value, get_user_preferences($preference, null, $userb));
        }
        self::assertEquals(true, get_user_preferences('control_a', null, $usera));
        self::assertEquals(false, get_user_preferences('control_b', null, $usera));
        self::assertEquals(true, get_user_preferences('control_c', null, $userb));
        self::assertEquals(false, get_user_preferences('control_d', null, $userb));

        if (!forward_static_call([$class, 'is_exportable'])) {
            // This is not exportable.
            return [$usera, $userb, $class, $preferences, $context];
        }

        $export = forward_static_call([$class, 'execute_export'], new target_user($usera), $context);
        self::assertInstanceOf(\totara_userdata\userdata\export::class, $export);

        foreach ($preferences as $preference => $values) {
            $value = reset($values);
            self::assertArrayHasKey($preference, $export->data);
            self::assertEquals($value, $export->data[$preference]);
        }
        self::assertArrayNotHasKey('control_a', $export->data);
        self::assertArrayNotHasKey('control_b', $export->data);
        self::assertArrayNotHasKey('control_c', $export->data);
        self::assertArrayNotHasKey('control_d', $export->data);

        $preferencecount = count($preferences);

        $count = forward_static_call([$class, 'execute_count'], new target_user($usera), $context);
        self::assertIsInt($count);
        self::assertSame($preferencecount, $count);

        $count = forward_static_call([$class, 'execute_count'], new target_user($userb), $context);
        self::assertIsInt($count);
        self::assertSame($preferencecount, $count);
    }

    public function test_purge_of_deleted_users() {
        $this->resetAfterTest(true);

        $generator = $this->getDataGenerator();
        $usera = $generator->create_user(['username' => 'a']);
        $userb = $generator->create_user(['username' => 'b']);

        // Create some preferences that won't be included, they are control preferences.
        set_user_preferences(['control_a' => true, 'control_b' => false], $usera);
        set_user_preferences(['control_c' => true, 'control_d' => false], $userb);

        $class = $this->get_preferences_class();
        $preferences = $this->get_preferences();
        $context = \context_system::instance();

        foreach ($preferences as $preference => $values) {

            $value = reset($values);

            set_user_preference($preference, $value, $usera->id);
            set_user_preference($preference, $value, $userb->id);

            self::assertEquals($value, get_user_preferences($preference, null, $usera->id));
            self::assertEquals($value, get_user_preferences($preference, null, $userb->id));
        }

        $usera = $this->delete_user_for_testing($usera->id);
        $userb = $this->delete_user_for_testing($userb->id);

        foreach ($preferences as $preference => $values) {
            $value = reset($values);
            self::assertEquals($value, get_user_preferences($preference, null, $usera), 'Testing preference ' . $preference . ', it is not the expected value');
            self::assertEquals($value, get_user_preferences($preference, null, $userb), 'Testing preference ' . $preference . ', it is not the expected value');
        }
        self::assertEquals(true, get_user_preferences('control_a', null, $usera));
        self::assertEquals(false, get_user_preferences('control_b', null, $usera));
        self::assertEquals(true, get_user_preferences('control_c', null, $userb));
        self::assertEquals(false, get_user_preferences('control_d', null, $userb));

        $result = forward_static_call([$class, 'execute_purge'], new target_user($usera), $context);
        $this::assertSame(item::RESULT_STATUS_SUCCESS, $result);

        // Ensure that the cache gets unset here. It is established on the user object, and as such is outside of the
        // access of the target user used by export.
        unset($usera->preference);
        unset($userb->preference);
        foreach ($preferences as $preference => $values) {
            $value = reset($values);
            self::assertEquals(null, get_user_preferences($preference, null, $usera), 'Testing preference ' . $preference . ', it is not the expected value');
            self::assertEquals($value, get_user_preferences($preference, null, $userb), 'Testing preference ' . $preference . ', it is not the expected value');
        }
        self::assertEquals(true, get_user_preferences('control_a', null, $usera));
        self::assertEquals(false, get_user_preferences('control_b', null, $usera));
        self::assertEquals(true, get_user_preferences('control_c', null, $userb));
        self::assertEquals(false, get_user_preferences('control_d', null, $userb));

        $result = forward_static_call([$class, 'execute_purge'], new target_user($userb), $context);
        $this::assertSame(item::RESULT_STATUS_SUCCESS, $result);

        // Ensure that the cache gets unset here. It is established on the user object, and as such is outside of the
        // access of the target user used by export.
        unset($usera->preference);
        unset($userb->preference);
        foreach ($preferences as $preference => $values) {
            self::assertEquals(null, get_user_preferences($preference, null, $usera->id));
            self::assertEquals(null, get_user_preferences($preference, null, $userb->id));
        }
        self::assertEquals(true, get_user_preferences('control_a', null, $usera));
        self::assertEquals(false, get_user_preferences('control_b', null, $usera));
        self::assertEquals(true, get_user_preferences('control_c', null, $userb));
        self::assertEquals(false, get_user_preferences('control_d', null, $userb));
    }

    public function test_export_of_deleted_users() {
        $generator = $this->getDataGenerator();
        $usera = $generator->create_user(['username' => 'a']);
        $userb = $generator->create_user(['username' => 'b']);

        // Create some preferences that won't be included, they are control preferences.
        set_user_preferences(['control_a' => true, 'control_b' => false], $usera);
        set_user_preferences(['control_c' => true, 'control_d' => false], $userb);

        $class = $this->get_preferences_class();
        $preferences = $this->get_preferences();
        $context = \context_system::instance();

        foreach ($preferences as $preference => $values) {

            $value = reset($values);

            set_user_preference($preference, $value, $usera->id);
            set_user_preference($preference, $value, $userb->id);

            self::assertEquals($value, get_user_preferences($preference, null, $usera->id));
            self::assertEquals($value, get_user_preferences($preference, null, $userb->id));
        }

        $usera = $this->delete_user_for_testing($usera->id);
        $userb = $this->delete_user_for_testing($userb->id);

        foreach ($preferences as $preference => $values) {
            $value = reset($values);
            self::assertEquals($value, get_user_preferences($preference, null, $usera->id), 'Testing preference ' . $preference . ', it is not the expected value');
            self::assertEquals($value, get_user_preferences($preference, null, $userb->id), 'Testing preference ' . $preference . ', it is not the expected value');
        }

        if (!forward_static_call([$class, 'is_exportable'])) {
            // This is not exportable.
            return [$usera, $userb, $class, $preferences, $context];
        }

        $export = forward_static_call([$class, 'execute_export'], new target_user($usera), $context);
        // Deleted user preferences cannot be exported, we expect that have been removed.
        self::assertSame(item::RESULT_STATUS_SKIPPED, $export);

        $preferencecount = count($preferences);

        $count = forward_static_call([$class, 'execute_count'], new target_user($usera), $context);
        self::assertIsInt($count);
        self::assertSame($preferencecount, $count);

        $count = forward_static_call([$class, 'execute_count'], new target_user($userb), $context);
        self::assertIsInt($count);
        self::assertSame($preferencecount, $count);
    }

    /**
     * DO NOT COPY THIS TO PRODUCTION CODE!
     *
     * See user/action.php
     *
     * @param int $userid
     * @return \stdClass The updated user object.
     */
    private function suspend_user_for_testing($userid) {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/user/lib.php');
        $user = $DB->get_record('user', ['id' => $userid]);
        $user->suspended = 1;
        // No need to end user sessions. DO NOT COPY THIS TO PRODUCTION CODE!
        user_update_user($user, false);
        \totara_core\event\user_suspended::create_from_user($user)->trigger();
        return $DB->get_record('user', ['id' => $user->id]);
    }



    /**
     * DO NOT COPY THIS TO PRODUCTION CODE!
     *
     * See user/action.php
     *
     * @param int $userid
     * @return \stdClass The updated user object.
     */
    private function delete_user_for_testing($userid) {
        global $DB;
        // Note that we don't properly delete the user, in fact we just simulate it.
        // That is enough for our testing.
        // If you need to delete the user properly the following code will be required:
        //     global $CFG,
        //     require_once($CFG->dirroot . '/user/lib.php');
        //     user_delete_user($DB->get_record('user', ['id' => $userid]));
        $DB->set_field('user', 'deleted', '1', ['id' => $userid]);
        return $DB->get_record('user', ['id' => $userid]);
    }

}