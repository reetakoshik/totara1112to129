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

namespace totara_userdata\rb\display;

use \totara_reportbuilder\rb\display\base;
use \core\output\flex_icon;

/**
 * Purge related actions.
 */
final class purge_actions extends base {
    /**
     * Display data.
     *
     * @param string $value
     * @param string $format
     * @param \stdClass $row
     * @param \rb_column $column
     * @param \reportbuilder $report
     * @return string
     */
    public static function display($value, $format, \stdClass $row, \rb_column $column, \reportbuilder $report) {
        global $OUTPUT, $USER;

        if (!$value) {
            return '';
        }

        if ($format !== 'html') {
            return '';
        }

        $purge = self::get_extrafields_row($row, $column);
        $purge->id = $value;

        $buttons = array();

        if (has_capability('totara/userdata:purgemanual', \context_system::instance())) {
            if ($purge->origin === 'manual' and $purge->result === null and $purge->usercreated == $USER->id) {
                $returnurl = deleted_user_actions::get_return_url($report)->out_as_local_url(false);
                $actionurl = new \moodle_url('/totara/userdata/purge_manually_cancel.php', array('id' => $purge->id, 'returnurl' => $returnurl, 'sesskey' => sesskey()));
                $buttons[] = $OUTPUT->action_icon($actionurl, new flex_icon('delete', array('alt' => get_string('cancel'))));
            }
        }

        return implode('', $buttons);
    }
}
