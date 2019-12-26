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
 * @package    totara_core
 * @subpackage navigation
 * @author     Oleg Demeshev <oleg.demeshev@totaralms.com>
 */
namespace totara_core\totara\menu;

use totara_job\job_assignment;

class myteam extends item {

    protected function get_default_title() {
        return get_string('team', 'totara_core');
    }

    protected function get_default_url() {
        return '/my/teammembers.php';
    }

    public function get_default_sortorder() {
        return 50000;
    }

    protected function check_visibility() {
        global $USER;

        if (!isloggedin() or isguestuser()) {
            return false;
        }

        static $cache = null;

        if (isset($cache)) {
            return $cache;
        }

        if (job_assignment::has_staff($USER->id) || is_siteadmin()) {
            $cache = true;
        } else {
            $cache = false;
        }
        return $cache;
    }

    /**
     * Is this menu item completely disabled?
     *
     * @return bool
     */
    public function is_disabled() {
        return totara_feature_disabled('myteam');
    }

    public function get_incompatible_preset_rules(): array {
        return ['can_view_my_team'];
    }
}
