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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Samantha Jayasinghe <samantha.jayasinghe@totaralearning.com>
 * @package totara_catalog
 */

namespace totara_catalog\task;

defined('MOODLE_INTERNAL') || die();

use \core\task\adhoc_task;
use totara_catalog\local\catalog_storage;
use totara_catalog\provider_handler;

/**
 * This adhoc task will populate provider data based on object type
 */
class provider_active_task extends adhoc_task {

    public function execute() {
        $data = $this->get_custom_data();

        if (provider_handler::instance()->is_active($data->objecttype)) {
            catalog_storage::populate_provider_data(provider_handler::instance()->get_provider($data->objecttype));
        }
    }
}
