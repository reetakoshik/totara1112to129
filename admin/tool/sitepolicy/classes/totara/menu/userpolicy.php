<?php
/**
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Valerii Kuznetsov <valerii.kuznetsov@totaralms.com>
 * @package tool_sitepolicy
 */

namespace tool_sitepolicy\totara\menu;

defined('MOODLE_INTERNAL') || die();

class userpolicy extends \totara_core\totara\menu\item {

    protected function get_default_title() {
        return get_string('previewconsents', 'tool_sitepolicy');
    }

    protected function get_default_url() {
        global $CFG;
        return "/{$CFG->admin}/tool/sitepolicy/userlist.php";
    }

    public function get_default_sortorder() {
        return 70000;
    }

    public function is_disabled() {
        global $CFG;
        if (!empty($CFG->enablesitepolicies)) {
            return false;
        }
        else {
            return true;
        }
    }

    protected function get_default_parent() {
        return '\totara_core\totara\menu\unused';
    }
}
