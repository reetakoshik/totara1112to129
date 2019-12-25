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

namespace totara_form;

/**
 * Main Totara form class.
 *
 * NOTE: the 'final' keyword is used mainly to prevent developers breaking ajax form submission support.
 *
 * @package   totara_form
 * @copyright 2016 Totara Learning Solutions Ltd {@link http://www.totaralms.com/}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Petr Skoda <petr.skoda@totaralms.com>
 */
abstract class form implements \templatable {
    /**
     * This is the description of the form structure
     * and it also contains all data - current and submitted.
     *
     * @var model $model form model
     */
    protected $model;

    /**
     * These are extra custom parameters for the form,
     * the difference from $currentdata is that they are
     * not stored in database.
     *
     * @var array $parameters custom form parameters
     */
    protected $parameters;

    /**
     * This indicates if we already verified the submission for validity,
     * the reason is we should run the validation only once.
     *  - null means not processed yet
     *  - true means form submission was valid and we have data
     *  - false means we did not accept form submission and get_data() is empty and no files.
     *
     * @var bool $isvalidsubmission is this a valid form submission?
     */
    private $isvalidsubmission;

    /**
     * This is the data obtained from valid submission.
     * It is cached here for performance reasons.
     *
     * @var array $data array of values indexed by element names
     */
    private $data;

    /**
     * This is the list of submitted draft files for
     * each element. It is cached here for performance reasons.
     *
     * @var \stored_file[][] $files array of draft file lists indexed by element names
     */
    private $files;

    /**
     * Returns class responsible for form handling.
     * This is intended especially for ajax processing.
     *
     * @return null|form_controller
     */
    public static function get_form_controller() {
        return null;
    }

    /**
     * Form constructor.
     *
     * @param array|\stdClass $currentdata
     * @param array|\stdClass $parameters custom form parameters
     * @param string $idsuffix identifies form instance submission and used as suffix for form and element ids
     */
    final public function __construct($currentdata = null, $parameters = null, $idsuffix = '') {
        // Make sure there are no legacy methods that are not compatible with new forms.
        $this->prevent_legacy_methods();

        $this->parameters = (array)$parameters;
        $this->model = new model($this, (array)$currentdata, $_POST, $idsuffix);
        $this->definition();
        $this->model->finalise(); // This must be the ONLY place where we finalise the model!!!
    }

    /**
     * Returns initial form parameters.
     *
     * This is intended for further customisation of model structure.
     *
     * @return array
     */
    public function get_parameters() {
        return $this->parameters;
    }

    /**
     * Form definition.
     *
     * @return void
     */
    protected abstract function definition();

    /**
     * Dummy stub method - override if you needed to perform some extra validation.
     * If there are errors return array of errors ("fieldname"=>"error message"),
     * otherwise true if ok.
     *
     * Server side rules do not work for uploaded files, implement serverside rules here if needed.
     *
     * NOTE: override if necessary
     *
     * @param array $data array of ("fieldname"=>value) of submitted data
     * @param array $files array of ("fieldname"=>stored_file[]) of submitted files
     * @return array of "element_name"=>"error_description" if there are errors,
     *         or an empty array if everything is OK
     */
    protected function validation(array $data, array $files) {
        return array();
    }

    /**
     * Get form submission URL.
     *
     * NOTE: override if necessary
     *
     * @throws \coding_exception If the AJAC script returns a coding action outside of the wwwroot.
     * @return \moodle_url
     */
    public function get_action_url() {
        global $CFG, $FULLME;

        if (AJAX_SCRIPT) {
            $action = optional_param('___tf_original_action', false, PARAM_URL);
            if ($action === false) {
                // If the original action URL was not provided then we will use ajax.php.
                return new \moodle_url(('/totara/form/ajax.php'));
            }
            $stripped_action = preg_replace('#^https?://#', '', $action);
            $stripped_wwwroot = preg_replace('#^https?://#', '', $CFG->wwwroot);
            if (strpos($stripped_action, $stripped_wwwroot) !== 0) {
                throw new \coding_exception('Totara form ajax script returns an original action that was outside wwwroot');
            }
            return new \moodle_url(strip_querystring($action));
        }

        return new \moodle_url(strip_querystring($FULLME));
    }

    /**
     * Return true if a cancel button has been pressed resulting in the form being submitted.
     *
     * @throws \coding_exception if the form has been finalised and the structure can no longer change.
     * @return bool true if a cancel button has been pressed
     */
    public function is_cancelled() {
        if (!$this->model->is_finalised()) {
            throw new \coding_exception('is_cancelled() cannot be used before the model is finalised');
        }

        if (!$this->model->is_form_submitted()) {
            return false;
        }

        return $this->model->is_form_cancelled();
    }

    /**
     * Checks if "no submit" button was pressed. Other elements might also prevent form submission.
     *
     * @throws \coding_exception if the form has been finalised and the structure can no longer change.
     * NOTE: Cancel button has separate method is_cancelled().
     *
     * @return bool
     */
    public function is_reloaded() {
        // No validation hacking allowed!
        if (!$this->model->is_finalised()) {
            throw new \coding_exception('is_reloaded() cannot be used before the model is finalised');
        }

        if (!$this->model->is_form_submitted()) {
            return false;
        }

        if ($this->model->is_form_cancelled()) {
            return false;
        }

        return $this->model->is_form_reloaded();
    }

    /**
     * Was the form submitted and data validated without errors?
     *
     * @throws \coding_exception if the form has been finalised and the structure can no longer change.
     * @return bool
     */
    final protected function is_valid_submission() {
        // No validation hacking allowed!
        if (!$this->model->is_finalised()) {
            throw new \coding_exception('is_valid_submission() cannot be used before the model is finalised');
        }

        if (isset($this->isvalidsubmission)) {
            // The validation must be done only once!
            return $this->isvalidsubmission;
        }

        if (!$this->model->is_form_submitted()) {
            $this->isvalidsubmission = false;
            return false;
        }

        if ($this->model->is_form_cancelled()) {
            $this->isvalidsubmission = false;
            return false;
        }

        if ($this->model->is_form_reloaded()) {
            $this->isvalidsubmission = false;
            return false;
        }

        // This must be the ONLY place where we do model validation!!!
        $this->model->validate();

        $this->data = $this->model->get_data();
        $this->files = $this->model->get_files();

        $errors = $this->validation($this->data, $this->files);
        if (!is_array($errors)) {
            throw new \coding_exception('Form validation() must return array');
        }
        foreach ($errors as $elname => $error) {
            // Look for element instances first because they must have unique names.
            $item = $this->model->find($elname, 'get_name', 'totara_form\item');
            if (!$item) {
                // If no idem found, add the error to the model, it must not be lost!
                $item = $this->model;
            }
            $error = (array)$error;
            foreach ($error as $e) {
                $item->add_error($e);
            }
        }

        if ($this->model->is_valid()) {
            $this->isvalidsubmission = true;
        } else {
            $this->data = null;
            $this->isvalidsubmission = false;
        }

        return $this->isvalidsubmission;
    }

    /**
     * Return submitted data if properly submitted or returns NULL if the form was not submitted properly.
     *
     * @return \stdClass submitted data; NULL if not valid or not submitted or cancelled
     */
    final public function get_data() {
        // No data hacking allowed!
        if (!$this->is_valid_submission()) {
            return null;
        }
        return (object)$this->data;
    }

    /**
     * Returns submitted draft files or NULL if the form was not submitted properly.
     *
     * @return \stdClass submitted files; NULL if not valid or not submitted or cancelled
     */
    final public function get_files() {
        // No data hacking allowed!
        if (!$this->is_valid_submission()) {
            return null;
        }
        return (object)$this->files;
    }

    /**
     * Save files using previously defined file_area from current data.
     *
     * The context and itemid parameters used use when adding new file areas where itemid
     * or context is not known before the form data is saved into database.
     *
     * @param string $elname
     * @param \context|null $context null means use current
     * @param int $itemid null means use current
     * @return bool success
     *
     */
    final public function update_file_area($elname, \context $context = null, $itemid = null) {
        $element = $this->model->find($elname, 'get_name', 'totara_form\item');
        if (!$element) {
            debugging("Element '$elname' does not exist in the form", DEBUG_DEVELOPER);
            return false;
        }

        if (!$this->is_valid_submission()) {
            return false;
        }

        if (!method_exists($element, 'update_file_area')) {
            debugging("Element '$elname' does not support updating of file area", DEBUG_DEVELOPER);
            return false;
        }

        return $element->update_file_area($context, $itemid);
    }

    /**
     * Get one draft file from element.
     *
     * @param string $elname key in the result of form::get_files()
     * @return false|\stored_file draft file
     */
    final protected function get_file($elname) {
        if (!$this->is_valid_submission()) {
            return false;
        }
        if (empty($this->files[$elname])) {
            return false;
        }

        foreach ($this->files[$elname] as $file) {
            if (!$file->is_directory()) {
                return $file;
            }
        }

        return false;
    }

    /**
     * Save file to standard filesystem
     *
     * @param string $elname name of element
     * @param string $pathname full path name of file
     * @param bool $override override file if exists
     * @return bool success
     */
    final public function save_file($elname, $pathname, $override = false) {
        if (!$file = $this->get_file($elname)) {
            return false;
        }

        if (file_exists($pathname)) {
            if (!$override) {
                return false;
            }
            @unlink($pathname);
        }

        return $file->copy_content_to($pathname);
    }


    /**
     * Returns a temporary request file.
     *
     * NOTE: the file is deleted automatically at the end of this request!
     *
     * @param string $elname name of the elmenet
     * @return string|bool either string or false
     */
    final public function save_temp_file($elname) {
        if (!$file = $this->get_file($elname)) {
            return false;
        }
        $path = make_request_directory() . '/' . $file->get_filename();
        $file->copy_content_to($path);
        if (file_exists($path)) {
            return $path;
        }
        return false;
    }

    /**
     * Save file to local filesystem pool
     *
     * @param string $elname name of element
     * @param int $newcontextid id of context
     * @param string $newcomponent name of the component
     * @param string $newfilearea name of file area
     * @param int $newitemid item id
     * @param string $newfilepath path of file where it get stored
     * @param string $newfilename use specified filename, if not specified name of uploaded file used
     * @param bool $overwrite overwrite file if exists
     * @param int $newuserid new userid if required
     * @return \stored_file|false if error; may throw exception if duplicate found
     */
    final public function save_stored_file($elname, $newcontextid, $newcomponent, $newfilearea, $newitemid, $newfilepath='/',
                              $newfilename=null, $overwrite=false, $newuserid=null) {
        global $CFG;
        require_once("$CFG->libdir/filelib.php");

        if (!$file = $this->get_file($elname)) {
            return false;
        }

        /** @var \file_storage $fs */
        $fs = get_file_storage();

        if ($overwrite) {
            if ($oldfile = $fs->get_file($newcontextid, $newcomponent, $newfilearea, $newitemid, $newfilepath, $newfilename)) {
                if (!$oldfile->delete()) {
                    return false;
                }
            }
        }

        $file_record = array(
            'contextid' => $newcontextid,
            'component' => $newcomponent,
            'filearea' => $newfilearea,
            'itemid' => $newitemid,
            'filepath' => $newfilepath,
            'filename' => $newfilename,
            'userid' => $newuserid);
        return $fs->create_file_from_storedfile($file_record, $file);
    }

    /**
     * Get content of uploaded file.
     *
     * @param string $elname name of file upload element
     * @return string|bool false in case of failure, string if ok
     */
    final public function get_file_content($elname) {
        if (!$file = $this->get_file($elname)) {
            return false;
        }
        return $file->get_content();
    }

    /**
     * Mustache template.
     *
     * NOTE: override if necessary
     *
     * @return string
     */
    public function get_template() {
        return 'totara_form/form';
    }

    /**
     * Get Mustache template data.
     *
     * @param \renderer_base $output
     * @return array
     */
    public function export_for_template(\renderer_base $output) {
        // Make sure we have everything initialised before rendering.
        $this->is_valid_submission();

        return $this->model->export_for_template($output);
    }

    /**
     * Renders the html form.
     *
     * Note that you can only output this rendered result once per page, as
     * it contains IDs which must be unique.
     *
     * @return string HTML code for the form
     */
    final public function render() {
        global $OUTPUT;
        return $OUTPUT->render_from_template($this->get_template(), $this->export_for_template($OUTPUT));
    }

    /**
     * Make sure there are no legacy methods that should have been removed.
     */
    protected function prevent_legacy_methods() {
        if (method_exists($this, 'display')) {
            debugging('form::display() needs to be removed, use rendered via "echo form::render()" instead', DEBUG_DEVELOPER);
            return;
        }
        if (method_exists($this, 'definition_after_data')) {
            debugging('form::definition_after_data() needs to be removed, move the code into form::definition() and use element::get_field_value()', DEBUG_DEVELOPER);
            return;
        }
        if (method_exists($this, 'set_data')) {
            debugging('form::set_data() needs to be removed, use $currentdata parameter in constructor instead', DEBUG_DEVELOPER);
            return;
        }
        if (method_exists($this, 'get_form_identifier')) {
            debugging('form::get_form_identifier() needs to be removed, use $idsuffix parameter in constructor instead', DEBUG_DEVELOPER);
            return;
        }
    }

    /**
     * Magic callbacks that help with conversion of legacy stuff.
     *
     * @throws \coding_exception If methods that exist on legacy forms are called where there is no equivilant.
     * @param string $name
     * @param array $arguments
     * @return mixed
     */
    public function __call($name , array $arguments) {
        // Let's provide some help with transition.
        if ($name === 'display') {
            debugging('form::display() is deprecated in Totara forms, use "echo form::render()" instead', DEBUG_DEVELOPER);
            echo $this->render();
            return null;
        }
        if ($name === 'add_action_buttons') {
            debugging('form::add_action_buttons() is deprecated in Totara forms, use "$this->model->add_action_buttons()" instead', DEBUG_DEVELOPER);
            return call_user_func_array(array($this->model, 'add_action_buttons'), $arguments);
        }
        if ($name === 'focus') {
            debugging('form::focus() is not available any more in Totara forms', DEBUG_DEVELOPER);
            return null;
        }
        if ($name === 'get_new_filename') {
            debugging('form::get_new_filename() is deprecated use form::get_file() instead', DEBUG_DEVELOPER);
            /** @var \stored_file $file */
            $file = $this->get_file($arguments[0]);
            if ($file) {
                return $file->get_filename();
            }
            return false;
        }

        // List of things that cannot be fixed.
        if ($name === 'no_submit_button_pressed') {
            throw new \coding_exception('Invalid form method call', 'form::no_submit_button_pressed() is not available any more, use form::is_reloaded() instead');
        }
        if ($name === 'get_submitted_data') {
            throw new \coding_exception('Invalid form method call', 'form::get_submitted_data() is not available any more, use element::get_field_value() in form definition instead');
        }
        if ($name === 'repeat_elements') {
            throw new \coding_exception('Invalid form method call', 'form::repeat_elements() is not supported any more, use your own PHP code to construct repeated elements in form definition');
        }
        if ($name === 'add_checkbox_controller') {
            throw new \coding_exception('Invalid form method call', 'form::add_checkbox_controller() is not available any more');
        }
        if ($name === 'save_files') {
            throw new \coding_exception('Invalid form method call', 'form::save_files() is not available any more');
        }
        if ($name === 'get_form_identifier') {
            throw new \coding_exception('Invalid form method call', 'form::get_form_identifier() is not available any more, use $idsuffix instead');
        }
        if ($name === 'set_data') {
            throw new \coding_exception('Invalid form method call', 'form::set_data() is not available any more, current data must be used in form constructor instead');
        }
        if ($name === 'definition_after_data') {
            throw new \coding_exception('Invalid form method call', 'form::definition_after_data() is not available any more, form::definition() has all data, so use it instead');
        }

        throw new \coding_exception('Invalid form method call', "method '$name' does not exit in class " . get_class($this));
    }
}
