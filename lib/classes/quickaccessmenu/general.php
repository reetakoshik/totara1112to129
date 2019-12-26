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
 */

namespace core\quickaccessmenu;

use \totara_core\quickaccessmenu\group;
use \totara_core\quickaccessmenu\item;

class general implements \totara_core\quickaccessmenu\provider {

    public static function get_items(): array {
        return [
            item::from_provider(
                'themesettings',
                group::get(group::CONFIGURATION),
                new \lang_string('appearance', 'admin'),
                1000
            ),
            item::from_provider(
                'navigation',
                group::get(group::CONFIGURATION),
                new \lang_string('navigation', 'core'),
                2000
            ),
            item::from_provider(
                'sitepolicies',
                group::get(group::CONFIGURATION),
                new \lang_string('security', 'admin'),
                3000
            ),
            item::from_provider(
                'userpolicies',
                group::get(group::PLATFORM),
                new \lang_string('permissions', 'role'),
                4000
            ),
            item::from_provider(
                'langsettings',
                group::get(group::CONFIGURATION),
                new \lang_string('localisation', 'admin'),
                5000
            ),
            item::from_provider(
                'environment',
                group::get(group::CONFIGURATION),
                new \lang_string('server', 'admin'),
                6000
            ),
            item::from_provider(
                'debugging',
                group::get(group::CONFIGURATION),
                new \lang_string('development', 'admin'),
                7000
            ),
            item::from_provider(
                'optionalsubsystems',
                group::get(group::CONFIGURATION),
                new \lang_string('advancedfeatures', 'admin'),
                8000
            ),
            item::from_provider(
                'adminnotifications',
                group::get(group::CONFIGURATION),
                new \lang_string('systeminformation', 'core'),
                9000
            ),
            item::from_provider(
                'pluginsoverview',
                group::get(group::CONFIGURATION),
                new \lang_string('plugins', 'admin'),
                2500
            ),
        ];
    }
}
