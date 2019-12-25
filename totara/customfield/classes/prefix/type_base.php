<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2010 onwards Totara Learning Solutions LTD
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
 * @author Maria Torres <maria.torres@totaralms.com>
 * @package totara_customfield
 */

namespace totara_customfield\prefix;
defined('MOODLE_INTERNAL') || die();

abstract class type_base {
    protected $tableprefix;
    protected $shortprefix;
    protected $prefix;
    protected $context;
    protected $other;

    /**
     * @param string $prefix Prefix of the customfield type
     * @param string $tableprefix Table prefix of the customfield type
     * @param string $shortprefix Short prefix of the customfield type
     * @param \context $context Context in which capabilities should be evaluated.
     * @param array $extrainfo Extra info containing the required and optional params passed to the page.
     */
    function __construct($prefix, $tableprefix, $shortprefix, $context, $extrainfo = array()) {
        $this->prefix = $prefix;
        $this->context = $context;
        $this->other = $extrainfo;
        $this->tableprefix = $tableprefix;
        $this->shortprefix = $shortprefix;
    }

    // Define capabilities required in the customfield type.
    abstract function get_capability_managefield();

    /**
     * Is feature type disabled?
     *
     * @return bool True if the type is disabled, false otherwise.
     */
    public function is_feature_type_disabled() {
        return false;
    }

    /**
     * Get the table prefix.
     *
     * @return string The table prefix
     */
    public function get_table_prefix() {
        return $this->tableprefix;
    }

    /**
     * Get the short prefix.
     *
     * @return string The short prefix
     */
    public function get_short_prefix() {
        return $this->shortprefix;
    }

    /**
     * Get the customfield prefix.
     *
     * @return string The prefix
     */
    public function get_prefix() {
        return $this->prefix;
    }

    /**
     * Get the context.
     *
     * @return \context context
     */
    public function get_context() {
        return $this->context;
    }

    /**
     * Get other params. Usually we pass all the required and optional params to other
     * so we can get access to those variables in case they are needed.
     *
     * @return Array other
     */
    public function get_other() {
        return $this->other;
    }

    /**
     * Create or update a customfield.
     *
     * @param array $data Data from the form
     * @return int the ID of the customfield created or updated.
     */
    public function edit($data) {
        global $DB;

        $tableprefix = $this->tableprefix;

        if (empty($data->id)) {
            unset($data->id);
            $data->id = $DB->insert_record($tableprefix.'_info_field', $data);
        } else {
            $data->id = $DB->update_record($tableprefix.'_info_field', $data);
        }

        $this->reorder_fields();

        return $data->id;
    }

    /**
     * Delete customfield.
     *
     * @param int $id ID of the customfield we want to delete.
     */
    public function delete($id) {
        global $DB;

        $tableprefix = $this->tableprefix;

        $dataids = $DB->get_fieldset_select($tableprefix.'_info_data', 'id', 'fieldid = :fieldid', array('fieldid' => $id));
        if (!empty($dataids)) {
            list($sql, $params) = $DB->get_in_or_equal($dataids);
            $tablename = $tableprefix.'_info_data_param';
            $DB->execute("DELETE FROM {{$tablename}} WHERE dataid $sql", $params);
        }

        // Remove any user data associated with this field.
        $DB->delete_records($tableprefix.'_info_data', array('fieldid' => $id));

        // Try to remove the record from the database.
        $DB->delete_records($tableprefix.'_info_field', array('id' => $id));

        // Reorder the remaining fields.
        $this->reorder_fields();
    }

    /**
     * Move a customfield up or down.
     *
     * @param int $id ID of the customfield we want to move.
     * @param string $move the direction - 'up' or 'down'.
     * @return bool
     */
    public function move($id, $move) {
        global $DB;

        $tableprefix = $this->tableprefix;
        $field = static::get_field_to_move($tableprefix, $id);

        if ($move === 'up') {
            $sqloperator = '<';
            $sqldirection = 'DESC';
        } else {
            $move = 'down';
            $sqloperator = '>';
            $sqldirection = 'ASC';
        }
        $sql = "SELECT id,sortorder
                      FROM {{$tableprefix}_info_field} cif
                     WHERE sortorder {$sqloperator} :sortorder
                  ORDER BY sortorder {$sqldirection}";

        $params = ['sortorder' => $field->sortorder];
        $swapfields = $DB->get_records_sql($sql, $params, 0, 1);

        if (count($swapfields) !== 1) {
            debugging('Invalid action, the selected field cannot be moved '.$move, DEBUG_DEVELOPER);
            return false;
        }
        $swapfield = reset($swapfields);

        $holding = $field->sortorder;
        $field->sortorder = $swapfield->sortorder;
        $swapfield->sortorder = $holding;

        // Always together.
        $transaction = $DB->start_delegated_transaction();
        $DB->update_record($tableprefix.'_info_field', $field);
        $DB->update_record($tableprefix.'_info_field', $swapfield);
        $transaction->allow_commit();

        // Finally re-order all fields, just to be safe.
        // Needed because those on earlier versions may have unbalanced sortorders to begin with.
        $this->reorder_fields();
        return true;
    }

    /**
     * Set the default sql where used to get the defined custom field of the corresponding type.
     *
     * @return array Array containing the conditions for the search.
     */
    public function get_fields_sql_where() {
        return array();
    }

    /**
     * Get an array of conditions to look for fields
     *
     * @param int $neworder New sortorder value
     * @param stdClass $field Record representing the custom field
     * @return array
     */
    public static function get_conditions_swapfields($neworder, $field) {
        return array('sortorder' => $neworder);
    }

    /**
     * Get the field record to move.
     *
     * @param string $tableprefix The table prefix
     * @param int $id The ID of the custom field that we want to move
     * @return mixed
     */
    public static function get_field_to_move($tableprefix, $id) {
        global $DB;
        return $DB->get_record($tableprefix.'_info_field', array('id' => $id), 'id, sortorder');
    }

    /**
     * Get the defined customfield for the corresponding type.
     *
     * @param array $where Where conditions to look for the customfields.
     * @return array Customfield records found.
     */
    public function get_defined_fields(array $where = array()) {
        global $DB;
        return $DB->get_records($this->tableprefix.'_info_field', $where, 'sortorder ASC');
    }

    /**
     * Reordering fields in database
     *
     * @return bool Result of the action executed.
     */
    public function reorder_fields() {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/totara/core/utils.php');
        $tableprefix = $this->tableprefix;
        $rs = $DB->get_recordset($tableprefix . '_info_field', array(), 'sortorder ASC');
        $i = 1;
        foreach ($rs as $field) {
            $f = new \stdClass();
            $f->id = $field->id;
            $f->sortorder = $i++;
            $DB->update_record($tableprefix.'_info_field', $f);
        }
        $rs->close();
        return true;
    }

    /**
     * Returns the sortorder value a new field should use.
     * @return int
     */
    public function get_next_sortorder() {
        global $DB;
        $sql = "SELECT id, sortorder
                  FROM {{$this->tableprefix}_info_field}
              ORDER BY sortorder DESC";
        $result = $DB->get_records_sql($sql, null, 0, 1);
        if (empty($result)) {
            // It will be the first field.
            return 1;
        } else {
            $record = reset($result);
            return $record->sortorder + 1;
        }
    }

}
