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

namespace totara_program\totara_catalog\program\dataformatter;

use advanced_testcase;
use context_system;
use core_completion_generator;
use stdClass;
use totara_catalog\dataformatter\dataformatter_test_base;
use totara_catalog\dataformatter\formatter;
use totara_program_generator;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . "/totara/catalog/tests/dataformatter_test_base.php");

/**
 * @group totara_catalog
 */
class totara_program_totara_catalog_dataformatter_test extends dataformatter_test_base {

    public function test_image() {
        global $CFG;
        $this->resetAfterTest();

        $context = context_system::instance();

        $df = new image('programidfield', 'altfield');
        $this->assertCount(2, $df->get_required_fields());
        $this->assertSame('programidfield', $df->get_required_fields()['programid']);
        $this->assertSame('altfield', $df->get_required_fields()['alt']);

        $this->assertSame([formatter::TYPE_PLACEHOLDER_IMAGE], $df->get_suitable_types());

        /** @var totara_program_generator $program_generator */
        $program_generator = $this->getDataGenerator()->get_plugin_generator('totara_program');
        $program = $program_generator->create_program();

        // Get url object for default image.
        $test_params = [
            'programid' => $program->id,
            'alt' => 'test_alt_text',
        ];
        $result = $df->get_formatted_value($test_params, $context);
        $this->assertInstanceOf(stdClass::class, $result);

        // Convert object to array so that we may read the protected attributes.
        $result = (array) $result;

        // Check that we get a theme-independent default icon reference.
        $this->assertContains($CFG->wwwroot, $result['url']);
        $this->assertContains('moodle/theme/image.php', $result['url']);
        $this->assertContains('defaultimage', $result['url']);
        $this->assertSame('test_alt_text', $result['alt']);

        $this->assert_exceptions($df, $test_params);
    }
}
