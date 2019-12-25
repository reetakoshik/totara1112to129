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

use advanced_testcase;
use coding_exception;
use context_system;

defined('MOODLE_INTERNAL') || die();

/**
 * Class dataformatter_test_base
 *
 * Functionality commonly used by dataformatter tests.
 *
 * @package totara_catalog
 * @group totara_catalog
 */
abstract class dataformatter_test_base extends advanced_testcase {

    /**
     * Assert exceptions
     *
     * Make sure dataformatter throws coding_exception when parameter is missing.
     * Not using expectException here as it doesn't work repeatedly in one test method.
     *
     * @param formatter $df
     * @param array $test_params
     */
    protected function assert_exceptions(formatter $df, array $test_params) {
        $context = context_system::instance();
        foreach ($test_params as $k => $v) {
            $params = $test_params;
            unset($params[$k]);
            try {
                $df->get_formatted_value($params, $context);
                $this->fail('coding_exception was not thrown when expected.');
            } catch (coding_exception $e) {
                $this->assertContains("data formatter expects '{$k}'", $e->getMessage());
            }
        }
    }
}
