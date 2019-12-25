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
 * @author Alastair Munro <alastair.munro@totaralms.com>
 * @package block_current_learning
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

class block_current_learning_edit_form extends block_edit_form {

    protected function specific_definition($mform) {
        $mform->addElement('header', 'configheader', get_string('blocksettings', 'block'));

        $mform->addElement('duration', 'config_warningperiod', get_string('itemduewarningperiod', 'block_current_learning'));
        $mform->setDefault('config_warningperiod', block_current_learning::DEFAULT_WARNING_PERIOD);
        $mform->addHelpButton('config_warningperiod', 'itemduewarningperiod', 'block_current_learning');

        $mform->addElement('duration', 'config_alertperiod', get_string('itemduealertperiod', 'block_current_learning'));
        $mform->setDefault('config_alertperiod', block_current_learning::DEFAULT_ALERT_PERIOD);
        $mform->addHelpButton('config_alertperiod', 'itemduealertperiod', 'block_current_learning');
    }

    /**
     * Custom validation for the form to make sure that
     * the warning period is not after the alert period.
     *
     * @param array $data
     * @param array $files
     * @return array An array of error messages for form items.
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        if ($data['config_warningperiod'] < $data['config_alertperiod']) {
            $errors['config_warningperiod'] = get_string('error:warningperiodgreaterthanalert' ,'block_current_learning');
            $errors['config_alertperiod'] = get_string('error:warningperiodgreaterthanalert' ,'block_current_learning');
        }

        return $errors;
    }
}
