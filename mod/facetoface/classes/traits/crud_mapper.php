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
 * @author  Valerii Kuznetsov <valerii.kuznetsov@totaralearning.com>
 * @author Oleg Demeshev <oleg.demeshev@totaralearning.com>
 * @package mod_facetoface
 */

namespace mod_facetoface\traits;

defined('MOODLE_INTERNAL') || die();

/**
 * Class crud_mapper
 */
trait crud_mapper {

    protected function crud_load(int $strictness = MUST_EXIST) : self {
        global $DB;

        if (!$this->id) {
            return $this;
        }

        $record = $DB->get_record(self::DBTABLE, ['id' => $this->id], '*', $strictness);
        if (!$record) {
            $this->id = 0;
            return $this;
        }

        $this->map_object($record);

        return $this;
    }

    protected function crud_save() {
        global $DB;

        $todb = $this->unmap_object();

        if ($this->id) {
            $DB->update_record(self::DBTABLE, $todb);
        } else {
            $this->id = $DB->insert_record(self::DBTABLE, $todb);
        }
        // Reload object with new values.
        $this->crud_load();
    }

    protected function map_object(\stdClass $object) {

        foreach ((array)$object as $property => $value) {
            if (property_exists($this, $property)) {
                $this->{$property} = $value;
            } else {
                debugging("Provided object does not have {$property} field", DEBUG_DEVELOPER);
            }
        }
        return $this;
    }

    protected function unmap_object() : \stdClass {
        global $DB;

        $columns = array_keys($DB->get_columns(self::DBTABLE));

        $todb = new \stdClass();
        foreach (get_object_vars($this) as $property => $value) {
            if (in_array($property, $columns)) {
                $todb->{$property} = $value;
            }
        }

        return $todb;
    }
}
