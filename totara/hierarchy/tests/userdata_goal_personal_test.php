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
use hierarchy_goal\userdata\personal_export_visible;
use hierarchy_goal\userdata\personal_export_hidden;
use hierarchy_goal\userdata\personal_purge;

/**
 * @group totara_userdata
 * @group totara_hierarchy
 * Class userdata_goal_personal_test
 */
class totara_hierarchy_userdata_goal_personal_testcase extends advanced_testcase {

    /**
     * Setup the test data for personal goals userdata.
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
            1 => ['name' => 'created', 'proficient' => 0, 'sortorder' => 1, 'default' => 1],
            2 => ['name' => 'Started', 'proficient' => 0, 'sortorder' => 2, 'default' => 0],
            3 => ['name' => 'Middled', 'proficient' => 0, 'sortorder' => 3, 'default' => 0],
            4 => ['name' => 'Almosts', 'proficient' => 0, 'sortorder' => 4, 'default' => 0],
            5 => ['name' => 'Finishd', 'proficient' => 1, 'sortorder' => 5, 'default' => 0]
        ];
        $scale1 = $hierarchygen->create_scale('goal', ['name' => 'goalscale1'], $valuedata);

        $frame1 = $hierarchygen->create_goal_frame('frame1'); // This returns the whole object.
        $ctype1 = $hierarchygen->create_goal_type(['idnumber' => 'ctype1']); // This returns the id, consistency!
        $ctype1 = $DB->get_record('goal_type', ['id' => $ctype1]); // Get the whole object for reasons.
        $cfield = $hierarchygen->create_hierarchy_type_checkbox(['hierarchy' => 'goal', 'typeidnumber' => $ctype1->idnumber, 'value' => 1]);
        $pgoal1 = $hierarchygen->create_goal(['fullname' => 'pgoal1', 'frameworkid' => $frame1->id, 'typeid' => $ctype1->id]);

        $hierarchygen->goal_assign_individuals($pgoal1->id, [$user1->id, $user2->id]);

        $retdata->pgoal1 = $pgoal1;

        // Create a personal goal type and add some fields to it.
        $ptype1 = $hierarchygen->create_personal_goal_type(['idnumber' => 'ptype1']);
        $hierarchygen->create_personal_goal_type_generic_menu(['typeidnumber' => $ptype1->idnumber, 'value' => 2345]);
        $hierarchygen->create_personal_goal_type_menu(['typeidnumber' => $ptype1->idnumber, 'value' => 2345]);
        $hierarchygen->create_personal_goal_type_text(['typeidnumber' => $ptype1->idnumber, 'value' => 'Enter a value']);
        $hierarchygen->create_personal_goal_type_datetime(['typeidnumber' => $ptype1->idnumber, 'value' => 0]);
        $hierarchygen->create_personal_goal_type_checkbox(['typeidnumber' => $ptype1->idnumber, 'value' => 1]);

        $cfdatename = 'cf_datetime' . $ptype1->id;
        $cfcheckname = 'cf_checkbox' . $ptype1->id;
        $cftextname = 'cf_text' . $ptype1->id;

        // Create a goal for the first user.
        $data1 = ['fullname' => 'pgoal1', 'frameworkid' => $frame1->id, 'typeid' => $ptype1->id, $cfdatename => 1234567890, $cfcheckname => 1, $cftextname => 'abc123'];
        $pgoal1 = $hierarchygen->create_personal_goal($user1->id, $data1);
        $retdata->pgoal1 = $pgoal1;

        // Create a control goal for the second user.
        $data2 = ['fullname' => 'pgoal2', 'frameworkid' => $frame1->id, 'typeid' => $ptype1->id, $cfdatename => 1231231230, $cfcheckname => 0, $cftextname => 'xyz321'];
        $pgoal2 = $hierarchygen->create_personal_goal($user2->id, $data2);
        $retdata->pgoal2 = $pgoal2;

        // Create a personal goal type and add some fields to it.
        $frame2 = $hierarchygen->create_goal_frame('frame2'); // This returns the whole object.
        $ptype2 = $hierarchygen->create_personal_goal_type(['idnumber' => 'ptype2']);
        $hierarchygen->create_personal_goal_type_generic_menu(['typeidnumber' => $ptype2->idnumber, 'value' => 2345]);
        $hierarchygen->create_personal_goal_type_menu(['typeidnumber' => $ptype2->idnumber, 'value' => 2345]);
        $hierarchygen->create_personal_goal_type_text(['typeidnumber' => $ptype2->idnumber, 'value' => 'Enter a value']);
        $hierarchygen->create_personal_goal_type_datetime(['typeidnumber' => $ptype2->idnumber, 'value' => 0]);
        $hierarchygen->create_personal_goal_type_checkbox(['typeidnumber' => $ptype2->idnumber, 'value' => 1]);

        // Update the custom field names for the second type.
        $cfdatename = 'cf_datetime' . $ptype2->id;
        $cfcheckname = 'cf_checkbox' . $ptype2->id;
        $cftextname = 'cf_text' . $ptype2->id;

        // Create a second goal for the first user, to make sure this works across all goal frameworks.
        $data3 = ['fullname' => 'pgoal3', 'frameworkid' => $frame2->id, 'typeid' => $ptype2->id, $cfdatename => 1234567890, $cfcheckname => 1, $cftextname => 'abc123'];
        $pgoal3 = $hierarchygen->create_personal_goal($user1->id, $data3);
        $retdata->pgoal3 = $pgoal3;

        // Create something for the deleted user.
        $data4 = ['fullname' => 'pgoal3', 'frameworkid' => $frame2->id, 'typeid' => $ptype2->id, $cfdatename => 1325467890, $cfcheckname => 1, $cftextname => '321cba'];
        $pgoal4 = $hierarchygen->create_personal_goal($user3->id, $data4);
        $retdata->pgoal4 = $pgoal4;

        return $retdata;
    }

    /**
     * Test the count function for the personal goal userdata items.
     */
    public function test_personal_goal_count() {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();
        $data = $this->make_user_goals();
        $syscontext = \context_system::instance();

        $this->assertTrue(personal_purge::is_countable());
        $this->assertTrue(personal_export_visible::is_countable());
        $this->assertTrue(personal_export_hidden::is_countable());

        $this->assertTrue(has_capability('totara/hierarchy:viewownpersonalgoal', $syscontext, $data->user1->id));
        $this->assertTrue(has_capability('totara/hierarchy:viewownpersonalgoal', $syscontext, $data->user2->id));

        $targetuser1 = new target_user($data->user1);
        $count = personal_export_visible::execute_count($targetuser1, $syscontext);
        $this->assertEquals(2, $count);
        $count = personal_export_hidden::execute_count($targetuser1, $syscontext);
        $this->assertEquals(0, $count);

        $targetuser2 = new target_user($data->user2);
        $count = personal_export_visible::execute_count($targetuser2, $syscontext);
        $this->assertEquals(1, $count);
        $count = personal_export_hidden::execute_count($targetuser2, $syscontext);
        $this->assertEquals(0, $count);

        // Remove the viewownpersonalgoal capability.
        $userrole = $DB->get_record('role', ['shortname' => 'user']);
        unassign_capability('totara/hierarchy:viewownpersonalgoal', $userrole->id, $syscontext);
        $this->assertFalse(has_capability('totara/hierarchy:viewownpersonalgoal', $syscontext, $data->user1->id));
        $this->assertFalse(has_capability('totara/hierarchy:viewownpersonalgoal', $syscontext, $data->user2->id));

        // Recount user 1.
        $count = personal_export_visible::execute_count($targetuser1, $syscontext);
        $this->assertEquals(0, $count);
        $count = personal_export_hidden::execute_count($targetuser1, $syscontext);
        $this->assertEquals(2, $count);

        // recount user 2.
        $count = personal_export_visible::execute_count($targetuser2, $syscontext);
        $this->assertEquals(0, $count);
        $count = personal_export_hidden::execute_count($targetuser2, $syscontext);
        $this->assertEquals(1, $count);
    }

    /**
     * Test the purge function for the personal goal userdata items.
     */
    public function test_personal_goal_purge_system() {
        global $DB, $CFG;

        require_once($CFG->dirroot . '/totara/hierarchy/prefix/goal/lib.php');

        $this->resetAfterTest(true);
        $this->setAdminUser();
        $data = $this->make_user_goals();
        $syscontext = \context_system::instance();

        $allstatus = [target_user::STATUS_ACTIVE, target_user::STATUS_SUSPENDED, target_user::STATUS_DELETED];
        foreach ($allstatus as $status) {
            $this->assertTrue(personal_purge::is_purgeable($status));
            $this->assertFalse(personal_export_visible::is_purgeable($status));
            $this->assertFalse(personal_export_hidden::is_purgeable($status));
        }

        // Get the personal goals before purging.
        $goals = $DB->get_records('goal_personal', ['userid' => $data->user1->id]);

        $this->assertTrue(has_capability('totara/hierarchy:viewownpersonalgoal', $syscontext, $data->user1->id));
        $this->assertTrue(has_capability('totara/hierarchy:viewownpersonalgoal', $syscontext, $data->user2->id));

        $targetuser1 = new target_user($data->user1);
        $this->assertEquals(2, personal_purge::execute_count($targetuser1, $syscontext));
        $result = personal_purge::execute_purge($targetuser1, $syscontext);
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $result);
        $this->assertEquals(0, personal_purge::execute_count($targetuser1, $syscontext));

        // Check the data is really gone from the database rather than rely on count.
        $this->assertEquals(0, $DB->count_records('goal_personal', ['userid' => $data->user1->id]));
        foreach ($goals as $goal) {
            $this->assertEquals(0, $DB->count_records('goal_user_info_data', ['goal_userid' => $goal->id]));
            $this->assertEquals(0, $DB->count_records('goal_item_history', ['scope' => \goal::SCOPE_PERSONAL, 'itemid' => $goal->id]));
        }

        // Double check that user 2s data has not been touched.
        $targetuser2 = new target_user($data->user2);
        $this->assertEquals(1, personal_purge::execute_count($targetuser2, $syscontext));

        $pgoals2 = $DB->get_records('goal_personal', ['userid' => $data->user2->id]);
        $this->assertEquals(1, count($pgoals2));

        $pgoal2 = array_pop($pgoals2);
        $pgoal2data = $DB->get_records('goal_user_info_data', ['goal_userid' => $pgoal2->id]);
        $this->assertEquals(3, count($pgoal2data));

        // Repurge user 1 to make sure it's fine to run multiple times
        $result = personal_purge::execute_purge($targetuser1, $syscontext);
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $result);
        $this->assertEquals(0, personal_purge::execute_count($targetuser1, $syscontext));

        // Create user 4 with no data and purge them to make sure that's fine
        $user4 = $this->getDataGenerator()->create_user();
        $targetuser4 = new target_user($user4);
        $result = personal_purge::execute_purge($targetuser4, $syscontext);
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $result);
        $this->assertEquals(0, personal_purge::execute_count($targetuser4, $syscontext));

        // Purge the deleted user to make sure that the purge runs properly on them.
        $targetuser3 = new target_user($data->user3);
        $pgoals4 = $DB->get_records('goal_personal', ['userid' => $data->user3->id]);
        $this->assertEquals(1, count($pgoals4));

        $result = personal_purge::execute_purge($targetuser3, $syscontext);
        $this->assertEquals(item::RESULT_STATUS_SUCCESS, $result);

        $pgoals4 = $DB->get_records('goal_personal', ['userid' => $data->user3->id]);
        $this->assertEquals(0, count($pgoals4));
    }

    /**
     * Test the export function for the personal goal userdata items.
     */
    public function test_personal_goal_export_system () {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();
        $data = $this->make_user_goals();
        $syscontext = \context_system::instance();

        $this->assertFalse(personal_purge::is_exportable());
        $this->assertTrue(personal_export_visible::is_exportable());
        $this->assertTrue(personal_export_hidden::is_exportable());

        $this->assertTrue(has_capability('totara/hierarchy:viewownpersonalgoal', $syscontext, $data->user1->id));
        $this->assertTrue(has_capability('totara/hierarchy:viewownpersonalgoal', $syscontext, $data->user2->id));

        $targetuser1 = new target_user($data->user1);
        $count = personal_export_visible::execute_count($targetuser1, $syscontext);
        $this->assertEquals(2, $count);
        $result = personal_export_visible::execute_export($targetuser1, $syscontext);
        $this->assertEquals(2, count($result->data));

        foreach ($result->data as $goal) {
            $this->assertEquals($data->user1->id, $goal['userid']);

            // Check the custom fields, note: only text, datetime, and checkbox are set by the generator at the moment.
            if (!empty($goal['typeid'])) {
                $fields = $DB->get_records('goal_user_info_field', ['typeid' => $goal['typeid']]);

                foreach ($fields as $field) {
                    $fname = 'cf_' . $field->shortname;
                    switch ($field->datatype) {
                        case 'text' :
                            $this->assertTrue(isset($goal[$fname]));
                            $cfdata = $goal[$fname];
                            $this->assertEquals('abc123', $cfdata['data']);
                            break;
                        case 'datetime' :
                            $this->assertTrue(isset($goal[$fname]));
                            $cfdata = $goal[$fname];
                            $this->assertEquals(1234567890, $cfdata['data']);
                            break;
                        case 'checkbox' :
                            $this->assertTrue(isset($goal[$fname]));
                            $cfdata = $goal[$fname];
                            $this->assertEquals(1, $cfdata['data']);
                            break;
                        default :
                            $this->assertFalse(isset($goal[$fname]));
                            break;
                    }
                }
            }
        }

        $result = personal_export_hidden::execute_export($targetuser1, $syscontext);
        $this->assertEquals(0, count($result->data));

        $targetuser2 = new target_user($data->user2);
        $count = personal_export_visible::execute_count($targetuser2, $syscontext);
        $this->assertEquals(1, $count);

        // Remove the viewownpersonalgoal capability.
        $userrole = $DB->get_record('role', ['shortname' => 'user']);
        unassign_capability('totara/hierarchy:viewownpersonalgoal', $userrole->id, $syscontext);
        $this->assertFalse(has_capability('totara/hierarchy:viewownpersonalgoal', $syscontext, $data->user1->id));
        $this->assertFalse(has_capability('totara/hierarchy:viewownpersonalgoal', $syscontext, $data->user2->id));

        // Retest to check that hidden and visible have switched.
        $count = personal_export_hidden::execute_count($targetuser1, $syscontext);
        $this->assertEquals(2, $count);
        $result = personal_export_hidden::execute_export($targetuser1, $syscontext);
        $this->assertEquals(2, count($result->data));

        foreach ($result->data as $goal) {
            $this->assertEquals($data->user1->id, $goal['userid']);

            // Check the custom fields, note: only text, datetime, and checkbox are set by the generator at the moment.
            if (!empty($goal['typeid'])) {
                $fields = $DB->get_records('goal_user_info_field', ['typeid' => $goal['typeid']]);

                foreach ($fields as $field) {
                    $fname = 'cf_' . $field->shortname;
                    switch ($field->datatype) {
                        case 'text' :
                            $this->assertTrue(isset($goal[$fname]));
                            $cfdata = $goal[$fname];
                            $this->assertEquals('abc123', $cfdata['data']);
                            break;
                        case 'datetime' :
                            $this->assertTrue(isset($goal[$fname]));
                            $cfdata = $goal[$fname];
                            $this->assertEquals(1234567890, $cfdata['data']);
                            break;
                        case 'checkbox' :
                            $this->assertTrue(isset($goal[$fname]));
                            $cfdata = $goal[$fname];
                            $this->assertEquals(1, $cfdata['data']);
                            break;
                        default :
                            $this->assertFalse(isset($goal[$fname]));
                            break;
                    }
                }
            }
        }

        $result = personal_export_visible::execute_export($targetuser1, $syscontext);
        $this->assertEquals(0, count($result->data));
    }
}
