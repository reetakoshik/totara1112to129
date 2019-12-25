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
 * @author Nathan Lewis <nathan.lewis@totaralearning.com>
 * @package totara_catalog
 */

defined('MOODLE_INTERNAL') || die();

use totara_catalog\optional_param;
/**
 * @group totara_catalog
 */
class totara_catalog_optional_param_testcase extends advanced_testcase {

    public function test_raw_type() {
        $raw_param = new optional_param('some-key', null, PARAM_RAW);
        $this->assertSame('some-key', $raw_param->key);
        $this->assertSame(PARAM_RAW, $raw_param->type);
        $this->assertNull($raw_param->default);
    }

    public function test_alpha_type() {
        $raw_param = new optional_param('some-key', 'some-value', PARAM_ALPHA);
        $this->assertSame('some-key', $raw_param->key);
        $this->assertSame(PARAM_ALPHA, $raw_param->type);
        $this->assertSame('some-value', $raw_param->default);
    }
}
