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
 * @author Petr Skoda <petr.skoda@totaralms.com>
 * @package mod_facetoface
 */

namespace mod_facetoface\form;

defined('MOODLE_INTERNAL') || die();

class signin extends \moodleform {
    public function definition() {
        $mform = $this->_form;

        $select = reportbuilder_get_export_options(null, true);
        if (count($select) == 0) {
            // No export options - don't show form.
            return;
        }

        $group = array();

        $session = $this->_customdata;

        $data = array('format' => null);
        // The sign-in sheet will be most likely printed out,
        // so use some reasonable printer friendly default here.
        if (defined('BEHAT_SITE_RUNNING')) {
            $data['format'] = 'csv';
        } else if (isset($select['pdflandscape'])) {
            $data['format'] = 'pdflandscape';
        }

        $options = array();
        foreach ($session->sessiondates as $date) {
            $dateobject = facetoface_format_session_times($date->timestart, $date->timefinish, $date->sessiontimezone);
            $options[$date->id] = get_string('sessionstartdatewithtime', 'mod_facetoface', $dateobject);
        }
        if (count($options) > 1) {
            $group[] = $mform->createElement('select', 'sessiondateid', get_string('sessiondate', 'mod_facetoface'), $options);
        } else {
            $mform->addElement('hidden', 'sessiondateid');
            $mform->setType('sessiondateid', PARAM_INT);
            if (count($options) === 1) {
                $data['sessiondateid'] = key($options);
            }
        }

        if (count($select) == 1) {
            $mform->addElement('hidden', 'format');
            $mform->setType('format', PARAM_PLUGIN);
        } else {
            $group[] = $mform->createElement('select', 'format', get_string('exportformat', 'totara_core'), $select);
        }

        $group[] = $mform->createElement('submit', 'export', get_string('downloadsigninsheet', 'mod_facetoface'));

        if (count($group) > 1) {
            $mform->addGroup($group, 'exportgroup', '', array(' '), false);
        } else {
            $mform->addElement($group[0]);
        }

        $this->set_data($data);
    }
}
