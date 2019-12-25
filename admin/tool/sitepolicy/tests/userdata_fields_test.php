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
 * Test userdata purgeable fields (like publisher or author)
 *
 * @group totara_userdata
 */
class tool_sitepolicy_userdata_fields_testcase extends advanced_testcase {

    /**
     * Create two site policies
     * With two version each
     * Authored and published by different users
     */
    private function create_sitepolicies() {
        global $DB;

        $that = new stdClass();
        $that->user = $this->getDataGenerator()->create_user();
        $that->otheruser = $this->getDataGenerator()->create_user();

        /**
         * @var tool_sitepolicy_generator $generator
         */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_sitepolicy');

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
        $that->firstsitepolicy = $generator->create_published_policy($policydef);
        $firstversion = tool_sitepolicy\policyversion::from_policy_latest($that->firstsitepolicy, tool_sitepolicy\policyversion::STATUS_PUBLISHED);
        $firstversion->archive();

        $secondversion = tool_sitepolicy\policyversion::new_policy_draft($that->firstsitepolicy);
        $secondversion->set_timecreated(time());
        $secondversion->save();
        $secondversion->clone_content($firstversion);

        // Change author
        $nextlocalisedpolicy = \tool_sitepolicy\localisedpolicy::from_version($secondversion, ['isprimary' => 1]);
        $nextlocalisedpolicy->set_authorid($that->otheruser->id);
        $nextlocalisedpolicy->save();

        $secondversion->publish($that->otheruser->id);

        $policydef['authorid'] =  $that->otheruser->id;
        $that->secondsitepolicy = $generator->create_published_policy($policydef);
        $firstversion = tool_sitepolicy\policyversion::from_policy_latest($that->secondsitepolicy, tool_sitepolicy\policyversion::STATUS_PUBLISHED);
        $firstversion->archive();

        $secondversion = tool_sitepolicy\policyversion::new_policy_draft($that->secondsitepolicy);
        $secondversion->set_timecreated(time());
        $secondversion->save();
        $secondversion->clone_content($firstversion);

        // Change author
        $nextlocalisedpolicy = \tool_sitepolicy\localisedpolicy::from_version($secondversion, ['isprimary' => 1]);
        $nextlocalisedpolicy->set_authorid($that->user->id);
        $nextlocalisedpolicy->save();

        $secondversion->publish($that->user->id);

        $this->assertEquals(2, $DB->count_records('tool_sitepolicy_site_policy'));
        $this->assertEquals(4, $DB->count_records('tool_sitepolicy_policy_version'));
        $this->assertEquals(6, $DB->count_records('tool_sitepolicy_localised_policy'));

        return $that;
    }

    /**
     * Test that only author field of localised policies is purged
     */
    public function test_purge_author() {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $that = $this->create_sitepolicies();

        // Confirm that user is author of some localised policies
        $this->assertEquals(3, $DB->count_records('tool_sitepolicy_localised_policy', ['authorid' => $that->user->id]));

        // Set up and execute the purge item.
        $target_user = new \totara_userdata\userdata\target_user($that->user);
        \tool_sitepolicy\userdata\author::execute_purge($target_user, context_system::instance());

        // No number of records must be changed
        $this->assertEquals(2, $DB->count_records('tool_sitepolicy_site_policy'));
        $this->assertEquals(4, $DB->count_records('tool_sitepolicy_policy_version'));
        $this->assertEquals(6, $DB->count_records('tool_sitepolicy_localised_policy'));

        // But user is no author
        $this->assertEquals(0, $DB->count_records('tool_sitepolicy_localised_policy', ['authorid' => $that->user->id]));

    }

    /**
     * Ensure that author is not exportable.
     */
    public function test_author_not_exportable() {
        self::assertFalse(\tool_sitepolicy\userdata\author::is_exportable());
    }

    /**
     * Ensure that author is not countable.
     */
    public function test_author_not_countable() {
        self::assertFalse(\tool_sitepolicy\userdata\author::is_countable());
    }


    /**
     * Test that only published of the version is purged
     */
    public function test_purge_publisher() {
        global $DB;

        $this->resetAfterTest(true);
        $this->setAdminUser();

        $that = $this->create_sitepolicies();

        // Confirm that user is publisher of some localised policies
        $this->assertEquals(2, $DB->count_records('tool_sitepolicy_policy_version', ['publisherid' => $that->user->id]));

        // Set up and execute the purge item.
        $target_user = new \totara_userdata\userdata\target_user($that->user);
        \tool_sitepolicy\userdata\publisher::execute_purge($target_user, context_system::instance());

        // No number of records must be changed.
        $this->assertEquals(2, $DB->count_records('tool_sitepolicy_site_policy'));
        $this->assertEquals(4, $DB->count_records('tool_sitepolicy_policy_version'));
        $this->assertEquals(6, $DB->count_records('tool_sitepolicy_localised_policy'));

        // But user is no publisher
        $this->assertEquals(0, $DB->count_records('tool_sitepolicy_policy_version', ['publisherid' => $that->user->id]));
    }


    /**
     * Ensure that publisher is not exportable.
     */
    public function test_publisher_not_exportable() {
        self::assertFalse(\tool_sitepolicy\userdata\publisher::is_exportable());
    }

    /**
     * Ensure that publisher is not countable.
     */
    public function test_publisher_not_countable() {
        self::assertFalse(\tool_sitepolicy\userdata\publisher::is_countable());
    }
}