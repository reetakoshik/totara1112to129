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
 * @author David Curry <david.curry@totaralms.com>
 * @package modules
 * @subpackage facetoface
 */

namespace mod_facetoface;
defined('MOODLE_INTERNAL') || die();

// Extending the user class for external and facetoface users.
class facetoface_user extends \core_user {

    // Facetoface messages default userid.
    const FACETOFACE_USER = -35;

    // @var stdClass keep record of facetoface user.
    public static $facetofaceuser = false;

    public static function get_user($userid, $fields = '*', $strictness = IGNORE_MISSING) {

        switch ($userid) {
            case self::FACETOFACE_USER:
                return self::get_facetoface_user($strictness);
                break;
            default:
                return parent::get_user($userid, $fields, $strictness);
                break;
        }
    }

    /**
     * Helper function to return dummy facetoface user record.
     *
     * @return stdClass     The dummy user object
     */
    public static function get_facetoface_user() {

        // Just return the cached user object.
        if (!empty(self::$facetofaceuser)) {
            return self::$facetofaceuser;
        }

        self::$facetofaceuser = parent::get_noreply_user();
        self::$facetofaceuser->maildisplay = 1;
        // Unset emailstop to make sure support message is sent.
        self::$facetofaceuser->emailstop = 0;
        return self::$facetofaceuser;
    }
}
