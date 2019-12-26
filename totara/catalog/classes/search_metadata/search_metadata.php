<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package totara_catalog
 */

namespace totara_catalog\search_metadata;
defined('MOODLE_INTERNAL') || die();

/**
 * @property-read int           $id
 * @property-read string|null   $value
 * @property-read string|null   $instanceid
 * @property-read int|null      $timecreated
 * @property-read int|null      $usermodified
 * @property-read string|null   $pluginname
 * @property-read string|null   $plugintype
 * @property-read int|null      $timemodified
 */
final class search_metadata {
    /**
     * Table for search metadata
     * @var string
     */
    public const DBTABLE = 'catalog_search_metadata';

    /**
     * @var int
     */
    private $id;

    /**
     * The value of search terms.
     * @var string
     */
    private $value;

    /**
     * @var int
     */
    private $instanceid;

    /**
     * @var int|null
     */
    private $timecreated;

    /**
     * @var int|null
     */
    private $usermodified;

    /**
     * @var int|null
     */
    private $timemodified;

    /**
     * @var string|null
     */
    private $pluginname;

    /**
     * @var string|null
     */
    private $plugintype;

    /**
     * search_metadata constructor.
     *
     * @param int $id
     */
    public function __construct(int $id = 0) {
        $this->id = $id;
        $this->load();
    }

    /**
     * @param string $value
     * @return void
     */
    public function set_value(string $value): void {
        // TRIM the $value, because we don't really want the metadata with value like ' hello ' to be saved
        // in the database.
        $this->value = trim($value);
    }

    /**
     * @param int $instanceid
     * @return void
     */
    public function set_instanceid(int $instanceid): void {
        $this->instanceid = $instanceid;
    }

    /**
     * @param string|null $name
     * @return void
     */
    public function set_pluginname(?string $name): void {
        $this->pluginname = $name;
    }

    /**
     * @param string $type
     * @return void
     */
    public function set_plugintype(string $type): void {
        $this->plugintype = $type;
    }

    /**
     * @param int $strictness
     * @return void
     */
    public function load(int $strictness = MUST_EXIST): void {
        global $DB;

        if (0 == $this->id) {
            return;
        }

        $record = $DB->get_record(static::DBTABLE, ['id' => $this->id], '*', $strictness);
        if (!$record) {
            $this->map($record);
            return;
        }
    }

    /**
     * @param \stdClass $record
     * @return void
     */
    private function map(\stdClass $record): void {
        $properties = (array) $record;

        foreach ($properties as $property => $value) {
            if (property_exists($this, $property)) {
                $this->{$property} = $value;
            }
        }
    }

    /**
     * Saving record into the table. If the $id is provided, this function will try to update the record instead.
     * However when updating a record, if the pluginname or plugintype or instanceid are being changed, then it will
     * stop the whole process.
     *
     * This happen, because we want to keep the record as simple as possible, and the property to be changed should be
     * the value of search_metadata only.
     *
     * @return void
     */
    public function save(): void {
        global $USER, $DB;

        if (null == $this->value) {
            // Using == because we need to include '' as well. Beside if the null is represent in $value
            // then most likely we should not save anything, but if the record is already existing in the system
            // then the caller should just delete the record if this is what dev want to do.
            debugging(
                "Cannot save with empty value of search metadata record",
                DEBUG_DEVELOPER
            );

            return;
        }

        if (0 == $this->id) {
            if (null == $this->timecreated) {
                $this->timecreated = time();
            }

            if (null == $this->usermodified) {
                $this->usermodified = $USER->id;
            }

            $record = $this->to_record();
            $id = $DB->insert_record(static::DBTABLE, $record);

            $this->id = $id;
            return;
        }

        $old = $DB->get_record(static::DBTABLE, ['id' => $this->id], '*', MUST_EXIST);

        if ($old->instanceid != $this->instanceid ||
            $old->pluginname != $this->pluginname ||
            $old->plugintype != $this->plugintype) {
            throw new \coding_exception(
                "Cannot update the search_metadata, because there are differences in either " .
                "of these fields: 'instanceid', 'pluginname' or 'plugintype'"
            );
        }

        if ($old->value === $this->value) {
            // They are the same, so we should not change anything pretty much.
            return;
        }

        // Set the time modified of this object.
        $time = time();
        $this->timemodified = $time;

        $old->value = $this->value;
        $old->timemodified = $time;

        $DB->update_record(static::DBTABLE, $old);
    }

    /**
     * Deleting its own record. Only if valid $id is provided, otherwise, it will not do anything.
     *
     * @return void
     */
    public function delete(): void {
        global $DB;

        if (0 == $this->id) {
            debugging("Cannot delete a search metadata that does not exist in system", DEBUG_DEVELOPER);
            return;
        }

        $DB->delete_records(static::DBTABLE, ['id' => $this->id]);
    }

    /**
     * Map the fields from this object into a dummy data holder class.
     *
     * @return \stdClass
     */
    private function to_record(): \stdClass {
        global $DB;

        $columns = array_keys($DB->get_columns(static::DBTABLE));
        $properties = get_object_vars($this);

        $record = new \stdClass();
        foreach ($properties as $property => $value) {
            if (in_array($property, $columns)) {
                $record->{$property} = $value;
            }
        }

        return $record;
    }

    /**
     * @param \stdClass $record
     * @return search_metadata
     */
    public static function from_record(\stdClass $record): search_metadata {
        $metadata = new static();
        $metadata->map($record);

        return $metadata;
    }

    /**
     * @param string $name
     * @return mixed|null
     */
    public function __get(string $name) {
        return $this->{$name} ?? null;
    }

    /**
     * @return string
     */
    public function __toString(): string {
        return $this->value;
    }

    /**
     * @param string $name
     * @param mixed $value
     *
     * @return void
     */
    public function __set(string $name, $value): void {
        if (!property_exists($this, $name)) {
            $classname = static::class;
            throw new \coding_exception(
                "The class '{$classname}' does not accept to create any new property on a fly"
            );
        }

        throw new \coding_exception(
            "Please use the set method for property '{$name}'"
        );
    }
}