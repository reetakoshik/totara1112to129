<?php
/*
 * This file is part of Totara LMS
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
 * @author Alastair Munro <alastair.munro@totaralearning.com>
 * @author Simon Player <simon.player@totaralearning.com>
 * @package totara
 * @subpackage reportbuilder
 *
 *
 */
global $CFG;
require_once($CFG->dirroot . '/completion/completion_completion.php');

/**
 * @group totara_reportbuilder
 */
class course_grade_percent_test extends advanced_testcase {
    use totara_reportbuilder\phpunit\report_testing;

    /**
     * Do the necessary setup.
     *
     * @return stdClass
     */
    protected function setupdata() {
        $this->resetAfterTest();
        $this->setAdminUser();

        $setupdata = new stdClass();

        // Create report.
        $rid = $this->create_report('courses', 'Test');
        $config = (new rb_config())->set_nocache(true);
        $setupdata->report = reportbuilder::create($rid, $config);

        // Mock objects to use in the display function.
        $setupdata->column = $this->getMockBuilder('\rb_column')
            ->setConstructorArgs(array('course_completion', 'passgrade', 'passgrade', 'grade_items.gradepass',
                array('extrafields' => array('rplgrade' => true, 'status' => true, 'maxgrade' => true))))
            ->getMock();
        $setupdata->format = "html";
        $setupdata->row = new stdClass();

        return $setupdata;
    }

    public function test_course_grade_percent_rpl() {
        $setup = $this->setupdata();

        $value = 'test';

        // rplgrade.
        $extrafieldrow = reportbuilder_get_extrafield_alias($setup->column->type, $setup->column->value, 'rplgrade');
        $setup->row->$extrafieldrow = 30;

        // status.
        $extrafieldrow = reportbuilder_get_extrafield_alias($setup->column->type, $setup->column->value, 'status');
        $setup->row->$extrafieldrow = COMPLETION_STATUS_COMPLETEVIARPL;

        // maxgrade.
        $extrafieldrow = reportbuilder_get_extrafield_alias($setup->column->type, $setup->column->value, 'maxgrade');
        $setup->row->$extrafieldrow = null;

        $display = \totara_reportbuilder\rb\display\course_grade_percent::display($value, $setup->format, $setup->row, $setup->column, $setup->report);
        $this->assertEquals('30.0%', $display);
    }

    public function test_course_grade_percent_percentage() {
        $setup = $this->setupdata();

        $value = 55;

        // rplgrade.
        $extrafieldrow = reportbuilder_get_extrafield_alias($setup->column->type, $setup->column->value, 'rplgrade');
        $setup->row->$extrafieldrow = 30;

        // status.
        $extrafieldrow = reportbuilder_get_extrafield_alias($setup->column->type, $setup->column->value, 'status');
        $setup->row->$extrafieldrow = COMPLETION_STATUS_COMPLETE;

        // maxgrade.
        $extrafieldrow = reportbuilder_get_extrafield_alias($setup->column->type, $setup->column->value, 'maxgrade');
        $setup->row->$extrafieldrow = 100;

        $display = \totara_reportbuilder\rb\display\course_grade_percent::display($value, $setup->format, $setup->row, $setup->column, $setup->report);
        $this->assertEquals('55.0%', $display);

        // Check a percent that isn't out of 100.
        $value = 10;

        // maxgrade.
        $extrafieldrow = reportbuilder_get_extrafield_alias($setup->column->type, $setup->column->value, 'maxgrade');
        $setup->row->$extrafieldrow = 30;

        $display = \totara_reportbuilder\rb\display\course_grade_percent::display($value, $setup->format, $setup->row, $setup->column, $setup->report);
        $this->assertEquals('33.3%', $display);
    }

    public function test_course_grade_percent_notempty() {
        $setup = $this->setupdata();

        $value = 72;

        // rplgrade.
        $extrafieldrow = reportbuilder_get_extrafield_alias($setup->column->type, $setup->column->value, 'rplgrade');
        $setup->row->$extrafieldrow = null;

        // status.
        $extrafieldrow = reportbuilder_get_extrafield_alias($setup->column->type, $setup->column->value, 'status');
        $setup->row->$extrafieldrow = COMPLETION_STATUS_COMPLETE;

        // maxgrade.
        $extrafieldrow = reportbuilder_get_extrafield_alias($setup->column->type, $setup->column->value, 'maxgrade');
        $setup->row->$extrafieldrow = null;

        $display = \totara_reportbuilder\rb\display\course_grade_percent::display($value, $setup->format, $setup->row, $setup->column, $setup->report);
        $this->assertEquals('72', $display);
    }

    public function test_course_grade_percent_novalue() {
        $setup = $this->setupdata();

        $value = null;

        // rplgrade.
        $extrafieldrow = reportbuilder_get_extrafield_alias($setup->column->type, $setup->column->value, 'rplgrade');
        $setup->row->$extrafieldrow = 30;

        // status.
        $extrafieldrow = reportbuilder_get_extrafield_alias($setup->column->type, $setup->column->value, 'status');
        $setup->row->$extrafieldrow = COMPLETION_STATUS_COMPLETE;

        // maxgrade.
        $extrafieldrow = reportbuilder_get_extrafield_alias($setup->column->type, $setup->column->value, 'maxgrade');
        $setup->row->$extrafieldrow = 30;

        $display = \totara_reportbuilder\rb\display\course_grade_percent::display($value, $setup->format, $setup->row, $setup->column, $setup->report);
        $this->assertEquals('-', $display);
    }
}
