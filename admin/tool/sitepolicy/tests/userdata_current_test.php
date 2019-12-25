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
 * Class tool_sitepolicy_userdata_current_testcase
 *
 * @group totara_userdata
 */
class tool_sitepolicy_userdata_current_testcase extends advanced_testcase {
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
        $latestversion = tool_sitepolicy\policyversion::from_policy_latest($that->sitepolicy, tool_sitepolicy\policyversion::STATUS_PUBLISHED);

        $time = time();
        $that->timeconsented = [1 => $time];
        // Consent on first version
        $generator->add_userconsent($that->sitepolicy, true, $that->user->id, null, $time);
        $generator->add_userconsent($that->sitepolicy, true, $that->otheruser->id, null, $time);

        $latestversion->archive();

        $nextversion = tool_sitepolicy\policyversion::new_policy_draft($that->sitepolicy);
        $nextversion->set_timecreated(time());
        $nextversion->save();
        $nextversion->clone_content($latestversion);

        // Change title, so we know that correct policy was picked up.
        $nextlocalisedpolicy = \tool_sitepolicy\localisedpolicy::from_version($nextversion, ['isprimary' => 1]);
        $nextlocalisedpolicy->set_title('New title');
        $nextlocalisedpolicy->save();

        $nextversion->publish();
        $that->sitepolicy = new \tool_sitepolicy\sitepolicy($that->sitepolicy->get_id());

        $time++;
        $that->timeconsented[2] = $time;
        // Consent on second version
        $generator->add_userconsent($that->sitepolicy, true, $that->user->id, null, $time);
        $generator->add_userconsent($that->sitepolicy, true, $that->otheruser->id, null, $time);

        // Consent of other user on other policy.
        $otherpolicy = $generator->create_published_policy($policydef);
        $generator->add_userconsent($otherpolicy, true, $that->otheruser->id);

        $this->assertEquals(2, $DB->count_records('tool_sitepolicy_site_policy'));
        $this->assertEquals(3, $DB->count_records('tool_sitepolicy_policy_version'));
        $this->assertEquals(5, $DB->count_records('tool_sitepolicy_localised_policy'));
        $this->assertEquals(5, $DB->count_records('tool_sitepolicy_localised_consent'));
        $this->assertEquals(5, $DB->count_records('tool_sitepolicy_user_consent'));

        $this->assertEquals(2, $DB->count_records('tool_sitepolicy_user_consent', ['userid' => $that->user->id]));
        $this->assertEquals(3, $DB->count_records('tool_sitepolicy_user_consent', ['userid' => $that->otheruser->id]));

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
        \tool_sitepolicy\userdata\current::execute_purge($target_user, context_system::instance());

        $userconsent = $DB->get_record('tool_sitepolicy_user_consent', ['userid' => $that->user->id], '*',  MUST_EXIST);
        $this->assertEquals(3, $DB->count_records('tool_sitepolicy_user_consent', ['userid' => $that->otheruser->id]));

        // Get user consents for archived version
        $archiveconsentsql = "
            SELECT tsco.id 
            FROM {tool_sitepolicy_consent_options} tsco
              JOIN {tool_sitepolicy_policy_version} tspv ON (tspv.id = tsco.policyversionid)
            WHERE tspv.timearchived IS NOT NULL;
        ";

        $archiveconsentids = $DB->get_records_sql($archiveconsentsql);

        $this->assertArrayHasKey($userconsent->consentoptionid, $archiveconsentids);
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
        $export = \tool_sitepolicy\userdata\current::execute_export(
            $target_user,
            context_system::instance()
        );
        $count = \tool_sitepolicy\userdata\current::execute_count(
            $target_user,
            context_system::instance()
        );

        $result = $export->data[0];
        $this->assertEquals('New title', $result['policy']);
        $this->assertEquals('en Consent statement 1', $result['statement']);
        $this->assertEquals('en Yes', $result['response']);
        $this->assertEquals('en', $result['language']);
        $this->assertEquals($that->timeconsented[2], $result['time']);
        $this->assertEquals(1, $count);
    }
}