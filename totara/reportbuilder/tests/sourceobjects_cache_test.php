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
 * @author Valerii Kuznetsov <valerii.kuznetsov@totaralearning.com>
 * @package totara_reportbuilder
 */

defined('MOODLE_INTERNAL') || die();

/**
 * @group totara_reportbuilder
 */
class totara_reportbuilder_sourceobjects_cache_testcase extends advanced_testcase {

    use totara_reportbuilder\phpunit\report_testing;

    public function test_cache_not_interfere_for_different_users() {
        global $CFG;
        require_once("$CFG->dirroot/totara/reportbuilder/lib.php");

        $this->resetAfterTest();
        $this->setAdminUser(); // We need permissions to access all reports.

        // Create two users and report.
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $reportid = $this->create_report('user', 'Test report');

        // Give user two spec. capability required by report source.
        $roleid = $this->getDataGenerator()->create_role();
        assign_capability('moodle/cohort:view', CAP_ALLOW, $roleid, context_system::instance());
        $this->getDataGenerator()->role_assign($roleid, $user2->id);
        accesslib_clear_all_caches_for_unit_testing();

        $has_usercohortids_filter_option = function (array $filteroptions) {
            foreach ($filteroptions as $filteroption) {
                if ($filteroption->type == 'user' && $filteroption->value == 'usercohortids') {
                    return true;
                }
            }
            return false;
        };

        // Get instance for user one - confirm no column definition.
        $this->setUser($user1);
        $report1 = reportbuilder::create($reportid);
        $this->assertFalse($has_usercohortids_filter_option($report1->src->filteroptions));

        // Get instance for user two - confirm there column definition.
        $this->setUser($user2);
        $report2 = reportbuilder::create($reportid);
        $this->assertTrue($has_usercohortids_filter_option($report2->src->filteroptions));
    }
}
