<?php
/*
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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package totara_program
 * @category totara_catalog
 */

namespace totara_program\totara_catalog\program\dataholder_factory;

use totara_catalog\{dataholder, dataholder_factory};
use totara_catalog\search_metadata\search_metadata_dataholder_factory;

defined('MOODLE_INTERNAL') || die();

class search_metadata extends dataholder_factory {
    /**
     * @return dataholder[]
     */
    public static function get_dataholders(): array {
        return search_metadata_dataholder_factory::get_dataholders('totara_program');
    }
}