<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2015 onwards Totara Learning Solutions LTD
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
 * @author Petr Skoda <petr.skoda@totaralms.com>
 * @package totara_core
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Test deprecated random string generator,
 * full tests now in standard moodlelib test file.
 */
class totara_core_random_string_testcase extends advanced_testcase {
    public static function setUpBeforeClass() {
        global $CFG;
        parent::setUpBeforeClass();
        require_once("$CFG->dirroot/totara/core/deprecatedlib.php");
    }

    public function test_totara_random_bytes() {
        global $CFG;
        require_once($CFG->dirroot . '/totara/core/deprecatedlib.php');

        $result = totara_random_bytes(10);
        $this->assertSame(10, strlen($result));
        $this->assertDebuggingCalled();
    }
}
