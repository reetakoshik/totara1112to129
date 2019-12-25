<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2017 onwards Totara Learning Solutions LTD
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
 * @package totara_form
 */

use totara_form\form\element\behat_helper\element;

/**
 * Test for \totara_form\form\element\action_button class.
 */
class totara_form_behat_helper_testcase extends advanced_testcase {
    public function test_element_split_values() {
        $this->assertSame(array(), element::split_values(''));
        $this->assertSame(array(), element::split_values(' '));
        $this->assertSame(array('a'), element::split_values('a'));
        $this->assertSame(array('a'), element::split_values(' a '));
        $this->assertSame(array('a', 'b'), element::split_values('a,b'));
        $this->assertSame(array('a', 'b'), element::split_values(' a , b '));
        $this->assertSame(array('a , b', 'c'), element::split_values(' a \, b ,c'));
        $this->assertSame(array('a \\', 'b', 'c'), element::split_values(' a \ , b ,c'));
    }
}