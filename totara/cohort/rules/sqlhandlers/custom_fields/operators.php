<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2016 onwards Totara Learning Solutions LTD
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
 * @author Murali Nair <murali.nair@totaralms.com>
 * @package totara_cohort
 */

require_once($CFG->dirroot . '/totara/cohort/rules/lib.php');

require_once($CFG->dirroot . '/totara/cohort/rules/sqlhandlers/custom_fields/sql_fragment.php');

require_once($CFG->dirroot . '/totara/core/searchlib.php');


/**
 * Manages cohort rule comparison operations.
 */
final class operators {
    /**
     * Search conditions for value based comparisons. These are a subset of the
     * parameters to the search_get_keyword_where_clause_options() function.
     */
    private static $value_based_comparisons = array(
        COHORT_RULES_OP_IN_CONTAINS   => array(false, 'contains'),
        COHORT_RULES_OP_IN_NOTCONTAIN => array(true, 'contains'),
        COHORT_RULES_OP_IN_ISEQUALTO  => array(false, 'equal'),
        COHORT_RULES_OP_IN_NOTEQUALTO => array(true, 'notequal'),
        COHORT_RULES_OP_IN_STARTSWITH => array(false, 'startswith'),
        COHORT_RULES_OP_IN_ENDSWITH   => array(false, 'endswith')
    );

    /**
     * List of operations which should account for SQL NULLs.
     */
    private static $null_based_comparisons = array(
        COHORT_RULES_OP_IN_NOTCONTAIN,
        COHORT_RULES_OP_IN_NOTEQUALTO,
        COHORT_RULES_OP_IN_ISEMPTY
    );

    /**
     * Returns the SQL WHERE conditions that compares the specified value using
     * the given comparison operator.
     *
     * Notes:
     * - Adapted from cohort_rule_sqlhandler_in::get_query_base_operator() and
     *   cleaned up in the process.
     *
     * @param string $column table column to use for matching.
     * @param array $values values to match.
     * @param int $operator one of the COHORT_RULES_OP_IN_ constants.
     *
     * @return \sql_prepared_statement prepared statement instance.
     *
     * @throws \coding_exception if the operator is not a COHORT_RULES_OP_IN_
     *         enumeration.
     */
    public static function sql_for(
        $column,
        array $values,
        $operator
    ) {
        // To a database, an SQL NULL is a distinct value which can never match
        // anything else. To the rest of the universe, an SQL NULL equals '' or
        // an empty string or is always "not equals" to some value.
        $empty_sql = in_array($operator, self::$null_based_comparisons)
            ? "$column IS NULL"
            : '';

        return \sql_fragment::join(
            new \sql_fragment('', new \sql_prepared_statement($empty_sql)),
            new \sql_fragment('OR', self::where($column, $values, $operator))
        );
    }

    /**
     * Returns the SQL WHERE conditions for non NULL comparisons.
     *
     * @param string $column table column to use for matching.
     * @param array $values values to match.
     * @param int $operator one of the COHORT_RULES_OP_IN_ constants.
     *
     * @return \sql_prepared_statement prepared statement instance.
     *
     * @throws \coding_exception if the operator is not a COHORT_RULES_OP_IN_
     *         enumeration.
     */
    private static function where(
        $column,
        array $values,
        $operator
    ) {
        if ($operator == COHORT_RULES_OP_IN_ISEMPTY) {
            return new sql_prepared_statement("$column = ''");
        }

        if (!array_key_exists($operator, self::$value_based_comparisons)) {
            throw new coding_exception("invalid value for operator :$operator");
        }

        list($negate, $op) = self::$value_based_comparisons[$operator];
        list($sql, $parameters) = search_get_keyword_where_clause_options(
            $column, $values, $negate, $op
        );

        return new \sql_prepared_statement($sql, $parameters);
    }

    /**
     * Default constructor. This is private to force callers to use the factory
     * methods provided.
     */
    private function __construct() {
        // EMPTY BLOCK.
    }
}
