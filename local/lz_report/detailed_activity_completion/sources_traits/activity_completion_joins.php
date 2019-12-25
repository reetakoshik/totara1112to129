<?php

trait activity_completion_joins
{
    private function add_activity_table_to_joinlist(&$joins, $table = 'base', $field = 'coursemoduleid')
    {
        $joins[] = new rb_join(
            'course_module',
            'INNER',
            '{course_modules}',
            "course_module.id = $table.$field",
            REPORT_BUILDER_RELATION_MANY_TO_ONE,
            $table
        );

        // $joins[] = new rb_join(
        //     'enrol',
        //     'INNER',
        //     '{enrol}',
        //     "enrol.courseid = course_module.course",
        //     REPORT_BUILDER_RELATION_MANY_TO_ONE,
        //     ['course_module']
        // );

        // $joins[] = new rb_join(
        //     'user_enrolments',
        //     'INNER',
        //     '{user_enrolments}',
        //     "user_enrolments.enrolid = course_enrol.id",
        //     REPORT_BUILDER_RELATION_MANY_TO_ONE,
        //     ['course_enrol']
        // );

        $joins[] = new rb_join(
            'course_module_completion',
            'LEFT',
            '{course_modules_completion}',
            "course_module_completion.coursemoduleid = course_module.id and course_module_completion.userid = auser.id",
            REPORT_BUILDER_RELATION_MANY_TO_ONE,
            [$table, 'course_module', 'auser']
        );

        $joins[] = new rb_join(
            'module',
            'INNER',
            '{modules}',
            "module.id = course_module.module",
            REPORT_BUILDER_RELATION_MANY_TO_ONE,
            [$table, 'course_module']
        );
    }
}
