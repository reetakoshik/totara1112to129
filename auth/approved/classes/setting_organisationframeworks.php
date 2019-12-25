<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2017 onwards Totara Learning Solutions LTD
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
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 *
 * @package auth_approved
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/adminlib.php');


final class auth_approved_setting_organisationframeworks extends admin_setting_configmultiselect {
    public function __construct($name) {
        parent::__construct(
            $name,
            new lang_string('organisationframeworks', 'auth_approved'),
            new lang_string('organisationframeworks_desc', 'auth_approved'),
            array(-1), null);
    }

    public function load_choices() {
        global $DB;
        if (is_array($this->choices)) {
            return true;
        }

        // NOTE: there are no nice APIs to deal with frameworks, so fetch it directly from DB without including the whole universe.
        $this->choices = array('-1' => get_string('allframeworks', 'auth_approved'));
        $this->choices += $DB->get_records_menu('org_framework', array('visible' => 1), 'fullname ASC', 'id, fullname');

        return true;
    }
}
