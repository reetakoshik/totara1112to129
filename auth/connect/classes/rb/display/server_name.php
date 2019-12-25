<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2015 onwards Totara Learning Solutions LTD
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
 * @author Petr Skoda <petr.skoda@totaralms.com>
 * @package auth_connect
 */

namespace auth_connect\rb\display;
use \auth_connect\util;

/**
 * Class describing column display formatting.
 *
 * @author Petr Skoda <petr.skoda@totaralms.com>
 * @package auth_connect
 */
class server_name extends \totara_reportbuilder\rb\display\base {
    public static function display($value, $format, \stdClass $row, \rb_column $column, \reportbuilder $report) {
        global $OUTPUT;

        if ($format !== 'html') {
            return $value;
        }

        if (!has_capability('moodle/site:config', \context_system::instance())) {
            return $value;
        }

        if (!$report->embedded) {
            // Editing in embedded only, sorry.
            return $value;
        }

        $extra = self::get_extrafields_row($row, $column);
        $actions = array();

        if ($extra->server_status == util::SERVER_STATUS_OK) {
            $url = new \moodle_url('/auth/connect/server_edit.php', array('id' => $extra->server_id));
            $actions[] = $OUTPUT->action_icon($url, new \pix_icon('t/edit', get_string('edit')));

            $url = new \moodle_url('/auth/connect/server_sync.php', array('id' => $extra->server_id, 'sesskey' => sesskey()));
            $actions[] = $OUTPUT->action_icon($url, new \pix_icon('t/reload', get_string('sync', 'auth_connect')));

            $url = new \moodle_url('/auth/connect/server_delete.php', array('id' => $extra->server_id));
            $actions[] = $OUTPUT->action_icon($url, new \pix_icon('t/delete', get_string('delete')));

        } else {
            // The deleting failed previously, let them retry.
            $url = new \moodle_url('/auth/connect/server_delete.php', array('id' => $extra->server_id));
            $actions[] = $OUTPUT->action_icon($url, new \pix_icon('t/delete', get_string('delete')));
        }

        if ($actions) {
            $value .= ' ' . implode('', $actions);
        }

        return $value;
    }

    public static function is_graphable(\rb_column $column, \rb_column_option $option, \reportbuilder $report) {
        return false;
    }
}
