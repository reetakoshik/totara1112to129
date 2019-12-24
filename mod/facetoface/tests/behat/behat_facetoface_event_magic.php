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
 * @author Murali Nair <murali.nair@totaralms.com>
 * @package mod_facetoface
 */

// Unfortunately, this file may be loaded by behat before including config.php;
// hence the unwieldy relative path instead of using $CFG->dirroot.
require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');


/**
 * Definitions for setting seminar event details via direct manipulation of the
 * values in the database.
 *
 * @package mod_facetoface
 */
class behat_facetoface_event_magic extends \behat_base {
    /**
     * Alters seminar event timestamps. One good use for this step is to change
     * timestamps so that you do not have to wait in tests.
     *
     * Currently, the only way to identify a seminar event is from its system
     * generated ID. Other field values - even when combined - are not enough to
     * uniquely identify it. Therefore, to change dates for one specific event,
     * ensure the target event field holds a unique value even across timezones.
     * Otherwise all events with the same timestamp get changed.
     *
     * @Given /^I use magic to adjust the seminar event "([^"]*)" from "([^"]*)" "([^"]*)" to "([^"]*)"$/
     *
     * @param string $field indicates the event timestamp field to update. One
     *        of the facetoface_event_timestamp_magic::$fields keys.
     * @param string $original event's original timestamp value, formatted as in
     *        facetoface_event_timestamp_magic::$format.
     * @param string $zone event timezone eg "Pacific/Auckland". This is not the
     *        timezone for display but rather timezone with which the original
     *        field was created.
     * @param string $updated event's new timestamp value, formatted as in
     *        facetoface_event_timestamp_magic::$format.
     *
     * @throws \InvalidArgumentException if there were invalid parameter values.
     */
    public function i_use_magic_to_adjust_the_seminar_event_timestamp(
        $field, $original, $zone, $updated
    ) {
        \behat_hooks::set_step_readonly(true); // No change in browser.
        facetoface_event_timestamp_magic::from(
            $field, $original, $zone, $updated
        )->run();
    }
}


/**
 * Manipulates event timestamps.
 */
class facetoface_event_timestamp_magic {
    /**
     * @var array "recognized" event timestamp fields.
     */
    private static $fields = [
        'start' => 'timestart',
        'end' => 'timefinish'
    ];

    /**
     * @var string timestamp parsing format.
     *
     * Note: the format must have a time component specified; only then will the
     *       PHP DateTime classes zero out the seconds in the timestamp. This is
     *       crucial since event times in the database are similarly zeroed out
     *       and the code retrieves events based on *exact* comparisons of the
     *       timestamp values.
     */
    private static $format ='d/m/Y H:i';

    /**
     * @var string indicates the event timestamp field to update.
     */
    private $field;

    /**
     * @var int original event timestamp in milliseconds since the Epoch.
     */
    private $original;

    /**
     * @var int adjusted event timestamp in milliseconds since the Epoch.
     */
    private $adjusted;


    /**
     * Creates an instance of `facetoface_event_timestamp_magic` from a set of
     * raw values.
     *
     * @param string $field indicates the event timestamp field to update.
     * @param string $original event's original timestamp value.
     * @param string $zone targetted event timezone.
     * @param string $adjusted event's new timestamp value.
     *
     * @return facetoface_event_timestamp_magic the instance.
     *
     * @throws \InvalidArgumentException if any of the input parameters are not
     *         valid.
     */
    public static function from($field, $original, $zone, $adjusted) {
        return self::parseField(
            $field
        )->withTimestamps(
            self::parseTimestamp($original, $zone),
            self::parseTimestamp($adjusted, $zone)
        );
    }

    /**
     * Determines the event field to be updated.
     *
     * @param string $value the raw field name.
     *
     * @return facetoface_event_timestamp_magic a new instance initialized with
     *         the field to be adjusted.
     *
     * @throws \InvalidArgumentException if the input value was invalid.
     */
    private static function parseField($value) {
        if (empty($value)) {
            throw new \InvalidArgumentException('empty field value');
        }

        $field = strtolower($value);
        if (!array_key_exists($field, self::$fields)) {
            $allowed = implode(',', array_keys(self::$fields));
            throw new \InvalidArgumentException(
                "allowed 'field' values are: $allowed"
            );
        }

        return (
            new facetoface_event_timestamp_magic()
        )->withField(
            self::$fields[$field]
        );
    }

    /**
     * Creates a DateTime instance given the raw timestamp and timezone.
     *
     * @param string $timestamp raw timestamp.
     * @param string $zone timezone.
     *
     * @return \DateTime the DateTime instance.
     *
     * @throws \InvalidArgumentException if any of the inputs are not valid.
     */
    private static function parseTimestamp($timestamp, $zone) {
        // Believe it or not, DateTime::createFromFormat will parse nonsensical
        // dates like '41/05/2006' (which supposedly is 10th Jun 2006). However,
        // it *knows* the original string was invalid since it fills up an array
        // that you can access via DateTime::getLastErrors()!
        $tz = self::parseTimezone($zone);
        $ts = DateTime::createFromFormat(self::$format, $timestamp, $tz);

        $errors = DateTime::getLastErrors();
        if (!empty($errors['warnings'])
            || !empty($errors['errors'])
        ) {
            throw new \InvalidArgumentException("invalid date: $timestamp");
        }

        return $ts;
    }

    /**
     * Creates a timezone instance given a specification string.
     *
     * @param string $zone incoming timezone specification.
     *
     * @return a \DateTimeZone instance.
     *
     * @throws \InvalidArgumentException if the specification was invalid.
     */
    private static function parseTimezone($zone) {
        try {
            return new DateTimeZone($zone);
        } catch(Exception $e) {
            throw new \InvalidArgumentException("invalid timezone: $zone");
        }
    }

    /**
     * Default constructor.
     */
    private function __construct() {
        $this->field = null;
        $this->original = 0;
        $this->adjusted = 0;
    }

    /**
     * Returns a string version of this object.
     *
     * @return string the stringified object.
     */
    public function __toString() {
        $values = '';
        foreach ($this->__debugInfo() as $field => $value) {
            $str = "$field=$value";
            $values = empty($values) ? $str : "$values, $str";
        }

        return get_class() . "[$values]";
    }

    /**
     * Returns a dump of the object for var_dump().
     *
     * @return array a list of object properties to show.
     */
    public function __debugInfo() {
        return [
            'field'          => $this->field,
            'from timestamp' => $this->original,
            'to timestamp'   => $this->adjusted
        ];
    }

    /**
     * Sets the targetted event field.
     *
     * @param string $value field.
     *
     * @return facetoface_event_timestamp_magic the updated instance.
     */
    public function withField($value) {
        $this->field = $value;
        return $this;
    }

    /**
     * Sets the original and adjusted timestamps.
     *
     * @param \DateTime $original original timestamp.
     * @param \DateTime $adjusted adjusted timestamp.
     *
     * @return facetoface_event_timestamp_magic the updated instance.
     */
    public function withTimestamps(\DateTime $original, \DateTime $adjusted) {
        $this->original = $original->getTimestamp();
        $this->adjusted = $adjusted->getTimestamp();
        return $this;
    }

    /**
     * Retrieves the event(s) to be updated and modifies them.
     */
    public function run() {
        global $DB;

        $table = 'facetoface_sessions_dates';
        $field = $this->field;

        array_map(
            function (\stdClass $event) use ($DB, $table, $field) {
                $event->$field = $this->adjusted;
                $DB->update_record($table, $event);
                return null;
            },

            $DB->get_records(
                $table, [$field => $this->original], '', "id,$field"
            )
        );

    }
}
