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
 * @package    totara_feedback360
 * @subpackage navigation
 * @author     Oleg Demeshev <oleg.demeshev@totaralms.com>
 */

namespace totara_feedback360\totara\menu;

class feedback360 extends \totara_core\totara\menu\item {

    protected function get_default_title() {
        return get_string('feedback360', 'totara_feedback360');
    }

    protected function get_default_url() {
        return '/totara/feedback360/index.php';
    }

    public function get_default_sortorder() {
        return 43000;
    }

    protected function check_visibility() {
        global $CFG, $USER;

        if (!isloggedin() or isguestuser()) {
            return false;
        }

        static $cache = null;

        if (isset($cache)) {
            return $cache;
        }

        require_once($CFG->dirroot . '/totara/feedback360/lib.php');
        if (\feedback360::can_view_feedback360s($USER->id)) {
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
        return totara_feature_disabled('feedback360');
    }

    protected function get_default_parent() {
        return '\totara_appraisal\totara\menu\appraisal';
    }

    public function get_incompatible_preset_rules(): array {
        return ['can_view_feedback_360s'];
    }
}
