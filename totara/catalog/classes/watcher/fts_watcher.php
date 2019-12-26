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
 * @package totara_catalog
 */

namespace totara_catalog\watcher;
use totara_catalog\local\catalog_storage;
use totara_catalog\provider_handler;
use totara_core\hook\fts_repopulation;

defined('MOODLE_INTERNAL') || die();

final class fts_watcher {
    /**
     * @param fts_repopulation $hook
     * @return void
     */
    public static function rebuild_catalog(fts_repopulation $hook): void {
        $hook->add_method(
            'catalog',
            function () {
                $providers = provider_handler::instance()->get_active_providers();
                foreach ($providers as $provider) {
                    catalog_storage::delete_provider_data($provider::get_object_type());
                    catalog_storage::populate_provider_data($provider);
                }
            }
        );
    }
}