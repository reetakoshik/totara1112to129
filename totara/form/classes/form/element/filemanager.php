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

use stored_file,
    totara_form\element,
    totara_form\file_area,
    totara_form\form\validator\attribute_required,
    totara_form\form\validator\element_filemanager,
    totara_form\item,
    totara_form\model;

/**
 * File manager element.
 *
 * @package   totara_form
 * @copyright 2016 Totara Learning Solutions Ltd {@link http://www.totaralms.com/}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Petr Skoda <petr.skoda@totaralms.com>
 */
class filemanager extends element {
    /** @var array $files the cache of get_files() result */
    private $files;

    /**
     * File manager constructor.
     *
     * @throws \coding_exception Guests cannot upload files.
     * @param string $name
     * @param string $label
     * @param array $options optional filemanager options (these can be accessed as attributes)
     */
    public function __construct($name, $label, array $options = null) {
        global $CFG;
        require_once($CFG->dirroot . '/lib/filelib.php');
        require_once($CFG->dirroot . '/repository/lib.php');

        if (!isloggedin() or isguestuser()) {
            throw new \coding_exception('Guests must not be allowed to upload any files!!!');
        }

        parent::__construct($name, $label);
        $this->attributes = array(
            'accept' => null,
            'areamaxbytes' => -1,
            'maxbytes' => null,
            'maxfiles' => -1,
            'required' => false,
            'return_types' => FILE_INTERNAL,
            'subdirs' => true,
            // The following just help with repository selection and defaults.
            'context' => null,
            'disable_types' => null,
        );

        $this->set_attributes((array)$options);

        // Add validators.
        $this->add_validator(new attribute_required());
        $this->add_validator(new element_filemanager());
    }

    /**
     * Called by parent before adding this element
     * or after removing element from parent.
     *
     * @param item $parent
     */
    public function set_parent(item $parent = null) {
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
        if ($name === 'accept') {
            // Convert everything to mimetypes only, null means everything.
            $value = file_area::normalise_accept_attribute($value, true);

        } else if ($name === 'maxfiles') {
            $value = isset($value) ? (int)$value : -1;

        } else if ($name === 'areamaxbytes') {
            $value = isset($value) ? (int)$value : -1;

        } else if ($name === 'subdirs') {
            $value = !empty($value);

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
        // No drafitemid here, developers need to store the results or use the get_files().
        return array();
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

        $name = $this->get_name();
        $model = $this->get_model();

        if ($this->is_frozen()) {
            $currentfilearea = $this->get_current_file_area();
            if (!$currentfilearea) {
                $this->files = array($name => null);
                return $this->files;
            }
            $draftid = $currentfilearea->create_draft_area();

        } else {
            $draftid = $model->get_raw_post_data($name);
            if (!is_number($draftid)) {
                // No value in _POST or invalid value format, this should not happen.
                $this->files = array($name => array());
                return $this->files;
            }
        }

        $fs = get_file_storage();
        $usercontext = \context_user::instance($USER->id);
        $files = $fs->get_area_files($usercontext->id, 'user', 'draft', $draftid, 'itemid, filepath, filename', $this->attributes['subdirs']);

        $this->files = array($name => array_values($files));
        return $this->files;
    }

    /**
     * Store the changed files.
     *
     * @throws \coding_exception if the filemanager does not know its own area.
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
     * @return bool result, may return false for invalid data or operator
     */
    public function compare_value($operator, $value2 = null, $finaldata = true) {
        if ($operator === model::OP_NOT_FILLED) {
            return !$this->compare_value(model::OP_FILLED);
        }
        if ($operator === model::OP_EMPTY) {
            return !$this->compare_value(model::OP_FILLED);
        }
        if ($operator !== model::OP_FILLED and $operator !== model::OP_NOT_EMPTY) {
            // Unsupported operator.
            return false;
        }
        if ($finaldata) {
            $files = $this->get_files();
            $name = $this->get_name();
            if (isset($files[$name])) {
                $files = $files[$name];
            } else {
                $files = array();
            }
        } else {
            // TODO TL-9417: deal with this situation.
            $files = array();
        }

        foreach ($files as $file) {
            /** @var \stored_file $file */
            if ($file->is_directory() and $file->get_filepath() === '/') {
                // Always ignore the root dir in case element returned it.
                continue;
            }
            if ($file->is_directory()) {
                // Ignore dirs.
                continue;
            }
            // File found, we have some data!
            return true;
        }

        return false;
    }

    /**
     * Get Mustache template data.
     *
     * @param \renderer_base $output
     * @return array
     */
    public function export_for_template(\renderer_base $output) {
        $this->get_model()->require_finalised();

        $frozen = $this->is_frozen();
        $maxbytes = $this->get_maxbytes();

        $result = array(
            'form_item_template' => 'totara_form/element_filemanager',
            'name__' . $this->get_name() => true,
            'name' => $this->get_name(),
            'id' => $this->get_id(),
            'label' => (string)$this->label,
            'frozen' => $frozen,
            'amdmodule' => 'totara_form/form_element_filemanager',
        );

        $attributes = $this->get_attributes();
        unset($attributes['maxbytes']);
        unset($attributes['areamaxbytes']);
        unset($attributes['subdirs']);
        unset($attributes['context']);
        unset($attributes['return_types']);
        unset($attributes['disable_types']);

        $draftitemid = $this->get_field_value();
        if ($draftitemid === null) {
            $draftitemid = file_get_unused_draft_itemid();
        }
        $fmoptions = $this->get_fm_options($draftitemid, true);

        $attributes['value'] = (string)$draftitemid;
        $attributes['maxbytes'] = $maxbytes;
        $attributes['fmoptions'] = json_encode($fmoptions);
        $attributes['client_id'] = $fmoptions->client_id;
        $attributes['restrictions'] = $fmoptions->restrictions;

        $this->set_attribute_template_data($result, $attributes);

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
     * Returns a file manager options object.
     *
     * @param int $draftitemid
     * @param bool $initfilepicker
     * @return \stdClass
     * @throws \coding_exception
     */
    protected function get_fm_options($draftitemid, $initfilepicker) {
        global $PAGE, $USER, $CFG;

        // NOTE: do not use form_filemanager class here because it would include the quickforms!

        $attributes = $this->get_attributes();

        /** @var \file_storage $fs */
        $fs = get_file_storage();
        $options = file_get_drafarea_files($draftitemid, '/');

        $options->frozen = $this->is_frozen();
        $options->maxbytes = $this->get_maxbytes();
        $options->areamaxbytes = $attributes['areamaxbytes'];
        $options->maxfiles = $attributes['maxfiles'];
        $options->itemid = $draftitemid;
        $options->subdirs = $attributes['subdirs'];
        $options->client_id = str_replace('.', '', uniqid('', true));
        $options->accepted_types = file_area::accept_attribute_to_accepted_types($attributes['accept']);
        $options->return_types = $attributes['return_types'];
        $options->disable_types = isset($attributes['disable_types']) ? $attributes['disable_types'] : array();
        $options->author = fullname($USER);
        if (isset($attributes['context'])) {
            $options->context = $attributes['context'];
        } else {
            $options->context = $this->get_model()->get_default_context();
        }
        $options->licenses = array();

        if (!empty($CFG->licenses)) {
            $array = explode(',', $CFG->licenses);
            foreach ($array as $license) {
                $l = new \stdClass();
                $l->shortname = $license;
                $l->fullname = get_string($license, 'license');
                $options->licenses[] = $l;
            }
        }
        if (!empty($CFG->sitedefaultlicense)) {
            $defaults['defaultlicense'] = $CFG->sitedefaultlicense;
        }

        // Calculate file count.
        $usercontext = \context_user::instance($USER->id);
        $files = $fs->get_area_files($usercontext->id, 'user', 'draft', $options->itemid, 'id', false);
        $filecount = count($files);
        $options->filecount = $filecount;

        // Create the restrictions markup.
        $strparam = array(
            'size' => $options->maxbytes,
            'attachments' => $options->maxfiles,
            'areasize' => display_size($options->areamaxbytes)
        );
        $hasmaxfiles = !empty($options->maxfiles) && $options->maxfiles > 0;
        $hasarealimit = !empty($options->areamaxbytes) && $options->areamaxbytes != -1;
        if ($hasmaxfiles && $hasarealimit) {
            $maxsize = get_string('maxsizeandattachmentsandareasize', 'moodle', $strparam);
        } else if ($hasmaxfiles) {
            $maxsize = get_string('maxsizeandattachments', 'moodle', $strparam);
        } else if ($hasarealimit) {
            $maxsize = get_string('maxsizeandareasize', 'moodle', $strparam);
        } else {
            $maxsize = get_string('maxfilesize', 'moodle', $options->maxbytes);
        }
        $options->restrictions = $maxsize;

        /** @var \core_files_renderer $fprenderer */
        $fprenderer = $PAGE->get_renderer('core', 'files');
        $options->fmtemplates = $fprenderer->filemanager_js_templates();

        if ($initfilepicker) {
            $params = new \stdClass();
            $params->accepted_types = $options->accepted_types;
            $params->return_types = $options->return_types;
            $params->context = $options->context;
            $params->env = 'filemanager';
            $params->disable_types = !empty($options->disable_types) ? $options->disable_types : array();
            $options->filepicker = initialise_filepicker($params, true);
        } else {
            $options->filepicker = null;
        }

        return $options;
    }

    /**
     * Get the current file area info class if available.
     *
     * @return file_area|null
     */
    protected function get_current_file_area() {
        $model = $this->get_model();
        $name = $this->get_name();

        $current = $model->get_current_data($name);
        if (isset($current[$name])) {
            $current = $current[$name];
            if ($current instanceof file_area) {
                return $current;
            }
        }
        return null;
    }

    /**
     * Get the value of text input element.
     *
     * @return int
     */
    public function get_field_value() {
        $model = $this->get_model();
        $name = $this->get_name();

        $data = $model->get_raw_post_data($name);
        if (is_number($data) and $data > 0) {
            return $data;
        }

        $currentfilearea = $this->get_current_file_area();
        if ($currentfilearea) {
            // This is our first time here, create new draft file area.
            return $currentfilearea->create_draft_area();
        }

        // No support for drafitemid in current files, devs must use the file_area instance!

        return null;
    }
}
