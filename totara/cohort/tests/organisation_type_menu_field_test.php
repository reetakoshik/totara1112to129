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
 * @author Murali Nair <murali.nair@totaralms.com>
 * @package totara_cohort
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/totara/cohort/lib.php');


class totara_cohort_organisation_type_menu_field_testcase
extends advanced_testcase {
    /**
     * Cohort test generator.
     */
    private $cohorts = null;

    /**
     * Dynamic audience under test.
     */
    private $audience = null;

    /**
     * Dynamic audience ruleset.
     */
    private $rules = null;

    /**
     * Custom field details.
     */
    private $custom_field = null;

    /**
     * No of users assigned to the organization type with the 1st custom menu
     * field value.
     */
    private $number_assigned = 10;


    protected function tearDown() {
        $this->cohorts = null;
        $this->audience = null;
        $this->rules = null;
        $this->custom_field = null;
        $this->number_assigned = null;
        parent::tearDown();
    }

    /**
     * Convenience function to return an existing record with the specified
     * field value from the specified table
     *
     * @param table table to query.
     * @param field name of database field on which to query.
     * @param value field value to match.
     *
     * @return the retrieved record.
     */
    private function get_record( $table, $field, $value ) {
        global $DB;
        $parms = array($field => $value);
        $this->assertTrue($DB->record_exists($table, $parms));

        return $DB->get_record($table, $parms, '*', MUST_EXIST);
    }

    /**
     * Convenience function to count the number of members in an audience.
     *
     * @param id id of cohort to look up.
     * @param expected expected number of members in the audience.
     */
    private function audience_count( $id, $expected ) {
        global $DB;
        $parms = array('cohortid' => $id);
        $actual = $DB->count_records('cohort_members', $parms);

        $this->assertEquals($expected, $actual, "Wrong number of members");
    }


    /**
     * PhpUnit fixture method that runs after the test method executes.
     */
    protected function setUp() {
        parent::setup();
        $this->resetAfterTest(true);
        $this->setAdminUser();

        $generator = $this->getDataGenerator();
        $this->cohorts = $generator->get_plugin_generator('totara_cohort');

        $hierarchies = $generator->get_plugin_generator('totara_hierarchy');
        $org_type = $this->get_record(
            'org_type', 'id', $hierarchies->create_org_type()
        );

        $hierarchies->create_hierarchy_type_generic_menu(
            array(
                'hierarchy' => 'organisation',
                'value' => 'IT,Fin',
                'typeidnumber' => $org_type->idnumber
            )
        );
        $this->custom_field = $this->get_record(
            'org_type_info_field', 'shortname', 'menu'.$org_type->id
        );

        $org = $hierarchies->create_org(
            array('frameworkid' => $hierarchies->create_org_frame(null)->id)
        );
        $hierarchies->create_hierarchy_type_assign(
            array(
                'hierarchy'    => 'organisation',
                'idnumber'     => $org->idnumber,
                'typeidnumber' => $org_type->idnumber,
                'field'        => 'menu',
                'value'        => '0'
            )
        );

        for ($i = 1; $i <= $this->number_assigned; $i++) {
            $user = $generator->create_user(
                array(
                    'username'  => "user$i",
                    'idnumber'  => "100$i",
                    'firstname' => "firstname_$i",
                    'lastname'  => "lastname_$i",
                    'email'  =>    "user$i@erewhon.com"
                )
            );

            \totara_job\job_assignment::create_default($user->id, array('organisationid' => $org->id));
        }

        cohort_rules_list(true);
        $cohort = $this->cohorts->create_cohort(
            array('cohorttype' => cohort::TYPE_DYNAMIC)
        );
        $this->audience = $this->get_record('cohort', 'id', $cohort->id);

        $rule_id = cohort_rule_create_ruleset(
            $this->audience->draftcollectionid
        );
        $this->rules = $this->get_record('cohort_rulesets', 'id', $rule_id);
    }

    /**
     * Tests dynamic audience behavior with org type equal to a custom field
     * value.
     */
    public function test_equals() {
        $this->cohorts->create_cohort_rule_params(
            $this->rules->id,
            'alljobassign',
            'orgcustomfield'.$this->custom_field->id,
            array('equal' => COHORT_RULES_OP_IN_EQUAL),
            array('IT') // 1st menu option.
        );

        cohort_rules_approve_changes($this->audience);
        $this->audience_count($this->audience->id, $this->number_assigned);
    }

    /**
     * Tests dynamic audience behavior with org type not equal to a custom field
     * value.
     */
    public function test_not_equals() {
        $this->cohorts->create_cohort_rule_params(
            $this->rules->id,
            'alljobassign',
            'orgcustomfield'.$this->custom_field->id,
            array('equal' => COHORT_RULES_OP_IN_NOTEQUAL),
            array('IT') // 1st menu option.
        );

        cohort_rules_approve_changes($this->audience);
        $this->audience_count($this->audience->id, 0);
    }
}
