<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2010 onwards Totara Learning Solutions LTD
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
 * @package modules
 * @subpackage facetoface
 */

namespace mod_facetoface\form;

defined('MOODLE_INTERNAL') || die();

class cancelsignup extends \moodleform {

    function definition() {
        $mform =& $this->_form;
        $cancellationnote = $this->_customdata['cancellation_note'];
        $strheader = 'cancelbooking';
        $strcancellationconfirm = 'cancellationconfirm';
        if ($this->_customdata['userisinwaitlist']) {
            $strheader = 'cancelwaitlist';
            $strcancellationconfirm = 'waitlistcancellationconfirm';
        }

        $mform->addElement('header', 'general', get_string($strheader, 'facetoface'));

        $mform->addElement('hidden', 's', $this->_customdata['s']);
        $mform->setType('s', PARAM_INT);
        $mform->addElement('hidden', 'backtoallsessions', $this->_customdata['backtoallsessions']);
        $mform->setType('backtoallsessions', PARAM_BOOL);

        $mform->addElement('html', get_string($strcancellationconfirm, 'facetoface')); // Instructions.

        $cancellation = new \stdClass();
        $cancellation->id = $cancellationnote->id;

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        customfield_definition($mform, $cancellation, 'facetofacecancellation', 0, 'facetoface_cancellation');
        // Verify 'customfields' is exists.
        if ($mform->elementExists('customfields')) {
            $mform->removeElement('customfields');
        }

        $buttonarray=array();
        $buttonarray[] = &$mform->createElement('submit', 'submitbutton', get_string('yes'));
        $buttonarray[] = &$mform->createElement('cancel', 'cancelbutton', get_string('no'));
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
    }
}
