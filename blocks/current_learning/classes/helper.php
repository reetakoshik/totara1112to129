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
 * @author Alastair Munro <alastair.munro@totaralearning.com>
 * @package block_current_learning
 */

namespace block_current_learning;

defined('MOODLE_INTERNAL') || die();

class helper {

    /**
     * Calculate the state for an item according to the settings for the block.
     *
     * - If the due date is in the past                                      => danger + alert flag.
     * - If: (duedate - warning period) > now < duedate                      => danger.
     * - If: (duedate - warning period) = now < duedate                      => danger.
     * - If: (duedate - alert period)   > now < (duedate - warning period)   => warning.
     * - If: (duedate - alert period)   = now < (duedate - warning period)   => warning.
     * - If: now < (duedate - alert period)                                  => info.
     *
     * @param int $duedate The duedate for the item
     * @param \stdClass $config The block config object
     * @param int $now The current timestamp, if null then the current time is used.
     * @return array An array with a state string and flag for warning icon
     */
    public static function get_duedate_state($duedate, $config, $now = null) {
        if ($now === null) {
            $now = time();
        }
        $alertperiod = $duedate - $config->alertperiod;
        $warningperiod = $duedate - $config->warningperiod;

        $alert = false;

        if ($now < $alertperiod) {
            // Not due.
            $state = 'label-info';

        } else if ($now >= $alertperiod && $now < $warningperiod) {
            // Warning.
            $state = 'label-warning';

        } else if ($now >= $warningperiod && $now < $duedate) {
            // Alert.
            $state = 'label-danger';

        } else if ($now >= $duedate) {
            // Overdue.
            $state = 'label-danger';
            $alert = true;
        }

        return array(
            'state' => $state,
            'alert' => $alert
        );
    }
}
