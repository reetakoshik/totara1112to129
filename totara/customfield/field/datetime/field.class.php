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
 * @author Eugene Venter <eugene@catalyst.net.nz>
 * @package totara
 * @subpackage customfield
 */


class customfield_datetime extends customfield_base {

    /**
     * Handles editing datetime fields
     *
     * @param object moodleform instance
     */
    function edit_field_add(&$mform) {
        // Check if the field is required
        if ($this->field->required) {
            $optional = false;
        } else {
            $optional = true;
        }

        $attributes = array(
            'startyear' => $this->field->param1,
            'stopyear'  => $this->field->param2,
            'timezone'  => 99,
            'optional'  => $optional
        );

        // Check if they wanted to include time as well
        if (!empty($this->field->param3)) {
            $mform->addElement('date_time_selector', $this->inputname, format_string($this->field->fullname), $attributes);
        } else {
            $mform->addElement('date_selector', $this->inputname, format_string($this->field->fullname), $attributes);
        }
    }

    /**
     * Create the output for the date/time custom field.
     *
     * @param string $data The field data to be displayed.
     * @param array $extradata Extra options to manage the output.
     * @return string The modified date.
     */
    static function display_item_data($data, $extradata=array()) {
        // A timestamp will be expected to output the data correctly,
        // but if we can't get one from the data just use it as given.
        $new_data = ctype_digit($data) ? intval($data) : strtotime($data);
        $data = $new_data ? $new_data : $data;

        // Export unix time
        if (!empty($extradata['isexport'])) {
            return $data;
        }

        // Only display the time if its been set.
        if (date('G:i', $data) !== '0:00') { // 12:00 am - assume no time was saved
            $format = get_string('strftimedaydatetime', 'langconfig');
        } else {
            $format = get_string('strftimedate', 'langconfig');
        }

        // Check if a date has been specified
        if (empty($data)) {
            return get_string('notset', 'totara_customfield');
        } else {
            return userdate($data, $format);
        }
    }

    /**
     * Changes the customfield value from a file data to the key and value.
     *
     * @param  object $syncitem The original syncitem to be processed.
     * @return object The syncitem with the customfield data processed.
     */
    public function sync_filedata_preprocess($syncitem) {
        global $CFG;

        $value = $syncitem->{$this->field->shortname};
        unset($syncitem->{$this->field->shortname});

        // Parse using $CFG->csvdateformat if set, or default if not set.
        $csvdateformat = (isset($CFG->csvdateformat)) ? $CFG->csvdateformat : get_string('csvdateformatdefault', 'totara_core');
        // If date can't be parsed, assume it is a unix timestamp and leave unchanged.
        $parsed_date = totara_date_parse_from_format($csvdateformat, $value, true);
        if ($parsed_date) {
            $value = $parsed_date;
        }
        $syncitem->{$this->inputname} = $value;

        return $syncitem;
    }


    /**
     * Validate the datetime form field.
     *
     * @param object $itemnew The custom field item to process.
     * @param string $prefix Field name prefix for the connexted table.
     * @param string $tableprefix Table name prefix.
     * @return array contains error message otherwise NULL
     */
    public function edit_validate_field ($itemnew, $prefix, $tableprefix) {
        global $DB;

        $errors = array ();
        $data = isset ($itemnew->{$this->inputname}) ? $itemnew->{$this->inputname} : 0;

        if ($data) {
            // Get timestamps for the permitted year range of the date/time field.
            $lower_limit = strtotime ($this->field->param1 . '-01-01');
            $upper_limit = strtotime ((intval($this->field->param2) + 1) . '-01-01') - 1;

            // Try and get a valid timestamp from the data we have.
            $timestamp = ctype_digit ($data) ? intval($data) : strtotime ($data);

            // Check the data and produce an error if not valid.
            if (!$timestamp) {
                $errors["{$this->inputname}"] = get_string ('error:invaliddateformat', 'totara_customfield',
                    array ('data' => $data, 'field' => $this->field->shortname));
            } else if ($timestamp < $lower_limit) {
                $errors["{$this->inputname}"] = get_string ('error:invaliddatetooearly', 'totara_customfield',
                    array ('data' => $data, 'field' => $this->field->shortname, 'year' => $this->field->param1));
            } else if ($timestamp > $upper_limit) {
                $errors["{$this->inputname}"] = get_string ('error:invaliddatetoolate', 'totara_customfield',
                    array ('data' => $data, 'field' => $this->field->shortname, 'year' => $this->field->param2));
            } else if ($this->is_unique()) {
                // Check that the timestamp is not already in use.
                $where = "fieldid = :fieldid AND " . $DB->sql_compare_text('data', 1024) . ' = :timestamp AND ' . $prefix . "id != :id";
                $params = array ('fieldid' => $this->field->id, 'timestamp' => $timestamp, 'id' => $itemnew->id);
                $result = $DB->record_exists_select ($tableprefix . '_info_data', $where, $params);

                if ($result) {
                    $errors["{$this->inputname}"] = get_string ('error:invaliddatenotunqiue', 'totara_customfield',
                        array ('data' => $data, 'field' => $this->field->shortname));
                }
            }
        }

        return $errors;
    }

    /**
     * Manipulate the datetime field data before saving.
     *
     * @param object $itemnew The custom field item to process.
     * @param string $prefix Field name prefix for the connexted table.
     * @param string $tableprefix Table name prefix.
     * @return object Updated $itemnew.
     */
    public function edit_save_data ($itemnew, $prefix, $tableprefix) {

        // Try and convert the data into a timestamp.
        if (isset ($itemnew->{$this->inputname})) {
            $data = $itemnew->{$this->inputname};
            $itemnew->{$this->inputname} = ctype_digit($data) ? intval($data) : strtotime($data);
        }

        parent::edit_save_data($itemnew, $prefix, $tableprefix);

        // Return only needed for unit testing.
        return $itemnew;
    }

    /**
     * Does some extra pre-processing for totara sync uploads.
     *
     * @param  object $itemnew The item being saved
     * @return object          The same item after processing
     */
    public function sync_data_preprocess($syncitem) {
        $fieldname = $this->inputname;

        if (!isset($syncitem->$fieldname)) {
            return $syncitem;
        }

        $syncitem->{$fieldname} = clean_param($syncitem->{$fieldname}, PARAM_TEXT);

        return $syncitem;
    }
}
