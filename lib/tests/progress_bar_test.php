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
 * @package totara_form
 */

 /**
 * Unit tests for class progress_bar inside lib/outputcomponents.php.
 *
 * @package   core
 * @category  phpunit
 * @copyright 2017 Brian Barnes <brian.barnes@totaralearning
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use core\output\popover;

global $CFG;
require_once($CFG->libdir . '/outputcomponents.php');

 class progress_bar_test extends basic_testcase {

    public function test_instantiation() {
        global $OUTPUT;
        $progress_bar = new static_progress_bar('abc', 0);
        $expected = array(
            'id' => 'abc',
            'width' => 0,
            'progress' => 0,
            'progresstext' => get_string('xpercent', 'core', 0),
        );

        $this->assertSame($expected, $progress_bar->export_for_template($OUTPUT));

        $progress_bar = new static_progress_bar('abc');
        $expected = array(
            'id' => 'abc',
            'width' => 500,
            'progress' => 0,
            'progresstext' => get_string('xpercent', 'core', 0),
        );

        $this->assertSame($expected, $progress_bar->export_for_template($OUTPUT));
    }

    public function test_id_generation() {
        global $OUTPUT;
        $pb1 = new static_progress_bar();
        $pb1 = $pb1->export_for_template($OUTPUT);
        $pb2 = new static_progress_bar();
        $pb2 = $pb2->export_for_template($OUTPUT);

        $this->assertNotEquals($pb1['id'], $pb2['id']);
    }

    public function test_progress_indication() {
        global $OUTPUT;
        $progress_bar = new progress_bar();

        // Default value
        $this->assertEquals($progress_bar->export_for_template($OUTPUT)['progress'], 0);

        // normal value
        $progress_bar->set_progress(5);
        $this->assertEquals($progress_bar->export_for_template($OUTPUT)['progress'], 5);

        // negative value
        $progress_bar->set_progress(-5);
        $this->assertEquals($progress_bar->export_for_template($OUTPUT)['progress'], 0);

        // large value
        $progress_bar->set_progress(5000);
        $this->assertEquals($progress_bar->export_for_template($OUTPUT)['progress'], 100);
    }

    public function test_popover_integration() {
        global $OUTPUT;

        $content = 'Hi there';
        $title = 'my title';

        $popover = popover::create_from_text($content, $title);

        $progress_bar = new progress_bar('abc', 0);
        $progress_bar->set_progress(10);
        $progress_bar->add_popover($popover);

        $expected = array(
            'id' => 'abc',
            'width' => 0,
            'progress' => 10,
            'progresstext' => get_string('xpercent', 'core', 10),
            'popover' => array(
                'contenttemplate' => false,
                'contenttemplatecontext' => false,
                'title' => 'my title',
                'contentraw' => 'Hi there',
                'arrow_placement' => null,
                'close_on_focus_out' => true,
                'placement_max_height' => null,
                'max_height' => null,
                'placement_max_width' => null,
                'max_width' => null,
                'trigger' => ''
            )
        );

        $this->assertSame($expected, $progress_bar->export_for_template($OUTPUT));
    }
 }