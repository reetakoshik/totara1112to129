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

//this file is also included by hierarchy/lib so it seems a good place to put these
require_once($CFG->libdir . '/formslib.php');

/**
 * Base class for the custom fields.
 */
class customfield_base {

    /// These 2 variables are really what we're interested in.
    /// Everything else can be extracted from them
    var $fieldid; //{tableprefix}_info_field field id
    var $itemid; //hierarchy item id
    var $dataid; //id field of the data record
    var $prefix;
    var $tableprefix;
    var $field;
    var $inputname;
    var $data;
    var $context;
    var $addsuffix; // If the custom field requires an item id suffix to make it unique.
    private $suffix; // If the custom field requires more than an item id to make it unique.

    /**
     * Constructor method.
     * @param integer $fieldid If from the _info_field table.
     * @param object &item The item using the using the custom data.
     * @param string $prefix The field name prefix.
     * @param string $tableprefix The database table name prefix.
     * @param boolean $addsuffix If the custom field should have a suffix added.
     * @param string $suffix the suffix to be added after the item id when loading data
     */
    function __construct($fieldid=0, &$item, $prefix, $tableprefix, $addsuffix = false, $suffix = '') {
        $this->set_fieldid($fieldid);
        $this->set_itemid($item->id);
        $this->set_addsuffix($addsuffix);
        $this->suffix = !empty($suffix) ? '_' . $suffix : '';
        $this->load_data($item, $prefix, $tableprefix);
        $this->prefix = $prefix;
        $this->tableprefix = $tableprefix;
    }

    /**
     * Display the data for this field
     */
    function display_data() {
        // call the static method belonging to this object's class
        // or the one below if not re-defined by child class
        return $this->display_item_data($this->data, array('prefix' => $this->prefix, 'itemid' => $this->dataid));
    }


/***** The following methods must be overwritten by child classes *****/

    /**
     * Abstract method: Adds the custom field to the moodle form class
     * @param  form  instance of the moodleform class
     */
    function edit_field_add(&$mform) {
        print_error('error:abstractmethod', 'totara_customfield');
    }


/***** The following methods may be overwritten by child classes *****/

    static function display_item_data($data, $extradata=array()) {
        $options = new stdClass();
        $options->para = false;
        return format_text($data, FORMAT_MOODLE, $options);
    }
    /**
     * Print out the form field in the edit page
     * @param   object   instance of the moodleform class
     * $return  boolean
     */
    function edit_field(&$mform) {

        if ($this->field->hidden == false) {
            $this->edit_field_add($mform);
            $this->edit_field_set_default($mform);
            $this->edit_field_set_required($mform);
            return true;
        }
        return false;
    }

    /**
     * Tweaks the edit form
     * @param   object   instance of the moodleform class
     * $return  boolean
     */
    function edit_after_data(&$mform) {

        if ($this->field->hidden == false) {
            $this->edit_field_set_locked($mform);
            return true;
        }
        return false;
    }

    /**
     * Saves the data coming from form
     * @param   mixed   data coming from the form
     * @param   string  name of the prefix (ie, competency)
     * @return  mixed   returns data id if success of db insert/update, false on fail, 0 if not permitted
     */
    function edit_save_data($itemnew, $prefix, $tableprefix) {
        global $DB;
        if (!isset($itemnew->{$this->inputname})) {
            // field not present in form, probably locked and invisible - skip it
            return;
        }
        $rawdata = $itemnew->{$this->inputname};
        $itemnew->{$this->inputname} = $this->edit_save_data_preprocess($rawdata);

        $data = new stdClass();
        $data->{$prefix.'id'} = $itemnew->id;
        $data->fieldid      = $this->field->id;
        $data->data         = $itemnew->{$this->inputname};

        if ($dataid = $DB->get_field($tableprefix.'_info_data', 'id', array($prefix.'id' => $itemnew->id, 'fieldid' => $data->fieldid))) {
            if ($itemnew->{$this->inputname} !== null) {
                $data->id = $dataid;
                $DB->update_record($tableprefix.'_info_data', $data);
            } else {
                // Don't update a field with a null value. Just delete the field data.
                // This is mostly for the case when the field is null. Could be when resetting an option to the default
                // For example in menu options when resetting to "Choose" option.
                $DB->delete_records($tableprefix.'_info_data', array($prefix.'id' => $itemnew->id, 'fieldid' => $data->fieldid));
            }
        } else {
            if ($itemnew->{$this->inputname} !== null) {
                $this->dataid = $DB->insert_record($tableprefix . '_info_data', $data);
            } else {
                // Invalid value. Don't save.
                return;
            }
        }
        $this->edit_save_data_postprocess($rawdata);
    }

    /**
     * Validate the form field from edit page
     * @return  string  contains error message otherwise NULL
     **/
    function edit_validate_field($itemnew, $prefix, $tableprefix) {
        global $DB, $TEXTAREA_OPTIONS, $FILEPICKER_OPTIONS;

        $errors = array();
        /// Check for uniqueness of data if required
        if ($this->is_unique() && isset($itemnew->{$this->inputname})) {

            switch ($this->field->datatype) {
                case 'menu':
                    $data = $this->options[$itemnew->{$this->inputname}];
                    break;
                case 'textarea':
                    $shortinputname = substr($this->inputname, 0, strlen($this->inputname)-7);
                    $itemnew = file_postupdate_standard_editor($itemnew, $shortinputname, $TEXTAREA_OPTIONS,
                        $TEXTAREA_OPTIONS['context'], 'totara_customfield', $prefix, $itemnew->id);
                    $data = $itemnew->{$shortinputname};
                    break;
                default:
                    $data = $itemnew->{$this->inputname};
            }

            // search for a match
            if ($data != '' && $DB->record_exists_select($tableprefix.'_info_data',
                            "fieldid = ? AND " . $DB->sql_compare_text('data', 1024) . ' = ? AND ' .
                            $prefix . "id != ?",
                            array($this->field->id, $data, $itemnew->id))) {
                    $errors["{$this->inputname}"] = get_string('valuealreadyused');
            }
        }
        return $errors;
    }

    /**
     * Sets the default data for the field in the form object
     * @param   object   instance of the moodleform class
     */
    function edit_field_set_default(&$mform) {
        if (!empty($this->field->defaultdata)) {
            $mform->setDefault($this->inputname, $this->field->defaultdata);
        }
    }

    /**
     * Does some extra pre-processing for totara sync uploads.
     * Only required for custom fields with several options
     * like menu of choices, and multi-select.
     *
     * @param  object $itemnew The item being saved
     * @return object          The same item after processing
     */
    function sync_data_preprocess($itemnew) {
        return $itemnew;
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

        $syncitem->{$this->inputname} = $value;

        return $syncitem;
    }

    /**
     * Sets the required flag for the field in the form object
     * @param   object   instance of the moodleform class
     */
    function edit_field_set_required(&$mform) {
        if ($this->is_required()) {
            $mform->addRule($this->inputname, get_string('customfieldrequired', 'totara_customfield'), 'required', null, 'client');
        }
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
            $mform->setConstant($this->inputname, $this->data);
        }
    }

    /**
     * Hook for child classess to process the data before it gets saved in database
     * @param   mixed
     * @return  mixed
     */
    function edit_save_data_preprocess($data) {
        return $data;
    }

    /**
     * Hook for child classes to process the data after it gets saved in database (dataid is set)
     * @param   mixed
     * @return  null
     */
    public function edit_save_data_postprocess($data) {
        return null;
    }
    /**
     * Loads an object with data for this field ready for the edit form
     * form
     * @param   object a object
     */
    function edit_load_item_data(&$item) {
        if ($this->data !== NULL) {
            $item->{$this->inputname} = $this->data;
        }
    }

    /**
     * Check if the field data should be loaded into the object
     * By default it is, but for field prefixes where the data may be potentially
     * large, the child class should override this and return false
     * @return boolean
     */
    function is_object_data() {
        return true;
    }

/***** The following methods generally should not be overwritten by child classes *****/
    /**
     * Accessor method: set the itemid for this instance
     * @param   integer   id from the prefix (competency etc) table
     */
    function set_itemid($itemid) {
        $this->itemid = $itemid;
    }

    /**
     * Accessor method: set the fieldid for this instance
     * @param   integer   id from the _info_field table
     */
    function set_fieldid($fieldid) {
        $this->fieldid = $fieldid;
    }

    /**
     * Accessor method: set the addsuffix for this instance
     * @param   boolean If an itemid suffix should be added to a fieldname
     */
    private function set_addsuffix($addsuffix) {
        $this->addsuffix = $addsuffix;
    }

    /**
     * Accessor method: Load the field record and prefix data and tableprefix associated with the prefix
     * object's fieldid and itemid
     *
     * @param integer $itemid The id of the item to add the custom fields to.
     * @param string $prefix The database field name prefix.
     * @param string $tableprefix The database tabel name prefix.
     */
    function load_data($itemid, $prefix, $tableprefix) {
        global $DB;

        /// Load the field object
        if (($this->fieldid == 0) || (!($field = $DB->get_record($tableprefix.'_info_field', array('id' => $this->fieldid))))) {
            $this->field = NULL;
            $this->inputname = '';
        } else {
            $this->field = $field;
            $this->inputname = 'customfield_'. $field->shortname . ($this->addsuffix ? '_' . $itemid->id . $this->suffix : '');
        }
        if (!empty($this->field)) {
            $table = $tableprefix.'_info_data';
            $params = array($prefix.'id' => $this->itemid, 'fieldid' => $this->fieldid);
            if ($datarecord = $DB->get_record($table, $params, 'id, data')) {
                $this->data = $datarecord->data;
                $this->dataid = $datarecord->id;
            } else {
                $this->data = $this->field->defaultdata;
            }
        } else {
            $this->data = NULL;
        }
    }

    /**
     * Check if the field data is hidden to the current item 
     * @return  boolean
     */
    function is_hidden() {
        if ($this->field->hidden) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Check if the field data is considered empty
     * return boolean
     */
    function is_empty() {
        return ( ($this->data != '0') and empty($this->data));
    }

    /**
     * Check if the field is required on the edit page
     * @return   boolean
     */
    function is_required() {
        return (boolean)$this->field->required;
    }

    /**
     * Check if the field is locked on the edit page
     * @return   boolean
     */
    function is_locked() {
        return (boolean)$this->field->locked;
    }

    /**
     * Check if the field data should be unique
     * @return   boolean
     */
    function is_unique() {
        return (boolean)$this->field->forceunique;
    }

    /**
     * Deletes data and any related files for this custom field instance. Can be
     * overwritten by child classes if necessary.
     *
     * @throws dml_exception
     */
    public function delete() {
        global $DB;
        if (empty($this->dataid)) {
            // There is no data to delete.
            return;
        }

        if (empty($this->context)) {
            // The default context is currently context_system.
            $this->context = context_system::instance();
        }

        // Delete related files.
        $fs = get_file_storage();
        $fs->delete_area_files($this->context->id, 'totara_customfield', $this->prefix . '_filemgr', $this->dataid);
        $fs->delete_area_files($this->context->id, 'totara_customfield', $this->prefix, $this->dataid);
        // Delete data.
        $DB->delete_records($this->tableprefix . '_info_data', array('id' => $this->dataid));
        $DB->delete_records($this->tableprefix . '_info_data_param', array('dataid' => $this->dataid));
    }

} /// End of class efinition


/***** General purpose functions for custom fields *****/

/**
 * Load the data into the custom fields.
 *
 * @param object $item The item to add the custom fields to.
 * @param string $prefix The database field name prefix.
 * @param string $tableprefix The database table name prefix.
 * @param boolean|false $addsuffix If an item id suffix should be added to the custom field.
 * @param string $suffix The suffix to add after the item id when loading the data
 * @throws coding_exception
 */
function customfield_load_data(&$item, $prefix, $tableprefix, $addsuffix = false, $suffix = '') {
    global $TEXTAREA_OPTIONS;

    $params = array();
    if (isset($item->typeid)) {
        $params['typeid'] = $item->typeid;
    }

    $fields = customfield_get_fields_definition($tableprefix, $params);
    foreach ($fields as $field) {
        $formfield = customfield_get_field_instance($item, $field, $tableprefix, $prefix, $addsuffix, $suffix);
        //edit_load_item_data adds the field and data to the $item object
        $formfield->edit_load_item_data($item);
        //if an unlocked textfield we also need to prepare the editor fields
        if ($field->datatype == 'textarea' && !$formfield->is_locked()) {
            // Get short form by removing trailing '_editor' from $this->inputname.
            $shortinputname = substr($formfield->inputname, 0, strlen($formfield->inputname) - 7);
            $formatstr = $shortinputname . 'format';
            $item->$formatstr = FORMAT_HTML;
            if ($formfield->data == $formfield->field->defaultdata) {
                $item->$shortinputname = $formfield->field->defaultdata;
                $item = file_prepare_standard_editor($item, $shortinputname, $TEXTAREA_OPTIONS, $TEXTAREA_OPTIONS['context'],
                                'totara_customfield', 'textarea', $formfield->fieldid);
            } else {
                $item = file_prepare_standard_editor($item, $shortinputname, $TEXTAREA_OPTIONS, $TEXTAREA_OPTIONS['context'],
                                'totara_customfield', $prefix, $formfield->dataid);
            }
        }
    }
}


/**
 * Print out the customisable fields.
 *
 * @param object &$mform Instance of the moodleform class
 * @param object $item The item to add the custom fields to.
 * @param string $prefix The database field name prefix.
 * @param integer $typeid The item id.
 * @param string $tableprefix The database table name prefix.
 * @param boolean|false $disableheader If a header for the custom fields should be displayed.
 * @param boolean|false $addsuffix If an item id suffix should be added to the custom field.
 * @param boolean|false $lock if the field should be readonly
 * @param string $suffix The suffix to add after the item id when loading the data
 * @throws coding_exception
 */
function customfield_definition(&$mform,
                                $item,
                                $prefix,
                                $typeid = 0,
                                $tableprefix,
                                $disableheader = false,
                                $addsuffix = false,
                                $lock = false,
                                $suffix = '') {

    $params = array();
    if (isset($item->typeid)) {
        $params['typeid'] = $typeid;
    }

    $fields = customfield_get_fields_definition($tableprefix, $params);
    // check first if *any* fields will be displayed
    $display = false;
    foreach ($fields as $field) {
        if ($field->hidden == false) {
            $display = true;
        }
    }

    // display the header and the fields
    if ($display) {
        if (!$disableheader) {
            $mform->addElement('header', 'customfields', get_string('customfields', 'totara_customfield'));
        }
        foreach ($fields as $field) {
            $formfield = customfield_get_field_instance($item, $field, $tableprefix, $prefix, $addsuffix, $suffix);
            if ($lock) {
                $formfield->field->locked = true;
            }
            $formfield->edit_field($mform);
            if ($lock) {
                $formfield->edit_field_set_locked($mform);
            }

        }
    }
}

function customfield_definition_after_data(&$mform, $item, $prefix, $typeid = 0, $tableprefix) {

    $params = array();
    if ($typeid != 0) {
        $params['typeid'] = $typeid;
    }

    $fields = customfield_get_fields_definition($tableprefix, $params);
    foreach ($fields as $field) {
        $formfield = customfield_get_field_instance($item, $field, $tableprefix, $prefix);
        $formfield->edit_after_data($mform);
    }
}

function customfield_validation($itemnew, $prefix, $tableprefix) {

    $err    = array();
    $params = array();
    if (!empty($itemnew->typeid)) {
        $params['typeid'] = $itemnew->typeid;
    }

    $fields = customfield_get_fields_definition($tableprefix, $params);
    foreach ($fields as $field) {
        $formfield = customfield_get_field_instance($itemnew, $field, $tableprefix, $prefix);
        $err += $formfield->edit_validate_field($itemnew, $prefix, $tableprefix);
    }
    return $err;
}

/**
 * Process the CSV file data for a custom field and validate it.
 *
 * @param object  $itemnew      The CSV file data we are overwrite and validating
 * @param string  $prefix       The custom field prefix (organisation, position, etc)
 * @param string  $tableprefix  The table prefix (org_type, pos_type, etc)
  *
 * @return list array $err $newdata
 */
function customfield_validation_filedata($itemnew, $prefix, $tableprefix) {

    $err    = array();
    $fields = customfield_get_fields_definition($tableprefix);
    foreach ($fields as $field) {
        if (isset($itemnew->{$field->shortname})) {
            $formfield = customfield_get_field_instance($itemnew, $field, $tableprefix, $prefix);
            $itemnew = $formfield->sync_filedata_preprocess($itemnew);
            $err += $formfield->edit_validate_field($itemnew, $prefix, $tableprefix);
        } else {
            $err += (array)get_string('error:novalue', 'totara_customfield', $field->shortname);
        }
    }
    return array($err, (array)$itemnew);
}

/**
 * Process the data for a custom field and save it to the appropriate database table.
 *
 * @param object  $itemnew      The data we are saving
 * @param string  $prefix       The custom field prefix (organisation, position, etc)
 * @param string  $tableprefix  The table prefix (org_type, pos_type, etc)
 * @param boolean $sync         Whether this is being called from sync and needs pre-preprocessing.
 * @param boolean $addsuffix    Whether the custom fix has an item id suffix added to it.
 * @param string  $suffix       The sufffix to add after the item id when loading the data
 */
function customfield_save_data($itemnew, $prefix, $tableprefix, $sync = false, $addsuffix = false, $suffix = '') {

    $params = array();
    if (isset($itemnew->typeid)) {
        $params['typeid'] = $itemnew->typeid;
    }

    $fields = customfield_get_fields_definition($tableprefix, $params);
    foreach ($fields as $field) {
        $formfield = customfield_get_field_instance($itemnew, $field, $tableprefix, $prefix, $addsuffix, $suffix);
        if ($sync) {
            $itemnew = $formfield->sync_data_preprocess($itemnew);
        }
        $formfield->edit_save_data($itemnew, $prefix, $tableprefix);
    }
}

/**
 * Return an associative array of custom field name/value pairs for display
 *
 * The array contains values formatted for printing to the page. Hidden and
 * empty fields are not returned. Data has been passed through the appropriate
 * display_data() method.
 *
 * @deprecated since Totara 11.0
 *
 * @param integer $item The item the fields belong to
 * @param string $tableprefix Prefix to append '_info_field' to
 * @param string $prefix Custom field prefix (e.g. 'course' or 'position')
 *
 * @return array Associate array of field names and data values
 */
function customfield_get_fields($item, $tableprefix, $prefix) {

    debugging('customfield_get_fields has been deprecated since 11.0. Please use customfield_get_data instead.', DEBUG_DEVELOPER);

    $out = array();
    $fields = customfield_get_fields_definition($tableprefix);
    foreach ($fields as $field) {
        $formfield = customfield_get_field_instance($item, $field, $tableprefix, $prefix);
        if (!$formfield->is_hidden() and !$formfield->is_empty()) {
            $out[format_string($formfield->field->fullname)] = $formfield->display_data();
        }
    }
    return $out;
}

/**
 * Return an associative array of custom field ids and definition pairs
 *
 *
 * @param string $tableprefix Prefix to append '_info_field' to
 * @param array $conditions optional array $fieldname=>requestedvalue with AND in between
 *
 * @return array Associate array of field definition ids and properties
 */
function customfield_get_fields_definition($tableprefix, $conditions = array()) {
    global $DB;

    $fields = $DB->get_records($tableprefix.'_info_field', $conditions, 'sortorder ASC');
    if (!$fields) {
        $fields = array();
    }
    return $fields;
}

/**
 * Get custom fields and their data.
 *
 * @param stdClass $item The Item associated with the customfield
 * @param string $tableprefix the table prefix of the customfield
 * @param string $prefix The prefix of the custom field
 * @param bool $indexfullname If true the index for each value will be the fullname of the field, otherwise the shortname
 * @param array $itemextradata customfield_file::display_item_data requires some additional extradata.
 * @return array Array with the customfield and its associated value
 */
function customfield_get_data($item, $tableprefix, $prefix, $indexfullname = true, $itemextradata = array()) {
    global $DB, $CFG;
    $out = array();

    $fieldid = $prefix . 'id';
    $sql = "SELECT tif.id, tif.datatype, tif.fullname, tif.shortname, tid.id AS dataid, tid.data
              FROM {{$tableprefix}_info_field} tif
        INNER JOIN {{$tableprefix}_info_data} tid
                ON tif.id = tid.fieldid
             WHERE tif.hidden = 0
               AND tid.{$fieldid} = :itemid
          ORDER BY tif.sortorder ASC";

    $fields = $DB->get_records_sql($sql, array('itemid' => $item->id));
    foreach ($fields as $field) {
        $data = $field->data;
        switch ($field->datatype) {
            case 'checkbox':
                require_once($CFG->dirroot.'/totara/customfield/field/checkbox/field.class.php');
                $data = \customfield_checkbox::display_item_data($data);
                break;
            case 'multiselect':
                $datavalue = json_decode($field->data, true);
                $values = array();
                $dataparams = $DB->get_records("{$tableprefix}_info_data_param", array('dataid' => $field->dataid));
                foreach ($dataparams as $dataparam) {
                    if (isset($datavalue[$dataparam->value])) {
                        $option = $datavalue[$dataparam->value];
                        $values[] = $option['option'];
                    }
                }
                $data = implode(', ', $values);
                break;
            case 'file':
                require_once($CFG->dirroot.'/totara/customfield/field/file/field.class.php');
                $extradata = array('prefix' => $prefix, 'itemid' => $item->id, 'isexport' => false);
                $data = \customfield_file::display_item_data($data, $extradata);
                break;
            case 'datetime':
                require_once($CFG->dirroot.'/totara/customfield/field/datetime/field.class.php');
                $data = \customfield_datetime::display_item_data($data);
                break;
            case 'textarea':
                require_once($CFG->dirroot.'/totara/customfield/field/textarea/field.class.php');
                $extradata = array('prefix' => $prefix, 'itemid' => $field->dataid);
                $data = \customfield_textarea::display_item_data($data, $extradata);
                break;
            case 'url':
                require_once($CFG->dirroot.'/totara/customfield/field/url/field.class.php');
                $extradata = array('prefix' => $prefix, 'itemid' => $field->dataid);
                $data = \customfield_url::display_item_data($data, $extradata);
                break;
            case 'location':
                require_once($CFG->dirroot.'/totara/customfield/field/location/field.class.php');
                // If require pass in 'extended' => true as a value in the $extradata array to get the full map layout.
                $extradata = array_merge($itemextradata, array('prefix' => $prefix, 'itemid' => $field->dataid));
                $data = json_decode($data);
                $data = \customfield_location::display_item_data($data, $extradata);
                break;
            case 'menu':
                require_once($CFG->dirroot.'/totara/customfield/field/menu/field.class.php');
                $data = \customfield_menu::display_item_data($data);
        }
        $out[$indexfullname ? format_string($field->fullname) : $field->shortname] = $data;
    }
    return $out;
}

/**
 * Return an associative array of custom field shortname/value pairs for display
 *
 * The array contains values formatted for printing to the page. Hidden and
 * empty fields are not returned. Data has been passed through the appropriate
 * display_data() method.
 *
 * @param integer $item The item the fields belong to
 * @param string $tableprefix Prefix to append '_info_field' to
 * @param string $prefix Custom field prefix (e.g. 'course' or 'position')
 *
 * @return array Associate array of field names and data values
 */
function customfield_get_data_shortname_key($item, $tableprefix, $prefix) {

    $out    = array();
    $fields = customfield_get_fields_definition($tableprefix);
    foreach ($fields as $field) {
        $formfield = customfield_get_field_instance($item, $field, $tableprefix, $prefix);
        if (!$formfield->is_hidden() and !$formfield->is_empty()) {
            $out[$formfield->field->shortname] = $formfield->display_data();
        }
    }
    return $out;
}


/**
 * Returns an object with the custom fields set for the given id
 * @param  integer  id
 * @return  object
 *
 * @deprecated since 10.0
 */
function customfield_record($id, $tableprefix) {
    throw new coding_exception('customfield_record has been deprecated since 10.');
}

/**
 * Get a single custom field instance.
 *
 * @param object $item the item the custom fields belong to
 * @param int/stdClass $field the custom field id
 * @param string $tableprefix Prefix to append '_info_field' to
 * @param string $prefix Custom field prefix (e.g. 'course' or 'position')
 * @param boolean $addsuffix If the custom field should have a suffix added
 * @param string $suffix The suffix along with the item id when loading data
 *
 * @return boolean|object containing field attributes and the field value, or false
 */
function customfield_get_field_instance($item, $field, $tableprefix, $prefix, $addsuffix = false, $suffix = '') {
    global $CFG, $DB;

    if (!is_object($field)) {
        $field = $DB->get_record($tableprefix . '_info_field', array('id' => (int)$field));
    }
    if (!$field) return false;

    require_once($CFG->dirroot.'/totara/customfield/field/'.$field->datatype.'/field.class.php');
    $newfield = 'customfield_'.$field->datatype;
    $formfield = new $newfield($field->id, $item, $prefix, $tableprefix, $addsuffix, $suffix);

    return $formfield;
}
