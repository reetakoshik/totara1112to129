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
 * @package totara_customfield
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/totara/customfield/fieldlib.php');
require_once($CFG->dirroot . '/totara/customfield/field/location/define.class.php');

class customfield_location extends customfield_base {

    public function edit_field_add(&$mform) {
        $mform->_customlocationfieldname = $this->inputname;

        $args = new stdClass();
        $args->fordisplay = false;
        $args->fieldprefix = $mform->_customlocationfieldname;

        customfield_define_location::define_add_js($args);
        customfield_define_location::add_location_field_form_elements($mform, $this->field->fullname, false);
    }

    public function edit_field_set_required(&$mform) {
        if ($this->is_required()) {
            $mform->addRule($this->inputname . 'address', get_string('err_required', 'form'), 'required', null, 'client');
        }
    }

    public function edit_field_set_default(&$mform) {
        parent::edit_field_set_default($mform);

        customfield_define_location::set_location_field_form_element_defaults($mform, $this->field->param2, $this->data);
    }

    function edit_save_data($itemnew, $prefix, $tableprefix) {
        customfield_define_location::prepare_form_location_data_for_db($itemnew, $this->inputname);
        parent::edit_save_data($itemnew, $prefix, $tableprefix);
    }

    /**
     * Changes the customfield value from a string to the key that matches
     * the string in the array of options.
     *
     * @param  object $syncitem     The original syncitem to be processed.
     * @return object               The syncitem with the customfield data processed.
     *
     */
    public function sync_data_preprocess($syncitem) {
        $fieldname = $this->inputname;

        if (!isset($syncitem->$fieldname)) {
            return $syncitem;
        }

        $address = clean_param($syncitem->$fieldname, PARAM_TEXT);
        // Make data in format required by @see customfield_location::prepare_form_location_data_for_db()
        $syncitem->{$fieldname . 'address'} = $address;
        $syncitem->{$fieldname . 'display'} = GMAP_DISPLAY_ADDRESS_ONLY;
        return $syncitem;
    }

    public function edit_load_item_data(&$item) {
        $item->{$this->inputname} = customfield_define_location::prepare_db_location_data_for_form($this->data);
    }

    /**
     * Displays the custom field.
     * This defaults to just the address as it is typically placed inside a table cell (see F2F upcoming sessions).
     * Pass in 'extended' => true as a value in the $extradata array to get the full map layout.
     *
     * @param $data
     * @param array $extradata
     * @return array|string
     */
    public static function display_item_data($data, $extradata = array()) {
        return customfield_define_location::render($data, $extradata);
    }
}
