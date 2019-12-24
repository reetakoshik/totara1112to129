<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2017 onwards Totara Learning Solutions LTD
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
 * @author Brendan Cox <brendan.cox@totaralearning.com>
 * @package core
 */

/**
 * Class core_command_executable_testcase
 *
 * Tests the behaviour of the \core\command\executable class.
 */
class core_command_executable_testcase extends advanced_testcase {

    /**
     * Stores the path to the phh cli for pcntl extension.
     * This gets explicitly set back to null in the tearDown method.
     * @var string
     */
    private $cfg_pcntl_phpclipath = null;

    /**
     * Containing path to an "executable file". We'll set it to something non-existent so that
     * if a command is somehow executed during a test (shouldn't happen) then nothing dangerous happens.
     * @return string
     */
    private function get_path_to_exec_file() {
        global $CFG;
        // This is a path for tests where we don't expect or want to execute anything.
        return $CFG->dirroot . '/lib/tests/notarealfile';
    }

    public function setUp() {
        parent::setUp();
        $this->resetAfterTest();
        global $CFG;

        if (isset($CFG->pcntl_phpclipath)) {
            $this->cfg_pcntl_phpclipath = $CFG->pcntl_phpclipath;
        }
    }

    public function tearDown() {
        global $CFG;

        if (isset($this->cfg_pcntl_phpclipath)) {
            $CFG->pcntl_phpclipath = $this->cfg_pcntl_phpclipath;
            $this->cfg_pcntl_phpclipath = null;
        } else {
            unset($CFG->pcntl_phpclipath);
        }

        // There are some static variables that may have been modified. Reset them to their
        // natural value prior to any further testing.
        \core\command\executable::can_use_pcntl(true);
        \core\command\executable::is_windows(true);
        $this->treat_as_cli();
        parent::tearDown();
    }

    /**
     * Set a core_command instance to act as though it is executed from the web interface.
     */
    private function treat_as_web_request() {
        $iswebrequest = new ReflectionProperty('\core\command\executable', 'iswebrequest');
        $iswebrequest->setAccessible(true);
        $iswebrequest->setValue(true);
    }

    /**
     * Set a core_command instance to act as though it is executed from the web interface.
     *
     * This should be the case by default when running phpunit, but given the danger of allowing
     * cli only commands to be executed via the web, we don't want any ambiguity.
     *
     */
    private function treat_as_cli() {
        $iswebrequest = new ReflectionProperty('\core\command\executable', 'iswebrequest');
        $iswebrequest->setAccessible(true);
        $iswebrequest->setValue(false);
    }

    /**
     * Enables or disables pcntl regardless of current environment and settings.
     *
     * Don't use this in tests that actually should execute pcntl scripts.
     *
     * @param bool $enable set to true to enable pcntl, and false to disable.
     */
    private function enable_fake_pcntl($enable) {
        global $CFG;

        if ($enable) {
            // Not a real bin as this won't actually be run.
            $CFG->pcntl_phpclipath = '/totaratest/path/to/php/bin';
        }

        $iswebrequest = new ReflectionProperty('\core\command\executable', 'canusepcntl');
        $iswebrequest->setAccessible(true);
        $iswebrequest->setValue($enable);
    }

    /**
     * In cases where we will actually be executing and want to know that we are testing with pcntl on, use this.
     *
     * If we want to enable it, it will skip the test if pcntl can't be tested
     * (which might be because we're on windows for example).
     */
    private function enable_real_pcntl($enable) {
        global $CFG;

        if (!$enable) {
            $this->enable_fake_pcntl(false);
            return;
        }

        if (!\core\command\executable::is_windows(true) and empty($CFG->pcntl_phpclipath)
            and defined('PHP_BINARY')) {
            $CFG->pcntl_phpclipath = PHP_BINARY;
        }

        if (!\core\command\executable::can_use_pcntl(true)) {
            // The test won't be valid.
            $this->markTestSkipped();
        }
    }

    /**
     * Assert that what will be executed on the command line is as expected.
     *
     * @param string $expectedcommand
     * @param \core\command\executable $obj_command
     */
    private function match_generated_command($expectedcommand, $obj_command) {
        $commandstring = phpunit_util::call_internal_method(
            $obj_command,
            'get_command',
            array(),
            '\core\command\executable'
        );
        $this->assertEquals($expectedcommand, $commandstring);
    }

    /**
     * Assert that what will be run using pcntl is as expected.
     *
     * @param string $expectedcontents
     * @param \core\command\executable $obj_command
     */
    private function math_pcntl_file_contents($expectedcontents, $obj_command) {
        $phpfile = phpunit_util::call_internal_method(
            $obj_command,
            'create_pcntl_file',
            array(),
            '\core\command\executable'
        );
        $phpcontents = file_get_contents($phpfile);

        $this->assertEquals(trim($expectedcontents), trim($phpcontents));
    }

    /**
     * Create a command that is whitelisted for web or cli and make sure it's not rejected.
     */
    public function test_allowed_command_generated() {
        global $CFG;
        $this->enable_fake_pcntl(false);

        $execpath = $this->get_path_to_exec_file();
        \set_config('pathtoclam', $execpath, 'antivirus_clamav');

        $command = new \core\command\executable($execpath);
        $this->match_generated_command(escapeshellarg($execpath), $command);
    }

    /**
     * Repeats test_allowed_command_generated using pcntl.
     */
    public function test_allowed_command_generated_pcntl() {
        global $CFG;
        $this->enable_fake_pcntl(true);

        $execpath = $this->get_path_to_exec_file();
        \set_config('pathtoclam', $execpath, 'antivirus_clamav');

        $command = new \core\command\executable($execpath);
        $expected = "<?php\n" . "pcntl_exec(" . escapeshellarg($execpath) . ", array (\n));";
        $this->math_pcntl_file_contents($expected, $command);
    }

    /**
     * Create a command that is not whitelisted for web or cli and make sure it is rejected.
     */
    public function test_empty_command_generates_exception() {
        $this->enable_fake_pcntl(false);
        try {
            $command = new \core\command\executable('');
            $this->fail('\core\command\exception not thrown as expected');
        } catch (\core\command\exception $e) {
            $exceptionmessage = $e->getMessage();
        }

        $this->assertFalse(isset($command));
        $this->assertEquals('Coding error detected, it must be fixed by a programmer: Action not allowed', $exceptionmessage);
    }

    /**
     * Create a command that is not whitelisted for web or cli and make sure it is rejected.
     */
    public function test_whitespaceonly_command_generates_exception() {
        $this->enable_fake_pcntl(false);
        try {
            $command = new \core\command\executable('  ');
            $this->fail('\core\command\exception not thrown as expected');
        } catch (\core\command\exception $e) {
            $exceptionmessage = $e->getMessage();
        }

        $this->assertFalse(isset($command));
        $this->assertEquals('Coding error detected, it must be fixed by a programmer: Action not allowed', $exceptionmessage);
    }

    /**
     * Create a command that is not whitelisted for web or cli and make sure it is rejected.
     */
    public function test_disallowed_command_generates_exception() {
        $this->enable_fake_pcntl(false);
        // We haven't added $this->get_path_to_exec_file() to any config variables. It should not be in the white list.
        try {
            $execpath = $this->get_path_to_exec_file();
            $command = new \core\command\executable($execpath);
            $this->fail('\core\command\exception not thrown as expected');
        } catch (\core\command\exception $e) {
            $exceptionmessage = $e->getMessage();
        }

        $this->assertFalse(isset($command));
        $this->assertEquals('Coding error detected, it must be fixed by a programmer: Action not allowed', $exceptionmessage);
    }

    /**
     * Disallowed commands should still be rejected when using pcntl.
     */
    public function test_disallowed_command_generates_exception_pcntl() {
        $this->enable_fake_pcntl(true);
        // We haven't added $this->get_path_to_exec_file() to any config variables. It should not be in the white list.
        try {
            $execpath = $this->get_path_to_exec_file();
            $command = new \core\command\executable($execpath);
            $this->fail('\core\command\exception not thrown as expected');
        } catch (\core\command\exception $e) {
            $exceptionmessage = $e->getMessage();
        }

        $this->assertFalse(isset($command));
        $this->assertEquals('Coding error detected, it must be fixed by a programmer: Action not allowed', $exceptionmessage);
    }

    /**
     * Ensure commands enabled for the web work as expected.
     */
    public function test_web_enabled_command_allowed_on_web() {
        global $CFG;
        $this->enable_fake_pcntl(false);

        $execpath = $this->get_path_to_exec_file();

        \set_config('pathtoclam', $execpath, 'antivirus_clamav');
        $command = new \core\command\executable($execpath);

        $this->treat_as_web_request();
        $this->match_generated_command(escapeshellarg($execpath), $command);
    }

    /**
     * Ensure commands enabled for the web work as expected when pcntl is enabled.
     */
    public function test_web_enabled_command_allowed_on_web_pcntl() {
        global $CFG;
        $this->enable_fake_pcntl(true);

        $execpath = $this->get_path_to_exec_file();

        \set_config('pathtoclam', $execpath, 'antivirus_clamav');
        $command = new \core\command\executable($execpath);

        $this->treat_as_web_request();
        $expected = "<?php\n" . "pcntl_exec(" . escapeshellarg($execpath) . ", array (\n));";
        $this->math_pcntl_file_contents($expected, $command);
    }

    /**
     * CLI-only commands should throw an exception when originating from a web request.
     */
    public function test_cli_only_command_generates_exception_on_web() {
        $this->enable_fake_pcntl(false);
        $this->treat_as_web_request();

        try {
            $command = new \core\command\executable('php');
            $this->fail('\core\command\exception not thrown as expected');
        } catch (\core\command\exception $e) {
            $exceptionmessage = $e->getMessage();
        }

        $this->assertFalse(isset($command));
        $this->assertEquals('Coding error detected, it must be fixed by a programmer: Action not allowed', $exceptionmessage);
    }

    /**
     * Enabling pcntl should not affect behaviour if CLI-only commands originate from
     * a web request.
     */
    public function test_cli_only_command_generates_exception_on_web_pcntl() {
        $this->enable_fake_pcntl(true);
        $this->treat_as_web_request();

        try {
            $command = new \core\command\executable('php');
            $this->fail('\core\command\exception not thrown as expected');
        } catch (\core\command\exception $e) {
            $exceptionmessage = $e->getMessage();
        }

        $this->assertFalse(isset($command));
        $this->assertEquals('Coding error detected, it must be fixed by a programmer: Action not allowed', $exceptionmessage);
    }

    /**
     * Web enabled commands can still be run via from CLI.
     */
    public function test_web_enabled_command_allowed_on_cli() {
        global $CFG;
        $this->enable_fake_pcntl(false);

        $execpath = $this->get_path_to_exec_file();

        \set_config('pathtoclam', $execpath, 'antivirus_clamav');
        $command = new \core\command\executable($execpath);

        $this->treat_as_cli();
        $this->match_generated_command(escapeshellarg($execpath), $command);
    }

    /**
     * Web enabled commands can still be run via from CLI and pcntl is enabled.
     */
    public function test_web_enabled_command_allowed_on_cli_pcntl() {
        global $CFG;
        $this->enable_fake_pcntl(true);

        $execpath = $this->get_path_to_exec_file();

        \set_config('pathtoclam', $execpath, 'antivirus_clamav');
        $command = new \core\command\executable($execpath);

        $this->treat_as_cli();

        $expected = "<?php\n" . "pcntl_exec(" . escapeshellarg($execpath) . ", array (\n));";
        $this->math_pcntl_file_contents($expected, $command);
    }

    /**
     * CLI-only commands should of course work from the CLI.
     */
    public function test_cli_only_command_allowed_on_cli() {
        $this->enable_fake_pcntl(false);
        $command = new \core\command\executable('php');

        $this->treat_as_cli();

        $this->match_generated_command(escapeshellarg('php'), $command);
    }

    /**
     * CLI-only commands should of course work from the CLI and pcntl is enabled.
     */
    public function test_cli_only_command_allowed_on_cli_pcntl() {
        $this->enable_fake_pcntl(true);
        $command = new \core\command\executable('php');

        $this->treat_as_cli();

        // We've defined out own test location of the php binary for these tests.
        $expected = "<?php\n" . "pcntl_exec(" . escapeshellarg('/totaratest/path/to/php/bin') . ", array (\n));";
        $this->math_pcntl_file_contents($expected, $command);
    }

    /**
     * Testing the use of $CFG->thirdpartexeclist for enabling via CLI only.
     */
    public function test_thirdpartyexeclist_enabled_for_cli_only() {
        global $CFG;
        $this->enable_fake_pcntl(false);
        $thirdpartyexec = '/totaratest/path/to/thirdparty/bin';

        $this->treat_as_cli();
        // Without adding to $CFG->thirdpartexeclist, we'll just make sure this pathname is rejected.
        try {
            $command = new \core\command\executable($thirdpartyexec);
            $this->fail('\core\command\exception not thrown as expected');
        } catch (\core\command\exception $e) {
            $exceptionmessage = $e->getMessage();
        }
        $this->assertFalse(isset($command));
        $this->assertEquals('Coding error detected, it must be fixed by a programmer: Action not allowed', $exceptionmessage);
        $exceptionmessage = null;

        // Now we add the path to the thirdparty list. So it has pathname in the key and bool which depends whether
        // it's enabled on the web as the value.
        $CFG->thirdpartyexeclist = array($thirdpartyexec => false);

        // We'll test as a web request first. We set the bool to false so it will fail.
        $this->treat_as_web_request();
        try {
            $command = new \core\command\executable($thirdpartyexec);
            $this->fail('\core\command\exception not thrown as expected');
        } catch (\core\command\exception $e) {
            $exceptionmessage = $e->getMessage();
        }
        $this->assertFalse(isset($command));
        $this->assertEquals('Coding error detected, it must be fixed by a programmer: Action not allowed', $exceptionmessage);
        $exceptionmessage = null;

        // Now create the command as if it was in a cli script.
        $this->treat_as_cli();
        $command = new \core\command\executable($thirdpartyexec);

        $this->match_generated_command(escapeshellarg($thirdpartyexec), $command);
    }

    /**
     * Testing the use of $CFG->thirdpartexeclist for enabling via CLI only with pcntl enabled.
     */
    public function test_thirdpartyexeclist_enabled_for_cli_only_pcntl() {
        global $CFG;
        $this->enable_fake_pcntl(true);
        $thirdpartyexec = '/totaratest/path/to/thirdparty/bin';

        $this->treat_as_cli();
        // Without adding to $CFG->thirdpartexeclist, we'll just make sure this pathname is rejected.
        try {
            $command = new \core\command\executable($thirdpartyexec);
            $this->fail('\core\command\exception not thrown as expected');
        } catch (\core\command\exception $e) {
            $exceptionmessage = $e->getMessage();
        }
        $this->assertFalse(isset($command));
        $this->assertEquals('Coding error detected, it must be fixed by a programmer: Action not allowed', $exceptionmessage);
        $exceptionmessage = null;

        // Now we add the path to the thirdparty list. So it has pathname in the key and bool which depends whether
        // it's enabled on the web as the value.
        $CFG->thirdpartyexeclist = array($thirdpartyexec => false);

        // We'll test as a web request first. We set the bool to false so it will fail.
        $this->treat_as_web_request();
        try {
            $command = new \core\command\executable($thirdpartyexec);
            $this->fail('\core\command\exception not thrown as expected');
        } catch (\core\command\exception $e) {
            $exceptionmessage = $e->getMessage();
        }
        $this->assertFalse(isset($command));
        $this->assertEquals('Coding error detected, it must be fixed by a programmer: Action not allowed', $exceptionmessage);
        $exceptionmessage = null;

        // Now create the command as if it was in a cli script.
        $this->treat_as_cli();
        $command = new \core\command\executable($thirdpartyexec);

        // We've defined out own test location of the php binary for these tests.
        $expected = "<?php\n" . "pcntl_exec(" . escapeshellarg($thirdpartyexec) . ", array (\n));";
        $this->math_pcntl_file_contents($expected, $command);
    }

    /**
     * Testing the use of $CFG->thirdpartexeclist for enabling for web requests.
     */
    public function test_thirdpartyexeclist_enabled_for_web() {
        global $CFG;
        $this->enable_fake_pcntl(false);
        $thirdpartyexec = '/totaratest/path/to/thirdparty/bin';

        $this->treat_as_web_request();
        // Without adding to $CFG->thirdpartexeclist, we'll just make sure this pathname is rejected.
        try {
            $command = new \core\command\executable($thirdpartyexec);
            $this->fail('\core\command\exception not thrown as expected');
        } catch (\core\command\exception $e) {
            $exceptionmessage = $e->getMessage();
        }
        $this->assertFalse(isset($command));
        $this->assertEquals('Coding error detected, it must be fixed by a programmer: Action not allowed', $exceptionmessage);
        $exceptionmessage = null;

        // Now we add the path to the thirdparty list. So it has pathname in the key and bool which depends whether
        // it's enabled on the web as the value.
        $CFG->thirdpartyexeclist = array($thirdpartyexec => true);

        // We'll test as a web request first. This works as we set the bool to true.
        $this->treat_as_web_request();
        $command = new \core\command\executable($thirdpartyexec);
        $this->match_generated_command(escapeshellarg($thirdpartyexec), $command);

        // Now create the command as if it was in a cli script. This also works.
        $this->treat_as_cli();
        $command = new \core\command\executable($thirdpartyexec);
        $this->match_generated_command(escapeshellarg($thirdpartyexec), $command);
    }

    /**
     * Testing the use of $CFG->thirdpartexeclist for enabling for web requests and pcntl is enabled.
     */
    public function test_thirdpartyexeclist_enabled_for_web_pcntl() {
        global $CFG;
        $this->enable_fake_pcntl(true);
        $thirdpartyexec = '/totaratest/path/to/thirdparty/bin';

        $this->treat_as_web_request();
        // Without adding to $CFG->thirdpartexeclist, we'll just make sure this pathname is rejected.
        try {
            $command = new \core\command\executable($thirdpartyexec);
            $this->fail('\core\command\exception not thrown as expected');
        } catch (\core\command\exception $e) {
            $exceptionmessage = $e->getMessage();
        }
        $this->assertFalse(isset($command));
        $this->assertEquals('Coding error detected, it must be fixed by a programmer: Action not allowed', $exceptionmessage);
        $exceptionmessage = null;

        // Now we add the path to the thirdparty list. So it has pathname in the key and bool which depends whether
        // it's enabled on the web as the value.
        $CFG->thirdpartyexeclist = array($thirdpartyexec => true);

        // We'll test as a web request first. We set the bool to false so it will fail.
        $this->treat_as_web_request();
        $command = new \core\command\executable($thirdpartyexec);
        $expected = "<?php\n" . "pcntl_exec(" . escapeshellarg($thirdpartyexec) . ", array (\n));";
        $this->math_pcntl_file_contents($expected, $command);

        // Now create the command as if it was in a cli script.
        $this->treat_as_cli();
        $command = new \core\command\executable($thirdpartyexec);
        $expected = "<?php\n" . "pcntl_exec(" . escapeshellarg($thirdpartyexec) . ", array (\n));";
        $this->math_pcntl_file_contents($expected, $command);
    }

    /**
     * Testing the use of $CFG->thirdpartexeclist for overriding the hardcoded true/false values
     * in the method for generating the whitelist.
     */
    public function test_thirdpartyexeclist_overrides_hardcoded() {
        global $CFG;

        $this->treat_as_web_request();

        try {
            $command = new \core\command\executable('php');
            $this->fail('\core\command\exception not thrown as expected');
        } catch (\core\command\exception $e) {
            $exceptionmessage = $e->getMessage();
        }

        // By default, 'php' should be prevented from being executable from a web request.
        $this->assertFalse(isset($command));
        $this->assertEquals('Coding error detected, it must be fixed by a programmer: Action not allowed', $exceptionmessage);

        // It's not recommended, but if someone really needs it, the setting for 'php' can be overridden.
        $CFG->thirdpartyexeclist = array('php' => true);

        $command = new \core\command\executable('php');
        // 'php' is allowed.
        $this->match_generated_command(escapeshellarg('php'), $command);

        // Let's try the other way. pathtoclam is allowed on the web, so we'll set a path for that.
        $execpath = $this->get_path_to_exec_file();
        \set_config('pathtoclam', $execpath, 'antivirus_clamav');
        $command = new \core\command\executable($execpath);
        $this->match_generated_command(escapeshellarg($execpath), $command);

        // Perhaps someone only ever wants it to be run on the cli.
        $CFG->thirdpartyexeclist = array($execpath => false);
        unset($command);

        try {
            $command = new \core\command\executable($execpath);
            $this->fail('\core\command\exception not thrown as expected');
        } catch (\core\command\exception $e) {
            $exceptionmessage = $e->getMessage();
        }

        // The pathtoclam was disallowed.
        $this->assertFalse(isset($command));
        $this->assertEquals('Coding error detected, it must be fixed by a programmer: Action not allowed', $exceptionmessage);
    }

    /**
     * Test commands generated when a range of options are added.
     *
     * Some of this functionality is tested in command_argument_testcase as well. Here we're mainly interested in
     * checking the parameters filter through and that the order of options is maintained.
     */
    public function test_multiple_standard_options_added() {
        global $CFG;
        $this->enable_fake_pcntl(false);
        $execpath = $this->get_path_to_exec_file();
        \set_config('pathtoclam', $execpath, 'antivirus_clamav');

        // Add several arguments, values and switches. Confirm each is escaped (or not) where expected and order is correct.
        $command = new \core\command\executable($execpath);
        $command->add_switch('-switch1');
        $command->add_argument('key1', 'value1');
        $command->add_value('value2');
        $command->add_argument('key2', 'value3', null, '=');

        // The components should stay in the same order.
        $expected = escapeshellarg($execpath);
        $expected .= ' -switch1';
        $expected .= ' key1 ' . escapeshellarg('value1') . ' ';
        $expected .= escapeshellarg('value2');
        $expected .= ' key2=' . escapeshellarg('value3');
        $this->match_generated_command($expected, $command);
    }

    /**
     * Test the pcntl php file generated when a range of options are added.
     *
     * Some of this functionality is tested in command_argument_testcase as well. Here we're mainly interested in
     * checking the parameters filter through and that the order of options is maintained.
     */
    public function test_multiple_standard_options_added_pcntl() {
        global $CFG;
        $this->enable_fake_pcntl(true);
        $execpath = $this->get_path_to_exec_file();
        \set_config('pathtoclam', $execpath, 'antivirus_clamav');

        // Add several arguments, values and switches. Confirm each is escaped (or not) where expected and order is correct.
        $command = new \core\command\executable($execpath);
        $command->add_switch('-switch1');
        $command->add_argument('key1', 'value1');
        $command->add_value('value2');
        $command->add_argument('key2', 'value3', null, '=');

        // In pcntl, where you'd have a space as the operator, e.g 'key operator', these become separate
        // array values, e.g. array('key', 'value').  If there is a non-space operator between then, you
        // have one array value, e.g. 'key=value' becomes array('key=value').
        $expected = "<?php\n" . "pcntl_exec(" . escapeshellarg($execpath) . ", array (\n";
        $expected .= "  0 => '-switch1',\n";
        $expected .= "  1 => 'key1',\n";
        $expected .= "  2 => 'value1',\n";
        $expected .= "  3 => 'value2',\n";
        $expected .= "  4 => 'key2=value3',\n";
        $expected .= "));";
        $this->math_pcntl_file_contents($expected, $command);
    }

    /**
     * Test the command generated when a range of options are added.
     */
    public function test_multiple_nonstandard_options_added() {
        global $CFG;
        $this->enable_fake_pcntl(false);
        $execpath = $this->get_path_to_exec_file();
        \set_config('pathtoclam', $execpath, 'antivirus_clamav');

        // This time we'll add a few non-standard parameters.
        // I can set the default operator to use for add_argument and it can basically be anything.
        $command = new \core\command\executable($execpath, '*#%');
        $command->add_switch('()#switch@*/*');
        // The operator in the constructor param will be put between the key and value here.
        $command->add_argument('key1', 'value1');
        // It won't be put in front of a value on its own though.
        $command->add_value('value2');
        // We can still overwrite with just a space if we want to.
        $command->add_argument('key2', 'value3', null, ' ');
        // But the operator in the constructor will be used the next time if we don't overwrite.
        $command->add_argument('key3', 'value4');

        // For values (including those added with add_argument), we can specify that they shouldn't be escaped.
        // Also note that setting operator to null (4th argument) will mean it still defaults to what was
        // defined in the constructor.
        $command->add_argument('key4', 'value5', null, null, false);
        $command->add_value('value6', null, false);

        $expected = escapeshellarg($execpath);
        $expected .= ' ()#switch@*/*';
        // There is no space between key and value if we've defined an operator.
        $expected .= ' key1*#%' . escapeshellarg('value1');
        $expected .= ' ' . escapeshellarg('value2');
        $expected .= ' key2 ' . escapeshellarg('value3');
        $expected .= ' key3*#%' . escapeshellarg('value4');
        $expected .= ' key4*#%value5';
        $expected .= ' value6';

        $this->match_generated_command($expected, $command);
    }

    /**
     * Test the pcntl php file generated when a range of options are added.
     */
    public function test_multiple_nonstandard_options_added_pcntl() {
        global $CFG;
        $this->enable_fake_pcntl(true);
        $execpath = $this->get_path_to_exec_file();
        \set_config('pathtoclam', $execpath, 'antivirus_clamav');

        // This time we'll add a few non-standard parameters.
        // I can set the default operator to use for add_argument and it can basically be anything.
        $command = new \core\command\executable($execpath, '*#%');
        $command->add_switch('()#switch@*/*');
        // The operator in the constructor param will be put between the key and value here.
        $command->add_argument('key1', 'value1');
        // It won't be put in front of a value on its own though.
        $command->add_value('value2');
        // We can still overwrite with just a space if we want to.
        $command->add_argument('key2', 'value3', null, ' ');
        // But the operator in the constructor will be used the next time if we don't overwrite.
        $command->add_argument('key3', 'value4');

        // Values aren't escaped when executed via pcntl anyway, so setting $escape_ifnopcntl
        // should end up making no difference.
        $command->add_argument('key4', 'value5', null, null, false);
        $command->add_value('value6', null, false);

        $expected = "<?php\n" . "pcntl_exec(" . escapeshellarg($execpath) . ", array (\n";
        $expected .= "  0 => '()#switch@*/*',\n";
        $expected .= "  1 => 'key1*#%value1',\n";
        $expected .= "  2 => 'value2',\n";
        $expected .= "  3 => 'key2',\n";
        $expected .= "  4 => 'value3',\n";
        $expected .= "  5 => 'key3*#%value4',\n";
        $expected .= "  6 => 'key4*#%value5',\n";
        $expected .= "  7 => 'value6',\n";
        $expected .= "));";
        $this->math_pcntl_file_contents($expected, $command);
    }

    /**
     * Invalid vales throw exceptions when supplied via the add_value method.
     *
     * These originate from the \core\command\argument class, so is tested more comprehensively in
     * the tests covering that.
     */
    public function test_add_value_invalid_values_throw_exceptions() {
        global $CFG;
        $execpath = $this->get_path_to_exec_file();
        \set_config('pathtoclam', $execpath, 'antivirus_clamav');

        // First test a valid value.
        $command = new \core\command\executable($execpath);
        $command->add_value(8, PARAM_INT);
        $expected = escapeshellarg($execpath) . ' ' . escapeshellarg(8);
        $this->match_generated_command($expected, $command);

        // Now the invalid value.
        unset($command);
        $command = new \core\command\executable($execpath);
        try {
            $command->add_value('eight', PARAM_INT);
            $this->fail('\core\command\exception not thrown as expected');
        } catch (\core\command\exception $e) {
            $exceptionmessage = $e->getMessage();
        }
        $this->assertEquals('Coding error detected, it must be fixed by a programmer: Invalid Value', $exceptionmessage);
    }

    /**
     * Invalid vales throw exceptions when supplied via the add_argument method.
     *
     * These originate from the \core\command\argument class, so is tested more comprehensively in
     * the tests covering that.
     */
    public function test_add_argument_invalid_values_throw_exceptions() {
        global $CFG;
        $execpath = $this->get_path_to_exec_file();
        \set_config('pathtoclam', $execpath, 'antivirus_clamav');

        // First test a valid value.
        $command = new \core\command\executable($execpath);
        $command->add_argument('number', 8, PARAM_INT);
        $expected = escapeshellarg($execpath) . ' number ' . escapeshellarg(8);
        $this->match_generated_command($expected, $command);

        // Now the invalid value.
        unset($command);
        $command = new \core\command\executable($execpath);
        try {
            $command->add_argument('number', 'eight', PARAM_INT);
            $this->fail('\core\command\exception not thrown as expected');
        } catch (\core\command\exception $e) {
            $exceptionmessage = $e->getMessage();
        }
        $this->assertEquals('Coding error detected, it must be fixed by a programmer: Invalid Value', $exceptionmessage);
    }

    /**
     * Make sure that when a non-whitelisted pathname is used, having params doesn't magically allow
     * something to get executed.
     */
    public function test_nonwhitelisted_still_not_allowed_with_options() {
        try {
            $command = new \core\command\executable('/totaratest/not/allowed/bin');
            // It should have actually failed by the time we get here, but testing anyway.
            $command->add_argument('key1', 'value1');
            $command->add_switch('switch1');
            $command->add_value('value2');
            $this->fail('\core\command\exception not thrown as expected');
        } catch (\core\command\exception $e) {
            $exceptionmessage = $e->getMessage();
        }
        $this->assertFalse(isset($command));
        $this->assertEquals('Coding error detected, it must be fixed by a programmer: Action not allowed', $exceptionmessage);
    }

    /**
     * Test actual command execution. Just uses 'php' as the executable.
     */
    public function test_execution_valid_command_pcntl_off() {
        $this->enable_real_pcntl(false);

        $phpfile = make_temp_directory('phpexectests') . '/test.php';
        file_put_contents($phpfile, '<?php die(0);');
        $phpcommand = new \core\command\executable('php');
        $phpcommand->add_value($phpfile, \core\command\argument::PARAM_FULLFILEPATH);
        $phpcommand->execute();
        $this->assertEmpty($phpcommand->get_output());
        $this->assertEquals(0, $phpcommand->get_return_status());
    }

    /**
     * Test actual command execution. Just uses 'php' as the executable.
     */
    public function test_execution_valid_command_pcntl_on() {
        $this->enable_real_pcntl(true);

        $phpfile = make_temp_directory('phpexectests') . '/test.php';
        file_put_contents($phpfile, '<?php die(0);');
        $phpcommand = new \core\command\executable('php');
        $phpcommand->add_value($phpfile, \core\command\argument::PARAM_FULLFILEPATH);
        $phpcommand->execute();
        $this->assertEmpty($phpcommand->get_output());
        $this->assertEquals(0, $phpcommand->get_return_status());
    }

    /**
     * Test actual command execution. Just uses 'php' as the executable.
     */
    public function test_execution_return_error_pcntl_off() {
        $this->enable_real_pcntl(false);

        $phpfile = make_temp_directory('phpexectests') . '/test.php';
        file_put_contents($phpfile, '<?php die(123);');
        $phpcommand = new \core\command\executable('php');
        $phpcommand->add_value($phpfile, \core\command\argument::PARAM_FULLFILEPATH);
        $phpcommand->execute();
        $this->assertEmpty($phpcommand->get_output());
        $this->assertEquals(123, $phpcommand->get_return_status());
    }

    /**
     * Test actual command execution. Just uses 'php' as the executable.
     */
    public function test_execution_return_error_pcntl_on() {
        $this->enable_real_pcntl(true);

        $phpfile = make_temp_directory('phpexectests') . '/test.php';
        file_put_contents($phpfile, '<?php die(123);');
        $phpcommand = new \core\command\executable('php');
        $phpcommand->add_value($phpfile, \core\command\argument::PARAM_FULLFILEPATH);
        $phpcommand->execute();
        $this->assertEmpty($phpcommand->get_output());
        $this->assertEquals(123, $phpcommand->get_return_status());
    }

    /**
     * Test actual command execution. Just uses 'php' as the executable.
     */
    public function test_execution_std_output_pcntl_off() {
        $this->enable_real_pcntl(false);

        $phpfile = make_temp_directory('phpexectests') . '/test.php';
        file_put_contents($phpfile, '<?php echo "some output"; die(0);');
        $phpcommand = new \core\command\executable('php');
        $phpcommand->add_value($phpfile, \core\command\argument::PARAM_FULLFILEPATH);
        $phpcommand->execute();
        $this->assertContains("some output", $phpcommand->get_output());
        $this->assertEquals(0, $phpcommand->get_return_status());
    }

    /**
     * Test actual command execution. Just uses 'php' as the executable.
     */
    public function test_execution_std_output_pcntl_on() {
        $this->enable_real_pcntl(true);

        $phpfile = make_temp_directory('phpexectests') . '/test.php';
        file_put_contents($phpfile, '<?php echo "some output"; die(0);');
        $phpcommand = new \core\command\executable('php');
        $phpcommand->add_value($phpfile, \core\command\argument::PARAM_FULLFILEPATH);
        $phpcommand->execute();
        $this->assertContains("some output", $phpcommand->get_output());
        $this->assertEquals(0, $phpcommand->get_return_status());
    }

    /**
     * Test actual command execution. Just uses 'php' as the executable.
     */
    public function test_execution_std_error_pcntl_off() {
        $this->enable_real_pcntl(false);

        $phpfile = make_temp_directory('phpexectests') . '/test.php';
        file_put_contents($phpfile, '<?php fwrite(STDERR, "error output"); die(0);');
        $phpcommand = new \core\command\executable('php');
        $phpcommand->add_value($phpfile, \core\command\argument::PARAM_FULLFILEPATH);
        $phpcommand->redirect_stderr_to_stdout(true);
        $phpcommand->execute();
        $this->assertContains("error output", $phpcommand->get_output());
        $this->assertEquals(0, $phpcommand->get_return_status());
    }

    /**
     * Test actual command execution. Just uses 'php' as the executable.
     */
    public function test_execution_std_error_pcntl_on() {
        $this->enable_real_pcntl(true);

        $phpfile = make_temp_directory('phpexectests') . '/test.php';
        file_put_contents($phpfile, '<?php fwrite(STDERR, "error output"); die(0);');
        $phpcommand = new \core\command\executable('php');
        $phpcommand->add_value($phpfile, \core\command\argument::PARAM_FULLFILEPATH);
        $phpcommand->redirect_stderr_to_stdout(true);
        $phpcommand->execute();
        $this->assertContains("error output", $phpcommand->get_output());
        $this->assertEquals(0, $phpcommand->get_return_status());
    }

    /**
     * Test actual command execution. Just uses 'php' as the executable.
     */
    public function test_execution_multiple_output_types_pcntl_off() {
        $this->enable_real_pcntl(false);

        $phpfile = make_temp_directory('phpexectests') . '/test.php';
        file_put_contents($phpfile, '<?php echo "some output\n"; fwrite(STDERR, "error output"); die(123);');
        $phpcommand = new \core\command\executable('php');
        $phpcommand->add_value($phpfile, \core\command\argument::PARAM_FULLFILEPATH);
        $phpcommand->redirect_stderr_to_stdout(true);
        $phpcommand->execute();
        $this->assertContains("some output", $phpcommand->get_output());
        $this->assertContains("error output", $phpcommand->get_output());
        $this->assertEquals(123, $phpcommand->get_return_status());
    }

    /**
     * Test actual command execution. Just uses 'php' as the executable.
     */
    public function test_execution_multiple_output_types_pcntl_on() {
        $this->enable_real_pcntl(true);

        $phpfile = make_temp_directory('phpexectests') . '/test.php';
        file_put_contents($phpfile, '<?php echo "some output\n";  fwrite(STDERR, "error output"); die(123);');
        $phpcommand = new \core\command\executable('php');
        $phpcommand->add_value($phpfile, \core\command\argument::PARAM_FULLFILEPATH);
        $phpcommand->redirect_stderr_to_stdout(true);
        $phpcommand->execute();
        $this->assertContains("some output", $phpcommand->get_output());
        $this->assertContains("error output", $phpcommand->get_output());
        $this->assertEquals(123, $phpcommand->get_return_status());
    }
}
