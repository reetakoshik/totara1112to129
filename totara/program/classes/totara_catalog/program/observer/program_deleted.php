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
 * @package totara_program
 * @category totara_catalog
 */

namespace totara_program\totara_catalog\program\observer;

defined('MOODLE_INTERNAL') || die();

use totara_catalog\observer\object_update_observer;

class program_deleted extends object_update_observer {

    public function get_observer_events(): array {
        return [
            '\totara_program\event\program_deleted',
        ];
    }

    /**
     * init program remove object based on deletd program
     */
    protected function init_change_objects(): void {
        // This conditional is left here as a reminder that we may, at some future time,
        // require distinguished behaviours between programs and certifications, i.e.
        // program_deleted::is_applicable_change(), or certification_deleted::is_applicable_change().
        if ($this->is_applicable_change($this->event->objectid)) {
            $this->register_for_delete($this->event->objectid);
        }
    }

    /**
     * Check it's a applicable event change, override in subclasses
     *
     * @param int $objectid
     * @return bool
     */
    protected function is_applicable_change(int $objectid): bool {
        // The logic that was previously here always returned true, because $data['other']['certifid']
        // is always explicitly set to 0 by program::delete() for program objects.
        return true;
    }
}
