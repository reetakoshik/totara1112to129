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
 * @author Valerii Kuznetsov <valerii.kuznetsov@totaralms.com>
 * @package mod_facetoface
 */

namespace mod_facetoface;
defined('MOODLE_INTERNAL') || die();

/**
 * Bulk add/remove user list support
 *
 * Class stores its list in session, with optional support of custom fields and navigation between add/remove steps
 * List can be saved with or without optional user data however mixed use is not supported
 */
class bulk_list {
    /**
     * List code
     * @var string
     */
    protected $listid;

    /**
     * Prepare list or get list data
     * @param string $listid List identifier
     * @param moodle_url $returnurl only for first step of list needed for navigation
     * @param string $srctype specifies type of action that this list is being used for, e.g. 'add', 'addfile' etc
     */
    public function __construct($listid, \moodle_url $returnurl = null, $srctype = '') {
        global $SESSION;
        $this->listid = $listid;

        if (!isset($SESSION->mod_facetoface_attendeeslist[$this->listid])) {
            // New list, so it must set return url and src type
            if (empty($returnurl) || empty($srctype)) {
                print_error(get_string('error:invaliduserid', 'facetoface'));
            }
            $SESSION->mod_facetoface_attendeeslist[$this->listid] = array(
                'userdata' => array(),
                'hasdata' => false, // When true list has additional data.
                'returnurl' => clone($returnurl),
                'srctype' => $srctype
                );
        } else {
            // Check that listid corresponds to its type (if set)
            if (!empty($srctype)) {
                // This shouldn't normally happen (but it can happen if user intentionally put wrong data in browser form.
                if ($SESSION->mod_facetoface_attendeeslist[$this->listid]['srctype'] != $srctype) {
                    throw new \coding_exception("Stored user list is incompatible with current list manager.");
                }
            }
        }
    }

    public function get_returnurl() {
        global $SESSION;
        return clone($SESSION->mod_facetoface_attendeeslist[$this->listid]['returnurl']);
    }
    /**
     * Get list of user ids without any additional data
     * @return array
     */
    public function get_user_ids() {
        global $SESSION;
        return array_keys($SESSION->mod_facetoface_attendeeslist[$this->listid]['userdata']);
    }

    /**
     * Set user ids without any additional data
     * @param array $userids
     */
    public function set_user_ids(array $userids) {
        global $SESSION;
        $emptyvalues = array_fill(0, count($userids), null);
        $SESSION->mod_facetoface_attendeeslist[$this->listid]['userdata'] = array_combine($userids, $emptyvalues);
        $SESSION->mod_facetoface_attendeeslist[$this->listid]['hasdata'] = false;
    }

    /**
     * Check if list has user data
     * @return bool
     */
    public function has_user_data() {
        global $SESSION;
        return !empty($SESSION->mod_facetoface_attendeeslist[$this->listid]['hasdata']);
    }

    /**
     * Store all users with additional data
     * @param array $userdata
     */
    public function set_all_user_data(array $userdata) {
        global $SESSION;
        $SESSION->mod_facetoface_attendeeslist[$this->listid]['userdata'] = $userdata;
        $SESSION->mod_facetoface_attendeeslist[$this->listid]['hasdata'] = true;
    }

    /**
     * Store one user with additional data
     * @param array $userdata
     * @param int $userid
     */
    public function set_user_data(array $userdata, $userid) {
        global $SESSION;
        $SESSION->mod_facetoface_attendeeslist[$this->listid]['userdata'][$userid] = $userdata;
        $SESSION->mod_facetoface_attendeeslist[$this->listid]['hasdata'] = true;
    }

    /**
     * Get user list with additional data
     * @return array $userdata
     */
    public function get_user_data($userid) {
        global $SESSION;
        $userdata =  $SESSION->mod_facetoface_attendeeslist[$this->listid]['userdata'][$userid];
        if (empty($userdata)) {
            return [];
        }
        return $userdata;
    }

    /**
     * Store user list form data. Used to repopulate the form when user decides to change selected users
     * @param \stdClass $formdata
     */
    public function set_form_data(\stdClass $formdata) {
        global $SESSION;
        $SESSION->mod_facetoface_attendeeslist[$this->listid]['formdata'] = $formdata;
    }

    /**
     * Get previously stored user list form data. Used to repopulate the form when user decides to change selected users
     * @return \stdClass
     */
    public function get_form_data() {
        global $SESSION;
        if (isset($SESSION->mod_facetoface_attendeeslist[$this->listid]['formdata'])) {
            return  $SESSION->mod_facetoface_attendeeslist[$this->listid]['formdata'];
        }
        return array();
    }

    /**
     * Remove all data about this list
     */
    public function clean() {
        global $SESSION;
        unset($SESSION->mod_facetoface_attendeeslist[$this->listid]);
    }

    public function set_validaton_results($results) {
        global $SESSION;
        $SESSION->mod_facetoface_attendeeslist[$this->listid]['validation'] = $results;
    }

    public function get_validation_results() {
        global $SESSION;
        if (isset($SESSION->mod_facetoface_attendeeslist[$this->listid]['validation'])) {
            return $SESSION->mod_facetoface_attendeeslist[$this->listid]['validation'];
        }
        return array();
    }

    /**
     * Get current list id
     * @return int
     */
    public function get_list_id() {
        return $this->listid;
    }
}
