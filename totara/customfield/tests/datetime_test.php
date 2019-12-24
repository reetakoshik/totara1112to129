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
 * @author Simon Player <simon.player@totaralms.com>
 * @package totara_customfield
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/totara/customfield/fieldlib.php');
require_once($CFG->dirroot . '/totara/customfield/field/datetime/field.class.php');

class totara_customfield_datetime_testcase extends advanced_testcase {

    private $generator;
    private $datetime_id;
    private $item_obj;
    private $prefix = 'facetofacesignup';
    private $tableprefix = 'facetoface_signup';

    protected function tearDown() {
        $this->generator = null;
        $this->datetime_id = null;
        $this->item_obj = null;
        $this->prefix = null;
        $this->tableprefix = null;
        parent::tearDown();
    }

    public function setUp() {
        global $DB;

        $this->generator = $this->getDataGenerator()->get_plugin_generator('totara_customfield');
        $settings = array('Datetime' => array('shortname' => 'datetime', 'forceunique' => true,  'startyear' => 2015, 'endyear' => 2030));
        $customfields = $this->generator->create_datetime('facetoface_signup', $settings);

        // Get the record id of the datetime custom field we've created.
        $this->datetime_id = $DB->get_field('facetoface_signup_info_field', 'id', array('shortname' => 'datetime'));

        // Create an custom field item object.
        $this->item_obj = new stdClass();
        $this->item_obj->id = 1;
        $this->item_obj->username = 'learner1';
        $this->item_obj->customfield_datetime = '1467130866';

        parent::setUp();
    }

    /**
     * Do a basic VALID test of the edit_validate_field function for the date/time custom field.
     */
    public function test_edit_validate_field_timestamp() {
        $this->resetAfterTest(true);

        // Create the custom field object and validate the data.
        $formfield = new customfield_datetime ($this->datetime_id, $this->item_obj, $this->prefix, $this->tableprefix);
        $errors = $formfield->edit_validate_field ($this->item_obj, $this->prefix, $this->tableprefix);

        // No errors we've passed in a valid timestamp.
        $this->assertCount(0, $errors);
    }

    /**
     * Test edit_validate_field function with some invalid date.
     */
    public function test_edit_validate_field_invalid() {
        $this->resetAfterTest(true);

        $this->item_obj->customfield_datetime = 'abcde';

        // Create the custom field object and validate the data.
        $formfield = new customfield_datetime ($this->datetime_id, $this->item_obj, $this->prefix, $this->tableprefix);
        $errors = $formfield->edit_validate_field ($this->item_obj, $this->prefix, $this->tableprefix);

        // One error as we've passed in an invalid date.
        $this->assertCount(1, $errors);
        $this->assertEquals($errors, array('customfield_datetime' => "The 'datetime' date/time custom field contains an invalid date ('abcde')."));
    }

    /**
     * Test edit_validate_field function checking the lower year range.
     */
    public function test_edit_validate_field_year_range_lower() {
        global $DB;

        $this->resetAfterTest(true);

        // Test a date that's earlier than the permitted lower year.
        $this->item_obj->customfield_datetime = '2014-12-31 11:59:59';

        // Create the custom field object and validate the data.
        $formfield = new customfield_datetime ($this->datetime_id, $this->item_obj, $this->prefix, $this->tableprefix);
        $errors = $formfield->edit_validate_field ($this->item_obj, $this->prefix, $this->tableprefix);

        // One error as we've passed in a date that's too early.
        $this->assertCount(1, $errors);
        $this->assertEquals($errors, array('customfield_datetime' => "The 'datetime' date/time custom field contains a date ('2014-12-31 11:59:59') earlier than 2015."));

        // Check that the lowest valid date works okay.
        $this->item_obj->customfield_datetime = '2015-01-01';

        // Create the custom field object and validate the data..
        $formfield = new customfield_datetime ($this->datetime_id, $this->item_obj, $this->prefix, $this->tableprefix);
        $errors = $formfield->edit_validate_field ($this->item_obj, $this->prefix, $this->tableprefix);

        // No errors we've passed in a valid timestamp.
        $this->assertCount(0, $errors);
    }

    /**
     * Test edit_validate_field function checking the upper year range.
     */
    public function test_edit_validate_field_year_range_upper() {
        global $DB;

        $this->resetAfterTest(true);

        // Test a date that's later than the permitted upper year.
        $this->item_obj->customfield_datetime = '2031-01-01 00:00:01';

        // Create the custom field object and validate the data..
        $formfield = new customfield_datetime ($this->datetime_id, $this->item_obj, $this->prefix, $this->tableprefix);
        $errors = $formfield->edit_validate_field ($this->item_obj, $this->prefix, $this->tableprefix);

        // One error as we've passed in a date that's too early.
        $this->assertCount(1, $errors);
        $this->assertEquals($errors, array('customfield_datetime' => "The 'datetime' date/time custom field contains a date ('2031-01-01 00:00:01') later than 2030."));

        // Check that the lowest valid date works okay.
        $this->item_obj->customfield_datetime = '2030-12-31 11:59:59';

        // Create the custom field object and validate the data..
        $formfield = new customfield_datetime ($this->datetime_id, $this->item_obj, $this->prefix, $this->tableprefix);
        $errors = $formfield->edit_validate_field ($this->item_obj, $this->prefix, $this->tableprefix);

        // No errors we've passed in a valid timestamp.
        $this->assertCount(0, $errors);
    }

    /**
     * Test edit_validate_field function checking unique values.
     */
    public function test_edit_validate_field_unqiue() {
        $this->resetAfterTest(true);

        $unique_value1 = '2020-12-25 15:00:00';
        $unique_value2 = '2020-12-31 23:00:00';

        // Insert a couple of values so we can check.
        $this->generator->set_datetime($this->item_obj, $this->datetime_id, strtotime($unique_value1), $this->prefix, $this->tableprefix);

        // Try and validate the first value. it already exists, so
        // validation should fail because it's not a unique value.
        $this->item_obj->id = 2;
        $this->item_obj->customfield_datetime = $unique_value1;
        $formfield = new customfield_datetime ($this->datetime_id, $this->item_obj, $this->prefix, $this->tableprefix);
        $errors = $formfield->edit_validate_field ($this->item_obj, $this->prefix, $this->tableprefix);

        // One error as we've passed in an non-unique date.
        $this->assertCount(1, $errors);
        $this->assertEquals($errors, array('customfield_datetime' => "The 'datetime' date/time custom field contains a non-unique date ('2020-12-25 15:00:00')."));

        // Try again with a value we know is unique.
        $this->item_obj->customfield_datetime = $unique_value2;
        $formfield = new customfield_datetime ($this->datetime_id, $this->item_obj, $this->prefix, $this->tableprefix);
        $errors = $formfield->edit_validate_field ($this->item_obj, $this->prefix, $this->tableprefix);

        // No error as we've passed in an unique date.
        $this->assertCount(0, $errors);
    }

    public function test_edit_save_data_timestamp() {
        $this->resetAfterTest(true);

        // Create the custom field object and validate the data.
        $formfield = new customfield_datetime ($this->datetime_id, $this->item_obj, $this->prefix, $this->tableprefix);
        $item = $formfield->edit_save_data ($this->item_obj, $this->prefix, $this->tableprefix);

        // No change we've passed in a valid timestamp.
        $this->assertEquals('1467130866', $item->customfield_datetime);
    }

    public function test_edit_save_data_timestamp_invalid() {
        $this->resetAfterTest(true);

        $this->item_obj->customfield_datetime = 'uvwxyz';

        // Create the custom field object and validate the data.
        $formfield = new customfield_datetime ($this->datetime_id, $this->item_obj, $this->prefix, $this->tableprefix);
        $item = $formfield->edit_save_data ($this->item_obj, $this->prefix, $this->tableprefix);

        // No change we've passed in a valid timestamp.
        $this->assertEquals(false, $item->customfield_datetime);
    }

    public function test_edit_save_data_timestamp_valid() {
        $this->resetAfterTest(true);

        $this->item_obj->customfield_datetime = '30th June 2020';

        // Create the custom field object and validate the data.
        $formfield = new customfield_datetime ($this->datetime_id, $this->item_obj, $this->prefix, $this->tableprefix);

        $item = $formfield->edit_save_data ($this->item_obj, $this->prefix, $this->tableprefix);

        // No change we've passed in a valid timestamp.
        $this->assertEquals('1593446400', $item->customfield_datetime);
    }

    public function test_display_item_data_guessing() {
        global $CFG;
        $this->resetAfterTest(true);

        // System timezone first with default admin timezone.
        $date = make_timestamp(2014, 1, 2, 10, 37, 0);
        $this->assertSame('Thursday, 2 January 2014, 10:37 AM', customfield_datetime::display_item_data($date));
        $this->assertSame('Thursday, 2 January 2014, 10:37 AM', customfield_datetime::display_item_data('2014-01-02T10:37'));
        $date = make_timestamp(2014, 1, 2, 0, 0, 10);
        $this->assertSame('2 January 2014', customfield_datetime::display_item_data($date));
        $this->assertSame('Not set', customfield_datetime::display_item_data(0));

        // Try user timezones - the results are pretty unpredictable, right?
        $user = $this->getDataGenerator()->create_user(array('timezone' => 'Europe/Prague'));
        $this->setUser($user);
        $date = make_timestamp(2014, 1, 2, 10, 37, 0, 'Europe/Prague');
        $this->assertSame('Thursday, 2 January 2014, 10:37 AM', customfield_datetime::display_item_data($date));
        $date = make_timestamp(2014, 1, 2, 0, 0, 10, 'Europe/Prague');
        $this->assertSame('Thursday, 2 January 2014, 12:00 AM', customfield_datetime::display_item_data($date));
        $date = make_timestamp(2014, 1, 2, 0, 0, 10, $CFG->timezone);
        $this->assertSame('1 January 2014', customfield_datetime::display_item_data($date));
        $this->assertSame('Not set', customfield_datetime::display_item_data(0));
    }
}
