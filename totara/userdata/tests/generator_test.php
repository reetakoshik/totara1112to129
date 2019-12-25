<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
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
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 * @package totara_userdata
 * @category test
 */

use totara_userdata\userdata\target_user;

defined('MOODLE_INTERNAL') || die();

/**
 * Tests the generator class.
 */
class totara_userdata_generator_testcase extends advanced_testcase {
    public function test_create_purge_type() {
        global $USER, $DB;
        $this->resetAfterTest();

        /** @var totara_userdata_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_userdata');

        $this->setCurrentTimeStart();
        $type = $generator->create_purge_type();
        $this->assertInstanceOf('stdClass', $type);
        $this->assertSame((string)target_user::STATUS_DELETED, $type->userstatus);
        $this->assertSame('Purge type 1', $type->fullname);
        $this->assertSame('idnumber1', $type->idnumber);
        $this->assertSame('', $type->description);
        $this->assertSame('0', $type->allowmanual);
        $this->assertSame('0', $type->allowdeleted);
        $this->assertSame('0', $type->allowsuspended);
        $this->assertSame((string)$USER->id, $type->usercreated);
        $this->assertTimeCurrent($type->timecreated);
        $this->assertSame($type->timecreated, $type->timechanged);
        $purgeclasses = \totara_userdata\local\purge::get_purgeable_item_classes((int)$type->userstatus);
        $this->assertSame(count($purgeclasses), $DB->count_records('totara_userdata_purge_type_item', array('purgetypeid' => $type->id)));
        $this->assertSame(count($purgeclasses), $DB->count_records('totara_userdata_purge_type_item', array('purgetypeid' => $type->id, 'purgedata' => 0)));

        $record = array(
            'userstatus' => (string)target_user::STATUS_ACTIVE,
            'fullname' => 'Some type',
            'idnumber' => 'grrr',
            'description' => 'lala',
            'allowmanual' => '1',
            'allowdeleted' => '0',
            'allowsuspended' => '0',
            'usercreated' => get_admin()->id,
            'timecreated' => '10001',
            'timechanged' => '20002',
            'items' => 'core_user-additionalnames, core_user-preferences'
        );
        $type = $generator->create_purge_type($record);
        $record['id'] = $type->id;
        unset($record['items']);
        $this->assertEquals((object)$record, $type);
        $purgeclasses = \totara_userdata\local\purge::get_purgeable_item_classes((int)$type->userstatus);
        $this->assertSame(count($purgeclasses), $DB->count_records('totara_userdata_purge_type_item', array('purgetypeid' => $type->id)));
        $this->assertSame(count($purgeclasses) - 2, $DB->count_records('totara_userdata_purge_type_item', array('purgetypeid' => $type->id, 'purgedata' => 0)));
        $this->assertTrue($DB->record_exists('totara_userdata_purge_type_item', array('purgetypeid' => $type->id, 'purgedata' => 1, 'component' => 'core_user', 'name' => 'additionalnames')));
        $this->assertTrue($DB->record_exists('totara_userdata_purge_type_item', array('purgetypeid' => $type->id, 'purgedata' => 1, 'component' => 'core_user', 'name' => 'preferences')));

        $record = array(
            'userstatus' => (string)target_user::STATUS_SUSPENDED,
            'fullname' => 'Some xx type',
            'idnumber' => 'grrr x ',
            'description' => 'lala',
            'allowmanual' => '1',
            'allowdeleted' => '0',
            'allowsuspended' => '1',
            'usercreated' => get_admin()->id,
            'timecreated' => '233232',
            'timechanged' => '233232',
            'items' => array('core_user\\userdata\\additionalnames', 'core_user\\userdata\\preferences'),
        );
        $type = $generator->create_purge_type($record);
        $record['id'] = $type->id;
        unset($record['items']);
        $this->assertEquals((object)$record, $type);
        $purgeclasses = \totara_userdata\local\purge::get_purgeable_item_classes((int)$type->userstatus);
        $this->assertSame(count($purgeclasses), $DB->count_records('totara_userdata_purge_type_item', array('purgetypeid' => $type->id)));
        $this->assertSame(count($purgeclasses) - 2, $DB->count_records('totara_userdata_purge_type_item', array('purgetypeid' => $type->id, 'purgedata' => 0)));
        $this->assertTrue($DB->record_exists('totara_userdata_purge_type_item', array('purgetypeid' => $type->id, 'purgedata' => 1, 'component' => 'core_user', 'name' => 'additionalnames')));
        $this->assertTrue($DB->record_exists('totara_userdata_purge_type_item', array('purgetypeid' => $type->id, 'purgedata' => 1, 'component' => 'core_user', 'name' => 'preferences')));

        $record = array(
            'userstatus' => (string)target_user::STATUS_DELETED,
            'fullname' => 'Some yy type',
            'idnumber' => 'xxx',
            'description' => 'lala',
            'allowmanual' => '1',
            'allowdeleted' => '1',
            'allowsuspended' => '0',
            'usercreated' => get_admin()->id,
            'timecreated' => '1111',
            'timechanged' => '2332222',
            'items' => 'core_user\\userdata\\username',
        );
        $type = $generator->create_purge_type($record);
        $record['id'] = $type->id;
        unset($record['items']);
        $this->assertEquals((object)$record, $type);
        $purgeclasses = \totara_userdata\local\purge::get_purgeable_item_classes((int)$type->userstatus);
        $this->assertSame(count($purgeclasses), $DB->count_records('totara_userdata_purge_type_item', array('purgetypeid' => $type->id)));
        $this->assertSame(count($purgeclasses) - 1, $DB->count_records('totara_userdata_purge_type_item', array('purgetypeid' => $type->id, 'purgedata' => 0)));
        $this->assertTrue($DB->record_exists('totara_userdata_purge_type_item', array('purgetypeid' => $type->id, 'purgedata' => 1, 'component' => 'core_user', 'name' => 'username')));
    }

    public function test_create_export_type() {
        global $USER, $DB;
        $this->resetAfterTest();

        /** @var totara_userdata_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_userdata');

        $this->setCurrentTimeStart();
        $type = $generator->create_export_type();
        $this->assertInstanceOf('stdClass', $type);
        $this->assertSame('Export type 1', $type->fullname);
        $this->assertSame('idnumber1', $type->idnumber);
        $this->assertSame('', $type->description);
        $this->assertSame('0', $type->allowself);
        $this->assertSame('1', $type->includefiledir);
        $this->assertSame((string)$USER->id, $type->usercreated);
        $this->assertTimeCurrent($type->timecreated);
        $this->assertSame($type->timecreated, $type->timechanged);
        $exportclasses = \totara_userdata\local\export::get_exportable_item_classes();
        $this->assertSame(count($exportclasses), $DB->count_records('totara_userdata_export_type_item', array('exporttypeid' => $type->id)));
        $this->assertSame(count($exportclasses), $DB->count_records('totara_userdata_export_type_item', array('exporttypeid' => $type->id, 'exportdata' => 0)));

        $record = array(
            'fullname' => 'Some type',
            'idnumber' => 'grrr',
            'description' => 'lala',
            'allowself' => '1',
            'includefiledir' => '0',
            'usercreated' => get_admin()->id,
            'timecreated' => '10001',
            'timechanged' => '20002',
            'items' => 'core_user-additionalnames, core_user-preferences,core_user\\userdata\\username'
        );
        $type = $generator->create_export_type($record);
        $record['id'] = $type->id;
        unset($record['items']);
        $this->assertEquals((object)$record, $type);
        $exportclasses = \totara_userdata\local\export::get_exportable_item_classes();
        $this->assertSame(count($exportclasses), $DB->count_records('totara_userdata_export_type_item', array('exporttypeid' => $type->id)));
        $this->assertSame(count($exportclasses) - 3, $DB->count_records('totara_userdata_export_type_item', array('exporttypeid' => $type->id, 'exportdata' => 0)));
        $this->assertTrue($DB->record_exists('totara_userdata_export_type_item', array('exporttypeid' => $type->id, 'exportdata' => 1, 'component' => 'core_user', 'name' => 'additionalnames')));
        $this->assertTrue($DB->record_exists('totara_userdata_export_type_item', array('exporttypeid' => $type->id, 'exportdata' => 1, 'component' => 'core_user', 'name' => 'preferences')));
        $this->assertTrue($DB->record_exists('totara_userdata_export_type_item', array('exporttypeid' => $type->id, 'exportdata' => 1, 'component' => 'core_user', 'name' => 'username')));
    }
}
