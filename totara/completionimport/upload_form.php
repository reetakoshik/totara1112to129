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
 * @author Russell England <russell.england@catalyst-net.nz>
 * @package totara
 * @subpackage completionimport
 */
defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/formslib.php');
require_once($CFG->dirroot . '/totara/completionimport/lib.php');
require_once($CFG->libdir . '/csvlib.class.php');

class upload_form extends moodleform {
    public function definition() {
        global $DB, $CFG;
        $mform =& $this->_form;

        $data = $this->_customdata;

        if (($data->filesource == TCI_SOURCE_EXTERNAL) and empty($CFG->completionimportdir)) {
            // We need the config setting when using external files.
            return;
        }

        switch ($data->importname) {
            case 'course':
                $upload_label = 'choosecoursefile';
                $upload_field = 'course_uploadfile';
                break;
            case 'certification':
                $upload_label = 'choosecertificationfile';
                $upload_field = 'certification_uploadfile';
                break;
            default:
                $upload_label = 'choosefile';
                $upload_field = 'uploadfile';
        }

        $upload_label = get_string($upload_label, 'totara_completionimport');

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'filesource');
        $mform->setType('filesource', PARAM_INT);

        if ($data->filesource == TCI_SOURCE_EXTERNAL) {
            $sourcefilegroup = array();
            $stringbeginwith = '<p>' . get_string('sourcefile_beginwith', 'totara_completionimport', $CFG->completionimportdir) . '</p>';
            $sourcefilegroup[] = $mform->createElement('static', '', '', $stringbeginwith);
            $sourcefilegroup[] = $mform->createElement('text', 'sourcefile', '');
            $mform->setType('sourcefile', PARAM_PATH);

            $mform->addGroup($sourcefilegroup, 'sourcefilegrp', get_string('sourcefile', 'totara_completionimport'), array(''), false);
            $mform->addHelpButton('sourcefilegrp', 'sourcefile', 'totara_completionimport');
            $mform->addRule('sourcefilegrp', get_string('sourcefilerequired', 'totara_completionimport'), 'required');
        } else if ($data->filesource == TCI_SOURCE_UPLOAD) {
            $mform->addElement('filepicker',
                    $upload_field,
                    $upload_label,
                    null,
                    array('accepted_types' => array('csv')));
            $mform->addRule($upload_field, get_string('uploadfilerequired', 'totara_completionimport'), 'required');
        }

        // Evidence type.
        $options = $DB->get_records_select_menu('dp_evidence_type', null, null, 'sortorder', 'id, name');
        $selector = array(0 => get_string('selectanevidencetype', 'totara_plan'));
        $selectoptions = $selector + $options;
        $mform->addElement('select', 'evidencetype', get_string('evidencetype', 'totara_completionimport'), $selectoptions);
        $mform->setType('evidencetype', PARAM_INT);
        $mform->addHelpButton('evidencetype', 'evidencetype', 'totara_completionimport');

        // Evidence custom field for completion date.
        $selectoptions = array(get_string('selectanevidencedatefield', 'totara_completionimport'));
        $options = $DB->get_records('dp_plan_evidence_info_field', array('datatype' => 'datetime', 'hidden' => 0), 'sortorder');
        foreach ($options as $option) {
            $selectoptions[$option->shortname] = format_string($option->fullname);
        }
        $mform->addElement('select', 'evidencedatefield', get_string('evidencedatefield', 'totara_completionimport'), $selectoptions);
        $mform->setType('evidencedatefield', PARAM_TEXT);
        $mform->addHelpButton('evidencedatefield', 'evidencedatefield', 'totara_completionimport');

        // Evidence custom field for the import description.
        $selectoptions = array(get_string('selectanevidencedescriptionfield', 'totara_completionimport'));
        $options = $DB->get_records('dp_plan_evidence_info_field', array('datatype' => 'textarea', 'hidden' => 0), 'sortorder');
        foreach ($options as $option) {
            $selectoptions[$option->shortname] = format_string($option->fullname);
        }
        $mform->addElement('select', 'evidencedescriptionfield', get_string('evidencedescriptionfield', 'totara_completionimport'), $selectoptions);
        $mform->setType('evidencedescriptionfield', PARAM_TEXT);
        $mform->addHelpButton('evidencedescriptionfield', 'evidencedescriptionfield', 'totara_completionimport');

        $dateformats = get_dateformats();
        $mform->addElement('select', 'csvdateformat', get_string('csvdateformat', 'totara_completionimport'), $dateformats);
        $mform->setType('csvdateformat', PARAM_TEXT);

        // Function get_delimiter_list() actually returns the list of separators as in "comma *separated* values".
        $separators = csv_import_reader::get_delimiter_list();
        $mform->addElement('select', 'csvseparator', get_string('csvseparator', 'totara_completionimport'), $separators);
        $mform->setType('csvseparator', PARAM_TEXT);
        if (array_key_exists('cfg', $separators)) {
            $mform->setDefault('csvseparator', 'cfg');
        } else if (get_string('listsep', 'langconfig') == ';') {
            $mform->setDefault('csvseparator', 'semicolon');
        } else {
            $mform->setDefault('csvseparator', 'comma');
        }

        $delimiters = array('"' => '"', "'" => "'", '' => 'none');
        $mform->addElement('select', 'csvdelimiter', get_string('csvdelimiter', 'totara_completionimport'), $delimiters);
        $mform->setType('csvdelimiter', PARAM_TEXT);

        $encodings = core_text::get_encodings();
        $mform->addElement('select', 'csvencoding', get_string('csvencoding', 'totara_completionimport'), $encodings);
        $mform->setType('csvencoding', PARAM_TEXT);
        $mform->setDefault('csvencoding', 'UTF-8');

        if ($data->importname == 'certification') {
            $selectoptions = array(
                COMPLETION_IMPORT_TO_HISTORY => get_string('importactioncertificationhistory', 'totara_completionimport'),
                COMPLETION_IMPORT_COMPLETE_INCOMPLETE => get_string('importactioncertificationcertify', 'totara_completionimport'),
                COMPLETION_IMPORT_OVERRIDE_IF_NEWER => get_string('importactioncertificationnewer', 'totara_completionimport'),
            );
            $mform->addElement('select', 'importactioncertification', get_string('importactioncertification', 'totara_completionimport'), $selectoptions);
            $mform->setType('importactioncertification', PARAM_INT);
            $mform->addHelpButton('importactioncertification', 'importactioncertification', 'totara_completionimport');
        } else {
            $overrideactivestr = get_string('overrideactive' . $data->importname, 'totara_completionimport');
            $mform->addElement('advcheckbox', 'overrideactive' . $data->importname, $overrideactivestr);
        }

        $mform->addElement('advcheckbox', 'forcecaseinsensitive'.$data->importname, get_string('caseinsensitive'.$data->importname, 'totara_completionimport'));
        $mform->addHelpButton('forcecaseinsensitive'.$data->importname, 'caseinsensitive'.$data->importname, 'totara_completionimport');
        $mform->setAdvanced('forcecaseinsensitive'.$data->importname);

        $this->add_action_buttons(false, get_string('upload'));

        $this->set_data($data);
    }

    /**
     * Overriding this function to get unique form id so the form can be used more than once
     *
     * @return string form identifier
     */
    protected function get_form_identifier() {
        $formid = $this->_customdata->importname . '_' . get_class($this);
        return $formid;
    }

    public function validation($data, $files) {
        global $CFG;
        $errors = parent::validation($data, $files);

        if (isset($data['sourcefile'])) {
            if (empty($CFG->completionimportdir)) {
                // This form shouldn't have been shown in the first place, but just in case.
                $errors['sourcefilegrp'] = get_string('sourcefile_noconfig', 'totara_completionimport');
            } else if (strpos($data['sourcefile'], $CFG->completionimportdir) !== 0) {
                $errors['sourcefilegrp'] = get_string('sourcefile_validation', 'totara_completionimport',
                    $CFG->completionimportdir);
            }
        }

        return $errors;
    }
}
