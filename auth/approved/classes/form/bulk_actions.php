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
 * @package auth_approved
 */

namespace auth_approved\form;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once("$CFG->libdir/formslib.php");

/**
 * Bulk action form similar to report_builder_export_form.
 */
final class bulk_actions extends \moodleform {
    public function definition() {
        $mform = $this->_form;

        // Remember the time when we displayed this form,
        // we do not want to change requests modified after this time!
        $mform->addElement('hidden', 'bulktime');
        $mform->setType('bulktime', PARAM_INT);

        // This form should look similar to report_builder_export_form.

        $actions = array('' => get_string('choosedots')) + $this->_customdata['actions'];
        $count = $this->_customdata['count'];

        $group = array();
        $group[] = $mform->createElement('select', 'bulkaction', get_string('bulkaction', 'auth_approved', $count), $actions);
        $group[] = $mform->createElement('submit', 'bulkexec', get_string('bulkexec', 'auth_approved'));
        $mform->addGroup($group, 'bulkgroup', get_string('bulkaction', 'auth_approved', $count), array(' '), false);

        $this->set_data(array('bulktime' => time()));
    }
}