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
 * @author     Oleg Demeshev <oleg.demeshev@totaralearning.com>
 */
namespace totara_core\totara\menu;

class unused extends \totara_core\totara\menu\container {

    protected function get_default_title() {
        return get_string('unused', 'totara_core');
    }

    public function is_disabled() {
        return true;
    }

    public function get_default_sortorder() {
        // Always displayed at the end in admin UI, never displayed in Main menu,
        // sortorder does not matter here, so we use low number so that it does not interfere with other items.
        return 1;
    }
}