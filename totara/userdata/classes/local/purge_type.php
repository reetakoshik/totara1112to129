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
use totara_userdata\userdata\manager;
use totara_userdata\userdata\target_user;

defined('MOODLE_INTERNAL') || die();

/**
 * Helpers for user data purge type.
 *
 * NOTE: This is not a public API - do not use in plugins or 3rd party code!
 */
final class purge_type {
    /**
     * Prepare record for adding.
     *
     * @param int $userstatus ignored if $duplicate sepcified
     * @param int $duplicate copy data from this type if non-zero
     * @return \stdClass
     */
    public static function prepare_for_add($userstatus, $duplicate) {
        if ($duplicate) {
            $purgetype = self::prepare_for_update($duplicate);
            $purgetype->id = '0';
            $purgetype->fullname = get_string('purgetypecopyof', 'totara_userdata', $purgetype);
            $purgetype->idnumber = '';
            unset($purgetype->usercreated);
            unset($purgetype->timecreated);
            unset($purgetype->timechanged);
            return $purgetype;
        }

        $purgetype = new \stdClass();
        $purgetype->id = '0';
        $purgetype->userstatus = (string)$userstatus;
        $purgetype->fullname = '';
        $purgetype->idnumber = '';
        $purgetype->description = '';
        $purgetype->availablefor = array();
        return $purgetype;
    }

    /**
     * Prepare record for updating.
     *
     * @param int $id
     * @return \stdClass
     */
    public static function prepare_for_update($id) {
        global $DB;

        $purgetype = $DB->get_record('totara_userdata_purge_type', array('id' => $id), '*', MUST_EXIST);
        unset($purgetype->usercreated);
        unset($purgetype->timecreated);
        unset($purgetype->timechanged);
        $purgetype->availablefor = array();
        if ($purgetype->allowmanual) {
            $purgetype->availablefor[] = 'allowmanual';
        }
        unset($purgetype->allowmanual);
        if ($purgetype->allowdeleted) {
            $purgetype->availablefor[] = 'allowdeleted';
        }
        unset($purgetype->allowdeleted);
        if ($purgetype->allowsuspended) {
            $purgetype->availablefor[] = 'allowsuspended';
        }
        unset($purgetype->allowsuspended);

        $sql = "SELECT " . $DB->sql_concat_join("'-'", array('component', 'name')) . ", purgedata
                  FROM {totara_userdata_purge_type_item}
                 WHERE purgetypeid = :purgetypeid";
        $currentitems = $DB->get_records_sql_menu($sql, array('purgetypeid' => $purgetype->id));

        foreach (purge::get_purgeable_items_grouped_list((int)$purgetype->userstatus) as $classes) {
            /** @var item $class this is not an instance, but it helps with autocomplete */
            foreach ($classes as $class) {
                $maincomponent = $class::get_main_component();
                $key = $class::get_component() . '-' . $class::get_name();
                if (!isset($purgetype->{'grp_' . $maincomponent})) {
                    $purgetype->{'grp_' . $maincomponent} = array();
                }
                if (empty($currentitems[$key])) {
                    continue;
                }
                $purgetype->{'grp_' . $maincomponent}[] = $key;
            }
        }

        return $purgetype;
    }

    /**
     * Is this purge type deletable?
     * Purge types cannot be deleted if they were already used anywhere.
     *
     * @param int $id purge type id
     * @return bool
     */
    public static function is_deletable($id) {
        global $DB;

        $defaultsuspendedpurgetypeid = get_config('totara_userdata', 'defaultsuspendedpurgetypeid');
        if ($defaultsuspendedpurgetypeid and $id == $defaultsuspendedpurgetypeid) {
            return false;
        }

        $defaultdeletedpurgetypeid = get_config('totara_userdata', 'defaultdeletedpurgetypeid');
        if ($defaultdeletedpurgetypeid and $id == $defaultdeletedpurgetypeid) {
            return false;
        }

        if ($DB->record_exists('totara_userdata_user', array('suspendedpurgetypeid' => $id))) {
            return false;
        }

        if ($DB->record_exists('totara_userdata_user', array('deletedpurgetypeid' => $id))) {
            return false;
        }

        if ($DB->record_exists('totara_userdata_purge', array('purgetypeid' => $id))) {
            return false;
        }


        return true;
    }

    /**
     * Delete purge type if possible.
     *
     * @param int $id
     * @return bool success
     */
    public static function delete($id) {
        global $DB;

        if (!self::is_deletable($id)) {
            return false;
        }

        $trans = $DB->start_delegated_transaction();
        $DB->delete_records('totara_userdata_purge_type_item', array('purgetypeid' => $id));
        $DB->delete_records('totara_userdata_purge_type', array('id' => $id));
        $trans->allow_commit();

        return true;
    }

    /**
     * Add or edit a purge type record.
     *
     * @param \stdClass $data data from \totara_userdata\form\purge_type_edit() form
     * @return \stdClass the record
     */
    public static function edit(\stdClass $data) {
        global $DB, $USER;

        $trans = $DB->start_delegated_transaction();
        $now = time();

        $record = new \stdClass();
        $record->fullname = $data->fullname;
        $record->idnumber = $data->idnumber;
        $record->description = $data->description;
        $record->allowmanual = (int)in_array('allowmanual', $data->availablefor);
        $record->allowdeleted = (int)in_array('allowdeleted', $data->availablefor);
        $record->allowsuspended = (int)in_array('allowsuspended', $data->availablefor);

        if ($data->id) {
            $record->id = $data->id;
            if (!empty($data->repurge) or $data->userstatus == target_user::STATUS_ACTIVE) {
                $record->timechanged = $now;
            }
            $DB->update_record('totara_userdata_purge_type', $record);

        } else {
            $record->userstatus = $data->userstatus;
            $record->timecreated = $now;
            $record->timechanged = $now;
            $record->usercreated = $USER->id;

            $record->id = $DB->insert_record('totara_userdata_purge_type', $record);
        }

        $record = $DB->get_record('totara_userdata_purge_type', array('id' => $record->id), '*', MUST_EXIST);

        // Enable/disable individual items.
        $sql = "SELECT " . $DB->sql_concat_join("'-'", array('component', 'name')) . ", {totara_userdata_purge_type_item}.*
                  FROM {totara_userdata_purge_type_item}
                 WHERE purgetypeid = :purgetypeid";
        $currentitems = $DB->get_records_sql($sql, array('purgetypeid' => $record->id));

        foreach (purge::get_purgeable_items_grouped_list((int)$record->userstatus) as $classes) {
            /** @var item $class this is not an instance, but it helps with autocomplete */
            foreach ($classes as $class) {
                $maincomponent = $class::get_main_component();
                $key = $class::get_component() . '-' . $class::get_name();
                $purgedata = (int)in_array($key, $data->{'grp_' . $maincomponent});

                if (isset($currentitems[$key])) {
                    $item = $currentitems[$key];
                    if ($item->purgedata != $purgedata) {
                        $update = new \stdClass();
                        $update->id = $item->id;
                        $update->purgedata = $purgedata;
                        $update->timechanged = $now;
                        $DB->update_record('totara_userdata_purge_type_item', $update);
                    }
                    unset($currentitems[$key]);
                    continue;
                }
                $item = new \stdClass();
                $item->purgetypeid = $record->id;
                $item->component = $class::get_component();
                $item->name = $class::get_name();
                $item->purgedata = $purgedata;
                $item->timecreated = $now;
                $item->timechanged = $now;
                $DB->insert_record('totara_userdata_purge_type_item', $item);
            }
        }
        foreach ($currentitems as $key => $item) {
            $DB->delete_records('totara_userdata_purge_type_item', array('purgetypeid' => $record->id, 'component' => $item->component, 'name' => $item->name));
        }

        $trans->allow_commit();

        return $record;
    }

    /**
     * How many users would be affected by repurge?
     *
     * @param int $purgetypeid
     * @return int
     */
    public static function count_repurged_users($purgetypeid) {
        global $DB;

        $purgetype = $DB->get_record('totara_userdata_purge_type', array('id' => $purgetypeid), '*', MUST_EXIST);

        if ($purgetype->userstatus == target_user::STATUS_SUSPENDED) {
            $sql = "SELECT COUNT(u.id)
                      FROM {totara_userdata_user} e
                      JOIN {user} u ON u.id = e.userid
                     WHERE e.suspendedpurgetypeid = :typeid AND u.suspended = 1 AND u.deleted = 0";
            return (int)$DB->count_records_sql($sql, array('typeid' => $purgetypeid));
        }

        if ($purgetype->userstatus == target_user::STATUS_DELETED) {
            $sql = "SELECT COUNT(u.id)
                      FROM {totara_userdata_user} e
                      JOIN {user} u ON u.id = e.userid
                     WHERE e.deletedpurgetypeid = :typeid AND u.deleted = 1";
            return (int)$DB->count_records_sql($sql, array('typeid' => $purgetypeid));
        }

        // This should not happen if used properly, because active is for manual purges only that are nor repurged.
        return 0;
    }

    /**
     * Returns new items since last save of the type.
     *
     * @param int $purgetypeid
     * @return string[] list of classes indexed by 'component-name'
     */
    public static function get_new_items($purgetypeid) {
        global $DB;

        $result = array();

        if (!$purgetypeid) {
            return $result;
        }
        $purgetype = $DB->get_record('totara_userdata_purge_type', array('id' => $purgetypeid));
        if (!$purgetype) {
            return $result;
        }

        $saveditems = $DB->get_records_menu('totara_userdata_purge_type_item',
            array('purgetypeid' => $purgetypeid), '', $DB->sql_concat_join("'-'", array('component', 'name')) . ',id');

        $groupeditems = \totara_userdata\local\purge::get_purgeable_items_grouped_list((int)$purgetype->userstatus);
        foreach ($groupeditems as $maincomponent => $classes) {
            /** @var \totara_userdata\userdata\item $class this is not an instance, but it helps with autocomplete */
            foreach ($classes as $class) {
                $key = $class::get_component() . '-' . $class::get_name();
                if (isset($saveditems[$key])) {
                    continue;
                }
                $result[$key] = $class;
            }
        }

        return $result;
    }

    /**
     * Request manual user data purge.
     *
     * @param int $purgetypeid
     * @param int $userid
     * @param int $contextid
     * @return int adhoc task id
     */
    public static function trigger_manual_purge($purgetypeid, $userid, $contextid) {
        $purgeid = manager::create_purge($userid, $contextid, $purgetypeid, 'manual');

        $adhoctask = new \totara_userdata\task\purge_manual();
        $adhoctask->set_custom_data($purgeid);
        $adhoctask->set_component('totara_userdata');
        return \core\task\manager::queue_adhoc_task($adhoctask);
    }
}
