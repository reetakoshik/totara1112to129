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
 * Sitepolicy policy version tests.
 */
class tool_sitepolicy_policyversion_test extends \advanced_testcase {

    /**
     * Test new_policy_draft with exception when sitepolicy not saved
     */
    public function test_new_policy_draft_exception_sitepolicy_not_saved() {

        $this->resetAfterTest();
        $this->expectException('coding_exception');
        $this->expectExceptionMessage('Site policy must be saved before adding policy versions');

        $sitepolicy = new sitepolicy();
        $version = policyversion::new_policy_draft($sitepolicy);
    }

    /**
     * Test new_policy_draft with exception when another draft already exists
     */
    public function test_new_policy_draft_exception_draft_exists() {

        $this->resetAfterTest();
        $this->expectException('coding_exception');
        $this->expectExceptionMessage('Cannot create draft as it already exists');
        /** @var \tool_sitepolicy_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_sitepolicy');

        $sitepolicy = $generator->create_draft_policy([]);
        $version = policyversion::new_policy_draft($sitepolicy);
    }

    /**
     * Test new_policy_draft
     */
    public function test_new_policy_draft_success() {
        global $DB;

        $this->resetAfterTest();
        /** @var \tool_sitepolicy_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_sitepolicy');

        $sitepolicy = $generator->create_published_policy([]);
        $rows = $DB->get_records('tool_sitepolicy_policy_version');
        $this->assertEquals(1, count($rows));
        $row = reset($rows);
        $this->assertNotNull($row->timepublished);

        $version = policyversion::new_policy_draft($sitepolicy);

        $this->assertEquals(0, $version->get_id());
        $this->assertEquals(0, $version->get_versionnumber());
        $this->assertEquals(0, $version->get_timecreated());
        $this->assertNull($version->get_timepublished());
        $this->assertNull($version->get_timearchived());
        $this->assertEquals($sitepolicy, $version->get_sitepolicy());
        $this->assertNull($version->get_publisherid());
        $this->assertEquals(policyversion::STATUS_DRAFT, $version->get_status());

        // Make sure nothing was saved to the database
        $rows = $DB->get_records('tool_sitepolicy_policy_version');
        $this->assertEquals(1, count($rows));
        $row = reset($rows);
        $this->assertNotNull($row->timepublished);
    }

    /**
     * Test save
     */
    public function test_save() {
        global $DB;

        $this->resetAfterTest();
        /** @var \tool_sitepolicy_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_sitepolicy');

        $sitepolicy = $generator->create_published_policy([]);
        $rows = $DB->get_records('tool_sitepolicy_policy_version');
        $this->assertEquals(1, count($rows));
        $row = reset($rows);
        $id = $row->id;

        $version = policyversion::new_policy_draft($sitepolicy);

        $this->assertEquals(0, $version->get_id());
        $this->assertEquals(0, $version->get_versionnumber());
        $this->assertEquals(0, $version->get_timecreated());
        $this->assertNull($version->get_timepublished());
        $this->assertNull($version->get_timearchived());
        $this->assertEquals($sitepolicy, $version->get_sitepolicy());
        $this->assertNull($version->get_publisherid());
        $this->assertEquals(policyversion::STATUS_DRAFT, $version->get_status());

        // Now save the new draft
        $version->save();

        $sql = "
            SELECT *
              FROM {tool_sitepolicy_policy_version}
             WHERE id <> :id";
        $row = $DB->get_record_sql($sql, ['id' => $id]);

        $this->assertEquals($row->id, $version->get_id());
        $this->assertEquals($row->versionnumber, $version->get_versionnumber());
        $this->assertEquals($row->timecreated, $version->get_timecreated());
        $this->assertEquals($row->timepublished, $version->get_timepublished());
        $this->assertEquals($row->timearchived, $version->get_timearchived());
        $this->assertEquals($sitepolicy, $version->get_sitepolicy());
        $this->assertEquals($row->publisherid, $version->get_publisherid());
        $this->assertEquals(policyversion::STATUS_DRAFT, $version->get_status());
    }

    /**
     * Test delete.
     * Ensure related rows are also deleted
     */
    public function test_delete() {
        global $DB;

        $this->resetAfterTest();
        /** @var \tool_sitepolicy_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_sitepolicy');

        $options = [
            'hasdraft' => true,
            'numpublished' => 0,
            'allarchived' => false,
            'authorid' => 2,
            'languages' => 'en',
            'title' => 'Test policy all',
            'statement' => 'Policy statement all',
            'numoptions' => 1,
            'consentstatement' => 'Consent statement all',
            'providetext' => 'yes',
            'withheldtext' => 'no',
            'mandatory' => 'first'
        ];

        $sitepolicy = $generator->create_multiversion_policy($options);

        // Ensure the rows were created
        $rows = $DB->get_records('tool_sitepolicy_site_policy');
        $this->assertEquals(1, count($rows));
        $rows = $DB->get_records('tool_sitepolicy_policy_version');
        $this->assertEquals(1, count($rows));
        $rows = $DB->get_records('tool_sitepolicy_consent_options');
        $this->assertEquals(1, count($rows));
        $rows = $DB->get_records('tool_sitepolicy_localised_policy');
        $this->assertEquals(1, count($rows));
        $rows = $DB->get_records('tool_sitepolicy_localised_consent');
        $this->assertEquals(1, count($rows));

        // Now delete the policy_version and ensure that related entries are also deleted
        $version = policyversion::from_policy_latest($sitepolicy);
        $version->delete();
        $rows = $DB->get_records('tool_sitepolicy_site_policy');
        $this->assertEquals(1, count($rows));
        $rows = $DB->get_records('tool_sitepolicy_policy_version');
        $this->assertEquals(0, count($rows));
        $rows = $DB->get_records('tool_sitepolicy_consent_options');
        $this->assertEquals(0, count($rows));
        $rows = $DB->get_records('tool_sitepolicy_localised_policy');
        $this->assertEquals(0, count($rows));
        $rows = $DB->get_records('tool_sitepolicy_localised_consent');
        $this->assertEquals(0, count($rows));
    }

    /**
     * Test delete error conditions.
     * Only test error conditions handled in policyversion here.
     * Other classes test their own error conditions.
     */
    public function test_delete_exceptions() {
        global $DB;

        $this->resetAfterTest();
        $this->expectException('coding_exception');
        $this->expectExceptionMessage('This version was published, so it cannot be deleted');
        /** @var \tool_sitepolicy_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_sitepolicy');

        $options = [
            'hasdraft' => false,
            'numpublished' => 1,
            'allarchived' => false,
            'authorid' => 2,
            'languages' => 'en',
            'title' => 'Test policy all',
            'statement' => 'Policy statement all',
            'numoptions' => 1,
            'consentstatement' => 'Consent statement all',
            'providetext' => 'yes',
            'withheldtext' => 'no',
            'mandatory' => 'first'
        ];

        $sitepolicy = $generator->create_multiversion_policy($options);

        // Ensure the rows were created
        $rows = $DB->get_records('tool_sitepolicy_site_policy');
        $this->assertEquals(1, count($rows));
        $rows = $DB->get_records('tool_sitepolicy_policy_version');
        $this->assertEquals(1, count($rows));
        $rows = $DB->get_records('tool_sitepolicy_consent_options');
        $this->assertEquals(1, count($rows));
        $rows = $DB->get_records('tool_sitepolicy_localised_policy');
        $this->assertEquals(1, count($rows));
        $rows = $DB->get_records('tool_sitepolicy_localised_consent');
        $this->assertEquals(1, count($rows));

        // Now try to delete - error should be thrown and rows left intacked
        $version = policyversion::from_policy_latest($sitepolicy);
        $version->delete();
    }

    /**
     * Test force delete
     */
    public function test_delete_force() {
        global $DB;

        $this->resetAfterTest();
        /** @var \tool_sitepolicy_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_sitepolicy');

        $options = [
            'hasdraft' => false,
            'numpublished' => 1,
            'allarchived' => false,
            'authorid' => 2,
            'languages' => 'en',
            'title' => 'Test policy all',
            'statement' => 'Policy statement all',
            'numoptions' => 1,
            'consentstatement' => 'Consent statement all',
            'providetext' => 'yes',
            'withheldtext' => 'no',
            'mandatory' => 'first'
        ];

        $sitepolicy = $generator->create_multiversion_policy($options);

        // Ensure the rows were created
        $rows = $DB->get_records('tool_sitepolicy_site_policy');
        $this->assertEquals(1, count($rows));
        $rows = $DB->get_records('tool_sitepolicy_policy_version');
        $this->assertEquals(1, count($rows));
        $rows = $DB->get_records('tool_sitepolicy_consent_options');
        $this->assertEquals(1, count($rows));
        $rows = $DB->get_records('tool_sitepolicy_localised_policy');
        $this->assertEquals(1, count($rows));
        $rows = $DB->get_records('tool_sitepolicy_localised_consent');
        $this->assertEquals(1, count($rows));

        // force delete
        $version = policyversion::from_policy_latest($sitepolicy);
        $version->delete(true);
        $rows = $DB->get_records('tool_sitepolicy_site_policy');
        $this->assertEquals(1, count($rows));
        $rows = $DB->get_records('tool_sitepolicy_policy_version');
        $this->assertEquals(0, count($rows));
        $rows = $DB->get_records('tool_sitepolicy_consent_options');
        $this->assertEquals(0, count($rows));
        $rows = $DB->get_records('tool_sitepolicy_localised_policy');
        $this->assertEquals(0, count($rows));
        $rows = $DB->get_records('tool_sitepolicy_localised_consent');
        $this->assertEquals(0, count($rows));
    }

    /**
     * Test from_policy_latest
     */
    public function test_from_policy_latest() {
        global $DB;

        $this->resetAfterTest();
        /** @var \tool_sitepolicy_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_sitepolicy');

        $options = [
            'hasdraft' => true,
            'numpublished' => 3,
            'allarchived' => false,
            'authorid' => 2,
            'languages' => 'en',
            'title' => 'Test policy all',
            'statement' => 'Policy statement all',
            'numoptions' => 1,
            'consentstatement' => 'Consent statement all',
            'providetext' => 'yes',
            'withheldtext' => 'no',
            'mandatory' => 'first'
        ];

        $sitepolicy = $generator->create_multiversion_policy($options);

        // Any status
        $sql = "
              SELECT *
                FROM {tool_sitepolicy_policy_version}
            ORDER BY versionnumber DESC";
        $expectedrow = $DB->get_record_sql($sql, [], IGNORE_MULTIPLE);

        $version = policyversion::from_policy_latest($sitepolicy);

        $this->assertEquals($expectedrow->id, $version->get_id());
        $this->assertEquals($expectedrow->versionnumber, $version->get_versionnumber());
        $this->assertEquals($expectedrow->timecreated, $version->get_timecreated());
        $this->assertEquals($expectedrow->timepublished, $version->get_timepublished());
        $this->assertEquals($expectedrow->timearchived, $version->get_timearchived());
        $this->assertEquals($sitepolicy, $version->get_sitepolicy());
        $this->assertEquals($expectedrow->publisherid, $version->get_publisherid());
        $this->assertEquals(policyversion::STATUS_DRAFT, $version->get_status());

        // Draft
        $sql = "
              SELECT *
                FROM {tool_sitepolicy_policy_version}
               WHERE timepublished IS NULL
            ORDER BY versionnumber DESC";
        $expectedrow = $DB->get_record_sql($sql, [], IGNORE_MULTIPLE);

        $version = policyversion::from_policy_latest($sitepolicy, policyversion::STATUS_DRAFT);

        $this->assertEquals($expectedrow->id, $version->get_id());
        $this->assertEquals($expectedrow->versionnumber, $version->get_versionnumber());
        $this->assertEquals($expectedrow->timecreated, $version->get_timecreated());
        $this->assertEquals($expectedrow->timepublished, $version->get_timepublished());
        $this->assertEquals($expectedrow->timearchived, $version->get_timearchived());
        $this->assertEquals($sitepolicy, $version->get_sitepolicy());
        $this->assertEquals($expectedrow->publisherid, $version->get_publisherid());
        $this->assertEquals(policyversion::STATUS_DRAFT, $version->get_status());

        // Published
        $sql = "
              SELECT *
                FROM {tool_sitepolicy_policy_version}
               WHERE timepublished IS NOT NULL
                 AND timearchived IS NULL
            ORDER BY versionnumber DESC";
        $expectedrow = $DB->get_record_sql($sql, [], IGNORE_MULTIPLE);

        $version = policyversion::from_policy_latest($sitepolicy, policyversion::STATUS_PUBLISHED);

        $this->assertEquals($expectedrow->id, $version->get_id());
        $this->assertEquals($expectedrow->versionnumber, $version->get_versionnumber());
        $this->assertEquals($expectedrow->timecreated, $version->get_timecreated());
        $this->assertEquals($expectedrow->timepublished, $version->get_timepublished());
        $this->assertEquals($expectedrow->timearchived, $version->get_timearchived());
        $this->assertEquals($sitepolicy, $version->get_sitepolicy());
        $this->assertEquals($expectedrow->publisherid, $version->get_publisherid());
        $this->assertEquals(policyversion::STATUS_PUBLISHED, $version->get_status());

        // Archived
        $sql = "
              SELECT *
                FROM {tool_sitepolicy_policy_version}
               WHERE timepublished IS NOT NULL
                 AND timearchived IS NOT NULL
            ORDER BY versionnumber DESC";
        $expectedrow = $DB->get_record_sql($sql, [], IGNORE_MULTIPLE);

        $version = policyversion::from_policy_latest($sitepolicy, policyversion::STATUS_ARCHIVED);

        $this->assertEquals($expectedrow->id, $version->get_id());
        $this->assertEquals($expectedrow->versionnumber, $version->get_versionnumber());
        $this->assertEquals($expectedrow->timecreated, $version->get_timecreated());
        $this->assertEquals($expectedrow->timepublished, $version->get_timepublished());
        $this->assertEquals($expectedrow->timearchived, $version->get_timearchived());
        $this->assertEquals($sitepolicy, $version->get_sitepolicy());
        $this->assertEquals($expectedrow->publisherid, $version->get_publisherid());
        $this->assertEquals(policyversion::STATUS_ARCHIVED, $version->get_status());
    }

    /**
     * Test we cannot get the latest version for an unsaved site policy.
     * @throws \coding_exception
     */
    public function test_from_policy_latest_unsaved_sitepolicy() {
        $this->resetAfterTest();
        $this->expectException('coding_exception');
        $this->expectExceptionMessage('Site policy must be saved before retrieving a version');

        $sitepolicy = new sitepolicy();
        policyversion::from_policy_latest($sitepolicy);
    }

    /**
     * Test we cannot get the latest version for an unsaved site policy.
     * @throws \coding_exception
     */
    public function test_from_policy_latest_invalid_status() {
        $this->resetAfterTest();
        $this->expectException('coding_exception');
        $this->expectExceptionMessage('Invalid status passed');

        $sitepolicy = sitepolicy::create_new_policy('test', 'test', [], 'en');
        policyversion::from_policy_latest($sitepolicy, 'monkeys');
    }

    /**
     * Test we cannot get the latest version for an unsaved site policy.
     * @throws \coding_exception
     */
    public function test_from_policy_latest_missing_versions() {
        global $DB;
        $this->resetAfterTest();
        $this->expectException('coding_exception');
        $this->expectExceptionMessage('Policy don\'t have any versions, remove policy and create new');

        $sitepolicy = sitepolicy::create_new_policy('test', 'test', [], 'en');

        $DB->delete_records('tool_sitepolicy_policy_version');

        policyversion::from_policy_latest($sitepolicy);
    }

    /**
     * Data provider for test_get_versionlist.
     */
    public function data_get_versionlist() {
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
                    'languages' => 'en, nl, he',
                    'langprefix' =>',nl ,he ',
                    'title' => 'Test policy all',
                    'statement' => 'Policy statement all',
                    'numoptions' => 3,
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
                    'languages' => 'en, nl',
                    'langprefix' => ',nl ',
                    'title' => 'Test policy draftandarvhiced',
                    'statement' => 'Policy statement draftandarvhiced',
                    'numoptions' => 3,
                    'consentstatement' => 'Consent statement draftandarchived',
                    'providetext' => 'yes',
                    'withheldtext' => 'no',
                    'mandatory' => 'first'
                ]
            ],
        ];
    }

    /**
     * Test get_versionlist
     *
     * @dataProvider data_get_versionlist
     **/
    public function test_get_versionlist($debugkey, $options) {

        $this->resetAfterTest();
        /** @var \tool_sitepolicy_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_sitepolicy');

        $sitepolicy = $generator->create_multiversion_policy($options);

        $list = policyversion::get_versionlist($sitepolicy->get_id());

        $hasdraft = $options['hasdraft'];
        $numpublished = $options['numpublished'];
        $numtranslations = isset($options['languages']) ? count(explode(',', $options['languages'])) : 1;
        $numoptions = isset($options['numoptions']) ? $options['numoptions'] : 1;
        $expectedcnt = (int)$hasdraft + $numpublished;

        $this->assertEquals($expectedcnt, count($list));
        for ($i = $expectedcnt; $i >= 1; $i--) {
            $row = array_shift($list);

            $this->assertEquals($i, $row->versionnumber);

            // Translations are not copied
            if ($i == 1) {
                $this->assertEquals($numoptions * $numtranslations, $row->cnt_options);
                $this->assertEquals($numtranslations, $row->cnt_translations );
                $this->assertEquals($numoptions * $numtranslations, $row->cnt_translatedoptions);
            } else {
                $this->assertEquals($numoptions, $row->cnt_options);
                $this->assertEquals(1, $row->cnt_translations );
                $this->assertEquals($numoptions, $row->cnt_translatedoptions);
            }

            if ($i == $expectedcnt) {
                if ($hasdraft) {
                    $this->assertNull($row->timepublished);
                    $this->assertNull($row->timearchived);
                    $this->assertEquals(policyversion::STATUS_DRAFT, $row->status);
                } else {
                    $this->assertNotNull($row->timepublished);
                    if (!$options['allarchived']) {
                        $this->assertNull($row->timearchived);
                        $this->assertEquals(policyversion::STATUS_PUBLISHED, $row->status);
                    } else {
                        $this->assertNotNull($row->timearchived);
                        $this->assertEquals(policyversion::STATUS_ARCHIVED, $row->status);
                    }
                }
            } else if ($i == ($expectedcnt - 1) && !$options['allarchived']) {
                $this->assertNotNull($row->timepublished);
                $this->assertNull($row->timearchived);
                $this->assertEquals(policyversion::STATUS_PUBLISHED, $row->status);
            } else {
                $this->assertNotNull($row->timepublished);
                $this->assertNotNull($row->timearchived);
                $this->assertEquals(policyversion::STATUS_ARCHIVED, $row->status);
            }
        }
    }

    /**
     * Test get_versionlist after manipulating options
     **/
    public function test_get_versionlist_optionchanges() {
        global $DB;

        $this->resetAfterTest();
        /** @var \tool_sitepolicy_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_sitepolicy');

        $options = [
            'hasdraft' => true,
            'numpublished' => 0,
            'allarchived' => false,
            'authorid' => 2,
            'languages' => 'en, nl',
            'langprefix' => ',nl',
            'title' => 'Test policy optionchanges',
            'statement' => 'Policy statement optionchanges',
            'numoptions' => 3,
            'consentstatement' => 'Consent statement optionchanges',
            'providetext' => 'yes',
            'withheldtext' => 'no',
            'mandatory' => 'first'
            ];

        $sitepolicy = $generator->create_multiversion_policy($options);
        $list = policyversion::get_versionlist($sitepolicy->get_id());

        $this->assertEquals(1, count($list));
        $row = array_shift($list);

        $this->assertEquals(1, $row->versionnumber);
        $this->assertEquals(6, $row->cnt_options);
        $this->assertEquals(2, $row->cnt_translations );
        $this->assertEquals(6, $row->cnt_translatedoptions);
        $this->assertNull($row->timepublished);
        $this->assertNull($row->timearchived);
        $this->assertEquals(policyversion::STATUS_DRAFT, $row->status);

        // Add more options to the primary language only
        $version = policyversion::from_policy_latest($sitepolicy, policyversion::STATUS_DRAFT);

        $params = ['policyversionid' => $version->get_id(), 'language' => 'en'];
        $localisedpolicyid = $DB->get_field('tool_sitepolicy_localised_policy', 'id', $params);

        // Insert two additional options into consent_options and localised consent
        for ($i = 10; $i < 12; $i++) {
            $entry = new \stdClass();
            $entry->mandatory = 1;
            $entry->idnumber = $i;
            $entry->policyversionid = $version->get_id();
            $consentoptionid = $DB->insert_record('tool_sitepolicy_consent_options', $entry);

            $entry = new \stdClass();
            $entry->statement = "New consent statement $i";
            $entry->consentoption = "New consent option $i";
            $entry->nonconsentoption = "New non-consent option $i";
            $entry->consentoptionid = $consentoptionid;
            $entry->localisedpolicyid = $localisedpolicyid;
            $DB->insert_record('tool_sitepolicy_localised_consent', $entry);
        }

        $list = policyversion::get_versionlist($sitepolicy->get_id());

        $this->assertEquals(1, count($list));
        $row = array_shift($list);

        $this->assertEquals(1, $row->versionnumber);
        $this->assertEquals(10, $row->cnt_options);
        $this->assertEquals(2, $row->cnt_translations );
        $this->assertEquals(8, $row->cnt_translatedoptions);
        $this->assertNull($row->timepublished);
        $this->assertNull($row->timearchived);
        $this->assertEquals(policyversion::STATUS_DRAFT, $row->status);
    }

    /**
     * Test get_summary
     **/
    public function test_get_summary() {
        global $DB;

        $this->resetAfterTest();
        /** @var \tool_sitepolicy_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_sitepolicy');

        $options = [
            'authorid' => 2,
            'languages' => 'en, nl, he',
            'langprefix' =>',nl ,he ',
            'title' => 'Test policy all',
            'statement' => 'Policy statement all',
            'numoptions' => 3,
            'consentstatement' => 'Consent statement all',
            'providetext' => 'yes',
            'withheldtext' => 'no',
            'mandatory' => 'first'
        ];

        $sitepolicy = $generator->create_published_policy($options);

        $version = policyversion::from_policy_latest($sitepolicy);

        $summary = $version->get_summary();

        $languages = explode(',', $options['languages']);
        $languages = array_map(function ($v) {return trim($v);}, $languages);

        $this->assertEquals(count($languages), count($summary));
        foreach ($summary as $row) {
            $this->assertContains($row->language, $languages);
            $this->assertEquals((int)($row->language == $languages[0]), $row->isprimary);
            $this->assertEquals($languages[0], $row->primarylanguage);
            $this->assertEquals($options['numoptions'], $row->cnt_statements);
            $this->assertEquals(0, $row->incomplete);
        }

        // Now add more options to the primary language only
        $params = ['policyversionid' => $version->get_id(), 'language' => 'en'];
        $localisedpolicyid = $DB->get_field('tool_sitepolicy_localised_policy', 'id', $params);

        $coids = [];
        // Insert two additional options into consent_options and localised consent
        for ($i = 10; $i < 12; $i++) {
            $entry = new \stdClass();
            $entry->mandatory = 1;
            $entry->idnumber = $i;
            $entry->policyversionid = $version->get_id();
            $coids[$i] = $DB->insert_record('tool_sitepolicy_consent_options', $entry);

            $entry = new \stdClass();
            $entry->statement = "New consent statement $i";
            $entry->consentoption = "New consent option $i";
            $entry->nonconsentoption = "New non-consent option $i";
            $entry->consentoptionid = $coids[$i];
            $entry->localisedpolicyid = $localisedpolicyid;
            $DB->insert_record('tool_sitepolicy_localised_consent', $entry);
        }

        $summary = $version->get_summary(true);

        $this->assertEquals(count($languages), count($summary));
        foreach ($summary as $row) {
            $this->assertContains($row->language, $languages);
            $this->assertEquals((int)($row->language == $languages[0]), $row->isprimary);
            $this->assertEquals($languages[0], $row->primarylanguage);
            $this->assertEquals($options['numoptions'] + 2, $row->cnt_statements);
            $this->assertEquals($row->language == $languages[0] ? 0 : 2, $row->incomplete);
        }

        // Now add translations for the new consentoptions
        $trlang = ['nl', 'he'];
        for ($i = 10; $i < 12; $i++) {
            foreach ($trlang as $lang) {
                $params = ['policyversionid' => $version->get_id(), 'language' => $lang];
                $localisedpolicyid = $DB->get_field('tool_sitepolicy_localised_policy', 'id', $params);
                $entry = new \stdClass();
                $entry->statement = "$lang - New consent statement $i";
                $entry->consentoption = "$lang - New consent option $i";
                $entry->nonconsentoption = "$lang - New non-consent option $i";
                $entry->consentoptionid = $coids[$i];
                $entry->localisedpolicyid = $localisedpolicyid;
                $DB->insert_record('tool_sitepolicy_localised_consent', $entry);
            }
        }

        $summary = $version->get_summary(true);

        $this->assertEquals(count($languages), count($summary));
        foreach ($summary as $row) {
            $this->assertContains($row->language, $languages);
            $this->assertEquals((int)($row->language == $languages[0]), $row->isprimary);
            $this->assertEquals($languages[0], $row->primarylanguage);
            $this->assertEquals($options['numoptions'] + 2, $row->cnt_statements);
            $this->assertEquals(0, $row->incomplete);
        }

        // Deletion of a primary consent option results in all localised consent entries being
        // deleted (call is made to localisedconsent::delete_all). Therefore not testing that scenario here.
    }

    /**
     * Test is_complete
     **/
    public function test_is_complete() {
        global $DB;

        $this->resetAfterTest();
        /** @var \tool_sitepolicy_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_sitepolicy');

        $options = [
            'authorid' => 2,
            'languages' => 'en, nl, he',
            'langprefix' =>',nl ,he ',
            'title' => 'Test policy all',
            'statement' => 'Policy statement all',
            'numoptions' => 3,
            'consentstatement' => 'Consent statement all',
            'providetext' => 'yes',
            'withheldtext' => 'no',
            'mandatory' => 'first'
        ];

        $sitepolicy = $generator->create_draft_policy($options);

        $version = policyversion::from_policy_latest($sitepolicy);

        $this->assertTrue($version->is_complete());

        // Now add an additional consent option
        $entry = new \stdClass();
        $entry->mandatory = 0;
        $entry->policyversionid = $version->get_id();
        $optionid = $DB->insert_record('tool_sitepolicy_consent_options', $entry);
        $this->assertFalse($version->is_complete());

        // Add some localised_consents for the new option, but not all
        foreach (['en', 'nl'] as $language) {
            $params = [
                'policyversionid' => $version->get_id(),
                'language' => $language
            ];
            $tslp = $DB->get_record('tool_sitepolicy_localised_policy', $params);

            $entry = new \stdClass();
            $entry->statement = "$language new statement";
            $entry->consentoption = 'Yip';
            $entry->nonconsentoption = 'Nope';
            $entry->localisedpolicyid = $tslp->id;
            $entry->consentoptionid = $optionid;
            $DB->insert_record('tool_sitepolicy_localised_consent', $entry);
        }
        $this->assertFalse($version->is_complete());

        // Now add localised consent for last language
        $params = [
            'policyversionid' => $version->get_id(),
            'language' => 'he'
        ];
        $tslp = $DB->get_record('tool_sitepolicy_localised_policy', $params);

        $entry = new \stdClass();
        $entry->statement = "$language new statement";
        $entry->consentoption = 'Yip';
        $entry->nonconsentoption = 'Nope';
        $entry->localisedpolicyid = $tslp->id;
        $entry->consentoptionid = $optionid;
        $DB->insert_record('tool_sitepolicy_localised_consent', $entry);
        $this->assertTrue($version->is_complete());
    }

    /**
     * Test archive
     */
    public function test_archive() {
        global $DB;

        $this->resetAfterTest();
        /** @var \tool_sitepolicy_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_sitepolicy');

        $sitepolicy = $generator->create_published_policy([]);
        $rows = $DB->get_records('tool_sitepolicy_policy_version');
        $this->assertEquals(1, count($rows));
        $row = reset($rows);
        $version = new policyversion($row->id);
        $this->assertNotNull($version->get_timepublished());
        $this->assertNull($version->get_timearchived());

        $version->archive();
        $this->assertNotNull($version->get_timepublished());
        $this->assertNotNull($version->get_timearchived());

        $version2 = new policyversion($version->get_id());
        $this->assertEquals($version->get_timepublished(), $version2->get_timepublished());
        $this->assertEquals($version2->get_timearchived(), $version2->get_timearchived());

        // Ensure we can archive it again with no change.
        $version2->archive();

        $version3 = new policyversion($version->get_id());
        $this->assertEquals($version->get_timepublished(), $version2->get_timepublished());
        $this->assertEquals($version2->get_timearchived(), $version2->get_timearchived());
        $this->assertEquals($version3->get_timepublished(), $version2->get_timepublished());
        $this->assertEquals($version2->get_timearchived(), $version3->get_timearchived());
    }

    /**
 * Test archive cannot be executed if the version is not published.
 */
    public function test_archive_exception_if_not_published() {
        $this->resetAfterTest();
        $this->expectException('coding_exception');
        $this->expectExceptionMessage('Cannot archive unpublished version');

        /** @var \tool_sitepolicy_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_sitepolicy');

        $sitepolicy = $generator->create_published_policy([]);
        $version = policyversion::new_policy_draft($sitepolicy);
        $version->archive();
    }

    /**
     * Test publish
     */
    public function test_publish() {
        global $DB;

        $this->resetAfterTest();
        /** @var \tool_sitepolicy_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_sitepolicy');

        $sitepolicy = $generator->create_draft_policy([]);
        $rows = $DB->get_records('tool_sitepolicy_policy_version');
        $this->assertEquals(1, count($rows));
        $row = reset($rows);
        $version = new policyversion($row->id);
        $this->assertNull($version->get_timepublished());
        $this->assertNull($version->get_timearchived());
        $this->assertTrue($version->is_draft());
        $this->assertFalse($version->is_archived());

        $version->publish();
        $this->assertNotNull($version->get_timepublished());
        $this->assertNull($version->get_timearchived());
        $this->assertFalse($version->is_draft());
        $this->assertFalse($version->is_archived());

        $version2 = new policyversion($version->get_id());
        $this->assertEquals($version->get_timepublished(), $version2->get_timepublished());
        $this->assertEquals($version2->get_timearchived(), $version2->get_timearchived());

        $version2->set_timepublished(12345);
        $version2->save();
        $this->assertEquals(12345, $version2->get_timepublished());
        $version2 = new policyversion($version->get_id());
        $this->assertEquals(12345, $version2->get_timepublished());

        // Finally confirm  that we can't publish it again.
        $this->expectException('coding_exception');
        $this->expectExceptionMessage('Cannot publish version that is already published');
        $version2->publish();
    }

    // Not testing clone_content explicitly here as it is uses only localisedcpolicy methods and is also
    // used extensively in the data generators

    /**
     * Test has_active
     *
     * @dataProvider data_get_versionlist
     **/
    public function test_has_active($debugkey, $options) {

        $this->resetAfterTest();
        /** @var \tool_sitepolicy_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_sitepolicy');

        $sitepolicy = $generator->create_multiversion_policy($options);

        $hasdraft = $options['hasdraft'];
        $numpublished = $options['numpublished'];
        $allarchived = $options['allarchived'];

        $expected = $numpublished > 0 && !$allarchived;
        $this->assertEquals($expected, policyversion::has_active($sitepolicy));
    }

    /**
     * Test get_languages
     **/
    public function test_get_languages() {

        $this->resetAfterTest();
        /** @var \tool_sitepolicy_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_sitepolicy');

        $options = [
            'authorid' => 2,
            'languages' => 'en, nl, he',
            'langprefix' =>',nl ,he ',
            'title' => 'Test policy all',
            'statement' => 'Policy statement all',
            'numoptions' => 3,
            'consentstatement' => 'Consent statement all',
            'providetext' => 'yes',
            'withheldtext' => 'no',
            'mandatory' => 'first'
        ];

        $sitepolicy = $generator->create_published_policy($options);
        $version = policyversion::from_policy_latest($sitepolicy);

        $languages = $version->get_languages();

        $this->assertEquals(3, count($languages));
        $expected = ['en', 'he', 'nl'];
        foreach ($languages as $language) {
            $this->assertContains($language->language, $expected);
            $this->assertEquals((int)($language->language == 'en'), $language->isprimary);
        }

        // Now test getting them formatted.
        $languages = $version->get_languages(true);
        $expected = ['English', 'Hebrew', 'Dutch; Flemish'];
        $this->assertEquals(3, count($languages));
        foreach ($languages as $language) {
            $shortlang = preg_replace('#^([a-zA-Z0-9]+) .*?$#', '$1', $language->language);
            $this->assertContains($shortlang, $expected);
            $this->assertEquals((int)($shortlang == 'English'), $language->isprimary);
        }
    }

    public function test_incomplete_language_translations() {
        $this->resetAfterTest();
        /** @var \tool_sitepolicy_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_sitepolicy');
        $options = [
            'authorid' => 2,
            'languages' => 'en, nl, he',
            'langprefix' =>',nl ,he ',
            'title' => 'Test policy all',
            'statement' => 'Policy statement all',
            'numoptions' => 3,
            'consentstatement' => 'Consent statement all',
            'providetext' => 'yes',
            'withheldtext' => 'no',
            'mandatory' => 'first'
        ];

        $sitepolicy = $generator->create_published_policy($options);
        $version = policyversion::from_policy_latest($sitepolicy);

        $generator->create_draft_policy([
            'sitepolicy' => $sitepolicy,
            'authorid' => 2,
            'languages' => 'en, nl',
            'langprefix' =>',nl ',
            'title' => 'Next policy all',
            'statement' => 'Next policy statement all',
            'numoptions' => 3,
            'consentstatement' => 'Next consent statement all',
            'providetext' => 'next yes',
            'withheldtext' => 'next no',
            'mandatory' => 'first'
        ]);

        $draft = policyversion::from_policy_latest($sitepolicy);
        $this->assertNotEquals($version->get_id(), $draft->get_id());

        // Add a new option to the english localisation.
        $localisation = $draft->get_primary_localisedpolicy();
        $statements = $localisation->get_statements();
        $statementids = array_map(function($item){
            return $item->dataid;
        }, $statements);
        $consent = new \stdClass();
        $consent->dataid = 0;
        $consent->mandatory = false;
        $consent->statement = 'My new statement';
        $consent->provided = 'Too right';
        $consent->withheld = 'Nah';
        $statements[] = $consent;
        $localisation->set_statements($statements);
        $localisation->save();
        $statements = $localisation->get_statements();
        $newstatementids = array_map(function($item){
            return $item->dataid;
        }, $statements);
        $missingids = array_diff($newstatementids, $statementids);
        self::assertCount(1, $missingids);
        $missingid = reset($missingids);

        // Reload the draft.
        $draft = policyversion::from_policy_latest($sitepolicy);

        // Confirm that it is incomplete.
        // Expected because English now has four options, but Dutch still has only three.
        $this->assertTrue($draft->has_incomplete_language_translations());
        $incomplete = $draft->get_incomplete_language_translations();
        self::assertCount(1, $incomplete);
        self::assertEquals(['Dutch; Flemish'], $incomplete);

        // Quickly confirm we cannot publish this draft as it is no complete.
        self::assertFalse($draft->is_complete());
        try {
            $draft->publish();
            $this->fail('It should not be possible to publish a draft version that is not complete.');
        } catch (\Exception $e) {
            self::assertInstanceOf(\coding_exception::class, $e);
            self::assertContains('Cannot publish incomplete version', $e->getMessage());
        }

        // Add the forth option to Dutch.
        $localisation = localisedpolicy::from_version($draft, ['language' => 'nl']);
        $statements = $localisation->get_statements();
        $consent = new \stdClass();
        $consent->dataid = $missingid;
        $consent->mandatory = false;
        $consent->statement = 'My new statement';
        $consent->provided = 'Too right';
        $consent->withheld = 'Nah';
        $statements[] = $consent;
        $localisation->set_statements($statements);
        $localisation->save();

        // Reload the draft.
        $draft = policyversion::from_policy_latest($sitepolicy);

        // Confirm that the world is once again balanced.
        $this->assertFalse($draft->has_incomplete_language_translations());
        $incomplete = $draft->get_incomplete_language_translations();
        self::assertCount(0, $incomplete);
    }
}