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

/**
 * @group totara_reportbuilder
 */
class totara_rb_content_restrictions_testcase extends advanced_testcase {
    use totara_reportbuilder\phpunit\report_testing;

    private $users, $positions, $organisations, $hierarchy, $reportid, $report, $wrapper;

    protected function tearDown() {
        $this->users = null;
        $this->positions = null;
        $this->organisations = null;
        $this->hierarchy = null;
        $this->reportid = null;
        $this->report = null;
        $this->wrapper = null;

        parent::tearDown();
    }

    protected function setUp() {
        global $DB;
        parent::setup();
        $this->setAdminUser();
        $this->resetAfterTest();

        $generator = $this->getDataGenerator();
        $hierarchy_generator = $this->getDataGenerator()->get_plugin_generator('totara_hierarchy');

        $this->wrapper = "SELECT base.id
                            FROM {user} base
                           WHERE ";

        for ($index = 1; $index <= 18; $index++) {
            $this->users[$index] = $generator->create_user();
        }

        $pframe = $hierarchy_generator->create_framework('position', array('fullname' => 'pframe', 'idnumber' => 'pframe'));
        $pos100 = $hierarchy_generator->create_hierarchy($pframe->id, 'position', array('fullname' => 'pos100', 'idnumber' => 'pos100'));
        $pos200 = $hierarchy_generator->create_hierarchy($pframe->id, 'position', array('fullname' => 'pos200', 'idnumber' => 'pos200'));
        $pos110 = $hierarchy_generator->create_hierarchy($pframe->id, 'position', array('fullname' => 'pos110', 'idnumber' => 'pos110', 'parentid' => $pos100->id));
        $pos120 = $hierarchy_generator->create_hierarchy($pframe->id, 'position', array('fullname' => 'pos120', 'idnumber' => 'pos120', 'parentid' => $pos100->id));
        $pos111 = $hierarchy_generator->create_hierarchy($pframe->id, 'position', array('fullname' => 'pos111', 'idnumber' => 'pos111', 'parentid' => $pos110->id));
        $pos112 = $hierarchy_generator->create_hierarchy($pframe->id, 'position', array('fullname' => 'pos112', 'idnumber' => 'pos112', 'parentid' => $pos110->id));

        $this->positions = array(
            'path100' => array(),
            'path200' => array(),
            'path110' => array(),
            'path120' => array(),
            'path111' => array(),
            'path112' => array(),
        );

        $oframe = $hierarchy_generator->create_framework('organisation', array('fullname' => 'oframe', 'idnumber' => 'oframe'));
        $org100 = $hierarchy_generator->create_hierarchy($oframe->id, 'organisation', array('fullname' => 'org100', 'idnumber' => 'org100'));
        $org200 = $hierarchy_generator->create_hierarchy($oframe->id, 'organisation', array('fullname' => 'org200', 'idnumber' => 'org200'));
        $org110 = $hierarchy_generator->create_hierarchy($oframe->id, 'organisation', array('fullname' => 'org110', 'idnumber' => 'org110', 'parentid' => $org100->id));
        $org120 = $hierarchy_generator->create_hierarchy($oframe->id, 'organisation', array('fullname' => 'org120', 'idnumber' => 'org120', 'parentid' => $org100->id));
        $org111 = $hierarchy_generator->create_hierarchy($oframe->id, 'organisation', array('fullname' => 'org111', 'idnumber' => 'org111', 'parentid' => $org110->id));
        $org112 = $hierarchy_generator->create_hierarchy($oframe->id, 'organisation', array('fullname' => 'org112', 'idnumber' => 'org112', 'parentid' => $org110->id));

        $this->organisations = array(
            'path100' => array(),
            'path200' => array(),
            'path110' => array(),
            'path120' => array(),
            'path111' => array(),
            'path112' => array(),
        );

        // Create job assignments.
        $this->hierarchy = array();
        for ($index = 1; $index <= 18; $index++) {
            $data = array(
                'userid' => $this->users[$index]->id,
                'fullname' => "User-{$this->users[$index]->id} Assignment-1",
                'idnumber' => "path200",
                'positionid' => $pos200->id,
                'organisationid' => $org200->id,
            );
            $topja = \totara_job\job_assignment::create($data);
            $this->hierarchy[$topja->userid] = array();
            $this->organisations['path200'][] = $topja->userid;
            $this->positions['path200'][] = $topja->userid;

            ++$index;
            $data = array(
                'userid' => $this->users[$index]->id,
                'fullname' => "User-{$this->users[$index]->id} Assignment-1",
                'idnumber' => "path100",
                'positionid' => $pos100->id,
                'organisationid' => $org100->id,
            );
            $topja = \totara_job\job_assignment::create($data);
            $this->hierarchy[$topja->userid] = array();
            $this->organisations['path100'][] = $topja->userid;
            $this->positions['path100'][] = $topja->userid;

            ++$index;
            $data = array(
                'userid' => $this->users[$index]->id,
                'fullname' => "User-{$this->users[$index]->id} Assignment-1",
                'idnumber' => "path120",
                'positionid' => $pos120->id,
                'organisationid' => $org120->id,
                'managerjaid' => $topja->id,
            );
            $midja = \totara_job\job_assignment::create($data);
            $this->hierarchy[$topja->userid][$midja->userid] = array();
            $this->organisations['path120'][] = $midja->userid;
            $this->positions['path120'][] = $midja->userid;

            ++$index;
            $data = array(
                'userid' => $this->users[$index]->id,
                'fullname' => "User-{$this->users[$index]->id} Assignment-1",
                'idnumber' => "path110",
                'positionid' => $pos110->id,
                'organisationid' => $org110->id,
                'managerjaid' => $topja->id,
            );
            $midja = \totara_job\job_assignment::create($data);
            $this->hierarchy[$topja->userid][$midja->userid] = array();
            $this->organisations['path110'][] = $midja->userid;
            $this->positions['path110'][] = $midja->userid;

            ++$index;
            $data = array(
                'userid' => $this->users[$index]->id,
                'fullname' => "User-{$this->users[$index]->id} Assignment-1",
                'idnumber' => "path111",
                'positionid' => $pos111->id,
                'organisationid' => $org111->id,
                'managerjaid' => $midja->id,
            );
            $subja = \totara_job\job_assignment::create($data);
            $this->hierarchy[$topja->userid][$midja->userid][$subja->userid] = $subja->userid;
            $this->organisations['path111'][] = $subja->userid;
            $this->positions['path111'][] = $subja->userid;

            ++$index;
            $data = array(
                'userid' => $this->users[$index]->id,
                'fullname' => "User-{$this->users[$index]->id} Assignment-1",
                'idnumber' => "path112",
                'positionid' => $pos112->id,
                'organisationid' => $org112->id,
                'managerjaid' => $midja->id,
            );
            $subja = \totara_job\job_assignment::create($data);
            $this->hierarchy[$topja->userid][$midja->userid][$subja->userid] = $subja->userid;
            $this->organisations['path112'][] = $subja->userid;
            $this->positions['path112'][] = $subja->userid;
        }

        $this->reportid = $this->create_report('user', 'Test User Report');
        $config = (new rb_config())->set_nocache(true);
        $this->report = reportbuilder::create($this->reportid, $config);

        $update = $DB->get_record('report_builder', array('id' => $this->reportid));
        $update->accessmode = REPORT_BUILDER_ACCESS_MODE_NONE;
        $update->contentmode = REPORT_BUILDER_CONTENT_MODE_ALL;
        $DB->update_record('report_builder', $update);
    }

    function test_rb_content_restriction_position_equal() {
        global $DB, $USER;

        reportbuilder::update_setting($this->reportid, 'current_pos_content', 'enable', 1);
        reportbuilder::update_setting($this->reportid, 'current_pos_content', 'recursive', 0); //CONTENT_POS_EQUAL

        // Admin shouldn't see anyone.
        $content = new rb_current_pos_content($USER->id);
        list($contentsql, $params) = $content->sql_restriction('base.id', $this->reportid);
        $results = $DB->get_records_sql($this->wrapper . $contentsql, $params);
        $this->assertEquals(0, count($results));

        foreach ($this->hierarchy as $top => $team) {
            $content = new rb_current_pos_content($top);
            list($contentsql, $params) = $content->sql_restriction('base.id', $this->reportid);
            $results = $DB->get_records_sql($this->wrapper . $contentsql, $params);
            $this->assertEquals(3, count($results));

            $jobs = $DB->get_records('job_assignment', array('userid' => $top));
            $job = array_shift($jobs);
            foreach ($results as $uid => $record) {
                $this->assertTrue(in_array($uid, $this->positions[$job->idnumber]));
            }

            foreach ($team as $mid => $staff) {
                $content = new rb_current_pos_content($mid);
                list($contentsql, $params) = $content->sql_restriction('base.id', $this->reportid);
                $results = $DB->get_records_sql($this->wrapper . $contentsql, $params);
                $this->assertEquals(3, count($results));

                $jobs = $DB->get_records('job_assignment', array('userid' => $mid));
                $job = array_shift($jobs);
                foreach ($results as $uid => $record) {
                    $this->assertTrue(in_array($uid, $this->positions[$job->idnumber]));
                }

                foreach ($staff as $sub) {
                    $content = new rb_current_pos_content($sub);
                    list($contentsql, $params) = $content->sql_restriction('base.id', $this->reportid);
                    $results = $DB->get_records_sql($this->wrapper . $contentsql, $params);
                    $this->assertEquals(3, count($results));

                    $jobs = $DB->get_records('job_assignment', array('userid' => $sub));
                    $job = array_shift($jobs);
                    foreach ($results as $uid => $record) {
                        $this->assertTrue(in_array($uid, $this->positions[$job->idnumber]));
                    }
                }
            }
        }
    }

    function test_rb_content_restriction_position_equalandbelow() {
        global $DB, $USER;

        reportbuilder::update_setting($this->reportid, 'current_pos_content', 'enable', 1);
        reportbuilder::update_setting($this->reportid, 'current_pos_content', 'recursive', 1); //CONTENT_POS_EQUALANDBELOW

        // Admin shouldn't see anyone.
        $content = new rb_current_pos_content($USER->id);
        list($contentsql, $params) = $content->sql_restriction('base.id', $this->reportid);
        $results = $DB->get_records_sql($this->wrapper . $contentsql, $params);
        $this->assertEquals(0, count($results));

        foreach ($this->hierarchy as $top => $team) {

            if (!empty($team)) {
                $expected = array_merge(
                    $this->positions['path100'],
                    $this->positions['path110'],
                    $this->positions['path120'],
                    $this->positions['path111'],
                    $this->positions['path112']
                );
            } else {
                $expected = $this->positions['path200'];
            }

            $content = new rb_current_pos_content($top);
            list($contentsql, $params) = $content->sql_restriction('base.id', $this->reportid);
            $results = $DB->get_records_sql($this->wrapper . $contentsql, $params);
            $this->assertEquals(count($expected), count($results));
            foreach ($results as $uid => $record) {
                $this->assertTrue(in_array($uid, $expected));
            }

            foreach ($team as $mid => $staff) {
                if (!empty($staff)) {
                    $expected = array_merge(
                        $this->positions['path110'],
                        $this->positions['path111'],
                        $this->positions['path112']
                    );
                } else {
                    $expected = $this->positions['path120'];
                }

                $content = new rb_current_pos_content($mid);
                list($contentsql, $params) = $content->sql_restriction('base.id', $this->reportid);
                $results = $DB->get_records_sql($this->wrapper . $contentsql, $params);
                $this->assertEquals(count($expected), count($results));
                foreach ($results as $uid => $record) {
                    $this->assertTrue(in_array($uid, $expected));
                }

                foreach ($staff as $sub) {
                    $content = new rb_current_pos_content($sub);
                    list($contentsql, $params) = $content->sql_restriction('base.id', $this->reportid);
                    $results = $DB->get_records_sql($this->wrapper . $contentsql, $params);
                    $this->assertEquals(3, count($results));

                    $jobs = $DB->get_records('job_assignment', array('userid' => $sub));
                    $job = array_shift($jobs);
                    foreach ($results as $uid => $record) {
                        $this->assertTrue(in_array($uid, $this->positions[$job->idnumber]));
                    }
                }
            }
        }
    }

    function test_rb_content_restriction_position_below() {
        global $DB;

        reportbuilder::update_setting($this->reportid, 'current_pos_content', 'enable', 1);
        reportbuilder::update_setting($this->reportid, 'current_pos_content', 'recursive', 2); //CONTENT_POS_BELOW

        foreach ($this->hierarchy as $top => $team) {
            $expected = array();
            if (!empty($team)) {
                $expected = array_merge(
                    $this->positions['path110'],
                    $this->positions['path120'],
                    $this->positions['path111'],
                    $this->positions['path112']
                );
            }
            $content = new rb_current_pos_content($top);
            list($contentsql, $params) = $content->sql_restriction('base.id', $this->reportid);
            $results = $DB->get_records_sql($this->wrapper . $contentsql, $params);
            $this->assertEquals(count($expected), count($results));
            foreach ($results as $uid => $record) {
                $this->assertTrue(in_array($uid, $expected), "Unexpected user({$uid})");
            }

            foreach ($team as $mid => $staff) {
                $expected = array();
                if (!empty($staff)) {
                    $expected = array_merge(
                        $this->positions['path111'],
                        $this->positions['path112']
                    );
                }
                $content = new rb_current_pos_content($mid);
                list($contentsql, $params) = $content->sql_restriction('base.id', $this->reportid);
                $results = $DB->get_records_sql($this->wrapper . $contentsql, $params);
                $this->assertEquals(count($expected), count($results));
                foreach ($results as $uid => $record) {
                    $this->assertTrue(in_array($uid, $expected));
                }

                foreach ($staff as $sub => $users) {
                    $content = new rb_current_pos_content($sub);
                    list($contentsql, $params) = $content->sql_restriction('base.id', $this->reportid);
                    $results = $DB->get_records_sql($this->wrapper . $contentsql, $params);
                    $this->assertEquals(0, count($results));
                }
            }
        }
    }

    function test_rb_content_restriction_organisation_equal() {
        global $DB, $USER;

        reportbuilder::update_setting($this->reportid, 'current_org_content', 'enable', 1);
        reportbuilder::update_setting($this->reportid, 'current_org_content', 'recursive', 0); //CONTENT_org_EQUAL

        // Admin shouldn't see anyone.
        $content = new rb_current_org_content($USER->id);
        list($contentsql, $params) = $content->sql_restriction('base.id', $this->reportid);
        $results = $DB->get_records_sql($this->wrapper . $contentsql, $params);
        $this->assertEquals(0, count($results));

        foreach ($this->hierarchy as $top => $team) {
            $content = new rb_current_org_content($top);
            list($contentsql, $params) = $content->sql_restriction('base.id', $this->reportid);
            $results = $DB->get_records_sql($this->wrapper . $contentsql, $params);
            $this->assertEquals(3, count($results));

            $jobs = $DB->get_records('job_assignment', array('userid' => $top));
            $job = array_shift($jobs);
            foreach ($results as $uid => $record) {
                $this->assertTrue(in_array($uid, $this->organisations[$job->idnumber]));
            }

            foreach ($team as $mid => $staff) {
                $content = new rb_current_org_content($mid);
                list($contentsql, $params) = $content->sql_restriction('base.id', $this->reportid);
                $results = $DB->get_records_sql($this->wrapper . $contentsql, $params);
                $this->assertEquals(3, count($results));

                $jobs = $DB->get_records('job_assignment', array('userid' => $mid));
                $job = array_shift($jobs);
                foreach ($results as $uid => $record) {
                    $this->assertTrue(in_array($uid, $this->organisations[$job->idnumber]));
                }

                foreach ($staff as $sub) {
                    $content = new rb_current_org_content($sub);
                    list($contentsql, $params) = $content->sql_restriction('base.id', $this->reportid);
                    $results = $DB->get_records_sql($this->wrapper . $contentsql, $params);
                    $this->assertEquals(3, count($results));

                    $jobs = $DB->get_records('job_assignment', array('userid' => $sub));
                    $job = array_shift($jobs);
                    foreach ($results as $uid => $record) {
                        $this->assertTrue(in_array($uid, $this->organisations[$job->idnumber]));
                    }
                }
            }
        }
    }

    function test_rb_content_restriction_organisation_equalandbelow() {
        global $DB, $USER;

        reportbuilder::update_setting($this->reportid, 'current_org_content', 'enable', 1);
        reportbuilder::update_setting($this->reportid, 'current_org_content', 'recursive', 1); //CONTENT_org_EQUALANDBELOW

        // Admin shouldn't see anyone.
        $content = new rb_current_org_content($USER->id);
        list($contentsql, $params) = $content->sql_restriction('base.id', $this->reportid);
        $results = $DB->get_records_sql($this->wrapper . $contentsql, $params);
        $this->assertEquals(0, count($results));

        foreach ($this->hierarchy as $top => $team) {

            if (!empty($team)) {
                $expected = array_merge(
                    $this->organisations['path100'],
                    $this->organisations['path110'],
                    $this->organisations['path120'],
                    $this->organisations['path111'],
                    $this->organisations['path112']
                );
            } else {
                $expected = $this->organisations['path200'];
            }

            $content = new rb_current_org_content($top);
            list($contentsql, $params) = $content->sql_restriction('base.id', $this->reportid);
            $results = $DB->get_records_sql($this->wrapper . $contentsql, $params);
            $this->assertEquals(count($expected), count($results));
            foreach ($results as $uid => $record) {
                $this->assertTrue(in_array($uid, $expected));
            }

            foreach ($team as $mid => $staff) {
                if (!empty($staff)) {
                    $expected = array_merge(
                        $this->organisations['path110'],
                        $this->organisations['path111'],
                        $this->organisations['path112']
                    );
                } else {
                    $expected = $this->organisations['path120'];
                }

                $content = new rb_current_org_content($mid);
                list($contentsql, $params) = $content->sql_restriction('base.id', $this->reportid);
                $results = $DB->get_records_sql($this->wrapper . $contentsql, $params);
                $this->assertEquals(count($expected), count($results));
                foreach ($results as $uid => $record) {
                    $this->assertTrue(in_array($uid, $expected));
                }

                foreach ($staff as $sub) {
                    $content = new rb_current_org_content($sub);
                    list($contentsql, $params) = $content->sql_restriction('base.id', $this->reportid);
                    $results = $DB->get_records_sql($this->wrapper . $contentsql, $params);
                    $this->assertEquals(3, count($results));

                    $jobs = $DB->get_records('job_assignment', array('userid' => $sub));
                    $job = array_shift($jobs);
                    foreach ($results as $uid => $record) {
                        $this->assertTrue(in_array($uid, $this->organisations[$job->idnumber]));
                    }
                }
            }
        }
    }

    function test_rb_content_restriction_organisation_below() {
        global $DB;

        reportbuilder::update_setting($this->reportid, 'current_org_content', 'enable', 1);
        reportbuilder::update_setting($this->reportid, 'current_org_content', 'recursive', 2); //CONTENT_org_BELOW

        foreach ($this->hierarchy as $top => $team) {
            $expected = array();
            if (!empty($team)) {
                $expected = array_merge(
                    $this->organisations['path110'],
                    $this->organisations['path120'],
                    $this->organisations['path111'],
                    $this->organisations['path112']
                );
            }
            $content = new rb_current_org_content($top);
            list($contentsql, $params) = $content->sql_restriction('base.id', $this->reportid);
            $results = $DB->get_records_sql($this->wrapper . $contentsql, $params);
            $this->assertEquals(count($expected), count($results));
            foreach ($results as $uid => $record) {
                $this->assertTrue(in_array($uid, $expected), "Unexpected user({$uid})");
            }

            foreach ($team as $mid => $staff) {
                $expected = array();
                if (!empty($staff)) {
                    $expected = array_merge(
                        $this->organisations['path111'],
                        $this->organisations['path112']
                    );
                }
                $content = new rb_current_org_content($mid);
                list($contentsql, $params) = $content->sql_restriction('base.id', $this->reportid);
                $results = $DB->get_records_sql($this->wrapper . $contentsql, $params);
                $this->assertEquals(count($expected), count($results));
                foreach ($results as $uid => $record) {
                    $this->assertTrue(in_array($uid, $expected));
                }

                foreach ($staff as $sub => $users) {
                    $content = new rb_current_org_content($sub);
                    list($contentsql, $params) = $content->sql_restriction('base.id', $this->reportid);
                    $results = $DB->get_records_sql($this->wrapper . $contentsql, $params);
                    $this->assertEquals(0, count($results));
                }
            }
        }
    }

    function test_rb_content_restriction_own_record() {
        global $DB;

        reportbuilder::update_setting($this->reportid, 'user_content', 'enable', 1);
        reportbuilder::update_setting($this->reportid, 'user_content', 'who', 1); // USER_OWN

        foreach ($this->users as $user) {
            $content = new rb_user_content($user->id);
            list($contentsql, $params) = $content->sql_restriction('base.id', $this->reportid);

            $results = $DB->get_records_sql($this->wrapper . $contentsql, $params);
            $this->assertEquals(1, count($results));
            $result = array_shift($results);
            $this->assertEquals($user->id, $result->id);
        }
    }

    function test_rb_content_restriction_direct_reports() {
        global $DB;

        reportbuilder::update_setting($this->reportid, 'user_content', 'enable', 1);
        reportbuilder::update_setting($this->reportid, 'user_content', 'who', 2); // USER_DIRECT_REPORTS

        foreach ($this->hierarchy as $top => $team) {
            $content = new rb_user_content($top);
            list($contentsql, $params) = $content->sql_restriction('base.id', $this->reportid);

            $results = $DB->get_records_sql($this->wrapper . $contentsql, $params);
            $this->assertEquals(count($team), count($results));
            foreach ($results as $uid => $record) {
                $this->assertTrue(in_array($uid, array_keys($team)), "Unexpected user({$uid}");
            }

            foreach ($team as $mid => $staff) {
                $content = new rb_user_content($mid);
                list($contentsql, $params) = $content->sql_restriction('base.id', $this->reportid);

                $results = $DB->get_records_sql($this->wrapper . $contentsql, $params);
                $this->assertEquals(count($staff), count($results));
                foreach ($results as $uid => $record) {
                    $this->assertTrue(in_array($uid, array_keys($staff)));
                }

                foreach ($staff as $sub => $users) {
                    $content = new rb_user_content($sub);
                    list($contentsql, $params) = $content->sql_restriction('base.id', $this->reportid);

                    $results = $DB->get_records_sql($this->wrapper . $contentsql, $params);
                    $this->assertEquals(0, count($results));
                }
            }
        }
    }

    function test_rb_content_restriction_indirect_reports() {
        global $DB;

        reportbuilder::update_setting($this->reportid, 'user_content', 'enable', 1);
        reportbuilder::update_setting($this->reportid, 'user_content', 'who', 4); // USER_INDIRECT_REPORTS

        foreach ($this->hierarchy as $top => $team) {
            $content = new rb_user_content($top);
            list($contentsql, $params) = $content->sql_restriction('base.id', $this->reportid);

            $results = $DB->get_records_sql($this->wrapper . $contentsql, $params);
            if (!empty($team)) {
                $staff = array();
                foreach ($team as $subs) {
                    $staff = array_merge($staff, $subs);
                }
                $this->assertEquals(count($staff), count($results));
                foreach ($results as $uid => $record) {
                    $this->assertTrue(in_array($uid, $staff));
                }

            } else {
                $this->assertEquals(0, count($results));
            }

            foreach ($team as $mid => $staff) {
                $content = new rb_user_content($mid);
                list($contentsql, $params) = $content->sql_restriction('base.id', $this->reportid);

                $results = $DB->get_records_sql($this->wrapper . $contentsql, $params);
                $this->assertEquals(0, count($results));

                foreach ($staff as $sub => $users) {
                    $content = new rb_user_content($sub);
                    list($contentsql, $params) = $content->sql_restriction('base.id', $this->reportid);

                    $results = $DB->get_records_sql($this->wrapper . $contentsql, $params);
                    $this->assertEquals(0, count($results));
                }
            }
        }
    }

    function test_rb_content_restriction_temp_reports() {
        global $DB;

        reportbuilder::update_setting($this->reportid, 'user_content', 'enable', 1);
        reportbuilder::update_setting($this->reportid, 'user_content', 'who', 8); // USER_TEMP_REPORTS

        $tempman = $this->getDataGenerator()->create_user();
        $data = array(
            'userid' => $tempman->id,
            'fullname' => "Temporary Manager",
            'idnumber' => "tempman",
        );
        $tempja = \totara_job\job_assignment::create($data);

        $expected = array();
        foreach ($this->hierarchy as $uid => $staff) {
            $data = array(
                'userid' => $uid,
                'fullname' => "Temporary Staff",
                'idnumber' => "tempstaff",
                'tempmanagerjaid' => $tempja->id,
                'tempmanagerexpirydate' =>1893456000
            );
            \totara_job\job_assignment::create($data);

            $expected[] = $uid;
        }


        foreach ($this->users as $user) {
            $content = new rb_user_content($user->id);
            list($contentsql, $params) = $content->sql_restriction('base.id', $this->reportid);
            $results = $DB->get_records_sql($this->wrapper . $contentsql, $params);
            $this->assertEquals(0, count($results));
        }

        $content = new rb_user_content($tempman->id);
        list($contentsql, $params) = $content->sql_restriction('base.id', $this->reportid);
        $results = $DB->get_records_sql($this->wrapper . $contentsql, $params);
        $this->assertEquals(count($expected), count($results));
        foreach ($results as $uid => $record) {
            $this->assertTrue(in_array($uid, $expected), "Unexpected user({$uid})");
        }
    }

}
