<?php // $Id$
/*
 * This file is part of Totara LMS
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
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package totara_hierarchy
 */

/*
 * PhpUnit tests for hierarchy/lib.php with content restrictions
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}
global $CFG;
require_once($CFG->dirroot . '/totara/hierarchy/lib.php');
require_once($CFG->dirroot . '/totara/hierarchy/prefix/position/lib.php');
require_once($CFG->dirroot . '/totara/hierarchy/prefix/organisation/lib.php');


class totara_hierarchy_lib_contentrestrictions_testcase extends advanced_testcase {
    use totara_reportbuilder\phpunit\report_testing;

    /**
     * Set up data that'll be purged, exported or counted.
     */
    private function setup_data() {
        global $DB;

        $data = new class() {
            /** @var array Test users */
            public $users;
            /** @var array Test position frameworks */
            public $posfw;
            /** @var array Test positions */
            public $pos;
            /** @var array Test organisation frameworks */
            public $orgfw;
            /** @var array Test organisations */
            public $org;
            /** @var position Position hierarchy to use for tests  */
            public $position;
            /** @var orgnisation Organisation hierarchy to use for tests  */
            public $organisation;
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

        for ($index = 1; $index <= 6; $index++) {
            $data->users[$index] = $generator->create_user();
        }

        $data->position = new position();
        $data->organisation = new organisation();

        // Positions
        $data->posfw['pframe'] = $hierarchy_generator->create_framework('position', ['fullname' => 'pframe', 'idnumber' => 'pframe']);
        $data->pos['pos100'] = $hierarchy_generator->create_hierarchy($data->posfw['pframe']->id, 'position', ['fullname' => 'pos100', 'idnumber' => 'pos100',
            'depthlevel' => 1, 'sortthread' => '01']);
        $data->pos['pos200'] = $hierarchy_generator->create_hierarchy($data->posfw['pframe']->id, 'position', ['fullname' => 'pos200', 'idnumber' => 'pos200',
            'depthlevel' => 1, 'sortthread' => '02']);
        $data->pos['pos110'] = $hierarchy_generator->create_hierarchy($data->posfw['pframe']->id, 'position', ['fullname' => 'pos110', 'idnumber' => 'pos110',
            'parentid' => $data->pos['pos100']->id, 'depthlevel' => 2, 'sortthread' => '01']);
        $data->pos['pos120'] = $hierarchy_generator->create_hierarchy($data->posfw['pframe']->id, 'position', ['fullname' => 'pos120', 'idnumber' => 'pos120',
            'parentid' => $data->pos['pos100']->id, 'depthlevel' => 2, 'sortthread' => '02']);
        $data->pos['pos111'] = $hierarchy_generator->create_hierarchy($data->posfw['pframe']->id, 'position', ['fullname' => 'pos111', 'idnumber' => 'pos111',
            'parentid' => $data->pos['pos110']->id, 'depthlevel' => 3, 'sortthread' => '01']);
        $data->pos['pos112'] = $hierarchy_generator->create_hierarchy($data->posfw['pframe']->id, 'position', ['fullname' => 'pos112', 'idnumber' => 'pos112',
            'parentid' => $data->pos['pos110']->id, 'depthlevel' => 2, 'sortthread' => '02']);

        $data->posfw['pframe2'] = $hierarchy_generator->create_framework('position', ['fullname' => 'pframe2', 'idnumber' => 'pframe2']);
        $data->pos['f2pos100'] = $hierarchy_generator->create_hierarchy($data->posfw['pframe2']->id, 'position', ['fullname' => 'f2pos100', 'idnumber' => 'f2pos100']);

        // Organisations
        $data->orgfw['oframe'] = $hierarchy_generator->create_framework('organisation', ['fullname' => 'oframe', 'idnumber' => 'oframe']);
        $data->org['org100'] = $hierarchy_generator->create_hierarchy($data->orgfw['oframe']->id, 'organisation', ['fullname' => 'org100', 'idnumber' => 'org100',
            'depthlevel' => 2, 'sortthread' => '01']);
        $data->org['org200'] = $hierarchy_generator->create_hierarchy($data->orgfw['oframe']->id, 'organisation', ['fullname' => 'org200', 'idnumber' => 'org200',
            'depthlevel' => 2, 'sortthread' => '01']);
        $data->org['org110'] = $hierarchy_generator->create_hierarchy($data->orgfw['oframe']->id, 'organisation', ['fullname' => 'org110', 'idnumber' => 'org110',
            'parentid' => $data->org['org100']->id, 'depthlevel' => 2, 'sortthread' => '01']);
        $data->org['org120'] = $hierarchy_generator->create_hierarchy($data->orgfw['oframe']->id, 'organisation', ['fullname' => 'org120', 'idnumber' => 'org120',
            'parentid' => $data->org['org100']->id, 'depthlevel' => 2, 'sortthread' => '02']);
        $data->org['org111'] = $hierarchy_generator->create_hierarchy($data->orgfw['oframe']->id, 'organisation', ['fullname' => 'org111', 'idnumber' => 'org111',
            'parentid' => $data->org['org110']->id, 'depthlevel' => 3, 'sortthread' => '01']);
        $data->org['org112'] = $hierarchy_generator->create_hierarchy($data->orgfw['oframe']->id, 'organisation', ['fullname' => 'org112', 'idnumber' => 'org112',
            'parentid' => $data->org['org110']->id, 'depthlevel' => 3, 'sortthread' => '02']);

        $data->orgfw['oframe2'] = $hierarchy_generator->create_framework('organisation', ['fullname' => 'oframe2', 'idnumber' => 'oframe2']);
        $data->org['f2org100'] = $hierarchy_generator->create_hierarchy($data->orgfw['oframe2']->id, 'organisation', ['fullname' => 'f2org100', 'idnumber' => 'f2org100']);

        // The Report for content restriction definition
        $data->reportid = $this->create_report('user', 'Test User Report');
        $config = (new rb_config())->set_nocache(true);
        $data->report = reportbuilder::create($data->reportid, $config);

        $update = $DB->get_record('report_builder', ['id' => $data->reportid]);
        $update->accessmode = REPORT_BUILDER_ACCESS_MODE_NONE;
        $update->contentmode = REPORT_BUILDER_CONTENT_MODE_ALL;
        $DB->update_record('report_builder', $update);

        // User job assignments
        $tocreate = [
            1 => [
                ['pos' => $data->pos['pos100'], 'org' => $data->org['org100'], 'parent' => ''],
                ['pos' => $data->pos['f2pos100'], 'org' => $data->org['f2org100'], 'parent' => ''],
            ],
            2 => [
                ['pos' => $data->pos['pos110'], 'org' => $data->org['org100'], 'parent' => '1:0'],
            ],
            3 => [
                ['pos' => $data->pos['pos111'], 'org' => $data->org['org100'], 'parent' => '2:0'],
            ],
            4 => [
                ['pos' => $data->pos['pos100'], 'org' => $data->org['org100'], 'parent' => ''],
                ['pos' => $data->pos['pos200'], 'org' => $data->org['org200'], 'parent' => ''],
            ],
            5 => [
                ['pos' => $data->pos['pos110'], 'org' => $data->org['org110'], 'parent' => '4:0'],
            ],
        ];

        // Now do the actual job assignments
        foreach ($tocreate as $idx => $assignments) {
            $userid = $data->users[$idx]->id;
            $data->hierarchy[$idx] = [
                'ja' => [],
                'posfw' => [],
                'pos' => [],
                'orgfw' => [],
                'org' => [],
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

                if (!in_array($pos->frameworkid, $userhierarchy['posfw'])) {
                    $userhierarchy['posfw'][] = $pos->frameworkid;
                }
                if (!in_array($pos->id, $userhierarchy['pos'])) {
                    $userhierarchy['pos'][] = $pos->id;
                }
                if (!in_array($org->frameworkid, $userhierarchy['orgfw'])) {
                    $userhierarchy['orgfw'][] = $org->frameworkid;
                }
                if (!in_array($org->id, $userhierarchy['org'])) {
                    $userhierarchy['org'][] = $org->id;
                }
            }
        }

        return $data;
    }


    /**
     * Test get_framework for positions with hierarchy content restriction
     */
    function test_hierarchy_get_framework_pos_restriction() {
        $data = $this->setup_data();

        // Without contentrestiction - should get the framework
        $this->assertEquals($data->posfw['pframe2'], $data->position->get_framework($data->posfw['pframe2']->id));

        // With contentrestictions
        reportbuilder::update_setting($data->reportid, 'current_pos_content', 'enable', 1);
        reportbuilder::update_setting($data->reportid, 'current_pos_content', 'recursive', 0); //CONTENT_POS_EQUAL

        // user1 should get pframe2
        $userid = $data->users[1]->id;
        $data->position->set_content_restriction_from_report($data->reportid, $userid);
        $this->assertEquals($data->posfw['pframe2'], $data->position->get_framework($data->posfw['pframe2']->id));

        // User2 should get a result if we search for first framework (id == 0)
        $userid = $data->users[2]->id;
        $data->position->set_content_restriction_from_report($data->reportid, $userid);
        $this->assertEquals($data->posfw['pframe'], $data->position->get_framework(0));

        // user2 should get an error if we search for pframe2
        $this->expectException('moodle_exception', get_string('frameworkdoesntexist', 'totara_hierarchy', 'position'));
        $data->position->get_framework($data->posfw['pframe2']->id);
    }

    /**
     * Test get_framework for organisations with hierarchy content restriction
     */
    function test_hierarchy_get_framework_org_restriction() {
        $data = $this->setup_data();

        // Without contentrestiction - should get the framework
        $this->assertEquals($data->orgfw['oframe2'], $data->organisation->get_framework($data->orgfw['oframe2']->id));

        // With contentrestictions
        reportbuilder::update_setting($data->reportid, 'current_org_content', 'enable', 1);
        reportbuilder::update_setting($data->reportid, 'current_org_content', 'recursive', 0); //CONTENT_ORG_EQUAL

        // user1 should get oframe2
        $userid = $data->users[1]->id;
        $data->organisation->set_content_restriction_from_report($data->reportid, $userid);
        $this->assertEquals($data->orgfw['oframe2'], $data->organisation->get_framework($data->orgfw['oframe2']->id));

        // user2 should get a result if we search for a the first framework (id == 0)
        $userid = $data->users[2]->id;
        $data->organisation->set_content_restriction_from_report($data->reportid, $userid);
        $this->assertEquals($data->orgfw['oframe'], $data->organisation->get_framework(0));

        // User2 should get an error when searching for oframe2
        $this->expectException('moodle_exception', get_string('frameworkdoesntexist', 'totara_hierarchy', 'organisation'));

        $userid = $data->users[2]->id;
        $data->organisation->set_content_restriction_from_report($data->reportid, $userid);
        $data->organisation->get_framework($data->orgfw['oframe2']->id);
    }

    /**
     * Test get_frameworks for positions with hierarchy content restriction
     */
    function test_hierarchy_get_frameworks_pos_restriction() {
        $data = $this->setup_data();

        $fws = $data->position->get_frameworks();

        // should return an array of frameworks
        $this->assertTrue((bool)is_array($fws));
        // the array should include all frameworks
        $this->assertEquals(count($data->posfw), count($fws));

        foreach ($data->posfw as $fw) {
            $this->assertTrue(in_array($fw->id, array_keys($fws)));
        }

        // With contentrestictions
        reportbuilder::update_setting($data->reportid, 'current_pos_content', 'enable', 1);
        reportbuilder::update_setting($data->reportid, 'current_pos_content', 'recursive', 0); //CONTENT_POS_EQUAL

        // user1 should get both frameworks
        $userid = $data->users[1]->id;
        $data->position->set_content_restriction_from_report($data->reportid, $userid);

        $fws = $data->position->get_frameworks();

        // should return an array of frameworks
        $this->assertTrue((bool)is_array($fws));
        // the array should include all frameworks
        $this->assertEquals(count($data->posfw), count($fws));

        foreach ($data->posfw as $fw) {
            $this->assertTrue(in_array($fw->id, array_keys($fws)));
        }

        // user2 should get only pframe
        $userid = $data->users[2]->id;
        $data->position->set_content_restriction_from_report($data->reportid, $userid);

        $fws = $data->position->get_frameworks();

        // should return an array of frameworks
        $this->assertTrue((bool)is_array($fws));
        // the array should include only pframe
        $this->assertEquals(1, count($fws));
        $this->assertTrue(in_array($data->posfw['pframe']->id, array_keys($fws)));

        // user4 should get only pframe
        $userid = $data->users[4]->id;
        $data->position->set_content_restriction_from_report($data->reportid, $userid);

        $fws = $data->position->get_frameworks();

        // should return an array of frameworks
        $this->assertTrue((bool)is_array($fws));
        // the array should include only pframe
        $this->assertEquals(1, count($fws));
        $this->assertTrue(in_array($data->posfw['pframe']->id, array_keys($fws)));

        // user6 should get none
        $userid = $data->users[6]->id;
        $data->position->set_content_restriction_from_report($data->reportid, $userid);

        $fws = $data->position->get_frameworks();
        $this->assertTrue((bool)empty($fws));
    }

    /**
     * Test get_frameworks for organisations with hierarchy content restriction
     */
    function test_hierarchy_get_frameworks_org_restriction() {
        $data = $this->setup_data();

        $fws = $data->organisation->get_frameworks();

        // should return an array of frameworks
        $this->assertTrue((bool)is_array($fws));
        // the array should include all frameworks
        $this->assertEquals(count($data->orgfw), count($fws));

        foreach ($data->orgfw as $fw) {
            $this->assertTrue(in_array($fw->id, array_keys($fws)));
        }

        // With contentrestictions
        reportbuilder::update_setting($data->reportid, 'current_org_content', 'enable', 1);
        reportbuilder::update_setting($data->reportid, 'current_org_content', 'recursive', 0); //CONTENT_ORG_EQUAL

        // user1 should get both frameworks
        $userid = $data->users[1]->id;
        $data->organisation->set_content_restriction_from_report($data->reportid, $userid);

        $fws = $data->organisation->get_frameworks();

        // should return an array of frameworks
        $this->assertTrue((bool)is_array($fws));
        // the array should include all frameworks
        $this->assertEquals(count($data->orgfw), count($fws));

        foreach ($data->orgfw as $fw) {
            $this->assertTrue(in_array($fw->id, array_keys($fws)));
        }

        // user2 should only get pframe
        $userid = $data->users[2]->id;
        $data->organisation->set_content_restriction_from_report($data->reportid, $userid);

        $fws = $data->organisation->get_frameworks();

        // should return an array of frameworks
        $this->assertTrue((bool)is_array($fws));
        // the array should include only pframe
        $this->assertEquals(1, count($fws));
        $this->assertTrue(in_array($data->orgfw['oframe']->id, array_keys($fws)));

        // user4 should only get pframe
        $userid = $data->users[4]->id;
        $data->organisation->set_content_restriction_from_report($data->reportid, $userid);

        $fws = $data->organisation->get_frameworks();

        // should return an array of frameworks
        $this->assertTrue((bool)is_array($fws));
        // the array should include only pframe
        $this->assertEquals(1, count($fws));
        $this->assertTrue(in_array($data->orgfw['oframe']->id, array_keys($fws)));

        // use6 should get none
        $userid = $data->users[6]->id;
        $data->organisation->set_content_restriction_from_report($data->reportid, $userid);

        $fws = $data->organisation->get_frameworks();
        $this->assertTrue((bool)empty($fws));
    }

    /**
     * Test get_item for positions with hierarchy content restriction
     */
    function test_hierarchy_get_item_pos_restricted() {
        $data = $this->setup_data();

        // without content restrictions
        $this->assertEquals($data->pos['f2pos100'], $data->position->get_item($data->pos['f2pos100']->id));

        // add content restrictions
        reportbuilder::update_setting($data->reportid, 'current_pos_content', 'enable', 1);
        reportbuilder::update_setting($data->reportid, 'current_pos_content', 'recursive', 0); //CONTENT_POS_EQUAL

        // user1 should get f2pos100
        $userid = $data->users[1]->id;
        $data->position->set_content_restriction_from_report($data->reportid, $userid);
        $this->assertEquals($data->pos['f2pos100'], $data->position->get_item($data->pos['f2pos100']->id));

        // user2 should not get f2pos100
        $userid = $data->users[2]->id;
        $data->position->set_content_restriction_from_report($data->reportid, $userid);
        $out = $data->position->get_item($data->pos['f2pos100']->id);
        $this->assertTrue(empty($data->position->get_item($data->pos['f2pos100']->id)));
    }

    /**
     * Test get_item for organisations with hierarchy content restriction
     */
    function test_hierarchy_get_item_org_restricted() {
        $data = $this->setup_data();

        // without content restrictions
        $this->assertEquals($data->org['f2org100'], $data->organisation->get_item($data->org['f2org100']->id));

        // add content restrictions
        reportbuilder::update_setting($data->reportid, 'current_org_content', 'enable', 1);
        reportbuilder::update_setting($data->reportid, 'current_org_content', 'recursive', 0); //CONTENT_ORG_EQUAL

        // user1 should get f2org100
        $userid = $data->users[1]->id;
        $data->organisation->set_content_restriction_from_report($data->reportid, $userid);
        $this->assertEquals($data->org['f2org100'], $data->organisation->get_item($data->org['f2org100']->id));

        // user2 should not get f2org100
        $userid = $data->users[2]->id;
        $data->organisation->set_content_restriction_from_report($data->reportid, $userid);
        $this->assertTrue(empty($data->organisation->get_item($data->org['f2org100']->id)));
    }

    /**
     * Test get_items for positions with hierarchy content restriction
     */
    function test_hierarchy_get_items_pos_restricted() {
        $data = $this->setup_data();

        // without content restriction should return an array of items
        $data->position->frameworkid = $data->posfw['pframe']->id;
        $items = $data->position->get_items();
        $this->assertTrue((bool)is_array($items));
        $this->assertEquals(6, count($items));
        $data->position->frameworkid = $data->posfw['pframe2']->id;
        $items = $data->position->get_items();
        $this->assertTrue((bool)is_array($items));
        $this->assertEquals(1, count($items));

        // with content restrictions
        reportbuilder::update_setting($data->reportid, 'current_pos_content', 'enable', 1);
        reportbuilder::update_setting($data->reportid, 'current_pos_content', 'recursive', 0); //CONTENT_POS_EQUAL

        // user1 has 1 position in pframe and 1 in pframe2
        $userid = $data->users[1]->id;
        $data->position->set_content_restriction_from_report($data->reportid, $userid);

        $data->position->frameworkid = $data->posfw['pframe']->id;
        $items = $data->position->get_items();
        $this->assertTrue((bool)is_array($items));
        $this->assertEquals(1, count($items));
        $this->assertEquals($data->pos['pos100'], current($items));

        $data->position->frameworkid = $data->posfw['pframe2']->id;
        $items = $data->position->get_items();
        $this->assertTrue((bool)is_array($items));
        $this->assertEquals(1, count($items));
        $this->assertEquals($data->pos['f2pos100'], current($items));

        // user2 has 1 item in pframe, but none pframe2
        $userid = $data->users[2]->id;
        $data->position->set_content_restriction_from_report($data->reportid, $userid);

        $data->position->frameworkid = $data->posfw['pframe']->id;
        $items = $data->position->get_items();
        $this->assertTrue((bool)is_array($items));
        $this->assertEquals(2, count($items));
        $this->assertEquals($data->pos['pos100'], current($items)); // includes parent to allow hierarchy tree visualisation
        $this->assertEquals($data->pos['pos110'], next($items));

        $data->position->frameworkid = $data->posfw['pframe2']->id;
        $items = $data->position->get_items();
        $this->assertTrue((bool)is_array($items));
        $this->assertEquals(0, count($items));
    }

    /**
     * Test get_items for organisations with hierarchy content restriction
     */
    function test_hierarchy_get_items_org_restricted() {
        $data = $this->setup_data();

        // without content restriction should return an array of items
        $data->organisation->frameworkid = $data->orgfw['oframe']->id;
        $items = $data->organisation->get_items();
        $this->assertTrue((bool)is_array($items));
        $this->assertEquals(6, count($items));
        $data->organisation->frameworkid = $data->orgfw['oframe2']->id;
        $items = $data->organisation->get_items();
        $this->assertTrue((bool)is_array($items));
        $this->assertEquals(1, count($items));

        // with content restrictions
        reportbuilder::update_setting($data->reportid, 'current_org_content', 'enable', 1);
        reportbuilder::update_setting($data->reportid, 'current_org_content', 'recursive', 0); //CONTENT_ORG_EQUAL

        // user1 has 1 organisation in oframe and 1 in oframe2
        $userid = $data->users[1]->id;
        $data->organisation->set_content_restriction_from_report($data->reportid, $userid);

        $data->organisation->frameworkid = $data->orgfw['oframe']->id;
        $items = $data->organisation->get_items();
        $this->assertTrue((bool)is_array($items));
        $this->assertEquals(1, count($items));
        $this->assertEquals($data->org['org100'], current($items));

        $data->organisation->frameworkid = $data->orgfw['oframe2']->id;
        $items = $data->organisation->get_items();
        $this->assertTrue((bool)is_array($items));
        $this->assertEquals(1, count($items));
        $this->assertEquals($data->org['f2org100'], current($items));

        // user2 has 1 item in oframe, but none oframe2
        $userid = $data->users[2]->id;
        $data->organisation->set_content_restriction_from_report($data->reportid, $userid);

        $data->organisation->frameworkid = $data->orgfw['oframe']->id;
        $items = $data->organisation->get_items();
        $this->assertTrue((bool)is_array($items));
        $this->assertEquals(1, count($items));
        $this->assertEquals($data->org['org100'], current($items));

        $data->organisation->frameworkid = $data->orgfw['oframe2']->id;
        $items = $data->organisation->get_items();
        $this->assertTrue((bool)is_array($items));
        $this->assertEquals(0, count($items));
    }

    /**
     * Test get_items_by_parent for positions with hierarchy content restriction
     */
    function test_hierarchy_get_items_by_parent_pos_restricted() {
        $data = $this->setup_data();

        // Without content restrictions
        // should return an array of items belonging to specified parent
        $items = $data->position->get_items_by_parent($data->pos['pos100']->id);
        $this->assertTrue((bool)is_array($items));
        $this->assertEquals(2, count($items));
        $this->assertTrue(array_key_exists($data->pos['pos110']->id, $items));
        $this->assertTrue(array_key_exists($data->pos['pos120']->id, $items));

        // if no parent specified should return root level items
        $items = $data->position->get_items_by_parent();
        $this->assertEquals(3, count($items));
        $this->assertTrue(array_key_exists($data->pos['pos100']->id, $items));
        $this->assertTrue(array_key_exists($data->pos['pos200']->id, $items));
        $this->assertTrue(array_key_exists($data->pos['f2pos100']->id, $items));

        // With content restrictions
        reportbuilder::update_setting($data->reportid, 'current_pos_content', 'enable', 1);
        reportbuilder::update_setting($data->reportid, 'current_pos_content', 'recursive', 0); //CONTENT_POS_EQUAL

        // user1 - no children of pos100
        $userid = $data->users[1]->id;
        $data->position->set_content_restriction_from_report($data->reportid, $userid);
        $items = $data->position->get_items_by_parent($data->pos['pos100']->id);
        $this->assertTrue((bool)is_array($items));
        $this->assertEquals(0, count($items));

        // user1 - 2 root items
        $items = $data->position->get_items_by_parent();
        $this->assertTrue((bool)is_array($items));
        $this->assertEquals(2, count($items));
        $this->assertTrue(array_key_exists($data->pos['pos100']->id, $items));
        $this->assertTrue(array_key_exists($data->pos['f2pos100']->id, $items));

        // user2 - 1 child of pos100
        $userid = $data->users[2]->id;
        $data->position->set_content_restriction_from_report($data->reportid, $userid);
        $items = $data->position->get_items_by_parent($data->pos['pos100']->id);
        $this->assertTrue((bool)is_array($items));
        $this->assertEquals(1, count($items));
        $this->assertTrue(array_key_exists($data->pos['pos110']->id, $items));

        // user2 - root item not allowed, but include it anyway so that we print hierarchy tree
        $items = $data->position->get_items_by_parent();
        $this->assertTrue((bool)is_array($items));
        $this->assertEquals(1, count($items));
        $this->assertTrue(array_key_exists($data->pos['pos100']->id, $items));
    }

    /**
     * Test get_items_by_parent for organisations with hierarchy content restriction
     */
    function test_hierarchy_get_items_by_parent_org_restricted() {
        $data = $this->setup_data();

        // Without content restrictions
        // should return an array of items belonging to specified parent
        $items = $data->organisation->get_items_by_parent($data->org['org100']->id);
        $this->assertTrue((bool)is_array($items));
        $this->assertEquals(2, count($items));
        $this->assertTrue(array_key_exists($data->org['org110']->id, $items));
        $this->assertTrue(array_key_exists($data->org['org120']->id, $items));

        // if no parent specified should return root level items
        $items = $data->organisation->get_items_by_parent();
        $this->assertEquals(3, count($items));
        $this->assertTrue(array_key_exists($data->org['org100']->id, $items));
        $this->assertTrue(array_key_exists($data->org['org200']->id, $items));
        $this->assertTrue(array_key_exists($data->org['f2org100']->id, $items));

        // With content restrictions
        reportbuilder::update_setting($data->reportid, 'current_org_content', 'enable', 1);
        reportbuilder::update_setting($data->reportid, 'current_org_content', 'recursive', 0); //CONTENT_ORG_EQUAL

        // user1 - no children of org100
        $userid = $data->users[1]->id;
        $data->organisation->set_content_restriction_from_report($data->reportid, $userid);
        $items = $data->organisation->get_items_by_parent($data->org['org100']->id);
        $this->assertTrue((bool)is_array($items));
        $this->assertEquals(0, count($items));

        // user1 - 2 root items
        $items = $data->organisation->get_items_by_parent();
        $this->assertTrue((bool)is_array($items));
        $this->assertEquals(2, count($items));
        $this->assertTrue(array_key_exists($data->org['org100']->id, $items));
        $this->assertTrue(array_key_exists($data->org['f2org100']->id, $items));

        // user2 - no children of org100
        $userid = $data->users[2]->id;
        $data->organisation->set_content_restriction_from_report($data->reportid, $userid);
        $items = $data->organisation->get_items_by_parent($data->org['org100']->id);
        $this->assertTrue((bool)is_array($items));
        $this->assertEquals(0, count($items));

        // user2 - 1 root items
        $items = $data->organisation->get_items_by_parent();
        $this->assertTrue((bool)is_array($items));
        $this->assertEquals(1, count($items));
        $this->assertTrue(array_key_exists($data->org['org100']->id, $items));

    }

    /**
     * Test get_all_root_items for positions with hierarchy content restriction
     */
    function test_hierarchy_get_all_root_items_pos_restricted() {
        $data = $this->setup_data();

        // Without content restriction
        // should return root items for framework
        $data->position->frameworkid = $data->posfw['pframe']->id;
        $items = $data->position->get_all_root_items();
        $this->assertTrue((bool)is_array($items));
        $this->assertEquals(2, count($items));
        $this->assertTrue(array_key_exists($data->pos['pos100']->id, $items));
        $this->assertTrue(array_key_exists($data->pos['pos200']->id, $items));

        // With content restrictions
        reportbuilder::update_setting($data->reportid, 'current_pos_content', 'enable', 1);
        reportbuilder::update_setting($data->reportid, 'current_pos_content', 'recursive', 0); //CONTENT_POS_EQUAL

        // user1 - pos100 only
        $userid = $data->users[1]->id;
        $data->position->set_content_restriction_from_report($data->reportid, $userid);
        $items = $data->position->get_all_root_items();
        $this->assertTrue((bool)is_array($items));
        $this->assertEquals(1, count($items));
        $this->assertTrue(array_key_exists($data->pos['pos100']->id, $items));

        // Return all root items of user1
        $items = $data->position->get_all_root_items(true);
        $this->assertTrue((bool)is_array($items));
        $this->assertEquals(2, count($items));
        $this->assertTrue(array_key_exists($data->pos['pos100']->id, $items));
        $this->assertTrue(array_key_exists($data->pos['f2pos100']->id, $items));
    }

    /**
     * Test get_all_root_items for organisations with hierarchy content restriction
     */
    function test_hierarchy_get_all_root_items_org_restricted() {
        $data = $this->setup_data();

        // Without content restriction
        // should return root items for framework
        $data->organisation->frameworkid = $data->orgfw['oframe']->id;
        $items = $data->organisation->get_all_root_items();
        $this->assertTrue((bool)is_array($items));
        $this->assertEquals(2, count($items));
        $this->assertTrue(array_key_exists($data->org['org100']->id, $items));
        $this->assertTrue(array_key_exists($data->org['org200']->id, $items));

        // With content restrictions
        reportbuilder::update_setting($data->reportid, 'current_org_content', 'enable', 1);
        reportbuilder::update_setting($data->reportid, 'current_org_content', 'recursive', 0); //CONTENT_ORG_EQUAL

        // user1 - org100 only
        $userid = $data->users[1]->id;
        $data->organisation->set_content_restriction_from_report($data->reportid, $userid);
        $items = $data->organisation->get_all_root_items();
        $this->assertTrue((bool)is_array($items));
        $this->assertEquals(1, count($items));
        $this->assertTrue(array_key_exists($data->org['org100']->id, $items));

        // Return all root items of user1
        $items = $data->organisation->get_all_root_items(true);
        $this->assertTrue((bool)is_array($items));
        $this->assertEquals(2, count($items));
        $this->assertTrue(array_key_exists($data->org['org100']->id, $items));
        $this->assertTrue(array_key_exists($data->org['f2org100']->id, $items));
    }

    /**
     * Test get_item_descendants for positions with hierarchy content restriction
     */
    function test_hierarchy_get_item_descendants_pos_restricted() {
        $data = $this->setup_data();

        // Without content restriction
        // should return an array of items
        $items = $data->position->get_item_descendants($data->pos['pos110']->id);
        $this->assertTrue((bool)is_array($items));
        $this->assertEquals(3, count($items));
        $this->assertTrue(array_key_exists($data->pos['pos110']->id, $items));
        $this->assertTrue(array_key_exists($data->pos['pos111']->id, $items));
        $this->assertTrue(array_key_exists($data->pos['pos112']->id, $items));

        // With content restrictions
        reportbuilder::update_setting($data->reportid, 'current_pos_content', 'enable', 1);
        reportbuilder::update_setting($data->reportid, 'current_pos_content', 'recursive', 0); //CONTENT_POS_EQUAL

        // user1 - None
        $userid = $data->users[1]->id;
        $data->position->set_content_restriction_from_report($data->reportid, $userid);
        $items = $data->position->get_item_descendants($data->pos['pos110']->id);
        $this->assertTrue((bool)is_array($items));
        $this->assertEquals(0, count($items));

        // user2 - pos110 only
        $userid = $data->users[2]->id;
        $data->position->set_content_restriction_from_report($data->reportid, $userid);
        $items = $data->position->get_item_descendants($data->pos['pos110']->id);
        $this->assertTrue((bool)is_array($items));
        $this->assertEquals(1, count($items));
        $this->assertTrue(array_key_exists($data->pos['pos110']->id, $items));

        // user3 - pos111 only
        $userid = $data->users[3]->id;
        $data->position->set_content_restriction_from_report($data->reportid, $userid);
        $items = $data->position->get_item_descendants($data->pos['pos110']->id);
        $this->assertTrue((bool)is_array($items));
        $this->assertEquals(2, count($items));
        $this->assertTrue(array_key_exists($data->pos['pos110']->id, $items)); // includes parent so that we can construct hierarchy tree
        $this->assertTrue(array_key_exists($data->pos['pos111']->id, $items));
    }

    /**
     * Test get_item_descendants for organisations with hierarchy content restriction
     */
    function test_hierarchy_get_item_descendants_org_restricted() {
        $data = $this->setup_data();

        // Without content restriction
        // should return an array of items
        $items = $data->organisation->get_item_descendants($data->org['org110']->id);
        $this->assertTrue((bool)is_array($items));
        $this->assertEquals(3, count($items));
        $this->assertTrue(array_key_exists($data->org['org110']->id, $items));
        $this->assertTrue(array_key_exists($data->org['org111']->id, $items));
        $this->assertTrue(array_key_exists($data->org['org112']->id, $items));

        // With content restrictions
        reportbuilder::update_setting($data->reportid, 'current_org_content', 'enable', 1);
        reportbuilder::update_setting($data->reportid, 'current_org_content', 'recursive', 0); //CONTENT_ORG_EQUAL

        // user1 - None
        $userid = $data->users[1]->id;
        $data->organisation->set_content_restriction_from_report($data->reportid, $userid);
        $items = $data->organisation->get_item_descendants($data->org['org110']->id);
        $this->assertTrue((bool)is_array($items));
        $this->assertEquals(0, count($items));

        // user2 - None
        $userid = $data->users[2]->id;
        $data->organisation->set_content_restriction_from_report($data->reportid, $userid);
        $items = $data->organisation->get_item_descendants($data->org['org110']->id);
        $this->assertTrue((bool)is_array($items));
        $this->assertEquals(0, count($items));

        // user3 - org110 only
        $userid = $data->users[5]->id;
        $data->organisation->set_content_restriction_from_report($data->reportid, $userid);
        $items = $data->organisation->get_item_descendants($data->org['org110']->id);
        $this->assertTrue((bool)is_array($items));
        $this->assertEquals(1, count($items));
        $this->assertTrue(array_key_exists($data->org['org110']->id, $items));
    }

    /**
     * Test get_hierarchy_item_adjacent_peer for positions with hierarchy content restriction
     */
    function test_hierarchy_get_hierarchy_item_adjacent_peer_pos_restricted() {
        $data = $this->setup_data();

        // Without content restriction
        // if an adjacent peer exists, should return its id
        $item = $data->position->get_hierarchy_item_adjacent_peer($data->pos['pos110'], HIERARCHY_ITEM_BELOW);
        $this->assertEquals($data->pos['pos120']->id, $item);
        // should return false if no adjacent peer exists in the direction specified
        $item = $data->position->get_hierarchy_item_adjacent_peer($data->pos['pos110'], HIERARCHY_ITEM_ABOVE);
        $this->assertFalse($item);

        // With content restrictions
        reportbuilder::update_setting($data->reportid, 'current_pos_content', 'enable', 1);
        reportbuilder::update_setting($data->reportid, 'current_pos_content', 'recursive', 0); //CONTENT_POS_EQUAL

        // No user in pos120
        for ($idx = 1; $idx <= 6; $idx++) {
            $userid = $data->users[$idx]->id;
            $data->position->set_content_restriction_from_report($data->reportid, $userid);
            $item = $data->position->get_hierarchy_item_adjacent_peer($data->pos['pos110'], HIERARCHY_ITEM_BELOW);
            $this->assertFalse($item);
        }
    }

    /**
     * Test get_hierarchy_item_adjacent_peer for organisations with hierarchy content restriction
     */
    function test_hierarchy_get_hierarchy_item_adjacent_peer_org_restricted() {
        $data = $this->setup_data();

        // Without content restriction
        // if an adjacent peer exists, should return its id
        $item = $data->organisation->get_hierarchy_item_adjacent_peer($data->org['org110'], HIERARCHY_ITEM_BELOW);
        $this->assertEquals($data->org['org120']->id, $item);
        // should return false if no adjacent peer exists in the direction specified
        $item = $data->organisation->get_hierarchy_item_adjacent_peer($data->org['org110'], HIERARCHY_ITEM_ABOVE);
        $this->assertFalse($item);

        // With content restrictions
        reportbuilder::update_setting($data->reportid, 'current_org_content', 'enable', 1);
        reportbuilder::update_setting($data->reportid, 'current_org_content', 'recursive', 0); //CONTENT_ORG_EQUAL

        // No user in org120
        for ($idx = 1; $idx <= 6; $idx++) {
            $userid = $data->users[$idx]->id;
            $data->organisation->set_content_restriction_from_report($data->reportid, $userid);
            $item = $data->organisation->get_hierarchy_item_adjacent_peer($data->org['org110'], HIERARCHY_ITEM_BELOW);
            $this->assertFalse($item);
        }
    }
}
