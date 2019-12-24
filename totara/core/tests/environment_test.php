<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2016 onwards Totara Learning Solutions LTD
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
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 * @package totara_core
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Tests our default PHP settings and environment stuff.
 */
class totara_core_environment_testcase extends advanced_testcase {
    public function test_default_charset() {
        $this->assertSame('UTF-8', ini_get('default_charset'));
        $this->assertSame('', ini_get('input_encoding'));
        $this->assertSame('', ini_get('output_encoding'));
        $this->assertSame('', ini_get('internal_encoding'));
        if (extension_loaded('mbstring')) {
            $this->assertSame('neutral', ini_get('mbstring.language'));
        }
    }
}
