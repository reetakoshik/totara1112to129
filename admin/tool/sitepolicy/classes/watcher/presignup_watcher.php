<?php
/**
 * This file is part of Totara LMS
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
 * @package tool_sitepolicy
 */

namespace tool_sitepolicy\watcher;

use moodle_url;
use totara_core\hook\presignup_redirect;

defined('MOODLE_INTERNAL') || die();

/**
 * Class for managing sign-up events hooks.
 */
class presignup_watcher {

    /**
     * Hook watcher that redirects user to a specific pre-signup requirement if any.
     *
     * @param presignup_redirect $hook
     * @return void
     */
    public static function confirm_site_policies(presignup_redirect $hook): void {
        global $CFG, $SESSION;

        // Site policies have not been consented to yet so redirect to site policies.
        if (!empty($CFG->enablesitepolicies) && empty($SESSION->tool_sitepolicy_consented)) {
            $SESSION->wantsurl = qualified_me();
            $SESSION->userconsentids = [];
            redirect(new moodle_url('/admin/tool/sitepolicy/userpolicy.php'));
        }
    }
}