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

use totara_program\totara_catalog\program\observer\settings_observer as program_settings_observer;

class settings_observer extends program_settings_observer {

    // Override parent class to work with certifications.
    const OBJECT_TYPE = 'certification';

    /**
     * Override parent class to work with certifications.
     */
    protected static function is_module_enabled(): bool {
        global $CFG;

        $enablecertifications = (int)$CFG->enablecertifications;
        if ($enablecertifications != TOTARA_DISABLEFEATURE) {
            return true;
        }

        return false;
    }

    /**
     * Check setting changed. Override in subclass.
     *
     * @param array $data containing the event data
     * @return bool
     */
    protected static function is_setting_changed(array $data): bool {
        global $CFG;

        if (isset($data['other']['olddata']['s__enablecertifications']) &&
            (int)$data['other']['olddata']['s__enablecertifications'] != (int)$CFG->enablecertifications) {
            return true;
        }

        return false;
    }
}
