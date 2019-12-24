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
    totara_form\form\validator\element_filepicker,
    totara_form\model;

/**
 * File picker element.
 *
 * This element is designed for uploading of a single file.
 *
 * NOTE: It is intentionally not possible to edit existing file with
 *       element, use 'filemanager' element with one file limit instead if necessary.
 *       References and external files are not allowed, we need the file content
 *       to be available.
 *
 * @package   totara_form
 * @copyright 2016 Totara Learning Solutions Ltd {@link http://www.totaralms.com/}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Petr Skoda <petr.skoda@totaralms.com>
 */
class filepicker extends element {
    /**
     * File picker constructor.
     *
     * @throws \coding_exception if a guest attempts to upload a file.
     * @param string $name
     * @param string $label
     * @param array $options optional filepicker options (these can be accessed as attributes)
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
            'accept' => null, // Comma separated - see http://www.w3schools.com/tags/att_input_accept.asp.
            'maxbytes' => null,
            'required' => false,
            // The following just help with repository selection and defaults.
            'context' => null,
            'disable_types' => null,
        );

        $this->set_attributes((array)$options);

        // Add validators.
        $this->add_validator(new attribute_required());
        $this->add_validator(new element_filepicker());
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
        // Do not return draftitemid here, this is not intended for editing of existing areas or files!
        return array();
    }

    /**
     * Get submitted draft files without validation.
     *
     * NOTE: form must be already finalised.
     *
     * @return array
     */
    public function get_files() {
        $name = $this->get_name();
        $model = $this->get_model();

        if ($this->is_frozen()) {
            return array($name => array());
        }

        $data = $model->get_raw_post_data($name);
        if ($data === null or is_array($data)) {
            // No value in _POST or invalid value format, most likely disabled element.
            return array($name => array());
        }

        if ($file = $this->get_draft_file($data)) {
            return array($name => array($file));
        }

        return array($name => array());
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
            'form_item_template' => 'totara_form/element_filepicker',
            'name__' . $this->get_name() => true,
            'name' => $this->get_name(),
            'id' => $this->get_id(),
            'label' => (string)$this->label,
            'frozen' => $frozen,
            'amdmodule' => 'totara_form/form_element_filepicker',
        );

        $draftitemid = $this->get_field_value();

        $attributes = $this->get_attributes();
        unset($attributes['context']);
        unset($attributes['disable_types']);
        unset($attributes['maxbytes']);

        if (!$frozen) {
            if ($draftitemid === null) {
                $draftitemid = file_get_unused_draft_itemid();
            }
            $fpoptions = $this->get_fp_options($draftitemid);
            $attributes['value'] = (string)$draftitemid;
            $attributes['maxbytes'] = $maxbytes;
            $attributes['fpoptions'] = json_encode($fpoptions);
            $attributes['displaymaxsize'] = ($maxbytes == -1) ? false : display_size($maxbytes);
            if ($attributes['displaymaxsize']) {
                $attributes['displaymaxsize_string'] = get_string('maxfilesize', 'core', s($attributes['displaymaxsize']));
            }
            $attributes['currentfile'] = $fpoptions->currentfile;
            $attributes['client_id'] = $fpoptions->client_id;
        }

        $this->set_attribute_template_data($result, $attributes);

        // Add errors if found, tweak attributes by validators.
        $this->set_validator_template_data($result, $output);

        // Add help button data.
        $this->set_help_template_data($result, $output);

        return $result;
    }

    /**
     * Get the value of text input element.
     *
     * @return string
     */
    public function get_field_value() {
        $model = $this->get_model();
        $name = $this->get_name();

        if ($this->is_frozen()) {
            return null;
        }

        $data = $model->get_raw_post_data($name);
        if ($data and is_number($data)) {
            return $data;
        }

        // No current data supported here,
        // we need to prevent devs from editing current files!
        return null;
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
     * Get weird file picker parameters in magic format.
     *
     * @param int $draftitemid
     * @return \stdClass
     */
    protected function get_fp_options($draftitemid) {
        $attributes = $this->get_attributes();

        $options = new \stdClass();
        $options->maxbytes = $this->get_maxbytes();
        $options->maxfiles = 1;
        $options->itemid = $draftitemid;
        $options->subdirs = 0;
        $options->client_id = str_replace('.', '', uniqid('', true));
        $options->accepted_types = file_area::accept_attribute_to_accepted_types($attributes['accept']);
        $options->return_types = FILE_INTERNAL;
        $options->disable_types = isset($attributes['disable_types']) ? $attributes['disable_types'] : array();
        if (isset($attributes['context'])) {
            $options->context = $attributes['context'];
        } else {
            $options->context = $this->get_model()->get_default_context();
        }

        $options->currentfile = '';
        if ($file = $this->get_draft_file($draftitemid)) {
            $options->currentfile = \html_writer::link(\moodle_url::make_draftfile_url($file->get_itemid(), $file->get_filepath(), $file->get_filename()), $file->get_filename());
        }

        return (object)array_merge((array)$options, (array)initialise_filepicker($options, true));
    }

    /**
     * Returns the picked file.
     *
     * @param int $draftitemid
     * @return \stored_file|null
     */
    protected function get_draft_file($draftitemid) {
        global $USER;

        if (!$draftitemid) {
            return null;
        }

        /** @var \file_storage $fs */
        $fs = get_file_storage();
        $usercontext = \context_user::instance($USER->id);
        if ($files = $fs->get_directory_files($usercontext->id, 'user', 'draft', $draftitemid, '/', false, false, 'id DESC')) {
            $file = reset($files);
            return $file;
        }

        return null;
    }
}
