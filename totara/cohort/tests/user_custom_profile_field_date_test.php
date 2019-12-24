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
 * @author Brendan Cox <brendan.cox@totaralms.com>
 * @package totara_cohort
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/totara/cohort/lib.php');
require_once($CFG->dirroot . '/user/profile/lib.php');
require_once($CFG->dirroot . '/user/profile/definelib.php');
require_once($CFG->dirroot . '/user/profile/field/datetime/define.class.php');

/**
 * Tests usage of the functions and operators used for rules that define audience membership via the custom user profile field of
 * date (without timezone)
 */

class totara_cohort_user_custom_profile_field_date_testcase extends advanced_testcase {
    // The custom profile fields to be used in rules
    protected $profiledate1;
    protected $profiledate2;
    protected $profiledate3;

    // To add an array of users
    protected $users;

    protected $cohort_generator;

    protected function tearDown() {
        $this->profiledate1 = null;
        $this->profiledate2 = null;
        $this->profiledate3 = null;
        $this->users = null;
        $this->cohort_generator = null;
        parent::tearDown();
    }

    public function setUp() {
        $this->resetAfterTest();
        parent::setup();

        $this->cohort_generator = $this->getDataGenerator()->get_plugin_generator('totara_cohort');

        // Adding custom profile fields using 'Date/Time'.
        $this->profiledate1 = $this->add_user_profile_date_field('date1', 0);
    }

    /**
     * @param string $name name of the custom field
     * @param int $default default value
     * @return string id of profile field just created
     */
    protected function add_user_profile_date_field($name, $default) {
        global $DB;
        $formfield = new profile_define_datetime();

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
        $data->datatype = 'datetime';
        $data->param1 = '2015';
        $data->param2 = '2016';
        $data->startyear = 2015;
        $data->startmonth = 1;
        $data->startday = 1;
        $data->endyear = 2016;
        $data->endmonth = 12;
        $data->endday = 31;
        $data->param3 = '1';

        $formfield->define_save($data);
        profile_reorder_fields();
        profile_reorder_categories();

        // We need to reset the rules after adding the custom user profile fields.
        cohort_rules_list(true);

        return $DB->get_field('user_info_field', 'id', array('shortname' => $name), IGNORE_MULTIPLE);
    }

    /**
     * Create users that have values within the custom profile fields and add these to $this->users
     */
    protected function create_users() {
        $generator = $this->getDataGenerator();

        $users = array();

        $users[0] = $generator->create_user();
        $users[0]->profile_field_date1 = 1482623999;   // 2016/12/24 23:59:59 UTC.
        profile_save_data($users[0]);
        profile_load_custom_fields($users[0]);

        $users[1] = $generator->create_user();
        $users[1]->profile_field_date1 = 1482624000;   // 2016/12/25 00:00:00 UTC.
        profile_save_data($users[1]);
        profile_load_custom_fields($users[1]);

        $users[2] = $generator->create_user();
        $users[2]->profile_field_date1 = 1482624001;  // 2016/12/25 00:00:01 UTC.
        profile_save_data($users[2]);
        profile_load_custom_fields($users[2]);

        // all timestamps should convert to midday when using UTC timezone. Checking a selection of dates for this
        $this->assertContains('23:59:59', userdate($users[0]->profile_field_date1, '%H:%M:%S', 'UTC'));
        $this->assertContains('00:00:00', userdate($users[1]->profile_field_date1, '%H:%M:%S', 'UTC'));
        $this->assertContains('00:00:01', userdate($users[2]->profile_field_date1, '%H:%M:%S', 'UTC'));

        $this->users = $users;
    }

    /**
     * Function that uses get_sql_snippet for condition to return users with id only
     *
     * @param int $profilefieldid id of profile field we are using for the rule
     * @param int $operator such as COHORT_RULE_DATE_OP_BEFORE_FIXED_DATE
     * @param int $date timestamp or number of days
     * @param int $now timestamp to be considered as now for the test. Null means time() will be used in get_sql_snippet().
     * @return array of records containing userids
     */
    protected function query_with_get_sql_snippet_date($profilefieldid, $operator, $date, $now = null) {
        global $DB;

        $sqlhandler = new cohort_rule_sqlhandler_date_usercustomfield($profilefieldid);
        $sqlhandler->operator = $operator;
        $sqlhandler->date = $date;

        $sqlhandlersnippet = $sqlhandler->get_sql_snippet($now);

        // get_sql_snippet() returns sql to append to where clause, so building very basic preliminary sql to attach it to
        $sql = "SELECT id
                FROM {user} u
                WHERE " . $sqlhandlersnippet->sql;

        return $DB->get_records_sql($sql, $sqlhandlersnippet->params);
    }

    /**
     * This tests the function get_sql_snippet() specifically in order to check that elements such as operators are used correctly.
     */
    public function test_get_sql_snippet() {
        $this->resetAfterTest();
        $this->create_users();

        // Testing operators for before and after fixed date  in various combinations.
        $time = 1482624002;  // 2016/12/25 00:00:02 UTC.
        $userids = $this->query_with_get_sql_snippet_date($this->profiledate1, COHORT_RULE_DATE_OP_BEFORE_FIXED_DATE, $time);
        $this->assertArrayHasKey($this->users[0]->id, $userids);
        $this->assertArrayHasKey($this->users[1]->id, $userids);
        $this->assertArrayHasKey($this->users[2]->id, $userids);

        $time = 1482623999;  // 2016/12/24 23:59:59 UTC.
        $userids = $this->query_with_get_sql_snippet_date($this->profiledate1, COHORT_RULE_DATE_OP_BEFORE_FIXED_DATE, $time);
        $this->assertArrayHasKey($this->users[0]->id, $userids);
        $this->assertArrayNotHasKey($this->users[1]->id, $userids);
        $this->assertArrayNotHasKey($this->users[2]->id, $userids);

        $time = 1482624000; // 2016/12/25 00:00:00 UTC.
        $userids = $this->query_with_get_sql_snippet_date($this->profiledate1, COHORT_RULE_DATE_OP_AFTER_FIXED_DATE, $time);
        $this->assertArrayNotHasKey($this->users[0]->id, $userids);
        $this->assertArrayHasKey($this->users[1]->id, $userids);
        $this->assertArrayHasKey($this->users[2]->id, $userids);

        $time = -307108800; // 08/04/1960 at 12:00:00 UTC.
        $userids = $this->query_with_get_sql_snippet_date($this->profiledate1, COHORT_RULE_DATE_OP_AFTER_FIXED_DATE, $time);
        $this->assertArrayHasKey($this->users[0]->id, $userids);
        $this->assertArrayHasKey($this->users[1]->id, $userids);
        $this->assertArrayHasKey($this->users[2]->id, $userids);
    }

    /**
     * Really this is supposed to test get_sql_snippet(), but from a higher level to ensure
     * rules created will work correctly with other code related to updating of audiences.
     * We can only test before/after fixed dates as we can't change $now in get_sql_snippet()
     * when testing from a higher level.
     */
    public function test_update_dynamic_cohort_members()
    {
        $this->resetAfterTest();
        $this->create_users();

        $audience1 = $this->cohort_generator->create_cohort(array('cohorttype' => cohort::TYPE_DYNAMIC));
        $this->assertEmpty(totara_get_members_cohort($audience1->id));

        // Trying a combination of rules.
        // The rules below are added incrementally without removing them before adding the next.

        $date = 1482624000; // 2016/12/25 00:00:00 UTC.
        // Create the empty records for the rule
        $rulesetid = cohort_rule_create_ruleset($audience1->draftcollectionid);
        $rulegroup = 'usercustomfields';
        $rulename = 'customfield' . $this->profiledate1 . '_0';
        $ruleinstanceid = cohort_rule_create_rule($rulesetid, $rulegroup, $rulename);

        // update rule with sqlhandler class
        $sqlhandler = new cohort_rule_sqlhandler_date_usercustomfield($this->profiledate1);
        $sqlhandler->operator = COHORT_RULE_DATE_OP_AFTER_FIXED_DATE;
        $sqlhandler->date = $date;
        $sqlhandler->fetch($ruleinstanceid);
        $sqlhandler->write();

        cohort_rules_approve_changes($audience1);

        $audiencemembers = totara_get_members_cohort($audience1->id);
        $this->assertArrayNotHasKey($this->users[0]->id, $audiencemembers);
        $this->assertArrayHasKey($this->users[1]->id, $audiencemembers);
        $this->assertArrayHasKey($this->users[2]->id, $audiencemembers);

        $date = 1482624000; // 2016/12/25 00:00:00 UTC.
        // Create the empty records for the rule
        $rulesetid = cohort_rule_create_ruleset($audience1->draftcollectionid);
        $rulegroup = 'usercustomfields';
        $rulename = 'customfield' . $this->profiledate1 . '_0';
        $ruleinstanceid = cohort_rule_create_rule($rulesetid, $rulegroup, $rulename);

        // update rule with sqlhandler class
        $sqlhandler = new cohort_rule_sqlhandler_date_usercustomfield($this->profiledate1);
        $sqlhandler->operator = COHORT_RULE_DATE_OP_BEFORE_FIXED_DATE;
        $sqlhandler->date = $date;
        $sqlhandler->fetch($ruleinstanceid);
        $sqlhandler->write();

        cohort_rules_approve_changes($audience1);

        $audiencemembers = totara_get_members_cohort($audience1->id);
        $this->assertArrayNotHasKey($this->users[0]->id, $audiencemembers);
        $this->assertArrayHasKey($this->users[1]->id, $audiencemembers);
        $this->assertArrayNotHasKey($this->users[2]->id, $audiencemembers);
    }
}