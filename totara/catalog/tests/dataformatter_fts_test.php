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
 * Class dataformatter_fts_test
 *
 * Tests all the dataformatters for catalog component.
 *
 * @package totara_catalog
 * @group totara_catalog
 */
class totara_catalog_dataformatter_fts_testcase extends dataformatter_test_base {

    public function test_fts() {
        $context = context_system::instance();

        $df = new fts('textfieldname');
        $this->assertCount(1, $df->get_required_fields());
        $this->assertSame('textfieldname', $df->get_required_fields()['text']);
        $this->assertSame([formatter::TYPE_FTS], $df->get_suitable_types());

        $test_params = ['text' => 'some search text with <format>ting stripped out'];
        $result = $df->get_formatted_value($test_params, $context);
        $this->assertSame('some search text with ting stripped out', $result);

        $result = $df->get_formatted_value(['text' => ''], $context);
        $this->assertSame('', $result);

        $this->assert_exceptions($df, $test_params);
    }
}
