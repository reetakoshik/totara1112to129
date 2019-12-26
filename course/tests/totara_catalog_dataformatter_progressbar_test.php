<?php
/*
 * This file is part of Totara Learn
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
 * @author Matthias Bonk <matthias.bonk@totaralearning.com>
 * @package totara_catalog
 */

namespace core_course\totara_catalog\course\dataformatter;

use context_system;
use core_completion_generator;
use stdClass;
use totara_catalog\dataformatter\dataformatter_test_base;
use totara_catalog\dataformatter\formatter;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . "/course/lib.php");
require_once($CFG->dirroot . "/totara/catalog/tests/dataformatter_test_base.php");

/**
 * @group totara_catalog
 */
class core_course_totara_catalog_dataformatter_progressbar_testcase extends dataformatter_test_base {

    public function test_progressbar() {
        global $CFG, $DB;
        $this->resetAfterTest();

        $CFG->enablecompletion = true;
        $context = context_system::instance();
        $generator = $this->getDataGenerator();

        // Set up student with some kind of progress, so we can expect progressbar data as a result.
        $student = $generator->create_user();
        $this->setUser($student);
        $course = $generator->create_course();
        $module_data = $generator->create_module('data', ['course' => $course->id]);
        $studentrole = $DB->get_record('role', ['shortname' => 'student']);
        $generator->enrol_user($student->id, $course->id, $studentrole->id);
        /** @var core_completion_generator $cgen */
        $cgen = $generator->get_plugin_generator('core_completion');
        $cgen->enable_completion_tracking($course);
        $cgen->set_activity_completion($course->id, [$module_data]);

        $df = new progressbar('courseidfield', 'statusfield');
        $this->assertCount(2, $df->get_required_fields());
        $this->assertSame('courseidfield', $df->get_required_fields()['courseid']);
        $this->assertSame('statusfield', $df->get_required_fields()['status']);

        $this->assertSame([formatter::TYPE_PLACEHOLDER_PROGRESS], $df->get_suitable_types());

        $test_params = [
            'courseid' => $course->id,
            'status' => COMPLETION_STATUS_INPROGRESS,
        ];
        $result = $df->get_formatted_value($test_params, $context);

        // Make sure result looks like progress data.
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('width', $result);
        $this->assertArrayHasKey('progress', $result);
        $this->assertArrayHasKey('progresstext', $result);
        $this->assertArrayHasKey('popover', $result);

        // Empty array expected if no progress data available.
        $course2 = $generator->create_course();
        $result = $df->get_formatted_value(['courseid' => $course2->id, 'status' => COMPLETION_STATUS_INPROGRESS], $context);
        $this->assertSame([], $result);

        // Empty array expected for empty course id.
        $result = $df->get_formatted_value(['courseid' => null, 'status' => COMPLETION_STATUS_INPROGRESS], $context);
        $this->assertSame([], $result);

        $this->assert_exceptions($df, $test_params);

        $this->expectException('coding_exception');
        $this->expectExceptionMessage(
            "Unknown or empty status passed to progress bar dataformatter when courseid was also provided"
        );
        $df->get_formatted_value(['courseid' => $course2->id, 'status' => 'bad_key'], $context);
    }
}
