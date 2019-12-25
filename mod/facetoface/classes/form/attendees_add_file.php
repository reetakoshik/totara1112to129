<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
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
 * @author Valerii Kuznetsov <valerii.kuznetsov@totaralearning.com>
 * @author Oleg Demeshev <oleg.demeshev@totaralearning.com>
 * @package mod_facetoface
 */

namespace mod_facetoface\form;

defined('MOODLE_INTERNAL') || die();

class attendees_add_file extends \moodleform {

    protected function definition() {
        $mform = $this->_form;

        // $customfieldinfo is used as $a in get_string().
        $customfieldinfo = new \stdClass();
        $customfieldinfo->customfields = '';
        $customfieldinfo->requiredcustomfields = '';

        $dataoptional = get_string('dataoptional', 'mod_facetoface');

        $customfields = $this->_customdata['customfields'];
        $requiredcustomfields = $this->_customdata['requiredcustomfields'];
        $optionalfields = array_diff($customfields, $requiredcustomfields);

        if (!empty($requiredcustomfields)) {
            foreach ($requiredcustomfields as $item) {
                $customfieldinfo->customfields .= "* '{$item}'\n";
                $customfieldinfo->requiredcustomfields .= "* '{$item}'\n";
            }
        }

        if (!empty($optionalfields)) {
            foreach ($optionalfields as $item) {
                $customfieldinfo->customfields .= "* '{$item}' ({$dataoptional})\n";
            }
        }

        $extrafields = $this->_customdata['extrafields'];
        if (!empty($extrafields)) {
            foreach ($extrafields as $item) {
                $customfieldinfo->customfields .= "* '{$item}' ({$dataoptional})\n";
            }
        }

        $mform->addElement('hidden', 's', $this->_customdata['s']);
        $mform->setType('s', PARAM_INT);

        $mform->addElement('hidden', 'listid', $this->_customdata['listid']);
        $mform->setType('listid', PARAM_ALPHANUM);

        $mform->addElement('header', 'addattendees', get_string('addattendees', 'mod_facetoface'));

        $fileoptions = array('accepted_types' => array('.csv'));
        $mform->addElement('filepicker', 'userfile', get_string('csvtextfile', 'mod_facetoface'), null, $fileoptions);
        $mform->setType('userfile', PARAM_FILE);
        $mform->addRule('userfile', null, 'required');

        $encodings = \core_text::get_encodings();
        $mform->addElement('select', 'encoding', get_string('encoding', 'mod_facetoface'), $encodings);

        $delimiters = \mod_facetoface\import_helper::csv_get_delimiter_list();
        $mform->addElement('select', 'delimiter', get_string('delimiter', 'mod_facetoface'), $delimiters);
        $mform->setDefault('delimiter', get_config('facetoface', 'defaultcsvdelimiter'));

        $mform->addElement('advcheckbox', 'ignoreconflicts', get_string('allowscheduleconflicts', 'mod_facetoface'));
        $mform->setType('ignoreconflicts', PARAM_BOOL);

        $mform->addelement('html', format_text(get_string('csvtextfile_help', 'mod_facetoface', $customfieldinfo), FORMAT_MARKDOWN));

        $this->add_action_buttons(true, get_string('continue'));
    }
}
