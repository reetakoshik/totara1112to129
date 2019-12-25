<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @author Vernon Denny <vernon.denny@totaralearning.com>
 * @package core_completion
 */

namespace totara_core\task;

defined('MOODLE_INTERNAL') || die();
/**
 * Class handling deletion of orphaned completion records.
 *
 * It is possible for cron to start running before the course delete steps have all completed, resulting
 * in data integrity issues, e.g. existence of course completion data, but no corresponding course.
 *
 */

class completion_orphans_task extends \core\task\scheduled_task {

    /**
     * Get a descriptive name for this task (shown to administrators).
     *
     * @return string
     */
    public function get_name() {
        return get_string('deletecompletionorphanstask', 'totara_core');
    }

    /**
     * Delete orphaned completion records task.
     *
     */
    public function execute() {
        global $DB;

        // Delete any orphaned course completions records that may exist.
        $DB->execute('DELETE FROM {course_completions} WHERE course NOT IN (SELECT id FROM {course})');
    }
}
