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

// We only allow this within unit tests.
(defined('MOODLE_INTERNAL') && PHPUNIT_TEST) || die();

global $CFG;
require_once($CFG->dirroot . '/completion/data_object.php');

/**
 * Unit test user data object.
 *
 * @author     Sam Hemelryk <sam.hemelryk@totaralearning.com>
 * @copyright  2016 Totara Learning Solutions
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package    core_completion
 */
class unit_test_user_data_object extends data_object {

    /**
     * DB table
     * @var string
     */
    public $table = 'user';

    /**#@+
     * Public properties for the user data object class.
     *
     * This is a phpdoc template btw :)
     *
     * @access public
     * @type string|int|null
     */
    public $id;
    public $username;
    public $deleted;
    public $suspended;
    public $idnumber;
    public $firstname;
    public $lastname;
    public $email;
    public $emailstop;
    public $lang;
    public $theme;
    public $timezone;
    public $lastnamephonetic;
    public $firstnamephonetic;
    public $middlename;
    public $alternatename;
    public $auth;
    public $confirmed;
    public $policyagreed;
    public $mnethostid;
    public $password;
    public $icq;
    public $skype;
    public $yahoo;
    public $aim;
    public $msn;
    public $phone1;
    public $phone2;
    public $institute;
    public $department;
    public $address;
    public $city;
    public $country;
    public $calendartype;
    public $firstaccess;
    public $lastlogin;
    public $currentlogin;
    public $lastip;
    public $secret;
    public $picture;
    public $url;
    public $mailformat;
    public $maildgest;
    public $maildisplay;
    public $autosubscribe;
    public $trackforums;
    public $timecreated;
    public $timemodified;
    public $trustbitmask;
    public $imagealt;
    public $totarasync;
    /**@#-*/

    /**
     * Array of required table fields, must start with 'id'.
     * @var array   $required_fields
     */
    public $required_fields = [
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

    /**
     * Optional values and their defaults.
     *
     * These must match the default in the database.
     *
     * @var array
     */
    public $optional_fields = [
        'description' => null,
        'descriptionformat' => FORMAT_HTML,
    ];

    /**
     * Array of unique fields, used in where clauses and constructor
     * @var array
     */
    public $unique_fields = array();

    /**
     * Finds and returns a data_object instance based on params.
     *
     * @param array $params associative arrays varname => value
     * @return object data_object instance or false if none found.
     */
    public static function fetch($params) {
        return self::fetch_helper('user', __CLASS__, $params);
    }

    /**
     * Finds and returns all data_object instances based on params.
     *
     * @param array $params associative arrays varname => value
     * @throws coding_exception This function MUST be overridden
     * @return array array of data_object instances or false if none found.
     */
    public static function fetch_all($params) {
        return self::fetch_all_helper('user', __CLASS__, $params);
    }

}