<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2010 onwards Totara Learning Solutions LTD
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
 * @author Valerii Kuznetsov <valerii.kuznetsov@totaralms.com>
 * @package totara_dashboard
 */

class block_totara_dashboard_renderer extends plugin_renderer_base {
    /**
     * Displays the dashboards block
     *
     * @param array $dashboards the list of dashboards
     * @param int $currentid Current dashboard id
     *
     * @returns the rendered results
     */
    public function display_dashboards($dashboards, $currentid) {
        if (count($dashboards) <= 0) {
            return get_string('nodashboards', 'block_totara_dashboard');
        }

        $output = html_writer::start_tag('ul', array('class' => 'list'));
        foreach ($dashboards as $dashboard) {
            $output .= $this->display_dashboard($dashboard, $currentid);
        }
        $output .= html_writer::end_tag('ul');
        return $output;
    }

    /**
     * Displays a single dashboard result
     *
     * @param $dashboard The dashboard to display
     * @param $currentid Current dashboard id
     *
     * @returns the rendered dashboard
     */
    public function display_dashboard($dashboard, $currentid) {
        $url = new moodle_url('/totara/dashboard/index.php', array('id' => $dashboard->id));
        $output = html_writer::start_tag('li', array('class' => 'dashboard'));
        if ($dashboard->id == $currentid) {
            $output .= html_writer::tag('div', format_string($dashboard->name), array('class' => 'name active'));
        } else {
            $output .= html_writer::tag('div', html_writer::link($url, format_string($dashboard->name)), array('class' => 'name'));
        }
        $output .= html_writer::end_tag('li');
        return $output;
    }
}
