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

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

/**
 * Output renderer for totara_dashboard
 */
class totara_dashboard_renderer extends plugin_renderer_base {
    /**
     * Return a button that when clicked, takes the user to new dashboard layout editor
     *
     * @return string HTML to display the button
     */
    public function create_dashboard_button() {
        $url = new moodle_url('/totara/dashboard/edit.php', array('action' => 'new', 'adminedit' => 1));
        return $this->output->single_button($url, get_string('createdashboard', 'totara_dashboard'), 'get');
    }

    /**
     * Renders a table containing dashboard list
     *
     * @param array $dashboards array of totara_dashboard object
     * @return string HTML table
     */
    public function dashboard_manage_table($dashboards) {
        if (empty($dashboards)) {
            return get_string('nodashboards', 'totara_dashboard');
        }

        $tableheader = array(get_string('name', 'totara_dashboard'),
                             get_string('availability', 'totara_dashboard'),
                             get_string('options', 'totara_dashboard'));

        $dashboardstable = new html_table();
        $dashboardstable->summary = '';
        $dashboardstable->head = $tableheader;
        $dashboardstable->data = array();
        $dashboardstable->attributes = array('class' => 'generaltable fullwidth');

        $strpublish = get_string('publish', 'totara_dashboard');
        $strunpublish = get_string('unpublish', 'totara_dashboard');
        $strdelete = get_string('delete', 'totara_dashboard');
        $stredit = get_string('editdashboard', 'totara_dashboard');
        $strclone = get_string('clonedashboard', 'totara_dashboard');

        $data = array();
        foreach ($dashboards as $dashboard) {
            $id = $dashboard->get_id();
            $name = format_string($dashboard->name);
            $urllayout = new moodle_url('/totara/dashboard/layout.php', array('id' => $id));
            $urledit = new moodle_url('/totara/dashboard/edit.php', array('id' => $id));
            $urlclone = new moodle_url('/totara/dashboard/manage.php', array('action' => 'clone', 'id' => $id, 'sesskey' => sesskey()));
            $urlpublish = new moodle_url('/totara/dashboard/manage.php', array('action' => 'publish', 'id' => $id, 'sesskey' => sesskey()));
            $urlunpublish = new moodle_url('/totara/dashboard/manage.php', array('action' => 'unpublish', 'id' => $id, 'sesskey' => sesskey()));
            $urlup = new moodle_url('/totara/dashboard/manage.php', array('action' => 'up', 'id' => $id, 'sesskey' => sesskey()));
            $urldown = new moodle_url('/totara/dashboard/manage.php', array('action' => 'down', 'id' => $id, 'sesskey' => sesskey()));
            $deleteurl = new moodle_url('/totara/dashboard/manage.php', array('action' => 'delete', 'id' => $id));

            $row = array();
            $row[] = html_writer::link($urllayout, $name);

            switch ($dashboard->get_published()) {
                case totara_dashboard::NONE:
                    $row[] = get_string('availablenone', 'totara_dashboard');
                    break;
                case totara_dashboard::AUDIENCE:
                    $cnt = count($dashboard->get_cohorts());
                    $row[] = get_string('availableaudiencecnt', 'totara_dashboard', $cnt);
                    break;
                case totara_dashboard::ALL:
                    $row[] = get_string('availableall', 'totara_dashboard');
                    break;
                default:
                    $row[] = get_string('availableunknown', 'totara_dashboard');
            }

            $options = '';
            $options .= $this->output->action_icon($urledit, new pix_icon('/t/edit', $stredit, 'moodle'), null,
                    array('class' => 'action-icon edit'));

            $options .= $this->output->action_icon($urlclone, new pix_icon('/t/copy', $strclone, 'moodle'), null,
                array('class' => 'action-icon clone'));

            if (!$dashboard->is_first()) {
                $options .= $this->output->action_icon($urlup, new pix_icon('/t/up', 'moveup', 'moodle'), null,
                        array('class' => 'action-icon up'));
            }
            if (!$dashboard->is_last()) {
                $options .= $this->output->action_icon($urldown, new pix_icon('/t/down', 'movedown', 'moodle'), null,
                        array('class' => 'action-icon down'));
            }

            $options .= $this->output->action_icon($deleteurl, new pix_icon('/t/delete', $strdelete, 'moodle'), null,
                        array('class' => 'action-icon delete'));
            $row[] = $options;

            $data[] = $row;
        }
        $dashboardstable->data = $data;

        return html_writer::table($dashboardstable);
    }
}