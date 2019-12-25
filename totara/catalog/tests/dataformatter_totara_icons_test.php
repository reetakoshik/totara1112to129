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
 * Class dataformatter_totara_icons_test
 *
 * Tests all the dataformatters for catalog component.
 *
 * @package totara_catalog
 * @group totara_catalog
 */
class totara_catalog_dataformatter_totara_icons_testcase extends dataformatter_test_base {

    /**
     * Tests for totara_icon and totara_icons dataformatter are very similar.
     */
    public function test_totara_icon() {
        global $CFG;
        $this->resetAfterTest();
        $context = context_system::instance();

        $df = new totara_icons('idfield', 'altfield', TOTARA_ICON_TYPE_COURSE);
        $this->assertCount(2, $df->get_required_fields());
        $this->assertSame('idfield', $df->get_required_fields()['id']);
        $this->assertSame('altfield', $df->get_required_fields()['alt']);

        $this->assertSame([formatter::TYPE_PLACEHOLDER_ICONS], $df->get_suitable_types());

        $course = $this->getDataGenerator()->create_course();

        $test_params = [
            'id' => $course->id,
            'alt' => 'test_alt_text',
        ];
        $result = $df->get_formatted_value($test_params, $context);
        $result = $result[0];

        // Check that we get a url back that includes default icon in its path.
        $this->assertContains($CFG->wwwroot, $result->url);
        $this->assertContains('/courseicons/default', $result->url);
        $this->assertSame('test_alt_text', $result->alt);

        $this->assert_exceptions($df, $test_params);
    }
}
