<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2010 onwards Totara Learning Solutions LTD
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
 * @author Brendan Cox <brendan.cox@totaralms.com>
 * @package totara_core
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.'); // It must be included from a Moodle page.
}

global $CFG;
require_once($CFG->dirroot . '/question/category_form.php');
require_once($CFG->dirroot . '/question/editlib.php');

class moodle_question_testcase extends advanced_testcase
{
    public function test_check_for_invalid_question_category_parents()
    {
        global $DB;

        $this->resetAfterTest();

        $generator = $this->getDataGenerator()->get_plugin_generator('core_question');

        $cat1 = $generator->create_question_category(array(
            'name' => 'Category1', 'sortorder' => 1));

        $cat2 = $generator->create_question_category(array(
            'name' => 'Category2', 'sortorder' => 1, 'parent' => $cat1->id));

        $cat3 = $generator->create_question_category(array(
            'name' => 'Category3', 'sortorder' => 1, 'parent' => $cat2->id));

        $cat4 = $generator->create_question_category(array(
            'name' => 'Category4', 'sortorder' => 1, 'parent' => $cat3->id));

        $cat5 = $generator->create_question_category(array(
            'name' => 'Category5', 'sortorder' => 1));

        // Setting up form object. We only need to do this once, since we'll overwrite the id and name with
        // the category we're editing anyway with the $data array.
        $context = new stdClass();
        $context->id = $cat1->contextid;
        $contexts = array ('contexts' => $context);
        $categoryform = new question_category_edit_form('', array('contexts' => $contexts, 'currentcat' => $cat1->id));

        $data = array('id' => $cat1->id, 'name' => $cat1->name, 'parent' => $cat3->id);
        $returned = $categoryform->validation($data, null);

        // No conflicts - validation returns an empty array.
        $data = array('id' => $cat4->id, 'name' => $cat4->name, 'parent' => $cat3->id);
        $this->assertEmpty($categoryform->validation($data, null));

        $data = array('id' => $cat1->id, 'name' => $cat1->name, 'parent' => $cat5->id);
        $this->assertEmpty($categoryform->validation($data, null));

        $data = array('id' => $cat5->id, 'name' => $cat5->name, 'parent' => $cat1->id);
        $this->assertEmpty($categoryform->validation($data, null));

        // No conflicts - change in parent (and no pre-existing conflicts).
        $data = array('id' => $cat4->id, 'name' => $cat4->name, 'parent' => $cat3->id);
        $this->assertEmpty($categoryform->validation($data, null));

        // No conflicts when moving to top level.
        $data = array('id' => $cat4->id, 'name' => $cat4->name, 'parent' => 0);
        $this->assertEmpty($categoryform->validation($data, null));

        // Conflict - error returned when creating a loop.
        $data = array('id' => $cat1->id, 'name' => $cat1->name, 'parent' => $cat2->id);
        $this->assertEquals(array('parent' => get_string('movecategoryparentconflict', 'error', $cat1->name)), $categoryform->validation($data, null));

        $data = array('id' => $cat1->id, 'name' => $cat1->name, 'parent' => $cat4->id);
        $this->assertEquals(array('parent' => get_string('movecategoryparentconflict', 'error', $cat1->name)), $categoryform->validation($data, null));

        // Now to test with a pre-existing loop.
        $cat1->parent = $cat4->id;
        $DB->update_record('question_categories', $cat1);
        $data = array('id' => $cat5->id, 'name' => $cat5->name, 'parent' => $cat4->id);
        $this->assertEquals(array('parent' => get_string('parentloopdetected', 'question')), $categoryform->validation($data, null));

        // Correct the above change but then try remove one of the parent categories.
        $cat1->parent = 0;
        $DB->update_record('question_categories', $cat1);
        $DB->delete_records('question_categories', array('id' => $cat2->id));
        $data = array('id' => $cat5->id, 'name' => $cat5->name, 'parent' => $cat4->id);
        $this->assertEquals(array('parent' => get_string('parentcategorymissing', 'question')), $categoryform->validation($data, null));
    }
}