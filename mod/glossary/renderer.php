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
 * @package mod_glossary
 */

defined('MOODLE_INTERNAL') || die();

class mod_glossary_renderer extends plugin_renderer_base {

    private const GLOSSARY_RECENT_ACTIVITY_LIMIT = 50;

    /**
     * Renders Recent activity to go in the recent activity block
     *  bassically warapper for {@link render_recent_activity()}
     *
     * @param array $activities array of stdClasses from {@link glossary_get_recent_mod_activity()}
     * @param bool $viewfullnames
     * @return string
     */
    public function render_recent_activities(array $activities, bool $viewfullnames=true) :string {
        if (count($activities) == 0) {
            return '';
        }
        $output = html_writer::tag('h3', get_string('newentries', 'glossary') . ':', ['class' => 'sectionname']);
        $count = 0;
        foreach ($activities as $activity) {
            if ($count > self::GLOSSARY_RECENT_ACTIVITY_LIMIT) {
                $totalentries = count($activities);
                $output .= '<div class="head"><div class="activityhead">'
                    .get_string('andmorenewentries', 'glossary', $totalentries - self::GLOSSARY_RECENT_ACTIVITY_LIMIT)
                    .'</div></div>';
                break;
            }
            $output .= print_recent_activity_note($activity->timestamp,
                $activity->user,
                $activity->text,
                $activity->link,
                true,
                $viewfullnames,
                null,
                isset($activity->extratext) ? $activity->extratext : '');
            $count += 1;
        }
        return $output;
    }
}
