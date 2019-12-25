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
 * @author Sam Hemelryk <sam.hemelryk@totaralms.com>
 * @package totara_cohort
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/totara/cohort/lib.php');
require_once($CFG->dirroot . '/user/profile/lib.php');
require_once($CFG->dirroot . '/user/profile/definelib.php');
require_once($CFG->dirroot . '/user/profile/field/menu/define.class.php');

/**
 * Test audience rules.
 *
 * NOTES:
 * - The numbers are coming straight from Totara 2.7.2 which is the baseline for
 *   us, any changes in results need to be documented here.
 * - Updates:
 *   - The original tests used the "correct" comparison enumeration for "equals/
 *     unequals"; the modified tests use the "wrong" version since that what is
 *     really passed from the UI in the current system.
 *   - The original test harness had tests for "contains", "starts with", etc.
 *     These tests have been removed because the patched system just uses equals
 *     and not equals for menu fields.
 */
class totara_cohort_user_custom_profile_field_menu_testcase extends advanced_testcase {

    /**
     * @var totara_cohort_generator The cohort data generator.
     */
    private $cohort_generator = null;
    private $cohort = null;
    private $ruleset = 0;
    /**
     * @var int The ID of the vegetable profile field.
     */
    protected $profilevegetableid;
    const TEST_USER_COUNT_MEMBERS = 53;

    protected function tearDown() {
        $this->cohort_generator = null;
        $this->cohort = null;
        $this->ruleset = null;
        $this->profilevegetableid = null;
        parent::tearDown();
    }

    protected function setUp() {
        global $DB;

        parent::setup();
        $this->resetAfterTest(true);
        $this->setAdminUser();

        $generator = $this->getDataGenerator();

        $options = array(
            '',
            'parsnip',
            'brussels sprout',
            'potato'

        );
        $this->profilevegetableid = $this->add_user_profile_menu_field('vegetable', $options, 'parsnip');

        // Set totara_cohort generator.
        $this->cohort_generator = $generator->get_plugin_generator('totara_cohort');

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

            $user = $generator->create_user($userdata);
            $users[$user->id] = $user;
        }
        $this->assertSame(self::TEST_USER_COUNT_MEMBERS + 2, $DB->count_records('user'));

        // Here we are generating 53 users with a custom profile field called "vegetable" with a default of "parsnip".
        // There is also one 'admin' account with default "parsnip".
        // Guest account should never be assigned to cohort, it is completely ignored here.
        //
        // The following is what we expect:
        //     10 users with 'potato'
        //      9 users with 'brussels sprout'
        //      5 users with ''
        //      7 users with 'parsnip' set explicitly
        //   22+1 users not set so using default 'parsnip' (the 1 is admin)
        // --------------------------------------------------------------
        //     54 total of users that may be assigned to cohort
        //     30 total of users that have 'parsnip' via default or value

        // Set custom field values for some of them.
        reset($users);
        for ($i = 0; $i < 10; $i++) {
            next($users);
            $user = new stdClass;
            $user->id = key($users);
            $user->profile_field_vegetable = '3';
            profile_save_data($user);
            $user = current($users);
            profile_load_custom_fields($user);
            $this->assertSame('potato', $user->profile['vegetable']);
        }
        for ($i = 0; $i < 9; $i++) {
            next($users);
            $user = new stdClass;
            $user->id = key($users);
            $user->profile_field_vegetable = '2';
            profile_save_data($user);
            $user = current($users);
            profile_load_custom_fields($user);
            $this->assertSame('brussels sprout', $user->profile['vegetable']);
        }
        for ($i = 0; $i < 5; $i++) {
            next($users);
            $user = new stdClass;
            $user->id = key($users);
            $user->profile_field_vegetable = '0';
            profile_save_data($user);
            $user = current($users);
            profile_load_custom_fields($user);
            $this->assertSame('', $user->profile['vegetable']);
        }
        for ($i = 0; $i < 7; $i++) {
            next($users);
            $user = new stdClass;
            $user->id = key($users);
            $user->profile_field_vegetable = '1';
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
     * Adds a "menu" custom user profile field.
     *
     * @param string $name
     * @param string $default
     * @return int
     */
    protected function add_user_profile_menu_field($name, array $options, $default) {
        global $DB;
        $formfield = new profile_define_menu();

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
        $data->param1 = join("\n", $options);
        $data->categoryid = $DB->get_field('user_info_category', 'id', array(), IGNORE_MULTIPLE);
        $data->datatype = 'menu';

        $formfield->define_save($data);
        profile_reorder_fields();
        profile_reorder_categories();

        // We need to reset the rules after adding the custom user profile fields.
        cohort_rules_list(true);

        return $DB->get_field('user_info_field', 'id', array('shortname' => $name), IGNORE_MULTIPLE);
    }

    /**
     * Data provider for the menu profile field rule.
     */
    public function data_menu_isequalto() {
        $data = array(
            array(array('potato'), 10),
            array(array('brussels sprout'), 9),
            array(array('carrot'), 0),
            array(array('parsnip'), 30),
            array(array('potato','parsnip'), 40),
            array(array('carrot','tomato'), 0),
            array(array('potato','tomato'), 10),
            array(array('potato','brussels sprout'), 19),
            array(array('brussels sprout','parsnip'), 39),
            array(array('potato','brussels sprout','parsnip'), 49),
        );
        return $data;
    }

    /**
     * Tests the menu profile field and multiple values.
     * @dataProvider data_menu_isequalto
     */
    public function test_menu_isequalto($values, $usercount) {
        global $DB;

        $this->cohort_generator->create_cohort_rule_params(
            $this->ruleset,
            'usercustomfields',
            'customfield'.$this->profilevegetableid.'_0',
            array('equal' => COHORT_RULES_OP_IN_EQUAL),
            $values
        );
        cohort_rules_approve_changes($this->cohort);
        $this->assertEquals($usercount, $DB->count_records('cohort_members', array('cohortid' => $this->cohort->id)));
    }

    /**
     * Data provider for the menu profile field rule.
     */
    public function data_menu_notequalto() {
        $data = array(
            array(array('potato'), 44),
            array(array('brussels sprout'), 45),
            array(array('carrot'), 54),
            array(array('parsnip'), 24),
            array(array('potato','parsnip'), 14),
            array(array('carrot','tomato'), 54),
            array(array('potato','tomato'), 44),
            array(array('brussels sprout','parsnip'), 15),
            array(array('potato','brussels sprout'), 35),
            array(array('potato','brussels sprout','parsnip'), 5),
        );
        return $data;
    }

    /**
     * Tests the menu profile field and multiple values.
     * @dataProvider data_menu_notequalto
     */
    public function test_menu_notequalto($listofvalues, $usercount) {
        global $DB;

        $this->cohort_generator->create_cohort_rule_params(
            $this->ruleset,
            'usercustomfields',
            'customfield'.$this->profilevegetableid.'_0',
            array('equal' => COHORT_RULES_OP_IN_NOTEQUAL),
            $listofvalues
        );
        cohort_rules_approve_changes($this->cohort);
        $this->assertEquals($usercount, $DB->count_records('cohort_members', array('cohortid' => $this->cohort->id)));
    }
}
