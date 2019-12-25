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
 * @author David Curry <david.curry@totaralms.com>
 * @package totara_hierarchy
 */

defined('MOODLE_INTERNAL') || die();

use totara_userdata\userdata\export;
use totara_userdata\userdata\item;
use totara_userdata\userdata\target_user;
use hierarchy_goal\userdata\company_export_visible;
use hierarchy_goal\userdata\company_export_hidden;
use hierarchy_goal\userdata\company_purge;

/**
 * @group totara_userdata
 * @group totara_hierarchy
 * Class userdata_goal_company_test
*/
class totara_hierarchy_userdata_goal_company_testcase extends advanced_testcase {

    /**
     * Setup the test data for company goals userdata.
     *
     * @return \stdClass - An object containing data created by this function.
     */
    private function make_user_goals() {
        global $DB;

        $retdata = new \stdClass();

        $hierarchygen = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');
        $user1 = $this->getDataGenerator()->create_user(); // Testing user.
        $retdata->user1 = $user1;
        $user2 = $this->getDataGenerator()->create_user(); // Control user.
        $retdata->user2 = $user2;
        $user3 = $this->getDataGenerator()->create_user(); // Deleted user.
        delete_user($user3);
        $retdata->user3 = $DB->get_record('user', ['id' => $user3->id]);

        $valuedata = [
            1 => ['name' => 'Assigned', 'proficient' => 0, 'sortorder' => 1, 'default' => 1],
            2 => ['name' => 'Begining', 'proficient' => 0, 'sortorder' => 2, 'default' => 0],
            3 => ['name' => 'Middling', 'proficient' => 0, 'sortorder' => 3, 'default' => 0],
            4 => ['name' => 'Resolvin', 'proficient' => 0, 'sortorder' => 4, 'default' => 0],
            5 => ['name' => 'Complete', 'proficient' => 1, 'sortorder' => 5, 'default' => 0]
        ];
        $retdata->custscale = $hierarchygen->create_scale('goal', ['name' => 'goalscale1'], $valuedata);
        $retdata->custvals = $DB->get_records('goal_scale_values', ['scaleid' => $retdata->custscale->id], 'sortorder'); // Get their real ids.

        $frame1 = $hierarchygen->create_goal_frame(['name' => 'frame1', 'scaleid' => $retdata->custscale->id]); // This returns the whole object.
        $scaleid = $DB->get_field('goal_scale_assignments', 'scaleid', ['frameworkid' => $frame1->id]);

        // Create goal one and assign both users.
        $cgoal1 = $hierarchygen->create_goal(['fullname' => 'cgoal1', 'frameworkid' => $frame1->id]);
        $retdata->cgoal1 = $cgoal1;
        $hierarchygen->goal_assign_individuals($cgoal1->id, [$user1->id, $user2->id]);

        // Update the scale value for user1 a few times to create history records.
        $record = $DB->get_record('goal_record', ['goalid' => $cgoal1->id, 'userid' => $user1->id]);
        foreach ($retdata->custvals as $scalevalue) {
            // Skip the default, they are already assigned to that.
            if ($scalevalue->sortorder == 1) {
                continue;
            }

            $hierarchygen->update_company_goal_user_scale_value($user1->id, $cgoal1->id, $scalevalue->id);
        }

        //Create goal two and assign no one.
        $cgoal2 = $hierarchygen->create_goal(['fullname' => 'cgoal2', 'frameworkid' => $frame1->id]);
        $retdata->cgoal2 = $cgoal2;

        // Create goal three in a different framework and assign user 1.
        $frame2 = $hierarchygen->create_goal_frame(['name' => 'frame2']);
        $cgoal3 = $hierarchygen->create_goal(['fullname' => 'cgoal3', 'frameworkid' => $frame2->id]);
        $hierarchygen->goal_assign_individuals($cgoal3->id, [$user1->id, $user3->id]);
        $retdata->cgoal3 = $cgoal3;

        return $retdata;
    }

    /**
     * Test the count function for the company goal userdata items.
     */
    public function test_company_goal_count() {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();
        $data = $this->make_user_goals();
        $syscontext = \context_system::instance();

        $this->assertTrue(company_purge::is_countable());
        $this->assertTrue(company_export_visible::is_countable());
        $this->assertTrue(company_export_hidden::is_countable());

        $this->assertTrue(has_capability('totara/hierarchy:viewowncompanygoal', $syscontext, $data->user1->id));
        $this->assertTrue(has_capability('totara/hierarchy:viewowncompanygoal', $syscontext, $data->user2->id));

        $targetuser1 = new target_user($data->user1);
        $count = company_export_visible::execute_count($targetuser1, $syscontext);
        $this->assertEquals(2, $count);
        $count = company_export_hidden::execute_count($targetuser1, $syscontext);
        $this->assertEquals(0, $count);

        $targetuser2 = new target_user($data->user2);
        $count = company_export_visible::execute_count($targetuser2, $syscontext);
        $this->assertEquals(1, $count);
        $count = company_export_hidden::execute_count($targetuser2, $syscontext);
        $this->assertEquals(0, $count);

        // Remove the viewowncompanygoal capability.
        $userrole = $DB->get_record('role', ['shortname' => 'user']);
        unassign_capability('totara/hierarchy:viewowncompanygoal', $userrole->id, $syscontext);
        $this->assertFalse(has_capability('totara/hierarchy:viewowncompanygoal', $syscontext, $data->user1->id));
        $this->assertFalse(has_capability('totara/hierarchy:viewowncompanygoal', $syscontext, $data->user2->id));

        // Recount user 1.
        $targetuser1 = new target_user($data->user1);
        $count = company_export_visible::execute_count($targetuser1, $syscontext);
        $this->assertEquals(0, $count);
        $count = company_export_hidden::execute_count($targetuser1, $syscontext);
        $this->assertEquals(2, $count);

        // recount user 2.
        $targetuser2 = new target_user($data->user2);
        $count = company_export_visible::execute_count($targetuser2, $syscontext);
        $this->assertEquals(0, $count);
        $count = company_export_hidden::execute_count($targetuser2, $syscontext);
        $this->assertEquals(1, $count);
    }

    /**
     * Test the purge function for the company goal userdata items.
     */
    public function test_company_goal_purge_system() {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();
        $data = $this->make_user_goals();
        $syscontext = \context_system::instance();

        $allstatus = [target_user::STATUS_ACTIVE, target_user::STATUS_SUSPENDED, target_user::STATUS_DELETED];
        foreach ($allstatus as $status) {
            $this->assertTrue(company_purge::is_purgeable($status));
            $this->assertFalse(company_export_visible::is_purgeable($status));
            $this->assertFalse(company_export_hidden::is_purgeable($status));
        }

        $targetuser1 = new target_user($data->user1);
        $records = $DB->get_records('goal_record', ['userid' => $data->user1->id]);
        $count = company_purge::execute_count($targetuser1, $syscontext);
        $this->assertEquals(2, $count);
        $this->assertEquals(8, $DB->count_records('goal_item_history'));
        $result = company_purge::execute_purge($targetuser1, $syscontext);
        $count = company_purge::execute_count($targetuser1, $syscontext);
        $this->assertEquals(0, $count);
        $u1goals = $DB->count_records('goal_user_assignment', array('userid' => $data->user1->id));
        $this->assertEquals(0, $u1goals);
        $this->assertEquals(2, $DB->count_records('goal_item_history')); // There should still be a record for users 2&3.
        foreach ($records as $record) {
            $this->assertEquals(0, $DB->count_records('goal_item_history', ['itemid' => $record->id]));
        }

        // Make sure that purging user 1 has not affected user 2.
        $targetuser2 = new target_user($data->user2);
        $count = company_purge::execute_count($targetuser2, $syscontext);
        $this->assertEquals(1, $count);
        $u2goals = $DB->count_records('goal_user_assignment', array('userid' => $data->user2->id));
        $this->assertEquals(1, $u2goals);

        $records = $DB->get_records('goal_record', ['userid' => $data->user2->id]);
        $this->assertEquals(1, count($records));
        foreach ($records as $record) {
            $this->assertEquals(1, $DB->count_records('goal_item_history', ['itemid' => $record->id]));
        }

        $records = $DB->get_records('goal_record', ['userid' => $data->user3->id]);
        $this->assertEquals(1, count($records));
        foreach ($records as $record) {
            $this->assertEquals(1, $DB->count_records('goal_item_history', ['itemid' => $record->id]));
        }

        // Repurge user 1 to make sure it's fine to run multiple times
        $result = company_purge::execute_purge($targetuser1, $syscontext);
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $result);

        // Create user 4 with no data and purge them to make sure that's fine
        $user4 = $this->getDataGenerator()->create_user();
        $targetuser4 = new target_user($user4);
        $result = company_purge::execute_purge($targetuser4, $syscontext);
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $result);
        $this->assertEquals(0, company_purge::execute_count($targetuser4, $syscontext));

        // Purge the deleted user to make sure that the purge runs properly on them.
        $targetuser3 = new target_user($data->user3);
        $u3goals = $DB->get_records('goal_user_assignment', ['userid' => $data->user3->id]);
        $this->assertEquals(1, count($u3goals));

        $result = company_purge::execute_purge($targetuser3, $syscontext);
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $result);

        $u3goals = $DB->get_records('goal_user_assignment', ['userid' => $data->user3->id]);
        $this->assertEquals(0, count($u3goals));
    }

    /**
     * Test the export function for the company goal userdata items.
     */
    public function test_company_goal_export_system () {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();
        $data = $this->make_user_goals();
        $syscontext = \context_system::instance();

        $this->assertFalse(company_purge::is_exportable());
        $this->assertTrue(company_export_visible::is_exportable());
        $this->assertTrue(company_export_hidden::is_exportable());

        $u1cgoals = $DB->get_records('goal_user_assignment', ['userid' => $data->user1->id]);
        $this->assertEquals(2, count($u1cgoals));

        // Run the export on the deleted user.
        // Note: Deleted users do not have the capability so all items are considered hidden.
        $targetuser3 = new target_user($data->user3);
        $result = company_export_hidden::execute_export($targetuser3, $syscontext);
        $this->assertEquals(1, count($result->data['active']));
        $result = company_export_visible::execute_export($targetuser3, $syscontext);
        $this->assertEquals(0, count($result->data));

        // Run the export on the test user.
        $targetuser1 = new target_user($data->user1);
        $count = company_export_visible::execute_count($targetuser1, $syscontext);
        $this->assertEquals(2, $count);
        $count = company_export_hidden::execute_count($targetuser1, $syscontext);
        $this->assertEquals(0, $count);
        $result = company_export_hidden::execute_export($targetuser1, $syscontext);
        $this->assertEquals(0, count($result->data));
        $result = company_export_visible::execute_export($targetuser1, $syscontext);

        // Check that each company goal assignment is included in the result.
        $this->assertEquals(0, count($result->data['deleted']));
        $this->assertEquals(count($u1cgoals), count($result->data['active']));
        foreach ($result->data['active'] as $item) {
            $found = false;

            // Check the result is one of the expected goal assignments.
            foreach ($u1cgoals as $goal) {
                if ($item->id == $goal->id) {
                    $found = true;
                    break;
                }
            }

            $this->assertTrue($found);

            // Check that the users records are included.
            $this->assertEquals(1, count($item->scaledata));
            $scaledata = array_pop($item->scaledata);

            // Check the historical data is included.
            if ($scaledata->goalid == $data->cgoal1->id) {
                $this->assertEquals(5, count($scaledata->scalehistory));
            } else {
                $this->assertEquals(1, count($scaledata->scalehistory));
            }
        }

        $targetuser2 = new target_user($data->user2);
        $count = company_export_visible::execute_count($targetuser2, $syscontext);
        $this->assertEquals(1, $count);

        // Remove the viewowncompanygoal capability.
        $userrole = $DB->get_record('role', ['shortname' => 'user']);
        unassign_capability('totara/hierarchy:viewowncompanygoal', $userrole->id, $syscontext);
        $this->assertFalse(has_capability('totara/hierarchy:viewowncompanygoal', $syscontext, $data->user1->id));
        $this->assertFalse(has_capability('totara/hierarchy:viewowncompanygoal', $syscontext, $data->user2->id));

        // retest to make sure the visible and hidden exports have switched.
        $count = company_export_visible::execute_count($targetuser1, $syscontext);
        $this->assertEquals(0, $count);
        $count = company_export_hidden::execute_count($targetuser1, $syscontext);
        $this->assertEquals(2, $count);
        $result = company_export_visible::execute_export($targetuser1, $syscontext);
        $this->assertEquals(0, count($result->data));
        $result = company_export_hidden::execute_export($targetuser1, $syscontext);

        // Check that each company goal assignment is included in the result.
        $this->assertEquals(0, count($result->data['deleted']));
        $this->assertEquals(count($u1cgoals), count($result->data['active']));
        foreach ($result->data['active'] as $item) {
            $found = false;

            // Check the result is one of the expected goal assignments.
            foreach ($u1cgoals as $goal) {
                if ($item->id == $goal->id) {
                    $found = true;
                    break;
                }
            }

            $this->assertTrue($found);

            // Check that the users records are included.
            $this->assertEquals(1, count($item->scaledata));
            $scaledata = array_pop($item->scaledata);

            // Check the historical data is included.
            if ($scaledata->goalid == $data->cgoal1->id) {
                $this->assertEquals(5, count($scaledata->scalehistory));
            } else {
                $this->assertEquals(1, count($scaledata->scalehistory));
            }
        }

        // Finally delete the goal user assignments check any historical data is still there.
        $records = $DB->get_records('goal_record', ['userid' => $data->user1->id]);
        foreach ($records as $record) {
            $record->deleted = 1;
            $DB->update_record('goal_record', $record);
        }
        $DB->delete_records('goal_user_assignment', ['userid' => $data->user1->id]);

        $result = company_export_hidden::execute_export($targetuser1, $syscontext);

        $this->assertEquals(0, count($result->data['active']));
        $this->assertEquals(count($u1cgoals), count($result->data['deleted']));
        foreach ($result->data['deleted'] as $item) {
            // Check the historical data is included.
            if ($item->goalid == $data->cgoal1->id) {
                $this->assertEquals(5, count($item->scalehistory));
            } else {
                $this->assertEquals(1, count($item->scalehistory));
            }
        }
    }
}
