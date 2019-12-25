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
 * Tests the observer class.
 */
class totara_userdata_local_observer_testcase extends advanced_testcase {
    public static function setUpBeforeClass(){
        global $CFG;
        parent::setUpBeforeClass();
        require_once("$CFG->dirroot/user/lib.php");
    }

    public function test_user_created() {
        global $DB;
        $this->resetAfterTest();

        $activeuserdata = $this->getDataGenerator()->create_user(array(), array('noinsert' => true));
        $activeuserid = user_create_user($activeuserdata, false, true);
        $activeuser = $DB->get_record('user', array('id' => $activeuserid));
        $record = $DB->get_record('totara_userdata_user', array('userid' => $activeuser->id), '*', MUST_EXIST);
        $activeusercontext = context_user::instance($activeuser->id);
        $this->assertEquals($activeusercontext->id, $record->usercontextid);
        $this->assertNull($record->suspendedpurgetypeid);
        $this->assertNull($record->timesuspended);
        $this->assertNull($record->timesuspendedpurged);
        $this->assertNull($record->deletedpurgetypeid);
        $this->assertNull($record->timedeleted);
        $this->assertNull($record->timedeletedpurged);

        $this->setCurrentTimeStart();
        $suspendeduserdata = $this->getDataGenerator()->create_user(array('suspended' => 1), array('noinsert' => true));
        $suspendeduserid = user_create_user($suspendeduserdata, false, true);
        $suspendeduser = $DB->get_record('user', array('id' => $suspendeduserid));
        $record = $DB->get_record('totara_userdata_user', array('userid' => $suspendeduser->id), '*', MUST_EXIST);
        $suspendedusercontext = context_user::instance($suspendeduser->id);
        $this->assertEquals($suspendedusercontext->id, $record->usercontextid);
        $this->assertNull($record->suspendedpurgetypeid);
        $this->assertTimeCurrent($record->timesuspended);
        $this->assertNull($record->timesuspendedpurged);
        $this->assertNull($record->deletedpurgetypeid);
        $this->assertNull($record->timedeleted);
        $this->assertNull($record->timedeletedpurged);
    }

    public function test_user_updated() {
        global $DB;
        $this->resetAfterTest();

        $activeuserdata = $this->getDataGenerator()->create_user(array(), array('noinsert' => true));
        $activeuserid = user_create_user($activeuserdata, false, true);
        $activeuser = $DB->get_record('user', array('id' => $activeuserid));
        $activeusercontext = context_user::instance($activeuser->id);
        $DB->set_field('totara_userdata_user', 'timesuspended', time(), array('userid' => $activeuser->id));
        $DB->set_field('totara_userdata_user', 'timedeleted', time(), array('userid' => $activeuser->id));
        user_update_user($activeuser, false, true);
        $record = $DB->get_record('totara_userdata_user', array('userid' => $activeuser->id), '*', MUST_EXIST);
        $this->assertEquals($activeusercontext->id, $record->usercontextid);
        $this->assertNull($record->suspendedpurgetypeid);
        $this->assertNull($record->timesuspended);
        $this->assertNull($record->timesuspendedpurged);
        $this->assertNull($record->deletedpurgetypeid);
        $this->assertNull($record->timedeleted);
        $this->assertNull($record->timedeletedpurged);

        $suspendeduserdata = $this->getDataGenerator()->create_user(array('suspended' => 1), array('noinsert' => true));
        $suspendeduserid = user_create_user($suspendeduserdata, false, true);
        $suspendeduser = $DB->get_record('user', array('id' => $suspendeduserid));
        $suspendedusercontext = context_user::instance($suspendeduser->id);
        $now = (string)time();
        $DB->set_field('totara_userdata_user', 'timesuspended', $now, array('userid' => $suspendeduser->id));
        $DB->set_field('totara_userdata_user', 'timedeleted', $now, array('userid' => $suspendeduser->id));
        user_update_user($suspendeduser, false, true);
        $record = $DB->get_record('totara_userdata_user', array('userid' => $suspendeduser->id), '*', MUST_EXIST);
        $this->assertEquals($suspendedusercontext->id, $record->usercontextid);
        $this->assertNull($record->suspendedpurgetypeid);
        $this->assertSame($now, $record->timesuspended);
        $this->assertNull($record->timesuspendedpurged);
        $this->assertNull($record->deletedpurgetypeid);
        $this->assertNull($record->timedeleted);
        $this->assertNull($record->timedeletedpurged);
    }

    public function test_user_suspended() {
        global $DB;
        $this->resetAfterTest();

        $activeuserdata = $this->getDataGenerator()->create_user(array(), array('noinsert' => true));
        $activeuserid = user_create_user($activeuserdata, false, true);
        $activeuser = $DB->get_record('user', array('id' => $activeuserid));
        $DB->set_field('user', 'suspended', 1, array('id' => $activeuser->id));
        $suspendeduser = $DB->get_record('user', array('id' => $activeuserid));
        $suspendedusercontext = context_user::instance($suspendeduser->id);
        $this->setCurrentTimeStart();
        \totara_core\event\user_suspended::create_from_user($suspendeduser)->trigger();
        $record = $DB->get_record('totara_userdata_user', array('userid' => $suspendeduser->id), '*', MUST_EXIST);
        $this->assertEquals($suspendedusercontext->id, $record->usercontextid);
        $this->assertNull($record->suspendedpurgetypeid);
        $this->assertTimeCurrent($record->timesuspended);
        $this->assertNull($record->timesuspendedpurged);
        $this->assertNull($record->deletedpurgetypeid);
        $this->assertNull($record->timedeleted);
        $this->assertNull($record->timedeletedpurged);
    }

    public function test_user_suspended_with_purge() {
        global $DB;
        $this->resetAfterTest();

        /** @var totara_userdata_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_userdata');

        $typesuspendedvalid = $generator->create_purge_type(array('userstatus' => target_user::STATUS_SUSPENDED, 'allowsuspended' => 1, 'items' => 'core_user-additionalnames'));
        $typesuspendedinvalid = $generator->create_purge_type(array('userstatus' => target_user::STATUS_SUSPENDED, 'allowsuspended' => 0, 'items' => 'core_user-additionalnames'));
        $typesuspendeddefault = $generator->create_purge_type(array('userstatus' => target_user::STATUS_SUSPENDED, 'allowsuspended' => 1, 'items' => 'core_user-additionalnames'));

        set_config('defaultsuspendedpurgetypeid', $typesuspendeddefault->id, 'totara_userdata');

        $userdata = $this->getDataGenerator()->create_user(array(), array('noinsert' => true));
        $userid = user_create_user($userdata, false, true);
        $DB->set_field('totara_userdata_user', 'suspendedpurgetypeid', $typesuspendedvalid->id, array('userid' => $userid));
        $DB->set_field('user', 'suspended', 1, array('id' => $userid));
        $suspendeduser = $DB->get_record('user', array('id' => $userid));
        $suspendedusercontext = context_user::instance($suspendeduser->id);
        $this->setCurrentTimeStart();
        \totara_core\event\user_suspended::create_from_user($suspendeduser)->trigger();
        $record = $DB->get_record('totara_userdata_user', array('userid' => $suspendeduser->id), '*', MUST_EXIST);
        $this->assertEquals($suspendedusercontext->id, $record->usercontextid);
        $this->assertSame($typesuspendedvalid->id, $record->suspendedpurgetypeid);
        $this->assertTimeCurrent($record->timesuspended);
        $this->assertNull($record->timesuspendedpurged);
        $this->assertNull($record->deletedpurgetypeid);
        $this->assertNull($record->timedeleted);
        $this->assertNull($record->timedeletedpurged);

        $userdata = $this->getDataGenerator()->create_user(array(), array('noinsert' => true));
        $userid = user_create_user($userdata, false, true);
        $DB->set_field('totara_userdata_user', 'suspendedpurgetypeid', $typesuspendedinvalid->id, array('userid' => $userid));
        $DB->set_field('user', 'suspended', 1, array('id' => $userid));
        $suspendeduser = $DB->get_record('user', array('id' => $userid));
        $suspendedusercontext = context_user::instance($suspendeduser->id);
        $this->setCurrentTimeStart();
        \totara_core\event\user_suspended::create_from_user($suspendeduser)->trigger();
        $record = $DB->get_record('totara_userdata_user', array('userid' => $suspendeduser->id), '*', MUST_EXIST);
        $this->assertEquals($suspendedusercontext->id, $record->usercontextid);
        $this->assertSame($typesuspendedinvalid->id, $record->suspendedpurgetypeid);
        $this->assertTimeCurrent($record->timesuspended);
        $this->assertNull($record->timesuspendedpurged);
        $this->assertNull($record->deletedpurgetypeid);
        $this->assertNull($record->timedeleted);
        $this->assertNull($record->timedeletedpurged);

        $userdata = $this->getDataGenerator()->create_user(array(), array('noinsert' => true));
        $userid = user_create_user($userdata, false, true);
        $DB->set_field('totara_userdata_user', 'suspendedpurgetypeid', null, array('userid' => $userid));
        $DB->set_field('user', 'suspended', 1, array('id' => $userid));
        $suspendeduser = $DB->get_record('user', array('id' => $userid));
        $suspendedusercontext = context_user::instance($suspendeduser->id);
        $this->setCurrentTimeStart();
        \totara_core\event\user_suspended::create_from_user($suspendeduser)->trigger();
        $record = $DB->get_record('totara_userdata_user', array('userid' => $suspendeduser->id), '*', MUST_EXIST);
        $this->assertEquals($suspendedusercontext->id, $record->usercontextid);
        $this->assertSame($typesuspendeddefault->id, $record->suspendedpurgetypeid);
        $this->assertTimeCurrent($record->timesuspended);
        $this->assertNull($record->timesuspendedpurged);
        $this->assertNull($record->deletedpurgetypeid);
        $this->assertNull($record->timedeleted);
        $this->assertNull($record->timedeletedpurged);
    }

    public function test_user_deleted() {
        global $DB;
        $this->resetAfterTest();

        $activeuserdata = $this->getDataGenerator()->create_user(array(), array('noinsert' => true));
        $activeuserid = user_create_user($activeuserdata, false, true);
        $activeuser = $DB->get_record('user', array('id' => $activeuserid));
        $activeusercontext = context_user::instance($activeuser->id);
        $this->setCurrentTimeStart();
        user_delete_user($activeuser);
        $deleteduser = $DB->get_record('user', array('id' => $activeuserid));
        $record = $DB->get_record('totara_userdata_user', array('userid' => $deleteduser->id), '*', MUST_EXIST);
        $this->assertEquals($activeusercontext->id, $record->usercontextid);
        $this->assertNull($record->suspendedpurgetypeid);
        $this->assertNull($record->timesuspended);
        $this->assertNull($record->timesuspendedpurged);
        $this->assertNull($record->deletedpurgetypeid);
        $this->assertTimeCurrent($record->timedeleted);
        $this->assertNull($record->timedeletedpurged);
    }

    public function test_user_deleted_with_purge() {
        global $DB;
        $this->resetAfterTest();

        /** @var totara_userdata_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_userdata');

        $typedeletedvalid = $generator->create_purge_type(array('userstatus' => target_user::STATUS_DELETED, 'allowdeleted' => 1, 'items' => 'core_user-additionalnames'));
        $typedeletedinvalid = $generator->create_purge_type(array('userstatus' => target_user::STATUS_DELETED, 'allowdeleted' => 0, 'items' => 'core_user-additionalnames'));
        $typedeleteddefault = $generator->create_purge_type(array('userstatus' => target_user::STATUS_DELETED, 'allowdeleted' => 1, 'items' => 'core_user-additionalnames'));

        set_config('defaultdeletedpurgetypeid', $typedeleteddefault->id, 'totara_userdata');

        $userdata = $this->getDataGenerator()->create_user(array(), array('noinsert' => true));
        $userid = user_create_user($userdata, false, true);
        $user = $DB->get_record('user', array('id' => $userid));
        $DB->set_field('totara_userdata_user', 'deletedpurgetypeid', $typedeletedvalid->id, array('userid' => $userid));
        $deletedusercontext = context_user::instance($user->id);
        $this->setCurrentTimeStart();
        user_delete_user($user);
        $deleteduser = $DB->get_record('user', array('id' => $userid));
        $record = $DB->get_record('totara_userdata_user', array('userid' => $deleteduser->id), '*', MUST_EXIST);
        $this->assertEquals($deletedusercontext->id, $record->usercontextid);
        $this->assertNull($record->suspendedpurgetypeid);
        $this->assertNull($record->timesuspended);
        $this->assertNull($record->timesuspendedpurged);
        $this->assertSame($typedeletedvalid->id, $record->deletedpurgetypeid);
        $this->assertTimeCurrent($record->timedeleted);
        $this->assertNull($record->timedeletedpurged);

        $userdata = $this->getDataGenerator()->create_user(array(), array('noinsert' => true));
        $userid = user_create_user($userdata, false, true);
        $user = $DB->get_record('user', array('id' => $userid));
        $DB->set_field('totara_userdata_user', 'deletedpurgetypeid', $typedeletedinvalid->id, array('userid' => $userid));
        $deletedusercontext = context_user::instance($user->id);
        $this->setCurrentTimeStart();
        user_delete_user($user);
        $deleteduser = $DB->get_record('user', array('id' => $userid));
        $record = $DB->get_record('totara_userdata_user', array('userid' => $deleteduser->id), '*', MUST_EXIST);
        $this->assertEquals($deletedusercontext->id, $record->usercontextid);
        $this->assertNull($record->suspendedpurgetypeid);
        $this->assertNull($record->timesuspended);
        $this->assertNull($record->timesuspendedpurged);
        $this->assertSame($typedeletedinvalid->id, $record->deletedpurgetypeid);
        $this->assertTimeCurrent($record->timedeleted);
        $this->assertNull($record->timedeletedpurged);

        $userdata = $this->getDataGenerator()->create_user(array(), array('noinsert' => true));
        $userid = user_create_user($userdata, false, true);
        $user = $DB->get_record('user', array('id' => $userid));
        $DB->set_field('totara_userdata_user', 'deletedpurgetypeid', null, array('userid' => $userid));
        $deletedusercontext = context_user::instance($user->id);
        $this->setCurrentTimeStart();
        user_delete_user($user);
        $deleteduser = $DB->get_record('user', array('id' => $userid));
        $record = $DB->get_record('totara_userdata_user', array('userid' => $deleteduser->id), '*', MUST_EXIST);
        $this->assertEquals($deletedusercontext->id, $record->usercontextid);
        $this->assertNull($record->suspendedpurgetypeid);
        $this->assertNull($record->timesuspended);
        $this->assertNull($record->timesuspendedpurged);
        $this->assertSame($typedeleteddefault->id, $record->deletedpurgetypeid);
        $this->assertTimeCurrent($record->timedeleted);
        $this->assertNull($record->timedeletedpurged);
    }

    public function test_user_undeleted() {
        global $DB;
        $this->resetAfterTest();

        $activeuserdata = $this->getDataGenerator()->create_user(array(), array('noinsert' => true));
        $activeuserid = user_create_user($activeuserdata, false, true);
        $activeuser = $DB->get_record('user', array('id' => $activeuserid));
        $activeusercontext = context_user::instance($activeuser->id);
        $DB->set_field('user', 'deleted', 1, array('id' => $activeuser->id));
        $activeuser->deleted = '1';
        $DB->set_field('totara_userdata_user', 'timesuspended', time(), array('userid' => $activeuser->id));
        $DB->set_field('totara_userdata_user', 'timedeleted', time(), array('userid' => $activeuser->id));
        undelete_user($activeuser);
        $undeleteduser = $DB->get_record('user', array('id' => $activeuserid));
        $record = $DB->get_record('totara_userdata_user', array('userid' => $undeleteduser->id), '*', MUST_EXIST);
        $this->assertEquals($activeusercontext->id, $record->usercontextid);
        $this->assertNull($record->suspendedpurgetypeid);
        $this->assertNull($record->timesuspended);
        $this->assertNull($record->timesuspendedpurged);
        $this->assertNull($record->deletedpurgetypeid);
        $this->assertNull($record->timedeleted);
        $this->assertNull($record->timedeletedpurged);

        $activeuserdata = $this->getDataGenerator()->create_user(array('suspended' => 1), array('noinsert' => true));
        $activeuserid = user_create_user($activeuserdata, false, true);
        $activeuser = $DB->get_record('user', array('id' => $activeuserid));
        $activeusercontext = context_user::instance($activeuser->id);
        $DB->set_field('user', 'deleted', 1, array('id' => $activeuser->id));
        $activeuser->deleted = '1';
        $DB->set_field('totara_userdata_user', 'timesuspended', time() - 100, array('userid' => $activeuser->id));
        $DB->set_field('totara_userdata_user', 'timedeleted', time() - 100, array('userid' => $activeuser->id));
        $this->setCurrentTimeStart();
        undelete_user($activeuser);
        $undeleteduser = $DB->get_record('user', array('id' => $activeuserid));
        $record = $DB->get_record('totara_userdata_user', array('userid' => $undeleteduser->id), '*', MUST_EXIST);
        $this->assertEquals($activeusercontext->id, $record->usercontextid);
        $this->assertNull($record->suspendedpurgetypeid);
        $this->assertTimeCurrent($record->timesuspended);
        $this->assertNull($record->timesuspendedpurged);
        $this->assertNull($record->deletedpurgetypeid);
        $this->assertNull($record->timedeleted);
        $this->assertNull($record->timedeletedpurged);
    }
}
