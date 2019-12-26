<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2014 onwards Totara Learning Solutions LTD
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

/**
 * @group totara_reportbuilder
 */
class totara_reportbuilder_clone_testcase extends advanced_testcase {
    use totara_reportbuilder\phpunit\report_testing;

    public function test_report_created_event() {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();

        $rid = $this->create_report('user', 'Test user report');

        // Add filters.
        $this->add_filter($rid, 'job_assignment', 'allmanagers', 0, 'Manager', 1, 0);
        $this->add_filter($rid, 'user', 'fullname', 1, 'Name', 1, 1);

        // Add settings.
        $this->set_setting($rid, 'date_content', 'enable', '1');
        $this->set_setting($rid, 'date_content', 'when', 'future');

        // Add graph.
        $this->add_graph($rid, 'column', 0, 500, 'user-namelinkicon', '', array('statistics-coursescompleted'), '');

        $config = (new rb_config())->set_nocache(true);
        $report = reportbuilder::create($rid, $config);

        // Add columns.
        $this->add_column($report, 'user', 'id', null, null, null, 0);
        $this->add_column($report, 'user', 'firstname', null, null, null, 0);
        $this->add_column($report, 'statistics', 'coursescompleted', null, null, null, 0);

        // Clone.
        $newid = reportbuilder_clone_report($report, 'Clone report');
        $config = (new rb_config())->set_nocache(true);
        $newreport = reportbuilder::create($newid, $config);

        // Test.
        $this->assertNotEquals($rid, $newid);

        // Assert graph.
        $newgraphs = $DB->get_records('report_builder_graph', array('reportid' => $newid));
        $newgraph = array_shift($newgraphs);
        $this->assertEquals('column', $newgraph->type);
        $this->assertEquals('0', $newgraph->stacked);
        $this->assertEquals('500', $newgraph->maxrecords);
        $this->assertEquals('user-namelinkicon', $newgraph->category);
        $this->assertEquals('', $newgraph->legend);
        $this->assertEquals('["statistics-coursescompleted"]', $newgraph->series);
        $this->assertEquals('', $newgraph->settings);

        // Assert filters.
        $filters = $newreport->filters;
        $this->assertCount(2, $filters);
        usort($filters, function($a, $b) {
            return strcmp($a->value, $b->value);
        });

        $filter = array_shift($filters);
        $this->assertEquals('job_assignment', $filter->type);
        $this->assertEquals('allmanagers', $filter->value);
        $this->assertEquals('0', $filter->advanced);
        $this->assertEquals('Manager', $filter->label);
        $this->assertEquals('1', $filter->customname);
        $this->assertEquals('0', $filter->region);

        $filter = array_shift($filters);
        $this->assertEquals('user', $filter->type);
        $this->assertEquals('fullname', $filter->value);
        $this->assertEquals('1', $filter->advanced);
        $this->assertEquals('Name', $filter->label);
        $this->assertEquals('1', $filter->customname);
        $this->assertEquals('1', $filter->region);

        // Assert columns.
        $columns = $newreport->columns;
        $this->assertCount(3, $columns);
        usort($columns, function($a, $b) {
            return strcmp($a->value, $b->value);
        });

        $column = array_shift($columns);
        $this->assertEquals('statistics', $column->type);
        $this->assertEquals('coursescompleted', $column->value);
        $this->assertEquals('User\'s Courses Completed Count', $column->heading);
        $this->assertEquals('0', $column->hidden);
        $this->assertEquals('0', $column->customheading);

        $column = array_shift($columns);
        $this->assertEquals('user', $column->type);
        $this->assertEquals('firstname', $column->value);
        $this->assertEquals('User First Name', $column->heading);
        $this->assertEquals('0', $column->hidden);
        $this->assertEquals('0', $column->customheading);

        $column = array_shift($columns);
        $this->assertEquals('user', $column->type);
        $this->assertEquals('id', $column->value);
        $this->assertEquals('User ID', $column->heading);
        $this->assertEquals('0', $column->hidden);
        $this->assertEquals('0', $column->customheading);

        // Assert settings.
        $settings = reportbuilder::get_all_settings($newid, 'date_content');
        $this->assertCount(2, $settings);
        ksort($settings);

        $key = key($settings);
        $value = current($settings);
        next($settings);
        $this->assertEquals('enable', $key);
        $this->assertEquals('1', $value);

        $key = key($settings);
        $value = current($settings);
        $this->assertEquals('when', $key);
        $this->assertEquals('future', $value);
    }
}
