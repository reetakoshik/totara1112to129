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
 * @author David Curry <david.curry@totaralearning.com>
 * @package totara
 * @subpackage reportbuilder
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

class totara_rb_content_hierarchy_restrictions_testcase extends advanced_testcase {
    use totara_reportbuilder\phpunit\report_testing;

    /**
     * Setup the test data structure
     */

    protected function setup_data() {
        global $DB;

        $data = new class() {
            /** @var array Test users */
            public $users;
            /** @var array Test hierarchy structure */
            public $hierarchy;
            /** @var int id of test report */
            public $reportid;
            /** @var reportbuilder Report instance */
            public $report;
        };

        $this->setAdminUser();
        $this->resetAfterTest();

        $generator = $this->getDataGenerator();
        $hierarchy_generator = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');

        for ($index = 1; $index <= 7; $index++) {
            $data->users[$index] = $generator->create_user();
        }

        $pframe = $hierarchy_generator->create_framework('position', ['fullname' => 'pframe', 'idnumber' => 'pframe']);
        $pos100 = $hierarchy_generator->create_hierarchy($pframe->id, 'position', ['fullname' => 'pos100', 'idnumber' => 'pos100']);
        $pos200 = $hierarchy_generator->create_hierarchy($pframe->id, 'position', ['fullname' => 'pos200', 'idnumber' => 'pos200']);
        $pos110 = $hierarchy_generator->create_hierarchy($pframe->id, 'position', ['fullname' => 'pos110', 'idnumber' => 'pos110',
            'parentid' => $pos100->id]);
        $pos120 = $hierarchy_generator->create_hierarchy($pframe->id, 'position', ['fullname' => 'pos120', 'idnumber' => 'pos120',
            'parentid' => $pos100->id]);
        $pos111 = $hierarchy_generator->create_hierarchy($pframe->id, 'position', ['fullname' => 'pos111', 'idnumber' => 'pos111',
            'parentid' => $pos110->id]);
        $pos112 = $hierarchy_generator->create_hierarchy($pframe->id, 'position', ['fullname' => 'pos112', 'idnumber' => 'pos112',
            'parentid' => $pos110->id]);

        $pframe2 = $hierarchy_generator->create_framework('position', ['fullname' => 'pframe2', 'idnumber' => 'pframe2']);
        $f2pos100 = $hierarchy_generator->create_hierarchy($pframe2->id, 'position', ['fullname' => 'f2pos100', 'idnumber' => 'f2pos100']);

        $positions = [
            $pos100->id => $pos100,
            $pos200->id => $pos200,
            $pos110->id => $pos110,
            $pos120->id => $pos120,
            $pos111->id => $pos111,
            $pos112->id => $pos112,
            $f2pos100->id => $f2pos100,
        ];
        $posusers = [];

        $oframe = $hierarchy_generator->create_framework('organisation', ['fullname' => 'oframe', 'idnumber' => 'oframe']);
        $org100 = $hierarchy_generator->create_hierarchy($oframe->id, 'organisation', ['fullname' => 'org100', 'idnumber' => 'org100']);
        $org200 = $hierarchy_generator->create_hierarchy($oframe->id, 'organisation', ['fullname' => 'org200', 'idnumber' => 'org200']);
        $org110 = $hierarchy_generator->create_hierarchy($oframe->id, 'organisation', ['fullname' => 'org110', 'idnumber' => 'org110',
            'parentid' => $org100->id]);
        $org120 = $hierarchy_generator->create_hierarchy($oframe->id, 'organisation', ['fullname' => 'org120', 'idnumber' => 'org120',
            'parentid' => $org100->id]);
        $org111 = $hierarchy_generator->create_hierarchy($oframe->id, 'organisation', ['fullname' => 'org111', 'idnumber' => 'org111',
            'parentid' => $org110->id]);
        $org112 = $hierarchy_generator->create_hierarchy($oframe->id, 'organisation', ['fullname' => 'org112', 'idnumber' => 'org112',
            'parentid' => $org110->id]);

        $oframe2 = $hierarchy_generator->create_framework('organisation', ['fullname' => 'oframe2', 'idnumber' => 'oframe2']);
        $f2org100 = $hierarchy_generator->create_hierarchy($oframe2->id, 'organisation', ['fullname' => 'f2org100', 'idnumber' => 'f2org100']);

        $organisations = [
            $org100->id => $org100,
            $org200->id => $org200,
            $org110->id => $org110,
            $org120->id => $org120,
            $org111->id => $org111,
            $org112->id => $org112,
            $f2org100->id => $f2org100,
        ];
        $orgusers = [];

        // Create job assignment hierarchy and store data for verification.
        $data->hierarchy = [];

        $tocreate = [
            1 => [
                ['pos' => $pos100, 'org' => $org100, 'parent' => ''],
                ['pos' => $pos200, 'org' => $org100, 'parent' => ''],
            ],
            2 => [
                ['pos' => $pos110, 'org' => $org100, 'parent' => '1:0'],
            ],
            3 => [
                ['pos' => $pos111, 'org' => $org100, 'parent' => '2:0'],
            ],
            4 => [
                ['pos' => $pos200, 'org' => $org100, 'parent' => ''],
                ['pos' => $f2pos100, 'org' => $f2org100, 'parent' => ''],
            ],
            5 => [
                ['pos' => $pos100, 'org' => $org100, 'parent' => ''],
            ],
            6 => [
                ['pos' => $pos110, 'org' => $org110, 'parent' => '5:0'],
            ],
            7 => [
                ['pos' => $pos111, 'org' => $org111, 'parent' => '6:0'],
            ],
        ];

        // Now do the actual job assignments
        foreach ($tocreate as $idx => $assignments) {
            $userid = $data->users[$idx]->id;
            $data->hierarchy[$idx] = [
                'ja' => [],
                'pos' => ['equal' => [], 'equalbelow' => [], 'below' => []],
                'org' => ['equal' => [], 'equalbelow' => [], 'below' => []],
            ];
            $userhierarchy = &$data->hierarchy[$idx];

            foreach ($assignments as $tstja) {
                $pos = $tstja['pos'];
                $org = $tstja['org'];
                $parent = $tstja['parent'];

                $jadata = [
                    'userid' => $userid,
                    'fullname' => "User-{$userid} Assignment-1",
                    'idnumber' => $pos->idnumber . $org->idnumber,
                    'positionid' => $pos->id,
                    'organisationid' => $org->id,
                ];

                $parentidx = 0;
                if (!empty($parent)) {
                    $tstparent = explode(':', $parent);
                    $parentidx = (int)$tstparent[0];
                    $parentjaidx = (int)$tstparent[1];

                    $parenthierarchy = &$data->hierarchy[$parentidx];
                    $jadata['managerjaid'] = $parenthierarchy['ja'][$parentjaidx]->id;
                }

                $ja = \totara_job\job_assignment::create($jadata);
                $userhierarchy['ja'][] = $ja;

                // Add posuser
                if(!array_key_exists($pos->id, $posusers)) {
                    $posusers[$pos->id] = [$idx];
                } else {
                    if (!in_array($idx, $posusers[$pos->id])) {
                        $posusers[$pos->id][] = $idx;
                    }
                }

                // Add orguser
                if (!array_key_exists($org->id, $orgusers)) {
                    $orgusers[$org->id] = [$idx];
                } else {
                    if (!in_array($idx, $orgusers[$org->id])) {
                        $orgusers[$org->id][] = $idx;
                    }
                }

                // We can already add current pos and org to the expected results
                if (!in_array($pos->id, $userhierarchy['pos']['equal'])) {
                    $userhierarchy['pos']['equal'][] = $pos->id;
                }
                if (!in_array($pos->id, $userhierarchy['pos']['equalbelow'])) {
                    $userhierarchy['pos']['equalbelow'][] = $pos->id;
                }
                if (!in_array($pos->id, $userhierarchy['pos']['below'])) {
                    $userhierarchy['pos']['below'][] = $pos->id;
                }
                // Look up the pos hierarchy in database directly.
                $belowposs = $DB->get_records_select('pos', "path LIKE " . $DB->sql_concat(':path', "'/%'"), array('path' => $pos->path));
                foreach ($belowposs as $belowpos) {
                    if (!in_array($belowpos->id, $userhierarchy['pos']['equalbelow'])) {
                        $userhierarchy['pos']['equalbelow'][] = $belowpos->id;
                    }
                    if (!in_array($belowpos->id, $userhierarchy['pos']['below'])) {
                        $userhierarchy['pos']['below'][] = $belowpos->id;
                    }
                }
                // Dialogs need the parents up to the top, no matter if they are above the user restricted positions.
                $aboveposs = $DB->get_records_select('pos', ":path LIKE " . $DB->sql_concat('path', "'/%'"), array('path' => $pos->path));
                foreach ($aboveposs as $abovepos) {
                    if (!in_array($abovepos->id, $userhierarchy['pos']['equal'])) {
                        $userhierarchy['pos']['equal'][] = $abovepos->id;
                    }
                    if (!in_array($abovepos->id, $userhierarchy['pos']['equalbelow'])) {
                        $userhierarchy['pos']['equalbelow'][] = $abovepos->id;
                    }
                    if (!in_array($abovepos->id, $userhierarchy['pos']['below'])) {
                        $userhierarchy['pos']['below'][] = $abovepos->id;
                    }
                }

                if (!in_array($org->id, $userhierarchy['org']['equal'])) {
                    $userhierarchy['org']['equal'][] = $org->id;
                }
                if (!in_array($org->id, $userhierarchy['org']['equalbelow'])) {
                    $userhierarchy['org']['equalbelow'][] = $org->id;
                }
                if (!in_array($org->id, $userhierarchy['org']['below'])) {
                    $userhierarchy['org']['below'][] = $org->id;
                }
                // Look up the org hierarchy in database directly.
                $beloworgs = $DB->get_records_select('org', "path LIKE " . $DB->sql_concat(':path', "'/%'"), array('path' => $org->path));
                foreach ($beloworgs as $beloworg) {
                    if (!in_array($beloworg->id, $userhierarchy['org']['equalbelow'])) {
                        $userhierarchy['org']['equalbelow'][] = $beloworg->id;
                    }
                    if (!in_array($beloworg->id, $userhierarchy['org']['below'])) {
                        $userhierarchy['org']['below'][] = $beloworg->id;
                    }
                }
                // Dialogs need the parents up to the top, no matter if they are above the user restricted organisations.
                $aboveorgs = $DB->get_records_select('org', ":path LIKE " . $DB->sql_concat('path', "'/%'"), array('path' => $org->path));
                foreach ($aboveorgs as $aboveorg) {
                    if (!in_array($aboveorg->id, $userhierarchy['org']['equal'])) {
                        $userhierarchy['org']['equal'][] = $aboveorg->id;
                    }
                    if (!in_array($aboveorg->id, $userhierarchy['org']['equalbelow'])) {
                        $userhierarchy['org']['equalbelow'][] = $aboveorg->id;
                    }
                    if (!in_array($aboveorg->id, $userhierarchy['org']['below'])) {
                        $userhierarchy['org']['below'][] = $aboveorg->id;
                    }
                }
            }
        }

        // The Report
        $data->reportid = $this->create_report('user', 'Test User Report');
        $config = (new rb_config())->set_nocache(true);
        $data->report = reportbuilder::create($data->reportid, $config);

        $update = $DB->get_record('report_builder', ['id' => $data->reportid]);
        $update->accessmode = REPORT_BUILDER_ACCESS_MODE_NONE;
        $update->contentmode = REPORT_BUILDER_CONTENT_MODE_ALL;
        $DB->update_record('report_builder', $update);

        $data->wrapper = [
            'pos' =>
                "SELECT base.id
                   FROM {pos} base
                  WHERE ",
            'org' =>
                "SELECT base.id
                   FROM {org} base
                   WHERE ",
        ];

        return $data;
    }

    /**
     * Test position sql_hierarchy_restriction with CONTENT_POS_EQUAL
     */
    function test_rb_content_hierarchy_restriction_position_equal() {
        global $DB, $USER;

        $data = $this->setup_data();

        reportbuilder::update_setting($data->reportid, 'current_pos_content', 'enable', 1);
        reportbuilder::update_setting($data->reportid, 'current_pos_content', 'recursive', 0); //CONTENT_POS_EQUAL

        // Admin shouldn't see anyone.
        $content = new rb_current_pos_content($USER->id);
        list($contentsql, $params) = $content->sql_hierarchy_restriction('base.id', $data->reportid);
        $results = $DB->get_records_sql($data->wrapper['pos'] . $contentsql, $params);
        $this->assertEquals(0, count($results));

        foreach ($data->hierarchy as $idx => $orgpos) {
            $userid = $data->users[$idx]->id;
            $content = new rb_current_pos_content($userid);
            list($contentsql, $params) = $content->sql_hierarchy_restriction('base.id', $data->reportid);
            $results = $DB->get_records_sql($data->wrapper['pos'] . $contentsql, $params);
            $this->assertEquals(count($orgpos['pos']['equal']), count($results));

            foreach ($results as $posid => $record) {
                $this->assertTrue(in_array($posid, $orgpos['pos']['equal']));
            }
        }
    }

    /**
     * Test position sql_hierarchy_restriction with CONTENT_POS_EQUALANDBELOW
     */
    function test_rb_content_restriction_position_equalandbelow() {
        global $DB, $USER;

        $data = $this->setup_data();

        reportbuilder::update_setting($data->reportid, 'current_pos_content', 'enable', 1);
        reportbuilder::update_setting($data->reportid, 'current_pos_content', 'recursive', 1); //CONTENT_POS_EQUALANDBELOW

        // Admin shouldn't see anyone.
        $content = new rb_current_pos_content($USER->id);
        list($contentsql, $params) = $content->sql_hierarchy_restriction('base.id', $data->reportid);
        $results = $DB->get_records_sql($data->wrapper['pos'] . $contentsql, $params);
        $this->assertEquals(0, count($results));

        foreach ($data->hierarchy as $idx => $orgpos) {
            $userid = $data->users[$idx]->id;
            $content = new rb_current_pos_content($userid);
            list($contentsql, $params) = $content->sql_hierarchy_restriction('base.id', $data->reportid);
            $results = $DB->get_records_sql($data->wrapper['pos'] . $contentsql, $params);
            $this->assertEquals(count($orgpos['pos']['equalbelow']), count($results));

            foreach ($results as $posid => $record) {
                $this->assertTrue(in_array($posid, $orgpos['pos']['equalbelow']));
            }
        }
    }

    /**
     * Test position sql_hierarchy_restriction with CONTENT_POS_BELOW
     */
    function test_rb_content_restriction_position_below() {
        global $DB, $USER;

        $data = $this->setup_data();

        reportbuilder::update_setting($data->reportid, 'current_pos_content', 'enable', 1);
        reportbuilder::update_setting($data->reportid, 'current_pos_content', 'recursive', 2); //CONTENT_POS_BELOW

        // Admin shouldn't see anyone.
        $content = new rb_current_pos_content($USER->id);
        list($contentsql, $params) = $content->sql_hierarchy_restriction('base.id', $data->reportid);
        $results = $DB->get_records_sql($data->wrapper['pos'] . $contentsql, $params);
        $this->assertEquals(0, count($results));

        foreach ($data->hierarchy as $idx => $orgpos) {
            $userid = $data->users[$idx]->id;
            $content = new rb_current_pos_content($userid);
            list($contentsql, $params) = $content->sql_hierarchy_restriction('base.id', $data->reportid);
            $results = $DB->get_records_sql($data->wrapper['pos'] . $contentsql, $params);
            $this->assertEquals(count($orgpos['pos']['below']), count($results));

            foreach ($results as $posid => $record) {
                $this->assertTrue(in_array($posid, $orgpos['pos']['below']));
            }
        }
    }

    /**
     * Test organisation sql_hierarchy_restriction with CONTENT_ORG_EQUAL
     */
    function test_rb_content_restriction_organisation_equal() {
        global $DB, $USER;

        $data = $this->setup_data();

        reportbuilder::update_setting($data->reportid, 'current_org_content', 'enable', 1);
        reportbuilder::update_setting($data->reportid, 'current_org_content', 'recursive', 0); //CONTENT_org_EQUAL

        // Admin shouldn't see anyone.
        $content = new rb_current_org_content($USER->id);
        list($contentsql, $params) = $content->sql_hierarchy_restriction('base.id', $data->reportid);
        $results = $DB->get_records_sql($data->wrapper['org'] . $contentsql, $params);
        $this->assertEquals(0, count($results));

        foreach ($data->hierarchy as $idx => $orgpos) {
            $userid = $data->users[$idx]->id;
            $content = new rb_current_org_content($userid);
            list($contentsql, $params) = $content->sql_hierarchy_restriction('base.id', $data->reportid);
            $results = $DB->get_records_sql($data->wrapper['org'] . $contentsql, $params);
            $this->assertEquals(count($orgpos['org']['equal']), count($results));

            foreach ($results as $orgid => $record) {
                $this->assertTrue(in_array($orgid, $orgpos['org']['equal']));
            }
        }
    }

    /**
     * Test organisation sql_hierarchy_restriction with CONTENT_ORG_EQUALANDBELOW
     */
    function test_rb_content_restriction_organisation_equalandbelow() {
        global $DB, $USER;

        $data = $this->setup_data();

        reportbuilder::update_setting($data->reportid, 'current_org_content', 'enable', 1);
        reportbuilder::update_setting($data->reportid, 'current_org_content', 'recursive', 1); //CONTENT_org_EQUALANDBELOW

        // Admin shouldn't see anyone.
        $content = new rb_current_org_content($USER->id);
        list($contentsql, $params) = $content->sql_hierarchy_restriction('base.id', $data->reportid);
        $results = $DB->get_records_sql($data->wrapper['org'] . $contentsql, $params);
        $this->assertEquals(0, count($results));

        foreach ($data->hierarchy as $idx => $orgpos) {
            $userid = $data->users[$idx]->id;
            $content = new rb_current_org_content($userid);
            list($contentsql, $params) = $content->sql_hierarchy_restriction('base.id', $data->reportid);
            $results = $DB->get_records_sql($data->wrapper['org'] . $contentsql, $params);
            $this->assertEquals(count($orgpos['org']['equalbelow']), count($results));

            foreach ($results as $orgid => $record) {
                $this->assertTrue(in_array($orgid, $orgpos['org']['equalbelow']));
            }
        }
    }

    /**
     * Test organisation sql_hierarchy_restriction with CONTENT_POS_EQUALANDBELOW
     */
    function test_rb_content_restriction_organisation_below() {
        global $DB, $USER;

        $data = $this->setup_data();

        reportbuilder::update_setting($data->reportid, 'current_org_content', 'enable', 1);
        reportbuilder::update_setting($data->reportid, 'current_org_content', 'recursive', 2); //CONTENT_org_BELOW

        // Admin shouldn't see anyone.
        $content = new rb_current_org_content($USER->id);
        list($contentsql, $params) = $content->sql_hierarchy_restriction('base.id', $data->reportid);
        $results = $DB->get_records_sql($data->wrapper['org'] . $contentsql, $params);
        $this->assertEquals(0, count($results));

        foreach ($data->hierarchy as $idx => $orgpos) {
            $userid = $data->users[$idx]->id;
            $content = new rb_current_org_content($userid);
            list($contentsql, $params) = $content->sql_hierarchy_restriction('base.id', $data->reportid);
            $results = $DB->get_records_sql($data->wrapper['org'] . $contentsql, $params);
            $this->assertEquals(count($orgpos['org']['below']), count($results));

            foreach ($results as $orgid => $record) {
                $this->assertTrue(in_array($orgid, $orgpos['org']['below']));
            }
        }
    }
}
