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
 * @author Aaron Wells <aaronw@catalyst.net.nz>
 * @package totara
 * @subpackage cohort/rules/sqlhandlers
 */
/**
 * This file contains sqlhandlers for rules expressed in SQL as "somecolumn IN (value1, value2, value3...)"
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    //  It must be included from a Moodle page
}

/**
 * This SQL handler handles rules that can be expressed as checking whether
 * a given database column's value matches any values in a supplied
 * list of values. In SQL terms, "column IN (1,2,3...)" or "column NOT IN (1,2,3...)"
 */
abstract class cohort_rule_sqlhandler_in extends cohort_rule_sqlhandler {

    public $params = array(
        'equal'=>0,
        'listofvalues'=>1
    );


    /**
     * The field we're comparing against. May be a column name or a custom field id
     * @var mixed
     */
    public $field;

    /**
     * Whether the field we're doing "IN" on holds a char datatype or not
     * @var bool
     */
    public $ischarfield = true;

    public function __construct($fieldnameorid, $ischarfield) {
        $this->field = $fieldnameorid;
        $this->ischarfield = $ischarfield;
    }

    /**
     * Returns the SQL snippet for this
     * @return stdClass
     */
    public function get_sql_snippet() {

        // If the list of values is empty and we are not interested in empty fields,
        // then this rule surprisingly returns all users because any string is interpreted later as no restriction.
        if ($this->equal != COHORT_RULES_OP_IN_ISEMPTY and count($this->listofvalues) == 0) {
            // TODO TL-7094 This needs to use sql snippet stdClass instead, for now this string means all users.
            return '1=0';
        }

        return $this->construct_sql_snippet($this->field, ($this->equal ? '' : 'not'), $this->listofvalues);
    }

    /**
     * Returns the SQL snippet and params base on the operator
     * @param int $operator one of the COHORT_RULES_OP_IN_ constants
     * @param string $query the field passed to search_get_keyword_where_clause_options
     * @param array $lov list of values
     * @param string $defaultdata optional default value
     * @return stdClass
     */
    public function get_query_base_operator($operator, $query, $lov, $defaultdata = null) {
        global $CFG;
        require_once($CFG->dirroot.'/totara/core/searchlib.php');

        // Create object to be returned
        $sqlhandler = new stdClass();

        // Case operator
        switch ($operator) {
            case COHORT_RULES_OP_IN_CONTAINS:
                list($sqlhandler->sql, $sqlhandler->params) = search_get_keyword_where_clause_options($query, $lov);
                break;
            case COHORT_RULES_OP_IN_NOTCONTAIN:
                list($sql, $params) = search_get_keyword_where_clause_options($query, $lov, true, 'contains');
                if (isset($defaultdata)) {
                    list($defaultsql, $defaultparams) = search_get_keyword_where_clause_options($defaultdata, $lov, true, 'contains', true);
                    $sqlhandler->sql = "( (({$query}) IS NULL AND ($defaultsql)) OR (({$query}) IS NOT NULL AND ({$sql})) )";
                    $sqlhandler->params = array_merge($params, $defaultparams);
                } else {
                    list($sqlhandler->sql, $sqlhandler->params) = array("(({$query}) IS NULL OR ({$sql}))", $params);
                }
                break;
            case COHORT_RULES_OP_IN_ISEQUALTO:
                list($sqlhandler->sql, $sqlhandler->params) = search_get_keyword_where_clause_options($query, $lov, false, 'equal');
                break;
            case COHORT_RULES_OP_IN_STARTSWITH:
                list($sqlhandler->sql, $sqlhandler->params) = search_get_keyword_where_clause_options($query, $lov, false, 'startswith');
                break;
            case COHORT_RULES_OP_IN_ENDSWITH:
                list($sqlhandler->sql, $sqlhandler->params) = search_get_keyword_where_clause_options($query, $lov, false, 'endswith');
                break;
            case COHORT_RULES_OP_IN_ISEMPTY:
                list($sqlhandler->sql, $sqlhandler->params) = array("({$query} = :empty OR ({$query}) IS NULL)", array('empty' => ''));
                break;
            case COHORT_RULES_OP_IN_NOTEQUALTO:
                list($sql, $params) = search_get_keyword_where_clause_options($query, $lov, true, 'notequal');
                if (isset($defaultdata)) {
                    list($defaultsql, $defaultparams) = search_get_keyword_where_clause_options($defaultdata, $lov, true, 'notequal', true);
                    $sqlhandler->sql = "( (({$query}) IS NULL AND ($defaultsql)) OR (({$query}) IS NOT NULL AND ({$sql})) )";
                    $sqlhandler->params = array_merge($params, $defaultparams);
                } else {
                    list($sqlhandler->sql, $sqlhandler->params) = array("(({$query}) IS NULL OR ({$sql}))", $params);
                }
                break;
            default:
                list($sqlhandler->sql, $sqlhandler->params) = array('', array());
                break;
        }

        return $sqlhandler;

    }

    /**
     * Concatenates together some constants and the cleaned-up variables to return the SQL snippet
     * @param $fieldname str
     * @param $not str
     * @param $lov str
     */
    protected abstract function construct_sql_snippet($field, $not, $lov);
}

/**
 * SQL snippet for a field of the mdl_user table.
 * @author aaronw
 */
class cohort_rule_sqlhandler_in_userfield extends cohort_rule_sqlhandler_in {
    protected function construct_sql_snippet($field, $not, $lov) {
        global $DB, $CFG;
        require_once($CFG->dirroot.'/totara/cohort/rules/settings.php');

        if ($this->fielddatatype === COHORT_RULES_TYPE_MENU) {
            $sqlhandler = new stdClass();
            list($sqlin, $params) = $DB->get_in_or_equal($lov, SQL_PARAMS_NAMED, 'iu'.$this->ruleid, ($not != 'not'));
            $sqlhandler->sql = "u.{$field} {$sqlin}";
            $sqlhandler->params = $params;
        } else {
            $query = "u.{$field}";
            $sqlhandler = $this->get_query_base_operator($this->equal, $query, $lov);
        }
        return $sqlhandler;
    }
}

class cohort_rule_sqlhandler_in_userfield_char extends cohort_rule_sqlhandler_in_userfield {
    protected $fielddatatype;

    public function __construct($field, $datatype) {
        $this->fielddatatype = $datatype;
        parent::__construct($field, true);
    }
}

class cohort_rule_sqlhandler_in_userfield_int extends cohort_rule_sqlhandler_in_userfield {
    public function __construct($field) {
        parent::__construct($field, false);
    }
}

/**
 * SQL snippet for a user custom field
 * @author aaronw
 * @deprecated use custom_field_sqlhandler instead.
 */
class cohort_rule_sqlhandler_in_usercustomfield extends cohort_rule_sqlhandler_in {

    protected $fielddatatype;

    public function __construct($field, $datatype) {
        debugging('cohort_rule_sqlhandler_in_usercustomfield deprecated; use custom_field_sqlhandler instead', DEBUG_DEVELOPER);

        $this->fielddatatype = $datatype;
        // Always a char field
        parent::__construct($field, true);
    }

    protected function construct_sql_snippet($field, $not, $lov) {
        global $DB;
        // If $field is int comes from a menu.
        if (is_int($field)) {
            // TODO TL-7096 this should be possible to simplify this for menu fields.
            list($sqlin, $params) = $DB->get_in_or_equal($lov, SQL_PARAMS_NAMED, 'icu' . $this->ruleid, ($not != 'not'));
            $sqlhandler = new stdClass();
            $sqlhandler->sql = "EXISTS (
                                    SELECT 1
                                    FROM {user_info_data} usinda
                                    WHERE usinda.userid = u.id
                                      AND usinda.fieldid = {$field}
                                      AND {$DB->sql_compare_text('usinda.data')} {$sqlin}
                               )";
            if ($this->fielddatatype === 'checkbox') {
                // Bring the default value of this checkbox.
                $deafultvalue = $DB->get_field('user_info_field', 'defaultdata', array('id' => $field));
                $rule = reset($lov);
                // Consider unset values for checkbox custom field.
                if ($deafultvalue != $rule) {
                    $sqlhandler->sql = "EXISTS (
                                            SELECT 1
                                            FROM {user_info_data} usinda
                                            WHERE usinda.userid = u.id
                                              AND usinda.fieldid = {$field}
                                              AND {$DB->sql_compare_text('usinda.data')} = '{$rule}'
                                       )";
                } else {
                    $sqlhandler->sql = "NOT EXISTS (
                                            SELECT 1
                                            FROM {user_info_data} usinda
                                            WHERE usinda.userid = u.id
                                              AND usinda.fieldid = {$field}
                                              AND {$DB->sql_compare_text('usinda.data')} != '{$rule}'
                                       )";
                }
            }
            $sqlhandler->params = $params;
        } else {
            $name = $DB->sql_compare_text('name', 255) . ' = ?';
            $fieldrecord = $DB->get_record_select('user_info_field', $name, array($field), 'id,defaultdata');
            $uniqueparam = $DB->get_unique_param('fieldid');
            $sql = "EXISTS (
                            SELECT 1
                            FROM {user} usr
                            LEFT JOIN {user_info_data} usinda
                              ON usinda.fieldid = :{$uniqueparam} AND usinda.userid = usr.id
                            WHERE u.id = usr.id
                              AND (";
            $query = 'usinda.data';
            $sqlhandler = $this->get_query_base_operator($this->equal, $query, $lov, $fieldrecord->defaultdata);
            $sqlhandler->sql = $sql . $sqlhandler->sql . " ) ) ";
            $sqlhandler->params[$uniqueparam] = $fieldrecord->id;
        }
        return $sqlhandler;
    }
}

/**
 * SQL snippet for a list of values that matches the "id" field of a table
 */
abstract class cohort_rule_sqlhandler_in_hierarchyid extends cohort_rule_sqlhandler_in {
    public $params = array(
        'equal'=>0,
        'includechildren'=>0,
        'listofvalues'=>1
    );

    /**
     * No constructor variables necessary. It's always on one particular column,
     * and the field is always an int
     */
    public function __construct() {
        parent::__construct(false, false);
    }

    /**
     * This one's a little strange... if they didn't tick the "include children" checkbox, then it
     * will produce an "in..." SQL snippet. If they do tick the "include children" checkbox, then it
     * acts significantly differently, needing to "like % or like % or like %"...
     * @return str
     */
    // let it use the parent get_sql_snippet in order to parse the $listofvalues into the necessary $lov

    protected function construct_sql_snippet($field, $not, $lov) {
        $sqlhandler = $this->construct_sql_snippet_firsthalf($not, $lov);
        if ($this->includechildren) {
            $sqlhandler->sql .= $this->construct_sql_snippet_pathclause();
        }
        $sqlhandler->sql .= $this->construct_sql_snippet_ending();
        return $sqlhandler;
    }

    protected abstract function construct_sql_snippet_firsthalf($not, $lov);
    protected function construct_sql_snippet_pathclause() {
        $likestart = "OR " . $this->likefield() . " LIKE '%/";
        $likeend = "/%'";
        $likeglue = "{$likeend} {$likestart}";
        return $likestart . implode($likeglue, $this->listofvalues) . $likeend . " ";
    }
    protected function construct_sql_snippet_ending() {
        return "))";
    }
    protected abstract function likefield();
}

/**
 * SQL Snippet for a list of positions by ID (matching any of the user's job assignments)
 */
class cohort_rule_sqlhandler_in_listofids_allpos extends cohort_rule_sqlhandler_in_hierarchyid {

    protected function likefield() {
        return 'pos.path';
    }

    protected function construct_sql_snippet_firsthalf($not, $lov) {
        global $DB;
        $sqlhandler = new stdClass();
        list($sqlin, $params) = $DB->get_in_or_equal($lov, SQL_PARAMS_NAMED, 'ilp'.$this->ruleid);
        $sqlhandler->sql = "{$not} exists ("
                ."select 1 from {job_assignment} ja "
                ."inner join {pos} pos "
                ."on ja.positionid=pos.id "
                ."where ja.userid=u.id "
                ."and (ja.positionid {$sqlin} ";
        $sqlhandler->params = $params;
        return $sqlhandler;
    }
}

/**
 * SQL Snippet for a list of organisations by ID (matching any of the user's job assignments)
 */
class cohort_rule_sqlhandler_in_listofids_allorg extends cohort_rule_sqlhandler_in_hierarchyid {

    protected function likefield() {
        return 'org.path';
    }

    protected function construct_sql_snippet_firsthalf($not, $lov) {
        global $DB;
        $sqlhandler = new stdClass();
        list($sqlin, $params) = $DB->get_in_or_equal($lov, SQL_PARAMS_NAMED, 'ilo'.$this->ruleid);
        $sqlhandler->sql = "{$not} exists ("
                ."select 1 from {job_assignment} ja "
                ."inner join {org} org "
                ."on ja.organisationid=org.id "
                ."where ja.userid=u.id "
                ."and (ja.organisationid {$sqlin} ";
        $sqlhandler->params = $params;
        return $sqlhandler;
    }
}

/**
 * SQL snippet for a field of mdl_jobs_assignment, applied across all the users job assignments.
 * @author Aldo Paradiso (aparadiso@multamedio.de)
 */
class cohort_rule_sqlhandler_in_alljobassignfield extends cohort_rule_sqlhandler_in {
    protected function construct_sql_snippet($field, $not, $lov) {
        global $DB;

        $sql = "EXISTS (SELECT 1
                          FROM {job_assignment} ja
                         WHERE ja.userid = u.id
                           AND ( ";

        $query = "ja.{$field}";

        if ($this->ischarfield) {
            $sqlhandler = $this->get_query_base_operator($this->equal, $query, $lov);
            $sqlhandler->sql = $sql . $sqlhandler->sql . " ) ) ";
        } else {
            $sqlhandler = new stdClass();
            list($sqlin, $params) = $DB->get_in_or_equal($lov, SQL_PARAMS_NAMED, 'iu'.$this->ruleid, ($not != 'not'));
            $sqlhandler->sql = "{$sql} {$query} {$sqlin} ) ) ";
            $sqlhandler->params = $params;
        }

        return $sqlhandler;
    }
}

/**
 * SQL snippet for a field from mdl_pos, joined across all of the users job assignments.
 */
class cohort_rule_sqlhandler_in_posfield extends cohort_rule_sqlhandler_in {
    protected function construct_sql_snippet($field, $not, $lov) {
        global $DB;

        $sql = "EXISTS (SELECT 1
                          FROM {job_assignment} ja
                    INNER JOIN {pos} p
                            ON ja.positionid = p.id
                         WHERE ja.userid = u.id
                           AND ( ";

        $query = "p.{$field}";

        if ($this->ischarfield) {
            $sqlhandler = $this->get_query_base_operator($this->equal, $query, $lov);
            $sqlhandler->sql = $sql . $sqlhandler->sql . " ) ) ";
        } else {
            $sqlhandler = new stdClass();
            list($sqlin, $params) = $DB->get_in_or_equal($lov, SQL_PARAMS_NAMED, 'iu'.$this->ruleid, ($not != 'not'));
            $sqlhandler->sql = "{$sql} {$query} {$sqlin} ) ) ";
            $sqlhandler->params = $params;
        }

        return $sqlhandler;
    }
}
/**
 * SQL snippet for a pos custom field, for the user's position across all of their job assignments.
 */
class cohort_rule_sqlhandler_in_poscustomfield extends cohort_rule_sqlhandler_in {
    /**
     * These fields are always char
     */
    public function __construct($field, $datatype) {

        $this->fielddatatype = $datatype;
        parent::__construct($field, true);
    }

    protected function construct_sql_snippet($field, $not, $lov) {

        $sql = "EXISTS (
                        SELECT 1
                        FROM {job_assignment} ja
                        INNER JOIN {pos_type_info_data} ptid
                          ON ja.positionid = ptid.positionid
                        WHERE ja.userid = u.id
                          AND ptid.fieldid = {$field}
                          AND ( ";
        $query = " ptid.data";
        $equal = $this->equal;
        if ($this->fielddatatype == 'menu') {
            $equal = $this->equal == COHORT_RULES_OP_IN_EQUAL ? COHORT_RULES_OP_IN_ISEQUALTO : COHORT_RULES_OP_IN_NOTEQUALTO;
        }
        $sqlhandler = $this->get_query_base_operator($equal, $query, $lov);
        $sqlhandler->sql = $sql . $sqlhandler->sql . " ) ) ";
        return $sqlhandler;
    }
}

/**
 * @deprecated Since v9.0
 *
 * This class was deprecated as part of the multiple jobs patch and replaced with
 * the cohort_rule_sqlhandler_in_orgfield class, please use that instead.
 */
class cohort_rule_sqlhandler_in_posorgfield extends cohort_rule_sqlhandler_in_orgfield {

    public function __construct(){
        debugging('Class cohort_rule_sqlhandler_in_posorgfield has been replaced and is now deprecated.
            Please use the cohort_rule_sqlhandler_in_orgfield class instead', DEBUG_DEVELOPER);
        parent::__construct();
    }
}

/**
 * SQL snippet for a field from mdl_org, joined across all of the users job assignments.
 */
class cohort_rule_sqlhandler_in_orgfield extends cohort_rule_sqlhandler_in {
    protected function construct_sql_snippet($field, $not, $lov) {
        global $DB;

        $sql = "EXISTS (SELECT 1
                          FROM {job_assignment} ja
                    INNER JOIN {org} o
                            ON ja.organisationid = o.id
                         WHERE ja.userid = u.id
                           AND ( ";

        $query = "o.{$field}";

        if ($this->ischarfield) {
            $sqlhandler = $this->get_query_base_operator($this->equal, $query, $lov);
            $sqlhandler->sql = $sql . $sqlhandler->sql . " ) ) ";
        } else {
            $sqlhandler = new stdClass();
            list($sqlin, $params) = $DB->get_in_or_equal($lov, SQL_PARAMS_NAMED, 'iu'.$this->ruleid, ($not != 'not'));
            $sqlhandler->sql = "{$sql} {$query} {$sqlin} ) ) ";
            $sqlhandler->params = $params;
        }

        return $sqlhandler;
    }
}

/**
 * @deprecated Since v9.0
 *
 * This class was deprecated as part of the multiple jobs patch and replaced with
 * the cohort_rule_sqlhandler_in_orgcustomfield class, please use that instead.
 */
class cohort_rule_sqlhandler_in_posorgcustomfield extends cohort_rule_sqlhandler_in_orgcustomfield {

    public function __construct(){
        debugging('Class cohort_rule_sqlhandler_in_posorgcustomfield has been replaced and is now deprecated.
            Please use the cohort_rule_sqlhandler_in_orgcustomfield class instead', DEBUG_DEVELOPER);
        parent::__construct();
    }
}

/**
 * SQL snippet for a field from mdl_org_info_field, joined across all of the users job assignments.
 */
class cohort_rule_sqlhandler_in_orgcustomfield extends cohort_rule_sqlhandler_in {
    /**
     * These fields are always char
     */
    public function __construct($field, $datatype) {
        $this->fielddatatype = $datatype;
        parent::__construct($field, true);
    }

    protected function construct_sql_snippet($field, $not, $lov) {
        $sql = "EXISTS (SELECT 1
                          FROM {job_assignment} ja
                    INNER JOIN {org_type_info_data} otid
                            ON ja.organisationid = otid.organisationid
                         WHERE ja.userid = u.id
                           AND otid.fieldid = {$field}
                           AND ( ";
        $query = " otid.data";
        $equal = $this->equal;

        // A menu custom field allows two operators when it is used as a filter
        // in dynamic audience rules: equals and not equals. In the UI, when the
        // user selects an operator, it is submitted as an enumerated integer to
        // the filter module. The filter module however, works with a completely
        // different set of enumerations. Therefore, this code exists to map the
        // UI enumeration into the correct filter module enumeration.
        if ($this->fielddatatype == 'menu') {
            $equal = $this->equal == COHORT_RULES_OP_IN_EQUAL ? COHORT_RULES_OP_IN_ISEQUALTO : COHORT_RULES_OP_IN_NOTEQUALTO;
        }
        $sqlhandler = $this->get_query_base_operator($equal, $query, $lov);
        $sqlhandler->sql = $sql . $sqlhandler->sql . " ) ) ";

        return $sqlhandler;
    }
}
