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
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 * @package totara_userdata
 * @category test
 */

use totara_userdata\userdata\item;
use totara_userdata\userdata\target_user;

defined('MOODLE_INTERNAL') || die();

/**
 * General tests for all item classes in the codebase.
 */
class totara_userdata_items_testcase extends advanced_testcase {
    /**
     * Returns list of all item classes to be tested.
     * @return array
     */
    public function item_classes() {
        $return = array();
        foreach (\totara_userdata\local\util::get_item_classes() as $class) {
            $return[] = array($class);
        }
        return $return;
    }

    /**
     * Check that strings for item full name exist.
     *
     * @param item $class it is not an instance, this is used for autocomplete in editors only
     * @dataProvider item_classes
     */
    public function test_get_fullname($class) {
        $class::get_fullname();
        $this->assertDebuggingNotCalled();
    }

    /**
     * Check that strings for item full name exist.
     *
     * @param item $class it is not an instance, this is used for autocomplete in editors only
     * @dataProvider item_classes
     */
    public function test_get_main_component($class) {
        $component = $class::get_main_component();

        list($type, $name) = \core_component::normalize_component($component);

        if ($type === 'core') {
            $this->assertTrue(\core_component::is_core_subsystem($name));
        } else {
            $this->assertNotNull(core_component::get_plugin_directory($type, $name));
        }
    }

    /**
     * Check that sortorder is valid.
     *
     * @param item $class it is not an instance, this is used for autocomplete in editors only
     * @dataProvider item_classes
     */
    public function test_get_sortorder($class) {
        $sortorder = $class::get_sortorder();

        $this->assertIsInt($sortorder);
    }

    /**
     * Check that context level tests return bools
     *
     * @param item $class it is not an instance, this is used for autocomplete in editors only
     * @dataProvider item_classes
     */
    public function test_is_compatible_context_level($class) {
        $compatible = $class::is_compatible_context_level(CONTEXT_SYSTEM);
        $this->assertIsBool($compatible);
        $compatible = $class::is_compatible_context_level(CONTEXT_USER);
        $this->assertIsBool($compatible);
        $compatible = $class::is_compatible_context_level(CONTEXT_COURSECAT);
        $this->assertIsBool($compatible);
        $compatible = $class::is_compatible_context_level(CONTEXT_COURSE);
        $this->assertIsBool($compatible);
        $compatible = $class::is_compatible_context_level(CONTEXT_MODULE);
        $this->assertIsBool($compatible);
        $compatible = $class::is_compatible_context_level(CONTEXT_BLOCK);
        $this->assertIsBool($compatible);
    }

    /**
     * Basic check of item count.
     *
     * @param item $class it is not an instance, this is used for autocomplete in editors only
     * @dataProvider item_classes
     */
    public function test_count($class) {
        global $DB;

        if (!$class::is_countable()) {
            return;
        }

        $this->setUser(null); // Use not-logged-in user to make sure there is no access control!

        $user = get_admin();
        $usercontext = context_user::instance($user->id);
        $syscontext = context_system::instance();
        $categorycontext = context_coursecat::instance($DB->get_field('course_categories', "MIN(id)", array('parent' => 0)));
        $coursecontext = context_course::instance(get_site()->id);
        $blockcontext = context_coursecat::instance($DB->get_field('block_instances', "MIN(id)", array()));

        $contexts = array($syscontext, $usercontext, $categorycontext, $coursecontext, $blockcontext);

        $targetuser = new target_user($user);

        foreach ($contexts as $context) {
            $result = $class::execute_count($targetuser, $context);
            if (!$class::is_compatible_context_level($context->contextlevel)) {
                $this->assertSame(item::RESULT_STATUS_ERROR, $result);
            } else {
                $this->assertGreaterThanOrEqual(0, $result);
            }
        }
    }

    /**
     * Basic check of item export.
     *
     * @param item $class it is not an instance, this is used for autocomplete in editors only
     * @dataProvider item_classes
     */
    public function test_export($class) {
        global $DB;
        if (!$class::is_exportable()) {
            return;
        }

        $this->setUser(null); // Use not-logged-in user to make sure there is no access control!

        $user = get_admin();
        $usercontext = context_user::instance($user->id);
        $syscontext = context_system::instance();
        $categorycontext = context_coursecat::instance($DB->get_field('course_categories', "MIN(id)", array('parent' => 0)));
        $coursecontext = context_course::instance(get_site()->id);
        $blockcontext = context_coursecat::instance($DB->get_field('block_instances', "MIN(id)", array()));

        $contexts = array($syscontext, $usercontext, $categorycontext, $coursecontext, $blockcontext);

        $targetuser = new target_user($user);

        foreach ($contexts as $context) {
            $export = $class::execute_export($targetuser, $context);
            if (!$class::is_compatible_context_level($context->contextlevel)) {
                $this->assertSame(item::RESULT_STATUS_ERROR, $export);
            } else {
                if ($export !== item::RESULT_STATUS_ERROR or $export !== item::RESULT_STATUS_SKIPPED) {
                    $this->assertInstanceOf('totara_userdata\userdata\export', $export);
                    $this->assertIsArray($export->data);
                    $this->assertIsArray($export->files);
                    foreach ($export->files as $file) {
                        $this->assertInstanceOf('stored_file', $file);
                    }
                }
            }
        }
    }

    /**
     * Basic check of item purge.
     *
     * @param item $class it is not an instance, this is used for autocomplete in editors only
     * @dataProvider item_classes
     */
    public function test_purge($class) {
        global $DB;

        if (!$class::is_purgeable(target_user::STATUS_ACTIVE)
            and !$class::is_purgeable(target_user::STATUS_SUSPENDED)
            and !$class::is_purgeable(target_user::STATUS_SUSPENDED)) {

            return;
        }

        $this->resetAfterTest();

        $this->setUser(null); // Use random user to make sure there is no access control!

        $syscontext = context_system::instance();
        $user = $this->getDataGenerator()->create_user();
        $usercontext = context_user::instance($user->id);
        $category = $this->getDataGenerator()->create_category();
        $categorycontext = context_coursecat::instance($category->id);
        $course = $this->getDataGenerator()->create_course(array('category' => $category->id));
        $coursecontext = context_course::instance($course->id);
        $forum = $this->getDataGenerator()->create_module('forum', array('course' => $course->id));
        $cm = get_coursemodule_from_instance('forum', $forum->id);
        $modcontext = context_module::instance($cm->id);
        $block = $this->getDataGenerator()->create_block('online_users', array('parentcontextid' => $modcontext->id));
        $blockcontext = context_block::instance($block->id);

        $contexts = array($syscontext, $usercontext, $categorycontext, $coursecontext, $modcontext, $blockcontext);

        foreach ($contexts as $context) {
            $user = $DB->get_record('user', array('id' => $user->id));
            $targetuser = new target_user($user);
            $result = $class::execute_purge($targetuser, $context);

            if (!$class::is_purgeable(target_user::STATUS_ACTIVE)) {
                $this->assertSame(item::RESULT_STATUS_ERROR, $result);
            } else if (!$class::is_compatible_context_level($context->contextlevel)) {
                $this->assertSame(item::RESULT_STATUS_ERROR, $result);
            } else {
                if ($result !== item::RESULT_STATUS_SUCCESS and $result !== item::RESULT_STATUS_ERROR and $result !== item::RESULT_STATUS_SKIPPED) {
                    $this->fail('Unexpected purge result code: ' . $result);
                }
            }

            $user = $DB->get_record('user', array('id' => $user->id));
            $targetuser = new target_user($user);
            $result2 = $class::execute_purge($targetuser, $context);
            $this->assertSame($result, $result2, 'Repeated purge request must complete with the same result');
        }

        $DB->set_field('user', 'suspended', 1, array('id' => $user->id));
        foreach ($contexts as $context) {
            $user = $DB->get_record('user', array('id' => $user->id));
            $targetuser = new target_user($user);
            $result = $class::execute_purge($targetuser, $context);

            if (!$class::is_purgeable(target_user::STATUS_SUSPENDED)) {
                $this->assertSame(item::RESULT_STATUS_ERROR, $result);
            } else if (!$class::is_compatible_context_level($context->contextlevel)) {
                $this->assertSame(item::RESULT_STATUS_ERROR, $result);
            } else {
                if ($result !== item::RESULT_STATUS_SUCCESS and $result !== item::RESULT_STATUS_ERROR and $result !== item::RESULT_STATUS_SKIPPED) {
                    $this->fail('Unexpected purge result code: ' . $result);
                }
            }

            $user = $DB->get_record('user', array('id' => $user->id));
            $targetuser = new target_user($user);
            $result2 = $class::execute_purge($targetuser, $context);
            $this->assertSame($result, $result2, 'Repeated purge request must complete with the same result');
        }

        delete_user($user);
        foreach ($contexts as $context) {
            $user = $DB->get_record('user', array('id' => $user->id));
            $targetuser = new target_user($user);
            $result = $class::execute_purge($targetuser, $context);

            if (!$class::is_purgeable(target_user::STATUS_DELETED)) {
                $this->assertSame(item::RESULT_STATUS_ERROR, $result);
            } else if (!$class::is_compatible_context_level($context->contextlevel)) {
                $this->assertSame(item::RESULT_STATUS_ERROR, $result);
            } else {
                if ($result !== item::RESULT_STATUS_SUCCESS and $result !== item::RESULT_STATUS_ERROR and $result !== item::RESULT_STATUS_SKIPPED) {
                    $this->fail('Unexpected purge result code: ' . $result);
                }
            }

            $user = $DB->get_record('user', array('id' => $user->id));
            $targetuser = new target_user($user);
            $result2 = $class::execute_purge($targetuser, $context);
            $this->assertSame($result, $result2, 'Repeated purge request must complete with the same result');
        }
    }
}