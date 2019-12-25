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


require_once($CFG->dirroot . '/totara/cohort/rules/sqlhandlers/custom_fields/operators.php');
require_once($CFG->dirroot . '/totara/cohort/rules/sqlhandlers/custom_fields/persistence_metadata.php');
require_once($CFG->dirroot . '/totara/cohort/rules/sqlhandlers/custom_fields/sql_fragment.php');


/**
 * Provides high level services to manipulate custom fields.
 *
 * Notes:
 * - To make things clear while reading the code in this class:
 *   - Conceptually, a custom field is just a single value attached to a single
 *     instance of a "parent module". If the parent module instance is a set of
 *     canned data eg user profile, position, organization, then a custom field
 *     just extends the attributes for the parent module instance.
 *   - A custom field has a template to define how it renders onscreen and its
 *     valid values. This abstraction is called "custom field definition" in the
 *     code. Custom field definitions are shared.
 *   - A parent module instance creates and fills in a value for an instance of
 *     a custom field; that custom field is now associated to that and only that
 *     parent instance. In the code, this custom field instance is referred to
 *     as the "custom field value".
 *
 * @todo Currently this only handles menu, text and checkbox custom fields; it
 *       should not be too difficult to fit it into an implementation with a
 *       common approach to all types of custom fields.
 */
final class custom_fields {
    /**
     * Custom field types.
     *
     * Note: the existing Totara code hardcodes these values everywhere.
     */
    const FIELD_TYPE_MENU = 'menu';
    const FIELD_TYPE_TEXT = 'text';
    const FIELD_TYPE_CHECKBOX = 'checkbox';

    /**
     * Returns an SQL statement that gets parent module instances whose custom
     * field values match the given values.
     *
     * Notes:
     * - The returned SQL statement starts with "1=1 AND EXISTS". This is a
     *   consequence of the existing design in which SQL strings are assembled
     *   together in the hope that the final string is syntatically correct SQL.
     * - The returned SQL is also embedded within "()". If the brackets are not
     *   there, the assembled SQL works for single rules but not if many rules
     *   are used together.
     * - The SQL fragment contains "magic" references eg 'u.id' *referenced from
     *   final SQL statement*.
     * - The SQL here is way too complicated and really fragile. However, given
     *   the way the existing mechanism is designed, it is the only way possible
     *   short of revamping everything.
     *
     * @param \custom_field_query $query query parameters.
     *
     * @return \sql_prepared_statement prepared statement instance.
     */
    public static function sql_entities_for(\custom_field_query $query) {
        // The repository distinguishes between entities with custom fields that
        // have been explicitly filled in and those whose fields have not. That
        // means entities which do not have explicit custom field values still
        // have "valid" values. Hence the two separate subqueries - "values" and
        // "defaults".
        $initial = new \sql_fragment('', new \sql_prepared_statement('1=1'));
        $values = new \sql_fragment('AND EXISTS', self::for_values($query));
        $defaults = new \sql_fragment('OR EXISTS', self::for_defaults($query));

        return \sql_fragment::join(
            $initial, $values, $defaults
        )->bracketed();
    }

    /**
     * Returns an SQL snippet to identify the entities whose custom field values
     * match the query values.
     *
     * @param \custom_field_query $query query parameters.
     *
     * @return \sql_prepared_statement prepared statement instance.
     */
    private static function for_values(\custom_field_query $query) {
        $metadata = $query->values_metadata();
        list($col_field, $col_parent, $col_value) = array_map(
            // The callback to invoke on each column name. This transforms the
            // name into a name qualified by the table alias.
            function ($field) use ($metadata) {
                return $metadata->sql_column_for($field);
            },

            // List of column names to alias.
            array(
                custom_field_values_metadata::FIELD_ID,
                custom_field_values_metadata::FIELD_PARENT,
                custom_field_values_metadata::FIELD_DATA
            )
        );

        $field_id = $query->field();
        $col_outer_parent = $query->embedded_parent_col();
        $table = $metadata->sql_aliased_table();

        // Note the reference "$col_outer_parent" from the final SQL statement.
        $sql = "SELECT 1
            FROM $table
           WHERE $col_parent = $col_outer_parent
             AND $col_field = $field_id
         ";

        return self::assemble($sql, $col_value, $query);
    }

    /**
     * Returns an SQL snippet to identify the entities who do not have explicit
     * custom field values but have matching "default" values.
     *
     * @param \custom_field_query $query query parameters.
     *
     * @return \sql_prepared_statement prepared statement instance.
     */
    private static function for_defaults(\custom_field_query $query) {
        $parent_metadata = $query->parent_metadata();
        $parent_table = $parent_metadata->sql_aliased_table();
        $col_parent_id = $parent_metadata->sql_pk();

        $defs_metadata = $query->defs_metadata();
        $custom_def_table = $defs_metadata->sql_aliased_table();
        $col_custom_id = $defs_metadata->sql_pk();

        $values_metadata = $query->values_metadata();
        $custom_value_table = $values_metadata->sql_aliased_table();

        list($col_value_field_id, $col_value_parent_id) = array_map(
            function ($field) use ($values_metadata) {
                return $values_metadata->sql_column_for($field);
            },

            array(
                custom_field_values_metadata::FIELD_ID,
                custom_field_values_metadata::FIELD_PARENT
            )
        );

        $field_id = $query->field();
        $parent_id = $query->embedded_parent_col();

        // A big gotcha here is that entities and custom field definition tables
        // do not have common columns; hence the need for a database cross join.
        // Luckily this is tempered by the WHERE clauses in place.
        //
        // To further increase performance, the WHERE conditions are ordered by
        // decreasing filtering efficiency. In theory, the 1st condition filters
        // out the majority of records before the 2nd kicks in, etc. In reality
        // of course, it is the database query optimizer that calls the shots.
        // But no harm declaring the SQL in this fashion.
        //
        // Also note the reference "$parent_id" from the final SQL statement.
        $sql = "
            SELECT 1
              FROM $parent_table, $custom_def_table
             WHERE $col_parent_id = $parent_id
               AND $col_custom_id = $field_id
               AND NOT EXISTS (
                    SELECT 1
                      FROM $custom_value_table
                     WHERE $col_value_parent_id = $col_parent_id
                       AND $col_value_field_id = $field_id
              )
        ";

        $col_value = $defs_metadata->sql_column_for(
            custom_field_definitions_metadata::FIELD_DATA
        );
        return self::assemble($sql, $col_value, $query);
    }

    /**
     * Returns an SQL fragment to identify entities who have matching values for
     * custom fields.
     *
     * @param string $main_sql main sql query.
     * @param string $col custom field column on which to do a search.
     * @param \custom_field_query $query query details.
     *
     * @return \sql_prepared_statement prepared statement instance.
     */
    private static function assemble(
        $main_sql,
        $col,
        \custom_field_query $query
    ) {
        $values = $query->values();
        $operator = $query->operator();
        $comparison_sql = operators::sql_for($col, $values, $operator);

        return \sql_fragment::join(
            new \sql_fragment('', new \sql_prepared_statement($main_sql)),
            new \sql_fragment('AND', $comparison_sql)
        );
    }
}

/**
 * Convenience class to hold query details on a custom field. This allows the
 * component API to be stable while allowing for future expansion.
 */
final class custom_field_query {
    // Indicates the custom field type on which to do the match.
    private $field = 0;

    // Reference values to match with the custom field value.
    private $values = array();

    // Comparison operator to use for matching. This is an int value and must be
    // interpreted in the context of the field type specified.
    private $operator = 0;

    // Custom field values metadata.
    private $values_metadata = null;

    // Custom field definitions metadata.
    private $defs_metadata = null;

    // Custom field parent entity metadata.
    private $parent_metadata = null;

    // Parent entity id column from the final SQL. Due the way the final SQL is
    // phrased, the returned SQL needs to embed this as a magic string.
    private $embedded_parent_col = null;

    // Custom field type; needed since generated SQL could be dependent of the
    // type of custom field being considered.
    private $field_type = null;

    /**
     * Returns a query for handling custom fields attached to user profiles.
     *
     * @param int $field custom field identifier on which to do the match.
     * @param array $values reference values to match with custom field value.
     * @param int $operator comparison operator enumeration to use for matching.
     * @param string $field_type one of the custom_fields::FIELD_TYPE constants.
     *
     * @return \custom_field_query query instance.
     */
    public static function user_query(
        $field,
        array $values,
        $operator,
        $field_type
    ) {
        $query = new custom_field_query();

        $query->field = $field;
        $query->values = $values;
        $query->operator = $operator;
        $query->field_type = $field_type;
        $query->values_metadata = \custom_field_values_metadata::for_user();
        $query->defs_metadata = \custom_field_definitions_metadata::for_user();
        $query->parent_metadata = \user_profile_metadata::persistence_metadata();
        $query->embedded_parent_col = 'u.id';

        return $query;
    }

    /**
     * Default constructor. This is private to force callers to use the factory
     * methods provided.
     */
    private function __construct() {}

    /**
     * Returns the field identifier on which to do the match.
     *
     * @return int field identifier.
     */
    public function field() {
        return $this->field;
    }

    /**
     * Returns query values to match with the custom field value.
     *
     * @return array query values.
     */
    public function values() {
        return $this->values;
    }

    /**
     * Returns the comparison operator enumeration to use for matching.
     *
     * @return int comparison operator enumeration.
     */
    public function operator() {
        return $this->operator;
    }

    /**
     * Returns custom field values metadata instance to use.
     *
     * @return \persistence_metadata values metadata instance.
     */
    public function values_metadata() {
        return $this->values_metadata;
    }

    /**
     * Returns the custom field definitions metadata instance to use.
     *
     * @return \persistence_metadata definitions metadata instance.
     */
    public function defs_metadata() {
        return $this->defs_metadata;
    }

    /**
     * Returns custom field parent metadata instance to use.
     *
     * @return \persistence_metadata parent metadata instance.
     */
    public function parent_metadata() {
        return $this->parent_metadata;
    }

    /**
     * Returns the parent entity id column from the final SQL. Due the way the
     * final SQL is phrased, the returned SQL needs to embed this as a magic
     * string.
     *
     * @return string the parent column.
     */
    public function embedded_parent_col() {
        return $this->embedded_parent_col;
    }

    /**
     * Returns the custom field type.
     *
     * @return string custom field type.
     */
    public function field_type() {
        return $this->field_type;
    }
}
