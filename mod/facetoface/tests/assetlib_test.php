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

use mod_facetoface\asset;
use mod_facetoface\asset_list;
use mod_facetoface\seminar_event;

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

        $sitewideassetclass = new asset($sitewideasset->id);
        $this->assertEquals($sitewideasset->id, $sitewideassetclass->get_id());
        $this->assertEquals($sitewideasset->name, $sitewideassetclass->get_name());

        $customassetclass = new asset($customasset->id);
        $this->assertEquals($customasset->id, $customassetclass->get_id());
        $this->assertEquals($customasset->name, $customassetclass->get_name());

        $invalidasset = new asset(0);
        $this->assertEmpty($invalidasset->get_id());
        $this->assertEmpty($invalidasset->get_name());

        try {
            $invalidasset = new asset(-1);
            $this->fail("Incorrect asset id should throw error");
        } catch (exception $e) {
            //Do nothing
        }
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

        $asset = new asset($sitewideasset->id);
        $asset->delete();
        $this->assertFalse($DB->record_exists('facetoface_asset', array('id' => $sitewideasset->id)));
        $this->assertTrue($DB->record_exists('facetoface_asset', array('id' => $customasset->id)));
        $sessiondate1 = $DB->get_record('facetoface_sessions_dates', array('sessionid' => $sessionid1), '*', MUST_EXIST);
        $sessiondate2 = $DB->get_record('facetoface_sessions_dates', array('sessionid' => $sessionid2), '*', MUST_EXIST);
        $this->assertCount(0, $DB->get_records('facetoface_asset_dates', array('assetid' => $sitewideasset->id)));
        $this->assertCount(1, $DB->get_records('facetoface_asset_dates', array('assetid' => $customasset->id)));
        $this->assertFalse($fs->file_exists_by_hash($sitefile->get_pathnamehash()));
        $this->assertTrue($fs->file_exists_by_hash($customfile->get_pathnamehash()));

        // Second delete should do nothing.
        $asset->delete();
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
        $seminarevent11 = new seminar_event($sessionid1_1);

        $sessionid1_2 = $this->facetoface_generator->add_session(array('facetoface' => $facetoface1->id, 'sessiondates' => array()));
        $seminarevent12 = new seminar_event($sessionid1_2);

        $sessiondates = array();
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 30), $now + (DAYSECS * 31), $sitewideasset1->id);
        $sessionid1_3 = $this->facetoface_generator->add_session(array('facetoface' => $facetoface1->id, 'sessiondates' => $sessiondates));
        $seminarevent13 = new seminar_event($sessionid1_3);

        $sessiondates = array();
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 5), $now + (DAYSECS * 6), $customasset3->id);
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 9), $now + (DAYSECS * 10), $sitewideasset4->id);
        $sessionid2_1 = $this->facetoface_generator->add_session(array('facetoface' => $facetoface2->id, 'sessiondates' => $sessiondates));
        $seminarevent21 = new seminar_event($sessionid2_1);

        $this->setUser(null);
        $tempevent = new seminar_event();

        // Get all site assets that are not hidden.

        $assets = asset_list::get_available(0, 0, new seminar_event());
        $this->assertCount(4, $assets);
        $this->assertTrue($assets->contains($sitewideasset1->id));
        $this->assertTrue($assets->contains($sitewideasset2->id));
        $this->assertTrue($assets->contains($sitewideasset4->id));
        $this->assertTrue($assets->contains($sitewideasset5->id));
        foreach ($allassets as $asset) {
            $asset = new asset($asset->id);
            if ($assets->contains($asset->get_id())) {
                $this->assertTrue($asset->is_available(0, 0, new seminar_event()));
            } else {
                $this->assertFalse($asset->is_available(0, 0, new seminar_event()));
            }
        }

        // Get available site assets for given slot.
        $assets = asset_list::get_available($now + (DAYSECS * -1), $now + (DAYSECS * 1), new seminar_event());
        $this->assertCount(4, $assets);
        $this->assertTrue($assets->contains($sitewideasset1->id));
        $this->assertTrue($assets->contains($sitewideasset2->id));
        $this->assertTrue($assets->contains($sitewideasset4->id));
        $this->assertTrue($assets->contains($sitewideasset5->id));
        foreach ($allassets as $asset) {
            $asset = new asset($asset->id);
            if ($assets->contains($asset->get_id())) {
                $this->assertTrue($asset->is_available($now + (DAYSECS * -1), $now + (DAYSECS * 1), new seminar_event()));
            } else {
                $this->assertFalse($asset->is_available($now + (DAYSECS * -1), $now + (DAYSECS * 1), new seminar_event()));
            }
        }

        $assets = asset_list::get_available($now + (DAYSECS * 1), $now + (DAYSECS * 2), new seminar_event());
        $this->assertCount(3, $assets);
        $this->assertTrue($assets->contains($sitewideasset2->id));
        $this->assertTrue($assets->contains($sitewideasset4->id));
        $this->assertTrue($assets->contains($sitewideasset5->id));
        foreach ($allassets as $asset) {
            $asset = new asset($asset->id);
            if ($assets->contains($asset->get_id())) {
                $this->assertTrue($asset->is_available($now + (DAYSECS * 1), $now + (DAYSECS * 2), new seminar_event()));
            } else {
                $this->assertFalse($asset->is_available($now + (DAYSECS * 1), $now + (DAYSECS * 2), new seminar_event()));
            }
        }

        $assets = asset_list::get_available($now + (DAYSECS * 0), $now + (DAYSECS * 3), new seminar_event());
        $this->assertCount(2, $assets);
        $this->assertTrue($assets->contains($sitewideasset4->id));
        $this->assertTrue($assets->contains($sitewideasset5->id));
        foreach ($allassets as $asset) {
            $asset = new asset($asset->id);
            if ($assets->contains($asset->get_id())) {
                $this->assertTrue($asset->is_available($now + (DAYSECS * 0), $now + (DAYSECS * 3), new seminar_event()));
            } else {
                $this->assertFalse($asset->is_available($now + (DAYSECS * 0), $now + (DAYSECS * 3), new seminar_event()));
            }
        }

        // Specify only seminar id such as when adding new session.
        $tempevent->set_facetoface($facetoface1->id);
        $assets = asset_list::get_available(0, 0, $tempevent);
        $this->assertCount(7, $assets);
        $this->assertTrue($assets->contains($sitewideasset1->id));
        $this->assertTrue($assets->contains($sitewideasset2->id));
        $this->assertTrue($assets->contains($sitewideasset4->id));
        $this->assertTrue($assets->contains($sitewideasset5->id));
        $this->assertTrue($assets->contains($customasset1->id));
        $this->assertTrue($assets->contains($customasset3->id));
        $this->assertTrue($assets->contains($customasset4->id));
        foreach ($allassets as $asset) {
            $asset = new asset($asset->id);
            $seminarevent = new seminar_event();
            $seminarevent->set_facetoface($facetoface1->id);
            if ($assets->contains($asset->get_id())) {
                $this->assertTrue($asset->is_available(0, 0, $seminarevent));
            } else {
                $this->assertFalse($asset->is_available(0, 0, $seminarevent));
            }
        }

        $tempevent->set_facetoface($facetoface2->id);
        $assets = asset_list::get_available(0, 0, $tempevent);
        $this->assertCount(5, $assets);
        $this->assertTrue($assets->contains($sitewideasset1->id));
        $this->assertTrue($assets->contains($sitewideasset2->id));
        $this->assertTrue($assets->contains($sitewideasset4->id));
        $this->assertTrue($assets->contains($sitewideasset5->id));
        $this->assertTrue($assets->contains($customasset3->id));
        foreach ($allassets as $asset) {
            $asset = new asset($asset->id);
            $seminarevent = new seminar_event();
            $seminarevent->set_facetoface($facetoface2->id);
            if ($assets->contains($asset->get_id())) {
                $this->assertTrue($asset->is_available(0, 0, $seminarevent));
            } else {
                $this->assertFalse($asset->is_available(0, 0, $seminarevent));
            }
        }

        $tempevent->set_facetoface($facetoface1->id);
        $assets = asset_list::get_available($now + (DAYSECS * -1), $now + (DAYSECS * 1), $tempevent);
        $this->assertCount(7, $assets);
        $this->assertTrue($assets->contains($sitewideasset1->id));
        $this->assertTrue($assets->contains($sitewideasset2->id));
        $this->assertTrue($assets->contains($sitewideasset4->id));
        $this->assertTrue($assets->contains($sitewideasset5->id));
        $this->assertTrue($assets->contains($customasset1->id));
        $this->assertTrue($assets->contains($customasset3->id));
        $this->assertTrue($assets->contains($customasset4->id));
        foreach ($allassets as $asset) {
            $asset = new asset($asset->id);
            $seminarevent = new seminar_event();
            $seminarevent->set_facetoface($facetoface1->id);
            if ($assets->contains($asset->get_id())) {
                $this->assertTrue($asset->is_available($now + (DAYSECS * -1), $now + (DAYSECS * 1), $seminarevent));
            } else {
                $this->assertFalse($asset->is_available($now + (DAYSECS * -1), $now + (DAYSECS * 1), $seminarevent));
            }
        }

        $assets = asset_list::get_available($now + (DAYSECS * 1), $now + (DAYSECS * 2), $tempevent);
        $this->assertCount(6, $assets);
        $this->assertTrue($assets->contains($sitewideasset2->id));
        $this->assertTrue($assets->contains($sitewideasset4->id));
        $this->assertTrue($assets->contains($sitewideasset5->id));
        $this->assertTrue($assets->contains($customasset1->id));
        $this->assertTrue($assets->contains($customasset3->id));
        $this->assertTrue($assets->contains($customasset4->id));
        foreach ($allassets as $asset) {
            $asset = new asset($asset->id);
            $seminarevent = new seminar_event();
            $seminarevent->set_facetoface($facetoface1->id);
            if ($assets->contains($asset->get_id())) {
                $this->assertTrue($asset->is_available($now + (DAYSECS * 1), $now + (DAYSECS * 2), $seminarevent));
            } else {
                $this->assertFalse($asset->is_available($now + (DAYSECS * 1), $now + (DAYSECS * 2), $seminarevent));
            }
        }

        $assets = asset_list::get_available($now + (DAYSECS * 0), $now + (DAYSECS * 3), $tempevent);
        $this->assertCount(5, $assets);
        $this->assertTrue($assets->contains($sitewideasset4->id));
        $this->assertTrue($assets->contains($sitewideasset5->id));
        $this->assertTrue($assets->contains($customasset1->id));
        $this->assertTrue($assets->contains($customasset3->id));
        $this->assertTrue($assets->contains($customasset4->id));
        foreach ($allassets as $asset) {
            $asset = new asset($asset->id);
            $seminarevent = new seminar_event();
            $seminarevent->set_facetoface($facetoface1->id);
            if ($assets->contains($asset->get_id())) {
                $this->assertTrue($asset->is_available($now + (DAYSECS * 0), $now + (DAYSECS * 3), $seminarevent));
            } else {
                $this->assertFalse($asset->is_available($now + (DAYSECS * 0), $now + (DAYSECS * 3), $seminarevent));
            }
        }

        $assets = asset_list::get_available($now + (DAYSECS * 1), $now + (DAYSECS * 20), $tempevent);
        $this->assertCount(3, $assets);
        $this->assertTrue($assets->contains($sitewideasset4->id));
        $this->assertTrue($assets->contains($sitewideasset5->id));
        $this->assertTrue($assets->contains($customasset4->id));
        foreach ($allassets as $asset) {
            $asset = new asset($asset->id);
            $seminarevent = new seminar_event();
            $seminarevent->set_facetoface($facetoface1->id);
            if ($assets->contains($asset->get_id())) {
                $this->assertTrue($asset->is_available($now + (DAYSECS * 1), $now + (DAYSECS * 20), $seminarevent));
            } else {
                $this->assertFalse($asset->is_available($now + (DAYSECS * 1), $now + (DAYSECS * 20), $seminarevent));
            }
        }

        $tempevent->set_facetoface($facetoface2->id);
        $assets = asset_list::get_available($now + (DAYSECS * 1), $now + (DAYSECS * 20), $tempevent);
        $this->assertCount(2, $assets);
        $this->assertTrue($assets->contains($sitewideasset4->id));
        $this->assertTrue($assets->contains($sitewideasset5->id));
        foreach ($allassets as $asset) {
            $asset = new asset($asset->id);
            $seminarevent = new seminar_event();
            $seminarevent->set_facetoface($facetoface2->id);
            if ($assets->contains($asset->get_id())) {
                $this->assertTrue($asset->is_available($now + (DAYSECS * 1), $now + (DAYSECS * 20), $seminarevent));
            } else {
                $this->assertFalse($asset->is_available($now + (DAYSECS * 1), $now + (DAYSECS * 20), $seminarevent));
            }
        }

        // Specify seminar id and session id such as when adding updating session.
        $assets = asset_list::get_available(0, 0, $seminarevent11);
        $this->assertCount(8, $assets);
        $this->assertTrue($assets->contains($sitewideasset1->id));
        $this->assertTrue($assets->contains($sitewideasset2->id));
        $this->assertTrue($assets->contains($sitewideasset4->id));
        $this->assertTrue($assets->contains($sitewideasset3->id));
        $this->assertTrue($assets->contains($sitewideasset5->id));
        $this->assertTrue($assets->contains($customasset1->id));
        $this->assertTrue($assets->contains($customasset3->id));
        $this->assertTrue($assets->contains($customasset4->id));
        foreach ($allassets as $asset) {
            $asset = new asset($asset->id);
            if ($assets->contains($asset->get_id())) {
                $this->assertTrue($asset->is_available(0, 0, $seminarevent11));
            } else {
                $this->assertFalse($asset->is_available(0, 0, $seminarevent11));
            }
        }

        $assets = asset_list::get_available(0, 0, $seminarevent12);
        $this->assertCount(7, $assets);
        $this->assertTrue($assets->contains($sitewideasset1->id));
        $this->assertTrue($assets->contains($sitewideasset2->id));
        $this->assertTrue($assets->contains($sitewideasset4->id));
        $this->assertTrue($assets->contains($sitewideasset5->id));
        $this->assertTrue($assets->contains($customasset1->id));
        $this->assertTrue($assets->contains($customasset3->id));
        $this->assertTrue($assets->contains($customasset4->id));
        foreach ($allassets as $asset) {
            $asset = new asset($asset->id);
            if ($assets->contains($asset->get_id())) {
                $this->assertTrue($asset->is_available(0, 0, $seminarevent12));
            } else {
                $this->assertFalse($asset->is_available(0, 0, $seminarevent12));
            }
        }

        $assets = asset_list::get_available(0, 0, $seminarevent21);
        $this->assertCount(5, $assets);
        $this->assertTrue($assets->contains($sitewideasset1->id));
        $this->assertTrue($assets->contains($sitewideasset2->id));
        $this->assertTrue($assets->contains($sitewideasset4->id));
        $this->assertTrue($assets->contains($sitewideasset5->id));
        $this->assertTrue($assets->contains($customasset3->id));
        foreach ($allassets as $asset) {
            $asset = new asset($asset->id);
            if ($assets->contains($asset->get_id())) {
                $this->assertTrue($asset->is_available(0, 0, $seminarevent21));
            } else {
                $this->assertFalse($asset->is_available(0, 0, $seminarevent21));
            }
        }

        $assets = asset_list::get_available($now + (DAYSECS * -1), $now + (DAYSECS * 1), $seminarevent11);
        $this->assertCount(8, $assets);
        $this->assertTrue($assets->contains($sitewideasset1->id));
        $this->assertTrue($assets->contains($sitewideasset2->id));
        $this->assertTrue($assets->contains($sitewideasset3->id));
        $this->assertTrue($assets->contains($sitewideasset4->id));
        $this->assertTrue($assets->contains($sitewideasset5->id));
        $this->assertTrue($assets->contains($customasset1->id));
        $this->assertTrue($assets->contains($customasset3->id));
        $this->assertTrue($assets->contains($customasset4->id));
        foreach ($allassets as $asset) {
            $asset = new asset($asset->id);
            if ($assets->contains($asset->get_id())) {
                $this->assertTrue($asset->is_available($now + (DAYSECS * -1), $now + (DAYSECS * 1), $seminarevent11));
            } else {
                $this->assertFalse($asset->is_available($now + (DAYSECS * -1), $now + (DAYSECS * 1), $seminarevent11));
            }
        }

        $assets = asset_list::get_available($now + (DAYSECS * 1), $now + (DAYSECS * 2), $seminarevent13);
        $this->assertCount(6, $assets);
        $this->assertTrue($assets->contains($sitewideasset2->id));
        $this->assertTrue($assets->contains($sitewideasset4->id));
        $this->assertTrue($assets->contains($sitewideasset5->id));
        $this->assertTrue($assets->contains($customasset1->id));
        $this->assertTrue($assets->contains($customasset3->id));
        $this->assertTrue($assets->contains($customasset4->id));
        foreach ($allassets as $asset) {
            $asset = new asset($asset->id);
            if ($assets->contains($asset->get_id())) {
                $this->assertTrue($asset->is_available($now + (DAYSECS * 1), $now + (DAYSECS * 2), $seminarevent13));
            } else {
                $this->assertFalse($asset->is_available($now + (DAYSECS * 1), $now + (DAYSECS * 2), $seminarevent13));
            }
        }

        $assets = asset_list::get_available($now + (DAYSECS * -1), $now + (DAYSECS * 1), $seminarevent12);
        $this->assertCount(7, $assets);
        $this->assertTrue($assets->contains($sitewideasset1->id));
        $this->assertTrue($assets->contains($sitewideasset2->id));
        $this->assertTrue($assets->contains($sitewideasset4->id));
        $this->assertTrue($assets->contains($sitewideasset5->id));
        $this->assertTrue($assets->contains($customasset1->id));
        $this->assertTrue($assets->contains($customasset3->id));
        $this->assertTrue($assets->contains($customasset4->id));
        foreach ($allassets as $asset) {
            $asset = new asset($asset->id);
            if ($assets->contains($asset->get_id())) {
                $this->assertTrue($asset->is_available($now + (DAYSECS * -1), $now + (DAYSECS * 1), $seminarevent12));
            } else {
                $this->assertFalse($asset->is_available($now + (DAYSECS * -1), $now + (DAYSECS * 1), $seminarevent12));
            }
        }

        $assets = asset_list::get_available($now + (DAYSECS * 1), $now + (DAYSECS * 2), $seminarevent11);
        $this->assertCount(8, $assets);
        $this->assertTrue($assets->contains($sitewideasset1->id));
        $this->assertTrue($assets->contains($sitewideasset2->id));
        $this->assertTrue($assets->contains($sitewideasset3->id));
        $this->assertTrue($assets->contains($sitewideasset4->id));
        $this->assertTrue($assets->contains($sitewideasset5->id));
        $this->assertTrue($assets->contains($customasset1->id));
        $this->assertTrue($assets->contains($customasset3->id));
        $this->assertTrue($assets->contains($customasset4->id));

        foreach ($allassets as $asset) {
            $asset = new asset($asset->id);
            if ($assets->contains($asset->get_id())) {
                $this->assertTrue($asset->is_available($now + (DAYSECS * 1), $now + (DAYSECS * 2), $seminarevent11));
            } else {
                $this->assertFalse($asset->is_available($now + (DAYSECS * 1), $now + (DAYSECS * 2), $seminarevent11));
            }
        }

        $assets = asset_list::get_available($now + (DAYSECS * 1), $now + (DAYSECS * 2), $seminarevent12);
        $this->assertCount(6, $assets);
        $this->assertTrue($assets->contains($sitewideasset2->id));
        $this->assertTrue($assets->contains($sitewideasset4->id));
        $this->assertTrue($assets->contains($sitewideasset5->id));
        $this->assertTrue($assets->contains($customasset1->id));
        $this->assertTrue($assets->contains($customasset3->id));
        $this->assertTrue($assets->contains($customasset4->id));
        foreach ($allassets as $asset) {
            $asset = new asset($asset->id);
            if ($assets->contains($asset->get_id())) {
                $this->assertTrue($asset->is_available($now + (DAYSECS * 1), $now + (DAYSECS * 2), $seminarevent12));
            } else {
                $this->assertFalse($asset->is_available($now + (DAYSECS * 1), $now + (DAYSECS * 2), $seminarevent12));
            }
        }

        $assets = asset_list::get_available($now + (DAYSECS * 0), $now + (DAYSECS * 3), $seminarevent11);
        $this->assertCount(8, $assets);
        $this->assertTrue($assets->contains($sitewideasset1->id));
        $this->assertTrue($assets->contains($sitewideasset2->id));
        $this->assertTrue($assets->contains($sitewideasset3->id));
        $this->assertTrue($assets->contains($sitewideasset4->id));
        $this->assertTrue($assets->contains($sitewideasset5->id));
        $this->assertTrue($assets->contains($customasset1->id));
        $this->assertTrue($assets->contains($customasset3->id));
        $this->assertTrue($assets->contains($customasset4->id));
        foreach ($allassets as $asset) {
            $asset = new asset($asset->id);
            if ($assets->contains($asset->get_id())) {
                $this->assertTrue($asset->is_available($now + (DAYSECS * 0), $now + (DAYSECS * 3), $seminarevent11));
            } else {
                $this->assertFalse($asset->is_available($now + (DAYSECS * 0), $now + (DAYSECS * 3), $seminarevent11));
            }
        }

        $assets = asset_list::get_available($now + (DAYSECS * 0), $now + (DAYSECS * 3), $seminarevent12);
        $this->assertCount(5, $assets);
        $this->assertTrue($assets->contains($sitewideasset4->id));
        $this->assertTrue($assets->contains($sitewideasset5->id));
        $this->assertTrue($assets->contains($customasset1->id));
        $this->assertTrue($assets->contains($customasset3->id));
        $this->assertTrue($assets->contains($customasset4->id));
        foreach ($allassets as $asset) {
            $asset = new asset($asset->id);
            if ($assets->contains($asset->get_id())) {
                $this->assertTrue($asset->is_available($now + (DAYSECS * 0), $now + (DAYSECS * 3), $seminarevent12));
            } else {
                $this->assertFalse($asset->is_available($now + (DAYSECS * 0), $now + (DAYSECS * 3), $seminarevent12));
            }
        }

        $assets = asset_list::get_available($now + (DAYSECS * 1), $now + (DAYSECS * 20), $seminarevent11);
        $this->assertCount(7, $assets);
        $this->assertTrue($assets->contains($sitewideasset1->id));
        $this->assertTrue($assets->contains($sitewideasset2->id));
        $this->assertTrue($assets->contains($sitewideasset3->id));
        $this->assertTrue($assets->contains($sitewideasset4->id));
        $this->assertTrue($assets->contains($sitewideasset5->id));
        $this->assertTrue($assets->contains($customasset1->id));
        $this->assertTrue($assets->contains($customasset4->id));

        foreach ($allassets as $asset) {
            $asset = new asset($asset->id);
            if ($assets->contains($asset->get_id())) {
                $this->assertTrue($asset->is_available($now + (DAYSECS * 1), $now + (DAYSECS * 20), $seminarevent11));
            } else {
                $this->assertFalse($asset->is_available($now + (DAYSECS * 1), $now + (DAYSECS * 20), $seminarevent11));
            }
        }

        $assets = asset_list::get_available($now + (DAYSECS * 1), $now + (DAYSECS * 20), $seminarevent12);
        $this->assertCount(3, $assets);
        $this->assertTrue($assets->contains($sitewideasset4->id));
        $this->assertTrue($assets->contains($sitewideasset5->id));
        $this->assertTrue($assets->contains($customasset4->id));
        foreach ($allassets as $asset) {
            $asset = new asset($asset->id);
            if ($assets->contains($asset->get_id())) {
                $this->assertTrue($asset->is_available($now + (DAYSECS * 1), $now + (DAYSECS * 20), $seminarevent12));
            } else {
                $this->assertFalse($asset->is_available($now + (DAYSECS * 1), $now + (DAYSECS * 20), $seminarevent12));
            }
        }

        $assets = asset_list::get_available($now + (DAYSECS * 1), $now + (DAYSECS * 20), $seminarevent21);
        $this->assertCount(2, $assets);
        $this->assertTrue($assets->contains($sitewideasset4->id));
        $this->assertTrue($assets->contains($sitewideasset5->id));
        foreach ($allassets as $asset) {
            $asset = new asset($asset->id);
            if ($assets->contains($asset->get_id())) {
                $this->assertTrue($asset->is_available($now + (DAYSECS * 1), $now + (DAYSECS * 20), $seminarevent21));
            } else {
                $this->assertFalse($asset->is_available($now + (DAYSECS * 1), $now + (DAYSECS * 20), $seminarevent21));
            }
        }

        // Now with user.

        $this->setUser($user1);

        $assets = asset_list::get_available(0, 0, new seminar_event());
        $this->assertCount(6, $assets);
        $this->assertTrue($assets->contains($sitewideasset1->id));
        $this->assertTrue($assets->contains($sitewideasset2->id));
        $this->assertTrue($assets->contains($sitewideasset4->id));
        $this->assertTrue($assets->contains($sitewideasset5->id));
        $this->assertTrue($assets->contains($customasset2->id));
        $this->assertTrue($assets->contains($customasset5->id));
        foreach ($allassets as $asset) {
            $asset = new asset($asset->id);
            if ($assets->contains($asset->get_id())) {
                $this->assertTrue($asset->is_available(0, 0, new seminar_event()));
            } else {
                $this->assertFalse($asset->is_available(0, 0, new seminar_event()));
            }
        }

        $assets = asset_list::get_available($now + (DAYSECS * -1), $now + (DAYSECS * 1), new seminar_event());
        $this->assertCount(6, $assets);
        $this->assertTrue($assets->contains($sitewideasset1->id));
        $this->assertTrue($assets->contains($sitewideasset2->id));
        $this->assertTrue($assets->contains($sitewideasset4->id));
        $this->assertTrue($assets->contains($sitewideasset5->id));
        $this->assertTrue($assets->contains($customasset2->id));
        $this->assertTrue($assets->contains($customasset5->id));
        foreach ($allassets as $asset) {
            $asset = new asset($asset->id);
            if ($assets->contains($asset->get_id())) {
                $this->assertTrue($asset->is_available($now + (DAYSECS * -1), $now + (DAYSECS * 1), new seminar_event()));
            } else {
                $this->assertFalse($asset->is_available($now + (DAYSECS * -1), $now + (DAYSECS * 1), new seminar_event()));
            }
        }

        $assets = asset_list::get_available($now + (DAYSECS * 0), $now + (DAYSECS * 3), new seminar_event());
        $this->assertCount(4, $assets);
        $this->assertTrue($assets->contains($sitewideasset4->id));
        $this->assertTrue($assets->contains($sitewideasset5->id));
        $this->assertTrue($assets->contains($customasset2->id));
        $this->assertTrue($assets->contains($customasset5->id));
        foreach ($allassets as $asset) {
            $asset = new asset($asset->id);
            if ($assets->contains($asset->get_id())) {
                $this->assertTrue($asset->is_available($now + (DAYSECS * 0), $now + (DAYSECS * 3), new seminar_event()));
            } else {
                $this->assertFalse($asset->is_available($now + (DAYSECS * 0), $now + (DAYSECS * 3), new seminar_event()));
            }
        }

        $assets = asset_list::get_available(0, 0, $seminarevent11);
        $this->assertCount(10, $assets);
        $this->assertTrue($assets->contains($sitewideasset1->id));
        $this->assertTrue($assets->contains($sitewideasset2->id));
        $this->assertTrue($assets->contains($sitewideasset3->id));
        $this->assertTrue($assets->contains($sitewideasset4->id));
        $this->assertTrue($assets->contains($sitewideasset5->id));
        $this->assertTrue($assets->contains($customasset1->id));
        $this->assertTrue($assets->contains($customasset3->id));
        $this->assertTrue($assets->contains($customasset4->id));
        $this->assertTrue($assets->contains($customasset2->id));
        $this->assertTrue($assets->contains($customasset5->id));
        foreach ($allassets as $asset) {
            $asset = new asset($asset->id);
            if ($assets->contains($asset->get_id())) {
                $this->assertTrue($asset->is_available(0, 0, $seminarevent11));
            } else {
                $this->assertFalse($asset->is_available(0, 0, $seminarevent11));
            }
        }

        $assets = asset_list::get_available(0, 0, $seminarevent12);
        $this->assertCount(9, $assets);
        $this->assertTrue($assets->contains($sitewideasset1->id));
        $this->assertTrue($assets->contains($sitewideasset2->id));
        $this->assertTrue($assets->contains($sitewideasset4->id));
        $this->assertTrue($assets->contains($sitewideasset5->id));
        $this->assertTrue($assets->contains($customasset1->id));
        $this->assertTrue($assets->contains($customasset3->id));
        $this->assertTrue($assets->contains($customasset4->id));
        $this->assertTrue($assets->contains($customasset2->id));
        $this->assertTrue($assets->contains($customasset5->id));
        foreach ($allassets as $asset) {
            $asset = new asset($asset->id);
            if ($assets->contains($asset->get_id())) {
                $this->assertTrue($asset->is_available(0, 0, $seminarevent12));
            } else {
                $this->assertFalse($asset->is_available(0, 0, $seminarevent12));
            }
        }

        $assets = asset_list::get_available($now + (DAYSECS * 1), $now + (DAYSECS * 20), $seminarevent11);
        $this->assertCount(9, $assets);
        $this->assertTrue($assets->contains($sitewideasset1->id));
        $this->assertTrue($assets->contains($sitewideasset2->id));
        $this->assertTrue($assets->contains($sitewideasset3->id));
        $this->assertTrue($assets->contains($sitewideasset4->id));
        $this->assertTrue($assets->contains($sitewideasset5->id));
        $this->assertTrue($assets->contains($customasset1->id));
        $this->assertTrue($assets->contains($customasset4->id));
        $this->assertTrue($assets->contains($customasset2->id));
        $this->assertTrue($assets->contains($customasset5->id));
        foreach ($allassets as $asset) {
            $asset = new asset($asset->id);
            if ($assets->contains($asset->get_id())) {
                $this->assertTrue($asset->is_available($now + (DAYSECS * 1), $now + (DAYSECS * 20), $seminarevent11));
            } else {
                $this->assertFalse($asset->is_available($now + (DAYSECS * 1), $now + (DAYSECS * 20), $seminarevent11));
            }
        }

        $assets = asset_list::get_available($now + (DAYSECS * 1), $now + (DAYSECS * 20), $seminarevent12);
        $this->assertCount(5, $assets);
        $this->assertTrue($assets->contains($sitewideasset4->id));
        $this->assertTrue($assets->contains($sitewideasset5->id));
        $this->assertTrue($assets->contains($customasset4->id));
        $this->assertTrue($assets->contains($customasset2->id));
        $this->assertTrue($assets->contains($customasset5->id));
        foreach ($allassets as $asset) {
            $asset = new asset($asset->id);
            if ($assets->contains($asset->get_id())) {
                $this->assertTrue($asset->is_available($now + (DAYSECS * 1), $now + (DAYSECS * 20), $seminarevent12));
            } else {
                $this->assertFalse($asset->is_available($now + (DAYSECS * 1), $now + (DAYSECS * 20), $seminarevent12));
            }
        }

        $assets = asset_list::get_available($now + (DAYSECS * 1), $now + (DAYSECS * 20), $seminarevent21);
        $this->assertCount(4, $assets);
        $this->assertTrue($assets->contains($sitewideasset4->id));
        $this->assertTrue($assets->contains($sitewideasset5->id));
        $this->assertTrue($assets->contains($customasset2->id));
        $this->assertTrue($assets->contains($customasset5->id));
        foreach ($allassets as $asset) {
            $asset = new asset($asset->id);
            if ($assets->contains($asset->get_id())) {
                $this->assertTrue($asset->is_available($now + (DAYSECS * 1), $now + (DAYSECS * 20), $seminarevent21));
            } else {
                $this->assertFalse($asset->is_available($now + (DAYSECS * 1), $now + (DAYSECS * 20), $seminarevent21));
            }
        }

        // Test slot must have size.
        $assets = asset_list::get_available(2, 1, new seminar_event());
        $this->assertDebuggingCalled();
        $this->assertCount(6, $assets);
        $this->assertTrue($assets->contains($sitewideasset1->id));
        $this->assertTrue($assets->contains($sitewideasset2->id));
        $this->assertTrue($assets->contains($sitewideasset4->id));
        $this->assertTrue($assets->contains($sitewideasset5->id));
        $this->assertTrue($assets->contains($customasset2->id));
        $this->assertTrue($assets->contains($customasset5->id));
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
        $seminarevent11 = new seminar_event($sessionid1_1);

        $sessionid1_2 = $this->facetoface_generator->add_session(array('facetoface' => $facetoface1->id, 'sessiondates' => array()));
        $seminarevent12 = new seminar_event($sessionid1_2);

        $sessiondates = array();
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 30), $now + (DAYSECS * 31), $sitewideasset1->id, $sitewideasset6->id);
        $sessionid1_3 = $this->facetoface_generator->add_session(array('facetoface' => $facetoface1->id, 'sessiondates' => $sessiondates));
        $seminarevent13 = new seminar_event($sessionid1_3);

        $sessiondates = array();
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 5), $now + (DAYSECS * 6), $customasset3->id);
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 9), $now + (DAYSECS * 10), $sitewideasset4->id);
        $sessionid2_1 = $this->facetoface_generator->add_session(array('facetoface' => $facetoface2->id, 'sessiondates' => $sessiondates));
        $seminarevent21 = new seminar_event($sessionid2_1);

        $tempevent = new seminar_event();
        $this->setUser(null);

        $assets = asset_list::get_available(0, 0, new seminar_event());
        $this->assertCount(4, $assets);
        $this->assertTrue($assets->contains($sitewideasset1->id));
        $this->assertTrue($assets->contains($sitewideasset2->id));
        $this->assertTrue($assets->contains($sitewideasset4->id));
        $this->assertTrue($assets->contains($sitewideasset5->id));
        foreach ($allassets as $asset) {
            $asset = new asset($asset->id);
            if ($assets->contains($asset->get_id())) {
                $this->assertTrue($asset->is_available(0, 0, new seminar_event()));
            } else {
                $this->assertFalse($asset->is_available(0, 0, new seminar_event()));
            }
        }

        $assets = asset_list::get_available($now + (DAYSECS * 1), $now + (DAYSECS * 2), new seminar_event());
        $this->assertCount(2, $assets);
        $this->assertTrue($assets->contains($sitewideasset4->id));
        $this->assertTrue($assets->contains($sitewideasset5->id));
        foreach ($allassets as $asset) {
            $asset = new asset($asset->id);
            if ($assets->contains($asset->get_id())) {
                $this->assertTrue($asset->is_available($now + (DAYSECS * 1), $now + (DAYSECS * 2), new seminar_event()));
            } else {
                $this->assertFalse($asset->is_available($now + (DAYSECS * 1), $now + (DAYSECS * 2), new seminar_event()));
            }
        }

        $tempevent->set_facetoface($facetoface1->id);
        $assets = asset_list::get_available(0, 0, $tempevent);
        $this->assertCount(7, $assets);
        $this->assertTrue($assets->contains($sitewideasset1->id));
        $this->assertTrue($assets->contains($sitewideasset2->id));
        $this->assertTrue($assets->contains($sitewideasset4->id));
        $this->assertTrue($assets->contains($sitewideasset5->id));
        $this->assertTrue($assets->contains($customasset1->id));
        $this->assertTrue($assets->contains($customasset3->id));
        $this->assertTrue($assets->contains($customasset4->id));
        foreach ($allassets as $asset) {
            $asset = new asset($asset->id);
            $seminarevent = new seminar_event();
            $seminarevent->set_facetoface($facetoface1->id);
            if ($assets->contains($asset->get_id())) {
                $this->assertTrue($asset->is_available(0, 0, $seminarevent));
            } else {
                $this->assertFalse($asset->is_available(0, 0, $seminarevent));
            }
        }

        $assets = asset_list::get_available($now + (DAYSECS * 1), $now + (DAYSECS * 2), $tempevent);
        $this->assertCount(4, $assets);
        $this->assertTrue($assets->contains($sitewideasset4->id));
        $this->assertTrue($assets->contains($sitewideasset5->id));
        $this->assertTrue($assets->contains($customasset1->id));
        $this->assertTrue($assets->contains($customasset4->id));
        foreach ($allassets as $asset) {
            $asset = new asset($asset->id);
            $seminarevent = new seminar_event();
            $seminarevent->set_facetoface($facetoface1->id);
            if ($assets->contains($asset->get_id())) {
                $this->assertTrue($asset->is_available($now + (DAYSECS * 1), $now + (DAYSECS * 2), $seminarevent));
            } else {
                $this->assertFalse($asset->is_available($now + (DAYSECS * 1), $now + (DAYSECS * 2), $seminarevent));
            }
        }

        $assets = asset_list::get_available(0, 0, $seminarevent11);
        $this->assertCount(8, $assets);
        $this->assertTrue($assets->contains($sitewideasset1->id));
        $this->assertTrue($assets->contains($sitewideasset2->id));
        $this->assertTrue($assets->contains($sitewideasset4->id));
        $this->assertTrue($assets->contains($sitewideasset3->id));
        $this->assertTrue($assets->contains($sitewideasset5->id));
        $this->assertTrue($assets->contains($customasset1->id));
        $this->assertTrue($assets->contains($customasset3->id));
        $this->assertTrue($assets->contains($customasset4->id));
        foreach ($allassets as $asset) {
            $asset = new asset($asset->id);
            if ($assets->contains($asset->get_id())) {
                $this->assertTrue($asset->is_available(0, 0, $seminarevent11));
            } else {
                $this->assertFalse($asset->is_available(0, 0, $seminarevent11));
            }
        }

        $assets = asset_list::get_available($now + (DAYSECS * 1), $now + (DAYSECS * 2), $seminarevent11);
        $this->assertCount(8, $assets);
        $this->assertTrue($assets->contains($sitewideasset1->id));
        $this->assertTrue($assets->contains($sitewideasset2->id));
        $this->assertTrue($assets->contains($sitewideasset3->id));
        $this->assertTrue($assets->contains($sitewideasset4->id));
        $this->assertTrue($assets->contains($sitewideasset5->id));
        $this->assertTrue($assets->contains($customasset1->id));
        $this->assertTrue($assets->contains($customasset3->id));
        $this->assertTrue($assets->contains($customasset4->id));

        foreach ($allassets as $asset) {
            $asset = new asset($asset->id);
            if ($assets->contains($asset->get_id())) {
                $this->assertTrue($asset->is_available($now + (DAYSECS * 1), $now + (DAYSECS * 2), $seminarevent11));
            } else {
                $this->assertFalse($asset->is_available($now + (DAYSECS * 1), $now + (DAYSECS * 2), $seminarevent11));
            }
        }

        $this->setUser($user1);

        $assets = asset_list::get_available(0, 0, new seminar_event());
        $this->assertCount(6, $assets);
        $this->assertTrue($assets->contains($sitewideasset1->id));
        $this->assertTrue($assets->contains($sitewideasset2->id));
        $this->assertTrue($assets->contains($sitewideasset4->id));
        $this->assertTrue($assets->contains($sitewideasset5->id));
        $this->assertTrue($assets->contains($customasset2->id));
        $this->assertTrue($assets->contains($customasset5->id));
        foreach ($allassets as $asset) {
            $asset = new asset($asset->id);
            if ($assets->contains($asset->get_id())) {
                $this->assertTrue($asset->is_available(0, 0, new seminar_event));
            } else {
                $this->assertFalse($asset->is_available(0, 0, new seminar_event()));
            }
        }

        $assets = asset_list::get_available($now + (DAYSECS * 1), $now + (DAYSECS * 2), new seminar_event());
        $this->assertCount(4, $assets);
        $this->assertTrue($assets->contains($sitewideasset4->id));
        $this->assertTrue($assets->contains($sitewideasset5->id));
        $this->assertTrue($assets->contains($customasset2->id));
        $this->assertTrue($assets->contains($customasset5->id));
        foreach ($allassets as $asset) {
            $asset = new asset($asset->id);
            if ($assets->contains($asset->get_id())) {
                $this->assertTrue($asset->is_available($now + (DAYSECS * 1), $now + (DAYSECS * 2), new seminar_event()));
            } else {
                $this->assertFalse($asset->is_available($now + (DAYSECS * 1), $now + (DAYSECS * 2), new seminar_event()));
            }
        }

        $tempevent->set_facetoface($facetoface1->id);
        $assets = asset_list::get_available(0, 0, $tempevent);
        $this->assertCount(9, $assets);
        $this->assertTrue($assets->contains($sitewideasset1->id));
        $this->assertTrue($assets->contains($sitewideasset2->id));
        $this->assertTrue($assets->contains($sitewideasset4->id));
        $this->assertTrue($assets->contains($sitewideasset5->id));
        $this->assertTrue($assets->contains($customasset1->id));
        $this->assertTrue($assets->contains($customasset2->id));
        $this->assertTrue($assets->contains($customasset3->id));
        $this->assertTrue($assets->contains($customasset4->id));
        $this->assertTrue($assets->contains($customasset5->id));
        foreach ($allassets as $asset) {
            $asset = new asset($asset->id);
            $seminarevent = new seminar_event();
            $seminarevent->set_facetoface($facetoface1->id);
            if ($assets->contains($asset->get_id())) {
                $this->assertTrue($asset->is_available(0, 0, $seminarevent));
            } else {
                $this->assertFalse($asset->is_available(0, 0, $seminarevent));
            }
        }

        $assets = asset_list::get_available($now + (DAYSECS * 1), $now + (DAYSECS * 2), $tempevent);
        $this->assertCount(6, $assets);
        $this->assertTrue($assets->contains($sitewideasset4->id));
        $this->assertTrue($assets->contains($sitewideasset5->id));
        $this->assertTrue($assets->contains($customasset1->id));
        $this->assertTrue($assets->contains($customasset2->id));
        $this->assertTrue($assets->contains($customasset4->id));
        $this->assertTrue($assets->contains($customasset5->id));
        foreach ($allassets as $asset) {
            $asset = new asset($asset->id);
            $seminarevent = new seminar_event();
            $seminarevent->set_facetoface($facetoface1->id);
            if ($assets->contains($asset->get_id())) {
                $this->assertTrue($asset->is_available($now + (DAYSECS * 1), $now + (DAYSECS * 2), $seminarevent));
            } else {
                $this->assertFalse($asset->is_available($now + (DAYSECS * 1), $now + (DAYSECS * 2), $seminarevent));
            }
        }

        $assets = asset_list::get_available(0, 0, $seminarevent11);
        $this->assertCount(10, $assets);
        $this->assertTrue($assets->contains($sitewideasset1->id));
        $this->assertTrue($assets->contains($sitewideasset2->id));
        $this->assertTrue($assets->contains($sitewideasset4->id));
        $this->assertTrue($assets->contains($sitewideasset3->id));
        $this->assertTrue($assets->contains($sitewideasset5->id));
        $this->assertTrue($assets->contains($customasset1->id));
        $this->assertTrue($assets->contains($customasset2->id));
        $this->assertTrue($assets->contains($customasset3->id));
        $this->assertTrue($assets->contains($customasset5->id));
        $this->assertTrue($assets->contains($customasset4->id));
        foreach ($allassets as $asset) {
            $asset = new asset($asset->id);
            if ($assets->contains($asset->get_id())) {
                $this->assertTrue($asset->is_available(0, 0, $seminarevent11));
            } else {
                $this->assertFalse($asset->is_available(0, 0, $seminarevent11));
            }
        }

        $assets = asset_list::get_available($now + (DAYSECS * 1), $now + (DAYSECS * 2), $seminarevent11);
        $this->assertCount(10, $assets);
        $this->assertTrue($assets->contains($sitewideasset1->id));
        $this->assertTrue($assets->contains($sitewideasset2->id));
        $this->assertTrue($assets->contains($sitewideasset3->id));
        $this->assertTrue($assets->contains($sitewideasset4->id));
        $this->assertTrue($assets->contains($sitewideasset5->id));
        $this->assertTrue($assets->contains($customasset1->id));
        $this->assertTrue($assets->contains($customasset2->id));
        $this->assertTrue($assets->contains($customasset3->id));
        $this->assertTrue($assets->contains($customasset5->id));
        $this->assertTrue($assets->contains($customasset4->id));
        foreach ($allassets as $asset) {
            $asset = new asset($asset->id);
            if ($assets->contains($asset->get_id())) {
                $this->assertTrue($asset->is_available($now + (DAYSECS * 1), $now + (DAYSECS * 2), $seminarevent11));
            } else {
                $this->assertFalse($asset->is_available($now + (DAYSECS * 1), $now + (DAYSECS * 2), $seminarevent11));
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
        $seminarevent11 = new seminar_event($sessionid1_1);

        $sessiondates = array();
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 1), $now + (DAYSECS * 3), $sitewideasset1->id);
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 2.5), $now + (DAYSECS * 4.5), $sitewideasset2->id);
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * -3), $now + (DAYSECS * -1.5), $sitewideasset3->id);
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 4), $now + (DAYSECS * 7), $customasset4->id);
        $sessionid1_2 = $this->facetoface_generator->add_session(array('facetoface' => $facetoface1->id, 'sessiondates' => $sessiondates));
        $seminarevent12 = new seminar_event($sessionid1_2);

        $sessiondates = array();
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 9), $now + (DAYSECS * 10), $sitewideasset4->id);
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 5.5), $now + (DAYSECS * 5.6), $customasset3->id);
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 8), $now + (DAYSECS * 9), $customasset4->id);
        $sessionid2_1 = $this->facetoface_generator->add_session(array('facetoface' => $facetoface2->id, 'sessiondates' => $sessiondates));
        $seminarevent21 = new seminar_event($sessionid2_1);

        $this->assertTrue((new asset($sitewideasset1->id))->has_conflicts());
        $this->assertTrue((new asset($sitewideasset2->id))->has_conflicts());
        $this->assertTrue((new asset($sitewideasset3->id))->has_conflicts());
        $this->assertTrue((new asset($sitewideasset4->id))->has_conflicts());
        $this->assertFalse((new asset($sitewideasset5->id))->has_conflicts());
        $this->assertFalse((new asset($sitewideasset6->id))->has_conflicts());
        $this->assertFalse((new asset($customasset1->id))->has_conflicts());
        $this->assertFalse((new asset($customasset2->id))->has_conflicts());
        $this->assertTrue((new asset($customasset3->id))->has_conflicts());
        $this->assertFalse((new asset($customasset4->id))->has_conflicts());
        $this->assertFalse((new asset($customasset5->id))->has_conflicts());
        $this->assertFalse((new asset($customasset6->id))->has_conflicts());
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
        $seminarevent11 = new seminar_event($sessionid1_1);

        $sessiondates = array();
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 1), $now + (DAYSECS * 3), $sitewideasset1->id);
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 2.5), $now + (DAYSECS * 4.5), $sitewideasset2->id);
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * -3), $now + (DAYSECS * -1.5), $sitewideasset3->id);
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 4), $now + (DAYSECS * 7), $customasset4->id);
        $sessionid1_2 = $this->facetoface_generator->add_session(array('facetoface' => $facetoface1->id, 'sessiondates' => $sessiondates));
        $seminarevent12 = new seminar_event($sessionid1_2);

        $sessiondates = array();
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 9), $now + (DAYSECS * 10), $sitewideasset4->id);
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 5.5), $now + (DAYSECS * 5.6), $customasset3->id);
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 8), $now + (DAYSECS * 9), $customasset4->id);
        $sessionid2_1 = $this->facetoface_generator->add_session(array('facetoface' => $facetoface2->id, 'sessiondates' => $sessiondates));
        $seminarevent21 = new seminar_event($sessionid2_1);

        $seminarevent = new \mod_facetoface\seminar_event($sessionid2_1);
        $seminarevent->cancel();
        $dateids = $DB->get_fieldset_select('facetoface_sessions_dates', 'id', "sessionid = :sessionid", array('sessionid' => $sessionid2_1));
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
