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
 * Metadata about persistent domain entities.
 */
final class persistence_metadata {
    /**
     * Associated RDBMS table name.
     */
    private $table = null;

    /**
     * Convenience RDBMS table name alias.
     */
    private $table_alias = null;

    /**
     * Table columns corresponding to persistent object fields.
     */
    private $field_columns = array();

    /**
     * Primary key column name.
     */
    private $col_pk = null;

    /**
     * Default constructor.
     *
     * @param string $table associated RDBMS table name.
     * @param string $table_alias associated RDBMS table alias.
     * @param array $field_columns a mapping of field names to table columns.
     * @param string $col_primary key column name.
     */
    public function __construct(
        $table,
        $table_alias,
        array $field_columns,
        $col_pk
    ) {
        if (empty($table)) {
            throw new coding_exception('table is null');
        }
        if (empty($table_alias)) {
            throw new coding_exception('table alias is null');
        }
        if (empty($field_columns)) {
            throw new coding_exception('fields are empty');
        }
        if (empty($col_pk)) {
            throw new coding_exception('pk column is empty');
        }

        $this->table = $table;
        $this->table_alias = $table_alias;
        $this->field_columns = $field_columns;
        $this->col_pk = $col_pk;
    }

    /**
     * Convenience function to create the table name and alias for an "SQL FROM"
     * clause.
     *
     * @return string aliased name.
     */
    public function sql_aliased_table() {
        return sprintf('{%s} %s', $this->table, $this->table_alias);
    }

    /**
     * Returns the column for the specified field.
     *
     * @param string $field field name to look up.
     *
     * @return string column name prefixed by the table alias for use in an SQL
     *         statement. Returns null if the field is unknown.
     */
    public function sql_column_for($field) {
        if (!array_key_exists($field, $this->field_columns)) {
            return null;
        }

        $column = $this->field_columns[$field];
        return sprintf('%s.%s', $this->table_alias, $column);
    }

    /**
     * Convenience function to return the entity's primary key table column.
     *
     * @return string primary key column name prefixed by the table alias.
     */
    public function sql_pk() {
        return $this->sql_column_for($this->col_pk);
    }
}

/**
 * Metadata about persistent custom field values. This are the values that are
 * attached to an associated parent entity instance.
 */
final class custom_field_values_metadata {
    /**
     * Field names.
     */
    const FIELD_DATA = 'data';
    const FIELD_ID = 'id';
    const FIELD_PARENT = 'parent_id';

    /**
     * List of "recognized" enclosing modules.
     *
     * The physical table structure for custom fields is the same, whatever the
     * context in which they are used. The only difference is that a context has
     * its own custom field tables eg for user profiles it is 'usr_info_data',
     * whereas for positions it is 'pos_info_data'.
     */
    private static $modules = array(
        'comp' => array('comp', 'competencyid'),
        'goal' => array('goal', 'goalid'),
        'goal_user' => array('goal_user', 'goal_userid'),
        'org' => array('org_type', 'organisationid'),
        'pos' => array('pos_type', 'positionid'),
        'prog' => array('prog', 'programid'),
        'user' => array('user', 'userid')
    );

    /**
     * Creates instances of this class that is associated with the competency
     * module.
     *
     * @return \persistence_metadata competency custom field values metadata.
     */
    public static function for_competency() {
        return self::get_for('comp');
    }

    /**
     * Creates instances of this class that is associated with the goal module.
     *
     * @return \persistence_metadata goal custom field values metadata.
     */
    public static function for_goal() {
        return self::get_for('goal');
    }

    /**
     * Creates instances of this class that is associated with the user goal
     * module.
     *
     * @return \persistence_metadata user goal custom field values metadata.
     */
    public static function for_goal_user() {
        return self::get_for('goal_user');
    }

    /**
     * Creates instances of this class that is associated with the organization
     * module.
     *
     * @return \persistence_metadata organization custom field values metadata.
     */
    public static function for_org() {
        return self::get_for('org');
    }

    /**
     * Creates instances of this class that is associated with the position
     * module.
     *
     * @return \persistence_metadata position custom field values metadata.
     */
    public static function for_pos() {
        return self::get_for('pos');
    }

    /**
     * Creates instances of this class that is associated with the program
     * module.
     *
     * @return \persistence_metadata program custom field values metadata.
     */
    public static function for_prog() {
        return self::get_for('prog');
    }

    /**
     * Creates instances of this class that is associated with the user module.
     *
     * @return \persistence_metadata user custom field values metadata.
     */
    public static function for_user() {
        return self::get_for('user');
    }

    /**
     * Creates an instance of \persistence_metadata for custom field values.
     *
     * @param string $module module to which the custom field is attached. Valid
     *        Valid values are the keys from self::$modules.
     *
     * @return \persistence_metadata custom field values metadata instance.
     */
    private static function get_for($module) {
        list($prefix, $col_module) = self::$modules[$module];

        $table = sprintf("%s_info_data", $prefix);
        $alias = sprintf("%s_cfv", $prefix);
        $fields = array(
            self::FIELD_DATA   => 'data',
            self::FIELD_ID     => 'fieldid',
            self::FIELD_PARENT => $col_module
        );

        return new \persistence_metadata(
            $table, $alias, $fields, self::FIELD_ID
        );
    }

    /**
     * Default constructor. This is private to force callers to use the factory
     * methods provided.
     */
    private function __construct() {
        // EMPTY BLOCK.
    }
}

/**
 * Metadata about persistent custom field definitions.
 */
final class custom_field_definitions_metadata {
    /**
     * Field names.
     */
    const FIELD_DATA = 'data';
    const FIELD_ID = 'id';
    const FIELD_NAME = 'name';

    /**
     * Creates instances of this class that is associated with the competency
     * module.
     *
     * @return \persistence_metadata competency custom field definition metadata.
     */
    public static function for_competency() {
        return self::get_for('comp');
    }

    /**
     * Creates instances of this class that is associated with the goal module.
     *
     * @return \persistence_metadata goal custom field definition metadata.
     */
    public static function for_goal() {
        return self::get_for('goal');
    }

    /**
     * Creates instances of this class that is associated with the user goal
     * module.
     *
     * @return \persistence_metadata user goal custom field definition metadata.
     */
    public static function for_goal_user() {
        return self::get_for('goal_user');
    }

    /**
     * Creates instances of this class that is associated with the organization
     * module.
     *
     * @return \persistence_metadata organization custom field definition metadata.
     */
    public static function for_org() {
        return self::get_for('org');
    }

    /**
     * Creates instances of this class that is associated with the position
     * module.
     *
     * @return \persistence_metadata position custom field definition metadata.
     */
    public static function for_pos() {
        return self::get_for('pos');
    }

    /**
     * Creates instances of this class that is associated with the program
     * module.
     *
     * @return \persistence_metadata program custom field definition metadata.
     */
    public static function for_prog() {
        return self::get_for('prog');
    }

    /**
     * Creates instances of this class that is associated with the user module.
     *
     * @return \persistence_metadata user custom field definition metadata.
     */
    public static function for_user() {
        return self::get_for('user');
    }

    /**
     * Creates a \persistence_metadata for custom field definitions.
     *
     * @param string $module module to which the custom field is attached. Valid
     *        values are from self::$modules.
     *
     * @return \persistence_metadata custom field definition metadata.
     */
    private static function get_for($module) {
        $table = sprintf("%s_info_field", $module);
        $alias = sprintf("%s_cfd", $module);
        $fields = array(
            self::FIELD_DATA => 'defaultdata',
            self::FIELD_ID     => 'id',
            self::FIELD_NAME => 'name'
        );

        return new \persistence_metadata(
            $table, $alias, $fields, self::FIELD_ID
        );
    }

    /**
     * Default constructor. This is private to force callers to use the factory
     * methods provided.
     */
    private function __construct() {
        // EMPTY BLOCK.
    }
}


/**
 * Metadata about user profiles.
 */
final class user_profile_metadata {
    /**
     * Field names.
     */
    const FIELD_ID = 'id';

    /**
     * Creates an instance of persistence_metadata for user profiles.
     *
     * @return \persistence_metadata user profile metadata instance.
     */
    public static function persistence_metadata() {
        $fields = array(
            self::FIELD_ID => 'id'
        );

        return new \persistence_metadata(
            'user', 'usr', $fields, self::FIELD_ID
        );
    }

    /**
     * Default constructor. This is private to force callers to use the factory
     * methods provided.
     */
    private function __construct() {
        // EMPTY BLOCK.
    }
}
