<?php

trait quiz_completions_columns
{
    private function add_quiz_fields_to_columns(&$columns, $quizTable = 'quiz') {
        $columns[] = new rb_column_option(
            'quiz',
            'id',
            get_string('quiz_id', 'rb_source_detailed_quiz_completion'),
            "$quizTable.id",
            ['joins' => $quizTable]
        );
        $columns[] = new rb_column_option(
            'quiz',
            'name',
            get_string('quiz_title', 'rb_source_detailed_quiz_completion'),
            "$quizTable.name",
            ['joins' => $quizTable]
        );
    }

    private function add_quiz_attempts_fields_to_columns(
        &$columns,
        $quizAttemptTable = 'base',
        $quizTable = 'quiz'
    ) {
        global $DB;

        $columns[] = new rb_column_option(
            'quiz_attempt',
            'id',
            get_string('quiz_attempt_id', 'rb_source_detailed_quiz_completion'),
            "$quizAttemptTable.id",
            ['joins' => $quizAttemptTable]
        );
        $columns[] = new rb_column_option(
            'quiz_attempt',
            'attempt',
            get_string('quiz_attempt_number', 'rb_source_detailed_quiz_completion'),
            "$quizAttemptTable.attempt",
            ['joins' => $quizAttemptTable]
        );
        $columns[] = new rb_column_option(
            'quiz_attempt',
            'state',
            get_string('quiz_attempt_state', 'rb_source_detailed_quiz_completion'),
            "$quizAttemptTable.state",
            [
                'joins'       => $quizAttemptTable,
                'displayfunc' => 'quiz_attempt_state',
                'extrafields' => ['id' => "$quizAttemptTable.id"],
            ]
        );
        $columns[] = new rb_column_option(
            'quiz_attempt',
            'sumgrades',
            get_string('quiz_attempt_sumgrades', 'rb_source_detailed_quiz_completion'),
            (
                "CASE WHEN $quizTable.sumgrades > 0 AND $quizAttemptTable.sumgrades > 0 THEN (
                    CASE WHEN $quizAttemptTable.sumgrades IS NULL THEN 0 ELSE $quizAttemptTable.sumgrades END * 
                    CASE WHEN $quizTable.grade IS NULL THEN 0 ELSE $quizTable.grade END /
                    CASE WHEN $quizTable.sumgrades IS NULL THEN 0 ELSE $quizTable.sumgrades END 
                ) ELSE 0 END"
            ),
            [
                'joins'       => [$quizAttemptTable, $quizTable],
                'extrafields' => [
                    'sumgrades'     => "$quizTable.sumgrades",
                    'grade'         => "$quizTable.grade",
                    'decimalpoints' => "$quizTable.decimalpoints"
                ],
                'displayfunc' => 'quiz_attempt_sumgrades'
            ]
        );
        $columns[] = new rb_column_option(
            'quiz_attempt',
            'timestart',
            get_string('quiz_attempt_timestart', 'rb_source_detailed_quiz_completion'),
            "$quizAttemptTable.timestart",
            ['joins' => $quizAttemptTable, 'displayfunc' => 'nice_datetime_seconds']
        );
        $columns[] = new rb_column_option(
            'quiz_attempt',
            'timefinish',
            get_string('quiz_attempt_timefinish', 'rb_source_detailed_quiz_completion'),
            "$quizAttemptTable.timefinish",
            ['joins' => $quizAttemptTable, 'displayfunc' => 'nice_datetime_seconds']
        );
        $columns[] = new rb_column_option(
            'quiz_attempt',
            'duration',
            get_string('quiz_attempt_duration', 'rb_source_detailed_quiz_completion'),
            "($quizAttemptTable.timefinish - $quizAttemptTable.timestart)",
            ['joins' => $quizAttemptTable, 'displayfunc' => 'quiz_attempt_duration']
        );
    }

    public function rb_display_quiz_attempt_sumgrades($rawgrade, $row)
    {
        // $grade = null;

        // if (is_null($rawgrade)) {
        //     $grade = null;
        // } else if ($row->sumgrades >= 0.000005) {
        //     $grade = $rawgrade * $row->grade / $row->sumgrades;
        // } else {
        //     $grade = 0;
        // }

        return format_float($rawgrade, $row->decimalpoints);
    }

    public function rb_display_quiz_attempt_duration($duration)
    {
        return $duration > 0 ? format_time($duration) : '';
    }

    public function rb_display_quiz_attempt_state($state, $row)
    {
        global $CFG;
        $id = $row->id;
        return "<a target=\"_blank\" href=\"{$CFG->wwwroot}/mod/quiz/review.php?attempt=$id\">".
                get_string("state$state", 'mod_quiz').
            '</a>';
    }
}
