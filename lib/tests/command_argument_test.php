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
 * Class core_command_argument_testcase
 *
 * Tests the behaviour of the \core\command\argument class.
 */
class core_command_argument_testcase extends advanced_testcase {

    /**
     * Stores the path to the phh cli for pcntl extension.
     * This gets explicitly set back to null in the tearDown method.
     * @var string
     */
    private $cfg_pcntl_phpclipath;

    public function setUp() {
        parent::setUp();
        $this->resetAfterTest();
        global $CFG;

        if (isset($CFG->pcntl_phpclipath)) {
            $this->cfg_pcntl_phpclipath = $CFG->pcntl_phpclipath;
        }

        // We'll default to no pcntl. We'll change this for each test where we want it.
        $this->enable_pcntl(false);
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
        parent::tearDown();
    }

    /**
     * Enables or disables pcntl regardless of current environment and settings.
     *
     * @param bool $enable set to true to enable pcntl, and false to disable.
     */
    private function enable_pcntl($enable) {
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
     * Test the set_key() method.
     */
    public function test_set_key() {
        $argument = new \core\command\argument();
        $argument->set_key('testkey');
        $this->assertEquals('testkey', $argument->get_argument_string());
        $this->assertEquals(array('testkey'), $argument->get_argument_array());
    }

    /**
     * Test the set_operator() method with something other than a ' ' (space).
     */
    public function test_set_operator_notspace() {
        $argument = new \core\command\argument();
        $argument->set_key('testkey');
        $argument->set_operator('=');

        // The operator is not used when there is not both a key and value.
        $this->assertEquals('testkey', $argument->get_argument_string());
        $this->assertEquals(array('testkey'), $argument->get_argument_array());

        // Not escaping the value so we get the same result on each OS.
        $argument->set_value('testvalue', PARAM_ALPHA, false);

        $this->assertEquals('testkey=testvalue', $argument->get_argument_string());
        $this->assertEquals(array('testkey=testvalue'), $argument->get_argument_array());
    }

    /**
     * Test the set_operator() method using a ' ' as its value.
     */
    public function test_set_operator_withspace() {
        $argument = new \core\command\argument();
        $argument->set_key('testkey');
        $argument->set_operator(' ');

        // The operator is not used when there is not both a key and value.
        $this->assertEquals('testkey', $argument->get_argument_string());
        $this->assertEquals(array('testkey'), $argument->get_argument_array());

        // Not escaping the value so we get the same result on each OS.
        $argument->set_value('testvalue', PARAM_ALPHA, false);

        $this->assertEquals('testkey testvalue', $argument->get_argument_string());
        $this->assertEquals(array('testkey', 'testvalue'), $argument->get_argument_array());
    }

    /**
     * Testing set_operator with an empty string '' (no space).
     */
    public function test_set_operator_emptystring() {
        $argument = new \core\command\argument();
        $argument->set_key('testkey');
        $argument->set_operator('');

        // The operator is not used when there is not both a key and value.
        $this->assertEquals('testkey', $argument->get_argument_string());
        $this->assertEquals(array('testkey'), $argument->get_argument_array());

        // Not escaping the value so we get the same result on each OS.
        $argument->set_value('testvalue', PARAM_ALPHA, false);

        $this->assertEquals('testkeytestvalue', $argument->get_argument_string());
        $this->assertEquals(array('testkeytestvalue'), $argument->get_argument_array());
    }

    /**
     * Test set_value(). Empty strings are not allowed.
     */
    public function test_set_value_emptystring() {
        $argument = new \core\command\argument();
        try {
            $argument->set_value('', PARAM_ALPHANUM, false);
            $this->fail('\core\command\exception not thrown as expected');
        } catch (\core\command\exception $e) {
            $exceptionmessage = $e->getMessage();
        }
        $this->assertEmpty($argument->get_argument_string());
        $this->assertEquals('Coding error detected, it must be fixed by a programmer: Invalid value', $exceptionmessage);
    }

    /**
     * Test set_value(). False is not allowed.
     */
    public function test_set_value_false() {
        $argument = new \core\command\argument();
        try {
            $argument->set_value(false);
            $this->fail('\core\command\exception not thrown as expected');
        } catch (\core\command\exception $e) {
            $exceptionmessage = $e->getMessage();
        }
        $this->assertEmpty($argument->get_argument_string());
        $this->assertEquals('Coding error detected, it must be fixed by a programmer: Invalid value', $exceptionmessage);
    }

    /**
     * Test set_value(). Zero is the only 'empty' value allowed.
     */
    public function test_set_value_zero() {
        // Zero is 'empty' but is a valid value.
        $argument = new \core\command\argument();
        $argument->set_value(0, PARAM_ALPHANUM, false);
        $this->assertEquals('0', $argument->get_argument_string());
        $this->assertEquals(array('0'), $argument->get_argument_array());
    }

    /**
     * By default, values can contain alphanumeric characters and underscores.
     */
    public function test_set_value_default_valid() {
        $argument = new \core\command\argument();
        $argument->set_value('test_value123');
        $this->assertEquals(escapeshellarg('test_value123'), $argument->get_argument_string());
        $this->assertEquals(array(escapeshellarg('test_value123')), $argument->get_argument_array());
    }

    /**
     * Spaces are not allowed in values by default.
     */
    public function test_set_value_default_withspace() {
        $argument = new \core\command\argument();
        try {
            $argument->set_value('test value123');
            $this->fail('\core\command\exception not thrown as expected');
        } catch  (\core\command\exception $e) {
            $exceptionmessage = $e->getMessage();
        }
        $this->assertEmpty($argument->get_argument_string());
        $this->assertEmpty($argument->get_argument_array());
        $this->assertEquals('Coding error detected, it must be fixed by a programmer: Invalid value', $exceptionmessage);
    }

    /**
     * Test the use of PARAM_FULLFILEPATH with set_value.
     */
    public function test_set_value_filepath_valid() {
        global $CFG;

        $argument = new \core\command\argument();
        // Get a valid pathname.
        $filelocation = $CFG->dirroot . '/lib/tests/fixture/empty.txt';
        $argument->set_value($filelocation, \core\command\argument::PARAM_FULLFILEPATH, false);
        $this->assertEquals($filelocation, $argument->get_argument_string());
        $this->assertEquals(array($filelocation), $argument->get_argument_array());
    }

    /**
     * Test the use of PARAM_FULLFILEPATH with set_value.
     */
    public function test_set_value_filepath_invalid() {
        $argument = new \core\command\argument();
        $filelocation = '/invalid/path/name';

        try {
            $argument->set_value($filelocation, \core\command\argument::PARAM_FULLFILEPATH, false);
            $this->fail('\core\command\exception not thrown as expected');
        } catch  (\core\command\exception $e) {
            $exceptionmessage = $e->getMessage();
        }
        $this->assertEmpty($argument->get_argument_string());
        $this->assertEmpty($argument->get_argument_array());
        $this->assertEquals('Coding error detected, it must be fixed by a programmer: Invalid Filepath Value', $exceptionmessage);
    }

    /**
     * Test use of regex in validating values.
     */
    public function test_set_value_regex_startend_match() {
        $argument = new \core\command\argument();
        $regex = '/^[a-b][0-5]$/';
        $argument->set_value('a4', $regex, false);
        $this->assertEquals('a4', $argument->get_argument_string());
        $this->assertEquals(array('a4'), $argument->get_argument_array());
    }

    /**
     * Test use of regex in validating values.
     */
    public function test_set_value_regex_startend_nomatch() {
        $argument = new \core\command\argument();
        $regex = '/^[a-b][0-5]$/';
        try {
            $argument->set_value('c6', $regex, false);
            $this->fail('\core\command\exception not thrown as expected');
        } catch  (\core\command\exception $e) {
            $exceptionmessage = $e->getMessage();
        }
        $this->assertEmpty($argument->get_argument_string());
        $this->assertEmpty($argument->get_argument_array());
        $this->assertEquals('Coding error detected, it must be fixed by a programmer: Invalid value', $exceptionmessage);
    }

    /**
     * Test use of regex in validating values.
     */
    public function test_set_value_regex_anyposition() {
        $argument = new \core\command\argument();
        $regex = '/[a-b][0-5]/';
        try {
            $argument->set_value('a4', $regex, false);
            $this->fail('\moodle_exception not thrown as expected');
        } catch  (\moodle_exception $e) {
            $exceptionmessage = $e->getMessage();
        }
        $this->assertEmpty($argument->get_argument_string());
        $this->assertEmpty($argument->get_argument_array());
        // The regex was not valid, so fell through to
        $this->assertEquals('Coding error detected, it must be fixed by a programmer: Invalid param type', $exceptionmessage);
    }

    /**
     * Test use of regex in validating values.
     */
    public function test_set_value_regex_nostart() {
        $argument = new \core\command\argument();
        $regex = '/[a-b][0-5]$/';
        try {
            $argument->set_value('a4', $regex, false);
            $this->fail('\moodle_exception not thrown as expected');
        } catch  (\moodle_exception $e) {
            $exceptionmessage = $e->getMessage();
        }
        $this->assertEmpty($argument->get_argument_string());
        $this->assertEmpty($argument->get_argument_array());
        // The regex was not valid, so fell through to
        $this->assertEquals('Coding error detected, it must be fixed by a programmer: Invalid param type', $exceptionmessage);
    }

    /**
     * Test use of regex in validating values.
     */
    public function test_set_value_regex_noend() {
        $argument = new \core\command\argument();
        $regex = '/^[a-b][0-5]/';
        try {
            $argument->set_value('a4', $regex, false);
            $this->fail('\moodle_exception not thrown as expected');
        } catch  (\moodle_exception $e) {
            $exceptionmessage = $e->getMessage();
        }
        $this->assertEmpty($argument->get_argument_string());
        $this->assertEmpty($argument->get_argument_array());
        // The regex was not valid, so fell through to
        $this->assertEquals('Coding error detected, it must be fixed by a programmer: Invalid param type', $exceptionmessage);
    }

    /**
     * Test use of regex in validating values.
     *
     * Case-insensitive modifier is allowed.
     */
    public function test_set_value_regex_modifiers_i() {
        $argument = new \core\command\argument();
        $regex = '/^[a-b][0-5]$/i';
        $argument->set_value('A4', $regex, false);
        $this->assertEquals('A4', $argument->get_argument_string());
        $this->assertEquals(array('A4'), $argument->get_argument_array());
    }

    /**
     * Test use of regex in validating values.
     *
     * Dot-all modifier is allowed.
     */
    public function test_set_value_regex_modifiers_s() {
        $argument = new \core\command\argument();
        $regex = '/^[a-b][0-5].+$/s';
        $argument->set_value("a4\n", $regex, false);
        $this->assertEquals("a4\n", $argument->get_argument_string());
        $this->assertEquals(array("a4\n"), $argument->get_argument_array());
    }

    /**
     * Test use of regex in validating values.
     *
     * Multi-line modifier is not allowed.
     */
    public function test_set_value_regex_modifiers_m() {
        $argument = new \core\command\argument();
        $regex = '/^abc$/m';
        try {
            // Here we'll try a safe example of what the above regex will permit.
            // But it's important to realise that the m modifier treats the ^ and $ as start and end of
            // the lines. Meaning "a2\n;Evil Command" would be allowed.
            $argument->set_value("a2\nb4", $regex, false);
            $this->fail('\moodle_exception not thrown as expected.');
        } catch (\moodle_exception $e) {
            $exceptionmessage = $e->getMessage();
        }
        $this->assertEmpty($argument->get_argument_string());
        $this->assertEmpty($argument->get_argument_array());
        // The regex was not valid, so fell through to
        $this->assertEquals('Coding error detected, it must be fixed by a programmer: Invalid param type', $exceptionmessage);
    }

    /**
     * Test use of param types in validating values.
     */
    public function test_set_value_paramtype_int_valid() {
        $argument = new \core\command\argument();
        $argument->set_value(7, PARAM_INT, false);
        $this->assertEquals('7', $argument->get_argument_string());
        $this->assertEquals(array('7'), $argument->get_argument_array());
    }

    /**
     * Test use of param types in validating values.
     */
    public function test_set_value_paramtype_int_invalid() {
        $argument = new \core\command\argument();
        try {
            $argument->set_value('7a', PARAM_INT, false);
            $this->fail('\core\command\exception not thrown as expected');
        } catch  (\core\command\exception $e) {
            $exceptionmessage = $e->getMessage();
        }
        $this->assertEmpty($argument->get_argument_string());
        $this->assertEmpty($argument->get_argument_array());
        $this->assertEquals('Coding error detected, it must be fixed by a programmer: Invalid Value', $exceptionmessage);
    }

    /**
     * Test use of param types in validating values.
     */
    public function test_set_value_paramtype_url_valid() {
        $argument = new \core\command\argument();
        $argument->set_value('http://example.com', PARAM_URL, false);
        $this->assertEquals('http://example.com', $argument->get_argument_string());
        $this->assertEquals(array('http://example.com'), $argument->get_argument_array());
    }

    /**
     * Test use of param types in validating values.
     */
    public function test_set_value_paramtype_url_invalid() {
        $argument = new \core\command\argument();
        try {
            $argument->set_value('file:///etc/importantfile.txt', PARAM_URL, false);
            $this->fail('\core\command\exception not thrown as expected');
        } catch  (\core\command\exception $e) {
            $exceptionmessage = $e->getMessage();
        }
        $this->assertEmpty($argument->get_argument_string());
        $this->assertEmpty($argument->get_argument_array());
        $this->assertEquals('Coding error detected, it must be fixed by a programmer: Invalid Value', $exceptionmessage);
    }

    /**
     * Test use of param types in validating values.
     */
    public function test_set_value_paramtype_unknown() {
        $argument = new \core\command\argument();
        try {
            $argument->set_value('a5', 'notaparamtype', false);
            $this->fail('\moodle_exception not thrown as expected');
        } catch  (\moodle_exception $e) {
            $exceptionmessage = $e->getMessage();
        }
        $this->assertEmpty($argument->get_argument_string());
        $this->assertEmpty($argument->get_argument_array());
        $this->assertContains('error/unknownparamtype', $exceptionmessage);
    }

    /**
     * Test turning escaping on or off based on pcntl.
     */
    public function test_set_value_doescape_pcntl_off() {
        $argument = new \core\command\argument();
        $testvalue = 'test $%^ #5 "  \' \\';
        $argument->set_value($testvalue, PARAM_RAW, true);
        $this->assertEquals(escapeshellarg($testvalue), $argument->get_argument_string());
        $this->assertEquals(array(escapeshellarg($testvalue)), $argument->get_argument_array());
    }

    /**
     * Test turning escaping on or off based on pcntl.
     */
    public function test_set_value_doescape_pcntl_on() {
        $this->enable_pcntl(true);

        $argument = new \core\command\argument();
        $testvalue = 'test $%^ #5 "  \' \\';
        $argument->set_value($testvalue, PARAM_RAW, true);
        $this->assertEquals($testvalue, $argument->get_argument_string());
        $this->assertEquals(array($testvalue), $argument->get_argument_array());
    }

    /**
     * Test turning escaping on or off based on pcntl.
     */
    public function test_set_value_dontescape_pcntl_off() {
        $argument = new \core\command\argument();
        $testvalue = 'test $%^ #5 "  \' \\';
        $argument->set_value($testvalue, PARAM_RAW, false);
        $this->assertEquals($testvalue, $argument->get_argument_string());
        $this->assertEquals(array($testvalue), $argument->get_argument_array());
    }

    /**
     * Test turning escaping on or off based on pcntl.
     */
    public function test_set_value_dontescape_pcntl_on() {
        $this->enable_pcntl(true);

        $argument = new \core\command\argument();
        $testvalue = 'test $%^ #5 "  \' \\';
        $argument->set_value($testvalue, PARAM_RAW, false);
        $this->assertEquals($testvalue, $argument->get_argument_string());
        $this->assertEquals(array($testvalue), $argument->get_argument_array());
    }

    /**
     * get_argument_string has been tested in many of the situations above. But this tests
     * the output when no keys or values have been supplied.
     */
    public function test_get_argument_string_empty() {
        $argument = new \core\command\argument();
        $this->assertEquals('', $argument->get_argument_string());
    }

    /**
     * get_argument_string has been tested in many of the situations above. But this tests
     * the output when no keys or values have been supplied.
     */
    public function test_get_argument_array_empty() {
        $argument = new \core\command\argument();
        $this->assertEquals(array(), $argument->get_argument_array());
    }

    /**
     * Test the validate_full_filepath() method.
     */
    public function test_validate_full_filepath_dataroot() {
        global $CFG;

        $pathname = $CFG->dataroot . '/some/directory/somefile.txt';

        $success = false;
        try {
            \core\command\argument::validate_full_filepath($pathname);
            $success = true;
        } catch (\coding_exception $e) {
            $this->fail('Validation of an allowed file path failed with exception:' . $e->getMessage());
        }
        $this->assertTrue($success);
    }

    /**
     * Test the validate_full_filepath() method.
     */
    public function test_validate_full_filepath_localcachedir() {
        global $CFG;

        $pathname = $CFG->localcachedir . '/some/directory/somefile.txt';

        $success = false;
        try {
            \core\command\argument::validate_full_filepath($pathname);
            $success = true;
        } catch (\coding_exception $e) {
            $this->fail('Validation of an allowed file path failed with exception:' . $e->getMessage());
        }
        $this->assertTrue($success);
    }

    /**
     * Test the validate_full_filepath() method.
     */
    public function test_validate_full_filepath_tempdir() {
        global $CFG;

        $pathname = $CFG->tempdir . '/some/directory/somefile.txt';

        $success = false;
        try {
            \core\command\argument::validate_full_filepath($pathname);
            $success = true;
        } catch (\coding_exception $e) {
            $this->fail('Validation of an allowed file path failed with exception:' . $e->getMessage());
        }
        $this->assertTrue($success);
    }

    /**
     * Test the validate_full_filepath() method.
     */
    public function test_validate_full_filepath_cachedir() {
        global $CFG;

        $pathname = $CFG->cachedir . '/some/directory/somefile.txt';

        $success = false;
        try {
            \core\command\argument::validate_full_filepath($pathname);
            $success = true;
        } catch (\coding_exception $e) {
            $this->fail('Validation of an allowed file path failed with exception:' . $e->getMessage());
        }
        $this->assertTrue($success);
    }

    /**
     * Test the validate_full_filepath() method.
     */
    public function test_validate_full_filepath_dirroot() {
        global $CFG;

        $pathname = $CFG->dirroot . '/some/directory/somefile.txt';

        $success = false;
        try {
            \core\command\argument::validate_full_filepath($pathname);
            $success = true;
        } catch (\coding_exception $e) {
            $this->fail('Validation of an allowed file path failed with exception:' . $e->getMessage());
        }
        $this->assertTrue($success);
    }

    /**
     * Test the validate_full_filepath() method.
     */
    public function test_validate_full_filepath_other_path() {
        $pathname = '/nonexistent/dir/path/to/file.txt';

        $success = false;
        try {
            \core\command\argument::validate_full_filepath($pathname);
            $success = true; // Still doing this to represent inverse of what the positive tests were doing.
            $this->fail('\core\command\exception not thrown as expected');
        } catch (\core\command\exception $e) {
            $exceptionmessage = $e->getMessage();
        }
        $this->assertFalse($success);
        $this->assertEquals('Coding error detected, it must be fixed by a programmer: Invalid Filepath Value', $exceptionmessage);
    }

    /**
     * Test the validate_full_filepath() method.
     */
    public function test_validate_full_filepath_dirroot_appended() {
        global $CFG;

        // This might give you /var/www/sitedata_other/file.txt for example.
        $pathname = $CFG->dirroot .'_other/file.txt';

        $success = false;
        try {
            \core\command\argument::validate_full_filepath($pathname);
            $success = true;
            $this->fail('\core\command\exception not thrown as expected');
        } catch (\core\command\exception $e) {
            $exceptionmessage = $e->getMessage();
        }
        $this->assertFalse($success);
        $this->assertEquals('Coding error detected, it must be fixed by a programmer: Invalid Filepath Value', $exceptionmessage);
    }

    /**
     * Test the validate_full_filepath() method.
     */
    public function test_validate_full_filepath_dirroot_as_file() {
        global $CFG;

        // This might give you /var/www/sitedata.txt for example.
        $pathname = $CFG->dirroot .'.txt';

        $success = false;
        try {
            \core\command\argument::validate_full_filepath($pathname);
            $success = true;
            $this->fail('\core\command\exception not thrown as expected');
        } catch (\core\command\exception $e) {
            $exceptionmessage = $e->getMessage();
        }
        $this->assertFalse($success);
        $this->assertEquals('Coding error detected, it must be fixed by a programmer: Invalid Filepath Value', $exceptionmessage);
    }

    /**
     * Test the validate_full_filepath() method.
     */
    public function test_validate_full_filepath_invalid_param_path() {
        global $CFG;

        $pathname = $CFG->dirroot .'/dir/../../../secrets.txt';

        $success = false;
        try {
            \core\command\argument::validate_full_filepath($pathname);
            $success = true;
            $this->fail('\core\command\exception not thrown as expected');
        } catch (\core\command\exception $e) {
            $exceptionmessage = $e->getMessage();
        }
        $this->assertFalse($success);
        $this->assertEquals('Coding error detected, it must be fixed by a programmer: Invalid Filepath Value', $exceptionmessage);
    }

    /**
     * Test the validate_full_filepath() method.
     */
    public function test_validate_full_filepath_windows_rewrite() {
        global $CFG;

        $pathname = $CFG->dirroot . '\some\directory\somefile.txt';

        $success = false;
        try {
            \core\command\argument::validate_full_filepath($pathname);
            $success = true;
        } catch (\coding_exception $e) {
            $this->fail('Validation of an allowed file path failed with exception:' . $e->getMessage());
        }
        $this->assertTrue($success);
    }
}
