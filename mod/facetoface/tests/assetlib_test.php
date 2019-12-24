<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2010 onwards Totara Learning Solutions LTD
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
 * @author Valerii Kuznetsov <valerii.kuznetsov@totaralearning.com>
 * @package totara_facetoface
 */

/*
 * Unit tests for mod/facetoface/asset/lib.php functions.
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    // It must be included from a Moodle page.
}

global $CFG;
require_once($CFG->dirroot . '/mod/facetoface/lib.php');

class mod_facetoface_assetlib_testcase extends advanced_testcase {

    /** @var mod_facetoface_generator */
    protected $facetoface_generator;

    /** @var totara_customfield_generator */
    protected $customfield_generator;

    private $cfprefix = 'facetofaceasset', $cftableprefix = 'facetoface_asset';

    protected function tearDown() {
        $this->facetoface_generator = null;
        $this->customfield_generator = null;
        $this->cfprefix = null;
        parent::tearDown();
    }

    public function setUp() {
        parent::setUp();
        $this->resetAfterTest();

        $this->facetoface_generator = $this->getDataGenerator()->get_plugin_generator('mod_facetoface');
        $this->customfield_generator = $this->getDataGenerator()->get_plugin_generator('totara_customfield');
    }

    public function test_facetoface_get_asset() {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/totara/customfield/fieldlib.php');

        $sitewideasset = $this->facetoface_generator->add_site_wide_asset(array());
        customfield_load_data($sitewideasset, 'facetofaceasset', 'facetoface_asset');

        $customasset = $this->facetoface_generator->add_custom_asset(array());
        customfield_load_data($customasset, 'facetofaceasset', 'facetoface_asset');

        $this->assertCount(2, $DB->get_records('facetoface_asset', array()));

        $this->assertEquals($sitewideasset, facetoface_get_asset($sitewideasset->id));
        $this->assertEquals($customasset, facetoface_get_asset($customasset->id));

        $this->assertFalse(facetoface_get_asset(-1));
        $this->assertFalse(facetoface_get_asset(0));
    }

    /**
     * Basic tests for asset deletes.
     */
    public function test_facetoface_delete_asset() {
        global $DB;

        $fs = get_file_storage();
        $syscontext = context_system::instance();

        $sitewideasset = $this->facetoface_generator->add_site_wide_asset(array());
        $filerecord = array(
            'contextid' => $syscontext->id,
            'component' => 'mod_facetoface',
            'filearea' => 'asset',
            'itemid' => $sitewideasset->id,
            'filepath' => '/',
            'filename' => 'xx.jpg',
        );
        $sitefile = $fs->create_file_from_string($filerecord, 'xx');

        $customasset = $this->facetoface_generator->add_custom_asset(array());
        $filerecord = array(
            'contextid' => $syscontext->id,
            'component' => 'mod_facetoface',
            'filearea' => 'asset',
            'itemid' => $customasset->id,
            'filepath' => '/',
            'filename' => 'xx.jpg',
        );
        $customfile = $fs->create_file_from_string($filerecord, 'xx');

        $this->assertCount(2, $DB->get_records('facetoface_asset', array()));

        $course = $this->getDataGenerator()->create_course();
        $facetoface = $this->facetoface_generator->create_instance(array('course' => $course->id));

        $sessiondate1 = new stdClass();
        $sessiondate1->timestart = time() + (DAYSECS * 1);
        $sessiondate1->timefinish = $sessiondate1->timestart + (DAYSECS * 1);
        $sessiondate1->sessiontimezone = '99';
        $sessiondate1->assetids = array($sitewideasset->id);
        $sessionid1 = $this->facetoface_generator->add_session(array('facetoface' => $facetoface->id, 'sessiondates' => array($sessiondate1)));
        $sessiondate1 = $DB->get_record('facetoface_sessions_dates', array('sessionid' => $sessionid1), '*', MUST_EXIST);
        $this->assertCount(1, $DB->get_records('facetoface_asset_dates', array('assetid' => $sitewideasset->id)));

        $sessiondate2 = new stdClass();
        $sessiondate2->timestart = time() + (DAYSECS * 2);
        $sessiondate2->timefinish = $sessiondate2->timestart + (DAYSECS * 2);
        $sessiondate2->sessiontimezone = '99';
        $sessiondate2->assetids = array($customasset->id, $sitewideasset->id);
        $sessionid2 = $this->facetoface_generator->add_session(array('facetoface' => $facetoface->id, 'sessiondates' => array($sessiondate2)));
        $sessiondate2 = $DB->get_record('facetoface_sessions_dates', array('sessionid' => $sessionid2), '*', MUST_EXIST);
        $this->assertCount(1, $DB->get_records('facetoface_asset_dates', array('assetid' => $customasset->id)));
        $this->assertCount(2, $DB->get_records('facetoface_asset_dates', array('assetid' => $sitewideasset->id)));

        facetoface_delete_asset($sitewideasset->id);
        $this->assertFalse($DB->record_exists('facetoface_asset', array('id' => $sitewideasset->id)));
        $this->assertTrue($DB->record_exists('facetoface_asset', array('id' => $customasset->id)));
        $sessiondate1 = $DB->get_record('facetoface_sessions_dates', array('sessionid' => $sessionid1), '*', MUST_EXIST);
        $sessiondate2 = $DB->get_record('facetoface_sessions_dates', array('sessionid' => $sessionid2), '*', MUST_EXIST);
        $this->assertCount(0, $DB->get_records('facetoface_asset_dates', array('assetid' => $sitewideasset->id)));
        $this->assertCount(1, $DB->get_records('facetoface_asset_dates', array('assetid' => $customasset->id)));
        $this->assertFalse($fs->file_exists_by_hash($sitefile->get_pathnamehash()));
        $this->assertTrue($fs->file_exists_by_hash($customfile->get_pathnamehash()));

        // Second delete should do nothing.
        facetoface_delete_asset($sitewideasset->id);
    }

    /**
     * Test asset availability functions.
     *
     * NOTE: this is a bit simplified because there is only one asset per date,
     *       the reason is this test is kept in sync with room tests.
     */
    public function test_facetoface_available_assets() {
        global $DB;

        $now = time();

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $sitewideasset1 = $this->facetoface_generator->add_site_wide_asset(array('name' => 'Site asset 1', 'allowconflicts' => 0, 'hidden' => 0));
        $sitewideasset2 = $this->facetoface_generator->add_site_wide_asset(array('name' => 'Site asset 2', 'allowconflicts' => 0, 'hidden' => 0));
        $sitewideasset3 = $this->facetoface_generator->add_site_wide_asset(array('name' => 'Site asset 3', 'allowconflicts' => 0, 'hidden' => 1));
        $sitewideasset4 = $this->facetoface_generator->add_site_wide_asset(array('name' => 'Site asset 4', 'allowconflicts' => 1, 'hidden' => 0));
        $sitewideasset5 = $this->facetoface_generator->add_site_wide_asset(array('name' => 'Site asset 5', 'allowconflicts' => 1, 'hidden' => 0));
        $sitewideasset6 = $this->facetoface_generator->add_site_wide_asset(array('name' => 'Site asset 6', 'allowconflicts' => 1, 'hidden' => 1));
        $customasset1 = $this->facetoface_generator->add_custom_asset(array('usercreated' => $user1->id, 'name' => 'Custom asset 1', 'allowconflicts' => 0));
        $customasset2 = $this->facetoface_generator->add_custom_asset(array('usercreated' => $user1->id, 'name' => 'Custom asset 2', 'allowconflicts' => 0));
        $customasset3 = $this->facetoface_generator->add_custom_asset(array('usercreated' => $user2->id, 'name' => 'Custom asset 3', 'allowconflicts' => 0));
        $customasset4 = $this->facetoface_generator->add_custom_asset(array('usercreated' => $user1->id, 'name' => 'Custom asset 4', 'allowconflicts' => 1));
        $customasset5 = $this->facetoface_generator->add_custom_asset(array('usercreated' => $user1->id, 'name' => 'Custom asset 5', 'allowconflicts' => 1));
        $customasset6 = $this->facetoface_generator->add_custom_asset(array('usercreated' => $user2->id, 'name' => 'Custom asset 6', 'allowconflicts' => 1));
        $allassets = $DB->get_records('facetoface_asset', array());

        $course = $this->getDataGenerator()->create_course();
        $facetoface1 = $this->facetoface_generator->create_instance(array('course' => $course->id));
        $facetoface2 = $this->facetoface_generator->create_instance(array('course' => $course->id));

        $sessiondates = array();
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * -2), $now + (DAYSECS * -1), $sitewideasset3->id);
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 1), $now + (DAYSECS * 2), $sitewideasset1->id);
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 2), $now + (DAYSECS * 3), $sitewideasset2->id);
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 3), $now + (DAYSECS * 4), $customasset1->id);
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 5), $now + (DAYSECS * 6), $customasset3->id);
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 7), $now + (DAYSECS * 8), $customasset4->id);
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 9), $now + (DAYSECS * 10), $sitewideasset4->id);
        $sessionid1_1 = $this->facetoface_generator->add_session(array('facetoface' => $facetoface1->id, 'sessiondates' => $sessiondates));

        $sessionid1_2 = $this->facetoface_generator->add_session(array('facetoface' => $facetoface1->id, 'sessiondates' => array()));

        $sessiondates = array();
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 30), $now + (DAYSECS * 31), $sitewideasset1->id);
        $sessionid1_3 = $this->facetoface_generator->add_session(array('facetoface' => $facetoface1->id, 'sessiondates' => $sessiondates));

        $sessiondates = array();
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 5), $now + (DAYSECS * 6), $customasset3->id);
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 9), $now + (DAYSECS * 10), $sitewideasset4->id);
        $sessionid2_1 = $this->facetoface_generator->add_session(array('facetoface' => $facetoface2->id, 'sessiondates' => $sessiondates));

        $this->setUser(null);

        // Get all site assets that are not hidden.

        $assets = facetoface_get_available_assets(0, 0, 'fa.*', 0, 0);
        $this->assertCount(4, $assets);
        $this->assertArrayHasKey($sitewideasset1->id, $assets);
        $this->assertArrayHasKey($sitewideasset2->id, $assets);
        $this->assertArrayHasKey($sitewideasset4->id, $assets);
        $this->assertArrayHasKey($sitewideasset5->id, $assets);
        foreach ($allassets as $asset) {
            if (isset($assets[$asset->id])) {
                $this->assertTrue(facetoface_is_asset_available(0, 0, $asset, 0, 0));
            } else {
                $this->assertFalse(facetoface_is_asset_available(0, 0, $asset, 0, 0));
            }
        }

        // Get available site assets for given slot.

        $assets = facetoface_get_available_assets($now + (DAYSECS * -1), $now + (DAYSECS * 1), 'fa.*', 0, 0);
        $this->assertCount(4, $assets);
        $this->assertArrayHasKey($sitewideasset1->id, $assets);
        $this->assertArrayHasKey($sitewideasset2->id, $assets);
        $this->assertArrayHasKey($sitewideasset4->id, $assets);
        $this->assertArrayHasKey($sitewideasset5->id, $assets);
        foreach ($allassets as $asset) {
            if (isset($assets[$asset->id])) {
                $this->assertTrue(facetoface_is_asset_available($now + (DAYSECS * -1), $now + (DAYSECS * 1), $asset, 0, 0));
            } else {
                $this->assertFalse(facetoface_is_asset_available($now + (DAYSECS * -1), $now + (DAYSECS * 1), $asset, 0, 0));
            }
        }

        $assets = facetoface_get_available_assets($now + (DAYSECS * 1), $now + (DAYSECS * 2), 'fa.*', 0, 0);
        $this->assertCount(3, $assets);
        $this->assertArrayHasKey($sitewideasset2->id, $assets);
        $this->assertArrayHasKey($sitewideasset4->id, $assets);
        $this->assertArrayHasKey($sitewideasset5->id, $assets);
        foreach ($allassets as $asset) {
            if (isset($assets[$asset->id])) {
                $this->assertTrue(facetoface_is_asset_available($now + (DAYSECS * 1), $now + (DAYSECS * 2), $asset, 0, 0));
            } else {
                $this->assertFalse(facetoface_is_asset_available($now + (DAYSECS * 1), $now + (DAYSECS * 2), $asset, 0, 0));
            }
        }

        $assets = facetoface_get_available_assets($now + (DAYSECS * 0), $now + (DAYSECS * 3), 'fa.*', 0, 0);
        $this->assertCount(2, $assets);
        $this->assertArrayHasKey($sitewideasset4->id, $assets);
        $this->assertArrayHasKey($sitewideasset5->id, $assets);
        foreach ($allassets as $asset) {
            if (isset($assets[$asset->id])) {
                $this->assertTrue(facetoface_is_asset_available($now + (DAYSECS * 0), $now + (DAYSECS * 3), $asset, 0, 0));
            } else {
                $this->assertFalse(facetoface_is_asset_available($now + (DAYSECS * 0), $now + (DAYSECS * 3), $asset, 0, 0));
            }
        }

        // Specify only seminar id such as when adding new session.

        $assets = facetoface_get_available_assets(0, 0, 'fa.*', 0, $facetoface1->id);
        $this->assertCount(7, $assets);
        $this->assertArrayHasKey($sitewideasset1->id, $assets);
        $this->assertArrayHasKey($sitewideasset2->id, $assets);
        $this->assertArrayHasKey($sitewideasset4->id, $assets);
        $this->assertArrayHasKey($sitewideasset5->id, $assets);
        $this->assertArrayHasKey($customasset1->id, $assets);
        $this->assertArrayHasKey($customasset3->id, $assets);
        $this->assertArrayHasKey($customasset4->id, $assets);
        foreach ($allassets as $asset) {
            if (isset($assets[$asset->id])) {
                $this->assertTrue(facetoface_is_asset_available(0, 0, $asset, 0, $facetoface1->id));
            } else {
                $this->assertFalse(facetoface_is_asset_available(0, 0, $asset, 0, $facetoface1->id));
            }
        }

        $assets = facetoface_get_available_assets(0, 0, 'fa.*', 0, $facetoface2->id);
        $this->assertCount(5, $assets);
        $this->assertArrayHasKey($sitewideasset1->id, $assets);
        $this->assertArrayHasKey($sitewideasset2->id, $assets);
        $this->assertArrayHasKey($sitewideasset4->id, $assets);
        $this->assertArrayHasKey($sitewideasset5->id, $assets);
        $this->assertArrayHasKey($customasset3->id, $assets);
        foreach ($allassets as $asset) {
            if (isset($assets[$asset->id])) {
                $this->assertTrue(facetoface_is_asset_available(0, 0, $asset, 0, $facetoface2->id));
            } else {
                $this->assertFalse(facetoface_is_asset_available(0, 0, $asset, 0, $facetoface2->id));
            }
        }

        $assets = facetoface_get_available_assets($now + (DAYSECS * -1), $now + (DAYSECS * 1), 'fa.*', 0, $facetoface1->id);
        $this->assertCount(7, $assets);
        $this->assertArrayHasKey($sitewideasset1->id, $assets);
        $this->assertArrayHasKey($sitewideasset2->id, $assets);
        $this->assertArrayHasKey($sitewideasset4->id, $assets);
        $this->assertArrayHasKey($sitewideasset5->id, $assets);
        $this->assertArrayHasKey($customasset1->id, $assets);
        $this->assertArrayHasKey($customasset3->id, $assets);
        $this->assertArrayHasKey($customasset4->id, $assets);
        foreach ($allassets as $asset) {
            if (isset($assets[$asset->id])) {
                $this->assertTrue(facetoface_is_asset_available($now + (DAYSECS * -1), $now + (DAYSECS * 1), $asset, 0, $facetoface1->id));
            } else {
                $this->assertFalse(facetoface_is_asset_available($now + (DAYSECS * -1), $now + (DAYSECS * 1), $asset, 0, $facetoface1->id));
            }
        }

        $assets = facetoface_get_available_assets($now + (DAYSECS * 1), $now + (DAYSECS * 2), 'fa.*', 0, $facetoface1->id);
        $this->assertCount(6, $assets);
        $this->assertArrayHasKey($sitewideasset2->id, $assets);
        $this->assertArrayHasKey($sitewideasset4->id, $assets);
        $this->assertArrayHasKey($sitewideasset5->id, $assets);
        $this->assertArrayHasKey($customasset1->id, $assets);
        $this->assertArrayHasKey($customasset3->id, $assets);
        $this->assertArrayHasKey($customasset4->id, $assets);
        foreach ($allassets as $asset) {
            if (isset($assets[$asset->id])) {
                $this->assertTrue(facetoface_is_asset_available($now + (DAYSECS * 1), $now + (DAYSECS * 2), $asset, 0, $facetoface1->id));
            } else {
                $this->assertFalse(facetoface_is_asset_available($now + (DAYSECS * 1), $now + (DAYSECS * 2), $asset, 0, $facetoface1->id));
            }
        }

        $assets = facetoface_get_available_assets($now + (DAYSECS * 0), $now + (DAYSECS * 3), 'fa.*', 0, $facetoface1->id);
        $this->assertCount(5, $assets);
        $this->assertArrayHasKey($sitewideasset4->id, $assets);
        $this->assertArrayHasKey($sitewideasset5->id, $assets);
        $this->assertArrayHasKey($customasset1->id, $assets);
        $this->assertArrayHasKey($customasset3->id, $assets);
        $this->assertArrayHasKey($customasset4->id, $assets);
        foreach ($allassets as $asset) {
            if (isset($assets[$asset->id])) {
                $this->assertTrue(facetoface_is_asset_available($now + (DAYSECS * 0), $now + (DAYSECS * 3), $asset, 0, $facetoface1->id));
            } else {
                $this->assertFalse(facetoface_is_asset_available($now + (DAYSECS * 0), $now + (DAYSECS * 3), $asset, 0, $facetoface1->id));
            }
        }

        $assets = facetoface_get_available_assets($now + (DAYSECS * 1), $now + (DAYSECS * 20), 'fa.*', 0, $facetoface1->id);
        $this->assertCount(3, $assets);
        $this->assertArrayHasKey($sitewideasset4->id, $assets);
        $this->assertArrayHasKey($sitewideasset5->id, $assets);
        $this->assertArrayHasKey($customasset4->id, $assets);
        foreach ($allassets as $asset) {
            if (isset($assets[$asset->id])) {
                $this->assertTrue(facetoface_is_asset_available($now + (DAYSECS * 1), $now + (DAYSECS * 20), $asset, 0, $facetoface1->id));
            } else {
                $this->assertFalse(facetoface_is_asset_available($now + (DAYSECS * 1), $now + (DAYSECS * 20), $asset, 0, $facetoface1->id));
            }
        }

        $assets = facetoface_get_available_assets($now + (DAYSECS * 1), $now + (DAYSECS * 20), 'fa.*', 0, $facetoface2->id);
        $this->assertCount(2, $assets);
        $this->assertArrayHasKey($sitewideasset4->id, $assets);
        $this->assertArrayHasKey($sitewideasset5->id, $assets);
        foreach ($allassets as $asset) {
            if (isset($assets[$asset->id])) {
                $this->assertTrue(facetoface_is_asset_available($now + (DAYSECS * 1), $now + (DAYSECS * 20), $asset, 0, $facetoface2->id));
            } else {
                $this->assertFalse(facetoface_is_asset_available($now + (DAYSECS * 1), $now + (DAYSECS * 20), $asset, 0, $facetoface2->id));
            }
        }

        // Specify seminar id and session id such as when adding updating session.

        $assets = facetoface_get_available_assets(0, 0, 'fa.*', $sessionid1_1, $facetoface1->id);
        $this->assertCount(8, $assets);
        $this->assertArrayHasKey($sitewideasset1->id, $assets);
        $this->assertArrayHasKey($sitewideasset2->id, $assets);
        $this->assertArrayHasKey($sitewideasset4->id, $assets);
        $this->assertArrayHasKey($sitewideasset3->id, $assets);
        $this->assertArrayHasKey($sitewideasset5->id, $assets);
        $this->assertArrayHasKey($customasset1->id, $assets);
        $this->assertArrayHasKey($customasset3->id, $assets);
        $this->assertArrayHasKey($customasset4->id, $assets);
        foreach ($allassets as $asset) {
            if (isset($assets[$asset->id])) {
                $this->assertTrue(facetoface_is_asset_available(0, 0, $asset, $sessionid1_1, $facetoface1->id));
            } else {
                $this->assertFalse(facetoface_is_asset_available(0, 0, $asset, $sessionid1_1, $facetoface1->id));
            }
        }

        $assets = facetoface_get_available_assets(0, 0, 'fa.*', $sessionid1_2, $facetoface1->id);
        $this->assertCount(7, $assets);
        $this->assertArrayHasKey($sitewideasset1->id, $assets);
        $this->assertArrayHasKey($sitewideasset2->id, $assets);
        $this->assertArrayHasKey($sitewideasset4->id, $assets);
        $this->assertArrayHasKey($sitewideasset5->id, $assets);
        $this->assertArrayHasKey($customasset1->id, $assets);
        $this->assertArrayHasKey($customasset3->id, $assets);
        $this->assertArrayHasKey($customasset4->id, $assets);
        foreach ($allassets as $asset) {
            if (isset($assets[$asset->id])) {
                $this->assertTrue(facetoface_is_asset_available(0, 0, $asset, $sessionid1_2, $facetoface1->id));
            } else {
                $this->assertFalse(facetoface_is_asset_available(0, 0, $asset, $sessionid1_2, $facetoface1->id));
            }
        }

        $assets = facetoface_get_available_assets(0, 0, 'fa.*', $sessionid2_1, $facetoface2->id);
        $this->assertCount(5, $assets);
        $this->assertArrayHasKey($sitewideasset1->id, $assets);
        $this->assertArrayHasKey($sitewideasset2->id, $assets);
        $this->assertArrayHasKey($sitewideasset4->id, $assets);
        $this->assertArrayHasKey($sitewideasset5->id, $assets);
        $this->assertArrayHasKey($customasset3->id, $assets);
        foreach ($allassets as $asset) {
            if (isset($assets[$asset->id])) {
                $this->assertTrue(facetoface_is_asset_available(0, 0, $asset, $sessionid2_1, $facetoface2->id));
            } else {
                $this->assertFalse(facetoface_is_asset_available(0, 0, $asset, $sessionid2_1, $facetoface2->id));
            }
        }

        $assets = facetoface_get_available_assets($now + (DAYSECS * -1), $now + (DAYSECS * 1), 'fa.*', $sessionid1_1, $facetoface1->id);
        $this->assertCount(8, $assets);
        $this->assertArrayHasKey($sitewideasset1->id, $assets);
        $this->assertArrayHasKey($sitewideasset2->id, $assets);
        $this->assertArrayHasKey($sitewideasset3->id, $assets);
        $this->assertArrayHasKey($sitewideasset4->id, $assets);
        $this->assertArrayHasKey($sitewideasset5->id, $assets);
        $this->assertArrayHasKey($customasset1->id, $assets);
        $this->assertArrayHasKey($customasset3->id, $assets);
        $this->assertArrayHasKey($customasset4->id, $assets);
        foreach ($allassets as $asset) {
            if (isset($assets[$asset->id])) {
                $this->assertTrue(facetoface_is_asset_available($now + (DAYSECS * -1), $now + (DAYSECS * 1), $asset, $sessionid1_1, $facetoface1->id));
            } else {
                $this->assertFalse(facetoface_is_asset_available($now + (DAYSECS * -1), $now + (DAYSECS * 1), $asset, $sessionid1_1, $facetoface1->id));
            }
        }

        $assets = facetoface_get_available_assets($now + (DAYSECS * 1), $now + (DAYSECS * 2), 'fa.*', $sessionid1_3, $facetoface1->id);
        $this->assertCount(6, $assets);
        $this->assertArrayHasKey($sitewideasset2->id, $assets);
        $this->assertArrayHasKey($sitewideasset4->id, $assets);
        $this->assertArrayHasKey($sitewideasset5->id, $assets);
        $this->assertArrayHasKey($customasset1->id, $assets);
        $this->assertArrayHasKey($customasset3->id, $assets);
        $this->assertArrayHasKey($customasset4->id, $assets);
        foreach ($allassets as $asset) {
            if (isset($assets[$asset->id])) {
                $this->assertTrue(facetoface_is_asset_available($now + (DAYSECS * 1), $now + (DAYSECS * 2), $asset, $sessionid1_3, $facetoface1->id));
            } else {
                $this->assertFalse(facetoface_is_asset_available($now + (DAYSECS * 1), $now + (DAYSECS * 2), $asset, $sessionid1_3, $facetoface1->id));
            }
        }

        $assets = facetoface_get_available_assets($now + (DAYSECS * -1), $now + (DAYSECS * 1), 'fa.*', $sessionid1_2, $facetoface1->id);
        $this->assertCount(7, $assets);
        $this->assertArrayHasKey($sitewideasset1->id, $assets);
        $this->assertArrayHasKey($sitewideasset2->id, $assets);
        $this->assertArrayHasKey($sitewideasset4->id, $assets);
        $this->assertArrayHasKey($sitewideasset5->id, $assets);
        $this->assertArrayHasKey($customasset1->id, $assets);
        $this->assertArrayHasKey($customasset3->id, $assets);
        $this->assertArrayHasKey($customasset4->id, $assets);
        foreach ($allassets as $asset) {
            if (isset($assets[$asset->id])) {
                $this->assertTrue(facetoface_is_asset_available($now + (DAYSECS * -1), $now + (DAYSECS * 1), $asset, $sessionid1_2, $facetoface1->id));
            } else {
                $this->assertFalse(facetoface_is_asset_available($now + (DAYSECS * -1), $now + (DAYSECS * 1), $asset, $sessionid1_2, $facetoface1->id));
            }
        }

        $assets = facetoface_get_available_assets($now + (DAYSECS * 1), $now + (DAYSECS * 2), 'fa.*', $sessionid1_1, $facetoface1->id);
        $this->assertCount(8, $assets);
        $this->assertArrayHasKey($sitewideasset1->id, $assets);
        $this->assertArrayHasKey($sitewideasset2->id, $assets);
        $this->assertArrayHasKey($sitewideasset3->id, $assets);
        $this->assertArrayHasKey($sitewideasset4->id, $assets);
        $this->assertArrayHasKey($sitewideasset5->id, $assets);
        $this->assertArrayHasKey($customasset1->id, $assets);
        $this->assertArrayHasKey($customasset3->id, $assets);
        $this->assertArrayHasKey($customasset4->id, $assets);

        foreach ($allassets as $asset) {
            if (isset($assets[$asset->id])) {
                $this->assertTrue(facetoface_is_asset_available($now + (DAYSECS * 1), $now + (DAYSECS * 2), $asset, $sessionid1_1, $facetoface1->id));
            } else {
                $this->assertFalse(facetoface_is_asset_available($now + (DAYSECS * 1), $now + (DAYSECS * 2), $asset, $sessionid1_1, $facetoface1->id));
            }
        }

        $assets = facetoface_get_available_assets($now + (DAYSECS * 1), $now + (DAYSECS * 2), 'fa.*', $sessionid1_2, $facetoface1->id);
        $this->assertCount(6, $assets);
        $this->assertArrayHasKey($sitewideasset2->id, $assets);
        $this->assertArrayHasKey($sitewideasset4->id, $assets);
        $this->assertArrayHasKey($sitewideasset5->id, $assets);
        $this->assertArrayHasKey($customasset1->id, $assets);
        $this->assertArrayHasKey($customasset3->id, $assets);
        $this->assertArrayHasKey($customasset4->id, $assets);
        foreach ($allassets as $asset) {
            if (isset($assets[$asset->id])) {
                $this->assertTrue(facetoface_is_asset_available($now + (DAYSECS * 1), $now + (DAYSECS * 2), $asset, $sessionid1_2, $facetoface1->id));
            } else {
                $this->assertFalse(facetoface_is_asset_available($now + (DAYSECS * 1), $now + (DAYSECS * 2), $asset, $sessionid1_2, $facetoface1->id));
            }
        }

        $assets = facetoface_get_available_assets($now + (DAYSECS * 0), $now + (DAYSECS * 3), 'fa.*', $sessionid1_1, $facetoface1->id);
        $this->assertCount(8, $assets);
        $this->assertArrayHasKey($sitewideasset1->id, $assets);
        $this->assertArrayHasKey($sitewideasset2->id, $assets);
        $this->assertArrayHasKey($sitewideasset3->id, $assets);
        $this->assertArrayHasKey($sitewideasset4->id, $assets);
        $this->assertArrayHasKey($sitewideasset5->id, $assets);
        $this->assertArrayHasKey($customasset1->id, $assets);
        $this->assertArrayHasKey($customasset3->id, $assets);
        $this->assertArrayHasKey($customasset4->id, $assets);
        foreach ($allassets as $asset) {
            if (isset($assets[$asset->id])) {
                $this->assertTrue(facetoface_is_asset_available($now + (DAYSECS * 0), $now + (DAYSECS * 3), $asset, $sessionid1_1, $facetoface1->id));
            } else {
                $this->assertFalse(facetoface_is_asset_available($now + (DAYSECS * 0), $now + (DAYSECS * 3), $asset, $sessionid1_1, $facetoface1->id));
            }
        }

        $assets = facetoface_get_available_assets($now + (DAYSECS * 0), $now + (DAYSECS * 3), 'fa.*', $sessionid1_2, $facetoface1->id);
        $this->assertCount(5, $assets);
        $this->assertArrayHasKey($sitewideasset4->id, $assets);
        $this->assertArrayHasKey($sitewideasset5->id, $assets);
        $this->assertArrayHasKey($customasset1->id, $assets);
        $this->assertArrayHasKey($customasset3->id, $assets);
        $this->assertArrayHasKey($customasset4->id, $assets);
        foreach ($allassets as $asset) {
            if (isset($assets[$asset->id])) {
                $this->assertTrue(facetoface_is_asset_available($now + (DAYSECS * 0), $now + (DAYSECS * 3), $asset, $sessionid1_2, $facetoface1->id));
            } else {
                $this->assertFalse(facetoface_is_asset_available($now + (DAYSECS * 0), $now + (DAYSECS * 3), $asset, $sessionid1_2, $facetoface1->id));
            }
        }

        $assets = facetoface_get_available_assets($now + (DAYSECS * 1), $now + (DAYSECS * 20), 'fa.*', $sessionid1_1, $facetoface1->id);
        $this->assertCount(7, $assets);
        $this->assertArrayHasKey($sitewideasset1->id, $assets);
        $this->assertArrayHasKey($sitewideasset2->id, $assets);
        $this->assertArrayHasKey($sitewideasset3->id, $assets);
        $this->assertArrayHasKey($sitewideasset4->id, $assets);
        $this->assertArrayHasKey($sitewideasset5->id, $assets);
        $this->assertArrayHasKey($customasset1->id, $assets);
        $this->assertArrayHasKey($customasset4->id, $assets);

        foreach ($allassets as $asset) {
            if (isset($assets[$asset->id])) {
                $this->assertTrue(facetoface_is_asset_available($now + (DAYSECS * 1), $now + (DAYSECS * 20), $asset, $sessionid1_1, $facetoface1->id));
            } else {
                $this->assertFalse(facetoface_is_asset_available($now + (DAYSECS * 1), $now + (DAYSECS * 20), $asset, $sessionid1_1, $facetoface1->id));
            }
        }

        $assets = facetoface_get_available_assets($now + (DAYSECS * 1), $now + (DAYSECS * 20), 'fa.*', $sessionid1_2, $facetoface1->id);
        $this->assertCount(3, $assets);
        $this->assertArrayHasKey($sitewideasset4->id, $assets);
        $this->assertArrayHasKey($sitewideasset5->id, $assets);
        $this->assertArrayHasKey($customasset4->id, $assets);
        foreach ($allassets as $asset) {
            if (isset($assets[$asset->id])) {
                $this->assertTrue(facetoface_is_asset_available($now + (DAYSECS * 1), $now + (DAYSECS * 20), $asset, $sessionid1_2, $facetoface1->id));
            } else {
                $this->assertFalse(facetoface_is_asset_available($now + (DAYSECS * 1), $now + (DAYSECS * 20), $asset, $sessionid1_2, $facetoface1->id));
            }
        }

        $assets = facetoface_get_available_assets($now + (DAYSECS * 1), $now + (DAYSECS * 20), 'fa.*', $sessionid2_1, $facetoface2->id);
        $this->assertCount(2, $assets);
        $this->assertArrayHasKey($sitewideasset4->id, $assets);
        $this->assertArrayHasKey($sitewideasset5->id, $assets);
        foreach ($allassets as $asset) {
            if (isset($assets[$asset->id])) {
                $this->assertTrue(facetoface_is_asset_available($now + (DAYSECS * 1), $now + (DAYSECS * 20), $asset, $sessionid2_1, $facetoface2->id));
            } else {
                $this->assertFalse(facetoface_is_asset_available($now + (DAYSECS * 1), $now + (DAYSECS * 20), $asset, $sessionid2_1, $facetoface2->id));
            }
        }

        // Now with user.

        $this->setUser($user1);

        $assets = facetoface_get_available_assets(0, 0, 'fa.*', 0, 0);
        $this->assertCount(6, $assets);
        $this->assertArrayHasKey($sitewideasset1->id, $assets);
        $this->assertArrayHasKey($sitewideasset2->id, $assets);
        $this->assertArrayHasKey($sitewideasset4->id, $assets);
        $this->assertArrayHasKey($sitewideasset5->id, $assets);
        $this->assertArrayHasKey($customasset2->id, $assets);
        $this->assertArrayHasKey($customasset5->id, $assets);
        foreach ($allassets as $asset) {
            if (isset($assets[$asset->id])) {
                $this->assertTrue(facetoface_is_asset_available(0, 0, $asset, 0, 0));
            } else {
                $this->assertFalse(facetoface_is_asset_available(0, 0, $asset, 0, 0));
            }
        }

        $assets = facetoface_get_available_assets($now + (DAYSECS * -1), $now + (DAYSECS * 1), 'fa.*', 0, 0);
        $this->assertCount(6, $assets);
        $this->assertArrayHasKey($sitewideasset1->id, $assets);
        $this->assertArrayHasKey($sitewideasset2->id, $assets);
        $this->assertArrayHasKey($sitewideasset4->id, $assets);
        $this->assertArrayHasKey($sitewideasset5->id, $assets);
        $this->assertArrayHasKey($customasset2->id, $assets);
        $this->assertArrayHasKey($customasset5->id, $assets);
        foreach ($allassets as $asset) {
            if (isset($assets[$asset->id])) {
                $this->assertTrue(facetoface_is_asset_available($now + (DAYSECS * -1), $now + (DAYSECS * 1), $asset, 0, 0));
            } else {
                $this->assertFalse(facetoface_is_asset_available($now + (DAYSECS * -1), $now + (DAYSECS * 1), $asset, 0, 0));
            }
        }

        $assets = facetoface_get_available_assets($now + (DAYSECS * 0), $now + (DAYSECS * 3), 'fa.*', 0, 0);
        $this->assertCount(4, $assets);
        $this->assertArrayHasKey($sitewideasset4->id, $assets);
        $this->assertArrayHasKey($sitewideasset5->id, $assets);
        $this->assertArrayHasKey($customasset2->id, $assets);
        $this->assertArrayHasKey($customasset5->id, $assets);
        foreach ($allassets as $asset) {
            if (isset($assets[$asset->id])) {
                $this->assertTrue(facetoface_is_asset_available($now + (DAYSECS * 0), $now + (DAYSECS * 3), $asset, 0, 0));
            } else {
                $this->assertFalse(facetoface_is_asset_available($now + (DAYSECS * 0), $now + (DAYSECS * 3), $asset, 0, 0));
            }
        }

        $assets = facetoface_get_available_assets(0, 0, 'fa.*', $sessionid1_1, $facetoface1->id);
        $this->assertCount(10, $assets);
        $this->assertArrayHasKey($sitewideasset1->id, $assets);
        $this->assertArrayHasKey($sitewideasset2->id, $assets);
        $this->assertArrayHasKey($sitewideasset3->id, $assets);
        $this->assertArrayHasKey($sitewideasset4->id, $assets);
        $this->assertArrayHasKey($sitewideasset5->id, $assets);
        $this->assertArrayHasKey($customasset1->id, $assets);
        $this->assertArrayHasKey($customasset3->id, $assets);
        $this->assertArrayHasKey($customasset4->id, $assets);
        $this->assertArrayHasKey($customasset2->id, $assets);
        $this->assertArrayHasKey($customasset5->id, $assets);
        foreach ($allassets as $asset) {
            if (isset($assets[$asset->id])) {
                $this->assertTrue(facetoface_is_asset_available(0, 0, $asset, $sessionid1_1, $facetoface1->id));
            } else {
                $this->assertFalse(facetoface_is_asset_available(0, 0, $asset, $sessionid1_1, $facetoface1->id));
            }
        }

        $assets = facetoface_get_available_assets(0, 0, 'fa.*', $sessionid1_2, $facetoface1->id);
        $this->assertCount(9, $assets);
        $this->assertArrayHasKey($sitewideasset1->id, $assets);
        $this->assertArrayHasKey($sitewideasset2->id, $assets);
        $this->assertArrayHasKey($sitewideasset4->id, $assets);
        $this->assertArrayHasKey($sitewideasset5->id, $assets);
        $this->assertArrayHasKey($customasset1->id, $assets);
        $this->assertArrayHasKey($customasset3->id, $assets);
        $this->assertArrayHasKey($customasset4->id, $assets);
        $this->assertArrayHasKey($customasset2->id, $assets);
        $this->assertArrayHasKey($customasset5->id, $assets);
        foreach ($allassets as $asset) {
            if (isset($assets[$asset->id])) {
                $this->assertTrue(facetoface_is_asset_available(0, 0, $asset, $sessionid1_2, $facetoface1->id));
            } else {
                $this->assertFalse(facetoface_is_asset_available(0, 0, $asset, $sessionid1_2, $facetoface1->id));
            }
        }

        $assets = facetoface_get_available_assets($now + (DAYSECS * 1), $now + (DAYSECS * 20), 'fa.*', $sessionid1_1, $facetoface1->id);
        $this->assertCount(9, $assets);
        $this->assertArrayHasKey($sitewideasset1->id, $assets);
        $this->assertArrayHasKey($sitewideasset2->id, $assets);
        $this->assertArrayHasKey($sitewideasset3->id, $assets);
        $this->assertArrayHasKey($sitewideasset4->id, $assets);
        $this->assertArrayHasKey($sitewideasset5->id, $assets);
        $this->assertArrayHasKey($customasset1->id, $assets);
        $this->assertArrayHasKey($customasset4->id, $assets);
        $this->assertArrayHasKey($customasset2->id, $assets);
        $this->assertArrayHasKey($customasset5->id, $assets);
        foreach ($allassets as $asset) {
            if (isset($assets[$asset->id])) {
                $this->assertTrue(facetoface_is_asset_available($now + (DAYSECS * 1), $now + (DAYSECS * 20), $asset, $sessionid1_1, $facetoface1->id));
            } else {
                $this->assertFalse(facetoface_is_asset_available($now + (DAYSECS * 1), $now + (DAYSECS * 20), $asset, $sessionid1_1, $facetoface1->id));
            }
        }

        $assets = facetoface_get_available_assets($now + (DAYSECS * 1), $now + (DAYSECS * 20), 'fa.*', $sessionid1_2, $facetoface1->id);
        $this->assertCount(5, $assets);
        $this->assertArrayHasKey($sitewideasset4->id, $assets);
        $this->assertArrayHasKey($sitewideasset5->id, $assets);
        $this->assertArrayHasKey($customasset4->id, $assets);
        $this->assertArrayHasKey($customasset2->id, $assets);
        $this->assertArrayHasKey($customasset5->id, $assets);
        foreach ($allassets as $asset) {
            if (isset($assets[$asset->id])) {
                $this->assertTrue(facetoface_is_asset_available($now + (DAYSECS * 1), $now + (DAYSECS * 20), $asset, $sessionid1_2, $facetoface1->id));
            } else {
                $this->assertFalse(facetoface_is_asset_available($now + (DAYSECS * 1), $now + (DAYSECS * 20), $asset, $sessionid1_2, $facetoface1->id));
            }
        }

        $assets = facetoface_get_available_assets($now + (DAYSECS * 1), $now + (DAYSECS * 20), 'fa.*', $sessionid2_1, $facetoface2->id);
        $this->assertCount(4, $assets);
        $this->assertArrayHasKey($sitewideasset4->id, $assets);
        $this->assertArrayHasKey($sitewideasset5->id, $assets);
        $this->assertArrayHasKey($customasset2->id, $assets);
        $this->assertArrayHasKey($customasset5->id, $assets);
        foreach ($allassets as $asset) {
            if (isset($assets[$asset->id])) {
                $this->assertTrue(facetoface_is_asset_available($now + (DAYSECS * 1), $now + (DAYSECS * 20), $asset, $sessionid2_1, $facetoface2->id));
            } else {
                $this->assertFalse(facetoface_is_asset_available($now + (DAYSECS * 1), $now + (DAYSECS * 20), $asset, $sessionid2_1, $facetoface2->id));
            }
        }

        // Test the fields can be specified.
        $assets = facetoface_get_available_assets($now + (DAYSECS * 1), $now + (DAYSECS * 20), 'fa.id, fa.custom', $sessionid1_1, $facetoface1->id);
        $this->assertCount(9, $assets);
        foreach ($assets as $asset) {
            $this->assertObjectHasAttribute('custom', $asset);
            $this->assertObjectNotHasAttribute('name', $asset);
        }

        // Test slot must have size.
        $assets = facetoface_get_available_assets(2, 1, 'fa.*', 0, 0);
        $this->assertDebuggingCalled();
        $this->assertCount(6, $assets);
        $this->assertArrayHasKey($sitewideasset1->id, $assets);
        $this->assertArrayHasKey($sitewideasset2->id, $assets);
        $this->assertArrayHasKey($sitewideasset4->id, $assets);
        $this->assertArrayHasKey($sitewideasset5->id, $assets);
        $this->assertArrayHasKey($customasset2->id, $assets);
        $this->assertArrayHasKey($customasset5->id, $assets);
    }

    /**
     * Advanced asset availability test with multiple assets.
     */
    public function test_facetoface_available_assets_multiple() {
        global $DB;

        $now = time();

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $sitewideasset1 = $this->facetoface_generator->add_site_wide_asset(array('name' => 'Site asset 1', 'allowconflicts' => 0, 'hidden' => 0));
        $sitewideasset2 = $this->facetoface_generator->add_site_wide_asset(array('name' => 'Site asset 2', 'allowconflicts' => 0, 'hidden' => 0));
        $sitewideasset3 = $this->facetoface_generator->add_site_wide_asset(array('name' => 'Site asset 3', 'allowconflicts' => 0, 'hidden' => 1));
        $sitewideasset4 = $this->facetoface_generator->add_site_wide_asset(array('name' => 'Site asset 4', 'allowconflicts' => 1, 'hidden' => 0));
        $sitewideasset5 = $this->facetoface_generator->add_site_wide_asset(array('name' => 'Site asset 5', 'allowconflicts' => 1, 'hidden' => 0));
        $sitewideasset6 = $this->facetoface_generator->add_site_wide_asset(array('name' => 'Site asset 6', 'allowconflicts' => 1, 'hidden' => 1));
        $customasset1 = $this->facetoface_generator->add_custom_asset(array('usercreated' => $user1->id, 'name' => 'Custom asset 1', 'allowconflicts' => 0));
        $customasset2 = $this->facetoface_generator->add_custom_asset(array('usercreated' => $user1->id, 'name' => 'Custom asset 2', 'allowconflicts' => 0));
        $customasset3 = $this->facetoface_generator->add_custom_asset(array('usercreated' => $user2->id, 'name' => 'Custom asset 3', 'allowconflicts' => 0));
        $customasset4 = $this->facetoface_generator->add_custom_asset(array('usercreated' => $user1->id, 'name' => 'Custom asset 4', 'allowconflicts' => 1));
        $customasset5 = $this->facetoface_generator->add_custom_asset(array('usercreated' => $user1->id, 'name' => 'Custom asset 5', 'allowconflicts' => 1));
        $customasset6 = $this->facetoface_generator->add_custom_asset(array('usercreated' => $user2->id, 'name' => 'Custom asset 6', 'allowconflicts' => 1));
        $allassets = $DB->get_records('facetoface_asset', array());

        $course = $this->getDataGenerator()->create_course();
        $facetoface1 = $this->facetoface_generator->create_instance(array('course' => $course->id));
        $facetoface2 = $this->facetoface_generator->create_instance(array('course' => $course->id));

        $sessiondates = array();
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * -2), $now + (DAYSECS * -1), $sitewideasset3->id, $sitewideasset1->id);
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 1), $now + (DAYSECS * 2), $sitewideasset1->id, $sitewideasset2->id, $customasset3->id);
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 2), $now + (DAYSECS * 3), $sitewideasset2->id);
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 3), $now + (DAYSECS * 4), $customasset1->id);
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 5), $now + (DAYSECS * 6), $customasset3->id);
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 7), $now + (DAYSECS * 8), $customasset4->id, $customasset3->id);
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 9), $now + (DAYSECS * 10));
        $sessionid1_1 = $this->facetoface_generator->add_session(array('facetoface' => $facetoface1->id, 'sessiondates' => $sessiondates));

        $sessionid1_2 = $this->facetoface_generator->add_session(array('facetoface' => $facetoface1->id, 'sessiondates' => array()));

        $sessiondates = array();
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 30), $now + (DAYSECS * 31), $sitewideasset1->id, $sitewideasset6->id);
        $sessionid1_3 = $this->facetoface_generator->add_session(array('facetoface' => $facetoface1->id, 'sessiondates' => $sessiondates));

        $sessiondates = array();
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 5), $now + (DAYSECS * 6), $customasset3->id);
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 9), $now + (DAYSECS * 10), $sitewideasset4->id);
        $sessionid2_1 = $this->facetoface_generator->add_session(array('facetoface' => $facetoface2->id, 'sessiondates' => $sessiondates));

        $this->setUser(null);

        $assets = facetoface_get_available_assets(0, 0, 'fa.*', 0, 0);
        $this->assertCount(4, $assets);
        $this->assertArrayHasKey($sitewideasset1->id, $assets);
        $this->assertArrayHasKey($sitewideasset2->id, $assets);
        $this->assertArrayHasKey($sitewideasset4->id, $assets);
        $this->assertArrayHasKey($sitewideasset5->id, $assets);
        foreach ($allassets as $asset) {
            if (isset($assets[$asset->id])) {
                $this->assertTrue(facetoface_is_asset_available(0, 0, $asset, 0, 0));
            } else {
                $this->assertFalse(facetoface_is_asset_available(0, 0, $asset, 0, 0));
            }
        }

        $assets = facetoface_get_available_assets($now + (DAYSECS * 1), $now + (DAYSECS * 2), 'fa.*', 0, 0);
        $this->assertCount(2, $assets);
        $this->assertArrayHasKey($sitewideasset4->id, $assets);
        $this->assertArrayHasKey($sitewideasset5->id, $assets);
        foreach ($allassets as $asset) {
            if (isset($assets[$asset->id])) {
                $this->assertTrue(facetoface_is_asset_available($now + (DAYSECS * 1), $now + (DAYSECS * 2), $asset, 0, 0));
            } else {
                $this->assertFalse(facetoface_is_asset_available($now + (DAYSECS * 1), $now + (DAYSECS * 2), $asset, 0, 0));
            }
        }

        $assets = facetoface_get_available_assets(0, 0, 'fa.*', 0, $facetoface1->id);
        $this->assertCount(7, $assets);
        $this->assertArrayHasKey($sitewideasset1->id, $assets);
        $this->assertArrayHasKey($sitewideasset2->id, $assets);
        $this->assertArrayHasKey($sitewideasset4->id, $assets);
        $this->assertArrayHasKey($sitewideasset5->id, $assets);
        $this->assertArrayHasKey($customasset1->id, $assets);
        $this->assertArrayHasKey($customasset3->id, $assets);
        $this->assertArrayHasKey($customasset4->id, $assets);
        foreach ($allassets as $asset) {
            if (isset($assets[$asset->id])) {
                $this->assertTrue(facetoface_is_asset_available(0, 0, $asset, 0, $facetoface1->id));
            } else {
                $this->assertFalse(facetoface_is_asset_available(0, 0, $asset, 0, $facetoface1->id));
            }
        }

        $assets = facetoface_get_available_assets($now + (DAYSECS * 1), $now + (DAYSECS * 2), 'fa.*', 0, $facetoface1->id);
        $this->assertCount(4, $assets);
        $this->assertArrayHasKey($sitewideasset4->id, $assets);
        $this->assertArrayHasKey($sitewideasset5->id, $assets);
        $this->assertArrayHasKey($customasset1->id, $assets);
        $this->assertArrayHasKey($customasset4->id, $assets);
        foreach ($allassets as $asset) {
            if (isset($assets[$asset->id])) {
                $this->assertTrue(facetoface_is_asset_available($now + (DAYSECS * 1), $now + (DAYSECS * 2), $asset, 0, $facetoface1->id));
            } else {
                $this->assertFalse(facetoface_is_asset_available($now + (DAYSECS * 1), $now + (DAYSECS * 2), $asset, 0, $facetoface1->id));
            }
        }

        $assets = facetoface_get_available_assets(0, 0, 'fa.*', $sessionid1_1, $facetoface1->id);
        $this->assertCount(8, $assets);
        $this->assertArrayHasKey($sitewideasset1->id, $assets);
        $this->assertArrayHasKey($sitewideasset2->id, $assets);
        $this->assertArrayHasKey($sitewideasset4->id, $assets);
        $this->assertArrayHasKey($sitewideasset3->id, $assets);
        $this->assertArrayHasKey($sitewideasset5->id, $assets);
        $this->assertArrayHasKey($customasset1->id, $assets);
        $this->assertArrayHasKey($customasset3->id, $assets);
        $this->assertArrayHasKey($customasset4->id, $assets);
        foreach ($allassets as $asset) {
            if (isset($assets[$asset->id])) {
                $this->assertTrue(facetoface_is_asset_available(0, 0, $asset, $sessionid1_1, $facetoface1->id));
            } else {
                $this->assertFalse(facetoface_is_asset_available(0, 0, $asset, $sessionid1_1, $facetoface1->id));
            }
        }

        $assets = facetoface_get_available_assets($now + (DAYSECS * 1), $now + (DAYSECS * 2), 'fa.*', $sessionid1_1, $facetoface1->id);
        $this->assertCount(8, $assets);
        $this->assertArrayHasKey($sitewideasset1->id, $assets);
        $this->assertArrayHasKey($sitewideasset2->id, $assets);
        $this->assertArrayHasKey($sitewideasset3->id, $assets);
        $this->assertArrayHasKey($sitewideasset4->id, $assets);
        $this->assertArrayHasKey($sitewideasset5->id, $assets);
        $this->assertArrayHasKey($customasset1->id, $assets);
        $this->assertArrayHasKey($customasset3->id, $assets);
        $this->assertArrayHasKey($customasset4->id, $assets);

        foreach ($allassets as $asset) {
            if (isset($assets[$asset->id])) {
                $this->assertTrue(facetoface_is_asset_available($now + (DAYSECS * 1), $now + (DAYSECS * 2), $asset, $sessionid1_1, $facetoface1->id));
            } else {
                $this->assertFalse(facetoface_is_asset_available($now + (DAYSECS * 1), $now + (DAYSECS * 2), $asset, $sessionid1_1, $facetoface1->id));
            }
        }

        $this->setUser($user1);

        $assets = facetoface_get_available_assets(0, 0, 'fa.*', 0, 0);
        $this->assertCount(6, $assets);
        $this->assertArrayHasKey($sitewideasset1->id, $assets);
        $this->assertArrayHasKey($sitewideasset2->id, $assets);
        $this->assertArrayHasKey($sitewideasset4->id, $assets);
        $this->assertArrayHasKey($sitewideasset5->id, $assets);
        $this->assertArrayHasKey($customasset2->id, $assets);
        $this->assertArrayHasKey($customasset5->id, $assets);
        foreach ($allassets as $asset) {
            if (isset($assets[$asset->id])) {
                $this->assertTrue(facetoface_is_asset_available(0, 0, $asset, 0, 0));
            } else {
                $this->assertFalse(facetoface_is_asset_available(0, 0, $asset, 0, 0));
            }
        }

        $assets = facetoface_get_available_assets($now + (DAYSECS * 1), $now + (DAYSECS * 2), 'fa.*', 0, 0);
        $this->assertCount(4, $assets);
        $this->assertArrayHasKey($sitewideasset4->id, $assets);
        $this->assertArrayHasKey($sitewideasset5->id, $assets);
        $this->assertArrayHasKey($customasset2->id, $assets);
        $this->assertArrayHasKey($customasset5->id, $assets);
        foreach ($allassets as $asset) {
            if (isset($assets[$asset->id])) {
                $this->assertTrue(facetoface_is_asset_available($now + (DAYSECS * 1), $now + (DAYSECS * 2), $asset, 0, 0));
            } else {
                $this->assertFalse(facetoface_is_asset_available($now + (DAYSECS * 1), $now + (DAYSECS * 2), $asset, 0, 0));
            }
        }

        $assets = facetoface_get_available_assets(0, 0, 'fa.*', 0, $facetoface1->id);
        $this->assertCount(9, $assets);
        $this->assertArrayHasKey($sitewideasset1->id, $assets);
        $this->assertArrayHasKey($sitewideasset2->id, $assets);
        $this->assertArrayHasKey($sitewideasset4->id, $assets);
        $this->assertArrayHasKey($sitewideasset5->id, $assets);
        $this->assertArrayHasKey($customasset1->id, $assets);
        $this->assertArrayHasKey($customasset2->id, $assets);
        $this->assertArrayHasKey($customasset3->id, $assets);
        $this->assertArrayHasKey($customasset4->id, $assets);
        $this->assertArrayHasKey($customasset5->id, $assets);
        foreach ($allassets as $asset) {
            if (isset($assets[$asset->id])) {
                $this->assertTrue(facetoface_is_asset_available(0, 0, $asset, 0, $facetoface1->id));
            } else {
                $this->assertFalse(facetoface_is_asset_available(0, 0, $asset, 0, $facetoface1->id));
            }
        }

        $assets = facetoface_get_available_assets($now + (DAYSECS * 1), $now + (DAYSECS * 2), 'fa.*', 0, $facetoface1->id);
        $this->assertCount(6, $assets);
        $this->assertArrayHasKey($sitewideasset4->id, $assets);
        $this->assertArrayHasKey($sitewideasset5->id, $assets);
        $this->assertArrayHasKey($customasset1->id, $assets);
        $this->assertArrayHasKey($customasset2->id, $assets);
        $this->assertArrayHasKey($customasset4->id, $assets);
        $this->assertArrayHasKey($customasset5->id, $assets);
        foreach ($allassets as $asset) {
            if (isset($assets[$asset->id])) {
                $this->assertTrue(facetoface_is_asset_available($now + (DAYSECS * 1), $now + (DAYSECS * 2), $asset, 0, $facetoface1->id));
            } else {
                $this->assertFalse(facetoface_is_asset_available($now + (DAYSECS * 1), $now + (DAYSECS * 2), $asset, 0, $facetoface1->id));
            }
        }

        $assets = facetoface_get_available_assets(0, 0, 'fa.*', $sessionid1_1, $facetoface1->id);
        $this->assertCount(10, $assets);
        $this->assertArrayHasKey($sitewideasset1->id, $assets);
        $this->assertArrayHasKey($sitewideasset2->id, $assets);
        $this->assertArrayHasKey($sitewideasset4->id, $assets);
        $this->assertArrayHasKey($sitewideasset3->id, $assets);
        $this->assertArrayHasKey($sitewideasset5->id, $assets);
        $this->assertArrayHasKey($customasset1->id, $assets);
        $this->assertArrayHasKey($customasset2->id, $assets);
        $this->assertArrayHasKey($customasset3->id, $assets);
        $this->assertArrayHasKey($customasset5->id, $assets);
        $this->assertArrayHasKey($customasset4->id, $assets);
        foreach ($allassets as $asset) {
            if (isset($assets[$asset->id])) {
                $this->assertTrue(facetoface_is_asset_available(0, 0, $asset, $sessionid1_1, $facetoface1->id));
            } else {
                $this->assertFalse(facetoface_is_asset_available(0, 0, $asset, $sessionid1_1, $facetoface1->id));
            }
        }

        $assets = facetoface_get_available_assets($now + (DAYSECS * 1), $now + (DAYSECS * 2), 'fa.*', $sessionid1_1, $facetoface1->id);
        $this->assertCount(10, $assets);
        $this->assertArrayHasKey($sitewideasset1->id, $assets);
        $this->assertArrayHasKey($sitewideasset2->id, $assets);
        $this->assertArrayHasKey($sitewideasset3->id, $assets);
        $this->assertArrayHasKey($sitewideasset4->id, $assets);
        $this->assertArrayHasKey($sitewideasset5->id, $assets);
        $this->assertArrayHasKey($customasset1->id, $assets);
        $this->assertArrayHasKey($customasset2->id, $assets);
        $this->assertArrayHasKey($customasset3->id, $assets);
        $this->assertArrayHasKey($customasset5->id, $assets);
        $this->assertArrayHasKey($customasset4->id, $assets);
        foreach ($allassets as $asset) {
            if (isset($assets[$asset->id])) {
                $this->assertTrue(facetoface_is_asset_available($now + (DAYSECS * 1), $now + (DAYSECS * 2), $asset, $sessionid1_1, $facetoface1->id));
            } else {
                $this->assertFalse(facetoface_is_asset_available($now + (DAYSECS * 1), $now + (DAYSECS * 2), $asset, $sessionid1_1, $facetoface1->id));
            }
        }
    }

    public function test_facetoface_asset_has_conflicts() {
        $now = time();

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $sitewideasset1 = $this->facetoface_generator->add_site_wide_asset(array('name' => 'Site asset 1', 'allowconflicts' => 0, 'hidden' => 0));
        $sitewideasset2 = $this->facetoface_generator->add_site_wide_asset(array('name' => 'Site asset 2', 'allowconflicts' => 0, 'hidden' => 0));
        $sitewideasset3 = $this->facetoface_generator->add_site_wide_asset(array('name' => 'Site asset 3', 'allowconflicts' => 0, 'hidden' => 1));
        $sitewideasset4 = $this->facetoface_generator->add_site_wide_asset(array('name' => 'Site asset 4', 'allowconflicts' => 1, 'hidden' => 0));
        $sitewideasset5 = $this->facetoface_generator->add_site_wide_asset(array('name' => 'Site asset 5', 'allowconflicts' => 1, 'hidden' => 0));
        $sitewideasset6 = $this->facetoface_generator->add_site_wide_asset(array('name' => 'Site asset 6', 'allowconflicts' => 1, 'hidden' => 1));
        $customasset1 = $this->facetoface_generator->add_custom_asset(array('usercreated' => $user1->id, 'name' => 'Custom asset 1', 'allowconflicts' => 0));
        $customasset2 = $this->facetoface_generator->add_custom_asset(array('usercreated' => $user1->id, 'name' => 'Custom asset 2', 'allowconflicts' => 0));
        $customasset3 = $this->facetoface_generator->add_custom_asset(array('usercreated' => $user2->id, 'name' => 'Custom asset 3', 'allowconflicts' => 0));
        $customasset4 = $this->facetoface_generator->add_custom_asset(array('usercreated' => $user1->id, 'name' => 'Custom asset 4', 'allowconflicts' => 1));
        $customasset5 = $this->facetoface_generator->add_custom_asset(array('usercreated' => $user1->id, 'name' => 'Custom asset 5', 'allowconflicts' => 1));
        $customasset6 = $this->facetoface_generator->add_custom_asset(array('usercreated' => $user2->id, 'name' => 'Custom asset 6', 'allowconflicts' => 1));

        $course = $this->getDataGenerator()->create_course();
        $facetoface1 = $this->facetoface_generator->create_instance(array('course' => $course->id));
        $facetoface2 = $this->facetoface_generator->create_instance(array('course' => $course->id));

        $sessiondates = array();
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 1), $now + (DAYSECS * 2), $sitewideasset1->id);
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 2), $now + (DAYSECS * 3), $sitewideasset2->id);
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * -2), $now + (DAYSECS * -1), $sitewideasset3->id);
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 9), $now + (DAYSECS * 10), $sitewideasset4->id);
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 3), $now + (DAYSECS * 4), $customasset1->id);
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 5), $now + (DAYSECS * 6), $customasset3->id);
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 7), $now + (DAYSECS * 8), $customasset4->id);
        $sessionid1_1 = $this->facetoface_generator->add_session(array('facetoface' => $facetoface1->id, 'sessiondates' => $sessiondates));

        $sessiondates = array();
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 1), $now + (DAYSECS * 3), $sitewideasset1->id);
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 2.5), $now + (DAYSECS * 4.5), $sitewideasset2->id);
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * -3), $now + (DAYSECS * -1.5), $sitewideasset3->id);
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 4), $now + (DAYSECS * 7), $customasset4->id);
        $sessionid1_2 = $this->facetoface_generator->add_session(array('facetoface' => $facetoface1->id, 'sessiondates' => $sessiondates));

        $sessiondates = array();
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 9), $now + (DAYSECS * 10), $sitewideasset4->id);
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 5.5), $now + (DAYSECS * 5.6), $customasset3->id);
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 8), $now + (DAYSECS * 9), $customasset4->id);
        $sessionid2_1 = $this->facetoface_generator->add_session(array('facetoface' => $facetoface2->id, 'sessiondates' => $sessiondates));

        $this->assertTrue(facetoface_asset_has_conflicts($sitewideasset1->id));
        $this->assertTrue(facetoface_asset_has_conflicts($sitewideasset2->id));
        $this->assertTrue(facetoface_asset_has_conflicts($sitewideasset3->id));
        $this->assertTrue(facetoface_asset_has_conflicts($sitewideasset4->id));
        $this->assertFalse(facetoface_asset_has_conflicts($sitewideasset5->id));
        $this->assertFalse(facetoface_asset_has_conflicts($sitewideasset6->id));
        $this->assertFalse(facetoface_asset_has_conflicts($customasset1->id));
        $this->assertFalse(facetoface_asset_has_conflicts($customasset2->id));
        $this->assertTrue(facetoface_asset_has_conflicts($customasset3->id));
        $this->assertFalse(facetoface_asset_has_conflicts($customasset4->id));
        $this->assertFalse(facetoface_asset_has_conflicts($customasset5->id));
        $this->assertFalse(facetoface_asset_has_conflicts($customasset6->id));
    }

    public function test_session_cancellation() {
        global $DB;

        $now = time();

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $sitewideasset1 = $this->facetoface_generator->add_site_wide_asset(array('name' => 'Site asset 1', 'allowconflicts' => 0, 'hidden' => 0));
        $sitewideasset2 = $this->facetoface_generator->add_site_wide_asset(array('name' => 'Site asset 2', 'allowconflicts' => 0, 'hidden' => 0));
        $sitewideasset3 = $this->facetoface_generator->add_site_wide_asset(array('name' => 'Site asset 3', 'allowconflicts' => 0, 'hidden' => 1));
        $sitewideasset4 = $this->facetoface_generator->add_site_wide_asset(array('name' => 'Site asset 4', 'allowconflicts' => 1, 'hidden' => 0));
        $sitewideasset5 = $this->facetoface_generator->add_site_wide_asset(array('name' => 'Site asset 5', 'allowconflicts' => 1, 'hidden' => 0));
        $sitewideasset6 = $this->facetoface_generator->add_site_wide_asset(array('name' => 'Site asset 6', 'allowconflicts' => 1, 'hidden' => 1));
        $customasset1 = $this->facetoface_generator->add_custom_asset(array('usercreated' => $user1->id, 'name' => 'Custom asset 1', 'allowconflicts' => 0));
        $customasset2 = $this->facetoface_generator->add_custom_asset(array('usercreated' => $user1->id, 'name' => 'Custom asset 2', 'allowconflicts' => 0));
        $customasset3 = $this->facetoface_generator->add_custom_asset(array('usercreated' => $user2->id, 'name' => 'Custom asset 3', 'allowconflicts' => 0));
        $customasset4 = $this->facetoface_generator->add_custom_asset(array('usercreated' => $user1->id, 'name' => 'Custom asset 4', 'allowconflicts' => 1));
        $customasset5 = $this->facetoface_generator->add_custom_asset(array('usercreated' => $user1->id, 'name' => 'Custom asset 5', 'allowconflicts' => 1));
        $customasset6 = $this->facetoface_generator->add_custom_asset(array('usercreated' => $user2->id, 'name' => 'Custom asset 6', 'allowconflicts' => 1));

        $course = $this->getDataGenerator()->create_course();
        $facetoface1 = $this->facetoface_generator->create_instance(array('course' => $course->id));
        $facetoface2 = $this->facetoface_generator->create_instance(array('course' => $course->id));

        $sessiondates = array();
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 1), $now + (DAYSECS * 2), $sitewideasset1->id);
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 2), $now + (DAYSECS * 3), $sitewideasset2->id);
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * -2), $now + (DAYSECS * -1), $sitewideasset3->id);
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 9), $now + (DAYSECS * 10), $sitewideasset4->id);
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 3), $now + (DAYSECS * 4), $customasset1->id);
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 5), $now + (DAYSECS * 6), $customasset3->id);
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 7), $now + (DAYSECS * 8), $customasset4->id);
        $sessionid1_1 = $this->facetoface_generator->add_session(array('facetoface' => $facetoface1->id, 'sessiondates' => $sessiondates));

        $sessiondates = array();
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 1), $now + (DAYSECS * 3), $sitewideasset1->id);
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 2.5), $now + (DAYSECS * 4.5), $sitewideasset2->id);
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * -3), $now + (DAYSECS * -1.5), $sitewideasset3->id);
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 4), $now + (DAYSECS * 7), $customasset4->id);
        $sessionid1_2 = $this->facetoface_generator->add_session(array('facetoface' => $facetoface1->id, 'sessiondates' => $sessiondates));

        $sessiondates = array();
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 9), $now + (DAYSECS * 10), $sitewideasset4->id);
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 5.5), $now + (DAYSECS * 5.6), $customasset3->id);
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 8), $now + (DAYSECS * 9), $customasset4->id);
        $sessionid2_1 = $this->facetoface_generator->add_session(array('facetoface' => $facetoface2->id, 'sessiondates' => $sessiondates));

        $session = $DB->get_record('facetoface_sessions', array('id' => $sessionid2_1));
        $session->sessiondates = facetoface_get_session_dates($session->id);

        facetoface_cancel_session($session, null);
        $dateids = $DB->get_fieldset_select('facetoface_sessions_dates', 'id', "sessionid = :sessionid", array('sessionid' => $session->id));
        foreach ($dateids as $did) {
            $this->assertFalse($DB->record_exists('facetoface_asset_dates', array('sessionsdateid' => $did)));
        }
    }

    protected function prepare_date($timestart, $timeend, $assetid1 = null, $assetid2 = null, $assetid3 = null) {
        $assetids = array();
        if ($assetid1) {
            $assetids[] = $assetid1;
        }
        if ($assetid2) {
            $assetids[] = $assetid2;
        }
        if ($assetid3) {
            $assetids[] = $assetid3;
        }
        $sessiondate = new stdClass();
        $sessiondate->timestart = (string)$timestart;
        $sessiondate->timefinish = (string)$timeend;
        $sessiondate->sessiontimezone = '99';
        $sessiondate->assetids = $assetids;
        return $sessiondate;
    }
}
