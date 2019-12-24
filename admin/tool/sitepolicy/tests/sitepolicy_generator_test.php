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
 * Sitepolicy generator tests
 */
class tool_sitepolicy_sitepolicy_generator_test extends \advanced_testcase {

    /**
     * Data provider for test_sitepolicy_generator.
     */
    public function data_sitepolicy_generator() {
        return [
            [
                'defaults',
                'draft',
                []
            ],
            [
                'singlelang',
                'draft',
                [
                    'authorid' => 2,
                    'languages' => 'en',
                    'title' => 'Test policy singlelang',
                    'statement' => 'Policy statement singlelang',
                    'consentstatement' => 'Consent statement singlelang',
                    'providetext' => 'yes',
                    'withheldtext' => 'no',
                    'mandatory' => 'all',
                ]
            ],
            [
                'singlelang_multiconsent',
                'draft',
                [
                    'authorid' => 2,
                    'languages' => 'en',
                    'title' => 'Test policy singlelang_multiconsent',
                    'statement' => 'Policy statement singlelang_multiconsent',
                    'statementformat' => FORMAT_MOODLE,
                    'numoptions' => 2,
                    'consentstatement' => 'Consent statement singlelang_multiconsent',
                    'providetext' => 'yes',
                    'withheldtext' => 'no',
                    'mandatory' => 'first',
                ]
            ],
            [
                'multilang_multiconsent',
                'draft',
                [
                    'authorid' => 2,
                    'languages' => 'en, nl',
                    'langprefix' => ',nl ',
                    'title' => 'Test policy multilang_multiconsent',
                    'statement' => 'Policy statement multilang_multiconsent',
                    'statementformat' => FORMAT_HTML,
                    'numoptions' => 2,
                    'consentstatement' => 'Consent statement multilang_multiconsent',
                    'providetext' => 'Yes',
                    'withheldtext' => 'No',
                    'mandatory' => 'all',
                ]
            ],
            [
                'published',
                'published',
                [
                    'authorid' => 2,
                    'languages' => 'en, nl',
                    'langprefix' => ',nl ',
                    'title' => 'Test policy published',
                    'statement' => 'Policy statement published',
                    'statementformat' => FORMAT_PLAIN,
                    'numoptions' => 2,
                    'consentstatement' => 'Consent statement published',
                    'providetext' => 'yes',
                    'withheldtext' => 'no',
                    'mandatory' => 'none'
                ]
            ]
        ];
    }

    /**
     * Test database rows created by the sitepolicy_generator
     *
     * @dataProvider data_sitepolicy_generator
     */
    public function test_sitepolicy_generator($debugkey, $status, $options) {
        global $DB;

        $this->resetAfterTest();
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_sitepolicy');

        switch ($status) {
            case 'published' :
                $sitepolicy = $generator->create_published_policy($options);
                break;
            default :
                $sitepolicy = $generator->create_draft_policy($options);
                break;
        }


        // One site_policy
        $rows = $DB->get_records('tool_sitepolicy_site_policy');
        $this->assertEquals(1, count($rows));

        // One policy_version
        $rows = $DB->get_records('tool_sitepolicy_policy_version');
        $this->assertEquals(1, count($rows));
        $row = array_shift($rows);
        $this->assertNotNull($row->timecreated);
        $this->assertNull($row->timearchived);

        if ($status == 'published') {
            $this->assertNotNull($row->timepublished);
        } else {
            $this->assertNull($row->timepublished);
        }

        $languages = isset($options['languages']) ? explode(',', $options['languages']) : ['en'];
        $languages = array_map(function($l) { return trim($l);}, $languages);
        $prefixes = isset($options['langprefix']) ? explode(',', $options['langprefix']) : [''];
        $prefixes = array_map(
            function($l) {
                if ($l != '') {
                    return trim($l) . ' ';
                } else {
                    return $l;
                }
            }, $prefixes);
        $numoptions = $options['numoptions'] ?? 1;

        // # lang localised policies
        $rows = $DB->get_records('tool_sitepolicy_localised_policy');
        $this->assertEquals(count($languages), count($rows));
        foreach ($rows as $row) {
            $idx = array_search($row->language, $languages);
            $isprimary = $idx !== false && $idx == 0;
            $this->assertEquals($isprimary, (bool)$row->isprimary);

            $prefix = $idx < count($prefixes) ? $prefixes[$idx] : '';
            if (isset($options['title'])) {
                $this->assertEquals($prefix . $options['title'], $row->title);
            }
            if (isset($options['statement'])) {
                $this->assertEquals($prefix . $options['statement'], $row->policytext);
            }
            $expectedformat = $options['statementformat'] ?? FORMAT_HTML;
            $this->assertEquals($expectedformat, $row->policytextformat);

            if (isset($options['authorid'])) {
                $this->assertEquals($options['authorid'], $row->authorid);
            }
            if (isset($options['time'])) {
                $this->assertEquals($options['time'], $row->timecreated);
            }
        }

        // # consent options
        $rows = $DB->get_records('tool_sitepolicy_consent_options');
        $this->assertEquals($numoptions, count($rows));

        // # localised_consent
        $rows = $DB->get_records('tool_sitepolicy_localised_consent');
        $this->assertEquals(count($languages) * $numoptions, count($rows));

        // No user_consent
        $rows = $DB->get_records('tool_sitepolicy_user_consent');
        $this->assertEquals(0, count($rows));
    }


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
                    'languages' => 'en',
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
            [
                'userconsented',
                [
                    'hasdraft' => true,
                    'numpublished' => 3,
                    'allarchived' => false,
                    'authorid' => 2,
                    'languages' => 'en, nl',
                    'langprefix' => ',nl ',
                    'title' => 'Test policy userconsented',
                    'statement' => 'Policy statement userconsented',
                    'numoptions' => 2,
                    'consentstatement' => 'Consent statement userconsented',
                    'providetext' => 'yes',
                    'withheldtext' => 'no',
                    'mandatory' => 'none',
                    'hasconsented' => true,
                    'consentuser' => 3,
                    'consentlanguage' => 'en',
                    'consenttime' => time()
                ]
            ],
        ];
    }

    /**
     * Test create_multiversion_policy data generator
     *
     * @dataProvider data_create_multiversion_policy_generator
     **/
    public function test_create_multiversion_policy_generator($debugkey, $options) {
        global $DB;

        $this->resetAfterTest();
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_sitepolicy');

        $sitepolicy = $generator->create_multiversion_policy($options);

        $hasdraft = $options['hasdraft'] ?? true;
        $numpublished = $options['numpublished'] ?? 0;
        $allarchived = $options['allarchived'] ?? false;
        $languages = isset($options['languages']) ? explode(',', $options['languages']) : ['en'];

        // One site_policy
        $rows = $DB->get_records('tool_sitepolicy_site_policy');
        $this->assertEquals(1, count($rows));

        // # policy_version
        $rows = $DB->get_records('tool_sitepolicy_policy_version');
        $this->assertEquals((int)$hasdraft + $numpublished, count($rows));

        $drafts = array_filter($rows, function($policy) {
            return (is_null($policy->timepublished) && is_null($policy->timearchived));
        });
        $this->assertEquals((int)$hasdraft, count($drafts));

        $published = array_filter($rows, function($policy) {
            return (!is_null($policy->timepublished) && is_null($policy->timearchived));
        });
        $expectedpublished = $numpublished > 0 && !$allarchived ? 1 : 0;
        $this->assertEquals($expectedpublished, count($published));

        $archived = array_filter($rows, function($policy) {
            return (!is_null($policy->timepublished) && !is_null($policy->timearchived));
        });
        $expectedarchived = $numpublished;
        if ($numpublished > 0 && !$allarchived) {
            $expectedarchived -= 1;
        }
        $this->assertEquals($expectedarchived, count($archived));

        if ($numpublished > 0 && !$allarchived && isset($options['hasconsented']) && isset($options['consentuser'])) {
            $rows = $DB->get_records('tool_sitepolicy_user_consent');
            $numoptions = $options['numoptions'] ?? 1;
            $this->assertEquals($numoptions, count($rows));
        }
    }
}