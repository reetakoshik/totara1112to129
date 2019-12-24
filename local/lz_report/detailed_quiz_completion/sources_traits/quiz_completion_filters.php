<?php

trait quiz_completions_filters
{
	private function add_quiz_fields_to_filters(&$filters)
    {
        $filters[] = new rb_filter_option(
            'quiz',
            'name',
            get_string('quiz_title', 'rb_source_detailed_quiz_completion'),
            'text'
        );
        $filters[] = new rb_filter_option(
            'quiz_attempt',
            'state',
            get_string('quiz_attempt_state', 'rb_source_detailed_quiz_completion'),
            'select',
            [
                'selectfunc' => 'attempt_status_list',
                'attributes' => rb_filter_option::select_width_limiter()
            ]
        );
        $filters[] = new rb_filter_option(
            'quiz_attempt',
            'id',
            get_string('quiz_attempt_id', 'rb_source_detailed_quiz_completion'),
            'number'
        );
        $filters[] = new rb_filter_option(
            'quiz_attempt',
            'attempt',
            get_string('quiz_attempt_number', 'rb_source_detailed_quiz_completion'),
            'number'
        );
        $filters[] = new rb_filter_option(
            'quiz_attempt',
            'sumgrades',
            get_string('quiz_attempt_sumgrades', 'rb_source_detailed_quiz_completion'),
            'number'
        );
        $filters[] = new rb_filter_option(
            'quiz_attempt',
            'timestart',
            get_string('quiz_attempt_timestart', 'rb_source_detailed_quiz_completion'),
            'date'
        );
        $filters[] = new rb_filter_option(
            'quiz_attempt',
            'timefinish',
            get_string('quiz_attempt_timefinish', 'rb_source_detailed_quiz_completion'),
            'date'
        );
    }

    public function rb_filter_attempt_status_list()
    {
        return [
            'finished'   => get_string("statefinished", 'mod_quiz'),
            'inprogress' => get_string("stateinprogress", 'mod_quiz'),
        ];
    }
}