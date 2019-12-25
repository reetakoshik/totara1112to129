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

class customfield_textarea extends customfield_base {

    function edit_field_add(&$mform) {
        global $TEXTAREA_OPTIONS;
        $cols = $this->field->param1;
        $rows = $this->field->param2;
        $context = context_system::instance();

        $isexport = false;
        if ($mform->elementExists('export')) {
            // The export element is used by appraisals snapshots, if set the output should be static.
            $isexport = $mform->getElement('export')->getValue();
        }

        // Create the form field.
        if ($this->itemid == 0) { // If its new record.
            $mform->addElement('editor', $this->inputname, format_string($this->field->fullname), array('cols' => $cols, 'rows' => $rows), $TEXTAREA_OPTIONS);
            if (!empty($this->field->defaultdata)) {
                $data = file_rewrite_pluginfile_urls($this->field->defaultdata, 'pluginfile.php', $context->id, 'totara_customfield', 'textarea', $this->fieldid);
                $mform->setDefault($this->inputname, array('text' => $data));
            }
            $mform->setType($this->inputname, PARAM_CLEANHTML);
        } else { // If its existing record.
            if ($this->is_locked() || $isexport) {
                $data = file_rewrite_pluginfile_urls($this->data, 'pluginfile.php', $context->id, 'totara_customfield', $this->prefix, $this->dataid);
                // Display the field using a hyphen if there's no content.
                $mform->addElement(
                    'static',
                    'freezedisplay',
                    format_string($this->field->fullname),
                    html_writer::div(
                        !empty($data) ? format_text($data, FORMAT_MOODLE) : get_string('readonlyemptyfield', 'totara_customfield'),
                        null,
                        ['id' => 'id_customfield_' . $this->field->shortname]
                    )
                );
            } else {
                $mform->addElement('editor', $this->inputname, format_string($this->field->fullname), array('cols' => $cols, 'rows' => $rows), $TEXTAREA_OPTIONS);
                $data = file_rewrite_pluginfile_urls($this->data, 'pluginfile.php', $context->id, 'totara_customfield', 'textarea', $this->fieldid);
                $mform->setDefault($this->inputname, array('text' => $data));
                $mform->setType($this->inputname, PARAM_CLEANHTML);
            }
        }
    }

    /// Overwrite base class method, data in this field type is potentially too large to be
    /// included in the item object
    function is_item_object_data() {
        return false;
    }

    /**
    * Accessor method: Load the field record and prefix data and tableprefix associated with the prefix
    * object's fieldid and itemid
    */
    function load_data($itemid, $prefix, $tableprefix) {
        $this->prefix = $prefix;
        parent::load_data($itemid, $prefix, $tableprefix);
        if ($this->inputname != '' && substr($this->inputname, strlen($this->inputname)-6) != '_editor') {
            $this->inputname = $this->inputname . '_editor';
        }
    }

    /**
     * Include processed customfield data to the sync object.
     *
     * @param  object $itemnew     The original syncitem to be processed.
     * @return object              The syncitem with the customfield data processed.
     */
    function sync_data_preprocess($itemnew) {

        // Get short form name by removing trailing '_editor' from $this->inputname;
        $shortinputname = substr($this->inputname, 0, -7);

        if (!isset($itemnew->$shortinputname)) {
            return $itemnew;
        }

        $systemcontext = context_system::instance();

        // Create textarea options array taken from global $TEXTAREA_OPTIONS
        $textarea_options = array(
            'subdirs' => 0,
            'maxfiles' => EDITOR_UNLIMITED_FILES,
            'maxbytes' => get_max_upload_file_size(),
            'trusttext' => false,
            'context' => $systemcontext,
            'collapsed' => true
        );

        // We need to include processed customfield data to the sync object.
        $itemnew = file_prepare_standard_editor($itemnew, $shortinputname, $textarea_options, $systemcontext, 'totara_customfield', $this->prefix);

        return $itemnew;
    }

    /**
    * Saves the data coming from form
    * @param   mixed   data coming from the form
    * @param   string  name of the prefix (ie, competency)
    * @return  mixed   returns data id if success of db insert/update, false on fail, 0 if not permitted
    */
    function edit_save_data($itemnew, $prefix, $tableprefix) {
        global $DB, $TEXTAREA_OPTIONS;

        //get short form by removing trailing '_editor' from $this->inputname;
        $shortinputname = substr($this->inputname, 0, strlen($this->inputname)-7);
        if (!isset($itemnew->{$this->inputname})) {
            // field not present in form, probably locked and invisible - skip it
            return;
        }
        $data = new stdClass();
        $data->{$prefix.'id'} = $itemnew->id;
        $data->fieldid      = $this->field->id;
        $data->data = '';
        if ($dataid = $DB->get_field($tableprefix.'_info_data', 'id', array($prefix.'id' => $itemnew->id, 'fieldid' => $data->fieldid))) {
            $data->id = $dataid;
            $DB->update_record($tableprefix.'_info_data', $data);
        } else {
            $data->id = $DB->insert_record($tableprefix.'_info_data', $data);
        }
        $itemnew = file_postupdate_standard_editor($itemnew, $shortinputname, $TEXTAREA_OPTIONS, $TEXTAREA_OPTIONS['context'], 'totara_customfield', $prefix, $data->id);
        $data->data = $itemnew->{$shortinputname};
        $DB->update_record($tableprefix.'_info_data', $data);
    }

    /**
    * Loads an object with data for this field ready for the edit form
     * form
    * @param   object a object
    */
    function edit_load_item_data(&$item) {
        //get short form by removing trailing '_editor' from $this->inputname;
        $shortinputname = substr($this->inputname, 0, strlen($this->inputname)-7);
        $context = context_system::instance();
        if ($this->data !== NULL && !$this->is_locked()) {
            $item->{$shortinputname} = $this->data;
        }
    }

    /**
     * Display the data for the textarea custom field.
     *
     * $data mixed The data to display.
     * $extradata array Data that identifies the source of the data.
     */
    static function display_item_data($data, $extradata=array()) {

        // Export - return raw data
        if (!empty($extradata['isexport'])) {
            return $data;
        }

        if (!empty($extradata['altprefix'])) {
            $extradata['prefix'] = $extradata['altprefix'];
        }
        if (empty($extradata['prefix']) || empty($extradata['itemid'])) {
            if (empty($data)) {
                return get_string('readonlyemptyfield', 'totara_customfield');
            } else {
                return $data;
            }
        }

        $context = context_system::instance();
        $data = file_rewrite_pluginfile_urls($data, 'pluginfile.php', $context->id, 'totara_customfield', $extradata['prefix'], $extradata['itemid']);

        if (!empty($extradata['isexport'])) {
            return format_string($data);
        } else {
            if (empty($data)) {
                return get_string('readonlyemptyfield', 'totara_customfield');
            } else {
                return $data;
            }
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

        $data = array();
        $data['text']   = $value;
        $data['itemid'] = '0';
        $data['format'] = FORMAT_HTML;

        $syncitem->{$this->inputname} = $data;

        return $syncitem;
    }
}
