<?php

trait course_completion_joins
{
    private function add_course_completion_tables_to_joinlist(
        &$joinlist,
        $courseJoin = 'base',
        $courseField = 'id',
        $userJoin = null,
        $userField = null
    ) {
        global $DB;

        $studentRoleId = $DB->get_record('role', ['shortname' => 'student'])->id;
        $requiredJoins = ['course_user_enrolments'];
        $joinCondition = "course_completion.course = $courseJoin.$courseField";
        if ($userJoin && $userField) {
            array_push($requiredJoins, $userJoin);
            $joinCondition .= " AND course_completion.userid = $userJoin.$userField";
        }

        $joinlist = array_merge($joinlist, [
            new rb_join(
                'course_user_enrolments',
                'INNER',
                '(SELECT userid, courseid FROM {enrol} AS e INNER JOIN {user_enrolments} AS ue ON ue.enrolid = e.id GROUP BY courseid, userid)',
                "course_user_enrolments.courseid = $courseJoin.$courseField",
                REPORT_BUILDER_RELATION_MANY_TO_MANY,
                [$courseJoin]
            ),
            new rb_join(
                'course_completion',
                'LEFT',
                '{course_completions}',
                $joinCondition,
                REPORT_BUILDER_RELATION_ONE_TO_ONE,
                $requiredJoins
            ),
            new rb_join(
                'completion_organisation',
                'LEFT',
                '{org}',
                'completion_organisation.id = course_completion.organisationid',
                REPORT_BUILDER_RELATION_ONE_TO_ONE,
                'course_completion'
            ),
            new rb_join(
                'completion_position',
                'LEFT',
                '{pos}',
                'completion_position.id = course_completion.positionid',
                REPORT_BUILDER_RELATION_ONE_TO_ONE,
                'course_completion'
            ),
            new rb_join(
                'criteria',
                'LEFT',
                '{course_completion_criteria}',
                '(criteria.course = course_completion.course AND ' .
                    'criteria.criteriatype = ' .
                    COMPLETION_CRITERIA_TYPE_GRADE . ')',
                REPORT_BUILDER_RELATION_ONE_TO_ONE,
                'course_completion'
            ),
            new rb_join(
                'critcompl',
                'LEFT',
                '{course_completion_crit_compl}',
                '(critcompl.userid = course_completion.userid AND ' .
                    'critcompl.criteriaid = criteria.id AND ' .
                    '(critcompl.deleted IS NULL OR critcompl.deleted = 0))',
                REPORT_BUILDER_RELATION_ONE_TO_ONE,
                'criteria'
            ),
            new rb_join(
                'grade_items',
                'LEFT',
                '{grade_items}',
                '(grade_items.courseid = course_completion.course AND ' .
                    'grade_items.itemtype = \'course\')',
                REPORT_BUILDER_RELATION_ONE_TO_ONE,
                'course_completion'
            ),
            new rb_join(
                'grade_grades',
                'LEFT',
                '{grade_grades}',
                '(grade_grades.itemid = grade_items.id AND ' .
                    'grade_grades.userid = course_completion.userid)',
                REPORT_BUILDER_RELATION_ONE_TO_ONE,
                'grade_items',
                'course_completion'
            ),
            new rb_join(
                'context',
                'INNER',
                '{context}',
                "(course_completion.course = context.instanceid 
                    AND context.contextlevel = ".CONTEXT_COURSE.")",
                REPORT_BUILDER_RELATION_ONE_TO_MANY,
                'course_completion'
            ),
            new rb_join(
                'role_assignments',
                'INNER',
                '{role_assignments}',
                "(context.id = role_assignments.contextid
                    AND role_assignments.roleid = $studentRoleId)",
                REPORT_BUILDER_RELATION_ONE_TO_MANY,
                'context'
            ),
        ]);
    }
}
