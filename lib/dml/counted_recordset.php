<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2017 onwards Totara Learning Solutions LTD
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
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 * @package core
 */

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__.'/moodle_recordset.php');

/**
 * Counted recordset class
 *
 * Used to wrap a recordset that has been created by get_counted_recordset_sql()
 * It either takes a count without limit or a field containing that value.
 * If a field is given it is stripped from each record before being returned.
 *
 * @since Totara 2.6.45, 2.7.28, 2.9.20, 9.8
 */
final class counted_recordset extends moodle_recordset {

    /**
     * The original recordset object that we'll be filtering.
     * @var moodle_recordset
     */
    private $rs;

    /**
     * The countfield if one is used, false if not.
     * If one is set then it will be stripped from records before returning.
     * @var string|false
     */
    private $countfield;

    /**
     * The count of records without limits applied.
     * @var int
     */
    private $count;

    /**
     * Build a new counted_recordset to iterate over and filter.
     *
     * @throws dml_exception if the $countorfield is not found on the resulting record.
     * @throws coding_exception if the count field contained an invalid null value.
     * @param moodle_recordset $result A recordset that we are wrapping.
     * @param string|int $countorfield The count field in results OR if the database driver could handle count natively the
     *   count as an integer. If a field is used it will be stripped from the records returned by this recordset.
     */
    public function __construct(moodle_recordset $result, $countorfield) {

        $this->rs = $result;
        if (is_numeric($countorfield)) {
            $this->count = (int)$countorfield;
            $this->countfield = false;
        } else {
            $this->countfield = $countorfield;
            if ($result->valid()) {
                $current = $this->current_unfiltered();
                if (!property_exists($current, $countorfield)) {
                    throw new dml_exception("Expected column {$countorfield} used for counting records without limit was not found");
                } else if (!isset($current->{$countorfield})) {
                    throw new coding_exception("Invalid count result in {$countorfield} used for counting records without limit");
                }
                $this->count = (int)$current->{$countorfield};
            } else {
                $this->count = 0;
            }
        }
    }

    /**
     * Filter the result and remove the count field if required.
     *
     * @param stdClass $result
     * @return stdClass
     */
    private function filter_result(stdClass $result) {
        if ($this->countfield) {
            unset($result->{$this->countfield});
        }
        // Just to make it chainable.
        return $result;
    }

    /**
     * Just copied from the moodle_recordset
     */
    public function __destruct() {
        $this->close();
    }

    /**
     * Pass through to the recordset current method but filtering the result.
     * @return stdClass
     */
    public function current() {
        return $this->filter_result($this->rs->current());
    }

    /**
     * Pass through to the recordset current method and do NOT filter the result.
     * @return stdClass
     */
    private function current_unfiltered() {
        return $this->rs->current();
    }

    /**
     * Returns the key of the current record.
     * @return mixed
     */
    public function key() {
        // return first column value as key
        return $this->rs->key();
    }

    /**
     * Moves the recordset forwards one.
     */
    public function next() {
        $this->rs->next();
    }

    /**
     * Returns true if the recordset is valid, false otherwise.
     * @return bool
     */
    public function valid() {
        return $this->rs->valid();
    }

    /**
     * Closes the recordset.
     */
    public function close() {
        $this->rs->close();
    }

    /**
     * Returns the count of records without limits applied.
     *
     * Count is calculated during construction so that the count is still available after the
     * recordset has been closed.
     *
     * @return int
     */
    public function get_count_without_limits() {
        return $this->count;
    }
}