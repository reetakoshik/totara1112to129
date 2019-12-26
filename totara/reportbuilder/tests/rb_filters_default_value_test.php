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
 * @author Maria Torres <maria.torres@totaralearning.com>
 * @package totara_reportbuilder
 *
 * Unit/functional tests to check filter default values
 */
if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.'); // It must be included from a Moodle page.
}
global $CFG;
require_once($CFG->dirroot . '/totara/reportbuilder/filters/lib.php');

/**
 * @group totara_reportbuilder
 */
class totara_reportbuilder_rb_filters_default_value_testcase extends advanced_testcase {
    use totara_reportbuilder\phpunit\report_testing;

    /**
     * Check all filters default value
     *
     */
    public function test_default_value() {
        $this->resetAfterTest();
        $this->setAdminUser();

        // Testing custom user report.
        $rid = $this->create_report('user', 'custom_user_report');

        // Add filters.
        $this->add_filter($rid, 'user', 'fullname', 1, 'Name', 1, 1, ['operator' => 3, 'value' => 'default text']);
        $this->add_filter($rid, 'user', 'deleted', 1, 'User Status', 1, 1, ['operator'=> 1, 'value' => 0]);

        $config = (new rb_config())->set_nocache(true);
        $report = reportbuilder::create($rid, $config);

        // Assert filters.
        $filters = $report->get_filters();
        $this->assertCount(2, $filters);

        $filter = array_shift($filters);
        $this->assertEquals('user', $filter->type);
        $this->assertEquals('fullname', $filter->value);
        $this->assertEquals('1', $filter->advanced);
        $this->assertEquals('Name', $filter->label);
        $this->assertEquals('1', $filter->customname);
        $this->assertEquals('1', $filter->region);
        $this->assertEquals(['operator'=> 3, 'value' => 'default text'], $filter->defaultvalue);
        $this->assertEquals('Name starts with "default text"',
            $filter->get_label($filter->defaultvalue));

        $filter = array_shift($filters);
        $this->assertEquals('user', $filter->type);
        $this->assertEquals('deleted', $filter->value);
        $this->assertEquals('1', $filter->advanced);
        $this->assertEquals('User Status', $filter->label);
        $this->assertEquals('1', $filter->customname);
        $this->assertEquals('1', $filter->region);
        $this->assertEquals(['operator'=> 1, 'value' => 0], $filter->defaultvalue);
        $this->assertEquals('User Status is equal to "Active"',
            $filter->get_label($filter->defaultvalue));

        // Testing custom course report.
        $rid = $this->create_report('courses', 'custom_embeded_course_report', 1);

        // Add filters.
        $this->add_filter($rid, 'course_category', 'path', 1, 'Category', 0, 1, ['operator' => 1, 'value' => 1, 'recursive' => 1]);
        $this->add_filter($rid, 'course', 'startdate', 1, 'Course start date', 0, 1, ['after' => 0, 'before' => 0, 'daysafter'=> 22, 'daysbefore' => 13]);

        $config = (new rb_config())->set_nocache(true);
        $report = reportbuilder::create($rid, $config);

        // Assert filters.
        $filters = $report->get_filters();
        $this->assertCount(2, $filters);

        $filter = array_shift($filters);
        $this->assertEquals('course_category', $filter->type);
        $this->assertEquals('path', $filter->value);
        $this->assertEquals('1', $filter->advanced);
        $this->assertEquals(['operator' => 1, 'value' => 1, 'recursive' => 1], $filter->defaultvalue);
        $this->assertEquals('Course Category (multichoice) is equal to "Miscellaneous" (and children)',
            $filter->get_label($filter->defaultvalue));

        $filter = array_shift($filters);
        $this->assertEquals('course', $filter->type);
        $this->assertEquals('startdate', $filter->value);
        $this->assertEquals('1', $filter->advanced);
        $this->assertEquals(['after' => 0, 'before' => 0, 'daysafter'=> 22, 'daysbefore' => 13], $filter->defaultvalue);
        $this->assertContains('Course Start Date is after ', $filter->get_label($filter->defaultvalue));
        $this->assertContains('and before ', $filter->get_label($filter->defaultvalue));

        // Testing badges report.
        $rid = $this->create_report('badge_issued', 'custom_badge_issued_report');

        // Creates a couple of courses.
        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();

        // Creates a couple of audiences.
        $audience1 = $this->getDataGenerator()->create_cohort();
        $audience2 = $this->getDataGenerator()->create_cohort();

        // Add filters.
        $this->add_filter($rid, 'badge', 'status', 1, '', 0, 1, ['operator' => 1, 'value' => ["1", "1", "1", "0", "0"]]);
        $this->add_filter($rid, 'course', 'id', 1, '', 0, 1, ['operator'=> 1, 'value' => "{$course1->id},{$course2->id}"]);
        $this->add_filter($rid, 'cohort', 'enrolledcoursecohortids', 1, '', 0, 1, ['value' => "{$audience1->id},{$audience2->id}"]);

        $config = (new rb_config())->set_nocache(true);
        $report = reportbuilder::create($rid, $config);

        // Assert filters.
        $filters = $report->get_filters();
        $this->assertCount(3, $filters);

        $filter = array_shift($filters);
        $this->assertEquals('badge', $filter->type);
        $this->assertEquals('status', $filter->value);
        $this->assertEquals('1', $filter->advanced);
        $this->assertEquals(['operator' => 1, 'value' => ["1", "1", "1", "0", "0"]], $filter->defaultvalue);
        $this->assertEquals('Badge status Any of the selected "Not available to users", "Available to users", "Locked - Not available to users"',
            $filter->get_label($filter->defaultvalue));

        $filter = array_shift($filters);
        $this->assertEquals('course', $filter->type);
        $this->assertEquals('id', $filter->value);
        $this->assertEquals('1', $filter->advanced);
        $this->assertEquals(['operator'=> 1, 'value' => "{$course1->id},{$course2->id}"], $filter->defaultvalue);
        $this->assertEquals('Course (multi-item) is equal to "' . $course1->fullname . '" or "' . $course2->fullname . '"',
            $filter->get_label($filter->defaultvalue));

        $filter = array_shift($filters);
        $this->assertEquals('cohort', $filter->type);
        $this->assertEquals('enrolledcoursecohortids', $filter->value);
        $this->assertEquals('1', $filter->advanced);
        $this->assertEquals(['value' => "{$audience1->id},{$audience2->id}"], $filter->defaultvalue);
        $this->assertEquals('Course with enrolled audience(s) "' . $audience1->name . '" or "' . $audience2->name . '"',
            $filter->get_label($filter->defaultvalue));
    }
}
