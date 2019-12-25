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
 * Totara navigation edit page.
 *
 * @package    totara_appraisal
 * @subpackage navigation
 * @author     Oleg Demeshev <oleg.demeshev@totaralms.com>
 */

namespace totara_appraisal\totara\menu;

class latestappraisal extends \totara_core\totara\menu\item {

    protected function get_default_title() {
        return get_string('latestappraisal', 'totara_appraisal');
    }

    protected function get_default_url() {
        return '/totara/appraisal/myappraisal.php?latest=1';
    }

    public function get_default_sortorder() {
        return 41000;
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

        require_once($CFG->dirroot . '/totara/appraisal/lib.php');
        if (\appraisal::can_view_own_appraisals($USER->id)) {
            $cache = true;
        } else {
            $cache = false;
        }
        return $cache;
    }

    protected function get_default_parent() {
        return '\totara_appraisal\totara\menu\appraisal';
    }

    /**
     * Is this menu item completely disabled?
     *
     * @return bool
     */
    public function is_disabled() {
        return totara_feature_disabled('appraisals');
    }

    public function get_incompatible_preset_rules(): array {
        return ['can_view_latestappraisal'];
    }
}
