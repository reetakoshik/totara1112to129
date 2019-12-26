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
 * @package totara_reportbuilder
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once("$CFG->dirroot/totara/reportbuilder/lib.php");

/**
 * @group totara_reportbuilder
 */
class totara_reportbuilder_role_access_testcase extends advanced_testcase {
    use totara_reportbuilder\phpunit\report_testing;

    public function test_get_all_access_plugins() {
        $result = reportbuilder::get_all_access_plugins();
        $this->assertIsArray($result);
        foreach ($result as $name => $instance) {
            $this->assertStringEndsWith('_access', $name);
            $this->assertInstanceOf('totara_reportbuilder\rb\access\base', $instance);
        }
    }

    public function test_new_report_defaults() {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        $managerrole = $DB->get_record('role', array('shortname' => 'manager'), '*', MUST_EXIST);

        $reportid = $this->create_report('user', 'report 1');

        $rb = reportbuilder::create($reportid);

        $enabled = reportbuilder::get_setting($reportid, 'role_access', 'enable');
        $this->assertSame('1', $enabled);

        $activeroles = reportbuilder::get_setting($reportid, 'role_access', 'activeroles');
        $this->assertSame((string)$managerrole->id, $activeroles);

        $context = reportbuilder::get_setting($reportid, 'role_access', 'context');
        $this->assertSame('site', $context);
    }

    public function test_get_reports_plugins_access() {
        global $DB;
        $this->resetAfterTest();

        $syscontext = context_system::instance();

        $admin = get_admin();
        $manager = $this->getDataGenerator()->create_user();
        $creator = $this->getDataGenerator()->create_user();
        $student = $this->getDataGenerator()->create_user();
        $nobody = $this->getDataGenerator()->create_user();
        $guest = guest_user();

        $managerrole = $DB->get_record('role', array('shortname' => 'manager'), '*', MUST_EXIST);
        $creatorrole = $DB->get_record('role', array('shortname' => 'coursecreator'), '*', MUST_EXIST);
        $studentrole = $DB->get_record('role', array('shortname' => 'student'), '*', MUST_EXIST);
        $userrole = $DB->get_record('role', array('shortname' => 'user'), '*', MUST_EXIST);
        $guestrole = $DB->get_record('role', array('shortname' => 'guest'), '*', MUST_EXIST);

        $course = $this->getDataGenerator()->create_course();
        $this->getDataGenerator()->role_assign($managerrole->id, $manager->id, $syscontext->id);
        $this->getDataGenerator()->role_assign($creatorrole->id, $creator->id, $syscontext->id);
        $this->getDataGenerator()->enrol_user($student->id, $course->id, 'student');

        $reportid1 = $this->create_report('user', 'report 1');
        $this->configure_role_access($reportid1, 'site', array($managerrole->id));

        $reportid2 = $this->create_report('user', 'report 2');
        $this->configure_role_access($reportid2, 'any', array($studentrole->id));

        $reportid3 = $this->create_report('user', 'report 3');
        $this->configure_role_access($reportid3, 'site', array($studentrole->id));

        $reportid4 = $this->create_report('user', 'report 4');
        $this->configure_role_access($reportid4, 'site', array($managerrole->id, $studentrole->id, $creatorrole->id));

        $reportid5 = $this->create_report('user', 'report 5');
        $this->configure_role_access($reportid5, 'any', array($managerrole->id, $studentrole->id, $creatorrole->id));

        $reportid6 = $this->create_report('user', 'report 6');
        $this->configure_role_access($reportid6, 'site', array());

        $reportid7 = $this->create_report('user', 'report 7');
        $this->configure_role_access($reportid7, 'site', array($userrole->id));

        $reportid8 = $this->create_report('user', 'report 7');
        $this->configure_role_access($reportid8, 'site', array($guestrole->id));

        // Now test results for each user.

        $result = reportbuilder::get_reports_plugins_access($admin->id);
        ksort($result);
        $expected = array(
            $reportid1 => array('role_access' => 1),
            $reportid2 => array('role_access' => 1),
            $reportid3 => array('role_access' => 1),
            $reportid4 => array('role_access' => 1),
            $reportid5 => array('role_access' => 1),
            $reportid6 => array('role_access' => 1),
            $reportid7 => array('role_access' => 1),
            $reportid8 => array('role_access' => 1),
        );
        $this->assertSame($expected, $result);

        $result = reportbuilder::get_reports_plugins_access($manager->id);
        ksort($result);
        $expected = array(
            $reportid1 => array('role_access' => 1),
            $reportid2 => array('role_access' => 0),
            $reportid3 => array('role_access' => 0),
            $reportid4 => array('role_access' => 1),
            $reportid5 => array('role_access' => 1),
            $reportid6 => array('role_access' => 0),
            $reportid7 => array('role_access' => 1),
            $reportid8 => array('role_access' => 0),
        );
        $this->assertSame($expected, $result);

        $result = reportbuilder::get_reports_plugins_access($creator->id);
        ksort($result);
        $expected = array(
            $reportid1 => array('role_access' => 0),
            $reportid2 => array('role_access' => 0),
            $reportid3 => array('role_access' => 0),
            $reportid4 => array('role_access' => 1),
            $reportid5 => array('role_access' => 1),
            $reportid6 => array('role_access' => 0),
            $reportid7 => array('role_access' => 1),
            $reportid8 => array('role_access' => 0),
        );
        $this->assertSame($expected, $result);

        $result = reportbuilder::get_reports_plugins_access($student->id);
        ksort($result);
        $expected = array(
            $reportid1 => array('role_access' => 0),
            $reportid2 => array('role_access' => 1),
            $reportid3 => array('role_access' => 0),
            $reportid4 => array('role_access' => 0),
            $reportid5 => array('role_access' => 1),
            $reportid6 => array('role_access' => 0),
            $reportid7 => array('role_access' => 1),
            $reportid8 => array('role_access' => 0),
        );
        $this->assertSame($expected, $result);

        $result = reportbuilder::get_reports_plugins_access($nobody->id);
        ksort($result);
        $expected = array(
            $reportid1 => array('role_access' => 0),
            $reportid2 => array('role_access' => 0),
            $reportid3 => array('role_access' => 0),
            $reportid4 => array('role_access' => 0),
            $reportid5 => array('role_access' => 0),
            $reportid6 => array('role_access' => 0),
            $reportid7 => array('role_access' => 1),
            $reportid8 => array('role_access' => 0),
        );
        $this->assertSame($expected, $result);

        $result = reportbuilder::get_reports_plugins_access($guest->id);
        ksort($result);
        $expected = array(
            $reportid1 => array('role_access' => 0),
            $reportid2 => array('role_access' => 0),
            $reportid3 => array('role_access' => 0),
            $reportid4 => array('role_access' => 0),
            $reportid5 => array('role_access' => 0),
            $reportid6 => array('role_access' => 0),
            $reportid7 => array('role_access' => 0),
            $reportid8 => array('role_access' => 1),
        );
        $this->assertSame($expected, $result);

        $result = reportbuilder::get_reports_plugins_access(0);
        ksort($result);
        $expected = array(
            $reportid1 => array('role_access' => 0),
            $reportid2 => array('role_access' => 0),
            $reportid3 => array('role_access' => 0),
            $reportid4 => array('role_access' => 0),
            $reportid5 => array('role_access' => 0),
            $reportid6 => array('role_access' => 0),
            $reportid7 => array('role_access' => 0),
            $reportid8 => array('role_access' => 1),
        );
        $this->assertSame($expected, $result);
    }

    public function test_get_permitted_reports() {
        global $DB;
        $this->resetAfterTest();

        $syscontext = context_system::instance();

        $admin = get_admin();
        $manager = $this->getDataGenerator()->create_user();
        $creator = $this->getDataGenerator()->create_user();
        $student = $this->getDataGenerator()->create_user();
        $nobody = $this->getDataGenerator()->create_user();
        $guest = guest_user();

        $managerrole = $DB->get_record('role', array('shortname' => 'manager'), '*', MUST_EXIST);
        $creatorrole = $DB->get_record('role', array('shortname' => 'coursecreator'), '*', MUST_EXIST);
        $studentrole = $DB->get_record('role', array('shortname' => 'student'), '*', MUST_EXIST);
        $userrole = $DB->get_record('role', array('shortname' => 'user'), '*', MUST_EXIST);
        $guestrole = $DB->get_record('role', array('shortname' => 'guest'), '*', MUST_EXIST);

        $course = $this->getDataGenerator()->create_course();
        $this->getDataGenerator()->role_assign($managerrole->id, $manager->id, $syscontext->id);
        $this->getDataGenerator()->role_assign($creatorrole->id, $creator->id, $syscontext->id);
        $this->getDataGenerator()->enrol_user($student->id, $course->id, 'student');

        $reportid1 = $this->create_report('user', 'report 1');
        $this->configure_role_access($reportid1, 'site', array($managerrole->id));

        $reportid2 = $this->create_report('user', 'report 2');
        $this->configure_role_access($reportid2, 'any', array($studentrole->id));

        $reportid3 = $this->create_report('user', 'report 3');
        $this->configure_role_access($reportid3, 'site', array($studentrole->id));

        $reportid4 = $this->create_report('user', 'report 4');
        $this->configure_role_access($reportid4, 'site', array($managerrole->id, $studentrole->id, $creatorrole->id));

        $reportid5 = $this->create_report('user', 'report 5');
        $this->configure_role_access($reportid5, 'any', array($managerrole->id, $studentrole->id, $creatorrole->id));

        $reportid6 = $this->create_report('user', 'report 6');
        $this->configure_role_access($reportid6, 'site', array());

        $reportid7 = $this->create_report('user', 'report 7');
        $this->configure_role_access($reportid7, 'site', array($userrole->id));

        $reportid8 = $this->create_report('user', 'report 7');
        $this->configure_role_access($reportid8, 'site', array($guestrole->id));

        // Now test results for each user.

        $result = reportbuilder::get_permitted_reports($admin->id);
        ksort($result);
        $result = array_keys($result);
        $expected = array($reportid1, $reportid2, $reportid3, $reportid4, $reportid5, $reportid6,$reportid7, $reportid8);
        $this->assertSame($expected, $result);

        $result = reportbuilder::get_permitted_reports($manager->id);
        ksort($result);
        $result = array_keys($result);
        $expected = array($reportid1, $reportid4, $reportid5, $reportid7);
        $this->assertSame($expected, $result);

        $result = reportbuilder::get_permitted_reports($creator->id);
        ksort($result);
        $result = array_keys($result);
        $expected = array($reportid4, $reportid5, $reportid7);
        $this->assertSame($expected, $result);

        $result = reportbuilder::get_permitted_reports($student->id);
        ksort($result);
        $result = array_keys($result);
        $expected = array($reportid2, $reportid5, $reportid7);
        $this->assertSame($expected, $result);

        $result = reportbuilder::get_permitted_reports($nobody->id);
        ksort($result);
        $result = array_keys($result);
        $expected = array($reportid7);
        $this->assertSame($expected, $result);

        $result = reportbuilder::get_permitted_reports($guest->id);
        ksort($result);
        $result = array_keys($result);
        $expected = array($reportid8);
        $this->assertSame($expected, $result);

        $result = reportbuilder::get_permitted_reports(0);
        ksort($result);
        $result = array_keys($result);
        $expected = array($reportid8);
        $this->assertSame($expected, $result);
    }

    public function test_has_reports() {
        global $DB;
        $this->resetAfterTest();

        $syscontext = context_system::instance();

        $admin = get_admin();
        $manager = $this->getDataGenerator()->create_user();
        $creator = $this->getDataGenerator()->create_user();
        $student = $this->getDataGenerator()->create_user();
        $nobody = $this->getDataGenerator()->create_user();
        $guest = guest_user();

        $managerrole = $DB->get_record('role', ['shortname' => 'manager'], '*', MUST_EXIST);
        $creatorrole = $DB->get_record('role', ['shortname' => 'coursecreator'], '*', MUST_EXIST);
        $studentrole = $DB->get_record('role', ['shortname' => 'student'], '*', MUST_EXIST);
        $userrole = $DB->get_record('role', ['shortname' => 'user'], '*', MUST_EXIST);
        $guestrole = $DB->get_record('role', ['shortname' => 'guest'], '*', MUST_EXIST);

        $course = $this->getDataGenerator()->create_course();
        $this->getDataGenerator()->role_assign($managerrole->id, $manager->id, $syscontext->id);
        $this->getDataGenerator()->role_assign($creatorrole->id, $creator->id, $syscontext->id);
        $this->getDataGenerator()->enrol_user($student->id, $course->id, 'student');

        $reportid1 = $this->create_report('user', 'report 1');
        $this->configure_role_access($reportid1, 'site', [$managerrole->id]);

        $reportid2 = $this->create_report('user', 'report 2');
        $this->configure_role_access($reportid2, 'any', [$studentrole->id]);

        $reportid3 = $this->create_report('user', 'report 3');
        $this->configure_role_access($reportid3, 'site', [$studentrole->id]);

        $reportid4 = $this->create_report('user', 'report 4');
        $this->configure_role_access($reportid4, 'site', [$managerrole->id, $studentrole->id, $creatorrole->id]);

        $reportid5 = $this->create_report('user', 'report 5');
        $this->configure_role_access($reportid5, 'any', [$managerrole->id, $studentrole->id, $creatorrole->id]);

        $reportid6 = $this->create_report('user', 'report 6');
        $this->configure_role_access($reportid6, 'site', []);

        $reportid7 = $this->create_report('user', 'report 7');
        $this->configure_role_access($reportid7, 'site', [$userrole->id]);

        $reportid8 = $this->create_report('user', 'report 8');
        $this->configure_role_access($reportid8, 'site', [$guestrole->id]);

        // Test results for each user.
        $this->assertTrue(reportbuilder::has_reports($admin->id));
        $this->assertTrue(reportbuilder::has_reports($manager->id));
        $this->assertTrue(reportbuilder::has_reports($creator->id));
        $this->assertTrue(reportbuilder::has_reports($student->id));
        $this->assertTrue(reportbuilder::has_reports($nobody->id));
        $this->assertTrue(reportbuilder::has_reports($guest->id));
        $this->assertTrue(reportbuilder::has_reports(0));

        // Update access for some reports.
        $this->configure_role_access($reportid8, 'site', []);
        $this->configure_role_access($reportid7, 'site', [$managerrole->id]);
        $this->assertTrue(reportbuilder::has_reports($admin->id));
        $this->assertTrue(reportbuilder::has_reports($manager->id));
        $this->assertTrue(reportbuilder::has_reports($creator->id));
        $this->assertTrue(reportbuilder::has_reports($student->id));
        $this->assertFalse(reportbuilder::has_reports($guest->id));
        $this->assertFalse(reportbuilder::has_reports($nobody->id));
        $this->assertFalse(reportbuilder::has_reports(0));
    }

    /**
     * Set role access restrictions - this code mimics \totara_reportbuilder\rb\access\role::form_process().
     * @param int $reportid
     * @param string $context 'any' or 'site'
     * @param int[] $activeroles array of role ids
     */
    protected function configure_role_access($reportid, $context, array $activeroles) {
        global $DB;

        reportbuilder::update_setting($reportid, 'role_access', 'context', $context);
        reportbuilder::update_setting($reportid, 'role_access', 'activeroles', implode('|', $activeroles));
        $DB->set_field('report_builder', 'accessmode', REPORT_BUILDER_ACCESS_MODE_ANY, array('id' => $reportid));
    }
}
