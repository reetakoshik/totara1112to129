<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2014 onwards Totara Learning Solutions LTD
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
 * @package totara_cohort
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Test Totara bulk enrolment modifications.
 */
class enrol_cohort_bulk_sync_testcase extends advanced_testcase {
    public function test_general() {
        global $CFG, $DB;
        require_once("$CFG->dirroot/enrol/cohort/locallib.php");

        $this->resetAfterTest();

        // Test other things work as expected.

        $this->assertEquals(0, $DB->count_records('enrol'));
        $this->assertEquals(0, $DB->count_records('user_enrolments'));
        $this->assertEquals(0, $DB->count_records('role_assignments'));

        set_config('enrol_plugins_enabled', 'manual');

        $cohortplugin = enrol_get_plugin('cohort');
        $manualplugin = enrol_get_plugin('manual');

        $studentrole = $DB->get_record('role', array('shortname' => 'student'));
        $this->assertNotEmpty($studentrole);
        $teacherrole = $DB->get_record('role', array('shortname' => 'teacher'));
        $this->assertNotEmpty($teacherrole);

        $course1 = $this->getDataGenerator()->create_course();
        $maninstance1 = $DB->get_record('enrol', array('courseid' => $course1->id, 'enrol' => 'manual'), '*', MUST_EXIST);

        $user1 = $this->getDataGenerator()->create_user();

        $cohort1 = $this->getDataGenerator()->create_cohort();

        $manualplugin->enrol_user($maninstance1, $user1->id, $studentrole->id);

        $this->assertEquals(1, $DB->count_records('enrol'));
        $this->assertEquals(1, $DB->count_records('user_enrolments'));
        $this->assertEquals(1, $DB->count_records('role_assignments'));

        // Test it runs fine when nothing present and plugin disabled.

        $sink = $this->redirectEvents();
        $nulltrace = new null_progress_trace();

        $sink->clear();
        $result = enrol_cohort_sync($nulltrace, null);

        $this->assertSame(2, $result);
        $this->assertCount(0, $sink->get_events());
        $this->assertEquals(1, $DB->count_records('enrol'));
        $this->assertEquals(1, $DB->count_records('user_enrolments'));
        $this->assertEquals(1, $DB->count_records('role_assignments'));

        // Test it runs fine when nothing present and plugin enabled.

        set_config('enrol_plugins_enabled', 'manual,cohort');

        $sink->clear();
        $result = enrol_cohort_sync($nulltrace, null);

        $this->assertSame(0, $result);
        $this->assertCount(0, $sink->get_events());
        $this->assertEquals(1, $DB->count_records('enrol'));
        $this->assertEquals(1, $DB->count_records('user_enrolments'));
        $this->assertEquals(1, $DB->count_records('role_assignments'));

        // Test adding of empty cohorts to course.

        $fields = array('name' => 'cohort sync 1', 'status' => ENROL_INSTANCE_ENABLED, 'customint1' => $cohort1->id, 'roleid' => 0, 'customint2' => 0);
        $cohortplugin->add_instance($course1, $fields);

        $sink->clear();
        $result = enrol_cohort_sync($nulltrace, null);
        $this->assertSame(0, $result);
        $this->assertCount(0, $sink->get_events());
        $this->assertEquals(2, $DB->count_records('enrol'));
        $this->assertEquals(1, $DB->count_records('user_enrolments'));
        $this->assertEquals(1, $DB->count_records('role_assignments'));

        // Test observers are ignored when events redirected.
        $sink->clear();
        cohort_add_member($cohort1->id, $user1->id);
        $this->assertEquals(2, $DB->count_records('enrol'));
        $this->assertEquals(1, $DB->count_records('user_enrolments'));
        $this->assertEquals(1, $DB->count_records('role_assignments'));

        $sink->close();
    }

    public function test_enrolments() {
        global $CFG, $DB, $USER;
        require_once("$CFG->dirroot/enrol/cohort/locallib.php");
        require_once("$CFG->dirroot/cohort/lib.php");

        $this->resetAfterTest();

        // When events are redirected the observer does not sync the data immediately,
        // here we are testing manual sync via enrol_cohort_sync() function.
        $sink = $this->redirectEvents();
        $nulltrace = new null_progress_trace();

        set_config('enrol_plugins_enabled', 'manual,cohort');

        $cohortplugin = enrol_get_plugin('cohort');
        $manualplugin = enrol_get_plugin('manual');

        $studentrole = $DB->get_record('role', array('shortname' => 'student'));
        $teacherrole = $DB->get_record('role', array('shortname' => 'teacher'));

        $cat1 = $this->getDataGenerator()->create_category();
        $cat2 = $this->getDataGenerator()->create_category();

        $course1 = $this->getDataGenerator()->create_course(array('category' => $cat1->id));
        $course2 = $this->getDataGenerator()->create_course(array('category' => $cat1->id));
        $course3 = $this->getDataGenerator()->create_course(array('category' => $cat2->id));
        $course4 = $this->getDataGenerator()->create_course(array('category' => $cat2->id));
        $maninstance1 = $DB->get_record('enrol', array('courseid' => $course1->id, 'enrol' => 'manual'), '*', MUST_EXIST);
        $maninstance2 = $DB->get_record('enrol', array('courseid' => $course2->id, 'enrol' => 'manual'), '*', MUST_EXIST);
        $maninstance3 = $DB->get_record('enrol', array('courseid' => $course3->id, 'enrol' => 'manual'), '*', MUST_EXIST);
        $maninstance4 = $DB->get_record('enrol', array('courseid' => $course4->id, 'enrol' => 'manual'), '*', MUST_EXIST);

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        $user4 = $this->getDataGenerator()->create_user();

        $cohort1 = $this->getDataGenerator()->create_cohort(array('contextid'=>context_coursecat::instance($cat1->id)->id));
        $cohort2 = $this->getDataGenerator()->create_cohort(array('contextid'=>context_coursecat::instance($cat2->id)->id));
        $cohort3 = $this->getDataGenerator()->create_cohort();
        $cohort4 = $this->getDataGenerator()->create_cohort();

        $this->assertEquals(4, $DB->count_records('enrol'));
        $this->assertEquals(0, $DB->count_records('user_enrolments'));
        $this->assertEquals(0, $DB->count_records('role_assignments'));

        $manualplugin->enrol_user($maninstance1, $user4->id, $studentrole->id);
        $manualplugin->enrol_user($maninstance4, $user4->id, 0);

        $fields = array('name' => 'cohort sync 1', 'status' => ENROL_INSTANCE_ENABLED, 'customint1' => $cohort1->id, 'roleid' => 0, 'customint2' => 0);
        $id = $cohortplugin->add_instance($course1, $fields);
        $cohortinstance1 = $DB->get_record('enrol', array('id' => $id));
        $fields = array('name' => 'cohort sync 2', 'status' => ENROL_INSTANCE_ENABLED, 'customint1' => $cohort2->id, 'roleid' => 0, 'customint2' => 0);
        $id = $cohortplugin->add_instance($course1, $fields);
        $cohortinstance2 = $DB->get_record('enrol', array('id' => $id));
        $fields = array('name' => 'cohort sync 3', 'status' => ENROL_INSTANCE_DISABLED, 'customint1' => $cohort3->id, 'roleid' => 0, 'customint2' => 0);
        $id = $cohortplugin->add_instance($course3, $fields);
        $cohortinstance3 = $DB->get_record('enrol', array('id' => $id));

        // Test enrolling of users.

        cohort_add_member($cohort1->id, $user1->id);

        $this->assertEquals(7, $DB->count_records('enrol'));
        $this->assertEquals(2, $DB->count_records('user_enrolments'));
        $this->assertEquals(1, $DB->count_records('role_assignments'));

        $sink->clear();
        $this->setCurrentTimeStart();
        $result = enrol_cohort_sync($nulltrace, null);
        $events = $sink->get_events();
        $this->assertSame(0, $result);
        $this->assertCount(3, $events);
        $this->assertEquals(7, $DB->count_records('enrol'));
        $this->assertEquals(3, $DB->count_records('user_enrolments'));
        $this->assertEquals(1, $DB->count_records('role_assignments'));

        $this->assertInstanceOf('totara_core\event\bulk_enrolments_started', $events[0]);
        $this->assertInstanceOf('core\event\user_enrolment_created', $events[1]);
        $this->assertInstanceOf('totara_core\event\bulk_enrolments_ended', $events[2]);

        $ue = $DB->get_record('user_enrolments', array('userid' => $user1->id, 'enrolid' => $cohortinstance1->id), '*', MUST_EXIST);
        $this->assertTimeCurrent($ue->timecreated);
        $this->assertSame($ue->timecreated, $ue->timemodified);
        $this->assertEquals($USER->id, $ue->modifierid);
        $this->assertEquals(ENROL_USER_ACTIVE, $ue->status);
        $this->assertSame('0', $ue->timestart);
        $this->assertSame('0', $ue->timeend);
        $this->assertSame($course1->id, $events[0]->courseid);
        $this->assertSame($course1->id, $events[1]->courseid);
        $this->assertSame($course1->id, $events[2]->courseid);

        cohort_add_member($cohort1->id, $user2->id);
        cohort_add_member($cohort1->id, $user4->id);
        cohort_add_member($cohort2->id, $user1->id);
        cohort_add_member($cohort3->id, $user1->id);
        cohort_add_member($cohort4->id, $user1->id);

        $this->assertEquals(7, $DB->count_records('enrol'));
        $this->assertEquals(3, $DB->count_records('user_enrolments'));
        $this->assertEquals(1, $DB->count_records('role_assignments'));

        $sink->clear();
        $result = enrol_cohort_sync($nulltrace, null);
        $events = $sink->get_events();
        $this->assertSame(0, $result);
        $this->assertCount(7, $events);
        $this->assertEquals(7, $DB->count_records('enrol'));
        $this->assertEquals(6, $DB->count_records('user_enrolments'));
        $this->assertEquals(1, $DB->count_records('role_assignments'));

        $this->assertInstanceOf('totara_core\event\bulk_enrolments_started', $events[0]);
        $this->assertInstanceOf('core\event\user_enrolment_created', $events[1]);
        $this->assertInstanceOf('core\event\user_enrolment_created', $events[5]);
        $this->assertInstanceOf('totara_core\event\bulk_enrolments_ended', $events[6]);

        $sink->clear();
        $result = enrol_cohort_sync($nulltrace, null); // Repeat with no change.
        $this->assertSame(0, $result);
        $this->assertCount(0, $sink->get_events());
        $this->assertEquals(7, $DB->count_records('enrol'));
        $this->assertEquals(6, $DB->count_records('user_enrolments'));
        $this->assertEquals(1, $DB->count_records('role_assignments'));

        cohort_add_member($cohort1->id, $user3->id);
        $cohortinstance3->status = ENROL_INSTANCE_ENABLED;
        $DB->update_record('enrol', $cohortinstance3);

        $sink->clear();
        $result = enrol_cohort_sync($nulltrace, $course2->id);
        $this->assertSame(0, $result);
        $this->assertCount(0, $sink->get_events());

        $sink->clear();
        $result = enrol_cohort_sync($nulltrace, $course1->id);
        $events = $sink->get_events();
        $this->assertSame(0, $result);
        $this->assertCount(3, $events);
        $this->assertEquals(7, $DB->count_records('enrol'));
        $this->assertEquals(7, $DB->count_records('user_enrolments'));
        $this->assertEquals(1, $DB->count_records('role_assignments'));
        $this->assertInstanceOf('totara_core\event\bulk_enrolments_started', $events[0]);
        $this->assertInstanceOf('core\event\user_enrolment_created', $events[1]);
        $this->assertInstanceOf('totara_core\event\bulk_enrolments_ended', $events[2]);

        $sink->clear();
        $result = enrol_cohort_sync($nulltrace, $course3->id);
        $events = $sink->get_events();
        $this->assertSame(0, $result);
        $this->assertCount(3, $events);
        $this->assertEquals(7, $DB->count_records('enrol'));
        $this->assertEquals(8, $DB->count_records('user_enrolments'));
        $this->assertEquals(1, $DB->count_records('role_assignments'));
        $this->assertInstanceOf('totara_core\event\bulk_enrolments_started', $events[0]);
        $this->assertInstanceOf('core\event\user_enrolment_created', $events[1]);
        $this->assertInstanceOf('totara_core\event\bulk_enrolments_ended', $events[2]);

        $sink->clear();
        $result = enrol_cohort_sync($nulltrace, null);
        $events = $sink->get_events();
        $this->assertSame(0, $result);
        $this->assertCount(0, $events);
        $this->assertEquals(7, $DB->count_records('enrol'));
        $this->assertEquals(8, $DB->count_records('user_enrolments'));
        $this->assertEquals(1, $DB->count_records('role_assignments'));

        // Work around the cheating with timestamps when triggering the updated events,
        // please not it is unlikely this script is going to run repeatedly within 1 second.
        $DB->execute("UPDATE {user_enrolments} SET timemodified = timemodified - 10, timecreated = timecreated - 10");

        $ue = $DB->get_record('user_enrolments', array('userid' => $user1->id, 'enrolid' => $cohortinstance1->id), '*', MUST_EXIST);
        $ue->status = ENROL_USER_SUSPENDED;
        $ue->timecreated = 10;
        $ue->timemodified = 20;
        $DB->update_record('user_enrolments', $ue);

        $this->setCurrentTimeStart();
        $sink->clear();
        $result = enrol_cohort_sync($nulltrace, null);
        $events = $sink->get_events();
        $this->assertSame(0, $result);
        $this->assertCount(3, $events);
        $this->assertEquals(7, $DB->count_records('enrol'));
        $this->assertEquals(8, $DB->count_records('user_enrolments'));
        $this->assertEquals(1, $DB->count_records('role_assignments'));
        $this->assertInstanceOf('totara_core\event\bulk_enrolments_started', $events[0]);
        $this->assertInstanceOf('core\event\user_enrolment_updated', $events[1]);
        $this->assertInstanceOf('totara_core\event\bulk_enrolments_ended', $events[2]);

        $ue = $DB->get_record('user_enrolments', array('userid' => $user1->id, 'enrolid' => $cohortinstance1->id), '*', MUST_EXIST);
        $this->assertEquals(ENROL_USER_ACTIVE, $ue->status);
        $this->assertEquals(10, $ue->timecreated);
        $this->assertTimeCurrent($ue->timemodified);
    }

    public function test_unenrolments() {
        global $CFG, $DB, $USER;
        require_once("$CFG->dirroot/enrol/cohort/locallib.php");
        require_once("$CFG->dirroot/cohort/lib.php");

        $this->resetAfterTest();

        // When events are redirected the observer does not sync the data immediately,
        // here we are testing manual sync via enrol_cohort_sync() function.
        $sink = $this->redirectEvents();
        $nulltrace = new null_progress_trace();

        set_config('enrol_plugins_enabled', 'manual,cohort');

        $cohortplugin = enrol_get_plugin('cohort');
        $manualplugin = enrol_get_plugin('manual');

        $studentrole = $DB->get_record('role', array('shortname' => 'student'));
        $teacherrole = $DB->get_record('role', array('shortname' => 'teacher'));

        $cat1 = $this->getDataGenerator()->create_category();
        $cat2 = $this->getDataGenerator()->create_category();

        $course1 = $this->getDataGenerator()->create_course(array('category' => $cat1->id));
        $course2 = $this->getDataGenerator()->create_course(array('category' => $cat1->id));
        $course3 = $this->getDataGenerator()->create_course(array('category' => $cat2->id));
        $course4 = $this->getDataGenerator()->create_course(array('category' => $cat2->id));
        $maninstance1 = $DB->get_record('enrol', array('courseid' => $course1->id, 'enrol' => 'manual'), '*', MUST_EXIST);
        $maninstance2 = $DB->get_record('enrol', array('courseid' => $course2->id, 'enrol' => 'manual'), '*', MUST_EXIST);
        $maninstance3 = $DB->get_record('enrol', array('courseid' => $course3->id, 'enrol' => 'manual'), '*', MUST_EXIST);
        $maninstance4 = $DB->get_record('enrol', array('courseid' => $course4->id, 'enrol' => 'manual'), '*', MUST_EXIST);

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        $user4 = $this->getDataGenerator()->create_user();

        $cohort1 = $this->getDataGenerator()->create_cohort(array('contextid'=>context_coursecat::instance($cat1->id)->id));
        $cohort2 = $this->getDataGenerator()->create_cohort(array('contextid'=>context_coursecat::instance($cat2->id)->id));
        $cohort3 = $this->getDataGenerator()->create_cohort();
        $cohort4 = $this->getDataGenerator()->create_cohort();

        $this->assertEquals(4, $DB->count_records('enrol'));
        $this->assertEquals(0, $DB->count_records('user_enrolments'));
        $this->assertEquals(0, $DB->count_records('role_assignments'));

        $manualplugin->enrol_user($maninstance1, $user4->id, $studentrole->id);
        $manualplugin->enrol_user($maninstance4, $user4->id, 0);

        $fields = array('name' => 'cohort sync 1', 'status' => ENROL_INSTANCE_ENABLED, 'customint1' => $cohort1->id, 'roleid' => 0, 'customint2' => 0);
        $id = $cohortplugin->add_instance($course1, $fields);
        $cohortinstance1 = $DB->get_record('enrol', array('id' => $id));
        $fields = array('name' => 'cohort sync 2', 'status' => ENROL_INSTANCE_ENABLED, 'customint1' => $cohort2->id, 'roleid' => 0, 'customint2' => 0);
        $id = $cohortplugin->add_instance($course1, $fields);
        $cohortinstance2 = $DB->get_record('enrol', array('id' => $id));
        $fields = array('name' => 'cohort sync 3', 'status' => ENROL_INSTANCE_ENABLED, 'customint1' => $cohort3->id, 'roleid' => 0, 'customint2' => 0);
        $id = $cohortplugin->add_instance($course3, $fields);
        $cohortinstance3 = $DB->get_record('enrol', array('id' => $id));

        cohort_add_member($cohort1->id, $user1->id);
        cohort_add_member($cohort1->id, $user2->id);
        cohort_add_member($cohort1->id, $user3->id);
        cohort_add_member($cohort1->id, $user4->id);
        cohort_add_member($cohort2->id, $user1->id);
        cohort_add_member($cohort3->id, $user1->id);
        cohort_add_member($cohort4->id, $user1->id);

        $sink->clear();
        $result = enrol_cohort_sync($nulltrace, null);
        $events = $sink->get_events();
        $this->assertSame(0, $result);
        $this->assertCount(12, $events);
        $this->assertEquals(7, $DB->count_records('enrol'));
        $this->assertEquals(8, $DB->count_records('user_enrolments'));
        $this->assertEquals(1, $DB->count_records('role_assignments'));

        // Work around the cheating with timestamps when triggering the updated events,
        // this should be fine because it is unlikely the sync is going to run repeatedly within 1 second.
        $DB->execute("UPDATE {user_enrolments} SET timemodified = timemodified - 10, timecreated = timecreated - 10");

        // Test suspending of users.

        $cohortplugin->set_config('unenrolaction', ENROL_EXT_REMOVED_SUSPENDNOROLES);

        $ue = $DB->get_record('user_enrolments', array('userid' => $user1->id, 'enrolid' => $cohortinstance1->id), '*', MUST_EXIST);
        $ue->timecreated = 10;
        $ue->timemodified = 20;
        $DB->update_record('user_enrolments', $ue);

        cohort_remove_member($cohort1->id, $user1->id);
        $cohortinstance3->status = ENROL_INSTANCE_DISABLED;
        $DB->update_record('enrol', $cohortinstance3);

        $this->assertEquals(7, $DB->count_records('enrol'));
        $this->assertEquals(8, $DB->count_records('user_enrolments'));
        $this->assertEquals(1, $DB->count_records('role_assignments'));

        $sink->clear();
        $this->setCurrentTimeStart();
        $result = enrol_cohort_sync($nulltrace, $course1->id);
        $events = $sink->get_events();
        $this->assertSame(0, $result);
        $this->assertCount(3, $events);
        $this->assertEquals(7, $DB->count_records('enrol'));
        $this->assertEquals(8, $DB->count_records('user_enrolments'));
        $this->assertEquals(1, $DB->count_records('role_assignments'));
        $this->assertInstanceOf('totara_core\event\bulk_enrolments_started', $events[0]);
        $this->assertInstanceOf('core\event\user_enrolment_updated', $events[1]);
        $this->assertInstanceOf('totara_core\event\bulk_enrolments_ended', $events[2]);

        $ue = $DB->get_record('user_enrolments', array('userid' => $user1->id, 'enrolid' => $cohortinstance1->id), '*', MUST_EXIST);
        $this->assertEquals(ENROL_USER_SUSPENDED, $ue->status);
        $this->assertEquals(10, $ue->timecreated);
        $this->assertEquals($USER->id, $ue->modifierid);
        $this->assertTimeCurrent($ue->timemodified);
        $this->assertSame($course1->id, $events[0]->courseid);
        $this->assertSame($course1->id, $events[1]->courseid);
        $this->assertEquals($ue->id, $events[1]->objectid);
        $this->assertSame($course1->id, $events[2]->courseid);

        // Work around the cheating with timestamps when triggering the updated events,
        // this should be fine because it is unlikely the sync is going to run repeatedly within 1 second.
        $DB->execute("UPDATE {user_enrolments} SET timemodified = timemodified - 10, timecreated = timecreated - 10");

        $sink->clear();
        $result = enrol_cohort_sync($nulltrace, null);
        $events = $sink->get_events();
        $this->assertSame(0, $result);
        $this->assertCount(0, $events);
        $this->assertEquals(7, $DB->count_records('enrol'));
        $this->assertEquals(8, $DB->count_records('user_enrolments'));
        $this->assertEquals(1, $DB->count_records('role_assignments'));

        $ue = $DB->get_record('user_enrolments', array('userid' => $user1->id, 'enrolid' => $cohortinstance3->id), '*', MUST_EXIST);
        $this->assertEquals($ue->status, ENROL_USER_ACTIVE);

        // Test full unenrol.

        $cohortplugin->set_config('unenrolaction', ENROL_EXT_REMOVED_UNENROL);

        cohort_remove_member($cohort2->id, $user1->id);
        cohort_remove_member($cohort3->id, $user1->id);

        $ue = $DB->get_record('user_enrolments', array('userid' => $user1->id, 'enrolid' => $cohortinstance3->id), '*', MUST_EXIST);

        $sink->clear();
        $result = enrol_cohort_sync($nulltrace, $course3->id);
        $events = $sink->get_events();
        $this->assertSame(0, $result);
        $this->assertCount(3, $events);
        $this->assertEquals(7, $DB->count_records('enrol'));
        $this->assertEquals(7, $DB->count_records('user_enrolments'));
        $this->assertEquals(1, $DB->count_records('role_assignments'));
        $this->assertInstanceOf('totara_core\event\bulk_enrolments_started', $events[0]);
        $this->assertInstanceOf('core\event\user_enrolment_deleted', $events[1]);
        $this->assertInstanceOf('totara_core\event\bulk_enrolments_ended', $events[2]);

        $this->assertSame($course3->id, $events[0]->courseid);
        $this->assertSame($course3->id, $events[1]->courseid);
        $this->assertSame($ue->id, $events[1]->objectid);
        $this->assertSame($course3->id, $events[2]->courseid);
        $this->assertFalse($DB->record_exists('user_enrolments', array('userid' => $user1->id, 'enrolid' => $cohortinstance3->id)));

        $sink->clear();
        $result = enrol_cohort_sync($nulltrace, null);
        $events = $sink->get_events();
        $this->assertSame(0, $result);
        $this->assertCount(6, $events);
        $this->assertEquals(7, $DB->count_records('enrol'));
        $this->assertEquals(5, $DB->count_records('user_enrolments'));
        $this->assertEquals(1, $DB->count_records('role_assignments'));
        $this->assertInstanceOf('totara_core\event\bulk_enrolments_started', $events[0]);
        $this->assertInstanceOf('core\event\user_enrolment_deleted', $events[1]);
        $this->assertInstanceOf('core\event\user_enrolment_deleted', $events[4]);
        $this->assertInstanceOf('totara_core\event\bulk_enrolments_ended', $events[5]);

        $this->assertFalse($DB->record_exists('user_enrolments', array('userid' => $user1->id, 'enrolid' => $cohortinstance1->id)));
        $this->assertFalse($DB->record_exists('user_enrolments', array('userid' => $user1->id, 'enrolid' => $cohortinstance2->id)));
        $this->assertFalse($DB->record_exists('user_enrolments', array('userid' => $user1->id, 'enrolid' => $cohortinstance3->id)));

        $sink->clear();
        $result = enrol_cohort_sync($nulltrace, null);
        $events = $sink->get_events();
        $this->assertSame(0, $result);
        $this->assertCount(0, $events);
        $this->assertEquals(7, $DB->count_records('enrol'));
        $this->assertEquals(5, $DB->count_records('user_enrolments'));
        $this->assertEquals(1, $DB->count_records('role_assignments'));
    }

    public function test_roles() {
        global $CFG, $DB, $USER;
        require_once("$CFG->dirroot/enrol/cohort/locallib.php");
        require_once("$CFG->dirroot/cohort/lib.php");

        $this->resetAfterTest();

        // When events are redirected the observer does not sync the data immediately,
        // here we are testing manual sync via enrol_cohort_sync() function.
        $sink = $this->redirectEvents();
        $nulltrace = new null_progress_trace();

        set_config('enrol_plugins_enabled', 'manual,cohort');

        $cohortplugin = enrol_get_plugin('cohort');
        $manualplugin = enrol_get_plugin('manual');

        $studentrole = $DB->get_record('role', array('shortname' => 'student'));
        $teacherrole = $DB->get_record('role', array('shortname' => 'teacher'));

        $cat1 = $this->getDataGenerator()->create_category();
        $cat2 = $this->getDataGenerator()->create_category();

        $course1 = $this->getDataGenerator()->create_course(array('category' => $cat1->id));
        $course2 = $this->getDataGenerator()->create_course(array('category' => $cat1->id));
        $course3 = $this->getDataGenerator()->create_course(array('category' => $cat2->id));
        $course4 = $this->getDataGenerator()->create_course(array('category' => $cat2->id));
        $maninstance1 = $DB->get_record('enrol', array('courseid' => $course1->id, 'enrol' => 'manual'), '*', MUST_EXIST);
        $maninstance2 = $DB->get_record('enrol', array('courseid' => $course2->id, 'enrol' => 'manual'), '*', MUST_EXIST);
        $maninstance3 = $DB->get_record('enrol', array('courseid' => $course3->id, 'enrol' => 'manual'), '*', MUST_EXIST);
        $maninstance4 = $DB->get_record('enrol', array('courseid' => $course4->id, 'enrol' => 'manual'), '*', MUST_EXIST);
        $context1 = context_course::instance($course1->id);
        $context2 = context_course::instance($course2->id);
        $context3 = context_course::instance($course3->id);
        $context4 = context_course::instance($course4->id);

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        $user4 = $this->getDataGenerator()->create_user();

        $cohort1 = $this->getDataGenerator()->create_cohort(array('contextid'=>context_coursecat::instance($cat1->id)->id));
        $cohort2 = $this->getDataGenerator()->create_cohort(array('contextid'=>context_coursecat::instance($cat2->id)->id));
        $cohort3 = $this->getDataGenerator()->create_cohort();
        $cohort4 = $this->getDataGenerator()->create_cohort();

        $this->assertEquals(4, $DB->count_records('enrol'));
        $this->assertEquals(0, $DB->count_records('user_enrolments'));
        $this->assertEquals(0, $DB->count_records('role_assignments'));

        $manualplugin->enrol_user($maninstance1, $user4->id, $studentrole->id);
        $manualplugin->enrol_user($maninstance4, $user4->id, 0);

        $fields = array('name' => 'cohort sync 1', 'status' => ENROL_INSTANCE_ENABLED, 'customint1' => $cohort1->id, 'roleid' => $studentrole->id, 'customint2' => 0);
        $id = $cohortplugin->add_instance($course1, $fields);
        $cohortinstance1 = $DB->get_record('enrol', array('id' => $id));
        $fields = array('name' => 'cohort sync 2', 'status' => ENROL_INSTANCE_ENABLED, 'customint1' => $cohort2->id, 'roleid' => $teacherrole->id, 'customint2' => 0);
        $id = $cohortplugin->add_instance($course1, $fields);
        $cohortinstance2 = $DB->get_record('enrol', array('id' => $id));
        $fields = array('name' => 'cohort sync 3', 'status' => ENROL_INSTANCE_ENABLED, 'customint1' => $cohort3->id, 'roleid' => 0, 'customint2' => 0);
        $id = $cohortplugin->add_instance($course3, $fields);
        $cohortinstance3 = $DB->get_record('enrol', array('id' => $id));

        cohort_add_member($cohort1->id, $user1->id);
        cohort_add_member($cohort1->id, $user2->id);
        cohort_add_member($cohort1->id, $user3->id);
        cohort_add_member($cohort1->id, $user4->id);
        cohort_add_member($cohort2->id, $user1->id);
        cohort_add_member($cohort3->id, $user1->id);
        cohort_add_member($cohort4->id, $user1->id);

        $this->assertEquals(7, $DB->count_records('enrol'));
        $this->assertEquals(2, $DB->count_records('user_enrolments'));
        $this->assertEquals(1, $DB->count_records('role_assignments'));

        $cohortplugin->set_config('unenrolaction', ENROL_EXT_REMOVED_SUSPENDNOROLES);

        $sink->clear();
        $this->setCurrentTimeStart();
        $result = enrol_cohort_sync($nulltrace, null);
        $events = $sink->get_events();
        $this->assertSame(0, $result);
        $this->assertcount(19, $events);
        $this->assertInstanceOf('totara_core\event\bulk_enrolments_started', $events[0]);
        $this->assertInstanceOf('core\event\user_enrolment_created', $events[1]);
        $this->assertInstanceOf('core\event\role_assigned', $events[17]);
        $this->assertInstanceOf('totara_core\event\bulk_role_assignments_ended', $events[18]);
        $this->assertEquals(7, $DB->count_records('enrol'));
        $this->assertEquals(8, $DB->count_records('user_enrolments'));
        $this->assertEquals(6, $DB->count_records('role_assignments'));

        $this->assertTrue($DB->record_exists('role_assignments',
            array('contextid' => $context1->id, 'itemid' => $cohortinstance1->id, 'component' => 'enrol_cohort',
                'userid' => $user1->id, 'roleid' => $studentrole->id)));
        $this->assertTrue($DB->record_exists('role_assignments',
            array('contextid' => $context1->id, 'itemid' => $cohortinstance1->id, 'component' => 'enrol_cohort',
                'userid' => $user2->id, 'roleid' => $studentrole->id)));
        $this->assertTrue($DB->record_exists('role_assignments',
            array('contextid' => $context1->id, 'itemid' => $cohortinstance1->id, 'component' => 'enrol_cohort',
                'userid' => $user3->id, 'roleid' => $studentrole->id)));
        $this->assertTrue($DB->record_exists('role_assignments',
            array('contextid' => $context1->id, 'itemid' => $cohortinstance1->id, 'component' => 'enrol_cohort',
                'userid' => $user4->id, 'roleid' => $studentrole->id)));
        $this->assertTrue($DB->record_exists('role_assignments',
            array('contextid' => $context1->id, 'itemid' => $cohortinstance2->id, 'component' => 'enrol_cohort',
                'userid' => $user1->id, 'roleid' => $teacherrole->id)));
        $this->assertTrue($DB->record_exists('role_assignments',
            array('contextid' => $context1->id, 'itemid' => 0, 'component' => '',
                'userid' => $user4->id, 'roleid' => $studentrole->id)));

        $ra = $DB->get_record('role_assignments',
            array('contextid' => $context1->id, 'itemid' => $cohortinstance1->id, 'component' => 'enrol_cohort',
                'userid' => $user1->id, 'roleid' => $studentrole->id));
        $this->assertTimeCurrent($ra->timemodified);
        $this->assertEquals($USER->id, $ra->modifierid);

        cohort_remove_member($cohort1->id, $user1->id);
        cohort_add_member($cohort3->id, $user1->id);

        $sink->clear();
        $result = enrol_cohort_sync($nulltrace, $course1->id);
        $events = $sink->get_events();
        $this->assertSame(0, $result);
        $this->assertcount(6, $events);
        $this->assertInstanceOf('totara_core\event\bulk_enrolments_started', $events[0]);
        $this->assertInstanceOf('core\event\user_enrolment_updated', $events[1]);
        $this->assertInstanceOf('totara_core\event\bulk_enrolments_ended', $events[2]);
        $this->assertInstanceOf('totara_core\event\bulk_role_assignments_started', $events[3]);
        $this->assertInstanceOf('core\event\role_unassigned', $events[4]);
        $this->assertInstanceOf('totara_core\event\bulk_role_assignments_ended', $events[5]);
        $this->assertEquals(7, $DB->count_records('enrol'));
        $this->assertEquals(8, $DB->count_records('user_enrolments'));
        $this->assertEquals(5, $DB->count_records('role_assignments'));
        $this->assertFalse($DB->record_exists('role_assignments',
            array('contextid' => $context1->id, 'itemid' => $cohortinstance1->id, 'component' => 'enrol_cohort',
                'userid' => $user1->id, 'roleid' => $studentrole->id)));
        $this->assertSame($ra->id, $events[4]->other['id']);

        $cohortplugin->set_config('unenrolaction', ENROL_EXT_REMOVED_UNENROL);

        cohort_remove_member($cohort2->id, $user1->id);

        $sink->clear();
        $result = enrol_cohort_sync($nulltrace, null);
        $events = $sink->get_events();
        $this->assertSame(0, $result);
        $this->assertcount(7, $events);
        $this->assertEquals(7, $DB->count_records('enrol'));
        $this->assertEquals(6, $DB->count_records('user_enrolments'));
        $this->assertEquals(4, $DB->count_records('role_assignments'));
        $this->assertFalse($DB->record_exists('role_assignments',
            array('contextid' => $context1->id, 'itemid' => $cohortinstance1->id, 'component' => 'enrol_cohort',
                'userid' => $user1->id, 'roleid' => $studentrole->id)));
        $this->assertTrue($DB->record_exists('role_assignments',
            array('contextid' => $context1->id, 'itemid' => $cohortinstance1->id, 'component' => 'enrol_cohort',
                'userid' => $user2->id, 'roleid' => $studentrole->id)));
        $this->assertTrue($DB->record_exists('role_assignments',
            array('contextid' => $context1->id, 'itemid' => $cohortinstance1->id, 'component' => 'enrol_cohort',
                'userid' => $user3->id, 'roleid' => $studentrole->id)));
        $this->assertTrue($DB->record_exists('role_assignments',
            array('contextid' => $context1->id, 'itemid' => $cohortinstance1->id, 'component' => 'enrol_cohort',
                'userid' => $user4->id, 'roleid' => $studentrole->id)));
        $this->assertFalse($DB->record_exists('role_assignments',
            array('contextid' => $context1->id, 'itemid' => $cohortinstance2->id, 'component' => 'enrol_cohort',
                'userid' => $user1->id, 'roleid' => $teacherrole->id)));
        $this->assertTrue($DB->record_exists('role_assignments',
            array('contextid' => $context1->id, 'itemid' => 0, 'component' => '',
                'userid' => $user4->id, 'roleid' => $studentrole->id)));

        $cohortplugin->delete_instance($cohortinstance1);
        $cohortplugin->delete_instance($cohortinstance2);
        $cohortplugin->delete_instance($cohortinstance3);

        $sink->clear();
        $result = enrol_cohort_sync($nulltrace, null);
        $this->assertSame(0, $result);
        $this->assertEquals(4, $DB->count_records('enrol'));
        $this->assertEquals(2, $DB->count_records('user_enrolments'));
        $this->assertEquals(1, $DB->count_records('role_assignments'));

        $sink->close();
    }

    public function test_groups() {
        global $CFG, $DB, $USER;
        require_once("$CFG->dirroot/enrol/cohort/locallib.php");
        require_once("$CFG->dirroot/cohort/lib.php");

        $this->resetAfterTest();

        // When events are redirected the observer does not sync the data immediately,
        // here we are testing manual sync via enrol_cohort_sync() function.
        $sink = $this->redirectEvents();
        $nulltrace = new null_progress_trace();

        set_config('enrol_plugins_enabled', 'manual,cohort');

        $cohortplugin = enrol_get_plugin('cohort');
        $manualplugin = enrol_get_plugin('manual');

        $studentrole = $DB->get_record('role', array('shortname' => 'student'));

        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();
        $maninstance1 = $DB->get_record('enrol', array('courseid' => $course1->id, 'enrol' => 'manual'), '*', MUST_EXIST);
        $maninstance2 = $DB->get_record('enrol', array('courseid' => $course2->id, 'enrol' => 'manual'), '*', MUST_EXIST);

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        $user4 = $this->getDataGenerator()->create_user();

        $cohort1 = $this->getDataGenerator()->create_cohort();
        $cohort2 = $this->getDataGenerator()->create_cohort();

        $group1 = $this->getDataGenerator()->create_group(array('courseid' => $course1->id));
        $group2 = $this->getDataGenerator()->create_group(array('courseid' => $course2->id));
        $group3 = $this->getDataGenerator()->create_group(array('courseid' => $course1->id));

        $manualplugin->enrol_user($maninstance1, $user1->id, $studentrole->id);
        groups_add_member($group3->id, $user1->id);

        $fields = array('name' => 'cohort sync 1', 'status' => ENROL_INSTANCE_ENABLED, 'customint1' => $cohort1->id, 'roleid' => 0, 'customint2' => $group1->id);
        $id = $cohortplugin->add_instance($course1, $fields);
        $cohortinstance1 = $DB->get_record('enrol', array('id' => $id));
        $fields = array('name' => 'cohort sync 2', 'status' => ENROL_INSTANCE_ENABLED, 'customint1' => $cohort2->id, 'roleid' => 0, 'customint2' => $group2->id);
        $id = $cohortplugin->add_instance($course2, $fields);
        $cohortinstance2 = $DB->get_record('enrol', array('id' => $id));

        cohort_add_member($cohort1->id, $user1->id);
        cohort_add_member($cohort1->id, $user2->id);
        cohort_add_member($cohort1->id, $user3->id);
        cohort_add_member($cohort1->id, $user4->id);
        cohort_add_member($cohort2->id, $user1->id);

        $this->assertEquals(4, $DB->count_records('enrol'));
        $this->assertEquals(1, $DB->count_records('user_enrolments'));
        $this->assertEquals(1, $DB->count_records('role_assignments'));
        $this->assertEquals(1, $DB->count_records('groups_members'));

        $cohortplugin->set_config('unenrolaction', ENROL_EXT_REMOVED_SUSPENDNOROLES);

        $sink->clear();
        $result = enrol_cohort_sync($nulltrace, null);
        $events = $sink->get_events();
        $this->assertSame(0, $result);
        $this->assertcount(14, $events);
        $this->assertInstanceOf('totara_core\event\bulk_enrolments_started', $events[0]);
        $this->assertInstanceOf('core\event\user_enrolment_created', $events[1]);
        $this->assertInstanceOf('core\event\group_member_added', $events[12]);
        $this->assertEquals(4, $DB->count_records('enrol'));
        $this->assertEquals(6, $DB->count_records('user_enrolments'));
        $this->assertEquals(1, $DB->count_records('role_assignments'));
        $this->assertEquals(6, $DB->count_records('groups_members'));

        cohort_remove_member($cohort1->id, $user1->id);
        cohort_remove_member($cohort2->id, $user1->id);

        $sink->clear();
        $result = enrol_cohort_sync($nulltrace, $course1->id);
        $events = $sink->get_events();
        $this->assertSame(0, $result);
        $this->assertcount(3, $events);
        $this->assertInstanceOf('totara_core\event\bulk_enrolments_started', $events[0]);
        $this->assertInstanceOf('core\event\user_enrolment_updated', $events[1]);
        $this->assertInstanceOf('totara_core\event\bulk_enrolments_ended', $events[2]);
        $this->assertEquals(4, $DB->count_records('enrol'));
        $this->assertEquals(6, $DB->count_records('user_enrolments'));
        $this->assertEquals(1, $DB->count_records('role_assignments'));
        $this->assertEquals(6, $DB->count_records('groups_members'));

        $cohortplugin->set_config('unenrolaction', ENROL_EXT_REMOVED_UNENROL);

        $sink->clear();
        $result = enrol_cohort_sync($nulltrace, $course1->id);
        $events = $sink->get_events();
        $this->assertSame(0, $result);
        $this->assertcount(4, $events);
        $this->assertInstanceOf('totara_core\event\bulk_enrolments_started', $events[0]);
        $this->assertInstanceOf('core\event\group_member_removed', $events[1]);
        $this->assertInstanceOf('core\event\user_enrolment_deleted', $events[2]);
        $this->assertInstanceOf('totara_core\event\bulk_enrolments_ended', $events[3]);
        $this->assertEquals(4, $DB->count_records('enrol'));
        $this->assertEquals(5, $DB->count_records('user_enrolments'));
        $this->assertEquals(1, $DB->count_records('role_assignments'));
        $this->assertEquals(5, $DB->count_records('groups_members'));

        $cohortplugin->delete_instance($cohortinstance1);
        $cohortplugin->delete_instance($cohortinstance2);

        $sink->clear();
        $result = enrol_cohort_sync($nulltrace, null);
        $this->assertSame(0, $result);
        $this->assertEquals(2, $DB->count_records('enrol'));
        $this->assertEquals(1, $DB->count_records('user_enrolments'));
        $this->assertEquals(1, $DB->count_records('role_assignments'));
        $this->assertEquals(1, $DB->count_records('groups_members'));

        $sink->close();
    }
}
