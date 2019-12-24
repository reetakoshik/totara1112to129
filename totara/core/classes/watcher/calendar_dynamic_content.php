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
 * @package totara_core
 */

namespace totara_core\watcher;

use \totara_core\hook\calendar_upcoming_event;



/**
 * Class for managing calendar upcoming events hooks.
 *
 *    \totara_core\hook\calendar_upcoming_events
 *        Gets called during building calendar upcoming events block.
 *
 * @package totara_core\watcher
 */
class calendar_dynamic_content {

    /**
     * Hook watcher that extends the user edit form with Totara specific elements.
     *
     * @param calendar_upcoming_event $hook
     */
    public static function create(calendar_upcoming_event $hook) {
        switch ($hook->event->modulename) {
            case 'facetoface':
                $seminarhook = new \mod_facetoface\hook\calendar_dynamic_content($hook->event, $hook->content);
                $seminarhook->execute();
                break;
            default:
                break;
        }
    }
}