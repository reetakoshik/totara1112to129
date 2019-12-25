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
 * @author Valerii Kuznetsov <valerii.kuznetsov@totaralms.com>
 * @package mod_facetoface
 */

namespace mod_facetoface\form;

defined('MOODLE_INTERNAL') || die();

class asset_edit extends \moodleform {

    /**
     * Definition of the asset form
     */
    public function definition() {
        global $DB;

        $mform = $this->_form;

        /**
         * @var \mod_facetoface\asset $asset
         */
        $asset = $this->_customdata['asset'];
        $seminar = empty($this->_customdata['seminar']) ? null : $this->_customdata['seminar'];
        $event = empty($this->_customdata['event']) ? null : $this->_customdata['event'];
        $editoroptions = $this->_customdata['editoroptions'];

        $modconfig = has_capability('totara/core:modconfig', \context_system::instance());

        $mform->addElement('hidden', 'id', $asset->get_id());
        $mform->setType('id', PARAM_INT);

        if (!empty($seminar)) {
            $mform->addElement('hidden', 'f', $seminar->get_id());
            $mform->setType('f', PARAM_INT);
        }
        if (!empty($event)) {
            $mform->addElement('hidden', 's', $event->get_id());
            $mform->setType('s', PARAM_INT);
        }

        $mform->addElement('text', 'name', get_string('assetname', 'facetoface'), array('size' => '45'));
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');


        $mform->addElement('advcheckbox', 'allowconflicts', get_string('allowassetconflicts', 'mod_facetoface'));
        $mform->addHelpButton('allowconflicts', 'allowassetconflicts', 'mod_facetoface');

        $mform->addElement('editor', 'description_editor', get_string('assetdescription', 'facetoface'), null, $this->_customdata['editoroptions']);

        customfield_definition($mform, (object)['id' => $asset->get_id()], 'facetofaceasset', 0, 'facetoface_asset');

        if ($modconfig and !empty($seminar) and $asset->get_custom()) {
            $mform->addElement('advcheckbox', 'notcustom', get_string('publishreuse', 'mod_facetoface'));
            // Disable if does not seem to work in dialog forms, back luck.
        }

        if ($asset->exists()) {
            $mform->addElement('header', 'versions', get_string('versioncontrol', 'mod_facetoface'));

            $created = new \stdClass();
            $created->user = get_string('unknownuser');
            $usercreated = $asset->get_usercreated();
            if (!empty($usercreated)) {
                $created->user = \html_writer::link(
                    new \moodle_url('/user/view.php', array('id' => $asset->get_usercreated())),
                    fullname($DB->get_record('user', ['id' => $usercreated]))
                );
            }
            $created->time = empty($asset->get_timecreated()) ? '' : userdate($asset->get_timecreated());
            $mform->addElement(
                    'static',
                    'versioncreated',
                    get_string('created', 'mod_facetoface'),
                    get_string('timestampbyuser', 'mod_facetoface', $created)
            );

            if (!empty($asset->get_timemodified()) and $asset->get_timemodified() != $asset->get_timecreated()) {
                $modified = new \stdClass();
                $modified->user = get_string('unknownuser');
                $usermodified = $asset->get_usermodified();
                if (!empty($usermodified)) {
                    $modified->user = \html_writer::link(
                        new \moodle_url('/user/view.php', array('id' => $usermodified)),
                        fullname($DB->get_record('user', ['id' => $usermodified]))
                    );
                }
                $modified->time = empty($asset->get_timemodified()) ? '' : userdate($asset->get_timemodified());
                $mform->addElement(
                        'static',
                        'versionmodified',
                        get_string('modified'),
                        get_string('timestampbyuser', 'mod_facetoface', $modified)
                );
            }
        }

        if (empty($seminar)) {
            $label = null;
            if (!$asset->exists()) {
                $label = get_string('addasset', 'facetoface');
            }
            $this->add_action_buttons(true, $label);
        }

        $formdata = (object)[
            'id' => $asset->get_id(),
            'name' => $asset->get_name(),
            'allowconflicts' => $asset->get_allowconflicts(),
            'description_editor' => ['text' => $asset->get_description()],
            'notcustom' => $asset->get_custom() ? 0 : 1,
            'description' => $asset->get_description(),
            'descriptionformat' => FORMAT_HTML,
        ];

        customfield_load_data($formdata, 'facetofaceasset', 'facetoface_asset');
        $formdata = file_prepare_standard_editor($formdata, 'description', $editoroptions, $editoroptions['context'],
            'mod_facetoface', 'asset', $asset->get_id());

        $this->set_data($formdata);
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        /**
         * @var \mod_facetoface\asset $asset
         */
        $asset = $this->_customdata['asset'];

        if ($asset->exists() and $asset->get_allowconflicts() and $data['allowconflicts'] == 0) {
            // Make sure there are no existing conflicts before we switch the setting!

            if ($asset->has_conflicts()) {
                $errors['allowconflicts'] = get_string('error:assetconflicts', 'mod_facetoface');
            }
        }

        return $errors;
    }
}
