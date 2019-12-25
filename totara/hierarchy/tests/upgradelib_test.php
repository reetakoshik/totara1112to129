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

}
