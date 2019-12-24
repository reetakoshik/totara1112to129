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
 * @author Petr Skoda <petr.skoda@totaralms.com>
 * @author Oleg Demeshev <oleg.demeshev@totaralms.com>
 * @package mod_facetoface
 */

namespace totara_cohort\task;

/**
 * Cleanup audience assign roles.
 */
class cleanup_task extends \core\task\scheduled_task {

    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('cleanuptask', 'totara_cohort');
    }

    /**
     * Periodic cron cleanup.
     */
    public function execute() {
        global $CFG;

        require_once($CFG->dirroot . "/totara/cohort/rules/lib.php");

        $trace = new \text_progress_trace();

        // Cleanup audience assign roles.
        $trace->output(date("H:i:s", time()).' Syncing audience assign roles...');
        totara_cohort_process_assig_roles();
        $trace->output(date("H:i:s", time()). ' Finished syncing audience assign roles...');
    }
}
