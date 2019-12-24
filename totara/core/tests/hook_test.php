<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2013 onwards Totara Learning Solutions LTD
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
 * @author  Petr Skoda <petr.skoda@totaralearning.com>
 * @package totara_core
 */

/**
 * Tests for hook manager, base class and watchers.
 *
 * @author  Petr Skoda <petr.skoda@totaralearning.com>
 * @package totara_core
 */

defined('MOODLE_INTERNAL') || die();

class totara_core_hook_testcase extends advanced_testcase {
    public function test_watcher_parsing() {
        require_once(__DIR__ . '/fixtures/test_hook.php');

        $watchers = array(
            array(
                'hookname' => 'totara_core_test_hook',
                'callback' => array('totara_core_test_hook_watcher', 'listen1'),
                'includefile' => 'totara/core/tests/fixtures/test_hook_watcher.php',
            ),
        );
        \totara_core\hook\manager::phpunit_replace_watchers($watchers);
        $this->assertDebuggingNotCalled();

        $watchers = array(
            array(
                'xhookname' => 'totara_core_test_hook',
                'callback' => array('totara_core_test_hook_watcher', 'listen1'),
                'includefile' => 'totara/core/tests/fixtures/test_hook_watcher.php',
            ),
        );
        \totara_core\hook\manager::phpunit_replace_watchers($watchers);
        $this->assertDebuggingCalled('Invalid \'hookname\' detected in phpunit watcher definition');

        $watchers = array(
            array(
                'hookname' => 'totara_core_test_hook',
                'xcallback' => array('totara_core_test_hook_watcher', 'listen1'),
                'includefile' => 'totara/core/tests/fixtures/test_hook_watcher.php',
            ),
        );
        \totara_core\hook\manager::phpunit_replace_watchers($watchers);
        $this->assertDebuggingCalled('Invalid \'callback\' detected in phpunit watcher definition');

        $watchers = array(
            array(
                'hookname' => 'totara_core_test_hook',
                'callback' => array('totara_core_test_hook_watcher', 'listen1'),
                'includefile' => 'xxxtotara/core/tests/fixtures/test_hook_watcher.php',
            ),
        );
        \totara_core\hook\manager::phpunit_replace_watchers($watchers);
        $this->assertDebuggingCalled('Invalid \'includefile\' detected in phpunit watcher definition');
    }

    public function test_execute() {
        require_once(__DIR__ . '/fixtures/test_hook.php');

        // This is the format used in db/hooks.php files.
        $watchers = [
            [
                'hookname' => '\\totara_core_test_hook', // Extra '\' in front is ignored.
                'callback' => array('totara_core_test_hook_watcher', 'listen0'),
                'includefile' => 'totara/core/tests/fixtures/test_hook_watcher.php',
                // NOTE: default priority is 100, higher is called first
            ],
            [
                'hookname' => 'totara_core_test_hook',
                'callback' => array('\\totara_core_test_hook_watcher', 'listen1'),
                'includefile' => 'totara/core/tests/fixtures/test_hook_watcher.php',
                'priority' => 99,
            ],
            [
                'hookname' => 'totara_core_test_hook',
                'callback' => array('\\totara_core_test_hook_watcher', 'listen2'),
                'includefile' => 'totara/core/tests/fixtures/test_hook_watcher.php',
                'priority' => 101,
            ],
        ];

        \totara_core\hook\manager::phpunit_replace_watchers($watchers);

        $hook = new totara_core_test_hook();
        $this->assertSame(array(), $hook->info);
        $result = $hook->execute();
        $this->assertSame($result, $hook);
        $this->assertSame(array(2, 0, 1), $hook->info);
    }

    public function test_watcher_definition_problems() {
        require_once(__DIR__ . '/fixtures/test_hook.php');

        $watchers = array(
            array(
                'hookname' => 'totara_core_test_hook',
                'callback' => array('totara_core_test_hook_watcher', 'listenxxx'),
                'includefile' => 'totara/core/tests/fixtures/test_hook_watcher.php',
            ),
        );
        \totara_core\hook\manager::phpunit_replace_watchers($watchers);
        $hook = new totara_core_test_hook();
        $result = $hook->execute();
        $this->assertSame($hook, $result);
        $this->assertDebuggingCalled();

        $watchers = array(
            array(
                'hookname' => 'totara_core_test_hook',
                'callback' => 'listenxxxfdjklslkfdsjklfdsjkldsfkjldsfjkl',
            ),
        );
        \totara_core\hook\manager::phpunit_replace_watchers($watchers);
        $hook = new totara_core_test_hook();
        $result = $hook->execute();
        $this->assertSame($hook, $result);
        $this->assertDebuggingCalled();
    }

    public function test_watcher_exception_problems() {
        require_once(__DIR__ . '/fixtures/test_hook.php');

        // This is the format used in db/hooks.php files.
        $watchers = array(
            array(
                'hookname' => 'totara_core_test_hook',
                'callback' => array('totara_core_test_hook_watcher', 'listen2'),
                'includefile' => 'totara/core/tests/fixtures/test_hook_watcher.php',
            ),
            array(
                'hookname' => 'totara_core_test_hook',
                'callback' => array('totara_core_test_hook_watcher', 'listen3'),
                'includefile' => 'totara/core/tests/fixtures/test_hook_watcher.php',
                'priority' => 101,
            ),
            array(
                'hookname' => 'totara_core_test_hook',
                'callback' => array('\\totara_core_test_hook_watcher', 'listen1'),
                'includefile' => 'totara/core/tests/fixtures/test_hook_watcher.php',
                'priority' => 99,
            ),
        );

        \totara_core\hook\manager::phpunit_replace_watchers($watchers);

        $hook = new totara_core_test_hook();
        $this->assertSame(array(), $hook->info);
        $result = $hook->execute();
        $this->assertSame($result, $hook);
        $this->assertSame(array(3, 2, 1), $hook->info);
        $this->assertDebuggingCalled("Exception encountered in hook watcher 'array (\n  0 => 'totara_core_test_hook_watcher',\n  1 => 'listen3',\n)': some problem");
    }

    /**
     * Test all hook definitions in distro to make sure there are no typos.
     *
     * NOTE: this may fail for add-ons designed for future versions.
     */
    public function test_hook_use_in_definitions() {
        \totara_core\hook\manager::phpunit_reset();

        $watchers = \totara_core\hook\manager::phpunit_get_watchers();
        foreach ($watchers as $hookname => $unused) {
            $this->assertTrue(class_exists($hookname), 'Invalid hookname detected in hook watcher: ' . $hookname);
        }
    }
}
