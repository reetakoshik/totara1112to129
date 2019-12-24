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
 * @author Simon Coggins <simon.coggins@totaralearning.com>
 * @package hierarchy_goal
 */

namespace hierarchy_goal;

defined('MOODLE_INTERNAL') || die();

class plugininfo extends \totara_hierarchy\plugininfo\hierarchy {
    public function get_usage_for_registration_data() {
        global $DB, $CFG;
        $data = array();
        $data['numpersonalgoals'] = $DB->count_records('goal_personal');
        $data['numcompanygoals'] = $DB->count_records('goal');
        $data['goalsenabled'] = (int)!empty($CFG->enablegoals);

        return $data;
    }
}
