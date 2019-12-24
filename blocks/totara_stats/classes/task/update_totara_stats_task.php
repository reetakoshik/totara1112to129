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
 * @package block_totara_stats
 */

namespace block_totara_stats\task;

class update_totara_stats_task extends \core\task\scheduled_task {

    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('updatetotarastatstask', 'block_totara_stats');
    }


    /**
     * Preprocess report groups
     */
    public function execute() {
        global $CFG;
        require_once($CFG->dirroot.'/blocks/totara_stats/locallib.php');

        $lastrun = (int)get_config('block_totara_stats', 'cronlastrun');
        if (empty($lastrun)) {
            // Set $lastrun to one month ago: (only process one month of historical stats).
            $lastrun = time() -(60*60*24*30);
        }
        if (time() > ($lastrun + (24*60*60))) {
            // If at least 24 hours since last run.
            require_once($CFG->dirroot.'/blocks/totara_stats/locallib.php');
            $nextrun = time();
            $stats = totara_stats_timespent($lastrun, $nextrun);
            foreach ($stats as $userid => $timespent) {
                // Insert daily stat for each user returned above into new stats table for reading.
                totara_stats_add_event($nextrun, $userid, STATS_EVENT_TIME_SPENT, '', $timespent);
            }
            set_config('cronlastrun', $nextrun, 'block_totara_stats');
        }
    }
}