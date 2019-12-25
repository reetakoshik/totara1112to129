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
 * @author Petr Skoda <petr.skoda@totaralms.com>
 * @package totara_form
 */

namespace totara_form\form\element;

use totara_form\element,
    totara_form\file_area,
    totara_form\form\validator\attribute_required,
    totara_form\form\validator\element_editor,
    totara_form\item;

/**
 * Text editor element.
 *
 * @package   totara_form
 * @copyright 2016 Totara Learning Solutions Ltd {@link http://www.totaralms.com/}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Petr Skoda <petr.skoda@totaralms.com>
 */
class editor extends element {
    /** @var array $files the cache of get_files() result */
    private $files;

    /**
     * Text editor constructor.
     *
     * @param string $name
     * @param string $label
     * @param array $options optional filemanager options (these can be accessed as attributes)
     */
    public function __construct($name, $label, array $options = null) {
        global $CFG;
        require_once($CFG->dirroot . '/lib/filelib.php');
        require_once($CFG->dirroot . '/repository/lib.php');

        parent::__construct($name, $label);
        $this->attributes = array(
            'areamaxbytes' => -1,
            'cols' => 80,
            'maxbytes' => null,
            'maxfiles' => -1,
            'required' => false,
            'return_types' => (FILE_INTERNAL | FILE_EXTERNAL | FILE_REFERENCE),
            'rows' => 15,
            'subdirs' => true,
            // Security stuff.
            'noclean' => false,
            // The following just help with repository selection and defaults.
            'context' => null,
            'disable_types' => null,
        );

        $this->set_attributes((array)$options);

        // Add validators.
        $this->add_validator(new attribute_required());
        $this->add_validator(new element_editor());
    }

    /**
     * Is the given name used by this element?
     *
     * @param string $name
     * @return bool
     */
    public function is_name_used($name) {
        if ($name === $this->get_name() . 'format') {
            return true;
        }
        if ($name === $this->get_name() . 'filearea') {
            return true;
        }
        return parent::is_name_used($name);
    }

    /**
     * Called by parent before adding this element
     * or after removing element from parent.
     *
     * @throws \coding_exception if the format name has already been used.
     * @throws \coding_exception if the filearea name has already been used.
     * @param item $parent
     */
    public function set_parent(item $parent = null) {
        $name = $this->get_name();

        if ($parent) {
            // Make sure no element is using (or abusing) the same name.
            if ($parent->get_model()->find(true, 'is_name_used', 'totara_form\item', true, array($name . 'format'), false)) {
                throw new \coding_exception('Duplicate name "' . $name . 'format" detected!');
            }
            if ($parent->get_model()->find(true, 'is_name_used', 'totara_form\item', true, array($name . 'filearea'), false)) {
                throw new \coding_exception('Duplicate name "' . $name . 'filearea" detected!');
            }
        }

        $this->files = null;
        parent::set_parent($parent);
    }

    /**
     * Set value of attribute.
     *
     * @param string $name
     * @param mixed $value null means value not specified
     */
    public function set_attribute($name, $value) {
        if ($name === 'maxfiles') {
            $value = isset($value) ? (int)$value : -1;

        } else if ($name === 'areamaxbytes') {
            $value = isset($value) ? (int)$value : -1;

        } else if ($name === 'cols') {
            $value = isset($value) ? (int)$value : 80;

        } else if ($name === 'rows') {
            $value = isset($value) ? (int)$value : 15;

        } else if ($name === 'subdirs') {
            $value = isset($value) ? (bool)$value : true;

        } else if ($name === 'return_types') {
            $value = isset($value) ? (int)$value : FILE_INTERNAL;

        } else if ($name === 'contextid') {
            $name = 'context';
            if ($value !== null) {
                $value = \context::instance_by_id($value, MUST_EXIST);
            }
        }
        parent::set_attribute($name, $value);
    }

    /**
     * Get submitted data without validation.
     *
     * @return array
     */
    public function get_data() {
        $model = $this->get_model();
        $name = $this->get_name();

        if ($this->is_frozen()) {
            return $this->get_current_data();
        }

        $data = $model->get_raw_post_data($name);
        if (!is_array($data) or !isset($data['text']) or is_array($data['text'])) {
            // Malformed or missing data, use current data to prevent data loss.
            return $this->get_current_data();
        }
        $result = array($name => $data['text']);

        // Add format only if it was in the current data.
        $currentformat = $model->get_current_data($name . 'format');
        if ($currentformat) {
            if (isset($data['format']) and !is_array($data['format'])) {
                $result[$name . 'format'] = $data['format'];
            } else {
                // This should not happen unless the template is borked.
                $result[$name . 'format'] = (string)FORMAT_HTML;
            }
        }

        // Rewrite the links if necessary.
        $currentfilearea = $this->get_current_file_area();
        if ($currentfilearea and isset($data['itemid']) and is_number($data['itemid'])) {
            // No loginhttsp support here, that features needs to be dropped.
            $result[$name] = file_rewrite_urls_to_pluginfile($result[$name], $data['itemid'], false);
        }

        return $result;
    }

    /**
     * Get submitted draft files without validation.
     *
     * @return array
     */
    public function get_files() {
        global $USER;

        // NOTE: for security reasons we need to cache the list of files,
        // otherwise users might modify the files between validation and saving in other request.

        if (isset($this->files)) {
            return $this->files;
        }

        $currentfilearea = $this->get_current_file_area();
        if (!$currentfilearea) {
            // No current file area means no file support!
            $this->files = array();
            return $this->files;
        }

        $name = $this->get_name();
        $model = $this->get_model();

        if ($this->is_frozen()) {
            $draftid = $currentfilearea->create_draft_area();

        } else {
            $data = $model->get_raw_post_data($name);
            if (isset($data['itemid']) and is_number($data['itemid'])) {
                $draftid = $data['itemid'];
            } else {
                // No value in _POST or invalid value format, this should not happen!
                $draftid = $currentfilearea->create_draft_area();
            }
        }

        /** @var \file_storage $fs */
        $fs = get_file_storage();
        $usercontext = \context_user::instance($USER->id);
        $files = $fs->get_area_files($usercontext->id, 'user', 'draft', $draftid, 'itemid, filepath, filename', $this->attributes['subdirs']);

        $this->files = array($name => array_values($files));
        return $this->files;
    }

    /**
     * Store the changed files.
     *
     * @throws \coding_exception if you try to call this method directly.
     * @throws \coding_exception if the element does not know its own area.
     * @param \context $context null means keep previous
     * @param null $itemid null means keep previous
     * @return bool success
     */
    public function update_file_area(\context $context = null, $itemid = null) {
        if (!$this->is_finalised() or !$this->is_valid()) {
            throw new \coding_exception('update_file_area() must not be called directly!');
        }
        $currentfilearea = $this->get_current_file_area();
        if (!$currentfilearea) {
            debugging('Cannot update file area because element does not know the area!', DEBUG_DEVELOPER);
            return false;
        }
        if ($this->is_frozen()) {
            return false;
        }

        $name = $this->get_name();
        $files = $this->get_files();

        $attributes = $this->get_attributes();
        $attributes['maxbytes'] = $this->get_maxbytes();

        return $currentfilearea->update_file_area($files[$name], $attributes, $context, $itemid);
    }

    /**
     * Compare element value.
     *
     * @param string $operator open of model::OP_XXX operators
     * @param mixed $value2
     * @param bool $finaldata true means use get_data(), false means use get_field_value()
     * @return bool result
     */
    public function compare_value($operator, $value2 = null, $finaldata = true) {
        $value1 = null;
        if ($finaldata) {
            $data = $this->get_data();
            $name = $this->get_name();
            if (isset($data[$name])) {
                $value1 = $data[$name];
            }
        } else {
            $data = $this->get_field_value();
            $value1 = $data['text'];
        }

        return $this->get_model()->compare($value1, $operator, $value2);
    }

    /**
     * Get Mustache template data.
     *
     * @param \renderer_base $output
     * @return array
     */
    public function export_for_template(\renderer_base $output) {
        $this->get_model()->require_finalised();

        $model = $this->get_model();
        $name = $this->get_name();

        $result = array(
            'form_item_template' => 'totara_form/element_editor',
            'name__' . $this->get_name() => true,
            'name' => $this->get_name(),
            'id' => $this->get_id(),
            'label' => (string)$this->label,
            'frozen' => $this->is_frozen(),
            'amdmodule' => 'totara_form/form_element_editor',
        );

        $value = $this->get_field_value();
        $text = isset($value['text']) ? $value['text'] : '';

        // If format is not included in current data then force it to be always HTML.
        $strformats = format_text_menu();
        if ($model->get_current_data($name . 'format')) {
            $result['fixedformat'] = false;
            $format = null; // Value null means autoselect based on user editor preference.
            if (array_key_exists('format', $value)) {
                if (isset($strformats[$value['format']])) {
                    $format = $value['format'];
                }
            }
        } else {
            $result['fixedformat'] = true;
            $format = FORMAT_HTML;
        }

        if ($this->get_current_file_area()) {
            if (empty($value['itemid'])) {
                $draftitemid = (int)file_get_unused_draft_itemid();
                $text = file_area::rewrite_links_to_draftarea($text, $draftitemid);
            } else {
                $draftitemid = (int)$value['itemid'];
            }
        } else {
            $draftitemid = false;
        }

        $editoroptions = $this->get_editor_options($draftitemid);

        /** @var \texteditor $editor */
        $editor = editors_get_preferred_editor($format);
        $result['formats'] = array();
        if ($result['fixedformat']) {
            $result['formats'][] = array('value' => $format, 'text' => $strformats[$format], 'selected' => true);
        } else {
            $formats = $editor->get_supported_formats();
            foreach ($formats as $fid) {
                if ($format === null) {
                    // Pick the first one if not set yet.
                    $format = $fid;
                }
                $result['formats'][] = array('value' => $fid, 'text' => $strformats[$fid], 'selected' => ($format === $fid));
            }
        }

        // Get file picker information.
        $fpoptions = array();
        $fptemplates = array();
        if ($editoroptions['maxfiles'] != 0 ) {
            $args = new \stdClass();
            // Need these three to filter repositories list.
            $args->return_types = $editoroptions['return_types'];
            $args->context = $editoroptions['context'];
            $args->env = 'filepicker';
            $args->disable_types = array();

            // Advimage plugin.
            $args->accepted_types = array('web_image');
            $image_options = initialise_filepicker($args, true);
            $image_options->context = $editoroptions['context'];
            $image_options->client_id = str_replace('.', '', uniqid('', true));
            $image_options->maxbytes = $editoroptions['maxbytes'];
            $image_options->areamaxbytes = $editoroptions['areamaxbytes'];
            $image_options->env = 'editor';
            $image_options->itemid = $draftitemid;

            // Moodlemedia plugin.
            $args->accepted_types = array('video', 'audio');
            $media_options = initialise_filepicker($args, true);
            $media_options->context = $editoroptions['context'];;
            $media_options->client_id = str_replace('.', '', uniqid('', true));
            $media_options->maxbytes  = $editoroptions['maxbytes'];
            $media_options->areamaxbytes  = $editoroptions['areamaxbytes'];
            $media_options->env = 'editor';
            $media_options->itemid = $draftitemid;

            // Advlink plugin.
            $args->accepted_types = '*';
            $link_options = initialise_filepicker($args, true);
            $link_options->context = $editoroptions['context'];;
            $link_options->client_id = str_replace('.', '', uniqid('', true));
            $link_options->maxbytes  = $editoroptions['maxbytes'];
            $link_options->areamaxbytes  = $editoroptions['areamaxbytes'];
            $link_options->env = 'editor';
            $link_options->itemid = $draftitemid;

            $fptemplates = array_merge($image_options->fptemplates, $media_options->fptemplates, $link_options->fptemplates);
            unset($image_options->fptemplates);
            unset($media_options->fptemplates);
            unset($link_options->fptemplates);

            $fpoptions['image'] = $image_options;
            $fpoptions['media'] = $media_options;
            $fpoptions['link'] = $link_options;
        }

        $attributes = array();
        $attributes['text'] = $text;
        $attributes['format'] = $format;
        $attributes['itemid'] = $draftitemid;
        $attributes['required'] = $this->get_attribute('required');
        $attributes['rows'] = $this->get_attribute('rows');
        $attributes['cols'] = $this->get_attribute('cols');
        $this->set_attribute_template_data($result, $attributes);

        $editor->totara_form_use_editor($result, $editoroptions, $fpoptions, $fptemplates);

        // Add errors if found, tweak attributes by validators.
        $this->set_validator_template_data($result, $output);

        // Add help button data.
        $this->set_help_template_data($result, $output);

        return $result;
    }

    /**
     * Returns maximum file size.
     *
     * @return int size in bytes, -1 means unlimited
     */
    public function get_maxbytes() {
        global $CFG, $DB;

        $maxbytes = (int)$this->get_attribute('maxbytes');
        $context = $this->get_attribute('context');
        if (!$context) {
            $context = $this->get_model()->get_default_context();
        }
        $coursemaxbytes = 0;
        $coursecontext = $context->get_course_context(false);
        if ($coursecontext) {
            $coursemaxbytes = (int)$DB->get_field('course', 'maxbytes', array('id' => $coursecontext->instanceid));
        }

        return (int)get_user_max_upload_file_size($context, $CFG->maxbytes, $coursemaxbytes, $maxbytes);
    }

    /**
     * Returns editor options.
     *
     * @param int|false $draftitemid
     * @return array
     */
    protected function get_editor_options($draftitemid) {
        $attributes = $this->get_attributes();
        $options = new \stdClass();

        if ($draftitemid === false) {
            $options->maxfiles = 0;
            $options->enable_filemanagement = false;
        } else {
            $options->maxfiles = (int)$attributes['maxfiles'];
            $options->enable_filemanagement = true;
        }
        $options->areamaxbytes = (int)$attributes['areamaxbytes'];
        $options->maxbytes = (int)$attributes['maxbytes'];
        $options->subdirs = (int)$attributes['subdirs'];

        if (!empty($attributes['context'])) {
            $options->context = $attributes['context'];
        } else {
            $options->context = $this->get_model()->get_default_context();
        }

        $options->noclean = (int)(bool)$attributes['noclean'];
        $options->trusttext = 0;
        $options->trusted = false;

        $options->return_types = (int)$attributes['return_types'];

        return (array)$options;
    }

    /**
     * Returns current data.
     *
     * @return array
     */
    protected function get_current_data() {
        $model = $this->get_model();
        $name = $this->get_name();

        $current = $model->get_current_data($name);
        if (!$current) {
            $current = array($name => null);
        }
        $format = $model->get_current_data($name . 'format');
        if ($format) {
            $current[$name . 'format'] = $format[$name . 'format'];
        }
        return $current;
    }

    /**
     * Get the current file area info class if available.
     *
     * @return file_area|null
     */
    protected function get_current_file_area() {
        $model = $this->get_model();
        $name = $this->get_name();

        $current = $model->get_current_data($name . 'filearea');
        if ($current) {
            $current = $current[$name . 'filearea'];
            if ($current instanceof file_area) {
                return $current;
            }
            debugging('Current filearead must be instance of \totara_form\file_area', DEBUG_DEVELOPER);
        }
        return null;
    }

    /**
     * Get the values of text editor element.
     *
     * NOTE: URls are not rewritten because we may not know the draftitemid yet.
     *
     * @return array
     */
    public function get_field_value() {
        $model = $this->get_model();
        $name = $this->get_name();

        if ($this->is_frozen()) {
            $current = $this->get_current_data();
            $result = array();
            $result['text'] = (string)$current[$name];
            if (array_key_exists($name . 'format', $current)) {
                $result['format'] = $current[$name . 'format'];
            }
            return $result;
        }

        if ($model->is_form_submitted()) {
            $data = $model->get_raw_post_data($name);
            if (isset($data['text'])) {
                return $data;
            }
        }

        $current = $this->get_current_data();
        $result = array();
        $result['text'] = (string)$current[$name];
        if (array_key_exists($name . 'format', $current)) {
            $result['format'] = $current[$name . 'format'];
        }
        if ($this->get_current_file_area()) {
            $result['itemid'] = null;
        }

        return $result;
    }
}
