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

defined('MOODLE_INTERNAL') || die();

/**
 * Class full_text_search
 *
 * The full text search datasearch filter works a bit different than other datasearch filters. You must call
 * set_fields_and_weights before adding sources. The $filterfield and $table passed to add_source will be
 * ignored - these will instead be determined by the full text search object.
 *
 * @package totara_catalog\datasearch
 */
class full_text_search extends filter {

    /** @var string */
    protected $joinsql = null;

    /** @var array */
    protected $joinparams = null;

    /** @var array */
    protected $fieldsandweights = false;

    /**
     * @param array $fieldsandweights containing $field => $weight
     * @throws \coding_exception
     */
    public function set_fields_and_weights(array $fieldsandweights) {
        global $DB;

        if (!empty($this->joinsql)) {
            throw new \coding_exception(
                'Cannot change full_text_search fields and weights after they have been set'
            );
        }

        // We need to cache the $fieldsandweights here. There are a few reason behind it, but first of all, that
        // at somepoint, we might need to re-calculating the fts_subquery.
        $this->fieldsandweights = $fieldsandweights;
        [$this->joinsql, $this->joinparams] = $DB->get_fts_subquery($this->basealias, $fieldsandweights, 'placeholder');
    }

    /**
     * Add a data source for the filter.
     *
     * The fields and weights MUST have been set before calling this function!
     *
     * @param string $filterfield Not used by full_text_search!
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
        if (empty($this->joinsql)) {
            throw new \coding_exception(
                'Cannot add sources to full_text_search until fields and weights have been specified'
            );
        }

        parent::add_source(
            'notused full_text_search',
            $this->joinsql,
            $alias,
            $joinons,
            $additionalcriteria,
            $additionalparams,
            $additionalselect
        );
    }

    /**
     * @param filter $otherfilter
     *
     * @return bool
     */
    public function can_merge(filter $otherfilter) {
        if (!parent::can_merge($otherfilter)) {
            return false;
        }

        if (empty($this->joinsql) || empty($otherfilter->joinsql)) {
            throw new \coding_exception(
                'Cannot merge full_text_search before fields and weights have been set'
            );
        }

        /** @var full_text_search $otherfilter */
        if ($this->joinsql !== $otherfilter->joinsql || $this->joinparams !== $otherfilter->joinparams) {
            throw new \coding_exception(
                'Found full_text_search which appear to be matching but don\'t have matching fields and weights'
            );
        }

        return true;
    }

    /**
     * Returning the formatted data for this filter. Which is going to be used in static::make_sql.
     *
     * @see full_text_search::make_sql()
     * @param mixed $data
     *
     * @return null|string
     */
    protected function validate_current_data($data) {
        if (is_null($data) || is_string($data)) {
            return $data;
        }

        throw new \coding_exception('full text search filter only accepts null or string data');
    }

    /**
     * Full text search is active only when some text has been entered.
     *
     * @return bool
     */
    public function is_active(): bool {
        // There is no current data - it hasn't been set.
        if (is_null($this->currentdata)) {
            return false;
        }

        // Active if any string has been set, even if empty.
        return true;
    }

    /**
     * @param \stdClass $source
     * @return array [string $where, array $params]
     */
    protected function make_compare(\stdClass $source): array {
        global $DB;

        if (!$this->is_active()) {
            throw new \coding_exception(
                'Tried to do full text search without specifying some text'
            );
        }

        $params = [];
        if (false !== stripos($this->currentdata, '*')) {
            // Refresh FTS source joining because the join snippet, params, and values depend on exact search term,
            // which was initially unreachable. Only refresh if the currentdata has asterisk in it.
            [$this->joinsql, $this->joinparams] = $DB->get_fts_subquery(
                $this->basealias,
                $this->fieldsandweights,
                $this->currentdata
            );

            $source->table = $this->joinsql;
            $params = $this->joinparams;
        } else {
            foreach ($this->joinparams as $key => $value) {
                $params[$key] = $this->currentdata;
            }
        }

        return ["", $params];
    }
}