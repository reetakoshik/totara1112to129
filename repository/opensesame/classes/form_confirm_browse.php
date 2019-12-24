<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2015 onwards Totara Learning Solutions LTD
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
 * @author Petr Skoda <petr.skoda@totaralms.com>
 * @package repository_opensesame
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once("$CFG->libdir/formslib.php");

class repository_opensesame_form_confirm_browse extends moodleform {
    protected function definition() {
        global $USER;

        $mform = $this->_form;

        $a = \repository_opensesame\local\opensesame_com::get_user_info($USER);
        $warning = get_string('confirmbrowsewarning', 'repository_opensesame', $a);
        $warning = markdown_to_html($warning);

        $mform->addElement('static', 'warning', '', $warning);

        $this->add_action_buttons(true, get_string('confirmbrowse', 'repository_opensesame'));
    }
}
