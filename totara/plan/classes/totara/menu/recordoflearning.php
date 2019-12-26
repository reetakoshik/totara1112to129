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
 * @package    totara_plan
 * @subpackage navigation
 * @author     Oleg Demeshev <oleg.demeshev@totaralms.com>
 */

namespace totara_plan\totara\menu;

class recordoflearning extends \totara_core\totara\menu\item {

    protected function get_default_title() {
        return get_string('recordoflearning', 'totara_plan');
    }

    protected function get_default_url() {
        return '/totara/plan/record/index.php';
    }

    public function get_default_sortorder() {
        return 30000;
    }

    protected function check_visibility() {
        if (!isloggedin() or isguestuser()) {
            return false;
        }
        return true;
    }

    /**
     * Is this menu item completely disabled?
     *
     * @return bool
     */
    public function is_disabled() {
        return totara_feature_disabled('recordoflearning');
    }
}
