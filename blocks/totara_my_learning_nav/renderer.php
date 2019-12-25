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
 * @author Brian Barnes <brian.barnes@totaralms.com>
 * @package totara
 * @subpackage block_totara_my_learning_nav
 */
class block_totara_my_learning_nav_renderer extends plugin_renderer_base {
    /**
     * print out the Totara My Learning nav section
     * @return html_writer::table
     */
    public function my_learning_nav() {
        global $USER;
        if (!isloggedin() || isguestuser()) {
            return '';
        }

        $learningitems = array();

        $access = get_config(null, 'enablelearningplans');

        $usercontext = context_user::instance($USER->id);
        if (has_capability('totara/plan:accessplan', $usercontext) && $access == 1) {
            $text = get_string('learningplans', 'totara_core');
            $icon = new pix_icon('plan', $text, 'totara_core');
            $url = new moodle_url('/totara/plan/index.php');
            $item = $this->output->action_icon($url, $icon);
            $item .= html_writer::link($url, $text);
            $learningitems[] = $item;
        }

        $text = get_string('bookings', 'totara_core');
        $icon = new pix_icon('bookings', $text, 'totara_core');
        $url = new moodle_url('/my/bookings.php?userid=' . $USER->id);
        $item = $this->output->action_icon($url, $icon);
        $item .= html_writer::link($url, $text);
        $learningitems[] = $item;

        $text = get_string('recordoflearning', 'totara_core');
        $icon = new pix_icon('record', $text, 'totara_core');
        $url = new moodle_url('/totara/plan/record/index.php?userid='.$USER->id);
        $item = $this->output->action_icon($url, $icon);
        $item .= html_writer::link($url, $text);
        $learningitems[] = $item;

        return html_writer::alist($learningitems, array('class' => 'list'));
    }
}
