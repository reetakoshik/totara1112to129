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
 * @package filter_tex
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/filter/tex/lib.php');

class filter_tex_mimetex_testcase extends advanced_testcase {

    private $cfg_pcntl_phpclipath;
    private $executable;

    public function setUp() {
        parent::setUp();
        $this->resetAfterTest();
        global $CFG;

        // Skipping these tests as they will fail on some systems, e.g. CentOS, where the OS is
        // 64-bit but no 32-bit library has been installed. This is because the mimetex.linux
        // executable is currently compiled for 32-bit only.
        $this->markTestSkipped();

        if (isset($CFG->pcntl_phpclipath)) {
            $this->cfg_pcntl_phpclipath = $CFG->pcntl_phpclipath;
        }

        $this->executable = filter_tex_get_executable();
        if (!file_is_executable($this->executable)) {
            $this->markTestSkipped();
        }
    }

    public function tearDown() {
        global $CFG;

        $this->executable = null;
        if (isset($this->cfg_pcntl_phpclipath)) {
            $CFG->pcntl_phpclipath = $this->cfg_pcntl_phpclipath;
            $this->cfg_pcntl_phpclipath = null;
        } else {
            unset($CFG->pcntl_phpclipath);
        }
        // Restore any static variables to their natural state.
        \core\command\executable::can_use_pcntl(true);
        parent::tearDown();
    }

    private function enable_pcntl($enable) {
        global $CFG;

        if (!$enable) {
            // Force disabling and exit.
            $iswebrequest = new ReflectionProperty('\core\command\executable', 'canusepcntl');
            $iswebrequest->setAccessible(true);
            $iswebrequest->setValue(false);
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

    public function test_valid_command_pcntl_off() {
        $this->enable_pcntl(false);

        $command = new \core\command\executable($this->executable);
        $command->add_value('1');
        $command->execute();
        $this->assertContains('...**...', $command->get_output());
        $this->assertEquals(0, $command->get_return_status());
    }

    public function test_valid_command_pcntl_on() {
        $this->enable_pcntl(true);

        $command = new \core\command\executable($this->executable);
        $command->add_value('1');
        $command->execute();
        $this->assertContains('...**...', $command->get_output());
        $this->assertEquals(0, $command->get_return_status());

        // Test the pcntl files were deleted after use.
        $pcntlfiles = glob(make_temp_directory('pcntl') .  '/*');
        $this->assertEquals(0, count($pcntlfiles));
    }

    public function test_redirect_stderr_pcntl_off() {
        $this->enable_pcntl(false);

        $command = new \core\command\executable($this->executable);
        $command->add_value('1');
        $command->redirect_stderr_to_stdout(true);
        $command->execute();
        $this->assertContains('...**...', $command->get_output());
        $this->assertEquals(0, $command->get_return_status());
    }

    public function test_redirect_stderr_pcntl_on() {
        $this->enable_pcntl(true);

        $command = new \core\command\executable($this->executable);
        $command->add_value('1');
        $command->redirect_stderr_to_stdout(true);
        $command->execute();
        $this->assertContains('...**...', $command->get_output());
        $this->assertEquals(0, $command->get_return_status());

        // Test the pcntl files were deleted after use.
        $pcntlfiles = glob(make_temp_directory('pcntl') .  '/*');
        $this->assertEquals(0, count($pcntlfiles));
    }
}