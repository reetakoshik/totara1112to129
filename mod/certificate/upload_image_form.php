<?php

// This file is part of the Certificate module for Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Handles uploading files
 *
 * @package    mod_certificate
 * @copyright  Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

require_once($CFG->libdir.'/formslib.php');
require_once($CFG->dirroot.'/mod/certificate/locallib.php');

class mod_certificate_upload_image_form extends moodleform {

    function definition() {
        global $CFG;

        $options = $this->_customdata['options'];
        $data = $this->_customdata['data'];
        $mform =& $this->_form;

        $mform->addElement('hidden', 'id', $data->id);
        $mform->setType('id', PARAM_INT);

        $mform->addElement('filemanager', 'border_filemanager', get_string('border', 'mod_certificate'), null, $options);
        $mform->addElement('filemanager', 'watermark_filemanager', get_string('watermark', 'mod_certificate'), null, $options);
        $mform->addElement('filemanager', 'seal_filemanager', get_string('seal', 'mod_certificate'), null, $options);
        $mform->addElement('filemanager', 'signature_filemanager', get_string('signature', 'mod_certificate'), null, $options);

        $this->add_action_buttons();
        $this->set_data($data);
    }
}
