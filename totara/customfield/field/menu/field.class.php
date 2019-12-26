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
 * @author Simon Coggins <simon.coggins@totaralms.com>
 * @package totara
 * @subpackage totara_customfield
 */

class customfield_menu extends customfield_base {

    /**
     * Options as they defined in database
     * @var string[] $options
     */
    public $options;

    /**
     * Options to be used in form select element (formatted)
     * @var string[] $formoptions
     */
    public $formoptions;

    /** @var int $datakey */
    public $datakey;

    /**
     * Get the choose option for the menu of choices.
     *
     * @return string
     */
    public function get_choose_option() {
        return get_string('choosedots');
    }

    /**
     * Constructor method.
     * Pulls out the options for the menu from the database and sets the
     * the corresponding key for the data if it exists
     */
    function __construct($fieldid=0, $itemid=0, $prefix, $tableprefix, $addsuffix = false, $suffix = '') {
        // First call parent constructor.
        parent::__construct($fieldid, $itemid, $prefix, $tableprefix, $addsuffix, $suffix);

        // Param 1 for menu type is the options.
        if (isset($this->field->param1)) {
            $this->options = explode("\n", $this->field->param1);
        } else {
            $this->options = array();
        }

        // Include the choose option at the beginning.
        $this->formoptions[''] = $this->get_choose_option();
        foreach($this->options as $key => $option) {
            $this->formoptions[$key] = format_string($option, true, ['context' => context_system::instance()]);// Multilang formatting.
            $this->options[$key] = $option;
        }

        // Set the data key.
        if (empty($this->data)) {
            // Set default value to the choosedots option.
            $this->datakey = '';
        } else if ($this->data !== NULL) {
            $this->datakey = (int)array_search($this->data, $this->options);
        }
    }

    /**
     * Create the code snippet for this field instance
     * Overwrites the base class method
     * @param   object   moodleform instance
     */
    public function edit_field_add(&$mform) {

        if ($this->itemid != 0 && $this->is_locked()) {
            // Display the field using a hyphen if there's no content.
            $mform->addElement(
                'static',
                'freezedisplay',
                format_string($this->field->fullname),
                html_writer::div(
                    !empty($this->data) ? format_text($this->data) : get_string('readonlyemptyfield', 'totara_customfield'),
                    null,
                    ['id' => 'id_customfield_' . $this->field->shortname]
                )
            );
        } else {
            $mform->addElement('select', $this->inputname, format_string($this->field->fullname), $this->formoptions);
        }
    }

    /**
     * Set the default value for this field instance
     * Overwrites the base class method
     */
    function edit_field_set_default(&$mform) {
        if (FALSE !==array_search($this->field->defaultdata, $this->options)){
            $defaultkey = (int)array_search($this->field->defaultdata, $this->options);
        } else {
            $defaultkey = '';
        }
        $mform->setDefault($this->inputname, $defaultkey);
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
        // Get the sync value out of the item.
        $fieldname = $this->inputname;

        if (!isset($syncitem->$fieldname)) {
            return $syncitem;
        }

        $value = $syncitem->$fieldname;

        // Now get the corresponding option for that value.
        foreach ($this->options as $key => $option) {
            if ($option == $value) {
                $selected = $key;
            }
        }

        // If no matching option is found set it to empty.
        if (!isset($selected)) {
            $selected = NULL;
        }

        $syncitem->$fieldname = $selected;
        return $syncitem;
    }

    /**
     * The data from the form returns the key. This should be converted to the
     * respective option string to be saved in database.
     * Don't save data if the option chosen is the default "choose" option as it does not
     * represent a real option.
     * Overwrites base class accessor method
     * @param   integer $key the key returned from the select input in the form
     * @return mixed|null
     */
    function edit_save_data_preprocess($key) {
        return (isset($this->options[$key]) && $this->options[$key] != $this->get_choose_option()) ? $this->options[$key] : NULL;
    }

    /**
     * When passing the type object to the form class for the edit custom page
     * we should load the key for the saved data
     * Overwrites the base class method
     * @param   object   item object
     */
    function edit_load_item_data(&$item) {
        $item->{$this->inputname} = $this->datakey;
    }

    /**
     * HardFreeze the field if locked.
     * @param   object   instance of the moodleform class
     */
    function edit_field_set_locked(&$mform) {
        if (!$mform->elementExists($this->inputname)) {
            return;
        }
        if ($this->is_locked()) {
            $mform->hardFreeze($this->inputname);
            $mform->setConstant($this->inputname, $this->datakey);
        }
    }

    /**
     * Changes the customfield value from a file data to the key and value.
     *
     * @param  object $syncitem The original syncitem to be processed.
     * @return object The syncitem with the customfield data processed.
     */
    public function sync_filedata_preprocess($syncitem) {

        $value = $syncitem->{$this->field->shortname};
        unset($syncitem->{$this->field->shortname});

        $value = core_text::strtolower($value);
        $options = explode("\n", core_text::strtolower($this->field->param1));
        foreach ($options as $key => $option) {
            if ($option == $value) {
                $value = (string)$key;
                break;
            }
        }
        $syncitem->{$this->inputname} = $value;

        return $syncitem;
    }

    /**
     * Display the data for the menu custom field.
     *
     * @param $data mixed The data to display.
     * @param $extradata array Data that identifies the source of the data.
     * @return string The formatted text for display.
     */
    public static function display_item_data($data, $extradata=array()) {

        // Export - return raw value
        if (!empty($extradata['isexport'])) {
            return $data;
        }

        if (empty($data)) {
            return get_string('readonlyemptyfield', 'totara_customfield');
        } else {
            return format_string($data);
        }
    }
}
