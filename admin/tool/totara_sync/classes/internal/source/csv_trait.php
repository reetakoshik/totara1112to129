<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2017 onwards Totara Learning Solutions LTD
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
 * @author Brendan Cox <brendan.cox@totaralearning.com>
 * @package tool_totara_sync
 */

namespace tool_totara_sync\internal\source;

defined('MOODLE_INTERNAL') || die();

/**
 * Trait csv_trait
 *
 * Can be used by a source to add functionality common to those sources that use csv imports.
 *
 * @package tool_totara_sync
 */
trait csv_trait {

    /**
     * Holds data in csv string that can be accessed from memory, rather than reading from a file.
     *
     * Intended for testing.
     *
     * The data is not directly read from this variable, but is inserted into a php stream that can
     * be accessed from php's file functions.
     *
     * @var string csv string of data
     */
    protected $csv_in_memory = null;

    /**
     * Stores the path to a temp file that must be cleaned up after processing.
     *
     * @var string file path
     */
    private $tempfilepath = null;

    /**
     * Adds the details fields to the csv settings form. This includes a static element that shows
     * the expected structure of the csv file as well as fields for encoding and delimiter.
     *
     * @param \MoodleQuickForm $mform
     */
    protected function config_form_add_csv_details($mform) {
        global $CFG;

        // Add some source file details
        $mform->addElement('header', 'fileheader', get_string('filedetails', 'tool_totara_sync'));
        $mform->setExpanded('fileheader');

        try {
            if ($this->get_element()->get_fileaccess() == FILE_ACCESS_DIRECTORY) {
                $mform->addElement('static', 'nameandloc', get_string('nameandloc', 'tool_totara_sync'),
                    \html_writer::tag('strong', $this->get_filepath()));
            } else {
                $link = "{$CFG->wwwroot}/admin/tool/totara_sync/admin/uploadsourcefiles.php";
                $mform->addElement('static', 'uploadfilelink', get_string('uploadfilelink', 'tool_totara_sync', $link));
            }
        } catch (\totara_sync_exception $e) {
            $mform->addElement('static', 'configurefileaccess', '', get_string('configurefileaccess', 'tool_totara_sync'));
        }

        $encodings = \core_text::get_encodings();
        $encodingconfig = 'csv' . $this->get_element_name() . 'encoding';
        $mform->addElement('select', $encodingconfig, get_string('csvencoding', 'tool_totara_sync'), $encodings);
        $mform->setType($encodingconfig, PARAM_ALPHANUMEXT);
        $default = $this->get_config($encodingconfig);
        $default = (!empty($default) ? $default : 'UTF-8');
        $mform->setDefault($encodingconfig, $default);

        $delimiteroptions = array(
            ',' => get_string('comma', 'tool_totara_sync'),
            ';' => get_string('semicolon', 'tool_totara_sync'),
            ':' => get_string('colon', 'tool_totara_sync'),
            '\t' => get_string('tab', 'tool_totara_sync'),
            '|' => get_string('pipe', 'tool_totara_sync')
        );

        $mform->addElement('select', 'delimiter', get_string('delimiter', 'tool_totara_sync'), $delimiteroptions);
        $default = $this->config->delimiter;
        if (empty($default)) {
            $default = ',';
        }
        $mform->setDefault('delimiter', $default);
    }

    /**
     * Saves data entered into fields that were created by the config_form_add_csv_details method.
     *
     * @param \stdClass $data
     */
    protected function config_save_csv_file_details($data) {
        // Make sure we use a tab character for the delimiter, if a tab is selected.
        $this->set_config('delimiter', $data->{'delimiter'} == '\t' ? "\t" : $data->{'delimiter'});
        $encodingconfig = 'csv' . $this->get_element_name() . 'encoding';
        $this->set_config($encodingconfig, $data->{$encodingconfig});
    }

    /**
     * Copies a csv file from the place it was uploaded to, when uploaded to a custom server directory,
     * to where it will be used for the import.
     *
     * @return string
     * @throws \totara_sync_exception
     */
    protected function copy_csv_file_from_directory() {
        if (!$this->filesdir) {
            throw new \totara_sync_exception($this->get_element_name(), 'populatesynctablecsv', 'nofilesdir');
        }
        $filepath = $this->get_filepath();
        if (!file_exists($filepath)) {
            throw new \totara_sync_exception($this->get_element_name(), 'populatesynctablecsv', 'nofiletosync', $filepath, null, 'warn');
        }
        $filemd5 = md5_file($filepath);
        while (true) {
            // Ensure file is not currently being written to
            sleep(2);
            $newmd5 = md5_file($filepath);
            if ($filemd5 != $newmd5) {
                $filemd5 = $newmd5;
            } else {
                break;
            }
        }

        // See if file is readable
        if (!$file = is_readable($filepath)) {
            throw new \totara_sync_exception($this->get_element_name(), 'populatesynctablecsv', 'cannotreadx', $filepath);
        }

        // Move file to store folder
        $storedir = $this->filesdir.'/csv/store';
        if (!totara_sync_make_dirs($storedir)) {
            throw new \totara_sync_exception($this->get_element_name(), 'populatesynctablecsv', 'cannotcreatedirx', $storedir);
        }

        $storefilepath = $storedir . '/' . time() . '.' . basename($filepath);

        rename($filepath, $storefilepath);

        return $storefilepath;
    }

    /**
     * Copies a csv file from the place it was uploaded to, when uploaded via a form,
     * to where it will be used for the import.
     *
     * @return string
     * @throws \totara_sync_exception
     */
    protected function copy_csv_file_from_upload() {
        global $CFG;

        $fs = get_file_storage();
        $systemcontext = \context_system::instance();
        $fieldidconfig = 'sync_' . $this->get_element_name() . '_itemid';
        $fieldid = get_config('totara_sync', $fieldidconfig);

        // Check the file exist
        if (!$fs->file_exists($systemcontext->id, 'totara_sync', $this->get_element_name(), $fieldid, '/', '')) {
            throw new \totara_sync_exception($this->get_element_name(), 'populatesynctablecsv', 'nofileuploaded', $this->get_element_name(), null, 'warn');
        }

        // Get the file
        $fsfiles = $fs->get_area_files($systemcontext->id, 'totara_sync', $this->get_element_name(), $fieldid, 'id DESC', false);
        $fsfile = reset($fsfiles);

        // Set up the temp dir
        $tempdir = $CFG->tempdir . '/totarasync/csv';
        check_dir_exists($tempdir, true, true);

        // Create temporary file (so we know the filepath)
        $storefilepath = $tempdir. '/' . $this->get_element_name() . '.csv';
        $fsfile->copy_content_to($storefilepath);
        $itemid = $fsfile->get_itemid();
        $fs->delete_area_files($systemcontext->id, 'totara_sync', $this->get_element_name(), $itemid);

        return $storefilepath;
    }

    /**
     * Sets csv data that will be accessed from memory rather than having to access a file.
     *
     * Intended for testing.
     *
     * @param string $contents
     */
    public function set_csv_in_memory($contents) {
        $this->csv_in_memory = $contents;
    }

    /**
     * Uses php's file API to access csv data saved in memory.
     *
     * @return bool|resource
     */
    protected function get_csv_from_memory() {
        $file = fopen('php://temp/', 'r+');
        fputs($file, $this->csv_in_memory);
        rewind($file);

        return $file;
    }

    /**
     * Gets a file resource which provides access to the csv we wish to process.
     *
     * Considers the file access settings in order to do so.
     *
     * @return bool|resource
     */
    protected function open_csv_file() {
        $fileaccess = $this->get_element()->get_fileaccess();

        switch($fileaccess) {
            case FILE_ACCESS_DIRECTORY:
                $storefilepath = $this->copy_csv_file_from_directory();
                break;
            case FILE_ACCESS_UPLOAD:
                $storefilepath = $this->copy_csv_file_from_upload();
                break;
            case TOTARA_SYNC_FILE_ACCESS_MEMORY:
                // We support just having the file contents in memory for unit tests.
                // Be aware that we miss out of totara_sync_clean_csvfile by returning here. If you are
                // testing that code, you'll need to the full file operations in your test.
                return $this->get_csv_from_memory();
                break;
            default:
                throw new \totara_sync_exception($this->get_element_name(), 'populatesynctablecsv', 'invalidfileaccess', $fileaccess);
        }

        $encodingconfig = 'csv' . $this->get_element_name() . 'encoding';
        $encoding = $this->get_config($encodingconfig);
        $storefilepath = totara_sync_clean_csvfile($storefilepath, $encoding, $fileaccess, $this->get_element_name());

        // Open file from store for processing.
        if (!$file = fopen($storefilepath, 'r')) {
            throw new \totara_sync_exception($this->get_element_name(), 'populatesynctablecsv', 'cannotopenx', $storefilepath);
        }

        if ($fileaccess == FILE_ACCESS_UPLOAD) {
            // If this was an uploaded file. We need to make sure it gets cleaned up.
            $this->tempfilepath = $storefilepath;
        }

        return $file;
    }

    /**
     * Close the csv file resource. Clean up the temp file if necessary.
     *
     * @param resource $file
     */
    protected function close_csv_file($file) {
        fclose($file);
        // Done, clean up the file(s)
        if (isset($this->tempfilepath)) {
            unlink($this->tempfilepath);
        }
    }

    /**
     * @return \totara_sync_element
     */
    abstract public function get_element();
}