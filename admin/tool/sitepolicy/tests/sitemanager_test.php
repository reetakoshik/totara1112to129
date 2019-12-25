<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
 * @package tool_sitepolicy
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/adminlib.php');


/**
 * Test that Site Manager can manage site policies
 *
 * @group tool_sitepolicy
 */
class tool_sitepolicy_sitemanager_testcase extends advanced_testcase {

    /*
     * Test that we can get the site policies with site policies enabled and
     * a user that has Site Manager role assigned
     */
    public function test_sitepolicy_with_enablesitepolicies() {
        global $DB, $CFG;
        $this->resetAfterTest(true);

        // enable site policies
        $CFG->enablesitepolicies = 1;

        // add site manager user
        $manageruser = $this->getDataGenerator()->create_user();
        $managerrole = $DB->get_record('role', array('shortname' => 'manager'));
        $this->getDataGenerator()->role_assign($managerrole->id, $manageruser->id);

        $this->setUser($manageruser);

        // get manager policies sub tree
        $adminroot = admin_get_root(true);
        $sitepolicy = $adminroot->locate('tool_sitepolicy-managerpolicies');

        // make sure that the value returned is an object (might be null in case of error)
        $this->assertInstanceOf('admin_externalpage', $sitepolicy);

        // make sure that the object returned is the correct part of object tree
        $this->assertEquals("tool_sitepolicy-managerpolicies", $sitepolicy->name);
    }

    /*
     * Test that we can not get the site policies with site policies disabled and
     * a user that has Site Manager role assigned
     */
    public function test_sitepolicy_without_enablesitepolicies() {
        global $DB, $CFG;
        $this->resetAfterTest(true);

        // disable site policies
        $CFG->enablesitepolicies = 0;

        // add site manager user
        $manageruser = $this->getDataGenerator()->create_user();
        $managerrole = $DB->get_record('role', array('shortname' => 'manager'));
        $this->getDataGenerator()->role_assign($managerrole->id, $manageruser->id);

        $this->setUser($manageruser);

        // get manager policies sub tree
        $adminroot = admin_get_root(true);
        $sitepolicy = $adminroot->locate('tool_sitepolicy-managerpolicies');

        // user should not have access to site policies ($sitepolicy will be null)
        $this->assertEmpty($sitepolicy);
    }

}
