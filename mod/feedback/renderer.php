<?php
/**
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
 * @author Andrew McGhie <andrew.mcghie@totaralearning.com>
 * @package mod_feedback
 */

defined('MOODLE_INTERNAL') || die();

class mod_feedback_renderer extends plugin_renderer_base{

    /**
     * Renders Recent activity to go in the recent activity block
     *  bassically warapper for {@link render_recent_activity()}
     *
     * @param array $activities array of stdClasses from {@link feedback_get_recent_mod_activity()}
     * @param bool $viewfullnames
     * @return string
     */
    public function render_recent_activities(array $activities, bool $viewfullnames=true) :string {
        if (count($activities) == 0) {
            return '';
        }
        $output = html_writer::tag('h3', get_string('newresponse', 'feedback') . ':', ['class' => 'sectionname']);
        $output .= render_recent_activity_notes($activities, $viewfullnames);
        return $output;
    }
}