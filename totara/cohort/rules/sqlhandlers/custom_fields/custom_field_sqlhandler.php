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

require_once($CFG->dirroot . '/totara/cohort/rules/sqlhandlers/inlist.php');

require_once($CFG->dirroot . '/totara/cohort/rules/sqlhandlers/custom_fields/custom_fields.php');


/**
 * Handles SQL code generation for custom fields. This is a drop in replacement
 * for the cohort_rule_sqlhandler_in_usercustomfield class.
 *
 * @todo Currently this only handles menu, text and checkbox custom fields; it
 *       can be enhanced to deal with other custom fields with minimum effort.
 */
final class custom_field_sqlhandler extends cohort_rule_sqlhandler_in {
    /**
     * Custom field type; needed since generated SQL could be dependent of the
     * type of custom field being considered.
     */
    private $field_type = null;

    /**
     * The UI uses two different sets of operator enumerations while the backend
     * only uses one. This allows this class to translate to the "correct" one
     * as necessary.
     */
    private $uses_obsolete_enumeration = false;

    /**
     * Default constructor.
     *
     * @param int $field_id associated custom field object.
     * @param string $field_type one of the custom_fields::FIELD_TYPE constants.
     * @param boolean $uses_obsolete_enumeration if this is true, indicates this
     *        instance receives the obsolete operator enumerator from the UI.
     */
    public function __construct(
        $field_id,
        $field_type,
        $uses_obsolete_enumeration
    ) {
        if (is_null($field_id)) {
            throw new coding_exception('custom field ID is empty');
        }
        if (!is_int($field_id)) {
            throw new coding_exception('custom field ID is not numeric');
        }
        if (empty($field_type)) {
            throw new coding_exception('custom field type is empty');
        }
        if (is_null($uses_obsolete_enumeration)) {
            throw new coding_exception(
                'custom field obsolete enumeration is empty'
            );
        }

        parent::__construct($field_id, true);

        $this->field_type = $field_type;
        $this->uses_obsolete_enumeration = filter_var(
            $uses_obsolete_enumeration, FILTER_VALIDATE_BOOLEAN
        );
    }

    /**
     * Constructs an SQL clause to compute the members of a dynamic cohort.
     *
     * Notes:
     * - The existing design to dynamically generate cohort members requires the
     *   joining of complex SQL strings from disparate sources and hoping they
     *   fit syntactically together - complete with brackets, escaping, dangling
     *   phrases and all. It is a not a good design even in the best of times.
     * - Note these caveats:
     *   - The SQL snippet this function returns contains "magic" references eg
     *     'u.id' that are *referenced from final SQL statement*. Very bad since
     *     it makes unwarranted assumptions and forces hardcoding.
     *   - Even though this class needs to "know" about the final SQL, it cannot
     *     control it. That SQL can still fail due to problems elsewhere.
     *   - Maintenance is made more difficult because you have to trace through
     *     bits and pieces everywhere while simultaneously trying to hold all of
     *     them in your head. There is no place in the code where you can see an
     *     assembled SQL statement in clear.
     *
     * @param int $field custom field ID.
     * @param string $unused unused parameter, but still here since it is in the
     *        parent class method signature.
     * @param array $values values to match against the custom field value.
     *
     * @return \stdClass object with two fields:
     *        - sql: which contains the sql snippet to be incorporated in to the
     *          final SQL
     *        - params: parameters for use with placeholders in the generated
     *          snippet.
     */
    protected function construct_sql_snippet(
        $field,
        $unused,
        $values
    ) {
        // The front end passes in the "wrong" enumeration for some comparisons.
        // Thus need to map the UI value to the proper backend enumeration here.
        $resolved_operator = $this->resolve_operator();

        // Unlike the original cohort_rule_sqlhandler_in_usercustomfield class,
        // this class outsources SQL generation to an external component.
        //
        // Custom fields are conceptually the same whether used for hierarchies,
        // user profiles or anything else. Hence, it makes more sense to isolate
        // common computations in a shared service than to splat it all over the
        // codebase as is the case currently.
        $query = \custom_field_query::user_query(
            $field, $values, $resolved_operator, $this->field_type
        );
        return custom_fields::sql_entities_for($query)->as_sqlhandler_class();
    }

    /**
     * Convenience function to convert from a UI operator enumeration to the
     * proper 'backend' one.
     *
     * Notes:
     * - Amazingly, the front end uses the "correct" comparison enumeration for
     *   some custom fields while using another enumeration for others.
     * - Not only that, the UI also uses both enumerations simultaneously for
     *   custom text fields!
     * - This mess comes about because the UI needs to sometimes show a dialog
     *   with only equals/unequals comparisons and show other operators at other
     *   times. For some reason, instead of reworking the equals/unequals only
     *   dialog to use the "right" enumeration, it was allowed to continue using
     *   the wrong one. Moreover *other parts of the codebase were adjusted to
     *   handle the discrepancy!* So the "wrong" enumeration cannot be removed
     *   without major disruption at this time.
     *
     * @return int the proper backend enumeration.
     */
    private function resolve_operator() {
        // The operator field may be named "$this->equal", but it is really an
        // enumeration for broader types of comparisons eg 'contains', 'empty',
        // etc. Very poorly named but cannot be changed since it inherits from
        // the existing design.
        $operator = $this->equal;

        if (!$this->uses_obsolete_enumeration) {
            return $operator;
        }

        return $operator == COHORT_RULES_OP_IN_EQUAL
            ? COHORT_RULES_OP_IN_ISEQUALTO
            : COHORT_RULES_OP_IN_NOTEQUALTO;
    }
}
