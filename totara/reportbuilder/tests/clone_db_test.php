<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2017 onwards Totara Learning Solutions LTD
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
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 * @package totara_reportbuilder
 */

defined('MOODLE_INTERNAL') || die();

/**
 * @group totara_reportbuilder
 */
class totara_reportbuilder_clone_db_testcase extends advanced_testcase {
    use totara_reportbuilder\phpunit\report_testing;

    public function test_useclonedb_off() {
        global $CFG, $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        $this->getDataGenerator()->create_user();

        // We need to be able to calculate the total count.
        set_config('allowtotalcount', 1, 'totara_reportbuilder');

        $rid = $this->create_report('user', 'Test user report 1', true);
        $DB->set_field('report_builder', 'defaultsortcolumn', 'user_id', array('id' => $rid));
        $DB->set_field('report_builder', 'defaultsortorder', SORT_ASC, array('id' => $rid));
        $config = (new rb_config())->set_nocache(true);
        $report = reportbuilder::create($rid, $config);
        $this->add_column($report, 'user', 'id', null, null, null, 0);
        $this->add_column($report, 'user', 'username', null, '', '', 0);

        $CFG->clone_dbname = $CFG->dbname;
        $reportdb = totara_get_clone_db(true);

        $report = reportbuilder::create($rid, $config);
        $reads = $reportdb->perf_get_reads();
        $writes = $reportdb->perf_get_writes();
        $report = reportbuilder::create($rid, $config);
        $report->get_filtered_count();
        $this->assertSame($reads, $reportdb->perf_get_reads());
        $this->assertSame($writes, $reportdb->perf_get_writes());

        $report = reportbuilder::create($rid, $config);
        $reads = $reportdb->perf_get_reads();
        $writes = $reportdb->perf_get_writes();
        $report->get_full_count();
        $this->assertSame($reads, $reportdb->perf_get_reads());
        $this->assertSame($writes, $reportdb->perf_get_writes());

        $report = reportbuilder::create($rid, $config);
        $reads = $reportdb->perf_get_reads();
        $writes = $reportdb->perf_get_writes();
        $report->display_table(true);
        $this->assertSame($reads, $reportdb->perf_get_reads());
        $this->assertSame($writes, $reportdb->perf_get_writes());

        $source = new \totara_reportbuilder\tabexport_source($report);
        foreach ($source as $row) {
            continue;
        }
        $source = null;
        $this->assertSame($reads, $reportdb->perf_get_reads());
        $this->assertSame($writes, $reportdb->perf_get_writes());
    }

    public function test_useclonedb_on() {
        global $CFG, $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        $this->getDataGenerator()->create_user();

        // We need to be able to calculate the total count.
        set_config('allowtotalcount', 1, 'totara_reportbuilder');

        $rid = $this->create_report('user', 'Test user report 1', true);
        $DB->set_field('report_builder', 'defaultsortcolumn', 'user_id', array('id' => $rid));
        $DB->set_field('report_builder', 'defaultsortorder', SORT_ASC, array('id' => $rid));
        $DB->set_field('report_builder', 'useclonedb', '1', array('id' => $rid));
        $config = (new rb_config())->set_nocache(true);
        $report = reportbuilder::create($rid, $config);
        $this->add_column($report, 'user', 'id', null, null, null, 0);
        $this->add_column($report, 'user', 'username', null, '', '', 0);

        $CFG->clone_dbname = $CFG->dbname;
        $reportdb = totara_get_clone_db(true);
        $reads = $reportdb->perf_get_reads();
        $writes = $reportdb->perf_get_writes();

        $report = reportbuilder::create($rid, $config);
        $report->get_filtered_count();
        $this->assertSame($reads + 1, $reportdb->perf_get_reads());
        $this->assertSame($writes, $reportdb->perf_get_writes());

        $report = reportbuilder::create($rid, $config);
        $reads = $reportdb->perf_get_reads();
        $writes = $reportdb->perf_get_writes();
        $report->get_full_count();
        $this->assertSame($reads + 1, $reportdb->perf_get_reads());
        $this->assertSame($writes, $reportdb->perf_get_writes());

        $report = reportbuilder::create($rid, $config);
        $reads = $reportdb->perf_get_reads();
        $writes = $reportdb->perf_get_writes();
        $report->display_table(true);
        $this->assertGreaterThanOrEqual($reads + 1, $reportdb->perf_get_reads());
        $this->assertSame($writes, $reportdb->perf_get_writes());

        $report = reportbuilder::create($rid, $config);
        $reads = $reportdb->perf_get_reads();
        $writes = $reportdb->perf_get_writes();
        $source = new \totara_reportbuilder\tabexport_source($report);
        foreach ($source as $row) {
            continue;
        }
        $source = null;
        $this->assertSame($reads + 1, $reportdb->perf_get_reads());
        $this->assertSame($writes, $reportdb->perf_get_writes());
    }

    public function test_useclonedb_on_cached() {
        global $CFG, $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        $this->getDataGenerator()->create_user();

        // We need to be able to calculate the total count.
        set_config('allowtotalcount', 1, 'totara_reportbuilder');

        $rid = $this->create_report('user', 'Test user report 1', true);
        $DB->set_field('report_builder', 'defaultsortcolumn', 'user_id', array('id' => $rid));
        $DB->set_field('report_builder', 'defaultsortorder', SORT_ASC, array('id' => $rid));
        $DB->set_field('report_builder', 'useclonedb', '1', array('id' => $rid));
        $config = (new rb_config())->set_nocache(true);
        $report = reportbuilder::create($rid, $config);
        $this->add_column($report, 'user', 'id', null, null, null, 0);
        $this->add_column($report, 'user', 'username', null, '', '', 0);
        $this->enable_caching($rid);

        $CFG->clone_dbname = $CFG->dbname;
        $reportdb = totara_get_clone_db(true);

        $config = (new rb_config())->set_nocache(false);
        $report = reportbuilder::create($rid, $config);
        $reads = $reportdb->perf_get_reads();
        $writes = $reportdb->perf_get_writes();
        $report->get_filtered_count();
        $this->assertSame($reads, $reportdb->perf_get_reads());
        $this->assertSame($writes, $reportdb->perf_get_writes());

        $report = reportbuilder::create($rid, $config);
        $reads = $reportdb->perf_get_reads();
        $writes = $reportdb->perf_get_writes();
        $report->get_full_count();
        $this->assertSame($reads, $reportdb->perf_get_reads());
        $this->assertSame($writes, $reportdb->perf_get_writes());

        $report = reportbuilder::create($rid, $config);
        $reads = $reportdb->perf_get_reads();
        $writes = $reportdb->perf_get_writes();
        $report->display_table(true);
        $this->assertSame($reads, $reportdb->perf_get_reads());
        $this->assertSame($writes, $reportdb->perf_get_writes());

        $report = reportbuilder::create($rid, $config);
        $reads = $reportdb->perf_get_reads();
        $writes = $reportdb->perf_get_writes();
        $source = new \totara_reportbuilder\tabexport_source($report);
        foreach ($source as $row) {
            continue;
        }
        $source = null;
        $this->assertSame($reads, $reportdb->perf_get_reads());
        $this->assertSame($writes, $reportdb->perf_get_writes());
    }

    protected function tearDown() {
        global $CFG;

        unset($CFG->clone_dbname);
        totara_get_clone_db(true);

        parent::tearDown();
    }
}
