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

namespace totara_customfield\totara_catalog\dataformatter;

use context_system;
use totara_catalog\dataformatter\dataformatter_test_base;
use totara_catalog\dataformatter\formatter;
use totara_customfield_generator;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . "/totara/catalog/tests/dataformatter_test_base.php");

/**
 * @group totara_customfield
 * @group totara_catalog
 */
class totara_customfield_totara_catalog_dataformatters_testcase extends dataformatter_test_base {

    public function customfield_dataformatter_provider() {
        return [
            [
                'customfield',
                [
                    formatter::TYPE_PLACEHOLDER_TEXT,
                    formatter::TYPE_PLACEHOLDER_TITLE,
                    formatter::TYPE_PLACEHOLDER_RICH_TEXT,
                ]
            ],
            [
                'customfield_fts',
                [
                    formatter::TYPE_FTS,
                ]
            ],
        ];
    }

    /**
     * @dataProvider customfield_dataformatter_provider
     *
     * @param string $classname
     * @param array $expected_suitable_types
     */
    public function test_customfield(string $classname, array $expected_suitable_types) {
        $this->resetAfterTest();

        $context = context_system::instance();

        $generator = $this->getDataGenerator();
        /** @var totara_customfield_generator $cf_generator */
        $cf_generator = $generator->get_plugin_generator('totara_customfield');

        $course = $generator->create_course();
        $field_ids = $cf_generator->create_text('course', ['test_text']);
        $cf_generator->set_text($course, $field_ids['test_text'], 'value_test_text', 'course', 'course');

        $full_classname = 'totara_customfield\\totara_catalog\\dataformatter\\' . $classname;
        /** @var formatter $df */
        $df = new $full_classname($field_ids['test_text'], 'idfield', 'course', 'course');
        $this->assertCount(1, $df->get_required_fields());
        $this->assertSame('idfield', $df->get_required_fields()['id']);

        $this->assertSame($expected_suitable_types, $df->get_suitable_types());

        $test_params = ['id' => $course->id];
        $result = $df->get_formatted_value($test_params, $context);
        $this->assertSame('value_test_text', $result);

        $this->assert_exceptions($df, $test_params);
    }
}
