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


/**
 * Contains an SQL string together with its prefix that allows the SQL string to
 * fit into a larger SQL statement.
 */
final class sql_fragment {
    /**
     * Prepared SQL statement.
     */
    private $statement = null;

    /**
     * Prefix to use when fitting this SQL fragment with another SQL fragment.
     */
    private $join_to = null;

    /**
     * Merges the specified fragments.
     *
     * @todo Currently, the "..." varargs operator is only supported in PHPv5.6
     *       onwards and Totara 9.0 is supposed to run on PHP 5.5.9. Hence the
     *       code uses the old 'func_get_args()' way of getting varargs. However
     *       this should be updated once the minimum PHP version changes to 5.6.
     *
     * @param \sql_fragment,... $fragments sql fragments to merge.
     *
     * @return sql_prepared_statement prepared statement with merged SQL.
     */
    public static function join($fragments) {
        $to_be_merged = func_get_args();

        return array_reduce(
            $to_be_merged,

            // Callback to invoke on each fragment. After an iteration, $merged
            // has the accumulated SQL string that has been built up so far.
            function (
                \sql_prepared_statement $merged,
                \sql_fragment $snippet
            ) {
                return $merged->append($snippet->join_to, $snippet->statement);
            },

            // The initial (empty) value for the merged statement.
            new \sql_prepared_statement('')
        );
    }

    /**
     * Default constructor.
     *
     * @param string $join_to phrase to use when joining the fragment to another
     *        eg 'AND EXISTS','OR', etc.
     * @param \sql_prepared_statement $statement SQL statement to embed.
     */
    public function __construct(
        $join_to,
        \sql_prepared_statement $statement
    ) {
        if (is_null($statement)) {
            throw new coding_exception('fragment statement is null');
        }

        $this->statement = $statement;
        $this->join_to = empty($join_to) ? '' : $join_to;
    }

    /**
     * Returns a string version of this object. Debugging with "print(fragment)"
     * will now not fail with an unhelpful "cannot convert to string" message.
     *
     * @return string stringified object.
     */
    public function __toString() {
        $sql = $this->statement;
        $prefix = $this->join_to;

        return sprintf('%s[sql=%s, prefix=%s]', __CLASS__, $sql, $prefix);
    }
}


/**
 * Holds a complete or partial SQL prepared statement. This is an SQL fragment
 * containing placeholders that will be substituted with the actual values when
 * the SQL is executed.
 */
final class sql_prepared_statement {
    /**
     * Prepared statement string.
     */
    private $sql = null;

    /**
     * Mapping of prepared statement placeholder names to actual values.
     */
    private $parameters = null;

    /**
     * Default constructor.
     *
     * @param string $sql SQL string.
     * @param array $parameters prepared statement parameters. This is a mapping
     *        of placeholder names to values.
     */
    public function __construct(
        $sql,
        array $parameters=array()
    ) {
        if (is_null($sql)) {
            throw new coding_exception('prepared statement sql is null');
        }
        if (is_null($parameters)) {
            throw new coding_exception('prepared statement parameters is null');
        }

        $this->sql = trim($sql);
        $this->parameters = $parameters;
    }

    /**
     * Returns a string version of this object.
     *
     * @return string stringified object.
     */
    public function __toString() {
        $parameters = array_reduce(
            array_keys($this->parameters),

            function (
                $accumulated,
                $key
            ) {
                $value = $this->parameters[$key];
                $string = "$key=$value";
                return empty($accumulated) ? $string : "$accumulated, $string";
            },

            ''
        );

        return sprintf(
            '%s[sql=%s, parameters=[%s]]', __CLASS__, $this->sql, $parameters
        );
    }

    /**
     * Appends the contents of the specified SQL prepared statement to this one.
     *
     * Note these caveats:
     * - If the current statement is empty, the statement to append is returned
     *   WITHOUT a prefix.
     * - If the statement to append is empty, the current statement is returned
     *   again, without any prefix.
     * - The two rules above work in 99% of the use cases. However, if a merged
     *   statement needs to have a "dangling" prefix in front of it, start with
     *   an initial statement that has the prefix as the SQL string. Then append
     *   other statements to that eg:
     *   <pre>
     *      $initial = new \sql_prepared_statement('EXISTS');
     *      $another = new \sql_prepared_statement('SELECT ...);
     *      $merged = $initial->append('', $another);
     *   </pre>
     *   That creates a merged "EXISTS (SELECT ...)".
     * - Unfortunately since these are strings being joined together, it is not
     *   possible to check if a merged statement is syntatically correct SQL.
     *
     * @param string $join_to prefix to use before appending the given statement
     *        to the current one eg OR', 'AND EXISTS', etc.
     * @param \sql_prepared_statement $other prepared statement to append to
     *        this one.
     *
     * @return \sql_prepared_statement prepared statement with merged contents.
     */
    public function append(
        $join_to,
        \sql_prepared_statement $other
    ) {
        if (empty($this->sql)) {
            return $other;
        }
        else if (empty($other->sql)) {
            return $this;
        }

        $this_sql = $this->sql;
        $that_sql = sprintf("(\n%s\n)", $other->sql);

        $merged_sql = "$this_sql $join_to $that_sql";
        $merged_parameters = array_merge($this->parameters, $other->parameters);

        return new \sql_prepared_statement($merged_sql, $merged_parameters);
    }

    /**
     * Returns a \stdClass class with the arbitrary fields "sql" and "params".
     * Unfortunately the legacy custom field  matching code uses this mechanism;
     * hence this method.
     *
     * @return \stdClass object with SQL details.
     */
    public function as_sqlhandler_class() {
        $sqlhandler = new stdClass();
        $sqlhandler->sql = $this->sql;
        $sqlhandler->params = $this->parameters;

        return $sqlhandler;
    }

    /**
     * Returns a new prepared statement whose SQL string is this object's SQL
     * string but contained within brackets.
     *
     * @return \sql_prepared_statement prepared statement whose contents are in
     *         brackets.
     */
    public function bracketed() {
        $bracketed_sql = sprintf("(%s)", $this->sql);
        return new \sql_prepared_statement($bracketed_sql, $this->parameters);
    }
}
