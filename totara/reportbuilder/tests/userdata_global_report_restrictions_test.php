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
 * @author Valerii Kuznetsov <valerii.kuznetsov@totaralearning.com>
 * @package totara_reportbuilder
 */

use totara_reportbuilder\userdata\global_report_restrictions;
use totara_userdata\userdata\item;
use totara_userdata\userdata\target_user;

defined('MOODLE_INTERNAL') || die();

global $CFG;

/**
 * This class tests purging and exporting global report restriction userdata item.
 *
 * @group totara_reportbuilder
 * @group totara_userdata
 */
class totara_reportbuilder_userdata_global_report_restrictions_test extends advanced_testcase {
    /**
     * Test that data are purged correctly
     */
    public function test_purge() {
        global $DB;
        $this->resetAfterTest(true);

        $generator = $this->getDataGenerator();
        /** @var \totara_reportbuilder_generator $rbgen */
        $rbgen = $generator->get_plugin_generator('totara_reportbuilder');
        /** @var \totara_hierarchy_generator $hierarchygen */
        $hierarchygen = $generator->get_plugin_generator('totara_hierarchy');

        $user = $generator->create_user();
        $otheruser = $generator->create_user();

        $cohort = $generator->create_cohort();
        $orgfw = $hierarchygen->create_org_frame([]);
        $org = $hierarchygen->create_org(['frameworkid' => $orgfw->id]);
        $posfw = $hierarchygen->create_pos_frame([]);
        $pos = $hierarchygen->create_pos(['frameworkid' => $posfw->id]);

        $grr = $rbgen->create_global_restriction();

        // User that will see restricted reports.
        $usercohort = $rbgen->assign_global_restriction_user(['prefix' => 'cohort', 'restrictionid' => $grr->id, 'itemid' => $cohort->id]);
        $userorg = $rbgen->assign_global_restriction_user(['prefix' => 'org', 'restrictionid' => $grr->id, 'itemid' => $org->id]);
        $userpos = $rbgen->assign_global_restriction_user(['prefix' => 'pos', 'restrictionid' => $grr->id, 'itemid' => $pos->id]);
        $rbgen->assign_global_restriction_user(['prefix' => 'user', 'restrictionid' => $grr->id, 'itemid' => $user->id]);
        $userother = $rbgen->assign_global_restriction_user(['prefix' => 'user', 'restrictionid' => $grr->id, 'itemid' => $otheruser->id]);

        // User that will appear in restricted reports.
        $recordcohort = $rbgen->assign_global_restriction_record(['prefix' => 'cohort', 'restrictionid' => $grr->id, 'itemid' => $cohort->id]);
        $recordorg = $rbgen->assign_global_restriction_record(['prefix' => 'org', 'restrictionid' => $grr->id, 'itemid' => $org->id]);
        $recordpos = $rbgen->assign_global_restriction_record(['prefix' => 'pos', 'restrictionid' => $grr->id, 'itemid' => $cohort->id]);
        $rbgen->assign_global_restriction_record(['prefix' => 'user', 'restrictionid' => $grr->id, 'itemid' => $user->id]);
        $recordother = $rbgen->assign_global_restriction_record(['prefix' => 'user', 'restrictionid' => $grr->id, 'itemid' => $otheruser->id]);

        // Run purge.
        $targetuser = new target_user($user);
        global_report_restrictions::execute_purge($targetuser, context_system::instance());

        // Assert results. Viva random id generator.
        $this->assertEquals($usercohort->id, $DB->get_record('reportbuilder_grp_cohort_user', [], '*', MUST_EXIST)->id);
        $this->assertEquals($userorg->id, $DB->get_record('reportbuilder_grp_org_user', [], '*', MUST_EXIST)->id);
        $this->assertEquals($userpos->id, $DB->get_record('reportbuilder_grp_pos_user', [], '*', MUST_EXIST)->id);
        $this->assertEquals($userother->id, $DB->get_record('reportbuilder_grp_user_user', [], '*', MUST_EXIST)->id);

        $this->assertEquals($recordcohort->id, $DB->get_record('reportbuilder_grp_cohort_record', [], '*', MUST_EXIST)->id);
        $this->assertEquals($recordorg->id, $DB->get_record('reportbuilder_grp_org_record', [], '*', MUST_EXIST)->id);
        $this->assertEquals($recordpos->id, $DB->get_record('reportbuilder_grp_pos_record', [], '*', MUST_EXIST)->id);
        $this->assertEquals($recordother->id, $DB->get_record('reportbuilder_grp_user_record', [], '*', MUST_EXIST)->id);
    }
}