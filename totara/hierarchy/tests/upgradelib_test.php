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
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 * @package totara_hierarchy
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/totara/hierarchy/db/upgradelib.php');
require_once($CFG->dirroot . '/totara/hierarchy/prefix/goal/lib.php');

/**
 * Unit tests for upgrade functions
 */
class totara_hierarchy_upgradelib_testcase extends advanced_testcase {

    /**
     * Test a basic sort operation where everything is correct to ensure nothing changes.
     */
    public function test_totara_hierarchy_fix_customfield_sortorder_correctly_ordered() {
        $this->resetAfterTest();

        $tableprefix = 'goal_type';
        $table = $tableprefix.'_info_field';

        $this->assertCount(0, $this->get_fields_menu($table, 1));
        $this->insert_fake_field($table, 1, 'field-1', 1);
        $this->insert_fake_field($table, 1, 'field-2', 2);
        $this->insert_fake_field($table, 1, 'field-3', 3);
        $this->insert_fake_field($table, 2, 'field-4', 1);
        $this->insert_fake_field($table, 2, 'field-5', 2);
        $fields = $this->get_fields_menu($table, 1);
        $this->assertCount(3, $fields);
        $this->assertSame(['field-1', 'field-2', 'field-3'], array_keys($fields));
        $this->assertSame(['1', '2', '3'], array_values($fields));

        // Now run the upgrade step and ensure nothing changes.
        totara_hierarchy_upgrade_fix_customfield_sortorder($tableprefix);

        $fields = $this->get_fields_menu($table, 1);
        $this->assertCount(3, $fields);
        $this->assertSame(['field-1', 'field-2', 'field-3'], array_keys($fields));
        $this->assertSame(['1', '2', '3'], array_values($fields));

        $fields = $this->get_fields_menu($table, 2);
        $this->assertCount(2, $fields);
        $this->assertSame(['field-4', 'field-5'], array_keys($fields));
        $this->assertSame(['1', '2'], array_values($fields));
    }

    /**
     * Test correct sort order get maintained across unsorted names.
     */
    public function test_totara_hierarchy_fix_customfield_sortorder_out_of_order_1() {
        $this->resetAfterTest();

        $tableprefix = 'goal_type';
        $table = $tableprefix.'_info_field';

        $this->assertCount(0, $this->get_fields_menu($table, 1));
        $this->insert_fake_field($table, 1, 'field-1', 3);
        $this->insert_fake_field($table, 1, 'field-2', 2);
        $this->insert_fake_field($table, 1, 'field-3', 1);
        $this->insert_fake_field($table, 2, 'field-4', 2);
        $this->insert_fake_field($table, 2, 'field-5', 1);
        $fields = $this->get_fields_menu($table, 1);
        $this->assertCount(3, $fields);
        $this->assertSame(['field-3', 'field-2', 'field-1'], array_keys($fields));
        $this->assertSame(['1', '2', '3'], array_values($fields));

        // Now run the upgrade step and ensure it corrects the order.
        totara_hierarchy_upgrade_fix_customfield_sortorder($tableprefix);

        $fields = $this->get_fields_menu($table, 1);
        $this->assertCount(3, $fields);
        $this->assertSame(['field-3', 'field-2', 'field-1'], array_keys($fields));
        $this->assertSame(['1', '2', '3'], array_values($fields));

        $fields = $this->get_fields_menu($table, 2);
        $this->assertCount(2, $fields);
        $this->assertSame(['field-5', 'field-4'], array_keys($fields));
        $this->assertSame(['1', '2'], array_values($fields));
    }

    /**
     * Test correct sort order get maintained across unsorted names.
     */
    public function test_totara_hierarchy_fix_customfield_sortorder_out_of_order_2() {
        $this->resetAfterTest();

        $tableprefix = 'goal_type';
        $table = $tableprefix.'_info_field';

        $this->assertCount(0, $this->get_fields_menu($table, 1));
        $this->insert_fake_field($table, 1, 'field-2', 1);
        $this->insert_fake_field($table, 1, 'field-1', 2);
        $this->insert_fake_field($table, 1, 'field-3', 3);
        $this->insert_fake_field($table, 2, 'field-5', 1);
        $this->insert_fake_field($table, 2, 'field-4', 2);
        $fields = $this->get_fields_menu($table, 1);
        $this->assertCount(3, $fields);
        $this->assertSame(['field-2', 'field-1', 'field-3'], array_keys($fields));
        $this->assertSame(['1', '2', '3'], array_values($fields));

        // Now run the upgrade step and ensure it corrects the order.
        totara_hierarchy_upgrade_fix_customfield_sortorder($tableprefix);

        $fields = $this->get_fields_menu($table, 1);
        $this->assertCount(3, $fields);
        $this->assertSame(['field-2', 'field-1', 'field-3'], array_keys($fields));
        $this->assertSame(['1', '2', '3'], array_values($fields));

        $fields = $this->get_fields_menu($table, 2);
        $this->assertCount(2, $fields);
        $this->assertSame(['field-5', 'field-4'], array_keys($fields));
        $this->assertSame(['1', '2'], array_values($fields));
    }

    /**
     * Test duplicate values in the sort order get fixed.
     */
    public function test_totara_hierarchy_fix_customfield_sortorder_duplicate_sortorder_1() {
        $this->resetAfterTest();

        $tableprefix = 'goal_type';
        $table = $tableprefix.'_info_field';

        $this->assertCount(0, $this->get_fields_menu($table, 1));
        $this->insert_fake_field($table, 1, 'field-1', 1);
        $this->insert_fake_field($table, 1, 'field-2', 1);
        $this->insert_fake_field($table, 1, 'field-3', 2);
        $this->insert_fake_field($table, 2, 'field-4', 1);
        $this->insert_fake_field($table, 2, 'field-5', 1);
        $fields = $this->get_fields_menu($table, 1);
        $this->assertCount(3, $fields);
        $this->assertSame(['field-1', 'field-2', 'field-3'], array_keys($fields));
        $this->assertSame(['1', '1', '2'], array_values($fields));

        // Now run the upgrade step and ensure it corrects the order.
        totara_hierarchy_upgrade_fix_customfield_sortorder($tableprefix);

        $fields = $this->get_fields_menu($table, 1);
        $this->assertCount(3, $fields);
        $this->assertSame(['field-1', 'field-2', 'field-3'], array_keys($fields));
        $this->assertSame(['1', '2', '3'], array_values($fields));

        $fields = $this->get_fields_menu($table, 2);
        $this->assertCount(2, $fields);
        $this->assertSame(['field-4', 'field-5'], array_keys($fields));
        $this->assertSame(['1', '2'], array_values($fields));
    }

    /**
     * Test duplicate values in the sort order get fixed.
     */
    public function test_totara_hierarchy_fix_customfield_sortorder_duplicate_sortorder_2() {
        $this->resetAfterTest();

        $tableprefix = 'goal_type';
        $table = $tableprefix.'_info_field';

        $this->assertCount(0, $this->get_fields_menu($table, 1));
        $this->insert_fake_field($table, 1, 'field-1', 2);
        $this->insert_fake_field($table, 1, 'field-2', 2);
        $this->insert_fake_field($table, 1, 'field-3', 2);
        $this->insert_fake_field($table, 2, 'field-4', 2);
        $this->insert_fake_field($table, 2, 'field-5', 2);
        $fields = $this->get_fields_menu($table, 1);
        $this->assertCount(3, $fields);
        $this->assertSame(['field-1', 'field-2', 'field-3'], array_keys($fields));
        $this->assertSame(['2', '2', '2'], array_values($fields));

        // Now run the upgrade step and ensure it corrects the order.
        totara_hierarchy_upgrade_fix_customfield_sortorder($tableprefix);

        $fields = $this->get_fields_menu($table, 1);
        $this->assertCount(3, $fields);
        $this->assertSame(['field-1', 'field-2', 'field-3'], array_keys($fields));
        $this->assertSame(['1', '2', '3'], array_values($fields));

        $fields = $this->get_fields_menu($table, 2);
        $this->assertCount(2, $fields);
        $this->assertSame(['field-4', 'field-5'], array_keys($fields));
        $this->assertSame(['1', '2'], array_values($fields));
    }

    /**
     * Test duplicate values in the sort order get fixed.
     */
    public function test_totara_hierarchy_fix_customfield_sortorder_duplicate_sortorder_3() {
        $this->resetAfterTest();

        $tableprefix = 'goal_type';
        $table = $tableprefix.'_info_field';

        $this->assertCount(0, $this->get_fields_menu($table, 1));
        $this->insert_fake_field($table, 1, 'field-3', 3);
        $this->insert_fake_field($table, 1, 'field-2', 3);
        $this->insert_fake_field($table, 1, 'field-1', 3);
        $this->insert_fake_field($table, 2, 'field-4', 4);
        $this->insert_fake_field($table, 2, 'field-5', 4);
        $fields = $this->get_fields_menu($table, 1);
        $this->assertCount(3, $fields);
        $this->assertSame(['field-3', 'field-2', 'field-1'], array_keys($fields));
        $this->assertSame(['3', '3', '3'], array_values($fields));

        // Now run the upgrade step and ensure it corrects the order.
        totara_hierarchy_upgrade_fix_customfield_sortorder($tableprefix);

        $fields = $this->get_fields_menu($table, 1);
        $this->assertCount(3, $fields);
        $this->assertSame(['field-3', 'field-2', 'field-1'], array_keys($fields));
        $this->assertSame(['1', '2', '3'], array_values($fields));

        $fields = $this->get_fields_menu($table, 2);
        $this->assertCount(2, $fields);
        $this->assertSame(['field-4', 'field-5'], array_keys($fields));
        $this->assertSame(['1', '2'], array_values($fields));
    }

    /**
     * Test gaps in the sort order get fixed.
     */
    public function test_totara_hierarchy_fix_customfield_sortorder_missing_sortorder() {
        $this->resetAfterTest();

        $tableprefix = 'goal_type';
        $table = $tableprefix.'_info_field';

        $this->assertCount(0, $this->get_fields_menu($table, 1));
        $this->insert_fake_field($table, 1, 'field-1', 1);
        $this->insert_fake_field($table, 1, 'field-2', 6);
        $this->insert_fake_field($table, 1, 'field-3', 3);
        $this->insert_fake_field($table, 2, 'field-4', 1);
        $this->insert_fake_field($table, 2, 'field-5', 3);
        $fields = $this->get_fields_menu($table, 1);
        $this->assertCount(3, $fields);
        $this->assertSame(['field-1', 'field-3', 'field-2'], array_keys($fields));
        $this->assertSame(['1', '3', '6'], array_values($fields));

        // Now run the upgrade step and ensure it corrects the order.
        totara_hierarchy_upgrade_fix_customfield_sortorder($tableprefix);

        $fields = $this->get_fields_menu($table, 1);
        $this->assertCount(3, $fields);
        $this->assertSame(['field-1', 'field-3', 'field-2'], array_keys($fields));
        $this->assertSame(['1', '2', '3'], array_values($fields));

        $fields = $this->get_fields_menu($table, 2);
        $this->assertCount(2, $fields);
        $this->assertSame(['field-4', 'field-5'], array_keys($fields));
        $this->assertSame(['1', '2'], array_values($fields));
    }

    /**
     * Inserts a fake custom field, please be aware associations may not exist.
     *
     * @param string $table
     * @param int $typeid
     * @param string $name
     * @param int $sortorder
     * @param array|null $additionalparams
     * @return int
     */
    private function insert_fake_field($table, $typeid, $name, $sortorder, array $additionalparams = null) {
        global $DB;
        $obj = new stdClass;
        $obj->shortname = $name;
        $obj->typeid = $typeid;
        $obj->description = '';
        $obj->sortorder = $sortorder;
        $obj->fullname = 'Test';
        $obj->type = 'text';
        $obj->hidden = 0;
        $obj->locked = 0;
        $obj->required = 0;
        $obj->forceunique = 0;
        $obj->param1 = 30;
        $obj->param2 = 2048;

        if (is_array($additionalparams)) {
            foreach ($additionalparams as $key => $value) {
                if (property_exists($obj, $key)) {
                    $obj->{$key} = $value;
                }
            }
        }

        return $DB->insert_record($table, $obj);
    }

    /**
     * Returns an associative array of fields, where key = shortname, and value = sortorder.
     *
     * @param string $table
     * @param int $typeid
     * @return array
     */
    private function get_fields_menu($table, $typeid = 1) {
        global $DB;
        return $DB->get_records_menu($table, ['typeid' => $typeid], 'typeid ASC, sortorder ASC, id ASC', 'shortname, sortorder');
    }

    public function test_totara_hierarchy_upgrade_user_assignment_extrainfo() {
        global $DB, $USER;

        $this->resetAfterTest(true);

        $goalid = 1001;
        $orgid = 2001;
        $posid = 2002;
        $audid = 2003;
        $userid = 3001;
        $timeodified = 4001;
        $usermodified = 5001;

        $rule_org = new stdClass();
        $rule_org->goalid = $goalid;
        $rule_org->orgid = $orgid;
        $rule_org->includechildren = true;
        $rule_org->timemodified = $timeodified;
        $rule_org->usermodified = $usermodified;
        $rule_org->id = $DB->insert_record('goal_grp_org', $rule_org);

        $rule_pos = new stdClass();
        $rule_pos->goalid = $goalid;
        $rule_pos->posid = $posid;
        $rule_pos->includechildren = true;
        $rule_pos->timemodified = $timeodified;
        $rule_pos->usermodified = $usermodified;
        $rule_pos->id = $DB->insert_record('goal_grp_pos', $rule_pos);

        $rule_aud = new stdClass();
        $rule_aud->goalid = $goalid;
        $rule_aud->cohortid = $audid;
        $rule_aud->includechildren = true;
        $rule_aud->timemodified = $timeodified;
        $rule_aud->usermodified = $usermodified;
        $rule_aud->id = $DB->insert_record('goal_grp_cohort', $rule_aud);

        // Org current (null extrainfo).
        $assign = new stdClass();
        $assign->assigntype = GOAL_ASSIGNMENT_ORGANISATION;
        $assign->assignmentid = $rule_org->id;
        $assign->goalid = $goalid;
        $assign->userid = $userid;
        $assign->extrainfo = null;
        $assign->timemodified = $timeodified;
        $assign->usermodified = $usermodified;
        $DB->insert_record('goal_user_assignment', $assign);

        // Org current child (PAR extrainfo - would be made OLD on next cron run anyway).
        $assign = new stdClass();
        $assign->assigntype = GOAL_ASSIGNMENT_ORGANISATION;
        $assign->assignmentid = $rule_org->id;
        $assign->goalid = $goalid;
        $assign->userid = $userid;
        $assignmenttype = GOAL_ASSIGNMENT_ORGANISATION;
        $assign->extrainfo = "PAR:{$assignmenttype},{$rule_org->id}";
        $assign->timemodified = $timeodified;
        $assign->usermodified = $usermodified;
        $DB->insert_record('goal_user_assignment', $assign);

        // Org OLD.
        $assign = new stdClass();
        $assign->assigntype = GOAL_ASSIGNMENT_INDIVIDUAL;
        $assign->assignmentid = 0;
        $assign->goalid = $goalid;
        $assign->userid = $userid;
        $assignmenttype = GOAL_ASSIGNMENT_ORGANISATION;
        $assign->extrainfo = "OLD:{$assignmenttype},{$rule_org->id}";
        $assign->timemodified = $timeodified;
        $assign->usermodified = $usermodified;
        $DB->insert_record('goal_user_assignment', $assign);

        // Pos current (null extrainfo).
        $assign = new stdClass();
        $assign->assigntype = GOAL_ASSIGNMENT_POSITION;
        $assign->assignmentid = $rule_pos->id;
        $assign->goalid = $goalid;
        $assign->userid = $userid;
        $assign->extrainfo = null;
        $assign->timemodified = $timeodified;
        $assign->usermodified = $usermodified;
        $DB->insert_record('goal_user_assignment', $assign);

        // Pos current child (PAR extrainfo - would be made OLD on next cron run anyway).
        $assign_org_current = new stdClass();
        $assign_org_current->assigntype = GOAL_ASSIGNMENT_POSITION;
        $assign_org_current->assignmentid = $rule_pos->id;
        $assign_org_current->goalid = $goalid;
        $assign_org_current->userid = $userid;
        $assignmenttype = GOAL_ASSIGNMENT_POSITION;
        $assign_org_current->extrainfo = "PAR:{$assignmenttype},{$rule_pos->id}";
        $assign_org_current->timemodified = $timeodified;
        $assign_org_current->usermodified = $usermodified;
        $DB->insert_record('goal_user_assignment', $assign);

        // Pos OLD.
        $assign_org_current = new stdClass();
        $assign_org_current->assigntype = GOAL_ASSIGNMENT_INDIVIDUAL;
        $assign_org_current->assignmentid = 0;
        $assign_org_current->goalid = $goalid;
        $assign_org_current->userid = $userid;
        $assignmenttype = GOAL_ASSIGNMENT_POSITION;
        $assign_org_current->extrainfo = "OLD:{$assignmenttype},{$rule_pos->id}";
        $assign_org_current->timemodified = $timeodified;
        $assign_org_current->usermodified = $usermodified;
        $DB->insert_record('goal_user_assignment', $assign);

        // Aud current (null extrainfo).
        $assign = new stdClass();
        $assign->assigntype = GOAL_ASSIGNMENT_AUDIENCE;
        $assign->assignmentid = $rule_aud->id;
        $assign->goalid = $goalid;
        $assign->userid = $userid;
        $assign->extrainfo = null;
        $assign->timemodified = $timeodified;
        $assign->usermodified = $usermodified;
        $DB->insert_record('goal_user_assignment', $assign);

        // Aud OLD.
        $assign_org_current = new stdClass();
        $assign_org_current->assigntype = GOAL_ASSIGNMENT_INDIVIDUAL;
        $assign_org_current->assignmentid = 0;
        $assign_org_current->goalid = $goalid;
        $assign_org_current->userid = $userid;
        $assignmenttype = GOAL_ASSIGNMENT_AUDIENCE;
        $assign_org_current->extrainfo = "OLD:{$assignmenttype},{$rule_aud->id}";
        $assign_org_current->timemodified = $timeodified;
        $assign_org_current->usermodified = $usermodified;
        $DB->insert_record('goal_user_assignment', $assign);

        // Figure out what we expect the records to look like after upgrade.
        $expected_user_assignments = $DB->get_records('goal_user_assignment', [], 'id');

        $itemid = -1;
        foreach ($expected_user_assignments as $expected_user_assignment) {
            switch ($expected_user_assignment->assigntype) {
                case GOAL_ASSIGNMENT_ORGANISATION:
                    $itemid = $orgid;
                    break;
                case GOAL_ASSIGNMENT_POSITION:
                    $itemid = $posid;
                    break;
                case GOAL_ASSIGNMENT_AUDIENCE:
                    $itemid = $audid;
                    break;
                default:
                    // Should not be changed.
                    continue 2;
            }
            if (is_null($expected_user_assignment->extrainfo)) {
                // We expect all current records to be updated to new ITEM records.
                $expected_user_assignment->extrainfo =
                    "ITEM:{$expected_user_assignment->assigntype},{$expected_user_assignment->assignmentid},{$itemid}";
            } else if (substr($expected_user_assignment->extrainfo, 0, 3) == 'PAR') {
                // If there are any PAR records then the next cron run would make them OLD anyway.
                $expected_user_assignment->extrainfo = "OLD:{$expected_user_assignment->assigntype},{$itemid}";
                $expected_user_assignment->assigntype = GOAL_ASSIGNMENT_INDIVIDUAL;
                $expected_user_assignment->assignmentid = 0;
                unset($expected_user_assignment->timemodified); // See later as to why this is removed.
                $expected_user_assignment->usermodified = $USER->id;
            }
        }

        // Run the upgrade.
        $timebefore = time();
        totara_hierarchy_upgrade_user_assignment_extrainfo();
        $timeafter = time();

        // Check the results.
        $actual_user_assignments = $DB->get_records('goal_user_assignment', [], 'id');
        foreach ($expected_user_assignments as $expected_user_assignment) {
            if (!isset($expected_user_assignment->timemodified)) {
                // If the expected record doesn't contain the timemodified then we should manually check the actual
                // record, to make sure the timemodified was changed, then remove it so that they match.
                $this->assertGreaterThanOrEqual($timebefore, $actual_user_assignments[$expected_user_assignment->id]->timemodified);
                $this->assertLessThanOrEqual($timeafter, $actual_user_assignments[$expected_user_assignment->id]->timemodified);
                unset($actual_user_assignments[$expected_user_assignment->id]->timemodified);
            }
        }
        $this->assertEquals($expected_user_assignments, $actual_user_assignments);
    }
}
