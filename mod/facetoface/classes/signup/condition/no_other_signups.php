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
 * @author  Valerii Kuznetsov <valerii.kuznetsov@totaralearning.com>
 * @package mod_facetoface
 */

namespace mod_facetoface\signup\condition;

defined('MOODLE_INTERNAL') || die();

/**
 * Class no_other_signups
 * Confirms that user don't have any other signups within activity unless multiplesessions are enabled.
 */
class no_other_signups extends condition {

    /**
     * Is condition passing
     * @return bool
     */
    public function pass() : bool {
        if(empty($this->signup->get_userid())) {
            return false;
        }
        $seminar = $this->signup->get_seminar_event()->get_seminar();
        if ($seminar->get_multiplesessions()) {
            return true;
        }
        return !$seminar->has_unarchived_signups($this->signup->get_userid());
    }

    public static function get_description() : string {
        return get_string('state_noothersignups_desc', 'mod_facetoface');
    }

    public function get_failure() : array {
        return ['no_other_signups' => get_string('error:signedupinothersession', 'mod_facetoface')];
    }
}
