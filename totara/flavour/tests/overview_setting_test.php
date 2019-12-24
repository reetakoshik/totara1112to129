<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2015 onwards Totara Learning Solutions LTD
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
 * @package totara_flavour
 */

use \totara_flavour\overview;
use \totara_flavour\helper;

defined('MOODLE_INTERNAL') || die();

/**
 * Tests flavour overview setting class
 */
class totara_flavour_overview_setting_testcase extends advanced_testcase {

    /**
     * True if the test flavour has been installed and is available
     * @var bool
     */
    protected $testflavouravailable = false;

    protected function setUp() {
        global $CFG;
        require_once($CFG->libdir . '/adminlib.php');
        parent::setUp();
        $this->resetAfterTest();
        // When/if we have a second core flavour we should convert our tests to use that instead of the test flavour.
        // The test flavour is available at TL-7812
        $this->testflavouravailable = file_exists("$CFG->dirroot/totara/flavour/flavours/test/classes/definition.php");
    }

    protected function tearDown() {
        global $CFG;
        // Make sure the $ADMIN static is not messed up by our flavours.
        unset($CFG->forceflavour);
        unset($CFG->showflavours);
        unset($CFG->currentflavour);
        admin_get_root(true, false);
        $this->testflavouravailable = null;
        parent::tearDown();
    }

    public function test_expected_defaults() {
        global $CFG;

        // Verify default settings.
        $this->assertObjectNotHasAttribute('forceflavour', $CFG);
        $this->assertObjectNotHasAttribute('showflavours', $CFG);
        $this->assertObjectNotHasAttribute('currentflavour', $CFG);
        $this->assertEquals(TOTARA_SHOWFEATURE, get_config('moodle', 'enableappraisals'));

        // We need some flavours for testing.
        $this->assertFileExists("$CFG->dirroot/totara/flavour/flavours/enterprise/classes/definition.php");
        if ($this->testflavouravailable) {
            $this->assertFileExists("$CFG->dirroot/totara/flavour/flavours/test/classes/definition.php");
        }
    }

    public function test_strings() {

        if (!$this->testflavouravailable) {
            // If you get this and want to test the overview strings for flavours you must install the test plugin at TL-7812.
            $this->markTestSkipped('You must install the test flavour in order to test the overview strings functionality.');
            return true; // Not needed but keeps it clear.
        }

        $this->setAdminUser();

        helper::set_active_flavour('flavour_test');
        $overview = new overview();
        $this->assertCount(19, $overview->settings);

        foreach ($overview->settings as $setting) {
            $name = $setting->get_name();
            $this->assertDebuggingNotCalled();
            $this->assertNotEmpty($name);

            $desc = $setting->get_description();
            $this->assertDebuggingNotCalled();
            $this->assertNotEmpty($desc);
        }
    }

    public function test_current_flavour() {
        global $CFG;

        if (!$this->testflavouravailable) {
            // If you get this and want to test the current flavour overview you must install the test plugin at TL-7812.
            $this->markTestSkipped('You must install the test flavour in order to test the overview current flavour functionality.');
            return true; // Not needed but keeps it clear.
        }

        $this->setAdminUser();

        helper::set_active_flavour('flavour_test');

        $overview = new overview();
        $setting = $overview->settings['moodle|enablegoals'];
        $this->assertInstanceOf('totara_flavour\\overview_setting', $setting);
        $this->assertSame('enablegoals', $setting->name);
        $this->assertSame('moodle', $setting->component);
        $this->assertEquals(TOTARA_DISABLEFEATURE, $setting->currentvalue);
        $this->assertTrue($setting->is_prohibited('flavour_test'));
        $this->assertFalse($setting->is_prohibited('flavour_enterprise'));
        $this->assertFalse($setting->is_on());
        $this->assertFalse($setting->is_set_in_configphp());

        set_config('enablegoals', TOTARA_SHOWFEATURE);
        $overview = new overview();
        $setting = $overview->settings['moodle|enablegoals'];
        $this->assertInstanceOf('totara_flavour\\overview_setting', $setting);
        $this->assertSame('enablegoals', $setting->name);
        $this->assertSame('moodle', $setting->component);
        $this->assertEquals(TOTARA_SHOWFEATURE, $setting->currentvalue);
        $this->assertTrue($setting->is_prohibited('flavour_test'));
        $this->assertFalse($setting->is_prohibited('flavour_enterprise'));
        $this->assertTrue($setting->is_on());
        $this->assertFalse($setting->is_set_in_configphp());

        set_config('enablegoals', TOTARA_HIDEFEATURE);
        $overview = new overview();
        $setting = $overview->settings['moodle|enablegoals'];
        $this->assertInstanceOf('totara_flavour\\overview_setting', $setting);
        $this->assertSame('enablegoals', $setting->name);
        $this->assertSame('moodle', $setting->component);
        $this->assertEquals(TOTARA_HIDEFEATURE, $setting->currentvalue);
        $this->assertTrue($setting->is_prohibited('flavour_test'));
        $this->assertFalse($setting->is_prohibited('flavour_enterprise'));
        $this->assertTrue($setting->is_on());
        $this->assertFalse($setting->is_set_in_configphp());

        set_config('enablegoals', TOTARA_DISABLEFEATURE);
        $CFG->enablegoals = (string)TOTARA_SHOWFEATURE;
        $CFG->config_php_settings['enablegoals'] = $CFG->enablegoals;
        $overview = new overview();
        $setting = $overview->settings['moodle|enablegoals'];
        $this->assertInstanceOf('totara_flavour\\overview_setting', $setting);
        $this->assertSame('enablegoals', $setting->name);
        $this->assertSame('moodle', $setting->component);
        $this->assertEquals(TOTARA_SHOWFEATURE, $setting->currentvalue);
        $this->assertTrue($setting->is_prohibited('flavour_test'));
        $this->assertFalse($setting->is_prohibited('flavour_enterprise'));
        $this->assertTrue($setting->is_on());
        $this->assertTrue($setting->is_set_in_configphp());
    }
}
