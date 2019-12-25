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
 * @package    totara_coursecatalogue
 * @subpackage navigation
 * @author     Oleg Demeshev <oleg.demeshev@totaralms.com>
 */

namespace totara_coursecatalog\totara\menu;

/**
 * Old style list of places where to look for courses, certifications, programs, etc.
 *
 * This container is displayed only if grid catalogue is NOT active.
 */
class findlearning extends \totara_core\totara\menu\container {

    protected function get_default_title() {
        global $CFG;

        if ($CFG->catalogtype === 'totara') {
            return get_string('findlearningdisabled', 'totara_coursecatalog');
        } else {
            return get_string('findlearning', 'totara_coursecatalog');
        }
    }

    public function is_disabled() {
        global $CFG;
        return ($CFG->catalogtype === 'totara');
    }

    public function get_default_sortorder() {
        return 70000;
    }
}
