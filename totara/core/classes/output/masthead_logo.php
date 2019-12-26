<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2016 onwards Totara Learning Solutions LTD
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
 * @copyright 2016 onwards Totara Learning Solutions LTD
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Joby Harding <joby.harding@totaralearning.com>
 * @package   theme_roots
 */

namespace totara_core\output;

defined('MOODLE_INTERNAL') || die();

class masthead_logo implements \renderable, \templatable {

    /**
     * Implements export_for_template().
     *
     * @param \renderer_base $output
     * @return array
     */
    public function export_for_template(\renderer_base $output) {
        global $PAGE, $SITE, $OUTPUT, $CFG, $USER;

        $defaultpage = $CFG->wwwroot . '/';
        if (get_home_page() == HOMEPAGE_TOTARA_DASHBOARD) {
            $defaultpage = $CFG->wwwroot . '/totara/dashboard/index.php';

            require_once($CFG->dirroot . '/totara/dashboard/lib.php');
            $availabledash = array_keys(\totara_dashboard::get_user_dashboards($USER->id));

            //Update the homepage dashboard id
            $id = get_user_preferences('user_home_totara_dashboard_id', -1);
            if ($id != -1 && in_array($id, $availabledash)) {
                $defaultpage .= '?id=' . $id;
            }
        }

        $templatecontext = array(
            'siteurl' => $defaultpage,
            'shortname' => $SITE->shortname,
        );

        if (!empty($PAGE->theme->settings->logo)) {
            $templatecontext['logourl'] = $PAGE->theme->setting_file_url('logo', 'logo');
        }

        if (empty($templatecontext['logourl'])) {
            $templatecontext['logourl'] = $OUTPUT->image_url('logo', 'totara_core');
        }

        if (!empty($PAGE->theme->settings->alttext)) {
            $templatecontext['logoalt'] = format_string($PAGE->theme->settings->alttext);
        }

        if (empty($templatecontext['logoalt'])) {
            $templatecontext['logoalt'] = get_string('totaralogo', 'totara_core');
        }

        if (!empty($PAGE->theme->settings->favicon)) {
            $templatecontext['faviconurl'] = $PAGE->theme->setting_file_url('favicon', 'favicon');
        } else {
            $templatecontext['faviconurl'] = $OUTPUT->favicon();
        }

        return $templatecontext;
    }

}
