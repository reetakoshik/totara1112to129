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
 * Bulk position change.
 */
final class bulk_position extends \moodleform {

    public function definition() {
        $mform = $this->_form;

        $currentdata = $this->_customdata['currentdata'];
        $parameters = $this->_customdata['parameters'];

        $bulkcount = count($parameters['requestids']);

        $mform->addElement('hidden', 'bulkaction');
        $mform->setType('bulkaction', PARAM_ALPHANUMEXT);
        $mform->addElement('hidden', 'bulktime');
        $mform->setType('bulktime', PARAM_INT);

        $mform->addElement('static', 'positionselector',
            get_string('bulkactionpositionselect', 'auth_approved', $bulkcount),
            \html_writer::tag('span', '', array('class' => '', 'id' => 'positiontitle')) .
            \html_writer::empty_tag('input', array('type' => 'button', 'value' => get_string('chooseposition', 'totara_job'), 'id' => 'show-position-dialog'))
        );

        $mform->addElement('hidden', 'positionid');
        $mform->setType('positionid', PARAM_INT);

        $this->add_action_buttons(true, get_string('bulkactionposition', 'auth_approved'));

        $this->set_data($currentdata);
    }

    public function definition_after_data() {
        global $DB;
        $mform = $this->_form;

        $positionid = $mform->getElementValue('positionid');
        if ($positionid) {
            $positiontitle = $DB->get_field('pos', 'fullname', array('id' => $positionid));
            $positiontitle = format_string($positiontitle);
            /** @var \MoodleQuickForm_static $positionselector */
            $positionselector = $mform->getElement('positionselector');
            $positionselector->setText(
                \html_writer::tag('span', $positiontitle, array('class' => 'nonempty', 'id' => 'positiontitle')).
                \html_writer::empty_tag('input', array('type' => 'button', 'value' => get_string('chooseposition', 'totara_job'), 'id' => 'show-position-dialog'))
            );
        }
    }
}
