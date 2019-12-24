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

use core_collator;

defined('MOODLE_INTERNAL') || die();

/**
 * General helpers for user data management.
 *
 * NOTE: This is not a public API - do not use in plugins or 3rd party code!
 */
final class util {

    /**
     * Returns list of item classes available in the system.
     *
     * @return string[] list of class names
     */
    public static function get_item_classes() {
        return \core_component::get_namespace_classes('userdata', 'totara_userdata\userdata\item');
    }

    /**
     * Returns localised component name.
     *
     * @param string $component
     * @return string
     */
    public static function get_component_name($component) {
        // NOTE: override component names as necessary here to make the UI pretty and easy to understand.

        // DO NOT add strings for plugins here.
        // Only core components need to be added here, and that is because they don't have the same structure as plugins.
        // All plugins should be handled within the get_component_string() function below.
        // Keep digging and you will find the answer to your question.
        switch ($component) {
            case 'core_user':
                return get_string('user');
            case 'core_badges':
                return get_string('badges', 'core_badges');
            case 'core_question':
                return get_string('questionbank', 'core_question');
            case 'core_course':
                return get_string('courses', 'core');
            case 'core_message':
                return get_string('messaging', 'core_message');
            case 'core_enrol':
                return get_string('enrolments', 'enrol');
            case 'tool_log':
                return get_string('log', 'admin');
            case 'core_grades':
                return get_string('gradebook', 'core_grades');
            case 'core_completion':
                return get_string('userdatacomponentname', 'completion');
        }

        return get_component_string($component, CONTEXT_SYSTEM);
    }

    /**
     * Get sorted group labels for array of components
     *
     * @param array $groupeditems Array of components to get labels for
     * @return array
     */
    public static function get_sorted_grouplabels(array $groupeditems): array {
        $grouplabels = [];
        foreach ($groupeditems as $maincomponent) {
            if ($maincomponent !== 'core_user') {
                $grouplabels[$maincomponent] = self::get_component_name($maincomponent);
            }
        }
        core_collator::asort($grouplabels);
        // We want core_user component always to be the first element.
        if (in_array('core_user', $groupeditems)) {
            $userlabel = ['core_user' => self::get_component_name('core_user')];
            $grouplabels = array_merge($userlabel, $grouplabels);
        }
        return $grouplabels;
    }


    /**
     * Back up user context so that we know it after user gets deleted,
     * this also pre-creates records in totara_userdata_user table to simplify code elsewhere.
     *
     * @param int $userid
     * @param int $contextid
     */
    public static function backup_user_context_id($userid, $contextid) {
        global $DB;

        try {
            $extra = $DB->get_record('totara_userdata_user', array('userid' => $userid), 'id,usercontextid');
            if ($extra) {
                if ($extra->usercontextid != $contextid) {
                    $DB->set_field('totara_userdata_user', 'usercontextid', $contextid, array('userid' => $userid));
                }
            } else {
                $extra = new \stdClass();
                $extra->userid = $userid;
                $extra->usercontextid = $contextid;
                $DB->insert_record('totara_userdata_user', $extra);
            }
        } catch (\Throwable $ex) {
            // This can happen in upgrades only, ignore it.
        }

        return;
    }

    /**
     * Try to recover previously deleted user context with the old context id
     * in case we backed it up previously.
     *
     * @param int $userid
     * @return false|\stdClass
     */
    public static function recover_user_context($userid) {
        global $DB;
        try {
            $extra = $DB->get_record('totara_userdata_user', array('userid' => $userid));
            if ($extra and $extra->usercontextid) {
                if (!$DB->record_exists('context', array('id' => $extra->usercontextid))) {
                    $record = new \stdClass();
                    $record->id = $extra->usercontextid;
                    $record->instanceid = $userid;
                    $record->contextlevel = CONTEXT_USER;
                    $record->depth = 2;
                    $record->path = '/' . SYSCONTEXTID . '/' . $record->id;
                    $DB->import_record('context', $record);
                    // Do not reset sequences here, we do not want to reuse deleted context ids!
                    $record = $DB->get_record('context', array('id' => $record->id), '*', MUST_EXIST);
                    return $record;
                }
            }
        } catch (\Throwable $ex) {
            // This can happen in upgrades only, ignore it.
        }

        return false;
    }

    /**
     * Get extra user data record from totara_userdata_user
     * and create it if it does not exist yet.
     *
     * WARNING: this must match self::sync_totara_userdata_user_table() and observer logic!
     *
     * @param int $userid
     * @param string $fields requested fields for returned record
     * @return \stdClass
     */
    public static function get_user_extras($userid, $fields = '*') {
        global $DB;

        $now = time();

        $user = $DB->get_record('user', array('id' => $userid), 'id,deleted,suspended,timemodified', MUST_EXIST);
        $timemodified = ($user->timemodified and $user->timemodified <= $now) ? $user->timemodified : $now;
        $record = $DB->get_record('totara_userdata_user', array('userid' => $userid), '*');

        if (!$record) {
            $usercontext = \context_user::instance($user->id, IGNORE_MISSING);

            $record = new \stdClass();
            $record->userid = $user->id;
            $record->usercontextid = $usercontext ? $usercontext->id : null;
            $record->timedeleted = $user->deleted ? $timemodified : null;
            $record->timesuspended = $user->suspended ? $timemodified : null;

            $record->id = $DB->insert_record('totara_userdata_user', $record);
            return $DB->get_record('totara_userdata_user', array('id' => $record->id), $fields, MUST_EXIST);
        }

        $updates = array();
        if ($user->deleted) {
            if (!$record->timedeleted) {
                $updates['timedeleted'] = $timemodified;
            }
            if (!$user->suspended) {
                if ($record->timesuspended !== null) {
                    $updates['timesuspended'] = null;
                }
            }

        } else {
            $usercontext = \context_user::instance($user->id, IGNORE_MISSING);
            if ($usercontext and $usercontext->id != $record->usercontextid) {
                // This should not happen, somebody must be messing with context table data.
                $updates['usercontextid'] = $usercontext->id;
            }
            if ($record->timedeleted !== null) {
                $updates['timedeleted'] = null;
            }
            if ($user->suspended) {
                if (!$record->timesuspended) {
                    $updates['timesuspended'] = $timemodified;
                }
            } else {
                if ($record->timesuspended !== null) {
                    $updates['timesuspended'] = null;
                }
            }
        }

        if (!$updates) {
            if ($fields !== '*') {
                return $DB->get_record('totara_userdata_user', array('id' => $record->id), $fields, MUST_EXIST);
            }
            return $record;
        }

        $updates['id'] = $record->id;
        $DB->update_record('totara_userdata_user', (object)$updates);
        return $DB->get_record('totara_userdata_user', array('id' => $record->id), $fields, MUST_EXIST);
    }

    /**
     * Synchronise records in 'totara_userdata_user' with 'user' database table.
     *
     * WARNING: this must match self::get_user_extras() and observer logic!
     *
     */
    public static function sync_totara_userdata_user_table() {
        global $DB;

        // NOTE: performance is important here, no fancy APIs here, raw DB access is ok.

        // Add missing records first.
        $now = time();
        $sql = "SELECT u.id, u.suspended, u.deleted, u.timemodified, c.id AS usercontextid
                  FROM {user} u
             LEFT JOIN {context} c ON (c.contextlevel = :userlevel AND c.instanceid = u.id)
             LEFT JOIN {totara_userdata_user} tuu ON tuu.userid = u.id
                 WHERE tuu.id IS NULL";
        $params = array('userlevel' => CONTEXT_USER);
        $users = $DB->get_recordset_sql($sql, $params);
        foreach ($users as $user) {
            $timemodified = ($user->timemodified and $user->timemodified <= $now) ? $user->timemodified : $now;
            $extra = new \stdClass();
            $extra->userid = $user->id;
            $extra->usercontextid = $user->usercontextid;
            if ($user->deleted) {
                $extra->timedeleted = $timemodified;
            } else if ($user->suspended) {
                $extra->timesuspended = $timemodified;
            }

            try {
                $DB->insert_record('totara_userdata_user', $extra);
            } catch (\Exception $ex) {
                // Ignore, most likely result of concurrent execution if adding large number of records.
            }
        }
        $users->close();

        // Fix changed and missing user context ids.
        $sql = "SELECT u.id, c.id AS usercontextid
                  FROM {user} u
                  JOIN {context} c ON (c.contextlevel = :userlevel AND c.instanceid = u.id)
                  JOIN {totara_userdata_user} tuu ON tuu.userid = u.id
                 WHERE tuu.usercontextid IS NULL OR tuu.usercontextid <> c.id";
        $params = array('userlevel' => CONTEXT_USER);
        $users = $DB->get_recordset_sql($sql, $params);
        foreach ($users as $user) {
            $DB->set_field('totara_userdata_user', 'usercontextid', $user->usercontextid, array('userid' => $user->id));
        }
        $users->close();

        // Remove invalid timesuspended flag.
        $sql = "SELECT u.id
                  FROM {user} u
                  JOIN {totara_userdata_user} tuu ON tuu.userid = u.id
                 WHERE u.suspended = 0 AND tuu.timesuspended IS NOT NULL";
        $users = $DB->get_recordset_sql($sql);
        foreach ($users as $user) {
            $DB->set_field('totara_userdata_user', 'timesuspended', null, array('userid' => $user->id));
        }
        $users->close();

        // Remove invalid timedeleted flag.
        $sql = "SELECT u.id
                  FROM {user} u
                  JOIN {totara_userdata_user} tuu ON tuu.userid = u.id
                 WHERE u.deleted = 0 AND tuu.timedeleted IS NOT NULL";
        $users = $DB->get_recordset_sql($sql);
        foreach ($users as $user) {
            $DB->set_field('totara_userdata_user', 'timedeleted', null, array('userid' => $user->id));
        }
        $users->close();

        // Add missing timesuspended flag.
        $now = time();
        $sql = "SELECT u.id, u.timemodified
                  FROM {user} u
                  JOIN {totara_userdata_user} tuu ON tuu.userid = u.id
                 WHERE u.suspended = 1 AND u.deleted = 0 AND tuu.timesuspended IS NULL";
        $users = $DB->get_recordset_sql($sql);
        foreach ($users as $user) {
            $timemodified = ($user->timemodified and $user->timemodified <= $now) ? $user->timemodified : $now;
            $DB->set_field('totara_userdata_user', 'timesuspended', $timemodified, array('userid' => $user->id));
        }
        $users->close();

        // Add missing timedeleted flag.
        $now = time();
        $sql = "SELECT u.id, u.timemodified
                  FROM {user} u
                  JOIN {totara_userdata_user} tuu ON tuu.userid = u.id
                 WHERE u.deleted = 1 AND tuu.timedeleted IS NULL";
        $users = $DB->get_recordset_sql($sql);
        foreach ($users as $user) {
            $timemodified = ($user->timemodified and $user->timemodified <= $now) ? $user->timemodified : $now;
            $DB->set_field('totara_userdata_user', 'timedeleted', $timemodified, array('userid' => $user->id));
        }
        $users->close();
    }
}