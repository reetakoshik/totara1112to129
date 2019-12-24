<?php
/**
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package tool_sitepolicy
 */

namespace tool_sitepolicy;

defined('MOODLE_INTERNAL') || die();

/**
 * Sitepolicy tests
 */
class tool_sitepolicy_sitepolicy_test extends \advanced_testcase {
    /**
     * Data provider for test_create_multiversion_policy generator.
     */
    public function data_create_multiversion_policy_generator() {
        return [
            [
                'onedraft',
                [
                    'hasdraft' => true,
                    'numpublished' => 0,
                    'allarchived' => false,
                    'authorid' => 2,
                    'languages' => 'en',
                    'title' => 'Test policy onedraft',
                    'statement' => 'Policy statement onedraft',
                    'numoptions' => 1,
                    'consentstatement' => 'Consent statement onedraft',
                    'providetext' => 'yes',
                    'withheldtext' => 'no',
                    'mandatory' => 'first'
                ]
            ],
            [
                'onepublished',
                [
                    'hasdraft' => false,
                    'numpublished' => 1,
                    'allarchived' => false,
                    'authorid' => 2,
                    'languages' => 'en',
                    'title' => 'Test policy onepublished',
                    'statement' => 'Policy statement onepublished',
                    'numoptions' => 1,
                    'consentstatement' => 'Consent statement onepublished',
                    'providetext' => 'yes',
                    'withheldtext' => 'no',
                    'mandatory' => 'first'
                ]
            ],
            [
                'threearchived',
                [
                    'hasdraft' => false,
                    'numpublished' => 3,
                    'allarchived' => true,
                    'authorid' => 2,
                    'languages' => 'en',
                    'title' => 'Test policy threearchived',
                    'statement' => 'Policy statement threearchived',
                    'numoptions' => 1,
                    'consentstatement' => 'Consent statement threearchived',
                    'providetext' => 'yes',
                    'withheldtext' => 'no',
                    'mandatory' => 'first'
                ]
            ],
            [
                'all',
                [
                    'hasdraft' => true,
                    'numpublished' => 3,
                    'allarchived' => false,
                    'authorid' => 2,
                    'languages' => 'en, nl, es',
                    'langprefix' => ',nl,es',
                    'title' => 'Test policy all',
                    'statement' => 'Policy statement all',
                    'numoptions' => 1,
                    'consentstatement' => 'Consent statement all',
                    'providetext' => 'yes',
                    'withheldtext' => 'no',
                    'mandatory' => 'first'
                ]
            ],
            [
                'draftandarchived',
                [
                    'hasdraft' => true,
                    'numpublished' => 3,
                    'allarchived' => true,
                    'authorid' => 2,
                    'languages' => 'en',
                    'title' => 'Test policy draftandarvhiced',
                    'statement' => 'Policy statement draftandarvhiced',
                    'numoptions' => 1,
                    'consentstatement' => 'Consent statement draftandarchived',
                    'providetext' => 'yes',
                    'withheldtext' => 'no',
                    'mandatory' => 'first'
                ]
            ],
        ];
    }

    /**
     * Test get_sitepolicylist
     *
     * @dataProvider data_create_multiversion_policy_generator
     */
    public function test_get_sitepolicylist($debugkey, $options) {
        $this->resetAfterTest();

        /** @var \tool_sitepolicy_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_sitepolicy');

        $generator->create_multiversion_policy($options);
        $list = sitepolicy::get_sitepolicylist();

        $hasdraft = $options['hasdraft'];
        $numpublished = $options['numpublished'];
        $allarchived = $options['allarchived'];

        $expected = [
            'draft' => (int)$hasdraft,
            'published' => $numpublished,
            'archived' => $numpublished > 0 ? $numpublished - 1 : 0,
            'status' => $hasdraft ? 'draft' : 'published'];
        if ($numpublished > 0 && $allarchived) {
            $expected['archived'] += 1;
            $expected['status'] = $hasdraft ? 'draft' : 'archived';
        }

        $this->assertEquals(1, count($list));
        $row = array_shift($list);
        $this->assertEquals($expected['draft'], $row->numdraft);
        $this->assertEquals($expected['published'], $row->numpublished);
        $this->assertEquals($expected['archived'], $row->numarchived);
        $this->assertEquals($expected['status'], $row->status);
    }

    /**
     * Test switchversion method
     */
    public function test_get_switchversion() {
        global $DB;

        $this->resetAfterTest();
        /** @var \tool_sitepolicy_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_sitepolicy');

        $options = [
            'hasdraft' => true,
            'numpublished' => 3,
            'allarchived' => false,
            'authorid' => 2,
            'languages' => 'en, nl, es',
            'langprefix' => ',nl,es',
            'title' => 'Test policy all',
            'statement' => 'Policy statement all',
            'numoptions' => 1,
            'consentstatement' => 'Consent statement all',
            'providetext' => 'yes',
            'withheldtext' => 'no',
            'mandatory' => 'first'
        ];

        $sitepolicy = $generator->create_multiversion_policy($options);

        $rows = $DB->get_records('tool_sitepolicy_policy_version');
        $this->assertEquals(4, count($rows));

        $drafts = array_filter($rows, function($policy) {
            return (is_null($policy->timepublished) && is_null($policy->timearchived));
        });
        $this->assertEquals(1, count($drafts));

        $published = array_filter($rows, function($policy) {
            return (!is_null($policy->timepublished) && is_null($policy->timearchived));
        });
        $this->assertEquals(1, count($published));

        $archived = array_filter($rows, function($policy) {
            return (!is_null($policy->timepublished) && !is_null($policy->timearchived));
        });
        $this->assertEquals(2, count($archived));

        $olddraftid = reset($drafts)->id;
        $oldpublishedid = reset($published)->id;
        $draftversion = new policyversion($olddraftid);

        // Now publish the old draft version
        $sitepolicy->switchversion($draftversion);

        // Verify the the old draft version is now the published version
        // and the old published version is now archived
        $rows = $DB->get_records('tool_sitepolicy_policy_version');
        $this->assertEquals(4, count($rows));

        $drafts = array_filter($rows, function($policy) {
            return (is_null($policy->timepublished) && is_null($policy->timearchived));
        });
        $this->assertEquals(0, count($drafts));

        $published = array_filter($rows, function($policy) {
            return (!is_null($policy->timepublished) && is_null($policy->timearchived));
        });
        $this->assertEquals(1, count($published));
        $this->assertEquals($olddraftid, reset($published)->id);

        $archived = array_filter($rows, function($policy) {
            return (!is_null($policy->timepublished) && !is_null($policy->timearchived));
        });
        $this->assertTrue(array_key_exists($oldpublishedid, $archived));

        $newpolicy = sitepolicy::create_new_policy('test', 'test', [], 'en');
        $newversion = policyversion::from_policy_latest($newpolicy);

        try {
            // Test a version from a different policy (sanity checking!)
            $sitepolicy->switchversion($newversion);
            $this->fail('Able to switch a sitepolicy to a version that does not belong to it.');
        } catch (\coding_exception $e) {
            $this->assertSame('Coding error detected, it must be fixed by a programmer: Cannot change to new policy version as it does not belong to this site policy', $e->getMessage());
        }

        $newversion->publish();
        try {
            // Test we can't switch to a published version.
            $newpolicy->switchversion($newversion);
            $this->fail('Able to switch a sitepolicy to an already published version.');
        } catch (\coding_exception $e) {
            $this->assertSame('Coding error detected, it must be fixed by a programmer: Cannot publish a non-draft policy version', $e->getMessage());
        }
    }

    /**
     * Test save and delete methods
     */
    public function test_save_and_delete() {
        global $DB;

        $this->resetAfterTest();

        // Verify no existing site_policies
        $rows = $DB->get_records('tool_sitepolicy_site_policy');
        $this->assertEquals(0, count($rows));

        $sitepolicy = new sitepolicy();
        $sitepolicy->save();
        $this->assertNotEmpty($sitepolicy->get_timecreated());

        // Verify new site_policy saved
        $rows = $DB->get_records('tool_sitepolicy_site_policy');
        $this->assertEquals(1, count($rows));
        $id = reset($rows)->id;

        // Now update timecreated and save again
        $sitepolicy->set_timecreated(12345);
        $sitepolicy->save();
        $rows = $DB->get_records('tool_sitepolicy_site_policy');
        $this->assertEquals(1, count($rows));
        $this->assertEquals($id, reset($rows)->id);

        // Now delete the policy
        $sitepolicy->delete();
        $rows = $DB->get_records('tool_sitepolicy_site_policy');
        $this->assertEquals(0, count($rows));
    }

    /**
     * Tests the creation of a new site policy.
     */
    public function test_create_new_policy_and_draft_version() {
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        self::assertCount(0, $list = sitepolicy::get_sitepolicylist());

        $sitepolicy = \tool_sitepolicy\sitepolicy::create_new_policy(
            'Test title',
            'I am the policy text',
            [],
            'en'
        );

        self::assertInstanceOf(sitepolicy::class, $sitepolicy);

        $list = sitepolicy::get_sitepolicylist();
        self::assertCount(1, $list);
        /** @var sitepolicy $expectedpolicy */
        $expectedpolicyobj = reset($list);
        $expectedpolicy = new sitepolicy($expectedpolicyobj->id);

        self::assertInstanceOf(sitepolicy::class, $expectedpolicy);
        self::assertSame($expectedpolicy->get_id(), $sitepolicy->get_id());
        self::assertSame($expectedpolicy->get_timecreated(), $sitepolicy->get_timecreated());

        $expectedversion = policyversion::from_policy_latest($expectedpolicy, policyversion::STATUS_DRAFT);
        $actualversion = policyversion::from_policy_latest($sitepolicy, policyversion::STATUS_DRAFT);

        self::assertInstanceOf(policyversion::class, $expectedversion);
        self::assertInstanceOf(policyversion::class, $actualversion);

        self::assertSame($expectedversion->get_id(), $actualversion->get_id());
        self::assertSame($expectedversion->get_timecreated(), $actualversion->get_timecreated());
        self::assertSame($expectedversion->get_versionnumber(), $actualversion->get_versionnumber());
        self::assertSame($expectedversion->get_primary_title(), $actualversion->get_primary_title());

        $expectedversion->publish();

        $actualdraft = $sitepolicy->create_new_draft_version();
        $expecteddraft = policyversion::from_policy_latest($sitepolicy, policyversion::STATUS_DRAFT);
        self::assertInstanceOf(policyversion::class, $actualdraft);
        self::assertInstanceOf(policyversion::class, $expecteddraft);
        self::assertSame($expecteddraft->get_id(), $actualdraft->get_id());
    }

    /**
     * Tests the create_new_policy parameters
     */
    public function test_create_new_policy_parameters() {
        global $DB;

        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        self::assertCount(0, $list = sitepolicy::get_sitepolicylist());

        $sql =
            "SELECT *
               FROM {tool_sitepolicy_localised_policy} lp
               JOIN {tool_sitepolicy_policy_version} pv
                 ON pv.id = lp.policyversionid
             WHERE pv.sitepolicyid = :sitepolicyid";

        $sitepolicies = [];

        $sitepolicies['defaultformat'] = \tool_sitepolicy\sitepolicy::create_new_policy(
            'Default format',
            'I am the policy text',
            [],
            'en'
        );
        $row = $DB->get_record_sql($sql, ['sitepolicyid' => $sitepolicies['defaultformat']->get_id()]);
        $this->assertEquals(FORMAT_HTML, $row->policytextformat);

        $sitepolicies['plainformat'] = \tool_sitepolicy\sitepolicy::create_new_policy(
            'Plain format',
            'I am the plain policy text',
            [],
            'en',
            null,
            (int)FORMAT_PLAIN
        );
        $row = $DB->get_record_sql($sql, ['sitepolicyid' => $sitepolicies['plainformat']->get_id()]);
        $this->assertEquals(FORMAT_PLAIN, $row->policytextformat);

        $sitepolicies['markdownformat'] = \tool_sitepolicy\sitepolicy::create_new_policy(
            'Markdown format',
            'I am the **markdown** policy text',
            [],
            'en',
            null,
            (int)FORMAT_MARKDOWN
        );
        $row = $DB->get_record_sql($sql, ['sitepolicyid' => $sitepolicies['markdownformat']->get_id()]);
        $this->assertEquals(FORMAT_MARKDOWN, $row->policytextformat);
    }
}