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
 * Actions for deleted users.
 */
final class deleted_user_actions extends base {
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
        global $OUTPUT, $CFG;

        if ($format !== 'html') {
            return '';
        }
        $buttons = array();

        $user = self::get_extrafields_row($row, $column);
        $user->id = $value;
        $syscontext = \context_system::instance();

        if (has_capability('totara/userdata:viewinfo', $syscontext)) {
            $actionurl = new \moodle_url('/totara/userdata/user_info.php', array('id' => $user->id));
            $buttons[] = $OUTPUT->action_icon($actionurl, new flex_icon('totara_userdata|icon', array('alt' => get_string('userinfo', 'totara_userdata'))));
        }

        // Legacy partially deleted accounts get special treatment.
        if (is_undeletable_user($user)) {
            if (has_capability('totara/core:undeleteuser', $syscontext)) {
                $actionurl = new \moodle_url('/user/action.php', array('id' => $user->id, 'returnto' => 'profile', 'action' => 'undelete'));
                $buttons[] = $OUTPUT->action_icon($actionurl, new flex_icon('recycle', array('alt' => get_string('undeleterecord', 'totara_reportbuilder', $user->fullname))));
            }
        }
        if (strpos($user->email, '@') !== false) {
            if ($CFG->authdeleteusers !== 'partial' and has_capability('moodle/user:delete', $syscontext)) {
                $returnurl = self::get_return_url($report)->out_as_local_url(false);
                $actionurl = new \moodle_url('/user/action.php', array('id' => $user->id, 'returnurl' => $returnurl, 'action' => 'delete'));
                $buttons[] = $OUTPUT->action_icon($actionurl, new flex_icon('delete', array('alt' => get_string('deleterecord', 'totara_reportbuilder', $user->fullname))));
            }
        }

        return implode('', $buttons);
    }

    /**
     * Back to the report.
     *
     * @param \reportbuilder $report
     * @return \moodle_url
     */
    public static function get_return_url(\reportbuilder $report) {
        $returnurl = new \moodle_url($report->get_current_url());
        $spage = optional_param('spage', '', PARAM_INT);
        if ($spage) {
            $returnurl->param('spage', $spage);
        }
        $perpage = optional_param('perpage', '', PARAM_INT);
        if ($perpage) {
            $returnurl->param('perpage', $perpage);
        }
        return $returnurl;
    }
}
