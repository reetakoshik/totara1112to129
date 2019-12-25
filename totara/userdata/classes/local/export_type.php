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

defined('MOODLE_INTERNAL') || die();

/**
 * Helpers for user data export type.
 *
 * NOTE: This is not a public API - do not use in plugins or 3rd party code!
 */
final class export_type {
    /**
     * Prepare record for adding.
     *
     * @param int|null $duplicate
     * @return \stdClass
     */
    public static function prepare_for_add($duplicate) {
        if ($duplicate) {
            $exporttype = self::prepare_for_update($duplicate);
            $exporttype->id = '0';
            $exporttype->fullname = get_string('exporttypecopyof', 'totara_userdata', $exporttype);
            $exporttype->idnumber = '';
            unset($exporttype->usercreated);
            unset($exporttype->timecreated);
            unset($exporttype->timechanged);
            return $exporttype;
        }

        $exporttype = new \stdClass();
        $exporttype->id = '0';
        $exporttype->fullname = '';
        $exporttype->idnumber = '';
        $exporttype->description = '';
        $exporttype->availablefor = array();
        $exporttype->includefiledir = '0';
        return $exporttype;
    }

    /**
     * Prepare record for updating.
     *
     * @param int $id
     * @return \stdClass
     */
    public static function prepare_for_update($id) {
        global $DB;

        $exporttype = $DB->get_record('totara_userdata_export_type', array('id' => $id), '*', MUST_EXIST);
        $exporttype->availablefor = array();
        if ($exporttype->allowself) {
            $exporttype->availablefor[] = 'allowself';
        }
        unset($exporttype->allowself);

        $sql = "SELECT " . $DB->sql_concat_join("'-'", array('component', 'name')) . ", exportdata
                  FROM {totara_userdata_export_type_item}
                 WHERE exporttypeid = :exporttypeid";
        $currentitems = $DB->get_records_sql_menu($sql, array('exporttypeid' => $exporttype->id));

        foreach (export::get_exportable_items_grouped_list() as $classes) {
            /** @var item $class this is not an instance, but it helps with autocomplete */
            foreach ($classes as $class) {
                $maincomponent = $class::get_main_component();
                $key = $class::get_component() . '-' . $class::get_name();
                if (!isset($exporttype->{'grp_' . $maincomponent})) {
                    $exporttype->{'grp_' . $maincomponent} = array();
                }
                if (empty($currentitems[$key])) {
                    continue;
                }
                $exporttype->{'grp_' . $maincomponent}[] = $key;
            }
        }

        return $exporttype;
    }

    /**
     * Is this export type deletable?
     * Export types cannot be deleted if they were already used anywhere.
     *
     * @param int $id export type id
     * @return bool
     */
    public static function is_deletable($id) {
        global $DB;

        if ($DB->record_exists('totara_userdata_export', array('exporttypeid' => $id))) {
            return false;
        }

        return true;
    }

    /**
     * Delete export type if possible.
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
        $DB->delete_records('totara_userdata_export_type_item', array('exporttypeid' => $id));
        $DB->delete_records('totara_userdata_export_type', array('id' => $id));
        $trans->allow_commit();

        return true;
    }

    /**
     * Add or edit a export type record.
     *
     * @param \stdClass $data data from \totara_userdata\form\export_type_edit() form
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
        $record->allowself = (int)in_array('allowself', $data->availablefor);
        $record->includefiledir = $data->includefiledir;

        if ($data->id) {
            $record->id = $data->id;
            $record->timechanged = $now;
            $DB->update_record('totara_userdata_export_type', $record);

        } else {
            $record->timecreated = $now;
            $record->timechanged = $now;
            $record->usercreated = $USER->id;

            $record->id = $DB->insert_record('totara_userdata_export_type', $record);
        }

        $record = $DB->get_record('totara_userdata_export_type', array('id' => $record->id), '*', MUST_EXIST);

        // Enable/disable individual items.
        $sql = "SELECT " . $DB->sql_concat_join("'-'", array('component', 'name')) . ", {totara_userdata_export_type_item}.*
                  FROM {totara_userdata_export_type_item}
                 WHERE exporttypeid = :exporttypeid";
        $currentitems = $DB->get_records_sql($sql, array('exporttypeid' => $record->id));

        foreach (export::get_exportable_items_grouped_list() as $classes) {
            /** @var item $class this is not an instance, but it helps with autocomplete */
            foreach ($classes as $class) {
                $maincomponent = $class::get_main_component();
                $key = $class::get_component() . '-' . $class::get_name();
                $exportdata = (int)in_array($key, $data->{'grp_' . $maincomponent});

                if (isset($currentitems[$key])) {
                    $item = $currentitems[$key];
                    if ($item->exportdata != $exportdata) {
                        $update = new \stdClass();
                        $update->id = $item->id;
                        $update->exportdata = $exportdata;
                        $update->timechanged = $now;
                        $DB->update_record('totara_userdata_export_type_item', $update);
                    }
                    unset($currentitems[$key]);
                    continue;
                }
                $item = new \stdClass();
                $item->exporttypeid = $record->id;
                $item->component = $class::get_component();
                $item->name = $class::get_name();
                $item->exportdata = $exportdata;
                $item->timecreated = $now;
                $item->timechanged = $now;
                $DB->insert_record('totara_userdata_export_type_item', $item);
            }
        }
        foreach ($currentitems as $key => $item) {
            $DB->delete_records('totara_userdata_export_type_item', array('exporttypeid' => $record->id, 'component' => $item->component, 'name' => $item->name));
        }

        $trans->allow_commit();

        return $record;
    }

    /**
     * Returns new items since last save of the type.
     *
     * @param int $exporttypeid
     * @return string[] list of classes indexed by 'component-name'
     */
    public static function get_new_items($exporttypeid) {
        global $DB;

        $result = array();

        if (!$exporttypeid) {
            return $result;
        }

        $saveditems = $DB->get_records_menu('totara_userdata_export_type_item',
            array('exporttypeid' => $exporttypeid), '', $DB->sql_concat_join("'-'", array('component', 'name')) . ',id');

        $groupeditems = \totara_userdata\local\export::get_exportable_items_grouped_list();
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
     * Request user data self export.
     * @param int $exporttypeid
     * @return int adhoc task id
     */
    public static function trigger_self_export($exporttypeid) {
        global $USER;

        $exportid = manager::create_export($USER->id, SYSCONTEXTID, $exporttypeid, 'self');

        $adhoctask = new \totara_userdata\task\export();
        $adhoctask->set_custom_data($exportid);
        $adhoctask->set_component('totara_userdata');
        return \core\task\manager::queue_adhoc_task($adhoctask);
    }
}
