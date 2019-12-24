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

namespace totara_userdata\userdata;

defined('MOODLE_INTERNAL') || die();

/**
 * This is the public API for userdata plugin,
 * custom code and plugins can use this class.
 */
final class manager {
    /**
     * List of all result constants and their names.
     *
     * @return string[] localised names of all results as int=>name
     */
    public static function get_results() {
        return array(
            item::RESULT_STATUS_SUCCESS => get_string('resultsuccess', 'totara_userdata'),
            item::RESULT_STATUS_ERROR => get_string('resulterror', 'totara_userdata'),
            item::RESULT_STATUS_SKIPPED => get_string('resultkipped', 'totara_userdata'),
            item::RESULT_STATUS_CANCELLED => get_string('resultcancelled', 'totara_userdata'),
            item::RESULT_STATUS_TIMEDOUT => get_string('resulttimedout', 'totara_userdata'),
        );
    }

    /**
     * Returns human readable list of purge types as menu options.
     *
     * @param int $userstatus target_user::STATUS_xxx constant
     * @param string $origin purge origin, use 'other' for custom code
     * @param int $current current purge id, added to the list even if not available
     * @return string[] human readable names indexed by purgetypeid
     */
    public static function get_purge_types($userstatus, $origin = 'other', $current = null) {
        global $DB;

        if (!in_array($origin, array('manual', 'deleted', 'suspended', 'other'))) {
            throw new \coding_exception('Invalid purge origin, user "other" for custom code');
        }

        if ($userstatus != target_user::STATUS_ACTIVE and $userstatus != target_user::STATUS_SUSPENDED
            and $userstatus != target_user::STATUS_DELETED) {
            throw new \coding_exception('Invalid userstatus value');
        }

        $select = "userstatus = :userstatus";
        $params = array('userstatus' => $userstatus);
        if (!$current) {
            if ($origin !== 'other') {
                $select .= " AND allow{$origin} = 1";
            }
        } else {
            if ($origin !== 'other') {
                $select .= " AND (allow{$origin} = 1 OR id = :current)";
                $params['current'] = $current;
            }
        }
        $options = $DB->get_records_select_menu('totara_userdata_purge_type', $select, $params, '', 'id, fullname');
        $options = array_map('format_string', $options);
        \core_collator::asort($options);

        return $options;
    }

    /**
     * Returns human readable list of export types as menu options.
     *
     * @param string $origin export origin, use 'other' for custom code
     * @param int $current current export id, added to the list even if not available
     * @return string[] human readable names indexed by exporttypeid
     */
    public static function get_export_types($origin = 'other', $current = null) {
        global $DB;

        if (!in_array($origin, array('self', 'other'))) {
            throw new \coding_exception('Invalid export origin, user "other" for custom code');
        }

        $select = "";
        $params = array();
        if (!$current) {
            if ($origin !== 'other') {
                $select = " allow{$origin} = 1";
            }
        } else {
            if ($origin !== 'other') {
                $select = " (allow{$origin} = 1 OR id = :current)";
                $params['current'] = $current;
            }
        }
        $options = $DB->get_records_select_menu('totara_userdata_export_type', $select, $params, '', 'id, fullname');
        $options = array_map('format_string', $options);
        \core_collator::asort($options);

        return $options;
    }

    /**
     * Create a new purge.
     *
     * @param int $userid user being purged
     * @param int $contextid restricts purging to the context and its subcontexts
     * @param int $purgetypeid type of purging
     * @param string $origin purge origin, use 'other' for custom code
     * @return int new purge id
     */
    public static function create_purge($userid, $contextid, $purgetypeid, $origin = 'other') {
        global $DB, $USER;

        if (!in_array($origin, array('manual', 'deleted', 'suspended', 'other'))) {
            throw new \coding_exception('Invalid purge origin, user "other" for custom code');
        }

        $user = $DB->get_record('user', array('id' => $userid), '*', MUST_EXIST);
        $targetuser = new target_user($user);

        // NOTE: do not check allowxxx here, these options restrict future usage only.

        $purgetype = $DB->get_record('totara_userdata_purge_type', array('id' => $purgetypeid, 'userstatus' => $targetuser->status), '*', MUST_EXIST);
        $context = \context::instance_by_id($contextid);

        $purge = new \stdClass();
        $purge->purgetypeid = $purgetype->id;
        $purge->origin = $origin;
        $purge->userid = $user->id;
        $purge->usercontextid = $targetuser->contextid;
        $purge->contextid = $context->id;
        $purge->usercreated = $USER->id;
        $purge->timecreated = time();

        return $DB->insert_record('totara_userdata_purge', $purge);
    }

    /**
     * Execute data purge.
     *
     * This can be called from a scheduled task or CLI script only
     * because it may be a very long operation.
     *
     * @param int $purgeid
     * @return int result - see item::STATUS_XXX constants
     */
    public static function execute_purge($purgeid) {
        global $DB, $USER;

        if (!CLI_SCRIPT) {
            throw new \coding_exception('execute_purge() method can be used from CLI or cron tasks only!');
        }

        $olduserid = $USER->id;

        $purge = $DB->get_record('totara_userdata_purge', array('id' => $purgeid));
        if (!$purge) {
            return item::RESULT_STATUS_ERROR;
        }

        if (isset($purge->result)) {
            // Nothing to do, it is already finished.
            return (int)$purge->result;
        }

        if (isset($purge->timestarted)) {
            // Concurrent execution means something went very wrong, stop!
            $DB->set_field('totara_userdata_purge', 'result', item::RESULT_STATUS_ERROR, array('id' => $purge->id));
            return item::RESULT_STATUS_ERROR;
        }

        $timestarted = time();
        $purge->timestarted = (string)$timestarted;
        $DB->set_field('totara_userdata_purge', 'timestarted', $purge->timestarted, array('id' => $purge->id));

        $exception = null;
        try {
            $result = \totara_userdata\local\purge::purge_items($purge);
        } catch (\Throwable $ex) {
            $exception = $ex;
            $result = item::RESULT_STATUS_ERROR;
        }

        $update = new \stdClass();
        $update->id = $purge->id;
        $update->timefinished = time();
        $update->result = $result;

        if ($update->result == item::RESULT_STATUS_SUCCESS) {
            if ($purge->origin === 'suspended') {
                $DB->set_field('totara_userdata_user', 'timesuspendedpurged', $timestarted, array('userid' => $purge->userid, 'suspendedpurgetypeid' => $purge->purgetypeid));
            } else if ($purge->origin === 'deleted') {
                $DB->set_field('totara_userdata_user', 'timedeletedpurged', $timestarted, array('userid' => $purge->userid, 'deletedpurgetypeid' => $purge->purgetypeid));
            }
        }

        $DB->update_record('totara_userdata_purge', $update);

        $olduser = $DB->get_record('user', array('id' => $olduserid));
        cron_setup_user($olduser);

        if ($exception) {
            // Rethrow exception only after marking purge as failed.
            throw $exception;
        }

        return $result;
    }

    /**
     * Create a new export.
     *
     * @param int $userid user being exportd
     * @param int $contextid restricts exporting to the context and its subcontexts
     * @param int $exporttypeid type of exporting
     * @param string $origin export origin, use 'other' for custom code
     * @return int new export id
     */
    public static function create_export($userid, $contextid, $exporttypeid, $origin = 'other') {
        global $DB, $USER;

        if (!in_array($origin, array('self', 'other'))) {
            throw new \coding_exception('Invalid export origin, user "other" for custom code');
        }

        $conditions = array('id' => $exporttypeid);
        if ($origin !== 'other') {
            $conditions['allow' . $origin] = 1;
        }

        $exporttype = $DB->get_record('totara_userdata_export_type', $conditions, '*', MUST_EXIST);
        $user = $DB->get_record('user', array('id' => $userid), '*', MUST_EXIST);
        $extra = \totara_userdata\local\util::get_user_extras($userid, '*');
        $context = \context::instance_by_id($contextid);

        $export = new \stdClass();
        $export->exporttypeid = $exporttype->id;
        $export->origin = $origin;
        $export->userid = $user->id;
        $export->usercontextid = $extra->usercontextid;
        $export->contextid = $context->id;
        $export->usercreated = $USER->id;
        $export->timecreated = time();

        return $DB->insert_record('totara_userdata_export', $export);
    }

    /**
     * Execute data export.
     *
     * This can be called from a scheduled task or CLI script only
     * because it may be a very long operation.
     *
     * @param int $exportid
     * @return int result - see item::STATUS_XXX constants
     */
    public static function execute_export($exportid) {
        global $DB, $USER;

        if (!CLI_SCRIPT) {
            throw new \coding_exception('execute_export() method can be used from CLI or cron tasks only!');
        }

        $olduserid = $USER->id;

        $export = $DB->get_record('totara_userdata_export', array('id' => $exportid));
        if (!$export) {
            return item::RESULT_STATUS_ERROR;
        }

        if (isset($export->result)) {
            // Nothing to do, it is already finished.
            return (int)$export->result;
        }

        if (isset($export->timestarted)) {
            // Concurrent execution means something went very wrong, stop!
            $DB->set_field('totara_userdata_export', 'result', item::RESULT_STATUS_ERROR, array('id' => $export->id));
            return item::RESULT_STATUS_ERROR;
        }

        // Is this export type still allowed?
        $origins = \totara_userdata\local\export::get_origins();
        if (!isset($origins[$export->origin])) {
            $DB->set_field('totara_userdata_export', 'result', item::RESULT_STATUS_ERROR, array('id' => $export->id));
            return item::RESULT_STATUS_ERROR;
        }
        $permittedtypes = self::get_export_types($export->origin);
        if (!isset($permittedtypes[$export->exporttypeid])) {
            $DB->set_field('totara_userdata_export', 'result', item::RESULT_STATUS_ERROR, array('id' => $export->id));
            return item::RESULT_STATUS_ERROR;
        }

        $timestarted = time();
        $export->timestarted = (string)$timestarted;
        $DB->set_field('totara_userdata_export', 'timestarted', $export->timestarted, array('id' => $export->id));

        $exception = null;
        try {
            $result = \totara_userdata\local\export::export_items($export);
        } catch (\Throwable $ex) {
            $exception = $ex;
            $result = item::RESULT_STATUS_ERROR;
        }

        $update = new \stdClass();
        $update->id = $export->id;
        $update->timefinished = time();
        $update->result = $result;

        $DB->update_record('totara_userdata_export', $update);

        $olduser = $DB->get_record('user', array('id' => $olduserid));
        cron_setup_user($olduser);

        if ($exception) {
            // Rethrow exception only after marking export as failed.
            throw $exception;
        }

        return $result;
    }
}
