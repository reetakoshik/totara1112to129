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
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 * @package block_admin_related_pages
 */

global $CFG;
require_once($CFG->libdir . '/blocklib.php');
require_once($CFG->dirroot . '/blocks/moodleblock.class.php');
require_once($CFG->dirroot . '/blocks/admin_related_pages/block_admin_related_pages.php');

/**
 * Block tests
 *
 * @package block_admin_related_pages
 * @group block_admin_related_pages
 */
class block_admin_related_pages_testcase extends \basic_testcase {

    public function test_instance_allow_config() {
        $instance = new \block_admin_related_pages();
        self::assertFalse($instance->instance_allow_config());
    }

    public function test_instance_allow_multiple() {
        $instance = new \block_admin_related_pages();
        self::assertFalse($instance->instance_allow_multiple());
    }

    public function test_applicable_formats() {
        $instance = new \block_admin_related_pages();
        self::assertSame(['admin' => true], $instance->applicable_formats());
    }

}
