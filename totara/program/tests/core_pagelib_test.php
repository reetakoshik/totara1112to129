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
 * @author Brendan Cox <brendan.cox@totaralearning.com>
 * @package totara_program
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

/**
 * Class totara_program_core_pagelib_testcase
 *
 * Tests the moodle_page class or anything in lib/pagelib.php as it relates to totara programs.
 * Putting this here and not in the moodle_page_test.php file so that it's not lost in a merge
 * from Moodle.
 */
class totara_program_core_pagelib_testcase extends advanced_testcase {

    /**
     * @var string the name of a theme that does not equal the default theme.
     */
    private $second_theme = 'base';

    public function setUp() {
        parent::setUp();
        $this->resetAfterTest();

        // Make sure the themes are different, otherwise our testing is redundant.
        $this->assertNotEquals(theme_config::DEFAULT_THEME, $this->second_theme);
    }

    /**
     * If we've created a page and haven't set the program, we can still try and get a program
     * but will just get null back.
     */
    public function test_no_program_returns_null() {
        $page = new moodle_page();
        $this->assertNull($page->program);
        $this->assertEquals(context_system::instance(), $page->context);
    }

    /**
     * We create a page and set the program. We should then be able to get that program via the program property.
     * Sounds like overtesting, but __get is used to call another method that does this, so worth a quick test.
     * The page context should also have been set.
     */
    public function test_added_program_is_returned() {
        /** @var program $program */
        $program = $this->getDataGenerator()->get_plugin_generator('totara_program')->create_program();

        $page = new moodle_page();
        $page->set_program($program);
        $this->assertEquals($program, $page->program);
        $this->assertEquals(context_program::instance($program->id), $page->context);
    }

    /**
     * We've set a program but there's no theme for its category. It should just fall to the default theme.
     */
    public function test_program_no_category_theme() {
        global $CFG;

        /** @var program $program */
        $program = $this->getDataGenerator()->get_plugin_generator('totara_program')->create_program();

        // The theme should just be the default whether the category themes setting is on or off.
        $page_nocfg = new moodle_page();
        $page_nocfg->set_program($program);
        $this->assertEquals(theme_config::DEFAULT_THEME, $page_nocfg->theme->name);

        $CFG->allowcategorythemes = true;

        $page_withcfg = new moodle_page();
        $page_withcfg->set_program($program);
        $this->assertEquals(theme_config::DEFAULT_THEME, $page_withcfg->theme->name);
    }

    /**
     * We've set a program and the theme for the category the program is in is different from the default theme.
     * The page should be set to that category theme.
     */
    public function test_program_has_category_theme() {
        global $DB, $CFG;

        $category = $DB->get_record('course_categories', array('name' => 'Miscellaneous'));
        $category->theme = $this->second_theme;
        $DB->update_record('course_categories', $category);

        /** @var program $program */
        $program = $this->getDataGenerator()->get_plugin_generator('totara_program')->create_program();

        // The program's category theme should not have an effect until the allowcategorythemes config setting is on.
        $page_nocfg = new moodle_page();
        $page_nocfg->set_program($program);
        $this->assertEquals(theme_config::DEFAULT_THEME, $page_nocfg->theme->name);

        $CFG->allowcategorythemes = true;

        $page_withcfg = new moodle_page();
        $page_withcfg->set_program($program);
        $this->assertEquals($this->second_theme, $page_withcfg->theme->name);
    }

    /**
     * This is well tested in a number of ways, but adding a test where no program is set and confirms that
     * we can still get a theme back. This validates the tests above.
     */
    public function test_no_program_get_theme() {
        global $DB, $CFG;

        // Add the theme to the category and add the program as well. These don't get used but should be present
        // since they are in the above tests.

        $category = $DB->get_record('course_categories', array('name' => 'Miscellaneous'));
        $category->theme = $this->second_theme;
        $DB->update_record('course_categories', $category);

        /** @var program $program */
        $program = $this->getDataGenerator()->get_plugin_generator('totara_program')->create_program();

        // The theme should just be the default whether the category themes setting is on or off.
        $page_nocfg = new moodle_page();
        // No program added to the page.
        $this->assertEquals(theme_config::DEFAULT_THEME, $page_nocfg->theme->name);

        $CFG->allowcategorythemes = true;

        $page_withcfg = new moodle_page();
        // No program added to the page.
        $this->assertEquals(theme_config::DEFAULT_THEME, $page_withcfg->theme->name);
    }
}