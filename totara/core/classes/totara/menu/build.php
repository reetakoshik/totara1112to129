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
 * @package    totara
 * @subpackage navigation
 * @author     Oleg Demeshev <oleg.demeshev@totaralms.com>
 */
namespace totara_core\totara\menu;

defined('MOODLE_INTERNAL') || die();

/**
 * This class was originally used to update the default menu during upgrades.
 * It was designed for internal use only, and should never have been used by others,
 * This is now done \totara_core\totara\menu\helper::add_default_items();
 *
 * @deprecated since Totara 12.0
 */
class build {
    public function __construct() {
        debugging('Internal class \totara_core\totara\menu\build is deprecated since Totara 12.0, do not use it', DEBUG_DEVELOPER);
    }

    public function add($classname) {
        // Do not use.
    }

    public function upgrade() {
        // Do not use.
    }
}
