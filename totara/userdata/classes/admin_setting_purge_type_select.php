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
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 * @package totara_userdata
 */

use \totara_userdata\userdata\manager;
use \totara_userdata\userdata\target_user;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/adminlib.php');

/**
 * Class for selection of default deleted and suspended purge type.
 *
 * NOTE: This is not a public API - do not use in plugins or 3rd party code!
 */
final class totara_userdata_admin_setting_purge_type_select extends admin_setting_configselect {
    private $origin;

    /**
     * Constructor.
     *
     * @param string $origin
     * @param string $name
     * @param string|lang_string $visiblename
     * @param string|lang_string $description
     */
    public function __construct($origin, $name, $visiblename, $description) {
        if ($origin !== 'deleted' and $origin !== 'suspended') {
            throw new \coding_exception('Invalid origin specified');
        }
        $this->origin = $origin;
        parent::__construct($name, $visiblename, $description, '', null);
    }

    /**
     * Load options.
     *
     * @return bool
     */
    public function load_choices() {
        if (is_array($this->choices)) {
            return true;
        }
        $this->choices = ['' => get_string('none')];
        try {
            if ($this->origin === 'deleted') {
                $userstatus = target_user::STATUS_DELETED;
            } else {
                $userstatus = target_user::STATUS_SUSPENDED;
            }
            $this->choices = $this->choices + manager::get_purge_types($userstatus, $this->origin);
        } catch (Exception $e) {
            // This happens during install.
        }
        return true;
    }
}
