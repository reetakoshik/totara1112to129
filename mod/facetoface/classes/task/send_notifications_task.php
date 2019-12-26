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
 * @package mod_facetoface
 */

namespace mod_facetoface\task;

/**
 * Send facetoface notifications
 */
class send_notifications_task extends \core\task\scheduled_task {
    // Test mode.
    public $testing = false;

    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('sendnotificationstask', 'mod_facetoface');
    }

    /**
     * Finds all facetoface notifications that have yet to be mailed out, and mails them.
     */
    public function execute() {
        global $DB, $CFG;
        require_once($CFG->dirroot.'/mod/facetoface/lib.php');

        // Send notifications if enabled.
        $notificationdisable = get_config(null, 'facetoface_notificationdisable');
        if (empty($notificationdisable)) {
            // Find "instant" manual notifications that haven't yet been sent.
            if (!$this->testing) {
                mtrace('Checking for instant Face-to-face notifications');
            }

            $manual = $DB->get_records_select(
                'facetoface_notification',
                'type = ? AND issent <> ? AND status = 1',
                array(MDL_F2F_NOTIFICATION_MANUAL, MDL_F2F_NOTIFICATION_STATE_FULLY_SENT),
                '',
                'id'
                );
            if ($manual) {
                foreach ($manual as $notif) {
                    $notification = new \facetoface_notification((array)$notif);
                    $notification->send_to_users();
                    unset($notification);
                }
            }
            unset($manual);

            // Find scheduled notifications that haven't yet been sent.
            if (!$this->testing) {
                mtrace('Checking for scheduled Face-to-face notifications');
            }
            $sched = $DB->get_records_select(
                'facetoface_notification',
                'scheduletime IS NOT NULL
                AND (type = ? OR type = ?)
                AND status = 1',
                array(MDL_F2F_NOTIFICATION_SCHEDULED, MDL_F2F_NOTIFICATION_AUTO),
                '',
                'id');
            if ($sched) {
                foreach ($sched as $notif) {
                    $notification = new \facetoface_notification((array)$notif);
                    $notification->send_scheduled();
                    unset($notification);
                }
            }
            unset($sched);
        }

        // Find finish Sign-Up dates that expired to send notifications to.
        if (!$this->testing) {
            mtrace('Checking for expired Face-to-face sign-up period dates');
        }
        facetoface_notify_registration_ended();

        // Find any reservations that are too close to the start of the session and delete them.
        \mod_facetoface\reservations::remove_after_deadline($this->testing);

        // Notify of sessions that are under capacity.
        if (!$this->testing) {
            mtrace("Checking for sessions below minimum bookings");
        }
        facetoface_notify_under_capacity();

        return true;
    }
}
