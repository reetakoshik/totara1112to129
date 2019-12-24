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
 * @package totara_reportbuilder
 */

/**
 * @group totara_reportbuilder
 */
class totara_reportbuilder_rb_global_restriction_testcase extends advanced_testcase {
    public static function setUpBeforeClass() {
        global $CFG;
        parent::setUpBeforeClass();

        require_once($CFG->dirroot . '/totara/reportbuilder/lib.php');
        require_once($CFG->dirroot . '/totara/reportbuilder/classes/rb_global_restriction.php');
    }

    public function test_insert() {
        global $DB;
        $this->resetAfterTest();

        $data = new stdClass();
        $data->name = 'Some restriction name';
        $data->description = 'Some restriction description';
        $data->active = '1';
        $data->allrecords = '0';
        $data->allusers = '0';

        // Basic insert.
        $restriction = new rb_global_restriction();
        $id = $restriction->insert($data);

        $this->assertSame($id, $restriction->id);

        $record = $DB->get_record('report_builder_global_restriction', array('id' => $id));

        // Test get_record_data() too.
        $this->assertEquals($record, $restriction->get_record_data());

        // Test the defaults.
        $data = new stdClass();
        $data->name = 'Some restriction name 2';
        $restriction = new rb_global_restriction();

        $this->setCurrentTimeStart();
        $restriction->insert($data);
        $this->assertSame($data->name, $restriction->name);
        $this->assertSame(null, $restriction->description);
        $this->assertSame('0', $restriction->active);
        $this->assertSame('0', $restriction->allrecords);
        $this->assertSame('0', $restriction->allusers);
        $this->assertSame('1', $restriction->sortorder);
        $this->assertTimeCurrent($restriction->timecreated);
        $this->assertSame($restriction->timemodified, $restriction->timecreated);

        // Test constructor data loading.
        $record = $DB->get_record('report_builder_global_restriction', array('id' => $id));
        $restriction = new rb_global_restriction($record->id);
        $this->assertEquals($record, $restriction->get_record_data());

        // Test invalid insert if id already present.
        try {
            $restriction->insert($data);
            $this->fail('Exception expected on double insert');
        } catch (moodle_exception $e) {
            $this->assertInstanceOf('coding_exception', $e);
            $this->assertEquals('Coding error detected, it must be fixed by a programmer: Cannot insert over existing restriction', $e->getMessage());
        }
    }

    public function test_update() {
        global $DB;
        $this->resetAfterTest();

        $data = new stdClass();
        $data->name = 'Some restriction name';
        $data->description = 'Some restriction description';
        $data->active = '1';
        $data->allrecords = '0';
        $data->allusers = '0';

        $restriction = new rb_global_restriction();
        $restriction->insert($data);

        $oldrecord = $DB->get_record('report_builder_global_restriction', array('id' => $restriction->id), '*', MUST_EXIST);

        $data = new stdClass();
        $data->name = 'Some new restriction name';
        $data->description = 'Some new restriction description';
        $data->active = '0';
        $data->allrecords = '1';
        $data->allusers = '1';

        $this->setCurrentTimeStart();
        $restriction->update($data);
        $record = $DB->get_record('report_builder_global_restriction', array('id' => $restriction->id), '*', MUST_EXIST);
        $newrecord = $restriction->get_record_data();
        $newrecord->timemodified = $record->timemodified; // This might have changed by one second.
        $this->assertEquals($record, $newrecord);

        $this->assertSame($data->name, $restriction->name);
        $this->assertSame($data->description, $restriction->description);
        $this->assertSame($data->active, $restriction->active);
        $this->assertSame($data->allrecords, $restriction->allrecords);
        $this->assertSame($data->allusers, $restriction->allusers);

        $this->assertSame($oldrecord->sortorder, $restriction->sortorder);
        $this->assertSame($oldrecord->timecreated, $restriction->timecreated);
        $this->assertTimeCurrent($restriction->timemodified);

        $restriction = new rb_global_restriction();
        try {
            $restriction->update($data);
            $this->fail('Exception expected on invalid update');
        } catch (moodle_exception $e) {
            $this->assertInstanceOf('coding_exception', $e);
            $this->assertEquals('Coding error detected, it must be fixed by a programmer: Cannot update non-existent restriction', $e->getMessage());
        }
    }

    public function test_delete() {
        global $DB;
        $this->resetAfterTest();

        $data = new stdClass();
        $data->name = 'Some restriction name 2';
        $restriction1 = new rb_global_restriction();
        $restriction1->insert($data);
        $record1 = $DB->get_record('report_builder_global_restriction', array('id' => $restriction1->id), '*', MUST_EXIST);

        $data = new stdClass();
        $data->name = 'Some restriction name 2';
        $restriction2 = new rb_global_restriction();
        $restriction2->insert($data);
        $record2 = $DB->get_record('report_builder_global_restriction', array('id' => $restriction2->id), '*', MUST_EXIST);

        $restriction2->delete();
        $this->assertEquals($record2, $restriction2->get_record_data());
        $this->assertFalse($DB->record_exists('report_builder_global_restriction', array('id' => $restriction2->id)));
        $this->assertTrue($DB->record_exists('report_builder_global_restriction', array('id' => $restriction1->id)));
    }

    public function test_activate_deactivate() {
        $this->resetAfterTest();

        $data = new stdClass();
        $data->name = 'Some restriction name';
        $data->active = '0';

        $restriction = new rb_global_restriction();
        $restriction->insert($data);
        $this->assertSame('0', $restriction->active);

        $restriction->activate();
        $this->assertSame('1', $restriction->active);
        $this->assertSame('1', $restriction->get_record_data()->active);

        $restriction->deactivate();
        $this->assertSame('0', $restriction->active);
        $this->assertSame('0', $restriction->get_record_data()->active);
    }

    public function test_down_up() {
        $this->resetAfterTest();

        $data = new stdClass();
        $data->name = 'Some restriction name 1';
        $restriction1 = new rb_global_restriction();
        $restriction1->insert($data);
        $this->assertEquals(0, $restriction1->sortorder);

        $data = new stdClass();
        $data->name = 'Some restriction name 2';
        $restriction2 = new rb_global_restriction();
        $restriction2->insert($data);
        $this->assertEquals(1, $restriction2->sortorder);

        $data = new stdClass();
        $data->name = 'Some restriction name 3';
        $restriction3 = new rb_global_restriction();
        $restriction3->insert($data);
        $this->assertEquals(2, $restriction3->sortorder);

        $data = new stdClass();
        $data->name = 'Some restriction name 4';
        $restriction4 = new rb_global_restriction();
        $restriction4->insert($data);
        $this->assertEquals(3, $restriction4->sortorder);

        // Normal down.

        $restriction2->down();
        $this->assertEquals(2, $restriction2->sortorder);

        $restriction1 = new rb_global_restriction($restriction1->id);
        $this->assertEquals(0, $restriction1->sortorder);

        $restriction3 = new rb_global_restriction($restriction3->id);
        $this->assertEquals(1, $restriction3->sortorder);

        $restriction4 = new rb_global_restriction($restriction4->id);
        $this->assertEquals(3, $restriction4->sortorder);

        // Normal up.

        $restriction2->up();
        $this->assertEquals(1, $restriction2->sortorder);

        $restriction1 = new rb_global_restriction($restriction1->id);
        $this->assertEquals(0, $restriction1->sortorder);

        $restriction3 = new rb_global_restriction($restriction3->id);
        $this->assertEquals(2, $restriction3->sortorder);

        $restriction4 = new rb_global_restriction($restriction4->id);
        $this->assertEquals(3, $restriction4->sortorder);

        // End down.

        $restriction4->down();
        $this->assertEquals(3, $restriction4->sortorder);

        $restriction1 = new rb_global_restriction($restriction1->id);
        $this->assertEquals(0, $restriction1->sortorder);

        $restriction2 = new rb_global_restriction($restriction2->id);
        $this->assertEquals(1, $restriction2->sortorder);

        $restriction3 = new rb_global_restriction($restriction3->id);
        $this->assertEquals(2, $restriction3->sortorder);

        // Top up.

        $restriction1->up();
        $this->assertEquals(0, $restriction1->sortorder);

        $restriction2 = new rb_global_restriction($restriction2->id);
        $this->assertEquals(1, $restriction2->sortorder);

        $restriction3 = new rb_global_restriction($restriction3->id);
        $this->assertEquals(2, $restriction3->sortorder);
    }

    public function test_get_all() {
        $this->resetAfterTest();

        // Prepare 5 restrictions.
        $restriction1 = new rb_global_restriction();
        $restriction1->insert((object)array('name' => 'Restriction 1', 'active' => 0, 'sortorder' => 1));
        $restriction2 = new rb_global_restriction();
        $restriction2->insert((object)array('name' => 'Restriction 2', 'active' => 1, 'sortorder' => 2));
        $restriction3 = new rb_global_restriction();
        $restriction3->insert((object)array('name' => 'Restriction 3', 'active' => 0, 'sortorder' => 3));
        $restriction4 = new rb_global_restriction();
        $restriction4->insert((object)array('name' => 'Restriction 4', 'active' => 1, 'sortorder' => 4));
        $restriction5 = new rb_global_restriction();
        $restriction5->insert((object)array('name' => 'Restriction 5', 'active' => 0, 'sortorder' => 5));

        // Check all restrictions.
        $count = 0;
        $all = rb_global_restriction::get_all(0, 40, $count);
        $this->assertCount(5, $all);

        // Check pagination support.
        $page = rb_global_restriction::get_all(1, 2, $count);
        $this->assertCount(2, $page);
        $this->assertEquals($restriction3->id, array_values($page)[0]->id);
        $this->assertEquals($restriction4->id, array_values($page)[1]->id);
    }

    public function test_get_record_data() {
        $this->resetAfterTest();

        // Create 3 users to assign: 1 to restricted users and 2 to restricted records.
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();

        $restriction = new rb_global_restriction();
        $id = $restriction->insert((object)array(
            'name' => 'Test Restriction',
            'active' => 1,
            'description' => 'Desc',
            'sortorder' => 1,
            'allrecords' => 1,
            'allusers' => 1
        ));

        $reportgenerator = $this->getDataGenerator()->get_plugin_generator('totara_reportbuilder');
        $reportgenerator->assign_global_restriction_record(array(
            'restrictionid' => $id,
            'prefix' => 'user',
            'itemid' => $user1->id
        ));
        $reportgenerator->assign_global_restriction_record(array(
            'restrictionid' => $id,
            'prefix' => 'user',
            'itemid' => $user2->id
        ));
        $reportgenerator->assign_global_restriction_user(array(
            'restrictionid' => $id,
            'prefix' => 'user',
            'itemid' => $user3->id
        ));

        $restrictiontest = new rb_global_restriction($id);
        $obj = $restrictiontest->get_record_data();
        $this->assertGreaterThan(0, $obj->id);
        $this->assertGreaterThan(0, $obj->timecreated);
        $this->assertGreaterThan(0, $obj->timemodified);
        $this->assertEquals(0, $obj->sortorder);
        $this->assertEquals(1, $obj->active);
        $this->assertEquals('Test Restriction', $obj->name);
        $this->assertEquals('Desc', $obj->description);
        $this->assertEquals(1, $obj->allrecords);
        $this->assertEquals(1, $obj->allusers);
    }

    public function test_get_unsupported_sources() {
        $sources = rb_global_restriction::get_unsupported_sources();
        $this->assertEmpty($sources);
    }
}
