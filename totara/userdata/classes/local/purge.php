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

use \totara_userdata\userdata\item;
use totara_userdata\userdata\target_user;

defined('MOODLE_INTERNAL') || die();

/**
 * Helpers for user data purge.
 *
 * NOTE: This is not a public API - do not use in plugins or 3rd party code!
 */
final class purge {
    /* Maximum allowed time for purge of one item */
    public const MAX_ITEM_EXECUTION_TIME = 60 * 60 * 1;

    /** How long do we allow purge to run? */
    public const MAX_TOTAL_EXECUTION_TIME = 60 * 60 * 24 * 2;

    /**
     * Returns names of allowed purge origins.
     *
     * @return string[]
     */
    public static function get_origins() {
        return array(
            'manual' => get_string('purgeoriginmanual', 'totara_userdata'),
            'deleted' => get_string('purgeorigindeleted', 'totara_userdata'),
            'suspended' => get_string('purgeoriginsuspended', 'totara_userdata'),
            'other' => get_string('purgeoriginother', 'totara_userdata'),
        );
    }

    /**
     * Returns list of item classes available in the system
     * that support user data purging.
     *
     * @param int $userstatus target_user::STATUS_ACTIVE, target_user::STATUS_DELETED or target_user::STATUS_SUSPENDED
     * @return string[] list of class names
     */
    public static function get_purgeable_item_classes(int $userstatus) {
        $classes = array();

        /** @var item $class this is not an instance, but it helps with autocomplete */
        foreach (util::get_item_classes() as $class) {
            if (!$class::is_purgeable($userstatus)) {
                continue;
            }
            $classes[] = $class;
        }

        return $classes;
    }

    /**
     * Returns list of all item classes that allow purging grouped by main component.
     *
     * This is intended for UI item visual grouping.
     *
     * @param int $userstatus target_user::STATUS_ACTIVE, target_user::STATUS_DELETED or target_user::STATUS_SUSPENDED
     * @return array nested lists of classes grouped by component
     */
    public static function get_purgeable_items_grouped_list(int $userstatus) {
        $classes = array();

        /** @var item $class this is not an instance, but it helps with autocomplete */
        foreach (self::get_purgeable_item_classes($userstatus) as $class) {
            $maincomponent = $class::get_main_component();
            if (!isset($classes[$maincomponent])) {
                $classes[$maincomponent] = array();
            }
            $classes[$maincomponent][$class] = $class::get_sortorder();
        }

        // Move 'User' to the top of the list.
        uksort($classes, function($a, $b) { return $b === 'core_user'; });

        // Sort user data items within components using sortorder defined in items.
        foreach ($classes as $maincomponent => $items) {
            asort($items, SORT_NUMERIC);
            $classes[$maincomponent] = array_keys($items);
        }

        return $classes;
    }

    /**
     * Is there a pending purge already?
     *
     * @param string $origin
     * @param int $purgetypeid
     * @param int $userid
     * @param int $contextid
     * @return bool
     */
    public static function is_execution_pending($origin, $purgetypeid, $userid, $contextid) {
        global $DB;
        $params = array('origin' => $origin, 'purgetypeid' => $purgetypeid, 'userid' => $userid, 'contextid' => $contextid);
        $select = "result IS NULL AND origin = :origin AND purgetypeid = :purgetypeid AND userid = :userid AND contextid = :contextid";
        return $DB->record_exists_select('totara_userdata_purge', $select, $params);
    }

    /**
     * Execute user data purge.
     *
     * @param \stdClass $purge
     * @return int purge result
     */
    public static function purge_items(\stdClass $purge) {
        global $DB;

        // This cannot run in transaction, it could lock up everything or run out of memory!
        $DB->transactions_forbidden();

        $oldtimelimit = ini_get('max_execution_time');

        $requester =  $DB->get_record('user', array('id' => $purge->usercreated, 'deleted' => 0), '*', MUST_EXIST);
        cron_setup_user($requester);

        $user = $DB->get_record('user', array('id' => $purge->userid), 'id,deleted,suspended');
        if (!$user) {
            return item::RESULT_STATUS_ERROR;
        }
        $purgetype = $DB->get_record('totara_userdata_purge_type', array('id' => $purge->purgetypeid));
        if (!$purgetype) {
            return item::RESULT_STATUS_ERROR;
        }

        // Make sure the expected userstatus does not change!
        $userstatus = (int)$purgetype->userstatus;

        // Make sure the user context is still valid, fail if not.
        $extra = util::get_user_extras($user->id);
        if ($extra->usercontextid != $purge->usercontextid) {
            // User context id changed, weird!
            return item::RESULT_STATUS_ERROR;
        }
        if ($extra->usercontextid) {
            $uc = $DB->get_record('context', array('id' => $extra->usercontextid));
            if ($uc) {
                if ($uc->contextlevel != CONTEXT_USER or $uc->instanceid != $user->id) {
                    // Something reused the context, we must not continue!
                    return item::RESULT_STATUS_ERROR;
                }
            }
        }

        // Normalise the data types.
        $context = \context::instance_by_id($purge->contextid, IGNORE_MISSING);
        if (!$context) {
            // The requested context was deleted in the meantime, stop!
            return item::RESULT_STATUS_ERROR;
        }

        $usersql = "SELECT u.*, p.result AS purgeresult
                        FROM {user} u
                        JOIN {totara_userdata_purge} p ON p.userid = u.id
                       WHERE p.id = :purgeid";
        $userparams = array('purgeid' => $purge->id);

        $items = $DB->get_records('totara_userdata_purge_type_item', array('purgetypeid' => $purge->purgetypeid, 'purgedata' => 1));
        $enabled = array();
        foreach ($items as $item) {
            $enabled[$item->component . '\\' . 'userdata' . '\\' . $item->name] = true;
        }
        unset($items);
        $classes = array();
        $groups = self::get_purgeable_items_grouped_list($userstatus); // Keep the order the same as in UI to prevent unexpected dependency problems.
        foreach ($groups as $list) {
            foreach ($list as $class) {
                if (empty($enabled[$class])) {
                    // Not enabled, skip it.
                    continue;
                }
                $classes[] = $class;
            }
        }
        unset($groups);
        foreach ($classes as $class) {
            /** @var item $class this is not an instance, but it helps with autocomplete */

            if (!$class::is_compatible_context_level($context->contextlevel)) {
                // Item not compatible with this level, no point adding record for this item.
                error_log('User data purge: item ' . $class . ' not compatible with context level ' . $context->contextlevel);
                continue;
            }

            // Make sure we can continue with next item, it might have been updated in the meantime.
            $user = $DB->get_record_sql($usersql, $userparams);
            if (!$user) {
                // Somebody must have deleted purge record, maybe the sysadmin panicked?
                return item::RESULT_STATUS_ERROR;
            }
            if ($user->purgeresult !== null) {
                // Some other process marked this as finished, abort and keep the existing status!
                return (int)$user->purgeresult;
            }
            unset($user->purgeresult);

            $targetuser = new target_user($user);
            if ($targetuser->status !== $userstatus) {
                // Somebody changed user status, better stop ASAP, we need to use different purge type.
                return item::RESULT_STATUS_CANCELLED;
            }

            $record = new \stdClass();
            $record->purgeid = $purge->id;
            $record->component = $class::get_component();
            $record->name = $class::get_name();
            $record->timestarted = time();
            $record->id = $DB->insert_record('totara_userdata_purge_item', $record);

            try {
                // We need to set some higher time limit, but we must not leave this unlimited.
                set_time_limit(self::MAX_ITEM_EXECUTION_TIME);
                $result = $class::execute_purge($targetuser, $context);

            } catch (\Throwable $ex) {
                $result = item::RESULT_STATUS_ERROR;
                $message = $ex->getMessage();
                if ($ex instanceof \moodle_exception) {
                    $message .= ' - ' . $ex->debuginfo;
                }
                if ($DB->is_transaction_started()) {
                    $DB->force_transaction_rollback();
                }
                debugging("Bug in item purge {$record->component} - {$record->name}: " . $message, DEBUG_DEVELOPER);
            }
            $record->timefinished = time();
            $record->result = $result;
            unset($record->purgeid);
            unset($record->component);
            unset($record->name);
            unset($record->timestarted);
            $DB->update_record('totara_userdata_purge_item', $record);
        }

        set_time_limit($oldtimelimit);

        // Aways return ok result if we get here, the individual items have separate result fields.
        return item::RESULT_STATUS_SUCCESS;
    }

}