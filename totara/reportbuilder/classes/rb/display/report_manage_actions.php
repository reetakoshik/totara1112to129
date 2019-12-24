<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2014 onwards Totara Learning Solutions LTD
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
 * Class describing column display formatting for the report actions column.
 *
 * Displays a set of icons, depending on your access rights which allow you to control
 * aspects of the report (such as edit, delete, clone or cache now).
 *
 * @author Simon Coggins <simon.coggins@totaralearning.com>
 * @package totara_reportbuilder
 */
class report_manage_actions extends base {
    public static function display($value, $format, \stdClass $row, \rb_column $column, \reportbuilder $report) {
        global $CFG, $PAGE;

        static $canmanageuserreports = null;
        static $canmanageembeddedreports = null;

        // Column uses noexport, but just to be sure...
        if ($format !== 'html') {
            return '';
        }

        $output = $PAGE->get_renderer('totara_reportbuilder');
        $syscontext = \context_system::instance();

        // Simple static cache to avoid duplicate capability checks.
        if (is_null($canmanageuserreports)) {
            $canmanageuserreports = has_capability('totara/reportbuilder:managereports', $syscontext);
        }
        if (is_null($canmanageembeddedreports)) {
            $canmanageembeddedreports = has_capability('totara/reportbuilder:manageembeddedreports', $syscontext);
        }

        // Retrieve the extra row data.
        $extra = self::get_extrafields_row($row, $column);

        // No actions to show if user can't manage this type of report.
        if (($extra->embedded && !$canmanageembeddedreports) ||
            (!$extra->embedded && !$canmanageuserreports)) {
            return '';
        }

        $strsettings = get_string('settings', 'totara_reportbuilder');
        $strreload = get_string('restoredefaults', 'totara_reportbuilder');
        $strclone = get_string('clonereport', 'totara_reportbuilder');
        $strdelete = get_string('delete', 'totara_reportbuilder');

        $editurl = new moodle_url('/totara/reportbuilder/general.php', ['id' => $value]);
        $deletereloadurl = new moodle_url('/totara/reportbuilder/delete.php', ['id' => $value, 'returnurl' => $PAGE->url->out_as_local_url(false)]);
        $cloneurl = new moodle_url('/totara/reportbuilder/clone.php', ['id' => $value, 'returnurl' => $PAGE->url->out_as_local_url(false)]);

        $settings = $output->action_icon($editurl, new pix_icon('/t/edit', $strsettings, 'moodle'), null,
            ['title' => $strsettings]);
        $reload = $output->action_icon($deletereloadurl, new pix_icon('/t/reload', $strreload, 'moodle'), null,
            ['title' => $strreload]);
        $clone = $output->action_icon($cloneurl, new pix_icon('/t/copy', $strclone, 'moodle'), null,
            ['title' => $strclone]);
        $delete = $output->action_icon($deletereloadurl, new pix_icon('/t/delete', $strdelete, 'moodle'), null,
            ['title' => $strdelete]);

        $out = "{$settings}";
        // Only offer clone option if they can manage user reports, as they need to
        // be able to manage user reports to do anything with the report they end up generating.
        if ($canmanageuserreports) {
            $out .= "{$clone}";
        }
        if (!empty($CFG->enablereportcaching) && !empty($extra->cache)) {
            $out .= $output->cachenow_button($value, true);
        }
        if (!empty($extra->embedded)) {
            $out .= $reload;
        } else {
            $out .= $delete;
        }

        return $out;
    }

    public static function is_graphable(\rb_column $column, \rb_column_option $option, \reportbuilder $report) {
        return false;
    }
}
