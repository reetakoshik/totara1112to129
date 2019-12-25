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

namespace totara_catalog\dataformatter;

use context_system;
use DateTime;
use DateTimeZone;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . "/course/lib.php");
require_once($CFG->dirroot . "/totara/catalog/tests/dataformatter_test_base.php");

/**
 * Class dataformatter_test
 *
 * Tests the formatter dataformatter base class for catalog component.
 *
 * @package totara_catalog
 * @group totara_catalog
 */
class totara_catalog_dataformatter_testcase extends dataformatter_test_base {

    public function test_is_suitable_for_type() {
        $df = new text('textfieldname');
        $this->assertSame(
            [
                formatter::TYPE_PLACEHOLDER_TEXT,
                formatter::TYPE_PLACEHOLDER_TITLE,
                formatter::TYPE_SORT_TIME,
            ],
            $df->get_suitable_types()
        );

        $this->assertTrue($df->is_suitable_for_type(formatter::TYPE_PLACEHOLDER_TEXT));
        $this->assertTrue($df->is_suitable_for_type(formatter::TYPE_PLACEHOLDER_TITLE));
        $this->assertTrue($df->is_suitable_for_type(formatter::TYPE_SORT_TIME));

        $this->assertFalse($df->is_suitable_for_type(formatter::TYPE_FTS));
    }

    public function test_required_fields() {
        $df = new duration('startfieldname', 'endfieldname');

        $expectedfields = [
            'start' => 'startfieldname',
            'end' => 'endfieldname',
        ];

        $this->assertEquals($expectedfields, $df->get_required_fields());
    }
}
