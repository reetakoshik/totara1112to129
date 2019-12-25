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
 * @package mod_quiz
 * @category phpunit
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;


/**
 * Test the question_categories_calculate_includedcount method
 */
class questionlib_question_categories_calculate_includedcount_testcase extends advanced_testcase {

    public function test_no_parents() {
        global $CFG;

        $this->resetAfterTest();

        $categories = array();
        for ($i = 1; $i <= 5; $i++) {
            $categories[$i] = new stdClass ();
            $categories[$i]->id = $i;
            $categories[$i]->contextid = $i;
            $categories[$i]->parent = 0;
            $categories[$i]->questioncount = $i;
        }

        $data = question_categories_calculate_includedcount($categories);
        foreach ($categories as $category) {
            $key = "$category->id,$category->contextid";
            $this->assertEquals($category->questioncount, $data[$key]['questioncount']);
            $this->assertEquals($category->questioncount, $data[$key]['includedcount']);
        }
    }

    public function test_single_parents() {
        global $CFG;

        $this->resetAfterTest();

        $categories = array();
        for ($i = 1; $i <= 5; $i++) {
            $categories[$i] = new stdClass ();
            $categories[$i]->id = $i;
            $categories[$i]->contextid = $i;
            $categories[$i]->parent = $i - 1;
            $categories[$i]->questioncount = $i;
        }

        $data = question_categories_calculate_includedcount($categories);
        $cnt = 0;

        for ($id = 5; $id >= 1; $id--) {
            $category = $categories[$id];

            $cnt += $category->questioncount;
            $key = "$category->id,$category->contextid";
            $this->assertEquals($category->questioncount, $data[$key]['questioncount']);
            $this->assertEquals($cnt, $data[$key]['includedcount']);
        }
    }

    public function test_multiple_parents() {
        global $CFG;

        $this->resetAfterTest();

        $categories = array();
        for ($i = 1; $i <= 5; $i++) {
            $categories[$i] = new stdClass ();
            $categories[$i]->id = $i;
            $categories[$i]->contextid = $i;
            $categories[$i]->parent = ($i == 1) ? 0 : 1;
            $categories[$i]->questioncount = $i;
        }

        $data = question_categories_calculate_includedcount($categories);
        $cnt = 0;

        for ($id = 5; $id >= 1; $id--) {
            $category = $categories[$id];

            $cnt += $category->questioncount;
            $key = "$category->id,$category->contextid";
            $this->assertEquals($category->questioncount, $data[$key]['questioncount']);
            if ($id == 1) {
                $this->assertEquals($cnt, $data[$key]['includedcount']);
            } else {
                $this->assertEquals($category->questioncount, $data[$key]['includedcount']);
            }
        }
    }
}
