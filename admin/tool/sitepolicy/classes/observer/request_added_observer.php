<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
 * @package tool_sitepolicy
 */

namespace tool_sitepolicy\observer;

use auth_approved\event\request_added;

defined('MOODLE_INTERNAL') || die();

class request_added_observer {

    /**
     * Reset sitepolicy_consented setting so that the user has to accept it again when creating new account.
     * This site may be accessed through a shared device and no login may have occurred yet, therefore we
     * can not be sure that the same user wants to create another account.
     *
     * @param request_added $event
     */
    public static function reset_site_policy(request_added $event): void {
        global $SESSION;
        unset($SESSION->tool_sitepolicy_consented);
    }
}
