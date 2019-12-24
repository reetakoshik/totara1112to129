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
 * @author Petr Skoda <petr.skoda@totaralms.com>
 * @package totara_core
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Tests our extensions in core_component class.
 */
class totara_core_component_testcase extends advanced_testcase {
    public function test_get_namespace_classes() {
        $displayclasses = core_component::get_namespace_classes('rb\display');
        $this->assertInternalType('array', $displayclasses);
        foreach ($displayclasses as $displayclass) {
            $this->assertContains('\rb\display\\', $displayclass);
        }
        $this->assertContains('totara_reportbuilder\rb\display\base', $displayclasses);
        $count = count($displayclasses);

        $displayclasses = core_component::get_namespace_classes('rb\display', 'totara_reportbuilder\rb\display\base');
        $this->assertInternalType('array', $displayclasses);
        foreach ($displayclasses as $displayclass) {
            $this->assertContains('\rb\display\\', $displayclass);
        }
        // NOTE: the display base class is not abstract.
        $this->assertCount($count, $displayclasses, 'All classes in rb\dispaly are expected to be extending base class!');

        $displayclasses = core_component::get_namespace_classes('rb\display', 'totara_reportbuilder\rb\display\legacy');
        $this->assertInternalType('array', $displayclasses);
        $this->assertContains('totara_reportbuilder\rb\display\legacy', $displayclasses);
        $this->assertCount(1, $displayclasses);

        $displayclasses = core_component::get_namespace_classes('rb\aggregate', null, null, true);
        $this->assertInternalType('array', $displayclasses);
        foreach ($displayclasses as $displayclass) {
            $this->assertContains('rb\aggregate\\', $displayclass);
        }
        $this->assertNotContains('totara_reportbuilder\rb\aggregate\base', $displayclasses);

        $displayclasses = core_component::get_namespace_classes('rb\aggregate', null, null, false);
        $this->assertInternalType('array', $displayclasses);
        foreach ($displayclasses as $displayclass) {
            $this->assertContains('rb\aggregate\\', $displayclass);
        }
        $this->assertContains('totara_reportbuilder\rb\aggregate\base', $displayclasses);

        $displayclasses = core_component::get_namespace_classes('rb\display', 'totara_reportbuilder\rb\display\base', 'totara_reportbuilder');
        $this->assertInternalType('array', $displayclasses);
        $this->assertGreaterThan(15, count($displayclasses));
        foreach ($displayclasses as $displayclass) {
            $this->assertContains('totara_reportbuilder\rb\display\\', $displayclass);
        }

        $logreaders = core_component::get_namespace_classes('log', 'core\log\reader');
        $this->assertGreaterThan(2, count($logreaders));
        foreach ($logreaders as $logreader) {
            $rc = new ReflectionClass($logreader);
            $this->assertTrue($rc->implementsInterface('core\log\reader'));
        }

        $displayclasses = core_component::get_namespace_classes('dsffdsdfsfdsfds');
        $this->assertSame(array(), $displayclasses);
        $this->assertDebuggingNotCalled();

        $displayclasses = core_component::get_namespace_classes('rb\display', 'xxxxdsfdsfsddsf');
        $this->assertSame(array(), $displayclasses);
        $this->assertDebuggingCalled();
    }
}
