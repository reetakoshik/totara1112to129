<?php

trait quiz_completions_joins
{
	private function add_quiz_table_to_joinlist(&$joinlist, $join = 'base', $field = 'quiz')
    {
        global $DB;
        
        $quizid = $DB->get_record_sql("
            SELECT id 
            FROM {modules} 
            WHERE name = 'quiz'"
        )->id;

        $joinlist[] =  new rb_join(
            'quiz',
            'INNER',
            '{quiz}',
            "quiz.id = $join.$field",
            REPORT_BUILDER_RELATION_MANY_TO_ONE,
            $join
        );

        $joinlist[] =  new rb_join(
            'course_module',
            'INNER',
            '{course_modules}',
            "quiz.id = course_module.instance AND course_module.module = $quizid",
            REPORT_BUILDER_RELATION_MANY_TO_ONE,
            $join
        );
    }
}
