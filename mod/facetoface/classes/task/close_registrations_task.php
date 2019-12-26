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
 * @author David Curry <david.curry@totaralearning.com>
 * @package mod_facetoface
 */

namespace mod_facetoface\task;

/**
 * Check for sessions where the registration period has recently ended,
 * cancel any pending requests for the session and send the users a
 * notification so they know to try sign up to another session.
 */
class close_registrations_task extends \core\task\scheduled_task {
    // Test mode.
    public $testing = false;

    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('closeregistrationstask', 'mod_facetoface');
    }

    /**
     * Finds all facetoface sessions that have a closed registration period and cancels all pending requests.
     */
    public function execute() {
        global $DB, $CFG;
        require_once($CFG->dirroot.'/mod/facetoface/lib.php');

        if (!$this->testing) {
            mtrace('Checking for Face-to-face sessions with expired registration periods...');
        }

        $conditions = array('component' => 'mod_facetoface', 'classname' => '\mod_facetoface\task\close_registrations_task');
        $lastcron = $DB->get_field('task_scheduled', 'lastruntime', $conditions);
        $time = time();

        $sql = "SELECT s.*
                  FROM {facetoface_sessions} s
                 WHERE registrationtimefinish < :now
                   AND registrationtimefinish > 0
                   AND EXISTS (
                       SELECT fs.id
                         FROM {facetoface_signups} fs
                         JOIN {facetoface_signups_status} fss
                           ON fss.signupid = fs.id
                        WHERE (fss.statuscode = :req OR fss.statuscode = :adreq)
                          AND fs.sessionid = s.id
                       )
              ORDER BY s.facetoface, s.id";
        $params = array(
            'now'      => $time,
            'req' => \mod_facetoface\signup\state\requested::get_code(),
            'adreq' => \mod_facetoface\signup\state\requestedadmin::get_code(),
        );

        $sessions = $DB->get_records_sql($sql, $params);

        foreach ($sessions as $session) {
            facetoface_cancel_pending_requests($session);
        }

        return true;
    }
}
