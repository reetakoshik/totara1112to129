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
 * @package totara_cohort
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Test cohort generator.
 */
class totara_cohort_generator_testcase extends advanced_testcase {
    public function test_disable_enrol_plugin_enrolment() {
        $this->resetAfterTest();

        /** @var totara_cohort_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_cohort');

        $this->assertTrue(enrol_is_enabled('cohort'));
        $generator->disable_enrol_plugin();
        $this->assertFalse(enrol_is_enabled('cohort'));
    }

    public function test_enable_enrol_plugin_enrolment() {
        $this->resetAfterTest();

        /** @var totara_cohort_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_cohort');

        $generator->disable_enrol_plugin();
        $this->assertFalse(enrol_is_enabled('cohort'));
        $generator->enable_enrol_plugin();
        $this->assertTrue(enrol_is_enabled('cohort'));
    }

    public function test_create_cohort_enrolment() {
        global $DB, $CFG;
        $this->resetAfterTest();

        /** @var totara_cohort_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_cohort');

        $course = $this->getDataGenerator()->create_course();
        $cohort1 = $this->getDataGenerator()->create_cohort();
        $cohort2 = $this->getDataGenerator()->create_cohort();

        $enrol = $generator->create_cohort_enrolment(array('cohortid' => $cohort1->id, 'courseid' => $course->id));
        $this->assertSame('cohort', $enrol->enrol);
        $this->assertEquals($course->id, $course->id);
        $this->assertEquals($cohort1->id, $enrol->customint1);
        $this->assertEquals($CFG->learnerroleid, $enrol->roleid);

        $teacherrole = $DB->get_record('role', array('shortname' => 'teacher'));
        $enrol = $generator->create_cohort_enrolment(array('cohortid' => $cohort2->id, 'courseid' => $course->id, 'roleid' => $teacherrole->id));
        $this->assertSame('cohort', $enrol->enrol);
        $this->assertEquals($course->id, $course->id);
        $this->assertEquals($cohort2->id, $enrol->customint1);
        $this->assertEquals($teacherrole->id, $enrol->roleid);

        try {
            $generator->create_cohort_enrolment(array('courseid' => $course->id));
            $this->fail('Exception expected!');
        } catch (moodle_exception $e) {
            $this->assertInstanceOf('coding_exception', $e);
            $this->assertEquals('Coding error detected, it must be fixed by a programmer: cohortid is required in totara_cohort_generator::create_cohort_enrolment() $record', $e->getMessage());
        }

        try {
            $generator->create_cohort_enrolment(array('cohortid' => $cohort2->id));
            $this->fail('Exception expected!');
        } catch (moodle_exception $e) {
            $this->assertInstanceOf('coding_exception', $e);
            $this->assertEquals('Coding error detected, it must be fixed by a programmer: courseid is required in totara_cohort_generator::create_cohort_enrolment() $record', $e->getMessage());
        }
    }

    public function test_create_cohort_member() {
        $this->resetAfterTest();

        /** @var totara_cohort_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_cohort');

        $cohort1 = $this->getDataGenerator()->create_cohort();
        $cohort2 = $this->getDataGenerator()->create_cohort();

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $this->assertFalse(cohort_is_member($cohort1->id, $user1->id));
        $this->assertFalse(cohort_is_member($cohort2->id, $user1->id));
        $this->assertFalse(cohort_is_member($cohort1->id, $user2->id));
        $generator->create_cohort_member(array('userid' => $user1->id, 'cohortid' => $cohort1->id));
        $this->assertTrue(cohort_is_member($cohort1->id, $user1->id));
        $this->assertFalse(cohort_is_member($cohort2->id, $user1->id));
        $this->assertFalse(cohort_is_member($cohort1->id, $user2->id));

        try {
            $generator->create_cohort_member(array('userid' => $user2->id));
            $this->fail('Exception expected!');
        } catch (moodle_exception $e) {
            $this->assertInstanceOf('coding_exception', $e);
            $this->assertEquals('Coding error detected, it must be fixed by a programmer: cohortid is required in totara_cohort_generator::create_cohort_member() $record', $e->getMessage());
        }

        try {
            $generator->create_cohort_member(array('cohortid' => $cohort2->id));
            $this->fail('Exception expected!');
        } catch (moodle_exception $e) {
            $this->assertInstanceOf('coding_exception', $e);
            $this->assertEquals('Coding error detected, it must be fixed by a programmer: userid is required in totara_cohort_generator::create_cohort_member() $record', $e->getMessage());
        }
    }

    public function test_create_cohort() {
        global $DB;
        $this->resetAfterTest();

        /** @var totara_cohort_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_cohort');

        $this->assertCount(0, $DB->get_records('cohort'));

        $cohort = $generator->create_cohort();
        $this->assertSame('AUD0001', $cohort->idnumber);
        $this->assertSame('tool_generator_AUD0001', $cohort->name);
        $this->assertEquals(context_system::instance()->id, $cohort->contextid);
        $this->assertEquals(cohort::TYPE_STATIC, $cohort->cohorttype);
        $this->assertSame('Audience create by tool_generator', $cohort->description);
        $this->assertEquals(FORMAT_HTML, $cohort->descriptionformat);
    }
}
