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

use totara_userdata\userdata\manager;
use totara_userdata\userdata\item;
use totara_userdata\userdata\target_user;

defined('MOODLE_INTERNAL') || die();

/**
 * Tests the base item class.
 */
class totara_userdata_manager_testcase extends advanced_testcase {
    public function test_get_results() {
        $results = manager::get_results();
        $this->assertCount(5, $results);
        $this->assertSame([item::RESULT_STATUS_SUCCESS, item::RESULT_STATUS_ERROR, item::RESULT_STATUS_SKIPPED, item::RESULT_STATUS_CANCELLED, item::RESULT_STATUS_TIMEDOUT], array_keys($results));
    }

    public function test_get_purge_types() {
        $this->resetAfterTest();

        /** @var totara_userdata_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_userdata');

        $typeactive1 = $generator->create_purge_type(array('userstatus' => target_user::STATUS_ACTIVE, 'allowmanual' => 1));
        $typeactive2 = $generator->create_purge_type(array('userstatus' => target_user::STATUS_ACTIVE, 'allowmanual' => 0));
        $typesuspended1 = $generator->create_purge_type(array('userstatus' => target_user::STATUS_SUSPENDED, 'allowmanual' => 1, 'allowsuspended' => 0));
        $typesuspended2 = $generator->create_purge_type(array('userstatus' => target_user::STATUS_SUSPENDED, 'allowmanual' => 0, 'allowsuspended' => 1));
        $typedeleted1 = $generator->create_purge_type(array('userstatus' => target_user::STATUS_DELETED, 'allowmanual' => 1, 'allowdeleted' => 0));
        $typedeleted2 = $generator->create_purge_type(array('userstatus' => target_user::STATUS_DELETED, 'allowmanual' => 0, 'allowdeleted' => 1));

        $types = manager::get_purge_types(target_user::STATUS_ACTIVE, 'manual');
        $this->assertCount(1, $types);
        $this->assertArrayHasKey($typeactive1->id, $types);

        $types = manager::get_purge_types(target_user::STATUS_ACTIVE, 'other');
        $this->assertCount(2, $types);
        $this->assertArrayHasKey($typeactive1->id, $types);
        $this->assertArrayHasKey($typeactive2->id, $types);

        $types = manager::get_purge_types(target_user::STATUS_ACTIVE);
        $this->assertCount(2, $types);
        $this->assertArrayHasKey($typeactive1->id, $types);
        $this->assertArrayHasKey($typeactive2->id, $types);

        $types = manager::get_purge_types(target_user::STATUS_ACTIVE, 'manual', $typeactive2->id);
        $this->assertCount(2, $types);
        $this->assertArrayHasKey($typeactive1->id, $types);
        $this->assertArrayHasKey($typeactive2->id, $types);

        $types = manager::get_purge_types(target_user::STATUS_ACTIVE, 'manual', $typesuspended1->id);
        $this->assertCount(1, $types);
        $this->assertArrayHasKey($typeactive1->id, $types);

        $types = manager::get_purge_types(target_user::STATUS_ACTIVE, 'deleted');
        $this->assertCount(0, $types);

        $types = manager::get_purge_types(target_user::STATUS_ACTIVE, 'suspended');
        $this->assertCount(0, $types);


        $types = manager::get_purge_types(target_user::STATUS_SUSPENDED, 'manual');
        $this->assertCount(1, $types);
        $this->assertArrayHasKey($typesuspended1->id, $types);

        $types = manager::get_purge_types(target_user::STATUS_SUSPENDED, 'other');
        $this->assertCount(2, $types);
        $this->assertArrayHasKey($typesuspended1->id, $types);
        $this->assertArrayHasKey($typesuspended2->id, $types);

        $types = manager::get_purge_types(target_user::STATUS_SUSPENDED);
        $this->assertCount(2, $types);
        $this->assertArrayHasKey($typesuspended1->id, $types);
        $this->assertArrayHasKey($typesuspended2->id, $types);

        $types = manager::get_purge_types(target_user::STATUS_SUSPENDED, 'manual', $typesuspended2->id);
        $this->assertCount(2, $types);
        $this->assertArrayHasKey($typesuspended1->id, $types);
        $this->assertArrayHasKey($typesuspended2->id, $types);

        $types = manager::get_purge_types(target_user::STATUS_SUSPENDED, 'manual', $typeactive1->id);
        $this->assertCount(1, $types);
        $this->assertArrayHasKey($typesuspended1->id, $types);

        $types = manager::get_purge_types(target_user::STATUS_SUSPENDED, 'suspended');
        $this->assertCount(1, $types);
        $this->assertArrayHasKey($typesuspended2->id, $types);

        $types = manager::get_purge_types(target_user::STATUS_SUSPENDED, 'suspended', $typesuspended1->id);
        $this->assertCount(2, $types);
        $this->assertArrayHasKey($typesuspended1->id, $types);
        $this->assertArrayHasKey($typesuspended2->id, $types);

        $types = manager::get_purge_types(target_user::STATUS_SUSPENDED, 'deleted');
        $this->assertCount(0, $types);


        $types = manager::get_purge_types(target_user::STATUS_DELETED, 'manual');
        $this->assertCount(1, $types);
        $this->assertArrayHasKey($typedeleted1->id, $types);

        $types = manager::get_purge_types(target_user::STATUS_DELETED, 'other');
        $this->assertCount(2, $types);
        $this->assertArrayHasKey($typedeleted1->id, $types);
        $this->assertArrayHasKey($typedeleted2->id, $types);

        $types = manager::get_purge_types(target_user::STATUS_DELETED);
        $this->assertCount(2, $types);
        $this->assertArrayHasKey($typedeleted1->id, $types);
        $this->assertArrayHasKey($typedeleted2->id, $types);

        $types = manager::get_purge_types(target_user::STATUS_DELETED, 'manual', $typedeleted2->id);
        $this->assertCount(2, $types);
        $this->assertArrayHasKey($typedeleted1->id, $types);
        $this->assertArrayHasKey($typedeleted2->id, $types);

        $types = manager::get_purge_types(target_user::STATUS_DELETED, 'manual', $typeactive1->id);
        $this->assertCount(1, $types);
        $this->assertArrayHasKey($typedeleted1->id, $types);

        $types = manager::get_purge_types(target_user::STATUS_DELETED, 'deleted');
        $this->assertCount(1, $types);
        $this->assertArrayHasKey($typedeleted2->id, $types);

        $types = manager::get_purge_types(target_user::STATUS_DELETED, 'deleted', $typedeleted1->id);
        $this->assertCount(2, $types);
        $this->assertArrayHasKey($typedeleted1->id, $types);
        $this->assertArrayHasKey($typedeleted2->id, $types);

        $types = manager::get_purge_types(target_user::STATUS_DELETED, 'suspended');
        $this->assertCount(0, $types);


        try {
            manager::get_purge_types(target_user::STATUS_ACTIVE, 'xxx');
            $this->fail('coding_exception expected');
        } catch (Throwable $ex) {
            $this->assertInstanceOf('coding_exception', $ex);
        }
    }

    public function test_get_export_types() {
        $this->resetAfterTest();

        /** @var totara_userdata_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_userdata');

        $type1 = $generator->create_export_type(array('userstatus' => target_user::STATUS_ACTIVE, 'allowself' => 1));
        $type2 = $generator->create_export_type(array('userstatus' => target_user::STATUS_ACTIVE, 'allowself' => 0));
        $type3 = $generator->create_export_type(array('userstatus' => target_user::STATUS_ACTIVE, 'allowself' => 1));

        $types = manager::get_export_types('self');
        $this->assertCount(2, $types);
        $this->assertArrayHasKey($type1->id, $types);
        $this->assertArrayHasKey($type3->id, $types);

        $types = manager::get_export_types('self', $type2->id);
        $this->assertCount(3, $types);
        $this->assertArrayHasKey($type1->id, $types);
        $this->assertArrayHasKey($type3->id, $types);

        $types = manager::get_export_types('other');
        $this->assertCount(3, $types);
        $this->assertArrayHasKey($type1->id, $types);
        $this->assertArrayHasKey($type2->id, $types);
        $this->assertArrayHasKey($type3->id, $types);

        $types = manager::get_export_types();
        $this->assertCount(3, $types);
        $this->assertArrayHasKey($type1->id, $types);
        $this->assertArrayHasKey($type2->id, $types);
        $this->assertArrayHasKey($type3->id, $types);

        try {
            manager::get_export_types('xxx');
        } catch (Throwable $ex) {
            $this->assertInstanceOf('coding_exception', $ex);
        }
    }

    public function test_create_purge() {
        global $DB;
        $this->resetAfterTest();

        /** @var totara_userdata_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_userdata');

        $typeactive1 = $generator->create_purge_type(array('userstatus' => target_user::STATUS_ACTIVE, 'allowmanual' => 1, 'items' => 'core_user-additionalnames'));
        $typeactive2 = $generator->create_purge_type(array('userstatus' => target_user::STATUS_ACTIVE, 'allowmanual' => 0, 'items' => 'core_user-additionalnames'));
        $typesuspended1 = $generator->create_purge_type(array('userstatus' => target_user::STATUS_SUSPENDED, 'allowmanual' => 1, 'allowsuspended' => 0, 'items' => 'core_user-additionalnames'));
        $typesuspended2 = $generator->create_purge_type(array('userstatus' => target_user::STATUS_SUSPENDED, 'allowmanual' => 0, 'allowsuspended' => 1, 'items' => 'core_user-additionalnames'));
        $typedeleted1 = $generator->create_purge_type(array('userstatus' => target_user::STATUS_DELETED, 'allowmanual' => 1, 'allowdeleted' => 0, 'items' => 'core_user-additionalnames'));
        $typedeleted2 = $generator->create_purge_type(array('userstatus' => target_user::STATUS_DELETED, 'allowmanual' => 0, 'allowdeleted' => 1, 'items' => 'core_user-additionalnames'));

        $activeuser = $this->getDataGenerator()->create_user();
        $suspendeduser = $this->getDataGenerator()->create_user(array('suspended' => 1));
        $deleteduser = $this->getDataGenerator()->create_user(array('deleted' => 1));
        $creator = $this->getDataGenerator()->create_user();

        $syscontext = context_system::instance();
        $coursecontext = context_course::instance(get_site()->id);
        $activeusercontext = context_user::instance($activeuser->id);
        $suspendedusercontext = context_user::instance($suspendeduser->id);

        $this->setUser($creator);

        $this->setCurrentTimeStart();
        $purgeid = manager::create_purge($activeuser->id, $syscontext->id, $typeactive1->id, 'manual');
        $purge = $DB->get_record('totara_userdata_purge', array('id' => $purgeid), '*', MUST_EXIST);
        $this->assertSame($typeactive1->id, $purge->purgetypeid);
        $this->assertSame('manual', $purge->origin);
        $this->assertSame((string)$activeusercontext->id, $purge->usercontextid);
        $this->assertSame((string)$syscontext->id, $purge->contextid);
        $this->assertSame($creator->id, $purge->usercreated);
        $this->assertTimeCurrent($purge->timecreated);
        $this->assertNull($purge->timestarted);
        $this->assertNull($purge->timefinished);
        $this->assertNull($purge->result);

        $this->setCurrentTimeStart();
        $purgeid = manager::create_purge($suspendeduser->id, $coursecontext->id, $typesuspended1->id, 'suspended');
        $purge = $DB->get_record('totara_userdata_purge', array('id' => $purgeid), '*', MUST_EXIST);
        $this->assertSame($typesuspended1->id, $purge->purgetypeid);
        $this->assertSame('suspended', $purge->origin);
        $this->assertSame((string)$suspendedusercontext->id, $purge->usercontextid);
        $this->assertSame((string)$coursecontext->id, $purge->contextid);
        $this->assertSame($creator->id, $purge->usercreated);
        $this->assertTimeCurrent($purge->timecreated);
        $this->assertNull($purge->timestarted);
        $this->assertNull($purge->timefinished);
        $this->assertNull($purge->result);

        $this->setCurrentTimeStart();
        $purgeid = manager::create_purge($deleteduser->id, $syscontext->id, $typedeleted2->id, 'deleted');
        $purge = $DB->get_record('totara_userdata_purge', array('id' => $purgeid), '*', MUST_EXIST);
        $this->assertSame($typedeleted2->id, $purge->purgetypeid);
        $this->assertSame('deleted', $purge->origin);
        $this->assertNull($purge->usercontextid);
        $this->assertSame((string)$syscontext->id, $purge->contextid);
        $this->assertSame($creator->id, $purge->usercreated);
        $this->assertTimeCurrent($purge->timecreated);
        $this->assertNull($purge->timestarted);
        $this->assertNull($purge->timefinished);
        $this->assertNull($purge->result);

        $this->setCurrentTimeStart();
        $purgeid = manager::create_purge($activeuser->id, $syscontext->id, $typeactive1->id, 'other');
        $purge = $DB->get_record('totara_userdata_purge', array('id' => $purgeid), '*', MUST_EXIST);
        $this->assertSame($typeactive1->id, $purge->purgetypeid);
        $this->assertSame('other', $purge->origin);
        $this->assertSame((string)$activeusercontext->id, $purge->usercontextid);
        $this->assertSame((string)$syscontext->id, $purge->contextid);
        $this->assertSame($creator->id, $purge->usercreated);
        $this->assertTimeCurrent($purge->timecreated);
        $this->assertNull($purge->timestarted);
        $this->assertNull($purge->timefinished);
        $this->assertNull($purge->result);

        $this->setCurrentTimeStart();
        $purgeid = manager::create_purge($activeuser->id, $syscontext->id, $typeactive1->id);
        $purge = $DB->get_record('totara_userdata_purge', array('id' => $purgeid), '*', MUST_EXIST);
        $this->assertSame($typeactive1->id, $purge->purgetypeid);
        $this->assertSame('other', $purge->origin);
        $this->assertSame((string)$activeusercontext->id, $purge->usercontextid);
        $this->assertSame((string)$syscontext->id, $purge->contextid);
        $this->assertSame($creator->id, $purge->usercreated);
        $this->assertTimeCurrent($purge->timecreated);
        $this->assertNull($purge->timestarted);
        $this->assertNull($purge->timefinished);
        $this->assertNull($purge->result);

        // Now problems.

        try {
            manager::create_purge($activeuser->id, $syscontext->id, $typeactive1->id, 'xxx');
            $this->fail('coding_exception expected');
        } catch (Throwable $ex) {
            $this->assertInstanceOf('coding_exception', $ex);
        }

        try {
            manager::create_purge($deleteduser->id, $syscontext->id, $typeactive1->id, 'manual');
            $this->fail('dml_missing_record_exception expected');
        } catch (Throwable $ex) {
            $this->assertInstanceOf('dml_missing_record_exception', $ex);
        }

        try {
            manager::create_purge($suspendeduser->id, $syscontext->id, $typedeleted1->id, 'manual');
            $this->fail('dml_missing_record_exception expected');
        } catch (Throwable $ex) {
            $this->assertInstanceOf('dml_missing_record_exception', $ex);
        }

        try {
            manager::create_purge(-11, $syscontext->id, $typeactive1->id, 'manual');
            $this->fail('dml_missing_record_exception expected');
        } catch (Throwable $ex) {
            $this->assertInstanceOf('dml_missing_record_exception', $ex);
        }
    }

    public function test_execute_purge() {
        global $DB;
        $this->resetAfterTest();

        /** @var totara_userdata_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_userdata');
        $typeactive1 = $generator->create_purge_type(array('userstatus' => target_user::STATUS_ACTIVE, 'allowmanual' => 1, 'items' => 'core_user-additionalnames'));
        $activeuser = $this->getDataGenerator()->create_user(array('middlename' => 'midddle'));
        $creator = $this->getDataGenerator()->create_user();
        $syscontext = context_system::instance();
        $activeusercontext = context_user::instance($activeuser->id);

        $this->assertSame('midddle', $activeuser->middlename);

        $this->setUser($creator);

        $purgeid = manager::create_purge($activeuser->id, $syscontext->id, $typeactive1->id, 'manual');
        $this->setCurrentTimeStart();
        $result = manager::execute_purge($purgeid);
        $purge = $DB->get_record('totara_userdata_purge', array('id' => $purgeid), '*', MUST_EXIST);
        $this->assertSame(item::RESULT_STATUS_SUCCESS, $result);
        $this->assertSame($typeactive1->id, $purge->purgetypeid);
        $this->assertSame('manual', $purge->origin);
        $this->assertSame((string)$activeusercontext->id, $purge->usercontextid);
        $this->assertSame((string)$syscontext->id, $purge->contextid);
        $this->assertSame($creator->id, $purge->usercreated);
        $this->assertTimeCurrent($purge->timestarted);
        $this->assertGreaterThanOrEqual($purge->timestarted, $purge->timefinished);
        $this->assertSame((string)$result, $purge->result);
        $purgeditems = $DB->get_records('totara_userdata_purge_item', array('purgeid' => $purgeid));
        $this->assertCount(1, $purgeditems);
        $purgeitem = reset($purgeditems);
        $this->assertSame('core_user', $purgeitem->component);
        $this->assertSame('additionalnames', $purgeitem->name);
        $this->assertGreaterThanOrEqual($purge->timestarted, $purgeitem->timestarted);
        $this->assertGreaterThanOrEqual($purgeitem->timestarted, $purgeitem->timefinished);
        $this->assertLessThanOrEqual($purge->timefinished, $purgeitem->timefinished);
        $this->assertSame((string)item::RESULT_STATUS_SUCCESS, $purgeitem->result);
        $purgeuser = $DB->get_record('user', array('id' => $activeuser->id));
        $this->assertNull($purgeuser->middlename);

        $this->setUser($creator);

        $result = manager::execute_purge($purgeid);
        $secondpurge = $DB->get_record('totara_userdata_purge', array('id' => $purgeid), '*', MUST_EXIST);
        $this->assertSame(item::RESULT_STATUS_SUCCESS, $result);
        $this->assertEquals($purge, $secondpurge);

        $this->setUser($creator);

        $purgeid = manager::create_purge($activeuser->id, $syscontext->id, $typeactive1->id, 'other');
        $this->setCurrentTimeStart();
        $DB->set_field('totara_userdata_purge', 'result', item::RESULT_STATUS_CANCELLED, array('id' => $purgeid));
        $result = manager::execute_purge($purgeid);
        $purge = $DB->get_record('totara_userdata_purge', array('id' => $purgeid), '*', MUST_EXIST);
        $this->assertSame(item::RESULT_STATUS_CANCELLED, $result);
        $this->assertSame($typeactive1->id, $purge->purgetypeid);
        $this->assertSame('other', $purge->origin);
        $this->assertSame((string)$activeusercontext->id, $purge->usercontextid);
        $this->assertSame((string)$syscontext->id, $purge->contextid);
        $this->assertSame($creator->id, $purge->usercreated);
        $this->assertNull($purge->timestarted);
        $this->assertNull($purge->timefinished);
        $this->assertSame((string)$result, $purge->result);
        $purgeditems = $DB->get_records('totara_userdata_purge_item', array('purgeid' => $purgeid));
        $this->assertCount(0, $purgeditems);

        $this->setUser($creator);

        $purgeid = manager::create_purge($activeuser->id, $syscontext->id, $typeactive1->id);
        $this->setCurrentTimeStart();
        $DB->set_field('totara_userdata_purge', 'result', item::RESULT_STATUS_CANCELLED, array('id' => $purgeid));
        $result = manager::execute_purge($purgeid);
        $purge = $DB->get_record('totara_userdata_purge', array('id' => $purgeid), '*', MUST_EXIST);
        $this->assertSame(item::RESULT_STATUS_CANCELLED, $result);
        $this->assertSame($typeactive1->id, $purge->purgetypeid);
        $this->assertSame('other', $purge->origin);
        $this->assertSame((string)$activeusercontext->id, $purge->usercontextid);
        $this->assertSame((string)$syscontext->id, $purge->contextid);
        $this->assertSame($creator->id, $purge->usercreated);
        $this->assertNull($purge->timestarted);
        $this->assertNull($purge->timefinished);
        $this->assertSame((string)$result, $purge->result);
        $purgeditems = $DB->get_records('totara_userdata_purge_item', array('purgeid' => $purgeid));
        $this->assertCount(0, $purgeditems);
    }

    public function test_create_export() {
        global $DB;
        $this->resetAfterTest();

        /** @var totara_userdata_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_userdata');

        $type1 = $generator->create_export_type(array('allowself' => 1, 'items' => 'core_user-additionalnames'));
        $type2 = $generator->create_export_type(array('allowself' => 0, 'items' => 'core_user-additionalnames'));

        $activeuser = $this->getDataGenerator()->create_user();
        $deleteduser = $this->getDataGenerator()->create_user(array('deleted' => 1));
        $creator = $this->getDataGenerator()->create_user();

        $syscontext = context_system::instance();
        $coursecontext = context_course::instance(get_site()->id);

        $this->setUser($creator);

        $this->setCurrentTimeStart();
        $exportid = manager::create_export($activeuser->id, $syscontext->id, $type1->id, 'self');
        $export = $DB->get_record('totara_userdata_export', array('id' => $exportid), '*', MUST_EXIST);
        $this->assertSame($type1->id, $export->exporttypeid);
        $this->assertSame('self', $export->origin);
        $this->assertSame((string)$syscontext->id, $export->contextid);
        $this->assertSame($creator->id, $export->usercreated);
        $this->assertTimeCurrent($export->timecreated);
        $this->assertNull($export->timestarted);
        $this->assertNull($export->timefinished);
        $this->assertNull($export->result);

        $this->setCurrentTimeStart();
        $exportid = manager::create_export($deleteduser->id, $coursecontext->id, $type2->id, 'other');
        $export = $DB->get_record('totara_userdata_export', array('id' => $exportid), '*', MUST_EXIST);
        $this->assertSame($type2->id, $export->exporttypeid);
        $this->assertSame('other', $export->origin);
        $this->assertSame((string)$coursecontext->id, $export->contextid);
        $this->assertSame($creator->id, $export->usercreated);
        $this->assertTimeCurrent($export->timecreated);
        $this->assertNull($export->timestarted);
        $this->assertNull($export->timefinished);
        $this->assertNull($export->result);

        $this->setCurrentTimeStart();
        $exportid = manager::create_export($deleteduser->id, $coursecontext->id, $type2->id);
        $export = $DB->get_record('totara_userdata_export', array('id' => $exportid), '*', MUST_EXIST);
        $this->assertSame($type2->id, $export->exporttypeid);
        $this->assertSame('other', $export->origin);
        $this->assertSame((string)$coursecontext->id, $export->contextid);
        $this->assertSame($creator->id, $export->usercreated);
        $this->assertTimeCurrent($export->timecreated);
        $this->assertNull($export->timestarted);
        $this->assertNull($export->timefinished);
        $this->assertNull($export->result);

        try {
            manager::create_export($activeuser->id, $syscontext->id, $type1->id, 'xxx');
            $this->fail('coding_exception expected');
        } catch (Throwable $ex) {
            $this->assertInstanceOf('coding_exception', $ex);
        }
    }

    public function test_execute_export() {
        global $DB;
        $this->resetAfterTest();

        /** @var totara_userdata_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_userdata');
        $typeactive1 = $generator->create_export_type(array('allowself' => 1, 'items' => 'core_user-additionalnames'));
        $activeuser = $this->getDataGenerator()->create_user(array('middlename' => 'midddle'));
        $otheruser = $this->getDataGenerator()->create_user(array());
        $syscontext = context_system::instance();

        $this->setUser($activeuser);

        $exportid = manager::create_export($activeuser->id, $syscontext->id, $typeactive1->id, 'self');
        $this->setCurrentTimeStart();
        $result = manager::execute_export($exportid);
        $export = $DB->get_record('totara_userdata_export', array('id' => $exportid), '*', MUST_EXIST);
        $this->assertSame(item::RESULT_STATUS_SUCCESS, $result);
        $this->assertSame($typeactive1->id, $export->exporttypeid);
        $this->assertSame('self', $export->origin);
        $this->assertSame((string)$syscontext->id, $export->contextid);
        $this->assertSame($activeuser->id, $export->usercreated);
        $this->assertTimeCurrent($export->timestarted);
        $this->assertGreaterThanOrEqual($export->timestarted, $export->timefinished);
        $this->assertSame((string)$result, $export->result);
        $exportditems = $DB->get_records('totara_userdata_export_item', array('exportid' => $exportid));
        $this->assertCount(1, $exportditems);
        $exportitem = reset($exportditems);
        $this->assertSame('core_user', $exportitem->component);
        $this->assertSame('additionalnames', $exportitem->name);
        $this->assertGreaterThanOrEqual($export->timestarted, $exportitem->timestarted);
        $this->assertGreaterThanOrEqual($exportitem->timestarted, $exportitem->timefinished);
        $this->assertLessThanOrEqual($export->timefinished, $exportitem->timefinished);
        $this->assertSame((string)item::RESULT_STATUS_SUCCESS, $exportitem->result);

        $this->setUser($activeuser);

        $result = manager::execute_export($exportid);
        $secondexport = $DB->get_record('totara_userdata_export', array('id' => $exportid), '*', MUST_EXIST);
        $this->assertSame(item::RESULT_STATUS_SUCCESS, $result);
        $this->assertEquals($export, $secondexport);

        $this->setUser($activeuser);

        $exportid = manager::create_export($activeuser->id, $syscontext->id, $typeactive1->id, 'self');
        $this->setCurrentTimeStart();
        $DB->set_field('totara_userdata_export', 'result', item::RESULT_STATUS_CANCELLED, array('id' => $exportid));
        $result = manager::execute_export($exportid);
        $export = $DB->get_record('totara_userdata_export', array('id' => $exportid), '*', MUST_EXIST);
        $this->assertSame(item::RESULT_STATUS_CANCELLED, $result);
        $this->assertSame($typeactive1->id, $export->exporttypeid);
        $this->assertSame('self', $export->origin);
        $this->assertSame((string)$syscontext->id, $export->contextid);
        $this->assertSame($activeuser->id, $export->usercreated);
        $this->assertNull($export->timestarted);
        $this->assertNull($export->timefinished);
        $this->assertSame((string)$result, $export->result);
        $exportditems = $DB->get_records('totara_userdata_export_item', array('exportid' => $exportid));
        $this->assertCount(0, $exportditems);

        $this->setUser($otheruser);

        $exportid = manager::create_export($activeuser->id, $syscontext->id, $typeactive1->id, 'other');
        $this->setCurrentTimeStart();
        $result = manager::execute_export($exportid);
        $export = $DB->get_record('totara_userdata_export', array('id' => $exportid), '*', MUST_EXIST);
        $this->assertSame(item::RESULT_STATUS_SUCCESS, $result);
        $this->assertSame($typeactive1->id, $export->exporttypeid);
        $this->assertSame('other', $export->origin);
        $this->assertSame((string)$syscontext->id, $export->contextid);
        $this->assertSame($otheruser->id, $export->usercreated);
        $this->assertTimeCurrent($export->timestarted);
        $this->assertGreaterThanOrEqual($export->timestarted, $export->timefinished);
        $this->assertSame((string)$result, $export->result);
        $exportditems = $DB->get_records('totara_userdata_export_item', array('exportid' => $exportid));
        $this->assertCount(1, $exportditems);
        $exportitem = reset($exportditems);
        $this->assertSame('core_user', $exportitem->component);
        $this->assertSame('additionalnames', $exportitem->name);
        $this->assertGreaterThanOrEqual($export->timestarted, $exportitem->timestarted);
        $this->assertGreaterThanOrEqual($exportitem->timestarted, $exportitem->timefinished);
        $this->assertLessThanOrEqual($export->timefinished, $exportitem->timefinished);
        $this->assertSame((string)item::RESULT_STATUS_SUCCESS, $exportitem->result);
    }
}
