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
 * Class dataformatter_duration_test
 *
 * Tests the duration dataformatter for catalog component.
 *
 * @package totara_catalog
 * @group totara_catalog
 */
class totara_catalog_dataformatter_duration_testcase extends dataformatter_test_base {

    public function test_duration() {
        $context = context_system::instance();

        $start = (new DateTime('2000-01-01T00:00:00+00:00', new DateTimeZone('Pacific/Auckland')))->getTimestamp();
        $end =   (new DateTime('2000-01-05T00:00:00+00:00', new DateTimeZone('Pacific/Auckland')))->getTimestamp();

        $df = new duration('start', 'end');
        $this->assertCount(2, $df->get_required_fields());
        $this->assertSame('start', $df->get_required_fields()['start']);
        $this->assertSame('end', $df->get_required_fields()['end']);
        $this->assertSame([formatter::TYPE_PLACEHOLDER_TEXT], $df->get_suitable_types());

        $test_params = ['start' => $start, 'end' => $end];
        $value = $df->get_formatted_value($test_params, $context);
        $this->assertSame('4 days', $value);

        $end = (new DateTime('2001-01-05T00:00:00+00:00', new DateTimeZone('Pacific/Auckland')))->getTimestamp();
        $df = new duration((string)$start, (string)$end);
        $value = $df->get_formatted_value(['start' => $start, 'end' => $end], $context);
        $this->assertSame('370 days', $value);

        $this->assert_exceptions($df, $test_params);
    }
}
