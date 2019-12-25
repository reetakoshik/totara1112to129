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
 * @author Oleg Demeshev <oleg.demeshev@totaralearning.com>
 * @package totara_completionimport
 */

/**
 * Clean course completion upload logs
 */
namespace totara_completionimport\task;

class clean_course_completion_upload_logs_task extends \core\task\scheduled_task {

    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('cleancoursecompletionuploadlogstask', 'totara_completionimport');
    }

    /**
     * Clean course completion upload logs.
     */
    public function execute() {
        global $DB;

        $loglifetime = get_config('complrecords', 'courseloglifetime');
        if ((int)$loglifetime > 0) {
            $time = time();
            $logcutoff = $time - ((int)$loglifetime * DAYSECS);
            if ($DB->execute("DELETE FROM {totara_compl_import_course}
                          WHERE timecreated < ?", array($logcutoff))) {
                mtrace(get_string('cleancomplete', 'totara_completionimport', 'course'));
            } else {
                mtrace(get_string('cleanfailed', 'totara_completionimport', 'course'));
            }
        }
    }
}