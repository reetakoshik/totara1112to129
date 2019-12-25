<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2016 onwards Totara Learning Solutions LTD
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
 * Make sure the changes in cohorts did not break enrol_cohort.
 */
class totara_cohort_enrol_testcase extends advanced_testcase {
    public function test_role_updates() {
        global $DB;

        $this->resetAfterTest();

        $trace = new null_progress_trace();

        $cohortplugin = enrol_get_plugin('cohort');

        $studentrole = $DB->get_record('role', array('shortname' => 'student'));
        $teacherrole = $DB->get_record('role', array('shortname' => 'editingteacher'));

        $course1 = $this->getDataGenerator()->create_course();
        $context1 = context_course::instance($course1->id);
        $course2 = $this->getDataGenerator()->create_course();

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $cohort1 = $this->getDataGenerator()->create_cohort();

        cohort_add_member($cohort1->id, $user1->id);
        cohort_add_member($cohort1->id, $user2->id);

        $cohortplugin->set_config('unenrolaction', ENROL_EXT_REMOVED_SUSPENDNOROLES);

        $this->assertEquals(0, $DB->count_records('role_assignments', array()));
        $this->assertEquals(0, $DB->count_records('user_enrolments', array()));

        $id = $cohortplugin->add_instance($course1, array('customint1' => $cohort1->id, 'roleid' => $studentrole->id));
        // user enrolments is now part of an adhoc task.
        phpunit_util::run_all_adhoc_tasks();

        $this->assertEquals(2, $DB->count_records('role_assignments', array()));
        $this->assertEquals(2, $DB->count_records('user_enrolments', array()));

        $cohortinstance1 = $DB->get_record('enrol', array('id' => $id));

        enrol_cohort_sync($trace);
        $this->assertEquals(2, $DB->count_records('role_assignments', array()));
        $this->assertEquals(2, $DB->count_records('user_enrolments', array()));
        $this->assertTrue($DB->record_exists('role_assignments', array('contextid' => $context1->id, 'userid' => $user1->id, 'roleid' => $studentrole->id, 'component' => 'enrol_cohort' , 'itemid' => $cohortinstance1->id)));
        $this->assertTrue($DB->record_exists('role_assignments', array('contextid' => $context1->id, 'userid' => $user2->id, 'roleid' => $studentrole->id, 'component' => 'enrol_cohort' , 'itemid' => $cohortinstance1->id)));

        $DB->delete_records('role_assignments', array());
        enrol_cohort_sync($trace);
        $this->assertEquals(2, $DB->count_records('role_assignments', array()));
        $this->assertEquals(2, $DB->count_records('user_enrolments', array()));
        $this->assertTrue($DB->record_exists('role_assignments', array('contextid' => $context1->id, 'userid' => $user1->id, 'roleid' => $studentrole->id, 'component' => 'enrol_cohort' , 'itemid' => $cohortinstance1->id)));
        $this->assertTrue($DB->record_exists('role_assignments', array('contextid' => $context1->id, 'userid' => $user2->id, 'roleid' => $studentrole->id, 'component' => 'enrol_cohort' , 'itemid' => $cohortinstance1->id)));

        $DB->set_field('role_assignments', 'roleid', $teacherrole->id, array('component' => 'enrol_chort'));
        enrol_cohort_sync($trace);
        $this->assertEquals(2, $DB->count_records('role_assignments', array()));
        $this->assertEquals(2, $DB->count_records('user_enrolments', array()));
        $this->assertTrue($DB->record_exists('role_assignments', array('contextid' => $context1->id, 'userid' => $user1->id, 'roleid' => $studentrole->id, 'component' => 'enrol_cohort' , 'itemid' => $cohortinstance1->id)));
        $this->assertTrue($DB->record_exists('role_assignments', array('contextid' => $context1->id, 'userid' => $user2->id, 'roleid' => $studentrole->id, 'component' => 'enrol_cohort' , 'itemid' => $cohortinstance1->id)));

        $cohortinstance1->status = ENROL_INSTANCE_DISABLED;
        $DB->update_record('enrol', $cohortinstance1);
        $DB->delete_records('role_assignments', array());
        enrol_cohort_sync($trace);
        $this->assertEquals(0, $DB->count_records('role_assignments', array()));
        $this->assertEquals(2, $DB->count_records('user_enrolments', array()));

        $cohortinstance1->status = ENROL_INSTANCE_ENABLED;
        $DB->update_record('enrol', $cohortinstance1);
        $DB->delete_records('role_assignments', array());
        enrol_cohort_sync($trace);
        $this->assertEquals(2, $DB->count_records('role_assignments', array()));
        $this->assertEquals(2, $DB->count_records('user_enrolments', array()));

        cohort_remove_member($cohort1->id, $user1->id);
        $this->assertEquals(1, $DB->count_records('role_assignments', array()));
        $this->assertEquals(2, $DB->count_records('user_enrolments', array()));
        enrol_cohort_sync($trace);
        $this->assertEquals(1, $DB->count_records('role_assignments', array()));
        $this->assertEquals(2, $DB->count_records('user_enrolments', array()));

        $DB->delete_records('role_assignments', array());
        enrol_cohort_sync($trace);
        $this->assertEquals(1, $DB->count_records('role_assignments', array()));
        $this->assertEquals(2, $DB->count_records('user_enrolments', array()));
        $this->assertTrue($DB->record_exists('role_assignments', array('contextid' => $context1->id, 'userid' => $user2->id, 'roleid' => $studentrole->id, 'component' => 'enrol_cohort' , 'itemid' => $cohortinstance1->id)));
    }
}
