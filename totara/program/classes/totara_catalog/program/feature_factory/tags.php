<?php
/*
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Murali Nair <murali.nair@totaralearning.com>
 * @package totara_program
 * @category totara_catalog
 */
namespace totara_program\totara_catalog\program\feature_factory;

defined('MOODLE_INTERNAL') || die();

use totara_catalog\feature_factory;
use core_tag\totara_catalog\feature_factory as tag_feature_factory;

class tags extends feature_factory {

    public static function get_features(): array {
        return tag_feature_factory::get_features('prog', 'program');
    }
}
