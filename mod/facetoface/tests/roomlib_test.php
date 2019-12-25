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
 * Unit tests for mod/facetoface/room/lib.php functions.
 */

use mod_facetoface\room;

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    // It must be included from a Moodle page.
}

global $CFG;
require_once($CFG->dirroot . '/mod/facetoface/lib.php');

class mod_facetoface_roomlib_testcase extends advanced_testcase {

    /** @var mod_facetoface_generator */
    protected $facetoface_generator;

    /** @var totara_customfield_generator */
    protected $customfield_generator;

    private $cfprefix = 'facetofaceroom', $cftableprefix = 'facetoface_room';

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

    public function test_facetoface_get_room() {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/totara/customfield/fieldlib.php');

        $sitewideroom = $this->facetoface_generator->add_site_wide_room(array());
        customfield_load_data($sitewideroom, 'facetofaceroom', 'facetoface_room');

        $customroom = $this->facetoface_generator->add_custom_room(array());
        customfield_load_data($customroom, 'facetofaceroom', 'facetoface_room');

        $this->assertCount(2, $DB->get_records('facetoface_room', array()));

        $room = new \mod_facetoface\room($sitewideroom->id);
        $this->assertEquals($sitewideroom->id, $room->get_id());
        $this->assertEquals($sitewideroom->name, $room->get_name());
        $this->assertEquals((boolean)$sitewideroom->custom, $room->get_custom());
        $this->assertEquals((boolean)$sitewideroom->hidden, $room->get_hidden());
        $this->assertEquals($sitewideroom->capacity, $room->get_capacity());
        $this->assertEquals((boolean)$sitewideroom->allowconflicts, $room->get_allowconflicts());

        $room = new \mod_facetoface\room($customroom->id);
        $this->assertEquals($customroom->id, $room->get_id());
        $this->assertEquals($customroom->name, $room->get_name());
        $this->assertEquals((boolean)$customroom->custom, $room->get_custom());
        $this->assertEquals((boolean)$customroom->hidden, $room->get_hidden());
        $this->assertEquals($customroom->capacity, $room->get_capacity());
        $this->assertEquals((boolean)$customroom->allowconflicts, $room->get_allowconflicts());

        $room = new \mod_facetoface\room();
        $this->assertFalse($room->exists());

        $room = new \mod_facetoface\room(0);
        $this->assertFalse($room->exists());

        try {
            $room = new \mod_facetoface\room(-1);
            $this->fail('Exception expected due to MUST_EXIST in database query');
        } catch (dml_missing_record_exception $e) {
            $this->assertEquals('invalidrecordunknown', $e->errorcode);
        }
    }

    public function test_facetoface_get_used_rooms() {
        $now = time();

        $sitewideroom1 = $this->facetoface_generator->add_site_wide_room(array('name' => 'Site x 1'));
        $sitewideroom2 = $this->facetoface_generator->add_site_wide_room(array('name' => 'Site a 2'));
        $sitewideroom3 = $this->facetoface_generator->add_site_wide_room(array('name' => 'Site b 3'));
        $customroom1 = $this->facetoface_generator->add_custom_room(array('name' => 'Custom 1'));
        $customroom2 = $this->facetoface_generator->add_custom_room(array('name' => 'Custom 2'));
        $customroom3 = $this->facetoface_generator->add_custom_room(array('name' => 'Custom 3'));

        $course = $this->getDataGenerator()->create_course();
        $facetoface1 = $this->facetoface_generator->create_instance(array('course' => $course->id));
        $facetoface2 = $this->facetoface_generator->create_instance(array('course' => $course->id));

        $sessiondates = array();
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 1), $now + (DAYSECS * 2), $sitewideroom1->id);
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 2), $now + (DAYSECS * 3), $sitewideroom2->id);
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 3), $now + (DAYSECS * 4), $customroom1->id);
        $sessionid1_1 = $this->facetoface_generator->add_session(array('facetoface' => $facetoface1->id, 'sessiondates' => $sessiondates));

        $sessiondates = array();
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 3), $now + (DAYSECS * 4), $customroom3->id);
        $sessionid1_2 = $this->facetoface_generator->add_session(array('facetoface' => $facetoface1->id, 'sessiondates' => $sessiondates));

        $sessiondates = array();
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 5), $now + (DAYSECS * 6), $customroom1->id);
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 9), $now + (DAYSECS * 10), $sitewideroom2->id);
        $sessionid2_1 = $this->facetoface_generator->add_session(array('facetoface' => $facetoface2->id, 'sessiondates' => $sessiondates));

        $rooms = \mod_facetoface\room_list::get_seminar_rooms($facetoface1->id);
        $this->assertTrue($rooms->contains($customroom1->id));
        $this->assertTrue($rooms->contains($customroom3->id));
        $this->assertTrue($rooms->contains($sitewideroom1->id));
        $this->assertTrue($rooms->contains($sitewideroom2->id));
        $this->assertEquals(4, $rooms->count(), 'unexpected amount of rooms used in seminar');
    }

    public function test_facetoface_get_session_rooms() {
        $now = time();

        $sitewideroom1 = $this->facetoface_generator->add_site_wide_room(array('name' => 'Site x 1'));
        customfield_load_data($sitewideroom1, 'facetofaceroom', 'facetoface_room');
        $sitewideroom2 = $this->facetoface_generator->add_site_wide_room(array('name' => 'Site a 2'));
        customfield_load_data($sitewideroom2 , 'facetofaceroom', 'facetoface_room');
        $sitewideroom3 = $this->facetoface_generator->add_site_wide_room(array('name' => 'Site b 3'));
        customfield_load_data($sitewideroom3, 'facetofaceroom', 'facetoface_room');
        $customroom1 = $this->facetoface_generator->add_custom_room(array('name' => 'Custom 1'));
        customfield_load_data($customroom1, 'facetofaceroom', 'facetoface_room');
        $customroom2 = $this->facetoface_generator->add_custom_room(array('name' => 'Custom 2'));
        customfield_load_data($customroom2, 'facetofaceroom', 'facetoface_room');
        $customroom3 = $this->facetoface_generator->add_custom_room(array('name' => 'Custom 3'));
        customfield_load_data($customroom3, 'facetofaceroom', 'facetoface_room');

        $course = $this->getDataGenerator()->create_course();
        $facetoface1 = $this->facetoface_generator->create_instance(array('course' => $course->id));
        $facetoface2 = $this->facetoface_generator->create_instance(array('course' => $course->id));

        $sessiondates = array();
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 1), $now + (DAYSECS * 2), $sitewideroom1->id);
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 2), $now + (DAYSECS * 3), $sitewideroom2->id);
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 3), $now + (DAYSECS * 4), $customroom1->id);
        $sessionid1_1 = $this->facetoface_generator->add_session(array('facetoface' => $facetoface1->id, 'sessiondates' => $sessiondates));

        $sessiondates = array();
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 3), $now + (DAYSECS * 4), $customroom3->id);
        $sessionid1_2 = $this->facetoface_generator->add_session(array('facetoface' => $facetoface1->id, 'sessiondates' => $sessiondates));

        $sessiondates = array();
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 5), $now + (DAYSECS * 6), $customroom1->id);
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 9), $now + (DAYSECS * 10), $sitewideroom2->id);
        $sessionid2_1 = $this->facetoface_generator->add_session(array('facetoface' => $facetoface2->id, 'sessiondates' => $sessiondates));

        $rooms = \mod_facetoface\room_list::get_event_rooms($sessionid1_1);
        $this->assertTrue($rooms->contains($customroom1->id));
        $this->assertTrue($rooms->contains($sitewideroom1->id));
        $this->assertTrue($rooms->contains($sitewideroom2->id));
    }

    /**
     * Basic tests for room deletes.
     */
    public function test_facetoface_delete_room() {
        global $DB;

        $fs = get_file_storage();
        $syscontext = context_system::instance();

        $sitewideroom = $this->facetoface_generator->add_site_wide_room(array());
        $filerecord = array(
            'contextid' => $syscontext->id,
            'component' => 'mod_facetoface',
            'filearea' => 'room',
            'itemid' => $sitewideroom->id,
            'filepath' => '/',
            'filename' => 'xx.jpg',
        );
        $sitefile = $fs->create_file_from_string($filerecord, 'xx');

        $customroom = $this->facetoface_generator->add_custom_room(array());
        $filerecord = array(
            'contextid' => $syscontext->id,
            'component' => 'mod_facetoface',
            'filearea' => 'room',
            'itemid' => $customroom->id,
            'filepath' => '/',
            'filename' => 'xx.jpg',
        );
        $customfile = $fs->create_file_from_string($filerecord, 'xx');

        $this->assertCount(2, $DB->get_records('facetoface_room', array()));

        $course = $this->getDataGenerator()->create_course();
        $facetoface = $this->facetoface_generator->create_instance(array('course' => $course->id));

        $sessiondate1 = new stdClass();
        $sessiondate1->timestart = time() + (DAYSECS * 1);
        $sessiondate1->timefinish = $sessiondate1->timestart + (DAYSECS * 1);
        $sessiondate1->sessiontimezone = '99';
        $sessiondate1->roomid = $sitewideroom->id;
        $sessionid1 = $this->facetoface_generator->add_session(array('facetoface' => $facetoface->id, 'sessiondates' => array($sessiondate1)));
        $sessiondate1 = $DB->get_record('facetoface_sessions_dates', array('sessionid' => $sessionid1), '*', MUST_EXIST);
        $this->assertSame($sitewideroom->id, $sessiondate1->roomid);

        $sessiondate2 = new stdClass();
        $sessiondate2->timestart = time() + (DAYSECS * 2);
        $sessiondate2->timefinish = $sessiondate2->timestart + (DAYSECS * 2);
        $sessiondate2->sessiontimezone = '99';
        $sessiondate2->roomid = $customroom->id;
        $sessionid2 = $this->facetoface_generator->add_session(array('facetoface' => $facetoface->id, 'sessiondates' => array($sessiondate2)));
        $sessiondate2 = $DB->get_record('facetoface_sessions_dates', array('sessionid' => $sessionid2), '*', MUST_EXIST);
        $this->assertSame($customroom->id, $sessiondate2->roomid);

        $room = new room($sitewideroom->id);
        $room->delete();
        $this->assertFalse($DB->record_exists('facetoface_room', array('id' => $sitewideroom->id)));
        $this->assertTrue($DB->record_exists('facetoface_room', array('id' => $customroom->id)));
        $sessiondate1 = $DB->get_record('facetoface_sessions_dates', array('sessionid' => $sessionid1), '*', MUST_EXIST);
        $this->assertSame('0', $sessiondate1->roomid);
        $this->assertFalse($fs->file_exists_by_hash($sitefile->get_pathnamehash()));
        $sessiondate2 = $DB->get_record('facetoface_sessions_dates', array('sessionid' => $sessionid2), '*', MUST_EXIST);
        $this->assertSame($customroom->id, $sessiondate2->roomid);
        $this->assertTrue($fs->file_exists_by_hash($customfile->get_pathnamehash()));
    }

    /**
     * This is the most basic test to make sure that customfields are deleted
     * when a room is deleted via room_delete().
     */
    public function test_facetoface_delete_room_customfield_text() {
        $this->resetAfterTest(true);
        global $DB;

        $sitewideroom = $this->facetoface_generator->add_site_wide_room(array());
        // Create a room customfield, text type.
        $roomcftextids = $this->customfield_generator->create_text($this->cftableprefix, array('fullname' => 'roomcftext'));
        // Add some text to it.
        $this->customfield_generator->set_text($sitewideroom, $roomcftextids['roomcftext'], 'Some test text', $this->cfprefix, $this->cftableprefix);
        $cfdata = customfield_get_data($sitewideroom, $this->cftableprefix, $this->cfprefix);
        $this->assertEquals('Some test text', $cfdata['roomcftext']);
        $this->assertEquals(1, $DB->count_records('facetoface_room_info_data', array('facetofaceroomid' => $sitewideroom->id)));

        $room = new room($sitewideroom->id);
        $room->delete();

        // We'll make sure the site-wide room was definitely deleted.
        $this->assertEquals(0, $DB->count_records('facetoface_room', array('id' => $sitewideroom->id)));

        //Get the customfield data again after deletion.
        $cfdata = customfield_get_data($sitewideroom, $this->cftableprefix, $this->cfprefix);
        $this->assertEmpty($cfdata);
        $this->assertEquals(0, $DB->count_records('facetoface_room_info_data', array('facetofaceroomid' => $sitewideroom->id)));
    }

    /**
     * Tests that room_delete also gets rid of files records when
     * deleting custom fields.
     */
    public function test_facetoface_delete_room_customfield_file() {
        $this->resetAfterTest(true);
        global $DB;

        // Create both a site-wide and custom room.
        $sitewideroom = $this->facetoface_generator->add_site_wide_room(array());
        $customroom = $this->facetoface_generator->add_custom_room(array());

        // The file handing used by functions during this test requires $USER to be set.
        $this->setAdminUser();

        // Create a file custom field.
        $roomcffileids = $this->customfield_generator->create_file($this->cftableprefix, array('roomcffile' => array()));
        $roomcffileid = $roomcffileids['roomcffile'];

        // Create several files.
        $itemid1 = 1;
        $filename = 'testfile1.txt';
        $filecontent = 'Test file content';
        $testfile1 = $this->customfield_generator->create_test_file_from_content($filename, $filecontent, $itemid1);

        $itemid2 = 2;
        $filename = 'testfile1.txt';
        $filecontent = 'Test file content';
        $testfile1copy = $this->customfield_generator->create_test_file_from_content($filename, $filecontent, $itemid2);
        $filename = 'testfile2.txt';
        $filecontent = 'Other test file content';
        $testfile2 = $this->customfield_generator->create_test_file_from_content($filename, $filecontent, $itemid2);

        // Add $testfile1 only to the $sitewideroom.
        $this->customfield_generator->set_file($sitewideroom, $roomcffileid, $itemid1, $this->cfprefix, $this->cftableprefix);
        // Add both $testfile1 and $testfile2 to the $customroom.
        $this->customfield_generator->set_file($customroom, $roomcffileid, $itemid2, $this->cfprefix, $this->cftableprefix);
        //$this->customfield_generator->set_file($customroom, $roomcffileid, $testfile2, $this->cfprefix, $this->cftableprefix);

        $infodata_sitewide_cffile = $DB->get_record('facetoface_room_info_data',
            array('facetofaceroomid' => $sitewideroom->id, 'fieldid' => $roomcffileid));
        $this->assertNotEmpty($infodata_sitewide_cffile);
        // Sitewide should now have testfile1 but not testfile2.
        $this->assertEquals(1, $DB->count_records('files',
            array('filearea' => 'facetofaceroom_filemgr', 'filename' => 'testfile1.txt', 'itemid' => $infodata_sitewide_cffile->id)));
        $this->assertEquals(0, $DB->count_records('files',
            array('filearea' => 'facetofaceroom_filemgr', 'filename' => 'testfile2.txt', 'itemid' => $infodata_sitewide_cffile->id)));

        $infodata_custom_cffile = $DB->get_record('facetoface_room_info_data',
            array('facetofaceroomid' => $customroom->id, 'fieldid' => $roomcffileid));
        $this->assertNotEmpty($infodata_custom_cffile);
        // Sitewide should now have both testfile1 and testfile2.
        $this->assertEquals(1, $DB->count_records('files',
            array('filearea' => 'facetofaceroom_filemgr', 'filename' => 'testfile1.txt', 'itemid' => $infodata_custom_cffile->id)));
        $this->assertEquals(1, $DB->count_records('files',
            array('filearea' => 'facetofaceroom_filemgr', 'filename' => 'testfile2.txt', 'itemid' => $infodata_custom_cffile->id)));

        $room = new room($sitewideroom->id);
        $room->delete();

        // We'll make sure the site-wide room was definitely deleted and the custom room wasn't.
        $this->assertEquals(0, $DB->count_records('facetoface_room', array('id' => $sitewideroom->id)));
        $this->assertEquals(1, $DB->count_records('facetoface_room', array('id' => $customroom->id)));

        // We don't want to overwrite the original $infodata_sitewide_cffile object because we want to use
        // it's id value for the next check.
        $infodata_sitewide_cffile_again = $DB->get_record('facetoface_room_info_data',
            array('facetofaceroomid' => $sitewideroom->id, 'fieldid' => $roomcffileid));
        $this->assertEmpty($infodata_sitewide_cffile_again);
        // There should be no files left with the id from the info_data record.
        $this->assertEquals(0, $DB->count_records('files',
            array('filearea' => 'facetofaceroom_filemgr', 'itemid' => $infodata_sitewide_cffile->id)));

        // Nothing should have changed for the custom room values.
        $infodata_custom_cffile = $DB->get_record('facetoface_room_info_data',
            array('facetofaceroomid' => $customroom->id, 'fieldid' => $roomcffileid));
        $this->assertNotEmpty($infodata_custom_cffile);
        // Sitewide should now have both testfile1 and testfile2.
        $this->assertEquals(1, $DB->count_records('files',
            array('filearea' => 'facetofaceroom_filemgr', 'filename' => 'testfile1.txt', 'itemid' => $infodata_custom_cffile->id)));
        $this->assertEquals(1, $DB->count_records('files',
            array('filearea' => 'facetofaceroom_filemgr', 'filename' => 'testfile2.txt', 'itemid' => $infodata_custom_cffile->id)));

        // Now we get rid of the custom room to make sure nothing about it being custom prevents deletion of custom files.
        $room = new room($customroom->id);
        $room->delete();
        $infodata_custom_cffile_again = $DB->get_record('facetoface_room_info_data',
            array('facetofaceroomid' => $customroom->id, 'fieldid' => $roomcffileid));
        $this->assertEmpty($infodata_custom_cffile_again);
        // There should be no files left with the id from the info_data record.
        $this->assertEquals(0, $DB->count_records('files',
            array('filearea' => 'facetofaceroom_filemgr', 'itemid' => $infodata_custom_cffile->id)));
    }

    /**
     * Tests the that the deletion of rooms will also delete custom field data when there
     * are several types in use.
     */
    public function test_facetoface_delete_room_customfield_mixed() {
        $this->resetAfterTest(true);
        global $DB;

        // Create both a site-wide and custom room.
        $sitewideroom = $this->facetoface_generator->add_site_wide_room(array());
        $customroom = $this->facetoface_generator->add_custom_room(array());

        // The file handing used by functions during this test requires $USER to be set.
        $this->setAdminUser();

        // Create various custom fields, including datetime, file and text types.
        $roomcffileids = $this->customfield_generator->create_file($this->cftableprefix, array('roomcffile' => array()));
        $roomcffileid = $roomcffileids['roomcffile'];

        // Create a text custom field.
        $roomcftextids = $this->customfield_generator->create_text($this->cftableprefix, array('fullname' => 'roomcftext'));
        $roomcftextid = $roomcftextids['roomcftext'];

        $roomcfdateids = $this->customfield_generator->create_datetime($this->cftableprefix, array('roomcfdate' => array()));
        $roomcfdateid = $roomcfdateids['roomcfdate'];

        // Add data to the rooms for each custom field type.

        // Create several files.
        $itemid1 = 1;
        $filename = 'testfile1.txt';
        $filecontent = 'Test file content';
        $testfile1 = $this->customfield_generator->create_test_file_from_content($filename, $filecontent, $itemid1);

        $itemid2 = 2;
        $filename = 'testfile1.txt';
        $filecontent = 'Test file content';
        $testfile1copy = $this->customfield_generator->create_test_file_from_content($filename, $filecontent, $itemid2);
        $filename = 'testfile2.txt';
        $filecontent = 'Other test file content';
        $testfile2 = $this->customfield_generator->create_test_file_from_content($filename, $filecontent, $itemid2);

        // Add $testfile1 only to the $sitewideroom.
        $this->customfield_generator->set_file($sitewideroom, $roomcffileid, $itemid1, $this->cfprefix, $this->cftableprefix);
        // Add both $testfile1 and $testfile2 to the $customroom.
        $this->customfield_generator->set_file($customroom, $roomcffileid, $itemid2, $this->cfprefix, $this->cftableprefix);
        //$this->customfield_generator->set_file($customroom, $roomcffileid, $testfile2, $this->cfprefix, $this->cftableprefix);

        $this->customfield_generator->set_text($sitewideroom, $roomcftextid, 'Here is some text', $this->cfprefix, $this->cftableprefix);
        $this->customfield_generator->set_text($customroom, $roomcftextid, 'Some other text', $this->cfprefix, $this->cftableprefix);

        $sitewidedate = 1000000;
        $customdate = 200000000;
        $this->customfield_generator->set_datetime($sitewideroom, $roomcfdateid, $sitewidedate, $this->cfprefix, $this->cftableprefix);
        $this->customfield_generator->set_datetime($customroom, $roomcfdateid, $customdate, $this->cfprefix, $this->cftableprefix);

        // Check all the data is as expecting before deleting any room.
        $infodata_sitewide_cffile = $DB->get_record('facetoface_room_info_data',
            array('facetofaceroomid' => $sitewideroom->id, 'fieldid' => $roomcffileid));
        $this->assertNotEmpty($infodata_sitewide_cffile);
        // Sitewide should now have testfile1 but not testfile2.
        $this->assertEquals(1, $DB->count_records('files',
            array('filearea' => 'facetofaceroom_filemgr', 'filename' => 'testfile1.txt', 'itemid' => $infodata_sitewide_cffile->id)));
        $this->assertEquals(0, $DB->count_records('files',
            array('filearea' => 'facetofaceroom_filemgr', 'filename' => 'testfile2.txt', 'itemid' => $infodata_sitewide_cffile->id)));

        $infodata_custom_cffile = $DB->get_record('facetoface_room_info_data',
            array('facetofaceroomid' => $customroom->id, 'fieldid' => $roomcffileid));
        $this->assertNotEmpty($infodata_custom_cffile);
        // Sitewide should now have both testfile1 and testfile2.
        $this->assertEquals(1, $DB->count_records('files',
            array('filearea' => 'facetofaceroom_filemgr', 'filename' => 'testfile1.txt', 'itemid' => $infodata_custom_cffile->id)));
        $this->assertEquals(1, $DB->count_records('files',
            array('filearea' => 'facetofaceroom_filemgr', 'filename' => 'testfile2.txt', 'itemid' => $infodata_custom_cffile->id)));

        // We can't currently test files with whats returned from customfield_get_data, but we can text the others.
        $cfdata = customfield_get_data($sitewideroom, $this->cftableprefix, $this->cfprefix);

        $this->assertEquals('Here is some text', $cfdata['roomcftext']);
        $this->assertEquals(3, $DB->count_records('facetoface_room_info_data', array('facetofaceroomid' => $sitewideroom->id)));
        $this->assertEquals(userdate($sitewidedate, get_string('strftimedaydatetime', 'langconfig')), $cfdata['roomcfdate']);

        $cfdata = customfield_get_data($customroom, $this->cftableprefix, $this->cfprefix);

        $this->assertEquals('Some other text', $cfdata['roomcftext']);
        $this->assertEquals(3, $DB->count_records('facetoface_room_info_data', array('facetofaceroomid' => $customroom->id)));
        $this->assertEquals(userdate($customdate, get_string('strftimedaydatetime', 'langconfig')), $cfdata['roomcfdate']);

        // Now we'll delete the custom room.
        $room = new room($customroom->id);
        $room->delete();

        // We'll make sure the custom room was definitely deleted and the site-wide room wasn't.
        $this->assertEquals(1, $DB->count_records('facetoface_room', array('id' => $sitewideroom->id)));
        $this->assertEquals(0, $DB->count_records('facetoface_room', array('id' => $customroom->id)));

        // Let's check the files first.

        $infodata_sitewide_cffile = $DB->get_record('facetoface_room_info_data',
            array('facetofaceroomid' => $sitewideroom->id, 'fieldid' => $roomcffileid));
        $this->assertNotEmpty($infodata_sitewide_cffile);
        $this->assertEquals(1, $DB->count_records('files',
            array('filearea' => 'facetofaceroom_filemgr', 'filename' => 'testfile1.txt', 'itemid' => $infodata_sitewide_cffile->id)));

        // Nothing should have changed for the custom room values.
        $infodata_custom_cffile_again = $DB->get_record('facetoface_room_info_data',
            array('facetofaceroomid' => $customroom->id, 'fieldid' => $roomcffileid));
        $this->assertEmpty($infodata_custom_cffile_again);
        // There should be no files left with the id from the info_data record.
        $this->assertEquals(0, $DB->count_records('files',
            array('filearea' => 'facetofaceroom_filemgr', 'itemid' => $infodata_custom_cffile->id)));

        // Now the rest of the fields.
        $cfdata = customfield_get_data($sitewideroom, $this->cftableprefix, $this->cfprefix);

        $this->assertEquals('Here is some text', $cfdata['roomcftext']);
        $this->assertEquals(3, $DB->count_records('facetoface_room_info_data', array('facetofaceroomid' => $sitewideroom->id)));
        $this->assertEquals(userdate($sitewidedate, get_string('strftimedaydatetime', 'langconfig')), $cfdata['roomcfdate']);

        $cfdata = customfield_get_data($customroom, $this->cftableprefix, $this->cfprefix);

        $this->assertEmpty($cfdata);
        $this->assertEquals(0, $DB->count_records('facetoface_room_info_data', array('facetofaceroomid' => $customroom->id)));
    }

    /**
     * Test room availability functions.
     */
    public function test_facetoface_available_rooms() {
        global $DB;

        $now = time();

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $sitewideroom1 = $this->facetoface_generator->add_site_wide_room(array('name' => 'Site room 1', 'allowconflicts' => 0, 'hidden' => 0));
        $sitewideroom2 = $this->facetoface_generator->add_site_wide_room(array('name' => 'Site room 2', 'allowconflicts' => 0, 'hidden' => 0));
        $sitewideroom3 = $this->facetoface_generator->add_site_wide_room(array('name' => 'Site room 3', 'allowconflicts' => 0, 'hidden' => 1));
        $sitewideroom4 = $this->facetoface_generator->add_site_wide_room(array('name' => 'Site room 4', 'allowconflicts' => 1, 'hidden' => 0));
        $sitewideroom5 = $this->facetoface_generator->add_site_wide_room(array('name' => 'Site room 5', 'allowconflicts' => 1, 'hidden' => 0));
        $sitewideroom6 = $this->facetoface_generator->add_site_wide_room(array('name' => 'Site room 6', 'allowconflicts' => 1, 'hidden' => 1));
        $customroom1 = $this->facetoface_generator->add_custom_room(array('usercreated' => $user1->id, 'name' => 'Custom room 1', 'allowconflicts' => 0));
        $customroom2 = $this->facetoface_generator->add_custom_room(array('usercreated' => $user1->id, 'name' => 'Custom room 2', 'allowconflicts' => 0));
        $customroom3 = $this->facetoface_generator->add_custom_room(array('usercreated' => $user2->id, 'name' => 'Custom room 3', 'allowconflicts' => 0));
        $customroom4 = $this->facetoface_generator->add_custom_room(array('usercreated' => $user1->id, 'name' => 'Custom room 4', 'allowconflicts' => 1));
        $customroom5 = $this->facetoface_generator->add_custom_room(array('usercreated' => $user1->id, 'name' => 'Custom room 5', 'allowconflicts' => 1));
        $customroom6 = $this->facetoface_generator->add_custom_room(array('usercreated' => $user2->id, 'name' => 'Custom room 6', 'allowconflicts' => 1));
        $allrooms = new \mod_facetoface\room_list();

        $course = $this->getDataGenerator()->create_course();
        $facetoface1 = $this->facetoface_generator->create_instance(array('course' => $course->id));
        $facetoface2 = $this->facetoface_generator->create_instance(array('course' => $course->id));

        $sessiondates = array();
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * -2), $now + (DAYSECS * -1), $sitewideroom3->id);
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 1), $now + (DAYSECS * 2), $sitewideroom1->id);
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 2), $now + (DAYSECS * 3), $sitewideroom2->id);
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 3), $now + (DAYSECS * 4), $customroom1->id);
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 5), $now + (DAYSECS * 6), $customroom3->id);
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 7), $now + (DAYSECS * 8), $customroom4->id);
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 9), $now + (DAYSECS * 10), $sitewideroom4->id);
        $sessionid1_1 = $this->facetoface_generator->add_session(array('facetoface' => $facetoface1->id, 'sessiondates' => $sessiondates));
        $event11 = new \mod_facetoface\seminar_event($sessionid1_1);

        $sessionid1_2 = $this->facetoface_generator->add_session(array('facetoface' => $facetoface1->id, 'sessiondates' => array()));
        $event12 = new \mod_facetoface\seminar_event($sessionid1_2);

        $sessiondates = array();
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 30), $now + (DAYSECS * 31), $sitewideroom1->id);
        $sessionid1_3 = $this->facetoface_generator->add_session(array('facetoface' => $facetoface1->id, 'sessiondates' => $sessiondates));
        $event13 = new \mod_facetoface\seminar_event($sessionid1_3);

        $sessiondates = array();
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 5), $now + (DAYSECS * 6), $customroom3->id);
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 9), $now + (DAYSECS * 10), $sitewideroom4->id);
        $sessionid2_1 = $this->facetoface_generator->add_session(array('facetoface' => $facetoface2->id, 'sessiondates' => $sessiondates));
        $event21 = new \mod_facetoface\seminar_event($sessionid2_1);

        $this->setUser(null);

        // Set up some empty events for the tests.
        $event00 = new \mod_facetoface\seminar_event();
        $event10 = new \mod_facetoface\seminar_event();
        $event10->set_facetoface($facetoface1->id);
        $event20 = new \mod_facetoface\seminar_event();
        $event20->set_facetoface($facetoface2->id);

        // Get all site rooms that are not hidden.
        $rooms = \mod_facetoface\room_list::get_available_rooms(0, 0, $event00);
        $this->assertEquals(4, $rooms->count());
        $this->assertTrue($rooms->contains($sitewideroom1->id));
        $this->assertTrue($rooms->contains($sitewideroom2->id));
        $this->assertTrue($rooms->contains($sitewideroom4->id));
        $this->assertTrue($rooms->contains($sitewideroom5->id));
        foreach ($allrooms as $room) {
            if ($rooms->contains($room->get_id())) {
                $this->assertTrue($room->is_available(0, 0, $event00));
            } else {
                $this->assertFalse($room->is_available(0, 0, $event00));
            }
        }

        // Get available site rooms for given slot.
        $rooms = \mod_facetoface\room_list::get_available_rooms($now + (DAYSECS * -1), $now + (DAYSECS * 1), $event00);
        $this->assertEquals(4, $rooms->count());
        $this->assertTrue($rooms->contains($sitewideroom1->id));
        $this->assertTrue($rooms->contains($sitewideroom2->id));
        $this->assertTrue($rooms->contains($sitewideroom4->id));
        $this->assertTrue($rooms->contains($sitewideroom5->id));
        foreach ($allrooms as $room) {
            if ($rooms->contains($room->get_id())) {
                $this->assertTrue($room->is_available($now + (DAYSECS * -1), $now + (DAYSECS * 1), $event00));
            } else {
                $this->assertFalse($room->is_available($now + (DAYSECS * -1), $now + (DAYSECS * 1), $event00));
            }
        }

        $rooms = \mod_facetoface\room_list::get_available_rooms($now + (DAYSECS * 1), $now + (DAYSECS * 2), $event00);
        $this->assertEquals(3, $rooms->count());
        $this->assertTrue($rooms->contains($sitewideroom2->id));
        $this->assertTrue($rooms->contains($sitewideroom4->id));
        $this->assertTrue($rooms->contains($sitewideroom5->id));
        foreach ($allrooms as $room) {
            if ($rooms->contains($room->get_id())) {
                $this->assertTrue($room->is_available($now + (DAYSECS * 1), $now + (DAYSECS * 2), $event00));
            } else {
                $this->assertFalse($room->is_available($now + (DAYSECS * 1), $now + (DAYSECS * 2), $event00));
            }
        }

        $rooms = \mod_facetoface\room_list::get_available_rooms($now + (DAYSECS * 0), $now + (DAYSECS * 3), $event00);
        $this->assertEquals(2, $rooms->count());
        $this->assertTrue($rooms->contains($sitewideroom4->id));
        $this->assertTrue($rooms->contains($sitewideroom5->id));
        foreach ($allrooms as $room) {
            if ($rooms->contains($room->get_id())) {
                $this->assertTrue($room->is_available($now + (DAYSECS * 0), $now + (DAYSECS * 3), $event00));
            } else {
                $this->assertFalse($room->is_available($now + (DAYSECS * 0), $now + (DAYSECS * 3), $event00));
            }
        }

        // Specify only seminar id such as when adding new session.
        $rooms = \mod_facetoface\room_list::get_available_rooms(0, 0, $event10);
        $this->assertEquals(7, $rooms->count());
        $this->assertTrue($rooms->contains($sitewideroom1->id));
        $this->assertTrue($rooms->contains($sitewideroom2->id));
        $this->assertTrue($rooms->contains($sitewideroom4->id));
        $this->assertTrue($rooms->contains($sitewideroom5->id));
        $this->assertTrue($rooms->contains($customroom1->id));
        $this->assertTrue($rooms->contains($customroom3->id));
        $this->assertTrue($rooms->contains($customroom4->id));
        foreach ($allrooms as $room) {
            if ($rooms->contains($room->get_id())) {
                $this->assertTrue($room->is_available(0, 0, $event10));
            } else {
                $this->assertFalse($room->is_available(0, 0, $event10));
            }
        }

        $rooms = \mod_facetoface\room_list::get_available_rooms(0, 0, $event20);
        $this->assertEquals(5, $rooms->count());
        $this->assertTrue($rooms->contains($sitewideroom1->id));
        $this->assertTrue($rooms->contains($sitewideroom2->id));
        $this->assertTrue($rooms->contains($sitewideroom4->id));
        $this->assertTrue($rooms->contains($sitewideroom5->id));
        $this->assertTrue($rooms->contains($customroom3->id));
        foreach ($allrooms as $room) {
            if ($rooms->contains($room->get_id())) {
                $this->assertTrue($room->is_available(0, 0, $event20));
            } else {
                $this->assertFalse($room->is_available(0, 0, $event20));
            }
        }

        $rooms = \mod_facetoface\room_list::get_available_rooms($now + (DAYSECS * -1), $now + (DAYSECS * 1), $event10);
        $this->assertEquals(7, $rooms->count());
        $this->assertTrue($rooms->contains($sitewideroom1->id));
        $this->assertTrue($rooms->contains($sitewideroom2->id));
        $this->assertTrue($rooms->contains($sitewideroom4->id));
        $this->assertTrue($rooms->contains($sitewideroom5->id));
        $this->assertTrue($rooms->contains($customroom1->id));
        $this->assertTrue($rooms->contains($customroom3->id));
        $this->assertTrue($rooms->contains($customroom4->id));
        foreach ($allrooms as $room) {
            if ($rooms->contains($room->get_id())) {
                $this->assertTrue($room->is_available($now + (DAYSECS * -1), $now + (DAYSECS * 1), $event10));
            } else {
                $this->assertFalse($room->is_available($now + (DAYSECS * -1), $now + (DAYSECS * 1), $event10));
            }
        }

        $rooms = \mod_facetoface\room_list::get_available_rooms($now + (DAYSECS * 1), $now + (DAYSECS * 2), $event10);
        $this->assertEquals(6, $rooms->count());
        $this->assertTrue($rooms->contains($sitewideroom2->id));
        $this->assertTrue($rooms->contains($sitewideroom4->id));
        $this->assertTrue($rooms->contains($sitewideroom5->id));
        $this->assertTrue($rooms->contains($customroom1->id));
        $this->assertTrue($rooms->contains($customroom3->id));
        $this->assertTrue($rooms->contains($customroom4->id));
        foreach ($allrooms as $room) {
            if ($rooms->contains($room->get_id())) {
                $this->assertTrue($room->is_available($now + (DAYSECS * 1), $now + (DAYSECS * 2), $event10));
            } else {
                $this->assertFalse($room->is_available($now + (DAYSECS * 1), $now + (DAYSECS * 2), $event10));
            }
        }

        $rooms = \mod_facetoface\room_list::get_available_rooms($now + (DAYSECS * 0), $now + (DAYSECS * 3), $event10);
        $this->assertEquals(5, $rooms->count());
        $this->assertTrue($rooms->contains($sitewideroom4->id));
        $this->assertTrue($rooms->contains($sitewideroom5->id));
        $this->assertTrue($rooms->contains($customroom1->id));
        $this->assertTrue($rooms->contains($customroom3->id));
        $this->assertTrue($rooms->contains($customroom4->id));
        foreach ($allrooms as $room) {
            if ($rooms->contains($room->get_id())) {
                $this->assertTrue($room->is_available($now + (DAYSECS * 0), $now + (DAYSECS * 3), $event10));
            } else {
                $this->assertFalse($room->is_available($now + (DAYSECS * 0), $now + (DAYSECS * 3), $event10));
            }
        }

        $rooms = \mod_facetoface\room_list::get_available_rooms($now + (DAYSECS * 1), $now + (DAYSECS * 20), $event10);
        $this->assertEquals(3, $rooms->count());
        $this->assertTrue($rooms->contains($sitewideroom4->id));
        $this->assertTrue($rooms->contains($sitewideroom5->id));
        $this->assertTrue($rooms->contains($customroom4->id));
        foreach ($allrooms as $room) {
            if ($rooms->contains($room->get_id())) {
                $this->assertTrue($room->is_available($now + (DAYSECS * 1), $now + (DAYSECS * 20), $event10));
            } else {
                $this->assertFalse($room->is_available($now + (DAYSECS * 1), $now + (DAYSECS * 20), $event10));
            }
        }

        $rooms = \mod_facetoface\room_list::get_available_rooms($now + (DAYSECS * 1), $now + (DAYSECS * 20), $event20);
        $this->assertEquals(2, $rooms->count());
        $this->assertTrue($rooms->contains($sitewideroom4->id));
        $this->assertTrue($rooms->contains($sitewideroom5->id));
        foreach ($allrooms as $room) {
            if ($rooms->contains($room->get_id())) {
                $this->assertTrue($room->is_available($now + (DAYSECS * 1), $now + (DAYSECS * 20), $event20));
            } else {
                $this->assertFalse($room->is_available($now + (DAYSECS * 1), $now + (DAYSECS * 20), $event20));
            }
        }

        // Specify seminar id and session id such as when adding updating session.
        $rooms = \mod_facetoface\room_list::get_available_rooms(0, 0, $event11);
        $this->assertEquals(8, $rooms->count());
        $this->assertTrue($rooms->contains($sitewideroom1->id));
        $this->assertTrue($rooms->contains($sitewideroom2->id));
        $this->assertTrue($rooms->contains($sitewideroom3->id));
        $this->assertTrue($rooms->contains($sitewideroom4->id));
        $this->assertTrue($rooms->contains($sitewideroom5->id));
        $this->assertTrue($rooms->contains($customroom1->id));
        $this->assertTrue($rooms->contains($customroom3->id));
        $this->assertTrue($rooms->contains($customroom4->id));
        foreach ($allrooms as $room) {
            if ($rooms->contains($room->get_id())) {
                $this->assertTrue($room->is_available(0, 0, $event11));
            } else {
                $this->assertFalse($room->is_available(0, 0, $event11));
            }
        }

        $rooms = \mod_facetoface\room_list::get_available_rooms(0, 0, $event12);
        $this->assertEquals(7, $rooms->count());
        $this->assertTrue($rooms->contains($sitewideroom1->id));
        $this->assertTrue($rooms->contains($sitewideroom2->id));
        $this->assertTrue($rooms->contains($sitewideroom4->id));
        $this->assertTrue($rooms->contains($sitewideroom5->id));
        $this->assertTrue($rooms->contains($customroom1->id));
        $this->assertTrue($rooms->contains($customroom3->id));
        $this->assertTrue($rooms->contains($customroom4->id));
        foreach ($allrooms as $room) {
            if ($rooms->contains($room->get_id())) {
                $this->assertTrue($room->is_available(0, 0, $event12));
            } else {
                $this->assertFalse($room->is_available(0, 0, $event12));
            }
        }

        $rooms = \mod_facetoface\room_list::get_available_rooms(0, 0, $event21);
        $this->assertEquals(5, $rooms->count());
        $this->assertTrue($rooms->contains($sitewideroom1->id));
        $this->assertTrue($rooms->contains($sitewideroom2->id));
        $this->assertTrue($rooms->contains($sitewideroom4->id));
        $this->assertTrue($rooms->contains($sitewideroom5->id));
        $this->assertTrue($rooms->contains($customroom3->id));
        foreach ($allrooms as $room) {
            if ($rooms->contains($room->get_id())) {
                $this->assertTrue($room->is_available(0, 0, $event21));
            } else {
                $this->assertFalse($room->is_available(0, 0, $event21));
            }
        }

        $rooms = \mod_facetoface\room_list::get_available_rooms($now + (DAYSECS * -1), $now + (DAYSECS * 1), $event11);
        $this->assertEquals(8, $rooms->count());
        $this->assertTrue($rooms->contains($sitewideroom1->id));
        $this->assertTrue($rooms->contains($sitewideroom2->id));
        $this->assertTrue($rooms->contains($sitewideroom3->id));
        $this->assertTrue($rooms->contains($sitewideroom4->id));
        $this->assertTrue($rooms->contains($sitewideroom5->id));
        $this->assertTrue($rooms->contains($customroom1->id));
        $this->assertTrue($rooms->contains($customroom3->id));
        $this->assertTrue($rooms->contains($customroom4->id));
        foreach ($allrooms as $room) {
            if ($rooms->contains($room->get_id())) {
                $this->assertTrue($room->is_available($now + (DAYSECS * -1), $now + (DAYSECS * 1), $event11));
            } else {
                $this->assertFalse($room->is_available($now + (DAYSECS * -1), $now + (DAYSECS * 1), $event11));
            }
        }

        $rooms = \mod_facetoface\room_list::get_available_rooms($now + (DAYSECS * 1), $now + (DAYSECS * 2), $event13);
        $this->assertEquals(6, $rooms->count());
        $this->assertTrue($rooms->contains($sitewideroom2->id));
        $this->assertTrue($rooms->contains($sitewideroom4->id));
        $this->assertTrue($rooms->contains($sitewideroom5->id));
        $this->assertTrue($rooms->contains($customroom1->id));
        $this->assertTrue($rooms->contains($customroom3->id));
        $this->assertTrue($rooms->contains($customroom4->id));
        foreach ($allrooms as $room) {
            if ($rooms->contains($room->get_id())) {
                $this->assertTrue($room->is_available($now + (DAYSECS * 1), $now + (DAYSECS * 2), $event13));
            } else {
                $this->assertFalse($room->is_available($now + (DAYSECS * 1), $now + (DAYSECS * 2), $event13));
            }
        }

        $rooms = \mod_facetoface\room_list::get_available_rooms($now + (DAYSECS * -1), $now + (DAYSECS * 1), $event12);
        $this->assertEquals(7, $rooms->count());
        $this->assertTrue($rooms->contains($sitewideroom1->id));
        $this->assertTrue($rooms->contains($sitewideroom2->id));
        $this->assertTrue($rooms->contains($sitewideroom4->id));
        $this->assertTrue($rooms->contains($sitewideroom5->id));
        $this->assertTrue($rooms->contains($customroom1->id));
        $this->assertTrue($rooms->contains($customroom3->id));
        $this->assertTrue($rooms->contains($customroom4->id));
        foreach ($allrooms as $room) {
            if ($rooms->contains($room->get_id())) {
                $this->assertTrue($room->is_available($now + (DAYSECS * -1), $now + (DAYSECS * 1), $event12));
            } else {
                $this->assertFalse($room->is_available($now + (DAYSECS * -1), $now + (DAYSECS * 1), $event12));
            }
        }

        $rooms = \mod_facetoface\room_list::get_available_rooms($now + (DAYSECS * 1), $now + (DAYSECS * 2), $event11);
        $this->assertEquals(8, $rooms->count());
        $this->assertTrue($rooms->contains($sitewideroom1->id));
        $this->assertTrue($rooms->contains($sitewideroom2->id));
        $this->assertTrue($rooms->contains($sitewideroom3->id));
        $this->assertTrue($rooms->contains($sitewideroom4->id));
        $this->assertTrue($rooms->contains($sitewideroom5->id));
        $this->assertTrue($rooms->contains($customroom1->id));
        $this->assertTrue($rooms->contains($customroom3->id));
        $this->assertTrue($rooms->contains($customroom4->id));
        foreach ($allrooms as $room) {
            if ($rooms->contains($room->get_id())) {
                $this->assertTrue($room->is_available($now + (DAYSECS * 1), $now + (DAYSECS * 2), $event11));
            } else {
                $this->assertFalse($room->is_available($now + (DAYSECS * 1), $now + (DAYSECS * 2), $event11));
            }
        }

        $rooms = \mod_facetoface\room_list::get_available_rooms($now + (DAYSECS * 1), $now + (DAYSECS * 2), $event12);
        $this->assertEquals(6, $rooms->count());
        $this->assertTrue($rooms->contains($sitewideroom2->id));
        $this->assertTrue($rooms->contains($sitewideroom4->id));
        $this->assertTrue($rooms->contains($sitewideroom5->id));
        $this->assertTrue($rooms->contains($customroom1->id));
        $this->assertTrue($rooms->contains($customroom3->id));
        $this->assertTrue($rooms->contains($customroom4->id));
        foreach ($allrooms as $room) {
            if ($rooms->contains($room->get_id())) {
                $this->assertTrue($room->is_available($now + (DAYSECS * 1), $now + (DAYSECS * 2), $event12));
            } else {
                $this->assertFalse($room->is_available($now + (DAYSECS * 1), $now + (DAYSECS * 2), $event12));
            }
        }

        $rooms = \mod_facetoface\room_list::get_available_rooms($now + (DAYSECS * 0), $now + (DAYSECS * 3), $event11);
        $this->assertEquals(8, $rooms->count());
        $this->assertTrue($rooms->contains($sitewideroom1->id));
        $this->assertTrue($rooms->contains($sitewideroom2->id));
        $this->assertTrue($rooms->contains($sitewideroom3->id));
        $this->assertTrue($rooms->contains($sitewideroom4->id));
        $this->assertTrue($rooms->contains($sitewideroom5->id));
        $this->assertTrue($rooms->contains($customroom1->id));
        $this->assertTrue($rooms->contains($customroom3->id));
        $this->assertTrue($rooms->contains($customroom4->id));
        foreach ($allrooms as $room) {
            if ($rooms->contains($room->get_id())) {
                $this->assertTrue($room->is_available($now + (DAYSECS * 0), $now + (DAYSECS * 3), $event11));
            } else {
                $this->assertFalse($room->is_available($now + (DAYSECS * 0), $now + (DAYSECS * 3), $event11));
            }
        }

        $rooms = \mod_facetoface\room_list::get_available_rooms($now + (DAYSECS * 0), $now + (DAYSECS * 3), $event12);
        $this->assertEquals(5, $rooms->count());
        $this->assertTrue($rooms->contains($sitewideroom4->id));
        $this->assertTrue($rooms->contains($sitewideroom5->id));
        $this->assertTrue($rooms->contains($customroom1->id));
        $this->assertTrue($rooms->contains($customroom3->id));
        $this->assertTrue($rooms->contains($customroom4->id));
        foreach ($allrooms as $room) {
            if ($rooms->contains($room->get_id())) {
                $this->assertTrue($room->is_available($now + (DAYSECS * 0), $now + (DAYSECS * 3), $event12));
            } else {
                $this->assertFalse($room->is_available($now + (DAYSECS * 0), $now + (DAYSECS * 3), $event12));
            }
        }

        $rooms = \mod_facetoface\room_list::get_available_rooms($now + (DAYSECS * 1), $now + (DAYSECS * 20), $event11);
        $this->assertEquals(7, $rooms->count());
        $this->assertTrue($rooms->contains($sitewideroom1->id));
        $this->assertTrue($rooms->contains($sitewideroom2->id));
        $this->assertTrue($rooms->contains($sitewideroom3->id));
        $this->assertTrue($rooms->contains($sitewideroom4->id));
        $this->assertTrue($rooms->contains($sitewideroom5->id));
        $this->assertTrue($rooms->contains($customroom1->id));
        $this->assertTrue($rooms->contains($customroom4->id));
        foreach ($allrooms as $room) {
            if ($rooms->contains($room->get_id())) {
                $this->assertTrue($room->is_available($now + (DAYSECS * 1), $now + (DAYSECS * 20), $event11));
            } else {
                $this->assertFalse($room->is_available($now + (DAYSECS * 1), $now + (DAYSECS * 20), $event11));
            }
        }

        $rooms = \mod_facetoface\room_list::get_available_rooms($now + (DAYSECS * 1), $now + (DAYSECS * 20), $event12);
        $this->assertEquals(3, $rooms->count());
        $this->assertTrue($rooms->contains($sitewideroom4->id));
        $this->assertTrue($rooms->contains($sitewideroom5->id));
        $this->assertTrue($rooms->contains($customroom4->id));
        foreach ($allrooms as $room) {
            if ($rooms->contains($room->get_id())) {
                $this->assertTrue($room->is_available($now + (DAYSECS * 1), $now + (DAYSECS * 20), $event12));
            } else {
                $this->assertFalse($room->is_available($now + (DAYSECS * 1), $now + (DAYSECS * 20), $event12));
            }
        }

        $rooms = \mod_facetoface\room_list::get_available_rooms($now + (DAYSECS * 1), $now + (DAYSECS * 20), $event21);
        $this->assertEquals(2, $rooms->count());
        $this->assertTrue($rooms->contains($sitewideroom4->id));
        $this->assertTrue($rooms->contains($sitewideroom5->id));
        foreach ($allrooms as $room) {
            if ($rooms->contains($room->get_id())) {
                $this->assertTrue($room->is_available($now + (DAYSECS * 1), $now + (DAYSECS * 20), $event21));
            } else {
                $this->assertFalse($room->is_available($now + (DAYSECS * 1), $now + (DAYSECS * 20), $event21));
            }
        }

        // Now with user.
        $this->setUser($user1);

        $rooms = \mod_facetoface\room_list::get_available_rooms(0, 0, $event00);
        $this->assertEquals(6, $rooms->count());
        $this->assertTrue($rooms->contains($sitewideroom1->id));
        $this->assertTrue($rooms->contains($sitewideroom2->id));
        $this->assertTrue($rooms->contains($sitewideroom4->id));
        $this->assertTrue($rooms->contains($sitewideroom5->id));
        $this->assertTrue($rooms->contains($customroom2->id));
        $this->assertTrue($rooms->contains($customroom5->id));
        foreach ($allrooms as $room) {
            if ($rooms->contains($room->get_id())) {
                $this->assertTrue($room->is_available($now + (DAYSECS * -1), $now + (DAYSECS * 1), $event00));
            } else {
                $this->assertFalse($room->is_available($now + (DAYSECS * -1), $now + (DAYSECS * 1), $event00));
            }
        }

        $rooms = \mod_facetoface\room_list::get_available_rooms($now + (DAYSECS * -1), $now + (DAYSECS * 1), $event00);
        $this->assertEquals(6, $rooms->count());
        $this->assertTrue($rooms->contains($sitewideroom1->id));
        $this->assertTrue($rooms->contains($sitewideroom2->id));
        $this->assertTrue($rooms->contains($sitewideroom4->id));
        $this->assertTrue($rooms->contains($sitewideroom5->id));
        $this->assertTrue($rooms->contains($customroom2->id));
        $this->assertTrue($rooms->contains($customroom5->id));
        foreach ($allrooms as $room) {
            if ($rooms->contains($room->get_id())) {
                $this->assertTrue($room->is_available($now + (DAYSECS * -1), $now + (DAYSECS * 1), $event00));
            } else {
                $this->assertFalse($room->is_available($now + (DAYSECS * -1), $now + (DAYSECS * 1), $event00));
            }
        }

        $rooms = \mod_facetoface\room_list::get_available_rooms($now + (DAYSECS * 0), $now + (DAYSECS * 3), $event00);
        $this->assertEquals(4, $rooms->count());
        $this->assertTrue($rooms->contains($sitewideroom4->id));
        $this->assertTrue($rooms->contains($sitewideroom5->id));
        $this->assertTrue($rooms->contains($customroom2->id));
        $this->assertTrue($rooms->contains($customroom5->id));
        foreach ($allrooms as $room) {
            if ($rooms->contains($room->get_id())) {
                $this->assertTrue($room->is_available($now + (DAYSECS * 0), $now + (DAYSECS * 3), $event00));
            } else {
                $this->assertFalse($room->is_available($now + (DAYSECS * 0), $now + (DAYSECS * 3), $event00));
            }
        }

        $rooms = \mod_facetoface\room_list::get_available_rooms(0, 0, $event11);
        $this->assertEquals(10, $rooms->count());
        $this->assertTrue($rooms->contains($sitewideroom1->id));
        $this->assertTrue($rooms->contains($sitewideroom2->id));
        $this->assertTrue($rooms->contains($sitewideroom3->id));
        $this->assertTrue($rooms->contains($sitewideroom4->id));
        $this->assertTrue($rooms->contains($sitewideroom5->id));
        $this->assertTrue($rooms->contains($customroom1->id));
        $this->assertTrue($rooms->contains($customroom2->id));
        $this->assertTrue($rooms->contains($customroom3->id));
        $this->assertTrue($rooms->contains($customroom4->id));
        $this->assertTrue($rooms->contains($customroom5->id));
        foreach ($allrooms as $room) {
            if ($rooms->contains($room->get_id())) {
                $this->assertTrue($room->is_available(0, 0, $event11));
            } else {
                $this->assertFalse($room->is_available(0, 0, $event11));
            }
        }

        $rooms = \mod_facetoface\room_list::get_available_rooms(0, 0, $event12);
        $this->assertEquals(9, $rooms->count());
        $this->assertTrue($rooms->contains($sitewideroom1->id));
        $this->assertTrue($rooms->contains($sitewideroom2->id));
        $this->assertTrue($rooms->contains($sitewideroom4->id));
        $this->assertTrue($rooms->contains($sitewideroom5->id));
        $this->assertTrue($rooms->contains($customroom1->id));
        $this->assertTrue($rooms->contains($customroom2->id));
        $this->assertTrue($rooms->contains($customroom3->id));
        $this->assertTrue($rooms->contains($customroom4->id));
        $this->assertTrue($rooms->contains($customroom5->id));
        foreach ($allrooms as $room) {
            if ($rooms->contains($room->get_id())) {
                $this->assertTrue($room->is_available(0, 0, $event12));
            } else {
                $this->assertFalse($room->is_available(0, 0, $event12));
            }
        }

        $rooms = \mod_facetoface\room_list::get_available_rooms($now + (DAYSECS * 1), $now + (DAYSECS * 20), $event11);
        $this->assertEquals(9, $rooms->count());
        $this->assertTrue($rooms->contains($sitewideroom1->id));
        $this->assertTrue($rooms->contains($sitewideroom2->id));
        $this->assertTrue($rooms->contains($sitewideroom3->id));
        $this->assertTrue($rooms->contains($sitewideroom4->id));
        $this->assertTrue($rooms->contains($sitewideroom5->id));
        $this->assertTrue($rooms->contains($customroom1->id));
        $this->assertTrue($rooms->contains($customroom2->id));
        $this->assertTrue($rooms->contains($customroom4->id));
        $this->assertTrue($rooms->contains($customroom5->id));
        foreach ($allrooms as $room) {
            if ($rooms->contains($room->get_id())) {
                $this->assertTrue($room->is_available($now + (DAYSECS * 1), $now + (DAYSECS * 20), $event11));
            } else {
                $this->assertFalse($room->is_available($now + (DAYSECS * 1), $now + (DAYSECS * 20), $event11));
            }
        }

        $rooms = \mod_facetoface\room_list::get_available_rooms($now + (DAYSECS * 1), $now + (DAYSECS * 20), $event12);
        $this->assertEquals(5, $rooms->count());
        $this->assertTrue($rooms->contains($sitewideroom4->id));
        $this->assertTrue($rooms->contains($sitewideroom5->id));
        $this->assertTrue($rooms->contains($customroom2->id));
        $this->assertTrue($rooms->contains($customroom4->id));
        $this->assertTrue($rooms->contains($customroom5->id));
        foreach ($allrooms as $room) {
            if ($rooms->contains($room->get_id())) {
                $this->assertTrue($room->is_available($now + (DAYSECS * 1), $now + (DAYSECS * 20), $event12));
            } else {
                $this->assertFalse($room->is_available($now + (DAYSECS * 1), $now + (DAYSECS * 20), $event12));
            }
        }

        $rooms = \mod_facetoface\room_list::get_available_rooms($now + (DAYSECS * 1), $now + (DAYSECS * 20), $event21);
        $this->assertEquals(4, $rooms->count());
        $this->assertTrue($rooms->contains($sitewideroom4->id));
        $this->assertTrue($rooms->contains($sitewideroom5->id));
        $this->assertTrue($rooms->contains($customroom2->id));
        $this->assertTrue($rooms->contains($customroom5->id));
        foreach ($allrooms as $room) {
            if ($rooms->contains($room->get_id())) {
                $this->assertTrue($room->is_available($now + (DAYSECS * 1), $now + (DAYSECS * 20), $event21));
            } else {
                $this->assertFalse($room->is_available($now + (DAYSECS * 1), $now + (DAYSECS * 20), $event21));
            }
        }

        // The fields can no longer be specified, make sure it contains all the important ones.
        $rooms = \mod_facetoface\room_list::get_available_rooms($now + (DAYSECS * 1), $now + (DAYSECS * 20), $event11);
        $this->assertEquals(9, $rooms->count());
        foreach ($rooms as $room) {
            $this->assertInstanceOf('\mod_facetoface\room', $room);
            $this->assertObjectHasAttribute('id', $room);
            $this->assertObjectHasAttribute('name', $room);
            $this->assertObjectHasAttribute('hidden', $room);
            $this->assertObjectHasAttribute('custom', $room);
            $this->assertObjectHasAttribute('capacity', $room);
            $this->assertObjectHasAttribute('allowconflicts', $room);
        }


        // Test slot must have size.
        $rooms = \mod_facetoface\room_list::get_available_rooms(2, 1, $event00);
        $this->assertDebuggingCalled();
        $this->assertEquals(6, $rooms->count());
        $this->assertTrue($rooms->contains($sitewideroom1->id));
        $this->assertTrue($rooms->contains($sitewideroom2->id));
        $this->assertTrue($rooms->contains($sitewideroom4->id));
        $this->assertTrue($rooms->contains($sitewideroom5->id));
        $this->assertTrue($rooms->contains($customroom2->id));
        $this->assertTrue($rooms->contains($customroom5->id));
    }

    public function test_facetoface_room_has_conflicts() {
        $now = time();

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $sitewideroom1 = $this->facetoface_generator->add_site_wide_room(array('name' => 'Site room 1', 'allowconflicts' => 0, 'hidden' => 0));
        $sitewideroom2 = $this->facetoface_generator->add_site_wide_room(array('name' => 'Site room 2', 'allowconflicts' => 0, 'hidden' => 0));
        $sitewideroom3 = $this->facetoface_generator->add_site_wide_room(array('name' => 'Site room 3', 'allowconflicts' => 0, 'hidden' => 1));
        $sitewideroom4 = $this->facetoface_generator->add_site_wide_room(array('name' => 'Site room 4', 'allowconflicts' => 1, 'hidden' => 0));
        $sitewideroom5 = $this->facetoface_generator->add_site_wide_room(array('name' => 'Site room 5', 'allowconflicts' => 1, 'hidden' => 0));
        $sitewideroom6 = $this->facetoface_generator->add_site_wide_room(array('name' => 'Site room 6', 'allowconflicts' => 1, 'hidden' => 1));
        $customroom1 = $this->facetoface_generator->add_custom_room(array('usercreated' => $user1->id, 'name' => 'Custom room 1', 'allowconflicts' => 0));
        $customroom2 = $this->facetoface_generator->add_custom_room(array('usercreated' => $user1->id, 'name' => 'Custom room 2', 'allowconflicts' => 0));
        $customroom3 = $this->facetoface_generator->add_custom_room(array('usercreated' => $user2->id, 'name' => 'Custom room 3', 'allowconflicts' => 0));
        $customroom4 = $this->facetoface_generator->add_custom_room(array('usercreated' => $user1->id, 'name' => 'Custom room 4', 'allowconflicts' => 1));
        $customroom5 = $this->facetoface_generator->add_custom_room(array('usercreated' => $user1->id, 'name' => 'Custom room 5', 'allowconflicts' => 1));
        $customroom6 = $this->facetoface_generator->add_custom_room(array('usercreated' => $user2->id, 'name' => 'Custom room 6', 'allowconflicts' => 1));

        $course = $this->getDataGenerator()->create_course();
        $facetoface1 = $this->facetoface_generator->create_instance(array('course' => $course->id));
        $facetoface2 = $this->facetoface_generator->create_instance(array('course' => $course->id));

        $sessiondates = array();
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 1), $now + (DAYSECS * 2), $sitewideroom1->id);
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 2), $now + (DAYSECS * 3), $sitewideroom2->id);
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * -2), $now + (DAYSECS * -1), $sitewideroom3->id);
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 9), $now + (DAYSECS * 10), $sitewideroom4->id);
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 3), $now + (DAYSECS * 4), $customroom1->id);
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 5), $now + (DAYSECS * 6), $customroom3->id);
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 7), $now + (DAYSECS * 8), $customroom4->id);
        $sessionid1_1 = $this->facetoface_generator->add_session(array('facetoface' => $facetoface1->id, 'sessiondates' => $sessiondates));

        $sessiondates = array();
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 1), $now + (DAYSECS * 3), $sitewideroom1->id);
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 2.5), $now + (DAYSECS * 4.5), $sitewideroom2->id);
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * -3), $now + (DAYSECS * -1.5), $sitewideroom3->id);
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 4), $now + (DAYSECS * 7), $customroom4->id);
        $sessionid1_2 = $this->facetoface_generator->add_session(array('facetoface' => $facetoface1->id, 'sessiondates' => $sessiondates));

        $sessiondates = array();
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 9), $now + (DAYSECS * 10), $sitewideroom4->id);
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 5.5), $now + (DAYSECS * 5.6), $customroom3->id);
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 8), $now + (DAYSECS * 9), $customroom4->id);
        $sessionid2_1 = $this->facetoface_generator->add_session(array('facetoface' => $facetoface2->id, 'sessiondates' => $sessiondates));

        $room = new \mod_facetoface\room();

        $room->from_record($sitewideroom1);
        $this->assertTrue($room->has_conflicts());
        $room->from_record($sitewideroom2);
        $this->assertTrue($room->has_conflicts());
        $room->from_record($sitewideroom3);
        $this->assertTrue($room->has_conflicts());
        $room->from_record($sitewideroom4);
        $this->assertTrue($room->has_conflicts());
        $room->from_record($sitewideroom5);
        $this->assertFalse($room->has_conflicts());
        $room->from_record($sitewideroom6);
        $this->assertFalse($room->has_conflicts());

        $room->from_record($customroom1);
        $this->assertFalse($room->has_conflicts());
        $room->from_record($customroom2);
        $this->assertFalse($room->has_conflicts());
        $room->from_record($customroom3);
        $this->assertTrue($room->has_conflicts());
        $room->from_record($customroom4);
        $this->assertFalse($room->has_conflicts());
        $room->from_record($customroom5);
        $this->assertFalse($room->has_conflicts());
        $room->from_record($customroom6);
        $this->assertFalse($room->has_conflicts());
    }

    public function test_session_cancellation() {
        global $DB;

        $now = time();

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $sitewideroom1 = $this->facetoface_generator->add_site_wide_room(array('name' => 'Site room 1', 'allowconflicts' => 0, 'hidden' => 0));
        $sitewideroom2 = $this->facetoface_generator->add_site_wide_room(array('name' => 'Site room 2', 'allowconflicts' => 0, 'hidden' => 0));
        $sitewideroom3 = $this->facetoface_generator->add_site_wide_room(array('name' => 'Site room 3', 'allowconflicts' => 0, 'hidden' => 1));
        $sitewideroom4 = $this->facetoface_generator->add_site_wide_room(array('name' => 'Site room 4', 'allowconflicts' => 1, 'hidden' => 0));
        $sitewideroom5 = $this->facetoface_generator->add_site_wide_room(array('name' => 'Site room 5', 'allowconflicts' => 1, 'hidden' => 0));
        $sitewideroom6 = $this->facetoface_generator->add_site_wide_room(array('name' => 'Site room 6', 'allowconflicts' => 1, 'hidden' => 1));
        $customroom1 = $this->facetoface_generator->add_custom_room(array('usercreated' => $user1->id, 'name' => 'Custom room 1', 'allowconflicts' => 0));
        $customroom2 = $this->facetoface_generator->add_custom_room(array('usercreated' => $user1->id, 'name' => 'Custom room 2', 'allowconflicts' => 0));
        $customroom3 = $this->facetoface_generator->add_custom_room(array('usercreated' => $user2->id, 'name' => 'Custom room 3', 'allowconflicts' => 0));
        $customroom4 = $this->facetoface_generator->add_custom_room(array('usercreated' => $user1->id, 'name' => 'Custom room 4', 'allowconflicts' => 1));
        $customroom5 = $this->facetoface_generator->add_custom_room(array('usercreated' => $user1->id, 'name' => 'Custom room 5', 'allowconflicts' => 1));
        $customroom6 = $this->facetoface_generator->add_custom_room(array('usercreated' => $user2->id, 'name' => 'Custom room 6', 'allowconflicts' => 1));

        $course = $this->getDataGenerator()->create_course();
        $facetoface1 = $this->facetoface_generator->create_instance(array('course' => $course->id));
        $facetoface2 = $this->facetoface_generator->create_instance(array('course' => $course->id));

        $sessiondates = array();
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 1), $now + (DAYSECS * 2), $sitewideroom1->id);
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 2), $now + (DAYSECS * 3), $sitewideroom2->id);
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * -2), $now + (DAYSECS * -1), $sitewideroom3->id);
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 9), $now + (DAYSECS * 10), $sitewideroom4->id);
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 3), $now + (DAYSECS * 4), $customroom1->id);
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 5), $now + (DAYSECS * 6), $customroom3->id);
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 7), $now + (DAYSECS * 8), $customroom4->id);
        $sessionid1_1 = $this->facetoface_generator->add_session(array('facetoface' => $facetoface1->id, 'sessiondates' => $sessiondates));

        $sessiondates = array();
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 1), $now + (DAYSECS * 3), $sitewideroom1->id);
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 2.5), $now + (DAYSECS * 4.5), $sitewideroom2->id);
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * -3), $now + (DAYSECS * -1.5), $sitewideroom3->id);
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 4), $now + (DAYSECS * 7), $customroom4->id);
        $sessionid1_2 = $this->facetoface_generator->add_session(array('facetoface' => $facetoface1->id, 'sessiondates' => $sessiondates));

        $sessiondates = array();
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 9), $now + (DAYSECS * 10), $sitewideroom4->id);
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 5.5), $now + (DAYSECS * 5.6), $customroom3->id);
        $sessiondates[] = $this->prepare_date($now + (DAYSECS * 8), $now + (DAYSECS * 9), $customroom4->id);
        $sessionid2_1 = $this->facetoface_generator->add_session(array('facetoface' => $facetoface2->id, 'sessiondates' => $sessiondates));

        $this->assertTrue($DB->record_exists_select('facetoface_sessions_dates', "sessionid = :sessionid and roomid > 0", array('sessionid' => $sessionid2_1)));
        $seminarevent = new \mod_facetoface\seminar_event($sessionid2_1);
        $seminarevent->cancel();
        $this->assertFalse($DB->record_exists_select('facetoface_sessions_dates', "sessionid = :sessionid and roomid > 0", array('sessionid' => $sessionid2_1)));
    }

    protected function prepare_date($timestart, $timeend, $roomid) {
        $sessiondate = new stdClass();
        $sessiondate->timestart = (string)$timestart;
        $sessiondate->timefinish = (string)$timeend;
        $sessiondate->sessiontimezone = '99';
        $sessiondate->roomid = (string)$roomid;
        return $sessiondate;
    }
}
