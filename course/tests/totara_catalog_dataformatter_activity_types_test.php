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
class core_course_totara_catalog_dataformatter_activity_types_testcase extends dataformatter_test_base {

    public function test_activity_types() {
        $context = context_system::instance();

        $df = new activity_types('modulesfield');
        $this->assertCount(1, $df->get_required_fields());
        $this->assertSame('modulesfield', $df->get_required_fields()['modules']);

        $this->assertSame([formatter::TYPE_PLACEHOLDER_TEXT, formatter::TYPE_FTS], $df->get_suitable_types());

        // Results should come from translations and get sorted.
        $test_params = ['modules' => 'forum,book,assign,resource'];
        $result = $df->get_formatted_value($test_params, $context);
        $this->assertSame('Assignment, Book, File, Forum', $result);

        $result = $df->get_formatted_value(['modules' => ''], $context);
        $this->assertSame('', $result);

        $result = $df->get_formatted_value(['modules' => ',,,forum ,,,book ,,,'], $context);
        $this->assertSame('Book, Forum', $result);

        // Activity types that are not in translations just get uppercased.
        $result = $df->get_formatted_value(['modules' => 'orangutan,resource,assign'], $context);
        $this->assertSame('Assignment, File, Orangutan', $result);

        $this->assert_exceptions($df, $test_params);
    }

    public function test_activity_types_with_delimiter(): void {
        $context = context_system::instance();

        $df = new activity_types('modulesfield', '+', '\n');
        $result = $df->get_formatted_value(['modules' => 'form+book+assign+resource'], $context);
        $this->assertSame('Assignment\nBook\nFile\nForm', $result);
    }
}
