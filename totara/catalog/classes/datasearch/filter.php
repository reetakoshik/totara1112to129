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
 * @author Nathan Lewis <nathan.lewis@totaralearning.com>
 * @package totara_catalog
 */

namespace totara_catalog\datasearch;

use core\command\exception;

defined('MOODLE_INTERNAL') || die();

/**
 * Filter base class.
 */
abstract class filter {

    /**
     * Used to group filters, when they have a common alias, and as a table alias when merging occurs.
     *
     * @var string
     */
    protected $alias;

    /**
     * The alias of the base table which the filter will be linking to.
     *
     * @var string
     */
    protected $basealias;

    /**
     * All the fields in the base table that must be joined to by all sources.
     *
     * @var array
     */
    protected $joinonbasefields;

    /**
     * The type of join to use, such as "JOIN" or "LEFT JOIN".
     *
     * @var string
     */
    protected $jointype;

    /**
     * List of sources that relate to this filter.
     *
     * @var array
     */
    public $sources = [];

    /**
     * List of current data.
     *
     * @var mixed
     */
    protected $currentdata = null;

    /**
     * Base filter constructor.
     *
     * @param string $alias Used to group filters with a common alias, and as an alias in the resulting query.
     * @param string $basealias The alias of the base table which the filter will be linking to.
     * @param array $joinonbasefields All fields in the base table that will be joined to.
     * @param string $jointype Such as "JOIN" (default) or "LEFT JOIN".
     */
    public function __construct(
        string $alias,
        string $basealias = 'base',
        array $joinonbasefields = [],
        string $jointype = "JOIN"
    ) {
        $this->alias = $alias;
        $this->basealias = $basealias;
        $this->joinonbasefields = $joinonbasefields;
        $this->jointype = $jointype;
    }

    /**
     * @return string
     */
    public function get_alias() {
        return $this->alias;
    }

    /**
     * @param mixed $data
     * @return void
     */
    final public function set_current_data($data) {
        $this->currentdata = $this->validate_current_data($data);
    }

    /**
     * Subclass validation of current data. This is needed because different
     * filters work with different data types; some accept single strings only,
     * other work with arrays only.
     *
     * @param mixed $data incoming value.
     *
     * @return mixed the validated value.
     *
     * @throws \coding_exception if the value is invalid.
     */
    abstract protected function validate_current_data($data);

    /**
     * Return true when the current data indicates that filtering must occur.
     *
     * @return bool
     */
    abstract public function is_active(): bool;

    /**
     * Add a data source for the filter.
     *
     * @param string $filterfield The field to use in the comparison. E.g. 'format'.
     * @param string|null $table Optional. A table which contains data that we want to join to. E.g. "{course}".
     * @param string|null $alias Optional. An alias for the joined table. E.g. 'course'.
     * @param array $joinons Mapping between the base table and the join table. Must contain all $this->joinonbasefields keys.
     * @param string $additionalcriteria Sql WHERE snippet, used when linking
     * @param array $additionalparams An array of sql param key => data. Keys can (and should) be used in 'table' or
     *                                'additionalcriteria' to restict the results of joined tables.
     * @param array $additionalselect An array of sql field alias => source. Will be made available to outer queries when
     *                                the source is put into a subquery (due to multiple sources joined with UNION).
     */
    public function add_source(
        string $filterfield,
        string $table = null,
        string $alias = null,
        array $joinons = [],
        string $additionalcriteria = '',
        array $additionalparams = [],
        array $additionalselect = []
    ) {
        if (!empty(array_diff(array_keys($joinons), $this->joinonbasefields))) {
            throw new \coding_exception('$joinons must contain the same keys as $this->joinonbasefields');
        }

        if (!empty(array_diff($this->joinonbasefields, array_keys($joinons)))) {
            throw new \coding_exception('$joinons must contain the same keys as $this->joinonbasefields');
        }

        $source = new \stdClass();
        $source->filterfield = $filterfield;
        $source->table = $table;
        $source->alias = $alias;
        $source->joinons = $joinons;
        $source->additionalcriteria = $additionalcriteria;
        $source->additionalparams = $additionalparams;
        $source->additionalselect = $additionalselect;

        $this->sources[] = $source;
    }

    /**
     * Return true if the specified filter can be merged into this filter.
     *
     * Filter subclasses should implement this function if they have custom attributes that should be compared.
     * Be sure to include parent::can_merge().
     *
     * @param filter $otherfilter
     * @return bool
     */
    public function can_merge(filter $otherfilter) {
        // Alias must match. This is the mechanism for identifying that filters can be merged.
        if ($this->alias != $otherfilter->alias) {
            return false;
        }

        // Classes must match, obviously. Otherwise treat them as separate fitlers.
        if (get_class($this) !== get_class($otherfilter)) {
            return false;
        }

        // Both filters must be joining to the same base table alias. Otherwise treat them as separate fitlers.
        if ($this->basealias != $otherfilter->basealias) {
            return false;
        }

        // The same fields in the base table must be joined to. Otherwise treat them as separate fitlers.
        if ($this->joinonbasefields != $otherfilter->joinonbasefields) {
            return false;
        }

        // The same join type must be used. Otherwise treat them as separate fitlers.
        if ($this->jointype != $otherfilter->jointype) {
            return false;
        }

        // Don't try to merge after setting data.
        if (!is_null($this->currentdata) || !is_null($otherfilter->currentdata)) {
            throw new \coding_exception("Shouldn't be checking if filters can be merged if data has already been set");
        }

        return true;
    }

    /**
     * Merge the sources of the specified filter into this filter.
     *
     * Filter subclasses should implement this function if they have custom attributes that should be merged.
     * Be sure to include parent::merge().
     *
     * @param filter $otherfilter
     */
    public function merge(filter $otherfilter) {
        if (!$this->can_merge($otherfilter)) {
            throw new exception("Tried to merge datasearch filters which don't match");
        }

        $sources = $otherfilter->sources;
        $this->sources = array_merge($this->sources, $sources);
    }

    /**
     * Make the sql snippet needed to filter the data
     *
     * @return array [string $join, string $where, array $params]
     */
    public function make_sql() {
        if (empty($this->sources)) {
            $join = "";
            $where = "";
            $params = [];
        } else if (count($this->sources) == 1) {
            // Because only one source is involved in this filter, we can put the WHERE code in the WHERE clause,
            // for best performance.

            $source = reset($this->sources);

            if (empty($this->joinonbasefields)) {
                $join = "";

                list($where, $params) = $this->make_compare($source);

                if (!empty($source->additionalcriteria)) {
                    if (empty($where)) {
                        $where = $source->additionalcriteria;
                    } else {
                        $where .= "
                         AND {$source->additionalcriteria}";
                    }
                }
            } else {
                $joinons = [];
                foreach ($source->joinons as $basefield => $joinfield) {
                    $joinons[] = "{$this->basealias}.{$basefield} = {$joinfield}";
                }

                if (!empty($source->additionalcriteria)) {
                    $joinons[] = $source->additionalcriteria;
                }

                list($where, $params) = $this->make_compare($source);

                $join = "{$this->jointype} {$source->table} {$source->alias}";

                if (!empty($joinons)) {
                    $join .= "
                     ON " . implode(
                        "
                    AND ",
                        $joinons
                    );
                }
            }

            $params = array_merge($params, $source->additionalparams);
        } else {
            // There are multiple sources, so they all need to be processed inside the JOIN, with UNION between SELECTs.

            $selects = [];
            $params = [];

            foreach ($this->sources as $source) {
                $selectfields = [];
                foreach ($source->joinons as $basefield => $joinfield) {
                    $selectfields[] = "{$joinfield} AS {$basefield}";
                }

                foreach ($source->additionalselect as $basefield => $joinfield) {
                    $selectfields[] = "{$joinfield} AS {$basefield}";
                }

                list($sourcewhere, $sourceparams) = $this->make_compare($source);

                $select = "SELECT " . implode(", ", $selectfields) . "
                              FROM {$source->table} {$source->alias}
                              ";

                if (!empty($source->additionalcriteria)) {
                    if (empty($sourcewhere)) {
                        $sourcewhere = $source->additionalcriteria;
                    } else {
                        $sourcewhere .= "
                         AND {$source->additionalcriteria}";
                    }
                }

                if (!empty($sourcewhere)) {
                    $select .= " WHERE {$sourcewhere}";
                }

                $params = array_merge($params, $sourceparams, $source->additionalparams);

                $selects[] = $select;
            }

            $joinons = [];
            foreach ($this->joinonbasefields as $basefield) {
                $joinons[] = "{$this->basealias}.{$basefield} = {$this->alias}.{$basefield}";
            }

            $selectssql = implode(
                " UNION
                ",
                $selects
            );
            $join = "{$this->jointype} ($selectssql) {$this->alias}";

            if (!empty($joinons)) {
                $join .= "
                 ON " . implode(
                    "
                 AND ",
                    $joinons
                );
            }

            $where = "";
        }
        return [$join, $where, $params];
    }

    /**
     * Create the sql WHERE clause to compare the selection to the database values for the given source.
     * This function should probably do some comparison to {$source->filterfield}.
     *
     * @param \stdClass $source
     * @return array(string where, array params)
     */
    abstract protected function make_compare(\stdClass $source): array;

    /**
     * We need to json encode string filter criteria in order to locate unicode characters (e.g. Matěj Dvořák) in db.
     *
     * @param $data
     * @return null|bool|string
     */
    protected function filter_json_encode($data) {
        if (is_string($data)) {
            $encode = substr(json_encode($data), 1, -1);
            if (strpos($encode, "\\") !== false) {
                $encode = addslashes($encode);
            }
        } else {
            $encode = $data;
        }
        return $encode;
    }
}
