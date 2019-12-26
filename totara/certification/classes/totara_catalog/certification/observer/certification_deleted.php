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
 * @package totara_certification
 * @category totara_catalog
 */

namespace totara_certification\totara_catalog\certification\observer;

defined('MOODLE_INTERNAL') || die();

use totara_program\totara_catalog\program\observer\program_deleted;

class certification_deleted extends program_deleted {

    /**
     * Override parent class to work with certifications.
     */
    protected function is_applicable_change(int $objectid): bool {
        // The logic that was previously here always returned false, because the {prog} record
        // it referred to had already been deleted by the time this event was created.  This
        // lead to orphaned certification records being left in the {catalog} table.
        return true;
    }
}
