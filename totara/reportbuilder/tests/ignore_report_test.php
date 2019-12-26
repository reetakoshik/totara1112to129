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
 * @package totara_reportbuilder
 */

defined('MOODLE_INTERNAL') || die();

/**
 * @group totara_reportbuilder
 */
class totara_reportbuilder_ignore_report_testcase extends advanced_testcase {
    use totara_reportbuilder\phpunit\report_testing;

    public function test_ignored_reports() {
        global $DB;

        $this->resetAfterTest();

        // Try as admin first.

        $this->setAdminUser();

        $rid = $this->create_report('user', 'Test user report 1');
        $config = (new rb_config())->set_nocache(true);
        $report1 = reportbuilder::create($rid, $config);
        $this->add_column($report1, 'user', 'id', null, null, null, 0);
        $this->add_column($report1, 'user', 'username', null, null, null, 0);
        reportbuilder::reset_caches();

        $reports = reportbuilder::get_user_permitted_reports();
        $this->assertCount(1, $reports);
        $this->assertArrayHasKey($report1->_id, $reports);

        // Try as manager user.

        $user = $this->getDataGenerator()->create_user();
        $managerrole = $DB->get_record('role', array('shortname' =>'manager'));
        role_assign($managerrole->id, $user->id, context_system::instance()->id);
        $this->setUser($user);

        $rid = $this->create_report('user', 'Test user report 2');
        $report2 = reportbuilder::create($rid, $config);
        $this->add_column($report2, 'user', 'id', null, null, null, 0);
        $this->add_column($report2, 'user', 'username', null, null, null, 0);
        reportbuilder::reset_caches();

        $reports = reportbuilder::get_user_permitted_reports();
        $this->assertCount(2, $reports);

        $rid = $this->create_report('opensesame', 'Ignored 2');
        $report3 = reportbuilder::create($rid, $config);
        $this->add_column($report3, 'opensesame', 'title', null, null, null, 0);
        $this->add_column($report3, 'opensesame', 'visible', null, null, null, 0);
        reportbuilder::reset_caches();

        $reports = reportbuilder::get_user_permitted_reports();
        $this->assertCount(2, $reports);

        reportbuilder::reset_caches();
        set_config('tenantkey', 'abc', 'repository_opensesame');

        $reports = reportbuilder::get_user_permitted_reports();
        $this->assertCount(3, $reports);

        reportbuilder::reset_caches();
        unset_config('tenantkey', 'repository_opensesame');

        // Test other methods.

        $this->setAdminUser();
        reportbuilder::reset_caches();

        $userreports = reportbuilder::get_user_generated_reports();
        $this->assertCount(2, $userreports);

        $embededreports = reportbuilder::get_embedded_reports();

        reportbuilder::reset_caches();
        set_config('tenantkey', 'abc', 'repository_opensesame');

        $userreports2 = reportbuilder::get_user_generated_reports();
        $this->assertCount(3, $userreports2);

        $embededreports2 = reportbuilder::get_embedded_reports();
        $this->assertCount(count($embededreports) + 1, $embededreports2);
    }
}
