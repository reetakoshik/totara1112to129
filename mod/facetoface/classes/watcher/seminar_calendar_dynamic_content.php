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
 * @author Oleg Demeshev <oleg.demeshev@totaralearning.com>
 * @package mod_facetoface
 */

namespace mod_facetoface\watcher;

use \mod_facetoface\hook\calendar_dynamic_content;

/**
 * Class for managing Seminar upcoming events form hooks.
 *
 *    \mod_facetoface\hook\calendar_dynamic_content
 *        Gets called during building calendar upcoming events to add Sign-up link.
 *
 * @package totara_core\watcher
 */
class seminar_calendar_dynamic_content {

    /**
     * @param calendar_dynamic_content $hook
     */
    public static function signup(calendar_dynamic_content $hook) {
        global $USER, $PAGE, $DB;

        if (!$session = facetoface_get_session($hook->event->uuid)) {
            return;
        }

        $content = '';
        $class = 'pull-right';

        if (facetoface_check_signup($session->facetoface, $session->id)) {
            $class .= ' text-uppercase label label-default';
            $content = get_string('booked', 'mod_facetoface');
        } else if (facetoface_can_user_signup($session, $USER->id)) {
            $facetoface = $DB->get_record('facetoface', array('id' => $session->facetoface), 'multiplesessions');
            if (!facetoface_has_unarchived_signups($session->facetoface, $USER->id)
                || $facetoface->multiplesessions) {

                $content = \html_writer::link(
                    new \moodle_url('/mod/facetoface/signup.php',
                        array('s' => $hook->event->uuid,
                            'returnurl' => $PAGE->url,
                        )
                    ),
                    get_string('signup', 'mod_facetoface'),
                    array('class' => 'btn btn-default btn-sm')
                );
            }
        }
        if (!empty($content)) {
            $hook->content .= \html_writer::div($content, $class);
            $hook->content .= \html_writer::div('&nbsp;', 'clearfix');
        }
    }
}