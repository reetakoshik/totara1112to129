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
 * @author Valerii Kuznetsov <valerii.kuznetsov@@totaralearning.com>
 * @package tool_sitepolicy
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Class tool_sitepolicy_userdata_archived_testcase
 *
 * @group totara_userdata
 */
class tool_sitepolicy_userdata_archived_testcase extends advanced_testcase {
    /**
     * Create site policies with the following scheme:
     * - Two users (user and otheruser)
     * - Site policy with two versions (archive and current)
     * - User consents on both versions
     * @return stdClass with created instances
     * @throws coding_exception
     */
    private function create_sitepolicies_with_user_consents() {
        global $DB;

        $that = new stdClass();
        $that->user = $this->getDataGenerator()->create_user();
        $that->otheruser = $this->getDataGenerator()->create_user();

        /**
         * @var tool_sitepolicy_generator $generator
         */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_sitepolicy');

        // Create 2 policies
        $policydef = [
            'time' => time(),
            'authorid' => $that->user->id,
            'languages' => 'en,fr',
            'langprefix' => 'en,fr',
            'title' => 'Policy title',
            'statement' => 'Policy statement',
            'consents' => [
                ['Consent', 'yes', 'no', true],
            ]
        ];
        $that->sitepolicy = $generator->create_published_policy($policydef);
        $firstversion = tool_sitepolicy\policyversion::from_policy_latest($that->sitepolicy, tool_sitepolicy\policyversion::STATUS_PUBLISHED);

        $time = time();
        $that->timeconsented = [1 => $time];
        // Consent on first version
        $generator->add_userconsent($that->sitepolicy, true, $that->user->id, null, $time);
        $generator->add_userconsent($that->sitepolicy, true, $that->otheruser->id, null, $time);

        $firstversion->archive();

        $secondversion = tool_sitepolicy\policyversion::new_policy_draft($that->sitepolicy);
        $secondversion->set_timecreated(time());
        $secondversion->save();
        $secondversion->clone_content($firstversion);

        // Change title, so we know that correct policy was picked up.
        $secondlocalisedpolicy = \tool_sitepolicy\localisedpolicy::from_version($secondversion, ['isprimary' => 1]);
        $secondlocalisedpolicy->set_title('Policy Version 2');
        $secondlocalisedpolicy->save();

        $secondversion->publish();

        $time++;
        $that->timeconsented[2] = $time;
        // Consent on second version
        $generator->add_userconsent($that->sitepolicy, true, $that->user->id, null, $time);
        $generator->add_userconsent($that->sitepolicy, true, $that->otheruser->id, null, $time);

        $secondversion->archive();

        // Create third version to get multiple archived results
        $thirdversion = tool_sitepolicy\policyversion::new_policy_draft($that->sitepolicy);
        $thirdversion->set_timecreated(time());
        $thirdversion->save();
        $thirdversion->clone_content($secondversion);

        // Change title, so we know that correct policy was picked up.
        $thirdlocalisedpolicy = \tool_sitepolicy\localisedpolicy::from_version($thirdversion, ['isprimary' => 1]);
        $thirdlocalisedpolicy->set_title('Policy Version 3');
        $thirdlocalisedpolicy->save();

        $thirdversion->publish();

        $time++;
        $that->timeconsented[3] = $time;
        // Consent on third version
        $generator->add_userconsent($that->sitepolicy, true, $that->user->id, null, $time);
        $generator->add_userconsent($that->sitepolicy, true, $that->otheruser->id, null, $time);


        // Consent of other user on other policy.
        $otherpolicy = $generator->create_published_policy($policydef);
        $generator->add_userconsent($otherpolicy, true, $that->otheruser->id, null, $time+200);

        $this->assertEquals(2, $DB->count_records('tool_sitepolicy_site_policy'));
        $this->assertEquals(4, $DB->count_records('tool_sitepolicy_policy_version'));
        $this->assertEquals(6, $DB->count_records('tool_sitepolicy_localised_policy'));
        $this->assertEquals(6, $DB->count_records('tool_sitepolicy_localised_consent'));
        $this->assertEquals(7, $DB->count_records('tool_sitepolicy_user_consent'));

        $this->assertEquals(3, $DB->count_records('tool_sitepolicy_user_consent', ['userid' => $that->user->id]));
        $this->assertEquals(4, $DB->count_records('tool_sitepolicy_user_consent', ['userid' => $that->otheruser->id]));

        return $that;
    }

    /**
     * Test that only current version of policy user consent is purged
     */
    public function test_purge() {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $that = $this->create_sitepolicies_with_user_consents();

        // Set up and execute the purge item.
        $target_user = new \totara_userdata\userdata\target_user($that->user);
        \tool_sitepolicy\userdata\archived::execute_purge($target_user, context_system::instance());

        $userconsent = $DB->get_record('tool_sitepolicy_user_consent', ['userid' => $that->user->id], '*',  MUST_EXIST);
        $this->assertEquals(4, $DB->count_records('tool_sitepolicy_user_consent', ['userid' => $that->otheruser->id]));

        // Get user consents for current version
        $publishedconsentsql = "
            SELECT tsco.id 
            FROM {tool_sitepolicy_consent_options} tsco
              JOIN {tool_sitepolicy_policy_version} tspv ON (tspv.id = tsco.policyversionid)
            WHERE tspv.timepublished IS NOT NULL
              AND tspv.timearchived IS NULL;
        ";

        $publishedconsentids = $DB->get_records_sql($publishedconsentsql);

        $this->assertArrayHasKey($userconsent->consentoptionid, $publishedconsentids);
    }
    /**
     * Test that only current version of policy user consent is exported and counted
     */
    public function test_export_count() {
        $this->resetAfterTest(true);
        $this->setAdminUser();

        $that = $this->create_sitepolicies_with_user_consents();

        // Set up and execute the purge item.
        $target_user = new \totara_userdata\userdata\target_user($that->user);
        $export = \tool_sitepolicy\userdata\archived::execute_export(
            $target_user,
            context_system::instance()
        );
        $count = \tool_sitepolicy\userdata\archived::execute_count(
            $target_user,
            context_system::instance()
        );

        $this->assertCount(2, $export->data);
        $this->assertEquals(2, $count);

        $this->assertEquals('en Policy title', $export->data[0]['policy']);
        $this->assertEquals('en Consent statement 1', $export->data[0]['statement']);
        $this->assertEquals('1', $export->data[0]['version']);
        $this->assertEquals('en Yes', $export->data[0]['response']);
        $this->assertEquals('en', $export->data[0]['language']);
        $this->assertEquals($that->timeconsented[1], $export->data[0]['time']);

        $this->assertEquals('Policy Version 2', $export->data[1]['policy']);
        $this->assertEquals('en Consent statement 1', $export->data[1]['statement']);
        $this->assertEquals('2', $export->data[1]['version']);
        $this->assertEquals('en Yes', $export->data[1]['response']);
        $this->assertEquals('en', $export->data[1]['language']);
        $this->assertEquals($that->timeconsented[2], $export->data[1]['time']);
    }
}