<?php
/*
 * This file is part of Totara LMS
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
 * @package report_security
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/report/security/locallib.php');
require_once($CFG->dirroot . '/lib/filterlib.php');

class report_security_locallib_testcase extends advanced_testcase {

    public function setUp() {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * Check with both the mediaplugin filter disabled and the swf setting turned off.
     * This should mean swf cannot be added and therefore the check returns as ok.
     */
    public function test_report_security_check_mediafilterswf_plugin_off() {
        global $CFG;

        filter_set_global_state('mediaplugin', TEXTFILTER_DISABLED);
        $result = report_security_check_mediafilterswf();
        $this->assertEquals(REPORT_SECURITY_OK, $result->status);

        // It should not check the below setting if the mediaplugin was disabled, but we'll make sure.
        $playerstoenable = array('swf');
        \core\plugininfo\media::set_enabled_plugins($playerstoenable);
        $enabledmediaplayers = \core\plugininfo\media::get_enabled_plugins();
        $this->assertContains('swf', $enabledmediaplayers);
        $result = report_security_check_mediafilterswf();
        $this->assertEquals(REPORT_SECURITY_OK, $result->status);
    }

    /**
     * Check with the mediaplugin filter enabled but the swf setting turned off.
     * This should mean swf cannot be added and therefore the check returns as ok.
     */
    public function test_report_security_check_mediafilterswf_plugin_on_swf_off() {
        global $CFG;

        // The mediaplugin filter should be enabled by default. We'll make sure.
        $active = filter_get_globally_enabled(true);
        $this->assertTrue(isset($active['mediaplugin']));

        // The swf setting should now be off by default on new installs.
        $enabledmediaplayers = \core\plugininfo\media::get_enabled_plugins();
        $this->assertNotContains('swf', $enabledmediaplayers);

        $result = report_security_check_mediafilterswf();
        $this->assertEquals(REPORT_SECURITY_OK, $result->status);
    }

    /**
     * Check with the mediaplugin filter enabled but the swf setting turned on.
     * This should mean swf can be added and therefore the check returns a warning.
     */
    public function test_report_security_check_mediafilterswf_plugin_on_swf_on() {
        global $CFG;

        // The mediaplugin filter should be enabled by default. We'll make sure.
        $active = filter_get_globally_enabled(true);
        $this->assertTrue(isset($active['mediaplugin']));

        $playerstoenable = array('swf');
        \core\plugininfo\media::set_enabled_plugins($playerstoenable);
        $enabledmediaplayers = \core\plugininfo\media::get_enabled_plugins();
        $this->assertContains('swf', $enabledmediaplayers);

        $result = report_security_check_mediafilterswf();
        $this->assertEquals(REPORT_SECURITY_WARNING, $result->status);
    }
}
