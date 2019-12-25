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
 * @category test
 */

use totara_userdata\userdata\item;
use totara_userdata\userdata\target_user;

defined('MOODLE_INTERNAL') || die();

/**
 * Class totara_userdata_generator
 */
class totara_userdata_generator extends component_generator_base {
    protected $purgetypecount = 0;
    protected $exporttypecount = 0;

    /**
     * To be called from data reset code only, do not use in tests.
     * @return void
     */
    public function reset() {
        parent::reset();
        $this->purgetypecount = 0;
        $this->exporttypecount = 0;
    }

    /**
     * Create new purge type.
     *
     * @param stdClass array $record
     * @return stdClass purge type record
     */
    public function create_purge_type($record = null) {
        global $DB, $USER;

        $record = (object)(array)$record;

        $this->purgetypecount++;
        $i = $this->purgetypecount;

        $items = array();
        if (!empty($record->items)) {
            if (is_array($record->items)) {
                $items = $record->items;
            } else {
                $items = explode(',', $record->items);
                $items = array_map('trim', $items);
            }
        }
        unset($record->items);

        $type = new \stdClass();
        $type->userstatus = isset($record->userstatus) ? (int)$record->userstatus : target_user::STATUS_DELETED;
        if ($type->userstatus !== target_user::STATUS_DELETED and target_user::STATUS_SUSPENDED and target_user::STATUS_ACTIVE) {
            throw new coding_exception('Invalid user status name detected: ' . $type->userstatus);
        }
        $type->fullname = isset($record->fullname) ? $record->fullname : 'Purge type ' . $i;
        $type->idnumber = isset($record->idnumber) ? $record->idnumber : 'idnumber' . $i;
        $type->description = isset($record->description) ? $record->description : '';
        $type->allowmanual = isset($record->allowmanual) ? $record->allowmanual : 0;
        $type->allowdeleted = isset($record->allowdeleted) ? $record->allowdeleted : 0;
        $type->allowsuspended = isset($record->allowsuspended) ? $record->allowsuspended : 0;
        $type->usercreated = isset($record->usercreated) ? $record->usercreated : $USER->id;
        $type->timecreated = isset($record->timecreated) ? $record->timecreated : time();
        $type->timechanged = isset($record->timechanged) ? $record->timechanged : $type->timecreated;

        $id = $DB->insert_record('totara_userdata_purge_type', $type);
        $type = $DB->get_record('totara_userdata_purge_type', array('id' => $id));

        $enable[] = array();
        foreach ($items as $item) {
            if (preg_match('/^[a-z0-9_]+-[a-z0-9_]+$/', $item)) {
                list($component, $name) = explode('-', $item);
                $classname = $component . '\\userdata\\' . $name;
            } else if (preg_match('/^[a-z0-9_]+\\\\userdata\\\\[a-z0-9_]+$/', $item)) {
                $classname = $item;
            } else {
                throw new coding_exception('Invalid item name detected, must be a full class name or "component-name": ' . $item);
            }
            $enable[$classname] = true;
        }

        $purgeclasses = \totara_userdata\local\purge::get_purgeable_item_classes((int)$type->userstatus);
        foreach ($purgeclasses as $purgeclass) {
            /** @var item $purgeclass */
            $item = new \stdClass();
            $item->purgetypeid = $type->id;
            $item->component = $purgeclass::get_component();
            $item->name = $purgeclass::get_name();
            $item->purgedata = (int)isset($enable[$purgeclass]);
            $item->timecreated = $type->timecreated;
            $item->timechanged = $type->timechanged;
            $item->id = $DB->insert_record('totara_userdata_purge_type_item', $item);
        }

        return $type;
    }

    /**
     * Create new export type.
     *
     * @param stdClass array $record
     * @return stdClass export type record
     */
    public function create_export_type($record = null) {
        global $DB, $USER;

        $record = (object)(array)$record;

        $this->exporttypecount++;
        $i = $this->exporttypecount;

        $items = array();
        if (!empty($record->items)) {
            if (is_array($record->items)) {
                $items = $record->items;
            } else {
                $items = explode(',', $record->items);
                $items = array_map('trim', $items);
            }
        }
        unset($record->items);

        $type = new \stdClass();
        $type->fullname = isset($record->fullname) ? $record->fullname : 'Export type ' . $i;
        $type->idnumber = isset($record->idnumber) ? $record->idnumber : 'idnumber' . $i;
        $type->description = isset($record->description) ? $record->description : '';
        $type->allowself = isset($record->allowself) ? $record->allowself : 0;
        $type->includefiledir = isset($record->includefiledir) ? $record->includefiledir : 1;
        $type->usercreated = isset($record->usercreated) ? $record->usercreated : $USER->id;
        $type->timecreated = isset($record->timecreated) ? $record->timecreated : time();
        $type->timechanged = isset($record->timechanged) ? $record->timechanged : $type->timecreated;

        $id = $DB->insert_record('totara_userdata_export_type', $type);
        $type = $DB->get_record('totara_userdata_export_type', array('id' => $id));

        $enable[] = array();
        foreach ($items as $item) {
            if (preg_match('/^[a-z0-9_]+-[a-z0-9_]+$/', $item)) {
                list($component, $name) = explode('-', $item);
                $classname = $component . '\\userdata\\' . $name;
            } else if (preg_match('/^[a-z0-9_]+\\\\userdata\\\\[a-z0-9_]+$/', $item)) {
                $classname = $item;
            } else {
                throw new coding_exception('Invalid item name detected, must be a full class name or "component-name": ' . $item);
            }
            $enable[$classname] = true;
        }

        $exportclasses = \totara_userdata\local\export::get_exportable_item_classes();
        foreach ($exportclasses as $exportclass) {
            /** @var item $exportclass */
            $item = new \stdClass();
            $item->exporttypeid = $type->id;
            $item->component = $exportclass::get_component();
            $item->name = $exportclass::get_name();
            $item->exportdata = (int)isset($enable[$exportclass]);
            $item->timecreated = $type->timecreated;
            $item->timechanged = $type->timechanged;
            $item->id = $DB->insert_record('totara_userdata_export_type_item', $item);
        }

        return $type;
    }
}
