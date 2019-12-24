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
 * @package auth_approved
 */
defined('MOODLE_INTERNAL') || die();

use auth_approved\request;


/**
 * Behat wrapper to generate data for testing the auth_approved plugin.
 *
 * Note the create_ prefix for public methods; this is required because this is
 * invoked via the behat_totara_data_generators class; that class expects all
 * generators to contain "create_XYZ" functions.
 */
final class auth_approved_generator extends \component_generator_base {
    /**
     * Recognized time durations for setting up signup times.
     */
    private static $intervals = [
        'day' => 'P%sD',
        'days' => 'P%sD',
        'month' => 'P%sM',
        'months' => 'P%sM',
        'year' => 'P%sY',
        'years' => 'P%sY'
    ];

    /**
     * Recognized signup statuses.
     */
    private static $status = [
        'pending' => request::STATUS_PENDING,
        'approved' => request::STATUS_APPROVED,
        'rejected' => request::STATUS_REJECTED
    ];


    /**
     * Default constructor.
     */
    public function __construct(\testing_data_generator $generator) {
        parent::__construct($generator);
    }

    /**
     * Creates a test signup application.
     *
     * These are the fields in the Behat feature table this function recognizes:
     * - 'username': [MANDATORY] user name.
     *
     * - 'password': [MANDATORY] user password.
     *
     * - 'email': [MANDATORY] signup email.
     *
     * - 'first name': [MANDATORY] signup first name.
     *
     * - 'surname': [MANDATORY] signup surname.
     *
     * - 'status': [MANDATORY] signup status; 'pending', 'approved', 'rejected'.
     *
     * - 'signup time': [OPTIONAL] signup time. The format for this column is
     *   described below. Default value is teh current time.
     *
     * - 'token': [OPTIONAL] confirmation token. Default is a random string.
     *
     * - 'confirmed': [OPTIONAL] whether the signup has "confirmed" his email;
     *   accepts anything PHP takes as boolean. Default is false.
     *
     * - 'city': [OPTIONAL] signup city. Default is ''.
     *      When creating a request through the API this is required.
     *      However for convenience we set a value if you don't provide it.
     *      If not specified Wellington is used.
     *
     * - 'country': [OPTIONAL] signup *ISO country code* - NOT THE VALUE SHOWN
     *      IN THE SIGNUP FORM. Default is ''.
     * When creating a request through the API this is required.
     *      However for convenience we set a value if you don't provide it.
     *      If not specified NZ is used.
     *
     * - 'lang': [OPTIONAL] the language this user was using, default to 'en'.
     *
     * - 'mgr text': [OPTIONAL] free form manager. Defaults to ''.
     *
     * - 'manager jaidnum': [OPTIONAL] manager job assignment idnumber.
     *
     * - 'pos text': [OPTIONAL] free form position.
     *
     * - 'pos idnum': [OPTIONAL] position idnumber.
     *
     * - 'org text': [OPTIONAL] free form organization.
     *
     * - 'org idnum': [OPTIONAL] position idnumber.
     *
     *  - 'approveduserorid': The ID of the user to map this request to when creating an approved
     *       request. If one is not provided a new user will be created.
     *
     * The signup time is in one of these formats:
     * - a string "[+|-] integer" indicating the no of seconds since the Epoch.
     *   *A signup time in this format is saved as is*.
     * - a string "[+|-] integer <days|months|years>" for a time relative to the
     *   current date. With this format, the time component of the signup time
     *   is always set to 00:00:00 ie midnight.
     *
     * @param array<string=>string> $row single data row from the Behat table.
     * @return int
     */
    public function create_signup(array $row) {
        global $DB;
        return $DB->insert_record('auth_approved_request', $this->as_signup($row));
    }

    /**
     * Populates a sign up from the set of values passed in.
     *
     * @throws coding_exception if required variables are missing.
     * @param array $raw mapping of behat table fields to values.
     * @return \stdClass the signup object.
     */
    private function as_signup(array $raw) {
        $recognized_fields = [
            // Raw field        Field                       Required    Default                     Processor callback
            ['username',        'username',                 true,       null,                       null],
            ['password',        'password',                 true,       null,                       '\hash_internal_user_password'],
            ['email',           'email',                    true,       null,                       null],
            ['first name',      'firstname',                true,       null,                       null],
            ['surname',         'lastname',                 true,       null,                       null],
            ['token',           'confirmtoken',             false,      \random_string(32),  null],
            ['confirmed',       'confirmed',                false,      0,                          [$this, 'as_boolean']],
            ['city',            'city',                     false,      'Wellington',               null],
            ['country',         'country',                  false,      'NZ',                       [$this, 'as_iso_country']],
            ['lang',            'lang',                     false,      'en',                       [$this, 'as_lang']],
            ['status',          'status',                   true,       null,                       [$this, 'as_status']],
            ['signup time',     'timecreated',              false,      \time(),                    [$this, 'as_timestamp']],
            ['manager jaidnum', 'managerjaid',              false,      0,                          [$this, 'as_mgrjaid']],
            ['mgr text',        'managerfreetext',          false,      null,                       null],
            ['pos idnum',       'positionid',               false,      0,                          [$this, 'as_posid']],
            ['pos text',        'positionfreetext',         false,      null,                       null],
            ['org idnum',       'organisationid',           false,      0,                          [$this, 'as_orgid']],
            ['org text',        'organisationfreetext',     false,      null,                       null]
        ];

        $signup = array();
        foreach ($recognized_fields as $tuple) {

            list($rawfield, $field, $required, $default, $callable) = $tuple;

            if (array_key_exists($rawfield, $raw)) {
                $value = $raw[$rawfield];
            } else if ($required) {
                throw new coding_exception("Field '$rawfield' must be provided when creating a request.");
            } else {
                $value = $default;
            }

            if (!is_null($callable) && !is_null($value)) {
                $value = call_user_func($callable, $value);
            }

            $signup[$field] = $value;
        }

        $signup['profilefields'] = json_encode([]);
        $signup['userid'] = null;
        $signup['timemodified'] = $signup['timecreated'];

        if ($signup['status'] == request::STATUS_APPROVED) {

            if (isset($raw['approveduserorid'])) {
                if (is_object($raw['approveduserorid'])) {
                    $userid = $raw['approveduserorid']->id;
                } else {
                    $userid = $raw['approveduserorid'];
                }
            } else {
                $user = $this->datagenerator->create_user();
                $userid = $user->id;
            }
            $signup['userid'] = $userid;
            $signup['resolvedby'] = \get_admin()->id;
            $signup['timeresolved'] = $signup['timecreated'];

        } else if ($signup['status'] == request::STATUS_REJECTED) {

            $signup['resolvedby'] = \get_admin()->id;
            $signup['timeresolved'] = $signup['timecreated'];

        }

        return (object)$signup;
    }


    /**
     * Returns a boolean value from the specified value.
     *
     * @param string $value value from which to derive a boolean value.
     * @return bool the boolean value.
     */
    private function as_boolean($value) {
        return filter_var($value,  FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Returns the ISO country code.
     *
     * @throws coding_exception if the given country is not valid
     * @param string $value the raw country name.
     * @return string the ISO country code.
     */
    private function as_iso_country($value) {
        if (!preg_match('#^[A-Z]{2,3}$#', $value)) {
            throw new coding_exception("The given country ISO code is not valid: '$value'");
        }

        return $value;
    }

    /**
     * Validates the given language and returns it.
     *
     * @throws coding_exception if the given language is not valid
     * @param string $value
     * @return string
     */
    private function as_lang($value) {
        if (!preg_match('#^[a-z]{2}(_[a-z]{2,5})?$#', $value)) {
            throw new coding_exception("The given language is no a valid language: '$value'");
        }
        return $value;
    }

    /**
     * Returns an object ID given the input idnumber.
     *
     * @throws coding_exception if the given idnumber is not valid
     * @param string $value the idnumber.
     * @return int the object id.
     */
    private function from_idnumber($table, $value) {
        global $DB;
        if (!$value) {
            return 0;
        }

        $id = $DB->get_field($table, 'id', ['idnumber' => $value]);

        if (empty($id)) {
            throw new coding_exception(
                "table '$table' does not have idnumber '$value'"
            );
        }

        return (int)$id;
    }

    /**
     * Returns the manager job assignment ID given the input idnumber.
     *
     * @param string $value the job assignment idnumber.
     * @return int the job assignment id.
     */
    private function as_mgrjaid($value) {
        return $this->from_idnumber('job_assignment', $value);
    }

    /**
     * Returns the organization ID given the input idnumber.
     *
     * @param string $value the organization idnumber.
     * @return int the organization id.
     */
    private function as_orgid($value) {
        return $this->from_idnumber('org', $value);
    }

    /**
     * Returns the position ID given the input idnumber.
     *
     * @param string $value the position idnumber.
     * @return int the position id.
     */
    private function as_posid($value) {
        return $this->from_idnumber('pos', $value);
    }

    /**
     * Returns the signup status as an integer.
     *
     * @throws coding_exception if the given status is not valid
     * @param string $value the raw status.
     * @return int the "correct" status.
     */
    private function as_status($value) {
        if (!array_key_exists($value, self::$status)) {
            throw new coding_exception("unknown status: '$value'");
        }
        return self::$status[$value];
    }

    /**
     * Sets up a timestamp relative to the current time.
     *
     * @throws coding_exception if the given time is not valid
     * @param string $time_desc a string indicating the duration to add or minus
     *        from the current time.
     * @return int the new time in number of seconds since the Epoch.
     */
    private function as_timestamp($time_desc) {
        $parts = preg_split ("/\s+/", $time_desc, 2);
        $number = $parts[0];
        if (!is_numeric($number)) {
            throw new coding_exception("time is not numeric: '$number'");
        }

        $type = count($parts) > 1 ? strtolower($parts[1]) : null;
        if (empty($type)) {
            return (int)$number;
        }
        else if (!array_key_exists($type, self::$intervals)) {
            $allowed = implode(',', array_keys(self::$intervals));
            throw new coding_exception("allowed time durations are: $allowed");
        }

        $interval = (int)$number;
        $spec = sprintf(self::$intervals[$type], abs($interval));
        $diff =  new DateInterval($spec);

        // Note the way $now is created, this forces DateTime to be in terms of
        // pure UTC; otherwise a default timezone will be used in computations.
        $now = new DateTimeImmutable('@' . time());
        $newDate = $interval > 0 ? $now->add($diff) : $now->sub($diff);
        return $newDate->setTime(0, 0, 0)->getTimestamp();
    }
}
