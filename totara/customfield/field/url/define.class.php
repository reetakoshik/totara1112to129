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
 * @author Simon Player <simon.player@totaralms.com>
 * @package totara_customfield
 */
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/totara/customfield/definelib.php');

/**
 * Class customfield_define_url
 */
class customfield_define_url extends customfield_define_base {

    /**
     * Adds the URL custom field arguments to the form
     *
     * @param moodleform $form
     */
    public function define_form_specific(&$form) {
        // The link URL.
        $form->addElement('text', 'defaultdata', get_string('customfielddefaultdataurl', 'totara_customfield'), 'maxlength="2048" size="50"');
        $form->setType('defaultdata', PARAM_URL);

        // Param 1 for the link text.
        $form->addElement('text', 'param1', get_string('customfielddefaultdataurltext', 'totara_customfield'), 'maxlength="2048" size="50"');
        $form->setType('param1', PARAM_TEXT);
        $form->addHelpButton('param1', 'customfielddefaultdataurltext', 'totara_customfield');

        // Param 2 for option to open link in a new window.
        $form->addElement('checkbox', 'param2', get_string('customfielddefaultdataurltarget', 'totara_customfield'));
        $form->setDefault('param2', true);
    }

    /**
     * Validates the data being used to define the URL custom field.
     *
     * @param stdClass $data
     * @param array $files
     * @param string $tableprefix
     * @return array
     */
    public function define_validate_specific($data, $files, $tableprefix) {

        if (!empty($data->defaultdata)) {
            if (substr($data->defaultdata, 0, 7) !== 'http://' && substr($data->defaultdata, 0, 8) !== 'https://' && substr($data->defaultdata, 0, 1) !== '/') {
                return array('defaultdata' => get_string('customfieldurlformaterror', 'totara_customfield'));
            }
        }

        return array();
    }
}
