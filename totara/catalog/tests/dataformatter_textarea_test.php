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
 * Class dataformatter_textarea_test
 *
 * Tests all the dataformatters for catalog component.
 *
 * @package totara_catalog
 * @group totara_catalog
 */
class totara_catalog_dataformatter_textarea_testcase extends dataformatter_test_base {

    public function test_textarea() {
        $context = context_system::instance();

        $df = new textarea('textfield', 'contextidfield', 'componentfield', 'fieldareafield', 'itemidfield');
        $this->assertCount(5, $df->get_required_fields());
        $this->assertSame('textfield', $df->get_required_fields()['text']);
        $this->assertSame('contextidfield', $df->get_required_fields()['contextid']);
        $this->assertSame('componentfield', $df->get_required_fields()['component']);
        $this->assertSame('fieldareafield', $df->get_required_fields()['filearea']);
        $this->assertSame('itemidfield', $df->get_required_fields()['itemid']);

        $this->assertSame([formatter::TYPE_PLACEHOLDER_RICH_TEXT], $df->get_suitable_types());

        $html = '<div class="unit_test">test_text</div>';
        $test_params = [
            'text' => $html,
            'contextid' => $context->id,
            'component' => 'totara_catalog',
            'filearea' => 'testarea',
            'itemid' => 123,
        ];
        $result = $df->get_formatted_value($test_params, $context);
        $this->assertContains($html, $result);

        $this->assert_exceptions($df, $test_params);
    }
}
