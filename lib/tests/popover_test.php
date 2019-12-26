<?php
/*
 * This file is part of Totara LMS
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
 * @author Brian Barnes <brian.barnes@totaralearning.com>
 * @package core_output
 */

defined('MOODLE_INTERNAL') || die();

use core\output\popover;

/**
 * Test for \core\output\popover class.
 */
class core_output_popover_testcase extends basic_testcase {

    public function test_instantiation_class() {
        global $OUTPUT;
        $context = array(
            'a' => 'b',
            'c' => 'd',
            'x' => 'z'
        );
        $template = 'template';
        $title = 'my title';

        $popover = popover::create_from_template($template, $context, $title);

        $property = new ReflectionProperty($popover, 'template');
        $property->setAccessible(true);
        $this->assertSame($template, $property->getValue($popover));

        $property = new ReflectionProperty($popover, 'templatecontext');
        $property->setAccessible(true);
        $this->assertEquals($context, $property->getValue($popover));

        $property = new ReflectionProperty($popover, 'title');
        $property->setAccessible(true);
        $this->assertSame($title, $property->getValue($popover));

        $output = array(
            'contenttemplate' => $template,
            'contenttemplatecontext' => $context,
            'title' => $title,
            'contentraw' => '',
            'arrow_placement' => null,
            'close_on_focus_out' => true,
            'placement_max_height' => null,
            'max_height' => null,
            'placement_max_width' => null,
            'max_width' => null,
            'trigger' => ''
        );
        $this->assertSame($output, $popover->export_for_template($OUTPUT));
    }

    public function test_instantiation_string() {
        global $OUTPUT;
        $content = 'Hi there';
        $title = 'my title';

        $popover = popover::create_from_text($content, $title);


        $property = new ReflectionProperty($popover, 'text');
        $property->setAccessible(true);
        $this->assertSame($content, $property->getValue($popover));

        $property = new ReflectionProperty($popover, 'title');
        $property->setAccessible(true);
        $this->assertSame($title, $property->getValue($popover));

        $output = array(
            'contenttemplate' => false,
            'contenttemplatecontext' => false,
            'title' => $title,
            'contentraw' => $content,
            'arrow_placement' => null,
            'close_on_focus_out' => true,
            'placement_max_height' => null,
            'max_height' => null,
            'placement_max_width' => null,
            'max_width' => null,
            'trigger' => ''
        );
        $this->assertSame($output, $popover->export_for_template($OUTPUT));
    }
}