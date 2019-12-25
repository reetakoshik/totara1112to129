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

defined('MOODLE_INTERNAL') || die();

require_once("{$CFG->dirroot}/lib/formslib.php");
require_once($CFG->dirroot . '/totara/customfield/field/location/define.class.php'); // TODO: TL-9425 this hack is unacceptable.

class mod_facetoface_asset_form extends moodleform {

    /**
     * Definition of the asset form
     */
    public function definition() {
        global $DB;

        $mform = $this->_form;
        $asset = $this->_customdata['asset'];
        $facetoface = $this->_customdata['facetoface'];
        $session = $this->_customdata['session'];
        $modconfig = has_capability('totara/core:modconfig', context_system::instance());

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        if ($facetoface) {
            $mform->addElement('hidden', 'f', $facetoface->id);
            $mform->setType('f', PARAM_INT);
        }
        if ($session) {
            $mform->addElement('hidden', 's', $session->id);
            $mform->setType('s', PARAM_INT);
        }

        $mform->addElement('text', 'name', get_string('assetname', 'facetoface'), array('size' => '45'));
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');


        $mform->addElement('advcheckbox', 'allowconflicts', get_string('allowassetconflicts', 'mod_facetoface'));
        $mform->addHelpButton('allowconflicts', 'allowassetconflicts', 'mod_facetoface');

        $mform->addElement('editor', 'description_editor', get_string('assetdescription', 'facetoface'), null, $this->_customdata['editoroptions']);

        customfield_definition($mform, $asset, 'facetofaceasset', 0, 'facetoface_asset');

        if ($modconfig and $facetoface and $asset->custom) {
            $mform->addElement('advcheckbox', 'notcustom', get_string('publishreuse', 'mod_facetoface'));
            // Disable if does not seem to work in dialog forms, back luck.
        }

        if ($asset->id) {
            $mform->addElement('header', 'versions', get_string('versioncontrol', 'mod_facetoface'));

            $created = new stdClass();
            $created->user = get_string('unknownuser');
            if (!empty($asset->usercreated)) {
                $created->user = html_writer::link(
                    new moodle_url('/user/view.php', array('id' => $asset->usercreated)),
                    fullname($DB->get_record('user', array('id' => $asset->usercreated)))
                );
            }
            $created->time = empty($asset->timecreated) ? '' : userdate($asset->timecreated);
            $mform->addElement(
                    'static',
                    'versioncreated',
                    get_string('created', 'mod_facetoface'),
                    get_string('timestampbyuser', 'mod_facetoface', $created)
            );

            if (!empty($asset->timemodified) and $asset->timemodified != $asset->timecreated) {
                $modified = new stdClass();
                $modified->user = get_string('unknownuser');
                if (!empty($asset->usermodified)) {
                    $modified->user = html_writer::link(
                        new moodle_url('/user/view.php', array('id' => $asset->usermodified)),
                        fullname($DB->get_record('user', array('id' => $asset->usermodified)))
                    );
                }
                $modified->time = empty($asset->timemodified) ? '' : userdate($asset->timemodified);
                $mform->addElement(
                        'static',
                        'versionmodified',
                        get_string('modified'),
                        get_string('timestampbyuser', 'mod_facetoface', $modified)
                );
            }
        }

        if (!$facetoface) {
            $label = null;
            if (!$asset->id) {
                $label = get_string('addasset', 'facetoface');
            }
            $this->add_action_buttons(true, $label);
        }

        $this->set_data($asset);
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        $asset = $this->_customdata['asset'];

        if ($asset->id and $asset->allowconflicts == 1 and $data['allowconflicts'] == 0) {
            // Make sure there are no existing conflicts before we switch the setting!

            if (facetoface_asset_has_conflicts($asset->id)) {
                $errors['allowconflicts'] = get_string('error:assetconflicts', 'mod_facetoface');
            }
        }

        return $errors;
    }
}
