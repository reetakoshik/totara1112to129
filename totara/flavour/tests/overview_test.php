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
 * Tests flavour overview class
 */
class totara_flavour_overview_testcase extends advanced_testcase {

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

    public function test_current_flavour() {
        global $CFG;
        $this->setAdminUser();

        $overview = new overview();
        $this->assertNull($overview->currentflavour);

        if ($this->testflavouravailable) {
            $CFG->forceflavour = 'test';
            helper::set_active_flavour('flavour_test');
            $overview = new overview();
            $this->assertSame('flavour_test', $overview->currentflavour);
        }

        unset($CFG->forceflavour);
        helper::set_active_flavour('flavour_enterprise');
        $overview = new overview();
        $this->assertSame('flavour_enterprise', $overview->currentflavour);
    }

    public function test_flavours() {
        global $CFG;

        if (!$this->testflavouravailable) {
            // If you get this and want to test the overview of flavours you must install the test plugin at TL-7812.
            $this->markTestSkipped('You must install the test flavour in order to test the overview functionality.');
            return true; // Not needed but keeps it clear.
        }

        $this->setAdminUser();

        // Show enterprise if nothing configured.
        $overview = new overview();
        $this->assertSame(array('flavour_enterprise'), array_keys($overview->flavours));
        $this->assertInstanceOf('flavour_enterprise\\definition', $overview->flavours['flavour_enterprise']);

        // Show configured in specified order.
        $CFG->showflavours = 'test,enterprise';
        $overview = new overview();
        $this->assertSame(array('flavour_test', 'flavour_enterprise'), array_keys($overview->flavours));
        $this->assertInstanceOf('flavour_enterprise\\definition', $overview->flavours['flavour_enterprise']);
        $this->assertInstanceOf('flavour_test\\definition', $overview->flavours['flavour_test']);

        // Hide all flavours.
        $CFG->showflavours = '';
        $overview = new overview();
        $this->assertSame(array(), array_keys($overview->flavours));

        // Make sure active is included, as last if not in the list.
        helper::set_active_flavour('flavour_test');
        unset($CFG->showflavours);
        $overview = new overview();
        $this->assertSame(array('flavour_enterprise', 'flavour_test'), array_keys($overview->flavours));
        $this->assertInstanceOf('flavour_enterprise\\definition', $overview->flavours['flavour_enterprise']);
        $this->assertInstanceOf('flavour_test\\definition', $overview->flavours['flavour_test']);

        $CFG->showflavours = 'test,enterprise';
        $overview = new overview();
        $this->assertSame(array('flavour_test', 'flavour_enterprise'), array_keys($overview->flavours));
        $this->assertInstanceOf('flavour_enterprise\\definition', $overview->flavours['flavour_enterprise']);
        $this->assertInstanceOf('flavour_test\\definition', $overview->flavours['flavour_test']);

        $CFG->showflavours = '';
        $overview = new overview();
        $this->assertSame(array('flavour_test'), array_keys($overview->flavours));
        $this->assertInstanceOf('flavour_test\\definition', $overview->flavours['flavour_test']);
    }

    public function test_get_flavour_to_enforce() {
        global $CFG;

        if (!$this->testflavouravailable) {
            // If you get this and want to test the overview of flavours you must install the test plugin at TL-7812.
            $this->markTestSkipped('You must install the test flavour in order to test the overview functionality.');
            return true; // Not needed but keeps it clear.
        }

        $this->setAdminUser();

        $overview = new overview();
        $result = $overview->get_flavour_to_enforce();
        $this->assertNull($result);

        $CFG->forceflavour = 'test';
        $overview = new overview();
        $result = $overview->get_flavour_to_enforce();
        $this->assertSame('flavour_test', $result);

        helper::set_active_flavour('flavour_test');
        $overview = new overview();
        $result = $overview->get_flavour_to_enforce();
        $this->assertSame('flavour_test', $result);

        set_config('enablegoals', TOTARA_SHOWFEATURE);
        $overview = new overview();
        $result = $overview->get_flavour_to_enforce();
        $this->assertSame('flavour_test', $result);

        helper::set_active_flavour('flavour_test');
        $overview = new overview();
        $result = $overview->get_flavour_to_enforce();
        $this->assertSame('flavour_test', $result);

        $CFG->enablegoals = 1;
        $CFG->config_php_settings['enablegoals'] = 1;
        $overview = new overview();
        $result = $overview->get_flavour_to_enforce();
        $this->assertSame('flavour_test', $result);
    }
}
