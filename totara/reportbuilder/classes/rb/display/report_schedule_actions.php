<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2017 onwards Totara Learning Solutions LTD
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
 * @package totara_reportbuilder
 */

namespace totara_reportbuilder\rb\display;

use moodle_url;
use pix_icon;

/**
 * Class describing column display formatting.
 *
 * @author Simon Coggins <simon.coggins@totaralearning.com>
 * @package totara_reportbuilder
 */
class report_schedule_actions extends base {
    public static function display($value, $format, \stdClass $row, \rb_column $column, \reportbuilder $report) {
        global $CFG, $PAGE;

        $output = $PAGE->get_renderer('totara_reportbuilder');

        // Column uses noexport, but just to be sure...
        if ($format !== 'html') {
            return '';
        }

        $strsettings = get_string('settings', 'totara_reportbuilder');
        $strdelete = get_string('delete', 'totara_reportbuilder');

        $editurl = new moodle_url('/totara/reportbuilder/scheduled.php', ['id' => $value, 'returnurl' => $PAGE->url->out_as_local_url(false)]);
        $deleteurl = new moodle_url('/totara/reportbuilder/deletescheduled.php', ['id' => $value, 'returnurl' => $PAGE->url->out_as_local_url(false)]);

        $settings = $output->action_icon($editurl, new pix_icon('/t/edit', $strsettings, 'moodle'), null,
            ['title' => $strsettings]);
        $delete = $output->action_icon($deleteurl, new pix_icon('/t/delete', $strdelete, 'moodle'), null,
            ['title' => $strdelete]);

        $out = "{$settings}{$delete}";

        return $out;
    }

    public static function is_graphable(\rb_column $column, \rb_column_option $option, \reportbuilder $report) {
        return false;
    }
}
