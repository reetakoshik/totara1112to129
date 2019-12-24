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
 * @package totara
 * @subpackage reportbuilder
 *
 *
 */
global $CFG;
require_once($CFG->dirroot . '/totara/reportbuilder/classes/rb_base_source.php');
require_once($CFG->dirroot . '/totara/plan/rb_sources/rb_source_dp_course.php');
require_once($CFG->dirroot . '/completion/completion_completion.php');

/**
 * @group totara_reportbuilder
 */
class course_grade_percent_test extends advanced_testcase {

    private $reportsource = null;

    protected function setUp() {
        // Initialise the report source so we can test the display function.
        $this->reportsource = new rb_source_dp_course(0);

        parent::setup();
    }

    public function test_course_grade_percent_rpl() {
        $item = 'test';

        $row = new stdClass();
        $row->rplgrade = 30;
        $row->status = COMPLETION_STATUS_COMPLETEVIARPL;
        $row->maxgrade = null;

        $result = $this->reportsource->rb_display_course_grade_percent($item, $row);
        $this->assertEquals('30.0%', $result);
    }

    public function test_course_grade_percent_percentage() {
        $item = 55;
        $row = new stdClass();
        $row->rplgrade = 30;
        $row->status = COMPLETION_STATUS_COMPLETE;
        $row->maxgrade = 100;

        $result = $this->reportsource->rb_display_course_grade_percent($item, $row);
        $this->assertEquals('55.0%', $result);

        // Check a percent that isn't out of 100.
        $item = 10;
        $row = new stdClass();
        $row->rplgrade = 30;
        $row->status = COMPLETION_STATUS_COMPLETE;
        $row->maxgrade = 30;

        $result = $this->reportsource->rb_display_course_grade_percent($item, $row);
        $this->assertEquals('33.3%', $result);
    }

    public function test_course_grade_percent_notempty() {

        $item = 72;
        $row = new stdClass();
        $row->rplgrade = null;
        $row->status = COMPLETION_STATUS_COMPLETE;
        $row->maxgrade = null;

        $result = $this->reportsource->rb_display_course_grade_percent($item, $row);
        $this->assertEquals('72', $result);
    }

    public function test_course_grade_percent_novalue() {

        $item = null;
        $row = new stdClass();
        $row->rplgrade = 30;
        $row->status = COMPLETION_STATUS_COMPLETE;
        $row->maxgrade = 30;

        $result = $this->reportsource->rb_display_course_grade_percent($item, $row);
        $this->assertEquals('-', $result);
    }
}
