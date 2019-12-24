<?php
/**
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
 * @author Andrew McGhie <andrew.mcghie@totaralearning.com>
 * @package block_totara_featured_links
 */
require_once('test_helper.php');


defined('MOODLE_INTERNAL') || die();

/**
 * Class block_totara_featured_links_learning_item_tile_testcase
 * Test the course_tile program_tile and certification_tile class
 */
class block_totara_featured_links_learning_item_tile_testcase extends test_helper {

    /**
     * Makes sure that the course id is saved to the database.
     */
    public function test_save_content_tile() {
        $this->resetAfterTest(false);
        global $DB;

        $this->setAdminUser();
        /* @var block_totara_featured_links_generator $blockgenerator */
        $blockgenerator = $this->getDataGenerator()->get_plugin_generator('block_totara_featured_links');
        $instance = $blockgenerator->create_instance();
        $tile = $blockgenerator->create_course_tile($instance->id);
        $tilenocourse = $blockgenerator->create_course_tile($instance->id);

        $course = $this->getDataGenerator()->create_course();
        $data = new \stdClass();
        $data->type = 'block_totara_featured_links-course_tile';
        $data->sortorder = 4;
        $data->course_name = $course->fullname;
        $data->course_name_id = $course->id;
        $data->background_color = '#FFFFFF';
        $tile->save_content($data);
        $this->assertEquals($course->id, json_decode($DB->get_field('block_totara_featured_links_tiles', 'dataraw', ['id' => $tile->id]))->courseid);

        return ['course' => $course, 'tile' => $tile, 'tilenocourse' => $tilenocourse];
    }

    /**
     * @param array $tiledata
     * @depends test_save_content_tile
     */
    public function test_get_course_valid($tiledata) {
        $this->resetAfterTest(false);

        /* @var \block_totara_featured_links\tile\course_tile $tile */
        $tile = $tiledata['tile'];
        $course = $tiledata['course'];

        $loadedcourse = $tile->get_course();
        $this->assertEquals($course->id, $loadedcourse->id);
    }

    /**
     * @param array $tiledata
     * @depends test_save_content_tile
     */
    public function test_get_course_reload($tiledata) {
        $this->resetAfterTest(false);
        global $DB;

        /* @var \block_totara_featured_links\tile\course_tile $tile */
        $tile = $tiledata['tile'];
        $course = $tiledata['course'];

        $originalshortname = $course->shortname;
        $course->shortname = 'newshortname';
        $this->assertNotEquals($originalshortname, $course->shortname);

        $DB->update_record('course', $course);

        // Without setting the reload argument to true, it still loads the original shortname.
        $loadedcourse = $tile->get_course();
        $this->assertEquals($originalshortname, $loadedcourse->shortname);

        $reloadedcourse = $tile->get_course(true);
        $this->assertEquals($course->id, $reloadedcourse->id);
        $this->assertEquals('newshortname', $reloadedcourse->shortname);

        /* @var \block_totara_featured_links\tile\course_tile $tilenocourse */
        $tilenocourse = $tiledata['tilenocourse'];
        $this->assertFalse($tilenocourse->get_course());
    }

    /**
     * @param array $tiledata
     * @depends test_save_content_tile
     */
    public function test_get_course_no_course($tiledata) {
        $this->resetAfterTest(false);

        /* @var \block_totara_featured_links\tile\learning_item $tilenocourse */
        $tilenocourse = $tiledata['tilenocourse'];
        $this->assertFalse($tilenocourse->get_course());
    }

    /**
     * Checks that the course is rendered with the tile.
     *
     * @param array $tiledata
     * @depends test_save_content_tile
     */
    public function test_render_course($tiledata) {
        $this->resetAfterTest(false);
        global $PAGE;

        $PAGE->set_url('/');

        $content = $tiledata['tile']->render_content_wrapper($PAGE->get_renderer('core'), []);
        $this->assertStringStartsWith('<div', $content);
        $this->assertStringEndsWith('</div>', $content);
        $this->assertContains('Test course 1', $content);
    }

    /**
     * @param array $tile
     * @depends test_save_content_tile
     */
    public function test_user_can_view_content($tiledata) {
        global $DB;
        $this->resetAfterTest(true);

        $this->setUser();

        /* @var \block_totara_featured_links\tile\learning_item $tile */
        $tile = $tiledata['tile'];
        $course = $tiledata['course'];

        $this->assertTrue($this->call_protected_method($tile, 'user_can_view_content'));

        $course->visible = '0';
        $DB->update_record('course', $course);
        $tile->get_course(true); // Reload the course data.

        $this->assertFalse($this->call_protected_method($tile, 'user_can_view_content'));
        $this->setAdminUser();
        $tile->get_course(true); // Reload the course data.
        $this->assertTrue($this->call_protected_method($tile, 'user_can_view_content'));
    }

    /**
     * makes sure the program gets saved
     *
     * @return array
     */
    public function test_save_program() {
        $this->resetAfterTest(false);
        global $DB;
        $this->setAdminUser();

        /* @var block_totara_featured_links_generator $blockgenerator */
        $blockgenerator = $this->getDataGenerator()->get_plugin_generator('block_totara_featured_links');
        $instance = $blockgenerator->create_instance();
        $tile = $blockgenerator->create_program_tile($instance->id);
        $tilenoprogram = $blockgenerator->create_program_tile($instance->id);

        $programgenerator = $this->getDataGenerator()->get_plugin_generator('totara_program');
        $program = $programgenerator->create_program(['fullname'=> 'Program']);

        $data = new \stdClass();
        $data->type = 'block_totara_featured_links-program_tile';
        $data->sortorder = 4;
        $data->program_name = $program->fullname;
        $data->program_name_id = $program->id;
        $data->background_color = '#FFFFFF';
        $tile->save_content($data);
        $this->assertEquals($program->id, json_decode($DB->get_field('block_totara_featured_links_tiles', 'dataraw', ['id' => $tile->id]))->programid);

        return ['program' => $program, 'tile' => $tile, 'tilenoprogram' => $tilenoprogram];
    }

    /**
     * @param $tiledata
     * @depends test_save_program
     */
    public function test_get_program_valid($tiledata) {
        $this->resetAfterTest(false);

        /** @var block_totara_featured_links\tile\program_tile $tile */
        $tile = $tiledata['tile'];
        $program = $tiledata['program'];

        $loadedprogram = $tile->get_program();
        $this->assertEquals($program->id, $loadedprogram->id);
        $this->assertEquals($program, $loadedprogram);
    }

    /**
     * @param $tiledata
     * @depends test_save_program
     */
    public function test_get_program_reload($tiledata) {
        $this->resetAfterTest(false);
        global $DB;

        /* @var \block_totara_featured_links\tile\program_tile $tile */
        $tile = $tiledata['tile'];
        $program = $tiledata['program'];

        $originalshortname = $program->shortname;
        $program->shortname = 'newshortname';
        $this->assertNotEquals($originalshortname, $program->shortname);

        $DB->update_record('prog', $program);

        // Without setting the reload argument to true, it still loads the original shortname.
        $loadedcourse = $tile->get_program();
        $this->assertEquals($originalshortname, $loadedcourse->shortname);

        $reloadedprogram = $tile->get_program(true);
        $this->assertEquals($program->id, $reloadedprogram->id);
        $this->assertEquals('newshortname', $reloadedprogram->shortname);
    }

    /**
     * @param $tiledata
     * @depends test_save_program
     */
    public function test_get_program_no_program($tiledata) {
        $this->resetAfterTest(false);

        /* @var \block_totara_featured_links\tile\program_tile $tilenoprogram */
        $tilenoprogram = $tiledata['tilenoprogram'];
        $this->assertFalse($tilenoprogram->get_program());
    }

    /**
     * @param $tiledata
     * @depends test_save_program
     */
    public function test_get_program_hidden($tiledata) {
        $this->resetAfterTest(false);
        global $DB;

        /* @var \block_totara_featured_links\tile\program_tile $tile */
        $tile = $tiledata['tile'];
        $program = $tiledata['program'];

        $this->assertNotFalse($tile->get_program());
        $program->visible = 0;
        $DB->update_record('prog', $program);
        $this->setGuestUser();
        $this->assertFalse($tile->get_program(true));
        $this->setAdminUser();
        $this->assertNotFalse($tile->get_program(true));
    }
}