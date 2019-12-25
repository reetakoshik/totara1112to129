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

/**
 * Class customfield_url
 */
class customfield_url extends customfield_base {

    /**
     * An array of properties for this URL instance.
     * This information gets stored as JSON against the field definition.
     * @var array
     */
    public $urldata = array();

    /**
     * Constructor method.
     *
     * Pulls out the json data if set to build the $urldata array.
     *
     * @param int $fieldid
     * @param int $itemid
     * @param string $prefix
     * @param string $tableprefix
     * @param bool $addsuffix
     */
    public function __construct($fieldid=0, $itemid=0, $prefix, $tableprefix, $addsuffix = false, $suffix = '') {
        // First call parent constructor.
        parent::__construct($fieldid, $itemid, $prefix, $tableprefix, $addsuffix, $suffix);

        // Decode any saved json data.
        $this->urldata = json_decode($this->data);
    }

    /**
     * Adds the fields for the URL custom fields to the form.
     *
     * @param MoodleQuickForm $mform
     */
    public function edit_field_add(&$mform) {
        $urlgrp = array();
        $urlgrpname = $this->inputname . '_group';

        // Why create the labels manually? Because there is no way to make the accessible label visible ...
        $urllabel = html_writer::tag('label', get_string('customfieldtypeurl', 'totara_customfield'), array('for' => 'id_' . $this->inputname . '_url'));
        $urlgrp[] = $mform->createElement('static', uniqid("{$this->inputname}_urllabel_"), null, $urllabel);
        $urlgrp[] = $mform->createElement('text', $this->inputname . '[url]');
        $textlabel = html_writer::tag('label', get_string('customfieldtypeurltext', 'totara_customfield'), array('for' => 'id_' . $this->inputname . '_text'));
        $urlgrp[] = $mform->createElement('static', uniqid("{$this->inputname}_textlabel_"), null, $textlabel);
        $urlgrp[] = $mform->createElement('text', $this->inputname . '[text]');
        $urlgrp[] = $mform->createElement('checkbox', $this->inputname . '[target]');
        $checkboxlabel = html_writer::tag('label', get_string('customfieldtypeurltarget', 'totara_customfield'), array('for' => 'id_' . $this->inputname . '_target'));
        $urlgrp[] = $mform->createElement('static', uniqid("{$this->inputname}_checkboxlabel_"), null, $checkboxlabel);

        $mform->addGroup($urlgrp, $urlgrpname, format_string($this->field->fullname), null, false);
        $mform->setType($this->inputname . '[url]', PARAM_URL);
        $mform->setType($this->inputname . '[text]', PARAM_TEXT);
        $mform->setType($this->inputname . '[target]', PARAM_BOOL);
        $mform->addHelpButton($urlgrpname, 'customfieldurl', 'totara_customfield');

        // Disable the 'text' and 'open in new window' input if a URL has not been added.
        $mform->disabledIf($this->inputname . '[text]', $this->inputname . '[url]', 'eq', '');
        $mform->disabledIf($this->inputname . '[target]', $this->inputname  . '[url]', 'eq', '');
    }

    /**
     * Set the default value for this field instance.
     *
     * @param MoodleQuickForm $mform
     */
    public function edit_field_set_default(&$mform) {
        if ($this->dataid) {
            // Set saved.
            if (isset($this->urldata->url)) {
                $mform->setDefault($this->inputname . '[url]', $this->urldata->url);
            }
            if (isset($this->urldata->text)) {
                $mform->setDefault($this->inputname . '[text]', $this->urldata->text);
            }
            if (isset($this->urldata->target)) {
                $mform->setDefault($this->inputname . '[target]', $this->urldata->target);
            }
        } else {
            // Set default.
            $mform->setDefault($this->inputname . '[url]', $this->data);
            $mform->setDefault($this->inputname . '[text]', $this->field->param1);
            $mform->setDefault($this->inputname . '[target]', $this->field->param2);
        }
    }

    /**
     * Saves the data coming from form
     *
     * @param object $itemnew  data coming from the form
     * @param string $prefix name of the prefix (ie, position)
     * @param string $tableprefix name of the the table prefix (ie, pos_type)
     * @return void
     */
    public function edit_save_data($itemnew, $prefix, $tableprefix) {
        global $DB;

        if (!isset($itemnew->{$this->inputname})) {
            return;
        }

        $urldata = $itemnew->{$this->inputname};

        $data = new stdClass();
        $data->{$prefix.'id'} = $itemnew->id;
        $data->fieldid  = $this->field->id;

        if (empty($urldata['url'])) {
            $data->data = '';
        } else {
            $data->data = json_encode($urldata);
        }

        if ($dataid = $DB->get_field($tableprefix.'_info_data', 'id', array($prefix.'id' => $itemnew->id, 'fieldid' => $data->fieldid))) {
            $data->id = $dataid;
            $DB->update_record($tableprefix.'_info_data', $data);
        } else {
            $this->dataid = $DB->insert_record($tableprefix.'_info_data', $data);
        }
    }

    /**
     * Display the data for this field
     *
     * @param string $data json encoded data
     * @param array $extradata additional data
     * @return string the html
     */
    public static function display_item_data($data, $extradata = array()) {
        if (empty($data)) {
            return '';
        } else {
            $urldata = json_decode($data);
            if (empty($urldata->url)) {
                return '';
            }

            // Exporting just the url
            if (!empty($extradata['isexport'])) {
                return $urldata->url;
            }

            $text = s(empty($urldata->text) ? $urldata->url : format_string($urldata->text));
            $target = !empty($urldata->target) ? array('target' => '_blank', 'rel' => 'noreferrer') : null;

            return html_writer::link($urldata->url, $text, $target);
        }
    }

    /**
     * Validates the URL value that has been set.
     *
     * @param object $itemnew
     * @param string $prefix
     * @param string $tableprefix
     * @return array An array of errors for this field, if it fails validation.
     */
    public function edit_validate_field($itemnew, $prefix, $tableprefix) {
        // URLs need to start with http://, https:// or /

        if (!isset($itemnew->{$this->inputname})) {
            return array();
        }

        $urlgrpname = $this->inputname . '_group';

        $urldata = $itemnew->{$this->inputname};

        if (!empty($urldata['url'])) {
            if (substr($urldata['url'], 0, 7) !== 'http://' && substr($urldata['url'], 0, 8) !== 'https://' && substr($urldata['url'], 0, 1) !== '/') {
                return array($urlgrpname => get_string('customfieldurlformaterror', 'totara_customfield'));
            }
        }

        return array();
    }

    /**
     * Sets the required flag for the field in the form object
     *
     * @param MoodleQuickForm $mform
     */
    public function edit_field_set_required(&$mform) {
        if ($this->is_required()) {
            $mform->addRule($this->inputname . '_group', get_string('err_required', 'form'), 'required');
        }
    }

    /**
     * HardFreeze the field if locked.
     *
     * @param MoodleQuickForm $mform
     */
    public function edit_field_set_locked(&$mform) {
        if (!$mform->elementExists($this->inputname . '_group')) {
            return;
        }
        if ($this->is_locked()) {
            /** var MoodleQuickForm_group $group */
            $mform->hardFreeze($this->inputname . '_group');
            $group = $mform->getElement($this->inputname . '_group');
            foreach ($group->getElements() as $element) {
                /** @var HTML_QuickForm_element $element */
                $value = null;
                $elname = $element->getName();
                switch ($elname) {
                    case 'customfield_lockedurl[url]':
                        $value = $this->urldata->url;
                        break;
                    case 'customfield_lockedurl[text]':
                        $value = $this->field->param1;
                        break;
                    case 'customfield_lockedurl[target]':
                        $value = $this->field->param2;
                        break;
                }
                if ($value !== null) {
                    // Constants were never designed to be used for elements within a group. This copies
                    // the logic from MoodleQuickForm::setConstant and abuses the lack of proper variable
                    // scope. If you hit this then you need to set constant on the fields within the group.
                    $mform->_constantValues = HTML_QuickForm::arrayMerge($mform->_constantValues, array($elname => $value));
                    $element->onQuickFormEvent('updateValue', null, $mform);
                }
            }

        }
    }

    /**
     * Changes the customfield value from a file data to the key and value.
     *
     * @param  object $syncitem The original syncitem to be processed.
     * @return array The syncitem with the customfield data processed.
     */
    public function sync_filedata_preprocess($syncitem) {

        $value = $syncitem->{$this->field->shortname};
        unset($syncitem->{$this->field->shortname});

        $data = array();
        if (!empty($value)) {
            $value = core_text::strtolower($value);
            if (substr($value, 0, 7) !== 'http://' && substr($value, 0, 8) !== 'https://' && substr($value, 0, 1) !== '/') {
                $value = 'http://' . $value;
            }
        }
        $data['url']  = $value;
        $data['text'] = '';
        $data['target'] = '0';

        $syncitem->{$this->inputname} = $data;

        return $syncitem;
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

        $url = $syncitem->$fieldname;

        if (isset($url['url'])) {
            // Has already been processed.
            return $syncitem;
        }

        $data = array();
        $url = clean_param($url, PARAM_URL);
        if (!empty($url)) {
            $url = core_text::strtolower($url);
            if (substr($url, 0, 7) !== 'http://' && substr($url, 0, 8) !== 'https://' && substr($url, 0, 1) !== '/') {
                $url = 'http://' . $url;
            }
        }
        $data['url']  = $url;
        $data['text'] = '';
        $data['target'] = '0';

        $syncitem->{$this->inputname} = $data;

        return $syncitem;
    }
}
