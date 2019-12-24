<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
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
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 * @package totara_userdata
 */

namespace totara_userdata\local;

use totara_userdata\userdata\manager;
use totara_userdata\userdata\target_user;

defined('MOODLE_INTERNAL') || die();

/**
 * Event observer - keeps 'totara_userdata_user' in sync with 'user' table.
 *
 * NOTE: Performance is very important here!
 *
 * WARNING: this must match util::sync_totara_userdata_user_table() and util::sync_totara_userdata_user_table()
 */
final class observer {
    /**
     * Event observer.
     * @param \core\event\user_created $event
     */
    public static function user_created(\core\event\user_created $event) {
        global $DB;
        $now = time();

        $extra = $DB->get_record('totara_userdata_user', array('userid' => $event->objectid), 'id,userid');
        $user = $event->get_record_snapshot('user', $event->objectid);

        if (!$extra) {
            error_log("warning: totara_userdata_user record for user {$event->objectid} did not exist when it should");
            $extra = util::get_user_extras($event->objectid, 'id,userid');
        }

        // Set the defaults.
        if ($user->suspended) {
            $extra->timesuspended = $now;
        } else {
            $extra->timesuspended = null;
        }
        $extra->timesuspendedpurged = null;
        $extra->timedeleted = null;
        $extra->timedeletedpurged = null;

        $DB->update_record('totara_userdata_user', $extra);
    }

    /**
     * Event observer.
     * @param \core\event\user_updated $event
     */
    public static function user_updated(\core\event\user_updated $event) {
        global $DB;
        $now = time();

        // User id should not be reused, but if it is, override the old record.
        $extra = $DB->get_record('totara_userdata_user', array('userid' => $event->objectid), '*');
        $user = $event->get_record_snapshot('user', $event->objectid);

        if (!$extra) {
            error_log("warning: totara_userdata_user record for user {$event->objectid} did not exist when it should");
            $extra = util::get_user_extras($event->objectid, '*');
        }

        $updates = array();
        if ($extra->timedeleted !== null) {
            $updates['timedeleted'] = null;
        }
        if ($user->suspended) {
            if (!$extra->timesuspended) {
                $updates['timesuspended'] = $now;
            }
        } else {
            if ($extra->timesuspended !== null) {
                $updates['timesuspended'] = null;
            }
        }

        if (!$updates) {
            return;
        }

        $updates['id'] = $extra->id;
        $DB->update_record('totara_userdata_user', (object)$updates);
    }

    /**
     * Event observer.
     * @param \totara_core\event\user_suspended $event
     */
    public static function user_suspended(\totara_core\event\user_suspended $event) {
        global $DB;
        $now = time();

        $extra = $DB->get_record('totara_userdata_user', array('userid' => $event->objectid), '*');

        if (!$extra) {
            error_log("warning: totara_userdata_user record for user {$event->objectid} did not exist when it should");
            $extra = util::get_user_extras($event->objectid, '*');
        }

        // Now is the right time to apply suspend purge type defaults!
        if ($extra->suspendedpurgetypeid === null) {
            if ($defaultsuspendedpurgetypeid = get_config('totara_userdata', 'defaultsuspendedpurgetypeid')) {
                $types = manager::get_purge_types(target_user::STATUS_SUSPENDED, 'suspended');
                if (isset($types[$defaultsuspendedpurgetypeid])) {
                    $extra->suspendedpurgetypeid = $defaultsuspendedpurgetypeid;
                }
            }
        }

        $extra->usercontextid = $event->contextid;
        $extra->timesuspended = $now;
        $extra->timedeleted = null;

        $DB->update_record('totara_userdata_user', $extra);
    }

    /**
     * Event observer.
     * @param \core\event\user_deleted $event
     */
    public static function user_deleted(\core\event\user_deleted $event) {
        global $DB;
        $now = time();

        $extra = $DB->get_record('totara_userdata_user', array('userid' => $event->objectid), '*');

        if (!$extra) {
            error_log("warning: totara_userdata_user record for user {$event->objectid} did not exist when it should");
            $extra = util::get_user_extras($event->objectid, '*');
        }

        // Now is the right time to apply delete purge type defaults!
        if ($extra->deletedpurgetypeid === null) {
            if ($defaultdeletedpurgetypeid = get_config('totara_userdata', 'defaultdeletedpurgetypeid')) {
                $types = manager::get_purge_types(target_user::STATUS_DELETED, 'deleted');
                if (isset($types[$defaultdeletedpurgetypeid])) {
                    $extra->deletedpurgetypeid = $defaultdeletedpurgetypeid;
                }
            }
        }

        $extra->usercontextid = $event->contextid;
        $extra->timedeleted = $now;

        $DB->update_record('totara_userdata_user', $extra);
    }

    /**
     * Event observer.
     * @param \totara_core\event\user_undeleted $event
     */
    public static function user_undeleted(\totara_core\event\user_undeleted $event) {
        global $DB;
        $now = time();

        $extra = $DB->get_record('totara_userdata_user', array('userid' => $event->objectid), 'id,userid');
        $user = $event->get_record_snapshot('user', $event->objectid);

        if (!$extra) {
            error_log("warning: totara_userdata_user record for user {$event->objectid} did not exist when it should");
            $extra = util::get_user_extras($event->objectid, 'id,userid');
        }

        if ($user->suspended) {
            $extra->timesuspended = $now;
        } else {
            $extra->timesuspended = null;
        }

        $extra->usercontextid = $event->contextid;
        $extra->timedeleted = null;

        $DB->update_record('totara_userdata_user', $extra);
    }
}
