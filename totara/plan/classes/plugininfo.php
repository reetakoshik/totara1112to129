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
 * @package totara_plan
 */

namespace totara_plan;

defined('MOODLE_INTERNAL') || die();

class plugininfo extends \core\plugininfo\totara {
    public function get_usage_for_registration_data() {
        global $DB, $CFG;
        $data = array();
        // Exclude default example plan template from count.
        $data['numtemplates'] = $DB->count_records_select('dp_template', 'fullname <> ?', array(get_string('learningplan', 'totara_plan')));
        $data['numplans'] = $DB->count_records('dp_plan');
        $data['plansenabled'] = (int)!empty($CFG->enablelearningplans);

        return $data;
    }
}
