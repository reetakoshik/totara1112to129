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

use auth_approved\event\request_approved;
use tool_sitepolicy\userconsent;

defined('MOODLE_INTERNAL') || die();

class request_approved_observer {

    /**
     * Whenever a user has accepted the site policy during signup we need to update
     * the userids in sitepolicy_user_consent to the new user id when the request is
     * approved.
     *
     * @param request_approved $event
     */
    public static function update_user_consent(request_approved $event): void {
        $extradata = \auth_approved\request::get_extradata($event->objectid);
        if (!empty($extradata['userconsentids'])) {
            foreach ($extradata['userconsentids'] as $userconsentid) {
                $consent = new userconsent($userconsentid);
                $consent->set_userid($event->relateduserid);
                $consent->save();
            }
        }
    }
}
