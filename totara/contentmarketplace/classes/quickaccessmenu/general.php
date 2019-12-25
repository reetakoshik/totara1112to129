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
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 * @package totara_contentmarketplace
 */

namespace totara_contentmarketplace\quickaccessmenu;

use \totara_core\quickaccessmenu\group;
use \totara_core\quickaccessmenu\item;

class general implements \totara_core\quickaccessmenu\provider {

    public static function get_items(): array {
        return [
            item::from_provider(
                'setup_content_marketplaces',
                group::get(group::LEARN),
                new \lang_string('contentmarketplace', 'totara_contentmarketplace'),
                7000
            ),
            // The same string is intentional here since only 'manage' will show in the admin tree
            // after the initial contentmarketplace setup.
            item::from_provider(
                'manage_content_marketplaces',
                group::get(group::LEARN),
                new \lang_string('contentmarketplace', 'totara_contentmarketplace'),
                8000
            ),
        ];
    }

}