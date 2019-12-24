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
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 * @package totara_userdata
 * @category test
 */

use totara_userdata\userdata\item;
use totara_userdata\local\count;

defined('MOODLE_INTERNAL') || die();

/**
 * Tests the count class.
 */
class totara_userdata_local_count_testcase extends advanced_testcase {
    public function test_get_countable_item_classes() {
        $classes = count::get_countable_item_classes();
        foreach ($classes as $class) {
            /** @var item $class this is not a real instance */
            $this->assertTrue($class::is_countable());
        }
    }

    public function test_get_countable_items_grouped_list() {
        $maincomponents = count::get_countable_items_grouped_list();
        foreach ($maincomponents as $maincomponent => $classes) {
            foreach ($classes as $class) {
                /** @var item $class this is not a real instance */
                $this->assertTrue($class::is_countable());
            }
        }
    }
}
