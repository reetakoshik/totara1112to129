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
 * @author     Sam Hemelryk <sam.hemelryk@totaralearning.com>
 * @copyright  2016 Totara Learning Solutions
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package    core_completion
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/completion/data_object.php');
require_once($CFG->dirroot . '/completion/tests/fixtures/unit_test_user_data_object.php');

/**
 * Data object tests.
 *
 * We use a class \unit_test_user_data_object for testing.
 * This is a fixture and is only usable within PHPUNIT tests.
 *
 * @author     Sam Hemelryk <sam.hemelryk@totaralearning.com>
 * @copyright  2016 Totara Learning Solutions
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package    core_completion
 */
class core_completion_data_object_testcase extends advanced_testcase {

    /**
     * Sets up the new completion data object test.
     */
    public function setUp() {
        $this->resetAfterTest();
        $users = [
            ['username' => 'samh', 'firstname' => 'Sam', 'lastname' => 'Hemelryk', 'password' => 'samh',
                'email' => 'sam.hemelryk@totaralearning.com'],
            ['username' => 'petrs', 'firstname' => 'Petr', 'lastname' => 'Škoda', 'password' => 'petrs',
                'email' => 'petr.skoda@totaralearning.com'],
            ['username' => 'simonc', 'firstname' => 'Simon', 'lastname' => 'Coggins', 'password' => 'simonc',
                'email' => 'simon.coggins@totaralearning.com'],
            ['username' => 'davek', 'firstname' => 'David', 'lastname' => 'Krill', 'password' => 'davidk',
                'email' => 'david.kirll@totaralearning.com', 'description' => 'Test description', 'descriptionformat' => FORMAT_MOODLE],
        ];
        $generator = $this->getDataGenerator();
        foreach ($users as $key => $user) {
            $record = $generator->create_user($user);
            $users[$key]['id'] = $record->id;
        }
    }

    /**
     * Tests the construction of the a new data object in the most common case.
     */
    public function test_construction() {
        global $DB;

        $record = $DB->get_record('user', ['username' => 'samh'], '*', MUST_EXIST);
        $user = new unit_test_user_data_object(['id' => $record->id]);

        foreach ($user->required_fields as $field) {
            $this->assertSame($record->$field, $user->$field, 'Required field ' . $field . ' does not match the database record value');
        }
    }

    /**
     * Test construction by params, requiring that one be presented to the array handline on fetch_all_helper.
     */
    public function test_construction_by_params() {
        global $DB;

        $record = $DB->get_record('user', ['username' => 'samh'], '*', MUST_EXIST);
        $user = new unit_test_user_data_object(['id' => $record->id, 'firstname' => 'Sam'], ['firstname']);

        foreach ($user->required_fields as $field) {
            $this->assertSame($record->$field, $user->$field, 'Required field ' . $field . ' does not match the database record value');
        }
    }

    /**
     * Test construction without fetching from the database.
     */
    public function test_construction_without_fetch() {
        global $DB;

        $record = $DB->get_record('user', ['username' => 'samh'], '*', MUST_EXIST);
        $user = new unit_test_user_data_object((array)$record, false);

        foreach ($user->required_fields as $field) {
            $this->assertSame($record->$field, $user->$field, 'Required field ' . $field . ' does not match the database record value');
        }
        foreach ($user->optional_fields as $field => $default) {
            $this->assertSame($record->$field, $user->$field, 'Optional field ' . $field . ' does not match the database record value');
        }
    }

    /**
     * Test construction including fetching all required fields in the where using the current values.
     */
    public function test_construction_fetching_all_required_fields() {
        global $DB;

        $requiredfields = [
            'id',
            'username',
            'deleted',
            'suspended',
            'idnumber',
            'firstname',
            'lastname',
            'email',
            'emailstop',
            'lang',
            'theme',
            'timezone',
            'lastnamephonetic',
            'firstnamephonetic',
            'middlename',
            'alternatename',
            'auth',
            'confirmed',
            'policyagreed',
            'mnethostid',
            'password',
            'icq',
            'skype',
            'yahoo',
            'aim',
            'msn',
            'phone1',
            'phone2',
            'institution',
            'department',
            'address',
            'city',
            'country',
            'calendartype',
            'firstaccess',
            'lastlogin',
            'lastaccess',
            'lastlogin',
            'currentlogin',
            'lastip',
            'secret',
            'picture',
            'url',
            'mailformat',
            'maildigest',
            'maildisplay',
            'autosubscribe',
            'trackforums',
            'timecreated',
            'timemodified',
            'trustbitmask',
            'imagealt',
            'totarasync'
        ];
        $record = $DB->get_record('user', ['username' => 'samh'], join(',', $requiredfields), MUST_EXIST);
        $user = new unit_test_user_data_object((array)$record);

        foreach ($user->required_fields as $field) {
            $this->assertSame($record->$field, $user->$field, 'Required field ' . $field . ' does not match the database record value');
        }

        foreach ($user->optional_fields as $field => $default) {
            $this->assertFalse(property_exists($record, $field), 'Optional property '.$field.' is present on the database record');
            $this->assertTrue(property_exists($user, $field), 'Optional property '.$field.' is not present on the data object record');
            $this->assertSame($default, $user->$field, 'Optional field ' . $field . ' is not using the default');
        }
    }

    /**
     * Test loading all optional fields.
     */
    public function test_load_optional_fields() {
        global $DB;
        $record = $DB->get_record('user', ['username' => 'samh'], '*', MUST_EXIST);
        $user = new unit_test_user_data_object(['id' => $record->id]);

        foreach ($user->optional_fields as $field => $default) {
            $this->assertTrue(property_exists($user, $field), 'Optional property '.$field.' is not present on the data object record');
            $this->assertSame($default, $user->$field);
        }

        // This super not optimal.
        $user->load_optional_fields();

        foreach ($user->optional_fields as $field => $default) {
            $this->assertTrue(property_exists($record, $field), 'Optional property '.$field.' is not present on the database record');
            $this->assertTrue(property_exists($user, $field), 'Optional property '.$field.' is not present on the data object record');
            $this->assertSame($record->$field, $user->$field, 'Optional field ' . $field . ' does not match the database record value');
        }
    }

}