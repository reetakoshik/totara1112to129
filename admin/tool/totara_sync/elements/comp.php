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
 * @author Brendan Cox <brendan.cox@totaralearning.com>
 * @package tool_totara_sync
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/admin/tool/totara_sync/elements/classes/hierarchy.element.class.php');

/**
 * Class totara_sync_element_comp
 *
 * The totara_sync_hierarchy class requires that we call this by the short prefix name for the
 * hierarchy type. i.e. we call competencies, 'comp'.
 */
class totara_sync_element_comp extends totara_sync_hierarchy {

    /**
     * @return competency
     */
    public function get_hierarchy() {
        global $CFG;
        require_once($CFG->dirroot.'/totara/hierarchy/prefix/competency/lib.php');

        return new competency();
    }

    /**
     * Checks the temporary table for data integrity.
     *
     * Calls the parent method to do general hierarchy related checks,
     * then does additional competency specific checks if no issues are found.
     *
     * @global object $DB
     * @param string $synctable
     * @param string $synctable_clone name of the clone table
     * @return boolean
     */
    public function check_sanity($synctable, $synctable_clone) {
        global $DB;

        if (!parent::check_sanity($synctable, $synctable_clone)) {
            // If the parent sanity check has come back negative, just return as we don't know what
            // issues are in there that might affect our ability to do the checks specific to competencies.
            return false;
        }

        // For new items, check aggregationmethod is not null.
        $sql = "SELECT s.idnumber
                  FROM {{$synctable}} s 
             LEFT JOIN {comp} c 
                    ON s.idnumber = c.idnumber 
                 WHERE c.id IS NULL 
                   AND s.aggregationmethod IS NULL";
        if ($idnumbers = $DB->get_fieldset_sql($sql)) {
            foreach($idnumbers as $idnumber) {
                $this->addlog(
                    get_string('aggregrationmethodmusthavevalue','tool_totara_sync', $idnumber),
                    'error',
                    'checksanity'
                );
            }
            return false;
        }

        return true;
    }
}

