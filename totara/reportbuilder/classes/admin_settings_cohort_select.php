<?php
/**
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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package totara_reportbuilder
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->libdir . '/adminlib.php');

/**
 * Admin setting for export options in reportbuilder.
 */
class totara_reportbuilder_admin_settings_cohort_select extends admin_setting_configselect {

    public function __construct($name, $visiblename, $description, $defaultsetting, $choices = null) {
        parent::__construct($name, $visiblename, $description, $defaultsetting, $choices);
    }

    /**
     * Lazy-load the available choices for the select box
     */
    public function load_choices() {
        global $CFG;

        if (is_array($this->choices)) {
            return true;
        }

        require_once($CFG->dirroot . '/totara/reportbuilder/classes/rb_audience_content.php');

        $this->choices = rb_audience_content::get_cohort_select_options();

        return true;
    }

}
