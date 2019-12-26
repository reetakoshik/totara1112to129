<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2015 onwards Totara Learning Solutions LTD
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
 * @author Valerii Kuznetsov <valerii.kuznetsov@totaralms.com>
 * @package totara_reportbuilder
 */

defined('MOODLE_INTERNAL') || die();

/**
 * @group totara_reportbuilder
 */
class totara_reportbuilder_rb_global_restriction_core_testcase extends advanced_testcase {
    use totara_reportbuilder\phpunit\report_testing;

    /**
     * @var stdClass user that has restriction (restricted user).
     */
    protected $user = null;

    /**
     * @var stdClass user that visible in restriction (records to view)
     */
    protected $uservis = null;

    /**
     * @var stdClass user that is non visible in restriction (not in records to view)
     */
    protected $usernonvis = null;

    /**
     * @var rb_global_restriction Restriction assigned to user
     */
    protected $restr = null;

    /**
     * @var totara_reportbuilder_generator
     */
    protected $reportgen = null;

    protected function tearDown() {
        $this->user = null;
        $this->uservis = null;
        $this->usernonvis = null;
        $this->restr = null;
        $this->reportgen = null;
        parent::tearDown();
    }

    protected function setUp() {
        global $CFG;
        parent::setUp();

        $this->resetAfterTest();
        $this->setAdminUser();

        $CFG->enableglobalrestrictions = 1;

        $this->user = $this->getDataGenerator()->create_user();
        $this->uservis = $this->getDataGenerator()->create_user();
        $this->usernonvis = $this->getDataGenerator()->create_user();

        /** @var totara_reportbuilder_generator $reportgen */
        $reportgen = $this->getDataGenerator()->get_plugin_generator('totara_reportbuilder');

        $this->restr = $reportgen->create_global_restriction(array('active' => 1));
        $reportgen->assign_global_restriction_record(array('restrictionid' => $this->restr->id, "prefix" => "user",
                "itemid" => $this->uservis->id));
        $reportgen->assign_global_restriction_user(array('restrictionid' => $this->restr->id, "prefix" => "user",
                "itemid" => $this->user->id));
        $this->reportgen = $reportgen;
    }

    /**
     * Test report that support global restrictions and restricted base table via "add_global_report_restriction_join".
     */
    public function test_base_add_join() {
        global $DB;

        $rid = $this->create_report('user', 'Test user report 1');
        $DB->set_field('report_builder', 'globalrestriction', '1', array('id' => $rid));

        $config = (new rb_config())->set_nocache(true);
        $report = reportbuilder::create($rid, $config);
        $this->add_column($report, 'user', 'id', null, null, null, 0);

        // Test report without restrictions.
        $report = reportbuilder::create($rid);
        list($sql, $params, $cache) = $report->build_query(false, true);
        $records = $DB->get_records_sql($sql, $params);

        $this->assertCount(5, $records); // All users, including admin, guest, and system.

        // Test report with restrictions.
        $globalrestrictionset = rb_global_restriction_set::create_from_ids($report, array($this->restr->id));
        $config = (new rb_config())->set_global_restriction_set($globalrestrictionset);
        $report = reportbuilder::create($rid, $config);
        $this->assertNotNull($report->globalrestrictionset);
        list($sql, $params, $cache) = $report->build_query(false, true);
        $records = $DB->get_records_sql($sql, $params);

        $this->assertCount(1, $records);
        $this->assertEquals($this->uservis->id, current($records)->id);
    }

    /**
     * Test report that support global restriction and restricted base sub query via "get_global_report_restriction_join".
     */
    public function test_base_get_join() {
        global $DB;

        $rid = $this->create_report('course_completion_all', 'Test course completion report');
        $DB->set_field('report_builder', 'globalrestriction', '1', array('id' => $rid));

        $config = (new rb_config())->set_nocache(true);
        $report = reportbuilder::create($rid, $config);
        $this->add_column($report, 'user', 'id', null, null, null, 0);

        // Create course and mock completion data.
        $now = time();

        $course = $this->getDataGenerator()->create_course();
        $this->getDataGenerator()->enrol_user($this->usernonvis->id, $course->id);
        $this->getDataGenerator()->enrol_user($this->uservis->id, $course->id);
        $DB->insert_record('course_completions', array(
            "course" => $course->id,
            "userid" => $this->usernonvis->id,
            "status" => 50,
            "timeenrolled" => $now,
            "timestarted" => $now
        ));
        $DB->insert_record('course_completions', array(
            "course" => $course->id,
            "userid" => $this->uservis->id,
            "status" => 50,
            "timeenrolled" => $now,
            "timestarted" => $now
        ));

        // Test without restrictions.
        $report = reportbuilder::create($rid);
        $this->assertNull($report->globalrestrictionset);
        list($sql, $params, $cache) = $report->build_query(false, true);
        $records = $DB->get_records_sql($sql, $params);
        $this->assertCount(2, $records);

        // Test with restrictions.
        $globalrestrictionset = rb_global_restriction_set::create_from_ids($report, array($this->restr->id));

        // Instantiate report with restrictions.
        $config = (new rb_config())->set_global_restriction_set($globalrestrictionset);
        $report = reportbuilder::create($rid, $config);
        $this->assertNotNull($report->globalrestrictionset);
        list($sql, $params, $cache) = $report->build_query(false, true);
        $records = $DB->get_records_sql($sql, $params);

        $this->assertCount(1, $records);
        $this->assertEquals($this->uservis->id, current($records)->user_id);
    }

    /**
     * Test report that support global restriction and restricted join sub query via "get_global_report_restriction_join".
     */
    public function test_join_get_join() {
        global $DB;
        $rid = $this->create_report('cohort', 'Test audience report');
        $DB->set_field('report_builder', 'globalrestriction', '1', array('id' => $rid));

        $config = (new rb_config())->set_nocache(true);
        $report = reportbuilder::create($rid, $config);
        $this->add_column($report, 'user', 'id', null, null, null, 0);

        $cohort = $this->getDataGenerator()->create_cohort();
        cohort_add_member($cohort->id, $this->usernonvis->id);
        cohort_add_member($cohort->id, $this->uservis->id);

        // Test without restrictions.
        $report = reportbuilder::create($rid);
        $this->assertNull($report->globalrestrictionset);
        list($sql, $params, $cache) = $report->build_query(false, true);
        $records = $DB->get_recordset_sql($sql, $params);
        // Count records. It's iterator and it doesn't implement count.
        $count = 0;
        foreach ($records as $record) {
            $count++;
        }
        $this->assertEquals(2, $count);

        // Test with restrictions.
        $globalrestrictionset = rb_global_restriction_set::create_from_ids($report, array($this->restr->id));

        // Instantiate report with restrictions.
        $config = (new rb_config())->set_global_restriction_set($globalrestrictionset);
        $report = reportbuilder::create($rid, $config);
        $this->assertNotNull($report->globalrestrictionset);
        list($sql, $params, $cache) = $report->build_query(false, true);
        $records = $DB->get_recordset_sql($sql, $params);
        $record = $records->current();

        // Count records. It's iterator and it doesn't implement count.
        $count = 0;
        foreach ($records as $record) {
            $count++;
        }

        $this->assertEquals(1, $count);
        $this->assertEquals($this->uservis->id, $record->user_id);
    }

    /**
     * Test that "allusers" works.
     */
    public function test_allusers_restriction() {
        global $DB;
        $restrall = $this->reportgen->create_global_restriction(array('active' => 1, 'allrecords' => 1));
        $this->reportgen->assign_global_restriction_user(array('restrictionid' => $restrall->id, "prefix" => "user",
                "itemid" => $this->user->id));

        $rid = $this->create_report('user', 'Test user report 1');
        $DB->set_field('report_builder', 'globalrestriction', '1', array('id' => $rid));

        $config = (new rb_config())->set_nocache(true);
        $report = reportbuilder::create($rid, $config);
        $this->add_column($report, 'user', 'id', null, null, null, 0);

        // Show none when no restrictions.
        set_config('noactiverestrictionsbehaviour', 0, 'reportbuilder');

        // Test with no selected restrictions.
        $globalrestrictionset = rb_global_restriction_set::create_from_ids($report, array());
        $config = (new rb_config())->set_global_restriction_set($globalrestrictionset);
        $report = reportbuilder::create($rid, $config);
        $this->assertNotNull($report->globalrestrictionset);
        list($sql, $params, $cache) = $report->build_query(false, true);
        $records = $DB->get_records_sql($sql, $params);
        // There must not be any guessing.
        $this->assertCount(0, $records);

        // Test report with restrictions.
        $globalrestrictionset = rb_global_restriction_set::create_from_ids($report, array($restrall->id));
        $config = (new rb_config())->set_global_restriction_set($globalrestrictionset);
        $report = reportbuilder::create($rid, $config);
        $this->assertNull($report->globalrestrictionset);
        list($sql, $params, $cache) = $report->build_query(false, true);
        $records = $DB->get_records_sql($sql, $params);
        // All returned.
        $this->assertCount(5, $records);

        // Test report with restrictions.
        $globalrestrictionset = rb_global_restriction_set::create_from_ids($report, array($restrall->id, $this->restr->id));
        $config = (new rb_config())->set_global_restriction_set($globalrestrictionset);
        $report = reportbuilder::create($rid, $config);
        $this->assertNull($report->globalrestrictionset);
        list($sql, $params, $cache) = $report->build_query(false, true);
        $records = $DB->get_records_sql($sql, $params);
        // All returned again.
        $this->assertCount(5, $records);
    }
}
