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

use totara_userdata\userdata\item;
use totara_userdata\local\util;

defined('MOODLE_INTERNAL') || die();

/**
 * Tests the util class.
 */
class totara_userdata_local_util_testcase extends advanced_testcase {
    public function test_get_item_classes() {
        $classes = util::get_item_classes();
        foreach ($classes as $i => $classname) {
            $this->assertIsInt($i);
            $this->assertRegExp('/^[a-z0-9_]+\\\\userdata\\\\[a-z0-9_]+$/', $classname);
        }
    }

    public function test_get_component_name() {
        $classes = util::get_item_classes();
        foreach ($classes as $class) {
            /** @var item $class it is not an instance, this is used for autocomplete in editors only */
            $maincomponent = $class::get_main_component();
            util::get_component_name($maincomponent);
            $this->assertDebuggingNotCalled();
        }
    }

    public function test_backup_user_context_id() {
        global $DB;
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        $usercontext = context_user::instance($user->id);
        $DB->delete_records('totara_userdata_user', array('userid' => $user->id));

        util::backup_user_context_id($user->id, $usercontext->id);
        $record = $DB->get_record('totara_userdata_user', array('userid' => $user->id), '*', MUST_EXIST);
        $this->assertEquals($usercontext->id, $record->usercontextid);
        $this->assertNull($record->suspendedpurgetypeid);
        $this->assertNull($record->timesuspended);
        $this->assertNull($record->timesuspendedpurged);
        $this->assertNull($record->deletedpurgetypeid);
        $this->assertNull($record->timedeleted);
        $this->assertNull($record->timedeletedpurged);

        util::backup_user_context_id($user->id, 666);
        $record = $DB->get_record('totara_userdata_user', array('userid' => $user->id), '*', MUST_EXIST);
        $this->assertEquals(666, $record->usercontextid);
        $this->assertNull($record->suspendedpurgetypeid);
        $this->assertNull($record->timesuspended);
        $this->assertNull($record->timesuspendedpurged);
        $this->assertNull($record->deletedpurgetypeid);
        $this->assertNull($record->timedeleted);
        $this->assertNull($record->timedeletedpurged);

        // Try hacking user record directly the same way as some hacky plugins do it.
        $hackuser = clone($user);
        unset($hackuser->id);
        $hackuser->username = 'xxxxxxx';
        $hackuser->idnumber = '';
        $hackuser->email = 'xxxxxxx@example.com';
        $hackuser->id = $DB->insert_record('user', $hackuser);
        $this->assertFalse($DB->record_exists('context', array('contextlevel' => CONTEXT_USER, 'instanceid' => $hackuser->id)));
        $hackusercontext = context_user::instance($hackuser->id);
        $this->assertTrue($DB->record_exists('context', array('contextlevel' => CONTEXT_USER, 'instanceid' => $hackuser->id)));
        $record = $DB->get_record('totara_userdata_user', array('userid' => $hackuser->id), '*', MUST_EXIST);
        $this->assertEquals($hackusercontext->id, $record->usercontextid);
        $this->assertNull($record->suspendedpurgetypeid);
        $this->assertNull($record->timesuspended);
        $this->assertNull($record->timesuspendedpurged);
        $this->assertNull($record->deletedpurgetypeid);
        $this->assertNull($record->timedeleted);
        $this->assertNull($record->timedeletedpurged);
    }

    public function test_recover_user_context() {
        global $DB;
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        $usercontext = context_user::instance($user->id);
        $DB->delete_records('totara_userdata_user', array('userid' => $user->id));
        $usercontext->delete();
        $this->assertFalse($DB->record_exists('context', array('id' => $usercontext->id)));
        $this->assertFalse($DB->record_exists('context', array('contextlevel' => CONTEXT_USER, 'instanceid' => $user->id)));

        \totara_userdata\local\util::recover_user_context($user->id);
        $this->assertTrue($DB->record_exists('context', array('id' => $usercontext->id)));
        $newusercontext = context_user::instance($user->id);
        $this->assertSame($usercontext->id, $newusercontext->id);
    }

    public function test_get_user_extras() {
        global $DB;
        $this->resetAfterTest();

        $pasttime = (string)(time() - 1000);

        $activeuser = $this->getDataGenerator()->create_user(array('timecreated' => $pasttime));
        $this->assertSame($pasttime, $activeuser->timecreated);
        $this->assertSame($pasttime, $activeuser->timemodified);
        $suspendeduser = $this->getDataGenerator()->create_user(array('suspended' => 1, 'timecreated' => $pasttime));
        $deleteduser = $this->getDataGenerator()->create_user(array('timecreated' => $pasttime));
        $activeusercontext = context_user::instance($activeuser->id);
        $suspendedusercontext = context_user::instance($suspendeduser->id);
        $deletedusercontext = context_user::instance($deleteduser->id);

        $record = util::get_user_extras($activeuser->id);
        $this->assertInstanceOf('stdClass', $record);
        $this->assertEquals($activeusercontext->id, $record->usercontextid);
        $this->assertNull($record->suspendedpurgetypeid);
        $this->assertNull($record->timesuspended);
        $this->assertNull($record->timesuspendedpurged);
        $this->assertNull($record->deletedpurgetypeid);
        $this->assertNull($record->timedeleted);
        $this->assertNull($record->timedeletedpurged);

        $record = util::get_user_extras($suspendeduser->id);
        $this->assertInstanceOf('stdClass', $record);
        $this->assertEquals($suspendedusercontext->id, $record->usercontextid);
        $this->assertNull($record->suspendedpurgetypeid);
        $this->assertSame($pasttime, $record->timesuspended);
        $this->assertNull($record->timesuspendedpurged);
        $this->assertNull($record->deletedpurgetypeid);
        $this->assertNull($record->timedeleted);
        $this->assertNull($record->timedeletedpurged);

        $this->setCurrentTimeStart();
        delete_user($deleteduser);
        $deleteduser = $DB->get_record('user', array('id' => $deleteduser->id));
        $record = util::get_user_extras($deleteduser->id);
        $this->assertInstanceOf('stdClass', $record);
        $this->assertEquals($deletedusercontext->id, $record->usercontextid);
        $this->assertNull($record->suspendedpurgetypeid);
        $this->assertNull($record->timesuspended);
        $this->assertNull($record->timesuspendedpurged);
        $this->assertNull($record->deletedpurgetypeid);
        $this->assertTimeCurrent($record->timedeleted);
        $this->assertNull($record->timedeletedpurged);

        $DB->delete_records('totara_userdata_user', array());

        $record = util::get_user_extras($activeuser->id);
        $this->assertInstanceOf('stdClass', $record);
        $this->assertEquals($activeusercontext->id, $record->usercontextid);
        $this->assertNull($record->suspendedpurgetypeid);
        $this->assertNull($record->timesuspended);
        $this->assertNull($record->timesuspendedpurged);
        $this->assertNull($record->deletedpurgetypeid);
        $this->assertNull($record->timedeleted);
        $this->assertNull($record->timedeletedpurged);

        $suspendeduser = $DB->get_record('user', array('id' => $suspendeduser->id));
        $record = util::get_user_extras($suspendeduser->id);
        $this->assertInstanceOf('stdClass', $record);
        $this->assertEquals($suspendedusercontext->id, $record->usercontextid);
        $this->assertNull($record->suspendedpurgetypeid);
        $this->assertSame($suspendeduser->timemodified, $record->timesuspended);
        $this->assertNull($record->timesuspendedpurged);
        $this->assertNull($record->deletedpurgetypeid);
        $this->assertNull($record->timedeleted);
        $this->assertNull($record->timedeletedpurged);

        $deleteduser = $DB->get_record('user', array('id' => $deleteduser->id));
        $record = util::get_user_extras($deleteduser->id);
        $this->assertInstanceOf('stdClass', $record);
        $this->assertNull($record->usercontextid);
        $this->assertNull($record->suspendedpurgetypeid);
        $this->assertNull($record->timesuspended);
        $this->assertNull($record->timesuspendedpurged);
        $this->assertNull($record->deletedpurgetypeid);
        $this->assertSame($deleteduser->timemodified, $record->timedeleted);
        $this->assertNull($record->timedeletedpurged);
    }

    public function test_sync_totara_userdata_user_table() {
        global $DB;
        $this->resetAfterTest();

        $pasttime = (string)(time() - 1000);

        $guest = guest_user();
        $admin = get_admin();
        $activeuser = $this->getDataGenerator()->create_user(array('timecreated' => $pasttime));
        $this->assertSame($pasttime, $activeuser->timecreated);
        $this->assertSame($pasttime, $activeuser->timemodified);
        $suspendeduser = $this->getDataGenerator()->create_user(array('suspended' => 1, 'timecreated' => $pasttime));
        $deleteduser = $this->getDataGenerator()->create_user(array('timecreated' => $pasttime));
        $activeusercontext = context_user::instance($activeuser->id);
        $suspendedusercontext = context_user::instance($suspendeduser->id);
        $deletedusercontext = context_user::instance($deleteduser->id);
        delete_user($deleteduser);
        $deleteduser = $DB->get_record('user', array('id' => $deleteduser->id));

        // Test nothing changes if all data in place.
        util::get_user_extras($guest->id);
        util::get_user_extras($admin->id);
        util::get_user_extras($activeuser->id);
        util::get_user_extras($suspendeduser->id);
        util::get_user_extras($deleteduser->id);
        $records = $DB->get_records('totara_userdata_user', array(), 'userid ASC');
        util::sync_totara_userdata_user_table();
        $this->assertEquals($records, $DB->get_records('totara_userdata_user', array(), 'userid ASC'));

        // Test contexts are returned back if disappear.
        $DB->set_field('totara_userdata_user', 'usercontextid', null, array());
        util::backup_user_context_id($deleteduser->id, $deletedusercontext->id);
        util::sync_totara_userdata_user_table();
        $this->assertEquals($records, $DB->get_records('totara_userdata_user', array(), 'userid ASC'));

        // Test suspended time gets removed if user not suspended.
        $DB->set_field('totara_userdata_user', 'timesuspended', time(), array('userid' => $activeuser->id));
        util::sync_totara_userdata_user_table();
        $this->assertEquals($records, $DB->get_records('totara_userdata_user', array(), 'userid ASC'));

        // Test deleted time gets removed if user not deleted.
        $DB->set_field('totara_userdata_user', 'timedeleted', time(), array('userid' => $activeuser->id));
        util::sync_totara_userdata_user_table();
        $this->assertEquals($records, $DB->get_records('totara_userdata_user', array(), 'userid ASC'));

        // Test suspended time gets removed if user not suspended.
        $DB->set_field('totara_userdata_user', 'timesuspended', null, array('userid' => $suspendeduser->id));
        util::sync_totara_userdata_user_table();
        $this->assertEquals($records, $DB->get_records('totara_userdata_user', array(), 'userid ASC'));

        // Test deleted time gets removed if user not deleted.
        foreach ($records as $k => $record) {
            if ($record->userid == $deleteduser->id) {
                $records[$k]->timedeleted = $deleteduser->timemodified;
            }
        }
        $DB->set_field('totara_userdata_user', 'timedeleted', null, array('userid' => $deleteduser->id));
        util::sync_totara_userdata_user_table();
        $this->assertEquals($records, $DB->get_records('totara_userdata_user', array(), 'userid ASC'));

        // Recreate everything.
        $DB->delete_records('totara_userdata_user', array());
        util::backup_user_context_id($deleteduser->id, $deletedusercontext->id);
        util::sync_totara_userdata_user_table();
        $newrecords = $DB->get_records('totara_userdata_user', array(), 'userid ASC');
        foreach ($newrecords as $k => $record) {
            unset($newrecords[$k]->id);
        }
        foreach ($records as $k => $unused) {
            unset($records[$k]->id);
        }
        $this->assertEquals(array_values($records), array_values($newrecords));
    }

    /**
     * test returning a sorted array of component labels
     */
    public function test_get_sorted_grouplabels() {
        $groupeditems = [
            'core_question',
            'mod_forum',
            'core_course',
            'core_user',
            'block_recent_activity',
            'block_totara_featured_links'
        ];
        $sortedlabels = util::get_sorted_grouplabels($groupeditems);
        $this->assertEquals(
            [
                'core_user' => 'User',
                'mod_forum' => 'Activity: Forum',
                'block_totara_featured_links' => 'Block: Featured Links',
                'block_recent_activity' => 'Block: Recent activity',
                'core_course' => 'Courses',
                'core_question' => 'Question bank'
            ],
            $sortedlabels
        );

        $sortedlabels = util::get_sorted_grouplabels([]);
        $this->assertEmpty($sortedlabels);
        $this->assertIsArray($sortedlabels);

        $groupeditems = ['core_user'];
        $sortedlabels = util::get_sorted_grouplabels($groupeditems);
        $this->assertEquals(['core_user' => 'User'], $sortedlabels);
    }

}
