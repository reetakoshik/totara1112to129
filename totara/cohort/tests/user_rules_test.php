<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2010 onwards Totara Learning Solutions LTD
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
 * @author Maria Torres <maria.torres@totaralms.com>
 * @package totara
 * @subpackage cohort
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/totara/reportbuilder/tests/reportcache_advanced_testcase.php');
require_once($CFG->dirroot . '/totara/cohort/lib.php');
require_once($CFG->dirroot.'/user/profile/lib.php');
require_once($CFG->dirroot.'/user/profile/definelib.php');
require_once($CFG->dirroot.'/user/profile/field/text/define.class.php');

/**
 * Test user rules.
 *
 * To test, run this from the command line from the $CFG->dirroot
 * vendor/bin/phpunit totara_cohort_user_rules_testcase
 *
 */
class totara_cohort_user_rules_testcase extends reportcache_advanced_testcase {

    /**
     * @var totara_cohort_generator The cohort data generator.
     */
    private $cohort_generator = null;
    private $cohort = null;
    private $ruleset = 0;
    /**
     * @var int The ID of the vegetable profile field.
     */
    private $profilevegetableid;
    const TEST_USER_COUNT_MEMBERS = 53;

    protected function tearDown() {
        $this->cohort_generator = null;
        $this->cohort = null;
        $this->ruleset = null;
        $this->profilevegetableid = null;
        parent::tearDown();
    }

    public function setUp() {
        parent::setup();
        global $DB;
        $this->resetAfterTest(true);
        $this->setAdminUser();
        $userscreated = 0;

        $this->profilevegetableid = $this->add_user_profile_text_field('vegetable', 'parsnip');

        // We need to reset the rules after adding the custom user profile fields.
        cohort_rules_list(true);

        // Set totara_cohort generator.
        $this->cohort_generator = $this->getDataGenerator()->get_plugin_generator('totara_cohort');

        // Create users.
        $users = array();
        for ($i = 1; $i <= self::TEST_USER_COUNT_MEMBERS; $i++) {
            $userdata = array(
                'username' => 'user' . $i,
                'idnumber' => 'USER00' . $i,
                'email' => 'user' . $i . '@123.com',
                'city' => 'Valencia',
                'country' => 'ES',
                'lang' => 'es',
                'institution' => 'UV',
                'department' => 'system',
            );

            if ($i <= 10) {
                $userdata['idnumber'] = 'USERX0' . $i;
            }

            if ($i%2 == 0) {
                $userdata['firstname'] = 'nz_' . $i . '_testuser';
                $userdata['lastname'] = 'NZ FAMILY NAME';
                $userdata['city'] = 'wellington';
                $userdata['country'] = 'NZ';
                $userdata['email'] = 'user' . $i . '@nz.com';
                $userdata['lang'] = 'en';
                $userdata['institution'] = 'Totara';
            }

            $user = $this->getDataGenerator()->create_user($userdata);
            $users[$user->id] = $user;
            $userscreated++;
        }
        $this->assertEquals(self::TEST_USER_COUNT_MEMBERS, $userscreated);

        // Set custom field values for some of them.
        reset($users);
        for ($i = 0; $i < 10; $i++) {
            next($users);
            $user = new stdClass;
            $user->id = key($users);
            $user->profile_field_vegetable = 'potato';
            profile_save_data($user);
            $user = current($users);
            profile_load_custom_fields($user);
            $this->assertSame('potato', $user->profile['vegetable']);
        }
        for ($i = 10; $i < 19; $i++) {
            next($users);
            $user = new stdClass;
            $user->id = key($users);
            $user->profile_field_vegetable = 'brussels sprout';
            profile_save_data($user);
            $user = current($users);
            profile_load_custom_fields($user);
            $this->assertSame('brussels sprout', $user->profile['vegetable']);
        }
        for ($i = 20; $i < 25; $i++) {
            next($users);
            $user = new stdClass;
            $user->id = key($users);
            $user->profile_field_vegetable = '';
            profile_save_data($user);
            $user = current($users);
            profile_load_custom_fields($user);
            $this->assertSame('', $user->profile['vegetable']);
        }
        for ($i = 30; $i < 37; $i++) {
            next($users);
            $user = new stdClass;
            $user->id = key($users);
            $user->profile_field_vegetable = 'parsnip';
            profile_save_data($user);
            $user = current($users);
            profile_load_custom_fields($user);
            $this->assertSame('parsnip', $user->profile['vegetable']);
        }

        // Creating an empty dynamic cohort.
        $this->cohort = $this->cohort_generator->create_cohort(array('cohorttype' => cohort::TYPE_DYNAMIC));
        $this->assertTrue($DB->record_exists('cohort', array('id' => $this->cohort->id)));
        $this->assertEquals(0, $DB->count_records('cohort_members', array('cohortid' => $this->cohort->id)));

        // Creating a ruleset.
        $this->ruleset = cohort_rule_create_ruleset($this->cohort->draftcollectionid);
    }

    /**
     * Adds a "text" custom user profile field.
     *
     * @param string $name
     * @param string $default
     * @return int
     */
    protected function add_user_profile_text_field($name, $default) {
        global $DB;
        $formfield = new profile_define_text();

        if (!$DB->record_exists('user_info_category', array())) {
            // Copied from user/profile/index.php.
            $defaultcategory = new stdClass();
            $defaultcategory->name = 'Default category';
            $defaultcategory->sortorder = 1;

            $DB->insert_record('user_info_category', $defaultcategory);
        }

        $data = new stdClass;
        $data->name = $name;
        $data->shortname = $name;
        $data->descriptionformat = FORMAT_HTML;
        $data->description = 'This custom user profile field was added by unit tests.';
        $data->defaultdataformat = FORMAT_PLAIN;
        $data->defaultdata = $default;
        $data->categoryid = $DB->get_field('user_info_category', 'id', array(), IGNORE_MULTIPLE);
        $data->datatype = 'text';

        $formfield->define_save($data);
        profile_reorder_fields();
        profile_reorder_categories();

        return $DB->get_field('user_info_field', 'id', array('shortname' => $name), IGNORE_MULTIPLE);
    }

    /**
     * Tests the COHORT_RULES_OP_IN_ISEQUALTO operation with custom fields.
     */
    public function test_user_profile_field_isequalto() {
        global $DB;

        // First test a complete value.
        $params = array('equal' => COHORT_RULES_OP_IN_ISEQUALTO);
        $values = array('potato');
        $this->cohort_generator->create_cohort_rule_params($this->ruleset, 'usercustomfields', 'customfield'.$this->profilevegetableid.'_0', $params, $values);
        $ruleparamid = $DB->get_field('cohort_rule_params', 'id', array('value' => 'potato'));
        cohort_rules_approve_changes($this->cohort);
        $this->assertEquals(10, $DB->count_records('cohort_members', array('cohortid' => $this->cohort->id)));

        // Test a value with a space in it.
        $DB->execute('UPDATE {cohort_rule_params} SET value=\'brussels sprout\' WHERE id=\''.$ruleparamid.'\'');
        cohort_rules_approve_changes($this->cohort);
        $this->assertEquals(9, $DB->count_records('cohort_members', array('cohortid' => $this->cohort->id)));

        // Test matching empty string.
        $DB->execute('UPDATE {cohort_rule_params} SET value=\'\' WHERE id=\''.$ruleparamid.'\'');
        cohort_rules_approve_changes($this->cohort);
        $this->assertEquals(5, $DB->count_records('cohort_members', array('cohortid' => $this->cohort->id)));

        // Test a value no one has.
        $DB->execute('UPDATE {cohort_rule_params} SET value=\'tomato\' WHERE id=\''.$ruleparamid.'\'');
        cohort_rules_approve_changes($this->cohort);
        $this->assertEquals(0, $DB->count_records('cohort_members', array('cohortid' => $this->cohort->id)));

        /**
         * This will be fixed by TL-6578
        // Now test the default, we manually set 7 people to the same value as the default.
        // There are 54 real users total in the system (55 if you counted the guest).
        // 24 users have an actual value that isn't the default.
        // There must be 54 - 24 = 30 users with the default value.
        $DB->execute('UPDATE {cohort_rule_params} SET value=\'parsnip\' WHERE id=\''.$ruleparamid.'\'');
        cohort_rules_approve_changes($this->cohort);
        $this->assertEquals(30, $DB->count_records('cohort_members', array('cohortid' => $this->cohort->id)));
         */
    }

    /**
     * Tests the COHORT_RULES_OP_IN_CONTAINS operation with custom fields.
     */
    public function test_user_profile_field_contains() {
        global $DB;

        // First test the complete string.
        $params = array('equal' => COHORT_RULES_OP_IN_CONTAINS);
        $values = array('potato');
        $this->cohort_generator->create_cohort_rule_params($this->ruleset, 'usercustomfields', 'customfield'.$this->profilevegetableid.'_0', $params, $values);
        $ruleparamid = $DB->get_field('cohort_rule_params', 'id', array('value' => 'potato'));
        cohort_rules_approve_changes($this->cohort);
        $this->assertEquals(10, $DB->count_records('cohort_members', array('cohortid' => $this->cohort->id)));

        // Now change it to brussels sprout and update.
        $DB->execute('UPDATE {cohort_rule_params} SET value=\'brussels sprout\' WHERE id=\''.$ruleparamid.'\'');
        cohort_rules_approve_changes($this->cohort);
        $this->assertEquals(9, $DB->count_records('cohort_members', array('cohortid' => $this->cohort->id)));

        // Now test matching a partial string.
        $DB->execute('UPDATE {cohort_rule_params} SET value=\'sse\' WHERE id=\''.$ruleparamid.'\'');
        cohort_rules_approve_changes($this->cohort);
        $this->assertEquals(9, $DB->count_records('cohort_members', array('cohortid' => $this->cohort->id)));

        /**
         * This will be fixed by TL-6578
        // Test the default, we manually set 7 people to the same value as the default.
        // There are 54 real users total in the system (55 if you counted the guest).
        // 24 users have an actual value that isn't the default.
        // There must be 54 - 24 = 30 users with the default value.
        $DB->execute('UPDATE {cohort_rule_params} SET value=\'parsnip\' WHERE id=\''.$ruleparamid.'\'');
        cohort_rules_approve_changes($this->cohort);
        $this->assertEquals(30, $DB->count_records('cohort_members', array('cohortid' => $this->cohort->id)));

        // Test an empty string, this should return EVERYONE!
        // There are 54 real users (53 generated + the admin).
        $DB->execute('UPDATE {cohort_rule_params} SET value=\'\' WHERE id=\''.$ruleparamid.'\'');
        cohort_rules_approve_changes($this->cohort);
        $this->assertEquals(54, $DB->count_records('cohort_members', array('cohortid' => $this->cohort->id)));
         */
    }

    /**
     * Tests the COHORT_RULES_OP_IN_STARTSWITH operation with custom fields.
     */
    public function test_user_profile_field_startswith() {
        global $DB;

        // First test the complete string.
        $params = array('equal' => COHORT_RULES_OP_IN_STARTSWITH);
        $values = array('potato');
        $this->cohort_generator->create_cohort_rule_params($this->ruleset, 'usercustomfields', 'customfield'.$this->profilevegetableid.'_0', $params, $values);
        $ruleparamid = $DB->get_field('cohort_rule_params', 'id', array('value' => 'potato'));
        cohort_rules_approve_changes($this->cohort);
        $this->assertEquals(10, $DB->count_records('cohort_members', array('cohortid' => $this->cohort->id)));

        // Now change it to brussels sprout and update.
        $DB->execute('UPDATE {cohort_rule_params} SET value=\'brussels sprout\' WHERE id=\''.$ruleparamid.'\'');
        cohort_rules_approve_changes($this->cohort);
        $this->assertEquals(9, $DB->count_records('cohort_members', array('cohortid' => $this->cohort->id)));

        // Now test matching a starting string we have.
        $DB->execute('UPDATE {cohort_rule_params} SET value=\'bru\' WHERE id=\''.$ruleparamid.'\'');
        cohort_rules_approve_changes($this->cohort);
        $this->assertEquals(9, $DB->count_records('cohort_members', array('cohortid' => $this->cohort->id)));

        // Now test matching a starting string we don't have but that would match middle of the string.
        $DB->execute('UPDATE {cohort_rule_params} SET value=\'sse\' WHERE id=\''.$ruleparamid.'\'');
        cohort_rules_approve_changes($this->cohort);
        $this->assertEquals(0, $DB->count_records('cohort_members', array('cohortid' => $this->cohort->id)));

        // Now test matching a starting string we don't have but that would match end of the string.
        $DB->execute('UPDATE {cohort_rule_params} SET value=\'out\' WHERE id=\''.$ruleparamid.'\'');
        cohort_rules_approve_changes($this->cohort);
        $this->assertEquals(0, $DB->count_records('cohort_members', array('cohortid' => $this->cohort->id)));

        /**
         * This will be fixed by TL-6578
        // Test a partial default, we manually set 7 people to the same value as the default.
        // There are 54 real users total in the system (55 if you counted the guest).
        // 24 users have an actual value that isn't the default.
        // There must be 54 - 24 = 30 users with the default value.
        $DB->execute('UPDATE {cohort_rule_params} SET value=\'par\' WHERE id=\''.$ruleparamid.'\'');
        cohort_rules_approve_changes($this->cohort);
        $this->assertEquals(30, $DB->count_records('cohort_members', array('cohortid' => $this->cohort->id)));

        // Test an empty string, this should return EVERYONE!
        // There are 54 real users (53 generated + the admin).
        $DB->execute('UPDATE {cohort_rule_params} SET value=\'\' WHERE id=\''.$ruleparamid.'\'');
        cohort_rules_approve_changes($this->cohort);
        $this->assertEquals(54, $DB->count_records('cohort_members', array('cohortid' => $this->cohort->id)));
         */
    }

    /**
     * Tests the COHORT_RULES_OP_IN_ENDSWITH operation with custom fields.
     */
    public function test_user_profile_field_endswith() {
        global $DB;

        // First test the complete string.
        $params = array('equal' => COHORT_RULES_OP_IN_ENDSWITH);
        $values = array('potato');
        $this->cohort_generator->create_cohort_rule_params($this->ruleset, 'usercustomfields', 'customfield'.$this->profilevegetableid.'_0', $params, $values);
        $ruleparamid = $DB->get_field('cohort_rule_params', 'id', array('value' => 'potato'));
        cohort_rules_approve_changes($this->cohort);
        $this->assertEquals(10, $DB->count_records('cohort_members', array('cohortid' => $this->cohort->id)));

        // Now change it to brussels sprout and update.
        $DB->execute('UPDATE {cohort_rule_params} SET value=\'brussels sprout\' WHERE id=\''.$ruleparamid.'\'');
        cohort_rules_approve_changes($this->cohort);
        $this->assertEquals(9, $DB->count_records('cohort_members', array('cohortid' => $this->cohort->id)));

        // Now test matching a ending string we have.
        $DB->execute('UPDATE {cohort_rule_params} SET value=\'out\' WHERE id=\''.$ruleparamid.'\'');
        cohort_rules_approve_changes($this->cohort);
        $this->assertEquals(9, $DB->count_records('cohort_members', array('cohortid' => $this->cohort->id)));

        // Now test matching a ending string we don't have but that would match middle of the string.
        $DB->execute('UPDATE {cohort_rule_params} SET value=\'sse\' WHERE id=\''.$ruleparamid.'\'');
        cohort_rules_approve_changes($this->cohort);
        $this->assertEquals(0, $DB->count_records('cohort_members', array('cohortid' => $this->cohort->id)));

        // Now test matching a ending string we don't have but that would match start of the string.
        $DB->execute('UPDATE {cohort_rule_params} SET value=\'bru\' WHERE id=\''.$ruleparamid.'\'');
        cohort_rules_approve_changes($this->cohort);
        $this->assertEquals(0, $DB->count_records('cohort_members', array('cohortid' => $this->cohort->id)));

        /**
         * This will be fixed by TL-6578
        // Test a partial default, we manually set 7 people to the same value as the default.
        // There are 54 real users total in the system (55 if you counted the guest).
        // 24 users have an actual value that isn't the default.
        // There must be 54 - 24 = 30 users with the default value.
        $DB->execute('UPDATE {cohort_rule_params} SET value=\'nip\' WHERE id=\''.$ruleparamid.'\'');
        cohort_rules_approve_changes($this->cohort);
        $this->assertEquals(30, $DB->count_records('cohort_members', array('cohortid' => $this->cohort->id)));

        // Test an empty string, this should return EVERYONE!
        // There are 54 real users (53 generated + the admin).
        $DB->execute('UPDATE {cohort_rule_params} SET value=\'\' WHERE id=\''.$ruleparamid.'\'');
        cohort_rules_approve_changes($this->cohort);
        $this->assertEquals(54, $DB->count_records('cohort_members', array('cohortid' => $this->cohort->id)));
         */
    }

    /**
     * Tests the COHORT_RULES_OP_IN_NOTCONTAIN operation with custom fields.
     */
    public function test_user_profile_field_notcontain() {
        global $DB;

        // First test the complete string with one we know exists.
        // 10 people have the value so there should be 44 who pass this condition.
        $params = array('equal' => COHORT_RULES_OP_IN_NOTCONTAIN);
        $values = array('potato');
        $this->cohort_generator->create_cohort_rule_params($this->ruleset, 'usercustomfields', 'customfield'.$this->profilevegetableid.'_0', $params, $values);
        $ruleparamid = $DB->get_field('cohort_rule_params', 'id', array('value' => 'potato'));
        cohort_rules_approve_changes($this->cohort);
        $this->assertEquals(44, $DB->count_records('cohort_members', array('cohortid' => $this->cohort->id)));

        // Now change it to brussels sprout and update.
        // There should be 9 people with so 45 without.
        $DB->execute('UPDATE {cohort_rule_params} SET value=\'brussels sprout\' WHERE id=\''.$ruleparamid.'\'');
        cohort_rules_approve_changes($this->cohort);
        $this->assertEquals(45, $DB->count_records('cohort_members', array('cohortid' => $this->cohort->id)));

        // Now test matching a string we don't have.
        // There should be all 54 users here.
        $DB->execute('UPDATE {cohort_rule_params} SET value=\'dinosaur\' WHERE id=\''.$ruleparamid.'\'');
        cohort_rules_approve_changes($this->cohort);
        $this->assertEquals(54, $DB->count_records('cohort_members', array('cohortid' => $this->cohort->id)));

        /**
         * This will be fixed by TL-6578
        // Test the default. There are 24 people with a value that is not the default.
        // That leaves 30 people who should pass this test and be included in the cohort.
        $DB->execute('UPDATE {cohort_rule_params} SET value=\'parsnip\' WHERE id=\''.$ruleparamid.'\'');
        cohort_rules_approve_changes($this->cohort);
        $this->assertEquals(30, $DB->count_records('cohort_members', array('cohortid' => $this->cohort->id)));

        // Test an empty string, there are 5 users with an empty string.
        $DB->execute('UPDATE {cohort_rule_params} SET value=\'\' WHERE id=\''.$ruleparamid.'\'');
        cohort_rules_approve_changes($this->cohort);
        $this->assertEquals(5, $DB->count_records('cohort_members', array('cohortid' => $this->cohort->id)));
         */
    }

    /**
     * Tests the COHORT_RULES_OP_IN_NOTEQUALTO operation with custom fields.
     */
    public function test_user_profile_field_notequalto() {
        global $DB;

        // First test the complete string with one we know exists.
        // 10 people have the value so there should be 44 who pass this condition.
        $params = array('equal' => COHORT_RULES_OP_IN_NOTEQUALTO);
        $values = array('potato');
        $this->cohort_generator->create_cohort_rule_params($this->ruleset, 'usercustomfields', 'customfield'.$this->profilevegetableid.'_0', $params, $values);
        $ruleparamid = $DB->get_field('cohort_rule_params', 'id', array('value' => 'potato'));
        cohort_rules_approve_changes($this->cohort);
        $this->assertEquals(44, $DB->count_records('cohort_members', array('cohortid' => $this->cohort->id)));

        // Now change it to brussels sprout and update.
        // There should be 9 people with so 45 without.
        $DB->execute('UPDATE {cohort_rule_params} SET value=\'brussels sprout\' WHERE id=\''.$ruleparamid.'\'');
        cohort_rules_approve_changes($this->cohort);
        $this->assertEquals(45, $DB->count_records('cohort_members', array('cohortid' => $this->cohort->id)));

        // Now test matching a string we don't have.
        // There should be all 54 users here.
        $DB->execute('UPDATE {cohort_rule_params} SET value=\'dinosaur\' WHERE id=\''.$ruleparamid.'\'');
        cohort_rules_approve_changes($this->cohort);
        $this->assertEquals(54, $DB->count_records('cohort_members', array('cohortid' => $this->cohort->id)));

        /**
         * This will be fixed by TL-6578
        // Test the default. There are 24 people with a value that is not the default.
        // That leaves 30 people who should pass this test and be included in the cohort.
        $DB->execute('UPDATE {cohort_rule_params} SET value=\'parsnip\' WHERE id=\''.$ruleparamid.'\'');
        cohort_rules_approve_changes($this->cohort);
        $this->assertEquals(30, $DB->count_records('cohort_members', array('cohortid' => $this->cohort->id)));

        // Test an empty string, there are 5 users with an empty string.
        $DB->execute('UPDATE {cohort_rule_params} SET value=\'\' WHERE id=\''.$ruleparamid.'\'');
        cohort_rules_approve_changes($this->cohort);
        $this->assertEquals(5, $DB->count_records('cohort_members', array('cohortid' => $this->cohort->id)));
         */
    }

    /**
     * Tests the COHORT_RULES_OP_IN_ISEMPTY operation with custom fields.
     */
    public function test_user_profile_field_is_empty() {
        global $DB;

        /**
         * This will be fixed by TL-6578
        // Test getting a custom field with an empty string.
        // There are 5 users who have a value set to ''.
        // The 30 users without a value essentially have the default value.
        $params = array('equal' => COHORT_RULES_OP_IN_ISEMPTY);
        $values = array();
        $this->cohort_generator->create_cohort_rule_params($this->ruleset, 'usercustomfields', 'customfield'.$this->profilevegetableid.'_0', $params, $values);
        cohort_rules_approve_changes($this->cohort);
        $this->assertEquals(5, $DB->count_records('cohort_members', array('cohortid' => $this->cohort->id)));
         */
    }

    /**
     * Data provider for the idnumber rule.
     */
    public function data_id_number() {
        $data = array(
            array(array('equal' => COHORT_RULES_OP_IN_CONTAINS), array('USER00'), 43),
            array(array('equal' => COHORT_RULES_OP_IN_CONTAINS), array('USERX0'), 10),
            array(array('equal' => COHORT_RULES_OP_IN_NOTCONTAIN), array('USER00'), 11),
            array(array('equal' => COHORT_RULES_OP_IN_STARTSWITH), array('USER'), 53),
        );
        return $data;
    }

    /**
     * Test the idnumber text rule.
     * @dataProvider data_id_number
     */
    public function test_idnumber_text_rule($params, $listofvalues, $usercount) {
        global $DB;
        $this->resetAfterTest(true);
        $this->setAdminUser();

        $this->cohort_generator->create_cohort_rule_params($this->ruleset, 'user', 'idnumber', $params, $listofvalues);
        cohort_rules_approve_changes($this->cohort);
        $this->assertEquals($usercount, $DB->count_records('cohort_members', array('cohortid' => $this->cohort->id)));
    }

    /**
     * Data provider for the username rule.
     */
    public function data_username() {
        $data = array(
            array(array('equal' => COHORT_RULES_OP_IN_STARTSWITH), array('user'), 53),
            array(array('equal' => COHORT_RULES_OP_IN_ISEQUALTO), array('user1'), 1),
            array(array('equal' => COHORT_RULES_OP_IN_ISEMPTY), array(), 0), // No user can have an empty username.
        );
        return $data;
    }

    /**
     * Test the username text rule.
     * @dataProvider data_username
     */
    public function test_username_text_rule($params, $listofvalues, $usercount) {
        global $DB;
        $this->resetAfterTest(true);
        $this->setAdminUser();

        // Create a username rule.
        $this->cohort_generator->create_cohort_rule_params($this->ruleset, 'user', 'username', $params, $listofvalues);
        cohort_rules_approve_changes($this->cohort);
        $this->assertEquals($usercount, $DB->count_records('cohort_members', array('cohortid' => $this->cohort->id)));
    }

    /**
     * Data provider for email rule.
     */
    public function data_email() {
        $data = array(
            array(array('equal' => COHORT_RULES_OP_IN_CONTAINS), array('@nz.com'), 26),
            array(array('equal' => COHORT_RULES_OP_IN_CONTAINS), array('@123'), 27),
            array(array('equal' => COHORT_RULES_OP_IN_ISEMPTY), array(), 0),
        );
        return $data;
    }

    /**
     * Test the email text rule.
     * @dataProvider data_email
     */
    public function test_email_text_rule($params, $listofvalues, $usercount) {
        global $DB;
        $this->resetAfterTest(true);
        $this->setAdminUser();

        // Create an email rule.
        $this->cohort_generator->create_cohort_rule_params($this->ruleset, 'user', 'email', $params, $listofvalues);
        cohort_rules_approve_changes($this->cohort);
        $this->assertEquals($usercount, $DB->count_records('cohort_members', array('cohortid' => $this->cohort->id)));
    }

    /**
     * Data provider for firstname rule.
     */
    public function data_firstname() {
        $data = array(
            array(array('equal' => COHORT_RULES_OP_IN_ENDSWITH), array('testuser'), 26),
        );
        return $data;
    }

    /**
     * Test the firstname text rule.
     * @dataProvider data_firstname
     */
    public function test_firstname_text_rule($params, $listofvalues, $usercount) {
        global $DB;
        $this->resetAfterTest(true);
        $this->setAdminUser();

        // Create a firstname rule.
        $this->cohort_generator->create_cohort_rule_params($this->ruleset, 'user', 'firstname', $params, $listofvalues);
        cohort_rules_approve_changes($this->cohort);
        $this->assertEquals($usercount, $DB->count_records('cohort_members', array('cohortid' => $this->cohort->id)));
    }

    /**
     * Data provider for lastname rule.
     */
    public function data_lastname() {
        $data = array(
            array(array('equal' => COHORT_RULES_OP_IN_ENDSWITH), array('NZ FAMILY NAME'), 26),
        );
        return $data;
    }

    /**
     * Test the lastname text rule.
     * @dataProvider data_lastname
     */
    public function test_lastname_text_rule($params, $listofvalues, $usercount) {
        global $DB;
        $this->resetAfterTest(true);
        $this->setAdminUser();

        // Create a lastname rule.
        $this->cohort_generator->create_cohort_rule_params($this->ruleset, 'user', 'lastname', $params, $listofvalues);
        cohort_rules_approve_changes($this->cohort);
        $this->assertEquals($usercount, $DB->count_records('cohort_members', array('cohortid' => $this->cohort->id)));
    }

    /**
     * Data provider for city rule.
     */
    public function data_city() {
        $data = array(
            array(array('equal' => COHORT_RULES_OP_IN_ISEQUALTO), array('Valencia'), 27),
        );
        return $data;
    }

    /**
     * Test the city text rule.
     * @dataProvider data_city
     */
    public function test_city_text_rule($params, $listofvalues, $usercount) {
        global $DB;
        $this->resetAfterTest(true);
        $this->setAdminUser();

        // Create a city rule.
        $this->cohort_generator->create_cohort_rule_params($this->ruleset, 'user', 'city', $params, $listofvalues);
        cohort_rules_approve_changes($this->cohort);
        $this->assertEquals($usercount, $DB->count_records('cohort_members', array('cohortid' => $this->cohort->id)));
    }

    /**
     * Data provider for institution rule.
     */
    public function data_institution() {
        $data = array(
            array(array('equal' => COHORT_RULES_OP_IN_ISEQUALTO), array('UV'), 27),
        );
        return $data;
    }

    /**
     * Test the institution text rule.
     * @dataProvider data_institution
     */
    public function test_institution_text_rule($params, $listofvalues, $usercount) {
        global $DB;
        $this->resetAfterTest(true);
        $this->setAdminUser();

        // Create an institution rule.
        $this->cohort_generator->create_cohort_rule_params($this->ruleset, 'user', 'institution', $params, $listofvalues);
        cohort_rules_approve_changes($this->cohort);
        $this->assertEquals($usercount, $DB->count_records('cohort_members', array('cohortid' => $this->cohort->id)));
    }

    /**
     * Data provider for department rule.
     */
    public function data_department() {
        $data = array(
            array(array('equal' => COHORT_RULES_OP_IN_NOTEQUALTO), array('system'), 1),
            array(array('equal' => COHORT_RULES_OP_IN_ISEQUALTO), array('system'), 53),
        );
        return $data;
    }

    /**
     * Test the department text rule.
     * @dataProvider data_department
     */
    public function test_department_text_rule($params, $listofvalues, $usercount) {
        global $DB;
        $this->resetAfterTest(true);
        $this->setAdminUser();

        // Add a rule with department not equal to system. It should not match any of the users created.
        $this->cohort_generator->create_cohort_rule_params($this->ruleset, 'user', 'department', $params, $listofvalues);
        cohort_rules_approve_changes($this->cohort);
        $this->assertEquals($usercount, $DB->count_records('cohort_members', array('cohortid' => $this->cohort->id)));
    }
}
