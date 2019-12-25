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
class core_course_totara_catalog_dataformatter_activity_type_icons_testcase extends dataformatter_test_base {

    public function test_activity_type_icons() {
        $context = context_system::instance();

        $df = new activity_type_icons('modulesfield');
        $this->assertCount(1, $df->get_required_fields());
        $this->assertSame('modulesfield', $df->get_required_fields()['modules']);

        $this->assertSame([formatter::TYPE_PLACEHOLDER_ICONS], $df->get_suitable_types());

        $test_params = ['modules' => 'forum,book,assign,resource'];
        $result = $df->get_formatted_value($test_params, $context);
        $this->assertCount(4, $result);
        foreach ($result as $icon_object) {
            $this->assertInstanceOf(stdClass::class, $icon_object);
            $this->assertContains('flex-icon', $icon_object->icon);
        }
        // Result should be sorted predictably.
        $this->assertContains('Assignment', $result[0]->icon);
        $this->assertContains('Book', $result[1]->icon);
        $this->assertContains('File', $result[2]->icon);
        $this->assertContains('Forum', $result[3]->icon);

        $result = $df->get_formatted_value(['modules' => ''], $context);
        $this->assertSame([], $result);

        $result = $df->get_formatted_value(['modules' => ',,,forum ,,,book ,,,'], $context);
        $this->assertCount(2, $result);
        $this->assertContains('Book', $result[0]->icon);
        $this->assertContains('Forum', $result[1]->icon);

        $this->assert_exceptions($df, $test_params);
    }

    public function test_type_icons_with_customdelimiter(): void {
        $context = context_system::instance();

        $df = new activity_type_icons('modulesfield', "+");
        $params = $df->get_formatted_value(['modules' => "forum+assign"], $context);
        $this->assertCount(2, $params);
    }
}
