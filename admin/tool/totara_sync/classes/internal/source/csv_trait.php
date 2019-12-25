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
     * Adds the details fields to the csv settings form. This includes a static element that shows
     * the expected structure of the csv file as well as fields for encoding and delimiter.
     *
     * @param \MoodleQuickForm $mform
     */
    protected function config_form_add_csv_details($mform) {
        global $CFG, $OUTPUT;

        // Add some source file details
        $mform->addElement('header', 'fileheader', get_string('filedetails', 'tool_totara_sync'));
        $mform->setExpanded('fileheader');
        if (get_config('totara_sync', 'fileaccess') == FILE_ACCESS_DIRECTORY) {
            $mform->addElement('static', 'nameandloc', get_string('nameandloc', 'tool_totara_sync'),
                \html_writer::tag('strong', $this->get_filepath()));
        } else {
            $link = "{$CFG->wwwroot}/admin/tool/totara_sync/admin/uploadsourcefiles.php";
            $mform->addElement('static', 'uploadfilelink', get_string('uploadfilelink', 'tool_totara_sync', $link));
        }

        $encodings = \core_text::get_encodings();
        $mform->addElement('select', 'csvjobassignmentencoding', get_string('csvencoding', 'tool_totara_sync'), $encodings);
        $mform->setType('csvjobassignmentencoding', PARAM_ALPHANUMEXT);
        $default = $this->get_config('csvjobassignmentencoding');
        $default = (!empty($default) ? $default : 'UTF-8');
        $mform->setDefault('csvjobassignmentencoding', $default);

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
        $this->set_config('csvjobassignmentencoding', $data->{'csvjobassignmentencoding'});
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
        $fieldid = get_config('totara_sync', 'sync_jobassignment_itemid');

        // Check the file exist
        if (!$fs->file_exists($systemcontext->id, 'totara_sync', 'jobassignment', $fieldid, '/', '')) {
            throw new \totara_sync_exception($this->get_element_name(), 'populatesynctablecsv', 'nofileuploaded', $this->get_element_name(), null, 'warn');
        }

        // Get the file
        $fsfiles = $fs->get_area_files($systemcontext->id, 'totara_sync', 'jobassignment', $fieldid, 'id DESC', false);
        $fsfile = reset($fsfiles);

        // Set up the temp dir
        $tempdir = $CFG->tempdir . '/totarasync/csv';
        check_dir_exists($tempdir, true, true);

        // Create temporary file (so we know the filepath)
        $fsfile->copy_content_to($tempdir.'/jobassignment.php');
        $itemid = $fsfile->get_itemid();
        $fs->delete_area_files($systemcontext->id, 'totara_sync', 'jobassignment', $itemid);
        $storefilepath = $tempdir.'/jobassignment.php';

        return $storefilepath;
    }
}