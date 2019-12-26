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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package totara_cohort
 */

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once("{$CFG->dirroot}/totara/cohort/lib.php");

/**
 * Class audience_member_authentication_rule_test
 */
class audience_member_authentication_rule_test extends advanced_testcase {
    /**
     * @param int $max
     * @param string $authtype
     * @return void
     */
    private function create_users(int $max, string $authtype): void {
        $generator = $this->getDataGenerator();
        for ($i = 0; $i < $max; $i++) {
            $generator->create_user([
                'auth' => $authtype
            ]);
        }
    }

    /**
     * @param stdClass $cohort
     * @param stdClass $rule
     * @return void
     */
    private function create_cohort_rules(stdClass $rule, stdClass $ruleparam): void {
        /** @var totara_cohort_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_cohort');
        $generator->create_cohort_rule_params(
            $rule->rulesetid, $rule->type, $rule->name, $ruleparam->params, $ruleparam->listofvalues
        );
    }

    /**
     * @return stdClass
     */
    private function create_cohort_and_ruleset(): stdClass {
        /** @var totara_cohort_generator $generator */
        $generator= $this->getDataGenerator()->get_plugin_generator('totara_cohort');
        $cohort = $generator->create_cohort();
        $rulesetid = cohort_rule_create_ruleset($cohort->activecollectionid);

        $cohort->rulesetid = $rulesetid;
        return $cohort;
    }

    /**
     * @return array
     */
    public function provide_test_data(): array {
        return [
            [2, 6, ["ldap", "lti", "email"], COHORT_RULES_OP_IN_EQUAL, ["ldap", "lti", "email"]],
            [2, 4, ["ldap", "lti", "manual", "email"], COHORT_RULES_OP_IN_EQUAL, ["email", "ldap"]],
            [2, 0, ["ldap", "lti", "manual"], COHORT_RULES_OP_IN_NOTEQUAL, ["email", "ldap", "lti", "manual"]],
            [2, 4, ["ldap", "lti", "manual"], COHORT_RULES_OP_IN_NOTEQUAL, ["manual"]]
        ];
    }

    /**
     * @dataProvider provide_test_data
     * @param int   $numberofusers          The number of users for each of authentication type within system
     * @param int   $numberofexpecteduser   The number of members expected, after update audiences with rules
     * @param array $listofauthtype         This is the lis of authentication type provided for environment
     * @param int   $operation              This is the operation of rule parameters
     * @param array $ruleauthtype           This is for rule parameters (list of values)
     * @return void
     */
    public function test_audiencen_rule_authentication_members(
        int $numberofusers, int $numberofexpecteduser, array $listofauthtype, int $operation, array $ruleauthtype
    ): void {
        global $DB;
        $this->resetAfterTest(true);
        $this->setAdminUser();

        // Creating the number of users with the authentication type here
        foreach ($listofauthtype as $authtype) {
            $this->create_users($numberofusers, $authtype);
        }

        $cohort = $this->create_cohort_and_ruleset();
        $rule = new stdClass();
        $rule->rulesetid = $cohort->rulesetid;
        $rule->type = "user";
        $rule->name = 'authenticationtype';

        $ruleparam = new stdClass();
        $ruleparam->params = ['equal' => $operation];
        $ruleparam->listofvalues = $ruleauthtype;

        $this->create_cohort_rules($rule, $ruleparam);
        totara_cohort_update_dynamic_cohort_members($cohort->id);

        $sql = "SELECT COUNT(cm.id) AS total_member FROM {cohort_members} cm WHERE cm.cohortid = {$cohort->id}";
        $results = $DB->get_record_sql($sql);

        $this->assertEquals($numberofexpecteduser, $results->total_member);

        $this->assertLessThanOrEqual(($numberofusers * count($listofauthtype)), $results->total_member);
    }
}
