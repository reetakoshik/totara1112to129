<?php
/*
 * This file is part of Totara Learn
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
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 * @package enrol_totara_facetoface
 */

namespace enrol_totara_facetoface\task;

/**
 * Task for processing Totara Seminar enrolments that have expired.
 *
 * @package enrol_totara_facetoface
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 */
class process_expirations extends \core\task\scheduled_task {

    /**
     * Get a descriptive name for this task.
     *
     * @return string
     */
    public function get_name() {
        return get_string('task_process_expirations', 'enrol_totara_facetoface');
    }

    /**
     * Performs the expiration of enrolments and the sending of expiration notifications.
     */
    public function execute() {
        // Check if the enrolment plugin is disabled - isn't really necessary as the task should not run if
        // the plugin is disabled, but there is no harm in making sure core hasn't done something wrong.
        if (!enrol_is_enabled('totara_facetoface')) {
            return;
        }

        /** @var \enrol_totara_facetoface_plugin $plugin */
        $plugin = enrol_get_plugin('totara_facetoface');
        $plugin->sync(new \text_progress_trace());
        // We use a new trace here as sync calls >finished() on the trace it is given before returning.
        $plugin->send_expiry_notifications(new \text_progress_trace());
    }

}