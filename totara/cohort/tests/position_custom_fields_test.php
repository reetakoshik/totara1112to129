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
 * @author Simon Player <simon.player@totaralms.com>
 * @package totara
 * @subpackage cohort
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/totara/reportbuilder/tests/reportcache_advanced_testcase.php');
require_once($CFG->dirroot . '/totara/cohort/lib.php');
require_once($CFG->libdir . '/testing/generator/lib.php');

/**
 * Test position custom fields within dynamic audience.
 *
 * To test, run this from the command line from the $CFG->dirroot
 * vendor/bin/phpunit totara_cohort_position_custom_fields_test
 *
 */
class totara_cohort_position_custom_fields_testcase extends advanced_testcase {

    private $cohort_generator = null;
    private $posfw = null;
    private $pos1 = null;
    private $postype1 = null;
    private $ruleset = 0;
    private $cohort = null;

    const TEST_USER_COUNT_MEMBERS = 10;

    protected function tearDown() {
        $this->cohort_generator = null;
        $this->posfw = null;
        $this->pos1 = null;
        $this->postype1 = null;
        $this->ruleset = null;
        $this->cohort = null;
        parent::tearDown();
    }

    public function setUp() {
        global $DB, $USER;

        parent::setup();
        $this->resetAfterTest(true);
        $this->setAdminUser();

        $generator = $this->getDataGenerator();

        // Set totara_hierarchy generator.
        $this->hierarchy_generator = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');

        // Create position framework.
        $name = totara_hierarchy_generator::DEFAULT_NAME_FRAMEWORK_POSITION;
        $name .= ' ' . totara_generator_util::get_next_record_number('pos_framework', 'fullname', $name);
        $data = array ('fullname' => $name);
        $this->posfw = $this->hierarchy_generator->create_framework('position', $data);
        $this->assertEquals(1, $DB->count_records('pos_framework'));

        // Create position.
        $this->pos1 = $this->hierarchy_generator->create_hierarchy($this->posfw->id, 'position', array('idnumber' => 'pos1', 'fullname' => 'Postion 1'));
        $this->assertEquals(1, $DB->count_records('pos'));

        // Create a position type.
        $newtype = new stdClass();
        $newtype->shortname = 'Position type 1';
        $newtype->fullname = 'type1';
        $newtype->idnumber = 'type1';
        $newtype->description = '';
        $newtype->timecreated = time();
        $newtype->usermodified = $USER->id;
        $newtype->timemodified = time();
        $this->postype1 = $DB->insert_record('pos_type', $newtype);

        // Check the record was created correctly.
        $this->assertInternalType('int', $this->postype1);

        // Assign the type position to pos1.
        $this->assertTrue($DB->set_field('pos', 'typeid', $this->postype1, array('id' => $this->pos1->id)));

        // Create users.
        for ($i = 1; $i <= self::TEST_USER_COUNT_MEMBERS; $i++) {
            $user = $generator->create_user();
            // Add user job assignment.
            \totara_job\job_assignment::create_default($user->id, array('positionid' => $this->pos1->id));
        }
        $this->assertSame(self::TEST_USER_COUNT_MEMBERS + 2, $DB->count_records('user'));

        // Set totara_cohort generator.
        $this->cohort_generator = $generator->get_plugin_generator('totara_cohort');

        // Creating an empty dynamic cohort.
        $this->cohort = $this->cohort_generator->create_cohort(array('name' => 'Audience 1', 'cohorttype' => cohort::TYPE_DYNAMIC));
        $this->assertTrue($DB->record_exists('cohort', array('id' => $this->cohort->id)));
        $this->assertEquals(0, $DB->count_records('cohort_members', array('cohortid' => $this->cohort->id)));

        // Creating a ruleset.
        $this->ruleset = cohort_rule_create_ruleset($this->cohort->draftcollectionid);
    }

    public function add_custom_field($name, $typeid, $datatype, $param1, $defaulfieldvalue) {
        global $DB;
        $newtype = new stdClass();
        $newtype->shortname = $name;
        $newtype->fullname = $name;
        $newtype->typeid = $typeid;
        $newtype->description = '';
        $newtype->sortorder = $DB->get_field('pos_type_info_field', '(case when max(sortorder) is null then 1 else max(sortorder) end) + 1', array());
        $newtype->hidden = 0;
        $newtype->locked = 0;
        $newtype->required = 0;
        $newtype->forceunique = 0;
        $newtype->datatype = $datatype;
        $newtype->param1 = $param1;
        $newtype->defaultdata = $defaulfieldvalue;

        return  $DB->insert_record('pos_type_info_field', $newtype);
    }

    /**
     * Data provider for testing position custom fields.
     */
    public function position_custom_fields_params() {
        $data = array();

         // We will be testing the following field types.
         // - Checkbox
         // - Menu of choices
         // - Text input
         // - Date/time

         // Checkbox tests.

             // Default field value, 1, Don't save custom field against position.
             // Set rule, checkbox is checked.
             // Expect member count of 0
             //
             // **********************************************************************
             // POTENTIAL IMPROVEMENT FOR FUTURE RELEASES
             // -----------------------------------------
             // If a value is not saved against the position, the default value
             // is taken from the custom filed type, if set.
             //
             // If so, this test would have member count ALL (self::TEST_USER_COUNT_MEMBERS )
             //
             // **********************************************************************
            $data[] = array(
                'checkbox', // Custom field type.
                null, // Param1 of custom field.
                1, // Position type: Default custom field value.
                false, // Position: Save custom field into position.
                null, // Position: If being saved, value to save into the position.
                array('equal' => '1'), // Audience rule: is checked
                array(1), // Audience rule: Value
                'listofvalues', // Audience rule: Param name
                0 // Expected member count for test.
            );

             // Default field value, 0, Save custom field against position.
             // Set rule, checkbox is checked.
             // Expect member count of 0 (Working with known issue below)
             //
             // **********************************************************************
             // KNOWN ISSUE
             // -----------------------------------------
             // The checkbox rules incorrectly behave in reverse. So a rule of checkbox
             // equal to checked would select all unchecked checkboxes and vis versa.
             //
             // **********************************************************************
             //
            $data[] = array(
                'checkbox', // Custom field type.
                null, // Param1 of custom field.
                0, // Position type: Default custom field value
                false, // Position: Save custom field into position.
                null, // Position: If being saved, value to save into the position.
                array('equal' => '1'), // Audience rule: is checked
                array(1), // Audience rule: Value
                'listofvalues', // Audience rule: Param name
                0 // Expected member count for test.
            );

             // Default field value, 0, Save custom field against position.
             // Set rule, checkbox is checked.
             // Expect member count of 0 (Working with known issue below)
             //
             // **********************************************************************
             // KNOWN ISSUE
             // -----------------------------------------
             // The checkbox rules incorrectly behave in reverse. So a rule of checkbox
             // equal to checked would select all unchecked checkboxes and vis versa.
             //
             // **********************************************************************
             //
            $data[] = array(
                'checkbox', // Custom field type.
                null, // Param1 of custom field.
                0, // Position type: Default custom field value.
                true, // Position: Save custom field into position.
                1, // Position: If being saved, value to save into the position.
                array('equal' => '1'), // Audience rule: is checked
                array(1), // Audience rule: Value
                'listofvalues', // Audience rule: Param name
                0 // Expected member count for test.
            );

             // Default field value, 0, Save custom field against position.
             // Set rule, checkbox is unchecked.
             // Expect member count of 0 (Working with known issue below)
             //
             // **********************************************************************
             // KNOWN ISSUE
             // -----------------------------------------
             // The checkbox rules incorrectly behave in reverse. So a rule of checkbox
             // equal to checked would select all unchecked checkboxes and vis versa.
             //
             // **********************************************************************
             //
            $data[] = array(
                'checkbox', // Custom field type.
                null, // Param1 of custom field.
                0, // Position type: Default custom field value.
                true, // Position: Save custom field into position.
                1, // Position: If being saved, value to save into the position.
                array('equal' => '1'), // Audience rule: is unchecked
                array(0), // Audience rule: Value
                'listofvalues', // Audience rule: Param name
                self::TEST_USER_COUNT_MEMBERS // Expected member count for test.
            );

             // Default field value, 0, Save custom field against position.
             // Set rule, checkbox is checked.
             // Expect member count of 0 (Working with known issue below)
             //
             // **********************************************************************
             // KNOWN ISSUE
             // -----------------------------------------
             // The checkbox rules incorrectly behave in reverse. So a rule of checkbox
             // equal to checked would select all unchecked checkboxes and vis versa.
             //
             // **********************************************************************
             //
            $data[] = array(
                'checkbox', // Custom field type.
                null, // Param1 of custom field.
                0, // Position type: Default custom field value.
                true, // Position: Save custom field into position.
                0, // Position: If being saved, value to save into the position.
                array('equal' => '1'), // Audience rule: is checked
                array(1), // Audience rule: Value
                'listofvalues', // Audience rule: Param name
                self::TEST_USER_COUNT_MEMBERS // Expected member count for test.
            );

             // Default field value, 0, Save custom field against position.
             // Set rule, checkbox is unchecked.
             // Expect member count of 0 (Working with known issue below)
             //
             // **********************************************************************
             // KNOWN ISSUE
             // -----------------------------------------
             // The checkbox rules incorrectly behave in reverse. So a rule of checkbox
             // equal to checked would select all unchecked checkboxes and vis versa.
             //
             // **********************************************************************
            $data[] = array(
                'checkbox', // Custom field type.
                null, // Param1 of custom field.
                0, // Position type: Default custom field value.
                true, // Position: Save custom field into position.
                0, // Position: If being saved, value to save into the position.
                array('equal' => '1'), // Audience rule: is unchecked
                array(0), // Audience rule: Value
                'listofvalues', // Audience rule: Param name
                0 // Expected member count for test.
            );

         // Menu of choices tests

             // Default field value, Item2, Don't save custom field against position.
             // Set rule, value is equal to 'Item2'
             // Expect member count of 0 (Working with known issue below)
             //
             // **********************************************************************
             // POTENTIAL IMPROVEMENT FOR FUTURE RELEASES
             // -----------------------------------------
             // If a value is not saved against the position, the default value
             // is taken from the custom filed type, if set.
             //
             // If so, this test would have member count self::TEST_USER_COUNT_MEMBERS
             //
             // **********************************************************************
             //
            $data[] = array(
                'menu', // Custom field type
                'Item1' . PHP_EOL . 'Item2' . PHP_EOL . 'Item3', // Param1 of custom field
                'Item2', // Position type: Default custom field value
                false, // Position: Save custom field into position.
                null, // Position: If being saved, value to save into the position.
                array('equal' => '1'), // Audience rule: is equal to
                array('Item2'), // Audience rule: Value
                'listofvalues', // Audience rule: Param name
                0 // Expected member count for test.
            );

             // Default field value, Item2, Don't save custom field against position.
             // Set rule, value is NOT equal to 'Item2'
             // Expect member count of ALL
             //
             // **********************************************************************
             // POTENTIAL IMPROVEMENT FOR FUTURE RELEASES
             // -----------------------------------------
             // If a value is not saved against the position, the default value
             // is taken from the custom filed type, if set.
             //
             // If so, this test would stay unaltered wtih a member count of 0
             //
             // **********************************************************************
             //
            $data[] = array(
                'menu', // Custom field type
                'Item1' . PHP_EOL . 'Item2' . PHP_EOL . 'Item3', // Param1 of custom field
                'Item2', // Position type: Default custom field value
                false, // Position: Save custom field into position.
                null, // Position: If being saved, value to save into the position.
                array('equal' => '2'), // Audience rule: not equal to
                array('Item2'), // Audience rule: Value
                'listofvalues', // Audience rule: Param name
                0 // Expected member count for test.
            );

             // Default field value, Item2, Save custom field against position.
             // Set rule, value is equal to 'Item1'
             // Expect member count of 0 (Working with known issue below)
            $data[] = array(
                'menu', // Custom field type
                'Item1' . PHP_EOL . 'Item2' . PHP_EOL . 'Item3', // Param1 of custom field
                'Item2', // Position type: Default custom field value
                true, // Position: Save custom field into position.
                'Item2', // Position: If being saved, value to save into the position.
                array('equal' => '1'), // Audience rule: is equal to
                array('Item1'), // Audience rule: Value
                'listofvalues', // Audience rule: Param name
                0 // Expected member count for test.
            );

             // Default field value, Item2, Save custom field against position.
             // Set rule, value is NOT equal to 'Item1'
             // Expect member count of ALL
            $data[] = array(
                'menu', // Custom field type
                'Item1' . PHP_EOL . 'Item2' . PHP_EOL . 'Item3', // Param1 of custom field
                'Item2', // Position type: Default custom field value
                true, // Position: Save custom field into position.
                'Item2', // Position: If being saved, value to save into the position.
                array('equal' => '0'), // Audience rule: not equal to
                array('Item1'), // Audience rule: Value
                'listofvalues', // Audience rule: Param name
                self::TEST_USER_COUNT_MEMBERS // Expected member count for test.
            );

             // Default field value, Item2, Save custom field against position.
             // Set rule, value is equal to 'Item2'
             // Expect member count of ALL
            $data[] = array(
                'menu', // Custom field type
                'Item1' . PHP_EOL . 'Item2' . PHP_EOL . 'Item3', // Param1 of custom field
                'Item2', // Position type: Default custom field value
                true, // Position: Save custom field into position.
                'Item2', // Position: If being saved, value to save into the position.
                array('equal' => '1'), // Audience rule: not equal to
                array('Item2'), // Audience rule: Value
                'listofvalues', // Audience rule: Param name
                self::TEST_USER_COUNT_MEMBERS // Expected member count for test.
            );

             // Default field value, Item2, Save custom field against position.
             // Set rule, value is NOT equal to 'Item2'
             // Expect member count of 0
            $data[] = array(
                'menu', // Custom field type
                'Item1' . PHP_EOL . 'Item2' . PHP_EOL . 'Item3', // Param1 of custom field
                'Item2', // Position type: Default custom field value
                true, // Position: Save custom field into position.
                'Item2', // Position: If being saved, value to save into the position.
                array('equal' => '0'), // Audience rule: not equal to
                array('Item2'), // Audience rule: Value
                'listofvalues', // Audience rule: Param name
                0 // Expected member count for test.
            );

         // Text input tests

                // Rule: Contains

                 // Default field value, Item1, Save custom field against position.
                 // Set rule, value Contains 'Item1'
                 // Expect member count of ALL
                $data[] = array(
                    'text', // Custom field type
                    null, // Param1 of custom field
                    'Item1', // Position type: Default custom field value
                    true, // Position: Save custom field into position.
                    'Item1', // Position: If being saved, value to save into the position.
                    array('equal' => '0'), // Audience rule: contains
                    array('Item1'), // Audience rule: Value
                    'listofvalues', // Audience rule: Param name
                    self::TEST_USER_COUNT_MEMBERS // Expected member count for test.
                );

                 // Default field value, Item1, Save custom field against position.
                 // Set rule, value Contains '1'
                 // Expect member count of ALL
                $data[] = array(
                    'text', // Custom field type
                    null, // Param1 of custom field
                    'Item1', // Position type: Default custom field value
                    true, // Position: Save custom field into position.
                    'Item1', // Position: If being saved, value to save into the position.
                    array('equal' => '0'), // Audience rule: contains
                    array('1'), // Audience rule: Value
                    'listofvalues', // Audience rule: Param name
                    self::TEST_USER_COUNT_MEMBERS // Expected member count for test.
                );

                 // Default field value, Item1, Save custom field against position.
                 // Set rule, value Contains '2'
                 // Expect member count of 0
                $data[] = array(
                    'text', // Custom field type
                    null, // Param1 of custom field
                    'Item1', // Position type: Default custom field value
                    true, // Position: Save custom field into position.
                    'Item1', // Position: If being saved, value to save into the position.
                    array('equal' => '0'), // Audience rule: contains
                    array('2'), // Audience rule: Value
                    'listofvalues', // Audience rule: Param name
                    0 // Expected member count for test.
                );

                // Rule: Does not contain

                 // Default field value, Item1, Save custom field against position.
                 // Set rule, value Does not contains 'Item1'
                 // Expect member count of 0
                $data[] = array(
                    'text', // Custom field type
                    null, // Param1 of custom field
                    'Item1', // Position type: Default custom field value
                    true, // Position: Save custom field into position.
                    'Item1', // Position: If being saved, value to save into the position.
                    array('equal' => '1'), // Audience rule: does not contains
                    array('Item1'), // Audience rule: Value
                    'listofvalues', // Audience rule: Param name
                    0 // Expected member count for test.
                );

                 // Default field value, Item1, Save custom field against position.
                 // Set rule, value Does not contains '1'
                 // Expect member count of 0
                $data[] = array(
                    'text', // Custom field type
                    null, // Param1 of custom field
                    'Item1', // Position type: Default custom field value
                    true, // Position: Save custom field into position.
                    'Item1', // Position: If being saved, value to save into the position.
                    array('equal' => '1'), // Audience rule: does not contains
                    array('1'), // Audience rule: Value
                    'listofvalues', // Audience rule: Param name
                    0 // Expected member count for test.
                );

                 // Default field value, Item1, Save custom field against position.
                 // Set rule, value Does not contains 'Item2'
                 // Expect member count of ALL
                $data[] = array(
                    'text', // Custom field type
                    null, // Param1 of custom field
                    'Item1', // Position type: Default custom field value
                    true, // Position: Save custom field into position.
                    'Item1', // Position: If being saved, value to save into the position.
                    array('equal' => '1'), // Audience rule: does not contains
                    array('Item2'), // Audience rule: Value
                    'listofvalues', // Audience rule: Param name
                    self::TEST_USER_COUNT_MEMBERS // Expected member count for test.
                );

                 // Default field value, Item1, Save custom field against position.
                 // Set rule, value Does not contains '2'
                 // Expect member count of ALL
                $data[] = array(
                    'text', // Custom field type
                    null, // Param1 of custom field
                    'Item1', // Position type: Default custom field value
                    true, // Position: Save custom field into position.
                    'Item1', // Position: If being saved, value to save into the position.
                    array('equal' => '1'), // Audience rule: equal to
                    array('2'), // Audience rule: Value
                    'listofvalues', // Audience rule: Param name
                    self::TEST_USER_COUNT_MEMBERS // Expected member count for test.
                );

                // Rule: Is equal to

                 // Default field value, Item1, Save custom field against position.
                 // Set rule, value Is equal to 'Item1'
                 // Expect member count of ALL
                $data[] = array(
                    'text', // Custom field type
                    null, // Param1 of custom field
                    'Item1', // Position type: Default custom field value
                    true, // Position: Save custom field into position.
                    'Item1', // Position: If being saved, value to save into the position.
                    array('equal' => '2'), // Audience rule: equal to
                    array('Item1'), // Audience rule: Value
                    'listofvalues', // Audience rule: Param name
                    self::TEST_USER_COUNT_MEMBERS // Expected member count for test.
                );

                 // Default field value, Item1, Save custom field against position.
                 // Set rule, value Is equal to 'Item2'
                 // Expect member count of 0
                $data[] = array(
                    'text', // Custom field type
                    null, // Param1 of custom field
                    'Item1', // Position type: Default custom field value
                    true, // Position: Save custom field into position.
                    'Item1', // Position: If being saved, value to save into the position.
                    array('equal' => '2'), // Audience rule: equal to
                    array('Item2'), // Audience rule: Value
                    'listofvalues', // Audience rule: Param name
                    0 // Expected member count for test.
                );

                 // Default field value, Item1, Save custom field against position.
                 // Set rule, value Is equal to '2'
                 // Expect member count of 0
                $data[] = array(
                    'text', // Custom field type
                    null, // Param1 of custom field
                    'Item1', // Position type: Default custom field value
                    true, // Position: Save custom field into position.
                    'Item1', // Position: If being saved, value to save into the position.
                    array('equal' => '2'), // Audience rule: equal to
                    array('2'), // Audience rule: Value
                    'listofvalues', // Audience rule: Param name
                    0 // Expected member count for test.
                );

            // Rule: Starts with

                 // Default field value, Item1, Save custom field against position.
                 // Set rule, value Starts with 'Item1'
                 // Expect member count of ALL
                $data[] = array(
                    'text', // Custom field type
                    null, // Param1 of custom field
                    'Item1', // Position type: Default custom field value
                    true, // Position: Save custom field into position.
                    'Item1', // Position: If being saved, value to save into the position.
                    array('equal' => '3'), // Audience rule: Starts with
                    array('Item1'), // Audience rule: Value
                    'listofvalues', // Audience rule: Param name
                    self::TEST_USER_COUNT_MEMBERS // Expected member count for test.
                );

                 // Default field value, Item1, Save custom field against position.
                 // Set rule, value Starts with 'Item'
                 // Expect member count of ALL
                $data[] = array(
                    'text', // Custom field type
                    null, // Param1 of custom field
                    'Item1', // Position type: Default custom field value
                    true, // Position: Save custom field into position.
                    'Item1', // Position: If being saved, value to save into the position.
                    array('equal' => '3'), // Audience rule: Starts with
                    array('Item1'), // Audience rule: Value
                    'listofvalues', // Audience rule: Param name
                    self::TEST_USER_COUNT_MEMBERS // Expected member count for test.
                );

                 // Default field value, Item1, Save custom field against position.
                 // Set rule, value Starts with 'aItem'
                 // Expect member count of 0
                $data[] = array(
                    'text', // Custom field type
                    null, // Param1 of custom field
                    'Item1', // Position type: Default custom field value
                    true, // Position: Save custom field into position.
                    'Item1', // Position: If being saved, value to save into the position.
                    array('equal' => '3'), // Audience rule: Starts with
                    array('aItem'), // Audience rule: Value
                    'listofvalues', // Audience rule: Param name
                    0 // Expected member count for test.
                );

                 // Default field value, Item1, Save custom field against position.
                 // Set rule, value Starts with 'Item2'
                 // Expect member count of 0
                $data[] = array(
                    'text', // Custom field type
                    null, // Param1 of custom field
                    'Item1', // Position type: Default custom field value
                    true, // Position: Save custom field into position.
                    'Item1', // Position: If being saved, value to save into the position.
                    array('equal' => '3'), // Audience rule: Starts with
                    array('Item2'), // Audience rule: Value
                    'listofvalues', // Audience rule: Param name
                    0 // Expected member count for test.
                );

            // Rule: Ends with

                 // Default field value, Item1, Save custom field against position.
                 // Set rule, value Starts with 'Item1'
                 // Expect member count of ALL
                $data[] = array(
                    'text', // Custom field type
                    null, // Param1 of custom field
                    'Item1', // Position type: Default custom field value
                    true, // Position: Save custom field into position.
                    'Item1', // Position: If being saved, value to save into the position.
                    array('equal' => '4'), // Audience rule: Starts with
                    array('Item1'), // Audience rule: Value
                    'listofvalues', // Audience rule: Param name
                    self::TEST_USER_COUNT_MEMBERS // Expected member count for test.
                );

                 // Default field value, Item1, Save custom field against position.
                 // Set rule, value Starts with '1'
                 // Expect member count of ALL
                $data[] = array(
                    'text', // Custom field type
                    null, // Param1 of custom field
                    'Item1', // Position type: Default custom field value
                    true, // Position: Save custom field into position.
                    'Item1', // Position: If being saved, value to save into the position.
                    array('equal' => '4'), // Audience rule: Starts with
                    array('1'), // Audience rule: Value
                    'listofvalues', // Audience rule: Param name
                    self::TEST_USER_COUNT_MEMBERS // Expected member count for test.
                );

                 // Default field value, Item1, Save custom field against position.
                 // Set rule, value Starts with 'Item2'
                 // Expect member count of 0
                $data[] = array(
                    'text', // Custom field type
                    null, // Param1 of custom field
                    'Item1', // Position type: Default custom field value
                    true, // Position: Save custom field into position.
                    'Item1', // Position: If being saved, value to save into the position.
                    array('equal' => '4'), // Audience rule: Starts with
                    array('Item2'), // Audience rule: Value
                    'listofvalues', // Audience rule: Param name
                    0 // Expected member count for test.
                );

                 // Default field value, Item1, Save custom field against position.
                 // Set rule, value Starts with '2'
                 // Expect member count of 0
                $data[] = array(
                    'text', // Custom field type
                    null, // Param1 of custom field
                    'Item1', // Position type: Default custom field value
                    true, // Position: Save custom field into position.
                    'Item1', // Position: If being saved, value to save into the position.
                    array('equal' => '4'), // Audience rule: Starts with
                    array('2'), // Audience rule: Value
                    'listofvalues', // Audience rule: Param name
                    0 // Expected member count for test.
                );

            // Rule: Is empty

                 // Default field value, Item1, Save custom field against position.
                 // Set rule, value Is empty
                 // Expect member count of ALL
                $data[] = array(
                    'text', // Custom field type
                    null, // Param1 of custom field
                    'Item1', // Position type: Default custom field value
                    true, // Position: Save custom field into position.
                    '', // Position: If being saved, value to save into the position.
                    array('equal' => '5'), // Audience rule: Is empty
                    array(), // Audience rule: Value
                    'listofvalues', // Audience rule: Param name
                    self::TEST_USER_COUNT_MEMBERS // Expected member count for test.
                );

                 // Default field value, Item1, Save custom field against position.
                 // Set rule, value Is empty
                 // Expect member count of ALL
                $data[] = array(
                    'text', // Custom field type
                    null, // Param1 of custom field
                    'Item1', // Position type: Default custom field value
                    true, // Position: Save custom field into position.
                    null, // Position: If being saved, value to save into the position.
                    array('equal' => '5'), // Audience rule: Is empty
                    array(), // Audience rule: Value
                    'listofvalues', // Audience rule: Param name
                    self::TEST_USER_COUNT_MEMBERS // Expected member count for test.
                );

                 // Default field value, Item1, Save custom field against position.
                 // Set rule, value Is empty
                 // Expect member count of 0
                $data[] = array(
                    'text', // Custom field type
                    null, // Param1 of custom field
                    'Item1', // Position type: Default custom field value
                    true, // Position: Save custom field into position.
                    'Item1', // Position: If being saved, value to save into the position.
                    array('equal' => '5'), // Audience rule: Is empty
                    array(), // Audience rule: Value
                    'listofvalues', // Audience rule: Param name
                    0 // Expected member count for test.
                );

            // Rule: Is not equal to

                 // Default field value, Item1, Save custom field against position.
                 // Set rule, value is not equal to 'Item2'
                 // Expect member count of ALL
                $data[] = array(
                    'text', // Custom field type
                    null, // Param1 of custom field
                    'Item1', // Position type: Default custom field value
                    true, // Position: Save custom field into position.
                    'Item1', // Position: If being saved, value to save into the position.
                    array('equal' => '6'), // Audience rule: not equal to
                    array('Item2'), // Audience rule: Value
                    'listofvalues', // Audience rule: Param name
                    self::TEST_USER_COUNT_MEMBERS // Expected member count for test.
                );

                 // Default field value, Item1, Save custom field against position.
                 // Set rule, value is not equal to 'Item1'
                 // Expect member count of 0
                $data[] = array(
                    'text', // Custom field type
                    null, // Param1 of custom field
                    'Item1', // Position type: Default custom field value
                    true, // Position: Save custom field into position.
                    'Item1', // Position: If being saved, value to save into the position.
                    array('equal' => '6'), // Audience rule: not equal to
                    array('Item1'), // Audience rule: Value
                    'listofvalues', // Audience rule: Param name
                    0 // Expected member count for test.
                );

         // Date/Time tests

             // Save custom field against position.
             // Set rule, date before
             // Expect member count of 0
            $data[] = array(
                'datetime', // Custom field type
                null, // Param1 of custom field
                null, // Position type: Default custom field value
                true, // Position: Save custom field into position.
                strtotime('2015-10-25'), // Position: If being saved, value to save into the position.
                array('operator' => '10'), // Audience rule: date before
                array(strtotime('2015-10-20')), // Audience rule: Value
                'date', // Audience rule: Param name
                0 // Expected member count for test.
            );

             // Save custom field against position.
             // Set rule, date before
             // Expect member count of ALL
            $data[] = array(
                'datetime', // Custom field type
                null, // Param1 of custom field
                null, // Position type: Default custom field value
                true, // Position: Save custom field into position.
                strtotime('2015-10-25'), // Position: If being saved, value to save into the position.
                array('operator' => '10'), // Audience rule: date before
                array(strtotime('2015-10-25')), // Audience rule: Value
                'date', // Audience rule: Param name
                self::TEST_USER_COUNT_MEMBERS  // Expected member count for test.
            );

             // Save custom field against position.
             // Set rule, date before
             // Expect member count of ALL
            $data[] = array(
                'datetime', // Custom field type
                null, // Param1 of custom field
                null, // Position type: Default custom field value
                true, // Position: Save custom field into position.
                strtotime('2015-10-25'), // Position: If being saved, value to save into the position.
                array('operator' => '10'), // Audience rule: date before
                array(strtotime('2015-10-30')), // Audience rule: Value
                'date', // Audience rule: Param name
                self::TEST_USER_COUNT_MEMBERS  // Expected member count for test.
            );

             // Save custom field against position.
             // Set rule, date after
             // Expect member count of ALL
            $data[] = array(
                'datetime', // Custom field type
                null, // Param1 of custom field
                null, // Position type: Default custom field value
                true, // Position: Save custom field into position.
                strtotime('2015-10-25'), // Position: If being saved, value to save into the position.
                array('operator' => '20'), // Audience rule: date after
                array(strtotime('2015-10-20')), // Audience rule: Value
                'date', // Audience rule: Param name
                self::TEST_USER_COUNT_MEMBERS // Expected member count for test.
            );

             // Save custom field against position.
             // Set rule, date after
             // Expect member count of ALL
            $data[] = array(
                'datetime', // Custom field type
                null, // Param1 of custom field
                null, // Position type: Default custom field value
                true, // Position: Save custom field into position.
                strtotime('2015-10-25'), // Position: If being saved, value to save into the position.
                array('operator' => '20'), // Audience rule: date after
                array(strtotime('2015-10-25')), // Audience rule: Value
                'date', // Audience rule: Param name
                self::TEST_USER_COUNT_MEMBERS  // Expected member count for test.
            );

             // Save custom field against position.
             // Set rule, date after
             // Expect member count of 0
            $data[] = array(
                'datetime', // Custom field type
                null, // Param1 of custom field
                null, // Position type: Default custom field value
                true, // Position: Save custom field into position.
                strtotime('2015-10-25'), // Position: If being saved, value to save into the position.
                array('operator' => '20'), // Audience rule: date after
                array(strtotime('2015-10-30')), // Audience rule: Value
                'date', // Audience rule: Param name
                0  // Expected member count for test.
            );

             // Save custom field against position.
             // Set rule, date after
             // Expect member count of 0
            $data[] = array(
                'datetime', // Custom field type
                null, // Param1 of custom field
                null, // Position type: Default custom field value
                true, // Position: Save custom field into position.
                strtotime('2015-10-25'), // Position: If being saved, value to save into the position.
                array('operator' => '20'), // Audience rule: date after
                array(strtotime('2015-10-20')), // Audience rule: Value
                'date', // Audience rule: Param name
                self::TEST_USER_COUNT_MEMBERS  // Expected member count for test.
            );

        return $data;
    }

    /**
     * Test position custom checkbox field.
     * @dataProvider position_custom_fields_params
     */
    public function test_position_custom_fields($customfieldtype, $param1, $defaulfieldvalue, $savefield, $savevalue, $rulepart1, $rulepart2, $paramname, $members) {

        global $DB;
        $this->resetAfterTest(true);
        set_debugging(DEBUG_ALL);
        $this->setAdminUser();

        // Add custom fields to position type.
        $fieldid = $this->add_custom_field('custom_field_test_' . rand(), $this->postype1, $customfieldtype, $param1, $defaulfieldvalue);

        // Create a rule in the Audience.
        $this->cohort_generator->create_cohort_rule_params($this->ruleset, 'alljobassign', 'poscustomfield' . $fieldid, $rulepart1, $rulepart2, $paramname);

        // Save custom field against postition.
        if ($savefield) {
            $newrule = new stdClass();
            $newrule->fieldid = $fieldid;
            $newrule->positionid = $this->pos1->id;
            $newrule->data = $savevalue;
            $DB->insert_record('pos_type_info_data', $newrule);
        }

        // Refresh rule list cache.
        cohort_rules_list(true);

        // Approve changes - build the list of members.
        cohort_rules_approve_changes($this->cohort);

        // Check the members count is correct
        $this->assertEquals($members, $DB->count_records('cohort_members', array('cohortid' => $this->cohort->id)));
    }
}
