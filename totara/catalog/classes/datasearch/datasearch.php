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
 * Base class for search.
 */
class datasearch {

    /** @var string */
    private $basetable;

    /** @var string */
    private $outputcolumns;

    /** @var string */
    private $sort;

    /** @var filter[] */
    private $filters = [];

    /**
     * @param string $basetable
     * @param string $outputcolumns
     * @param string $sort
     */
    public function __construct(string $basetable, string $outputcolumns, string $sort = "") {
        $this->basetable = $basetable;
        $this->outputcolumns = $outputcolumns;
        $this->sort = $sort;
    }

    /**
     * @param filter $filter
     */
    final public function add_filter(filter $filter) {
        $alias = $filter->get_alias();

        // If the filter matches an existing one, merge their sources.
        if (isset($this->filters[$alias])) {
            $existingfilter = $this->filters[$alias];

            $existingfilter->merge($filter);
            return;
        }

        $this->filters[$alias] = $filter;
    }

    /**
     * @return array [string $joinsql, string $wheresql, array $params]
     */
    final public function get_filter_joins() {
        $joins = [];
        $wheres = [];
        $params = [];

        foreach ($this->filters as $filter) {
            list($filterjoin, $filterwhere, $filterparams) = $filter->make_sql();

            // Exclude duplicate joins.
            if (!empty($filterjoin) && !isset($joins[$filterjoin])) {
                $joins[$filterjoin] = $filterjoin;
            }

            if (!empty($filterwhere)) {
                $wheres[] = $filterwhere;
            }

            $params += $filterparams;
        }

        $joinsql = implode(
            "
              ",
            $joins
        );
        $wheresql = implode(
            "
               AND ",
            $wheres
        );

        return [$joinsql, $wheresql, $params];
    }

    /**
     * Get the full query.
     *
     * @return array [string $selectsql, string $countsql, array $params]
     */
    final public function get_sql(): array {
        list($joinsql, $wheresql, $params) = $this->get_filter_joins();

        $bodysql = "  FROM {$this->basetable}
            ";

        if (!empty($joinsql)) {
            $bodysql .= "  {$joinsql}
            ";
        }

        if (!empty($wheresql)) {
            $bodysql .= " WHERE {$wheresql}
            ";
        }

        $countsql = "
            SELECT COUNT(1)
            " . $bodysql;

        $selectsql = "
            SELECT DISTINCT {$this->outputcolumns}
            " . $bodysql;

        if (!empty($this->sort)) {
            $selectsql .= " ORDER BY {$this->sort}
            ";
        }

        return [$selectsql, $countsql, $params];
    }
}
