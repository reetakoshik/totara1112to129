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
 * @author Yuliya Bozhko <yuliya.bozhko@totaralearning.com>
 *
 * @package block_course_navigation
 */

class block_course_navigation_edit_form extends block_edit_form {

    protected function has_general_settings() {
        return false;
    }

    /**
     * @param MoodleQuickForm $mform
     */
    protected function specific_definition($mform) {
        parent::specific_definition($mform);
        $mform->addElement('header', 'configheader', get_string('customblocksettings', 'block'));

        $options = array(
            block_course_navigation::TRIM_RIGHT => get_string('trimmoderight', 'block_course_navigation'),
            block_course_navigation::TRIM_LEFT => get_string('trimmodeleft', 'block_course_navigation'),
            block_course_navigation::TRIM_CENTER => get_string('trimmodecenter', 'block_course_navigation')
        );
        $mform->addElement('select', 'config_trimmode', get_string('trimmode', 'block_course_navigation'), $options);
        $mform->setType('config_trimmode', PARAM_INT);

        $mform->addElement('text', 'config_trimlength', get_string('trimlength', 'block_course_navigation'));
        $mform->setDefault('config_trimlength', 50);
        $mform->setType('config_trimlength', PARAM_INT);
    }
}
