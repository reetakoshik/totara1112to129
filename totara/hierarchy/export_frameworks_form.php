<?php
/*
 * This file is part of Totara LMS
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
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package totara
 * @subpackage hierarchy
 */

/**
 * Formslib template for generating an export all frameworks form
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

require_once "$CFG->dirroot/lib/formslib.php";

class hierarchy_export_frameworks_form extends moodleform {

    /**
     * Definition of the export frameworks form
     */
    function definition() {
        global $HIERARCHY_EXPORT_OPTIONS;
        $mform = $this->_form;

        $select = array();
        $sitecontext = context_system::instance();
        foreach ($HIERARCHY_EXPORT_OPTIONS as $option => $code) {
            $select[$option] = get_string('export'.$option, 'totara_hierarchy');
        }

        $attributes = $this->_customdata['attributes'] ?? '';

        if (count($select) == 0) {
            // no export options - don't show form
            return false;
        } else if (count($select) == 1) {
            // no options - show a button
            $mform->addElement('hidden', 'format', key($select));
            $mform->addElement('submit', 'export', current($select), $attributes);
        } else {
            // show pulldown menu
            $group=array();
            $group[] = $mform->createElement('select', 'format', get_string('exportframeworks', 'totara_hierarchy'), $select, $attributes);
            $group[] = $mform->createElement('submit', 'export', get_string('export', 'totara_hierarchy'), $attributes);
            $mform->addGroup($group, 'exportframeworksgroup', get_string('exportframeworks', 'totara_hierarchy'), array(' '), false);
            $mform->addHelpButton('exportframeworksgroup', 'exportframeworks', 'totara_hierarchy');
        }
    }
}
