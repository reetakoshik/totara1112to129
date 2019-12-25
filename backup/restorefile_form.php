<?php

// This file is part of Moodle - http://moodle.org/
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
 * Import backup file form
 * @package   core_backup
 * @copyright 2010 Dongsheng Cai <dongsheng@moodle.com>
 * @author    Petr Skoda <petr.skoda@totaralearning.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once($CFG->libdir.'/formslib.php');

class course_restore_form extends moodleform {
    /**
     * Define the form.
     */
    public function definition() {
        $mform = $this->_form;
        $contextid = $this->_customdata['contextid'];
        $mform->addElement('hidden', 'contextid', $contextid);
        $mform->setType('contextid', PARAM_INT);
        $mform->addElement('filepicker', 'backupfile', get_string('files'), null, array('accepted_types' => array('.mbz', '.zip', '.imscc')));
        $mform->addRule('backupfile', get_string('required'), 'required');
        $this->add_action_buttons(false, get_string('restore'));
    }

    /**
     * Validate form.
     *
     * @param array $data
     * @param array $files
     * @return array errors
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        $contextid = $this->_customdata['contextid'];
        $context = context::instance_by_id($contextid);

        $file = $this->get_backup_file($data['backupfile']);
        if ($file) {
            if (!has_capability('moodle/restore:restoreuntrusted', $context)) {
                if (!backup_helper::is_trusted_backup($file)) {
                    $errors['backupfile'] = get_string('erroruntrustedrestore', 'backup');
                }
            }
        } else {
            $errors['backupfile'] = get_string('required');
        }

        return $errors;
    }

    /**
     * Returns backup file.
     *
     * NOTE: this method does not check submission
     *
     * @param int $draftid
     * @return stored_file|null
     */
    public function get_backup_file($draftid) {
        global $USER;
        if (!$draftid) {
            return null;
        }
        $usercontext = context_user::instance($USER->id);
        $fs = get_file_storage();
        $files = $fs->get_area_files($usercontext->id, 'user' ,'draft', $draftid, 'id DESC', false);
        if (!$files) {
            return null;
        }
        return reset($files);
    }
}
