<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2017 onwards Totara Learning Solutions LTD
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
 * @author Murali Nair <murali.nair@totaralearning.com>
 * @package totara
 * @subpackage reportbuilder
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

global $CFG;
require_once($CFG->dirroot . '/totara/reportbuilder/lib.php');

/**
 * @group totara_reportbuilder
 */
final class totara_reportbuilder_rb_manage_scheduled_reports_embedded_testcase
extends advanced_testcase {
    /**
     * Report name.
     */
    private $report_name = 'manage_scheduled_reports';

    /**
     * Required capability needed to view report.
     */
    private $capability = 'totara/reportbuilder:managescheduledreports';

    /**
     * Required role needed to view report; this contains the capability needed
     * to view the report.
     */
    private $role_id = null;

    /**
     * Context in which role operates.
     */
    private $context = null;


    /**
     * Prepare mock data for testing.
     */
    protected function setUp() {
        parent::setup();
        $this->setAdminUser();
        $this->resetAfterTest(true);
        $this->preventResetByRollback();

        $role_name = 'test manage scheduled report role';
        $this->context = context_system::instance();
        $this->role_id = \create_role($role_name, $role_name, $role_name);
        \assign_capability(
            $this->capability, CAP_ALLOW, $this->role_id, $this->context
        );
    }

    /**
     * Prepare mock data for testing.
     */
    protected function tearDown() {
        $this->context = null;
        $this->role_id = null;

        parent::tearDown();
    }

    public function test_is_capable() {
        $generator = $this->getDataGenerator();
        $user_wo_capability = $generator->create_user()->id;
        $user_with_capability = $generator->create_user()->id;
        \role_assign($this->role_id, $user_with_capability, $this->context);

        $report = reportbuilder::create_embedded($this->report_name);
        $embedded_report = $report->embedobj;

        $this->assertFalse(
            $embedded_report->is_capable($user_wo_capability, $report),
            sprintf("user without '%s' can access report", $this->capability)
        );

        $this->assertTrue(
            $embedded_report->is_capable($user_with_capability, $report),
            sprintf("user with '%s' cannot access report", $this->capability)
        );

        $this->assertTrue(
            $embedded_report->is_capable(\get_admin()->id, $report),
            'admin cannot access report'
         );
    }
}
