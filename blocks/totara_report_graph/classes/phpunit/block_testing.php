<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2016 onwards Totara Learning Solutions LTD
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
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 * @package block_totara_report_graph
 */

namespace block_totara_report_graph\phpunit;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/totara/reportbuilder/lib.php');

/**
 * Utility methods for testing the report graph block.
 *
 * Uses the report builder trait to get a good head start on the report side.
 *
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 * @package block_totara_report_graph
 */
trait block_testing {

    /**
     * We want to get a head start with report functionality. Yay for traits!
     */
    use \totara_reportbuilder\phpunit\report_testing;

    /**
     * Creates a basic user report containing a graph, and a few users to ensure it gets populated
     * @return int The report id.
     */
    protected function create_user_report_with_graph() {
        global $DB, $USER;

        // We want total counts here, we'll be testing that later.
        set_config('allowtotalcount', 1, 'totara_reportbuilder');

        $DB->set_field('user', 'country', 'NZ', ['country' => '']);
        $this->getDataGenerator()->create_user(['country' => 'NZ']);
        $this->getDataGenerator()->create_user(['country' => 'NZ']);
        $this->getDataGenerator()->create_user(['country' => 'NZ']);
        $this->getDataGenerator()->create_user(['country' => 'US']);
        $this->getDataGenerator()->create_user(['country' => 'US']);
        $this->getDataGenerator()->create_user(['country' => 'AU']);

        $rid = $this->create_report('user', 'Test user report 1', true);
        $config = new \rb_config();
        $config->set_nocache(true);
        $report = \reportbuilder::create($rid, $config);
        // First up delete all columns.
        $this->add_column($report, 'user', 'username', null, 'countdistinct', null, 0);
        $this->add_column($report, 'user', 'country', null, null, null, 0);
        $this->add_graph($rid, 'pie', 0, 500, 'user-country', '', ['user-username'], '');

        $config = new \rb_config();
        $config->set_nocache(true);
        $report = \reportbuilder::create($rid, $config);

        // Assert graph.
        $newgraphs = $DB->get_records('report_builder_graph', array('reportid' => $rid));
        $newgraph = array_shift($newgraphs);
        $this->assertEquals('pie', $newgraph->type);
        $this->assertEquals('0', $newgraph->stacked);
        $this->assertEquals('500', $newgraph->maxrecords);
        $this->assertEquals('user-country', $newgraph->category);
        $this->assertEquals('', $newgraph->legend);
        $this->assertEquals('["user-username"]', $newgraph->series);
        $this->assertEquals('', $newgraph->settings);

        // Assert columns.
        $columns = $report->columns;
        $this->assertCount(2, $columns);
        usort($columns, function($a, $b) {
            return strcmp($a->value, $b->value);
        });

        $column = array_shift($columns);
        $this->assertEquals('user', $column->type);
        $this->assertEquals('country', $column->value);
        $this->assertEquals('User\'s Country', $column->heading);
        $this->assertEquals('0', $column->hidden);
        $this->assertEquals('0', $column->customheading);
        $this->assertEquals('', $column->aggregate);

        $column = array_shift($columns);
        $this->assertEquals('user', $column->type);
        $this->assertEquals('username', $column->value);
        $this->assertEquals('Username', $column->heading);
        $this->assertEquals('0', $column->hidden);
        $this->assertEquals('0', $column->customheading);
        $this->assertEquals('countdistinct', $column->aggregate);

        $this->assertSame(3, $report->get_full_count()); // NZ, US, AU.

        return $rid;
    }

    /**
     * Create a report graph block instance
     * @param int $rid
     * @param array $config
     * @return \block_totara_report_graph
     */
    protected function create_report_graph_block_instance($rid, array $config = []) {
        global $DB;

        $realconfig = (object)[
            'title' => 'My report block',
            'reportorsavedid' => $rid,
            'reportfor' => 1,
            'graphimage_maxwidth' => '789px',
            'graphimage_maxheight' => '327px',
            'cachettl' => 3600
        ];
        foreach ($config as $key => $value) {
            if (isset($realconfig->{$key})) {
                $realconfig->{$key} = $value;
            }
        }

        $configdata = base64_encode(serialize($realconfig));

        $page = new \moodle_page();
        $page->set_context(\context_system::instance());
        $page->blocks->get_regions();
        $page->blocks->add_block('totara_report_graph', BLOCK_POS_LEFT, 0, false, '*', null);

        $block = $DB->get_record('block_instances', ['blockname' => 'totara_report_graph'], '*', IGNORE_MULTIPLE);
        $DB->set_field('block_instances', 'configdata', $configdata, ['id' => $block->id]);
        $block->configdata = $configdata;

        return block_instance('totara_report_graph', $block);
    }
}