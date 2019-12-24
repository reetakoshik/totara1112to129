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
 * @author Simon Player <simon.player@totaralearning.com>
 * @package totara_customfield
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/totara/customfield/fieldlib.php');
require_once($CFG->dirroot . '/totara/customfield/field/multiselect/field.class.php');

class totara_customfield_multiselect_testcase extends advanced_testcase {

    private $generator;
    private $item_obj;
    private $prefix = 'course';
    private $tableprefix = 'course';

    protected function tearDown() {
        $this->generator = null;
        $this->item_obj = null;
        $this->prefix = null;
        $this->tableprefix = null;
        parent::tearDown();
    }

    public function setUp() {
        $this->generator = $this->getDataGenerator()->get_plugin_generator('totara_customfield');

        // Create an custom field item object.
        $this->item_obj = new stdClass();
        $this->item_obj->id = 1;
        $this->item_obj->username = 'learner1';

        parent::setUp();
    }

    public function test_sync_data_preprocess() {
        $this->resetAfterTest(true);

        //
        // Single item to be saved.
        //
        $name = "multiselect1";
        $options = ['opt1', 'opt2', 'opt3'];
        $saved = "opt1";

        // Create the multiselect customfield.
        $ids = $this->generator->create_multiselect('course', array($name => $options));

        // Save the customfield data.
        $field = "customfield_" . $name;
        $this->item_obj->$field = $saved;
        customfield_save_data($this->item_obj, $this->prefix, $this->tableprefix, true);

        // Check the saved data.
        $customfields = new customfield_multiselect($ids[$name], $this->item_obj, $this->prefix, $this->tableprefix);
        $savedata = $customfields->display_data();
        $this->assertSame('opt1', strip_tags($savedata));

        //
        // multiple items to be saved.
        //
        $name = "multiselect2";
        $options = ['opt1', 'opt2', 'opt3'];
        $saved = "opt1, opt3";

        // Create the multiselect customfield.
        $ids = $this->generator->create_multiselect('course', array($name => $options));

        // Save the customfield data.
        $field = "customfield_" . $name;
        $this->item_obj->$field = $saved;
        customfield_save_data($this->item_obj, $this->prefix, $this->tableprefix, true);

        // Check the saved data.
        $customfields = new customfield_multiselect($ids[$name], $this->item_obj, $this->prefix, $this->tableprefix);
        $savedata = $customfields->display_data();
        $this->assertSame('opt1 opt3', strip_tags($savedata));

        //
        // Single item with comma , to be saved.
        //
        $name = "multiselect3";
        $options = ['opt1 with , comma', 'opt2 with , comma', 'opt3 with , comma'];
        $saved = "'opt1 with , comma'";

        // Create the multiselect customfield.
        $ids = $this->generator->create_multiselect('course', array($name => $options));

        // Save the customfield data.
        $field = "customfield_" . $name;
        $this->item_obj->$field = $saved;
        customfield_save_data($this->item_obj, $this->prefix, $this->tableprefix, true);

        // Check the saved data.
        $customfields = new customfield_multiselect($ids[$name], $this->item_obj, $this->prefix, $this->tableprefix);
        $savedata = $customfields->display_data();
        $this->assertSame("opt1 with , comma", strip_tags($savedata));

        //
        // Multiple items with comma , to be saved.
        //
        $name = "multiselect4";
        $options = ['opt1 with , comma', 'opt2 with , comma', 'opt3 with , comma'];
        $saved = "'opt1 with , comma', 'opt3 with , comma'";

        // Create the multiselect customfield.
        $ids = $this->generator->create_multiselect('course', array($name => $options));

        // Save the customfield data.
        $field = "customfield_" . $name;
        $this->item_obj->$field = $saved;
        customfield_save_data($this->item_obj, $this->prefix, $this->tableprefix, true);

        // Check the saved data.
        $customfields = new customfield_multiselect($ids[$name], $this->item_obj, $this->prefix, $this->tableprefix);
        $savedata = $customfields->display_data();
        $this->assertSame("opt1 with , comma opt3 with , comma", strip_tags($savedata));

        //
        // Multiple items with and without commas , to be saved.
        //
        $name = "multiselect5";
        $options = ['opt1 with , comma', 'opt2 with , comma', 'opt3 with , comma', 'opt5', 'opt6', 'opt7'];
        $saved = "'opt1 with , comma', 'opt3 with , comma', 'opt5', opt7";

        // Create the multiselect customfield.
        $ids = $this->generator->create_multiselect('course', array($name => $options));

        // Save the customfield data.
        $field = "customfield_" . $name;
        $this->item_obj->$field = $saved;
        customfield_save_data($this->item_obj, $this->prefix, $this->tableprefix, true);

        // Check the saved data.
        $customfields = new customfield_multiselect($ids[$name], $this->item_obj, $this->prefix, $this->tableprefix);
        $savedata = $customfields->display_data();
        $this->assertSame("opt1 with , comma opt3 with , comma opt5 opt7", strip_tags($savedata));

    }

}
